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
 * @package     Enterprise_SalesArchive
 * @copyright   Copyright (c) 2010 Magento Inc. (http://www.magentocommerce.com)
 * @license     http://www.magentocommerce.com/license/enterprise-edition
 */

/**
 * Order archive observer model
 *
 */
class Enterprise_SalesArchive_Model_Observer
{
    /**
     * Archive model
     * @var Enterprise_SalesArchive_Model_Archive
     */
    protected $_archive;

    /**
     * Archive config model
     * @var Enterprise_SalesArchive_Model_Config
     */
    protected $_config;

    public function __construct()
    {
        $this->_archive = Mage::getModel('enterprise_salesarchive/archive');
        $this->_config  = Mage::getSingleton('enterprise_salesarchive/config');
    }

    /**
     * Archive order by cron
     *
     * @return Enterprise_SalesArchive_Model_Observer
     */
    public function archiveOrdersByCron()
    {
        if ($this->_config->isArchiveActive()) {
            $this->_archive->archiveOrders();
        }

        return $this;
    }

    /**
     * Mark sales object as archived and set back urls for them
     *
     * @param Varien_Event_Observer $observer
     * @return Enterprise_SalesArchive_Model_Observer
     */
    public function salesObjectAfterLoad(Varien_Event_Observer $observer)
    {
        if (!$this->_config->isArchiveActive()) {
            return $this;
        }
        $object = $observer->getEvent()->getDataObject();
        $archiveEntity = $this->_archive->detectArchiveEntity($object);

        if (!$archiveEntity) {
            return $this;
        }
        $ids = $this->_archive->getIdsInArchive($archiveEntity, $object->getId());
        $object->setIsArchived(!empty($ids));

        if ($object->getIsArchived()) {
            $object->setBackUrl(
                Mage::helper('adminhtml')->getUrl('adminhtml/sales_archive/' . $archiveEntity . 's')
            );
        } elseif ($object->getIsMoveable() !== false) {
            $object->setIsMoveable(
                in_array($object->getStatus(), $this->_config->getArchiveOrderStatuses())
            );
        }
        return $this;
    }

    /**
     * Observes grid records update and depends on data updates records in grid too
     *
     * @param Varien_Event_Observer $observer
     * @return Enterprise_SalesArchive_Model_Observer
     */
    public function salesUpdateGridRecords(Varien_Event_Observer $observer)
    {
        if (!$this->_config->isArchiveActive()) {
            return $this;
        }

        $proxy = $observer->getEvent()->getProxy();

        $archiveEntity = $this->_archive->detectArchiveEntity($proxy->getResource());

        if (!$archiveEntity) {
            return $this;
        }

        $ids = $proxy->getIds();
        $idsInArchive = $this->_archive->getIdsInArchive($archiveEntity, $ids);
        // Exclude archive records from default grid rows update
        $ids = array_diff($ids, $idsInArchive);
        // Check for newly created shipments, creditmemos, invoices
        if ($archiveEntity != Enterprise_SalesArchive_Model_Archive::ORDER && !empty($ids)) {
            $relatedIds = $this->_archive->getRelatedIds($archiveEntity, $ids);
            $ids = array_diff($ids, $relatedIds);
            $idsInArchive = array_merge($idsInArchive, $relatedIds);
        }

        $proxy->setIds($ids);

        if (!empty($idsInArchive)) {
            $this->_archive->updateGridRecords($archiveEntity, $idsInArchive);
        }

        return $this;
    }

    /**
     * Add archived orders to order grid collection select
     *
     * @param Varien_Event_Observer $observer
     * @return Enterprise_SalesArchive_Model_Observer
     */
    public function appendGridCollection(Varien_Event_Observer $observer)
    {
        $collection = $observer->getEvent()->getOrderGridCollection();
        if ($collection instanceof Enterprise_SalesArchive_Model_Mysql4_Order_Collection
            || !$collection->getIsCustomerMode()) {
            return $this;
        }

        $collectionSelect = $collection->getSelect();
        $cloneSelect = clone $collectionSelect;

        $union = Mage::getResourceModel('enterprise_salesarchive/order_collection')
            ->getOrderGridArchiveSelect($cloneSelect);

        $unionParts = array($cloneSelect, $union);

        $collectionSelect->reset();
        $collectionSelect->union($unionParts, Zend_Db_Select::SQL_UNION_ALL);

        return $this;
    }
}
