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
 * Gift registry entity collection
 *
 * @category   Enterprise
 * @package    Enterprise_GiftRegistry
 */
class Enterprise_GiftRegistry_Model_Mysql4_Entity_Collection
    extends Mage_Core_Model_Mysql4_Collection_Abstract
{
    /**
     * Internal constructor
     */
    protected function _construct()
    {
        $this->_init('enterprise_giftregistry/entity', 'entity_id');
    }

    /**
     * Load collection by customer id
     *
     * @param int $id
     * @return Enterprise_GiftRegistry_Model_Mysql4_Entity_Collection
     */
    public function filterByCustomerId($id)
    {
        $this->getSelect()->where('main_table.customer_id = ?', $id);
        return $this;
    }

    /**
     * Load collection by customer id
     *
     * @return Enterprise_GiftRegistry_Model_Mysql4_Entity_Collection
     */
    public function filterByActive()
    {
        $this->getSelect()->where('main_table.is_active = 1');
        return $this;
    }

    /**
     * Add registry info
     *
     * @return Enterprise_GiftRegistry_Model_Mysql4_Entity_Collection
     */
    public function addRegistryInfo()
    {
        $this->_addQtyItemsData();
        $this->_addEventData();
        $this->_addRegistrantData();

        return $this;
    }

    /**
     * Add registry quantity info
     *
     * @return Enterprise_GiftRegistry_Model_Mysql4_Entity_Collection
     */
    protected function _addQtyItemsData()
    {
        $select = $this->getConnection()->select()
            ->from(array('item' => $this->getTable('enterprise_giftregistry/item')), array(
                'entity_id',
                'qty' => new Zend_Db_Expr('SUM(item.qty)'),
                'qty_fulfilled' => new Zend_Db_Expr('SUM(item.qty_fulfilled)'),
                'qty_remaining' => new Zend_Db_Expr('SUM(item.qty - item.qty_fulfilled)')
            ))
            ->group('entity_id');

        $this->getSelect()->joinLeft(
            array('items' => $select),
            'main_table.entity_id = items.entity_id',
            array('qty', 'qty_fulfilled', 'qty_remaining')
        );
        return $this;
    }

    /**
     * Add event info to collection
     *
     * @return Enterprise_GiftRegistry_Model_Mysql4_Entity_Collection
     */
    protected function _addEventData()
    {
        $this->getSelect()->joinLeft(
            array('data' => $this->getTable('enterprise_giftregistry/data')),
            'main_table.entity_id = data.entity_id',
            array('data.event_date')
        );
        return $this;
    }

    /**
     * Add registrant info to collection
     *
     * @return Enterprise_GiftRegistry_Model_Mysql4_Entity_Collection
     */
    protected function _addRegistrantData()
    {
        $select = $this->getConnection()->select()
            ->from($this->getTable('enterprise_giftregistry/person'), array(
                'entity_id',
                'registrants' => new Zend_Db_Expr("GROUP_CONCAT(firstname,' ',lastname SEPARATOR ', ')")
            ))
            ->group('entity_id');

        $this->getSelect()->joinLeft(
            array('person' => $select),
            'main_table.entity_id = person.entity_id',
            array('registrants')
        );
        return $this;
    }

    /**
     * Apply search filters
     *
     * @param array $params
     * @return Enterprise_GiftRegistry_Model_Mysql4_Entity_Collection
     */
    public function applySearchFilters($params)
    {
        $select = $this->getConnection()->select();
        $select->from(array('m' => $this->getMainTable()), array('*'))
            ->where('m.is_public = 1 AND m.is_active = 1')
            ->where('m.website_id=?', Mage::app()->getStore()->getWebsiteId());

        /*
         * Join registry type store label
         */
        $select->joinLeft(
            array('i1' => $this->getTable('enterprise_giftregistry/info')),
            'i1.type_id = m.type_id AND i1.store_id = 0',
            array()
        );
        $select->joinLeft(
            array('i2' => $this->getTable('enterprise_giftregistry/info')),
            'i2.type_id = m.type_id AND i2.store_id = ' . Mage::app()->getStore()->getId(),
            array('type' => new Zend_Db_Expr('IFNULL(i2.label, i1.label)'))
        );

        /*
         * Join registrant data
         */
        $select->joinInner(
            array('p' => $this->getTable('enterprise_giftregistry/person')),
            'm.entity_id = p.entity_id',
            array('registrant' => new Zend_Db_Expr("CONCAT(firstname,' ',lastname)"))
        );

        /*
         * Join entity event data
         */
        $select->joinLeft(
            array('d' => $this->getTable('enterprise_giftregistry/data')),
            'm.entity_id = d.entity_id',
            array('event_date', 'event_location')
        );

        /*
         * Apply search filters
         */
        if (!empty($params['type_id'])) {
            $select->where('m.type_id=?', $params['type_id']);
        }
        if (!empty($params['id'])) {
            $select->where($this->getConnection()->quoteInto('m.url_key =?', $params['id']));
        }
        if (!empty($params['firstname'])) {
            $select->where($this->getConnection()->quoteInto('p.firstname LIKE ?', $params['firstname'] . '%'));
        }
        if (!empty($params['lastname'])) {
            $select->where($this->getConnection()->quoteInto('p.lastname LIKE ?', $params['lastname'] . '%'));
        }
        if (!empty($params['email'])) {
            $select->where($this->getConnection()->quoteInto('p.email =?', $params['email']));
        }

        /*
         * Apply search filters by static attributes
         */
        $config = Mage::getSingleton('enterprise_giftregistry/attribute_config');
        $staticCodes = $config->getStaticTypesCodes();
        foreach ($staticCodes as $code) {
            if (!empty($params[$code])) {
                $select->where($this->getConnection()->quoteInto($code . ' =?', $params[$code]));
            }
        }
        $dateType = $config->getStaticDateType();
        if (!empty($params[$dateType . '_from'])) {
            $select->where($this->getConnection()->quoteInto($dateType . ' >= ?', $params[$dateType . '_from']));
        }
        if (!empty($params[$dateType . '_to'])) {
            $select->where($this->getConnection()->quoteInto($dateType . ' <= ?', $params[$dateType . '_to']));
        }

        $select->group(array('m.entity_id'));
        $this->getSelect()->reset()->from(
            array('main_table' => $select),
            array('*')
        );
        return $this;
    }

    /**
     * Filter collection by specified websites
     *
     * @param array|int $websiteIds
     * @return Enterprise_GiftRegistry_Model_Mysql4_GiftRegistry_Collection
     */
    public function addWebsiteFilter($websiteIds)
    {
        $this->getSelect()->where('main_table.website_id IN (?)', $websiteIds);
        return $this;
    }

    /**
     * Filter collection by specified status
     *
     * @param int $status
     * @return Enterprise_GiftRegistry_Model_Mysql4_Entity_Collection
     */
    public function filterByIsActive($status)
    {
        $this->getSelect()->where('main_table.is_active = ?', $status);
        return $this;
    }
}
