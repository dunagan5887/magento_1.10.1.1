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


class Enterprise_Staging_Model_Mysql4_Adapter_Item_Default extends Enterprise_Staging_Model_Mysql4_Adapter_Abstract
{
    /**
     * Processed tables
     *
     * @var array
     */
    protected $_processedTables = array();

    /**
     * in backend mode only backend tables will be processed
     *
     * @var boolean
     */
    protected $_isBackendProcessing = false;

    /**
     * Check backend Staging Tables Creates
     *
     * @param   object Enterprise_Staging_Model_Staging $staging
     *
     * @return Enterprise_Staging_Model_Mysql4_Adapter_Item_Default
     */
    public function checkfrontendRun(Enterprise_Staging_Model_Staging $staging, $event = null)
    {
        parent::checkfrontendRun($staging, $event);
        $this->_processItemMethodCallback('_checkBackendTables');
        return $this;
    }

    /**
     * Staging Create (Staging Item handle part)
     *
     * @param   object Enterprise_Staging_Model_Staging $staging
     * @param   object Enterprise_Staging_Model_Staging_Event $event
     *
     * @return Enterprise_Staging_Model_Mysql4_Adapter_Item_Default
     */
    public function createRun(Enterprise_Staging_Model_Staging $staging, $event = null)
    {
        parent::checkfrontendRun($staging, $event);
        $this->_processItemMethodCallback('_createItem');
        return $this;
    }

    /**
     * Staging Backup (Staging Item handle part)
     *
     * @param   object Enterprise_Staging_Model_Staging $staging
     * @param   object Enterprise_Staging_Model_Staging_Event $event
     *
     * @return Enterprise_Staging_Model_Mysql4_Adapter_Item_Default
     */
    public function backupRun(Enterprise_Staging_Model_Staging $staging, $event = null)
    {
        parent::backupRun($staging, $event);
        $this->_processItemMethodCallback('_backupItem');
        return $this;
    }

    /**
     * Staging Merge (Staging Item handle part)
     *
     * @param   object Enterprise_Staging_Model_Staging $staging
     * @param   object Enterprise_Staging_Model_Staging_Event $event
     *
     * @return Enterprise_Staging_Model_Mysql4_Adapter_Item_Default
     */
    public function mergeRun(Enterprise_Staging_Model_Staging $staging, $event = null)
    {
        parent::mergeRun($staging, $event);
        $this->_processItemMethodCallback('_mergeItem');
        return $this;
    }

    /**
     * Staging Rollback (Staging Item handle part)
     *
     * @param   object Enterprise_Staging_Model_Staging $staging
     * @param   object Enterprise_Staging_Model_Staging_Event $event
     *
     * @return Enterprise_Staging_Model_Mysql4_Adapter_Item_Default
     */
    public function rollbackRun(Enterprise_Staging_Model_Staging $staging, $event = null)
    {
        parent::rollbackRun($staging, $event);
        $this->_processItemMethodCallback('_rollbackItem');
        return $this;
    }

    /**
     * Validate and run callback method for flat item
     *
     * @param string $entityName
     * @param string $callbackMethod
     */
    protected function _itemFlatRun($entityName, $callbackMethod)
    {
        $helper   = Mage::helper($entityName);
        $resource = Mage::getResourceModel($entityName);

        if ($helper->isBuilt()) {
            $staging    = $this->getStaging();
            $websites   = $staging->getMapperInstance()->getWebsiteObjects();
            $callback   = $callbackMethod . 'Flat';

            if (!empty($websites)) {
                foreach ($websites as $website) {
                    $stores = $website->getStores();
                    foreach ($stores as $store) {
                        $masterStoreId  = (int)$store->getMasterStoreId();
                        $stagingStoreId = (int)$store->getStagingStoreId();
                        if (!$masterStoreId || !$stagingStoreId) {
                            continue;
                        }

                        $this->$callback($store, $resource);
                    }
                }
            }
        }

        // set processed tables flag
        $this->_processedTables[$entityName] = $entityName;

        return $this;
    }

    /**
     * Check Staging backend tables to exist
     *
     * @param string  $entityName
     *
     * @return  Enterprise_Staging_Model_Mysql4_Adapter_Item_Default
     */
    protected function _checkBackendTables($entityName)
    {
        $stagingTablePrefix = Mage::getSingleton('enterprise_staging/staging_config')->getTablePrefix();
        $targetTable        = $stagingTablePrefix . $this->getTable($entityName);

        if (!$this->tableExists($targetTable)) {
            $this->createTable($targetTable, $entityName);
        }

        $this->_processedTables[$entityName] = $targetTable;
        return $this;
    }

