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
 * Reminder adminhtml promo rules notice block
 */
class Enterprise_Reminder_Block_Adminhtml_Promo_Notice extends Mage_Adminhtml_Block_Template
{
    /**
     * Preparing block layout
     *
     * @return Enterprise_Reminder_Block_Adminhtml_Promo_Notice
     */
    protected function _prepareLayout()
    {
        if ($salesRule = Mage::registry('current_promo_quote_rule')) {
            $resource = Mage::getResourceModel('enterprise_reminder/rule');
            if ($count = $resource->getAssignedRulesCount($salesRule->getId())) {
                $confirm = Mage::helper('enterprise_reminder')->__('This rule is assigned to %s automated reminder rule(s). Deleting this rule will automatically unassign it.', $count);
                $block = $this->getLayout()->getBlock('promo_quote_edit');
                if ($block instanceof Mage_Adminhtml_Block_Promo_Quote_Edit) {
                    $block->updateButton('delete', 'onclick', 'deleteConfirm(\'' . $confirm . '\', \'' . $block->getDeleteUrl() . '\')');
                }
            }
        }
        return $this;
    }
}
