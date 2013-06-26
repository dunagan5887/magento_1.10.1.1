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
 * @package     Enterprise_GiftRegistry
 * @copyright   Copyright (c) 2010 Magento Inc. (http://www.magentocommerce.com)
 * @license     http://www.magentocommerce.com/license/enterprise-edition
 */

/**
 * Gift registry entity resource model
 */
class Enterprise_GiftRegistry_Model_Mysql4_Entity extends Mage_Core_Model_Mysql4_Abstract
{
    /**
     * Event table name
     *
     * @var string
     */
    protected $_eventTable;

    /**
     * Assigning eventTable
     */
    protected function _construct() {
        $this->_init('enterprise_giftregistry/entity', 'entity_id');
        $this->_eventTable = $this->getTable('enterprise_giftregistry/data');
    }

    /**
     * Converting some data to internal database format
     *
     * @param Mage_Core_Model_Abstract $object
     * @return Mage_Core_Model_Mysql4_Abstract
     */
    protected function _beforeSave(Mage_Core_Model_Abstract $object)
    {
        $customValues = $object->getCustomValues();
        $object->setCustomValues(serialize($customValues));
        return parent::_beforeSave($object);
    }

    /**
     * Fetching data from event table at same time as from entity table
     *
     * @param   string $field
     * @param   mixed $value
     * @return  Zend_Db_Select
     */
    protected function _getLoadSelect($field, $value, $object)
    {
        $select = parent::_getLoadSelect($field, $value, $object);
        $this->_joinEventData($select);

        return $select;
    }

    /**
     * Join event table to select object
     *
     * @param  Zend_Db_Select $select
     * @return Zend_Db_Select
     */
    protected function _joinEventData($select)
    {
        $select->joinLeft(array(
            'e' => $this->_eventTable),
            'e.' . $this->getIdFieldName() . '=' . $this->getMainTable() . '.' . $this->getIdFieldName(), '*'
        );
        return $select;
    }

    /**
     * Perform actions after object is loaded
     *
     * @param Mage_Core_Model_Abstract $object
     */
    protected function _afterLoad(Mage_Core_Model_Abstract $object)
    {
        if ($object->getId()) {
            $object->setTypeById($object->getData('type_id'));
            $object->setCustomValues(unserialize($object->getCustomValues()));
        }
        return parent::_afterLoad($object);
    }

    /**
     * Perform action after object is saved - saving data to the eventTable
     *
     * @param Mage_Core_Model_Abstract $object
     */
    protected function _afterSave(Mage_Core_Model_Abstract $object)
    {
        $data = array();
        foreach ($object->getStaticTypeIds() as $code) {
            $objectData = $object->getData($code);
            if ($objectData) {
                $data[$code] = $objectData;
            }
        }

        $updateFields = array_keys($data);
        if ($object->getId()) {
            $data['entity_id'] = $object->getId();
            $this->_getWriteAdapter()->insertOnDuplicate($this->_eventTable, $data, $updateFields);
        }
        return parent::_afterSave($object);
    }

    /**
     * Fetches typeId for entity
     *
     * @param int
     * @return string
     */
    public function getTypeIdByEntityId($entityId)
    {
        return $this->_getReadAdapter()->fetchOne(
            $this->_getReadAdapter()->select()
                ->from($this->getMainTable(), 'type_id')
                ->where($this->getIdFieldName() . ' = ?', $entityId));
    }

    /**
     * Fetches websiteId for entity
     *
     * @param int
     * @return string
     */
    public function getWebsiteIdByEntityId($entityId)
    {
        return $this->_getReadAdapter()->fetchOne(
            $this->_getReadAdapter()->select()
                ->from($this->getMainTable(), 'website_id')
                ->where($this->getIdFieldName() . ' = ?', $entityId));
    }

    /**
     * Set active entity filtered by customer
     *
     * @param int $customerId
     * @param int $entityId
     * @return Enterprise_GiftRegistry_Model_Mysql4_Entity
     */
    public function setActiveEntity($customerId, $entityId)
    {
        $adapter = $this->_getWriteAdapter();
        $adapter->update($this->getMainTable(),
            array('is_active' => '0'),
            array('customer_id =?' => $customerId)
        );
        $adapter->update($this->getMainTable(),
            array('is_active' => '1'),
            array('customer_id =?' => $customerId, 'entity_id =?' => $entityId)
        );
        return $this;
    }

    /**
     * Load entity by gift registry item id
     *
     * @param Enterprise_GiftRegistry_Model_Entity $object
     * @param int $itemId
     * @return Enterprise_GiftRegistry_Model_Mysql4_Entity
     */
    public function loadByEntityItem($object, $itemId)
    {
        $adapter = $this->_getReadAdapter();
        $select = $adapter->select()->from(array('e' => $this->getMainTable()));
        $select->joinInner(
            array('i' => $this->getTable('enterprise_giftregistry/item')),
            'e.entity_id = i.entity_id AND i.item_id = ' . (int) $itemId,
            array()
        );
        if ($data = $adapter->fetchRow($select)) {
            $object->setData($data);
            $this->_afterLoad($object);
        }
        return $this;
    }

    /**
     * Load entity by url key
     *
     * @param Enterprise_GiftRegistry_Model_Entity $object
     * @param string $urlKey
     * @return Enterprise_GiftRegistry_Model_Mysql4_Entity
     */
    public function loadByUrlKey($object, $urlKey)
    {
        $adapter = $this->_getReadAdapter();
        $select  = $adapter->select()
            ->from($this->getMainTable())
            ->where('url_key=?', $urlKey);

        $this->_joinEventData($select);

        if ($data = $adapter->fetchRow($select)) {
            $object->setData($data);
            $this->_afterLoad($object);
        }

        return $this;
    }
}
