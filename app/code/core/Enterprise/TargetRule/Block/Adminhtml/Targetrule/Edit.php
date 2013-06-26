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
 * Target rule edit form
 *
 */

class Enterprise_TargetRule_Block_Adminhtml_Targetrule_Edit extends Mage_Adminhtml_Block_Widget_Form_Container
{
    protected $_blockGroup = 'enterprise_targetrule';
    protected $_controller = 'adminhtml_targetrule';

    public function __construct()
    {
        parent::__construct();
        $this->_updateButton('save', 'label', Mage::helper('enterprise_targetrule')->__('Save Rule'));
        $this->_updateButton('delete', 'label', Mage::helper('enterprise_targetrule')->__('Delete Rule'));
        $this->_addButton('save_and_continue_edit', array(
            'class' => 'save',
            'label' => Mage::helper('enterprise_targetrule')->__('Save and Continue Edit'),
            'onclick'   => 'saveAndContinueEdit()',
        ), 3);

        $this->_formScripts[] = '
            function saveAndContinueEdit() {
                editForm.submit($(\'edit_form\').action + \'back/edit/\');
            }';
    }

    public function getHeaderText()
    {
        $rule = Mage::registry('current_target_rule');
        if ($rule && $rule->getRuleId()) {
            return Mage::helper('enterprise_targetrule')->__("Edit Rule '%s'", $this->htmlEscape($rule->getName()));
        }
        else {
            return Mage::helper('enterprise_targetrule')->__('New Rule');
        }
    }

}
