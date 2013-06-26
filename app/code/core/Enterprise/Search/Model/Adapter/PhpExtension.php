<?php
/**
 * Magento Enterprise Edition
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Magento Enterprise Edition License
 * that is bundled with this package in the file LICENSE_EE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.magentocommerce.com/license/enterprise-edition
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magentocommerce.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Magento to newer
 * versions in the future. If you wish to customize Magento for your
 * needs please refer to http://www.magentocommerce.com for more information.
 *
 * @category    Enterprise
 * @package     Enterprise_Search
 * @copyright   Copyright (c) 2010 Magento Inc. (http://www.magentocommerce.com)
 * @license     http://www.magentocommerce.com/license/enterprise-edition
 */

/**
 * Solr search engine adapter
 *
 * @category   Enterprise
 * @package    Enterprise_Search
 * @author     Magento Core Team <core@magentocommerce.com>
 */
class Enterprise_Search_Model_Adapter_PhpExtension extends Enterprise_Search_Model_Adapter_Solr_Abstract
{
    /**
     * Object name used to create solr document object
     *
     * @var string
     */
    protected $_clientDocObjectName = 'SolrInputDocument';

    /**
     * Initialize connect to Solr Client
     *
     * @param array $options
     */
    public function __construct($options = array())
    {
        try {
            if (!extension_loaded('solr')) {
                throw new Exception('Solr extension not enabled!');
            }
            $this->_connect($options);
        } catch (Exception $e) {
            Mage::logException($e);
            Mage::throwException(Mage::helper('enterprise_search')->__('Unable to perform search because of search engine missed configuration.'));
        }
    }

    /**
     * Connect to Solr Client by specified options that will be merged with default
     *
     * @param  array $options
     * @return SolrClient
     */
    protected function _connect($options = array())
    {
        $helper = Mage::helper('enterprise_search');
        $def_options = array(
            'hostname' => $helper->getSolrConfigData('server_hostname'),
            'login'    => $helper->getSolrConfigData('server_username'),
            'password' => $helper->getSolrConfigData('server_password'),
            'port'     => $helper->getSolrConfigData('server_port'),
            'timeout'  => $helper->getSolrConfigData('server_timeout'),
            'path'     => $helper->getSolrConfigData('server_path')
        );
        $options = array_merge($def_options, $options);

        try {
            $this->_client = new SolrClient($options);
        } catch (Exception $e) {
            Mage::logException($e);
        }

        return $this->_client;
    }

