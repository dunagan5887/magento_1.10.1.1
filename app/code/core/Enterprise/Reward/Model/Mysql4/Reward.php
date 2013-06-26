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
 * Reward resource model
 *
 * @category    Enterprise
 * @package     Enterprise_Reward
 * @author      Magento Core Team <core@magentocommerce.com>
 */
class Enterprise_Reward_Model_Mysql4_Reward extends Mage_Core_Model_Mysql4_Abstract
{
    /**
     * Internal constructor
     */
    protected function _construct()
    {
        $this->_init('enterprise_reward/reward', 'reward_id');
    }

    /**
     * Fetch reward by customer and website and set data to reward object
     *
     * @param Enterprise_Reward_Model_Reward $reward
     * @param integer $customerId
     * @param integer $websiteId
     * @return Enterprise_Reward_Model_Mysql4_Reward
     */
    public function loadByCustomerId(Enterprise_Reward_Model_Reward $reward, $customerId, $websiteId)
    {
        $select = $this->_getReadAdapter()->select()
            ->from($this->getMainTable())
            ->where('customer_id = ?', $customerId)
            ->where('website_id = ?', $websiteId);
        if ($data = $this->_getReadAdapter()->fetchRow($select)) {
            $reward->addData($data);
        }
        $this->_afterLoad($reward);
        return $this;
    }

    /**
     * Perform Row-level data update
     *
     * @param Enterprise_Reward_Model_Reward $reward
     * @param array $data New data
     * @return Enterprise_Reward_Model_Mysql4_Reward
     */
    public function updateRewardRow(Enterprise_Reward_Model_Reward $object, $data)
    {
        if (!$object->getId() || !is_array($data)) {
            return $this;
        }
        $where = array($this->getIdFieldName().'=?' => $object->getId());
        $this->_getWriteAdapter()
            ->update($this->getMainTable(), $data, $where);
        return $this;
    }

    /**
     * Prepare orphan points by given website id and website base currency code
     * after website was deleted
     *
     * @param integer $websiteId
     * @param string $baseCurrencyCode
     * @return Enterprise_Reward_Model_Mysql4_Reward
     */
    public function prepareOrphanPoints($websiteId, $baseCurrencyCode)
    {
        if ($websiteId) {
            $this->_getWriteAdapter()->update($this->getMainTable(),
                array(
                    'website_id' => null,
                    'website_currency_code' => $baseCurrencyCode
                ), $this->_getWriteAdapter()->quoteInto('website_id = ?', $websiteId));
        }
        return $this;
    }

    /**
     * Delete orphan (points of deleted website) points by given customer
     *
     * @param integer $customer
     * @return Enterprise_Reward_Model_Mysql4_Reward
     */
    public function deleteOrphanPointsByCustomer($customerId)
    {
        if ($customerId) {
            $this->_getWriteAdapter()->delete($this->getMainTable(),
                $this->_getWriteAdapter()->quoteInto('customer_id = ?', $customerId) . ' AND `website_id` IS NULL');
        }
        return $this;
    }

    /**
     * Save salesrule reward points delta
     *
     * @param integer $ruleId
     * @param integer $pointsDelta
     * @return Enterprise_Reward_Model_Mysql4_Reward
     */
    public function saveRewardSalesrule($ruleId, $pointsDelta)
    {
        $select = $this->_getWriteAdapter()->select()
            ->from($this->getTable('enterprise_reward/reward_salesrule'), array('rule_id'))
            ->where('rule_id = ?', $ruleId);
        if ($this->_getWriteAdapter()->fetchOne($select)) {
            $this->_getWriteAdapter()->update($this->getTable('enterprise_reward/reward_salesrule'), array(
                'points_delta' => $pointsDelta), $this->_getWriteAdapter()->quoteInto('rule_id = ?', $ruleId));
        } else {
            $this->_getWriteAdapter()->insert($this->getTable('enterprise_reward/reward_salesrule'), array(
                'rule_id' => $ruleId,
                'points_delta' => $pointsDelta
            ));
        }
        return $this;
    }

    /**
     * Retrieve reward salesrule data by given rule Id or array of Ids
     *
     * @param integer | array $rule
     * @return array
     */
    public function getRewardSalesrule($rule)
    {
        $data = array();
        $select = $this->_getReadAdapter()->select()
            ->from($this->getTable('enterprise_reward/reward_salesrule'));
        if (is_array($rule)) {
            $select->where('rule_id IN (?)', $rule);
            $data = $this->_getReadAdapter()->fetchAll($select);
        } elseif (intval($rule)) {
            $select->where('rule_id = ?', intval($rule));
            $data = $this->_getReadAdapter()->fetchRow($select);
        }
        return $data;
    }
}

