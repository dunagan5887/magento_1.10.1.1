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
/* @var $installer Mage_Sales_Model_Mysql4_Setup */

$installer->getConnection()->addColumn($installer->getTable('sales_order'), 'protect_code', 'VARCHAR( 6 ) NULL DEFAULT NULL');

$installer->addAttribute('order', 'protect_code', array('type'=>'static'));

$installer->run("UPDATE `{$installer->getTable('sales_order')}` SET protect_code = SUBSTRING(MD5(CONCAT(RAND(), DATE_FORMAT(NOW(), '%H %k %I %r %T %S'), RAND())), 5, 6) WHERE protect_code IS NULL");
