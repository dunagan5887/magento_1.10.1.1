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
 * Revision selector
 *
 * @category   Enterprise
 * @package    Enterprise_Cms
 * @author      Magento Core Team <core@magentocommerce.com>
 */
class Enterprise_Cms_Block_Adminhtml_Cms_Page_Preview_Revision extends Mage_Adminhtml_Block_Template
{
    /**
     * Retrieve id of currently selected revision
     *
     * @return int
     */
    public function getRevisionId()
    {
        if (!$this->hasRevisionId()) {
            $this->setData('revision_id', (int)$this->getRequest()->getPost('preview_selected_revision'));
        }
        return $this->getData('revision_id');
    }

    /**
     * Prepare array with revisions sorted by versions
     *
     * @return array
     */
    public function getRevisions()
    {
        /* var $collection Enterprise_Cms_Model_Mysql4_Revision_Collection */
        $collection = Mage::getModel('enterprise_cms/page_revision')->getCollection()
            ->addPageFilter($this->getRequest()->getParam('page_id'))
            ->joinVersions()
            ->addNumberSort()
            ->addVisibilityFilter(Mage::getSingleton('admin/session')->getUser()->getId(),
                Mage::getSingleton('enterprise_cms/config')->getAllowedAccessLevel());

        $revisions = array();

        foreach ($collection->getItems() as $item) {
            if (isset($revisions[$item->getVersionId()])) {
                $revisions[$item->getVersionId()]['revisions'][] = $item;
            } else {
                $revisions[$item->getVersionId()] = array(
                    'revisions' => array($item),
                    'label' => ($item->getLabel() ? $item->getLabel() : $this->__('N/A'))
                );
            }
        }
        krsort($revisions);
        reset($revisions);
        return $revisions;
    }
}
