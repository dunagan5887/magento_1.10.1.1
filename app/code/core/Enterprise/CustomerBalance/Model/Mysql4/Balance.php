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
 * @package     Enterprise_CustomerBalance
 * @copyright   Copyright (c) 2010 Magento Inc. (http://www.magentocommerce.com)
 * @license     http://www.magentocommerce.com/license/enterprise-edition
 */

/**
 * Customerbalance resource model
 *
 */
class Enterprise_CustomerBalance_Model_Mysql4_Balance extends Mage_Core_Model_Mysql4_Abstract
{
    /**
     * Initialize table name and primary key name
     *
     */
    protected function _construct()
    {
        $this->_init('enterprise_customerbalance/balance', 'balance_id');
    }

    /**
     * Load customer balance data by specified customer id and website id
     *
     * @param Enterprise_CustomerBalance_Model_Balance $object
     * @param int $customerId
     * @param int $websiteId
     */
    public function loadByCustomerAndWebsiteIds($object, $customerId, $websiteId)
    {
        if ($data = $this->getReadConnection()->fetchRow($this->getReadConnection()->select()
            ->from($this->getMainTable())
            ->where('customer_id = ?', $customerId)
            ->where('website_id = ?', $websiteId)
            ->limit(1))) {
            $object->addData($data);
        }
    }

     /**
     * Update customers balance currency code per website id
     *
     * @param int $websiteId
     * @param string $currencyCode
     * @return Enterprise_CustomerBalance_Model_Mysql4_Balance
     */
    public function setCustomersBalanceCurrencyTo($websiteId, $currencyCode)
    {
        $bind = array('base_currency_code' => $currencyCode);
        $this->_getWriteAdapter()->update(
            $this->getMainTable(), $bind,
            array('website_id=?' => $websiteId, 'base_currency_code IS NULL')
        );
        return $this;
    }

    /**
     * Delete customer orphan balances
     *
     * @param int $customerId
     * @return Enterprise_CustomerBalance_Model_Mysql4_Balance
     */
    public function deleteBalancesByCustomerId($customerId)
    {
        $adapter = $this->_getWriteAdapter();

        $adapter->delete(
            $this->getMainTable(), $adapter->quoteInto('customer_id = ? AND website_id IS NULL', $customerId)
        );
        return $this;
    }

    /**
     * Get customer orphan balances count
     *
     * @param int $customerId
     * @return Enterprise_CustomerBalance_Model_Mysql4_Balance
     */
    public function getOrphanBalancesCount($customerId)
    {
        $adapter = $this->_getReadAdapter();
        return $adapter->fetchOne($adapter->select()
            ->from($this->getMainTable(), 'count(*)')
            ->where('customer_id = ?', $customerId)
            ->where('website_id IS NULL'));
    }
}
