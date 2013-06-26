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
 * @package     Enterprise_Invitation
 * @copyright   Copyright (c) 2010 Magento Inc. (http://www.magentocommerce.com)
 * @license     http://www.magentocommerce.com/license/enterprise-edition
 */


/**
 * Reports invitation report collection
 *
 * @category   Enterprise
 * @package    Enterprise_Invitation
 */
class Enterprise_Invitation_Model_Mysql4_Report_Invitation_Collection
    extends Enterprise_Invitation_Model_Mysql4_Invitation_Collection
{

    /**
     * Joins Invitation report data, and filter by date
     *
     * @param Zend_Date|string $from
     * @param Zend_Date|string $to
     * @return Enterprise_Invitation_Model_Mysql4_Report_Invitation_Collection
     */
    public function setDateRange($from, $to)
    {
        $this->_reset();

        $this->addFieldToFilter('date', array('from' => $from, 'to' => $to, 'time'=>true))
            ->getSelect()
            ->reset(Zend_Db_Select::COLUMNS)
            ->columns(array(
                'sent' => new Zend_Db_Expr('COUNT(main_table.invitation_id)'),
                'accepted' => new Zend_Db_Expr('COUNT(DISTINCT main_table.referral_id)'),
                'canceled' => new Zend_Db_Expr('COUNT(DISTINCT IF(main_table.status = \'canceled\', main_table.invitation_id, NULL)) '),
                'canceled_rate' => new Zend_Db_Expr('COUNT(DISTINCT IF(main_table.status = \'canceled\', main_table.invitation_id, NULL)) / COUNT(main_table.invitation_id) * 100'),
                'accepted_rate' => new Zend_Db_Expr('COUNT(DISTINCT main_table.referral_id) / COUNT(main_table.invitation_id) * 100')
            ))->group('("*")');

        $this->_joinFields($from, $to);

        return $this;
    }

    /**
     * Join custom fields
     *
     * @return Enterprise_Invitation_Model_Mysql4_Report_Invitation_Collection
     */
    protected function _joinFields()
    {
        return $this;
    }

    /**
     * Filters report by stores
     *
     * @param array $storeIds
     * @return Enterprise_Invitation_Model_Mysql4_Report_Invitation_Collection
     */
    public function setStoreIds($storeIds)
    {
        if ($storeIds) {
            $this->addFieldToFilter('main_table.store_id', array('in' => (array)$storeIds));
        }
        return $this;
    }
}
