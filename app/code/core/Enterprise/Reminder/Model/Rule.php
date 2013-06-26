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
 * Reminder rules processing model
 */
class Enterprise_Reminder_Model_Rule extends Mage_Rule_Model_Rule
{
    const XML_PATH_EMAIL_TEMPLATE  = 'enterprise_reminder_email_template';

    /**
     * Contains data defined per store view, will be used in email templates as variables
     */
    protected $_storeData = array();

    /**
     * Intialize model
     *
     * @return void
     */
    protected function _construct()
    {
        parent::_construct();
        $this->_init('enterprise_reminder/rule');
    }

    /**
     * Perform actions after object load
     *
     * @return Enterprise_Reminder_Model_Rule
     */
    protected function _afterLoad()
    {
        Mage_Core_Model_Abstract::_afterLoad();
        $conditionsArr = unserialize($this->getConditionsSerialized());
        if (!empty($conditionsArr) && is_array($conditionsArr)) {
            $this->getConditions()->loadArray($conditionsArr);
        }

        $storeData = $this->_getResource()->getStoreData($this->getId());
        $defaultTemplate = self::XML_PATH_EMAIL_TEMPLATE;

        foreach($storeData as $data) {
            $template = (empty($data['template_id'])) ? $defaultTemplate : $data['template_id'];
            $this->setData('store_template_' . $data['store_id'], $template);
            $this->setData('store_label_' . $data['store_id'], $data['label']);
            $this->setData('store_description_' . $data['store_id'], $data['description']);
        }

        return $this;
    }

    /**
     * Perform actions before object save.
     */
    protected function _beforeSave()
    {
        $this->setConditionSql(
            $this->getConditions()->getConditionsSql(null, new Zend_Db_Expr(':website_id'))
        );

        if (!$this->getSalesruleId()) {
            $this->setSalesruleId(null);
        }
        parent::_beforeSave();
    }

    /**
     * Live website ids data as is
     *
     * @return Enterprise_Reminder_Model_Rule
     */
    protected function _prepareWebsiteIds()
    {
        return $this;
    }

    /**
     * Return conditions instance
     *
     * @return Enterprise_Reminder_Model_Rule_Condition_Combine
     */
    public function getConditionsInstance()
    {
        return Mage::getModel('enterprise_reminder/rule_condition_combine_root');
    }

    /**
     * Get rule associated website ids
     *
     * @return array
     */
    public function getWebsiteIds()
    {
        if (!$this->hasData('website_ids')) {
            $this->setData('website_ids', $this->_getResource()->getWebsiteIds($this->getId()));
        }
        return $this->_getData('website_ids');
    }

    /**
     * Send reminder emails
     *
     * @return Enterprise_Reminder_Model_Rule
     */
    public function sendReminderEmails()
    {
        $mail = Mage::getModel('core/email_template');

        /* @var $translate Mage_Core_Model_Translate */
        $translate = Mage::getSingleton('core/translate');
        $translate->setTranslateInline(false);

        $identity = Mage::helper('enterprise_reminder')->getEmailIdentity();

        $this->_matchCustomers();
        $limit = Mage::helper('enterprise_reminder')->getOneRunLimit();

        $recipients = $this->_getResource()->getCustomersForNotification($limit, $this->getRuleId());

        foreach ($recipients as $recipient) {

            /* @var $customer Mage_Customer_Model_Customer */
            $customer = Mage::getModel('customer/customer')->load($recipient['customer_id']);
            if (!$customer || !$customer->getId()) {
                continue;
            }

            if ($customer->getStoreId()) {
                $store = $customer->getStore();
            } else {
                $store = Mage::app()->getWebsite($customer->getWebsiteId())->getDefaultStore();
            }

            $storeData = $this->getStoreData($recipient['rule_id'], $store->getId());
            if (!$storeData) {
                continue;
            }

            /* @var $coupon Mage_SalesRule_Model_Coupon */
            $coupon = Mage::getModel('salesrule/coupon')->load($recipient['coupon_id']);

            $templateVars = array(
                'store' => $store,
                'customer' => $customer,
                'promotion_name' => $storeData['label'],
                'promotion_description' => $storeData['description'],
                'coupon' => $coupon
            );

            $mail->setDesignConfig(array('area' => 'frontend', 'store' => $store->getId()));
            $mail->sendTransactional($storeData['template_id'], $identity,
                $customer->getEmail(), null, $templateVars, $store->getId()
            );

            if ($mail->getSentSuccess()) {
                $this->_getResource()->addNotificationLog($recipient['rule_id'], $customer->getId());
            } else {
                $this->_getResource()->updateFailedEmailsCounter($recipient['rule_id'], $customer->getId());
            }
        }

        $translate->setTranslateInline(true);
        return $this;
    }

    /**
     * Match customers and assign coupons
     *
     * @return Enterprise_Reminder_Model_Observer
     */
    protected function _matchCustomers()
    {
        $threshold = Mage::helper('enterprise_reminder')->getSendFailureThreshold();

        $currentDate = Mage::getModel('core/date')->date('Y-m-d');
        $rules = $this->getCollection()->addDateFilter($currentDate)
            ->addIsActiveFilter(1);

        if ($ruleId = $this->getRuleId()) {
            $rules->addRuleFilter($ruleId);
        }

        foreach ($rules as $rule) {
            $this->_getResource()->deactivateMatchedCustomers($rule->getId());

            if ($rule->getSalesruleId()) {
                /* @var $salesRule Mage_SalesRule_Model_Rule */
                $salesRule = Mage::getSingleton('salesrule/rule')->load($rule->getSalesruleId());
                $websiteIds = array_intersect($rule->getWebsiteIds(), $salesRule->getWebsiteIds());
            } else {
                $salesRule = null;
                $websiteIds = $rule->getWebsiteIds();
            }

            foreach ($websiteIds as $websiteId) {
                $this->_getResource()->saveMatchedCustomers($rule, $salesRule, $websiteId, $threshold);
            }
        }
        return $this;
    }

    /**
     * Return store data
     *
     * @param int $ruleId
     * @param int $storeId
     * @return array|false
     */
    public function getStoreData($ruleId, $storeId)
    {
        if (!isset($this->_storeData[$ruleId][$storeId])) {
            if ($data = $this->_getResource()->getStoreTemplateData($ruleId, $storeId)) {
                if (empty($data['template_id'])) {
                    $data['template_id'] = self::XML_PATH_EMAIL_TEMPLATE;
                }
                $this->_storeData[$ruleId][$storeId] = $data;
            }
            else {
                return false;
            }
        }
        return $this->_storeData[$ruleId][$storeId];
    }
}
