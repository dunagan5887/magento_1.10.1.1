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

class Enterprise_TargetRule_Model_Rule extends Mage_Rule_Model_Rule
{
    const BOTH_SELECTED_AND_RULE_BASED  = 0;
    const SELECTED_ONLY                 = 1;
    const RULE_BASED_ONLY               = 2;

    const RELATED_PRODUCTS              = 1;
    const UP_SELLS                      = 2;
    const CROSS_SELLS                   = 3;

    const XML_PATH_DEFAULT_VALUES       = 'catalog/enterprise_targetrule/';

    /**
     * Matched product objects array
     *
     * @var array
     */
    protected $_products;

    /**
     * Matched product ids array
     *
     * @var array
     */
    protected $_productIds;

    /**
     * Check valid date for store cache
     *
     * @var array
     */
    protected $_checkDateForStore = array();

    /**
     * Initialize resource model
     *
     */
    protected function _construct()
    {
        $this->_init('enterprise_targetrule/rule');
    }

    /**
     * Reset action cached select if actions conditions has changed
     *
     * @return Enterprise_TargetRule_Model_Rule
     */
    protected function _beforeSave()
    {
        parent::_beforeSave();

        if ($this->dataHasChangedFor('actions_serialized')) {
            $this->setData('action_select', null);
            $this->setData('action_select_bind', null);
        }

        return $this;
    }

    /**
     * Return conditions instance
     *
     * @return Enterprise_TargetRule_Model_Rule_Condition_Combine
     */
    public function getConditionsInstance()
    {
        return Mage::getModel('enterprise_targetrule/rule_condition_combine');
    }

    /**
     * Return actions condition instance
     *
     * @return Enterprise_TargetRule_Model_Actions_Condition_Combine
     */
    public function getActionsInstance()
    {
        return Mage::getModel('enterprise_targetrule/actions_condition_combine');
    }

    /**
     * Initialize rule model data from array
     *
     * @param   array $rule
     * @return  Enterprise_TargetRule_Model_Rule
     */
    public function loadPost(array $rule)
    {
        $arr = $this->_convertFlatToRecursive($rule);
        if (isset($arr['conditions'])) {
            $this->getConditions()->setConditions(array())->loadArray($arr['conditions'][1]);
        }
        if (isset($arr['actions'])) {
            $this->getActions()->setActions(array())->loadArray($arr['actions'][1], 'actions');
        }
        return $this;
    }

    /**
     * Get options for `Apply to` field
     *
     * @param bool $withEmpty
     * @return array
     */
    public function getAppliesToOptions($withEmpty = false)
    {
        $result = array();
        if ($withEmpty) {
            $result[''] = Mage::helper('adminhtml')->__('-- Please Select --');
        }
        $result[Enterprise_TargetRule_Model_Rule::RELATED_PRODUCTS]
            = Mage::helper('enterprise_targetrule')->__('Related Products');
        $result[Enterprise_TargetRule_Model_Rule::UP_SELLS]
            = Mage::helper('enterprise_targetrule')->__('Up-sells');
        $result[Enterprise_TargetRule_Model_Rule::CROSS_SELLS]
            = Mage::helper('enterprise_targetrule')->__('Cross-sells');
        return $result;
    }

    /**
     * Retrieve Customer Segment Relations
     * Return empty array for rule didn't save or didn't use customer segment limitation
     *
     * @return array
     */
    public function getCustomerSegmentRelations()
    {
        if (!$this->getUseCustomerSegment() || !$this->getId()) {
            return array();
        }
        $relations = $this->_getData('customer_segment_relations');
        if (!is_array($relations)) {
            $relations = $this->_getResource()->getCustomerSegmentRelations($this->getId());
            $this->setData('customer_segment_relations', $relations);
        }

        return $relations;
    }

