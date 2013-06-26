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
 * Admihtml Manage Cms Hierarchy Controller
 *
 * @category   Enterprise
 * @package    Enterprise_Cms
 */
class Enterprise_Cms_Adminhtml_Cms_HierarchyController extends Mage_Adminhtml_Controller_Action
{
    /**
     * Controller pre dispatch method
     *
     * @return Enterprise_Cms_HierarchyController
     */
    public function preDispatch()
    {
        parent::preDispatch();
        if (!Mage::helper('enterprise_cms/hierarchy')->isEnabled()) {
            if ($this->getRequest()->getActionName() != 'noroute') {
                $this->_forward('noroute');
            }
        }
        return $this;
    }

    /**
     * Load layout, set active menu and breadcrumbs
     *
     * @return Enterprise_Cms_HierarchyController
     */
    protected function _initAction()
    {
        $this->loadLayout()
            ->_setActiveMenu('cms/hierarchy')
            ->_addBreadcrumb(Mage::helper('enterprise_cms')->__('CMS'),
                Mage::helper('enterprise_cms')->__('CMS'))
            ->_addBreadcrumb(Mage::helper('enterprise_cms')->__('CMS Page Trees'),
                Mage::helper('enterprise_cms')->__('CMS Page Trees'));
        return $this;
    }

    /**
     * Edit Page Tree
     *
     */
    public function indexAction()
    {
        $this->_title($this->__('CMS'))
             ->_title($this->__('Pages'))
             ->_title($this->__('Manage Hierarchy'));

        $this->_getLockModel()->revalidate();

        if ($this->_getLockModel()->isLockedByMe()) {
            $this->_getSession()->addNotice(
                Mage::helper('enterprise_cms')->__('This Page is locked by you.')
            );
        }

        if ($this->_getLockModel()->isLockedByOther()) {
            $this->_getSession()->addNotice(
                Mage::helper('enterprise_cms')->__("This Page is locked by '%s'.", $this->_getLockModel()->getUserName())
            );
        }

        $node = Mage::getModel('enterprise_cms/hierarchy_node');

        $data = $this->_getSession()->getFormData(true);
        if (!empty($data)) {
            $node->addData($data);
        }

        Mage::register('current_hierarchy_node', $node);

        $this->_initAction()
            ->renderLayout();
    }

    /**
     * Lock page
     */
    public function lockAction()
    {
        $this->_getLockModel()->lock();
        $this->_redirect('*/*/');
    }

    /**
     * Save changes
     *
     */
    public function saveAction()
    {
        if ($this->getRequest()->isPost()) {
            if (Mage::getModel('enterprise_cms/hierarchy_lock')->isLockedByOther()) {
                $this->_getSession()->addError(
                    Mage::helper('enterprise_cms')->__('This page is currently locked.')
                );
                $this->_redirect('*/*/');
                return $this;
            }

            /** @var $node Enterprise_Cms_Model_Hierarchy_Node */
            $node       = Mage::getModel('enterprise_cms/hierarchy_node');
            $data       = $this->getRequest()->getPost();
            $hasError   = true;

            try {
                if (!empty($data['nodes_data'])) {
                    try{
                        $nodesData = Mage::helper('core')->jsonDecode($data['nodes_data']);
                    }catch (Zend_Json_Exception $e){
                        $nodesData = array();
                    }
                } else {
                    $nodesData = array();
                }
                if (!empty($data['removed_nodes'])) {
                    $removedNodes = explode(',', $data['removed_nodes']);
                } else {
                    $removedNodes = array();
                }

                // fill in meta_chapter and meta_section based on meta_chapter_section
                foreach ($nodesData as &$n) {
                    $n['meta_chapter'] = 0;
                    $n['meta_section'] = 0;
                    if (!isset($n['meta_chapter_section'])) {
                        continue;
                    }
                    if ($n['meta_chapter_section'] == 'both' || $n['meta_chapter_section'] == 'chapter') {
                        $n['meta_chapter'] = 1;
                    }
                    if ($n['meta_chapter_section'] == 'both' || $n['meta_chapter_section'] == 'section') {
                        $n['meta_section'] = 1;
                    }
                }

                $node->collectTree($nodesData, $removedNodes);

                $hasError = false;
                $this->_getSession()->addSuccess(
                    Mage::helper('enterprise_cms')->__('The hierarchy has been saved.')
                );
            }
            catch (Mage_Core_Exception $e) {
                $this->_getSession()->addError($e->getMessage());
            }
            catch (Exception $e) {
                $this->_getSession()->addException($e,
                    Mage::helper('enterprise_cms')->__('Error in saving hierarchy.')
                );
                Mage::logException($e);
            }

            if ($hasError) {
                //save data in session
                $this->_getSession()->setFormData($data);
            }
        }

        $this->_redirect('*/*/');
    }

    /**
     * Cms Pages Ajax Grid
     *
     */
    public function pageGridAction()
    {
        $this->loadLayout();
        $this->renderLayout();
    }

    /**
     * Return lock model instance
     *
     * @return Enterprise_Cms_Model_Hierarchy_Lock
     */
    protected function _getLockModel()
    {
        return Mage::getSingleton('enterprise_cms/hierarchy_lock');
    }

    /**
     * Check is allowed access to action
     *
     * @return bool
     */
    protected function _isAllowed()
    {
        return Mage::getSingleton('admin/session')->isAllowed('cms/hierarchy');
    }
}
