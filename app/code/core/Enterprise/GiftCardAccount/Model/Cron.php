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

class Enterprise_GiftCardAccount_Model_Cron
{
    /**
     * Update Gift Card Account states by cron
     *
     * @return Enterprise_GiftCardAccount_Model_Cron
     */
    public function updateStates()
    {
        // update to expired
        $model = Mage::getModel('enterprise_giftcardaccount/giftcardaccount');

        $now = Mage::getModel('core/date')->date('Y-m-d');

        $collection = $model->getCollection()
            ->addFieldToFilter('state', Enterprise_GiftCardAccount_Model_Giftcardaccount::STATE_AVAILABLE)
            ->addFieldToFilter('date_expires', array('notnull'=>true))
            ->addFieldToFilter('date_expires', array('lt'=>$now));

        $ids = $collection->getAllIds();
        if ($ids) {
            $state = Enterprise_GiftCardAccount_Model_Giftcardaccount::STATE_EXPIRED;
            $model->updateState($ids, $state);
        }
        return $this;
    }
}
