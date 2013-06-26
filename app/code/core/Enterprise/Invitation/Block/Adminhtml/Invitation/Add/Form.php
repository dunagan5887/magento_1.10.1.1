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
 * @package     Enterprise_Invitation
 * @copyright   Copyright (c) 2010 Magento Inc. (http://www.magentocommerce.com)
 * @license     http://www.magentocommerce.com/license/enterprise-edition
 */

/**
 * Invintation create form
 *
 * @category   Enterprise
 * @package    Enterprise_Invitation
 */
class Enterprise_Invitation_Block_Adminhtml_Invitation_Add_Form extends Mage_Adminhtml_Block_Widget_Form
{
    /**
     * Return invitation form action url
     *
     * @return string
     */
    public function getActionUrl()
    {
        return $this->getUrl('*/*/save', array('_current' => true));
    }

    /**
     * Prepare invitation form
     *
     * @return Enterprise_Invitation_Block_Adminhtml_Invitation_Add_Form
     */
    protected function _prepareForm()
    {
        $form = new Varien_Data_Form(
            array(
                'id' => 'edit_form',
                'action' => $this->getActionUrl(),
                'method' => 'post'
            )
        );

        $fieldset = $form->addFieldset('base_fieldset', array(
            'legend' => $this->helper('enterprise_invitation')->__('Invitations Information'),
            'class' => 'fieldset-wide'
        ));

        $fieldset->addField('email', 'textarea', array(
            'label' => $this->helper('enterprise_invitation')->__('Enter Each Email on New Line'),
            'required' => true,
            'class' => 'validate-emails',
            'name' => 'email'
        ));

        $fieldset->addField('message', 'textarea', array(
            'label' => $this->helper('enterprise_invitation')->__('Message'),
            'name' => 'message'
        ));

        if (!Mage::app()->isSingleStoreMode()) {
            $fieldset->addField('store_id', 'select', array(
                'label' => $this->helper('enterprise_invitation')->__('Send From'),
                'required' => true,
                'name' => 'store_id',
                'values' => Mage::getSingleton('adminhtml/system_store')->getStoreValuesForForm()
            ));
        }

        $groups = Mage::getModel('customer/group')->getCollection()
            ->addFieldToFilter('customer_group_id', array('gt'=> 0))
            ->load()
            ->toOptionHash();

        $fieldset->addField('group_id', 'select', array(
            'label' => $this->helper('enterprise_invitation')->__('Invitee Group'),
            'required' => true,
            'name' => 'group_id',
            'values' => $groups
        ));

        $form->setUseContainer(true);
        $this->setForm($form);
        $form->setValues($this->_getSession()->getInvitationFormData());

        return parent::_prepareForm();
    }

    /**
     * Return adminhtml session
     *
     * @return Mage_Adminhtml_Model_Session
     */
    protected function _getSession()
    {
        return Mage::getSingleton('adminhtml/session');
    }

}
