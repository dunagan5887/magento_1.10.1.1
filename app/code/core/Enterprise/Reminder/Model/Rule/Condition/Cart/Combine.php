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
 * Rule conditions container
 */
class Enterprise_Reminder_Model_Rule_Condition_Cart_Combine
    extends Enterprise_Reminder_Model_Condition_Combine_Abstract
{
    /**
     * Intialize model
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
        $this->setType('enterprise_reminder/rule_condition_cart_combine');
    }

    /**
     * Get inherited conditions selectors
     *
     * @return array
     */
    public function getNewChildSelectOptions()
    {
        $prefix = 'enterprise_reminder/rule_condition_cart_';

        return array_merge_recursive(
            parent::getNewChildSelectOptions(), array(
                $this->_getRecursiveChildSelectOption(),
                Mage::getModel("{$prefix}couponcode")->getNewChildSelectOptions(),
                Mage::getModel("{$prefix}itemsquantity")->getNewChildSelectOptions(),
                Mage::getModel("{$prefix}totalquantity")->getNewChildSelectOptions(),
                Mage::getModel("{$prefix}virtual")->getNewChildSelectOptions(),
                Mage::getModel("{$prefix}amount")->getNewChildSelectOptions(),
                array( // subselection combo
                    'value' => 'enterprise_reminder/rule_condition_cart_subselection',
                    'label' => Mage::helper('enterprise_reminder')->__('Items Subselection')
                )
            )
        );
    }
}
