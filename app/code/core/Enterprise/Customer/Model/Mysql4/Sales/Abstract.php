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
 * Customer Sales Mysql4 abstract resource
 *
 */
abstract class Enterprise_Customer_Model_Mysql4_Sales_Abstract extends Mage_Core_Model_Mysql4_Abstract
{
    /**
     * Used us prefix to name of column table
     *
     * @var null | string
     */
    protected $_columnPrefix        = 'customer';

    /**
     * Primery key auto increment flag
     *
     * @var bool
     */
    protected $_isPkAutoIncrement   = false;

    /**
     * Main entity resource model name
     * Should be overwritten in subclasses.
     *
     * @var string
     */
    protected $_parentResourceModelName = '';

    /**
     * Return column name for attribute
     *
     * @param Mage_Customer_Model_Attribute $attribute
     * @return string
     */
    protected function _getColumnName(Mage_Customer_Model_Attribute $attribute)
    {
        if ($this->_columnPrefix) {
            return sprintf('%s_%s', $this->_columnPrefix, $attribute->getAttributeCode());
        }
        return $attribute->getAttributeCode();
    }

    /**
     * Saves a new attribute
     *
     * @param Mage_Customer_Model_Attribute $attribute
     * @return Enterprise_Customer_Model_Mysql4_Sales_Abstract
     */
    public function saveNewAttribute(Mage_Customer_Model_Attribute $attribute)
    {
        $backendType = $attribute->getBackendType();
        if ($backendType == Mage_Customer_Model_Attribute::TYPE_STATIC) {
            return $this;
        }

        switch ($backendType) {
            case 'datetime':
                $defination = "DATE NULL DEFAULT NULL";
                break;
            case 'decimal':
                $defination = "DECIMAL(12,4) DEFAULT NULL";
                break;
            case 'int':
                $defination = "INT(11) DEFAULT NULL";
                break;
            case 'text':
                $defination = "TEXT DEFAULT NULL";
                break;
            case 'varchar':
                $defination = "VARCHAR(255) DEFAULT NULL";
                break;
            default:
                return $this;
        }

        $this->_getWriteAdapter()->addColumn($this->getMainTable(), $this->_getColumnName($attribute), $defination);

        return $this;
    }

    /**
     * Deletes an attribute
     *
     * @param Mage_Customer_Model_Attribute $attribute
     * @return Enterprise_Customer_Model_Mysql4_Sales_Abstract
     */
    public function deleteAttribute(Mage_Customer_Model_Attribute $attribute)
    {
        $this->_getWriteAdapter()->dropColumn($this->getMainTable(), $this->_getColumnName($attribute));
        return $this;
    }

    /**
     * Return resource model of the main entity
     *
     * @return Mage_Core_Model_Mysql4_Abstract | null
     */
    protected function _getParentResourceModel()
    {
        if (!$this->_parentResourceModelName) {
            return null;
        }
        return Mage::getResourceSingleton($this->_parentResourceModelName);
    }

    /**
     * Check if main entity exists in main table.
     * Need to prevent errors in case of multiple customer log in into one account.
     *
     * @param Enterprise_Customer_Model_Sales_Abstract $sales
     * @return bool
     */
    public function isEntityExists(Enterprise_Customer_Model_Sales_Abstract $sales)
    {
        if (!$sales->getId()) {
            return false;
        }

        $resource = $this->_getParentResourceModel();
        if (!$resource) {
            /**
             * If resource model is absent, we shouldn't check the database for if main entity exists.
             */
            return true;
        }

        $parentTable = $resource->getMainTable();
        $parentIdField = $resource->getIdFieldName();
        $select = $this->_getWriteAdapter()->select()
            ->from($parentTable, $parentIdField)
            ->forUpdate(true)
            ->where("{$parentIdField} = ?", $sales->getId());
        if ($this->_getWriteAdapter()->fetchOne($select)) {
            return true;
        }
        return false;
    }
}
