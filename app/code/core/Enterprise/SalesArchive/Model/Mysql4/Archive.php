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
 * Archive resource model
 *
 */
class Enterprise_SalesArchive_Model_Mysql4_Archive extends Mage_Core_Model_Mysql4_Abstract
{
    /**
     * Archive entities tables association
     *
     * @var $_tables array
     */
    protected $_tables = array(
        Enterprise_SalesArchive_Model_Archive::ORDER     => array('sales/order_grid', 'enterprise_salesarchive/order_grid'),
        Enterprise_SalesArchive_Model_Archive::INVOICE   => array('sales/invoice_grid', 'enterprise_salesarchive/invoice_grid'),
        Enterprise_SalesArchive_Model_Archive::SHIPMENT  => array('sales/shipment_grid', 'enterprise_salesarchive/shipment_grid'),
        Enterprise_SalesArchive_Model_Archive::CREDITMEMO=> array('sales/creditmemo_grid', 'enterprise_salesarchive/creditmemo_grid')
    );

    protected function _construct()
    {
        $this->_setResource('enterprise_salesarchive');
    }

    /**
     * Check archive entity existance
     *
     * @param string $archiveEntity
     * @return boolean
     */
    public function isArchiveEntityExists($archiveEntity)
    {
        return isset($this->_tables[$archiveEntity]);
    }

    /**
     * Get archive config
     *
     * @return Enterprise_SalesArchive_Model_Config
     */
    protected function _getConfig()
    {
        return Mage::getSingleton('enterprise_salesarchive/config');
    }

    /**
     * Get archive entity table
     *
     * @param string $archiveEntity
     * @return string
     */
    public function getArchiveEntityTable($archiveEntity)
    {
        if (!$this->isArchiveEntityExists($archiveEntity)) {
            return false;
        }
        return $this->getTable($this->_tables[$archiveEntity][1]);
    }

    /**
     * Retrieve archive entity source table
     *
     * @param string $archiveEntity
     * @return string
     */
    public function getArchiveEntitySourceTable($archiveEntity)
    {
        if (!$this->isArchiveEntityExists($archiveEntity)) {
            return false;
        }
        return $this->getTable($this->_tables[$archiveEntity][0]);
    }

    /**
     * Retrieve entity ids in archive
     *
     * @param string $archiveEntity
     * @param array|int $ids
     * @return array
     */
    public function getIdsInArchive($archiveEntity, $ids)
    {
        if (!$this->isArchiveEntityExists($archiveEntity) || empty($ids)) {
            return array();
        }

        if (!is_array($ids)) {
            $ids = array($ids);
        }

        $select = $this->_getReadAdapter()->select()
            ->from($this->getArchiveEntityTable($archiveEntity), 'entity_id')
            ->where('entity_id IN(?)', $ids);

        return $this->_getReadAdapter()->fetchCol($select);
    }

    /**
     * Retrieve order ids for archive
     *
     * @param array $orderIds
     * @param boolean $useAge
     * @return array
     */
    public function getOrderIdsForArchive($orderIds = array(), $useAge = false)
    {
        $statuses = $this->_getConfig()->getArchiveOrderStatuses();
        $archiveAge = ($useAge ? $this->_getConfig()->getArchiveAge() : 0);

        if (empty($statuses)) {
            return array();
        }

        $select = $this->_getOrderIdsForArchiveSelect($statuses, $archiveAge);
        if (!empty($orderIds)) {
            $select->where('entity_id IN(?)', $orderIds);
        }
        return $this->_getReadAdapter()->fetchCol($select);
    }

    /**
     * Retrieve order ids in archive select
     *
     * @param array $statuses
     * @param int $archiveAge
     * @return Varien_Db_Select
     */
    protected function _getOrderIdsForArchiveSelect($statuses, $archiveAge)
    {
        $table = $this->getArchiveEntitySourceTable(Enterprise_SalesArchive_Model_Archive::ORDER);
        $select = $this->_getReadAdapter()->select()
            ->from($table, 'entity_id')
            ->where('status IN(?)', $statuses);

        if ($archiveAge) { // Check archive age
            $select->where('(TO_DAYS(?) - TO_DAYS(updated_at)) >= ' . (int) $archiveAge, $this->formatDate(time()));
        }

        return $select;
    }

