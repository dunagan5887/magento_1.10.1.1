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
 * @package     Enterprise_PageCache
 * @copyright   Copyright (c) 2010 Magento Inc. (http://www.magentocommerce.com)
 * @license     http://www.magentocommerce.com/license/enterprise-edition
 */

/**
 * Cart sidebar container
 */
class Enterprise_PageCache_Model_Container_Messages extends Enterprise_PageCache_Model_Container_Abstract
{
    /**
     * Message store factory names
     *
     * @var array
     */
    protected $_messageStoreTypes = array(
        'core/session',
        'customer/session',
        'catalog/session',
        'checkout/session',
        'tag/session'
    );

    /**
     * Check for new messages. New message flag will be reseted if needed.
     *
     * @return bool
     */
    protected function _isNewMessageRecived()
    {
        return ($this->_getCookieValue(Enterprise_PageCache_Model_Cookie::COOKIE_MESSAGE) ? true : false);
    }

    /**
     * Redirect to content processing on new message
     *
     * @param string $content
     * @return bool
     */
    public function applyWithoutApp(&$content)
    {
        if ($this->_isNewMessageRecived()) {
            return false;
        }
        return parent::applyWithoutApp($content);
    }

    /**
     * Render block content
     *
     * @return string
     */
    protected function _renderBlock()
    {
        Mage::getSingleton('core/cookie')->delete(Enterprise_PageCache_Model_Cookie::COOKIE_MESSAGE);

        $block = $this->_placeholder->getAttribute('block');
        $block = new $block;

        foreach ($this->_messageStoreTypes as $type) {
            $this->_addMessagesToBlock($type, $block);
        }

        return $block->toHtml();
    }

    /**
     * Add messages from storage to message block
     *
     * @param string $messagesStorage
     * @param Mage_Core_Block_Messages $block
     */
    protected function _addMessagesToBlock($messagesStorage, Mage_Core_Block_Messages $block)
    {
        if ($storage = Mage::getSingleton($messagesStorage)) {
            $block->addMessages($storage->getMessages(true));
            $block->setEscapeMessageFlag($storage->getEscapeMessages(true));
        }
    }
}
