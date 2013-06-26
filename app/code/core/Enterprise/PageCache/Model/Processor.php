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

class Enterprise_PageCache_Model_Processor
{
    const NO_CACHE_COOKIE               = 'NO_CACHE';
    const XML_NODE_ALLOWED_CACHE        = 'frontend/cache/requests';
    const XML_PATH_ALLOWED_DEPTH        = 'system/page_cache/allowed_depth';
    const XML_PATH_LIFE_TIME            = 'system/page_cache/lifetime';  /** @deprecated after 1.8 */
    const XML_PATH_CACHE_MULTICURRENCY  = 'system/page_cache/multicurrency';
    const XML_PATH_CACHE_DEBUG          = 'system/page_cache/debug';
    const REQUEST_ID_PREFIX             = 'REQEST_';
    const CACHE_TAG                     = 'FPC';  // Full Page Cache, minimize
    const DESIGN_EXCEPTION_KEY          = 'FPC_DESIGN_EXCEPTION_CACHE';
    const CACHE_SIZE_KEY                = 'FPC_CACHE_SIZE_CAHCE_KEY';
    const XML_PATH_CACHE_MAX_SIZE       = 'system/page_cache/max_cache_size';

    /**
     * @deprecated after 1.8.0.0 - moved to Enterprise_PageCache_Model_Container_Viewedproducts
     */
    const LAST_PRODUCT_COOKIE           = 'LAST_PRODUCT';

    const METADATA_CACHE_SUFFIX        = '_metadata';

    /**
     * Request identifier
     *
     * @var string
     */
    protected $_requestId;

    /**
     * Request page cache identifier
     *
     * @var string
     */
    protected $_requestCacheId;

    /**
     * Cache tags related with request
     * @var array
     */
    protected $_requestTags;

    /**
     * Cache service info
     * @var mixed
     */
    protected $_metaData = null;

    /**
     * Flag whether design exception value presents in cache
     * It always must be present (maybe serialized empty value)
     * @var boolean
     */
    protected $_designExceptionExistsInCache = false;

    /**
     * Class constructor
     */
    public function __construct()
    {
        $this->_createRequestIds();
        $this->_requestTags     = array(self::CACHE_TAG);
    }

    /**
     * Populate request ids
     * @return Enterprise_PageCache_Model_Processor
     */
    protected function _createRequestIds()
    {
        $uri = $this->_getFullPageUrl();

        //Removing get params
        $pieces = explode('?', $uri);
        $uri = array_shift($pieces);

        /**
         * Define COOKIE state
         */
        if ($uri) {
            if (isset($_COOKIE['store'])) {
                $uri = $uri.'_'.$_COOKIE['store'];
            }
            if (isset($_COOKIE['currency'])) {
                $uri = $uri.'_'.$_COOKIE['currency'];
            }
            if (isset($_COOKIE[Enterprise_PageCache_Model_Cookie::COOKIE_CUSTOMER_GROUP])) {
                $uri .= '_' . $_COOKIE[Enterprise_PageCache_Model_Cookie::COOKIE_CUSTOMER_GROUP];
            }
            $designPackage = $this->_getDesignPackage();

            if ($designPackage) {
                $uri .= '_' . $designPackage;
            }
        }

        $this->_requestId       = $uri;
        $this->_requestCacheId  = $this->prepareCacheId($this->_requestId);

        return $this;
    }

    /**
     * Refresh values of request ids
     *
     * Some parts of $this->_requestId and $this->_requestCacheId might be changed in runtime
     * E.g. we may not know about design package
     * But during cache save we need this data to be actual
     *
     * @return Enterprise_PageCache_Model_Processor
     */
    public function refreshRequestIds()
    {
        if (!$this->_designExceptionExistsInCache) {
            $this->_createRequestIds();
        }
        return $this;
    }

    /**
     * Get currenly configured design package.
     * Depends on design exception rules configuration and browser user agent
     *
     * return string|bool
     */
    protected function _getDesignPackage()
    {
        $exceptions = Mage::app()->loadCache(self::DESIGN_EXCEPTION_KEY);

        if (!$exceptions) {
            return false;
        } else {
            $this->_designExceptionExistsInCache = true;
        }

        $rules = @unserialize($exceptions);
        if (empty($rules)) {
            return false;
        }
        return Mage_Core_Model_Design_Package::getPackageByUserAgent($rules);
    }

    /**
     * Prepare page identifier
     *
     * @param string $id
     * @return string
     */
    public function prepareCacheId($id)
    {
        return self::REQUEST_ID_PREFIX . md5($id);
    }

