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
 * @package     Enterprise_CatalogEvent
 * @copyright   Copyright (c) 2010 Magento Inc. (http://www.magentocommerce.com)
 * @license     http://www.magentocommerce.com/license/enterprise-edition
 */


/**
 * Catalog Event resource model
 *
 * @category   Enterprise
 * @package    Enterprise_CatalogEvent
 */
class Enterprise_CatalogEvent_Model_Mysql4_Event extends Mage_Core_Model_Mysql4_Abstract
{

    const EVENT_FROM_PARENT_FIRST = 1;
    const EVENT_FROM_PARENT_LAST  = 2;

    protected $_childToParentList;

    /**
     * var which represented catalogevent collection
     *
     * @var array
     */
    protected $_eventCategories;

    /**
     * Initialize resource
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('enterprise_catalogevent/event', 'event_id');
        $this->addUniqueField(array('field' => 'category_id' , 'title' => Mage::helper('enterprise_catalogevent')->__('Event for selected category')));
    }

    /**
     * Before model save
     *
     * @param Mage_Core_Model_Abstract $object
     * @return Enterprise_CatalogEvent_Model_Mysql4_Event
     */
    protected function _beforeSave(Mage_Core_Model_Abstract $object)
    {
        if (strlen($object->getSortOrder()) === 0) {
            $object->setSortOrder(null);
        }

        return parent::_beforeSave($object);
    }

    /**
     * Retrieve category ids with events
     *
     * @param int|string|Mage_Core_Model_Store $storeId
     * @return array
     */
    public function getCategoryIdsWithEvent($storeId = null)
    {
        $rootCategoryId = Mage::app()->getStore($storeId)->getRootCategoryId();

        /* @var $select Varien_Db_Select */
        $select = Mage::getModel('catalog/category')->getCollection()
            ->setStoreId(Mage::app()->getStore($storeId)->getId())
            ->addIsActiveFilter()
            ->addPathsFilter(Mage_Catalog_Model_Category::TREE_ROOT_ID . '/' . $rootCategoryId)
            ->getSelect();

        $parts = $select->getPart(Zend_Db_Select::FROM);

        if (isset($parts['main_table'])) {
            $categoryCorrelationName = 'main_table';
        } else {
            $categoryCorrelationName = 'e';

        }

        $select->reset(Zend_Db_Select::COLUMNS);
        $select->columns(array('entity_id','level', 'path'), $categoryCorrelationName);

        $select
            ->joinLeft(
                array('event'=>$this->getMainTable()),
                'event.category_id = ' . $categoryCorrelationName . '.entity_id',
                'event_id'
        )->order($categoryCorrelationName . '.level ASC');

        $this->_eventCategories = $this->_getReadAdapter()->fetchAssoc($select);

        if (empty($this->_eventCategories)) {
            return array();
        }
        $this->_setChildToParentList();

        foreach ($this->_eventCategories as $categoryId => $category){
            if ($category['event_id'] === null && isset($category['level']) && $category['level'] > 2) {
                $result[$categoryId] = $this->_getEventFromParent($categoryId, self::EVENT_FROM_PARENT_LAST);
            } elseif  ($category['event_id'] !== null) {
                $result[$categoryId] = $category['event_id'];
            } else {
                $result[$categoryId] = null;
            }
        }

        return $result;
    }

    /**
     * Method for building relates beetwean child and parent node
     *
     * @param array  $collection
     * @return Enterprise_CatalogEvent
     */
    protected function _setChildToParentList() {
        if (is_array($this->_eventCategories)) {
            foreach ($this->_eventCategories as $row) {
                $category = explode('/', $row['path']);
                $amount = count($category);
                if ($amount > 2) {
                    $key = $category[$amount-1];
                    $val = $category[$amount-2];
                    if (empty($this->_childToParentList[$key])) {
                        $this->_childToParentList[$key] = $val;
                    }
                }
            }
        }
        return $this;
    }

    /**
     * Retrieve Event from close parent
     *
     * @param int $categoryId
     * @param int $flag
     * @return unknown
     */
    protected function _getEventFromParent($categoryId, $flag =2) {
        if (isset($this->_childToParentList[$categoryId])) {
            $parentId = $this->_childToParentList[$categoryId];
        }
        if (!isset($parentId)) {
            return null;
        }
        $eventId = null;
        if (isset($this->_eventCategories[$parentId])) {
            $eventId = $this->_eventCategories[$parentId]['event_id'];
        }
        if ($flag == self::EVENT_FROM_PARENT_LAST){
            if (isset($eventId) && ($eventId !== null)) {
                return $eventId;
            }
            elseif ($eventId === null) {
                return $this->_getEventFromParent($parentId, $flag);
            }
        }
        return null;
    }

    /**
     * After model save (save event image)
     *
     * @param Mage_Core_Model_Abstract $object
     * @return Enterprise_CatalogEvent_Model_Mysql4_Event
     */
    protected function _afterSave(Mage_Core_Model_Abstract $object)
    {
        $this->_getWriteAdapter()->delete(
            $this->getTable('event_image'),
             $object->getIdFieldName() . ' = ' . $object->getId() .
            ' AND store_id = ' . $object->getStoreId()
        );

        if ($object->getImage() !== null) {
            $this->_getWriteAdapter()->insert(
                $this->getTable('event_image'),
                array(
                    $object->getIdFieldName() => $object->getId(),
                    'store_id' => $object->getStoreId(),
                    'image' => $object->getImage()
                )
            );
        }
        return parent::_afterSave($object);
    }

    /**
     * After model load (loads event image)
     *
     * @param Mage_Core_Model_Abstract $object
     * @return Enterprise_CatalogEvent_Model_Mysql4_Event
     */
    protected function _afterLoad(Mage_Core_Model_Abstract $object)
    {
        $select = $this->_getReadAdapter()->select()
            ->from($this->getTable('event_image'), array(
                'type' => 'IF(store_id = 0, \'default\', \'store\')',
                'image'
            ))
            ->where($object->getIdFieldName() . ' = ?', $object->getId())
            ->where('store_id IN (0,?)', $object->getStoreId());

        $images = $this->_getReadAdapter()->fetchPairs($select);

        if (isset($images['store'])) {
            $object->setImage($images['store']);
            $object->setImageDefault(isset($images['default']) ? $images['default'] : '');
        }

        if (isset($images['default']) && !isset($images['store'])) {
            $object->setImage($images['default']);
        }

        return parent::_afterLoad($object);
    }
}