    /**
     * Create item table and records, run processes in website and store scopes
     *
     * @param string  $entityName
     *
     * @return Enterprise_Staging_Model_Mysql4_Adapter_Item_Default
     */
    protected function _createItem($entityName)
    {
        $srcTableDesc = $this->getTableProperties($entityName);
        if (!$srcTableDesc) {
            return $this;
        }

        $fields = $srcTableDesc['fields'];
        foreach ($fields as $id => $field) {
            if ((strpos($entityName, 'product_website') === false)) {
                if ($field['extra'] == 'auto_increment') {
                    unset($fields[$id]);
                }
            }
        }
        $fields = array_keys($fields);

        if ($this->allowToProceedInWebsiteScope($fields)) {
            $this->_createWebsiteScopeItemTableData($entityName, $fields);
        }
        if ($this->allowToProceedInStoreScope($fields)) {
            $this->_createStoreScopeItemTableData($entityName, $fields);
        }

        $this->_processedTables[$entityName] = $this->_getStagingTableName($entityName);
        return $this;
    }

    /**
     * Create item table and records for flat tables
     *
     * @param Varien_Object $store
     * @param Mage_Core_Model_Mysql4_Abstract $resource
     * @throws Enterprise_Staging_Exception
     * @return Enterprise_Staging_Model_Mysql4_Adapter_Item_Default
     */
    protected function _createItemFlat(Varien_Object $store, $resource)
    {
        $sourceStoreId  = (int)$store->getMasterStoreId();
        $targetStoreId  = (int)$store->getStagingStoreId();

        $sourceTable    = $resource->setStoreId($sourceStoreId)->getMainTable();
        $targetTable    = $resource->setStoreId($targetStoreId)->getMainTable();

        $this->_copyFlatTable($sourceTable, $targetTable, true);

        return $this;
    }

    /**
     * Copy table and records process
     *
     * @param string $sourceTableName
     * @param string $targetTableName
     * @param bool $create              create table if does not exists flag
     * @throws Enterprise_Staging_Exception
     * @return Enterprise_Staging_Model_Mysql4_Adapter_Item_Default
     */
    protected function _copyFlatTable($sourceTableName, $targetTableName, $create = false)
    {
        $sourceTableDesc = $this->getTableProperties($sourceTableName);
        if (!$sourceTableDesc) {
            return $this;
        }

        if ($create) {
            $this->createTable($targetTableName, $sourceTableName, true);
        }
        $this->cloneTable($sourceTableName, $targetTableName);

        return $this;
    }

    /**
     * Backup process (empty function)
     *
     * @param Varien_Object $store
     * @param Mage_Core_Model_Mysql4_Abstract $resource
     * @return Enterprise_Staging_Model_Mysql4_Adapter_Item_Default
     */
    protected function _backupItemFlat(Varien_Object $store, $resource)
    {
        return $this;
    }

    /**
     * Merge item records for flat tables
     *
     * @param Varien_Object $store
     * @param Mage_Core_Model_Mysql4_Abstract $resource
     * @throws Enterprise_Staging_Exception
     * @return Enterprise_Staging_Model_Mysql4_Adapter_Item_Default
     */
    protected function _mergeItemFlat(Varien_Object $store, $resource)
    {
        $sourceStoreId  = (int)$store->getStagingStoreId();
        $targetStoreId  = (int)$store->getMasterStoreId();

        $sourceTable    = $resource->setStoreId($sourceStoreId)->getMainTable();
        $targetTable    = $resource->setStoreId($targetStoreId)->getMainTable();

        $this->_copyFlatTable($sourceTable, $targetTable, false);

        return $this;
    }

    /**
     * Rollback process (empty function)
     *
     * @param Varien_Object $store
     * @param Mage_Core_Model_Mysql4_Abstract $resource
     * @return Enterprise_Staging_Model_Mysql4_Adapter_Item_Default
     */
    protected function _rollbackItemFlat(Varien_Object $store, $resource)
    {
        return $this;
    }

