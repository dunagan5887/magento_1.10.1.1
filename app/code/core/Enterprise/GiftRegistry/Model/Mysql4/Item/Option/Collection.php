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
 * @package     Enterprise_GiftRegistry
 * @copyright   Copyright (c) 2010 Magento Inc. (http://www.magentocommerce.com)
 * @license     http://www.magentocommerce.com/license/enterprise-edition
 */

/**
 * Gift registry item option collection
 *
 * @category    Enterprise
 * @package     Enterprise_GiftRegistry
 * @author      Magento Core Team <core@magentocommerce.com>
 */
class Enterprise_GiftRegistry_Model_Mysql4_Item_Option_Collection extends Mage_Core_Model_Mysql4_Collection_Abstract
{
    /**
     * List of option IDs grouped by item ID
     *
     * @var array
     */
    protected $_optionsByItem = array();

    /**
     * List of option IDs grouped by product ID
     *
     * @var array
     */
    protected $_optionsByProduct = array();

    /**
     * Internal constructor
     * Defines resource model for collection
     */
    protected function _construct()
    {
        $this->_init('enterprise_giftregistry/item_option');
    }

    /**
     * After load processing
     * Fills the lists of options grouped by item and product
     *
     * @return Enterprise_GiftRegistry_Model_Mysql4_Item_Option_Collection
     */
    protected function _afterLoad()
    {
        parent::_afterLoad();

        foreach ($this as $option) {
            $optionId = $option->getId();
            $itemId = $option->getItemId();
            $productId = $option->getProductId();
            if (isset($this->_optionsByItem[$itemId])) {
                $this->_optionsByItem[$itemId][] = $optionId;
            } else {
                $this->_optionsByItem[$itemId] = array($optionId);
            }
            if (isset($this->_optionsByProduct[$productId])) {
                $this->_optionsByProduct[$productId][] = $optionId;
            } else {
                $this->_optionsByProduct[$productId] = array($optionId);
            }
        }

        return $this;
    }

    /**
     * Apply gift registry item(s) filter to collection
     *
     * @param   int|array $item
     * @return  Enterprise_GiftRegistry_Model_Mysql4_Item_Option_Collection
     */
    public function addItemFilter($item)
    {
        if (empty($item)) {
            $this->_totalRecords = 0;
            $this->_setIsLoaded(true);
        } else if (is_array($item)) {
            $this->addFieldToFilter('item_id', array('in' => $item));
        } else if ($item instanceof Enterprise_GiftRegistry_Model_Item) {
            $this->addFieldToFilter('item_id', $item->getId());
        } else {
            $this->addFieldToFilter('item_id', $item);
        }

        return $this;
    }

    /**
     * Retrieve the list of all product IDs
     *
     * @return array
     */
    public function getProductIds()
    {
        $this->load();

        return array_keys($this->_optionsByProduct);
    }

    /**
     * Retrieve all options related to the specified gift registry item
     *
     * @param   mixed $item
     * @return  array
     */
    public function getOptionsByItem($item)
    {
        if ($item instanceof Enterprise_GiftRegistry_Model_Item) {
            $itemId = $item->getId();
        } else {
            $itemId = $item;
        }

        $this->load();

        $options = array();
        if (isset($this->_optionsByItem[$itemId])) {
            foreach ($this->_optionsByItem[$itemId] as $optionId) {
                $options[] = $this->_items[$optionId];
            }
        }

        return $options;
    }

    /**
     * Retrieve all options related to the specified product
     *
     * @param   mixed $product
     * @return  array
     */
    public function getOptionsByProduct($product)
    {
        if ($product instanceof Mage_Catalog_Model_Product) {
            $productId = $product->getId();
        } else {
            $productId = $product;
        }

        $this->load();

        $options = array();
        if (isset($this->_optionsByProduct[$productId])) {
            foreach ($this->_optionsByProduct[$productId] as $optionId) {
                $options[] = $this->_items[$optionId];
            }
        }

        return $options;
    }
}
