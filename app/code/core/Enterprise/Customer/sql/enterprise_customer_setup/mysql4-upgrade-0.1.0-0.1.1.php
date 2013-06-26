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
 * @package     Enterprise_Customer
 * @copyright   Copyright (c) 2010 Magento Inc. (http://www.magentocommerce.com)
 * @license     http://www.magentocommerce.com/license/enterprise-edition
 */

/**
 *
 */

$installer = $this;

/* @var $installer Mage_Eav_Model_Entity_Setup */
$installer->startSetup();

$installer->run("
-- DROP TABLE IF EXISTS `{$installer->getTable('enterprise_customer/sales_order')}`;
CREATE TABLE `{$installer->getTable('enterprise_customer/sales_order')}` (
  `entity_id` int(10) unsigned NOT NULL default '0',
  PRIMARY KEY  (`entity_id`),
  CONSTRAINT `FK_ENTERPRISE_CUSTOMER_SALES_ORDER` FOREIGN KEY (`entity_id`) REFERENCES `{$installer->getTable('sales/order')}` (`entity_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- DROP TABLE IF EXISTS `{$installer->getTable('enterprise_customer/sales_order_address')}`;
CREATE TABLE `{$installer->getTable('enterprise_customer/sales_order_address')}` (
  `entity_id` int(10) unsigned NOT NULL default '0',
  PRIMARY KEY  (`entity_id`),
  CONSTRAINT `FK_ENTERPRISE_CUSTOMER_SALES_ORDER_ADDRESS` FOREIGN KEY (`entity_id`) REFERENCES `{$installer->getTable('sales/order_address')}` (`entity_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- DROP TABLE IF EXISTS `{$installer->getTable('enterprise_customer/sales_quote')}`;
CREATE TABLE `{$installer->getTable('enterprise_customer/sales_quote')}` (
  `entity_id` int(10) unsigned NOT NULL default '0',
  PRIMARY KEY  (`entity_id`),
  CONSTRAINT `FK_ENTERPRISE_CUSTOMER_SALES_QUOTE` FOREIGN KEY (`entity_id`) REFERENCES `{$installer->getTable('sales/quote')}` (`entity_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- DROP TABLE IF EXISTS `{$installer->getTable('enterprise_customer/sales_quote_address')}`;
CREATE TABLE `{$installer->getTable('enterprise_customer/sales_quote_address')}` (
  `entity_id` int(10) unsigned NOT NULL default '0',
  PRIMARY KEY  (`entity_id`),
  CONSTRAINT `FK_ENTERPRISE_CUSTOMER_SALES_QUOTE_ADDRESS` FOREIGN KEY (`entity_id`) REFERENCES `{$installer->getTable('sales/quote_address')}` (`address_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

");

$installer->endSetup();
