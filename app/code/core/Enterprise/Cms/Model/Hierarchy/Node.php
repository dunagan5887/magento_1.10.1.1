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
 * Cms Hierarchy Pages Node Model
 *
 * @category   Enterprise
 * @package    Enterprise_Cms
 */
class Enterprise_Cms_Model_Hierarchy_Node extends Mage_Core_Model_Abstract
{
    /**
     *
     * @var unknown_type
     */
    protected $_metaNodes = array();

    /**
     * Meta node's types
     */
    const META_NODE_TYPE_CHAPTER = 'chapter';
    const META_NODE_TYPE_SECTION = 'section';
    const META_NODE_TYPE_FIRST = 'start';
    const META_NODE_TYPE_NEXT = 'next';
    const META_NODE_TYPE_PREVIOUS = 'prev';

    /**
     * Initialize resource model
     *
     */
    protected function _construct()
    {
        $this->_init('enterprise_cms/hierarchy_node');
    }

    /**
     * Retrieve Resource instance wrapper
     *
     * @return Enterprise_Cms_Model_Mysql4_Hierarchy_Node
     */
    protected function _getResource()
    {
        return parent::_getResource();
    }

    /**
     * Collect and save tree
     *
     * @param array $data       modified nodes data array
     * @param array $remove     the removed node ids
     * @return Enterprise_Cms_Model_Hierarchy_Node
     */
    public function collectTree($data, $remove)
    {
        if (!is_array($data)) {
            return $this;
        }

        $nodes = array();
        foreach ($data as $v) {
            $required = array(
                'node_id', 'parent_node_id', 'page_id', 'label', 'identifier', 'level', 'sort_order'
            );
            // validate required node data
            foreach ($required as $field) {
                if (!array_key_exists($field, $v)) {
                    Mage::throwException(
                        Mage::helper('enterprise_cms')->__('Invalid node data.')
                    );
                }
            }
            $parentNodeId = empty($v['parent_node_id']) ? 0 : $v['parent_node_id'];
            $pageId = empty($v['page_id']) ? null : intval($v['page_id']);


            $_node = array(
                'node_id'            => strpos($v['node_id'], '_') === 0 ? null : intval($v['node_id']),
                'page_id'            => $pageId,
                'label'              => !$pageId ? $v['label'] : null,
                'identifier'         => !$pageId ? $v['identifier'] : null,
                'level'              => intval($v['level']),
                'sort_order'         => intval($v['sort_order']),
                'request_url'        => $v['identifier']
            );

            $nodes[$parentNodeId][$v['node_id']] = Mage::helper('enterprise_cms/hierarchy')
                ->copyMetaData($v, $_node);
        }

        $this->_getResource()->beginTransaction();
        try {
            // remove deleted nodes
            if (!empty($remove)) {
                $this->_getResource()->dropNodes($remove);
            }
            // recursive node save
            $this->_collectTree($nodes, $this->getId(), $this->getRequestUrl(), $this->getId(), 0);

            $this->_getResource()->commit();
        } catch (Exception $e) {
            $this->_getResource()->rollBack();
            throw $e;
        }

        return $this;
    }

    /**
     * Recursive save nodes
     *
     * @param array $nodes
     * @param int $parentNodeId
     * @param string $path
     * @param int $level
     * @return Enterprise_Cms_Model_Hierarchy_Node
     */
    protected function _collectTree(array $nodes, $parentNodeId, $path = '', $xpath = '', $level = 0)
    {
        if (!isset($nodes[$level])) {
            return $this;
        }
        foreach ($nodes[$level] as $k => $v) {
            $v['parent_node_id'] = $parentNodeId;
            if ($path != '') {
                $v['request_url'] = $path . '/' . $v['request_url'];
            } else {
                $v['request_url'] = $v['request_url'];
            }

            if ($xpath != '') {
                $v['xpath'] = $xpath . '/';
            } else {
                $v['xpath'] = '';
            }

            $object = clone $this;
            $object->setData($v)->save();

            if (isset($nodes[$k])) {
                $this->_collectTree($nodes, $object->getId(), $object->getRequestUrl(), $object->getXpath(), $k);
            }
        }
        return $this;
    }

    /**
     * Flag to indicate whether append active pages only or not
     *
     * @param bool $flag
     * @return Enterprise_Cms_Model_Hierarchy_Node
     */
    public function setCollectActivePagesOnly($flag)
    {
        $flag = (bool)$flag;
        $this->setData('collect_active_pages_only', $flag);
        $this->_getResource()->setAppendActivePagesOnly($flag);
        return $this;
    }

    /**
     * Flag to indicate whether append included pages (menu_excluded=0) only or not
     *
     * @param bool $flag
     * @return Enterprise_Cms_Model_Hierarchy_Node
     */
    public function setCollectIncludedPagesOnly($flag)
    {
        $flag = (bool)$flag;
        $this->setData('collect_included_pages_only', $flag);
        $this->_getResource()->setAppendIncludedPagesOnly($flag);
        return $this;
    }

