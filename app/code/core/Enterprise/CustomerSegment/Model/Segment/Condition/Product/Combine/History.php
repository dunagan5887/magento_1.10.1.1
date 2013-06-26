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
 * Last viewed/orderd items conditions combine
 */
class Enterprise_CustomerSegment_Model_Segment_Condition_Product_Combine_History
    extends Enterprise_CustomerSegment_Model_Condition_Combine_Abstract
{
    /**
     * Flag of using History condition (for conditions of Product_Attribute)
     *
     * @var bool
     */
    protected $_combineHistory = true;

    const VIEWED    = 'viewed_history';
    const ORDERED   = 'ordered_history';

    protected $_inputType = 'select';

    public function __construct()
    {
        parent::__construct();
        $this->setType('enterprise_customersegment/segment_condition_product_combine_history');
        $this->setValue(self::VIEWED);
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
            case self::ORDERED:
                $events = array('sales_order_save_commit_after');
                break;
            default:
                $events = array('catalog_controller_product_view');
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
     * @return Enterprise_CustomerSegment_Model_Segment_Condition_Product_Combine_History
     */
    public function loadValueOptions()
    {
        $this->setValueOption(array(
            self::VIEWED  => Mage::helper('enterprise_customersegment')->__('viewed'),
            self::ORDERED => Mage::helper('enterprise_customersegment')->__('ordered'),
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
     * @return Enterprise_CustomerSegment_Model_Segment_Condition_Product_Combine_History
     */
    public function loadOperatorOptions()
    {
        parent::loadOperatorOptions();
        $this->setOperatorOption(array(
            '=='  => Mage::helper('rule')->__('was'),
            '!='  => Mage::helper('rule')->__('was not')
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
            . Mage::helper('enterprise_customersegment')->__('If Product %s %s and matches %s of these Conditions:',
                $this->getOperatorElementHtml(), $this->getValueElementHtml(), $this->getAggregatorElement()->getHtml())
            . $this->getRemoveLinkHtml();
    }

    /**
     * Build query for matching last viewed/orderd items
     *
     * @param $customer
     * @param int | Zend_Db_Expr $website
     * @return Varien_Db_Select
     */
    protected function _prepareConditionsSql($customer, $website)
    {
        $select = $this->getResource()->createSelect();

        switch ($this->getValue()) {
            case self::ORDERED:
                $select->from(
                    array('item' => $this->getResource()->getTable('sales/order_item')),
                    array(new Zend_Db_Expr(1))
                );
                $select->joinInner(
                    array('order' => $this->getResource()->getTable('sales/order')),
                    'item.order_id = order.entity_id',
                    array()
                );
                $select->where($this->_createCustomerFilter($customer, 'order.customer_id'));
                $this->_limitByStoreWebsite($select, $website, 'order.store_id');
                break;
            default:
                $select->from(
                    array('item' => $this->getResource()->getTable('reports/viewed_product_index')),
                    array(new Zend_Db_Expr(1))
                );
                $select->where($this->_createCustomerFilter($customer, 'item.customer_id'));
                $this->_limitByStoreWebsite($select, $website, 'item.store_id');
                break;
        }

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
            case self::ORDERED:
                $dateField = 'item.created_at';
                break;

            default:
                $dateField = 'item.added_at';
                break;
        }

        return array(
            'product' => 'item.product_id',
            'date'    => $dateField
        );
    }
}