    /**
     * Get HTTP request identifier
     *
     * @return string
     */
    public function getRequestId()
    {
        return $this->_requestId;
    }

    /**
     * Get page identifier for loading page from cache
     * @return string
     */
    public function getRequestCacheId()
    {
        return $this->_requestCacheId;
    }

    /**
     * Check if processor is allowed for current HTTP request.
     * Disable processing HTTPS requests and requests with "NO_CACHE" cookie
     *
     * @return bool
     */
    public function isAllowed()
    {
        if (!$this->_requestId) {
            return false;
        }
        if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') {
            return false;
        }
        if (isset($_COOKIE['NO_CACHE'])) {
            return false;
        }
        if (isset($_GET['no_cache'])) {
            return false;
        }

        return true;
    }

    /**
     * Get page content from cache storage
     *
     * @param string $content
     * @return string | false
     */
    public function extractContent($content)
    {
        if (!$this->_designExceptionExistsInCache) {
            //no design exception value - error
            //must be at least empty value
            return false;
        }
        if (!$content && $this->isAllowed()) {

            $subprocessorClass = $this->getMetadata('cache_subprocessor');
            if (!$subprocessorClass) {
                return $content;
            }

            /*
             * @var Enterprise_PageCache_Model_Processor_Default
             */
            $subprocessor = new $subprocessorClass;
            $cacheId = $this->prepareCacheId($subprocessor->getPageIdWithoutApp($this));

            $content = Mage::app()->loadCache($cacheId);

            if ($content) {
                if (function_exists('gzuncompress')) {
                    $content = gzuncompress($content);
                }
                $content = $this->_processContent($content);

                // restore response headers
                $responseHeaders = $this->getMetadata('response_headers');
                if (is_array($responseHeaders)) {
                    foreach ($responseHeaders as $header) {
                        Mage::app()->getResponse()->setHeader($header['name'], $header['value'], $header['replace']);
                    }
                }

                // renew recently viewed products
                $productId = Mage::app()->loadCache($this->getRequestCacheId() . '_current_product_id');
                $countLimit = Mage::app()->loadCache($this->getRecentlyViewedCountCacheId());
                if ($productId && $countLimit) {
                    Enterprise_PageCache_Model_Cookie::registerViewedProducts($productId, $countLimit);
                }
            }

        }
        return $content;
    }

    /**
     * Retrieve recently viewed count cache identifier
     *
     * @return string
     */
    public function getRecentlyViewedCountCacheId()
    {
        $cookieName = Mage_Core_Model_Store::COOKIE_NAME;
        return 'recently_viewed_count' . (isset($_COOKIE[$cookieName]) ? '_' . $_COOKIE[$cookieName] : '');
    }

    /**
     * Retrieve session info cache identifier
     *
     * @return string
     */
    public function getSessionInfoCacheId()
    {
        $cookieName = Mage_Core_Model_Store::COOKIE_NAME;
        return 'full_page_cache_session_info' . (isset($_COOKIE[$cookieName]) ? '_' . $_COOKIE[$cookieName] : '');
    }

    /**
     * Determine and process all defined containers.
     * Direct request to pagecache/request/process action if necessary for additional processing
     *
     * @param string $content
     * @return string|false
     */
    protected function _processContent($content)
    {
        $placeholders = array();
        preg_match_all(
            Enterprise_PageCache_Model_Container_Placeholder::HTML_NAME_PATTERN,
            $content, $placeholders, PREG_PATTERN_ORDER
        );
        $placeholders = array_unique($placeholders[1]);
        $containers   = array();
        foreach ($placeholders as $definition) {
            $placeholder= new Enterprise_PageCache_Model_Container_Placeholder($definition);
            $container  = $placeholder->getContainerClass();
            if (!$container) {
                continue;
            }
            $container  = new $container($placeholder);
            if (!$container->applyWithoutApp($content)) {
                $containers[] = $container;
            }
        }
        $isProcessed = empty($containers);
        // renew session cookie
        $sessionInfo = Mage::app()->loadCache($this->getSessionInfoCacheId());
        if ($sessionInfo) {
            $sessionInfo = unserialize($sessionInfo);
            foreach ($sessionInfo as $cookieName => $cookieInfo) {
                if (isset($_COOKIE[$cookieName]) && isset($cookieInfo['lifetime'])
                    && isset($cookieInfo['path']) && isset($cookieInfo['domain'])
                    && isset($cookieInfo['secure']) && isset($cookieInfo['httponly'])
                ) {
                    $lifeTime = (0 == $cookieInfo['lifetime']) ? 0 : time() + $cookieInfo['lifetime'];
                    setcookie($cookieName, $_COOKIE[$cookieName], $lifeTime,
                        $cookieInfo['path'], $cookieInfo['domain'],
                        $cookieInfo['secure'], $cookieInfo['httponly']
                    );
                }
            }
        } else {
            $isProcessed = false;
        }

        /**
         * restore session_id in content whether content is completely processed or not
         */
        $sidCookieName = $this->getMetadata('sid_cookie_name');
        $sidCookieValue = ($sidCookieName && isset($_COOKIE[$sidCookieName]) ? $_COOKIE[$sidCookieName] : '');
        Enterprise_PageCache_Helper_Url::restoreSid($content, $sidCookieValue);

        if ($isProcessed) {
            return $content;
        } else {
            Mage::register('cached_page_content', $content);
            Mage::register('cached_page_containers', $containers);
            Mage::app()->getRequest()
                ->setModuleName('pagecache')
                ->setControllerName('request')
                ->setActionName('process')
                ->isStraight(true);

            // restore original routing info
            $routingInfo = array(
                    'aliases'              => $this->getMetadata('routing_aliases'),
                    'requested_route'      => $this->getMetadata('routing_requested_route'),
                    'requested_controller' => $this->getMetadata('routing_requested_controller'),
                    'requested_action'     => $this->getMetadata('routing_requested_action')
                );

            Mage::app()->getRequest()->setRoutingInfo($routingInfo);
            return false;
        }
    }

    /**
     * Associate tag with page cache request identifier
     *
     * @param array|string $tag
     * @return Enterprise_PageCache_Model_Processor
     */
    public function addRequestTag($tag)
    {
        if (is_array($tag)) {
            $this->_requestTags = array_merge($this->_requestTags, $tag);
        } else {
            $this->_requestTags[] = $tag;
        }
        return $this;
    }

    /**
     * Get cache request associated tags
     * @return array
     */
    public function getRequestTags()
    {
        return $this->_requestTags;
    }

    /**
     * Process response body by specific request
     *
     * @param Zend_Controller_Request_Http $request
     * @param Zend_Controller_Response_Http $response
     * @return Enterprise_PageCache_Model_Processor
     */
    public function processRequestResponse(Zend_Controller_Request_Http $request,
        Zend_Controller_Response_Http $response)
    {
        /**
         * Basic validation for request processing
         */
        if ($this->canProcessRequest($request)) {
            $processor = $this->getRequestProcessor($request);
            if ($processor && $processor->allowCache($request)) {
                $this->setMetadata('cache_subprocessor', get_class($processor));

                $cacheId = $this->prepareCacheId($processor->getPageIdInApp($this));
                $content = $processor->prepareContent($response);

                /**
                 * Replace all occurrences of session_id with unique marker
                 */
                Enterprise_PageCache_Helper_Url::replaceSid($content);

                if (function_exists('gzcompress')) {
                    $content = gzcompress($content);
                }

                $contentSize = strlen($content);
                $currentStorageSize = (int) Mage::app()->loadCache(self::CACHE_SIZE_KEY);

                $maxSizeInBytes = Mage::getStoreConfig(self::XML_PATH_CACHE_MAX_SIZE) * 1024 * 1024;

                if ($currentStorageSize >= $maxSizeInBytes) {
                    Mage::app()->getCacheInstance()->invalidateType('full_page');
                    return $this;
                }

                Mage::app()->saveCache($content, $cacheId, $this->getRequestTags());

                Mage::app()->saveCache(
                    $currentStorageSize + $contentSize,
                    self::CACHE_SIZE_KEY,
                    $this->getRequestTags()
                );

                // save response headers
                $this->setMetadata('response_headers', $response->getHeaders());

                // save original routing info
                $this->setMetadata('routing_aliases', Mage::app()->getRequest()->getAliases());
                $this->setMetadata('routing_requested_route', Mage::app()->getRequest()->getRequestedRouteName());
                $this->setMetadata('routing_requested_controller',
                    Mage::app()->getRequest()->getRequestedControllerName());
                $this->setMetadata('routing_requested_action', Mage::app()->getRequest()->getRequestedActionName());

                $this->setMetadata('sid_cookie_name', Mage::getSingleton('core/session')->getSessionName());

                $this->_saveMetadata();
            }
        }
        return $this;
    }

    /**
     * Do basic validation for request to be cached
     *
     * @param Zend_Controller_Request_Http $request
     * @return bool
     */
    public function canProcessRequest(Zend_Controller_Request_Http $request)
    {
        $res = $this->isAllowed();
        $res = $res && Mage::app()->useCache('full_page');
        if ($request->getParam('no_cache')) {
            $res = false;
        }

        if ($res) {
            $maxDepth = Mage::getStoreConfig(self::XML_PATH_ALLOWED_DEPTH);
            $queryParams = $request->getQuery();
            $res = count($queryParams)<=$maxDepth;
        }
        if ($res) {
            $multicurrency = Mage::getStoreConfig(self::XML_PATH_CACHE_MULTICURRENCY);
            if (!$multicurrency && !empty($_COOKIE['currency'])) {
                $res = false;
            }
        }
        return $res;
    }

    /**
     * Get specific request processor based on request parameters.
     *
     * @param Zend_Controller_Request_Http $request
     * @return Enterprise_PageCache_Model_Processor_Default
     */
    public function getRequestProcessor(Zend_Controller_Request_Http $request)
    {
        $processor = false;
        $configuration = Mage::getConfig()->getNode(self::XML_NODE_ALLOWED_CACHE);
        if ($configuration) {
            $configuration = $configuration->asArray();
        }
        $module = $request->getModuleName();
        if (isset($configuration[$module])) {
            $model = $configuration[$module];
            $controller = $request->getControllerName();
            if (is_array($configuration[$module]) && isset($configuration[$module][$controller])) {
                $model = $configuration[$module][$controller];
                $action = $request->getActionName();
                if (is_array($configuration[$module][$controller])
                        && isset($configuration[$module][$controller][$action])) {
                    $model = $configuration[$module][$controller][$action];
                }
            }
            if (is_string($model)) {
                $processor = Mage::getModel($model);
            }
        }
        return $processor;
    }

    /**
     * Set metadata value for specified key
     *
     * @param string $key
     * @param string $value
     *
     * @return Enterprise_PageCache_Model_Processor
     */
    public function setMetadata($key, $value)
    {
        $this->_loadMetadata();
        $this->_metaData[$key] = $value;
        return $this;
    }

    /**
     * Get metadata value for specified key
     *
     * @param string $key
     *
     * @return mixed
     */
    public function getMetadata($key)
    {
        $this->_loadMetadata();
        return (isset($this->_metaData[$key])) ? $this->_metaData[$key] : null;
    }

    /**
     * Return current page base url
     *
     * @return string
     */
    protected function _getFullPageUrl()
    {
        $uri = false;
        /**
         * Define server HTTP HOST
         */
        if (isset($_SERVER['HTTP_HOST'])) {
            $uri = $_SERVER['HTTP_HOST'];
        } elseif (isset($_SERVER['SERVER_NAME'])) {
            $uri = $_SERVER['SERVER_NAME'];
        }

        /**
         * Define request URI
         */
        if ($uri) {
            if (isset($_SERVER['REQUEST_URI'])) {
                $uri.= $_SERVER['REQUEST_URI'];
            } elseif (!empty($_SERVER['IIS_WasUrlRewritten']) && !empty($_SERVER['UNENCODED_URL'])) {
                $uri.= $_SERVER['UNENCODED_URL'];
            } elseif (isset($_SERVER['ORIG_PATH_INFO'])) {
                $uri.= $_SERVER['ORIG_PATH_INFO'];
                if (!empty($_SERVER['QUERY_STRING'])) {
                    $uri.= $_SERVER['QUERY_STRING'];
                }
            }
        }
        return $uri;
    }


    /**
     * Save metadata for cache in cache storage
     */
    protected function _saveMetadata()
    {
        Mage::app()->saveCache(
            serialize($this->_metaData),
            $this->getRequestCacheId() . self::METADATA_CACHE_SUFFIX,
            $this->getRequestTags()
            );
    }

    /**
     * Load cache metadata from storage
     */
    protected function _loadMetadata()
    {
        if ($this->_metaData === null) {
            $cacheMetadata = Mage::app()->loadCache($this->getRequestCacheId() . self::METADATA_CACHE_SUFFIX);
            if ($cacheMetadata) {
                $cacheMetadata = unserialize($cacheMetadata);
            }
            $this->_metaData = (empty($cacheMetadata) || !is_array($cacheMetadata)) ? array() : $cacheMetadata;
        }
    }
}