    /**
     * Simple Search interface
     *
     * @param string|array $query   The raw query string
     * @param array $params Params  can be specified like this:
     *        'offset'            - The starting offset for result documents
     *        'limit              - The maximum number of result documents to return
     *        'sort_by'           - Sort field, can be just sort field name (and asceding order will be used by default),
     *                              or can be an array of arrays like this: array('sort_field_name' => 'asc|desc')
     *                              to define sort order and sorting fields.
     *                              If sort order not asc|desc - asceding will used by default
     *        'fields'            - Fields names which are need to be retrieved from found documents
     *        'solr_params'       - Key / value pairs for other query parameters (see Solr documentation),
     *                              use arrays for parameter keys used more than once (e.g. facet.field)
     *        'locale_code'       - Locale code, it used to define what suffix is needed for text fields,
     *                              by which will be performed search request and sorting
     *        'ignore_handler'    - Flag that allows to ignore handler (qt) and make multifield search
     *
     * @see Enterprise_Search_Model_Adapter_HttpStream::_getLanguageCodeByLocaleCode()
     * @return array
     */
    protected function _search($query, $params = array())
    {
        /**
         * Hard code to prevent Solr bug:
         * Bug #17009 Creating two SolrQuery objects leads to wrong query value
         * @link http://pecl.php.net/bugs/bug.php?id=17009&edit=1
         * @link http://svn.php.net/viewvc?view=revision&revision=293379
         */
        if ((int)('1' . str_replace('.', '', solr_get_version())) < 1099) {
            $this->_connect();
        }

        $searchConditions = $this->prepareSearchConditions($query);
        if (!$searchConditions) {
            return array();
        }

        $_params = $this->_defaultQueryParams;
        if (is_array($params) && !empty($params)) {
            $_params = array_intersect_key($params, $_params) + array_diff_key($_params, $params);
        }

        $offset = (isset($_params['offset'])) ? (int)$_params['offset'] : 0;
        $limit  = (isset($_params['limit']))
            ? (int)$_params['limit']
            : Enterprise_Search_Model_Adapter_Solr_Abstract::DEFAULT_ROWS_LIMIT;

        /**
         * Now supported search only in fulltext field
         * By default in Solr  set <defaultSearchField> is "fulltext"
         * When language fields need to be used, then perform search in appropriate field
         */
        $languageCode   = $this->_getLanguageCodeByLocaleCode($params['locale_code']);
        $languageSuffix = ($languageCode) ? '_' . $languageCode : '';

        $solrQuery = new SolrQuery();
        $solrQuery->setStart($offset)->setRows($limit);

        $solrQuery->setQuery($searchConditions);

        if (!is_array($_params['fields'])) {
            $_params['fields'] = array($_params['fields']);
        }

        if (!is_array($_params['solr_params'])) {
            $_params['solr_params'] = array($_params['solr_params']);
        }

        /**
         * Add sort fields
         */
        $sortFields = $this->_prepareSortFields($_params['sort_by']);
        foreach ($sortFields as $sortField) {
            $sortField['sortType'] = ($sortField['sortType'] == 'desc') ? SolrQuery::ORDER_DESC : SolrQuery::ORDER_ASC;
            $solrQuery->addSortField($sortField['sortField'], $sortField['sortType']);
        }

        /**
         * Fields to retrieve
         */
        if ($limit && !empty($_params['fields'])) {
            foreach ($_params['fields'] as $field) {
                $solrQuery->addField($field);
            }
        }

        /**
         * Now supported search only in fulltext and name fields based on dismax requestHandler (named as magento_lng).
         * Using dismax requestHandler for each language make matches in name field
         * are much more significant than matches in fulltext field.
         */
        if ($_params['ignore_handler'] !== true) {
            $_params['solr_params']['qt'] = 'magento' . $languageSuffix;
        }

        /**
         * Facets search
         */
        $useFacetSearch = (isset($params['solr_params']['facet']) && $params['solr_params']['facet'] == 'on');
        if ($useFacetSearch) {
            $_params['solr_params'] += $this->_prepareFacetConditions($params['facet']);
        }

        /**
         * Suggestions search
         */
        $useSpellcheckSearch = (isset($params['solr_params']['spellcheck']) && $params['solr_params']['spellcheck'] == 'true');
        if ($useSpellcheckSearch) {
            $spellcheckCount = (isset($params['solr_params']['spellcheck.count']) && $params['solr_params']['spellcheck.count'])
                ? $params['solr_params']['spellcheck.count']
                : self::DEFAULT_SPELLCHECK_COUNT;
            $_params['solr_params'] += array(
                'spellcheck.collate'         => 'true',
                'spellcheck.dictionary'      => 'magento_spell' . $languageSuffix,
                'spellcheck.extendedResults' => 'true',
                'spellcheck.count'           => $spellcheckCount
            );
        }

        /**
         * Specific Solr params
         */
        if (!empty($_params['solr_params'])) {
            foreach ($_params['solr_params'] as $name => $value) {
                if (is_array($value)) {
                    foreach ($value as $val) {
                        $solrQuery->addParam($name, $val);
                    }
                } else {
                    $solrQuery->addParam($name, $value);
                }
            }
        }

        $filtersConditions = $this->_prepareFilters($_params['filters']);
        foreach ($filtersConditions as $condition) {
            $solrQuery->addFilterQuery($condition);
        }

        $this->_client->setServlet(SolrClient::SEARCH_SERVLET_TYPE, 'select');
        /**
         * Store filtering
         */
        if ($_params['store_id'] > 0) {
            $solrQuery->addFilterQuery('store_id:' . $_params['store_id']);
        }
        if (!Mage::helper('cataloginventory')->isShowOutOfStock()) {
            $solrQuery->addFilterQuery('in_stock:true');
        }

        try {
            $this->ping();
            $response = $this->_client->query($solrQuery);
            $data = $response->getResponse();

            if (!isset($params['solr_params']['stats']) || $params['solr_params']['stats'] != 'true') {
                $result = array('ids' => $this->_prepareQueryResponse($data));

                /**
                 * Extract facet search results
                 */
                if ($useFacetSearch) {
                    $result['facets'] = $this->_prepareFacetsQueryResponse($data);
                }

                /**
                 * Extract suggestions search results
                 */
                if ($useSpellcheckSearch) {
                    $resultSuggestions = $this->_prepareSuggestionsQueryResponse($data);
                    /* Calc results count for each suggestion */
                    if (isset($params['spellcheck_result_counts']) && $params['spellcheck_result_counts'] == true
                        && count($resultSuggestions)
                        && $spellcheckCount > 0
                    ) {
                        /* Temporary store value for main search query */
                        $tmpLastNumFound = $this->_lastNumFound;

                        unset($params['solr_params']['spellcheck']);
                        unset($params['solr_params']['spellcheck.count']);
                        unset($params['spellcheck_result_counts']);

                        $suggestions = array();
                        foreach ($resultSuggestions as $key => $item) {
                            $this->_lastNumFound = 0;
                            $this->search($item['word'], $params);
                            if ($this->_lastNumFound) {
                                $resultSuggestions[$key]['num_results'] = $this->_lastNumFound;
                                $suggestions[] = $resultSuggestions[$key];
                                $spellcheckCount--;
                            }
                            if ($spellcheckCount <= 0) {
                                break;
                            }
                        }
                        /* Return store value for main search query */
                        $this->_lastNumFound = $tmpLastNumFound;
                    } else {
                        $suggestions = array_slice($resultSuggestions, 0, $spellcheckCount);
                    }
                    $result['suggestions'] = $suggestions;
                }
            } else {
                $result = $this->_prepateStatsQueryResponce($data);
            }

            return $result;
        } catch (Exception $e) {
            Mage::logException($e);
        }
    }

