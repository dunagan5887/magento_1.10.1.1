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
 * Cms page version collection
 *
 * @category    Enterprise
 * @package     Enterprise_Cms
 * @author      Magento Core Team <core@magentocommerce.com>
 */

class Enterprise_Cms_Model_Mysql4_Page_Version_Collection  extends Enterprise_Cms_Model_Mysql4_Page_Collection_Abstract
{
    /**
     * Constructor
     */
    protected function _construct()
    {
        parent::_construct();
        $this->_init('enterprise_cms/page_version');
    }

    /**
     * Add access level filter.
     * Can take parameter array or one level.
     *
     * @param mixed $level
     * @return Enterprise_Cms_Model_Mysql4_Version_Collection
     */
    public function addAccessLevelFilter($level)
    {
        if (is_array($level)) {
            $this->addFieldToFilter('access_level', array('in' => $level));
        } else {
            $this->addFieldToFilter('access_level', $level);
        }

        return $this;
    }

    /**
     * Prepare two dimensional array basing on version_id as key and
     * version label as value data from collection.
     *
     * @return array
     */
    public function getIdLabelArray()
    {
        return $this->_toOptionHash('version_id', 'version_label');
    }

    /**
     * Prepare two dimensional array basing on key and value field.
     *
     * @param string $keyField
     * @param string $valueField
     * @return array
     */
    public function getAsArray($keyField, $valueField)
    {
        $data = $this->_toOptionHash($keyField, $valueField);
        return array_filter($data);
    }

    /**
     * Join revision data by version id
     *
     * @return Enterprise_Cms_Model_Mysql4_Version_Collection
     */
    public function joinRevisions()
    {
        if (!$this->getFlag('revisions_joined')) {
            $this->getSelect()->joinLeft(
                array('rev_table' => $this->getTable('enterprise_cms/page_revision')),
                'rev_table.version_id=main_table.version_id', '*');

            $this->setFlag('revisions_joined');
        }
        return $this;
    }

    /**
     * Add order by version number in specified direction.
     *
     * @param string $dir
     * @return Enterprise_Cms_Model_Mysql4_Page_Version_Collection
     */
    public function addNumberSort($dir = 'desc')
    {
        $this->setOrder('version_number', $dir);

        return $this;
    }
}
