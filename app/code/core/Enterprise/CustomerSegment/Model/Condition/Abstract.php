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
 * @package     Enterprise_CustomerSegment
 * @copyright   Copyright (c) 2010 Magento Inc. (http://www.magentocommerce.com)
 * @license     http://www.magentocommerce.com/license/enterprise-edition
 */

class Enterprise_CustomerSegment_Model_Condition_Abstract extends Mage_Rule_Model_Condition_Abstract
{
    /**
     * Get array of event names where segment with such conditions combine can be matched
     *
     * @return array
     */
    public function getMatchedEvents()
    {
        return array();
    }

    /**
     * Customize default operator input by type mapper for some types
     * @return array
     */
    public function getDefaultOperatorInputByType()
    {
        if (null === $this->_defaultOperatorInputByType) {
            parent::getDefaultOperatorInputByType();
            $this->_defaultOperatorInputByType['numeric'] = array('==', '!=', '>=', '>', '<=', '<');
            $this->_defaultOperatorInputByType['string'] = array('==', '!=', '{}', '!{}');
            $this->_defaultOperatorInputByType['multiselect'] = array('==', '!=', '[]', '![]');

        }
        return $this->_defaultOperatorInputByType;
    }

    /**
     * Default operator options getter
     * Provides all possible operator options
     *
     * @return array
     */
    public function getDefaultOperatorOptions()
    {
        if (null === $this->_defaultOperatorOptions) {
            $this->_defaultOperatorOptions = parent::getDefaultOperatorOptions();

            $this->_defaultOperatorOptions['[]'] = Mage::helper('rule')->__('contains');
            $this->_defaultOperatorOptions['![]'] = Mage::helper('rule')->__('does not contains');
        }
        return $this->_defaultOperatorOptions;
    }

    /**
     * Get condition combine resource model
     *
     * @return Enterprise_CustomerSegment_Model_Mysql4_Segment
     */
    public function getResource()
    {
        return Mage::getResourceSingleton('enterprise_customersegment/segment');
    }

    /**
     * Generate customer condition string
     *
     * @param $customer
     * @param string $fieldName
     * @return string
     */
    protected function _createCustomerFilter($customer, $fieldName)
    {
        return "{$fieldName} = root.entity_id";
    }

    /**
     * Limit select by website with joining to store table
     *
     * @param Zend_Db_Select $select
     * @param int | Zend_Db_Expr $website
     * @param string $storeIdField
     * @return Enterprise_CustomerSegment_Model_Condition_Abstract
     */
    protected function _limitByStoreWebsite(Zend_Db_Select $select, $website, $storeIdField)
    {
        $storeTable = $this->getResource()->getTable('core/store');
        $select->join(array('store'=> $storeTable), $storeIdField.'=store.store_id', array())
            ->where('store.website_id=?', $website);
        return $this;
    }
}
