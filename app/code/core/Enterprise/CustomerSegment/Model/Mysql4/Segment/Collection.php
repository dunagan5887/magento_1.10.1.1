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
class Enterprise_CustomerSegment_Model_Mysql4_Segment_Collection extends Mage_Core_Model_Mysql4_Collection_Abstract
{
    protected $_customerCountAdded = false;

   /**
     * Intialize collection
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('enterprise_customersegment/segment');
    }

    /**
     * Limit segments collection by is_active column
     *
     * @param int $value
     * @return Enterprise_CustomerSegment_Model_Mysql4_Segment_Collection
     */
    public function addIsActiveFilter($value)
    {
        $this->getSelect()->where('main_table.is_active = ?', $value);
        return $this;
    }

    /**
     * Join website table if needed before load
     *
     * @return Enterprise_CustomerSegment_Model_Mysql4_Segment_Collection
     */
    protected function _beforeLoad()
    {
        $isFilteredByWebsite = $this->getFlag('is_filtered_by_website');
        $isOrderedByWebsite = array_key_exists('website_ids', $this->_orders);
        if (($isFilteredByWebsite || $isOrderedByWebsite) && !$this->getFlag('is_website_table_joined')) {
            $this->setFlag('is_website_table_joined', true);
            $join = ($isFilteredByWebsite ? 'joinInner' : 'joinLeft');
            $cols = ($isOrderedByWebsite ? array('website_ids' => 'website.website_id') : array());
            $this->getSelect()->$join(
                array('website' => $this->getTable('enterprise_customersegment/website')),
                'main_table.segment_id = website.segment_id',
                $cols
            );
        }
        return parent::_beforeLoad();
    }

    /**
     * Redeclare after load method for adding website ids to items
     *
     * @return Enterprise_CustomerSegment_Model_Mysql4_Segment_Collection
     */
    protected function _afterLoad()
    {
        parent::_afterLoad();
        if ($this->getFlag('add_websites_to_result') && $this->_items) {
            $select = $this->getConnection()->select()
                ->from($this->getTable('enterprise_customersegment/website'), array(
                    'segment_id',
                    new Zend_Db_Expr('GROUP_CONCAT(website_id)')
                ))
                ->where('segment_id IN (?)', array_keys($this->_items))
                ->group('segment_id');
            $websites = $this->getConnection()->fetchPairs($select);
            foreach ($this->_items as $item) {
                if (isset($websites[$item->getId()])) {
                    $item->setWebsiteIds(explode(',', $websites[$item->getId()]));
                }
            }
        }

        return $this;
    }

    /**
     * Init flag for adding segment website ids to collection result
     *
     * @param   bool | null $flag
     * @return  Enterprise_CustomerSegment_Model_Mysql4_Segment_Collection
     */
    public function addWebsitesToResult($flag = null)
    {
        $flag = ($flag === null) ? true : $flag;
        $this->setFlag('add_websites_to_result', $flag);
        return $this;
    }

    /**
     * Limit segments collection by event name
     *
     * @param string $eventName
     * @return Enterprise_CustomerSegment_Model_Mysql4_Segment_Collection
     */
    public function addEventFilter($eventName)
    {
        if (!$this->getFlag('is_event_table_joined')) {
            $this->setFlag('is_event_table_joined', true);
            $this->getSelect()->joinInner(
                array('evt'=>$this->getTable('enterprise_customersegment/event')),
                'main_table.segment_id = evt.segment_id',
                array()
            );
        }
        $this->getSelect()->where('evt.event = ?', $eventName);
        return $this;
    }

    /**
     * Limit segments collection by specific website
     *
     * @param int | array | Mage_Core_Model_Website $websiteId
     * @return Enterprise_CustomerSegment_Model_Mysql4_Segment_Collection
     */
    public function addWebsiteFilter($websiteId)
    {
        $this->setFlag('is_filtered_by_website', true);
        if ($websiteId instanceof Mage_Core_Model_Website) {
            $websiteId = $websiteId->getId();
        }
        $this->getSelect()->where('website.website_id IN (?)', $websiteId);
        return $this;
    }

    /**
     * Redeclared for support website id filter
     *
     * @param string $field
     * @param mixed $condition
     * @return Enterprise_CustomerSegment_Model_Mysql4_Segment_Collection
     */
    public function addFieldToFilter($field, $condition=null)
    {
        if ($field == 'website_ids') {
            return $this->addWebsiteFilter($condition);
        } else if ($field == $this->getResource()->getIdFieldName()) {
            $field = 'main_table.' . $field;
        }
        return parent::addFieldToFilter($field, $condition);
    }

    /**
     * Retrieve collection items as option array
     *
     * @return array
     */
    public function toOptionArray()
    {
        return $this->_toOptionArray('segment_id', 'name');
    }

    /**
     * Get SQL for get record count.
     * Reset left join, group and having parts
     *
     * @return Varien_Db_Select
     */
    public function getSelectCountSql()
    {
        $countSelect = parent::getSelectCountSql();
        if ($this->_customerCountAdded) {
            $countSelect->reset(Zend_Db_Select::GROUP);
            $countSelect->reset(Zend_Db_Select::HAVING);
            $countSelect->resetJoinLeft();
        }
        return $countSelect;
    }

    /**
     * Aggregate customer count by each segment
     *
     * @return Enterprise_CustomerSegment_Model_Mysql4_Segment_Collection
     */
    public function addCustomerCountToSelect()
    {
        if ($this->_customerCountAdded) {
            return $this;
        }
        $this->_customerCountAdded = true;
        $this->getSelect()
            ->joinLeft(
                array('customer_count_table' => $this->getTable('enterprise_customersegment/customer')),
                'customer_count_table.segment_id = main_table.segment_id',
                array('customer_count' => new Zend_Db_Expr('COUNT(customer_count_table.customer_id)'))
            )
            ->group('main_table.segment_id');
        return $this;
    }

    /**
     * Add customer count filter
     *
     * @param integer $customerCount
     * @return Enterprise_CustomerSegment_Model_Mysql4_Segment_Collection
     */
    public function addCustomerCountFilter($customerCount)
    {
        $this->addCustomerCountToSelect();
        $this->getSelect()
            ->having('`customer_count` = ?', $customerCount);
        return $this;
    }

    /**
     * Retrive all ids for collection
     *
     * @return array
     */
    public function getAllIds()
    {
        $idsSelect = clone $this->getSelect();
        $idsSelect->reset(Zend_Db_Select::ORDER);
        $idsSelect->reset(Zend_Db_Select::LIMIT_COUNT);
        $idsSelect->reset(Zend_Db_Select::LIMIT_OFFSET);
        $select = $this->getConnection()->select()->from(array('t' => $idsSelect), array(
            't.' . $this->getResource()->getIdFieldName()
        ));
        return $this->getConnection()->fetchCol($select, $this->_bindParams);
    }
}
