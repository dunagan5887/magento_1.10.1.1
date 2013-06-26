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
 * Enterprise Staging Observer class.
 */
class Enterprise_Staging_Model_Observer
{
    /**
     * Get staging table name for the entities while staging website browse
     *
     * @param $observer Varien_Object
     *
     */
    public function getTableName($observer)
    {
        if (!Mage::app()->isInstalled()) {
            return $this;
        }
        if (Mage::app()->getStore()->isAdmin()) {
            return $this;
        }
        if (Mage::registry('staging/frontend_checked_started')) {
            return $this;
        }

        try {
            $resource    = $observer->getEvent()->getResource();
            $tableName   = $observer->getEvent()->getTableName();
            $modelEntity = $observer->getEvent()->getModelEntity();
            $website     = Mage::app()->getWebsite();
            if ($website->getIsStaging()) {
                $_tableName = Mage::getSingleton('enterprise_staging/staging_config')
                    ->getStagingFrontendTableName($tableName, $modelEntity, $website);
                if ($_tableName) {
                    $resource->setMappedTableName($tableName, $_tableName);
                }
            }
        } catch (Enterprise_Staging_Exception $e) {
            Mage::throwException(Mage::helper('enterprise_staging')->__('Cannot run the staging website.'));
        }
    }

    /**
     * observer execute before frontend init
     *
     */
    public function beforeFrontendInit($observer)
    {
        if (Mage::app()->getStore()->isAdmin()) {
            return $this;
        }
        $website = Mage::app()->getWebsite();
        if ($website->getIsStaging()) {
            $staging = Mage::getModel('enterprise_staging/staging');
            $staging->loadByStagingWebsiteId($website->getId());

            try {
                $defaultWebsite = Mage::app()->getWebsite(true);
                if ($defaultWebsite) {
                    $defaultStore = $defaultWebsite->getDefaultStore();
                }
                if ($defaultStore) {
                    $baseUrl = $defaultStore->getConfig('web/unsecure/base_url');
                } else {
                    $baseUrl = '/';
                }
            } catch (Exception $e) {
                $baseUrl = '/';
            }

            if (!$staging->getId()) {
                Mage::app()->getResponse()->setRedirect($baseUrl)->sendResponse();
                return $this;
            }

            switch ($website->getVisibility()) {
                case Enterprise_Staging_Model_Staging_Config::VISIBILITY_NOT_ACCESSIBLE :
                    Mage::app()->getResponse()->setRedirect($baseUrl)->sendResponse();
                    exit();
                    break;
                case Enterprise_Staging_Model_Staging_Config::VISIBILITY_ACCESSIBLE :

                    break;
                case Enterprise_Staging_Model_Staging_Config::VISIBILITY_REQUIRE_HTTP_AUTH :
                    $this->_checkHttpAuth();
                    break;
            }
        }

        return $this;
    }

    /**
     * check http auth on staging website loading
     *
     */
    protected function _checkHttpAuth()
    {
        $website = Mage::app()->getWebsite();

        try {
            if (empty($_SERVER['PHP_AUTH_USER']) || empty($_SERVER['PHP_AUTH_PW'])) {
                throw new Exception('Staging Website is Unauthorized.');
            }

            $login      = $_SERVER['PHP_AUTH_USER'];
            if ($website->getMasterLogin() != $login) {
                throw new Exception('Invalid login.');
            }

            $password   = $_SERVER['PHP_AUTH_PW'];
            if (Mage::helper('core')->decrypt($website->getMasterPassword()) != $password) {
                throw new Exception('Invalid password.');
            }
        } catch (Exception $e) {
            header('WWW-Authenticate: Basic realm="'.$e->getMessage().'"');
            header('HTTP/1.0 401 Unauthorized');
            exit();
        }
    }

    /**
     * automate/crontab processing, check and execute all scheduled actions
     *
     */
    public function automates()
    {
        try {
            $currentDate = Mage::getModel('core/date')->gmtDate();
            $collection  = Mage::getResourceModel('enterprise_staging/staging_collection')
                ->addIsSheduledToFilter();

            foreach ($collection as $staging) {
                $applyDate = $staging->getMergeSchedulingDate();
                if ($currentDate >= $applyDate) {
                    $mapData = $staging->getMergeSchedulingMap();
                    if (!empty($mapData)) {
                        $mapper = $staging->getMapperInstance()->unserialize($mapData);
                        if ($mapper->getIsBackup()) {
                            $staging->backup();
                        }
                        $staging->setIsMegreByCron(true);
                        $staging->merge();
                    }
                }
            }
        } catch (Enterprise_Staging_Exception $e) {}
    }

    /**
     * perform action on slave website delete
     *
     * @param Enterprise_Staging_Model_Observer $observer
     * @return Enterprise_Staging_Model_Observer
     */
    public function deleteWebsite($observer)
    {
        try {
            $website = $observer->getEvent()->getWebsite();
            $websiteId = $website->getId();
            $_website = Mage::app()->getWebsite($websiteId);

            if (!$_website || !$_website->getIsStaging()) {
                return $this;
            }

            $collection = Mage::getResourceModel('enterprise_staging/staging_collection')
                ->addStagingWebsiteToFilter($_website->getId());

            foreach ($collection as $staging) {
                Mage::dispatchEvent('enterprise_staging_controller_staging_delete', array('staging'=>$staging));
                $staging->delete();
            }

        } catch (Exception $e) {

        }

        return $this;
    }

    /**
     * Take down entire frontend if required
     *
     * @param Varien_Event_Observer $observer
     */
    public function takeFrontendDown($observer)
    {
        $result = $observer->getEvent()->getResult();
        if ($result->getShouldProceed() && (bool)Mage::getStoreConfig('general/content_staging/block_frontend')) {

            $currentSiteId = Mage::app()->getWebsite()->getId();

            // check whether frontend should be down
            $isNeedToDisable = false;

            if ((int)Mage::getStoreConfig('general/content_staging/block_frontend')===1) {
                $eventProcessingSites = Mage::getResourceModel('enterprise_staging/staging')
                    ->getProcessingWebsites();
                if (count($eventProcessingSites)>0){
                    $isNeedToDisable = true;
                }
            }

            if ((int)Mage::getStoreConfig('general/content_staging/block_frontend')===2) {
                 $isNeedToDisable = Mage::getResourceModel('enterprise_staging/staging')
                    ->isWebsiteInProcessing($currentSiteId);
            }

            if ($isNeedToDisable===true) {
                // take the frontend down

                $controller = $observer->getEvent()->getController();

                if ($controller->getFullActionName() !== 'staging_index_stub') {
                    $controller->getRequest()
                        ->setModuleName('staging')
                        ->setControllerName('index')
                        ->setActionName('stub')
                        ->setDispatched(false);
                    $controller->getResponse()->setHeader('HTTP/1.1','503 Service Unavailable');
                    $result->setShouldProceed(false);
                }
            }
            return $this;
        }
    }
}
