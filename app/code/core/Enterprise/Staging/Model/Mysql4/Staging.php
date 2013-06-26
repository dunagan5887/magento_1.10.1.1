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


class Enterprise_Staging_Model_Mysql4_Staging extends Mage_Core_Model_Mysql4_Abstract
{
    protected function _construct()
    {
        $this->_init('enterprise_staging/staging', 'staging_id');
    }

    /**
     * Add website name to select
     *
     * @param   string $field
     * @param   mixed $value
     * @param   object $object
     *
     * @return  Zend_Db_Select
     */
    protected function _getLoadSelect($field, $value, $object)
    {
        $select = parent::_getLoadSelect($field, $value, $object);
        $select->joinLeft(
            array('site'=>$this->getTable('core/website')),
            "staging_website_id = site.website_id",
            array('name' => 'site.name')
        );
        return $select;
    }

    /**
     * Before save processing
     *
     * @param Varien_Object $object
     */
    protected function _beforeSave(Mage_Core_Model_Abstract $object)
    {
        $object->setUpdatedAt($this->formatDate(time()));
        if (!$object->getId()) {
            $object->setCreatedAt($object->getUpdatedAt());
        }

        parent::_beforeSave($object);

        return $this;
    }

    public function saveItems($staging)
    {
        foreach ($staging->getItemsCollection() as $item) {
            $item->save();
        }

        return $this;
    }

    /**
     * Validate all object's attributes against configuration
     *
     * @param Enterprise_Staging_Model_Staging $staging
     * @return Enterprise_Staging_Model_Mysql4_Staging
     */
    public function validate($staging)
    {
        return $this;
    }

    /**
     * Update specific attribute value (set new value back in given model)
     *
     * @param Enterprise_Staging_Model_Staging $staging
     * @param string $attribute
     * @param mixed  $value
     *
     * @return Enterprise_Staging_Model_Mysql4_Staging_Website
     */
    public function updateAttribute($staging, $attribute, $value)
    {
        if (!$stagingId = (int)$staging->getId()) {
            return $this;
        }
        $whereSql = "staging_id = {$stagingId}";
        $this->_getWriteAdapter()
           ->update($this->getMainTable(), array($attribute => $value), $whereSql);
       $staging->setData($attribute, $value);
       return $this;
    }

    /**
     * Return websites with processing status
     *
     * @return array
     */
    public function getProcessingWebsites()
    {
        $select = $this->_getReadAdapter()->select()->from($this->getMainTable(), array('staging_website_id'))
            ->where("status = ?", Enterprise_Staging_Model_Staging_Config::STATUS_STARTED);
        return $this->_getReadAdapter()->fetchAll($select);
    }

