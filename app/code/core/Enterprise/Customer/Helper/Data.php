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
 * Enterprise Customer Data Helper
 *
 * @category   Enterprise
 * @package    Enterprise_Customer
 */
class Enterprise_Customer_Helper_Data extends Mage_Core_Helper_Abstract
{
    /**
     * Array of User Defined attribute codes per entity type code
     *
     * @var array
     */
    protected $_userDefinedAttributeCodes = array();

    /**
     * Return form types ids of given attribute
     *
     * @param Mage_Eav_Model_Entity_Attribute_Abstract $attribute
     * @return array
     */
    public function getAttributeFormTypeIds($attribute)
    {
        $types = Mage::getResourceModel('eav/form_type')
            ->getFormTypesByAttribute($attribute);
        $typesIds = array();
        foreach ($types as $type) {
            $typesIds[] = $type['type_id'];
        }
        return $typesIds;
    }

    /**
     * Return available customer attribute form as select options
     *
     * @return array
     */
    public function getCustomerAttributeFormOptions()
    {
        return array(
            array(
                'label' => Mage::helper('enterprise_customer')->__('Customer Checkout Register'),
                'value' => 'checkout_register'
            ),
            array(
                'label' => Mage::helper('enterprise_customer')->__('Customer Registration'),
                'value' => 'customer_account_create'
            ),
            array(
                'label' => Mage::helper('enterprise_customer')->__('Customer Account Edit'),
                'value' => 'customer_account_edit'
            ),
            array(
                'label' => Mage::helper('enterprise_customer')->__('Admin Checkout'),
                'value' => 'adminhtml_checkout'
            ),
        );
    }

    /**
     * Return available customer address attribute form as select options
     *
     * @return array
     */
    public function getCustomerAddressAttributeFormOptions()
    {
        return array(
            array(
                'label' => Mage::helper('enterprise_customer')->__('Customer Address Registration'),
                'value' => 'customer_register_address'
            ),
            array(
                'label' => Mage::helper('enterprise_customer')->__('Customer Account Address'),
                'value' => 'customer_address_edit'
            ),
        );
    }

