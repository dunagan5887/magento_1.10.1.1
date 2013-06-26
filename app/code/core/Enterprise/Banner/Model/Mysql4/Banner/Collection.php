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
 * @package     Enterprise_Banner
 * @copyright   Copyright (c) 2010 Magento Inc. (http://www.magentocommerce.com)
 * @license     http://www.magentocommerce.com/license/enterprise-edition
 */

class Enterprise_Banner_Model_Mysql4_Banner_Collection extends Mage_Core_Model_Mysql4_Collection_Abstract
{
    /**
     * Initialize banner resource model
     *
     */
    protected function _construct()
    {
        $this->_init('enterprise_banner/banner');
        $this->_map['fields']['banner_id'] = 'main_table.banner_id';
    }

    /**
     * Add stores column
     *
     * @param bool $printQuery
     * @param bool $logQuery
     * @return Enterprise_Banner_Model_Mysql4_Banner_Collection
     */
    protected function _afterLoad()
    {
        parent::_afterLoad();
        if ($this->getFlag('add_stores_column')) {
            $this->_addStoresVisibility();
        }
        $this->walk('getTypes'); // fetch banner types from comma-separated
        return $this;
    }

    /**
     * Set add stores column flag
     *
     * @return Enterprise_Banner_Model_Mysql4_Banner_Collection
     */
    public function addStoresVisibility()
    {
        $this->setFlag('add_stores_column', true);
        return $this;
    }

    /**
     * Collect and set stores ids to each collection item
     * Used in banners grid as Visible in column info
     *
     * @return Enterprise_Banner_Model_Mysql4_Banner_Collection
     */
    protected function _addStoresVisibility()
    {
        $bannerIds = $this->getColumnValues('banner_id');
        $bannersStores = array();
        if (sizeof($bannerIds)>0) {
            $select = $this->getConnection()->select()
                ->from($this->getTable('enterprise_banner/content'), array('store_id', 'banner_id'))
                ->where('banner_id IN(?)', $bannerIds);
            $bannersRaw = $this->getConnection()->fetchAll($select);

            foreach ($bannersRaw as $banner) {
                if (!isset($bannersStores[$banner['banner_id']])) {
                    $bannersStores[$banner['banner_id']] = array();
                }

                $bannersStores[$banner['banner_id']][] = $banner['store_id'];
            }
        }

        foreach ($this as $item) {
            if(isset($bannersStores[$item->getId()])) {
                $item->setStores($bannersStores[$item->getId()]);
            } else {
                $item->setStores(array());
            }
        }

        return $this;
    }

    /**
     * Add Filter by store
     *
     * @param int|array $storeIds
     * @param bool $withAdmin
     * @return Enterprise_Banner_Model_Mysql4_Banner_Collection
     */
    public function addStoreFilter($storeIds, $withAdmin = true)
    {
        if (!$this->getFlag('store_filter')) {
            if ($withAdmin) {
                $storeIds = array(0, $storeIds);
            }

            $this->getSelect()->join(
                array('store_table' => $this->getTable('enterprise_banner/content')),
                'main_table.banner_id = store_table.banner_id',
                array()
            )
            ->where('store_table.store_id in (?)', $storeIds)
            ->group('main_table.banner_id');

            $this->setFlag('store_filter', true);
        }
        return $this;
    }

    /**
     * Add filter by banners
     *
     * @param array $bannerIds
     * @param bool $exclude
     * @return Enterprise_Banner_Model_Mysql4_Banner_Collection
     */
    public function addBannerIdsFilter($bannerIds, $exclude = false)
    {
        $this->addFieldToFilter('main_table.banner_id', array(($exclude ? 'nin' : 'in') => $bannerIds));
        return $this;
    }
}
