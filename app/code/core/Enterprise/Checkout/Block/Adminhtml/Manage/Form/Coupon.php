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
 * @package     Enterprise_Checkout
 * @copyright   Copyright (c) 2010 Magento Inc. (http://www.magentocommerce.com)
 * @license     http://www.magentocommerce.com/license/enterprise-edition
 */

/**
 * Checkout coupon code form
 *
 * @category   Enterprise
 * @package    Enterprise_Checkout
 * @author     Magento Core Team <core@magentocommerce.com>
 */
class Enterprise_Checkout_Block_Adminhtml_Manage_Form_Coupon extends Mage_Adminhtml_Block_Template
{
    /**
     * Return applied coupon code for current quote
     *
     * @return string
     */
    public function getCouponCode()
    {
        return $this->_getQuote()->getCouponCode();
    }

    /**
     * Return current quote from regisrty
     *
     * @return Mage_Sales_Model_Quote
     */
    protected function _getQuote()
    {
        return Mage::registry('checkout_current_quote');
    }

    /**
     * Button html
     *
     * @return string
     */
    public function getApplyButtonHtml()
    {
        return $this->getLayout()
            ->createBlock('adminhtml/widget_button')
                ->setData(array(
                    'id'        => 'apply_coupon',
                    'label'     => Mage::helper('enterprise_checkout')->__('Apply'),
                    'onclick'   => "checkoutObj.applyCoupon($('coupon_code').value)",
                ))
            ->toHtml();
    }

    /**
     * Apply admin acl
     */
    protected function _toHtml()
    {
        if (!Mage::getSingleton('admin/session')->isAllowed('sales/enterprise_checkout/update')) {
            return '';
        }
        return parent::_toHtml();
    }
}
