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
 * Enterprise search collection resource model
 *
 * @category   Enterprise
 * @package    Enterprise_Search
 * @author     Magento Core Team <core@magentocommerce.com>
 */

class Enterprise_Search_Model_Resource_Collection
    extends Mage_Catalog_Model_Resource_Eav_Mysql4_Product_Collection
{

    /**
     * Store search query text
     *
     * @var string
     */
    protected $_searchQueryText = '';

    /**
     * Store search query params
     *
     * @var array
     */
    protected $_searchQueryParams = array();

    /**
     * Store search query filters
     *
     * @var array
     */
    protected $_searchQueryFilters = array();

    /**
     * Store found entities ids
     *
     * @var array
     */
    protected $_searchedEntityIds = array();

    /**
     * Store found suggestions
     *
     * @var array
     */
    protected $_searchedSuggestions = array();

    /**
     * Store engine instance
     *
     * @var object
     */
    protected $_engine = null;

    /**
     * Store sort orders
     *
     * @var array
     */
    protected $_sortBy = array();

    /**
     * General default query *:* to disable query limitation
     *
     * @var array
     */
    protected $_generalDefaultQuery = array('*' => '*');

    /**
     * Faceted search result data
     *
     * @var array
     */
    protected $_facetedData = array();

    /**
     * Suggestions search result data
     *
     * @var array
     */
    protected $_suggestionsData = array();

    /**
     * Conditions for faceted search
     *
     * @var array
     */
    protected $_facetedConditions = array();

    /**
     * Return field facated data from faceted search result
     *
     * @param string $field
     *
     * @return array
     */
    public function getFacetedData($field)
    {
        if (isset($this->_facetedData[$field])) {
            return $this->_facetedData[$field];
        }
        return array();
    }

    /**
     * Return suggestions search result data
     *
     *  @return array
     */
    public function getSuggestionsData()
    {
        return $this->_suggestionsData;
    }

    /**
     * Allow to set faceted search conditions to retrive result by single query
     *
     * @param string $field
     * @param string | array $condition
     *
     * @return Enterprise_Search_Model_Resource_Collection
     */
    public function setFacetCondition($field, $condition = null)
    {
        if (array_key_exists($field, $this->_facetedConditions)) {
            if (!empty($this->_facetedConditions[$field])){
                $this->_facetedConditions[$field] = array($this->_facetedConditions[$field]);
            }
            $this->_facetedConditions[$field][] = $condition;
        } else {
            $this->_facetedConditions[$field] = $condition;
        }

        return $this;
    }

    /**
     * Add search query filter
     * Set search query
     *
     * @param   string $query
     *
     * @return  Enterprise_Search_Model_Resource_Collection
     */
    public function addSearchFilter($query)
    {
        $this->_searchQueryText = $query;

        return $this;
    }

    /**
     * Add search query filter
     * Set search query parameters
     *
     * @param   string|array $param
     * @param   string|array $value
     *
     * @return  Enterprise_Search_Model_Resource_Collection
     */
    public function addSearchParam($param, $value = null)
    {
        if (is_array($param)) {
            foreach ($param as $field => $value) {
                $this->addSearchParam($field, $value);
            }
        } elseif (!empty($value)) {
            $this->_searchQueryParams[$param] = $value;
        }

        return $this;
    }

    public function getExtendedSearchParams()
    {
        $result = $this->_searchQueryFilters;
        $result['query_text'] = $this->_searchQueryText;
        return $result;
    }

    /**
     * Add search query filter (qf)
     *
     * @deprecated after 1.9.0.0
     *
     * @param   string|array $param
     * @param   string|array $value
     * @return  Enterprise_Search_Model_Resource_Collection
     */
    public function addSearchQfFilter($param, $value = null)
    {
        if (is_array($param)) {
            foreach ($param as $field => $value) {
                $this->addSearchQfFilter($field, $value);
            }
        } elseif (isset($value)) {
            if (isset($this->_searchQueryFilters[$param]) && !is_array($this->_searchQueryFilters[$param])) {
                $this->_searchQueryFilters[$param] = array($this->_searchQueryFilters[$param]);
                $this->_searchQueryFilters[$param][] = $value;
            } else {
                $this->_searchQueryFilters[$param] = $value;
            }
        }

        return $this;
    }

    /**
     * Add search query filter (fq)
     *
     * @param   array $param
     * @return  Enterprise_Search_Model_Resource_Collection
     */
    public function addFqFilter($param)
    {
        if (is_array($param)) {
            foreach ($param as $field => $value) {
                $this->_searchQueryFilters[$field] = $value;
            }
        }

        return $this;
    }

    /**
     * Add advanced search query filter
     * Set search query
     *
     * @param  string $query
     * @return Enterprise_Search_Model_Resource_Collection
     */
    public function addAdvancedSearchFilter($query)
    {
        return $this->addSearchFilter($query);
    }

    /**
     * Specify category filter for product collection
     *
     * @param   Mage_Catalog_Model_Category $category
     * @return  Enterprise_Search_Model_Resource_Collection
     */
    public function addCategoryFilter(Mage_Catalog_Model_Category $category)
    {
        $this->addFqFilter(array('categories' => $category->getId()));

        return $this;
    }

    /**
     * Add sort order
     *
     * @param string $attribute
     * @param string $dir
     * @return Enterprise_Search_Model_Resource_Collection
     */
    public function setOrder($attribute, $dir = 'desc')
    {
        $this->_sortBy[] = array($attribute => $dir);
        return $this;
    }

    /**
     * Prepare base parameters for search adapters
     *
     * @return array
     */
    protected function _prepareBaseParams()
    {
        $store  = Mage::app()->getStore();
        $params = array(
            'store_id'      => $store->getId(),
            'locale_code'   => $store->getConfig(Mage_Core_Model_Locale::XML_PATH_DEFAULT_LOCALE),
            'filters'       => $this->_searchQueryFilters
        );

        if (!empty($this->_searchQueryParams)) {
            $params['ignore_handler'] = true;
            $query = $this->_searchQueryParams;
        } else {
            $query = $this->_searchQueryText;
        }

        return array($query, $params);
    }

    /**
     * Search documents by query
     * Set found ids and number of found results
     *
     * @return Enterprise_Search_Model_Resource_Collection
     */
    protected function _beforeLoad()
    {
        $ids = array();
        if ($this->_engine) {
            list($query, $params) = $this->_prepareBaseParams();

            if ($this->_sortBy) {
                $params['sort_by'] = $this->_sortBy;
            }
            if ($this->_pageSize !== false) {
                $page              = ($this->_curPage  > 0) ? (int) $this->_curPage  : 1;
                $rowCount          = ($this->_pageSize > 0) ? (int) $this->_pageSize : 1;
                $params['offset']  = $rowCount * ($page - 1);
                $params['limit']   = $rowCount;
            }

            $params['solr_params']['facet'] = 'on';
            $params['facet'] = $this->_facetedConditions;

            $result = $this->_engine->getIdsByQuery($query, $params);
            $ids    = (array) $result['ids'];
            $this->_facetedData = $result['facetedData'];
        }

        $this->_searchedEntityIds = &$ids;
        $this->getSelect()->where('e.entity_id IN (?)', $this->_searchedEntityIds);

        /**
         * To prevent limitations to the collection, because of new data logic
         */
        $this->_pageSize = false;

        return parent::_beforeLoad();
    }

    /**
     * Sort collection items by sort order of found ids
     *
     * @return Enterprise_Search_Model_Resource_Collection
     */
    protected function _afterLoad()
    {
        parent::_afterLoad();
        $sortedItems = array();
        foreach ($this->_searchedEntityIds as $id) {
            if (isset($this->_items[$id])) {
                $sortedItems[$id] = $this->_items[$id];
            }
        }
        $this->_items = &$sortedItems;

        return $this;
    }

    /**
     * Retrieve found number of items
     *
     * @return int
     */
    public function getSize()
    {
        if (is_null($this->_totalRecords)) {
            list($query, $params) = $this->_prepareBaseParams();
            $params['limit'] = 1;

            if ($this->_searchQueryParams != $this->_generalDefaultQuery) {
                $helper = Mage::helper('enterprise_search');
                $searchSuggestionsEnabled = $helper->getSolrConfigData('server_suggestion_enabled');
                if ($searchSuggestionsEnabled) {
                    $params['solr_params']['spellcheck'] = 'true';
                    $searchSuggestionsCount = (int) $helper->getSolrConfigData('server_suggestion_count');
                    if ($searchSuggestionsCount < 1) {
                        $searchSuggestionsCount = 1;
                    }
                    $params['solr_params']['spellcheck.count'] = $searchSuggestionsCount;
                    $params['spellcheck_result_counts'] = (bool) $helper
                        ->getSolrConfigData('server_suggestion_count_results_enabled');
                }
            }

            $result = $this->_engine->getIdsByQuery($query, $params);
            $this->_suggestionsData = $result['suggestionsData'];
            $this->_totalRecords    = $this->_engine->getLastNumFound();
        }

        return $this->_totalRecords;
    }

    /**
     * Collect stats per field
     *
     * @param  array $fields
     * @return array
     */
    public function getStats($fields)
    {
        list($query, $params) = $this->_prepareBaseParams();
        $params['limit'] = 0;
        $params['solr_params']['stats'] = 'true';

        if (!is_array($fields)) {
            $fields = array($fields);
        }
        foreach ($fields as $field) {
            $params['solr_params']['stats.field'][] = $field;
        }

        /**
         * To prevent limitations to the collection, because of new data logic
         */
        $this->_pageSize = false;

        return $this->_engine->getStats($query, $params);
    }

    /**
     * Retrieve faceted search results
     *
     * @deprecated after 1.9.0.0 - integrated into $this->getSize()
     *
     * @param  array $params
     * @return array
     */
    public function getFacets($params)
    {
        list($query, $params) = $this->_prepareBaseParams();
        $params['limit'] = 1;

        return (array) $this->_engine->getFacetsByQuery($query, $params);
    }

    /**
     * Set query *:* to disable query limitation
     *
     * @return Enterprise_Search_Model_Resource_Collection
     */
    public function setGeneralDefaultQuery()
    {
        $this->_searchQueryParams = $this->_generalDefaultQuery;
        return $this;
    }

    /**
     * Set search engine
     *
     * @param object $engine
     * @return Enterprise_Search_Model_Resource_Collection
     */
    public function setEngine($engine)
    {
        $this->_engine = $engine;
        return $this;
    }

    /**
     * Stub method
     *
     * @param array $fields
     *
     * @return Enterprise_Search_Model_Resource_Collection
     */
    public function addFieldsToFilter($fields)
    {
        return $this;
    }

    /**
     * Adding product count to categories collection
     *
     * @param   Mage_Eav_Model_Entity_Collection_Abstract $categoryCollection
     * @return  Mage_Eav_Model_Entity_Collection_Abstract
     */
    public function addCountToCategories($categoryCollection)
    {
        return $this;
    }

    /**
     * Set product visibility filter for enabled products
     *
     * @param   array $visibility
     * @return  Mage_Catalog_Model_Resource_Eav_Mysql4_Product_Collection
     */
    public function setVisibility($visibility)
    {
        if (is_array($visibility)) {
            foreach ($visibility as $visibilityId) {
                $this->addFqFilter(array('visibility' => $visibilityId));
            }
        }

        return $this;
    }
}
