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
 * @package     Enterprise_CatalogPermissions
 * @copyright   Copyright (c) 2010 Magento Inc. (http://www.magentocommerce.com)
 * @license     http://www.magentocommerce.com/license/enterprise-edition
 */

/**
 * Permission indexer resource
 *
 * @category   Enterprise
 * @package    Enterprise_CatalogPermissions
 */
class Enterprise_CatalogPermissions_Model_Mysql4_Permission_Index extends Mage_Core_Model_Mysql4_Abstract
{
    const XML_PATH_GRANT_BASE = 'catalog/enterprise_catalogpermissions/';

    /**
     * Store ids
     *
     * @var arrray
     */
    protected $_storeIds = array();

    /**
     * Data for insert
     *
     * @var array
     */
    protected $_insertData = array();

    /**
     * Table fields for insert
     *
     * @var array
     */
    protected $_tableFields = array();

    /**
     * Permission cache
     *
     * @var array
     */
    protected $_permissionCache = array();


    /**
     * Inheritance of grant appling in categories tree
     *
     * @return array
     */
    protected $_grantsInheritance = array(
        'grant_catalog_category_view' => 'deny',
        'grant_catalog_product_price' => 'allow',
        'grant_checkout_items' => 'allow'
    );

    protected function _construct()
    {
        $this->_init('enterprise_catalogpermissions/permission_index', 'category_id');
    }

    /**
     * Reindex category permissions
     *
     * @param string $categoryPath
     * @return Enterprise_CatalogPermissions_Model_Mysql4_Permission_Index
     */
    public function reindex($categoryPath)
    {
        $select = $this->_getReadAdapter()->select()
            ->from($this->getTable('catalog/category'), array('entity_id','path'))
            ->where('path LIKE ?', $categoryPath . '/%')
            ->orWhere('entity_id IN(?)', explode('/', $categoryPath))
            ->order('level ASC');

        $categoryPath = $this->_getReadAdapter()->fetchPairs($select);
        $categoryIds = array_keys($categoryPath);

        $select = $this->_getReadAdapter()->select()
            ->from(array('permission' => $this->getTable('enterprise_catalogpermissions/permission')), array(
                'category_id',
                'website_id',
                'customer_group_id',
                'grant_catalog_category_view',
                'grant_catalog_product_price',
                'grant_checkout_items'
            ))
            ->where('permission.category_id IN (?)', $categoryIds);

        $websiteIds = Mage::getModel('core/website')->getCollection()
            ->addFieldToFilter('website_id', array('neq'=>0))
            ->getAllIds();


        $customerGroupIds = Mage::getModel('customer/group')->getCollection()
            ->getAllIds();



        $notEmptyWhere = array();

        foreach (array_keys($this->_grantsInheritance) as $grant) {
             $notEmptyWhere[] = $this->_getReadAdapter()->quoteInto(
                'permission.' . $grant . ' != ?',
                Enterprise_CatalogPermissions_Model_Permission::PERMISSION_PARENT
             );
        }

        $select->where('(' . implode(' OR ', $notEmptyWhere).  ')');

        $permissions = $this->_getReadAdapter()->fetchAll($select);

        // Delete old index
        $this->_getWriteAdapter()->delete(
            $this->getMainTable(),
            $this->_getWriteAdapter()->quoteInto('category_id IN (?)', $categoryIds)
        );

        $this->_permissionCache = array();

        foreach ($permissions as $permission) {
            $uniqKey = $permission['website_id']
                     . '_' . $permission['customer_group_id'];
            if ($permission['website_id'] === null ||
                $permission['customer_group_id'] === null) {
                $uniqKey .= '_default';
            }

            $path = $categoryPath[$permission['category_id']];
            $this->_permissionCache[$path][$uniqKey] = $permission;
        }

        unset ($permissions);

        foreach ($this->_permissionCache as &$permissions) {
            foreach (array_keys($permissions) as $uniqKey) {
                $permission = $permissions[$uniqKey];
                if ($permission['website_id'] === null &&
                    $permission['customer_group_id'] === null) {
                    foreach ($customerGroupIds as $customerGroupId) {
                        // Apply permissions for all customer groups
                        if (!isset($permissions['_' . $customerGroupId . '_default'])) {
                            $permission['customer_group_id'] = $customerGroupId;
                            $permissions['_' . $customerGroupId . '_default'] = $permission;
                        }
                    }
                    unset($permissions[$uniqKey]);
                }
            }

            foreach (array_keys($permissions) as $uniqKey) {
                $permission = $permissions[$uniqKey];
                if ($permission['website_id'] === null) {
                    foreach ($websiteIds as $websiteId) {
                        if (!isset($permissions[$websiteId . '__default'])
                            && !isset($permissions[$websiteId . '_' . $permission['customer_group_id']])) {
                            // Apply permissions for all websites
                            $permission['website_id'] = $websiteId;
                            $permissions[$websiteId . '_' . $permission['customer_group_id']] = $permission;
                        }
                    }
                } elseif ($permission['customer_group_id'] === null) {
                    foreach ($customerGroupIds as $customerGroupId) {
                        if (!isset($permissions[$permission['website_id'] . '_' . $customerGroupId])) {
                            $permission['customer_group_id'] = $customerGroupId;
                            $permissions[$permission['website_id'] . '_' . $customerGroupId] = $permission;
                        }
                    }
                } else {
                    continue;
                }
                unset($permissions[$uniqKey]);
            }
        }

        $fields =  array_merge(
            array(
                'category_id', 'website_id', 'customer_group_id',
                'grant_catalog_category_view',
                'grant_catalog_product_price',
                'grant_checkout_items'
            )
        );

        $this->_beginInsert('permission_index', $fields);

        foreach ($categoryPath as $categoryId => $path) {
            $this->_inheritCategoryPermission($path);
            if (isset($this->_permissionCache[$path])) {
                foreach ($this->_permissionCache[$path] as $permission) {
                    if ($permission['grant_catalog_category_view'] == Enterprise_CatalogPermissions_Model_Permission::PERMISSION_DENY) {
                        $permission['grant_catalog_product_price'] = Enterprise_CatalogPermissions_Model_Permission::PERMISSION_DENY;
                    }
                    if ($permission['grant_catalog_product_price'] == Enterprise_CatalogPermissions_Model_Permission::PERMISSION_DENY) {
                        $permission['grant_checkout_items'] = Enterprise_CatalogPermissions_Model_Permission::PERMISSION_DENY;
                    }
                    $this->_insert('permission_index', array(
                        $categoryId,
                        $permission['website_id'],
                        $permission['customer_group_id'],
                        $permission['grant_catalog_category_view'],
                        $permission['grant_catalog_product_price'],
                        $permission['grant_checkout_items']
                    ));
                }
            }
        }

        $this->_commitInsert('permission_index');
        $this->_permissionCache = array();

        $select = $this->_getReadAdapter()->select()
            ->from($this->getTable('catalog/category_product'), 'product_id')
            ->distinct(true)
            ->where('category_id IN(?)', $categoryIds);

        $productIds = $this->_getReadAdapter()->fetchCol($select);

        $this->reindexProducts($productIds);

        return $this;
    }

    /**
     * Reindex products permissions
     *
     * @param array|string $productIds
     * @return Enterprise_CatalogPermissions_Model_Mysql4_Permission_Index
     */
    public function reindexProducts($productIds = null)
    {
        /* @var $isActive Mage_Eav_Model_Entity_Attribute */
        $isActive = Mage::getSingleton('eav/config')->getAttribute('catalog_category', 'is_active');

        //$isActiveAttributeId = Mage::getSingleton('eav/config')->getAttribute('catalog_category', 'is_active')->getId();

        $selectCategory = $this->getReadConnection()->select()
            ->from(
                array('category_product_index' => $this->getTable('catalog/category_product_index')),
                array('product_id', 'store_id')
            );
        if ($isActive->isScopeGlobal()) {
            $selectCategory->joinLeft(
                array('category_is_active' => $isActive->getBackend()->getTable()),
                'category_product_index.category_id = category_is_active.entity_id AND
                    category_is_active.store_id = 0
                 AND category_is_active.attribute_id = ' . (int) $isActive->getAttributeId(),
                array())
            ->where('category_is_active.value = 1');
        } else {
            $table = $isActive->getBackend()->getTable();
            $selectCategory->joinLeft(
                array('category_is_active' => $table),
                'category_product_index.category_id = category_is_active.entity_id AND
                    category_is_active.store_id = category_product_index.store_id
                 AND category_is_active.attribute_id = ' . (int) $isActive->getAttributeId(),
                array())
            ->joinLeft(
                array('category_is_active_default' => $table),
                'category_product_index.category_id = category_is_active_default.entity_id AND
                    category_is_active_default.store_id = 0
                  AND category_is_active_default.attribute_id = ' . (int) $isActive->getAttributeId(),
                array())
            ->where('IF(category_is_active.value_id > 0, category_is_active.value, category_is_active_default.value) = 1');
        }
            $selectCategory->join(
                array('store' => $this->getTable('core/store')),
                'category_product_index.store_id = store.store_id',
                array()
            )->group(array(
                'category_product_index.store_id',
                'category_product_index.product_id',
                'permission_index.customer_group_id'
           ))
            // Select for per category product index (without anchor category usage)
             ->columns('category_id', 'category_product_index')
             ->join(
                array('permission_index'=>$this->getTable('permission_index')),
                'category_product_index.category_id = permission_index.category_id AND
                 store.website_id = permission_index.website_id',
                array(
                    'customer_group_id',
                    'grant_catalog_category_view' => 'MAX(IF(permission_index.grant_catalog_category_view = ' .  Enterprise_CatalogPermissions_Model_Permission::PERMISSION_PARENT . ', NULL, permission_index.grant_catalog_category_view))',
                    'grant_catalog_product_price' => 'MAX(IF(permission_index.grant_catalog_product_price = ' .  Enterprise_CatalogPermissions_Model_Permission::PERMISSION_PARENT . ', NULL, permission_index.grant_catalog_product_price))',
                    'grant_checkout_items' => 'MAX(IF(permission_index.grant_checkout_items = ' .  Enterprise_CatalogPermissions_Model_Permission::PERMISSION_PARENT . ', NULL, permission_index.grant_checkout_items))'
                )
            )->group('category_product_index.category_id')
            ->where('category_product_index.is_parent = ?', 1);

        // Select for per category product index (with anchor category)
        $selectAnchorCategory = $this->_getReadAdapter()->select();
        $selectAnchorCategory
            ->from(array('permission_index_product'=>$this->getTable('permission_index_product')),
                array(
                    'product_id',
                    'store_id'
                )
            )->join(
                array('category_product_index' => $this->getTable('catalog/category_product_index')),
                'permission_index_product.product_id = category_product_index.product_id',
                array('category_id')
            )->join(
                array('category'=>$this->getTable('catalog/category')),
                'category.entity_id = category_product_index.category_id',
                array()
            )->join(
                array('category_child'=>$this->getTable('catalog/category')),
                'category_child.path LIKE CONCAT(category.path, \'/%\')
                AND category_child.entity_id = permission_index_product.category_id',
                array()
            )->columns(
                array(
                    'customer_group_id',
                    'grant_catalog_category_view' => 'MAX(IF(permission_index_product.grant_catalog_category_view = ' .  Enterprise_CatalogPermissions_Model_Permission::PERMISSION_PARENT . ', NULL, permission_index_product.grant_catalog_category_view))',
                    'grant_catalog_product_price' => 'MAX(IF(permission_index_product.grant_catalog_product_price = ' .  Enterprise_CatalogPermissions_Model_Permission::PERMISSION_PARENT . ', NULL, permission_index_product.grant_catalog_product_price))',
                    'grant_checkout_items' => 'MAX(IF(permission_index_product.grant_checkout_items = ' .  Enterprise_CatalogPermissions_Model_Permission::PERMISSION_PARENT . ', NULL, permission_index_product.grant_checkout_items))'
                ),
                'permission_index_product'
           )->group(array(
                'permission_index_product.store_id',
                'permission_index_product.product_id',
                'permission_index_product.customer_group_id',
                'category_product_index.category_id'
           ))->where('category_product_index.is_parent = 0');


        if ($productIds !== null) {
            if (!is_array($productIds)) {
                $productIds = array($productIds);
            }
            $selectCategory->where('category_product_index.product_id IN(?)', $productIds);
            $selectAnchorCategory->where('permission_index_product.product_id IN(?)', $productIds);
            $condition = $this->_getReadAdapter()->quoteInto('product_id IN(?)', $productIds);
        } else {
            $condition = '';
        }

        $fields = array(
            'product_id', 'store_id', 'category_id', 'customer_group_id',
            'grant_catalog_category_view', 'grant_catalog_product_price',
            'grant_checkout_items'
        );

        $this->_getWriteAdapter()->delete($this->getTable('permission_index_product'), $condition);
        $this->_getWriteAdapter()->query($selectCategory->insertFromSelect($this->getTable('permission_index_product'), $fields));
        $this->_getWriteAdapter()->query($selectAnchorCategory->insertFromSelect($this->getTable('permission_index_product'), $fields));

        $this->reindexProductsStandalone($productIds);

        return $this;
    }

    /**
     * Reindex products permissions for standalone mode
     *
     * @param array|string $productIds
     * @return Enterprise_CatalogPermissions_Model_Mysql4_Permission_Index
     */
    public function reindexProductsStandalone($productIds = null)
    {

        $selectConfig = $this->_getReadAdapter()->select();
        // Config depend index select
        $selectConfig->from(
                array('category_product_index' => $this->getTable('catalog/category_product_index')),
                array()
            )->join(
                array('permission_index_product'=>$this->getTable('permission_index_product')),
                'permission_index_product.product_id = category_product_index.product_id AND
                permission_index_product.store_id = category_product_index.store_id AND
                permission_index_product.is_config = 0',
                array('product_id', 'store_id')
            )->joinLeft(
                array('permission_index_product_exists'=>$this->getTable('permission_index_product')),
                'permission_index_product_exists.product_id = permission_index_product.product_id AND
                permission_index_product_exists.store_id = permission_index_product.store_id AND
                permission_index_product_exists.customer_group_id = permission_index_product.customer_group_id AND
                permission_index_product_exists.category_id = category_product_index.category_id',
                array()
            )->columns('category_id')
             ->columns(array(
                'customer_group_id',
                'grant_catalog_category_view' => $this->_getConfigGrantDbExpr('grant_catalog_category_view', 'permission_index_product'),
                'grant_catalog_product_price' => $this->_getConfigGrantDbExpr('grant_catalog_product_price', 'permission_index_product'),
                'grant_checkout_items' => $this->_getConfigGrantDbExpr('grant_checkout_items', 'permission_index_product'),
                'is_config' => new Zend_Db_Expr('1')
            ), 'permission_index_product')->group(array(
                'category_product_index.category_id',
                'permission_index_product.product_id',
                'permission_index_product.store_id',
                'permission_index_product.customer_group_id'
            ))->where('permission_index_product_exists.category_id IS NULL');

        // Select for standalone product index
        $selectStandalone = $this->_getReadAdapter()->select();
        $selectStandalone
            ->from(array('permission_index_product'=>$this->getTable('permission_index_product')),
                array(
                    'product_id',
                    'store_id'
                )
            )->columns(
                array(
                    'category_id' => new Zend_Db_Expr('NULL'),
                    'customer_group_id',
                    'grant_catalog_category_view' => 'MAX(IF(permission_index_product.grant_catalog_category_view = ' .  Enterprise_CatalogPermissions_Model_Permission::PERMISSION_PARENT . ', NULL, permission_index_product.grant_catalog_category_view))',
                    'grant_catalog_product_price' => 'MAX(IF(permission_index_product.grant_catalog_product_price = ' .  Enterprise_CatalogPermissions_Model_Permission::PERMISSION_PARENT . ', NULL, permission_index_product.grant_catalog_product_price))',
                    'grant_checkout_items' => 'MAX(IF(permission_index_product.grant_checkout_items = ' .  Enterprise_CatalogPermissions_Model_Permission::PERMISSION_PARENT . ', NULL, permission_index_product.grant_checkout_items))',
                    'is_config' => new Zend_Db_Expr('1')
                ),
                'permission_index_product'
           )->group(array(
                'permission_index_product.store_id',
                'permission_index_product.product_id',
                'permission_index_product.customer_group_id'
           ));

        $condition = 'is_config = 1';



        if ($productIds !== null) {
            if (!is_array($productIds)) {
                $productIds = array($productIds);
            }
            $selectConfig->where('category_product_index.product_id IN(?)', $productIds);
            $selectStandalone->where('permission_index_product.product_id IN(?)', $productIds);
            $condition .= $this->_getReadAdapter()->quoteInto(' AND product_id IN(?)', $productIds);
        }

        $fields = array(
            'product_id', 'store_id', 'category_id', 'customer_group_id',
            'grant_catalog_category_view', 'grant_catalog_product_price',
            'grant_checkout_items', 'is_config'
        );

        $this->_getWriteAdapter()->delete($this->getTable('permission_index_product'), $condition);
        $this->_getWriteAdapter()->query($selectConfig->insertFromSelect($this->getTable('permission_index_product'), $fields));
        $this->_getWriteAdapter()->query($selectStandalone->insertFromSelect($this->getTable('permission_index_product'), $fields));
        // Fix inherited permissions
        $deny = (int) Enterprise_CatalogPermissions_Model_Permission::PERMISSION_DENY;
        $this->_getWriteAdapter()->query(
            "UPDATE {$this->getTable('permission_index_product')}
                SET
                    grant_catalog_product_price = IF(grant_catalog_category_view = {$deny}, {$deny}, grant_catalog_product_price),
                    grant_checkout_items = IF(grant_catalog_category_view = {$deny} OR grant_catalog_product_price = {$deny}, {$deny}, grant_checkout_items)
                WHERE
                    {$condition}
            ");

        return $this;
    }

    /**
     * Generates CASE ... WHEN .... THEN expression for grant depends on config
     *
     * @param string $grant
     * @param string $tableAlias
     * @return Zend_Db_Expr
     */
    protected function _getConfigGrantDbExpr($grant, $tableAlias)
    {
        $conditions = array();
        foreach ($this->_getStoreIds() as $storeId) {
            $config = Mage::getStoreConfig(self::XML_PATH_GRANT_BASE . $grant);

            if ($config == 2) {
                $groups = explode(',', trim(Mage::getStoreConfig(
                    self::XML_PATH_GRANT_BASE . $grant . '_groups'
                )));
                foreach ($groups as $groupId) {
                    if (is_numeric($groupId)) {
                        // Case per customer group
                        $condition = $tableAlias . '.store_id = ' . $storeId
                                   . ' AND '. $tableAlias . '.customer_group_id = '
                                   . (int) $groupId;
                        $conditions[$condition] = Enterprise_CatalogPermissions_Model_Permission::PERMISSION_ALLOW;
                    }
                }

                $condition = $tableAlias . '.store_id = ' . $storeId;
                $conditions[$condition] = Enterprise_CatalogPermissions_Model_Permission::PERMISSION_DENY;
            } else {
                $condition = $tableAlias . '.store_id = ' . $storeId;
                $conditions[$condition] = (
                    $config ?
                    Enterprise_CatalogPermissions_Model_Permission::PERMISSION_ALLOW :
                    Enterprise_CatalogPermissions_Model_Permission::PERMISSION_DENY
                );
            }
        }

        if (empty($conditions)) {
            return new Zend_Db_Expr('0');
        }

        $expr = 'CASE ';
        foreach ($conditions as $condition => $value) {
            $expr .= ' WHEN ' . $condition . ' THEN ' . $this->_getReadAdapter()->quote($value);
        }
        $expr .= ' END';

        return new Zend_Db_Expr($expr);
    }

    protected function _getStoreIds()
    {
        if (empty($this->_storeIds)) {
            $this->_storeIds = array();
            $stores = Mage::app()->getConfig()->getNode('stores');
            foreach ($stores->children() as $store) {
                $storeId = (int) $store->descend('system/store/id');
                if ($storeId) {
                    $this->_storeIds[] = $storeId;
                }
            }
        }

        return $this->_storeIds;
    }

    /**
     * Inherit category permission from it's parent
     *
     * @param string $path
     * @return Enterprise_CatalogPermissions_Model_Mysql4_Permission_Index
     */
    protected function _inheritCategoryPermission($path)
    {
        if (strpos($path, '/') !== false) {
            $parentPath = substr($path, 0, strrpos($path, '/'));
        } else {
            $parentPath = '';
        }

        if (isset($this->_permissionCache[$path])) {
            foreach (array_keys($this->_permissionCache[$path]) as $uniqKey) {
                if (isset($this->_permissionCache[$parentPath][$uniqKey])) {
                    foreach ($this->_grantsInheritance as $grant => $inheritance) {
                        if ($this->_permissionCache[$path][$uniqKey][$grant] == Enterprise_CatalogPermissions_Model_Permission::PERMISSION_PARENT) {
                            $this->_permissionCache[$path][$uniqKey][$grant] = $this->_permissionCache[$parentPath][$uniqKey][$grant];
                        } else {
                            $value = $this->_permissionCache[$parentPath][$uniqKey][$grant];

                            if ($inheritance == 'allow') {
                                $value = max(
                                    $this->_permissionCache[$path][$uniqKey][$grant],
                                    $value
                                );
                            }

                            $value = min(
                                $this->_permissionCache[$path][$uniqKey][$grant],
                                $value
                            );

                            $this->_permissionCache[$path][$uniqKey][$grant] = $value;
                        }

                        if ($this->_permissionCache[$path][$uniqKey][$grant] == Enterprise_CatalogPermissions_Model_Permission::PERMISSION_PARENT) {
                            $this->_permissionCache[$path][$uniqKey][$grant] = null;
                        }

                    }
                }
            }
            if (isset($this->_permissionCache[$parentPath])) {
                foreach (array_keys($this->_permissionCache[$parentPath]) as $uniqKey) {
                    if (!isset($this->_permissionCache[$path][$uniqKey])) {
                        $this->_permissionCache[$path][$uniqKey] = $this->_permissionCache[$parentPath][$uniqKey];
                    }
                }
            }
        } elseif (isset($this->_permissionCache[$parentPath])) {
            $this->_permissionCache[$path] = $this->_permissionCache[$parentPath];
        }



        return $this;
    }


    /**
     * Retrieve permission index for category or categories with specified customer group and website id
     *
     * @param int|array $categoryId
     * @param int $customerGroupId
     * @param int $websiteId
     * @return array
     */
    public function getIndexForCategory($categoryId, $customerGroupId = null, $websiteId = null)
    {
        if (!is_array($categoryId)) {
            $categoryId = array($categoryId);
        }

        $select = $this->_getReadAdapter()->select()
            ->from($this->getMainTable())
            ->where('category_id IN(?)', $categoryId);
        if (!is_null($customerGroupId)) {
            $select->where('customer_group_id = ?', $customerGroupId);
        }
        if (!is_null($websiteId)) {
            $select->where('website_id = ?', $websiteId);
        }

        return $this->_getReadAdapter()->fetchAssoc($select);
    }

    /**
     * Retrieve restricted category ids for customer group and website
     *
     * @param int $customerGroupId
     * @param int $websiteId
     * @return array
     */
    public function getRestrictedCategoryIds($customerGroupId, $websiteId)
    {
        $select = $this->_getReadAdapter()->select()
            ->from($this->getMainTable(), 'category_id')
            ->where('customer_group_id = ?', $customerGroupId)
            ->where('website_id = ?', $websiteId);

        if (!Mage::helper('enterprise_catalogpermissions')->isAllowedCategoryView()) {
            $select
                ->where('grant_catalog_category_view = ' . Enterprise_CatalogPermissions_Model_Permission::PERMISSION_ALLOW);
        } else {
            $select
                ->where('grant_catalog_category_view = ' . Enterprise_CatalogPermissions_Model_Permission::PERMISSION_DENY);
        }

        $restrictedCatIds = $this->_getReadAdapter()->fetchCol($select);

        $select = $this->_getReadAdapter()->select()
            ->from($this->getTable('catalog/category'), 'entity_id');

        if (!empty($restrictedCatIds) && !Mage::helper('enterprise_catalogpermissions')->isAllowedCategoryView()) {
            $select->where('entity_id NOT IN(?)', $restrictedCatIds);
        } elseif (!empty($restrictedCatIds) && Mage::helper('enterprise_catalogpermissions')->isAllowedCategoryView()) {
            $select->where('entity_id IN(?)', $restrictedCatIds);
        } elseif (Mage::helper('enterprise_catalogpermissions')->isAllowedCategoryView()) {
            $select->where('1 = 0'); // category view allowed for all
        }

        return $this->_getReadAdapter()->fetchCol($select);
    }

    /**
     * Apply price grant on price index select
     *
     * @param Varien_Object $data
     * @param int $customerGroupId
     * @return Enterprise_CatalogPermissions_Model_Mysql4_Permission_Index
     */
    public function applyPriceGrantToPriceIndex($data, $customerGroupId)
    {

        $select = $data->getSelect();
        $parts = $select->getPart(Zend_Db_Select::FROM);


        if (!isset($parts['permission_index_product'])) {
            $select->joinLeft(
                array('permission_index_product'=>$this->getTable('permission_index_product')),
                'permission_index_product.category_id IS NULL AND
                 permission_index_product.product_id = ' . $data->getTable() .'.entity_id AND
                 permission_index_product.store_id = '. (int) $data->getStoreId() .' AND
                 permission_index_product.customer_group_id = ' . (int) $customerGroupId,
                array()
            );
        }

        if (!Mage::helper('enterprise_catalogpermissions')->isAllowedProductPrice()) {
            $select->where('permission_index_product.grant_catalog_product_price = ' . Enterprise_CatalogPermissions_Model_Permission::PERMISSION_ALLOW);
        } else {
            $select->where('permission_index_product.grant_catalog_product_price != ' . Enterprise_CatalogPermissions_Model_Permission::PERMISSION_DENY . '
                     OR permission_index_product.grant_catalog_product_price IS NULL');
        }

        return $this;
    }

    /**
     * Add index to product count select in product collection
     *
     * @param Mage_Catalog_Model_Resource_Eav_Mysql4_Product_Collection $collection
     * @param int $customerGroupId
     * @return Enterprise_CatalogPermissions_Model_Mysql4_Permission_Index
     */
    public function addIndexToProductCount($collection, $customerGroupId)
    {
        $parts = $collection->getSelect()->getPart(Zend_Db_Select::FROM);

        if (isset($parts['permission_index_product'])) {
            return $this;
        }

        $collection->getProductCountSelect()
            ->joinLeft(
                array('permission_index_product_count'=>$this->getTable('permission_index_product')),
                'permission_index_product_count.category_id = count_table.category_id AND
                 permission_index_product_count.product_id = count_table.product_id AND
                 permission_index_product_count.store_id = count_table.store_id AND
                 permission_index_product_count.customer_group_id = ' . (int) $customerGroupId,
                array()
            );

        if (!Mage::helper('enterprise_catalogpermissions')->isAllowedCategoryView()) {
            $collection->getProductCountSelect()
                ->where('permission_index_product_count.grant_catalog_category_view = ' . Enterprise_CatalogPermissions_Model_Permission::PERMISSION_ALLOW);
        } else {
            $collection->getProductCountSelect()
                ->where('permission_index_product_count.grant_catalog_category_view != ' . Enterprise_CatalogPermissions_Model_Permission::PERMISSION_DENY . '
                         OR permission_index_product_count.grant_catalog_category_view IS NULL');
        }

        return $this;
    }

    /**
     * Add index to category collection
     *
     * @param Mage_Catalog_Model_Resource_Eav_Mysql4_Category_Collection|Mage_Catalog_Model_Resource_Eav_Mysql4_Category_Flat_Collection $collection
     * @param int $customerGroupId
     * @param int $websiteId
     * @return Enterprise_CatalogPermissions_Model_Mysql4_Permission_Index
     */
    public function addIndexToCategoryCollection($collection, $customerGroupId, $websiteId)
    {
        if ($collection instanceof Mage_Catalog_Model_Resource_Eav_Mysql4_Category_Flat_Collection) {
            $tableAlias = 'main_table';
        } else {
            $tableAlias = 'e';
        }

        $collection->getSelect()->joinLeft(
            array('permission_index'=>$this->getTable('permission_index')),
            'permission_index.category_id = ' . $tableAlias . '.entity_id AND
             permission_index.website_id = ' . (int) $websiteId . ' AND
             permission_index.customer_group_id = ' . (int) $customerGroupId,
            array()
        );

        if (!Mage::helper('enterprise_catalogpermissions')->isAllowedCategoryView()) {
            $collection->getSelect()
                ->where('permission_index.grant_catalog_category_view = ' . Enterprise_CatalogPermissions_Model_Permission::PERMISSION_ALLOW);
        } else {
            $collection->getSelect()
                ->where('permission_index.grant_catalog_category_view != ' . Enterprise_CatalogPermissions_Model_Permission::PERMISSION_DENY . '
                             OR permission_index.grant_catalog_category_view IS NULL');
        }

        return $this;
    }

    /**
     * Add index select in product collection
     *
     * @param Mage_Catalog_Model_Resource_Eav_Mysql4_Product_Collection $collection
     * @return Enterprise_CatalogPermissions_Model_Mysql4_Permission_Index
     */
    public function addIndexToProductCollection($collection, $customerGroupId)
    {
        $parts = $collection->getSelect()->getPart(Zend_Db_Select::FROM);

        $conditions = array();
        if (isset($parts['cat_index']) && $parts['cat_index']['tableName'] == $this->getTable('catalog/category_product_index')) {
            $conditions[] = 'permission_index_product.category_id = cat_index.category_id';
            $conditions[] = 'permission_index_product.product_id = cat_index.product_id';
            $conditions[] = 'permission_index_product.store_id = cat_index.store_id';
        }
        else {
            $conditions[] = 'permission_index_product.category_id IS NULL';
            $conditions[] = 'permission_index_product.product_id = e.entity_id';
            $conditions[] = 'permission_index_product.store_id=' . intval($collection->getStoreId());
        }
        $conditions[] = 'permission_index_product.customer_group_id=' . intval($customerGroupId);

        $condition = join(' AND ', $conditions);

        if (isset($parts['permission_index_product'])) {
            $parts['permission_index_product']['joinCondition'] = $condition;
            $collection->getSelect()->setPart(Zend_Db_Select::FROM, $parts);
        }
        else {
            $collection->getSelect()
                ->joinLeft(
                    array('permission_index_product' => $this->getTable('permission_index_product')),
                    $condition,
                    array(
                        'grant_catalog_category_view',
                        'grant_catalog_product_price',
                        'grant_checkout_items'
                    )
                );
            if (!Mage::helper('enterprise_catalogpermissions')->isAllowedCategoryView()) {
                $collection->getSelect()
                    ->where('permission_index_product.grant_catalog_category_view = ' . Enterprise_CatalogPermissions_Model_Permission::PERMISSION_ALLOW);
            } else {
                $collection->getSelect()
                    ->where('permission_index_product.grant_catalog_category_view != ' . Enterprise_CatalogPermissions_Model_Permission::PERMISSION_DENY . '
                             OR permission_index_product.grant_catalog_category_view IS NULL');
            }

            /*
             * Checking if passed collection has link model attached
             */
            if (method_exists($collection, 'getLinkModel')){
                $linkTypeId = $collection->getLinkModel()->getLinkTypeId();
                $linkTypeIds = array(
                    Mage_Catalog_Model_Product_Link::LINK_TYPE_CROSSSELL,
                    Mage_Catalog_Model_Product_Link::LINK_TYPE_UPSELL
                );

                /*
                 * If collection has appropriate link type (cross-sell or up-sell) we need to
                 * limit products by permissions (display price and add to cart)
                 */
                if (in_array($linkTypeId, $linkTypeIds)) {

                    if (!Mage::helper('enterprise_catalogpermissions')->isAllowedProductPrice()) {
                        $collection->getSelect()
                            ->where('permission_index_product.grant_catalog_product_price = ' . Enterprise_CatalogPermissions_Model_Permission::PERMISSION_ALLOW);
                    } else {
                        $collection->getSelect()
                            ->where('permission_index_product.grant_catalog_product_price != ' . Enterprise_CatalogPermissions_Model_Permission::PERMISSION_DENY . '
                                     OR permission_index_product.grant_catalog_product_price IS NULL');
                    }

                    if (!Mage::helper('enterprise_catalogpermissions')->isAllowedCheckoutItems()) {
                        $collection->getSelect()
                            ->where('permission_index_product.grant_checkout_items = ' . Enterprise_CatalogPermissions_Model_Permission::PERMISSION_ALLOW);
                    } else {
                        $collection->getSelect()
                            ->where('permission_index_product.grant_checkout_items != ' . Enterprise_CatalogPermissions_Model_Permission::PERMISSION_DENY . '
                                     OR permission_index_product.grant_checkout_items IS NULL');
                    }
                }
            }
        }

        return $this;
    }

    /**
     * Add permission index to product model
     *
     * @param Mage_Catalog_Model_Product $product
     * @param int $customerGroupId
     * @return Enterprise_CatalogPermissions_Model_Mysql4_Permission_Index
     */
    public function addIndexToProduct($product, $customerGroupId)
    {
        $select = $this->_getReadAdapter()->select()
            ->from($this->getTable('permission_index_product'),
                array(
                    'grant_catalog_category_view',
                    'grant_catalog_product_price',
                    'grant_checkout_items'
                )
            )
            ->where('product_id = ?', $product->getId())
            ->where('customer_group_id = ?', $customerGroupId)
            ->where('store_id = ?', $product->getStoreId());

        if ($product->getCategory()) {
            $select->where('category_id = ?', $product->getCategory()->getId());
        } else {
            $select->where('category_id IS NULL');
        }

        $permission = $this->_getReadAdapter()->fetchRow($select);

        if ($permission) {
            $product->addData($permission);
        }

        return $this;
    }

    /**
     * Get permission index for products
     *
     * @param int|array $productId
     * @param int $customerGroupId
     * @param int $storeId
     * @return array
     */
    public function getIndexForProduct($productId, $customerGroupId, $storeId)
    {
        if (!is_array($productId)) {
            $productId = array($productId);
        }

        $select = $this->_getReadAdapter()->select()
            ->from($this->getTable('permission_index_product'),
                array(
                    'product_id',
                    'grant_catalog_category_view',
                    'grant_catalog_product_price',
                    'grant_checkout_items'
                )
            )
            ->where('product_id IN(?)', $productId)
            ->where('customer_group_id = ?', $customerGroupId)
            ->where('store_id = ?', $storeId)
            ->where('category_id IS NULL');

        return $this->_getReadAdapter()->fetchAssoc($select);
    }

    /**
     * Prepare base information for data insert
     *
     * @param   string $table
     * @param   array $fields
     * @return  Enterprise_CatalogPermissions_Model_Mysql4_Permission_Index
     */
    protected function _beginInsert($table, $fields)
    {
        $this->_tableFields[$table] = $fields;
        return $this;
    }

    /**
     * Put data into table
     *
     * @param   string $table
     * @param   bool $forced
     * @return  Enterprise_CatalogPermissions_Model_Mysql4_Permission_Index
     */
    protected function _commitInsert($table, $forced = true){
        if (isset($this->_insertData[$table]) && count($this->_insertData[$table]) && ($forced || count($this->_insertData[$table]) >= 100)) {
            $query = 'INSERT INTO ' . $this->getTable($table) . ' (' . implode(', ', $this->_tableFields[$table]) . ') VALUES ';
            $separator = '';
            foreach ($this->_insertData[$table] as $row) {
                $rowString = $this->_getWriteAdapter()->quoteInto('(?)', $row);
                $query .= $separator . $rowString;
                $separator = ', ';
            }
            $this->_getWriteAdapter()->query($query);
            $this->_insertData[$table] = array();
        }
        return $this;
    }

    /**
     * Insert data to table
     *
     * @param   string $table
     * @param   array $data
     * @return  Enterprise_CatalogPermissions_Model_Mysql4_Permission_Indexer
     */
    protected function _insert($table, $data) {
        $this->_insertData[$table][] = $data;
        $this->_commitInsert($table, false);
        return $this;
    }
}
