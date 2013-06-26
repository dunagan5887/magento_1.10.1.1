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

/**
 * Customer address region selector
 *
 */
class Enterprise_CustomerSegment_Model_Segment_Condition_Customer_Address_Region
    extends Enterprise_CustomerSegment_Model_Condition_Abstract
{
    /**
     * Input type
     *
     * @var string
     */
    protected $_inputType = 'select';

    /**
     * Class constructor
     */
    public function __construct()
    {
        parent::__construct();
        $this->setType('enterprise_customersegment/segment_condition_customer_address_region');
        $this->setValue(1);
    }

    /**
     * Get array of event names where segment with such conditions combine can be matched
     *
     * @return array
     */
    public function getMatchedEvents()
    {
        return Mage::getModel('enterprise_customersegment/segment_condition_customer_address_attributes')
            ->getMatchedEvents();
    }

    /**
     * Get inherited conditions selectors
     *
     * @return array
     */
    public function getNewChildSelectOptions()
    {
        return array(array(
            'value' => $this->getType(),
            'label' => Mage::helper('enterprise_customersegment')->__('Has State/Province')
        ));
    }

    /**
     * Get HTML of condition string
     *
     * @return string
     */
    public function asHtml()
    {
        $element = $this->getValueElementHtml();
        return $this->getTypeElementHtml()
            .Mage::helper('enterprise_customersegment')->__('If Customer Address %s State/Province specified', $element)
            .$this->getRemoveLinkHtml();
    }

    /**
     * Get element type for value select
     *
     * @return string
     */
    public function getValueElementType()
    {
        return 'select';
    }

    /**
     * Init list of available values
     *
     * @return array
     */
    public function loadValueOptions()
    {
        $this->setValueOption(array(
            '1' => Mage::helper('enterprise_customersegment')->__('has'),
            '0' => Mage::helper('enterprise_customersegment')->__('does not have'),
        ));
        return $this;
    }

    /**
     * Get condition query
     * In all cases "region name" will be in ..._varchar table
     *
     * @param $customer
     * @param $website
     * @return Varien_Db_Select
     */
    public function getConditionsSql($customer, $website)
    {
        $inversion = ((int)$this->getValue() ? '' : '!');
        $attribute = Mage::getSingleton('eav/config')->getAttribute('customer_address', 'region');
        $select = $this->getResource()->createSelect();
        $select->from(array('caev'=>$attribute->getBackendTable()), "{$inversion}(IFNULL(caev.value, '') <> '')");
        $select->where('caev.attribute_id = ?', $attribute->getId())
            ->where("caev.entity_id = customer_address.entity_id");
        $select->limit(1);

        return $select;
    }
}
