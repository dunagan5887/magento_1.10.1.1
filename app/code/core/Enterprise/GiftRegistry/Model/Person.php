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
 * Entity registrants data model
 */
class Enterprise_GiftRegistry_Model_Person extends Mage_Core_Model_Abstract
{
    function _construct() {
        $this->_init('enterprise_giftregistry/person');
    }

    /**
     * Validate registrant attribute values
     *
     * @return array|bool
     */
    public function validate()
    {
        // not Checking entityId !!!
        $errors = array();
        $helper = Mage::helper('enterprise_giftregistry');

        if (!Zend_Validate::is($this->getFirstname(), 'NotEmpty')) {
            $errors[] = $helper->__('Please enter the first name.');
        }

        if (!Zend_Validate::is($this->getLastname(), 'NotEmpty')) {
            $errors[] = $helper->__('Please enter the last name.');
        }

        if (!Zend_Validate::is($this->getEmail(), 'EmailAddress')) {
            $errors[] = $helper->__('"Email" is not a valid email address.');
        }

        $customValues = $this->getCustom();
        $attributes = Mage::getSingleton('enterprise_giftregistry/entity')->getRegistrantAttributes();

        $errorsCustom = $helper->validateCustomAttributes($customValues, $attributes);
        if ($errorsCustom !== true) {
            $errors = empty($errors) ? $errorsCustom : array_merge($errors, $errorsCustom);
        }
        if (empty($errors)) {
            return true;
        }
        return $errors;
    }

    /**
     * Unpack "custom" value array
     *
     * @return $this
     */
    public function unserialiseCustom() {
        if (is_string($this->getCustomValues())) {
            $this->setCustom(unserialize($this->getCustomValues()));
        }
        return $this;
    }
}
