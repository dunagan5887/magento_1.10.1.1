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

/**
 * Segment/customer relatio model. Model working in website scope. If website is not declared
 * all methods are working in current ran website scoupe
 */
class Enterprise_CustomerSegment_Model_Customer extends Mage_Core_Model_Abstract
{
    /**
     * Array of Segments collections per event name
     *
     * @var array
     */
    protected $_segmentMap = array();

    /**
     * Array of segment ids per customer if
     *
     * @deprecated after 1.6.0 - please use $_customerWebsiteSegments
     * @var array
     */
    protected $_customerSegments = array();

    /**
     * Array of segment ids per customer id and website id
     *
     * @var array
     */
    protected $_customerWebsiteSegments = array();

    /**
     * Class constructor
     */
    protected function _construct()
    {
        parent::_construct();
        $this->_init('enterprise_customersegment/customer');
    }

    /**
     * Get list of active segments for specific event
     *
     * @param string $eventName
     * @param int $websiteId
     * @return Enterprise_CustomerSegment_Model_Mysql4_Segment_Collection
     */
    public function getActiveSegmentsForEvent($eventName, $websiteId)
    {
        if (!isset($this->_segmentMap[$eventName][$websiteId])) {
            $this->_segmentMap[$eventName][$websiteId] = Mage::getResourceModel('enterprise_customersegment/segment_collection')
                ->addEventFilter($eventName)
                ->addWebsiteFilter($websiteId)
                ->addIsActiveFilter(1);
        }
        return $this->_segmentMap[$eventName][$websiteId];
    }

    /**
     * Match all related to event segments and assign/deassign customer to segments on specific website
     *
     * @param   string $eventName
     * @param   Mage_Customer_Model_Customer | int $customer
     * @param   Mage_Core_Model_Website | int $website
     * @return  Enterprise_CustomerSegment_Model_Customer
     */
    public function processEvent($eventName, $customer, $website)
    {
        Varien_Profiler::start('__SEGMENTS_MATCHING__');
        $website    = Mage::app()->getWebsite($website);
        $websiteId  = $website->getId();
        $segments = $this->getActiveSegmentsForEvent($eventName, $websiteId);
        if ($customer instanceof Mage_Customer_Model_Customer) {
            $customerId = $customer->getId();
        } else {
            $customerId = $customer;
        }
        $matchedIds     = array();
        $notMatchedIds  = array();
        foreach ($segments as $segment) {
            $isMatched = $segment->validateCustomer($customer, $website);
            if ($isMatched) {
                $matchedIds[]   = $segment->getId();
            } else {
                $notMatchedIds[]= $segment->getId();
            }
        }

        $this->addCustomerToWebsiteSegments($customerId, $websiteId, $matchedIds);
        $this->removeCustomerFromWebsiteSegments($customerId, $websiteId, $notMatchedIds);

        Varien_Profiler::stop('__SEGMENTS_MATCHING__');
        return $this;
    }

    /**
     * Validate all segments for specific customer on specific website
     *
     * @param   Mage_Customer_Model_Customer $customer
     * @param   Mage_Core_Model_Website $website
     * @return  Enterprise_CustomerSegment_Model_Customer
     */
    public function processCustomer(Mage_Customer_Model_Customer $customer, $website)
    {
        $website = Mage::app()->getWebsite($website);
        $segments = Mage::getResourceModel('enterprise_customersegment/segment_collection')
            ->addWebsiteFilter($website)
            ->addIsActiveFilter(1);

        $matchedIds     = array();
        $notMatchedIds  = array();
        foreach ($segments as $segment) {
            $isMatched = $segment->validateCustomer($customer, $website);
            if ($isMatched) {
                $matchedIds[]   = $segment->getId();
            } else {
                $notMatchedIds[]= $segment->getId();
            }
        }
        $this->addCustomerToWebsiteSegments($customer->getId(), $website->getId(), $matchedIds);
        $this->removeCustomerFromWebsiteSegments($customer->getId(), $website->getId(), $notMatchedIds);
        return $this;
    }

