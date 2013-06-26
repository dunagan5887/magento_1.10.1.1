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
 * @package     Enterprise_Staging
 * @copyright   Copyright (c) 2010 Magento Inc. (http://www.magentocommerce.com)
 * @license     http://www.magentocommerce.com/license/enterprise-edition
 */

/**
 * Create Staging settings tab
 *
 * @author     Magento Core Team <core@magentocommerce.com>
 */
class Enterprise_Staging_Block_Adminhtml_Staging_Edit_Tabs_Settings extends Mage_Adminhtml_Block_Widget_Form
{
    /**
     * Keep main translate helper instance
     *
     * @var object
     */
    protected $helper;

    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct();
        $this->setFieldNameSuffix('');
    }

    /**
     * Prepare block additional children
     *
     * @return Enterprise_Staging_Block_Manage_Staging_Edit_Tabs_Settings
     */
    protected function _prepareLayout()
    {
        $this->setChild('continue_button',
            $this->getLayout()->createBlock('adminhtml/widget_button')
                ->setData(array(
                    'label'     => Mage::helper('enterprise_staging')->__('Continue'),
                    'onclick'   => "setSettings('".$this->getContinueUrl()."', 'master_website_id', 'type')",
                    'class'     => 'save'
            ))
        );
        return parent::_prepareLayout();
    }

    /**
     * Prepare form before rendering HTML
     *
     * @return Enterprise_Staging_Block_Manage_Staging_Edit_Tabs_Settings
     */
    protected function _prepareForm()
    {
        $form = new Varien_Data_Form();

        $fieldset = $form->addFieldset('settings', array('legend'=>Mage::helper('enterprise_staging')->__('Staging Website Settings')));

        $websiteCollection = Mage::getModel('core/website')->getCollection()
            ->initCache($this->getCache(), 'app', array(Mage_Core_Model_Website::CACHE_TAG));

        $fieldset->addField('master_website_id', 'select', array(
            'label' => Mage::helper('enterprise_staging')->__('Source Website'),
            'title' => Mage::helper('enterprise_staging')->__('Source Website'),
            'name'  => 'master_website_id',
            'value' => '',
            'values'=> $websiteCollection->toOptionArray()
        ));

        $fieldset->addField('type', 'hidden', array(
            'label' => Mage::helper('enterprise_staging')->__('Staging Type'),
            'title' => Mage::helper('enterprise_staging')->__('Staging Type'),
            'name'  => 'type',
            'value' => 'website'
        ));

        $fieldset->addField('continue_button', 'note', array(
            'text'  => $this->getChildHtml('continue_button'),
        ));

        $form->setFieldNameSuffix($this->getFieldNameSuffix());
        $this->setForm($form);

        return parent::_prepareForm();
    }

    /**
     * Retrieve Url for continue button
     *
     * @return string
     */
    public function getContinueUrl()
    {
        return $this->getUrl('*/*/edit', array(
            '_current' => true,
            'master_website_id'  => '{{master_website_id}}',
            'type' => '{{type}}'
        ));
    }
}
