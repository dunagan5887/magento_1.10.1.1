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
 * Gift wrapping default helper
 *
 * @category    Enterprise
 * @package     Enterprise_GiftWrapping
 * @author      Magento Core Team <core@magentocommerce.com>
 */
class Enterprise_GiftWrapping_Helper_Data extends Mage_Core_Helper_Abstract
{
    /**
     * Gift wrapping allow section in configuration
     */
    const XML_PATH_ALLOWED_FOR_ITEMS = 'sales/gift_options/wrapping_allow_items';
    const XML_PATH_ALLOWED_FOR_ORDER = 'sales/gift_options/wrapping_allow_order';

    /**
     * Gift wrapping tax class
     */
    const XML_PATH_TAX_CLASS = 'tax/classes/wrapping_tax_class';

    /**
     * Shopping cart display settings
     */
    const XML_PATH_PRICE_DISPLAY_CART_WRAPPING        = 'tax/cart_display/gift_wrapping';
    const XML_PATH_PRICE_DISPLAY_CART_PRINTED_CARD    = 'tax/cart_display/printed_card';

    /**
     * Sales display settings
     */
    const XML_PATH_PRICE_DISPLAY_SALES_WRAPPING        = 'tax/sales_display/gift_wrapping';
    const XML_PATH_PRICE_DISPLAY_SALES_PRINTED_CARD    = 'tax/sales_display/printed_card';

    /**
     * Gift receipt and printed card settings
     */
    const XML_PATH_ALLOW_GIFT_RECEIPT = 'sales/gift_options/allow_gift_receipt';
    const XML_PATH_ALLOW_PRINTED_CARD = 'sales/gift_options/allow_printed_card';
    const XML_PATH_PRINTED_CARD_PRICE = 'sales/gift_options/printed_card_price';

    /**
     * Check availablity of gift wrapping for product
     *
     * @param int $productConfig
     * @param Mage_Core_Model_Store|int $store
     * @return bool
     */
    public function isGiftWrappingAvailableForProduct($productConfig, $store = null)
    {
        if (is_null($productConfig) || '' === $productConfig) {
            return $this->isGiftWrappingAvailableForItems($store);
        } else {
            return $productConfig;
        }
    }

    /**
     * Check availablity of gift wrapping on items level
     *
     * @param Mage_Core_Model_Store|int $store
     * @return bool
     */
    public function isGiftWrappingAvailableForItems($store = null)
    {
        return Mage::getStoreConfig(self::XML_PATH_ALLOWED_FOR_ITEMS, $store);
    }

    /**
     * Check availablity of gift wrapping on order level
     *
     * @param Mage_Core_Model_Store|int $store
     * @return bool
     */
    public function isGiftWrappingAvailableForOrder($store = null)
    {
        return Mage::getStoreConfig(self::XML_PATH_ALLOWED_FOR_ORDER, $store);
    }

    /**
     * Check ability to display both prices for printed card
     *
     * @param Mage_Core_Model_Store|int $store
     * @return bool
     */
    public function getWrappingTaxClass($store = null)
    {
        return Mage::getStoreConfig(self::XML_PATH_TAX_CLASS, $store);
    }

    /**
     * Check printed card allow
     *
     * @param Mage_Core_Model_Store|int $store
     * @return bool
     */
    public function allowPrintedCard($store = null)
    {
        return Mage::getStoreConfig(self::XML_PATH_ALLOW_PRINTED_CARD, $store);
    }

    /**
     * Check allow gift receipt
     *
     * @param Mage_Core_Model_Store|int $store
     * @return bool
     */
    public function allowGiftReceipt($store = null)
    {
        return Mage::getStoreConfig(self::XML_PATH_ALLOW_GIFT_RECEIPT, $store);
    }

    /**
     * Return printed card base price
     *
     * @param Mage_Core_Model_Store|int $store
     * @return mixed
     */
    public function getPrintedCardPrice($store = null)
    {
        return Mage::getStoreConfig(self::XML_PATH_PRINTED_CARD_PRICE, $store);
    }

    /**
     * Check ability to display prices including tax for gift wrapping in shopping cart
     *
     * @param Mage_Core_Model_Store|int $store
     * @return bool
     */
    public function displayCartWrappingIncludeTaxPrice($store = null)
    {
        $configValue = Mage::getStoreConfig(self::XML_PATH_PRICE_DISPLAY_CART_WRAPPING, $store);
        return $configValue == Mage_Tax_Model_Config::DISPLAY_TYPE_BOTH || $configValue == Mage_Tax_Model_Config::DISPLAY_TYPE_INCLUDING_TAX;
    }

    /**
     * Check ability to display prices excluding tax for gift wrapping in shopping cart
     *
     * @param Mage_Core_Model_Store|int $store
     * @return bool
     */
    public function displayCartWrappingExcludeTaxPrice($store = null)
    {
        $configValue = Mage::getStoreConfig(self::XML_PATH_PRICE_DISPLAY_CART_WRAPPING, $store);
        return $configValue == Mage_Tax_Model_Config::DISPLAY_TYPE_EXCLUDING_TAX;
    }

