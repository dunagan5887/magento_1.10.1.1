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
 * Enterprise search suggestions block
 *
 * @category   Enterprise
 * @package    Enterprise_Search
 * @author     Magento Core Team <core@magentocommerce.com>
 */
class Enterprise_Search_Block_Recommendations extends Mage_Core_Block_Template
{
    /**
     * Retrieve search recommendations
     *
     * @return array
     */
    public function getRecommendations()
    {
        $searchRecommendationsEnabled = (boolean)Mage::helper('enterprise_search')
            ->getSearchConfigData('search_recommendations_enabled');

        if (!$searchRecommendationsEnabled) {
            return array();
        }

        $recommendationsModel = Mage::getModel('enterprise_search/recommendations');
        $recommendations = $recommendationsModel->getSearchRecommendations();

        if (!count($recommendations)) {
            return array();
        }
        $result = array();
        foreach ($recommendations as $recommendation) {
            $result[] = array(
                'word'        => $recommendation['query_text'],
                'num_results' => $recommendation['num_results'],
                'link'        => $this->getUrl("*/*/") . "?q=" . urlencode($recommendation['query_text'])
            );
        }
        return $result;
    }

    /**
     * Retrieve search recommendations count results enabled
     *
     * @return boolean
     */
    public function isCountResultsEnabled()
    {
        return (boolean)Mage::helper('enterprise_search')
            ->getSearchConfigData('search_recommendations_count_results_enabled');
    }
}