    /**
     * Retrieve Node or Page identifier
     *
     * @return string
     */
    public function getIdentifier()
    {
        $identifier = $this->_getData('identifier');
        if (is_null($identifier)) {
            $identifier = $this->_getData('page_identifier');
        }
        return $identifier;
    }

    /**
     * Is Node used original Page Identifier
     *
     * @return bool
     */
    public function isUseDefaultIdentifier()
    {
        return is_null($this->_getData('identifier'));
    }

    /**
     * Retrieve Node label or Page title
     *
     * @return string
     */
    public function getLabel()
    {
        $label = $this->_getData('label');
        if (is_null($label)) {
            $label = $this->_getData('page_title');
        }
        return $label;
    }

    /**
     * Is Node used original Page Label
     *
     * @return bool
     */
    public function isUseDefaultLabel()
    {
        return is_null($this->_getData('label'));
    }

    /**
     * Load node by Request Url
     *
     * @param string $url
     * @return Enterprise_Cms_Model_Hierarchy_Node
     */
    public function loadByRequestUrl($url)
    {
        $this->_getResource()->loadByRequestUrl($this, $url);
        $this->_afterLoad();
        $this->setOrigData();
        return $this;
    }

    /**
     * Retrieve first child node
     *
     * @param int $parentNodeId
     * @return Enterprise_Cms_Model_Hierarchy_Node
     */
    public function loadFirstChildByParent($parentNodeId)
    {
        $this->_getResource()->loadFirstChildByParent($this, $parentNodeId);
        $this->_afterLoad();
        $this->setOrigData();
        return $this;
    }

    /**
     * Update rewrite for page (if identifier changed)
     *
     * @param Mage_Cms_Model_Page $page
     * @return Enterprise_Cms_Model_Hierarchy_Node
     */
    public function updateRewriteUrls(Mage_Cms_Model_Page $page)
    {
        $xpaths = $this->_getResource()->getTreeXpathsByPage($page->getId());
        foreach ($xpaths as $xpath) {
            $this->_getResource()->updateRequestUrlsForTreeByXpath($xpath);
        }
        return $this;
    }

    /**
     * Check identifier
     *
     * If a CMS Page belongs to a tree (binded to a tree node), it should not be accessed standalone
     * only by URL that identifies it in a hierarchy.
     *
     * Return true if a page binded to a tree node
     *
     * @param string $identifier
     * @param int|Mage_Core_Model_Store $storeId
     * @return bool
     */
    public function checkIdentifier($identifier, $storeId = null)
    {
        $storeId = Mage::app()->getStore($storeId)->getId();
        return $this->_getResource()->checkIdentifier($identifier, $storeId);
    }

    /**
     * Retrieve meta node by specified type for current node's tree.
     * Allowed types:
     *  - chapter       parent node chapter
     *  - section       parent node section
     *  - first         first node in current parent node level
     *  - next          next node (only in current parent node level)
     *  - previous      previous node (only in current parent node level)
     *
     * @param string $type
     * @return Enterprise_Cms_Model_Hierarchy_Node
     */
    public function getMetaNodeByType($type)
    {
        if (!isset($this->_metaNodes[$type])) {
            $model = Mage::getModel('enterprise_cms/hierarchy_node')
                ->setData($this->_getResource()->getMetaNodeDataByType($this, $type));

            $this->_metaNodes[$type] = $model;
        }

        return $this->_metaNodes[$type];
    }

    /**
     * Retrieve Page URL
     *
     * @param mixed $store
     * @return string
     */
    public function getUrl($store = null)
    {
        return Mage::app()->getStore($store)->getUrl('', array(
            '_direct' => trim($this->getRequestUrl())
        ));
    }

    /**
     * Setter for tree_max_depth data
     * Maximum tree depth for tree slice, if equals zero - no limitations
     *
     * @param int $depth
     * @return Enterprise_Cms_Model_Hierarchy_Node
     */
    public function setTreeMaxDepth($depth)
    {
        $this->setData('tree_max_depth', (int)$depth);
        return $this;
    }

    /**
     * Setter for tree_is_brief data
     * Tree Detalization, i.e. brief or detailed
     *
     * @param bool $brief
     * @return Enterprise_Cms_Model_Hierarchy_Node
     */
    public function setTreeIsBrief($brief)
    {
        $this->setData('tree_is_brief', (bool)$brief);
        return $this;
    }

    /**
     * Retrieve Tree Slice like two level array of node models.
     *
     * @param int $up, if equals zero - no limitation
     * @param int $down, if equals zero - no limitation
     * @return array
     */
    public function getTreeSlice($up = 0, $down = 0)
    {
        $data = $this->_getResource()
            ->setTreeMaxDepth($this->_getData('tree_max_depth'))
            ->setTreeIsBrief($this->_getData('tree_is_brief'))
            ->getTreeSlice($this, $up, $down);

        $blankModel = Mage::getModel('enterprise_cms/hierarchy_node');
        foreach ($data as $parentId => $children) {
            foreach ($children as $childId => $child) {
                $newModel = clone $blankModel;
                $data[$parentId][$childId] = $newModel->setData($child);
            }
        }
        return $data;
    }
    /**
     * Retrieve parent node's children.
     *
     * @return array
     */
    public function getParentNodeChildren()
    {
        $children = $this->_getResource()->getParentNodeChildren($this);
        $blankModel = Mage::getModel('enterprise_cms/hierarchy_node');
        foreach ($children as $childId => $child) {
            $newModel = clone $blankModel;
            $children[$childId] = $newModel->setData($child);
        }
        return $children;
    }

