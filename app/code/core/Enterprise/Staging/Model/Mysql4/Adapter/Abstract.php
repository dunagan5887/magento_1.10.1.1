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

abstract class Enterprise_Staging_Model_Mysql4_Adapter_Abstract extends Mage_Core_Model_Mysql4_Abstract implements Enterprise_Staging_Model_Mysql4_Adapter_Interface
{
    /**
     * Replace direction for mapping table name
     */
    const REPLACE_DIRECTION_TO      = true;

    /**
     * Replace direction for mapping table name 
     */
    const REPLACE_DIRECTION_FROM    = false;

    /**
     * Staging instance
     *
     * @var object Enterprise_Staging_Model_Staging
     */
    protected $_staging;

    /**
     * Event instance
     *
     * @var object Enterprise_Staging_Model_Staging_Event
     */
    protected $_event;

    /**
     * Staging type config data
     *
     * @var mixed
     */
    protected $_config;

    /**
     * Flat type table list
     *
     * @var mixed
     */
    protected $_flatTables = array(
        'catalog/category_flat' => true,
        'catalog/product_flat'  => true
    );

    /**
     * EAV type Table models
     *
     * @var mixed
     */
    protected $_eavModels = array(
        'catalog/product'           => 'catalog',
        'catalog/category'          => 'catalog',
        'sales/order'               => 'sales',
        'sales/order_entity'        => 'sales',
        'customer/entity'           => 'customer',
        'customer/address_entity'   => 'customer',
    );

    /**
     * Table names replaces map
     *
     * @var mixed
     */
    protected $_tableNameMap = array(
        'catalog'   => 'ctl',
        'category'  => 'ctg',
        'entity'    => 'ntt',
        'product'   => 'prd'
    );

    /**
     * EAV table entities
     *
     * @var modex
     */
    protected $_eavTableTypes = array('int', 'decimal', 'varchar', 'text', 'datetime');

    protected function _construct()
    {
        $this->_setResource('enterprise_staging');
    }
    /**
     * Staging content check
     *
     * @param Enterprise_Staging_Model_Staging $staging
     * @param Enterprise_Staging_Model_Staging_Event $event
     *
     * @return Enterprise_Staging_Model_Staging_Adapter_Abstract
     */
    public function checkfrontendRun(Enterprise_Staging_Model_Staging $staging, $event = null)
    {
        $this->setStaging($staging);
        $this->setEvent($event);
        return $this;
    }

    /**
     * Create item method
     *
     * @param Enterprise_Staging_Model_Staging_Action_Run $runModel
     * @return Enterprise_Staging_Model_Staging_Adapter_Abstract
     */
    public function createRun(Enterprise_Staging_Model_Staging $staging, $event = null)
    {
        $this->setStaging($staging);
        $this->setEvent($event);
        return $this;
    }

    /**
     * Update item method
     *
     * @param Enterprise_Staging_Model_Staging_Action_Run $runModel
     * @return Enterprise_Staging_Model_Staging_Adapter_Abstract
     */
    public function updateRun(Enterprise_Staging_Model_Staging $staging, $event = null)
    {
        $this->setStaging($staging);
        $this->setEvent($event);
        return $this;
    }

    /**
     * Create Staging content backup
     *
     * @param Enterprise_Staging_Model_Staging $staging
     * @param Enterprise_Staging_Model_Staging_Event $event
     *
     * @return Enterprise_Staging_Model_Staging_Adapter_Abstract
     */
    public function backupRun(Enterprise_Staging_Model_Staging $staging, $event = null)
    {
        $this->setStaging($staging);
        $this->setEvent($event);
        return $this;
    }

    /**
     * Make staging content merge
     *
     * @param Enterprise_Staging_Model_Staging $staging
     * @param Enterprise_Staging_Model_Staging_Event $event
     *
     * @return Enterprise_Staging_Model_Staging_Adapter_Abstract
     */
    public function mergeRun(Enterprise_Staging_Model_Staging $staging, $event = null)
    {
        $this->setStaging($staging);
        $this->setEvent($event);
        return $this;
    }

