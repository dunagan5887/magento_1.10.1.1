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
 * @package     Enterprise_Cms
 * @copyright   Copyright (c) 2010 Magento Inc. (http://www.magentocommerce.com)
 * @license     http://www.magentocommerce.com/license/enterprise-edition
 */


/**
 * Cms Hierarchy Node Widget Block
 *
 * @category   Enterprise
 * @package    Enterprise_Cms
 */
class Enterprise_Cms_Block_Widget_Node
    extends Mage_Core_Block_Html_Link
    implements Mage_Widget_Block_Interface
{
    /**
     * Current Hierarchy Node Page Instance
     *
     * @var Enterprise_Cms_Model_Hierarchy_Node
     */
    protected $_node;

    /**
     * Retrieve specified anchor text
     *
     * @return string
     */
    public function getAnchorText()
    {
        if ($this->getData('anchor_text')) {
            return $this->getData('anchor_text');
        }
        return $this->_node->getLabel();
    }

    /**
     * Retrieve link specified title
     *
     * @return string
     */
    public function getTitle()
    {
        if ($this->getData('title')) {
            return $this->getData('title');
        }
        return $this->_node->getLabel();
    }

    /**
     * Retrieve Node URL
     *
     * @return string
     */
    public function getHref()
    {
        return $this->_node->getUrl();
    }

    /**
     * Render block HTML
     *
     * @return string
     */
    protected function _toHtml()
    {
        if ($this->getNodeId()) {
            $this->_node = Mage::getModel('enterprise_cms/hierarchy_node')
                ->load($this->getNodeId());
        } else {
            $this->_node = Mage::registry('current_cms_hierarchy_node');
        }

        if (!$this->_node) {
            return '';
        }

        return parent::_toHtml();
    }
}
