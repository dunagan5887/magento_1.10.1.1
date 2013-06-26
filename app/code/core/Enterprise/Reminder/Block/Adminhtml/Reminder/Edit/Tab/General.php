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
 * Reminder rules edit form general fields
 */
class Enterprise_Reminder_Block_Adminhtml_Reminder_Edit_Tab_General
    extends Mage_Adminhtml_Block_Widget_Form
{
    /**
     * Prepare general properties form
     *
     * @return Enterprise_Reminder_Block_Adminhtml_Reminder_Edit_Tab_General
     */
    protected function _prepareForm()
    {
        $form = new Varien_Data_Form();
        $model = Mage::registry('current_reminder_rule');

        $fieldset = $form->addFieldset('base_fieldset', array(
            'legend'  => Mage::helper('enterprise_reminder')->__('General Information'),
            'comment' => Mage::helper('enterprise_reminder')->__('Reminder emails may promote a shopping cart price rule with or without coupon. If a shopping cart price rule defines an auto-generated coupon, this reminder rule will generate a random coupon code for each customer.'),
        ));

        if ($model->getId()) {
            $fieldset->addField('rule_id', 'hidden', array(
                'name' => 'rule_id',
            ));
        }

        $fieldset->addField('name', 'text', array(
            'name'     => 'name',
            'label'    => Mage::helper('enterprise_reminder')->__('Rule Name'),
            'required' => true,
        ));

        $fieldset->addField('description', 'textarea', array(
            'name'  => 'description',
            'label' => Mage::helper('enterprise_reminder')->__('Description'),
            'style' => 'width: 98%; height: 100px;',
        ));

        $field = $fieldset->addField('salesrule_id', 'note', array(
            'name'  => 'salesrule_id',
            'label' => Mage::helper('enterprise_reminder')->__('Shopping Cart Price Rule'),
            'class' => 'widget-option',
            'value' => $model->getSalesruleId(),
            'note'  => Mage::helper('enterprise_reminder')->__('Promotion rule this reminder will advertise.'),
        ));

        $model->unsSalesruleId();
        $helperBlock = $this->getLayout()->createBlock('adminhtml/promo_widget_chooser');

        if ($helperBlock instanceof Varien_Object) {
            $helperBlock->setConfig($this->getChooserConfig())
                ->setFieldsetId($fieldset->getId())
                ->setTranslationHelper(Mage::helper('salesrule'))
                ->prepareElementHtml($field);
        }

        if (!Mage::app()->isSingleStoreMode()) {
            $fieldset->addField('website_ids','multiselect',array(
                'name'     => 'website_ids',
                'required' => true,
                'label'    => Mage::helper('newsletter')->__('Assigned to Websites'),
                'values'   => Mage::getSingleton('adminhtml/system_store')->getWebsiteValuesForForm(),
                'value'    => $model->getWebsiteIds()
            ));
        }

        $fieldset->addField('is_active', 'select', array(
            'label'    => Mage::helper('enterprise_reminder')->__('Status'),
            'name'     => 'is_active',
            'required' => true,
            'options'  => array(
                '1' => Mage::helper('enterprise_reminder')->__('Active'),
                '0' => Mage::helper('enterprise_reminder')->__('Inactive'),
            ),
        ));

        if (!$model->getId()) {
            $model->setData('is_active', '1');
        }

        $dateFormatIso = Mage::app()->getLocale()->getDateFormat(Mage_Core_Model_Locale::FORMAT_TYPE_SHORT);

        $fieldset->addField('active_from', 'date', array(
            'name'   => 'active_from',
            'label'  => Mage::helper('enterprise_reminder')->__('Active From'),
            'title'  => Mage::helper('enterprise_reminder')->__('Active From'),
            'image'  => $this->getSkinUrl('images/grid-cal.gif'),
            'input_format' => Varien_Date::DATE_INTERNAL_FORMAT,
            'format'       => $dateFormatIso
        ));
        $fieldset->addField('active_to', 'date', array(
            'name'   => 'active_to',
            'label'  => Mage::helper('enterprise_reminder')->__('Active To'),
            'title'  => Mage::helper('enterprise_reminder')->__('Active To'),
            'image'  => $this->getSkinUrl('images/grid-cal.gif'),
            'input_format' => Varien_Date::DATE_INTERNAL_FORMAT,
            'format'       => $dateFormatIso
        ));

        $fieldset->addField('schedule', 'text', array(
            'name' => 'schedule',
            'label' => Mage::helper('enterprise_reminder')->__('Repeat Schedule'),
            'note' => Mage::helper('enterprise_reminder')->__('In what number of days to repeat reminder email, if the rule condition still matches. Enter days, comma-separated.'),
        ));

        $form->setValues($model->getData());
        $this->setForm($form);

        return parent::_prepareForm();
    }

    /**
     * Get chooser config data
     *
     * @return array
     */
    public function getChooserConfig()
    {
        return array(
            'button' => array('open'=>'Select Rule...'),
            'type' => 'adminhtml/promo_widget_chooser_rule'
        );
    }
}
