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
 * @package     Enterprise_GiftCardAccount
 * @copyright   Copyright (c) 2010 Magento Inc. (http://www.magentocommerce.com)
 * @license     http://www.magentocommerce.com/license/enterprise-edition
 */


abstract class Enterprise_GiftCardAccount_Model_Pool_Abstract extends Mage_Core_Model_Abstract
{
    const STATUS_FREE = 0;
    const STATUS_USED = 1;

    protected $_pool_percent_used = null;
    protected $_pool_size = 0;
    protected $_pool_free_size = 0;

    /**
     * Return first free code
     * 
     * @return string
     */
    public function shift()
    {
        $notInArray = $this->getExcludedIds();
        $collection = $this->getCollection()
            ->addFieldToFilter('status', self::STATUS_FREE)
            ->setPageSize(1);
        if (is_array($notInArray) && !empty($notInArray)) {
            $collection->addFieldToFilter('code', array('nin' => $notInArray));
        }
        $collection->load();
        if (!$items = $collection->getItems()) {
            Mage::throwException(Mage::helper('enterprise_giftcardaccount')->__('No codes left in the pool.'));
        }

        $item = array_shift($items);
        return $item->getId();
    }

    /**
     * Load code pool usage info
     *
     * @return Varien_Object
     */
    public function getPoolUsageInfo()
    {
        if (is_null($this->_pool_percent_used)) {
            $this->_pool_size = $this->getCollection()->getSize();
            $this->_pool_free_size = $this->getCollection()
                ->addFieldToFilter('status', self::STATUS_FREE)
                ->getSize();
            if (!$this->_pool_size) {
                $this->_pool_percent_used = 100;
            } else {
                $this->_pool_percent_used = 100-round($this->_pool_free_size/($this->_pool_size/100), 2);
            }
        }

        $result = new Varien_Object();
        $result
            ->setTotal($this->_pool_size)
            ->setFree($this->_pool_free_size)
            ->setPercent($this->_pool_percent_used);
        return $result;
    }

    /**
     * Delete free codes from pool
     *
     * @return Enterprise_GiftCardAccount_Model_Pool_Abstract
     */
    public function cleanupFree()
    {
        $this->getResource()->cleanupByStatus(self::STATUS_FREE);
        /*
        $this->getCollection()
            ->addFieldToFilter('status', self::STATUS_FREE)
            ->walk('delete');
        */
        return $this;
    }
}
