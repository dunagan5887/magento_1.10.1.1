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
 * @package     Enterprise_Logging
 * @copyright   Copyright (c) 2010 Magento Inc. (http://www.magentocommerce.com)
 * @license     http://www.magentocommerce.com/license/enterprise-edition
 */

/**
 * Admin Actions Log Archive grid
 *
 */
class Enterprise_Logging_Block_Adminhtml_Archive_Grid extends Mage_Adminhtml_Block_Widget_Grid
{
    /**
     * Initialize default sorting and html ID
     */
    protected function _construct()
    {
        $this->setId('loggingArchiveGrid');
        $this->setDefaultSort('basename');
        $this->setDefaultDir('DESC');
        $this->setSaveParametersInSession(true);
        $this->setUseAjax(true);
    }

    /**
     * Prepare grid collection
     *
     * @return Enterprise_Logging_Block_Events_Archive_Grid
     */
    protected function _prepareCollection()
    {
        $this->setCollection(Mage::getSingleton('enterprise_logging/archive_collection'));
        return parent::_prepareCollection();
    }

    /**
     * Prepare grid columns
     *
     * @return Enterprise_Logging_Block_Events_Archive_Grid
     */
    protected function _prepareColumns()
    {
        $downloadUrl = $this->getUrl('*/*/download');

        $this->addColumn('download', array(
            'header'    => Mage::helper('enterprise_logging')->__('Archive File'),
            'format'    => '<a href="' . $downloadUrl .'basename/$basename/">$basename</a>',
            'index'     => 'basename',
        ));

        $this->addColumn('date', array(
            'header'    => Mage::helper('enterprise_logging')->__('Date'),
            'type'      => 'date',
            'index'     => 'time',
            'filter'    => 'enterprise_logging/adminhtml_archive_grid_filter_date'
        ));

        return parent::_prepareColumns();
    }

    /**
     * Row click callback URL
     *
     * @return string
     */
    public function getGridUrl()
    {
        return $this->getUrl('*/*/archiveGrid', array('_current' => true));
    }
}
