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
 * @package     Enterprise_Search
 * @copyright   Copyright (c) 2010 Magento Inc. (http://www.magentocommerce.com)
 * @license     http://www.magentocommerce.com/license/enterprise-edition
 */

/**
 * Layer price filter
 *
 * @category    Enterprise
 * @package     Enterprise_Search
 * @author      Magento Core Team <core@magentocommerce.com>
 */
class Enterprise_Search_Model_Catalog_Layer_Filter_Price extends Mage_Catalog_Model_Layer_Filter_Price
{
    const CACHE_TAG = 'MAXPRICE';

    /**
     * Return cache tag for layered price filter 
     *
     * @return string
     */
    public function getCacheTag()
    {
        return self::CACHE_TAG;
    }

    /**
     * Get facet field name based on current website and customer group
     *
     * @return string
     */
    protected function _getFilterField()
    {
        $websiteId       = Mage::app()->getStore()->getWebsiteId();
        $customerGroupId = Mage::getSingleton('customer/session')->getCustomerGroupId();
        $priceField      = 'price_'. $customerGroupId .'_'. $websiteId;

        return $priceField;
    }

    /**
     * Get data for build price filter items
     *
     * @return array
     */
    protected function _getItemsData()
    {
        $range  = $this->getPriceRange();
        $facets = $this->getLayer()->getProductCollection()->getFacetedData($this->_getFilterField());

        $data = array();
        if (!empty($facets)) {
            foreach ($facets as $key => $count) {
                preg_match('/TO ([\d\.]+)\]$/', $key, $rangeKey);
                $rangeKey = $rangeKey[1] / $range;
                if ($count > 0) {
                    $rangeKey = round($rangeKey);
                    $data[] = array(
                        'label' => $this->_renderItemLabel($range, $rangeKey),
                        'value' => $rangeKey . ',' . $range,
                        'count' => $count
                    );
                }
            }
        }

        return $data;
    }

    /**
     * Get maximum price from layer products set using cache
     *
     * @return float
     */
    public function getMaxPriceInt()
    {
        $searchParams = $this->getLayer()->getProductCollection()->getExtendedSearchParams();
        $uniquePart = strtoupper(md5(serialize($searchParams)));
        $cacheKey = 'MAXPRICE_' . $this->getLayer()->getStateKey() . '_' . $uniquePart;

        $cachedData = Mage::app()->loadCache($cacheKey);
        if (!$cachedData) {
            $stats = $this->getLayer()->getProductCollection()->getStats($this->_getFilterField());

            $max = $stats[$this->_getFilterField()]['max'];
            if (!is_numeric($max)) {
                $max = parent::getMaxPriceInt();
            }

            $cachedData = (float) $max;
            $tags = $this->getLayer()->getStateTags();
            $tags[] = self::CACHE_TAG;
            Mage::app()->saveCache($cachedData, $cacheKey, $tags);
        }

        return $cachedData;
    }

    /**
     * Add params to faceted search
     *
     * @return Enterprise_Search_Model_Catalog_Layer_Filter_Price
     */
    public function addFacetCondition()
    {
        $range    = $this->getPriceRange();
        $maxPrice = $this->getMaxPriceInt();
        if ($maxPrice > 0) {
            $priceFacets = array();
            $facetCount  = ceil($maxPrice / $range);

            for ($i = 0; $i < $facetCount + 1; $i++) {
                $to = ($i + 1) * $range;
                if ($i < $facetCount) {
                    $to -= 0.001;
                }
                $priceFacets[] = array(
                    'from' => $i * $range,
                    'to'   => $to
                );
            }

            $this->getLayer()->getProductCollection()->setFacetCondition($this->_getFilterField(), $priceFacets);
        }

        return $this;
    }

    /**
     * Apply filter value to product collection based on filter range and selected value
     *
     * @param int $range
     * @param int $index
     * @return Mage_Catalog_Model_Layer_Filter_Price
     */
    protected function _applyToCollection($range, $index)
    {
        $to = $range * $index;
        if ($to < $this->getMaxPriceInt()) {
            $to -= 0.001;
        }

        $value = array(
            $this->_getFilterField() => array(
                'from' => ($range * ($index - 1)),
                'to'   => $to
            )
        );

        $this->getLayer()->getProductCollection()->addFqFilter($value);

        return $this;
    }
}