    /**
     * Make staging content rollback
     *
     * @param Enterprise_Staging_Model_Staging $staging
     * @param Enterprise_Staging_Model_Staging_Event $event
     *
     * @return Enterprise_Staging_Model_Staging_Adapter_Abstract
     */
    public function rollbackRun(Enterprise_Staging_Model_Staging $staging, $event = null)
    {
        $this->setStaging($staging);
        $this->setEvent($event);
        return $this;
    }

    /**
     * Specify event instance
     *
     * @param   Enterprise_Staging_Model_Staging $staging
     *
     * @return  Enterprise_Staging_Model_Mysql4_Adapter_Abstract
     */
    public function setEvent($event)
    {
        $this->_event = $event;
        return $this;
    }

    /**
     * Retrieve event object
     *
     * @return Enterprise_Staging_Model_Staging
     */
    public function getEvent()
    {
        return $this->_event;
    }

    /**
     * Specify staging instance
     *
     * @param   Enterprise_Staging_Model_Staging $staging
     *
     * @return  Enterprise_Staging_Model_Mysql4_Adapter_Abstract
     */
    public function setStaging(Enterprise_Staging_Model_Staging $staging)
    {
        $this->_staging = $staging;
        return $this;
    }

    /**
     * Retrieve staging object
     *
     * @return Enterprise_Staging_Model_Staging
     */
    public function getStaging()
    {
        return $this->_staging;
    }

    /**
     * Specify item xml config
     *
     * @param   Varien_Simplexml_Config $config
     *
     * @return  Enterprise_Staging_Model_Staging_Adapter_Abstract
     */
    public function setConfig($config)
    {
        $this->_config = $config;
        return $this;
    }

    /**
     * Retrieve item xml config
     *
     * @return Varien_Simplexml_Config
     */
    public function getConfig()
    {
        return $this->_config;
    }

    protected function allowToProceedInWebsiteScope($fields)
    {
        if (in_array('website_id', $fields) || in_array('website_ids', $fields) || in_array('scope_id', $fields)) {
            return true;
        } else {
            return false;
        }
    }