    /**
     * Create item table, run website and item table structure
     *
     * @param string    $entityName
     * @param mixed     $fields
     *
     * @return Enterprise_Staging_Model_Mysql4_Adapter_Item_Default
     */
    protected function _createWebsiteScopeItemTableData($entityName, $fields)
    {
        $staging            = $this->getStaging();
        $connection         = $this->_getWriteAdapter();

        $masterWebsiteId    = (int) $staging->getMasterWebsiteId();
        $stagingWebsiteId   = (int) $staging->getStagingWebsiteId();
        if (!$masterWebsiteId || !$stagingWebsiteId) {
            return $this;
        }

        $srcTable    = $this->getTable($entityName);
        $targetTable = $this->_getStagingTableName($srcTable);
        $updateField = end($fields);

        if (in_array('website_ids', $fields)) {
            $destInsertSql = "UPDATE `{$targetTable}` SET `website_ids` = IF(FIND_IN_SET({$stagingWebsiteId},`website_ids`), `website_ids`, CONCAT(`website_ids`,',{$stagingWebsiteId}'))
                    WHERE FIND_IN_SET({$masterWebsiteId},`website_ids`)";
        } else {
            $destInsertSql = "INSERT INTO `{$targetTable}` (".$this->_prepareFields($fields).") (%s) ON DUPLICATE KEY UPDATE `{$updateField}`=VALUES(`{$updateField}`)";

            $_websiteFieldNameSql = 'website_id';
            foreach ($fields as $id => $field) {
                if ($field == 'website_id') {
                    $fields[$id] = $stagingWebsiteId;
                    $_websiteFieldNameSql = "`{$field}` = {$masterWebsiteId}";
                } elseif ($field == 'scope_id') {
                    $fields[$id] = $stagingWebsiteId;
                    $_websiteFieldNameSql = "scope = 'websites' AND `{$field}` = {$masterWebsiteId}";
                } elseif ($field == 'website_ids') {
                    $fields[$id] = new Zend_Db_Expr("CONCAT(website_ids,',{$stagingWebsiteId}')");
                    $_websiteFieldNameSql = "FIND_IN_SET({$masterWebsiteId},website_ids)";
                }
            }

            $srcSelectSql  = $this->_getSimpleSelect($srcTable, $fields, $_websiteFieldNameSql);
            $destInsertSql = sprintf($destInsertSql, $srcSelectSql);
        }
        $connection->query($destInsertSql);

        return $this;
    }

    /**
     * Create item table, run website and item table structure
     *
     * @param string $entityName
     * @param mixed  $fields
     *
     * @return Enterprise_Staging_Model_Mysql4_Adapter_Item_Default
     */
    protected function _createStoreScopeItemTableData($entityName, $fields)
    {
        $staging    = $this->getStaging();
        $connection = $this->_getWriteAdapter();
        $websites   = $staging->getMapperInstance()->getWebsiteObjects();

        if (!empty($websites)) {
            $srcTable    = $this->getTable($entityName);
            $targetTable = $this->_getStagingTableName($srcTable);
            $updateField = end($fields);
            foreach ($websites as $website) {
                $stores = $website->getStores();
                foreach ($stores as $store) {
                    $masterStoreId  = (int) $store->getMasterStoreId();
                    $stagingStoreId = (int) $store->getStagingStoreId();
                    if (!$masterStoreId || !$stagingStoreId) {
                        return $this;
                    }

                    $destInsertSql = "INSERT INTO `{$targetTable}` (".$this->_prepareFields($fields).") (%s) ON DUPLICATE KEY UPDATE `{$updateField}`=VALUES(`{$updateField}`)";
                    $_storeFieldNameSql = 'store_id';

                    $_fields = $fields;
                    foreach ($_fields as $id => $field) {
                        if ($field == 'store_id') {
                            $_fields[$id] = $stagingStoreId;
                            $_storeFieldNameSql = "({$field} = {$masterStoreId})";
                        } elseif ($field == 'scope_id') {
                            $_fields[$id] = $stagingStoreId;
                            $_storeFieldNameSql = "`scope` = 'stores' AND `{$field}` = {$masterStoreId}";
                        }
                    }
                    $srcSelectSql  = $this->_getSimpleSelect($srcTable, $_fields, $_storeFieldNameSql);
                    $destInsertSql = sprintf($destInsertSql, $srcSelectSql);
                    $connection->query($destInsertSql);
                }
            }
        }
        return $this;
    }

    /**
     * Prepare data for merging
     *
     * @param string  $entityName
     *
     * @return Enterprise_Staging_Model_Mysql4_Adapter_Item_Default
     */
    protected function _backupItem($entityName)
    {
        $srcTable     = $this->getTable($entityName);
        $backupPrefix = $this->getBackupTablePrefix($this->getEvent()->getId());
        $targetTable  = $this->getStagingTableName($srcTable, $backupPrefix);

        if ($srcTable != $targetTable) {
            if ($this->tableExists($srcTable)) {
                $this->_checkCreateTable($targetTable, $srcTable, $backupPrefix);
                $this->_backupItemData($srcTable, $targetTable);
            }
        }

        $this->_processedTables[$entityName] = $targetTable;
        return $this;
    }