    /**
     * Simple Search suggestions interface
     *
     * @deprecated after 1.9.0.0 - integrated into $this->_search()
     *
     * @param string $query The raw query string
     * @param array $params
     * @param int|bool $limit
     * @param bool $withResultsCounts
     * @return array
     */
    public function _searchSuggestions($query, $params = array(), $limit = false, $withResultsCounts = false)
    {

        /**
         * @see self::_search()
         */
        if ((int)('1' . str_replace('.', '', solr_get_version())) < 1099) {
            $this->_connect();
        }

        $query = $this->_escapePhrase($query);

        if (!$query) {
            return false;
        }
        $_params = array();


        $languageCode = $this->_getLanguageCodeByLocaleCode($params['locale_code']);
        $languageSuffix = ($languageCode) ? '_' . $languageCode : '';

        $solrQuery = new SolrQuery($query);

        /**
         * Now supported search only in fulltext and name fields based on dismax requestHandler (named as magento_lng).
         * Using dismax requestHandler for each language make matches in name field
         * are much more significant than matches in fulltext field.
         */

        $_params['solr_params'] = array (
            'spellcheck'                 => 'true',
            'spellcheck.collate'         => 'true',
            'spellcheck.dictionary'      => 'magento_spell' . $languageSuffix,
            'spellcheck.extendedResults' => 'true',
            'spellcheck.count'           => $limit ? $limit : 1,
        );

        /**
         * Specific Solr params
         */
        if (!empty($_params['solr_params'])) {
            foreach ($_params['solr_params'] as $name => $value) {
                $solrQuery->setParam($name, $value);
            }
        }

        $this->_client->setServlet(SolrClient::SEARCH_SERVLET_TYPE, 'spell');
        /**
         * Store filtering
         */
        if (!empty($params['store_id'])) {
            $solrQuery->addFilterQuery('store_id:' . $params['store_id']);
        }
        if (!Mage::helper('cataloginventory')->isShowOutOfStock()) {
            $solrQuery->addFilterQuery('in_stock:true');
        }

        try {
            $this->ping();
            $response = $this->_client->query($solrQuery);
            $result = $this->_prepareSuggestionsQueryResponse($response->getResponse());
            $resultLimit = array();
            // Calc results count for each suggestion
            if ($withResultsCounts && $limit) {
                $tmp = $this->_lastNumFound; //Temporary store value for main search query
                $this->_lastNumFound = 0;
                foreach ($result as $key => $item) {
                    $this->search($item['word'], $params);
                    if ($this->_lastNumFound) {
                        $result[$key]['num_results'] = $this->_lastNumFound;
                        $resultLimit[]= $result[$key];
                        $limit--;
                    }
                    if ($limit <= 0) {
                        break;
                    }
                }
                $this->_lastNumFound = $tmp; //Revert store value for main search query
            } else {
                $resultLimit = array_slice($result, 0, $limit);
            }

            return $resultLimit;
        } catch (Exception $e) {
            Mage::logException($e);
            return array();
        }
    }

    /**
     * Checks if Solr server is still up
     *
     * @return bool
     */
    public function ping()
    {
        Mage::helper('enterprise_search')->getSolrSupportedLanguages();
        return parent::ping();
    }
}
