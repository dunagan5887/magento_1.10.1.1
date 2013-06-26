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
 * @package     Mage_Sales
 * @copyright   Copyright (c) 2010 Magento Inc. (http://www.magentocommerce.com)
 * @license     http://www.magentocommerce.com/license/enterprise-edition
 */

/**
 * Flat sales order abstract comments collection, used as parent for: invoice, shipment, creditmemo
 *
 */
abstract class Mage_Sales_Model_Mysql4_Order_Comment_Collection_Abstract extends Mage_Sales_Model_Mysql4_Collection_Abstract
{
    /**
     * Set filter on comments by their parent item
     *
     * @param Mage_Core_Model_Abstract|int $parent
     * @return Mage_Sales_Model_Mysql4_Order_Comment_Collection_Abstract
     */
    public function setParentFilter($parent)
    {
        if ($parent instanceof Mage_Core_Model_Abstract) {
            $parent = $parent->getId();
        }
        return $this->addFieldToFilter('parent_id', $parent);
    }

    /**
     * Adds filter to get only 'visible on front' comments
     *
     * @param int $flag
     * @return Mage_Sales_Model_Mysql4_Order_Comment_Collection_Abstract
     */
    public function addVisibleOnFrontFilter($flag = 1)
    {
        return $this->addFieldToFilter('is_visible_on_front', $flag);
    }

    /**
     * Set created_at sort order
     *
     * @param string $direction
     * @return Mage_Sales_Model_Mysql4_Order_Comment_Collection_Abstract
     */
    public function setCreatedAtOrder($direction='desc')
    {
        return $this->setOrder('created_at', $direction);
    }
}
