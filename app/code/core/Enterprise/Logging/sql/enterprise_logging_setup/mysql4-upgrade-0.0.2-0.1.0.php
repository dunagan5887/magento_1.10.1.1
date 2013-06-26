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
 * @package     Enterprise_Logging
 * @copyright   Copyright (c) 2010 Magento Inc. (http://www.magentocommerce.com)
 * @license     http://www.magentocommerce.com/license/enterprise-edition
 */

/**
 * Resource setup - add columns to roles table:
 * is_all_permissions - yes/no flag
 * website_ids - comma-separated
 * store_group_ids - comma-separated
 */

$installer = $this;
/* @var $installer Mage_Core_Model_Resource_Setup */
$installer->startSetup();

$installer->run("DROP TABLE IF EXISTS `".$this->getTable('enterprise_logging/event')."`");
$installer->run("CREATE TABLE `".$this->getTable('enterprise_logging/event')."` (
  `log_id` int(11) NOT NULL auto_increment,
  `ip` bigint(20) unsigned NOT NULL default '0',
  `event_code` char(20) NOT NULL default '',
  `time` datetime NOT NULL default '0000-00-00 00:00:00',
  `user_id` int(11) NOT NULL default '0',
  `action` char(20) NOT NULL default '-',
  `info` varchar(255) NOT NULL default '-',
  PRIMARY KEY  (`log_id`)) ENGINE=InnoDB DEFAULT CHARSET=utf8;"
);

$installer->endSetup();
