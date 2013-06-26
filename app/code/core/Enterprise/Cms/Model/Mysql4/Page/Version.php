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
 * Cms page version resource model
 *
 * @category    Enterprise
 * @package     Enterprise_Cms
 * @author      Magento Core Team <core@magentocommerce.com>
 */

class Enterprise_Cms_Model_Mysql4_Page_Version extends Mage_Core_Model_Mysql4_Abstract
{
    /**
     * Constructor
     */
    protected function _construct()
    {
        $this->_init('enterprise_cms/page_version', 'version_id');
    }

    /**
     * Checking if version id not last public for its page
     *
     * @param Mage_Core_Model_Abstract $object
     * @return bool
     */
    public function isVersionLastPublic(Mage_Core_Model_Abstract $object)
    {
        $select = $this->_getReadAdapter()->select();
        $select->from($this->getMainTable(), 'count(*)')
            ->where('page_id = ?', $object->getPageId())
            ->where('access_level = ?', Enterprise_Cms_Model_Page_Version::ACCESS_LEVEL_PUBLIC)
            ->where('version_id <> ? ', $object->getVersionId());

        return !$this->_getReadAdapter()->fetchOne($select);
    }

    /**
     * Checking if Version does not contain published revision
     *
     * @param Mage_Core_Model_Abstract $object
     * @return bool
     */
    public function isVersionHasPublishedRevision(Mage_Core_Model_Abstract $object)
    {

        $select = $this->_getReadAdapter()->select();
        $select->from(array('p' => $this->getTable('cms/page')), array())
            ->where('p.page_id = ?', $object->getPageId())
            ->join(array('r' => $this->getTable('enterprise_cms/page_revision')),
                'r.revision_id = p.published_revision_id', array('r.version_id'));

        $result = $this->_getReadAdapter()->fetchOne($select);

        return $result == $object->getVersionId();
    }

    /**
     * Add access restriction filters to allow load only by granted user.
     *
     * @param Zend_Db_Select $select
     * @param int $accessLevel
     * @param int $userId
     * @return Zend_Db_Select
     */
    protected function _addAccessRestrictionsToSelect($select, $accessLevel, $userId)
    {
        $conditions = array('user_id = ' . $userId);

        if (is_array($accessLevel) && !empty($accessLevel)) {
            $conditions[] = 'access_level in ("' . implode('","', $accessLevel) . '")';
        } else if ($accessLevel) {
            $conditions[] = 'access_level = "' . $accessLevel . '"';
        } else {
            $conditions[] = 'access_level = ""';
        }

        $conditions = implode(' OR ', $conditions);

        $select->where($conditions);

        return $select;
    }

    /**
     * Loading data with extra access level checking.
     *
     * @param Enterprise_Cms_Model_Page_Version $object
     * @param array|string $accessLevel
     * @param int $userId
     * @param int|string $value
     * @param string|null $field
     * @return Enterprise_Cms_Model_Page_Version
     */
    public function loadWithRestrictions($object, $accessLevel, $userId, $value, $field = null)
    {
        if (is_null($field)) {
            $field = $this->getIdFieldName();
        }

        $read = $this->_getReadAdapter();
        if ($read && $value) {
            $select = $this->_getLoadSelect($field, $value, $object);

            $select = $this->_addAccessRestrictionsToSelect($select, $accessLevel, $userId);

            $data = $read->fetchRow($select);
            if ($data) {
                $object->setData($data);
            }
        }

        $this->_afterLoad($object);
        return $this;
    }
}