    /**
     * Get backup table prefix
     *
     * @param  string $addOnPrefix
     * @return string
     */
    public function getBackupTablePrefix($addOnPrefix = '')
    {
        $backupPrefix = Mage::getSingleton('enterprise_staging/staging_config')
            ->getStagingBackupTablePrefix();
        if (!empty($addOnPrefix)) {
            $backupPrefix .= $addOnPrefix;
        }
        return $backupPrefix . "_";;
    }

    /**
     * Process backup item
     *
     * @param string $srcTable
     * @param string $targetTable
     *
     * @return Enterprise_Staging_Model_Mysql4_Adapter_Item_Default
     */
    protected function _backupItemData($srcTable, $targetTable)
    {
        $this->_getWriteAdapter()->query("SET foreign_key_checks = 0;");
        try {
            $destInsertSql = "INSERT INTO `{$targetTable}` (%s)";
            $srcSelectSql  = $this->_getSimpleSelect($srcTable, '*');
            $destInsertSql = sprintf($destInsertSql, $srcSelectSql);
            $this->_getWriteAdapter()->query($destInsertSql);
            $this->_getWriteAdapter()->query("SET foreign_key_checks = 1;");
        } catch (Exception $e) {
            $this->_getWriteAdapter()->query("SET foreign_key_checks = 1;");
            throw $e;
        }
        return $this;
    }

    /**
     * Prepare data to merge as Website Scope and as Store scope
     *
     * @param string  $entityName
     *
     * @return Enterprise_Staging_Model_Mysql4_Adapter_Item_Default
     */
    protected function _mergeItem($entityName)
    {
        $srcTableDesc = $this->getTableProperties($entityName);
        if (!$srcTableDesc) {
            return $this;
        }

        $fields = $srcTableDesc['fields'];
        foreach ($fields as $id => $field) {
            if ((strpos($entityName, 'product_website') === false)) {
                if ($field['extra'] == 'auto_increment') {
                    unset($fields[$id]);
                }
            }
        }
        $fields = array_keys($fields);

        if ($this->allowToProceedInWebsiteScope($fields)) {
            $this->_mergeTableDataInWebsiteScope($entityName, $fields);
        }
        if ($this->allowToProceedInStoreScope($fields)) {
            $this->_mergeTableDataInStoreScope($entityName, $fields);
        }

        $this->_processedTables[$entityName] = $this->_getStagingTableName($entityName);
        return $this;
    }

    /**
     * Process website scope
     *
     * @param string    $entityName
     * @param mixed     $fields
     *
     * @return Enterprise_Staging_Model_Mysql4_Adapter_Item_Default
     */
    protected function _mergeTableDataInWebsiteScope($entityName, $fields)
    {
        $staging        = $this->getStaging();
        $connection     = $this->_getWriteAdapter();
        $mappedWebsites = $staging->getMapperInstance()->getWebsites();
        if (in_array('website_ids', $fields)) {
            $this->_mergeTableDataInWebsiteScopeUpdate($mappedWebsites, $connection, $entityName);
        } else {
            $this->_mergeTableDataInWebsiteScopeInsert($mappedWebsites, $connection, $entityName, $fields);
        }
        return $this;
    }

    /**
     * Insert New data on merge
     *
     * @param array     $mappedWebsites
     * @param object    $connection
     * @param string    $entityName
     * @param array     $fields
     *
     * @return Enterprise_Staging_Model_Mysql4_Adapter_Item_Default
     */
    protected function _mergeTableDataInWebsiteScopeInsert($mappedWebsites, $connection, $entityName, $fields)
    {
        $srcTable    = $this->getTable($entityName);
        $targetTable = $this->_getStagingTableName($srcTable);
        $updateField = end($fields);

        foreach ($mappedWebsites as $stagingWebsiteId => $masterWebsiteIds) {
            if (empty($stagingWebsiteId) || empty($masterWebsiteIds)) {
                continue;
            }
            $stagingWebsiteId     = intval($stagingWebsiteId);
            $_websiteFieldNameSql = 'website_id';

            foreach ($masterWebsiteIds as $masterWebsiteId) {
                if (empty($masterWebsiteId)) {
                    continue;
                }
                $masterWebsiteId = intval($masterWebsiteId);

                $destInsertSql = "INSERT INTO `{$targetTable}` (".$this->_prepareFields($fields).") (%s) ON DUPLICATE KEY UPDATE `{$updateField}`=VALUES(`{$updateField}`)";

                $_fields = $fields;
                foreach ($_fields as $id => $field) {
                    if ($field == 'website_id') {
                        $_fields[$id] = $masterWebsiteId;
                        $_websiteFieldNameSql = "{$field} = {$stagingWebsiteId}";
                    } elseif ($field == 'scope_id') {
                        $_fields[$id] = $masterWebsiteId;
                        $_websiteFieldNameSql = "`scope` = 'websites' AND `{$field}` = {$stagingWebsiteId}";
                    }
                }

                $srcSelectSql = $this->_getSimpleSelect($srcTable, $_fields, $_websiteFieldNameSql);
                $destInsertSql = sprintf($destInsertSql, $srcSelectSql);

                $connection->query($destInsertSql);
            }
        }
        return $this;
    }

