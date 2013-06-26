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
 * @package     Enterprise_Reminder
 * @copyright   Copyright (c) 2010 Magento Inc. (http://www.magentocommerce.com)
 * @license     http://www.magentocommerce.com/license/enterprise-edition
 */

/**
 * Customer cart conditions combine
 */
class Enterprise_Reminder_Model_Rule_Condition_Cart
    extends Enterprise_Reminder_Model_Condition_Combine_Abstract
{
    /**
     * class constructor
     */
    public function __construct()
    {
        parent::__construct();
        $this->setType('enterprise_reminder/rule_condition_cart');
        $this->setValue(null);
    }

    /**
     * Get list of available subconditions
     *
     * @return array
     */
    public function getNewChildSelectOptions()
    {
        return Mage::getModel('enterprise_reminder/rule_condition_cart_combine')->getNewChildSelectOptions();
    }

    /**
     * Get input type for attribute value
     *
     * @return string
     */
    public function getValueElementType()
    {
        return 'text';
    }

    /**
     * Override parent method
     *
     * @return Enterprise_Reminder_Model_Rule_Condition_Cart
     */
    public function loadValueOptions()
    {
        $this->setValueOption(array());
        return $this;
    }

    /**
     * Prepare operator select options
     *
     * @return Enterprise_Reminder_Model_Rule_Condition_Cart
     */
    public function loadOperatorOptions()
    {
        $this->setOperatorOption(array(
            '==' => Mage::helper('rule')->__('for'),
            '>'  => Mage::helper('rule')->__('for greater than'),
            '>=' => Mage::helper('rule')->__('for or greater than')
        ));
        return $this;
    }

    /**
     * Return required validation
     *
     * @return true
     */
    protected function _getRequiredValidation()
    {
        return true;
    }

    /**
     * Get HTML of condition string
     *
     * @return string
     */
    public function asHtml()
    {
        return $this->getTypeElementHtml()
            . Mage::helper('enterprise_reminder')->__('Shopping cart is not empty and abandoned %s %s days and %s of these conditions match:',
                $this->getOperatorElementHtml(),
                $this->getValueElementHtml(),
                $this->getAggregatorElement()->getHtml())
            . $this->getRemoveLinkHtml();
    }

    /**
     * Get condition SQL select
     *
     * @param   int|Zend_Db_Expr $customer
     * @param   int|Zend_Db_Expr $website
     * @return  Varien_Db_Select
     */
    protected function _prepareConditionsSql($customer, $website)
    {
        $conditionValue = (int) $this->getValue();
        if ($conditionValue < 0) {
            Mage::throwException(Mage::helper('enterprise_reminder')->__('Root shopping cart condition should have days value at least 0.'));
        }

        $table = $this->getResource()->getTable('sales/quote');
        $operator = $this->getResource()->getSqlOperator($this->getOperator());

        $select = $this->getResource()->createSelect();
        $select->from(array('quote' => $table), array(new Zend_Db_Expr(1)));

        $this->_limitByStoreWebsite($select, $website, 'quote.store_id');

        $currentDateStart = now(true);
        if ($operator == '=') {
            $select
                ->where("UNIX_TIMESTAMP('". $currentDateStart ."' - INTERVAL ? DAY) < UNIX_TIMESTAMP(quote.updated_at)",
                    $conditionValue)
                ->where("UNIX_TIMESTAMP('". $currentDateStart ."' - INTERVAL ? DAY) > UNIX_TIMESTAMP(quote.updated_at)",
                    $conditionValue - 1);
        } else {
            if ($operator == '>=') {
                if ($conditionValue > 0) {
                    $conditionValue--;
                } else {
                    $currentDateStart = now();
                }
            }

            $select
                ->where("UNIX_TIMESTAMP('". $currentDateStart ."' - INTERVAL ? DAY) {$operator} UNIX_TIMESTAMP(quote.updated_at)",
                    $conditionValue);
        }

        $select->where('quote.is_active = 1')
            ->where('quote.items_count > 0')
            ->where($this->_createCustomerFilter($customer, 'quote.customer_id'))
            ->limit(1);

        return $select;
    }

    /**
     * Get base SQL select
     *
     * @param   int|Zend_Db_Expr $customer
     * @param   int|Zend_Db_Expr $website
     * @return  Varien_Db_Select
     */
    public function getConditionsSql($customer, $website)
    {
        $select     = $this->_prepareConditionsSql($customer, $website);
        $required   = $this->_getRequiredValidation();
        $aggregator = ($this->getAggregator() == 'all') ? ' AND ' : ' OR ';
        $operator   = $required ? '=' : '<>';
        $conditions = array();

        foreach ($this->getConditions() as $condition) {
            if ($sql = $condition->getConditionsSql($customer, $website)) {
                $conditions[] = "(IFNULL(($sql), 0) {$operator} 1)";
            }
        }

        if (!empty($conditions)) {
            $select->where(implode($aggregator, $conditions));
        }

        return $select;
    }
}