    /**
     * Check ability to display both prices for gift wrapping in shopping cart
     *
     * @param Mage_Core_Model_Store|int $store
     * @return bool
     */
    public function displayCartWrappingBothPrices($store = null)
    {
        $configValue = Mage::getStoreConfig(self::XML_PATH_PRICE_DISPLAY_CART_WRAPPING, $store);
        return $configValue == Mage_Tax_Model_Config::DISPLAY_TYPE_BOTH;
    }

    /**
     * Check ability to display prices including tax for printed card in shopping cart
     *
     * @param Mage_Core_Model_Store|int $store
     * @return bool
     */
    public function displayCartCardIncludeTaxPrice($store = null)
    {
        $configValue = Mage::getStoreConfig(self::XML_PATH_PRICE_DISPLAY_CART_PRINTED_CARD, $store);
        return $configValue == Mage_Tax_Model_Config::DISPLAY_TYPE_BOTH || $configValue == Mage_Tax_Model_Config::DISPLAY_TYPE_INCLUDING_TAX;
    }

    /**
     * Check ability to display both prices for printed card in shopping cart
     *
     * @param Mage_Core_Model_Store|int $store
     * @return bool
     */
    public function displayCartCardBothPrices($store = null)
    {
        $configValue = Mage::getStoreConfig(self::XML_PATH_PRICE_DISPLAY_CART_PRINTED_CARD, $store);
        return $configValue == Mage_Tax_Model_Config::DISPLAY_TYPE_BOTH;
    }

    /**
     * Check ability to display prices including tax for gift wrapping in backend sales
     *
     * @param Mage_Core_Model_Store|int $store
     * @return bool
     */
    public function displaySalesWrappingIncludeTaxPrice($store = null)
    {
        $configValue = Mage::getStoreConfig(self::XML_PATH_PRICE_DISPLAY_SALES_WRAPPING, $store);
        return $configValue == Mage_Tax_Model_Config::DISPLAY_TYPE_BOTH || $configValue == Mage_Tax_Model_Config::DISPLAY_TYPE_INCLUDING_TAX;
    }

    /**
     * Check ability to display prices excluding tax for gift wrapping in backend sales
     *
     * @param Mage_Core_Model_Store|int $store
     * @return bool
     */
    public function displaySalesWrappingExcludeTaxPrice($store = null)
    {
        $configValue = Mage::getStoreConfig(self::XML_PATH_PRICE_DISPLAY_SALES_WRAPPING, $store);
        return $configValue == Mage_Tax_Model_Config::DISPLAY_TYPE_EXCLUDING_TAX;
    }

    /**
     * Check ability to display both prices for gift wrapping in backend sales
     *
     * @param Mage_Core_Model_Store|int $store
     * @return bool
     */
    public function displaySalesWrappingBothPrices($store = null)
    {
        $configValue = Mage::getStoreConfig(self::XML_PATH_PRICE_DISPLAY_SALES_WRAPPING, $store);
        return $configValue == Mage_Tax_Model_Config::DISPLAY_TYPE_BOTH;
    }

    /**
     * Check ability to display prices including tax for printed card in backend sales
     *
     * @param Mage_Core_Model_Store|int $store
     * @return bool
     */
    public function displaySalesCardIncludeTaxPrice($store = null)
    {
        $configValue = Mage::getStoreConfig(self::XML_PATH_PRICE_DISPLAY_SALES_PRINTED_CARD, $store);
        return $configValue == Mage_Tax_Model_Config::DISPLAY_TYPE_BOTH || $configValue == Mage_Tax_Model_Config::DISPLAY_TYPE_INCLUDING_TAX;
    }

    /**
     * Check ability to display both prices for printed card in backend sales
     *
     * @param Mage_Core_Model_Store|int $store
     * @return bool
     */
    public function displaySalesCardBothPrices($store = null)
    {
        $configValue = Mage::getStoreConfig(self::XML_PATH_PRICE_DISPLAY_SALES_PRINTED_CARD, $store);
        return $configValue == Mage_Tax_Model_Config::DISPLAY_TYPE_BOTH;
    }

