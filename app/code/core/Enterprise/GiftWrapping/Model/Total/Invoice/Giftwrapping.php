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
 * GiftWrapping total calculator for invoice
 *
 */
class Enterprise_GiftWrapping_Model_Total_Invoice_Giftwrapping extends Mage_Sales_Model_Order_Invoice_Total_Abstract
{
    /**
     * Collect gift wrapping totals
     *
     * @param Mage_Sales_Model_Order_Invoice $invoice
     * @return Enterprise_GiftWrapping_Model_Total_Invoice_Giftwrapping
     */
    public function collect(Mage_Sales_Model_Order_Invoice $invoice)
    {
        $order = $invoice->getOrder();

        /**
         * Wrapping for items
         */
        $invoiced = 0;
        $baseInvoiced = 0;
        foreach ($invoice->getAllItems() as $invoiceItem) {
            if (!$invoiceItem->getQty() || $invoiceItem->getQty() == 0) {
                continue;
            }   
            $orderItem = $invoiceItem->getOrderItem();
            if ($orderItem->getGwId() && $orderItem->getGwBasePrice()
                && $orderItem->getGwBasePrice() != $orderItem->getGwBasePriceInvoiced()) {
                $orderItem->setGwBasePriceInvoiced($orderItem->getGwBasePrice());
                $orderItem->setGwPriceInvoiced($orderItem->getGwPrice());
                $baseInvoiced += $orderItem->getGwBasePrice();
                $invoiced += $orderItem->getGwPrice();
            }
        }
        if ($invoiced > 0 || $baseInvoiced > 0) {
            $order->setGwItemsBasePriceInvoiced($order->getGwItemsBasePriceInvoiced() + $baseInvoiced);
            $order->setGwItemsPriceInvoiced($order->getGwItemsPriceInvoiced() + $invoiced);
            $invoice->setGwItemsBasePrice($baseInvoiced);
            $invoice->setGwItemsPrice($invoiced);
        }

        /**
         * Wrapping for order
         */
        if ($order->getGwId() && $order->getGwBasePrice()
            && $order->getGwBasePrice() != $order->getGwBasePriceInvoiced()) {
            $order->setGwBasePriceInvoiced($order->getGwBasePrice());
            $order->setGwPriceInvoiced($order->getGwPrice());
            $invoice->setGwBasePrice($order->getGwBasePrice());
            $invoice->setGwPrice($order->getGwPrice());
        }

        /**
         * Printed card
         */
        if ($order->getGwAddPrintedCard() && $order->getGwPrintedCardBasePrice()
            && $order->getGwPrintedCardBasePrice() != $order->getGwPrintedCardBasePriceInvoiced()) {
            $order->setGwPrintedCardBasePriceInvoiced($order->getGwPrintedCardBasePrice());
            $order->setGwPrintedCardPriceInvoiced($order->getGwPrintedCardPrice());
            $invoice->setGwPrintedCardBasePrice($order->getGwPrintedCardBasePrice());
            $invoice->setGwPrintedCardPrice($order->getGwPrintedCardPrice());
        }

        $invoice->setBaseGrandTotal(
            $invoice->getBaseGrandTotal()
            + $invoice->getGwItemsBasePrice()
            + $invoice->getGwBasePrice()
            + $invoice->getGwPrintedCardBasePrice()
        );
        $invoice->setGrandTotal(
            $invoice->getGrandTotal()
            + $invoice->getGwItemsPrice()
            + $invoice->getGwPrice()
            + $invoice->getGwPrintedCardPrice()
        );
        return $this;
    }
}
