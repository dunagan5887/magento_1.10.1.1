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
 * @package     Enterprise_Reward
 * @copyright   Copyright (c) 2010 Magento Inc. (http://www.magentocommerce.com)
 * @license     http://www.magentocommerce.com/license/enterprise-edition
 */


/**
 * Reward rate edit form
 *
 * @category    Enterprise
 * @package     Enterprise_Reward
 * @author      Magento Core Team <core@magentocommerce.com>
 */
class Enterprise_Reward_Block_Adminhtml_Reward_Rate_Edit_Form extends Mage_Adminhtml_Block_Widget_Form
{
    /**
     * Getter
     *
     * @return Enterprise_Reward_Model_Reward_Rate
     */
    public function getRate()
    {
        return Mage::registry('current_reward_rate');
    }

    /**
     * Prepare form
     *
     * @return Enterprise_Reward_Block_Adminhtml_Reward_Rate_Edit_Form
     */
    protected function _prepareForm()
    {
        $form = new Varien_Data_Form(array(
            'id' => 'edit_form',
            'action' => $this->getUrl('*/*/save', array('_current' => true)),
            'method' => 'post'
        ));
        $form->setFieldNameSuffix('rate');
        $fieldset = $form->addFieldset('base_fieldset', array(
            'legend' => Mage::helper('enterprise_reward')->__('Reward Exchange Rate Information')
        ));

        $fieldset->addField('website_id', 'select', array(
            'name'   => 'website_id',
            'title'  => Mage::helper('enterprise_reward')->__('Website'),
            'label'  => Mage::helper('enterprise_reward')->__('Website'),
            'values' => Mage::getModel('enterprise_reward/source_website')->toOptionArray()
        ));

        $fieldset->addField('customer_group_id', 'select', array(
            'name'   => 'customer_group_id',
            'title'  => Mage::helper('enterprise_reward')->__('Customer Group'),
            'label'  => Mage::helper('enterprise_reward')->__('Customer Group'),
            'values' => Mage::getModel('enterprise_reward/source_customer_groups')->toOptionArray()
        ));

        $fieldset->addField('direction', 'select', array(
            'name'   => 'direction',
            'title'  => Mage::helper('enterprise_reward')->__('Direction'),
            'label'  => Mage::helper('enterprise_reward')->__('Direction'),
            'values' => $this->getRate()->getDirectionsOptionArray()
        ));

        $rateRenderer = $this->getLayout()
            ->createBlock('enterprise_reward/adminhtml_reward_rate_edit_form_renderer_rate')
            ->setRate($this->getRate());
        $fromIndex = $this->getRate()->getDirection() == Enterprise_Reward_Model_Reward_Rate::RATE_EXCHANGE_DIRECTION_TO_CURRENCY
                   ? 'points' : 'currency_amount';
        $toIndex = $this->getRate()->getDirection() == Enterprise_Reward_Model_Reward_Rate::RATE_EXCHANGE_DIRECTION_TO_CURRENCY
                 ? 'currency_amount' : 'points';
        $fieldset->addField('rate_to_currency', 'note', array(
            'title'             => Mage::helper('enterprise_reward')->__('Rate'),
            'label'             => Mage::helper('enterprise_reward')->__('Rate'),
            'value_index'       => $fromIndex,
            'equal_value_index' => $toIndex
        ))->setRenderer($rateRenderer);

        $form->setUseContainer(true);
        $form->setValues($this->getRate()->getData());
        $this->setForm($form);

        return parent::_prepareForm();
    }
}
