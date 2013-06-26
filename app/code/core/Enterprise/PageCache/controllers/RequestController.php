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
 * @package     Enterprise_PageCache
 * @copyright   Copyright (c) 2010 Magento Inc. (http://www.magentocommerce.com)
 * @license     http://www.magentocommerce.com/license/enterprise-edition
 */
class Enterprise_PageCache_RequestController extends Mage_Core_Controller_Front_Action
{
    /**
     * Request processing action
     */
    public function processAction()
    {
        $processor  = Mage::getSingleton('enterprise_pagecache/processor');
        $content    = Mage::registry('cached_page_content');
        $containers = Mage::registry('cached_page_containers');
        foreach ($containers as $container) {
            $container->applyInApp($content);
        }
        $this->getResponse()->appendBody($content);
        // save session cookie lifetime info
        $cacheId = $processor->getSessionInfoCacheId();
        $sessionInfo = Mage::app()->loadCache($cacheId);
        if ($sessionInfo) {
            $sessionInfo = unserialize($sessionInfo);
        } else {
            $sessionInfo = array();
        }
        $session = Mage::getSingleton('core/session');
        $cookieName = $session->getSessionName();
        $cookieInfo = array(
            'lifetime' => $session->getCookie()->getLifetime(),
            'path'     => $session->getCookie()->getPath(),
            'domain'   => $session->getCookie()->getDomain(),
            'secure'   => $session->getCookie()->isSecure(),
            'httponly' => $session->getCookie()->getHttponly(),
        );
        if (!isset($sessionInfo[$cookieName]) || $sessionInfo[$cookieName] != $cookieInfo) {
            $sessionInfo[$cookieName] = $cookieInfo;
            // customer cookies have to be refreshed as well as the session cookie
            $sessionInfo[Enterprise_PageCache_Model_Cookie::COOKIE_CUSTOMER] = $cookieInfo;
            $sessionInfo[Enterprise_PageCache_Model_Cookie::COOKIE_CUSTOMER_GROUP] = $cookieInfo;
            $sessionInfo = serialize($sessionInfo);
            Mage::app()->saveCache($sessionInfo, $cacheId, array(Enterprise_PageCache_Model_Processor::CACHE_TAG));
        }
    }
}
