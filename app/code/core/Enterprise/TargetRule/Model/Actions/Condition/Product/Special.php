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
 * @package     Enterprise_TargetRule
 * @copyright   Copyright (c) 2010 Magento Inc. (http://www.magentocommerce.com)
 * @license     http://www.magentocommerce.com/license/enterprise-edition
 */


/**
 * TargetRule Action Special Product Attributes Condition Model
 *
 * @category   Enterprise
 * @package    Enterprise_TargetRule
 */
class Enterprise_TargetRule_Model_Actions_Condition_Product_Special
    extends Mage_CatalogRule_Model_Rule_Condition_Product
{
    /**
     * Set condition type and value
     *
     */
    public function __construct()
    {
        parent::__construct();
        $this->setType('enterprise_targetrule/actions_condition_product_special');
        $this->setValue(null);
    }

    /**
     * Retrieve new child select options
     *
     * @return array
     */
    public function getNewChildSelectOptions()
    {
        $conditions = array(
            array(
                'value' => 'enterprise_targetrule/actions_condition_product_special_price',
                'label' => Mage::helper('enterprise_targetrule')->__('Price (percentage)')
            )
        );

        return array(
            'value' => $conditions,
            'label' => Mage::helper('enterprise_targetrule')->__('Product Special')
        );
    }
}
