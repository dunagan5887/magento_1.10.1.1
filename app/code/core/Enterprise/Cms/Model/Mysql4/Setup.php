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
 * @package     Enterprise_Cms
 * @copyright   Copyright (c) 2010 Magento Inc. (http://www.magentocommerce.com)
 * @license     http://www.magentocommerce.com/license/enterprise-edition
 */


/**
 * Enterprise Cms Resource Setup model
 *
 * @category   Enterprise
 * @package    Enterprise_Cms
 */
class Enterprise_Cms_Model_Mysql4_Setup extends Mage_Core_Model_Resource_Setup
{
    /**
     * Fix xpath for hierarchy node table
     *
     * @return Enterprise_Cms_Model_Mysql4_Setup
     */
    public function fixXpathForHierarchyNode()
    {
        $connection = $this->getConnection();
        $nodes  = array();
        $select = $connection->select()->from(
            $this->getTable('enterprise_cms/hierarchy_node'),
            array('node_id', 'parent_node_id')
        );
        $rowSet = $select->query()->fetchAll();
        foreach ($rowSet as $k => $row) {
            $nodes[(int)$row['parent_node_id']][] = (int)$row['node_id'];
            unset($rowSet[$k]);
        }

        $this->_updateXpathCallback($nodes, null, 0);

        return $this;
    }

    /**
     * Update Hierarchy nodes Xpath Callback method
     *
     * @param array $nodes
     * @param string $xpath
     * @param int $parentNodeId
     * @return Enterprise_Cms_Model_Mysql4_Setup
     */
    protected function _updateXpathCallback(array $nodes, $xpath = '', $parentNodeId = 0)
    {
        if (!isset($nodes[$parentNodeId])) {
            return $this;
        }
        foreach ($nodes[$parentNodeId] as $nodeId) {
            $nodeXpath = $xpath ? $xpath . '/' . $nodeId : $nodeId;

            $bind  = array(
                'xpath' => $nodeXpath
            );
            $where = $this->getConnection()->quoteInto('node_id=?', $nodeId);

            $this->getConnection()->update($this->getTable('enterprise_cms/hierarchy_node'), $bind, $where);
            if (isset($nodes[$nodeId])) {
                $this->_updateXpathCallback($nodes, $nodeXpath, $nodeId);
            }
        }

        return $this;
    }
}
