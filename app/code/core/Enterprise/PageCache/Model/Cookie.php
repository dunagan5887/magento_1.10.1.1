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

/**
 * Full page cache cookie model
 *
 * @category   Enterprise
 * @package    Enterprise_PageCache
 * @author     Magento Core Team <core@magentocommerce.com>
 */
class Enterprise_PageCache_Model_Cookie extends Mage_Core_Model_Cookie
{
    /**
     * Cookie names
     */
    const COOKIE_CUSTOMER           = 'CUSTOMER';
    const COOKIE_CUSTOMER_GROUP     = 'CUSTOMER_INFO';

    const COOKIE_MESSAGE            = 'NEWMESSAGE';
    const COOKIE_CART               = 'CART';
    const COOKIE_COMPARE_LIST       = 'COMPARE';
    const COOKIE_RECENTLY_COMPARED  = 'RECENTLYCOMPARED';
    const COOKIE_WISHLIST           = 'WISHLIST';
    const COOKIE_WISHLIST_ITEMS     = 'WISHLIST_CNT';

    /**
     * Subprocessors cookie names
     */
    const COOKIE_CATEGORY_PROCESSOR = 'CATEGORY_INFO';

    /**
     * Encryption salt value
     *
     * @var sting
     */
    protected $_salt = null;

    /**
     * Retrieve encryption salt
     *
     * @var string
     */
    protected function _getSalt()
    {
        if ($this->_salt === null) {
            $saltCacheId = 'full_page_cache_key';
            $this->_salt = Mage::app()->getCache()->load($saltCacheId);
            if (!$this->_salt) {
                $this->_salt = md5(microtime() . rand());
                Mage::app()->getCache()->save($this->_salt, $saltCacheId,
                    array(Enterprise_PageCache_Model_Processor::CACHE_TAG));
            }
        }
        return $this->_salt;
    }

    /**
     * Set cookie with obscure value
     *
     * @param string $name The cookie name
     * @param string $value The cookie value
     * @param int $period Lifetime period
     * @param string $path
     * @param string $domain
     * @param int|bool $secure
     * @return Mage_Core_Model_Cookie
     */
    public function setObscure($name, $value, $period = null, $path = null, $domain = null, $secure = null, $httponly = null)
    {
        $value = md5($this->_getSalt() . $value);
        return $this->set($name, $value, $period, $path, $domain, $secure, $httponly);
    }

    /**
     * Keep customer cookies synchronized with customer session
     *
     * @return Mage_Core_Model_Cookie
     */
    public function updateCustomerCookies()
    {
        /** @var Mage_Customer_Model_Session $session */
        $session = Mage::getSingleton('customer/session');
        if ($session->isLoggedIn()) {
            $this->setObscure(self::COOKIE_CUSTOMER, 'customer_' . $session->getCustomerId());
            $this->setObscure(self::COOKIE_CUSTOMER_GROUP, 'customer_group_' . $session->getCustomerGroupId());
        } else {
            $this->delete(self::COOKIE_CUSTOMER);
            $this->delete(self::COOKIE_CUSTOMER_GROUP);
        }
    }

    /**
     * Register viewed product ids in cookie
     *
     * @param int|string|array $productIds
     * @param int $countLimit
     * @param bool $append
     */
    public static function registerViewedProducts($productIds, $countLimit, $append = true)
    {
        if (!is_array($productIds)) {
            $productIds = array($productIds);
        }
        if ($append) {
            if (!empty($_COOKIE[Enterprise_PageCache_Model_Container_Viewedproducts::COOKIE_NAME])) {
                $cookieIds = $_COOKIE[Enterprise_PageCache_Model_Container_Viewedproducts::COOKIE_NAME];
                $cookieIds = explode(',', $cookieIds);
            } else {
                $cookieIds = array();
            }
            array_splice($cookieIds, 0, 0, $productIds);  // append to the beginning
        } else {
            $cookieIds = $productIds;
        }
        $cookieIds = array_unique($cookieIds);
        $cookieIds = array_slice($cookieIds, 0, $countLimit);
        $cookieIds = implode(',', $cookieIds);
        setcookie(Enterprise_PageCache_Model_Container_Viewedproducts::COOKIE_NAME, $cookieIds, 0, '/');
    }

    /**
     * Set catalog cookie
     *
     * @param string $value
     */
    public static function setCategoryCookieValue($value)
    {
        setcookie(self::COOKIE_CATEGORY_PROCESSOR, $value, 0, '/');
    }

    /**
     * Get catalog cookie
     *
     * @param string $value
     */
    public static function getCategoryCookieValue()
    {
        return (isset($_COOKIE[self::COOKIE_CATEGORY_PROCESSOR])) ? $_COOKIE[self::COOKIE_CATEGORY_PROCESSOR] : false;
    }
}
