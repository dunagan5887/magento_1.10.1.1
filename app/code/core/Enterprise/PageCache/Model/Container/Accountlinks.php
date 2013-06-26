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
 * Account links container
 */
class Enterprise_PageCache_Model_Container_Accountlinks extends Enterprise_PageCache_Model_Container_Customer
{
    /**
     * Get cart hash from cookies
     */
    protected function _isLogged()
    {
        return ($this->_getCookieValue(Enterprise_PageCache_Model_Cookie::COOKIE_CUSTOMER) ? true : false);
    }

    /**
     * Get cache identifier
     *
     * @return string
     */
    protected function _getCacheId()
    {
        return 'CONTAINER_LINKS_' . md5($this->_placeholder->getAttribute('cache_id') .
            (($this->_isLogged()) ? 'logged' : 'not_logged'));
    }

    /**
     * Render block content
     *
     * @return string
     */
    protected function _renderBlock()
    {
        $block = $this->_placeholder->getAttribute('block');
        $template = $this->_placeholder->getAttribute('template');
        $name = $this->_placeholder->getAttribute('name');
        $links = $this->_placeholder->getAttribute('links');

        $block = new $block;
        $block->setTemplate($template);
        $block->setNameInLayout($name);

        if ($links) {
            $links = unserialize(base64_decode($links));
            foreach ($links as $position => $linkInfo) {
                $block->addLink($linkInfo['label'], $linkInfo['url'], $linkInfo['title'], false, array(), $position,
                    $linkInfo['li_params'], $linkInfo['a_params'], $linkInfo['before_text'], $linkInfo['after_text']);
            }
        }

        return $block->toHtml();
    }
}
