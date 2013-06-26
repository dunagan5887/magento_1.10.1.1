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
class Enterprise_Search_Model_Resource_Engine
{
    /**
     * Store search engine adapter model instance
     *
     * @var object
     */
    protected $_adapter = null;

    /**
     * Advanced index fields prefix
     *
     * @var string
     */
    protected $_advancedIndexFieldsPrefix = '#';

    /**
     * List of static fields for index
     *
     * @var array
     */
    protected $_advancedStaticIndexFields = array(
        '#categories',
        '#show_in_categories',
        '#visibility'
    );

    /**
     * List of obligatory dynamic fields for index
     *
     * @var array
     */
    protected $_advancedDynamicIndexFields = array(
        '#position_category_',
        '#price_'
    );



    /**
     * Set search engine adapter
     *
     */
    public function __construct()
    {
        $this->_adapter = $this->_getAdapterModel('solr');
        $this->_adapter->setAdvancedIndexFieldPrefix($this->getFieldsPrefix());
    }

    /**
     * Returns advanced index fields prefix
     *
     * @return string
     */
    public function getFieldsPrefix()
    {
        return $this->_advancedIndexFieldsPrefix;
    }

    /**
     * Set search resource model
     *
     * @return string
     */
    public function getResourceName()
    {
        return 'enterprise_search/advanced';
    }

    /**
     * Retrieve found document ids search index sorted by relevance
     *
     * @param string $query
     * @param array $params see description in appropriate search adapter
     * @param string $entityType 'product'|'cms'
     * @return array
     */
    public function getIdsByQuery($query, $params = array(), $entityType = 'product')
    {
        return $this->_adapter->getIdsByQuery($query, $params);
    }

    public function getStats($query, $params = array(), $entityType = 'product')
    {
        return $this->_adapter->getStats($query, $params);
    }

    /**
     * Retrieve search suggestions
     *
     * @deprecated after 1.9.0.0
     *
     * @param string $query
     * @param array $params see description in appropriate search adapter
     * @param int|bool $limit
     * @param bool $withResultsCounts
     * @return array
     */
    public function getSuggestionsByQuery($query, $params = array(), $limit = false, $withResultsCounts = false)
    {
        return $this->_adapter->getSuggestionsByQuery($query, $params, $limit, $withResultsCounts);
    }

    /**
     * Add entity data to search index
     *
     * @param int $entityId
     * @param int $storeId
     * @param array $index
     * @param string $entityType 'product'|'cms'
     * @return Enterprise_Search_Model_Resource_Engine
     */
    public function saveEntityIndex($entityId, $storeId, $index, $entityType = 'product')
    {
        $store             = Mage::app()->getStore($storeId);
        $localeCode        = $store->getConfig(Mage_Core_Model_Locale::XML_PATH_DEFAULT_LOCALE);
        $index['store_id'] = $storeId;
        $docs = $this->_adapter->prepareDocs(array($entityId => $index), $localeCode);
        $this->_adapter->addDocs($docs);
        return $this;
    }

    /**
     * Multi add entities data to search index
     *
     * @param int $storeId
     * @param array $entityIndexes
     * @param string $entityType 'product'|'cms'
     * @return Enterprise_Search_Model_Resource_Engine
     */
    public function saveEntityIndexes($storeId, $entityIndexes, $entityType = 'product')
    {
        $store      = Mage::app()->getStore($storeId);
        $localeCode = $store->getConfig(Mage_Core_Model_Locale::XML_PATH_DEFAULT_LOCALE);
        foreach ($entityIndexes as $entityId => $indexData) {
            $entityIndexes[$entityId]['store_id'] = $storeId;
        }

        $docs = $this->_adapter->prepareDocs($entityIndexes, $localeCode);

        $this->_adapter->addDocs($docs);
        return $this;
    }

    /**
     * Refresh products indexes affected on category update
     *
     * @param array $productIds
     * @param array $categoryIds
     * @return Enterprise_Search_Model_Resource_Engine
     */
    public function updateCategoryIndex($productIds, $categoryIds)
    {
        if (!is_array($productIds) || empty($productIds)) {
            $productIds = Mage::getResourceSingleton('enterprise_search/index')
                ->getMovedCategoryProductIds($categoryIds[0]);
        }

        if (!empty($productIds)) {
            Mage::getResourceSingleton('catalogsearch/fulltext')->rebuildIndex(null, $productIds);
        }

        return $this;
    }

