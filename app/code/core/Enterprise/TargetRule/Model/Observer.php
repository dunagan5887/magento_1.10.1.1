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
 * TargetRule observer
 *
 */
class Enterprise_TargetRule_Model_Observer
{
    /**
     * Prepare target rule data
     *
     * @param Varien_Event_Observer $observer
     */
    public function prepareTargetRuleSave(Varien_Event_Observer $observer)
    {
        $_vars = array('targetrule_rule_based_positions', 'targetrule_position_behavior');
        $_varPrefix = array('related_', 'upsell_', 'crosssell_');
        if ($product = $observer->getEvent()->getProduct()) {
            foreach ($_vars as $var) {
                foreach ($_varPrefix as $pref) {
                    $v = $pref . $var;
                    if ($product->getData($v.'_default') == 1) {
                        $product->setData($v, null);
                    }
                }
            }
        }
    }

    /**
     * After Catalog Product Save - rebuild product index by rule conditions
     * and refresh cache index
     *
     * @param Varien_Event_Observer $observer
     * @return Enterprise_TargetRule_Model_Observer
     */
    public function catalogProductAfterSave(Varien_Event_Observer $observer)
    {
        /* @var $product Mage_Catalog_Model_Product */
        $product = $observer->getEvent()->getProduct();

        /* @var $indexResource Enterprise_TargetRule_Model_Mysql4_Index */
        $indexResource = Mage::getResourceSingleton('enterprise_targetrule/index');

        // remove old cache index data
        $indexResource->removeIndexByProductIds($product->getId());

        // remove old matched product index
        $indexResource->removeProductIndex($product->getId());

        $ruleCollection = Mage::getResourceModel('enterprise_targetrule/rule_collection');
        foreach ($ruleCollection as $rule) {
            /* @var $rule Enterprise_TargetRule_Model_Rule */
            if ($rule->validate($product)) {
                $indexResource->saveProductIndex($rule->getId(), $product->getId());
            }
        }
    }
}
