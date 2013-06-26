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
 * CustomerSegment data resource model
 *
 * @category   Enterprise
 * @package    Enterprise_CustomerSegment
 */
class Enterprise_CustomerSegment_Model_Mysql4_Segment extends Mage_Core_Model_Mysql4_Abstract
{
    /**
     * Segment websites table name
     *
     * @var string
     */
    protected $_websiteTable;

    /**
     * Intialize resource model
     *
     * @return void
     */
    protected function _construct ()
    {
        $this->_init('enterprise_customersegment/segment', 'segment_id');
        $this->_websiteTable = $this->getTable('enterprise_customersegment/website');
    }

    /**
     * Get website ids associated to the segment id
     *
     * @param   int $segmentId
     * @return  array
     */
    public function getWebsiteIds($segmentId)
    {
        $select = $this->_getReadAdapter()->select()
            ->from($this->_websiteTable, 'website_id')
            ->where('segment_id=?', $segmentId);
        return $this->_getReadAdapter()->fetchCol($select);
    }

    /**
     * Perform actions after object save
     *
     * @param Mage_Core_Model_Abstract $object
     */
    protected function _afterSave(Mage_Core_Model_Abstract $object)
    {
        $id = $object->getId();
        $this->_getWriteAdapter()->delete(
            $this->getTable('enterprise_customersegment/event'),
            array('segment_id = ?' => $id)
        );
        if ($object->getMatchedEvents() && is_array($object->getMatchedEvents())) {
            foreach ($object->getMatchedEvents() as $event) {
                $data = array(
                    'segment_id' => $id,
                    'event'      => $event,
                );
                $this->_getWriteAdapter()->insert($this->getTable('enterprise_customersegment/event'), $data);
            }
        }

        if ($object->hasData('website_ids')) {
            $this->_saveWebsiteIds($object);
        }

        return parent::_afterSave($object);
    }

    /**
     * Save all website ids associated to segment
     *
     * @param $segment
     * @return unknown_type
     */
    protected function _saveWebsiteIds($segment)
    {
        $adapter = $this->_getWriteAdapter();
        $adapter->delete($this->_websiteTable, array('segment_id=?'=>$segment->getId()));
        foreach ($segment->getWebsiteIds() as $websiteId) {
            $adapter->insert($this->_websiteTable, array(
                'website_id' => $websiteId,
                'segment_id' => $segment->getId()
            ));
        }
        return $this;
    }

    /**
     * Get select query result
     *
     * @param   Varien_Db_Select|string $sql
     * @param   array $bindParams array of binded variables
     * @return  int
     */
    public function runConditionSql($sql, $bindParams)
    {
        return $this->_getReadAdapter()->fetchOne($sql, $bindParams);
    }

    /**
     * Get empty select object
     *
     * @return Varien_Db_Select
     */
    public function createSelect()
    {
        return $this->_getReadAdapter()->select();
    }

    /**
     * Quote parameters into condition string
     *
     * @param string $string
     * @param string | array $param
     * @return string unknown_type
     */
    public function quoteInto($string, $param)
    {
        return $this->_getReadAdapter()->quoteInto($string, $param);
    }

    /**
     * Get comparison condition for rule condition operatr which will be used in SQL query
     *
     * @param string $operator
     * @return string
     */
    public function getSqlOperator($operator)
    {
        /*
            '{}'  => Mage::helper('rule')->__('contains'),
            '!{}' => Mage::helper('rule')->__('does not contain'),
            '()'  => Mage::helper('rule')->__('is one of'),
            '!()' => Mage::helper('rule')->__('is not one of'),
            requires custom selects
        */

        switch ($operator) {
            case '==':
                return '=';
            case '!=':
                return '<>';
            case '{}':
                return 'LIKE';
            case '!{}':
                return 'NOT LIKE';
            case '[]':
                return 'FIND_IN_SET(%s, %s)';
            case '![]':
                return 'FIND_IN_SET(%s, %s) IS NULL';
            case 'between':
                return "BETWEEN '%s' AND '%s'";
            case '>':
            case '<':
            case '>=':
            case '<=':
                return $operator;
            default:
                Mage::throwException(Mage::helper('enterprise_customersegment')->__('Unknown operator specified.'));
        }
    }

