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
 * @package     Enterprise_Pbridge
 * @copyright   Copyright (c) 2010 Magento Inc. (http://www.magentocommerce.com)
 * @license     http://www.magentocommerce.com/license/enterprise-edition
 */


/**
 * Paypal Direct payment block
 *
 * @category    Enterprise
 * @package     Enterprise_Pbridge
 * @author      Magento Core Team <core@magentocommerce.com>
 */
class Enterprise_Pbridge_Block_Adminhtml_Sales_Order_Create_Abstract extends Enterprise_Pbridge_Block_Payment_Form_Abstract
{
    /**
     * Return url model class for adminhtml
     *
     *  @return string
     */
    protected function _getUrlModelClass()
    {
        return 'adminhtml/url';
    }

    /**
     * Paypal payment code
     *
     * @var string
     */
    protected $_code = Mage_Paypal_Model_Config::METHOD_WPP_DIRECT;

    /**
     * Adminhtml template for payment form block
     *
     * @var string
     */
    protected $_template = 'enterprise/pbridge/sales/order/create/pbridge.phtml';

    /**
     * Adminhtml Iframe block type
     *
     * @var string
     */
    protected $_iframeBlockType = 'adminhtml/template';

    /**
     * Adminhtml iframe template
     *
     * @var string
     */
    protected $_iframeTemplate = 'enterprise/pbridge/sales/order/create/iframe.phtml';

    /**
     * Return redirect url for Payment Bridge application
     *
     * @return string
     */
    public function getRedirectUrl()
    {
        return Mage::getModel('adminhtml/url')->getUrl('*/pbridge/result', array('store' => $this->getQuote()->getStoreId()));
    }

    /**
     * Getter
     *
     * @return Mage_Sales_Model_Quote
     */
    public function getQuote()
    {
        return Mage::getSingleton('adminhtml/session_quote')->getQuote();
    }
}
