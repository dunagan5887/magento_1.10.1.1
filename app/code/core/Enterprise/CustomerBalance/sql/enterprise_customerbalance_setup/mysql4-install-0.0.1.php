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
 * @package     Enterprise_CustomerBalance
 * @copyright   Copyright (c) 2010 Magento Inc. (http://www.magentocommerce.com)
 * @license     http://www.magentocommerce.com/license/enterprise-edition
 */

$this->startSetup();

$this->run("
CREATE TABLE {$this->getTable('enterprise_customerbalance')} (
    `primary_id` INT NOT NULL AUTO_INCREMENT ,
    `customer_id` INT( 10 ) UNSIGNED NOT NULL ,
    `website_id` SMALLINT( 5 ) NOT NULL ,
    `balance` DECIMAL( 12, 4 ) NOT NULL ,
    PRIMARY KEY ( `primary_id` ),
    KEY `FK_CUSTOMERBALANCE_CUSTOMER_ENTITY` (`customer_id`),
    CONSTRAINT `FK_CUSTOMERBALANCE_CUSTOMER_ENTITY` FOREIGN KEY (customer_id) REFERENCES `{$this->getTable('customer_entity')}` (`entity_id`) ON DELETE CASCADE ON UPDATE CASCADE
    ) ENGINE = InnoDB
");

$this->endSetup();
