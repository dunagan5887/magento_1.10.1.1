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
 * @category    Mage
 * @package     Mage_Tax
 * @copyright   Copyright (c) 2010 Magento Inc. (http://www.magentocommerce.com)
 * @license     http://www.magentocommerce.com/license/enterprise-edition
 */

class Mage_Tax_Model_Sales_Pdf_Tax extends Mage_Sales_Model_Order_Pdf_Total_Default
{
    /**
     * Check if tax amount should be included to grandtotal block
     * array(
     *  $index => array(
     *      'amount'   => $amount,
     *      'label'    => $label,
     *      'font_size'=> $font_size
     *  )
     * )
     * @return array
     */
    public function getTotalsForDisplay()
    {
        $store = $this->getOrder()->getStore();
        $config= Mage::getSingleton('tax/config');
        if ($config->displaySalesTaxWithGrandTotal($store)) {
            return array();
        }

        $fontSize = $this->getFontSize() ? $this->getFontSize() : 7;
        $totals = array();

        if ($config->displaySalesFullSummary($store)) {
           $totals = $this->getFullTaxInfo();
        }

        $totals = array_merge($totals, parent::getTotalsForDisplay());

        return $totals;
    }


}
