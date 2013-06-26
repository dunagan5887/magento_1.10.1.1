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
 * @package     Mage_Eav
 * @copyright   Copyright (c) 2010 Magento Inc. (http://www.magentocommerce.com)
 * @license     http://www.magentocommerce.com/license/enterprise-edition
 */


class Mage_Eav_Model_Mysql4_Entity_Attribute_Set_Collection extends Mage_Core_Model_Mysql4_Collection_Abstract
{
    public function _construct()
    {
        $this->_init('eav/entity_attribute_set');
    }

    public function setEntityTypeFilter($typeId)
    {
        $this->getSelect()->where('main_table.entity_type_id=?', $typeId);
        return $this;
    }

    public function toOptionArray()
    {
        return parent::_toOptionArray('attribute_set_id', 'attribute_set_name');
    }

    public function toOptionHash()
    {
        return parent::_toOptionHash('attribute_set_id', 'attribute_set_name');
    }
}
