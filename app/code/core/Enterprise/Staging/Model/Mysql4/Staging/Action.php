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
 * @package     Enterprise_Staging
 * @copyright   Copyright (c) 2010 Magento Inc. (http://www.magentocommerce.com)
 * @license     http://www.magentocommerce.com/license/enterprise-edition
 */

class Enterprise_Staging_Model_Mysql4_Staging_Action extends Mage_Core_Model_Mysql4_Abstract
{
    protected function _construct()
    {
        $this->_init('enterprise_staging/staging_action', 'action_id');
    }

    /**
     * Before save processing
     *
     * @param   Mage_Core_Model_Abstract $object
     * @return  Enterprise_Staging_Model_Mysql4_Staging_Backup
     */
    protected function _beforeSave(Mage_Core_Model_Abstract $object)
    {
        $staging = $object->getStaging();
        if ($staging instanceof Enterprise_Staging_Model_Staging) {
            if ($staging->getId()) {
                $object->setStagingId($staging->getId());
                $object->setStagingWebsiteId($staging->getStagingWebsiteId());
                $object->setMasterWebsiteId($staging->getMasterWebsiteId());
            }
        }

        if (!$object->getId() && !$object->getCreatedAt()) {
            $value = $this->formatDate(time());
            $object->setCreatedAt($value);
        }
        if ($object->getId()) {
            $value = $this->formatDate(time());
            $object->setUpdatedAt($value);
        }

        parent::_beforeSave($object);

        return $this;
    }

    /**
     * Needto delete all backup tables also
     *
     * @param   Mage_Core_Model_Abstract $object
     * @return  Enterprise_Staging_Model_Mysql4_Staging_Backup
     */
    protected function _afterDelete(Mage_Core_Model_Abstract $object)
    {
        if ($object->getIsDeleteTables() === true) {
            $stagingTablePrefix = $object->getStagingTablePrefix();
            $connection = $this->_getWriteAdapter();
            $sql = "SHOW TABLES LIKE '{$stagingTablePrefix}%'";
            $result = $connection->fetchAll($sql);

            $connection->query("SET foreign_key_checks = 0;");
            foreach ($result AS $row) {
                $table = array_values($row);
                if (!empty($table[0])) {
                    $dropTableSql = "DROP TABLE {$table[0]}";
                    $connection->query($dropTableSql);
                }
            }
            $connection->query("SET foreign_key_checks = 1;");
        }
        return $this;
    }

    public function getBackupTables($stagingTablePrefix)
    {
        $sql    = "SHOW TABLES LIKE '{$stagingTablePrefix}%'";
        $result = $this->_getReadAdapter()->fetchAll($sql);
        $resultArray = array();
        if ($result) {
            foreach ($result as $row) {
                $table = array_values($row);
                $resultArray[] = $table[0];
            }
        }
        return $resultArray;
    }
}
