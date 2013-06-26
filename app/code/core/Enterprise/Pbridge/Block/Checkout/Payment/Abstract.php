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
 * @package     Enterprise_PBridge
 * @author      Magento Core Team <core@magentocommerce.com>
 */
abstract class Enterprise_PBridge_Block_Checkout_Payment_Abstract extends Mage_Payment_Block_Form
{
    /**
     * Code of payment method
     *
     * @var string
     */
    protected $_code;

    /**
     * Default template for payment form block
     *
     * @var string
     */
    protected $_template = 'pbridge/checkout/payment/pbridge.phtml';

    /**
     * Return payment method code
     *
     *  @return string
     */
    public function getCode()
    {
        if (!$this->_code) {
            Mage::throwException(Mage::helper('enterprise_pbridge')->__('Can not retrieve requested gateway code'));
        }
        return $this->_code;
    }

    /**
     * Return redirect url for Payment Bridge application
     *
     * @return string
     */
    public function getRedirectUrl()
    {
        return $this->getUrl('enterprise_pbridge/pbridge/result', array('_current' => true));
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
            'request_gateway_code' => $this->getCode()
        ));
        return $sourceUrl;
    }
}
