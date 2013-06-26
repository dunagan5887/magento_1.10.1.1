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
 * TargetRule Catalog Product List Abstract Block
 *
 * @category   Enterprise
 * @package    Enterprise_TargetRule
 */
abstract class Enterprise_TargetRule_Block_Catalog_Product_List_Abstract extends Mage_Catalog_Block_Product_Abstract
{
    /**
     * Catalog Product Link Collection
     *
     * @var Mage_Catalog_Model_Resource_Eav_Mysql4_Product_Collection
     */
    protected $_linkCollection;

    /**
     * Catalog Product List Item Collection array
     *
     * @var array
     */
    protected $_items;

    /**
     * TargetRule Index instance
     *
     * @var Enterprise_TargetRule_Model_Index
     */
    protected $_index;

    /**
     * Array of exclude Product Ids
     *
     * @var array
     */
    protected $_excludeProductIds;

    /**
     * Retrieve Catalog Product List Type identifier
     *
     * @return int
     */
    abstract public function getType();

    /**
     * Retrieve current product instance (if actual and available)
     *
     * @return Mage_Catalog_Model_Product
     */
    public function getProduct()
    {
        return Mage::registry('product');
    }

    /**
     * Retrieve TargetRule data helper
     *
     * @return Enterprise_TargetRule_Helper_Data
     */
    public function getTargetRuleHelper()
    {
        return Mage::helper('enterprise_targetrule');
    }

    /**
     * Retrieve Catalog Product List Type prefix
     * without last underscore
     *
     * @return string
     */
    protected function _getTypePrefix()
    {
        switch ($this->getType()) {
            case Enterprise_TargetRule_Model_Rule::RELATED_PRODUCTS:
                $prefix = 'related';
                break;

            case Enterprise_TargetRule_Model_Rule::UP_SELLS:
                $prefix = 'upsell';
                break;

            default:
                Mage::throwException(
                    Mage::helper('enterprise_targetrule')->__('Undefined Catalog Product List Type')
                );
        }
        return $prefix;
    }

    /**
     * Retrieve Target Rule Index instance
     *
     * @return Enterprise_TargetRule_Model_Index
     */
    protected function _getTargetRuleIndex()
    {
        if (is_null($this->_index)) {
            $this->_index = Mage::getModel('enterprise_targetrule/index');
        }
        return $this->_index;
    }

    /**
     * Retrieve position limit product attribute name
     *
     * @return string
     */
    protected function _getPositionLimitField()
    {
        return sprintf('%s_targetrule_position_limit', $this->_getTypePrefix());
    }

    /**
     * Retrieve position behavior product attribute name
     *
     * @return string
     */
    protected function _getPositionBehaviorField()
    {
        return sprintf('%s_targetrule_position_behavior', $this->_getTypePrefix());
    }

    /**
     * Retrieve Maximum Number Of Product
     *
     * @return int
     */
    public function getPositionLimit()
    {
        $limit = $this->getProduct()->getData($this->_getPositionLimitField());
        if (is_null($limit)) { // use configuration settings
            $limit = $this->getTargetRuleHelper()->getMaximumNumberOfProduct($this->getType());
            $this->getProduct()->setData($this->_getPositionLimitField(), $limit);
        }
        return $this->getTargetRuleHelper()->getMaxProductsListResult($limit);
    }

    /**
     * Retrieve Position Behavior
     *
     * @return int
     */
    public function getPositionBehavior()
    {
        $behavior = $this->getProduct()->getData($this->_getPositionBehaviorField());
        if (is_null($behavior)) { // use configuration settings
            $behavior = $this->getTargetRuleHelper()->getShowProducts($this->getType());
            $this->getProduct()->setData($this->_getPositionBehaviorField(), $behavior);
        }
        return $behavior;
    }

    /**
     * Retrieve array of exclude product ids
     *
     * @return array
     */
    public function getExcludeProductIds()
    {
        if (is_null($this->_excludeProductIds)) {
            $this->_excludeProductIds = array($this->getProduct()->getEntityId());
        }
        return $this->_excludeProductIds;
    }

