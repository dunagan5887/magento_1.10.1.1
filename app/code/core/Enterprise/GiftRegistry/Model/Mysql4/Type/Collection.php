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
 * Gift refistry type resource collection
 */
class Enterprise_GiftRegistry_Model_Mysql4_Type_Collection
    extends Mage_Core_Model_Mysql4_Collection_Abstract
{
    /**
     * Intialize collection
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('enterprise_giftregistry/type');
    }

    /**
     * Add store data to collection
     *
     * @param int $storeId
     * @return Enterprise_GiftRegistry_Model_Mysql4_Type_Collection
     */
    public function addStoreData($storeId = 0)
    {
        $infoTable = $this->getTable('enterprise_giftregistry/info');

        $select = $this->getConnection()->select();
        $select->from(array('m' => $this->getMainTable()), array('*'));

        $select->joinInner(
            array('d' => $infoTable),
            'm.type_id = d.type_id AND d.store_id = 0',
            array()
        );
        $select->joinLeft(
            array('s' => $infoTable),
            's.type_id = m.type_id AND s.store_id = ' . $storeId,
            array(
                'label' => new Zend_Db_Expr('IFNULL(s.label, d.label)'),
                'is_listed' => new Zend_Db_Expr('IFNULL(s.is_listed, d.is_listed)'),
                'sort_order' => new Zend_Db_Expr('IFNULL(s.sort_order, d.sort_order)')
            )
        );

        $this->getSelect()->reset()->from(
            array('main_table' => $select),
            array('*')
        );

        return $this;
    }

    /**
     * Filter collection by listed param
     *
     * @return Enterprise_GiftRegistry_Model_Mysql4_Type_Collection
     */
    public function applyListedFilter()
    {
        $this->getSelect()->where('is_listed = 1');
        return $this;
    }

    /**
     * Apply sorting by sort_order param
     *
     * @return Enterprise_GiftRegistry_Model_Mysql4_Type_Collection
     */
    public function applySortOrder()
    {
        $this->getSelect()->order('sort_order');
        return $this;
    }

    /**
     * Convert collection to array for select options
     *
     * @param bool $withEmpty
     * @return array
     */
    public function toOptionArray($withEmpty = false)
    {
        $result = $this->_toOptionArray('type_id', 'label');
        if ($withEmpty) {
            $result = array_merge(array(array(
                'value' => '',
                'label' => Mage::helper('enterprise_giftregistry')->__('-- All --')
            )), $result);
        }
        return $result;
    }
}
