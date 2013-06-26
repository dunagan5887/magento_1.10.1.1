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
 * Reward collection
 *
 * @category    Enterprise
 * @package     Enterprise_Reward
 * @author      Magento Core Team <core@magentocommerce.com>
 */
class Enterprise_Reward_Model_Mysql4_Reward_Collection extends Mage_Core_Model_Mysql4_Collection_Abstract
{
    /**
     * Internal construcotr
     */
    protected function _construct()
    {
        $this->_init('enterprise_reward/reward');
    }

    /**
     * Add filter by website id
     *
     * @param integer|array $websiteId
     * @return Enterprise_Reward_Model_Mysql4_Reward_Collection
     */
    public function addWebsiteFilter($websiteId)
    {
        $this->getSelect()->where(is_array($websiteId) ? 'main_table.website_id IN (?)' : 'main_table.website_id = ?', $websiteId);
        return $this;
    }
}

