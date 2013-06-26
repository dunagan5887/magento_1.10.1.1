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

/**
 * Staging backup edit tabs
 *
 * @author     Magento Core Team <core@magentocommerce.com>
 */
class Enterprise_Staging_Block_Adminhtml_Backup_Edit_Tabs extends Mage_Adminhtml_Block_Widget_Tabs
{
    public function __construct()
    {
        parent::__construct();
        $this->setId('enterprise_staging_backup_tabs');
        $this->setDestElementId('enterprise_staging_backup_form');
        $this->setTitle(Mage::helper('enterprise_staging')->__('Websites Backup Information'));
    }

    /**
     * Preparing global layout
     *
     * @return Enterprise_Staging_Block_Manage_Staging_Edit_Tabs
     */
    protected function _prepareLayout()
    {
        $this->addTab('backup_general_info', array(
            'label'     => Mage::helper('enterprise_staging')->__('Backup General Info'),
            'content'   => $this->getLayout()
                ->createBlock('enterprise_staging/adminhtml_backup_edit_tabs_general')
                ->toHtml()
        ));

        $this->addTab('rollback', array(
            'label'     => Mage::helper('enterprise_staging')->__('Rollback'),
            'content'   => $this->getLayout()
                ->createBlock('enterprise_staging/adminhtml_backup_edit_tabs_rollback')
                ->toHtml()
        ));

        return parent::_prepareLayout();
    }
}
