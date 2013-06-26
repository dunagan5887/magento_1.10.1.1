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
 * GiftWrapping total tax calculator for invoice
 *
 */
class Enterprise_GiftWrapping_Model_Total_Invoice_Tax_Giftwrapping extends Mage_Sales_Model_Order_Invoice_Total_Abstract
{
    /**
     * Collect gift wrapping tax totals
     *
     * @param Mage_Sales_Model_Order_Invoice $invoice
     * @return Enterprise_GiftWrapping_Model_Total_Invoice_Tax_Giftwrapping
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
            if ($orderItem->getGwId() && $orderItem->getGwBaseTaxAmount()
                && $orderItem->getGwBaseTaxAmount() != $orderItem->getGwBaseTaxAmountInvoiced()) {
                $orderItem->setGwBaseTaxAmountInvoiced($orderItem->getGwBaseTaxAmount());
                $orderItem->setGwTaxAmountInvoiced($orderItem->getGwTaxAmount());
                $baseInvoiced += $orderItem->getGwBaseTaxAmount();
                $invoiced += $orderItem->getGwTaxAmount();
            }
        }
        if ($invoiced > 0 || $baseInvoiced > 0) {
            $order->setGwItemsBaseTaxAmountInvoiced($order->getGwItemsBaseTaxAmountInvoiced() + $baseInvoiced);
            $order->setGwItemsTaxAmountInvoiced($order->getGwItemsTaxAmountInvoiced() + $invoiced);
            $invoice->setGwItemsBaseTaxAmount($baseInvoiced);
            $invoice->setGwItemsTaxAmount($invoiced);
        }

        /**
         * Wrapping for order
         */
        if ($order->getGwId() && $order->getGwBaseTaxAmount()
            && $order->getGwBaseTaxAmount() != $order->getGwBaseTaxAmountInvoiced()) {
            $order->setGwBaseTaxAmountInvoiced($order->getGwBaseTaxAmount());
            $order->setGwTaxAmountInvoiced($order->getGwTaxAmount());
            $invoice->setGwBaseTaxAmount($order->getGwBaseTaxAmount());
            $invoice->setGwTaxAmount($order->getGwTaxAmount());
        }

        /**
         * Printed card
         */
        if ($order->getGwAddPrintedCard() && $order->getGwPrintedCardBaseTaxAmount()
            && $order->getGwPrintedCardBaseTaxAmount() != $order->getGwPrintedCardBaseTaxAmountInvoiced()) {
            $order->setGwPrintedCardBaseTaxAmountInvoiced($order->getGwPrintedCardBaseTaxAmount());
            $order->setGwPrintedCardTaxAmountInvoiced($order->getGwPrintedCardTaxAmount());
            $invoice->setGwPrintedCardBaseTaxAmount($order->getGwPrintedCardBaseTaxAmount());
            $invoice->setGwPrintedCardTaxAmount($order->getGwPrintedCardTaxAmount());
        }

        if (!$invoice->isLast()) {
            $baseTaxAmount = $invoice->getGwItemsBaseTaxAmount()
                + $invoice->getGwBaseTaxAmount()
                + $invoice->getGwPrintedCardBaseTaxAmount();
            $taxAmount = $invoice->getGwItemsTaxAmount()
                + $invoice->getGwTaxAmount()
                + $invoice->getGwPrintedCardTaxAmount();
            $invoice->setBaseTaxAmount($invoice->getBaseTaxAmount() + $baseTaxAmount);
            $invoice->setTaxAmount($invoice->getTaxAmount() + $taxAmount);
            $invoice->setBaseGrandTotal($invoice->getBaseGrandTotal() + $baseTaxAmount);
            $invoice->setGrandTotal($invoice->getGrandTotal() + $taxAmount);
        }

        return $this;
    }
}
