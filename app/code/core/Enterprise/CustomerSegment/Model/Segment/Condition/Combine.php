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
 * Segment conditions container
 */
class Enterprise_CustomerSegment_Model_Segment_Condition_Combine
    extends Enterprise_CustomerSegment_Model_Condition_Combine_Abstract
{
    /**
     * Intialize model
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
        $this->setType('enterprise_customersegment/segment_condition_combine');
    }

    /**
     * Get inherited conditions selectors
     *
     * @return array
     */
        public function getNewChildSelectOptions()
    {
        $conditions = array(
            array( // subconditions combo
                'value' => 'enterprise_customersegment/segment_condition_combine',
                'label' => Mage::helper('enterprise_customersegment')->__('Conditions Combination')),

            array( // customer address combo
                'value' => 'enterprise_customersegment/segment_condition_customer_address',
                'label' => Mage::helper('enterprise_customersegment')->__('Customer Address')),

            // customer attribute group
            Mage::getModel('enterprise_customersegment/segment_condition_customer')->getNewChildSelectOptions(),

            // shopping cart group
            Mage::getModel('enterprise_customersegment/segment_condition_shoppingcart')->getNewChildSelectOptions(),

            array('value' => array(
                    array( // product list combo
                        'value' => 'enterprise_customersegment/segment_condition_product_combine_list',
                        'label' => Mage::helper('enterprise_customersegment')->__('Product List')),
                    array( // product history combo
                        'value' => 'enterprise_customersegment/segment_condition_product_combine_history',
                        'label' => Mage::helper('enterprise_customersegment')->__('Product History')),
                ),
                'label' => Mage::helper('enterprise_customersegment')->__('Products'),
            ),

            // sales group
            Mage::getModel('enterprise_customersegment/segment_condition_sales')->getNewChildSelectOptions(),
        );

        $conditions = array_merge_recursive(parent::getNewChildSelectOptions(), $conditions);
        return $conditions;
    }
}
