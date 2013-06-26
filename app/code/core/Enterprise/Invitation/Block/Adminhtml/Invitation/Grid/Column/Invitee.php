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
 * @package     Enterprise_Invitation
 * @copyright   Copyright (c) 2010 Magento Inc. (http://www.magentocommerce.com)
 * @license     http://www.magentocommerce.com/license/enterprise-edition
 */

/**
 * Column renderer for Invitee in invitations grid
 *
 */
class Enterprise_Invitation_Block_Adminhtml_Invitation_Grid_Column_Invitee
    extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract
{
    /**
     * Render invitee email linked to its account edit page
     *
     * @param   Varien_Object $row
     * @return  string
     */
    protected function _getValue(Varien_Object $row)
    {
        if (!$row->getReferralId()) {
            return '';
        }
        return '<a href="' . Mage::getSingleton('adminhtml/url')->getUrl('*/customer/edit', array('id' => $row->getReferralId())) . '">'
            . $this->htmlEscape($row->getData($this->getColumn()->getIndex())) . '</a>';
    }
}
