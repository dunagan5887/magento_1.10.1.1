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
 * @package     Enterprise_CustomerSegment
 * @copyright   Copyright (c) 2010 Magento Inc. (http://www.magentocommerce.com)
 * @license     http://www.magentocommerce.com/license/enterprise-edition
 */

class Enterprise_CustomerSegment_Block_Adminhtml_Customersegment_Edit extends Mage_Adminhtml_Block_Widget_Form_Container
{

    /**
     * Intialize form
     *
     * @return void
     */
    public function __construct()
    {
        $this->_objectId = 'id';
        $this->_controller = 'adminhtml_customersegment';
        $this->_blockGroup = 'enterprise_customersegment';

        parent::__construct();
        /** @var Enterprise_CustomerSegment_Model_Segment */
        $segment = Mage::registry('current_customer_segment');
        if ($segment) {
            if ($segment->getId()) {
                $this->_addButton('match_customers', array(
                    'label'     => Mage::helper('enterprise_customersegment')->__('Refresh Segment Data'),
                    'onclick'   => 'setLocation(\'' . $this->getMatchUrl() . '\')',
                ), -1);
            }

            if ($segment->isReadonly()) {
                $this->_removeButton('save');
                $this->_removeButton('delete');
            } else {
                $this->_updateButton('save', 'label', Mage::helper('enterprise_customersegment')->__('Save'));
                $this->_updateButton('delete', 'label', Mage::helper('enterprise_customersegment')->__('Delete'));
                $this->_addButton('save_and_continue_edit', array(
                    'class' => 'save',
                    'label' => Mage::helper('enterprise_customersegment')->__('Save and Continue Edit'),
                    'onclick'   => 'saveAndContinueEdit()',
                ), 3);

                $this->_formScripts[] = "
                    function saveAndContinueEdit() {
                        editForm.submit($('edit_form').action + 'back/edit/');
                    }";
            }
        }
    }

    /**
     * Get url for run segment customers matching
     *
     * @return string
     */
    public function getMatchUrl()
    {
        $segment = Mage::registry('current_customer_segment');
        return $this->getUrl('*/*/match', array('id'=>$segment->getId()));
    }

    /**
     * Return form header text
     *
     * @return string
     */
    public function getHeaderText()
    {
        $segment = Mage::registry('current_customer_segment');
        if ($segment->getSegmentId()) {
            return Mage::helper('enterprise_customersegment')->__("Edit Segment '%s'", $this->htmlEscape($segment->getName()));
        }
        else {
            return Mage::helper('enterprise_customersegment')->__('New Segment');
        }
    }
}
