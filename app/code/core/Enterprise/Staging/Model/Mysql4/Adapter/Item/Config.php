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
 * @package     Enterprise_Staging
 * @copyright   Copyright (c) 2010 Magento Inc. (http://www.magentocommerce.com)
 * @license     http://www.magentocommerce.com/license/enterprise-edition
 */

class Enterprise_Staging_Model_Mysql4_Adapter_Item_Config extends Enterprise_Staging_Model_Mysql4_Adapter_Item_Default
{
    /**
     * Prepare simple select by given parameters
     *
     * @param mixed  $table
     * @param string $fields
     * @param string $where
     * @return string
     */
    protected function _getSimpleSelect($table, $fields, $where = null)
    {
        $_where = array();
        if (!is_null($where)) {
            $_where[] = $where;
        }

        if ($this->getEvent()->getCode() !== 'rollback') {
            $itemXmlConfig = $this->getConfig();
            if ($itemXmlConfig->ignore_nodes) {
                foreach ($itemXmlConfig->ignore_nodes->children() as $node) {
                    $path = (string) $node->path;
                    $_where[] = "path NOT LIKE '%{$path}%'";
                }
            }
        }
        if (is_array($fields)) {
            $fields = $this->_prepareFields($fields);
        }
        if (!empty($_where)) {
            $_where = implode(' AND ', $_where);
            $_where = " WHERE " . $_where;
        }

        return "SELECT $fields FROM `{$table}` $_where";
    }
}
