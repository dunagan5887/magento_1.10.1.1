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
 * @package     Enterprise_Reward
 * @copyright   Copyright (c) 2010 Magento Inc. (http://www.magentocommerce.com)
 * @license     http://www.magentocommerce.com/license/enterprise-edition
 */

/**
 * Reward action for refunding points
 *
 * @category    Enterprise
 * @package     Enterprise_Reward
 * @author      Magento Core Team <core@magentocommerce.com>
 */
class Enterprise_Reward_Model_Action_Creditmemo extends Enterprise_Reward_Model_Action_Abstract
{
    /**
     * Check whether rewards can be added for action
     *
     * @return bool
     */
    public function canAddRewardPoints()
    {
        return true;
    }

    /**
     * Return action message for history log
     *
     * @param array $args Additional history data
     * @return string
     */
    public function getHistoryMessage($args = array())
    {
        $incrementId = isset($args['increment_id']) ? $args['increment_id'] : '';
        return Mage::helper('enterprise_reward')->__('Refunded from order #%s.', $incrementId);
    }

    /**
     * Setter for $_entity and add some extra data to history
     *
     * @param Varien_Object $entity
     * @return Enterprise_Reward_Model_Action_Abstract
     */
    public function setEntity($entity)
    {
        parent::setEntity($entity);
        $this->getHistory()->addAdditionalData(array(
            'increment_id' => $this->getEntity()->getIncrementId()
        ));
        return $this;
    }
}
