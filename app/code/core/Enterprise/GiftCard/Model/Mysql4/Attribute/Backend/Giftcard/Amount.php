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
 * @package     Enterprise_GiftCard
 * @copyright   Copyright (c) 2010 Magento Inc. (http://www.magentocommerce.com)
 * @license     http://www.magentocommerce.com/license/enterprise-edition
 */

class Enterprise_GiftCard_Model_Mysql4_Attribute_Backend_Giftcard_Amount extends Mage_Core_Model_Mysql4_Abstract
{
    protected function _construct()
    {
        $this->_init('enterprise_giftcard/amount', 'value_id');
    }

    public function loadProductData($product, $attribute)
    {
        $select = $this->_getReadAdapter()->select()
            ->from($this->getMainTable(), array(
                'website_id',
                'value'
            ))
            ->where('entity_id=?', $product->getId())
            ->where('attribute_id=?', $attribute->getId());
        if ($attribute->isScopeGlobal()) {
            $select->where('website_id=?', 0);
        }
        else {
            if ($storeId = $product->getStoreId()) {
                $select->where('website_id IN (?)', array(0, Mage::app()->getStore($storeId)->getWebsiteId()));
            }
        }
        return $this->_getReadAdapter()->fetchAll($select);
    }

    public function deleteProductData($product, $attribute)
    {
        $condition = array();

        if (!$attribute->isScopeGlobal()) {
            if ($storeId = $product->getStoreId()) {
                $condition[] = $this->_getWriteAdapter()->quoteInto('website_id IN (?)', array(0, Mage::app()->getStore($storeId)->getWebsiteId()));
            }
        }

        $condition[] = $this->_getWriteAdapter()->quoteInto('entity_id=?', $product->getId());
        $condition[] = $this->_getWriteAdapter()->quoteInto('attribute_id=?', $attribute->getId());

        $this->_getWriteAdapter()->delete($this->getMainTable(), implode(' AND ', $condition));
        return $this;
    }

    public function insertProductData($product, $data)
    {
        $data['entity_id'] = $product->getId();
        $data['entity_type_id'] = $product->getEntityTypeId();

        $this->_getWriteAdapter()->insert($this->getMainTable(), $data);
        return $this;
    }
}