    /**
     * Update data on merge
     *
     * @param array  $mappedWebsites
     * @param object $connection
     * @param string $entityName
     *
     * @return Enterprise_Staging_Model_Mysql4_Adapter_Item_Default
     */
    protected function _mergeTableDataInWebsiteScopeUpdate($mappedWebsites, $connection, $entityName)
    {
        $targetTable = $this->_getStagingTableName($entityName);

        foreach ($mappedWebsites as $stagingWebsiteId => $masterWebsiteIds) {
            if (empty($stagingWebsiteId) || empty($masterWebsiteIds)) {
                continue;
            }
            $stagingWebsiteId = intval($stagingWebsiteId);

            foreach ($masterWebsiteIds as $masterWebsiteId) {
                if (empty($masterWebsiteId)) {
                    continue;
                }
                $masterWebsiteId = intval($masterWebsiteId);

                $destInsertSql = "UPDATE `{$targetTable}` SET `website_ids` = IF(FIND_IN_SET({$masterWebsiteId},`website_ids`), `website_ids`, CONCAT(`website_ids`,',{$masterWebsiteId}'))
                    WHERE FIND_IN_SET({$stagingWebsiteId},`website_ids`)";

                $connection->query($destInsertSql);
            }
        }
        return $this;
    }

    /**
     * Process Store scope
     *
     * @param string $entityName
     * @param mixed  $fields
     *
     * @return Enterprise_Staging_Model_Mysql4_Adapter_Item_Default
     */
    protected function _mergeTableDataInStoreScope($entityName, $fields)
    {
        $staging    = $this->getStaging();
        $connection = $this->_getWriteAdapter();
        $storesMap  = $staging->getMapperInstance()->getStores();

        if (!empty($storesMap)) {
            $srcTable    = $this->getTable($entityName);
            $targetTable = $this->_getStagingTableName($srcTable);
            $updateField = end($fields);
            foreach ($storesMap as $stagingStoreId => $masterStoreIds) {
                $stagingStoreId = intval($stagingStoreId);

                foreach ($masterStoreIds as $masterStoreId) {
                    $masterStoreId = intval($masterStoreId);

                    $this->_beforeStoreMerge($entityName, $fields, $masterStoreId, $stagingStoreId);

                    $destInsertSql = "INSERT INTO `{$targetTable}` (".$this->_prepareFields($fields).") (%s) ON DUPLICATE KEY UPDATE `{$updateField}`=VALUES(`{$updateField}`)";
                    $_storeFieldNameSql = 'store_id';
                    $_fields = $fields;
                    foreach ($fields as $id => $field) {
                        if ($field == 'store_id') {
                            $_fields[$id] = $masterStoreId;
                        } elseif ($field == 'scope_id') {
                            $_fields[$id] = $masterStoreId;
                            $_storeFieldNameSql = "`scope` = 'stores' AND `{$field}`";
                        }
                    }
                    $srcSelectSql = $this->_getSimpleSelect($srcTable, $_fields, "{$_storeFieldNameSql} = {$stagingStoreId}");
                    $destInsertSql = sprintf($destInsertSql, $srcSelectSql);

                    $connection->query($destInsertSql);

                    $this->_afterStoreMerge($entityName, $fields, $masterStoreId, $stagingStoreId);
                }
            }
        }
        return $this;
    }

    /**
     * Executed before merging staging store to master store
     *
     * @param string $entityName
     * @param mixed $fields
     * @param int $masterStoreId
     * @param int $stagingStoreId
     *
     * @return Enterprise_Staging_Model_Mysql4_Adapter_Item_Default
     */
    protected function _beforeStoreMerge($entityName, $fields, $masterStoreId, $stagingStoreId)
    {
        return $this;
    }

