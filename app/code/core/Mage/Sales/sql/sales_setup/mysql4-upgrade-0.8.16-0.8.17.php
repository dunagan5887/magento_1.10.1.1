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
 * @package     Mage_Sales
 * @copyright   Copyright (c) 2010 Magento Inc. (http://www.magentocommerce.com)
 * @license     http://www.magentocommerce.com/license/enterprise-edition
 */

$installer = $this;
$this->startSetup();
$this->run("
ALTER TABLE `{$installer->getTable('sales_quote_item')}`
    MODIFY COLUMN `weight` DECIMAL(12,4) DEFAULT '0.0000',
    MODIFY COLUMN `discount_percent` DECIMAL(12,4) DEFAULT '0.0000',
    MODIFY COLUMN `discount_amount` DECIMAL(12,4) DEFAULT '0.0000',
    MODIFY COLUMN `tax_percent` DECIMAL(12,4) DEFAULT '0.0000',
    MODIFY COLUMN `tax_amount` DECIMAL(12,4) DEFAULT '0.0000',
    MODIFY COLUMN `row_total_with_discount` DECIMAL(12,4) DEFAULT '0.0000',
    MODIFY COLUMN `base_discount_amount` DECIMAL(12,4) DEFAULT '0.0000',
    MODIFY COLUMN `base_tax_amount` DECIMAL(12,4) DEFAULT '0.0000',
    MODIFY COLUMN `row_weight` DECIMAL(12,4) DEFAULT '0.0000';
");
$this->endSetup();
$this->installEntities();
