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
 * @package     Mage_Poll
 * @copyright   Copyright (c) 2010 Magento Inc. (http://www.magentocommerce.com)
 * @license     http://www.magentocommerce.com/license/enterprise-edition
 */

/**
 * @category   Mage
 * @package    Mage_Poll
 * @author      Magento Core Team <core@magentocommerce.com>
 */
class Mage_Poll_Model_Mysql4_Poll_Answer_Collection extends Mage_Core_Model_Mysql4_Collection_Abstract
{
    public function _construct()
    {
        $this->_init('poll/poll_answer');
    }

    public function addPollFilter($pollId)
    {
        $this->getSelect()->where("poll_id IN(?) ", $pollId);
        return $this;
    }

    public function countPercent($pollObject)
    {
        if( !$pollObject ) {
            return;
        } else {
            foreach( $this->getItems() as $answer ) {
                $answer->countPercent($pollObject);
            }
        }
        return $this;
    }
}
