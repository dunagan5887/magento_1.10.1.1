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
 * @package     Enterprise_CustomerSegment
 * @copyright   Copyright (c) 2010 Magento Inc. (http://www.magentocommerce.com)
 * @license     http://www.magentocommerce.com/license/enterprise-edition
 */

/**
 * Shopping cart/wishlist items condition
 */
class Enterprise_CustomerSegment_Model_Segment_Condition_Product_Combine_List
    extends Enterprise_CustomerSegment_Model_Condition_Combine_Abstract
{
    const WISHLIST  = 'wishlist';
    const CART      = 'shopping_cart';

    protected $_inputType = 'select';

    public function __construct()
    {
        parent::__construct();
        $this->setType('enterprise_customersegment/segment_condition_product_combine_list');
        $this->setValue(self::CART);
    }

    /**
     * Get array of event names where segment with such conditions combine can be matched
     *
     * @return array
     */
    public function getMatchedEvents()
    {
        $events = array();
        switch ($this->getValue()) {
            case self::WISHLIST:
                $events = array('wishlist_items_renewed');
                break;
            default:
                $events = array('checkout_cart_save_after');
        }
        return $events;
    }

    /**
     * Get inherited conditions selectors
     *
     * @return array
     */
    public function getNewChildSelectOptions()
    {
        return Mage::getModel('enterprise_customersegment/segment_condition_product_combine')
            ->setDateConditions(true)
            ->getNewChildSelectOptions();
    }

    /**
     * Initialize value select options
     *
     * @return Enterprise_CustomerSegment_Model_Segment_Condition_Product_Combine_List
     */
    public function loadValueOptions()
    {
        $this->setValueOption(array(
            self::CART      => Mage::helper('enterprise_customersegment')->__('Shopping Cart'),
            self::WISHLIST  => Mage::helper('enterprise_customersegment')->__('Wishlist'),
        ));
        return $this;
    }

    /**
     * Get input type for attribute value.
     *
     * @return string
     */
    public function getValueElementType()
    {
        return 'select';
    }

    /**
     * Prepare operator select options
     *
     * @return Enterprise_CustomerSegment_Model_Segment_Condition_Product_Combine_List
     */
    public function loadOperatorOptions()
    {
        parent::loadOperatorOptions();
        $this->setOperatorOption(array(
            '=='  => Mage::helper('rule')->__('found'),
            '!='  => Mage::helper('rule')->__('not found')
        ));
        return $this;
    }

    /**
     * Get HTML of condition string
     *
     * @return string
     */
    public function asHtml()
    {
        return $this->getTypeElementHtml()
            . Mage::helper('enterprise_customersegment')->__('If Product is %s in the %s with %s of these Conditions match:',
                $this->getOperatorElementHtml(), $this->getValueElementHtml(), $this->getAggregatorElement()->getHtml())
            . $this->getRemoveLinkHtml();
    }

    /**
     * Build query for matching shopping cart/wishlist items
     *
     * @param $customer
     * @param int | Zend_Db_Expr $website
     * @return Varien_Db_Select
     */
    protected function _prepareConditionsSql($customer, $website)
    {
        $select = $this->getResource()->createSelect();

        switch ($this->getValue()) {
            case self::WISHLIST:
                $select->from(
                    array('item' => $this->getResource()->getTable('wishlist/item')),
                    array(new Zend_Db_Expr(1))
                );
                $conditions = "item.wishlist_id = list.wishlist_id";
                $select->joinInner(
                    array('list' => $this->getResource()->getTable('wishlist/wishlist')),
                    $conditions,
                    array()
                );
                $this->_limitByStoreWebsite($select, $website, 'item.store_id');
                break;
            default:
                $select->from(
                    array('item' => $this->getResource()->getTable('sales/quote_item')),
                    array(new Zend_Db_Expr(1))
                );
                $conditions = "item.quote_id = list.entity_id";
                $select->joinInner(
                    array('list' => $this->getResource()->getTable('sales/quote')),
                    $conditions,
                    array()
                );
                $this->_limitByStoreWebsite($select, $website, 'list.store_id');
                break;
        }

        $select->where($this->_createCustomerFilter($customer, 'list.customer_id'));
        $select->limit(1);

        return $select;
    }

    /**
     * Check if validation should be strict
     *
     * @return bool
     */
    protected function _getRequiredValidation()
    {
        return ($this->getOperator() == '==');
    }

    /**
     * Get SQL select for matching customer to segment condition
     *
     * @param $customer
     * @param $website
     * @return Varien_Db_Select
     */
    public function getConditionsSql($customer, $website)
    {
        $select = parent::getConditionsSql($customer, $website);

        return $this->_getRequiredValidation() ? $select : "IF(({$select}),NULL,1)";
    }

    /**
     * Get field names map for subfilter conditions
     *
     * @return array
     */
    protected function _getSubfilterMap()
    {
        switch ($this->getValue()) {
            case self::WISHLIST:
                $dateField = 'item.added_at';
                break;

            default:
                $dateField = 'item.created_at';
                break;
        }

        return array(
            'product' => 'item.product_id',
            'date'    => $dateField
        );
    }
}