    /**
     * Create string for select "where" condition based on field name, comparison operator and vield value
     *
     * @param string $field
     * @param string $operator
     * @param mixed $value
     * @return string
     */
    public function createConditionSql($field, $operator, $value)
    {
        $sqlOperator = $this->getSqlOperator($operator);
        $condition = '';
        switch ($operator) {
            case '{}':
            case '!{}':
                if (is_array($value)) {
                    if (!empty($value)) {
                        $sqlOperator = ($operator == '{}') ? 'IN' : 'NOT IN';
                        $condition = $this->_getReadAdapter()->quoteInto(
                            $field.' '.$sqlOperator.' (?)', $value
                        );
                    }
                } else {
                    $condition = $this->_getReadAdapter()->quoteInto(
                        $field.' '.$sqlOperator.' ?', '%'.$value.'%'
                    );
                }
                break;
            case '[]':
            case '![]':
                if (is_array($value) && !empty($value)) {
                    $format = 'FIND_IN_SET(%s, %s)';
                    $conditions = array();
                    foreach ($value as $v) {
                        $conditions[] = sprintf($format, $this->_getReadAdapter()->quote($v), $field);
                    }
                    $condition  = sprintf('(%s)=%d', join(' AND ', $conditions), $operator == '[]' ? 1 : 0);
                } else {
                    if ($operator == '[]') {
                        $format = 'FIND_IN_SET(%s, %s)';
                    } else {
                        $format = 'FIND_IN_SET(%s, %s) IS NULL';
                    }
                    $condition = sprintf($format, $this->_getReadAdapter()->quote($value), $field);
                }
                break;
            case 'between':
                $condition = $field.' '.sprintf($sqlOperator, $value['start'], $value['end']);
                break;
            default:
                $condition = $this->_getReadAdapter()->quoteInto(
                    $field.' '.$sqlOperator.' ?', $value
                );
                break;
        }
        return $condition;
    }

    /**
     * Delete association between customer and segment for specific segment
     *
     * @param   Enterprise_CustomerSegment_Model_Segment $segment
     * @return  Enterprise_CustomerSegment_Model_Mysql4_Segment
     */
    public function deleteSegmentCustomers($segment)
    {
        $this->_getWriteAdapter()->delete(
            $this->getTable('enterprise_customersegment/customer'),
            array('segment_id=?' => $segment->getId())
        );
        return $this;
    }

    /**
     * Save customer ids mutched by segment SQL select on specific website
     *
     * @param   Enterprise_CustomerSegment_Model_Segment $segment
     * @param   int $websiteId
     * @param   string $select
     * @return  Enterprise_CustomerSegment_Model_Mysql4_Segment
     */
    public function saveCustomersFromSelect($segment, $websiteId, $select)
    {
        $table = $this->getTable('enterprise_customersegment/customer');
        $adapter = $this->_getWriteAdapter();
        $segmentId = $segment->getId();
        $now = $this->formatDate(time());

        $data = array();
        $count= 0;
        $stmt = $adapter->query($select);
        while ($row = $stmt->fetch()) {
            $data[] = array(
                'segment_id'    => $segmentId,
                'customer_id'   => $row['entity_id'],
                'website_id'    => $websiteId,
                'added_date'    => $now,
                'updated_date'  => $now,
            );
            $count++;
            if ($count>1000) {
                $count = 0;
                $adapter->insertMultiple($table, $data);
                $data = array();
            }
        }
        if ($count>0) {
            $adapter->insertMultiple($table, $data);
        }
        return $this;
    }

    /**
     * Count customers in specified segments
     *
     * @param int $segmentId
     * @return int
     */
    public function getSegmentCustomersQty($segmentId)
    {
        return (int)$this->_getReadAdapter()->fetchOne("SELECT COUNT(DISTINCT customer_id)
            FROM {$this->getTable('enterprise_customersegment/customer')} WHERE segment_id = " . (int)$segmentId);
    }

    /**
     * @deprecate after 1.6.0.0 - please use saveCustomersFromSelect
     */
    public function saveSegmentCustomersFromSelect($segment, $select)
    {
        $table = $this->getTable('enterprise_customersegment/customer');
        $adapter = $this->_getWriteAdapter();
        $segmentId = $segment->getId();
        $now = $this->formatDate(time());

        $adapter->delete($table, $adapter->quoteInto('segment_id=?', $segmentId));

        $data = array();
        $count= 0;
        $stmt = $adapter->query($select);
        while ($row = $stmt->fetch()) {
            $data[] = array(
                'segment_id'    => $segmentId,
                'customer_id'   => $row['entity_id'],
                'added_date'    => $now,
                'updated_date'  => $now,
            );
            $count++;
            if ($count>1000) {
                $count = 0;
                $adapter->insertMultiple($table, $data);
                $data = array();
            }
        }
        if ($count>0) {
            $adapter->insertMultiple($table, $data);
        }
        return $this;
    }
}
