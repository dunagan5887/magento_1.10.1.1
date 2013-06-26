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
 * @package     Enterprise_Checkout
 * @copyright   Copyright (c) 2010 Magento Inc. (http://www.magentocommerce.com)
 * @license     http://www.magentocommerce.com/license/enterprise-edition
 */

/**
 * Store switcher for shopping cart management
 *
 * @category   Enterprise
 * @package    Enterprise_Checkout
 * @author     Magento Core Team <core@magentocommerce.com>
 */
class Enterprise_Checkout_Block_Adminhtml_Manage_Store_Switcher extends Mage_Adminhtml_Block_Store_Switcher
{
    /**
     * @var bool
     */
    protected $_hasDefaultOption = false;

    /**
     * Add website filter
     */
    public function __construct()
    {
        parent::__construct();
        $this->setUseConfirm(false);
        if ($this->_getCustomer()->getSharingConfig()->isWebsiteScope()) {
            $this->setWebsiteIds($this->_getCustomer()->getSharedWebsiteIds());
        }
    }

    /**
     * Return current customer from regisrty
     *
     * @return Mage_Customer_Model_Customer
     */
    protected function _getCustomer()
    {
        return Mage::registry('checkout_current_customer');
    }

    /**
     * Return current store from regisrty
     *
     * @return Mage_Core_Model_Store
     */
    protected function _getStore()
    {
        return Mage::registry('checkout_current_store');
    }
}
