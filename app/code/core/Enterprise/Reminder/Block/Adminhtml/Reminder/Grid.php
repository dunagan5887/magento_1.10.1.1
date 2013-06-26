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
 * Reminder rules grid block
 */
class Enterprise_Reminder_Block_Adminhtml_Reminder_Grid extends Mage_Adminhtml_Block_Widget_Grid
{
    /**
     * Intialize grid
     */
    public function __construct()
    {
        parent::__construct();
        $this->setId('reminderGrid');
        $this->setDefaultSort('rule_id');
        $this->setDefaultDir('ASC');
    }

    /**
     * Instantiate and prepare collection
     *
     * @return Enterprise_Reminder_Block_Adminhtml_Reminder_Grid
     */
    protected function _prepareCollection()
    {
        $collection = Mage::getModel('enterprise_reminder/rule')->getCollection();
        $collection->addWebsitesToResult();
        $this->setCollection($collection);
        return parent::_prepareCollection();
    }

    /**
     * Prepare columns for grid
     *
     * @return Enterprise_Reminder_Block_Adminhtml_Reminder_Grid
     */
    protected function _prepareColumns()
    {
        $this->addColumn('rule_id', array(
            'header'    => Mage::helper('enterprise_reminder')->__('ID'),
            'align'     => 'right',
            'width'     => '50px',
            'index'     => 'rule_id',
        ));

        $this->addColumn('name', array(
            'header'    => Mage::helper('enterprise_reminder')->__('Rule Name'),
            'align'     => 'left',
            'index'     => 'name',
        ));

        $this->addColumn('from_date', array(
            'header'    => Mage::helper('enterprise_reminder')->__('Active From'),
            'align'     => 'left',
            'width'     => '120px',
            'type'      => 'date',
            'default'   => '--',
            'index'     => 'active_from',
        ));

        $this->addColumn('to_date', array(
            'header'    => Mage::helper('enterprise_reminder')->__('Active To'),
            'align'     => 'left',
            'width'     => '120px',
            'type'      => 'date',
            'default'   => '--',
            'index'     => 'active_to',
        ));

        $this->addColumn('is_active', array(
            'header'    => Mage::helper('enterprise_reminder')->__('Status'),
            'align'     => 'left',
            'width'     => '80px',
            'index'     => 'is_active',
            'type'      => 'options',
            'options'   => array(
                1 => 'Active',
                0 => 'Inactive',
            ),
        ));

        if (!Mage::app()->isSingleStoreMode()) {
            $this->addColumn('rule_website', array(
                'header'    => Mage::helper('enterprise_reminder')->__('Website'),
                'align'     =>'left',
                'index'     => 'website_ids',
                'type'      => 'options',
                'sortable'  => false,
                'options'   => Mage::getSingleton('adminhtml/system_store')->getWebsiteOptionHash(),
                'width'     => 200,
            ));
        }
        return parent::_prepareColumns();
    }

    /**
     * Return url for current row
     *
     * @param Enterprise_Reminder_Model_Rule $row
     * @return string
     */
    public function getRowUrl($row)
    {
        return $this->getUrl('*/*/edit', array('id' => $row->getRuleId()));
    }

}
