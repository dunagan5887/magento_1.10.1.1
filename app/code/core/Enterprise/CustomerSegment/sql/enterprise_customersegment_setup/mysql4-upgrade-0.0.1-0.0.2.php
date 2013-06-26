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
 * @package     Enterprise_CustomerSegment
 * @copyright   Copyright (c) 2010 Magento Inc. (http://www.magentocommerce.com)
 * @license     http://www.magentocommerce.com/license/enterprise-edition
 */

$installer = $this;
/* @var $installer Enterprise_CustomerSegment_Model_Mysql4_Setup */

// add field that indicates that attribute is used for customer segments to attribute properties
$installer->getConnection()->addColumn($installer->getTable('catalog/eav_attribute'), "is_used_for_customer_segment", "TINYINT(1) UNSIGNED NOT NULL DEFAULT '0'");
$installer->getConnection()->addColumn($installer->getTable('customer/eav_attribute'), "is_used_for_customer_segment", "TINYINT(1) UNSIGNED NOT NULL DEFAULT '0'");
$installer->getConnection()->addColumn($installer->getTable('enterprise_customersegment_segment'), "website_id", "smallint(5) unsigned NOT NULL DEFAULT 0");

// use specific attributes for customer segments
$attributesOfEntities = array(
    'customer' => array('dob', 'email', 'firstname', 'group_id', 'lastname', 'gender', 'default_billing', 'default_shipping'),
    'customer_address' => array('firstname', 'lastname', 'company', 'street', 'city', 'region_id', 'postcode', 'country_id', 'telephone'),
    'order_address' => array('firstname', 'lastname', 'company', 'street', 'city', 'region_id', 'postcode', 'country_id', 'telephone', 'email'),
);
foreach ($attributesOfEntities as $entityTypeId => $attributes){
    foreach ($attributes as $attributeCode){
        $installer->updateAttribute($entityTypeId, $attributeCode, 'is_used_for_customer_segment', '1');
    }
}
