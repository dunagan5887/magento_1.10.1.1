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
 * @package     Enterprise_CustomerSegment
 * @copyright   Copyright (c) 2010 Magento Inc. (http://www.magentocommerce.com)
 * @license     http://www.magentocommerce.com/license/enterprise-edition
 */

class Enterprise_CustomerSegment_Model_Mysql4_Customer extends Mage_Core_Model_Mysql4_Abstract
{
    /**
     * Intialize resource model
     *
     * @return void
     */
    protected function _construct ()
    {
        $this->_init('enterprise_customersegment/customer', 'customer_id');
    }

    /**
     * Save relations between customer id and segment ids with specific website id
     *
     * @param int $customerId
     * @param int $websiteId
     * @param array $segmentIds
     * @return Enterprise_CustomerSegment_Model_Mysql4_Customer
     */
    public function addCustomerToWebsiteSegments($customerId, $websiteId, $segmentIds)
    {
        $data = array();
        $now = $this->formatDate(time(), true);
        foreach ($segmentIds as $segmentId) {
            $data = array(
                'segment_id'    => $segmentId,
                'customer_id'   => $customerId,
                'added_date'    => $now,
                'updated_date'  => $now,
                'website_id'    => $websiteId,
            );
            $this->_getWriteAdapter()->insertOnDuplicate($this->getMainTable(), $data, array('updated_date'));
        }
        return $this;
    }

    /**
     * Remove relations between customer id and segment ids on specific website
     *
     * @param int $customerId
     * @param int $websiteId
     * @param array $segmentIds
     * @return Enterprise_CustomerSegment_Model_Mysql4_Customer
     */
    public function removeCustomerFromWebsiteSegments($customerId, $websiteId, $segmentIds)
    {
        if (!empty($segmentIds)) {
            $this->_getWriteAdapter()->delete($this->getMainTable(), array(
                'customer_id=?'     => $customerId,
                'website_id=?'      => $websiteId,
                'segment_id IN(?)'  => $segmentIds
            ));
        }
        return $this;
    }

    /**
     * Get segment ids assigned to customer id on specific website
     *
     * @param   int $customerId
     * @param   int $websiteId
     * @return  array
     */
    public function getCustomerWebsiteSegments($customerId, $websiteId)
    {
        $select = $this->_getReadAdapter()->select()
            ->from($this->getMainTable(), 'segment_id')
            ->where('customer_id = ?', $customerId)
            ->where('website_id = ?', $websiteId);
        return $this->_getReadAdapter()->fetchCol($select);
    }























    /**
     * Save relations between customer id and segment ids
     *
     * @param int $customerId
     * @param array $segmentIds
     * @deprecated after 1.6.0 - please use addCustomerToWebsiteSegments
     * @return Enterprise_CustomerSegment_Model_Mysql4_Customer
     */
    public function addCustomerToSegments($customerId, $segmentIds)
    {
        $data = array();
        $now = $this->formatDate(time(), true);
        foreach ($segmentIds as $segmentId) {
            $data = array(
                'segment_id'    => $segmentId,
                'customer_id'   => $customerId,
                'added_date'    => $now,
                'updated_date'  => $now,
            );
            $this->_getWriteAdapter()->insertOnDuplicate($this->getMainTable(), $data, array('updated_date'));
        }
        return $this;
    }

    /**
     * Remove relations between customer id and segment ids
     *
     * @param int $customerId
     * @param array $segmentIds
     * @deprecated after 1.6.0 - please use removeCustomerFromWebsiteSegments
     * @return Enterprise_CustomerSegment_Model_Mysql4_Customer
     */
    public function removeCustomerFromSegments($customerId, $segmentIds)
    {
        if (!empty($segmentIds)) {
            $adapter = $this->_getWriteAdapter();
            $condition = $adapter->quoteInto('customer_id=? AND ', $customerId)
                . $adapter->quoteInto('segment_id IN (?)', $segmentIds);
            $adapter->delete($this->getMainTable(), $condition);
        }
        return $this;
    }

    /**
     * Get segment ids assigned to customer id
     *
     * @param   int $customerId
     * @deprecated after 1.6.0 - please use getCustomerWebsiteSegments
     * @return  array
     */
    public function getCustomerSegments($customerId)
    {
        $select = $this->_getReadAdapter()->select()
            ->from($this->getMainTable(), 'segment_id')
            ->where('customer_id = ?', $customerId);
        return $this->_getReadAdapter()->fetchCol($select);
    }
}
