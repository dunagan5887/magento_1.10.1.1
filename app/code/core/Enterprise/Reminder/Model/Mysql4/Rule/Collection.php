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
 * @package     Enterprise_Reminder
 * @copyright   Copyright (c) 2010 Magento Inc. (http://www.magentocommerce.com)
 * @license     http://www.magentocommerce.com/license/enterprise-edition
 */

/**
 * Reminder rules resource collection
 */
class Enterprise_Reminder_Model_Mysql4_Rule_Collection extends Mage_Core_Model_Mysql4_Collection_Abstract
{
   /**
     * Intialize collection
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('enterprise_reminder/rule');
    }

    /**
     * Limit rules collection by is_active column
     *
     * @param int $value
     * @return Enterprise_Reminder_Model_Mysql4_Rule_Collection
     */
    public function addIsActiveFilter($value)
    {
        $this->getSelect()->where('main_table.is_active = ?', $value);
        return $this;
    }

    /**
     * Limit rules collection by date columns
     *
     * @param int $value
     * @return Enterprise_Reminder_Model_Mysql4_Rule_Collection
     */
    public function addDateFilter($date)
    {
        $this->getSelect()
            ->where($this->getConnection()->quoteInto('active_from IS NULL OR active_from <= ?', $date))
            ->where($this->getConnection()->quoteInto('active_to IS NULL OR active_to >= ?', $date));

        return $this;
    }

    /**
     * Limit rules collection by separate rule
     *
     * @param int $value
     * @return Enterprise_Reminder_Model_Mysql4_Rule_Collection
     */
    public function addRuleFilter($value)
    {
        $this->getSelect()->where('main_table.rule_id = ?', $value);
        return $this;
    }

    /**
     * Redeclare after load method for adding website ids to items
     *
     * @return Enterprise_Reminder_Model_Mysql4_Rule_Collection
     */
    protected function _afterLoad()
    {
        parent::_afterLoad();
        if ($this->getFlag('add_websites_to_result') && $this->_items) {
            $select = $this->getConnection()->select()
                ->from($this->getTable('enterprise_reminder/website'), array(
                    'rule_id',
                    new Zend_Db_Expr('GROUP_CONCAT(website_id)')
                ))
                ->where('rule_id IN (?)', array_keys($this->_items))
                ->group('rule_id');
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
     * Init flag for adding rule website ids to collection result
     *
     * @param   bool | null $flag
     * @return  Enterprise_Reminder_Model_Mysql4_Rule_Collection
     */
    public function addWebsitesToResult($flag = null)
    {
        $flag = ($flag === null) ? true : $flag;
        $this->setFlag('add_websites_to_result', $flag);
        return $this;
    }

    /**
     * Limit rules collection by specific website
     *
     * @param int | array | Mage_Core_Model_Website $websiteId
     * @return Enterprise_Reminder_Model_Mysql4_Rule_Collection
     */
    public function addWebsiteFilter($websiteId)
    {
        if (!$this->getFlag('is_website_table_joined')) {
            $this->setFlag('is_website_table_joined', true);
            $this->getSelect()->joinInner(
                array('website' => $this->getTable('enterprise_reminder/website')),
                'main_table.rule_id = website.rule_id',
                array()
            );
        }

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
     * @return Enterprise_Reminder_Model_Mysql4_Rule_Collection
     */
    public function addFieldToFilter($field, $condition=null)
    {
        if ($field == 'website_ids') {
            return $this->addWebsiteFilter($condition);
        }
        return parent::addFieldToFilter($field, $condition);
    }
}
