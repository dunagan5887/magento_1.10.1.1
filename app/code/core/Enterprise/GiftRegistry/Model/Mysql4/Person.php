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
 * Gift registry entity registrants resource model
 */
class Enterprise_GiftRegistry_Model_Mysql4_Person extends Mage_Core_Model_Mysql4_Abstract
{
    /**
     * Intialize resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('enterprise_giftregistry/person', 'person_id');
    }

    /**
     * Serialization for custom attributes
     *
     * @param Mage_Core_Model_Abstract $object
     * @return Mage_Core_Model_Mysql4_Abstract
     */
    protected function _beforeSave(Mage_Core_Model_Abstract $object)
    {
        $object->setCustomValues(serialize($object->getCustom()));
        return parent::_beforeSave($object);
    }

    /**
     * Deserialization for custom attributes
     *
     * @param Mage_Core_Model_Abstract $object
     * @return Mage_Core_Model_Mysql4_Abstract
     */
    protected function _afterLoad(Mage_Core_Model_Abstract $object)
    {
        $object->setCustom(unserialize($object->getCustomValues()));
        return parent::_afterLoad($object);
    }

    /**
     * Delete orphan persons
     *
     * @param int $entityId
     * @param array $personLeft - records which should not be deleted
     */
    public function deleteOrphan($entityId, $personLeft = array())
    {
        $condition = array();
        $conditionIn = array();
        $condition[] = $this->_getWriteAdapter()->quoteInto('entity_id=?', $entityId);
        if (is_array($personLeft)) {
            foreach ($personLeft as $personId) {
                $conditionIn[] = $this->_getWriteAdapter()->quoteInto('?', $personId);
            }
            $condition[] = '`person_id` NOT IN(' . implode(',', $conditionIn) . ')';
        }
        $this->_getWriteAdapter()->delete($this->getMainTable(), implode(' AND ', $condition));
        return $this;
    }
}
