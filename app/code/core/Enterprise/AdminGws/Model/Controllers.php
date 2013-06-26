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
 * @package     Enterprise_AdminGws
 * @copyright   Copyright (c) 2010 Magento Inc. (http://www.magentocommerce.com)
 * @license     http://www.magentocommerce.com/license/enterprise-edition
 */

/**
 * Controllers AdminGws validator
 */
class Enterprise_AdminGws_Model_Controllers extends Enterprise_AdminGws_Model_Observer_Abstract
{
    /**
     * @var Mage_Core_Controller_Request_Http
     */
    protected $_request;

    /**
     * @var bool
     */
    protected $_isForwarded = false;

    /**
     * Initialize helper
     *
     */
    public function __construct()
    {
        parent::__construct();
        $this->_request = Mage::app()->getRequest();
    }

    /**
     * Make sure the System Configuration pages are used in proper scopes
     *
     * @param Mage_Adminhtml_Controller_Action $controller
     */
    public function validateSystemConfig($controller)
    {
        // allow specific store view scope
        if ($storeCode = $this->_request->getParam('store')) {
            if ($store = Mage::app()->getStore($storeCode)) {
                if ($this->_role->hasStoreAccess($store->getId())) {
                    return;
                }
            }
        }
        // allow specific website scope
        elseif ($websiteCode = $this->_request->getParam('website')) {
            try {
                if ($website = Mage::app()->getWebsite($websiteCode)) {
                    if ($this->_role->hasWebsiteAccess($website->getId(), true)) {
                        return;
                    }
                }
            }
            catch (Mage_Core_Exception $e) {
                // redirect later from non-existing website
            }
        }

        // redirect to first allowed website or store scope
        if ($this->_role->getWebsiteIds()) {
            return $this->_redirect($controller, Mage::getSingleton('adminhtml/url')
                ->getUrl('adminhtml/system_config/edit',
                     array('website' => Mage::app()->getAnyStoreView()->getWebsite()->getCode()))
            );
        }
        $this->_redirect($controller, Mage::getSingleton('adminhtml/url')->getUrl('adminhtml/system_config/edit',
            array('website' => Mage::app()->getAnyStoreView()->getWebsite()->getCode(),
            'store' => Mage::app()->getAnyStoreView()->getCode()))
        );
    }

    /**
     * Validate misc catalog product requests
     *
     * @param Mage_Adminhtml_Controller_Action $controller
     */
    public function validateCatalogProduct($controller)
    {
        if (!$this->validateNoWebsiteGeneric($controller, array('new', 'delete', 'duplicate'))) {
            return;
        }
    }

    /**
     * Validate catalog product edit page
     *
     * @param Mage_Adminhtml_Controller_Action $controller
     */
    public function validateCatalogProductEdit($controller)
    {
        // redirect from disallowed scope
        if ($this->_isDisallowedStoreInRequest()) {
            return $this->_redirect($controller, array('*/*/*', 'id' => $this->_request->getParam('id')));
        }
    }

    /**
     * Validate catalog product review save, edit action
     *
     * @param Mage_Adminhtml_Controller_Action $controller
     */
    public function validateCatalogProductReview($controller)
    {
        $reviewStores = Mage::getModel('review/review')
            ->load($controller->getRequest()->getParam('id'))
            ->getStores();

        $storeIds = $this->_role->getStoreIds();

        $allowedIds = array_intersect($reviewStores, $storeIds);
        if (empty($allowedIds)) {
            $this->_redirect($controller);
        }
    }

    /**
     * Avoid viewing disallowed customer
     *
     * @param Mage_Adminhtml_Controller_Action $controller
     */
    public function validateCustomerEdit($controller)
    {
        $customer = Mage::getModel('customer/customer')->load($this->_request->getParam('id'));
        if ($customer->getId() && !in_array($customer->getWebsiteId(), $this->_role->getRelevantWebsiteIds())) {
            return $this->_forward();
        }
    }

    /**
     * Avoid viewing disallowed customer balance
     *
     * @param Mage_Adminhtml_Controller_Action $controller
     */
    public function validateCustomerbalance()
    {
        if (!$id = $this->_request->getParam('id')) {
            return $this->_forward();
        }
        $customer = Mage::getModel('customer/customer')->load($id);
        if ((!$customer->getId()) || !in_array($customer->getWebsiteId(), $this->_role->getRelevantWebsiteIds())) {
            return $this->_forward();
        }
    }

    /**
     * Disallow submitting gift cards without website-level permissions
     *
     * @param Mage_Adminhtml_Controller_Action $controller
     */
    public function validateGiftCardAccount($controller)
    {
        $controller->setShowCodePoolStatusMessage(false);
        if (!$this->_role->getIsWebsiteLevel()) {
            $action = $controller->getRequest()->getActionName();
            if (in_array($action, array('new', 'generate'))
                || $action == 'edit' && !$controller->getRequest()->getParam('id')) {
                return $this->_forward();
            }
        }
    }

    /**
     * Disallow saving catalog rules in disallowed scopes
     *
     * @param Mage_Adminhtml_Controller_Action $controller
     */
    public function validatePromoCatalog($controller)
    {
        return $this->validatePromoQuote($controller, Mage::getModel('catalogrule/rule'));
    }

    /**
     * Disallow saving quote rules in disallowed scopes
     *
     * @param Mage_Adminhtml_Controller_Action $controller
     * @param Mage_Core_Model_Abstract $model
     */
    public function validatePromoQuote($controller, $model = null)
    {
        if (!$this->validateNoWebsiteGeneric($controller, array('new', 'delete', 'applyRules'), 'save', 'rule_id')) {
            return;
        }
        $request = $controller->getRequest();
        if (null === $model) {
            $model = Mage::getModel('salesrule/rule');
        }
        switch ($request->getActionName()) {
            case 'edit': // also forwards from 'new'
                $id = $request->getParam('id');
                // break intentionally omitted
            case 'save':
                $id = $request->getParam('rule_id');
                $model->load($id);
                if (!$model->getId()) {
                    return;
                }
                if (!$this->_role->hasWebsiteAccess(Mage::helper('enterprise_admingws')->explodeIds(
                    $model->getOrigData('website_ids')))) {
                    return $this->_forward();
                }
                break;
        }
    }

    /**
     * Prevent viewing wrong categories and creation pages
     *
     * @param Mage_Adminhtml_Controller_Action $controller
     */
    public function validateCatalogCategories($controller)
    {
        $forward = false;
        switch ($controller->getRequest()->getActionName()) {
            case 'add':
                /**
                 * adding is not allowed from begining if user has scope specified permissions
                 */
                $forward = true;
                $parentId = $controller->getRequest()->getParam('parent');
                if ($parentId) {
                    $forward = !$this->_validateCatalogSubCategoryAddPermission($parentId);
                }
                break;
            case 'edit':
                if (!$controller->getRequest()->getParam('id')) {
                    $parentId = $controller->getRequest()->getParam('parent');
                    if ($parentId) {
                        $forward = !$this->_validateCatalogSubCategoryAddPermission($parentId);
                    } else {
                        $forward = true; // no adding root categories
                    }
                } else {
                    $category = Mage::getModel('catalog/category')->load($controller->getRequest()->getParam('id'));
                    if (!$category->getId() || !$this->_isCategoryAllowed($category)) {
                        $forward = true; // no viewing wrong categories
                    }
                }
                break;
        }

        // forward to first allowed root category
        if ($forward) {
            $firstRootId = current(array_keys($this->_role->getAllowedRootCategories()));
            if ($firstRootId) {
                $controller->getRequest()->setParam('id', $firstRootId);
                $controller->getRequest()->setParam('clear', 1);
                return $this->_forward('edit');
            }
            $this->_forward();
        }
    }

    /**
     * Disallow viewing categories in disallowed scopes
     *
     * @param Mage_Adminhtml_Controller_Action $controller
     */
    public function validateCatalogCategoryView($controller)
    {

    }

    /**
     * Disallow submitting catalog event in wrong scope
     *
     * @param Mage_Adminhtml_Controller_Action $controller
     */
    public function validateCatalogEvents($controller)
    {
        // instead of generic (we are capped by allowed store groups root categories)
        // check whether attempting to create event for wrong category
        if ('new' === $this->_request->getActionName()) {
            $category = Mage::getModel('catalog/category')->load($this->_request->getParam('category_id'));
            if (($this->_request->getParam('category_id') && !$this->_isCategoryAllowed($category)) ||
                !$this->_role->getIsWebsiteLevel()) {
                return $this->_forward();
            }
        }
    }

    /**
     * Disallow viewing wrong catalog events or viewing them in disallowed scope
     *
     * @param Mage_Adminhtml_Controller_Action $controller
     */
    public function validateCatalogEventEdit($controller)
    {
        if (!$this->_request->getParam('id') && $this->_role->getIsWebsiteLevel()) {
            return;
        }

        // avoid viewing disallowed events
        $catalogEvent = Mage::getModel('enterprise_catalogevent/event')->load($this->_request->getParam('id'));
        $category     = Mage::getModel('catalog/category')->load($catalogEvent->getCategoryId());
        if (!$this->_isCategoryAllowed($category)) {
            return $this->_forward();
        }

        // redirect from disallowed store scope
        if ($this->_isDisallowedStoreInRequest()) {
            return $this->_redirect(
                $controller,
                array('*/*/*', 'store' => Mage::app()->getAnyStoreView()->getId(), 'id' => $catalogEvent->getId())
            );
        }
    }

    /**
     * Disallow any creation order activity, if there is no website-level access
     *
     * @param Mage_Adminhtml_Controller_Action $controller
     */
    public function validateSalesOrderCreation($controller)
    {
        if (!$this->_role->getWebsiteIds()) {
            return $this->_forward();
        }

         // check whether there is disallowed website in request?
    }

// TODO allow viewing sales information only from allowed websites

    /**
     * Don't allow to create or delete entity, if there is no website permissions
     *
     * Returns false if disallowed
     *
     * @param Mage_Adminhtml_Controller_Action $controller (first param is reserved, don't remove it)
     * @param string|array $denyActions
     * @param string $saveAction
     * @param string $idFieldName
     * @return bool
     */
    public function validateNoWebsiteGeneric(
        $controller = null, $denyActions = array('new', 'delete'), $saveAction = 'save', $idFieldName = 'id'
    )
    {
        if (!is_array($denyActions)) {
            $denyActions = array($denyActions);
        }
        if ((!$this->_role->getWebsiteIds()) && (in_array($this->_request->getActionName(), $denyActions)
            || ($saveAction === $this->_request->getActionName() && 0 == $this->_request->getParam($idFieldName)))) {
            $this->_forward();
            return false;
        }
        return true;
    }

    /**
     * Validate Manage Stores pages actions
     *
     * @param Mage_Adminhtml_Controller_Action $controller
     */
    public function validateSystemStore($controller)
    {
        // due to design of the original controller, need to run this check only once, on the first dispatch
        if (Mage::registry('enterprise_admingws_system_store_matched')) {
            return;
        } elseif (in_array($this->_request->getActionName(), array('save', 'newWebsite', 'newGroup', 'newStore',
            'editWebsite', 'editGroup', 'editStore', 'deleteWebsite', 'deleteWebsitePost', 'deleteGroup',
            'deleteGroupPost', 'deleteStore', 'deleteStorePost'
            ))) {
            Mage::register('enterprise_admingws_system_store_matched', true, true);
        }

        switch ($this->_request->getActionName()) {
            case 'save':
                $params = $this->_request->getParams();
                if (isset($params['website'])) {
                    return $this->_forward();
                }
                if (isset($params['store']) || isset($params['group'])) {
                    if (!$this->_role->getWebsiteIds()) {
                        return $this->_forward();
                    }
                    // preventing saving stores/groups for wrong website is handled by their models
                }
                break;
            case 'newWebsite':
                return $this->_forward();
                break;
            case 'newGroup': // break intentionally omitted
            case 'newStore':
                if (!$this->_role->getWebsiteIds()) {
                    return $this->_forward();
                }
                break;
            case 'editWebsite':
                if (!$this->_role->hasWebsiteAccess($this->_request->getParam('website_id'))) {
                    return $this->_forward();
                }
                break;
            case 'editGroup':
                if (!$this->_role->hasStoreGroupAccess($this->_request->getParam('group_id'))) {
                    return $this->_forward();
                }
                break;
            case 'editStore':
                if (!$this->_role->hasStoreAccess($this->_request->getParam('store_id'))) {
                    return $this->_forward();
                }
                break;
            case 'deleteWebsite': // break intentionally omitted
            case 'deleteWebsitePost':
                return $this->_forward();
                break;
            case 'deleteGroup': // break intentionally omitted
            case 'deleteGroupPost':
                if ($group = $this->_role->getGroup($this->_request->getParam('item_id'))) {
                    if ($this->_role->hasWebsiteAccess($group->getWebsiteId(), true)) {
                        return;
                    }
                }
                return $this->_forward();
                break;
            case 'deleteStore': // break intentionally omitted
            case 'deleteStorePost':
                if ($store = Mage::app()->getStore($this->_request->getParam('item_id'))) {
                    if ($this->_role->hasWebsiteAccess($store->getWebsiteId(), true)) {
                        return;
                    }
                }
                return $this->_forward();
                break;
        }
    }

    /**
     * Redirect to a specific page
     *
     * @param Mage_Adminhtml_Controller_Action $controller
     */
    protected function _redirect($controller, $url = null)
    {
        $controller->setFlag('', Mage_Core_Controller_Varien_Action::FLAG_NO_DISPATCH, true);
        if (null === $url) {
            $url = Mage::getSingleton('adminhtml/url')->getUrl('*/*/denied');
        }
        elseif (is_array($url)) {
            $url = Mage::getSingleton('adminhtml/url')->getUrl(array_shift($url), $url);
        }
        elseif (false === strpos($url, 'http', 0)) {
            $url = Mage::getSingleton('adminhtml/url')->getUrl($url);
        }
        Mage::app()->getResponse()->setRedirect($url);
    }

    /**
     * Forward current request
     *
     * @param string $action
     * @param string $module
     * @param string $controller
     */
    protected function _forward($action = 'denied', $module = null, $controller = null)
    {
        // avoid cycling
        if ($this->_request->getActionName() === $action
            && (null === $module || $this->_request->getModuleName() === $module)
            && (null === $controller || $this->_request->getControllerName() === $controller)) {
            return;
        }

        $this->_request->initForward();

        if ($module) {
            $this->_request->setModuleName($module);
        }
        if ($controller) {
            $this->_request->setControllerName($controller);
        }
        $this->_request->setActionName($action)->setDispatched(false);
        $this->_isForwarded = true;
    }

    /**
     * Check whether a disallowed store is in request
     *
     * @param string $idFieldName
     * @return bool
     */
    protected function _isDisallowedStoreInRequest($idFieldName = 'store')
    {
        $store = Mage::app()->getStore($this->_request->getParam($idFieldName), 0);
        return ($store->isAdmin() ? false : !$this->_role->hasStoreAccess($store->getId()));
    }

    /**
     * Check whether specified category is allowed
     *
     * @param Mage_Catalog_Model_Category $category
     * @return bool
     */
    protected function _isCategoryAllowed($category)
    {
        if (!$category->getId()) {
            return false;
        }
        $categoryPath = $category->getPath();
        foreach ($this->_role->getAllowedRootCategories() as $rootPath) {
            if ($categoryPath === $rootPath || 0 === strpos($categoryPath, "{$rootPath}/")) {
                return true;
            }
        }
        return false;
    }

    /**
     * Validate Order view actions
     *
     * @param Mage_Adminhtml_Controller_Action $controller
     */
    public function validateSalesOrderViewAction($controller)
    {
        if ($id = $this->_request->getParam('order_id')) {
            $object = Mage::getModel('sales/order')->load($id);
            if ($object && $object->getId()) {
                $store = $object->getStoreId();
                if (!$this->_role->hasStoreAccess($store)) {
                    $this->_forward();
                    return false;
                }
            }
        }
        return true;
    }

    /**
     * Validate Creditmemo view actions
     *
     * @param Mage_Adminhtml_Controller_Action $controller
     */
    public function validateSalesOrderCreditmemoViewAction($controller)
    {
        $id = $this->_request->getParam('creditmemo_id');
        if (!$id) {
            $id = $this->_request->getParam('id');
        }
        if ($id) {
            $object = Mage::getModel('sales/order_creditmemo')->load($id);
            if ($object && $object->getId()) {
                $store = $object->getStoreId();
                if (!$this->_role->hasStoreAccess($store)) {
                    $this->_forward();
                    return false;
                }
            }
        }
        return true;
    }

    /**
     * Validate Invoice view actions
     *
     * @param Mage_Adminhtml_Controller_Action $controller
     */
    public function validateSalesOrderInvoiceViewAction($controller)
    {
        $id = $this->_request->getParam('invoice_id');
        if (!$id) {
            $id = $this->_request->getParam('id');
        }
        if ($id) {
            $object = Mage::getModel('sales/order_invoice')->load($id);
            if ($object && $object->getId()) {
                $store = $object->getStoreId();
                if (!$this->_role->hasStoreAccess($store)) {
                    $this->_forward();
                    return false;
                }
            }
        }
        return true;
    }

    /**
     * Validate Shipment view actions
     *
     * @param Mage_Adminhtml_Controller_Action $controller
     */
    public function validateSalesOrderShipmentViewAction($controller)
    {
        $id = $this->_request->getParam('shipment_id');
        if (!$id) {
            $id = $this->_request->getParam('id');
        }
        if ($id) {
            $object = Mage::getModel('sales/order_shipment')->load($id);
            if ($object && $object->getId()) {
                $store = $object->getStoreId();
                if (!$this->_role->hasStoreAccess($store)) {
                    $this->_forward();
                    return false;
                }
            }
        }
        return true;
    }

    /**
     * Validate Creditmemo creation actions
     *
     * @param Mage_Adminhtml_Controller_Action $controller
     */
    public function validateSalesOrderCreditmemoCreateAction($controller)
    {
        if ($id = $this->_request->getParam('order_id')) {
            $className = 'sales/order';
        } else if ($id = $this->_request->getParam('invoice_id')) {
            $className = 'sales/order_invoice';
        } else if ($id = $this->_request->getParam('creditmemo_id')) {
            $className = 'sales/order_creditmemo';
        } else {
            return true;
        }

        if ($id) {
            $object = Mage::getModel($className)->load($id);
            if ($object && $object->getId()) {
                $store = $object->getStoreId();
                if (!$this->_role->hasStoreAccess($store)) {
                    $this->_forward();
                    return false;
                }
            }
        }
        return true;
    }

    /**
     * Validate Invoice creation actions
     *
     * @param Mage_Adminhtml_Controller_Action $controller
     */
    public function validateSalesOrderInvoiceCreateAction($controller)
    {
        if ($id = $this->_request->getParam('order_id')) {
            $className = 'sales/order';
        } else if ($id = $this->_request->getParam('invoice_id')) {
            $className = 'sales/order_invoice';
        } else {
            return true;
        }

        if ($id) {
            $object = Mage::getModel($className)->load($id);
            if ($object && $object->getId()) {
                $store = $object->getStoreId();
                if (!$this->_role->hasStoreAccess($store)) {
                    $this->_forward();
                    return false;
                }
            }
        }
        return true;
    }

    /**
     * Validate Shipment creation actions
     *
     * @param Mage_Adminhtml_Controller_Action $controller
     */
    public function validateSalesOrderShipmentCreateAction($controller)
    {
        if ($id = $this->_request->getParam('order_id')) {
            $className = 'sales/order';
        } else if ($id = $this->_request->getParam('shipment_id')) {
            $className = 'sales/order_shipment';
        } else {
            return true;
        }

        if ($id) {
            $object = Mage::getModel($className)->load($id);
            if ($object && $object->getId()) {
                $store = $object->getStoreId();
                if (!$this->_role->hasStoreAccess($store)) {
                    $this->_forward();
                    return false;
                }
            }
        }
        return true;
    }

    /**
     * Validate Order mass actions
     *
     * @param Mage_Adminhtml_Controller_Action $controller
     */
    public function validateSalesOrderMassAction($controller)
    {
        if ($ids = $this->_request->getParam('order_ids', array())) {
            if ($ids && is_array($ids)) {
                foreach ($ids as $id) {
                    $object = Mage::getModel('sales/order')->load($id);
                    if ($object && $object->getId()) {
                        $store = $object->getStoreId();
                        if (!$this->_role->hasStoreAccess($store)) {
                            $this->_forward();
                            return false;
                        }
                    }
                }
            }
        }
        return true;
    }

    /**
     * Validate Order edit action
     *
     * @param Mage_Adminhtml_Controller_Action $controller
     */
    public function validateSalesOrderEditStartAction($controller)
    {
        $id = $this->_request->getParam('order_id');
        if ($id) {
            $object = Mage::getModel('sales/order')->load($id);
            if ($object && $object->getId()) {
                $store = $object->getStoreId();
                if (!$this->_role->hasStoreAccess($store)) {
                    $this->_forward();
                    return false;
                }
            }
        }
        return true;
    }

    /**
     * Validate Shipment tracking actions
     *
     * @param Mage_Adminhtml_Controller_Action $controller
     */
    public function validateSalesOrderShipmentTrackAction($controller)
    {
        $id = $this->_request->getParam('track_id');
        if ($id) {
            $object = Mage::getModel('sales/order_shipment_track')->load($id);
            if ($object && $object->getId()) {
                $store = $object->getStoreId();
                if (!$this->_role->hasStoreAccess($store)) {
                    $this->_forward();
                    return false;
                }
            }
        }
        return $this->validateSalesOrderShipmentCreateAction($controller);
    }

    /**
     * Validate Terms and Conditions management edit action
     *
     * @param Mage_Adminhtml_Controller_Action $controller
     */
    public function validateCheckoutAgreementEditAction($controller)
    {
        $id = $this->_request->getParam('id');
        if ($id) {
            $object = Mage::getModel('checkout/agreement')->load($id);
            if ($object && $object->getId()) {
                $stores = $object->getStoreId();
                foreach ($stores as $store) {
                    if ($store == 0 || !$this->_role->hasStoreAccess($store)) {
                        $this->_forward();
                        return false;
                    }
                }
            }
        }
        return true;
    }

    /**
     * Validate URL Rewrite Management edit action
     *
     * @param Mage_Adminhtml_Controller_Action $controller
     */
    public function validateUrlRewriteEditAction($controller)
    {
        $id = $this->_request->getParam('id');
        if ($id) {
            $object = Mage::getModel('core/url_rewrite')->load($id);
            if ($object && $object->getId()) {
                if (!$this->_role->hasStoreAccess($object->getStoreId())) {
                    $this->_forward();
                    return false;
                }
            }
        }
        return true;
    }

    /**
     * Validate Admin User management actions
     *
     * @param Mage_Adminhtml_Controller_Action $controller
     */
    public function validateAdminUserAction($controller)
    {
        $id = $this->_request->getParam('user_id');
        if ($id) {
            $limited = Mage::getResourceModel('enterprise_admingws/collections')
                ->getUsersOutsideLimitedScope(
                    $this->_role->getIsAll(),
                    $this->_role->getWebsiteIds(),
                    $this->_role->getStoreGroupIds()
                );

            if (in_array($id, $limited)) {
                $this->_forward();
                return false;
            }
        }
        return true;
    }

    /**
     * Validate Admin Role management actions
     *
     * @param Mage_Adminhtml_Controller_Action $controller
     */
    public function validateAdminRoleAction($controller)
    {
        $id = $this->_request->getParam('rid', $this->_request->getParam('role_id'));
        if ($id) {
            $limited = Mage::getResourceModel('enterprise_admingws/collections')
                ->getRolesOutsideLimitedScope(
                    $this->_role->getIsAll(),
                    $this->_role->getWebsiteIds(),
                    $this->_role->getStoreGroupIds()
                );
            if (in_array($id, $limited)) {
                $this->_forward();
                return false;
            }
        }
        return true;
    }

    /**
     * Validate Attribute management actions
     *
     * @param Mage_Adminhtml_Controller_Action $controller
     */
    public function validateCatalogProductAttributeActions($controller)
    {
        if (!$this->_role->getIsAll()) {
            $this->_forward();
            return false;
        }
        return true;
    }

    /**
     * Validate Attribute creation action
     *
     * @param Mage_Adminhtml_Controller_Action $controller
     */
    public function validateCatalogProductAttributeCreateAction($controller)
    {
        if (!$this->_role->getIsAll() && !$this->_request->getParam('attribute_id')) {
            $this->_forward();
            return false;
        }

        return true;
    }

    /**
     * Validate Products in Catalog Product MassDelete Action
     *
     * @param Mage_Adminhtml_Controller_Action $controller
     */
    public function catalogProductMassDeleteAction($controller)
    {
        $productIds             = $this->_request->getParam('product');
        $productNotExclusiveIds = array();
        $productExclusiveIds    = array();

        $resource = Mage::getResourceModel('catalog/product');

        $productsWebsites = $resource->getWebsiteIdsByProductIds($productIds);

        foreach ($productsWebsites as $productWebsites) {
            if (!$this->_role->hasExclusiveAccess(explode(',', $productWebsites['website_ids']))) {
                $productNotExclusiveIds[]  = $productWebsites['product_id'];
            } else {
                $productExclusiveIds[]     = $productWebsites['product_id'];
            }
        }

        if (!empty($productNotExclusiveIds)) {
            $productNotExclusiveIds = implode(', ', $productNotExclusiveIds);
            $helper = Mage::helper('enterprise_admingws');
            $message = $helper->__('Not enough permissions to delete this item(s): %s.', $productNotExclusiveIds);
            Mage::getSingleton('adminhtml/session')->addError($message);
        }

        $this->_request->setParam('product', $productExclusiveIds);
    }


    /**
     * Validate Attribute set creation, deletion and saving actions
     *
     * @param Mage_Adminhtml_Controller_Action $controller
     */
    public function validateAttributeSetActions($controller)
    {
        $this->_forward();
        return false;
    }

    /**
     * Validate permission for adding new sub category to specified parent id
     *
     * @param Mage_Adminhtml_Controller_Action $controller
     */
    protected function _validateCatalogSubCategoryAddPermission($categoryId)
    {
        $category = Mage::getModel('catalog/category')->load($categoryId);
        if ($category->getId()) {
            /**
             * viewing for parent category allowed and
             * user has exclusive access to root category
             * so we can allow user to add sub category
             */
            if ($this->_isCategoryAllowed($category)
                && $this->_role->hasExclusiveCategoryAccess($category->getPath())) {
                return true;
            }
        }

        return false;
    }

    /**
     * Validate applying rules action
     *
     * @param Mage_Adminhtml_Controller_Action $controller
     */
    public function validatePromoCatalogApplyRules($controller)
    {
        $this->_forward();
        return false;
    }

    /**
     * Block actions for all GWS limited users.
     *
     * @param Mage_Adminhtml_Controller_Action $controller
     * @return bool
     */
    public function blockStagingAction($controller)
    {
        $this->_forward();
        return false;
    }

    /**
     * Block index actions for all GWS limited users.
     *
     * @param Mage_Adminhtml_Controller_Action $controller
     * @return bool
     */
    public function blockIndexAction($controller)
    {
        $this->_forward();
        return false;
    }

    /**
     * Validate misc Manage Currency Rates requests
     *
     * @param Mage_Adminhtml_Controller_Action $controller
     */
    public function validateManageCurrencyRates($controller)
    {
        if (in_array($controller->getRequest()->getActionName(), array('fetchRates', 'saveRates'))) {
            $this->_forward();
            return false;
        }
    }

    /**
     * Validate misc Transactional Emails
     *
     * @param Mage_Adminhtml_Controller_Action $controller
     */
    public function validateTransactionalEmails($controller)
    {
        if (in_array($controller->getRequest()->getActionName(), array('delete', 'save', 'new'))) {
            $this->_forward();
            return false;
        }
    }

    /**
     * Block save action for all GWS limited users
     *
     * @return bool
     */
    public function blockCustomerGroupSave()
    {
        $this->_forward();
        return false;
    }

    /**
     * Block save and delete action for all GWS limited users
     *
     * @return bool
     */
    public function blockTaxChange()
    {
        $this->_forward();
        return false;
    }

    /**
     * Validate Giftregistry actions : edit, add, share, delete
     *
     * @param Mage_Adminhtml_Controller_Action $controller
     *
     * @return bool
     */
    public function validateGiftregistryEntityAction($controller)
    {
        $id = $this->_request->getParam('id', $this->_request->getParam('entity_id'));
        if ($id) {
            $websiteId = Mage::getModel('enterprise_giftregistry/entity')->getResource()->getWebsiteIdByEntityId($id);
            if (!in_array($websiteId, $this->_role->getWebsiteIds())) {
                $this->_forward();
                return false;
            }
        } else {
            $this->_forward();
            return false;
        }
        return true;
    }

    /**
     * Validate customer attribute actions
     *
     * @param Mage_Adminhtml_Controller_Action $controller
     * @return bool
     */
    public function validateCustomerAttributeActions($controller)
    {
        $actionName = $this->_request->getActionName();
        $attributeId = $this->_request->getParam('attribute_id');
        $websiteId = $this->_request->getParam('website');
        if (in_array($actionName, array('new', 'delete'))
            || (in_array($actionName, array('edit', 'save')) && !$attributeId)
            || ($websiteId && !$this->_role->hasWebsiteAccess($websiteId, true))) {
            $this->_forward();
            return false;
        }
        return true;
    }
}
