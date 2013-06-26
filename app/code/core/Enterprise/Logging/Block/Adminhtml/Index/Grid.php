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
 * @package     Enterprise_Logging
 * @copyright   Copyright (c) 2010 Magento Inc. (http://www.magentocommerce.com)
 * @license     http://www.magentocommerce.com/license/enterprise-edition
 */

/**
 * Admin Actions Log Grid
 */
class Enterprise_Logging_Block_Adminhtml_Index_Grid extends Mage_Adminhtml_Block_Widget_Grid
{
    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct();

        $this->setId('loggingLogGrid');
        $this->setDefaultSort('time');
        $this->setDefaultDir('DESC');
        $this->setSaveParametersInSession(true);
        $this->setUseAjax(true);
    }

    /**
     * PrepareCollection method.
     */
    protected function _prepareCollection()
    {
        $this->setCollection(Mage::getResourceModel('enterprise_logging/event_collection'));
        return parent::_prepareCollection();
    }

    /**
     * Return grids url
     */
    public function getGridUrl()
    {
        return $this->getUrl('*/*/grid', array('_current' => true));
    }

    /**
     * Grid URL
     *
     * @return string
     */
    public function getRowUrl($row)
    {
        return $this->getUrl('*/*/details', array('event_id'=>$row->getId()));
    }

    /**
     * Configuration of grid
     */
    protected function _prepareColumns()
    {
        $this->addColumn('time', array(
            'header'    => Mage::helper('enterprise_logging')->__('Time'),
            'index'     => 'time',
            'type'      => 'datetime',
            'width'     => 160,
        ));

        $this->addColumn('event', array(
            'header'    => Mage::helper('enterprise_logging')->__('Action Group'),
            'index'     => 'event_code',
            'type'      => 'options',
            'sortable'  => false,
            'options'   => Mage::getSingleton('enterprise_logging/config')->getLabels(),
        ));

        $actions = array();
        foreach (Mage::getResourceSingleton('enterprise_logging/event')->getAllFieldValues('action') as $action) {
            $actions[$action] = $action;
        }
        $this->addColumn('action', array(
            'header'    => Mage::helper('enterprise_logging')->__('Action'),
            'index'     => 'action',
            'type'      => 'options',
            'options'   => $actions,
            'sortable'  => false,
            'width'     => 75,
        ));

        $this->addColumn('ip', array(
            'header'    => Mage::helper('enterprise_logging')->__('IP Address'),
            'index'     => 'ip',
            'type'      => 'text',
            'filter'    => 'enterprise_logging/adminhtml_grid_filter_ip',
            'renderer'  => 'adminhtml/widget_grid_column_renderer_ip',
            'sortable'  => false,
            'width'     => 125,
        ));

        $this->addColumn('user', array(
            'header'    => Mage::helper('enterprise_logging')->__('Username'),
            'index'     => 'user',
            'type'      => 'text',
            'escape'    => true,
            'sortable'  => false,
            'filter'    => 'enterprise_logging/adminhtml_grid_filter_user',
            'width'     => 150,
        ));

        $this->addColumn('status', array(
            'header'    => Mage::helper('enterprise_logging')->__('Result'),
            'index'     => 'status',
            'sortable'  => false,
            'type'      => 'options',
            'options'   => array(
                Enterprise_Logging_Model_Event::RESULT_SUCCESS => Mage::helper('enterprise_logging')->__('Success'),
                Enterprise_Logging_Model_Event::RESULT_FAILURE => Mage::helper('enterprise_logging')->__('Failure'),
            ),
            'width'     => 100,
        ));

        $this->addColumn('fullaction', array(
            'header'   => Mage::helper('enterprise_logging')->__('Full Action Name'),
            'index'    => 'fullaction',
            'sortable' => false,
            'type'     => 'text'
        ));

        $this->addColumn('info', array(
            'header'    => Mage::helper('enterprise_logging')->__('Short Details'),
            'index'     => 'info',
            'type'      => 'text',
            'sortable'  => false,
            'filter'    => 'adminhtml/widget_grid_column_filter_text',
            'renderer'  => 'enterprise_logging/adminhtml_grid_renderer_details',
            'width'     => 100,
        ));

        $this->addColumn('view', array(
            'header'  => Mage::helper('enterprise_logging')->__('Full Details'),
            'width'   => 50,
            'type'    => 'action',
            'getter'  => 'getId',
            'actions' => array(array(
                'caption' => Mage::helper('enterprise_logging')->__('View'),
                'url'     => array(
                    'base'   => '*/*/details',
                ),
                'field'   => 'event_id'
            )),
            'filter'    => false,
            'sortable'  => false,
        ));

        $this->addExportType('*/*/exportCsv', Mage::helper('customer')->__('CSV'));
        $this->addExportType('*/*/exportXml', Mage::helper('customer')->__('Excel XML'));
        return $this;
    }


}