    /**
     * Executed after merging staging store to master store
     *
     * @param string $entityName
     * @param mixed $fields
     * @param int $stagingStoreId
     * @param int $masterStoreId
     *
     * @return Enterprise_Staging_Model_Mysql4_Adapter_Item_Default
     */
    protected function _afterStoreMerge($entityName, $fields, $masterStoreId, $stagingStoreId)
    {
        return $this;
    }

    /**
     * Prepare table data to rollback
     *
     * @param string  $entityName
     *
     * @return Enterprise_Staging_Model_Mysql4_Adapter_Item_Default
     */
    protected function _rollbackItem($entityName)
    {
        $srcTableDesc = $this->getTableProperties($entityName);
        if (!$srcTableDesc) {
            return $this;
        }

        $connection = $this->_getWriteAdapter();
        $fields     = $srcTableDesc['fields'];
        $fields     = array_keys($fields);

        $backupPrefix = $this->getStaging()->getMapperInstance()->getBackupTablePrefix();
        $backupTable  = $backupPrefix . $this->getTable($entityName);

        if ($this->tableExists($backupTable)) {
            if ($this->allowToProceedInWebsiteScope($fields)) {
                $this->_rollbackTableDataInWebsiteScope($backupTable, $entityName, $connection, $fields);
            }
            if ($this->allowToProceedInStoreScope($fields)) {
                $this->_rollbackTableDataInStoreScope($backupTable, $entityName, $connection, $fields);
            }
        }
        $this->_processedTables[$entityName] = $backupTable;
        return $this;
    }

    /**
     * process website rollback
     *
     * @param string $srcTable
     * @param string $targetTable
     * @param object $connection
     * @param mixed  $fields
     *
     * @return Enterprise_Staging_Model_Mysql4_Adapter_Item_Default
     */
    protected function _rollbackTableDataInWebsiteScope($srcTable, $targetTable, $connection, $fields)
    {
        $staging        = $this->getStaging();
        $mergedWebsites = $staging->getMapperInstance()->getWebsites();

        if (!empty($mergedWebsites)) {
            $srcTable    = $this->getTable($srcTable);
            $targetTable = $this->getTable($targetTable);
            $updateField = end($fields);
            foreach ($mergedWebsites as $stagingWebsiteId => $masterWebsiteIds) {
                if (!empty($masterWebsiteIds)) {
                    $_websiteFieldNameSql = 'website_id';
                    if (in_array('website_id', $fields)) {
                        $_websiteFieldNameSql = " `{$srcTable}`.`website_id` IN (" . implode(", ", $masterWebsiteIds). ")";
                    } elseif (in_array('scope_id', $fields)) {
                        $_websiteFieldNameSql = "`{$srcTable}`.`scope` = 'websites' AND `{$srcTable}`.`scope_id` IN (" . implode(", ", $masterWebsiteIds). ")";
                    } elseif (in_array('website_ids', $fields)) {
                        $whereFields = array();
                        foreach ($masterWebsiteIds AS $webId) {
                            $whereFields[] = "FIND_IN_SET($webId, `{$srcTable}`.`website_ids`)";
                        }
                        $_websiteFieldNameSql = implode(" OR " , $whereFields);
                    }
                    // FIXME need to investigate next code ASAP !
                    $tableDestDesc = $this->getTableProperties($targetTable);
                    if (!$tableDestDesc) {
                        continue;
                    }
                    //1 - need remove all resords from web_site tables, which added via marging
                    if (!empty($tableDestDesc['keys'])) {
                        if (!empty($tableDestDesc['keys']['PRIMARY']) && !empty($tableDestDesc['keys']['PRIMARY']['fields'])) {
                            $primaryFields = $tableDestDesc['keys']['PRIMARY']['fields'];
                        } else {
                            $primaryFields = array();
                        }
                        $destDeleteSql = $this->_deleteDataByKeys('UNIQUE', 'website',$srcTable, $targetTable, $stagingWebsiteId, $masterWebsiteIds, $tableDestDesc['keys']);
                        if (!empty($destDeleteSql)) {
                            $connection->query($destDeleteSql);
                        }

                        $additionalWhereCondition = $_websiteFieldNameSql;
                        if (in_array('website_id', $primaryFields) || in_array('scope_id', $primaryFields) || in_array('website_ids', $primaryFields)) {
                            $additionalWhereCondition = "";
                        }
                        $destDeleteSql = $this->_deleteDataByKeys('PRIMARY', 'website', $srcTable, $targetTable, $masterWebsiteIds, $stagingWebsiteId, $tableDestDesc['keys'], $additionalWhereCondition);
                        //if ($destDeleteSql) {
                            //$connection->query($destDeleteSql);
                        //}
                    }

                    //2 - copy old data from bk_ tables
                    $destInsertSql = "INSERT INTO `{$targetTable}` (".$this->_prepareFields($fields).") (%s) ON DUPLICATE KEY UPDATE `{$updateField}`=VALUES(`{$updateField}`)";

                    $srcSelectSql = $this->_getSimpleSelect($srcTable, $fields, $_websiteFieldNameSql);
                    $destInsertSql = sprintf($destInsertSql, $srcSelectSql);

                    $connection->query($destInsertSql);
                }
            }
        }

        return $this;
    }

