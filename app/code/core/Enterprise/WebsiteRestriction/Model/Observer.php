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
 * @package     Enterprise_WebsiteRestriction
 * @copyright   Copyright (c) 2010 Magento Inc. (http://www.magentocommerce.com)
 * @license     http://www.magentocommerce.com/license/enterprise-edition
 */

/**
 * Private sales and stubs observer
 *
 */
class Enterprise_WebsiteRestriction_Model_Observer
{
    /**
     * Implement website stub or private sales restriction
     *
     * @param Varien_Event_Observer $observer
     */
    public function restrictWebsite($observer)
    {
        /* @var $controller Mage_Core_Controller_Front_Action */
        $controller = $observer->getEvent()->getControllerAction();

        if (!Mage::app()->getStore()->isAdmin()) {
            $dispatchResult = new Varien_Object(array('should_proceed' => true));
            Mage::dispatchEvent('websiterestriction_frontend', array('controller' => $controller, 'result' => $dispatchResult));
            if (!$dispatchResult->getShouldProceed()) {
                return;
            }
            if (!(int)Mage::getStoreConfig('general/restriction/is_active')) {
                return;
            }
            /* @var $request Mage_Core_Controller_Request_Http */
            $request    = $controller->getRequest();
            /* @var $response Mage_Core_Controller_Response_Http */
            $response   = $controller->getResponse();
            switch ((int)Mage::getStoreConfig('general/restriction/mode')) {
                // show only landing page with 503 or 200 code
                case Enterprise_WebsiteRestriction_Model_Mode::ALLOW_NONE:
                    if ($controller->getFullActionName() !== 'restriction_index_stub') {
                        $request->setModuleName('restriction')
                            ->setControllerName('index')
                            ->setActionName('stub')
                            ->setDispatched(false);
                        return;
                    }
                    if (Enterprise_WebsiteRestriction_Model_Mode::HTTP_503 === (int)Mage::getStoreConfig('general/restriction/http_status')) {
                        $response->setHeader('HTTP/1.1','503 Service Unavailable');
                    }
                    break;

                case Enterprise_WebsiteRestriction_Model_Mode::ALLOW_REGISTER:
                    // break intentionally omitted

                // redirect to landing page/login
                case Enterprise_WebsiteRestriction_Model_Mode::ALLOW_LOGIN:
                    if (!Mage::helper('customer')->isLoggedIn()) {
                        // see whether redirect is required and where
                        $redirectUrl = false;
                        $allowedActionNames = array_keys(Mage::getConfig()
                            ->getNode('frontend/enterprise/websiterestriction/full_action_names/generic')->asArray()
                        );
                        if (Mage::helper('customer')->isRegistrationAllowed()) {
                            foreach(array_keys(Mage::getConfig()->getNode('frontend/enterprise/websiterestriction/full_action_names/register')
                                ->asArray()) as $fullActionName) {
                                $allowedActionNames[] = $fullActionName;
                            }
                        }

                        // to specified landing page
                       if (Enterprise_WebsiteRestriction_Model_Mode::HTTP_302_LANDING
                            === (int)Mage::getStoreConfig('general/restriction/http_redirect')) {
                            $allowedActionNames[] = 'cms_page_view';
                            $pageIdentifier = Mage::getStoreConfig('general/restriction/cms_page');
                            if ((!in_array($controller->getFullActionName(), $allowedActionNames))
                                || $request->getParam('page_id') === $pageIdentifier) {
                                $redirectUrl = Mage::getUrl('', array('_direct' => $pageIdentifier));
                            }
                        }
                        // to login form
                        elseif (!in_array($controller->getFullActionName(), $allowedActionNames)) {
                            $redirectUrl = Mage::getUrl('customer/account/login');
                        }

                        if ($redirectUrl) {
                            $response->setRedirect($redirectUrl);
                            $controller->setFlag('', Mage_Core_Controller_Varien_Action::FLAG_NO_DISPATCH, true);
                        }
                        if (Mage::getStoreConfigFlag('customer/startup/redirect_dashboard')) {
                            $afterLoginUrl = Mage::helper('customer')->getDashboardUrl();
                        } else {
                            $afterLoginUrl = Mage::getUrl();
                        }
                        Mage::getSingleton('core/session')->setWebsiteRestrictionAfterLoginUrl($afterLoginUrl);
                    }
                    elseif (Mage::getSingleton('core/session')->hasWebsiteRestrictionAfterLoginUrl()) {
                        $response->setRedirect(Mage::getSingleton('core/session')->getWebsiteRestrictionAfterLoginUrl(true));
                        $controller->setFlag('', Mage_Core_Controller_Varien_Action::FLAG_NO_DISPATCH, true);
                    }
                    break;
            }
        }
    }

    /**
     * Attempt to disallow customers registration
     *
     * @param Varien_Event_Observer $observer
     */
    public function restrictCustomersRegistration($observer)
    {
        $result = $observer->getEvent()->getResult();
        if ((!Mage::app()->getStore()->isAdmin()) && $result->getIsAllowed()) {
            $result->setIsAllowed((!(bool)(int)Mage::getStoreConfig('general/restriction/is_active'))
                || (Enterprise_WebsiteRestriction_Model_Mode::ALLOW_REGISTER === (int)Mage::getStoreConfig('general/restriction/mode'))
            );
        }
    }

    /**
     * Make layout load additional handler when in private sales mode
     *
     * @param Varien_Event_Observer $observer
     */
    public function addPrivateSalesLayoutUpdate($observer)
    {
        if (in_array((int)Mage::getStoreConfig('general/restriction/mode'), array(
            Enterprise_WebsiteRestriction_Model_Mode::ALLOW_REGISTER,
            Enterprise_WebsiteRestriction_Model_Mode::ALLOW_LOGIN
            ), true)) {
            $observer->getEvent()->getLayout()->getUpdate()->addHandle('restriction_privatesales_mode');
        }
    }
}