    /**
     * Return data array of customer and customer address attribute Input Types
     *
     * @param string $inputType
     * @return array
     */
    public function getAttributeInputTypes($inputType = null)
    {
        $inputTypes = array(
            'text'          => array(
                'label'             => Mage::helper('enterprise_customer')->__('Text Field'),
                'manage_options'    => false,
                'validate_types'    => array(
                    'min_text_length',
                    'max_text_length',
                ),
                'validate_filters'  => array(
                    'alphanumeric',
                    'numeric',
                    'alpha',
                    'url',
                    'email',
                ),
                'filter_types'      => array(
                    'striptags',
                    'escapehtml'
                ),
                'backend_type'      => 'varchar',
                'default_value'     => 'text',
            ),
            'textarea'      => array(
                'label'             => Mage::helper('enterprise_customer')->__('Text Area'),
                'manage_options'    => false,
                'validate_types'    => array(
                    'min_text_length',
                    'max_text_length',
                ),
                'validate_filters'  => array(),
                'filter_types'      => array(
                    'striptags',
                    'escapehtml'
                ),
                'backend_type'      => 'text',
                'default_value'     => 'textarea',
            ),
            'multiline'     => array(
                'label'             => Mage::helper('enterprise_customer')->__('Multiple Line'),
                'manage_options'    => false,
                'validate_types'    => array(
                    'min_text_length',
                    'max_text_length',
                ),
                'validate_filters'  => array(
                    'alphanumeric',
                    'numeric',
                    'alpha',
                    'url',
                    'email',
                ),
                'filter_types'      => array(
                    'striptags',
                    'escapehtml'
                ),
                'backend_type'      => 'text',
                'default_value'     => 'text',
            ),
            'date'          => array(
                'label'             => Mage::helper('enterprise_customer')->__('Date'),
                'manage_options'    => false,
                'validate_types'    => array(
                    'date_range_min',
                    'date_range_max'
                ),
                'validate_filters'  => array(
                    'date'
                ),
                'filter_types'      => array(
                    'date'
                ),
                'backend_model'     => 'eav/entity_attribute_backend_datetime',
                'backend_type'      => 'datetime',
                'default_value'     => 'date',
            ),
            'select'        => array(
                'label'             => Mage::helper('enterprise_customer')->__('Dropdown'),
                'manage_options'    => true,
                'option_default'    => 'radio',
                'validate_types'    => array(),
                'validate_filters'  => array(),
                'filter_types'      => array(),
                'source_model'      => 'eav/entity_attribute_source_table',
                'backend_type'      => 'int',
                'default_value'     => false,
            ),
            'multiselect'   => array(
                'label'             => Mage::helper('enterprise_customer')->__('Multiple Select'),
                'manage_options'    => true,
                'option_default'    => 'checkbox',
                'validate_types'    => array(),
                'filter_types'      => array(),
                'validate_filters'  => array(),
                'backend_model'     => 'eav/entity_attribute_backend_array',
                'source_model'      => 'eav/entity_attribute_source_table',
                'backend_type'      => 'varchar',
                'default_value'     => false,
            ),
            'boolean'       => array(
                'label'             => Mage::helper('enterprise_customer')->__('Yes/No'),
                'manage_options'    => false,
                'validate_types'    => array(),
                'validate_filters'  => array(),
                'filter_types'      => array(),
                'source_model'      => 'eav/entity_attribute_source_boolean',
                'backend_type'      => 'int',
                'default_value'     => 'yesno',
            ),
            'file'          => array(
                'label'             => Mage::helper('enterprise_customer')->__('File (attachment)'),
                'manage_options'    => false,
                'validate_types'    => array(
                    'max_file_size',
                    'file_extensions'
                ),
                'validate_filters'  => array(),
                'filter_types'      => array(),
                'backend_type'      => 'varchar',
                'default_value'     => false,
            ),
            'image'         => array(
                'label'             => Mage::helper('enterprise_customer')->__('Image File'),
                'manage_options'    => false,
                'validate_types'    => array(
                    'max_file_size',
                    'max_image_width',
                    'max_image_heght',
                ),
                'validate_filters'  => array(),
                'filter_types'      => array(),
                'backend_type'      => 'varchar',
                'default_value'     => false,
            ),
        );

        if (is_null($inputType)) {
            return $inputTypes;
        } else if (isset($inputTypes[$inputType])) {
            return $inputTypes[$inputType];
        }
        return array();
    }

    /**
     * Return options array of customer attribute Front-end Input types
     *
     * @return array
     */
    public function getFrontendInputOptions()
    {
        $inputTypes = $this->getAttributeInputTypes();
        $options    = array();
        foreach ($inputTypes as $k => $v) {
            $options[] = array(
                'value' => $k,
                'label' => $v['label']
            );
        }

        return $options;
    }

    public function getAttributeValidateFilters()
    {
        return array(
            'alphanumeric'  => Mage::helper('enterprise_customer')->__('Alphanumeric'),
            'numeric'       => Mage::helper('enterprise_customer')->__('Numeric Only'),
            'alpha'         => Mage::helper('enterprise_customer')->__('Alpha Only'),
            'url'           => Mage::helper('enterprise_customer')->__('URL'),
            'email'         => Mage::helper('enterprise_customer')->__('Email'),
            'date'          => Mage::helper('enterprise_customer')->__('Date'),
        );
    }

    public function getAttributeFilterTypes()
    {
        return array(
            'striptags'     => Mage::helper('enterprise_customer')->__('Strip HTML Tags'),
            'escapehtml'    => Mage::helper('enterprise_customer')->__('Escape HTML Entities'),
            'date'          => Mage::helper('enterprise_customer')->__('Normalize Date')
        );
    }

    public function getAttributeElementScopes()
    {
        return array(
            'is_required'            => 'website',
            'is_visible'             => 'website',
            'multiline_count'        => 'website',
            'default_value_text'     => 'website',
            'default_value_yesno'    => 'website',
            'default_value_date'     => 'website',
            'default_value_textarea' => 'website',
            'date_range_min'         => 'website',
            'date_range_max'         => 'website'
        );
    }