    /**
     * Set customer segment relations
     *
     * @param array|string $relations
     * @return Enterprise_TargetRule_Model_Rule
     */
    public function setCustomerSegmentRelations($relations)
    {
        if (is_array($relations)) {
            $this->setData('customer_segment_relations', $relations);
        } else if (is_string($relations)) {
            if (empty($relations)) {
                $relations = array();
            } else {
                $relations = explode(',', $relations);
            }
            $this->setData('customer_segment_relations', $relations);
        }

        return $this;
    }

    /**
     * Retrieve array of product objects which are matched by rule
     *
     * @return array
     */
    public function getMatchingProducts()
    {
        if (is_null($this->_products)) {
            $productCollection = Mage::getResourceModel('catalog/product_collection');

            $this->setCollectedAttributes(array());
            $this->getConditions()->collectValidatedAttributes($productCollection);

            $this->_productIds = array();
            $this->_products   = array();
            Mage::getSingleton('core/resource_iterator')->walk(
                $productCollection->getSelect(),
                array(
                    array($this, 'callbackValidateProduct')
                ),
                array(
                    'attributes' => $this->getCollectedAttributes(),
                    'product'    => Mage::getModel('catalog/product'),
                )
            );
        }

        return $this->_products;
    }

    /**
     * Retrieve array of product ids which are matched by rule
     *
     * @return array
     */
    public function getMatchingProductIds()
    {
        if (is_null($this->_productIds)) {
            $this->getMatchingProducts();
        }

        return $this->_productIds;
    }

    /**
     * Callback function for product matching
     *
     * @param array $args
     */
    public function callbackValidateProduct($args)
    {
        $product = clone $args['product'];
        $product->setData($args['row']);

        if ($this->getConditions()->validate($product)) {
            $this->_productIds[] = $product->getId();
            $this->_products[]   = $product;
        }
    }

    /**
     * Check is applicable rule by date for store
     *
     * @param int $storeId
     * @return bool
     */
    public function checkDateForStore($storeId)
    {
        if (!isset($this->_checkDateForStore[$storeId])) {
            $this->_checkDateForStore[$storeId] = Mage::app()->getLocale()
                ->isStoreDateInInterval(null, $this->getFromDate(), $this->getToDate());
        }
        return $this->_checkDateForStore[$storeId];
    }

    /**
     * Retrieve Result limit for rule
     * If value is 0 - return default max value
     *
     */
    public function getPositionsLimit()
    {
        $limit = $this->getData('positions_limit');
        if (!$limit) {
            $limit = 20;
        }
        return $limit;
    }

    /**
     * Retrieve Action select bind array
     *
     * @return array|null
     */
    public function getActionSelectBind()
    {
        $bind = $this->getData('action_select_bind');
        if (!is_null($bind) && !is_array($bind)) {
            $bind = unserialize($bind);
        }

        return $bind;
    }

    /**
     * Set action select bind array or serialized string
     *
     * @param array|string $bind
     * @return Enterprise_TargetRule_Model_Rule
     */
    public function setActionSelectBind($bind)
    {
        if (is_array($bind)) {
            $bind = serialize($bind);
        }
        return $this->setData('action_select_bind', $bind);
    }

    /**
     * Retrieve Actions instance wrapper
     *
     * @return Enterprise_TargetRule_Model_Actions_Condition_Combine
     */
    public function getActions()
    {
        return parent::getActions();
    }

    /**
     * Validates data for rule
     * @param Varien_Object $object
     * @returns boolean|array - returns true if validation passed successfully. Array with error
     * description otherwise
     */
    public function validate(Varien_Object $object)
    {
        if($object->getData('from_date') && $object->getData('to_date')){
            $dateStartUnixTime = strtotime($object->getData('from_date'));
            $dateEndUnixTime   = strtotime($object->getData('to_date'));

            if ($dateEndUnixTime < $dateStartUnixTime) {
                return array(Mage::helper('enterprise_targetrule')->__("End Date should be greater than Start Date"));
            }
        }
        return true;
    }
}
