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
 * @package     Mage_GiftMessage
 * @copyright   Copyright (c) 2010 Magento Inc. (http://www.magentocommerce.com)
 * @license     http://www.magentocommerce.com/license/enterprise-edition
 */

$installer = $this;
/* $installer Mage_Core_Model_Resource_Setup */

$installer->updateAttribute(
    'catalog_product',
    'gift_message_available',
    'source_model',
    'eav/entity_attribute_source_boolean'
);

$installer->updateAttribute(
    'catalog_product',
    'gift_message_available',
    'backend_model',
    'catalog/product_attribute_backend_boolean'
);

$installer->updateAttribute(
    'catalog_product',
    'gift_message_available',
    'frontend_input_renderer',
    'adminhtml/catalog_product_helper_form_config'
);

$installer->updateAttribute(
    'catalog_product',
    'gift_message_available',
    'default_value',
    ''
);

/*
 * Update previously saved data for 'gift_message_available' attribute
 */
$entityTypeId = $installer->getEntityTypeId('catalog_product');
$attributeId  = $installer->getAttributeId($entityTypeId, 'gift_message_available');

$installer->getConnection()->update($installer->getTable('catalog_product_entity_varchar'),
    array('value' => ''),
    array(
        'entity_type_id =?' => $entityTypeId,
        'attribute_id =?' => $attributeId,
        'value =?' => '2'
    )
);
