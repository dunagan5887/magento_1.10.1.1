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

class Enterprise_PageCache_Model_Observer
{
    /*
     * Design exception key
     */
    const XML_PATH_DESIGN_EXCEPTION = 'design/package/ua_regexp';

    /**
     * @var Enterprise_PageCache_Model_Processor
     */
    protected $_processor;
    protected $_config;
    protected $_isEnabled;

    /**
     * Class constructor
     */
    public function __construct()
    {
        $this->_processor = Mage::getSingleton('enterprise_pagecache/processor');
        $this->_config    = Mage::getSingleton('enterprise_pagecache/config');
        $this->_isEnabled = Mage::app()->useCache('full_page');
    }

    /**
     * Check if full page cache is enabled
     * @return bool
     */
    public function isCacheEnabled()
    {
        return $this->_isEnabled;
    }

    /**
     * Save page body to cache storage
     *
     * @param Varien_Event_Observer $observer
     * @return Enterprise_PageCache_Model_Observer
     */
    public function cacheResponse(Varien_Event_Observer $observer)
    {
        if (!$this->isCacheEnabled()) {
            return $this;
        }
        $frontController = $observer->getEvent()->getFront();
        $request = $frontController->getRequest();
        $response = $frontController->getResponse();
        $this->_saveDesignException();
        $this->_processor->processRequestResponse($request, $response);
        return $this;
    }

    /**
     * Check when cache should be disabled
     *
     * @param $observer
     */
    public function processPreDispatch(Varien_Event_Observer $observer)
    {
        if (!$this->isCacheEnabled()) {
            return $this;
        }
        $action = $observer->getEvent()->getControllerAction();
        /* @var $request Mage_Core_Controller_Request_Http */
        $request = $action->getRequest();

        $noCache = $this->_getCookie()->get(Enterprise_PageCache_Model_Processor::NO_CACHE_COOKIE);
        if ($noCache) {
            Mage::getSingleton('catalog/session')->setParamsMemorizeDisabled(false);
            $this->_getCookie()->renew(Enterprise_PageCache_Model_Processor::NO_CACHE_COOKIE);
        } elseif ($action) {
            Mage::getSingleton('catalog/session')->setParamsMemorizeDisabled(true);
        }
        /**
         * Check if request will be cached
         */
        if ($this->_processor->canProcessRequest($request)) {
            Mage::app()->getCacheInstance()->banUse(Mage_Core_Block_Abstract::CACHE_GROUP); // disable blocks cache
        }
        $this->_getCookie()->updateCustomerCookies();
        return $this;
    }

    /**
     * Checks whether exists design exception value in cache.
     * If not, gets it from config and puts into cache
     *
     * @return Enterprise_PageCache_Model_Observer
     */
    protected function _saveDesignException()
    {
        if (!$this->isCacheEnabled()) {
            return $this;
        }
        $cacheId = Enterprise_PageCache_Model_Processor::DESIGN_EXCEPTION_KEY;

        $exception = Mage::app()->loadCache($cacheId);
        if (!$exception) {
            $exception = Mage::getStoreConfig(self::XML_PATH_DESIGN_EXCEPTION);
            Mage::app()->saveCache($exception, $cacheId);
            $this->_processor->refreshRequestIds();
        }
        return $this;
    }

    /**
     * model_load_after event processor. Collect tags of all loaded entities
     *
     * @param $observer
     */
    public function registerModelTag(Varien_Event_Observer $observer)
    {
        if (!$this->isCacheEnabled()) {
            return $this;
        }
        $object = $observer->getEvent()->getObject();
        if ($object && $object->getId()) {
            $tags = $object->getCacheIdTags();
            if ($tags) {
                $this->_processor->addRequestTag($tags);
            }
        }
    }

