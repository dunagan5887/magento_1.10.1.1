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
 * @package     Enterprise_Customer
 * @copyright   Copyright (c) 2010 Magento Inc. (http://www.magentocommerce.com)
 * @license     http://www.magentocommerce.com/license/enterprise-edition
 */

/**
 * Customer attribute form fieldset element renderer
 *
 * @category   Enterprise
 * @package    Enterprise_Customer
 */
class Enterprise_Customer_Block_Adminhtml_Form_Renderer_Fieldset_Element
    extends Mage_Adminhtml_Block_Widget_Form_Renderer_Fieldset_Element
{
    /**
     * Retrieve data object related with form
     *
     * @return Varien_Object
     */
    public function getDataObject()
    {
        return $this->getElement()->getForm()->getDataObject();
    }

    /**
     * Check "Use default" checkbox display availability
     *
     * @return bool
     */
    public function canDisplayUseDefault()
    {
        $element = $this->getElement();
        if ($element) {
            if ($element->getScope() != 'global' && $element->getScope() != null && $this->getDataObject()
                && $this->getDataObject()->getId() && $this->getDataObject()->getWebsite()->getId()) {
                return true;
            }
        }
        return false;
    }

    /**
     * Check default value usage fact
     *
     * @return bool
     */
    public function usedDefault()
    {
        $key = $this->getElement()->getId();
        if (strpos($key, 'default_value_') === 0) {
            $key = 'default_value';
        }
        $storeValue = $this->getDataObject()->getData('scope_' . $key);
        return ($storeValue === null);
    }

    /**
     * Disable field in default value using case
     *
     * @return Enterprise_Customer_Block_Adminhtml_Form_Renderer_Fieldset_Element
     */
    public function checkFieldDisable()
    {
        if ($this->canDisplayUseDefault() && $this->usedDefault()) {
            $this->getElement()->setDisabled(true);
        }
        return $this;
    }

    /**
     * Retrieve label of attribute scope
     *
     * GLOBAL | WEBSITE | STORE
     *
     * @return string
     */
    public function getScopeLabel()
    {
        $html = '';
        $element = $this->getElement();
        if (Mage::app()->isSingleStoreMode()) {
            return $html;
        }

        if ($element->getScope() == 'global' || $element->getScope() === null) {
            $html = Mage::helper('enterprise_customer')->__('[GLOBAL]');
        } else if ($element->getScope() == 'website') {
            $html = Mage::helper('enterprise_customer')->__('[WEBSITE]');
        } else if ($element->getScope() == 'store') {
            $html = Mage::helper('enterprise_customer')->__('[STORE VIEW]');
        }

        return $html;
    }

    /**
     * Retrieve element label html
     *
     * @return string
     */
    public function getElementLabelHtml()
    {
        return $this->getElement()->getLabelHtml();
    }

    /**
     * Retrieve element html
     *
     * @return string
     */
    public function getElementHtml()
    {
        return $this->getElement()->getElementHtml();
    }
}
