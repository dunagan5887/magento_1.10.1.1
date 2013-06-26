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
 * @package     Enterprise_Logging
 * @copyright   Copyright (c) 2010 Magento Inc. (http://www.magentocommerce.com)
 * @license     http://www.magentocommerce.com/license/enterprise-edition
 */

class Enterprise_Logging_Model_Mysql4_Event extends Mage_Core_Model_Mysql4_Abstract
{
   /**
    * Constructor
    */
    protected function _construct()
    {
        $this->_init('enterprise_logging/event', 'log_id');
    }

    /**
     * Before save ip convertor
     */
    protected function _beforeSave(Mage_Core_Model_Abstract $event)
    {
        $event->setData('ip', ip2long($event->getIp()));
        $event->setTime($this->formatDate($event->getTime()));
    }

    /**
     * Rotate logs - get from database and pump to CSV-file
     *
     * @param int $lifetime
     */
    public function rotate($lifetime)
    {
        try {
            $this->beginTransaction();

            $table = $this->getTable('enterprise_logging/event');

            // get the latest log entry required to the moment
            $clearBefore = $this->formatDate(time() - $lifetime);
            $latestLogEntry = $this->_getWriteAdapter()->fetchOne("SELECT log_id FROM {$table}
                WHERE `time` < '{$clearBefore}' ORDER BY 1 DESC LIMIT 1");
            if (!$latestLogEntry) {
                return;
            }

            // make sure folder for dump file will exist
            $archive = Mage::getModel('enterprise_logging/archive');
            $archive->createNew();

            // dump all records before this log entry into a CSV-file
            $csv = fopen($archive->getFilename(), 'w');
            foreach ($this->_getWriteAdapter()->fetchAll("SELECT *, INET_NTOA(ip)
                FROM {$table} WHERE log_id <= {$latestLogEntry}") as $row) {
                fputcsv($csv, $row);
            }
            fclose($csv);
            $this->_getWriteAdapter()->query("DELETE FROM {$table} WHERE log_id <= {$latestLogEntry}");
            $this->commit();
        } catch (Exception $e) {
            $this->rollBack();
        }
    }

    /**
     * Select all values of specified field from main table
     *
     * @param string $field
     * @param bool $order
     * @return array
     */
    public function getAllFieldValues($field, $order = true)
    {
        return $this->_getReadAdapter()->fetchCol("SELECT DISTINCT
            {$this->_getReadAdapter()->quoteIdentifier($field)} FROM {$this->getMainTable()}"
            . (null !== $order ? ' ORDER BY 1' . ($order ? '' : ' DESC') : '')
        );
    }

    /**
     * Get all admin usernames that are currently in event log table
     *
     * Possible SQL-performance issue
     *
     * @return array
     */
    public function getUserNames()
    {
        $select = $this->_getReadAdapter()->select()
            ->distinct()
            ->from(array('admins' => $this->getTable('admin/user')), 'username')
            ->joinInner(array('events' => $this->getTable('enterprise_logging/event')),
                'admins.username = events.user', array());
        return $this->_getReadAdapter()->fetchCol($select);
    }

    /**
     * Get event change ids of specified event
     *
     * @param int $eventId
     * @return array
     */
    public function getEventChangeIds($eventId)
    {
        $adapter = $this->_getReadAdapter();
        $select = $adapter->select()
            ->from($this->getTable('enterprise_logging/event_changes'), array('id'))
            ->where('event_id = ?', $eventId);
        return $adapter->fetchCol($select);
    }
}
