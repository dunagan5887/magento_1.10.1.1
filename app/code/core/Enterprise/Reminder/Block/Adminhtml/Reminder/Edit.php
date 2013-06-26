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
 * Reminder rules edit block
 */
class Enterprise_Reminder_Block_Adminhtml_Reminder_Edit extends Mage_Adminhtml_Block_Widget_Form_Container
{

    /**
     * Intialize form
     *
     * @return void
     */
    public function __construct()
    {
        $this->_objectId = 'id';
        $this->_blockGroup = 'enterprise_reminder';
        $this->_controller = 'adminhtml_reminder';

        parent::__construct();
        $rule = Mage::registry('current_reminder_rule');

        if ($rule) {
            $this->_updateButton('save', 'label', Mage::helper('enterprise_reminder')->__('Save'));
            $this->_updateButton('delete', 'label', Mage::helper('enterprise_reminder')->__('Delete'));

            if ($rule->getId()) {
                $confirm = Mage::helper('enterprise_reminder')->__('Are you sure you want to match this rule now?');
                if ($limit = Mage::helper('enterprise_reminder')->getOneRunLimit()) {
                    $confirm .= ' ' . Mage::helper('enterprise_reminder')->__('Up to %s customers may receive reminder email after this action.', $limit);
                }
                $this->_addButton('run_now', array(
                    'label'   => Mage::helper('enterprise_reminder')->__('Run Now'),
                    'onclick' => "confirmSetLocation('{$confirm}', '{$this->getRunUrl()}')"
                ), -1);
            }

            $this->_addButton('save_and_continue_edit', array(
                'class'   => 'save',
                'label'   => Mage::helper('enterprise_reminder')->__('Save and Continue Edit'),
                'onclick' => 'editForm.submit($(\'edit_form\').action + \'back/edit/\')',
            ), 3);
        }
    }

    /**
     * Return form header text
     *
     * @return string
     */
    public function getHeaderText()
    {
        $rule = Mage::registry('current_reminder_rule');
        if ($rule->getRuleId()) {
            return Mage::helper('enterprise_reminder')->__("Edit Rule '%s'", $this->htmlEscape($rule->getName()));
        }
        else {
            return Mage::helper('enterprise_reminder')->__('New Rule');
        }
    }

    /**
     * Get url for immidiately run sending process
     *
     * @return string
     */
    public function getRunUrl()
    {
        $rule = Mage::registry('current_reminder_rule');
        return $this->getUrl('*/*/run', array('id' => $rule->getRuleId()));
    }
}
