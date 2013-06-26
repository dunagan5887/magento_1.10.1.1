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
 * @package     Enterprise_Customer
 * @copyright   Copyright (c) 2010 Magento Inc. (http://www.magentocommerce.com)
 * @license     http://www.magentocommerce.com/license/enterprise-edition
 */


/**
 * Customer Dynamic attributes Form Block
 *
 * @category    Enterprise
 * @package     Enterprise_Customer
 * @author      Magento Core Team <core@magentocommerce.com>
 */
class Enterprise_Customer_Block_Form extends Mage_Core_Block_Template
{
    /**
     * Array of attribute renderers data keyed by attribute front-end type
     *
     * @var array
     */
    protected $_renderBlockTypes    = array();

    /**
     * Array of renderer blocks keyed by attribute front-end type
     *
     * @var array
     */
    protected $_renderBlocks        = array();

    /**
     * Customer Form Type code
     *
     * @var string
     */
    protected $_formCode;

    /**
     * Entity model class type for new entity object
     *
     * @var string
     */
    protected $_entityModelClass;

    /**
     * Entity type instance
     *
     * @var Mage_Eav_Model_Entity_Type
     */
    protected $_entityType;

    /**
     * Customer form instance
     *
     * @var Mage_Customer_Model_Form
     */
    protected $_form;

    /**
     * Customer or Customer Address Entity Model
     *
     * @var Mage_Customer_Model_Customer|Mage_Customer_Model_Address
     */
    protected $_entity;

    /**
     * Format for HTML elements id attribute
     *
     * @var string
     */
    protected $_fieldIdFormat   = '%1$s';

    /**
     * Format for HTML elements name attribute
     *
     * @var string
     */
    protected $_fieldNameFormat = '%1$s';

    /**
     * Add custom renderer block and template for rendering customer attributes
     *
     * @param string $type
     * @param string $block
     * @param string $template
     * @return Enterprise_Customer_Block_Form
     */
    public function addRenderer($type, $block, $template)
    {
        $this->_renderBlockTypes[$type] = array(
            'block'     => $block,
            'template'  => $template,
        );

        return $this;
    }

    /**
     * Try to get Customer Form Template Block
     * Get Attribute renderers from it, and add to self
     *
     * @return Enterprise_Customer_Block_Form
     */
    protected function _prepareLayout()
    {
        /* $var $template Enterprise_Customer_Block_Form_Template */
        $template = $this->getLayout()->getBlock('customer_form_template');
        if ($template) {
            foreach ($template->getRenderers() as $type => $data) {
                $this->addRenderer($type, $data['block'], $data['template']);
            }
        }
        return parent::_prepareLayout();
    }

    /**
     * Return attribute renderer by fron-end input type
     *
     * @param string $type
     * @return Enterprise_Customer_Block_Form_Renderer_Abstract
     */
    public function getRenderer($type)
    {
        if (!isset($this->_renderBlocks[$type])) {
            if (isset($this->_renderBlockTypes[$type])) {
                $data   = $this->_renderBlockTypes[$type];
                $block  = $this->getLayout()->createBlock($data['block']);
                if ($block) {
                    $block->setTemplate($data['template']);
                }
            } else {
                $block = false;
            }
            $this->_renderBlocks[$type] = $block;
        }
        return $this->_renderBlocks[$type];
    }

    /**
     * Set Entity object
     *
     * @param Mage_Customer_Model_Customer|Mage_Customer_Model_Address $entity
     * @return Enterprise_Customer_Block_Form
     */
    public function setEntity(Mage_Core_Model_Abstract $entity)
    {
        $this->_entity = $entity;
        return $this;
    }

    /**
     * Set entity model class for new object
     *
     * @param string $model
     * @return Enterprise_Customer_Block_Form
     */
    public function setEntityModelClass($model)
    {
        $this->_entityModelClass = $model;
        return $this;
    }

    /**
     * Set Entity type if entity model entity type is not defined or is different
     *
     * @param int|string|Mage_Eav_Model_Entity_Type $entityType
     * @return Enterprise_Customer_Block_Form
     */
    public function setEntityType($entityType)
    {
        $this->_entityType = Mage::getSingleton('eav/config')->getEntityType($entityType);
        return $this;
    }

    /**
     * Return Entity object
     *
     * @return Mage_Customer_Model_Customer|Mage_Customer_Model_Address
     */
    public function getEntity()
    {
        if (is_null($this->_entity)) {
            if ($this->_entityModelClass) {
                $this->_entity = Mage::getModel($this->_entityModelClass);
            }
        }
        return $this->_entity;
    }

    /**
     * Set customer form instance
     *
     * @param Mage_Customer_Model_Form $form
     * @return Enterprise_Customer_Block_Form
     */
    public function setForm(Mage_Customer_Model_Form $form)
    {
        $this->_form = $form;
        return $this;
    }

    /**
     * Set Customer Form code
     *
     * @param string $code
     * @return Enterprise_Customer_Block_Form
     */
    public function setFormCode($code)
    {
        $this->_formCode = $code;
        return $this;
    }

    /**
     * Return Customer Form instance
     *
     * @return Mage_Customer_Model_Form
     */
    public function getForm()
    {
        if (is_null($this->_form)) {
            $this->_form = Mage::getModel('customer/form')
                ->setFormCode($this->_formCode)
                ->setEntity($this->getEntity());
            if ($this->_entityType) {
                $this->_form->setEntityType($this->_entityType);
            }
            $this->_form->initDefaultValues();
        }
        return $this->_form;
    }

    /**
     * Check customer form has User defined attributes
     *
     * @return boolean
     */
    public function hasUserDefinedAttributes()
    {
        return count($this->getUserDefinedAttributes()) > 0;
    }

    /**
     * Return array of user defined attributes
     *
     * @return array
     */
    public function getUserDefinedAttributes()
    {
        $attributes = array();
        foreach ($this->getForm()->getUserAttributes() as $attribute) {
            if ($this->getExcludeFileAttributes() && in_array($attribute->getFrontendInput(), array('image', 'file'))) {
                continue;
            }
            if ($attribute->getIsVisible()) {
                $attributes[$attribute->getAttributeCode()] = $attribute;
            }
        }
        return $attributes;
    }

    /**
     * Render attribute row and return HTML
     *
     * @param Mage_Customer_Model_Attribute $attribute
     * @return string
     */
    public function getAttributeHtml(Mage_Customer_Model_Attribute $attribute)
    {
        $type   = $attribute->getFrontendInput();
        $block  = $this->getRenderer($type);
        if ($block) {
            $block->setAttributeObject($attribute)
                ->setEntity($this->getEntity())
                ->setFieldIdFormat($this->_fieldIdFormat)
                ->setFieldNameFormat($this->_fieldNameFormat);
            return $block->toHtml();
        }
        return false;
    }

    /**
     * Set format for HTML elements id attribute
     *
     * @param string $format
     * @return Enterprise_Customer_Block_Form
     */
    public function setFieldIdFormat($format)
    {
        $this->_fieldIdFormat = $format;
        return $this;
    }

    /**
     * Set format for HTML elements name attribute
     *
     * @param string $format
     * @return Enterprise_Customer_Block_Form
     */
    public function setFieldNameFormat($format)
    {
        $this->_fieldNameFormat = $format;
        return $this;
    }

    /**
     * Check is show HTML container
     *
     * @return boolean
     */
    public function isShowContainer()
    {
        if ($this->hasData('show_container')) {
            return $this->getData('show_container');
        }
        return true;
    }
}
