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
 * Abstract payment block
 *
 * @category    Enterprise
 * @package     Enterprise_Pbridge
 * @author      Magento Core Team <core@magentocommerce.com>
 */
abstract class Enterprise_Pbridge_Block_Payment_Form_Abstract extends Mage_Payment_Block_Form
{
    /**
     * Default template for payment form block
     *
     * @var string
     */
    protected $_template = 'pbridge/checkout/payment/pbridge.phtml';

    /**
     * Default iframe block type
     *
     * @var string
     */
    protected $_iframeBlockType = 'core/template';

    /**
     * Default iframe template
     *
     * @var string
     */
    protected $_iframeTemplate = 'pbridge/checkout/payment/iframe.phtml';

    /**
     * Return original payment method code
     *
     *  @return string
     */
    public function getOriginalCode()
    {
        return $this->getMethod()->getOriginalCode();
    }

    /**
     * Return redirect url for Payment Bridge application
     *
     * @return string
     */
    public function getRedirectUrl()
    {
        return $this->getUrl('enterprise_pbridge/pbridge/result', array('_current' => true, '_secure' => true));
    }

    /**
     * Getter
     *
     * @return Mage_Sales_Model_Quote
     */
    public function getQuote()
    {
        return Mage::getSingleton('checkout/session')->getQuote();
    }

    /**
     * Getter.
     * Return Payment Bridge url with required parameters (such as merchant code, merchant key etc.)
     *
     * @return string
     */
    public function getSourceUrl()
    {
        $sourceUrl = Mage::helper('enterprise_pbridge')->getGatewayFormUrl(array(
            'redirect_url' => $this->getRedirectUrl(),
            'request_gateway_code' => $this->getOriginalCode()
        ), $this->getQuote());
        return $sourceUrl;
    }

    /**
     * Create and return iframe block
     *
     * @return Mage_Core_Block_Template
     */
    public function getIframeBlock()
    {
        $iframeBlock = $this->getLayout()->createBlock($this->_iframeBlockType)
            ->setTemplate($this->_iframeTemplate)
            ->setMethodCode($this->getMethodCode())
            ->setSourceUrl($this->getSourceUrl());
        return $iframeBlock;
    }

    /**
     * Render block HTML
     *
     * @return string
     */
    protected function _toHtml()
    {
        $this->setChild('pbridge_iframe', $this->getIframeBlock());
        return parent::_toHtml();
    }
}
