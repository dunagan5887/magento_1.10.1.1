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
 * Module setup
 *
 */
class Enterprise_SalesArchive_Model_Mysql4_Setup extends Mage_Core_Model_Resource_Setup
{
    /**
     * Call afterApplyAllUpdates flag
     *
     * @var boolean
     */
    protected $_callAfterApplyAllUpdates = true;

    protected $_tablesMap = array(
        'sales/order_grid' =>'enterprise_salesarchive/order_grid',
        'sales/invoice_grid' => 'enterprise_salesarchive/invoice_grid',
        'sales/creditmemo_grid' => 'enterprise_salesarchive/creditmemo_grid',
        'sales/shipment_grid' => 'enterprise_salesarchive/shipment_grid'
    );

    protected $_tableContraintMap = array(
        'sales/order_grid' => array('SALES_FLAT_ORDER_GRID', 'SALES_FLAT_ORDER_GRID_ARCHIVE'),
        'sales/invoice_grid' => array('SALES_FLAT_INVOICE_GRID', 'SALES_FLAT_INVOICE_GRID_ARCHIVE'),
        'sales/creditmemo_grid' => array('SALES_FLAT_CREDITMEMO_GRID', 'SALES_FLAT_CREDITMEMO_GRID_ARCHIVE'),
        'sales/shipment_grid' => array('SALES_FLAT_SHIPMENT_GRID', 'SALES_FLAT_SHIPMENT_GRID_ARCHIVE')
    );

    /**
     * Run each time after applying of all updates,
     * if setup model setted  $_callAfterApplyAllUpdates flag to true
     *
     * @return Enterprise_SalesArchive_Model_Mysql4_Setup
     */
    public function afterApplyAllUpdates()
    {
        $this->_syncArchiveStructure();
        return $this;
    }

    /**
     * Synchronize archive structure
     *
     * @return Enterprise_SalesArchive_Model_Mysql4_Setup
     */
    protected function _syncArchiveStructure()
    {
        foreach ($this->_tablesMap as $sourceTable => $targetTable) {
            $this->_syncTable(
                $this->getTable($sourceTable),
                $this->getTable($targetTable)
            );

            $this->_syncTableIndex(
                $this->getTable($sourceTable),
                $this->getTable($targetTable)
            );

            if (isset($this->_tableContraintMap[$sourceTable])) {
                $this->_syncTableConstraint(
                    $this->getTable($sourceTable),
                    $this->getTable($targetTable),
                    $this->_tableContraintMap[$sourceTable][0],
                    $this->_tableContraintMap[$sourceTable][1]
                );
            }
        }
    }

    /**
     * Fast table describe retrieve
     *
     * @param string $table
     * @return array
     */
    protected function _fastDescribe($table)
    {
        return $this->getConnection()->fetchPairs('DESCRIBE ' . $table);
    }

    /**
     * Synchronize tables structure
     *
     * @param string $sourceTable
     * @param string $targetTable
     * @return Enterprise_SalesArchive_Model_Mysql4_Setup
     */
    protected function _syncTable($sourceTable, $targetTable)
    {
        $sourceFields = $this->_fastDescribe($sourceTable);

        if (!$this->tableExists($targetTable)) {
            $sql = 'CREATE TABLE ' . $this->getConnection()->quoteIdentifier($targetTable) . '(';
            foreach ($sourceFields as $field => $definition) {
                $sql .= ' ' . $this->getConnection()->quoteIdentifier($field) . ' ' . $definition . ',';
            }
            $indecies = $this->getConnection()->getIndexList($sourceTable);
            if (isset($indecies['PRIMARY'])) {
                $sql .= ' PRIMARY KEY (' . implode(',', array_map(
                    array($this->getConnection(), 'quoteIdentifier'),
                    $indecies['PRIMARY']['COLUMNS_LIST']
                )) . ')';
            } else {
                $sql = rtrim($sql, ',');
            }

            $sql .= ') ENGINE=InnoDB DEFAULT CHARSET = utf8';
            $this->getConnection()->query($sql);
        } else {
            $targetFields = $this->_fastDescribe($targetTable);
            foreach ($sourceFields as $field => $definition) {
                if (isset($targetFields[$field]) && $targetFields[$field] === $definition) {
                    continue;
                }

                if (isset($targetFields[$field])) {
                    $this->getConnection()->modifyColumn($targetTable, $field, $definition);
                } else {
                    $this->getConnection()->addColumn($targetTable, $field, $definition);
                    $targetFields[$field] = $definition;
                }
            }

            $previous = false;
            // Synchronize column positions
            foreach ($sourceFields as $field => $definition) {
                if ($previous === false) {
                    reset($targetFields);
                    if (key($targetFields) !== $field) {
                        $this->changeColumnPosition($targetTable, $field, false, true);
                    }
                } else {
                    reset($targetFields);
                    $currentKey = key($targetFields);
                    while($currentKey !== $field) { // Search for column position in target table
                        if (next($targetFields) === false) {
                            $currentKey = false;
                            break;
                        }
                        $currentKey = key($targetFields);
                    }
                    if ($currentKey) {
                        $moved = prev($targetFields) !== false;
                        if (($moved && $previous !== key($targetFields)) || !$moved) { // If column positions diffrent
                            $this->changeColumnPosition($targetTable, $field, $previous);
                        }
                    }
                }

                $previous = $field;
            }
        }

        return $this;
    }

