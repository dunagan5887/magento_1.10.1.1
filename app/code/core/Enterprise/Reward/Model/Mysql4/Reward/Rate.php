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
 * @package     Enterprise_Reward
 * @copyright   Copyright (c) 2010 Magento Inc. (http://www.magentocommerce.com)
 * @license     http://www.magentocommerce.com/license/enterprise-edition
 */


/**
 * Reward rate resource model
 *
 * @category    Enterprise
 * @package     Enterprise_Reward
 * @author      Magento Core Team <core@magentocommerce.com>
 */
class Enterprise_Reward_Model_Mysql4_Reward_Rate extends Mage_Core_Model_Mysql4_Abstract
{
    /**
     * Internal constructor
     */
    protected function _construct()
    {
        $this->_init('enterprise_reward/reward_rate', 'rate_id');
    }

    /**
     * Fetch rate customer group and website
     *
     * @param Enterprise_Reward_Model_Reward_Rate $rate
     * @param integer $customerId
     * @param integer $websiteId
     * @return Enterprise_Reward_Model_Mysql4_Reward_Rate
     */
    public function fetch(Enterprise_Reward_Model_Reward_Rate $rate, $customerGroupId, $websiteId, $direction)
    {
        $select = $this->_getReadAdapter()->select()
            ->from($this->getMainTable())
            ->where('website_id IN (?, 0)', (int)$websiteId)
            ->where('customer_group_id IN (?, 0)', $customerGroupId)
            ->where('direction = ?', $direction)
            ->order('customer_group_id DESC')
            ->order('website_id DESC')
            ->limit(1);

        if ($row = $this->_getReadAdapter()->fetchRow($select)) {
            $rate->addData($row);
        }

        $this->_afterLoad($rate);
        return $this;
    }

    /**
     * Retrieve rate data bu given params or empty array if rate with such params does not exists
     *
     * @param integer $websiteId
     * @param integer $customerGroupId
     * @param integer $direction
     * @return array
     */
    public function getRateData($websiteId, $customerGroupId, $direction)
    {
        $result = true;
        $select = $this->_getWriteAdapter()->select()
            ->from($this->getMainTable())
            ->where('website_id = ?', $websiteId)
            ->where('customer_group_id = ?', $customerGroupId)
            ->where('direction = ?', $direction);
        if ($data = $this->_getWriteAdapter()->fetchRow($select)) {
            return $data;
        }
        return array();
    }
}
