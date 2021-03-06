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
 * @category    Mage
 * @package     Mage_Reports
 * @copyright   Copyright (c) 2010 Magento Inc. (http://www.magentocommerce.com)
 * @license     http://www.magentocommerce.com/license/enterprise-edition
 */


/**
 * Reports Product Index Abstract Product Resource Collection
 *
 * @category   Mage
 * @package    Mage_Reports
 * @author     Magento Core Team <core@magentocommerce.com>
 */
abstract class Mage_Reports_Model_Mysql4_Product_Index_Collection_Abstract
    extends Mage_Catalog_Model_Resource_Eav_Mysql4_Product_Collection
{
    /**
     * Retrieve Product Index table name
     *
     * @return string
     */
    abstract protected function _getTableName();

    /**
     * Join index table
     */
    protected function _joinIdxTable()
    {
        if (!$this->getFlag('is_idx_table_joined')) {
            $this->joinTable(
                array('idx_table' => $this->_getTableName()),
                'product_id=entity_id',
                array(
                    'product_id'    => 'product_id',
                    'item_store_id' => 'store_id',
                    'added_at'      => 'added_at'
                ),
                $this->_getWhereCondition()
            );
            $this->setFlag('is_idx_table_joined', true);
        }
        return $this;
    }

    /**
     * Add Viewed Products Index to Collection
     *
     * @return Mage_Reports_Model_Mysql4_Product_Index_Collection_Abstract
     */
    public function addIndexFilter()
    {
        $this->_joinIdxTable();
        $this->_productLimitationFilters['store_table'] = 'idx_table';
        $this->setFlag('url_data_object', true);
        $this->setFlag('do_not_use_category_id', true);
        return $this;
    }

    /**
     * Add filter by product ids
     * @param array $ids
     */
    public function addFilterByIds($ids)
    {
        if (empty($ids)) {
            $this->getSelect()->where('0');
        } else {
            $this->getSelect()->where('e.entity_id IN(?)', $ids);
        }
        return $this;
    }

    /**
     * Retrieve Where Condition to Index table
     *
     * @return array
     */
    protected function _getWhereCondition()
    {
        $condition = array();

        if (Mage::getSingleton('customer/session')->isLoggedIn()) {
            $condition['customer_id'] = Mage::getSingleton('customer/session')->getCustomerId();
        }
        else {
            $condition['visitor_id'] = Mage::getSingleton('log/visitor')->getId();
        }

        return $condition;
    }

    /**
     * Add order by "added at"
     *
     * @param string $dir
     * @return Mage_Reports_Model_Mysql4_Product_Index_Collection_Abstract
     */
    public function setAddedAtOrder($dir = 'desc')
    {
        if ($this->getFlag('is_idx_table_joined')) {
            $this->getSelect()->order('added_at '.$dir);
        }
        return $this;
    }

    /**
     * Add exclude Product Ids
     *
     * @param int|array $productIds
     * @return Mage_Reports_Model_Mysql4_Product_Index_Collection_Abstract
     */
    public function excludeProductIds($productIds)
    {
        if (empty($productIds)) {
            return $this;
        }
        $this->_joinIdxTable();
        $this->getSelect()->where('idx_table.product_id NOT IN(?)', $productIds);
        return $this;
    }
}