    /**
     * Change columns position
     *
     * @param string $table
     * @param string $column
     * @param boolean $after
     * @param boolean $first
     * @return Enterprise_SalesArchive_Model_Mysql4_Setup
     */
    public function changeColumnPosition($table, $column, $after = false, $first = false)
    {
        if ($after && $first) {
            if (is_string($after)) {
                $first = false;
            } else {
                $after = false;
            }
        } elseif (!$after && !$first) {
            // If no new position specified
            return $this;
        }

        if (!$this->tableExists($table)) {
            Mage::throwException(Mage::helper('enterprise_salesarchive')->__('Table not found'));
        }

        $columns = $this->_fastDescribe($table);

        if (!isset($columns[$column])) {
            Mage::throwException(Mage::helper('enterprise_salesarchive')->__('Column not found'));
        } elseif ($after && !isset($columns[$after])) {
            Mage::throwException(Mage::helper('enterprise_salesarchive')->__('Positioning column not found'));
        }

        if ($after) {
            $sql = sprintf(
                'ALTER TABLE %s MODIFY COLUMN %s %s AFTER %s',
                $this->getConnection()->quoteIdentifier($table),
                $this->getConnection()->quoteIdentifier($column),
                $columns[$column],
                $this->getConnection()->quoteIdentifier($after)
            );
       } else {
            $sql = sprintf(
                'ALTER TABLE %s MODIFY COLUMN %s %s FIRST',
                $this->getConnection()->quoteIdentifier($table),
                $this->getConnection()->quoteIdentifier($column),
                $columns[$column]
            );
       }

        $this->getConnection()->query($sql);
        return $this;
    }

    /**
     * Syncronize table indicies
     *
     * @param string $sourceTable
     * @param string $targetTable
     * @return Enterprise_SalesArchive_Model_Mysql4_Setup
     */
    protected function _syncTableIndex($sourceTable, $targetTable)
    {
        $sourceIndex = $this->getConnection()->getIndexList($sourceTable);
        $targetIndex = $this->getConnection()->getIndexList($targetTable);

        foreach ($sourceIndex as $indexKey => $indexData) {
             if (!isset($targetIndex[$indexKey]) ||
                $this->_checkIndexDifference($sourceIndex[$indexKey], $targetIndex[$indexKey])) {
                $this->getConnection()->addKey($targetTable, $indexData['KEY_NAME'], $indexData['COLUMNS_LIST'], $indexData['INDEX_TYPE']);
             }
        }

        return $this;
    }

    /**
     * Check indicies difference for synchronization
     *
     * @param array $sourceIndex
     * @param array $targetIndex
     * @return boolean
     */
    protected function _checkIndexDifference($sourceIndex, $targetIndex)
    {
        return ($sourceIndex['INDEX_TYPE'] != $targetIndex['INDEX_TYPE'] ||
                count(array_diff($sourceIndex['COLUMNS_LIST'], $targetIndex['COLUMNS_LIST'])) > 0);
    }

    /**
     * Check indicies difference for synchronization
     *
     * @param array $sourceConstraint
     * @param array $targetConstraint
     * @return boolean
     */
    protected function _checkConstraintDifference($sourceConstraint, $targetConstraint)
    {
        return ($sourceConstraint['COLUMN_NAME'] != $targetConstraint['COLUMN_NAME'] ||
                $sourceConstraint['REF_TABLE_NAME'] != $targetConstraint['REF_TABLE_NAME'] ||
                $sourceConstraint['REF_COLUMN_NAME'] != $targetConstraint['REF_COLUMN_NAME'] ||
                $sourceConstraint['ON_DELETE'] != $targetConstraint['ON_DELETE'] ||
                $sourceConstraint['ON_UPDATE'] != $targetConstraint['ON_UPDATE']);
    }

    /**
     * Synchronize tables foreign keys
     *
     * @param string $sourceTable
     * @param string $targetTable
     * @return Enterprise_SalesArchive_Model_Mysql4_Setup
     */
    protected function _syncTableConstraint($sourceTable, $targetTable, $sourceKey, $targetKey)
    {
        $sourceConstraints = $this->getConnection()->getForeignKeys($sourceTable);
        $targetConstraints = $this->getConnection()->getForeignKeys($targetTable);

        $targetConstraintUsedInSource = array();
        foreach ($sourceConstraints as $sourceConstraint => $constraintInfo) {
            $targetConstraint = str_replace($sourceKey, $targetKey, $sourceConstraint);
            if ($sourceConstraint == $targetConstraint) { // Constraint have invalid prefix,
                continue;                                 // we will have conflict in synchoronizing
            }

            if (!isset($targetConstraints[$targetConstraint]) ||
                $this->_checkConstraintDifference($constraintInfo, $targetConstraints[$targetConstraint])) {
                $this->getConnection()->addConstraint(
                    $targetConstraint,
                    $targetTable,
                    $constraintInfo['COLUMN_NAME'],
                    $constraintInfo['REF_TABLE_NAME'],
                    $constraintInfo['REF_COLUMN_NAME'],
                    $constraintInfo['ON_DELETE'],
                    $constraintInfo['ON_UPDATE']
                );
            }

            $targetConstraintUsedInSource[] = $targetConstraint;
        }

        $constraintToDelete = array_diff(array_keys($targetConstraints), $targetConstraintUsedInSource);

        foreach ($constraintToDelete as $constraint) { // Clear old not used constraints
            $this->getConnection()->dropForeignKey($targetTable, $constraint);
        }

        return $this;
    }
}
