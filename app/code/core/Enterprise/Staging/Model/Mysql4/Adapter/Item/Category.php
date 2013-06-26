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
 * @package     Enterprise_Staging
 * @copyright   Copyright (c) 2010 Magento Inc. (http://www.magentocommerce.com)
 * @license     http://www.magentocommerce.com/license/enterprise-edition
 */

class Enterprise_Staging_Model_Mysql4_Adapter_Item_Category extends Enterprise_Staging_Model_Mysql4_Adapter_Item_Default
{
    /**
     * List of table codes which shuldn't process if product item were not chosen
     *
     * @var array
     */
    protected $_ignoreIfProductNotChosen = array('category_product', 'category_product_index');

    /**
     * Create item table and records, run processes in website and store scopes
     *
     * @param string    $entityName
     *
     * @return Enterprise_Staging_Model_Staging_Adapter_Item_Abstract
     */
    protected function _createItem($entityName)
    {
        if (!$this->getStaging()->getMapperInstance()->hasStagingItem('product')) {
            if (strpos($entityName, 'product') !== false) {
                return $this;
            }
        }
        return parent::_createItem($entityName);
    }
}