    protected function allowToProceedInStoreScope($fields)
    {
        if (in_array('store_id', $fields) || in_array('store_ids', $fields) || in_array('scope_id', $fields)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Create table
     *
     * @param string $tableName
     * @param string $srcTableName
     * @param bool $isFlat
     *
     * @return Enterprise_Staging_Model_Staging_Adapter_Abstract
     */
    public function createTable($tableName, $srcTableName, $isFlat = false)
    {
        $srcTableDesc = $this->getTableProperties($srcTableName);
        if (!$srcTableDesc) {
            return $this;
        }
        $srcTableDesc['table_name'] = $this->getTable($tableName);
        $srcTableDesc['src_table_name'] = $this->getTable($srcTableName);
        $sql = $this->_getCreateSql($srcTableDesc, $isFlat);

        try {
            $this->_getWriteAdapter()->query($sql);
        } catch (Exception $e) {
            $message = Mage::helper('enterprise_staging')->__('An exception occurred while performing an SQL query: %s. Query: %s', $e->getMessage(), $sql);
            throw new Enterprise_Staging_Exception($message);
        }
        return $this;
    }

    /**
     * Clone Table data
     *
     * @param string $sourceTableName
     * @param string $targetTableName
     * @return Enterprise_Staging_Model_Mysql4_Adapter_Abstract
     */
    public function cloneTable($sourceTableName, $targetTableName)
    {
        // validate tables
        $sourceDesc = $this->getTableProperties($sourceTableName);
        $targetDesc = $this->getTableProperties($targetTableName);

        $diff = array_diff_key($sourceDesc['fields'], $targetDesc['fields']);
        if ($diff) {
            $message = Mage::helper('enterprise_staging')->__('Staging Table "%s" and Master Tables "%s" has different fields',
                $targetTableName, $sourceTableName);
            throw new Enterprise_Staging_Exception($message);
        }

        /* @var $select Varien_Db_Select */
        $fields = array_keys($sourceDesc['fields']);
        $select = $this->_getWriteAdapter()->select()
            ->from(array('s' => $sourceTableName), $fields);
        $sql = $select->insertFromSelect($targetTableName, $fields);

        try {
            $this->_getWriteAdapter()->query($sql);
        }
        catch (Zend_Db_Exception $e) {
            $message = Mage::helper('enterprise_staging')->__('An exception occurred while performing an SQL query: %s. Query: %s', $e->getMessage(), $sql);
            throw new Enterprise_Staging_Exception($message);
        }

        return $this;
    }

    /**
     * Check table for existing and create it if not
     *
     * @param string $tableName
     * @param string $srcTableName
     * @param string $prefix
     *
     * @return Enterprise_Staging_Model_Staging_Adapter_Abstract
     */
    protected function _checkCreateTable($tableName, $srcTableName, $prefix)
    {
        $tableDesc = $this->getTableProperties($tableName);
        if (!$tableDesc) {
            $srcTableDesc = $this->getTableProperties($srcTableName);
            if ($srcTableDesc) {
                $srcTableDesc['table_name'] = $tableName;
                $srcTableDesc['src_table_name'] = $srcTableName;
                if (!empty($srcTableDesc['constraints'])) {
                    foreach ($srcTableDesc['constraints'] as $constraint => $data) {
                        $srcTableDesc['constraints'][$constraint]['fk_name'] = $prefix . $data['fk_name'];
                    }
                }
                $sql = $this->_getCreateSql($srcTableDesc);
                $this->_getWriteAdapter()->query($sql);
            }
        }
        return $this;
    }

    /**
     * Get create table sql
     *
     * @param  mixed  $tableDescription
     * @param bool $isFlat
     * @return string
     */
    protected function _getCreateSql($tableDescription, $isFlat = false)
    {
        $_sql = "CREATE TABLE IF NOT EXISTS `{$tableDescription['table_name']}`\n";

        $rows = array();
        if (!empty($tableDescription['fields'])) {
            foreach ($tableDescription['fields'] as $field) {
                $rows[] = $this->_getFieldSql($field);
            }
        }

        foreach ($tableDescription['keys'] as $key) {
            $rows[] = $this->_getKeySql($key);
        }
        foreach ($tableDescription['constraints'] as $key) {
            if ($isFlat) {
                $rows[] = $this->_getFlatConstraintSql($key, $tableDescription);
            }
            else {
                $rows[] = $this->_getConstraintSql($key);
            }
        }
        $rows = implode(",\n", $rows);
        $_sql .= " ({$rows})";

        if (!empty($tableDescription['engine'])) {
            $_sql .= " ENGINE={$tableDescription['engine']}";
        }
        if (!empty($tableDescription['charset'])) {
            $_sql .= " DEFAULT CHARSET={$tableDescription['charset']}";
        }
        if (!empty($tableDescription['collate'])) {
            $_sql .= " COLLATE={$tableDescription['collate']}";
        }

        return $_sql;
    }

    /**
     * Get sql fields list
     *
     * @param  mixed  $field
     * @return string
     */
    protected function _getFieldSql($field)
    {
        $_fieldSql = "`{$field['name']}` {$field['type']} {$field['extra']}";

        switch ((boolean) $field['is_null']) {
            case true:
                $_fieldSql .= "";
                break;
            case false:
                $_fieldSql .= " NOT NULL";
                break;
        }

        switch ($field['default']) {
            case null:
                $_fieldSql .= "";
                break;
            case 'CURRENT_TIMESTAMP':
                $_fieldSql .= " DEFAULT {$field['default']}";
                break;
            default:
                $_fieldSql .= " DEFAULT '{$field['default']}'";
                break;
        }
        return $_fieldSql;
    }

    /**
     * Get sql keys list
     *
     * @param  mixed  $key
     * @return string
     */
    protected function _getKeySql($key)
    {
        $_keySql = "";
        switch ((string) $key['type']) {
            case 'INDEX':
                $_keySql .= " KEY";
                $_keySql .= " `{$key['name']}`";
                break;
            case 'PRIMARY' :
                $_keySql .= " {$key['type']} KEY";
                break;
            default:
                $_keySql .= " {$key['type']} KEY";
                $_keySql .= " `{$key['name']}`";
                break;
        }
        $fields = array();
        foreach ($key['fields'] as $field) {
            $fields[] = "`{$field}`";
        }
        $fields = implode(',', $fields);
        $_keySql .= "($fields)";
        return $_keySql;
    }

    /**
     * Retrieve SQL fragment for FOREIGN KEY
     *
     * @param array $properties the foreign key properties
     * @param array $table      the table properties
     * @return string
     */
    protected function _getFlatConstraintSql(array $properties, array $table)
    {
        $masterStoreId = explode('_', $table['src_table_name']);
        $masterStoreId = end($masterStoreId);
        $targetStoreId = explode('_', $table['table_name']);
        $targetStoreId = end($targetStoreId);

        $properties['fk_name'] = preg_replace('#_('.$masterStoreId.')(_)?#',
            '_'.$targetStoreId.'\\2', $properties['fk_name']);

        $tpl = ' CONSTRAINT `%s` FOREIGN KEY (`%s`) REFERENCES `%s` (`%s`)%s%s';
        return sprintf($tpl,
            $properties['fk_name'],
            $properties['pri_field'],
            $properties['ref_table'],
            $properties['ref_field'],
            ($properties['on_delete'] ? ' ON DELETE ' . $properties['on_delete'] : ''),
            ($properties['on_update'] ? ' ON UPDATE ' . $properties['on_update'] : '')
        );
    }

    /**
     * Retrieve SQL FOREIGN KEY list
     *
     * @param mixed $key
     * @return string
     */
    protected function _getConstraintSql($key)
    {
        $targetRefTable = $this->getStagingTableName($key['ref_table']);

        if ($targetRefTable) {
            $_refTable = "`$targetRefTable`";
        } else {
            $_refTable = "";
            if ($key['ref_db']) {
                $_refTable .= "`{$key['ref_db']}`.";
            }
            $_refTable .= "`{$key['ref_table']}`";
        }

        $onDelete = "";
        if ($key['on_delete']) {
            $onDelete .= "ON DELETE {$key['on_delete']}";
        }

        $onUpdate = "";
        if ($key['on_update']) {
            $onUpdate .= "ON UPDATE {$key['on_update']}";
        }

        if ($this->getStaging()) {
            $prefix = strtoupper($this->getStaging()->getTablePrefix());
        } else {
            $prefix = 'S_';
        }

        $_keySql = " CONSTRAINT `{$prefix}{$key['fk_name']}` FOREIGN KEY (`{$key['pri_field']}`) "
            . "REFERENCES {$_refTable} (`{$key['ref_field']}`) {$onDelete} {$onUpdate}";

        return $_keySql;
    }

    /**
     * Retrieve table properties as array
     * fields, keys, constraints, engine, charset, create
     *
     * @param string $entityName
     * @param bool   $strongRestrict
     * @return array
     */
    public function getTableProperties($entityName, $strongRestrict = false)
    {
        if (strpos($entityName, '/') !== false) {
            $table = $this->getTable($entityName);
        } else {
            $table = $entityName;
        }

        if (!$this->tableExists($table)) {
            if ($strongRestrict) {
                throw new Enterprise_Staging_Exception(Mage::helper('enterprise_staging')->__('Staging Table %s does not exist', $table));
            }
            return false;
        }

        $prefix = '';

        $tableProp = array(
            'table_name'  => $table,
            'fields'      => array(),
            'keys'        => array(),
            'constraints' => array(),
            'engine'      => 'MYISAM',
            'charset'     => 'utf8',
            'collate'     => null,
            'create_sql'  => null
        );

        // collect fields
        $sql = "SHOW FULL COLUMNS FROM `{$table}`";
        $result = $this->_getReadAdapter()->fetchAll($sql);

        foreach($result as $row) {
            $tableProp['fields'][$row["Field"]] = array(
                'name'      => $row["Field"],
                'type'      => $row["Type"],
                'collation' => $row["Collation"],
                'is_null'   => strtoupper($row["Null"]) == 'YES' ? true : false,
                'key'       => $row["Key"],
                'default'   => $row["Default"],
                'extra'     => $row["Extra"],
                'privileges'=> $row["Privileges"]
            );
        }

        // create sql
        $sql = "SHOW CREATE TABLE `{$table}`";
        $result = $this->_getReadAdapter()->fetchRow($sql);

        $tableProp['create_sql'] = $result["Create Table"];

        // collect keys
        foreach ($this->_getWriteAdapter()->getIndexList($table) as $keyName => $key) {
            $tableProp['keys'][$keyName] = array(
                'type'   => $key['INDEX_TYPE'],
                'name'   => $keyName,
                'fields' => $key['fields']
            );
        }

        // collect CONSTRAINT
        $regExp  = '#,\s+CONSTRAINT `([^`]*)` FOREIGN KEY \(`([^`]*)`\) '
            . 'REFERENCES (`[^`]*\.)?`([^`]*)` \(`([^`]*)`\)'
            . '( ON DELETE (RESTRICT|CASCADE|SET NULL|NO ACTION))?'
            . '( ON UPDATE (RESTRICT|CASCADE|SET NULL|NO ACTION))?#';
        $matches = array();
        preg_match_all($regExp, $tableProp['create_sql'], $matches, PREG_SET_ORDER);
        foreach ($matches as $match) {
            $tableProp['constraints'][strtoupper($match[1])] = array(
                'fk_name'   => strtoupper($match[1]),
                'ref_db'    => isset($match[3]) ? $match[3] : null,
                'pri_table' => $table,
                'pri_field' => $match[2],
                'ref_table' => substr($match[4], strlen($prefix)),
                'ref_field' => $match[5],
                'on_delete' => isset($match[6]) ? $match[7] : '',
                'on_update' => isset($match[8]) ? $match[9] : ''
            );
        }

        // engine
        $regExp = "#(ENGINE|TYPE)="
            . "(MEMORY|HEAP|INNODB|MYISAM|ISAM|BLACKHOLE|BDB|BERKELEYDB|MRG_MYISAM|ARCHIVE|CSV|EXAMPLE)"
            . "#i";
        $match  = array();
        if (preg_match($regExp, $tableProp['create_sql'], $match)) {
            $tableProp['engine'] = strtoupper($match[2]);
        }

        //charset
        $regExp = "#DEFAULT CHARSET=([a-z0-9]+)( COLLATE=([a-z0-9_]+))?#i";
        $match  = array();
        if (preg_match($regExp, $tableProp['create_sql'], $match)) {
            $tableProp['charset'] = strtolower($match[1]);
            if (isset($match[3])) {
                $tableProp['collate'] = $match[3];
            }
        }

        return $tableProp;
    }

    /**
     * Check exists table
     *
     * @param string $table
     * @return bool
     */
    public function tableExists($table)
    {
        $connection = $this->_getReadAdapter();
        $sql        = $connection->quoteInto("SHOW TABLES LIKE ?", $table);
        $stmt       = $connection->query($sql);
        if (!$stmt->fetch()) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * Prepare simple select by given parameters
     *
     * @param mixed  $entityName
     * @param mixed  $fields
     * @param string $where
     *
     * @return string
     */
    protected function _getSimpleSelect($entityName, $fields, $where = null)
    {
        if (is_array($fields)) {
            $fields = $this->_prepareFields($fields);
        }

        if (isset($where)) {
            $where = " WHERE " . $where;
        }

        return "SELECT $fields FROM `".$this->getTable($entityName)."` $where";
    }

    /**
     * Add sql quotes to fields and return imploded string
     *
     * @param array $fields
     * @return string
     */
    protected function _prepareFields($fields)
    {
        foreach ($fields as $k => $field) {
            if ($field instanceof Zend_Db_Expr) {
                $fields[$k] = (string) $field;
            } elseif (is_int($field)) {
                $fields[$k] = "{$field}";
            } else {
                $fields[$k] = "`{$field}`";
            }
        }
        return implode(', ', $fields);
    }

    /**
     * Delete rows by Unique fields
     *
     * @param string $type
     * @param string $scope
     * @param string $srcTable
     * @param string $targetTable
     * @param mixed  $masterIds
     * @param mixed  $slaveIds
     * @param mixed  $keys
     * @param string $addidtionalWhereCondition
     *
     * @return value
     */
    protected function _deleteDataByKeys($type='UNIQUE', $scope='websites', $srcTable, $targetTable, $masterIds, $slaveIds, $keys, $addidtionalWhereCondition=null)
    {
        if (is_array($masterIds)) {
            $masterWhere = " IN (" . implode(", ", $masterIds). ") ";
        } else {
            $masterWhere = " = " . $masterIds;
        }
        if (is_array($slaveIds)) {
            $slaveWhere = " IN (" . implode(", ", $slaveIds). ") ";
        } else {
            $slaveWhere = " = " . $slaveIds;
        }

        foreach ($keys as $keyData) {
            if ($keyData['type'] == $type) {
                $_websiteFieldNameSql = array();
                foreach ($keyData['fields'] as $field) {

                    if ($field == 'website_id' || $field == 'store_id') {
                        $_websiteFieldNameSql[] = " T1.`{$field}` $slaveWhere
                            AND T2.`{$field}` $masterWhere ";
                    } elseif ($field == 'scope_id') {
                        $_websiteFieldNameSql[] = " T1.`scope` = '{$scope}' AND T1.`{$field}` $slaveWhere
                            AND T2.`{$field}` $masterWhere ";
                    } else { //website_ids is update data as rule, so it must be in backup.
                        $_websiteFieldNameSql[] = "T1.`$field` = T2.`$field`";
                    }
                }

                $sql = "DELETE T1.* FROM `{$targetTable}` as T1, `{$srcTable}` as T2 WHERE " . implode(" AND ", $_websiteFieldNameSql);
                if (!empty($addidtionalWhereCondition)) {
                    $addidtionalWhereCondition = str_replace(array($srcTable, $targetTable), array("T2", "T1") , $addidtionalWhereCondition);
                    $sql .= " AND " . $addidtionalWhereCondition;
                }
                return $sql;
            }
        }
        return "";
    }

    /**
     * Retrieve table name for the entity
     *
     * @param string $entityName
     * @return string
     */
    public function getTable($entityName)
    {
        if (strpos($entityName, '/') !== false) {
            $table = parent::getTable($entityName);
        } else {
            $table = $entityName;
        }
        return $table;
    }

    /**
     * Return Staging table name with all prefixes
     *
     * @param string $table
     * @param string $internalPrefix
     * @return string
     */
    public function getStagingTableName($table, $internalPrefix = '')
    {
        if ($internalPrefix) {
            $tablePrefix = Mage::getSingleton('enterprise_staging/staging_config')
                ->getTablePrefix($this->getStaging(), $internalPrefix);
            $table = $tablePrefix . str_replace(Mage::getConfig()->getTablePrefix(), '', $table);
            return $this->_mapTableName($table);
        }
        return $table;
    }

    /**
     * Maping table name
     *
     * @param string $tableName
     * @param bool $direction
     * @return string
     */
    protected function _mapTableName($tableName, $direction = self::REPLACE_DIRECTION_TO)
    {
        foreach ($this->_tableNameMap as $from => $to) {
            if ($direction == self::REPLACE_DIRECTION_TO) {
                $tableName = str_replace($from, $to, $tableName);
            } else {
                $tableName = str_replace($to, $from, $tableName);
            }
        }

        return $tableName;
    }

}
