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
 * Increment resource model
 *
 * @category    Enterprise
 * @package     Enterprise_Cms
 * @author      Magento Core Team <core@magentocommerce.com>
 */

class Enterprise_Cms_Model_Mysql4_Increment extends Mage_Core_Model_Mysql4_Abstract
{
    /**
     * Constructor
     */
    protected function _construct()
    {
        $this->_init('enterprise_cms/increment', 'increment_id');
    }

    /**
     * Load increment counter by passed node and level
     *
     * @param Mage_Core_Model_Abstract $object
     * @param int $type
     * @param int $node
     * @param int $level
     * @return bool
     */
    public function loadByTypeNodeLevel(Mage_Core_Model_Abstract $object, $type, $node, $level)
    {
        $read = $this->_getReadAdapter();

        $select = $read->select()->from($this->getMainTable())
            ->forUpdate(true)
            ->where('type=?', $type)
            ->where('node=?', $node)
            ->where('level=?', $level);

        $data = $read->fetchRow($select);

        if (!$data) {
            return false;
        }

        $object->setData($data);

        $this->_afterLoad($object);

        return true;
    }

    /**
     * Remove unneeded increment record.
     *
     * @param int $type
     * @param int $node
     * @param int $level
     * @return Enterprise_Cms_Model_Mysql4_Increment
     */
    public function cleanIncrementRecord($type, $node, $level)
    {
        $write = $this->_getWriteAdapter();
        $write->delete($this->getMainTable(),
            array('type=?' => $type,
                'node=?' => $node,
                'level=?' => $level));

        return $this;
    }
}