    /**
     * Check category state on post dispatch to allow category page be cached
     *
     * @param Varien_Event_Observer $observer
     */
    public function checkCategoryState(Varien_Event_Observer $observer)
    {
        if (!$this->isCacheEnabled()) {
            return $this;
        }
        $category = Mage::registry('current_category');
        /**
         * Categories with category event can't be cached
         */
        if ($category && $category->getEvent()) {
            $request = $observer->getEvent()->getControllerAction()->getRequest();
            $request->setParam('no_cache', true);
        }
        return $this;
    }

    /**
     * Check product state on post dispatch to allow product page be cached
     *
     * @param Varien_Event_Observer $observer
     */
    public function checkProductState(Varien_Event_Observer $observer)
    {
        if (!$this->isCacheEnabled()) {
            return $this;
        }
        $product = Mage::registry('current_product');
        /**
         * Categories with category event can't be cached
         */
        if ($product && $product->getEvent()) {
            $request = $observer->getEvent()->getControllerAction()->getRequest();
            $request->setParam('no_cache', true);
        }
        return $this;
    }

    /**
     * Check if data changes duering object save affect cached pages
     *
     * @param Varien_Event_Observer $observer
     */
    public function validateDataChanges(Varien_Event_Observer $observer)
    {
        if (!$this->isCacheEnabled()) {
            return $this;
        }
        $object = $observer->getEvent()->getObject();
        $object = Mage::getModel('enterprise_pagecache/validator')->checkDataChange($object);
    }

    /**
     * Check if data delete affect cached pages
     *
     * @param Varien_Event_Observer $observer
     */
    public function validateDataDelete(Varien_Event_Observer $observer)
    {
        if (!$this->isCacheEnabled()) {
            return $this;
        }
        $object = $observer->getEvent()->getObject();
        $object = Mage::getModel('enterprise_pagecache/validator')->checkDataDelete($object);
    }

    /**
     * Clean full page cache
     */
    public function cleanCache()
    {
        Mage::app()->cleanCache(Enterprise_PageCache_Model_Processor::CACHE_TAG);
        return $this;
    }

    /**
     * Invalidate full page cache
     */
    public function invalidateCache()
    {
        Mage::app()->getCacheInstance()->invalidateType('full_page');
        return $this;
    }

    /**
     * Render placeholder tags around the block if needed
     *
     * @param Varien_Event_Observer $observer
     */
    public function renderBlockPlaceholder(Varien_Event_Observer $observer)
    {
        if (!$this->_isEnabled) {
            return $this;
        }
        $block = $observer->getEvent()->getBlock();
        $transport = $observer->getEvent()->getTransport();
        $placeholder = $this->_config->getBlockPlaceholder($block);
        if ($transport && $placeholder) {
            $blockHtml = $transport->getHtml();
            $blockHtml = $placeholder->getStartTag() . $blockHtml . $placeholder->getEndTag();
            $transport->setHtml($blockHtml);
        }
        return $this;
    }

    /**
     * Check cache settings for specific block type and associate block to container if needed
     *
     * @param Varien_Event_Observer $observer
     * @deprecated after 1.8
     */
    public function blockCreateAfter(Varien_Event_Observer $observer)
    {
        if (!$this->_isEnabled) {
            return $this;
        }
        $block  = $observer->getEvent()->getBlock();
        $placeholder = $this->_config->getBlockPlaceholder($block);
        if ($placeholder) {
            $block->setFrameTags($placeholder->getStartTag(), $placeholder->getEndTag());
        }
        return $this;
    }

    /**
     * Set cart hash in cookie on quote change
     *
     * @param Varien_Event_Observer $observer
     */
    public function registerQuoteChange(Varien_Event_Observer $observer)
    {
        if (!$this->isCacheEnabled()) {
            return $this;
        }
        /** @var Mage_Sales_Model_Quote */
        $quote = ($observer->getEvent()->getQuote()) ? $observer->getEvent()->getQuote() :
            $observer->getEvent()->getQuoteItem()->getQuote();
        $this->_getCookie()->setObscure(Enterprise_PageCache_Model_Cookie::COOKIE_CART, 'quote_' . $quote->getId());

        $cacheTag = md5(Enterprise_PageCache_Model_Container_Sidebar_Cart::CACHE_TAG_PREFIX
            . $this->_getCookie()->get(Enterprise_PageCache_Model_Cookie::COOKIE_CART)
            . $this->_getCookie()->get(Enterprise_PageCache_Model_Cookie::COOKIE_CUSTOMER));
        Mage::app()->getCache()->clean(Zend_Cache::CLEANING_MODE_MATCHING_ANY_TAG, array($cacheTag));

        return $this;
    }

