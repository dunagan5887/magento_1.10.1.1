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
 * @package     Enterprise_GiftWrapping
 * @copyright   Copyright (c) 2010 Magento Inc. (http://www.magentocommerce.com)
 * @license     http://www.magentocommerce.com/license/enterprise-edition
 */


/**
 * GiftWrapping total calculator for creditmemo
 *
 */
class Enterprise_GiftWrapping_Model_Total_Creditmemo_Giftwrapping extends Mage_Sales_Model_Order_Creditmemo_Total_Abstract
{
    /**
     * Collect gift wrapping totals
     *
     * @param   Mage_Sales_Model_Order_Creditmemo $creditmemo
     * @return  Enterprise_GiftWrapping_Model_Total_Creditmemo_Giftwrapping
     */
    public function collect(Mage_Sales_Model_Order_Creditmemo $creditmemo)
    {
        $order = $creditmemo->getOrder();

        /**
         * Wrapping for items
         */
        $refunded = 0;
        $baseRefunded = 0;
        foreach ($creditmemo->getAllItems() as $creditmemoItem) {
            if (!$creditmemoItem->getQty() || $creditmemoItem->getQty() == 0) {
                continue;
            }
            $orderItem = $creditmemoItem->getOrderItem();
            if ($orderItem->getGwId() && $orderItem->getGwBasePriceInvoiced()
                && $orderItem->getGwBasePriceInvoiced() != $orderItem->getGwBasePriceRefunded()) {
                $orderItem->setGwBasePriceRefunded($orderItem->getGwBasePriceInvoiced());
                $orderItem->setGwPriceRefunded($orderItem->getGwPriceInvoiced());
                $baseRefunded += $orderItem->getGwBasePriceInvoiced();
                $refunded += $orderItem->getGwPriceInvoiced();
            }
        }
        if ($refunded > 0 || $baseRefunded > 0) {
            $order->setGwItemsBasePriceRefunded($order->getGwItemsBasePriceRefunded() + $baseRefunded);
            $order->setGwItemsPriceRefunded($order->getGwItemsPriceRefunded() + $refunded);
            $creditmemo->setGwItemsBasePrice($baseRefunded);
            $creditmemo->setGwItemsPrice($refunded);
        }

        /**
         * Wrapping for order
         */
        if ($order->getGwId() && $order->getGwBasePriceInvoiced()
            && $order->getGwBasePriceInvoiced() != $order->getGwBasePriceRefunded()) {
            $order->setGwBasePriceRefunded($order->getGwBasePriceInvoiced());
            $order->setGwPriceRefunded($order->getGwPriceInvoiced());
            $creditmemo->setGwBasePrice($order->getGwBasePriceInvoiced());
            $creditmemo->setGwPrice($order->getGwPriceInvoiced());
        }

        /**
         * Printed card
         */
        if ($order->getGwAddPrintedCard() && $order->getGwPrintedCardBasePriceInvoiced()
            && $order->getGwPrintedCardBasePriceInvoiced() != $order->getGwPrintedCardBasePriceRefunded()) {
            $order->setGwPrintedCardBasePriceRefunded($order->getGwPrintedCardBasePriceInvoiced());
            $order->setGwPrintedCardPriceRefunded($order->getGwPrintedCardPriceInvoiced());
            $creditmemo->setGwPrintedCardBasePrice($order->getGwPrintedCardBasePriceInvoiced());
            $creditmemo->setGwPrintedCardPrice($order->getGwPrintedCardPriceInvoiced());
        }

        $creditmemo->setBaseGrandTotal(
            $creditmemo->getBaseGrandTotal()
            + $creditmemo->getGwItemsBasePrice()
            + $creditmemo->getGwBasePrice()
            + $creditmemo->getGwPrintedCardBasePrice()
        );
        $creditmemo->setGrandTotal(
            $creditmemo->getGrandTotal()
            + $creditmemo->getGwItemsPrice()
            + $creditmemo->getGwPrice()
            + $creditmemo->getGwPrintedCardPrice()
        );

        $creditmemo->setBaseCustomerBalanceReturnMax(
            $creditmemo->getBaseCustomerBalanceReturnMax()
            + $creditmemo->getGwPrintedCardBasePrice()
            + $creditmemo->getGwBasePrice()
            + $creditmemo->getGwItemsBasePrice()
        );
        $creditmemo->setCustomerBalanceReturnMax(
            $creditmemo->getCustomerBalanceReturnMax()
            + $creditmemo->getGwPrintedCardPrice()
            + $creditmemo->getGwPrice()
            + $creditmemo->getGwItemsPrice()
        );

        return $this;
    }
}
