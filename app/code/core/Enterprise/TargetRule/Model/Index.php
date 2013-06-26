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
 * @package     Enterprise_TargetRule
 * @copyright   Copyright (c) 2010 Magento Inc. (http://www.magentocommerce.com)
 * @license     http://www.magentocommerce.com/license/enterprise-edition
 */


/**
 * TargetRule Product Index by Rule Product List Type Model
 *
 * @category   Enterprise
 * @package    Enterprise_TargetRule
 */
class Enterprise_TargetRule_Model_Index extends Mage_Core_Model_Abstract
{
    /**
     * Initialize resource model
     *
     */
    protected function _construct()
    {
        $this->_init('enterprise_targetrule/index');
    }

    /**
     * Retrieve resource instance
     *
     * @return Enterprise_TargetRule_Model_Mysql4_Index
     */
    protected function _getResource()
    {
        return parent::_getResource();
    }

    /**
     * Set Catalog Product List identifier
     *
     * @param int $type
     * @return Enterprise_TargetRule_Model_Index
     */
    public function setType($type)
    {
        return $this->setData('type', $type);
    }

    /**
     * Retrieve Catalog Product List identifier
     *
     * @throws Mage_Core_Exception
     * @return int
     */
    public function getType()
    {
        $type = $this->getData('type');
        if (is_null($type)) {
            Mage::throwException(
                Mage::helper('enterprise_targetrule')->__('Undefined Catalog Product List Type')
            );
        }
        return $type;
    }

    /**
     * Set store scope
     *
     * @param int $storeId
     * @return Enterprise_TargetRule_Model_Index
     */
    public function setStoreId($storeId)
    {
        return $this->setData('store_id', $storeId);
    }

    /**
     * Retrieve store identifier scope
     *
     * @return int
     */
    public function getStoreId()
    {
        $storeId = $this->getData('store_id');
        if (is_null($storeId)) {
            $storeId = Mage::app()->getStore()->getId();
        }
        return $storeId;
    }

    /**
     * Set customer group identifier
     *
     * @param int $customerGroupId
     * @return Enterprise_TargetRule_Model_Index
     */
    public function setCustomerGroupId($customerGroupId)
    {
        return $this->setData('customer_group_id', $customerGroupId);
    }

    /**
     * Retrieve customer group identifier
     *
     * @return int
     */
    public function getCustomerGroupId()
    {
        $customerGroupId = $this->getData('customer_group_id');
        if (is_null($customerGroupId)) {
            $customerGroupId = Mage::getSingleton('customer/session')->getCustomerGroupId();
        }
        return $customerGroupId;
    }

    /**
     * Set result limit
     *
     * @param int $limit
     * @return Enterprise_TargetRule_Model_Index
     */
    public function setLimit($limit)
    {
        return $this->setData('limit', $limit);
    }

    /**
     * Retrieve result limit
     *
     * @return int
     */
    public function getLimit()
    {
        $limit = $this->getData('limit');
        if (is_null($limit)) {
            $limit = Mage::helper('enterprise_targetrule')->getMaximumNumberOfProduct($this->getType());
        }
        return $limit;
    }

    /**
     * Set Product data object
     *
     * @param Varien_Object $product
     * @return Enterprise_TargetRule_Model_Index
     */
    public function setProduct(Varien_Object $product)
    {
        return $this->setData('product', $product);
    }

    /**
     * Retrieve Product data object
     *
     * @throws Mage_Core_Exception
     * @return Varien_Object
     */
    public function getProduct()
    {
        $product = $this->getData('product');
        if (!$product instanceof Varien_Object) {
            Mage::throwException(Mage::helper('enterprise_targetrule')->__('Please define product data object'));
        }
        return $product;
    }

    /**
     * Set product ids list be excluded
     *
     * @param int|array $productIds
     * @return Enterprise_TargetRule_Model_Index
     */
    public function setExcludeProductIds($productIds)
    {
        if (!is_array($productIds)) {
            $productIds = array($productIds);
        }
        return $this->setData('exclude_product_ids', $productIds);
    }

    /**
     * Retrieve Product Ids which must be excluded
     *
     * @return array
     */
    public function getExcludeProductIds()
    {
        $productIds = $this->getData('exclude_product_ids');
        if (!is_array($productIds)) {
            $productIds = array();
        }
        return $productIds;
    }

    /**
     * Retrieve related product Ids
     *
     * @return array
     */
    public function getProductIds()
    {
        return $this->_getResource()->getProductIds($this);
    }

    /**
     * Retrieve Rule collection by type and product
     *
     * @return Enterprise_TargetRule_Model_Mysql4_Rule_Collection
     */
    public function getRuleCollection()
    {
        /* @var $collection Enterprise_TargetRule_Model_Mysql4_Rule_Collection */
        $collection = Mage::getResourceModel('enterprise_targetrule/rule_collection');
        $collection->addApplyToFilter($this->getType())
            ->addProductFilter($this->getProduct()->getId())
            ->addIsActiveFilter()
            ->setPriorityOrder()
            ->setFlag('do_not_run_after_load', true);

        return $collection;
    }

    /**
     * Retrieve SELECT instance for conditions
     *
     * @return Varien_Db_Select
     */
    public function select()
    {
        return $this->_getResource()->select();
    }

    /**
     * Run processing by cron
     * Check store datetime and every day per store clean index cache
     *
     */
    public function cron()
    {
        $websites = Mage::app()->getWebsites();
        foreach ($websites as $website) {
            /* @var $website Mage_Core_Model_Website */
            $store = $website->getDefaultStore();
            $date  = Mage::app()->getLocale()->storeDate($store);
            if ($date->equals(0, Zend_Date::HOUR)) {
                $this->_getResource()->cleanIndex(null, $website->getStoreIds());
            }
        }
    }
}
