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
 * Cms Page Hierarchy Tree Nodes Collection
 *
 * @category   Enterprise
 * @package    Enterprise_Cms
 */
class Enterprise_Cms_Model_Mysql4_Hierarchy_Node_Collection extends Mage_Core_Model_Mysql4_Collection_Abstract
{
    /**
     * Define resource model for collection
     *
     */
    protected function _construct()
    {
        $this->_init('enterprise_cms/hierarchy_node');
    }

    /**
     * Join Cms Page data to collection
     *
     * @return Enterprise_Cms_Model_Mysql4_Hierarchy_Node_Collection
     */
    public function joinCmsPage()
    {
        if (!$this->getFlag('cms_page_data_joined')) {
            $this->getSelect()->joinLeft(
                array('page_table' => $this->getTable('cms/page')),
                'main_table.page_id = page_table.page_id',
                array(
                    'page_title'        => 'title',
                    'page_identifier'   => 'identifier'
                )
            );
            $this->setFlag('cms_page_data_joined', true);
        }
        return $this;
    }

    /**
     * Add Store Filter to assigned CMS pages
     *
     * @param int|Mage_Core_Model_Store $store
     * @param bool $withAdmin Include admin store or not
     * @return Enterprise_Cms_Model_Mysql4_Hierarchy_Node_Collection
     */
    public function addStoreFilter($store, $withAdmin = true)
    {
        if ($store instanceof Mage_Core_Model_Store) {
            $store = array($store->getId());
        }
        $this->addCmsPageInStoresColumn();
        $this->getFlag('page_in_stores_select')->where('store.store_id in (?)', ($withAdmin ? array(0, $store) : $store));
        $this->getSelect()->having('main_table.page_id IS NULL OR page_in_stores IS NOT NULL');
        return $this;
    }

    /**
     * Adding sub query for custom column to determine on which stores page active.
     *
     * @return Enterprise_Cms_Model_Mysql4_Hierarchy_Node_Collection
     */
    public function addCmsPageInStoresColumn()
    {
        if (!$this->getFlag('cms_page_in_stores_data_joined')) {
            $subSelect = $this->getConnection()->select();
            $subSelect->from(array('store' => $this->getTable('cms/page_store')), new Zend_Db_Expr('GROUP_CONCAT(`store_id`)'))
                ->where('store.page_id = main_table.page_id');

            $this->getSelect()->columns(array('page_in_stores' => new Zend_Db_Expr('(' . $subSelect . ')')));

            // save subSelect to use later
            $this->setFlag('page_in_stores_select', $subSelect);

            $this->setFlag('cms_page_in_stores_data_joined', true);
        }
        return $this;
    }

    /**
     * Order nodes as tree
     *
     * @return Enterprise_Cms_Model_Mysql4_Hierarchy_Node_Collection
     */
    public function setTreeOrder()
    {
        if (!$this->getFlag('tree_order_added')) {
            $this->getSelect()->order(array(
                'parent_node_id', 'level', 'main_table.sort_order'
            ));
            $this->setFlag('tree_order_added', true);
        }
        return $this;
    }

    /**
     * Order tree by level and position
     *
     * @return Enterprise_Cms_Model_Mysql4_Hierarchy_Node_Collection
     */
    public function setOrderByLevel()
    {
        $this->getSelect()->order(array('level', 'sort_order'));
        return $this;
    }

    /**
     * Join meta data for tree root nodes from extra table.
     *
     * @return Enterprise_Cms_Model_Mysql4_Hierarchy_Node_Collection
     */
    public function joinMetaData()
    {
        if (!$this->getFlag('meta_data_joined')) {
            $this->getSelect()->joinLeft(array('metadata_table' => $this->getTable('enterprise_cms/hierarchy_metadata')),
                'main_table.node_id = metadata_table.node_id',
                array(
                    'meta_first_last',
                    'meta_next_previous',
                    'meta_chapter',
                    'meta_section',
                    'meta_cs_enabled',
                    'pager_visibility',
                    'pager_frame',
                    'pager_jump',
                    'menu_visibility',
                    'menu_layout',
                    'menu_brief',
                    'menu_excluded',
                    'menu_levels_down',
                    'menu_ordered',
                    'menu_list_type'
                ));
        }
        $this->setFlag('meta_data_joined', true);
        return $this;
    }

    /**
     * Join main table on self to discover which nodes
     * have defined page as direct child node.
     *
     * @param int|Mage_Cms_Model_Page $page
     * @return Enterprise_Cms_Model_Mysql4_Hierarchy_Node_Collection
     */
    public function joinPageExistsNodeInfo($page)
    {
        if (!$this->getFlag('page_exists_joined')) {
            if ($page instanceof Mage_Cms_Model_Page) {
                $page = $page->getId();
            }

            $onClause = 'main_table.node_id = clone.parent_node_id AND clone.page_id = ?';
            $ifPageExistExpr = new Zend_Db_Expr('IF(clone.node_id is NULL, 0, 1)');
            $ifCurrentPageExpr = new Zend_Db_Expr(
                    $this->getConnection()->quoteInto('IF(main_table.page_id = ?, 1, 0)', $page)
                );

            $this->getSelect()->joinLeft(
                    array('clone' => $this->getResource()->getMainTable()),
                    $this->getConnection()->quoteInto($onClause, $page),
                    array('page_exists' => $ifPageExistExpr, 'current_page' => $ifCurrentPageExpr)
                );

            $this->setFlag('page_exists_joined', true);
        }
        return $this;
    }

    /**
     * Apply filter to retrieve nodes with ids which
     * were defined as parameter or nodes which contain
     * defined page in their direct children.
     *
     * @param int|array $nodeIds
     * @param int|Mage_Cms_Model_Page|null $page
     * @return Enterprise_Cms_Model_Mysql4_Hierarchy_Node_Collection
     */
    public function applyPageExistsOrNodeIdFilter($nodeIds, $page = null)
    {
        if (!$this->getFlag('page_exists_or_node_id_filter_applied')) {
            if (!$this->getFlag('page_exists_joined')) {
                $this->joinPageExistsNodeInfo($page);
            }

            $whereExpr = new Zend_Db_Expr(
                $this->getConnection()->quoteInto('clone.node_id IS NOT NULL OR main_table.node_id IN (?)', $nodeIds)
            );

            $this->getSelect()->where($whereExpr);
            $this->setFlag('page_exists_or_node_id_filter_applied', true);
        }

        return $this;
    }

    /**
     * Adds dynamic column with maximum value (which means that it
     * is sort_order of last direct child) of sort_order column in scope of one node.
     *
     * @return Enterprise_Cms_Model_Mysql4_Hierarchy_Node_Collection
     */
    public function addLastChildSortOrderColumn()
    {
        if (!$this->getFlag('last_child_sort_order_column_added')) {
            $subSelect = $this->getConnection()->select();
            $subSelect->from($this->getResource()->getMainTable(), new Zend_Db_Expr('MAX(sort_order)'))
                ->where('parent_node_id = `main_table`.`node_id`');
            $this->getSelect()->columns(array('last_child_sort_order' => $subSelect));

            $this->setFlag('last_child_sort_order_column_added', true);
        }

        return $this;
    }

    /**
     * Apply filter to retrieve only root nodes.
     *
     * @return Enterprise_Cms_Model_Mysql4_Hierarchy_Node_Collection
     */
    public function applyRootNodeFilter()
    {
        $this->addFieldToFilter('parent_node_id', array('null' => true));

        return $this;
    }
}
