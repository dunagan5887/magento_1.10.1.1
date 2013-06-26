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
 * Shopping cart totals amount condition
 */
class Enterprise_CustomerSegment_Model_Segment_Condition_Shoppingcart_Amount
    extends Enterprise_CustomerSegment_Model_Condition_Abstract
{
    protected $_inputType = 'numeric';

    /**
     * Class constructor
     */
    public function __construct()
    {
        parent::__construct();
        $this->setType('enterprise_customersegment/segment_condition_shoppingcart_amount');
        $this->setValue(null);
    }

    /**
     * Get array of event names where segment with such conditions combine can be matched
     *
     * @return array
     */
    public function getMatchedEvents()
    {
        return array('sales_quote_save_commit_after');
    }

    /**
     * Get information for being presented in condition list
     *
     * @return array
     */
    public function getNewChildSelectOptions()
    {
        return array('value' => $this->getType(),
            'label'=>Mage::helper('enterprise_customersegment')->__('Shopping Cart Total'));
    }

    /**
     * Init available options list
     *
     * @return Enterprise_CustomerSegment_Model_Segment_Condition_Shoppingcart_Amount
     */
    public function loadAttributeOptions()
    {
        $this->setAttributeOption(array(
            'subtotal'  => Mage::helper('enterprise_customersegment')->__('Subtotal'),
            'grand_total'  => Mage::helper('enterprise_customersegment')->__('Grand Total'),
            'tax'  => Mage::helper('enterprise_customersegment')->__('Tax'),
            'shipping'  => Mage::helper('enterprise_customersegment')->__('Shipping'),
            'store_credit'  => Mage::helper('enterprise_customersegment')->__('Store Credit'),
            'gift_card'  => Mage::helper('enterprise_customersegment')->__('Gift Card'),
        ));
        return $this;
    }

    /**
     * Condition string on conditions page
     *
     * @return string
     */
    public function asHtml()
    {
        return $this->getTypeElementHtml()
            . Mage::helper('enterprise_customersegment')->__('Shopping Cart %s Amount %s %s:',
                $this->getAttributeElementHtml(), $this->getOperatorElementHtml(), $this->getValueElementHtml())
            . $this->getRemoveLinkHtml();
    }

    /**
     * Build condition limitations sql string for specific website
     *
     * @param $customer
     * @param int | Zend_Db_Expr $website
     * @return Varien_Db_Select
     */
    public function getConditionsSql($customer, $website)
    {
        $table = $this->getResource()->getTable('sales/quote');
        $addressTable = $this->getResource()->getTable('sales/quote_address');
        $operator = $this->getResource()->getSqlOperator($this->getOperator());

        $select = $this->getResource()->createSelect();
        $select->from(array('quote'=>$table), array(new Zend_Db_Expr(1)))
            ->where('quote.is_active=1')
            ->limit(1);
        $this->_limitByStoreWebsite($select, $website, 'quote.store_id');

        $joinAddress = false;
        switch ($this->getAttribute()) {
            case 'subtotal':
                $field = 'quote.base_subtotal';
                break;
            case 'grand_total':
                $field = 'quote.base_grand_total';
                break;
            case 'tax':
                $field = 'base_tax_amount';
                $joinAddress = true;
                break;
            case 'shipping':
                $field = 'base_shipping_amount';
                $joinAddress = true;
                break;
            case 'store_credit':
                $field = 'quote.base_customer_balance_amount_used';
                break;
            case 'gift_card':
                $field = 'quote.base_gift_cards_amount_used';
                break;
            default:
                Mage::throwException(Mage::helper('enterprise_customersegment')->__('Unknown quote total specified.'));
        }

        if ($joinAddress) {
            $subselect = $this->getResource()->createSelect();
            $subselect->from(
                array('address'=>$addressTable),
                array(
                    'quote_id' => 'quote_id',
                    $field     => new Zend_Db_Expr("SUM({$field})")
                )
            );

            $subselect->group('quote_id');
            $select->joinInner(array('address'=>$subselect), 'address.quote_id = quote.entity_id', array());
            $field = "address.{$field}";
        }

        $select->where("{$field} {$operator} ?", $this->getValue());
        $select->where($this->_createCustomerFilter($customer, 'customer_id'));
        return $select;
    }
}
