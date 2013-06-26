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

/**
 * Customer Segment grid
 *
 * @category   Enterprise
 * @package    Enterprise_CustomerSegment
 */
class Enterprise_CustomerSegment_Block_Adminhtml_Customersegment_Grid extends Mage_Adminhtml_Block_Widget_Grid
{
    /**
     * Intialize grid
     */
    public function __construct()
    {
        parent::__construct();
        $this->setId('customersegmentGrid');
        $this->setDefaultSort('name');
        $this->setDefaultDir('ASC');
        $this->setSaveParametersInSession(true);
        $this->setUseAjax(true);
    }

    /**
     * Instantiate and prepare collection
     *
     * @return Enterprise_CustomerSegment_Block_Adminhtml_Customersegment_Grid
     */
    protected function _prepareCollection()
    {
        $collection = Mage::getModel('enterprise_customersegment/segment')->getCollection();
        $collection->addWebsitesToResult();
        $this->setCollection($collection);
        return parent::_prepareCollection();
    }

    /**
     * Prepare columns for grid
     *
     * @return Enterprise_CustomerSegment_Block_Adminhtml_Customersegment_Grid
     */
    protected function _prepareColumns()
    {
        // this column is mandatory for the chooser mode. It needs to be first
        $this->addColumn('grid_segment_id', array(
            'header'    => Mage::helper('enterprise_customersegment')->__('ID'),
            'align'     =>'right',
            'width'     => 50,
            'index'     => 'segment_id',
        ));

        $this->addColumn('grid_segment_name', array(
            'header'    => Mage::helper('enterprise_customersegment')->__('Segment Name'),
            'align'     =>'left',
            'index'     => 'name',
        ));

        $this->addColumn('grid_segment_is_active', array(
            'header'    => Mage::helper('enterprise_customersegment')->__('Status'),
            'align'     => 'left',
            'width'     => 80,
            'index'     => 'is_active',
            'type'      => 'options',
            'options'   => array(
                1 => 'Active',
                0 => 'Inactive',
            ),
        ));

        if (!Mage::app()->isSingleStoreMode()) {
            $this->addColumn('grid_segment_website', array(
                'header'    => Mage::helper('enterprise_customersegment')->__('Website'),
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
     * @param Enterprise_CustomerSegment_Model_Segment $row
     * @return string
     */
    public function getRowUrl($row)
    {
        if ($this->getIsChooserMode()) {
            return null;
        }
        return $this->getUrl('*/*/edit', array('id' => $row->getSegmentId()));
    }

    /**
     * Row click javasctipt callback getter
     *
     * @return string
     */
    public function getRowClickCallback()
    {
        if ($this->getIsChooserMode() && $elementId = $this->getRequest()->getParam('value_element_id')) {
            return 'function (grid, event) {
                var trElement = Event.findElement(event, "tr");
                if (trElement) {
                    $(\'' . $elementId . '\').value = trElement.down("td").innerHTML;
                    $(grid.containerId).up().hide();
                }}';
        }
        return 'openGridRow';
    }

    /**
     * Grid URL getter for ajax mode
     *
     * @return string
     */
    public function getGridUrl()
    {
        return $this->getUrl('adminhtml/customersegment/grid', array('_current' => true));
    }
}
