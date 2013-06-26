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
 * Pbridge API model
 *
 * @category    Enterprise
 * @package     Enterprise_Pbridge
 * @author      Magento Core Team <core@magentocommerce.com>
 */
class Enterprise_Pbridge_Model_Payment_Method_Pbridge_Api extends Varien_Object
{
    /**
     * Api response
     *
     * @var $_response array
     */
    protected $_response = array();

    /**
     * Make a call to Payment Bridge service with given request parameters
     *
     * @param array $request
     * @return array
     * @throws Mage_Core_Exception
     */
    protected function _call(array $request)
    {
        $response = null;
        $debugData = array('request' => $request);
        try {
            $http = new Varien_Http_Adapter_Curl();
            $config = array('timeout' => 30);
            $http->setConfig($config);
            $http->write(Zend_Http_Client::POST, $this->getPbridgeEndpoint(), '1.1', array(), $this->_prepareRequestParams($request));
            $response = $http->read();
            $http->close();
        } catch (Exception $e) {
            $debugData['result'] = array('error' => $e->getMessage(), 'code' => $e->getCode());
            $this->_debug($debugData);
            throw $e;
        }

        $this->_debug($response);

        if ($response) {

            $response = preg_split('/^\r?$/m', $response, 2);
            $response = Mage::helper('core')->jsonDecode(trim($response[1]));

            $debugData['result'] = $response;
            $this->_debug($debugData);

            if ($http->getErrno()) {
                Mage::logException(new Exception(
                    sprintf('Payment Bridge CURL connection error #%s: %s', $http->getErrno(), $http->getError())
                ));
                Mage::throwException(
                    Mage::helper('enterprise_pbridge')->__('Unable to communicate with Payment Bridge service.')
                );
            }
            if (isset($response['status']) && $response['status'] == 'Success') {
                $this->_response = $response;
                return true;
            }
        }
        $this->_handleError($response);
        $this->_response = $response;
        return false;
    }

    /**
     * Handle error of given response
     *
     * @param array $response
     * @return void
     * @throws Mage_Core_Exception
     */
    protected function _handleError($response)
    {
        if (isset($response['status']) && $response['status'] == 'Fail' && isset($response['error'])) {
            Mage::throwException(Mage::helper('enterprise_pbridge')->__('Payment Bridge service error: %s', $response['error']));
        }
        Mage::throwException(Mage::helper('enterprise_pbridge')->__('Payment Bridge service error.'));
    }

    /**
     * Prepare, merge, encrypt required params for Payment Bridge and payment request params.
     * Return request params as http query string
     *
     * @param array $request
     * @return string
     */
    protected function _prepareRequestParams($request)
    {
        $request['action'] = 'Payments';
        $request['token'] = $this->getMethodInstance()->getPbridgeResponse('token');
        $request = Mage::helper('enterprise_pbridge')->getRequestParams($request);
        $request = array('data' => Mage::helper('enterprise_pbridge')->encrypt(serialize($request)));
        return http_build_query($request, '', '&');
    }

    /**
     * Retrieve Payment Bridge servise URL
     *
     * @return string
     */
    public function getPbridgeEndpoint()
    {
        return Mage::helper('enterprise_pbridge')->getRequestUrl();
    }

    /**
     * Log debug data to file
     *
     * @param mixed $debugData
     * @return void
     */
    protected function _debug($debugData)
    {
        $this->_debugFlag = (bool)Mage::getStoreConfigFlag('payment/pbridge/debug');
        if ($this->_debugFlag) {
            Mage::getModel('core/log_adapter', 'payment_pbridge.log')
//               ->setFilterDataKeys($this->_debugReplacePrivateDataKeys)//check this
               ->log($debugData);
        }
    }

    public function validateToken($orderId)
    {
        $this->_call(array(
            'client_identifier' => $orderId,
            'payment_action' => 'validate_token'
        ));
        return $this;
    }

    /**
     * Authorize
     *
     * @param Varien_Object $request
     * @return Enterprise_Pbridge_Model_Payment_Method_Pbridge_Api
     */
    public function doAuthorize($request)
    {
        $request->setData('payment_action', 'place');
        $this->_call($request->getData());
        return $this;
    }

    /**
     * Capture
     *
     * @param Varien_Object $request
     * @return Enterprise_Pbridge_Model_Payment_Method_Pbridge_Api
     */
    public function doCapture($request)
    {
        $request->setData('payment_action', 'capture');
        $this->_call($request->getData());
        return $this;
    }

    /**
     * Refund
     *
     * @param Varien_Object $request
     * @return Enterprise_Pbridge_Model_Payment_Method_Pbridge_Api
     */
    public function doRefund($request)
    {
        $request->setData('payment_action', 'refund');
        $this->_call($request->getData());
        return $this;
    }

    /**
     * Void
     *
     * @param Varien_Object $request
     * @return Enterprise_Pbridge_Model_Payment_Method_Pbridge_Api
     */
    public function doVoid($request)
    {
        $request->setData('payment_action', 'void');
        $this->_call($request->getData());
        return $this;
    }

    /**
     * Return API response
     */
    public function getResponse()
    {
        return $this->_response;
    }
}
