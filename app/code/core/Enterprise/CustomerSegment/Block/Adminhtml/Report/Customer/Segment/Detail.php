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
 * Customer Segments Detail grid container
 *
 * @category   Enterprise
 * @package    Enterprise_CustomerSegment
 * @author     Magento Core Team <core@magentocommerce.com>
 */
class Enterprise_CustomerSegment_Block_Adminhtml_Report_Customer_Segment_Detail
    extends Mage_Adminhtml_Block_Widget_Grid_Container
{
    /**
     * Constructor
     *
     */
    public function __construct()
    {
        $this->_blockGroup = 'enterprise_customersegment';
        $this->_controller = 'adminhtml_report_customer_segment_detail';
        $this->_headerText = (!$this->htmlEscape($this->getCustomerSegment()->getName()))
            ? Mage::helper('enterprise_customersegment')->__('Customer Segments Report')
            : Mage::helper('enterprise_customersegment')->__('Customer Segment Report \'%s\'',$this->htmlEscape($this->getCustomerSegment()->getName()));

        parent::__construct();
        $this->_removeButton('add');
        $this->addButton('back', array(
            'label'     => Mage::helper('enterprise_customersegment')->__('Back'),
            'onclick'   => 'setLocation(\'' . $this->getBackUrl() .'\')',
            'class'     => 'back',
        ));
        $this->addButton('refresh', array(
            'label'     => Mage::helper('enterprise_customersegment')->__('Refresh Segment Data'),
            'onclick'   => 'setLocation(\'' . $this->getRefreshUrl() .'\')',
        ));
    }

    /**
     * Get URL for refresh button
     *
     * @return string
     */
    public function getRefreshUrl()
    {
        return $this->getUrl('*/*/refresh', array('_current' => true));
    }

    /**
     * Get URL for back button
     *
     * @return string
     */
    public function getBackUrl()
    {
        return $this->getUrl('*/*/segment');
    }

    /**
     * Getter
     *
     * @return Enterprise_CustomerSegment_Model_Segment
     */
    public function getCustomerSegment()
    {
        return Mage::registry('current_customer_segment');
    }

    /**
     * Retrieve all websites
     *
     * @return array
     */
    public function getWebsites()
    {
        return Mage::app()->getWebsites();
    }

}
