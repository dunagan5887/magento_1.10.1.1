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
 * Reward tab block
 *
 * @category    Enterprise
 * @package     Enterprise_Reward
 * @author      Magento Core Team <core@magentocommerce.com>
 */
class Enterprise_Reward_Block_Adminhtml_Customer_Edit_Tab_Reward
    extends Mage_Adminhtml_Block_Template
    implements Mage_Adminhtml_Block_Widget_Tab_Interface
{
    /**
     * Return tab label
     *
     * @return string
     */
    public function getTabLabel()
    {
        return Mage::helper('enterprise_reward')->__('Reward Points');
    }

    /**
     * Return tab title
     *
     * @return string
     */
    public function getTabTitle()
    {
        return Mage::helper('enterprise_reward')->__('Reward Points');
    }

    /**
     * Check if can show tab
     *
     * @return boolean
     */
    public function canShowTab()
    {
        $customer = Mage::registry('current_customer');
        return $customer->getId()
            && Mage::helper('enterprise_reward')->isEnabled()
            && Mage::getSingleton('admin/session')->isAllowed('enterprise_reward/balance');
    }

    /**
     * Check if tab hidden
     *
     * @return boolean
     */
    public function isHidden()
    {
        return false;
    }

    /**
     * Prepare layout.
     * Add accordion items
     *
     * @return Enterprise_Reward_Block_Adminhtml_Customer_Edit_Tab_Reward
     */
    protected function _prepareLayout()
    {
        $accordion = $this->getLayout()->createBlock('adminhtml/widget_accordion');
        $accordion->addItem('reward_points_history', array(
            'title'       => Mage::helper('enterprise_reward')->__('Reward Points History'),
            'open'        => false,
            'class'       => '',
            'ajax'        => true,
            'content_url' => $this->getUrl('*/customer_reward/history', array('_current' => true))
        ));
        $this->setChild('history_accordion', $accordion);

        return parent::_prepareLayout();
    }

    /**
     * Precessor tab ID getter
     *
     * @return string
     */
    public function getAfter()
    {
        return 'tags';
    }
}