    /**
     * process store scope rollback
     *
     * @param string $srcTable
     * @param string $targetTable
     * @param object $connection
     * @param mixed  $fields
     * @return Enterprise_Staging_Model_Mysql4_Adapter_Item_Default
     */
    protected function _rollbackTableDataInStoreScope($srcTable, $targetTable, $connection, $fields)
    {
        $staging        = $this->getStaging();
        $mergedStores   = $staging->getMapperInstance()->getStores();

        if (!empty($mergedStores)) {
            $origSrcTable = $srcTable;
            $srcTable    = $this->getTable($srcTable);
            $origTargetTable = $targetTable;
            $targetTable = $this->getTable($targetTable);
            $updateField = end($fields);
            foreach ($mergedStores as $stagingStoreId => $masterStoreIds) {
                if (empty($stagingStoreId) || empty($masterStoreIds)) {
                    continue;
                }
                $stagingStoreId = intval($stagingStoreId);

                foreach ($masterStoreIds as $masterStoreId) {
                    if (empty($masterStoreId)) {
                        continue;
                    }
                    $masterStoreId = intval($masterStoreId);

                    $this->_beforeStoreRollback($origSrcTable, $origTargetTable, $connection, $fields, $masterStoreId, $stagingStoreId);

                    $_storeFieldNameSql = "`{$srcTable}`.`store_id`";
                    $_fields = $fields;

                    foreach ($_fields as $id => $field) {
                        if ($field == 'store_id') {
                            $_fields[$id] = $masterStoreId;
                        } elseif ($field == 'scope_id') {
                            $_storeFieldNameSql = "`{$srcTable}`.`scope` = 'stores' AND `{$srcTable}`.`{$field}`";
                        }
                    }
                    // FIXME need to investigate next code ASAP !
                    $tableDestDesc = $this->getTableProperties($targetTable);
                    if (!$tableDestDesc) {
                        continue;
                    }
                    //1 - need remove all resords from stores tables, which added via marging
                    if (!empty($tableDestDesc['keys'])) {
                        if (!empty($tableDestDesc['keys']['PRIMARY']) && !empty($tableDestDesc['keys']['PRIMARY']['fields'])) {
                            $primaryFields = $tableDestDesc['keys']['PRIMARY']['fields'];
                        } else {
                            $primaryFields = array();
                        }

                        $destDeleteSql = $this->_deleteDataByKeys('UNIQUE', 'store', $srcTable, $targetTable, $stagingStoreId, $masterStoreId, $tableDestDesc['keys']);
                        if (!empty($destDeleteSql)) {
                            $connection->query($destDeleteSql);
                        }

                        $additionalWhereCondition = "{$_storeFieldNameSql} = {$masterStoreId}";
                        if ( in_array('store_id' , $primaryFields) || in_array('scope_id' ,$primaryFields)) {
                            $additionalWhereCondition = "";
                        }

                        $destDeleteSql = $this->_deleteDataByKeys('PRIMARY', 'store', $srcTable, $targetTable, $masterStoreId, $stagingStoreId, $tableDestDesc['keys'], $additionalWhereCondition);
                        //if ($destDeleteSql) {
                            //$connection->query($destDeleteSql);
                        //}
                    }

                    //2 - refresh data by backup
                    $destInsertSql = "INSERT INTO `{$targetTable}` (".$this->_prepareFields($fields).") (%s) ON DUPLICATE KEY UPDATE `{$updateField}`=VALUES(`{$updateField}`)";

                    $srcSelectSql = $this->_getSimpleSelect($srcTable, $_fields, "{$_storeFieldNameSql} = {$masterStoreId}");
                    $destInsertSql = sprintf($destInsertSql, $srcSelectSql);

                    $connection->query($destInsertSql);

                    $this->_afterStoreRollback($origSrcTable, $origTargetTable, $connection, $fields, $masterStoreId, $stagingStoreId);
                }
            }
        }
        return $this;
    }

