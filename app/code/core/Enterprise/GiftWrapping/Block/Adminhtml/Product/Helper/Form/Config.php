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
 * Adminhtml additional helper block for product configuration
 *
 * @category   Enterprise
 * @package    Enterprise_GiftWrapping
 */
class Enterprise_GiftWrapping_Block_Adminhtml_Product_Helper_Form_Config
    extends Mage_Adminhtml_Block_Catalog_Product_Helper_Form_Config
{
    /**
     * Get config value data
     *
     * @return mixed
     */
    protected function _getValueFromConfig()
    {
        return Mage::helper('enterprise_giftwrapping')->isGiftWrappingAvailableForItems();
    }
}
