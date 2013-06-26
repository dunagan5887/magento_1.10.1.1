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
 * GiftWrapping total tax calculator for creditmemo
 *
 */
class Enterprise_GiftWrapping_Model_Total_Creditmemo_Tax_Giftwrapping extends Mage_Sales_Model_Order_Creditmemo_Total_Abstract
{
    /**
     * Collect gift wrapping tax totals
     *
     * @param   Mage_Sales_Model_Order_Creditmemo $creditmemo
     * @return  Enterprise_GiftWrapping_Model_Total_Creditmemo_Tax_Giftwrapping
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
            if ($orderItem->getGwId() && $orderItem->getGwBaseTaxAmountInvoiced()
                && $orderItem->getGwBaseTaxAmountInvoiced() != $orderItem->getGwBaseTaxAmountRefunded()) {
                $orderItem->setGwBaseTaxAmountRefunded($orderItem->getGwBaseTaxAmountInvoiced());
                $orderItem->setGwTaxAmountRefunded($orderItem->getGwTaxAmountInvoiced());
                $baseRefunded += $orderItem->getGwBaseTaxAmountInvoiced();
                $refunded += $orderItem->getGwTaxAmountInvoiced();
            }
        }
        if ($refunded > 0 || $baseRefunded > 0) {
            $order->setGwItemsBaseTaxAmountRefunded($order->getGwItemsBaseTaxAmountRefunded() + $baseRefunded);
            $order->setGwItemsTaxAmountRefunded($order->getGwItemsTaxAmountRefunded() + $refunded);
            $creditmemo->setGwItemsBaseTaxAmount($baseRefunded);
            $creditmemo->setGwItemsTaxAmount($refunded);
        }

        /**
         * Wrapping for order
         */
        if ($order->getGwId() && $order->getGwBaseTaxAmountInvoiced()
            && $order->getGwBaseTaxAmountInvoiced() != $order->getGwBaseTaxAmountRefunded()) {
            $order->setGwBaseTaxAmountRefunded($order->getGwBaseTaxAmountInvoiced());
            $order->setGwTaxAmountRefunded($order->getGwTaxAmountInvoiced());
            $creditmemo->setGwBaseTaxAmount($order->getGwBaseTaxAmountInvoiced());
            $creditmemo->setGwTaxAmount($order->getGwTaxAmountInvoiced());
        }

        /**
         * Printed card
         */
        if ($order->getGwAddPrintedCard() && $order->getGwPrintedCardBaseTaxAmountInvoiced()
            && $order->getGwPrintedCardBaseTaxAmountInvoiced() != $order->getGwPrintedCardBaseTaxAmountRefunded()) {
            $order->setGwPrintedCardBaseTaxAmountRefunded($order->getGwPrintedCardBaseTaxAmountInvoiced());
            $order->setGwPrintedCardTaxAmountRefunded($order->getGwPrintedCardTaxAmountInvoiced());
            $creditmemo->setGwPrintedCardBaseTaxAmount($order->getGwPrintedCardBaseTaxAmountInvoiced());
            $creditmemo->setGwPrintedCardTaxAmount($order->getGwPrintedCardTaxAmountInvoiced());
        }

        $baseTaxAmount = $creditmemo->getGwItemsBaseTaxAmount()
            + $creditmemo->getGwBaseTaxAmount()
            + $creditmemo->getGwPrintedCardBaseTaxAmount();
        $taxAmount = $creditmemo->getGwItemsTaxAmount()
            + $creditmemo->getGwTaxAmount()
            + $creditmemo->getGwPrintedCardTaxAmount();
        $creditmemo->setBaseTaxAmount($creditmemo->getBaseTaxAmount() + $baseTaxAmount);
        $creditmemo->setTaxAmount($creditmemo->getTaxAmount() + $taxAmount);
        $creditmemo->setBaseGrandTotal($creditmemo->getBaseGrandTotal() + $baseTaxAmount);
        $creditmemo->setGrandTotal($creditmemo->getGrandTotal() + $taxAmount);

        $creditmemo->setBaseCustomerBalanceReturnMax($creditmemo->getBaseCustomerBalanceReturnMax() + $baseTaxAmount);
        $creditmemo->setCustomerBalanceReturnMax($creditmemo->getCustomerBalanceReturnMax() + $taxAmount);

        return $this;
    }
}
