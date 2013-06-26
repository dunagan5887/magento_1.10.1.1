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
 * Gift registry type data resource model
 */
class Enterprise_GiftRegistry_Model_Mysql4_Type extends Mage_Core_Model_Mysql4_Abstract
{
    protected $_infoTable;
    protected $_labelTable;

    /**
     * Intialize resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('enterprise_giftregistry/type', 'type_id');

        $this->_infoTable = $this->getTable('enterprise_giftregistry/info');
        $this->_labelTable = $this->getTable('enterprise_giftregistry/label');
    }

    /**
     * Add store date to registry type data
     *
     * @param Mage_Core_Model_Abstract $object
     * @return Mage_Core_Model_Mysql4_Abstract
     */
    protected function _afterLoad(Mage_Core_Model_Abstract $object)
    {
        $select = $this->_getReadAdapter()->select()
            ->from($this->_infoTable, array(
                'scope' => 'IF(store_id = 0, \'default\', \'store\')',
                'label', 'is_listed', 'sort_order'
            ))
            ->where('type_id = ?', $object->getId())
            ->where('store_id IN (0,?)', $object->getStoreId());

        $data = $this->_getReadAdapter()->fetchAssoc($select);

        if (isset($data['store']) && is_array($data['store'])) {
            foreach ($data['store'] as $key => $value) {
                $object->setData($key, ($value !== null) ? $value : $data['default'][$key]);
                $object->setData($key.'_store', $value);
            }
        } else if (isset($data['default'])) {
            foreach ($data['default'] as $key => $value) {
                $object->setData($key, $value);
            }
        }
        return parent::_afterLoad($object);
    }

    /**
     * Perform actions after object save
     *
     * @param Mage_Core_Model_Abstract $object
     */
    protected function _afterSave(Mage_Core_Model_Abstract $object)
    {
        return parent::_afterSave($object);
    }

    /**
     * Save registry type per store view data
     *
     * @param Enterprise_GiftRegistry_Model_Type $type
     */
    public function saveTypeStoreData($type)
    {
        $this->_getWriteAdapter()->delete($this->_infoTable, array(
            'type_id = ?' => $type->getId(),
            'store_id = ?' => $type->getStoreId()
        ));

        $this->_getWriteAdapter()->insert($this->_infoTable, array(
            'type_id' => $type->getId(),
            'store_id' => $type->getStoreId(),
            'label' => $type->getLabel(),
            'is_listed' => $type->getIsListed(),
            'sort_order' => $type->getSortOrder()
        ));
    }

    /**
     * Save store data
     *
     * @param Mage_Core_Model_Abstract $object
     * @param array $data
     * @param string $optionCode
     */
    public function saveStoreData($type, $data, $optionCode = '')
    {
        $adapter = $this->_getWriteAdapter();
        if (isset($data['use_default'])) {
            $adapter->delete($this->_labelTable, array(
                'type_id = ?' => $type->getId(),
                'attribute_code = ?' => $data['code'],
                'store_id = ?' => $type->getStoreId(),
                'option_code = ?' => $optionCode
            ));
        } else {
            $values = array(
                'type_id' => $type->getId(),
                'attribute_code' => $data['code'],
                'store_id' => $type->getStoreId(),
                'option_code' => $optionCode,
                'label' => $data['label']
            );
            $adapter->insertOnDuplicate($this->_labelTable, $values, array('label'));
        }
    }

    /**
     * Get attribute store data
     *
     * @param Enterprise_GiftRegistry_Model_Type $type
     * @param string $code
     * @return null|array
     */
    public function getAttributesStoreData($type)
    {
        $select = $this->_getReadAdapter()->select()
            ->from($this->_labelTable, array('attribute_code', 'option_code', 'label'))
            ->where('type_id = ?', $type->getId())
            ->where('store_id = ?', $type->getStoreId());

        return $this->_getReadAdapter()->fetchAll($select);
    }

    /**
     * Delete attribute store data
     *
     * @param int $typeId
     * @param string $attributeCode
     * @param string $optionCode
     */
    public function deleteAttributeStoreData($typeId, $attributeCode, $optionCode = null)
    {
        $where = array(
            'type_id = ?' => $typeId,
            'attribute_code = ?' => $attributeCode
        );

        if (!is_null($optionCode)) {
            $where['option_code = ?'] = $optionCode;
        }

        $this->_getWriteAdapter()->delete($this->_labelTable, $where);
    }

    /**
     * Delete attribute values
     *
     * @param int $typeId
     * @param string $attributeCode
     * @param bool $personValue
     */
    public function deleteAttributeValues($typeId, $attributeCode, $personValue = false)
    {
        $entityTable = $this->getTable('enterprise_giftregistry/entity');
        $select = $this->_getReadAdapter()->select();
        $select->from(array('e' => $entityTable), array('entity_id'))
            ->where('type_id = ?', $typeId);

        if ($personValue) {
            $table = $this->getTable('enterprise_giftregistry/person');
        } else {
            $table = $this->getTable('enterprise_giftregistry/data');
        }

        $this->_getWriteAdapter()->update($table,
            array($attributeCode => new Zend_Db_Expr('NULL')),
            array('entity_id IN (?)' => $select)
        );
    }
}