    /**
     * Remove entity data from search index
     *
     * @param int $storeId
     * @param int $entityId
     * @param string $entityType 'product'|'cms'
     * @return Enterprise_Search_Model_Resource_Engine
     */
    public function cleanIndex($storeId = null, $entityId = null, $entityType = 'product')
    {
        if ($storeId == Mage_Core_Model_App::ADMIN_STORE_ID) {
            foreach (Mage::app()->getStores(false) as $store) {
                $this->cleanIndex($store->getId(), $entityId, $entityType);
            }

            return $this;
        }

        if (is_null($storeId) && is_null($entityId)) {
            $this->_adapter->deleteDocs(array(), 'all');
        } else if (is_null($storeId) && !is_null($entityId)) {
            $this->_adapter->deleteDocs($entityId);
        } else if (!is_null($storeId) && is_null($entityId)) {
            $this->_adapter->deleteDocs(array(), array('store_id:' . $storeId));
        } else if (!is_null($storeId) && !is_null($entityId)) {
            $idsQuery = array();
            if (!is_array($entityId)) {
                $entityId = array($entityId);
            }
            foreach ($entityId as $id) {
                $idsQuery[] = $this->_adapter->getUniqueKey() . ':' . $id . '|' . $storeId;
            }
            $this->_adapter->deleteDocs(array(), array('store_id:' . $storeId . ' AND (' . implode(' OR ', $idsQuery) . ')'));
        }
        return $this;
    }

    /**
     * Retrieve last query number of found results
     *
     * @return int
     */
    public function getLastNumFound()
    {
        return $this->_adapter->getLastNumFound();
    }

    /**
     * Retrieve search result data collection
     *
     * @return Enterprise_Search_Model_Resource_Collection
     */
    public function getResultCollection()
    {
        return Mage::getResourceModel('enterprise_search/collection')->setEngine($this);
    }

    /**
     * Retrieve advanced search result data collection
     *
     * @return Enterprise_Search_Model_Resource_Collection
     */
    public function getAdvancedResultCollection()
    {
        return $this->getResultCollection();
    }

    /**
     * Define if current search engine supports advanced index
     *
     * @return bool
     */
    public function allowAdvancedIndex()
    {
        return true;
    }

    /**
     * Add to index fields that allowed in advanced index
     *
     * @param array $productData
     *
     * @return array
     */
    public function addAllowedAdvancedIndexField($productData)
    {
        $advancedIndex = array();

        foreach ($productData as $field => $value) {
            if (in_array($field, $this->_advancedStaticIndexFields)
                || $this->_isDynamicField($field)) {
                if (!empty($value)){
                    $advancedIndex[$field] = $value;
                }
            }
        }

        return $advancedIndex;
    }

    /**
     * Define if field is dynamic index field
     *
     * @param string $field
     *
     * @return bool
     */
    protected function _isDynamicField($field)
    {
        foreach ($this->_advancedDynamicIndexFields as $dynamicField) {
            $length = strlen($dynamicField);
            if (substr($field, 0, $length) == $dynamicField) {
                return true;
            }
        }

        return false;
    }

    /**
     * Prepare advanced index for products
     *
     * @see Mage_CatalogSearch_Model_Mysql4_Fulltext->_getSearchableProducts()
     *
     * @param array $index
     * @param int $storeId
     * @param array | null $productIds
     *
     * @return array
     */
    public function addAdvancedIndex($index, $storeId, $productIds = null)
    {
        return Mage::getResourceSingleton('enterprise_search/index')
            ->addAdvancedIndex($index, $storeId, $productIds);
    }

    /**
     * Prepare index array
     *
     * @param array $index
     * @param string $separator
     * @return array
     */
    public function prepareEntityIndex($index, $separator = null)
    {
        return $index;
    }

    /**
     * Define if Layered Navigation is allowed
     *
     * @deprecated after 1.9.1 - use $this->isLayeredNavigationAllowed()
     *
     * @return bool
     */
    public function isLeyeredNavigationAllowed()
    {
        $this->isLayeredNavigationAllowed();
    }

    /**
     * Define if Layered Navigation is allowed
     *
     * @return bool
     */
    public function isLayeredNavigationAllowed()
    {
        return true;
    }

    /**
     * Retrieve search engine adapter model by adapter name
     * Now suppoting only Solr search engine adapter
     *
     * @param string $adapterName
     * @return object
     */
    protected function _getAdapterModel($adapterName)
    {
        $model = '';
        switch ($adapterName) {
            case 'solr':
            default:
                if (extension_loaded('solr')) {
                    $model = 'enterprise_search/adapter_phpExtension';
                } else {
                    $model = 'enterprise_search/adapter_httpStream';
                }
                break;
        }

        return Mage::getSingleton($model);
    }

    /**
     * Define if selected adapter is available
     *
     * @return bool
     */
    public function test()
    {
        return $this->_adapter->ping();
    }
}
