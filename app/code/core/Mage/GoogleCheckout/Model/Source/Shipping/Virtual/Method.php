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
 * @package     Mage_GoogleCheckout
 * @copyright   Copyright (c) 2010 Magento Inc. (http://www.magentocommerce.com)
 * @license     http://www.magentocommerce.com/license/enterprise-edition
 */


class Mage_GoogleCheckout_Model_Source_Shipping_Virtual_Method
{
    public function toOptionArray()
    {
        $hlp = Mage::helper('googlecheckout');
        return array(
            array('value'=>'email', 'label'=>$hlp->__('Email delivery')),
            // array('value'=>'key_url', 'label'=>$hlp->__('Key/URL delivery')),
            // array('value'=>'description_based', 'label'=>$hlp->__('Description-based delivery')),
        );
    }
}
