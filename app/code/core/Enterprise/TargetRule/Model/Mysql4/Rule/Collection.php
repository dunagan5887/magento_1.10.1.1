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
 * TargetRule Rule Resource Collection
 *
 * @category   Enterprise
 * @package    Enterprise_TargetRule
 */
class Enterprise_TargetRule_Model_Mysql4_Rule_Collection extends Mage_Core_Model_Mysql4_Collection_Abstract
{
    /**
     * Initialize resource collection
     *
     */
    protected function _construct()
    {
        $this->_init('enterprise_targetrule/rule');
    }

    /**
     * Add Apply To Product List Filter to Collection
     *
     * @param int|array $applyTo
     * @return Enterprise_TargetRule_Model_Mysql4_Rule_Collection
     */
    public function addApplyToFilter($applyTo)
    {
        $this->addFieldToFilter('apply_to', $applyTo);
        return $this;
    }

    /**
     * Add Is active rule filter to collection
     *
     * @param int $isActive
     * @return Enterprise_TargetRule_Model_Mysql4_Rule_Collection
     */
    public function addIsActiveFilter($isActive = 1)
    {
        $this->addFieldToFilter('is_active', $isActive);
        return $this;
    }

    /**
     * Set Priority Sort order
     *
     * @param string $direction
     * @return Enterprise_TargetRule_Model_Mysql4_Rule_Collection
     */
    public function setPriorityOrder($direction = self::SORT_ORDER_ASC)
    {
        $this->setOrder('sort_order', $direction);
        return $this;
    }

    /**
     * After load collection load customer segment relation
     *
     * @return Enterprise_TargetRule_Model_Mysql4_Rule_Collection
     */
    protected function _afterLoad()
    {
        if ($this->getFlag('add_customersegment_relations')) {
            $this->getResource()->addCustomerSegmentRelationsToCollection($this);
        }

        foreach ($this->_items as $rule) {
            /* @var $rule Enterprise_TargetRule_Model_Rule */
            if (!$this->getFlag('do_not_run_after_load')) {
                $rule->afterLoad();
            }
        }

        return parent::_afterLoad();
    }

    /**
     * Add filter by product id to collection
     *
     * @param int $productId
     * @return Enterprise_TargetRule_Model_Mysql4_Rule_Collection
     */
    public function addProductFilter($productId)
    {
        $this->getSelect()->join(
            array('product_idx' => $this->getTable('enterprise_targetrule/product')),
            'product_idx.rule_id = main_table.rule_id',
            array()
        );
        $this->getSelect()->where('product_idx.product_id=?', $productId);

        return $this;
    }
}
