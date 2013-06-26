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
 * Customer Segments grid
 *
 * @category   Enterprise
 * @package    Enterprise_CustomerSegment
 * @author     Magento Core Team <core@magentocommerce.com>
 */
class Enterprise_CustomerSegment_Block_Adminhtml_Report_Customer_Segment_Grid
    extends Mage_Adminhtml_Block_Widget_Grid
{
    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct();
        $this->setId('gridReportCustomersegments');
    }

    /**
     * Prepare report collection
     *
     * @return Enterprise_CustomerSegment_Block_Adminhtml_Report_Customer_Segment_Grid
     */
    protected function _prepareCollection()
    {
        $collection = Mage::getModel('enterprise_customersegment/segment')->getCollection();
        $collection->addCustomerCountToSelect();
        $collection->addWebsitesToResult();
        $this->setCollection($collection);
        return parent::_prepareCollection();
    }

    /**
     * Filter number of customers column
     *
     * @param Mage_Adminhtml_Block_Widget_Grid_Column $column
     * @return Enterprise_CustomerSegment_Block_Adminhtml_Report_Customer_Segment_Grid
     */
    protected function _addColumnFilterToCollection($column)
    {
        if ($column->getId() == 'customer_count') {
            if ($column->getFilter()->getValue() !== null) {
                $this->getCollection()->addCustomerCountFilter($column->getFilter()->getValue());
            }
        } else {
            parent::_addColumnFilterToCollection($column);
        }
        return $this;
    }

    /**
     * Prepare grid columns
     *
     * @return Enterprise_CustomerSegment_Block_Adminhtml_Report_Customer_Segment_Grid
     */
    protected function _prepareColumns()
    {
        parent::_prepareColumns();

        $this->addColumn('segment_id', array(
            'header'    => Mage::helper('enterprise_customersegment')->__('ID'),
            'align'     =>'right',
            'width'     => 50,
            'index'     => 'segment_id',
        ));

        $this->addColumn('name', array(
            'header'    => Mage::helper('enterprise_customersegment')->__('Segment Name'),
            'align'     =>'left',
            'index'     => 'name',
        ));

        $this->addColumn('is_active', array(
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

        $this->addColumn('website', array(
            'header'    => Mage::helper('enterprise_customersegment')->__('Website'),
            'align'     =>'left',
            'width'     => 200,
            'index'     => 'website_ids',
            'type'      => 'options',
            'options'   => Mage::getSingleton('adminhtml/system_store')->getWebsiteOptionHash()
        ));

        $this->addColumn('customer_count', array(
            'header'    => Mage::helper('enterprise_customersegment')->__('Number of Customers'),
            'index'     =>'customer_count',
            'width'     => 200
        ));

        return $this;
    }

    /**
     * Prepare massasction
     *
     * @return Enterprise_CustomerSegment_Block_Adminhtml_Report_Customer_Segment_Grid
     */
    protected function _prepareMassaction()
    {
        $this->setMassactionIdField('segment_id');
        $this->getMassactionBlock()->addItem('view', array(
            'label'=> Mage::helper('enterprise_customersegment')->__('View Combined Report'),
            'url'  => $this->getUrl('*/*/detail', array('_current'=>true)),
            'additional' => array(
                'visibility' => array(
                         'name'     => 'view_mode',
                         'type'     => 'select',
                         'class'    => 'required-entry',
                         'label'    => Mage::helper('enterprise_customersegment')->__('Set'),
                         'values'   => Mage::helper('enterprise_customersegment')->getOptionsArray()
                     )
             )
        ));
        return $this;
    }

    /**
     * Return url for current row
     *
     * @param Enterprise_CustomerSegment_Model_Segment $row
     * @return string
     */
    public function getRowUrl($row)
    {
        return $this->getUrl('*/*/detail', array('segment_id' => $row->getId()));
    }
}
