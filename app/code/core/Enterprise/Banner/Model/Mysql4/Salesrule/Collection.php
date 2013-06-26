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
 * @package     Enterprise_Banner
 * @copyright   Copyright (c) 2010 Magento Inc. (http://www.magentocommerce.com)
 * @license     http://www.magentocommerce.com/license/enterprise-edition
 */

class Enterprise_Banner_Model_Mysql4_Salesrule_Collection extends Mage_SalesRule_Model_Mysql4_Rule_Collection
{
    /**
     * Define if banner filter is already called
     *
     * @var bool
     */
    protected $_isBannerFilterAdded = false;

    /**
     * Define if customer segment filter is already called
     *
     * @var bool
     */
    protected $_isCustomerSegmentFilterAdded = false;

    /**
     * Reset collection select
     *
     * @return Enterprise_Banner_Model_Mysql4_Salesrule_Collection
     */
    public function resetColumns()
    {
        $this->getSelect()->reset();
        return $this;
    }

    /**
     * Set related banners to sales rule
     *
     * @param array $aplliedRules
     * @param bool $enabledOnly if true then only enabled banners will be joined
     * @return Enterprise_Banner_Model_Mysql4_Salesrule_Collection
     */
    public function addBannersFilter($aplliedRules, $enabledOnly = false)
    {
        if (!$this->_isBannerFilterAdded) {
            $select = $this->getSelect();
            $select->from(
                array('rule_related_banners' => $this->getTable('enterprise_banner/salesrule')),
                array('banner_id')
            );
            if(empty($aplliedRules)){
                $aplliedRules = array(0);
            }
            $select->where('rule_related_banners.rule_id IN (?)', $aplliedRules);
            if ($enabledOnly) {
                $select->join(
                    array('banners' => $this->getTable('enterprise_banner/banner')),
                    'banners.banner_id = rule_related_banners.banner_id AND banners.is_enabled=1',
                    array()
                );
            }
            $select->group('rule_related_banners.banner_id');

            $this->_isBannerFilterAdded = true;
        }
        return $this;
    }

    /**
     * Filter banners by customer segments
     *
     * @param array $matchedCustomerSegments
     * @return Enterprise_Banner_Model_Mysql4_Salesrule_Collection
     */
    public function addCustomerSegmentFilter($matchedCustomerSegments)
    {
        if (!$this->_isCustomerSegmentFilterAdded && !empty($matchedCustomerSegments)) {
            $select = $this->getSelect();
            $select->joinLeft(
                array('banner_segments' => $this->getTable('enterprise_banner/customersegment')),
                'banners.banner_id = banner_segments.banner_id',
                array()
            );
            if (empty($matchedCustomerSegments)) {
                $select->where('banner_segments.segment_id IS NULL');
            } else {
                $select->where('banner_segments.segment_id IS NULL OR banner_segments.segment_id IN (?)', $matchedCustomerSegments);
            }
            $this->_isCustomerSegmentFilterAdded = true;
        }
        return $this;
    }
}
