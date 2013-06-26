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
 * Checkout reward payment block
 *
 * @category    Enterprise
 * @package     Enterprise_Reward
 * @author      Magento Core Team <core@magentocommerce.com>
 */
class Enterprise_Reward_Block_Checkout_Payment_Additional extends Mage_Core_Block_Template
{
    /**
     * Getter
     *
     * @return Mage_Customer_Model_Customer
     */
    public function getCustomer()
    {
        return Mage::getSingleton('customer/session')->getCustomer();
    }

    /**
     * Getter
     *
     * @return Mage_Sales_Model_Quote
     */
    public function getQuote()
    {
        return Mage::getSingleton('checkout/session')->getQuote();
    }

    /**
     * Getter
     *
     * @return Enterprise_Reward_Model_Reward
     */
    public function getReward()
    {
        if (!$this->getData('reward')) {
            $reward = Mage::getModel('enterprise_reward/reward')
                ->setCustomer($this->getCustomer())
                ->setWebsiteId(Mage::app()->getStore()->getWebsiteId())
                ->loadByCustomer();
            $this->setData('reward', $reward);
        }
        return $this->getData('reward');
    }

    /**
     * Return flag from quote to use reward points or not
     *
     * @return boolean
     */
    public function useRewardPoints()
    {
        return (bool)$this->getQuote()->getUseRewardPoints();
    }

    /**
     * Return true if customer can use his reward points.
     * In case if currency amount of his points more then 0 and has minimum limit of points
     *
     * @return boolean
     */
    public function getCanUseRewardPoints()
    {
        if (!Mage::helper('enterprise_reward')->getHasRates()
            || !Mage::helper('enterprise_reward')->isEnabledOnFront()) {
            return false;
        }
        $minPointsToUse = Mage::helper('enterprise_reward')
            ->getGeneralConfig('min_points_balance', (int)Mage::app()->getWebsite()->getId());
        $canUseRewadPoints = ($this->getPointsBalance() >= $minPointsToUse) ? true : false;
        return (boolean)(((float)$this->getCurrencyAmount() > 0) && $canUseRewadPoints);
    }

    /**
     * Getter
     *
     * @return integer
     */
    public function getPointsBalance()
    {
        return $this->getReward()->getPointsBalance();
    }

    /**
     * Getter
     *
     * @return float
     */
    public function getCurrencyAmount()
    {
        return $this->getReward()->getCurrencyAmount();
    }

    /**
     * Check if customer has enough points to cover total
     *
     * @return boolean
     */
    public function isEnoughPoints()
    {
        $baseGrandTotal = $this->getQuote()->getBaseGrandTotal()+$this->getQuote()->getBaseRewardCurrencyAmount();
        return $this->getReward()->isEnoughPointsToCoverAmount($baseGrandTotal);
    }
}
