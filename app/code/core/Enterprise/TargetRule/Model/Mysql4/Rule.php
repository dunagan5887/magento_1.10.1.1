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
 * @package     Enterprise_TargetRule
 * @copyright   Copyright (c) 2010 Magento Inc. (http://www.magentocommerce.com)
 * @license     http://www.magentocommerce.com/license/enterprise-edition
 */


/**
 * TargetRule Rule Resource Model
 *
 * @category   Enterprise
 * @package    Enterprise_TargetRule
 */
class Enterprise_TargetRule_Model_Mysql4_Rule extends Mage_Core_Model_Mysql4_Abstract
{
   /**
    * Constructor
    */
    protected function _construct()
    {
        $this->_init('enterprise_targetrule/rule', 'rule_id');
    }

    /**
     * Prepare target rule before save
     *
     * @param Mage_Core_Model_Abstract $object
     */
    protected function _beforeSave(Mage_Core_Model_Abstract $object)
    {
        if ($object->getFromDate() instanceof Zend_Date) {
            $object->setFromDate($object->getFromDate()->toString(Varien_Date::DATETIME_INTERNAL_FORMAT));
        } else {
            $object->setFromDate(null);
        }

        if ($object->getToDate() instanceof Zend_Date) {
            $object->setToDate($object->getToDate()->toString(Varien_Date::DATETIME_INTERNAL_FORMAT));
        } else {
            $object->setToDate(null);
        }
    }

    /**
     * Save Customer Segment relations after save rule
     *
     * @param Enterprise_TargetRule_Model_Rule $object
     * @return Enterprise_TargetRule_Model_Mysql4_Rule
     */
    protected function _afterSave(Mage_Core_Model_Abstract $object)
    {
        parent::_afterSave($object);
//        $this->_saveCustomerSegmentRelations($object);
        $this->_prepareRuleProducts($object);

        if (!$object->isObjectNew() && $object->getOrigData('apply_to') != $object->getData('apply_to')) {
            Mage::getResourceModel('enterprise_targetrule/index')
                ->cleanIndex();
        } else {
            Mage::getResourceModel('enterprise_targetrule/index')
                ->cleanIndex($object->getData('apply_to'));
        }

        return $this;
    }

    /**
     * Remove index before delete rule
     *
     * @param Enterprise_TargetRule_Model_Rule $object
     * @return Enterprise_TargetRule_Model_Mysql4_Rule
     */
    protected function _beforeDelete(Mage_Core_Model_Abstract $object)
    {
        Mage::getResourceModel('enterprise_targetrule/index')
            ->cleanIndex($object->getData('apply_to'));

        return parent::_beforeDelete($object);
    }

    /**
     * Retrieve target rule and customer segment relations table name
     *
     * @return string
     */
    protected function _getCustomerSegmentRelationsTable()
    {
        return $this->getTable('enterprise_targetrule/customersegment');
    }

    /**
     * Retrieve target rule matched by condition products table name
     *
     * @return string
     */
    protected function _getRuleProductsTable()
    {
        return $this->getTable('enterprise_targetrule/product');
    }

    /**
     * Retrieve customer segment relations by target rule id
     *
     * @param int $ruleId
     * @return array
     */
    public function getCustomerSegmentRelations($ruleId)
    {
        $adapter = $this->_getReadAdapter();
        $select = $adapter->select()
            ->from($this->_getCustomerSegmentRelationsTable(), 'segment_id')
            ->where('rule_id=?', $ruleId);
        return $adapter->fetchCol($select);
    }

    /**
     * Save Customer Segment Relations
     *
     * @param Enterprise_TargetRule_Model_Rule $object
     * @return Enterprise_TargetRule_Model_Mysql4_Rule
     */
    protected function _saveCustomerSegmentRelations(Mage_Core_Model_Abstract $object)
    {
        $adapter = $this->_getWriteAdapter();
        $ruleId  = $object->getId();
        if (!$object->getUseCustomerSegment()) {
            $adapter->delete($this->_getCustomerSegmentRelationsTable(), array('rule_id=?' => $ruleId));
            return $this;
        }

        $old = $this->getCustomerSegmentRelations($ruleId);
        $new = $object->getCustomerSegmentRelations();

        $insert = array_diff($new, $old);
        $delete = array_diff($old, $new);

        if (!empty($insert)) {
            $data = array();
            foreach ($insert as $segmentId) {
                $data[] = array(
                    'rule_id'       => $ruleId,
                    'segment_id'    => $segmentId
                );
            }
            $adapter->insertMultiple($this->_getCustomerSegmentRelationsTable(), $data);
        }

        if (!empty($delete)) {
            $where = join(' AND ', array(
                $adapter->quoteInto('rule_id=?', $ruleId),
                $adapter->quoteInto('segment_id IN(?)', $delete)
            ));
            $adapter->delete($this->_getCustomerSegmentRelationsTable(), $where);
        }

        return $this;
    }

    /**
     * Prepare and Save Matched products for Rule
     *
     * @param Enterprise_TargetRule_Model_Rule $object
     * @return Enterprise_TargetRule_Model_Mysql4_Rule
     */
    protected function _prepareRuleProducts($object)
    {
        $adapter = $this->_getWriteAdapter();

        // remove old matched products
        $ruleId  = $object->getId();
        $adapter->delete($this->_getRuleProductsTable(), array('rule_id=?' => $ruleId));

        // retrieve and save new matched product ids
        $chunk = array_chunk($object->getMatchingProductIds(), 1000);
        foreach ($chunk as $productIds) {
            $data = array();
            foreach ($productIds as $productId) {
                $data[] = array(
                    'rule_id'       => $ruleId,
                    'product_id'    => $productId,
                    'store_id'      => 0
                );
            }
            if ($data) {
                $adapter->insertMultiple($this->_getRuleProductsTable(), $data);
            }
        }

        return $this;
    }

    /**
     * Add Customer segment relations to Rule Resource Collection
     *
     * @param Enterprise_TargetRule_Model_Mysql4_Rule_Collection $collection
     * @return Enterprise_TargetRule_Model_Mysql4_Rule
     */
    public function addCustomerSegmentRelationsToCollection(Mage_Core_Model_Mysql4_Collection_Abstract $collection)
    {
        $ruleIds    = array_keys($collection->getItems());
        $segments   = array();
        if ($ruleIds) {
            $adapter = $this->_getReadAdapter();
            $select = $adapter->select()
                ->from($this->_getCustomerSegmentRelationsTable())
                ->where('rule_id IN(?)', $ruleIds);
            $rowSet = $adapter->fetchAll($select);

            foreach ($rowSet as $row) {
                $segments[$row['rule_id']][$row['segment_id']] = $row['segment_id'];
            }
        }

        foreach ($collection->getItems() as $rule) {
            /* @var $rule Enterprise_TargetRule_Model_Rule */
            if ($rule->getUseCustomerSegment()) {
                $data = isset($segments[$rule->getId()]) ? $segments[$rule->getId()] : array();
                $rule->setCustomerSegmentRelations($data);
            }
        }

        return $this;
    }
}