    /**
     * get bool result if website in processing now
     *
     * @param int $currentWebsiteId
     * @return bool
     */
    public function isWebsiteInProcessing($currentWebsiteId)
    {
        $select = $this->_getReadAdapter()->select()->from($this->getMainTable(), array('COUNT(*)'))
            ->where("status = ?", Enterprise_Staging_Model_Staging_Config::STATUS_STARTED)
            ->where("staging_website_id = " . $currentWebsiteId);

        $result = (int) $this->_getReadAdapter()->fetchOne($select);
        if ($result > 0) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Create Staging Website with all relatives
     *
     * @param   object Enterprise_Staging_Model_Staging $staging
     * @param   object Enterprise_Staging_Model_Staging_Event $event
     *
     * @return  object Enterprise_Staging_Model_Mysql4_Staging
     */
    public function createRun($staging, $event = null)
    {
        Mage::getResourceModel('enterprise_staging/adapter_website')
            ->createRun($staging, $event);

        Mage::getResourceModel('enterprise_staging/adapter_group')
            ->createRun($staging, $event);

        Mage::getResourceModel('enterprise_staging/adapter_store')
            ->createRun($staging, $event);

        Mage::getResourceModel('enterprise_staging/adapter_item')
            ->createRun($staging, $event);

        $this->_processStagingItemsCallback('createRun', $staging, $event);

        return $this;
    }

    /**
     * Update Staging Website
     *
     * @param   object Enterprise_Staging_Model_Staging $staging
     * @param   object Enterprise_Staging_Model_Staging_Event $event
     *
     * @return  Enterprise_Staging_Model_Staging_Action_Run
     */
    public function updateRun($staging, $event = null)
    {
        Mage::getResourceModel('enterprise_staging/adapter_website')
            ->updateRun($staging, $event);

        return $this;
    }

    /**
     * Run Backup default tables before merge
     *
     * @param   object Enterprise_Staging_Model_Staging $staging
     * @param   object Enterprise_Staging_Model_Staging_Event $event
     *
     * @return  Enterprise_Staging_Model_Staging_Action_Run
     */
    public function backupRun($staging, $event = null)
    {
        $this->_processStagingItemsCallback('backupRun', $staging, $event);

        Mage::getModel('enterprise_staging/staging_action')->saveOnBackupRun($staging, $event);

        return $this;
    }

    /**
     * Run Staging Website Merge
     *
     * @param   object Enterprise_Staging_Model_Staging $staging
     * @param   object Enterprise_Staging_Model_Staging_Event $event
     *
     * @return  Enterprise_Staging_Model_Staging_Action_Run
     */
    public function mergeRun($staging, $event = null)
    {
        if ($staging->getIsMergeLater()) {
            return $this;
        }

        $this->_processStagingItemsCallback('mergeRun', $staging, $event);

        return $this;
    }

    /**
     * Run Staging Website Unschedule
     *
     * @param   object Enterprise_Staging_Model_Staging $staging
     * @param   object Enterprise_Staging_Model_Staging_Event $event
     *
     * @return  Enterprise_Staging_Model_Staging_Action_Run
     */
    public function unscheduleMergeRun($staging, $event = null)
    {
        return $this;
    }

    /**
     * Run Staging Website Reset
     *
     * @param   object Enterprise_Staging_Model_Staging $staging
     * @param   object Enterprise_Staging_Model_Staging_Event $event
     *
     * @return  Enterprise_Staging_Model_Staging_Action_Run
     */
    public function resetRun($staging, $event = null)
    {
        return $this;
    }

    /**
     * Run Staging Website Rollback
     *
     * @param   object Enterprise_Staging_Model_Staging $staging
     * @param   object Enterprise_Staging_Model_Staging_Event $event
     *
     * @return Enterprise_Staging_Model_Staging_Action_Run
     */
    public function rollbackRun($staging, $event = null)
    {
        $this->_processStagingItemsCallback('rollbackRun', $staging, $event, true);
        return $this;
    }

    /**
     * Run validate backend tables for frontend usage within Staging Website navigation
     *
     * @param   object Enterprise_Staging_Model_Staging $staging
     * @return  Enterprise_Staging_Model_Staging_Action_Run
     */
    public function checkfrontendRun($staging)
    {
        if (Mage::registry('staging/frontend_checked_started')) {
            return $this;
        }
        Mage::register('staging/frontend_checked_started', true);

        $stagingItems = Mage::getSingleton('enterprise_staging/staging_config')->getStagingItems();
        foreach ($stagingItems as $stagingItem) {
            if (!$stagingItem->is_backend) {
                continue;
            }

            $adapter = $this->getItemAdapterInstanse($stagingItem);
            $adapter->checkfrontendRun($staging);

            if ($stagingItem->extends) {
                foreach ($stagingItem->extends->children() as $extendItem) {
                    if (!$extendItem->is_backend) {
                        continue;
                    }
                    if ((string)$extendItem->use_storage_method !== 'table_prefix') {
                        continue;
                    }
                    $adapter = $this->getItemAdapterInstanse($extendItem);
                    $adapter->checkfrontendRun($staging);
                }
            }
        }

        Mage::unregister('staging/frontend_checked_started');
        Mage::getSingleton("core/session")->setData('staging_frontend_website_is_checked', true);
        return $this;
    }

    /**
     * Retrieve item resource adapter instance
     *
     * @param Varien_Simplexml_Element $itemXmlConfig
     *
     * @return Enterprise_Staging_Model_Staging_Adapter_Item_Abstract
     */
    public function getItemAdapterInstanse($itemXmlConfig)
    {
        if ($itemXmlConfig) {
            $resourceAdapterName = (string) $itemXmlConfig->resource_adapter;
            if (!$resourceAdapterName) {
                $resourceAdapterName = 'enterprise_staging/adapter_item_default';
            }
            $resourceAdapter = Mage::getResourceModel($resourceAdapterName);
            if ($resourceAdapter) {
                $resourceAdapter->setConfig($itemXmlConfig);
                return $resourceAdapter;
            }
        }
        throw new Enterprise_Staging_Exception(Mage::helper('enterprise_staging')->__('Wrong item resource adapter model.'));
    }

    protected function _processStagingItemsCallback($callback, $staging, $event = null, $ignoreExtends = false)
    {
        $stagingItems = $staging->getMapperInstance()->getStagingItems();
        foreach ($stagingItems as $stagingItem) {
            $adapter = $this->getItemAdapterInstanse($stagingItem);
            $adapter->{$callback}($staging, $event);
            if ($ignoreExtends) {
                continue;
            }
            if ($stagingItem->extends) {
                foreach ($stagingItem->extends->children() as $extendItem) {
                    if (!Mage::getSingleton('enterprise_staging/staging_config')->isItemModuleActive($extendItem)) {
                         continue;
                    }
                    $adapter = $this->getItemAdapterInstanse($extendItem);
                    $adapter->{$callback}($staging, $event);
                }
            }
        }
        return $this;
    }
}