    /**
     * Retrieve order ids for archive subselect expression
     *
     * @return Zend_Db_Expr
     */
    public function getOrderIdsForArchiveExpression()
    {
        $statuses = $this->_getConfig()->getArchiveOrderStatuses();
        $archiveAge = $this->_getConfig()->getArchiveAge();

        if (empty($statuses)) {
            $statuses = array(0);
        }
        $select = $this->_getOrderIdsForArchiveSelect($statuses, $archiveAge);
        return new Zend_Db_Expr($select);
    }

    /**
     * Move records to from regular grid tables to archive
     *
     * @param Enterprise_SalesArchive_Model_Archive $archive
     * @param string $archiveEntity
     * @param string $conditionField
     * @param array $conditionValue
     * @return Enterprise_SalesArchive_Model_Mysql4_Archive
     */
    public function moveToArchive($archive, $archiveEntity, $conditionField, $conditionValue)
    {
        if (!$this->isArchiveEntityExists($archiveEntity)) {
            return $this;
        }

        $sourceTable = $this->getArchiveEntitySourceTable($archiveEntity);
        $targetTable = $this->getArchiveEntityTable($archiveEntity);

        $insertFields = array_intersect(
            array_keys($this->_getWriteAdapter()->describeTable($targetTable)),
            array_keys($this->_getWriteAdapter()->describeTable($sourceTable))
        );

        $fieldCondition = $this->_getWriteAdapter()->quoteIdentifier($conditionField) . ' IN(?)';
        $select = $this->_getWriteAdapter()->select()
            ->from($sourceTable, $insertFields)
            ->where($fieldCondition, $conditionValue);

        $this->_getWriteAdapter()->query($select->insertFromSelect($targetTable, $insertFields, true));
        return $this;
    }

    /**
     * Remove regords from source grid table
     *
     * @param Enterprise_SalesArchive_Model_Archive $archive
     * @param string $archiveEntity
     * @param string $conditionField
     * @param array $conditionValue
     * @return Enterprise_SalesArchive_Model_Mysql4_Archive
     */
    public function removeFromGrid($archive, $archiveEntity, $conditionField, $conditionValue)
    {
        if (!$this->isArchiveEntityExists($archiveEntity)) {
            return $this;
        }

        $sourceTable = $this->getArchiveEntitySourceTable($archiveEntity);
        $targetTable = $this->getArchiveEntityTable($archiveEntity);
        $sourceResource = Mage::getResourceSingleton($archive->getEntityModel($archiveEntity));
        if ($conditionValue instanceof Zend_Db_Expr) {
            $select = $this->_getWriteAdapter()->select();
            $select->from($targetTable, $sourceResource->getIdFieldName()); // Remove order grid records moved to archive
            $condition = $this->_getWriteAdapter()->quoteInto($sourceResource->getIdFieldName() . ' IN(?)', new Zend_Db_Expr($select));
        } else {
            $fieldCondition = $this->_getWriteAdapter()->quoteIdentifier($conditionField) . ' IN(?)';
            $condition = $this->_getWriteAdapter()->quoteInto($fieldCondition, $conditionValue);
        }

        $this->_getWriteAdapter()->delete($sourceTable, $condition);
        return $this;
    }


