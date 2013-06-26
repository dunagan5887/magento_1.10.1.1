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
 * @package     Enterprise_Cms
 * @copyright   Copyright (c) 2010 Magento Inc. (http://www.magentocommerce.com)
 * @license     http://www.magentocommerce.com/license/enterprise-edition
 */


/**
 * Cms Page Tree Edit Form Container Block
 *
 * @category   Enterprise
 * @package    Enterprise_Cms
 */
class Enterprise_Cms_Block_Adminhtml_Cms_Hierarchy_Edit extends Mage_Adminhtml_Block_Widget_Form_Container
{
    /**
     * Initialize Form Container
     *
     */
    public function __construct()
    {
        $this->_objectId   = 'node_id';
        $this->_blockGroup = 'enterprise_cms';
        $this->_controller = 'adminhtml_cms_hierarchy';

        parent::__construct();

        $this->_updateButton('save', 'onclick', 'hierarchyNodes.save()');
        $this->_updateButton('save', 'label', Mage::helper('enterprise_cms')->__('Save Pages Hierarchy'));
        $this->_removeButton('back');

        if (Mage::getSingleton('enterprise_cms/hierarchy_lock')->isLockedByOther()) {
            $confirmMessage = Mage::helper('enterprise_cms')->__('Are you sure you want to break current lock?');
            $this->addButton('break_lock', array(
                'label'     => Mage::helper('enterprise_cms')->__('Unlock This Page'),
                'onclick'   => "confirmSetLocation('{$confirmMessage}', '{$this->getUrl('*/*/lock')}')"
            ));
            $this->_updateButton('save', 'disabled', true);
            $this->_updateButton('save', 'class', 'disabled');
        }
    }

    /**
     * Retrieve text for header element
     *
     * @return string
     */
    public function getHeaderText()
    {
        return Mage::helper('enterprise_cms')->__('Manage Pages Hierarchy');
    }
}
