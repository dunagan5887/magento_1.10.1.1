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
 * TargetRule Product List Abstract Indexer Resource Model
 *
 * @category   Enterprise
 * @package    Enterprise_TargetRule
 */
abstract class Enterprise_TargetRule_Model_Mysql4_Index_Abstract extends Mage_Core_Model_Mysql4_Abstract
{
    /**
     * Product List Type identifier
     *
     * @var int
     */
    protected $_listType;

    /**
     * Retrieve Product List Type identifier
     *
     * @throws Mage_Core_Exception
     * @return int
     */
    public function getListType()
    {
        if (is_null($this->_listType)) {
            Mage::throwException(
                Mage::helper('enterprise_targetrule')->__('Product list type identifier does not defined')
            );
        }
        return $this->_listType;
    }

    /**
     * Set Product List identifier
     *
     * @param int $listType
     * @return Enterprise_TargetRule_Model_Mysql4_Index_Abstract
     */
    public function setListType($listType)
    {
        $this->_listType = $listType;
        return $this;
    }

    /**
     * Retrieve Product Resource instance
     *
     * @return Mage_Catalog_Model_Resource_Eav_Mysql4_Product
     */
    public function getProductResource()
    {
        return Mage::getResourceSingleton('catalog/product');
    }

    /**
     * Load Product Ids by Index object
     *
     * @param Enterprise_TargetRule_Model_Index $object
     * @return array
     */
    public function loadProductIds($object)
    {
        $select = $this->_getReadAdapter()->select()
            ->from($this->getMainTable(), 'value')
            ->where('entity_id=?', $object->getProduct()->getEntityId())
            ->where('store_id=?', $object->getStoreId())
            ->where('customer_group_id=?', $object->getCustomerGroupId());

        $value  = $this->_getReadAdapter()->fetchOne($select);
        if (!empty($value)) {
            $productIds = explode(',', $value);
        } else {
            $productIds = array();
        }

        return $productIds;
    }

    /**
     * Save matched product Ids
     *
     * @param Enterprise_TargetRule_Model_Index $object
     * @param string $value
     * @return Enterprise_TargetRule_Model_Mysql4_Index_Abstract
     */
    public function saveResult($object, $value)
    {
        $adapter = $this->_getWriteAdapter();
        $data    = array(
            'entity_id'         => $object->getProduct()->getEntityId(),
            'store_id'          => $object->getStoreId(),
            'customer_group_id' => $object->getCustomerGroupId(),
            'value'             => $value
        );

        $adapter->insertOnDuplicate($this->getMainTable(), $data, array('value'));

        return $this;
    }

    /**
     * Remove index by product ids
     *
     * @param Varien_Db_Select|array $entityIds
     * @return Enterprise_TargetRule_Model_Mysql4_Index_Abstract
     */
    public function removeIndex($entityIds)
    {
        $this->_getWriteAdapter()->delete($this->getMainTable(), array(
            'entity_id IN(?)'   => $entityIds
        ));

        return $this;
    }

    /**
     * Remove all data from index
     *
     * @param Mage_Core_Model_Store|int|array $store
     * @return Enterprise_TargetRule_Model_Mysql4_Index_Abstract
     */
    public function cleanIndex($store = null)
    {
        if (is_null($store)) {
            $this->_getWriteAdapter()->truncate($this->getMainTable());
            return $this;
        }
        if ($store instanceof Mage_Core_Model_Store) {
            $store = $store->getId();
        }
        $where = array('store_id IN(?)' => $store);
        $this->_getWriteAdapter()->delete($this->getMainTable(), $where);

        return $this;
    }
}