    /**
     * Executed before rolling back backup to master store
     *
     * @param string $srcTable
     * @param string $targetTable
     * @param object $connection
     * @param mixed $fields
     * @param int $masterStoreId
     * @param int $stagingStoreId
     *
     * @return Enterprise_Staging_Model_Mysql4_Adapter_Item_Default
     */
    protected function _beforeStoreRollback($srcTable, $targetTable, $connection, $fields, $masterStoreId, $stagingStoreId)
    {
        return $this;
    }

    /**
     * Executed after rolling back backup to master store
     *
     * @param string $srcTable
     * @param string $targetTable
     * @param object $connection
     * @param mixed $fields
     * @param int $masterStoreId
     * @param int $stagingStoreId
     *
     * @return Enterprise_Staging_Model_Mysql4_Adapter_Item_Default
     */
    protected function _afterStoreRollback($srcTable, $targetTable, $connection, $fields, $masterStoreId, $stagingStoreId)
    {
        return $this;
    }

    /**
     * Return Staging table name with all prefixes
     *
     * @param string $entityName
     * @param string $internalPrefix
     * @return string
     */
    public function getStagingTableName($entityName, $internalPrefix = '')
    {
        $table = $this->getTable($entityName);

        if (isset($this->_processedTables[$table])) {
            return $this->_processedTables[$table];
        }
        return parent::getStagingTableName($table, $internalPrefix);
    }

    /**
     * Get Staging Table Name
     *
     * @param string  $entityName
     *
     * @return string
     */
    protected function _getStagingTableName($entityName)
    {
        if ($this->_isBackendProcessing) {
            $targetTable = $this->getStagingTableName($entityName);
            if (!$this->tableExists($targetTable)) {
                $targetTable = $this->getTable($entityName);
            }
        } else {
            $targetTable = $this->getTable($entityName);
        }

        return $targetTable;
    }

    /**
     * Check is table matchs to current staging item
     *
     * @param string $table
     * @param string $code
     * @param string $model
     * @return bollean
     */
    protected function _matchTable($table, $code, $model)
    {
        if ($model == 'catalog') {
            if (strpos($table, $code) !== 0) {
                return false;
            }
        }
        return true;
    }

    /**
     * Prepares data for action and makes callback
     *
     * @param string $callbackMethod
     *
     * @return Enterprise_Staging_Model_Mysql4_Adapter_Item_Default
     */
    protected function _processItemMethodCallback($callbackMethod)
    {
        $itemConfig = $this->getConfig();

        $this->_isBackendProcessing = ((string)$itemConfig->is_backend === '1');

        $code = (string) $itemConfig->getName();
        if ($itemConfig->model) {
            $model = (string) $itemConfig->model;
        } else {
            $model = $code;
        }

        $tables       = (array) $itemConfig->entities;
        $ignoreTables = (array) $itemConfig->ignore_tables;

        $resourceName = (string) Mage::getConfig()->getNode("global/models/{$model}/resourceModel");
        $entityTables = (array) Mage::getConfig()->getNode("global/models/{$resourceName}/entities");

        foreach ($entityTables as $entityTableConfig) {
            $table = $entityTableConfig->getName();
            if (!empty($tables)) {
                if (!array_key_exists($table, $tables)) {
                    continue;
                }
            }
            if (!empty($ignoreTables)) {
                if (array_key_exists($table, $ignoreTables)) {
                    continue;
                }
            }
            if (!$this->_matchTable($table, $code, $model)) {
                continue;
            }
            $entityName = "{$model}/{$table}";
            if (isset($this->_processedTables[$entityName])) {
                continue;
            }

            if (isset($this->_eavModels[$entityName])) {
                if ($this->_isBackendProcessing) {
                    $this->{$callbackMethod}($entityName);
                }
                $srcTable = $this->getTable($entityName);
                foreach ($this->_eavTableTypes as $type) {
                    $_srcTable = $srcTable . '_' . $type;
                    $this->{$callbackMethod}($_srcTable);
                }
                continue;
            }
            else if (isset($this->_flatTables[$entityName])) {
                $this->_itemFlatRun($entityName, $callbackMethod);
                continue;
            }
            $this->{$callbackMethod}($entityName);
        }
        return $this;
    }
}