    /**
     * Return totals of data object
     *
     * @param  Varien_Object $dataObject
     * @return array
     */
    public function getTotals($dataObject)
    {
        $totals = array();

        $displayWrappingBothPrices = false;
        $displayWrappingIncludeTaxPrice = false;
        $displayCardBothPrices = false;
        $displayCardIncludeTaxPrice = false;

        if ($dataObject instanceof Mage_Sales_Model_Order
            || $dataObject instanceof Mage_Sales_Model_Order_Invoice
            || $dataObject instanceof Mage_Sales_Model_Order_Creditmemo) {
            $displayWrappingBothPrices = $this->displaySalesWrappingBothPrices();
            $displayWrappingIncludeTaxPrice = $this->displaySalesWrappingIncludeTaxPrice();
            $displayCardBothPrices = $this->displaySalesCardBothPrices();
            $displayCardIncludeTaxPrice = $this->displaySalesCardIncludeTaxPrice();
        } elseif ($dataObject instanceof Mage_Sales_Model_Quote_Address_Total) {
            $displayWrappingBothPrices = $this->displayCartWrappingBothPrices();
            $displayWrappingIncludeTaxPrice = $this->displayCartWrappingIncludeTaxPrice();
            $displayCardBothPrices = $this->displayCartCardBothPrices();
            $displayCardIncludeTaxPrice = $this->displayCartCardIncludeTaxPrice();
        }

        /**
         * Gift wrapping for order totals
         */
        if ($displayWrappingBothPrices || $displayWrappingIncludeTaxPrice) {
            $this->_addTotalToTotals(
                $totals,
                'gw_order_incl',
                $dataObject->getGwPrice() + $dataObject->getGwTaxAmount(),
                $dataObject->getGwBasePrice() + $dataObject->getGwBaseTaxAmount(),
                $this->__('Gift Wrapping for Order (Incl. Tax)')
            );
            if ($displayWrappingBothPrices) {
                $this->_addTotalToTotals(
                    $totals,
                    'gw_order_excl',
                    $dataObject->getGwPrice(),
                    $dataObject->getGwBasePrice(),
                    $this->__('Gift Wrapping for Order (Excl. Tax)')
                );
            }
        } else {
            $this->_addTotalToTotals(
                $totals,
                'gw_order',
                $dataObject->getGwPrice(),
                $dataObject->getGwBasePrice(),
                $this->__('Gift Wrapping for Order')
            );
        }

        /**
         * Gift wrapping for items totals
         */
        if ($displayWrappingBothPrices || $displayWrappingIncludeTaxPrice) {
            $this->_addTotalToTotals(
                $totals,
                'gw_items_incl',
                $dataObject->getGwItemsPrice() + $dataObject->getGwItemsTaxAmount(),
                $dataObject->getGwItemsBasePrice() + $dataObject->getGwItemsBaseTaxAmount(),
                $this->__('Gift Wrapping for Items (Incl. Tax)')
            );
            if ($displayWrappingBothPrices) {
                $this->_addTotalToTotals(
                    $totals,
                    'gw_items_excl',
                    $dataObject->getGwItemsPrice(),
                    $dataObject->getGwItemsBasePrice(),
                    $this->__('Gift Wrapping for Items (Excl. Tax)')
                );
            }
        } else {
            $this->_addTotalToTotals(
                $totals,
                'gw_items',
                $dataObject->getGwItemsPrice(),
                $dataObject->getGwItemsBasePrice(),
                $this->__('Gift Wrapping for Items')
            );
        }

        /**
         * Printed card totals
         */
        if ($displayCardBothPrices || $displayCardIncludeTaxPrice) {
            $this->_addTotalToTotals(
                $totals,
                'gw_printed_card_incl',
                $dataObject->getGwPrintedCardPrice() + $dataObject->getGwPrintedCardTaxAmount(),
                $dataObject->getGwPrintedCardBasePrice() + $dataObject->getGwPrintedCardBaseTaxAmount(),
                $this->__('Printed Card (Incl. Tax)')
            );
            if ($displayCardBothPrices) {
                $this->_addTotalToTotals(
                    $totals,
                    'gw_printed_card_excl',
                    $dataObject->getGwPrintedCardPrice(),
                    $dataObject->getGwPrintedCardBasePrice(),
                    $this->__('Printed Card (Excl. Tax)')
                );
            }
        } else {
            $this->_addTotalToTotals(
                $totals,
                'gw_printed_card',
                $dataObject->getGwPrintedCardPrice(),
                $dataObject->getGwPrintedCardBasePrice(),
                $this->__('Printed Card')
            );
        }

        return $totals;
    }

    /**
     * Add total into array totals
     *
     * @param  array $totals
     * @param  string $code
     * @param  decimal $value
     * @param  decimal $baseValue
     * @param  string $label
     */
    protected function _addTotalToTotals(&$totals, $code, $value, $baseValue, $label)
    {
        if ($value == 0 && $baseValue == 0) {
            return;
        }
        $total = array(
            'code'      => $code,
            'value'     => $value,
            'base_value'=> $baseValue,
            'label'     => $label
        );
        $totals[] = $total;
    }

    /**
     * Get gift wrapping items price with tax processing
     *
     * @param  Varien_Object $item
     * @param  float $price
     * @param  bool $includingTax
     * @param  null|Mage_Customer_Model_Address $shippingAddress
     * @param  null|Mage_Customer_Model_Address $billingAddress
     * @param  null|int $ctc
     * @param  mixed $store
     * @return float
     */
    public function getPrice($item, $price, $includeTax = false, $shippingAddress = null, $billingAddress = null, $ctc = null, $store = null)
    {
       if (!$price) {
            return $price;
        }
        $store = Mage::app()->getStore($store);
        $taxClassId = $item->getTaxClassId();
        if ($taxClassId && $includeTax) {
            $request = Mage::getSingleton('tax/calculation')->getRateRequest($shippingAddress, $billingAddress, $ctc, $store);
            $percent = Mage::getSingleton('tax/calculation')->getRate($request->setProductClassId($taxClassId));
            if ($percent) {
                $price = $price * (1 + ($percent / 100));
            }
        }
        return $store->roundPrice($price);
    }
}
