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
 * @package     Enterprise_CustomerBalance
 * @copyright   Copyright (c) 2010 Magento Inc. (http://www.magentocommerce.com)
 * @license     http://www.magentocommerce.com/license/enterprise-edition
 */

/**
 * Customer balance block for order creation page
 *
 */
class Enterprise_CustomerBalance_Block_Adminhtml_Sales_Order_Create_Payment
extends Mage_Core_Block_Template
{
    /**
     * @var Enterprise_CustomerBalance_Model_Balance
     */
    protected $_balanceInstance;

    /**
     * Retrieve order create model
     *
     * @return Mage_Adminhtml_Model_Sales_Order_Create
     */
    protected function _getOrderCreateModel()
    {
        return Mage::getSingleton('adminhtml/sales_order_create');
    }

    /**
     * Format value as price
     *
     * @param numeric $value
     * @return string
     */
    public function formatPrice($value)
    {
        return Mage::getSingleton('adminhtml/session_quote')->getStore()->formatPrice($value);
    }

    /**
     * Balance getter
     *
     * @return float
     */
    public function getBalance()
    {
        if (!Mage::helper('enterprise_customerbalance')->isEnabled() || !$this->_getBalanceInstance()) {
            return 0.0;
        }
        return $this->_getBalanceInstance()->getAmount();
    }

    /**
     * Check whether quote uses customer balance
     *
     * @return bool
     */
    public function getUseCustomerBalance()
    {
        return $this->_getOrderCreateModel()->getQuote()->getUseCustomerBalance();
    }

    /**
     * Check whether customer balance fully covers quote
     *
     * @return bool
     */
    public function isFullyPaid()
    {
        if (!$this->_getBalanceInstance()) {
            return false;
        }
        return $this->_getBalanceInstance()->isFullAmountCovered($this->_getOrderCreateModel()->getQuote());
    }

    /**
     * Check whether quote uses customer balance
     *
     * @return bool
     */
    public function isUsed()
    {
        return $this->getUseCustomerBalance();
    }

    /**
     * Instantiate/load balance and return it
     *
     * @return Enterprise_CustomerBalance_Model_Balance|false
     */
    protected function _getBalanceInstance()
    {
        if (!$this->_balanceInstance) {
            $quote = $this->_getOrderCreateModel()->getQuote();
            if (!$quote || !$quote->getCustomerId() || !$quote->getStoreId()) {
                return false;
            }

            $store = Mage::app()->getStore($quote->getStoreId());
            $this->_balanceInstance = Mage::getModel('enterprise_customerbalance/balance')
                ->setCustomerId($quote->getCustomerId())
                ->setWebsiteId($store->getWebsiteId())
                ->loadByCustomer();
        }
        return $this->_balanceInstance;
    }
}
