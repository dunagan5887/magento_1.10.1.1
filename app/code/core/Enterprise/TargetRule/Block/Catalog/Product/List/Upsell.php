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
 * @package     Enterprise_TargetRule
 * @copyright   Copyright (c) 2010 Magento Inc. (http://www.magentocommerce.com)
 * @license     http://www.magentocommerce.com/license/enterprise-edition
 */


/**
 * TargetRule Catalog Product List Upsell Block
 *
 * @category   Enterprise
 * @package    Enterprise_TargetRule
 */
class Enterprise_TargetRule_Block_Catalog_Product_List_Upsell
    extends Enterprise_TargetRule_Block_Catalog_Product_List_Abstract
{
    /**
     * Retrieve Catalog Product List Type identifier
     *
     * @return int
     */
    public function getType()
    {
        return Enterprise_TargetRule_Model_Rule::UP_SELLS;
    }

    /**
     * Retrieve related product collection assigned to product
     *
     * @throws Mage_Core_Exception
     * @return Mage_Catalog_Model_Resource_Eav_Mysql4_Product_Collection
     */
    public function getLinkCollection()
    {
        if (is_null($this->_linkCollection)) {
            parent::getLinkCollection();
            /**
             * Updating collection with desired items
             */
            Mage::dispatchEvent('catalog_product_upsell', array(
                'product'       => $this->getProduct(),
                'collection'    => $this->_linkCollection,
                'limit'         => $this->getPositionLimit()
            ));
        }

        return $this->_linkCollection;
    }
}