    /**
     * Match customer id to all segments related to event on all websites where customer can be presented
     *
     * @param string $eventName
     * @param int $customerId
     * @return Enterprise_CustomerSegment_Model_Customer
     */
    public function processCustomerEvent($eventName, $customerId)
    {
        if (Mage::getSingleton('customer/config_share')->isWebsiteScope()) {
            $websiteIds = Mage::getResourceSingleton('customer/customer')->getWebsiteId($customerId);
            if ($websiteIds) {
                $websiteIds = array($websiteIds);
            } else {
                $websiteIds = array();
            }
        } else {
            $websiteIds = Mage::app()->getWebsites();
            $websiteIds = array_keys($websiteIds);
        }
        foreach ($websiteIds as $websiteId) {
            $this->processEvent($eventName, $customerId, $websiteId);
        }
        return $this;
    }

    /**
     * Add customer relation with segment for specific website
     *
     * @param int $customerId
     * @param int $websiteId
     * @param array $segmentIds
     * @return Enterprise_CustomerSegment_Model_Customer
     */
    public function addCustomerToWebsiteSegments($customerId, $websiteId, $segmentIds)
    {
        $existingIds = $this->getCustomerSegmentIdsForWebsite($customerId, $websiteId);
        $this->_getResource()->addCustomerToWebsiteSegments($customerId, $websiteId, $segmentIds);
        $this->_customerWebsiteSegments[$websiteId][$customerId] = array_unique(array_merge($existingIds, $segmentIds));
        return $this;
    }

    /**
     * Remove customer id association with segment ids on specific website
     *
     * @param int $customerId
     * @param int $websiteId
     * @param array $segmentIds
     * @return Enterprise_CustomerSegment_Model_Customer
     */
    public function removeCustomerFromWebsiteSegments($customerId, $websiteId, $segmentIds)
    {
        $existingIds = $this->getCustomerSegmentIdsForWebsite($customerId, $websiteId);
        $this->_getResource()->removeCustomerFromWebsiteSegments($customerId, $websiteId, $segmentIds);
        $this->_customerWebsiteSegments[$websiteId][$customerId] = array_diff($existingIds, $segmentIds);
        return $this;
    }

    /**
     * Get segment ids for specific customer id and website id
     *
     * @param int $customerId
     * @param int $websiteId
     * @return array
     */
    public function getCustomerSegmentIdsForWebsite($customerId, $websiteId)
    {
        if (!isset($this->_customerWebsiteSegments[$websiteId][$customerId])) {
            $this->_customerWebsiteSegments[$websiteId][$customerId] = $this->_getResource()
                ->getCustomerWebsiteSegments($customerId, $websiteId);
        }
        return $this->_customerWebsiteSegments[$websiteId][$customerId];
    }










    /**
     * Assign customer with specific segment ids
     *
     * @param Mage_Customer_Model_Customer $customer
     * @param array $segmentIds
     * @deprecated after 1.6.0 please use addCustomerToWebsiteSegments
     * @return Enterprise_CustomerSegment_Model_Customer
     */
    public function addCustomerToSegments($customer, $segmentIds)
    {
        $customerId = $customer->getId();
        $existingIds = $this->getCustomerSegmentIds($customer);
        $this->_getResource()->addCustomerToSegments($customerId, $segmentIds);
        $this->_customerSegments[$customerId] = array_unique(array_merge($existingIds, $segmentIds));
        return $this;
    }

    /**
     * Unassign customer from specific segment ids
     *
     * @param Mage_Customer_Model_Customer $customer
     * @param array $segmentIds
     * @deprecated after 1.6.0 please use removeCustomerFromWebsiteSegments
     * @return Enterprise_CustomerSegment_Model_Customer
     */
    public function removeCustomerFromSegments($customer, $segmentIds)
    {
        $customerId = $customer->getId();
        $existingIds = $this->getCustomerSegmentIds($customer);
        $this->_getResource()->removeCustomerFromSegments($customerId, $segmentIds);
        $this->_customerSegments[$customerId] = array_diff($existingIds, $segmentIds);
        return $this;
    }

    /**
     * Get array of segment ids for customer
     *
     * @param Mage_Customer_Model_Customer $customer
     * @deprecated after 1.6.0 please use getCustomerSegmentIdsForWebsite
     * @return array
     */
    public function getCustomerSegmentIds(Mage_Customer_Model_Customer $customer)
    {
        $customerId = $customer->getId();
        if (!isset($this->_customerSegments[$customerId])) {
            $this->_customerSegments[$customerId] = $this->_getResource()->getCustomerSegments($customerId);
        }
        return $this->_customerSegments[$customerId];
    }
}
