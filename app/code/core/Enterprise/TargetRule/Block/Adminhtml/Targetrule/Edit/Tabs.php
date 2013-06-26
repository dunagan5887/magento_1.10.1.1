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
 * Enterprise TargetRule left-navigation block
 *
 */
class Enterprise_TargetRule_Block_Adminhtml_Targetrule_Edit_Tabs extends Mage_Adminhtml_Block_Widget_Tabs
{

    public function __construct()
    {
        parent::__construct();
        $this->setId('targetrule_tabs');
        $this->setDestElementId('edit_form');
        $this->setTitle(Mage::helper('enterprise_targetrule')->__('Product Rule Information'));
    }

    protected function _beforeToHtml()
    {
        $this->addTab('main_section', array(
            'label'     => Mage::helper('enterprise_targetrule')->__('Rule Information'),
            'content'   => $this->getLayout()->createBlock('enterprise_targetrule/adminhtml_targetrule_edit_tab_main')->toHtml(),
            'active'    => true
        ));

        $this->addTab('conditions_section', array(
            'label'     => Mage::helper('enterprise_targetrule')->__('Products to Match'),
            'content'   => $this->getLayout()->createBlock('enterprise_targetrule/adminhtml_targetrule_edit_tab_conditions')->toHtml(),
        ));

        $this->addTab('targeted_products', array(
            'label'     => Mage::helper('enterprise_targetrule')->__('Products to Display'),
            'content'   => $this->getLayout()->createBlock('enterprise_targetrule/adminhtml_targetrule_edit_tab_actions')->toHtml(),
        ));

        return parent::_beforeToHtml();
    }

}