    /**
     * Retrieve related product collection assigned to product
     *
     * @throws Mage_Core_Exception
     * @return Mage_Catalog_Model_Resource_Eav_Mysql4_Product_Collection
     */
    public function getLinkCollection()
    {
        if (is_null($this->_linkCollection)) {
            switch ($this->getType()) {
                case Enterprise_TargetRule_Model_Rule::RELATED_PRODUCTS:
                    $this->_linkCollection = $this->getProduct()
                        ->getRelatedProductCollection();
                    break;

                case Enterprise_TargetRule_Model_Rule::UP_SELLS:
                    $this->_linkCollection = $this->getProduct()
                        ->getUpSellProductCollection();
                    break;

                default:
                    Mage::throwException(
                        Mage::helper('enterprise_targetrule')->__('Undefined Catalog Product List Type')
                    );
            }

            $this->_addProductAttributesAndPrices($this->_linkCollection);
            Mage::getSingleton('catalog/product_visibility')
                ->addVisibleInCatalogFilterToCollection($this->_linkCollection);

            $this->_linkCollection->addAttributeToSort('position', 'ASC')
                ->setFlag('do_not_use_category_id', true)
                ->setPageSize($this->getPositionLimit());

            $excludeProductIds = $this->getExcludeProductIds();
            if ($excludeProductIds) {
                $this->_linkCollection->addAttributeToFilter('entity_id', array('nin' => $excludeProductIds));
            }
        }

        return $this->_linkCollection;
    }

    /**
     * Retrieve count of related linked products assigned to product
     *
     * @return int
     */
    public function getLinkCollectionCount()
    {
        return count($this->getLinkCollection()->getItems());
    }

    /**
     * Retrieve Catalog Product List Items
     *
     * @return array
     */
    public function getItemCollection()
    {
        if (is_null($this->_items)) {
            $ruleBased  = array(
                Enterprise_TargetRule_Model_Rule::BOTH_SELECTED_AND_RULE_BASED,
                Enterprise_TargetRule_Model_Rule::RULE_BASED_ONLY,
            );
            $selected   = array(
                Enterprise_TargetRule_Model_Rule::BOTH_SELECTED_AND_RULE_BASED,
                Enterprise_TargetRule_Model_Rule::SELECTED_ONLY,
            );
            $behavior   = $this->getPositionBehavior();
            $limit      = $this->getPositionLimit();

            $this->_items = array();

            if (in_array($behavior, $selected)) {
                foreach ($this->getLinkCollection() as $item) {
                    $this->_items[$item->getEntityId()] = $item;
                }
            }

            if (in_array($behavior, $ruleBased) && $limit > count($this->_items)) {
                $excludeProductIds = array_merge(array_keys($this->_items), $this->getExcludeProductIds());

                $count = $limit - count($this->_items);
                $productIds = $this->_getTargetRuleIndex()
                    ->setType($this->getType())
                    ->setLimit($count)
                    ->setProduct($this->getProduct())
                    ->setExcludeProductIds($excludeProductIds)
                    ->getProductIds();

                if ($productIds) {
                    /* @var $collection Mage_Catalog_Model_Resource_Eav_Mysql4_Product_Collection */
                    $collection = Mage::getResourceModel('catalog/product_collection');
                    $collection->addFieldToFilter('entity_id', array('in' => $productIds));
                    $this->_addProductAttributesAndPrices($collection);

                    Mage::getSingleton('catalog/product_visibility')
                        ->addVisibleInCatalogFilterToCollection($collection);
                    $collection->setPageSize($count)
                        ->setFlag('do_not_use_category_id', true);

                    $orderedAr = array_flip($productIds);

                    foreach ($collection as $item) {
                        $this->_items[(int)$orderedAr[$item->getEntityId()]] = $item;
                    }
                    ksort($this->_items);
                }
            }
        }

        return $this->_items;
    }

    /**
     * Check is has items
     *
     * @return bool
     */
    public function hasItems()
    {
        return $this->getItemsCount() > 0;
    }

    /**
     * Retrieve count of product in collection
     *
     * @return int
     */
    public function getItemsCount()
    {
        return count($this->getItemCollection());
    }
}