    /**
     * Set compare list in cookie on list change. Also modify recently compared cookie.
     *
     * @param Varien_Event_Observer $observer
     */
    public function registerCompareListChange(Varien_Event_Observer $observer)
    {
        if (!$this->isCacheEnabled()) {
            return $this;
        }

        $listItems = Mage::helper('catalog/product_compare')->getItemCollection();
        $previouseList = $this->_getCookie()->get(Enterprise_PageCache_Model_Cookie::COOKIE_COMPARE_LIST);
        $previouseList = (empty($previouseList)) ? array() : explode(',', $previouseList);

        $ids = array();
        foreach ($listItems as $item) {
            $ids[] = $item->getId();
        }
        sort($ids);
        $this->_getCookie()->set(Enterprise_PageCache_Model_Cookie::COOKIE_COMPARE_LIST, implode(',', $ids));

        //Recenlty compared products processing
        $recentlyComparedProducts = $this->_getCookie()
            ->get(Enterprise_PageCache_Model_Cookie::COOKIE_RECENTLY_COMPARED);
        $recentlyComparedProducts = (empty($recentlyComparedProducts)) ? array()
            : explode(',', $recentlyComparedProducts);

        //Adding products deleted from compare list to "recently compared products"
        $deletedProducts = array_diff($previouseList, $ids);
        $recentlyComparedProducts = array_merge($recentlyComparedProducts, $deletedProducts);

        //Removing products from recently product list if it's present in compare list
        $addedProducts = array_diff($ids, $previouseList);
        $recentlyComparedProducts = array_diff($recentlyComparedProducts, $addedProducts);

        $recentlyComparedProducts = array_unique($recentlyComparedProducts);
        sort($recentlyComparedProducts);

        $this->_getCookie()->set(Enterprise_PageCache_Model_Cookie::COOKIE_RECENTLY_COMPARED,
            implode(',', $recentlyComparedProducts));

       return $this;
    }

    /**
     * Set new message cookie on adding messsage to session.
     *
     * @param Varien_Event_Observer $observer
     */
    public function processNewMessage(Varien_Event_Observer $observer)
    {
        if (!$this->isCacheEnabled()) {
            return $this;
        }
        $this->_getCookie()->set(Enterprise_PageCache_Model_Cookie::COOKIE_MESSAGE, '1');
        return $this;
    }

    /**
     * Set cookie for logged in customer
     *
     * @param Varien_Event_Observer $observer
     */
    public function customerLogin(Varien_Event_Observer $observer)
    {
        if (!$this->isCacheEnabled()) {
            return $this;
        }
        $this->_getCookie()->updateCustomerCookies();

        // update customer viewed products index
        try {
            $productIds = $this->_getCookie()->get(Enterprise_PageCache_Model_Container_Viewedproducts::COOKIE_NAME);
            if ($productIds) {
                $productIds = explode(',', $productIds);
                Mage::getModel('reports/product_index_viewed')->registerIds($productIds);
            }
        } catch (Exception $e) {
            Mage::logException($e);
        }

        // renew customer viewed product ids cookie
        $countLimit = Mage::getStoreConfig(Mage_Reports_Block_Product_Viewed::XML_PATH_RECENTLY_VIEWED_COUNT);
        $collection = Mage::getResourceModel('reports/product_index_viewed_collection')
            ->addIndexFilter()
            ->setAddedAtOrder()
            ->setPageSize($countLimit)
            ->setCurPage(1);
        Mage::getSingleton('catalog/product_visibility')->addVisibleInSiteFilterToCollection($collection);
        $productIds = $collection->load()->getLoadedIds();
        $productIds = implode(',', $productIds);
        Enterprise_PageCache_Model_Cookie::registerViewedProducts($productIds, $countLimit, false);

        return $this;

    }

