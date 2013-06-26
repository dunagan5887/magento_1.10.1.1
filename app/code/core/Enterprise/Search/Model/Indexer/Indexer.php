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
 * @package     Enterprise_Search
 * @copyright   Copyright (c) 2010 Magento Inc. (http://www.magentocommerce.com)
 * @license     http://www.magentocommerce.com/license/enterprise-edition
 */

 /**
 * Enterprise search model indexer
 *
 *
 * @category   Enterprise
 * @package    Enterprise_Search
 * @author     Magento Core Team <core@magentocommerce.com>
 */
class Enterprise_Search_Model_Indexer_Indexer
{
    /**
     * Change indexes status to defined
     *
     * @param   string|array $indexList
     * @param   string $status
     * @return  Enterprise_Search_Model_Indexer_Indexer
     */
    protected function _changeIndexesStatus($indexList, $status)
    {
        $indexer = Mage::getSingleton('index/indexer');

        if (!is_array($indexList)) {
            $indexList = array($indexList);
        }

        foreach ($indexList as $index) {
            $indexer->getProcessByCode($index)
                ->changeStatus($status);
        }

        return $this;
    }

    public function reindexAll()
    {
        $helper = Mage::helper('enterprise_search');
        if ($helper->isThirdPartSearchEngine() && $helper->isActiveEngine()) {
            $indexList = array('catalogsearch_fulltext', 'catalog_category_product');

            /* Change indexes status to running */
            $this->_changeIndexesStatus(
                $indexList,
                Mage_Index_Model_Process::STATUS_RUNNING
            );

            Mage::getSingleton('catalogsearch/indexer_fulltext')->reindexAll();

            /* Refresh indexes status after reindex process is completed */
            $this->_changeIndexesStatus(
                $indexList,
                Mage_Index_Model_Process::STATUS_PENDING
            );
        }
    }
}