    /**
     * Return default value field name by attribute input type
     *
     * @param string $inputType
     * @return string
     */
    public function getAttributeDefaultValueByInput($inputType)
    {
        $inputTypes = $this->getAttributeInputTypes();
        if (isset($inputTypes[$inputType])) {
            $value = $inputTypes[$inputType]['default_value'];
            if ($value) {
                return 'default_value_' . $value;
            }
        }
        return false;
    }

    /**
     * Return array of attribute validate rules
     *
     * @param string $inputType
     * @param array $data
     * @return array
     */
    public function getAttributeValidateRules($inputType, array $data)
    {
        $inputTypes = $this->getAttributeInputTypes();
        $rules      = array();
        if (isset($inputTypes[$inputType])) {
            foreach ($inputTypes[$inputType]['validate_types'] as $validateType) {
                if (!empty($data[$validateType])) {
                    $rules[$validateType] = $data[$validateType];
                }
            }
            //transform date validate rules to timestamp
            if ($inputType === 'date') {
                foreach(array('date_range_min', 'date_range_max') as $dateRangeBorder) {
                    if (isset($rules[$dateRangeBorder])) {
                        $date = new Zend_Date($rules[$dateRangeBorder], $this->getDateFormat());
                        $rules[$dateRangeBorder] = $date->getTimestamp();
                    }
                }
            }
            if (!empty($inputTypes[$inputType]['validate_filters']) && !empty($data['input_validation'])) {
                if (in_array($data['input_validation'], $inputTypes[$inputType]['validate_filters'])) {
                    $rules['input_validation'] = $data['input_validation'];
                }
            }
        }
        return $rules;
    }

    /**
     * Return default attribute back-end model by input type
     *
     * @param string $inputType
     * @return string|null
     */
    public function getAttributeBackendModelByInputType($inputType)
    {
        $inputTypes = $this->getAttributeInputTypes();
        if (!empty($inputTypes[$inputType]['backend_model'])) {
            return $inputTypes[$inputType]['backend_model'];
        }
        return null;
    }

    /**
     * Return default attribute source model by input type
     *
     * @param string $inputType
     * @return string|null
     */
    public function getAttributeSourceModelByInputType($inputType)
    {
        $inputTypes = $this->getAttributeInputTypes();
        if (!empty($inputTypes[$inputType]['source_model'])) {
            return $inputTypes[$inputType]['source_model'];
        }
        return null;
    }

    /**
     * Return default attribute backend storage type by input type
     *
     * @param string $inputType
     * @return string|null
     */
    public function getAttributeBackendTypeByInputType($inputType)
    {
        $inputTypes = $this->getAttributeInputTypes();
        if (!empty($inputTypes[$inputType]['backend_type'])) {
            return $inputTypes[$inputType]['backend_type'];
        }
        return null;
    }

    /**
     * Returns array of user defined attribute codes
     *
     * @param string $entityTypeCode
     * @return array
     */
    protected function _getUserDefinedAttributeCodes($entityTypeCode)
    {
        if (empty($this->_userDefinedAttributeCodes[$entityTypeCode])) {
            $this->_userDefinedAttributeCodes[$entityTypeCode] = array();
            /* @var $config Mage_Eav_Model_Config */
            $config = Mage::getSingleton('eav/config');
            foreach ($config->getEntityAttributeCodes($entityTypeCode) as $attributeCode) {
                $attribute = $config->getAttribute($entityTypeCode, $attributeCode);
                if ($attribute && $attribute->getIsUserDefined()) {
                    $this->_userDefinedAttributeCodes[$entityTypeCode][] = $attributeCode;
                }
            }
        }
        return $this->_userDefinedAttributeCodes[$entityTypeCode];
    }

    /**
     * Returns array of user defined attribute codes for customer entity type
     *
     * @return array
     */
    public function getCustomerUserDefinedAttributeCodes()
    {
        return $this->_getUserDefinedAttributeCodes('customer');
    }

    /**
     * Returns array of user defined attribute codes for customer address entity type
     *
     * @return array
     */
    public function getCustomerAddressUserDefinedAttributeCodes()
    {
        return $this->_getUserDefinedAttributeCodes('customer_address');
    }

    /**
     * return date format
     *
     * @return string
     */
    public function getDateFormat()
    {
        return Mage::app()->getLocale()->getDateFormat(Mage_Core_Model_Locale::FORMAT_TYPE_SHORT);
    }
}
