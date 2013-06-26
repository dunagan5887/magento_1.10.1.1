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
 * @package     Enterprise_GiftCardAccount
 * @copyright   Copyright (c) 2010 Magento Inc. (http://www.magentocommerce.com)
 * @license     http://www.magentocommerce.com/license/enterprise-edition
 */

class Enterprise_GiftCardAccount_Model_Giftcardaccount extends Mage_Core_Model_Abstract
{
    const STATUS_DISABLED = 0;
    const STATUS_ENABLED  = 1;

    const STATE_AVAILABLE = 0;
    const STATE_USED      = 1;
    const STATE_REDEEMED  = 2;
    const STATE_EXPIRED   = 3;

    const REDEEMABLE     = 1;
    const NOT_REDEEMABLE = 0;

    protected $_eventPrefix = 'enterprise_giftcardaccount';
    protected $_eventObject = 'giftcardaccount';
    /**
     * Giftcard code that was requested for load
     *
     * @var bool|string
     */
    protected $_requestedCode = false;

    protected $_defaultPoolModelClass = 'enterprise_giftcardaccount/pool';

    /**
     * Static variable to contain codes, that were saved on previous steps in series of consecutive saves
     * Used if you use different read and write connections
     *
     * @var array
     */
    protected static $_alreadySelectedIds = array();

    protected function _construct()
    {
        $this->_init('enterprise_giftcardaccount/giftcardaccount');
    }

    protected function _beforeSave()
    {
        parent::_beforeSave();

        if (!$this->getId()) {
            $now = Mage::app()->getLocale()->date()
                    ->setTimezone(Mage_Core_Model_Locale::DEFAULT_TIMEZONE)
                    ->toString(Varien_Date::DATE_INTERNAL_FORMAT);

            $this->setDateCreated($now);
            if (!$this->hasCode()) {
                $this->_defineCode();
            }
            $this->setIsNew(true);
        } else {
            if ($this->getOrigData('balance') != $this->getBalance()) {
                if ($this->getBalance() > 0) {
                    $this->setState(self::STATE_AVAILABLE);
                }
                elseif ($this->getIsRedeemable() && $this->getIsRedeemed())  {
                    $this->setState(self::STATE_REDEEMED);
                }
                else {
                    $this->setState(self::STATE_USED);
                }
            }
        }

        if (is_numeric($this->getLifetime()) && $this->getLifetime() > 0) {
            $this->setDateExpires(date('Y-m-d', strtotime("now +{$this->getLifetime()}days")));
        } else {
            if ($this->getDateExpires()) {
                $expirationDate =  Mage::app()->getLocale()->date(
                    $this->getDateExpires(), Varien_Date::DATE_INTERNAL_FORMAT,
                    null, false);
                $currentDate = Mage::app()->getLocale()->date(
                    null, Varien_Date::DATE_INTERNAL_FORMAT,
                    null, false);
                if ($expirationDate < $currentDate) {
                    Mage::throwException(Mage::helper('enterprise_giftcardaccount')->__('Expiration date cannot be in the past.'));
                }
            } else {
                $this->setDateExpires(null);
            }
        }

        if (!$this->getId() && !$this->hasHistoryAction()) {
            $this->setHistoryAction(Enterprise_GiftCardAccount_Model_History::ACTION_CREATED);
        }

        if (!$this->hasHistoryAction() && $this->getOrigData('balance') != $this->getBalance()) {
            $this->setHistoryAction(Enterprise_GiftCardAccount_Model_History::ACTION_UPDATED)
                ->setBalanceDelta($this->getBalance() - $this->getOrigData('balance'));
        }
        if ($this->getBalance() < 0) {
            Mage::throwException(Mage::helper('enterprise_giftcardaccount')->__('Balance cannot be less than zero.'));
        }
    }

    protected function _afterSave()
    {
        if ($this->getIsNew()) {
            $this->getPoolModel()
                ->setId($this->getCode())
                ->setStatus(Enterprise_GiftCardAccount_Model_Pool_Abstract::STATUS_USED)
                ->save();
            self::$_alreadySelectedIds[] = $this->getCode();
        }

        parent::_afterSave();
    }

    /**
     * Generate and save gift card account code
     *
     * @return Enterprise_GiftCardAccount_Model_Giftcardaccount
     */
    protected function _defineCode()
    {
        return $this->setCode($this->getPoolModel()->setExcludedIds(self::$_alreadySelectedIds)->shift());
    }


    /**
     * Load gift card account model using specified code
     *
     * @param string $code
     * @return Enterprise_GiftCardAccount_Model_Giftcardaccount
     */
    public function loadByCode($code)
    {
        $this->_requestedCode = $code;

        return $this->load($code, 'code');
    }


    /**
     * Add gift card to quote gift card storage
     *
     * @param bool $saveQuote
     * @return Enterprise_GiftCardAccount_Model_Giftcardaccount
     */
    public function addToCart($saveQuote = true, $quote = null)
    {
        if (is_null($quote)) {
            $quote = $this->_getCheckoutSession()->getQuote();
        }
        $website = Mage::app()->getStore($quote->getStoreId())->getWebsite();
        if ($this->isValid(true, true, $website)) {
            $cards = Mage::helper('enterprise_giftcardaccount')->getCards($quote);
            if (!$cards) {
                $cards = array();
            } else {
                foreach ($cards as $one) {
                    if ($one['i'] == $this->getId()) {
                        Mage::throwException(Mage::helper('enterprise_giftcardaccount')->__('This gift card account is already in the quote.'));
                    }
                }
            }
            $cards[] = array(
                'i'=>$this->getId(),        // id
                'c'=>$this->getCode(),      // code
                'a'=>$this->getBalance(),   // amount
                'ba'=>$this->getBalance(),  // base amount
            );
            Mage::helper('enterprise_giftcardaccount')->setCards($quote, $cards);

            if ($saveQuote) {
                $quote->save();
            }
        }

        return $this;
    }

    /**
     * Remove gift card from quote gift card storage
     *
     * @param bool $saveQuote
     * @return Enterprise_GiftCardAccount_Model_Giftcardaccount
     */
    public function removeFromCart($saveQuote = true, $quote = null)
    {
        if (!$this->getId()) {
            $this->_throwException(Mage::helper('enterprise_giftcardaccount')->__('Wrong gift card account code: "%s".', $this->_requestedCode));
        }
        if (is_null($quote)) {
            $quote = $this->_getCheckoutSession()->getQuote();
        }

        $cards = Mage::helper('enterprise_giftcardaccount')->getCards($quote);
        if ($cards) {
            foreach ($cards as $k=>$one) {
                if ($one['i'] == $this->getId()) {
                    unset($cards[$k]);
                    Mage::helper('enterprise_giftcardaccount')->setCards($quote, $cards);

                    if ($saveQuote) {
                        $quote->save();
                    }
                    return $this;
                }
            }
        }

        $this->_throwException(Mage::helper('enterprise_giftcardaccount')->__('This gift card account wasn\'t found in the quote.'));
    }

    /**
     * Return checkout/session model singleton
     *
     * @return Mage_Checkout_Model_Session
     */
    protected function _getCheckoutSession()
    {
        return Mage::getSingleton('checkout/session');
    }

    /**
     * Check if this gift card is expired at the moment
     *
     * @return bool
     */
    public function isExpired()
    {
        if (!$this->getDateExpires()) {
            return false;
        }

        $currentDate = strtotime(Mage::getModel('core/date')->date('Y-m-d'));

        if (strtotime($this->getDateExpires()) < $currentDate) {
            return true;
        }
        return false;
    }


    /**
     * Check all the gift card validity attributes
     *
     * @param bool $expirationCheck
     * @param bool $statusCheck
     * @param mixed $websiteCheck
     * @param mixed $balanceCheck
     * @return bool
     */
    public function isValid($expirationCheck = true, $statusCheck = true, $websiteCheck = false, $balanceCheck = true)
    {
        if (!$this->getId()) {
            $this->_throwException(
                Mage::helper('enterprise_giftcardaccount')->__('Wrong gift card account ID. Requested code: "%s"', $this->_requestedCode)
            );
        }

        if ($websiteCheck) {
            if ($websiteCheck === true) {
                $websiteCheck = null;
            }
            $website = Mage::app()->getWebsite($websiteCheck)->getId();
            if ($this->getWebsiteId() != $website) {
                $this->_throwException(
                    Mage::helper('enterprise_giftcardaccount')->__('Wrong gift card account website: %s.', $this->getWebsiteId())
                );
            }
        }

        if ($statusCheck && ($this->getStatus() != self::STATUS_ENABLED)) {
            $this->_throwException(
                Mage::helper('enterprise_giftcardaccount')->__('Gift card account %s is not enabled.', $this->getId())
            );
        }

        if ($expirationCheck && $this->isExpired()) {
            $this->_throwException(
                Mage::helper('enterprise_giftcardaccount')->__('Gift card account %s is expired.', $this->getId())
            );
        }

        if ($balanceCheck) {
            if ($this->getBalance() <= 0) {
                $this->_throwException(
                    Mage::helper('enterprise_giftcardaccount')->__('Gift card account %s balance does not have funds.', $this->getId())
                );
            }
            if ($balanceCheck !== true && is_numeric($balanceCheck)) {
                if ($this->getBalance() < $balanceCheck) {
                    $this->_throwException(
                        Mage::helper('enterprise_giftcardaccount')->__('Gift card account %s balance is less than amount to be charged.', $this->getId())
                    );
                }
            }
        }

        return true;
    }

    /**
     * Reduce Gift Card Account balance by specified amount
     *
     * @param decimal $amount
     */
    public function charge($amount)
    {
        if ($this->isValid(false, false, false, $amount)) {
            $this->setBalanceDelta(-$amount)
                ->setBalance($this->getBalance() - $amount)
                ->setHistoryAction(Enterprise_GiftCardAccount_Model_History::ACTION_USED);
        }

        return $this;
    }

    /**
     * Revert amount to gift card balance if order was not placed
     *
     * @param   float $amount
     * @return  Enterprise_GiftCardAccount_Model_Giftcardaccount
     */
    public function revert($amount)
    {
        $amount = (float) $amount;

        if ($amount > 0 && $this->isValid(true, true, false, false)) {
            $this->setBalanceDelta($amount)
                ->setBalance($this->getBalance() + $amount)
                ->setHistoryAction(Enterprise_GiftCardAccount_Model_History::ACTION_UPDATED);
        }

        return $this;
    }

    /**
     * Return Gift Card Account state as user-friendly label
     *
     * @deprecated after 1.3.2.3 use magic method instead
     * @return string
     */
    public function getStateText()
    {
        return $this->_setStateText();
    }

    /**
     * Set state text on after load
     *
     * @return Enterprise_GiftCardAccount_Model_Giftcardaccount
     */
    public function _afterLoad()
    {
        $this->_setStateText();
        return parent::_afterLoad();
    }

    /**
     * Return Gift Card Account state options
     *
     * @return array
     */
    public function getStatesAsOptionList()
    {
        $result = array();

        $result[self::STATE_AVAILABLE] = Mage::helper('enterprise_giftcardaccount')->__('Available');
        $result[self::STATE_USED]      = Mage::helper('enterprise_giftcardaccount')->__('Used');
        $result[self::STATE_REDEEMED]  = Mage::helper('enterprise_giftcardaccount')->__('Redeemed');
        $result[self::STATE_EXPIRED]   = Mage::helper('enterprise_giftcardaccount')->__('Expired');

        return $result;
    }

    /**
     * Return code pool model class name
     *
     * @return string
     */
    public function getPoolModelClass()
    {
        if (!$this->hasPoolModelClass()) {
            $this->setPoolModelClass($this->_defaultPoolModelClass);
        }
        return $this->getData('pool_model_class');
    }

    /**
     * Retreive pool model instance
     *
     * @return Enterprise_GiftCardAccount_Model_Pool_Abstract
     */
    public function getPoolModel()
    {
        return Mage::getModel($this->getPoolModelClass());
    }

    /**
     * Update gift card accounts state
     *
     * @param array $ids
     * @param int $state
     * @return Enterprise_GiftCardAccount_Model_Giftcardaccount
     */
    public function updateState($ids, $state)
    {
        if ($ids) {
            $this->getResource()->updateState($ids, $state);
        }
        return $this;
    }

    /**
     * Redeem gift card (-gca balance, +cb balance)
     *
     * @return Enterprise_GiftCardAccount_Model_Giftcardaccount
     */
    public function redeem($customerId = null)
    {
        if ($this->isValid(true, true, true, true)) {
            if ($this->getIsRedeemable() != self::REDEEMABLE) {
                $this->_throwException(sprintf('Gift card account %s is not redeemable.', $this->getId()));
            }
            if (is_null($customerId)) {
                $customerId = Mage::getSingleton('customer/session')->getCustomerId();
            }
            if (!$customerId) {
                Mage::throwException(Mage::helper('enterprise_giftcardaccount')->__('Invalid customer ID supplied.'));
            }

            $additionalInfo = Mage::helper('enterprise_giftcardaccount')
                ->__('Gift Card Redeemed: %s. For customer #%s.', $this->getCode(), $customerId);

            $balance = Mage::getModel('enterprise_customerbalance/balance')
                ->setCustomerId($customerId)
                ->setWebsiteId(Mage::app()->getWebsite()->getId())
                ->setAmountDelta($this->getBalance())
                ->setNotifyByEmail(false)
                ->setUpdatedActionAdditionalInfo($additionalInfo)
                ->save();

            $this->setBalanceDelta(-$this->getBalance())
                ->setHistoryAction(Enterprise_GiftCardAccount_Model_History::ACTION_REDEEMED)
                ->setBalance(0)
                ->setCustomerId($customerId)
                ->save();
        }

        return $this;
    }

    public function sendEmail()
    {
        $recipientName = $this->getRecipientName();
        $recipientEmail = $this->getRecipientEmail();
        $recipientStore = $this->getRecipientStore();
        if (is_null($recipientStore)) {
            $recipientStore = Mage::app()->getWebsite($this->getWebsiteId())->getDefaultStore();
        } else {
            $recipientStore = Mage::app()->getStore($recipientStore);
        }

        $storeId = $recipientStore->getId();

        $balance = $this->getBalance();
        $code = $this->getCode();

        $balance = Mage::app()->getLocale()->currency($recipientStore->getBaseCurrencyCode())->toCurrency($balance);

        $email = Mage::getModel('core/email_template')->setDesignConfig(array('store' => $storeId));
        $email->sendTransactional(
            Mage::getStoreConfig('giftcard/giftcardaccount_email/template', $storeId),
            Mage::getStoreConfig('giftcard/giftcardaccount_email/identity', $storeId),
            $recipientEmail,
            $recipientName,
            array(
                'name'          => $recipientName,
                'code'          => $code,
                'balance'       => $balance,
                'store'         => $recipientStore,
                'store_name'    => $recipientStore->getName()  // @deprecated after 1.4.0.0-beta1
            )
        );

        $this->setEmailSent(false);
        if ($email->getSentSuccess()) {
            $this->setEmailSent(true)
                ->setHistoryAction(Enterprise_GiftCardAccount_Model_History::ACTION_SENT)
                ->save();
        }
    }

    /**
     * Set state text by loaded state code
     * Used in _afterLoad() and old getStateText()
     *
     * @return string
     */
    protected function _setStateText()
    {
        $states = $this->getStatesAsOptionList();

        if (isset($states[$this->getState()])) {
            $stateText = $states[$this->getState()];
            $this->setStateText($stateText);
            return $stateText;
        }
        return '';
    }

    /**
     * Obscure real exception message to prevent brute force attacks
     *
     * @throws Mage_Core_Exception
     * @param string $realMessage
     * @param string $fakeMessage
     */
    protected function _throwException($realMessage, $fakeMessage = '')
    {
        $e = Mage::exception('Mage_Core', $realMessage);
        Mage::logException($e);
        if (!$fakeMessage) {
            $fakeMessage = Mage::helper('enterprise_giftcardaccount')->__('Wrong gift card code.');
        }
        $e->setMessage($fakeMessage);
        throw $e;
    }
}
