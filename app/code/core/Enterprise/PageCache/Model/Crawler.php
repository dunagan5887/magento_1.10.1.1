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

class Enterprise_PageCache_Model_Crawler extends Mage_Core_Model_Abstract
{
    const XML_PATH_CRAWLER_ENABLED     = 'system/page_crawl/enable';
    const XML_PATH_CRAWLER_THREADS     = 'system/page_crawl/threads';
    const XML_PATH_CRAWL_MULTICURRENCY = 'system/page_crawl/multicurrency';


    const USER_AGENT = 'MagentoCrawler';

    protected function _construct()
    {
        $this->_init('enterprise_pagecache/crawler');
    }

    /**
     * Get internal links from page content
     *
     * @param string $pageContent
     * @return array
     */
    public function getUrls($pageContent)
    {
        $urls = array();
        preg_match_all(
            "/\s+href\s*=\s*[\"\']?([^\s\"\']+)[\"\'\s]+/ims",
            $pageContent,
            $urls
        );
        $urls = $urls[1];
        return $urls;
    }

    /**
     * Get configuration for stores base urls.
     * array(
     *  $index => array(
     *      'store_id'  => $storeId,
     *      'base_url'  => $url,
     *      'cookie'    => $cookie
     *  )
     * )
     * @return array
     */
    public function getStoresInfo()
    {
        $baseUrls = array();

        foreach (Mage::app()->getStores() as $store) {
            $website = Mage::app()->getWebsite($store->getWebsiteId());
            $defaultWebsiteStore = $website->getDefaultStore();
            $defaultWebsiteBaseUrl      = $defaultWebsiteStore->getBaseUrl();
            $defaultWebsiteBaseCurrency = $defaultWebsiteStore->getDefaultCurrencyCode();

            $baseUrl            = Mage::app()->getStore($store)->getBaseUrl();
            $defaultCurrency    = Mage::app()->getStore($store)->getDefaultCurrencyCode();

            $cookie = '';
            if (($baseUrl == $defaultWebsiteBaseUrl) && ($defaultWebsiteStore->getId() != $store->getId())) {
                $cookie = 'store='.$store->getCode().';';
            }

            $baseUrls[] = array(
                'store_id' => $store->getId(),
                'base_url' => $baseUrl,
                'cookie'   => $cookie,
            );
            if ($store->getConfig(self::XML_PATH_CRAWL_MULTICURRENCY)
                && $store->getConfig(Enterprise_PageCache_Model_Processor::XML_PATH_CACHE_MULTICURRENCY)) {
                $currencies = $store->getAvailableCurrencyCodes(true);
                foreach ($currencies as $currencyCode) {
                    if ($currencyCode != $defaultCurrency) {
                        $baseUrls[] = array(
                            'store_id' => $store->getId(),
                            'base_url' => $baseUrl,
                            'cookie'   => $cookie.'currency='.$currencyCode.';'
                        );
                    }
                }
            }
        }
        return $baseUrls;
    }

    /**
     * Crawl all system urls
     * @return Enterprise_PageCache_Model_Crawler
     */
    public function crawl()
    {
        $storesInfo = $this->getStoresInfo();
        $adapter = new Varien_Http_Adapter_Curl();

        foreach ($storesInfo as $info) {
            $options    = array(CURLOPT_USERAGENT => self::USER_AGENT);
            $storeId    = $info['store_id'];

            if (!Mage::app()->getStore($storeId)->getConfig(self::XML_PATH_CRAWLER_ENABLED)) {
                continue;
            }
            $threads = (int)Mage::app()->getStore($storeId)->getConfig(self::XML_PATH_CRAWLER_THREADS);
            if (!$threads) {
                $threads = 1;
            }
            $stmt       = $this->_getResource()->getUrlStmt($storeId);
            $baseUrl    = $info['base_url'];
            if (!empty($info['cookie'])) {
                $options[CURLOPT_COOKIE] = $info['cookie'];
            }
            $urls = array();
            $urlsCount = 0;
            $totalCount = 0;
            while ($row = $stmt->fetch()) {
                $urls[] = $baseUrl.$row['request_path'];
                $urlsCount++;
                $totalCount++;
                if ($urlsCount==$threads) {
                    $adapter->multiRequest($urls, $options);
                    $urlsCount = 0;
                    $urls = array();
                }
            }
            if (!empty($urls)) {
                $adapter->multiRequest($urls, $options);
            }
        }
        return $this;
    }
}
