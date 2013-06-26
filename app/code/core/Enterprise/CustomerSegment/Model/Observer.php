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

/**
 * CustomerSegment observer
 *
 */
class Enterprise_CustomerSegment_Model_Observer
{
    /**
     * Add Customer Segment condition to the salesrule management
     *
     * @param Varien_Event_Observer $observer
     */
    public function addSegmentsToSalesRuleCombine(Varien_Event_Observer $observer)
    {
        if (!Mage::helper('enterprise_customersegment')->isEnabled()) {
            return;
        }
        $additional = $observer->getEvent()->getAdditional();
        $additional->setConditions(array(array(
            'label' => Mage::helper('enterprise_customersegment')->__('Customer Segment'),
            'value' => 'enterprise_customersegment/segment_condition_segment'
        )));
    }

    /**
     * Process customer related data changing. Method can process just events with customer object
     *
     * @param   Varien_Event_Observer $observer
     */
    public function processCustomerEvent(Varien_Event_Observer $observer)
    {
        $eventName = $observer->getEvent()->getName();
        $customer  = $observer->getEvent()->getCustomer();
        $dataObject= $observer->getEvent()->getDataObject();
        $customerId= false;

        if ($customer) {
            $customerId = $customer->getId();
        }
        if (!$customerId && $dataObject) {
            $customerId = $dataObject->getCustomerId();
        }

        if ($customerId) {
            Mage::getSingleton('enterprise_customersegment/customer')->processCustomerEvent(
                $eventName,
                $customerId
            );
        }
    }

    /**
     * Match customer segments on supplied event for currently logged in customer and ran website.
     * Can be used for processing just frontend events
     *
     * @param Varien_Event_Observer $observer
     */
    public function processEvent(Varien_Event_Observer $observer)
    {
        $eventName = $observer->getEvent()->getName();
        $customerSession = Mage::getSingleton('customer/session');
        if (!$customerSession->isLoggedIn()) {
            return $this;
        }
        $customer = $customerSession->getCustomer();
        $website = Mage::app()->getStore()->getWebsite();
        Mage::getSingleton('enterprise_customersegment/customer')->processEvent($eventName, $customer, $website);
    }

    /**
     * Match quote customer to all customer segments.
     * Used before quote recollect in admin
     *
     * @param Varien_Event_Observer $observer
     * @return void
     */
    public function processQuote(Varien_Event_Observer $observer)
    {
        $quote = $observer->getEvent()->getQuote();
        $customer = $quote->getCustomer();
        if ($customer && $customer->getId()) {
            $website = $quote->getStore()->getWebsite();
            Mage::getSingleton('enterprise_customersegment/customer')->processCustomer($customer, $website);
        }
    }

    /**
     * Add field "Use in Customer Segment" for Customer and Customer Address attribute edit form
     *
     * @param Varien_Event_Observer $observer
     */
    public function enterpiseCustomerAttributeEditPrepareForm(Varien_Event_Observer $observer)
    {
        $form       = $observer->getEvent()->getForm();
        $fieldset   = $form->getElement('base_fieldset');
        $fieldset->addField('is_used_for_customer_segment', 'select', array(
            'name'      => 'is_used_for_customer_segment',
            'label'     => Mage::helper('enterprise_customersegment')->__('Use in Customer Segment'),
            'title'     => Mage::helper('enterprise_customersegment')->__('Use in Customer Segment'),
            'values'    => Mage::getModel('adminhtml/system_config_source_yesno')->toOptionArray(),
        ));
    }
}
