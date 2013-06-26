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
 * @package     Enterprise_CatalogPermissions
 * @copyright   Copyright (c) 2010 Magento Inc. (http://www.magentocommerce.com)
 * @license     http://www.magentocommerce.com/license/enterprise-edition
 */

/**
 * Permission indexer
 *
 * @category   Enterprise
 * @package    Enterprise_CatalogPermissions
 */
class Enterprise_CatalogPermissions_Model_Permission_Index extends Mage_Core_Model_Abstract
{
    protected function _construct()
    {
        $this->_init('enterprise_catalogpermissions/permission_index');
    }

    /**
     * Reindex category permissions
     *
     * @param string $categoryPath
     * @return Enterprise_CatalogPermissions_Model_Permission_Index
     */
    public function reindex($categoryPath)
    {
        $this->getResource()->reindex($categoryPath);
        return $this;
    }

    /**
     * Reindex products permissions
     *
     * @param array|string $productIds
     * @return Enterprise_CatalogPermissions_Model_Permission_Index
     */
    public function reindexProducts($productIds = null)
    {
        $this->getResource()->reindexProducts($productIds);
        return $this;
    }

    /**
     * Reindex products permissions for standalone mode
     *
     * @param array|string $productIds
     * @return Enterprise_CatalogPermissions_Model_Permission_Index
     */
    public function reindexProductsStandalone($productIds = null)
    {
        $this->getResource()->reindexProductsStandalone($productIds);
        return $this;
    }

    /**
     * Retrieve permission index for category or categories with specified customer group and website id
     *
     * @param int|array $categoryId
     * @param int $customerGroupId
     * @param int $websiteId
     * @return array
     */
    public function getIndexForCategory($categoryId, $customerGroupId, $websiteId)
    {
        return $this->getResource()->getIndexForCategory($categoryId, $customerGroupId, $websiteId);
    }

    /**
     * Add index to product count select in product collection
     *
     * @param Mage_Catalog_Model_Resource_Eav_Mysql4_Product_Collection $collection
     * @return Enterprise_CatalogPermissions_Model_Permission_Index
     */
    public function addIndexToProductCount($collection, $customerGroupId)
    {
        $this->getResource()->addIndexToProductCount($collection, $customerGroupId);
        return $this;
    }

    /**
     * Add index to category collection
     *
     * @param Mage_Catalog_Model_Resource_Eav_Mysql4_Category_Collection|Mage_Catalog_Model_Resource_Eav_Mysql4_Category_Flat_Collection $collection
     * @param int $customerGroupId
     * @param int $websiteId
     * @return Enterprise_CatalogPermissions_Model_Permission_Index
     */
    public function addIndexToCategoryCollection($collection, $customerGroupId, $websiteId)
    {
        $this->getResource()->addIndexToCategoryCollection($collection, $customerGroupId, $websiteId);
        return $this;
    }

    /**
     * Apply price grant on price index select
     *
     * @param Varien_Object $data
     * @param int $customerGroupId
     * @return Enterprise_CatalogPermissions_Model_Permission_Index
     */
    public function applyPriceGrantToPriceIndex($data, $customerGroupId)
    {
        $this->getResource()->applyPriceGrantToPriceIndex($data, $customerGroupId);
        return $this;
    }

    /**
     * Retrieve restricted category ids for customer group and website
     *
     * @param int $customerGroupId
     * @param int $websiteId
     * @return array
     */
    public function getRestrictedCategoryIds($customerGroupId, $websiteId)
    {
        return $this->getResource()->getRestrictedCategoryIds($customerGroupId, $websiteId);
    }


    /**
     * Add index select in product collection
     *
     * @param Mage_Catalog_Model_Resource_Eav_Mysql4_Product_Collection $collection
     * @return Enterprise_CatalogPermissions_Model_Permission_Index
     */
    public function addIndexToProductCollection($collection, $customerGroupId)
    {
        $this->getResource()->addIndexToProductCollection($collection, $customerGroupId);
        return $this;
    }

     /**
     * Add permission index to product model
     *
     * @param Mage_Catalog_Model_Product $product
     * @param int $customerGroupId
     * @return Enterprise_CatalogPermissions_Model_Permission_Index
     */
    public function addIndexToProduct($product, $customerGroupId)
    {
        $this->getResource()->addIndexToProduct($product, $customerGroupId);
        return $this;
    }

    /**
     * Get permission index for products
     *
     * @param int|array $productId
     * @param int $customerGroupId
     * @param int $storeId
     * @return array
     */
    public function getIndexForProduct($productId, $customerGroupId, $storeId)
    {
        return $this->getResource()->getIndexForProduct($productId, $customerGroupId, $storeId);
    }
}