    /**
     * Load page data for model if defined page id end undefined page data
     *
     * @return Enterprise_Cms_Model_Hierarchy_Node
     */
    public function loadPageData()
    {
        if ($this->getPageId() && !$this->getPageIdentifier()) {
            $this->_getResource()->loadPageData($this);
        }

        return $this;
    }

    /**
     * Appending passed page as child node for specified nodes and set it specified sort order.
     * Parent nodes specified as array (parentNodeId => sortOrder)
     *
     * @param Mage_Cms_Model_Page $page
     * @param array $nodes
     * @return Enterprise_Cms_Model_Hierarchy_Node
     */
    public function appendPageToNodes($page, $nodes)
    {
        $parentNodes = $this->getCollection()
            ->joinPageExistsNodeInfo($page)
            ->applyPageExistsOrNodeIdFilter(array_keys($nodes), $page);

        $pageData = array(
            'page_id' => $page->getId(),
            'identifier' => null,
            'label' => null
        );

        $removeFromNodes = array();

        foreach ($parentNodes as $node) {
            /* @var $node Enterprise_Cms_Model_Hierarchy_Node */
            if (isset($nodes[$node->getId()])) {
                $sortOrder = $nodes[$node->getId()];
                if ($node->getPageExists()) {
                    continue;
                } else {
                    $node->addData($pageData)
                        ->setParentNodeId($node->getId())
                        ->unsetData($this->getIdFieldName())
                        ->setLevel($node->getLevel() + 1)
                        ->setSortOrder($sortOrder)
                        ->setRequestUrl($node->getRequestUrl() . '/' . $page->getIdentifier())
                        ->setXpath($node->getXpath() . '/')
                        ->save();
                }
            } else {
                $removeFromNodes[] = $node->getId();
            }
        }

        if (!empty($removeFromNodes)) {
            $this->_getResource()->removePageFromNodes($page->getId(), $removeFromNodes);
        }

        return $this;
    }

    /**
     * Get tree meta data flags for current node's tree.
     *
     * @return array|bool
     */
    public function getTreeMetaData()
    {
        if (is_null($this->_treeMetaData)) {
            $this->_treeMetaData = $this->_getResource()->getTreeMetaData($this);
        }

        return $this->_treeMetaData;
    }

    /**
     * Return nearest parent params for node pagination
     *
     * @return array|null
     */
    public function getMetadataPagerParams()
    {
        $values = array(
            Enterprise_Cms_Helper_Hierarchy::METADATA_VISIBILITY_YES,
            Enterprise_Cms_Helper_Hierarchy::METADATA_VISIBILITY_NO);

        return $this->getResource()->getParentMetadataParams($this, 'pager_visibility', $values);
    }

    /**
     * Return nearest parent params for node context menu
     *
     * @return array|null
     */
    public function getMetadataContextMenuParams()
    {
        // Node is excluded from Menu
        if ($this->getData('menu_excluded') == 1) {
            return null;
        }

        // Menu is disabled in some of parent nodes
        $params = $this->getResource()->getParentMetadataParams($this, 'menu_excluded', array(1));
        if ($params !== null && $params['level'] > 1) {
            return null;
        }

        // Root node menu params
        $params = $this->getResource()->getTreeMetaData($this);
        if (isset($params['menu_visibility']) && $params['menu_visibility'] == 1) {
            return $params;
        }

        return null;
    }

    /**
     * Return Hierarchy Menu Layout Info object for Node
     *
     * @return Varien_Object|null
     */
    public function getMenuLayout()
    {
        $rootParams = $this->_getResource()->getTreeMetaData($this);
        if (!isset($rootParams['menu_layout'])) {
            return null;
        }
        $layoutCode = $rootParams['menu_layout'];
        if (!$layoutCode) {
            $layoutCode = Mage::getStoreConfig('cms/hierarchy/menu_layout');
        }
        if (!$layoutCode) {
            return null;
        }
        $layout = Mage::getSingleton('enterprise_cms/hierarchy_config')->getContextMenuLayout($layoutCode);
        return is_object($layout) ? $layout : null;
    }

    /**
     * Process additional data after save.
     *
     * @return Enterprise_Cms_Model_Hierarchy_Node
     */
    protected function _afterSave()
    {
        parent::_afterSave();
        // we save to metadata table not only metadata :(
        //if (Mage::helper('enterprise_cms/hierarchy')->isMetadataEnabled()) {
            $this->_getResource()->saveMetaData($this);
        //}

        return $this;
    }
}
