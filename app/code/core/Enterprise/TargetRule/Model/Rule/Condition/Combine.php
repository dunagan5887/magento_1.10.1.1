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


class Enterprise_TargetRule_Model_Rule_Condition_Combine extends Mage_Rule_Model_Condition_Combine
{
    /**
     * Set condition type
     *
     */
    public function __construct()
    {
        parent::__construct();
        $this->setType('enterprise_targetrule/rule_condition_combine');
    }

    /**
     * Prepare list of contitions
     *
     * @return array
     */
    public function getNewChildSelectOptions()
    {
        $conditions = array(
            array(
                'value' => $this->getType(),
                'label' => Mage::helper('enterprise_targetrule')->__('Conditions Combination')
            ),
            Mage::getModel('enterprise_targetrule/rule_condition_product_attributes')->getNewChildSelectOptions(),
        );

        $conditions = array_merge_recursive(parent::getNewChildSelectOptions(), $conditions);
        return $conditions;
    }

    /**
     * Collect validated attributes for Product Collection
     *
     * @param Mage_Catalog_Model_Resource_Eav_Mysql4_Product_Collection $productCollection
     * @return Enterprise_TargetRule_Model_Rule_Condition_Combine
     */
    public function collectValidatedAttributes($productCollection)
    {
        foreach ($this->getConditions() as $condition) {
            $condition->collectValidatedAttributes($productCollection);
        }
        return $this;
    }
}
