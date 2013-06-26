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
 * Shopping cart conditions options group
 */
class Enterprise_CustomerSegment_Model_Segment_Condition_Shoppingcart
    extends Enterprise_CustomerSegment_Model_Condition_Abstract
{
    /**
     * Class constructor
     */
    public function __construct()
    {
        parent::__construct();
        $this->setType('enterprise_customersegment/segment_condition_shoppingcart');
        $this->setValue(null);
    }

    /**
     * Get condition "selectors" for parent block
     *
     * @return string
     */
    public function getNewChildSelectOptions()
    {
        $prefix = 'enterprise_customersegment/segment_condition_shoppingcart_';
        return array('value' => array(
                Mage::getModel($prefix.'amount')->getNewChildSelectOptions(),
                Mage::getModel($prefix.'itemsquantity')->getNewChildSelectOptions(),
                Mage::getModel($prefix.'productsquantity')->getNewChildSelectOptions(),
            ),
            'label' => Mage::helper('enterprise_customersegment')->__('Shopping Cart')
        );
    }
}