    /**
     * Remove records from archive
     *
     * @param Enterprise_SalesArchive_Model_Archive $archive
     * @param string $archiveEntity
     * @param string $conditionField
     * @param array $conditionValue
     * @return Enterprise_SalesArchive_Model_Mysql4_Archive
     */
    public function removeFromArchive($archive, $archiveEntity, $conditionField = '', $conditionValue = null)
    {
        if (!$this->isArchiveEntityExists($archiveEntity)) {
            return $this;
        }

        $sourceTable = $this->getArchiveEntityTable($archiveEntity);
        $targetTable = $this->getArchiveEntitySourceTable($archiveEntity);
        $sourceResource = Mage::getResourceSingleton($archive->getEntityModel($archiveEntity));

        $insertFields = array_intersect(
            array_keys($this->_getWriteAdapter()->describeTable($targetTable)),
            array_keys($this->_getWriteAdapter()->describeTable($sourceTable))
        );
        $updatedAtIndex = array_search('updated_at', $insertFields);
        if ($updatedAtIndex !== false) {
            unset($insertFields[$updatedAtIndex]);
            $insertFields['updated_at'] = new Zend_Db_Expr("'".$this->formatDate(time())."'");
        }

        $select = $this->_getWriteAdapter()->select()
            ->from($sourceTable, $insertFields);

        if (!empty($conditionField)) {
            $select->where($this->_getWriteAdapter()->quoteIdentifier($conditionField) . ' IN(?)', $conditionValue);
        }

        $this->_getWriteAdapter()->query($select->insertFromSelect($targetTable, $insertFields, true));
        if ($conditionValue instanceof Zend_Db_Expr) {
            $select->reset()
                ->from($targetTable, $sourceResource->getIdFieldName()); // Remove order grid records from archive
            $condition = $this->_getWriteAdapter()->quoteInto($sourceResource->getIdFieldName() . ' IN(?)', new Zend_Db_Expr($select));
        } elseif (!empty($conditionField)) {
            $condition = $this->_getWriteAdapter()->quoteInto(
                $this->_getWriteAdapter()->quoteIdentifier($conditionField) . ' IN(?)', $conditionValue
            );
        } else {
            $condition = '';
        }

        $this->_getWriteAdapter()->delete($sourceTable, $condition);
        return $this;
    }

    /**
     * Update grid records
     *
     * @param Enterprise_SalesArchive_Model_Archive $archive
     * @param string $archiveEntity
     * @param array $ids
     * @return Enterprise_SalesArchive_Model_Mysql4_Archive
     */
    public function updateGridRecords($archive, $archiveEntity, $ids)
    {
        if (!$this->isArchiveEntityExists($archiveEntity) || empty($ids)) {
            return $this;
        }

        /* @var $resource Mage_Sales_Model_Mysql4_Abstract */
        $resource = Mage::getResourceSingleton($archive->getEntityModel($archiveEntity));

        $gridColumns = array_keys($this->_getWriteAdapter()->describeTable(
            $this->getArchiveEntityTable($archiveEntity)
        ));

        $columnsToSelect = array();

        $select = $resource->getUpdateGridRecordsSelect($ids, $columnsToSelect,  $gridColumns, true);

        $this->_getWriteAdapter()->query(
            $select->insertFromSelect($this->getArchiveEntityTable($archiveEntity), $columnsToSelect, true)
        );

        return $this;
    }

    /**
     * Find related to order entity ids for checking of new items in archive
     *
     * @param Enterprise_SalesArchive_Model_Archive $archive
     * @param string $archiveEntity
     * @param array $ids
     * @return array
     */
    public function getRelatedIds($archive, $archiveEntity, $ids)
    {
        $resourceClass = $archive->getEntityModel($archiveEntity);

        if (empty($resourceClass) || empty($ids)) {
            return array();
        }

        /* @var $resource Mage_Sales_Model_Mysql4_Abstract */
        $resource = Mage::getResourceSingleton($resourceClass);

        $select = $this->_getReadAdapter()->select()
            ->from(array('main_table' => $resource->getMainTable()), 'entity_id')
            ->joinInner( // Filter by archived orders
                array('order_archive' => $this->getArchiveEntityTable('order')),
                'main_table.order_id = order_archive.entity_id',
                array()
            )
            ->where('main_table.entity_id IN(?)', $ids);

        return $this->_getReadAdapter()->fetchCol($select);
    }
}