    /**
     * Remove customer cookie
     *
     * @param Varien_Event_Observer $observer
     */
    public function customerLogout(Varien_Event_Observer $observer)
    {
        if (!$this->isCacheEnabled()) {
            return $this;
        }
        $this->_getCookie()->updateCustomerCookies();
        return $this;
    }

    /**
     * Set wishlist hash in cookie on wishlist change
     *
     * @param Varien_Event_Observer $observer
     */
    public function registerWishlistChange(Varien_Event_Observer $observer)
    {
        if (!$this->isCacheEnabled()) {
            return $this;
        }

        $cookieValue = '';
        foreach (Mage::helper('wishlist')->getWishlistItemCollection() as $item) {
            $cookieValue .= ($cookieValue ? '_' : '') . $item->getId();
        }

        // Wishlist sidebar hash
        $this->_getCookie()->setObscure(Enterprise_PageCache_Model_Cookie::COOKIE_WISHLIST, $cookieValue);

        // Wishlist items count hash for top link
        $this->_getCookie()->setObscure(Enterprise_PageCache_Model_Cookie::COOKIE_WISHLIST_ITEMS,
            'wishlist_item_count_' . Mage::helper('wishlist')->getItemCount());

        return $this;
    }

    /**
     * Clean order sidebar cache
     *
     * @param Varien_Event_Observer $observer
     */
    public function registerNewOrder(Varien_Event_Observer $observer)
    {
        if (!$this->isCacheEnabled()) {
            return $this;
        }

        // Customer order sidebar tag
        $cacheTag = md5(Enterprise_PageCache_Model_Container_Orders::CACHE_TAG_PREFIX
            . $this->_getCookie()->get(Enterprise_PageCache_Model_Cookie::COOKIE_CUSTOMER));

        Mage::app()->getCache()->clean(Zend_Cache::CLEANING_MODE_MATCHING_ANY_TAG, array($cacheTag));
        return $this;
    }

    /**
     * Remove new message cookie on clearing session messages.
     *
     * @param Varien_Event_Observer $observer
     */
    public function processMessageClearing(Varien_Event_Observer $observer)
    {
        if (!$this->isCacheEnabled()) {
            return $this;
        }
        $this->_getCookie()->delete(Enterprise_PageCache_Model_Cookie::COOKIE_MESSAGE);
        return $this;
    }

    /**
     * Resave exception rules to cache storage
     *
     * @param Varien_Event_Observer $observer
     */
    public function registerDesignExceptionsChange(Varien_Event_Observer $observer)
    {
        $object = $observer->getDataObject();
        Mage::app()->saveCache($object->getValue(), Enterprise_PageCache_Model_Processor::DESIGN_EXCEPTION_KEY);
        return $this;
    }

    /**
     * Retrieve cookie instance
     *
     * @return Enterprise_PageCache_Model_Cookie
     */
    protected function _getCookie()
    {
        return Mage::getSingleton('enterprise_pagecache/cookie');
    }

    /**
     * Check if last viewed product id should be processed after cached product view
     * @deprecated after 1.8 - added dynamic block generation
     */
    protected function _checkViewedProducts()
    {
        $varName = Enterprise_PageCache_Model_Processor::LAST_PRODUCT_COOKIE;
        $productId = (int) Mage::getSingleton('core/cookie')->get($varName);
        if ($productId) {
            $model = Mage::getModel('reports/product_index_viewed');
            if (!$model->getCount()) {
                $product = Mage::getModel('catalog/product')->load($productId);
                if ($product->getId()) {
                    $model->setProductId($productId)
                        ->save()
                        ->calculate();
                }
            }
            Mage::getSingleton('core/cookie')->delete($varName);
        }
    }
}
