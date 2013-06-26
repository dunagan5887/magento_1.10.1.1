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
 * Sales conditions combine
 */
class Enterprise_CustomerSegment_Model_Segment_Condition_Sales_Combine
    extends Enterprise_CustomerSegment_Model_Condition_Combine_Abstract
{
    protected $_inputType = 'numeric';

    public function __construct()
    {
        parent::__construct();
        $this->setType('enterprise_customersegment/segment_condition_sales_combine');
    }

    /**
     * Get inherited conditions selectors
     *
     * @return array
     */
    public function getNewChildSelectOptions()
    {
        return array_merge_recursive(parent::getNewChildSelectOptions(), array(
            Mage::getModel('enterprise_customersegment/segment_condition_order_status')->getNewChildSelectOptions(),
            // date ranges
            array(
                'value' => array(
                    Mage::getModel('enterprise_customersegment/segment_condition_uptodate')->getNewChildSelectOptions(),
                    Mage::getModel('enterprise_customersegment/segment_condition_daterange')->getNewChildSelectOptions(),
                ),
                'label' => Mage::helper('enterprise_customersegment')->__('Date Ranges')
            ),
        ));
    }

    /**
     * Init attribute select options
     *
     * @return Enterprise_CustomerSegment_Model_Segment_Condition_Sales_Combine
     */
    public function loadAttributeOptions()
    {
        $this->setAttributeOption(array(
            'total'   => Mage::helper('enterprise_customersegment')->__('Total'),
            'average' => Mage::helper('enterprise_customersegment')->__('Average'),
        ));
        return $this;
    }

    /**
     * Get input type for attribute value.
     *
     * @return string
     */
    public function getValueElementType()
    {
        return 'text';
    }

    /**
     * Check if validation should be strict
     *
     * @return bool
     */
    protected function _getRequiredValidation()
    {
        return true;
    }

    /**
     * Get field names map for subfilters
     *
     * @return unknown_type
     */
    protected function _getSubfilterMap()
    {
        return array(
            'order' => 'order.status',
            'date' => 'order.created_at',
        );
    }
}
