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

class Enterprise_Staging_Model_Mysql4_Adapter_Item extends Enterprise_Staging_Model_Mysql4_Adapter_Abstract
{
    /**
     * Enter description here...
     *
     * @param Enterprise_Staging_Model_Staging $staging
     * @param Enterprise_Staging_Model_Staging_Event $event
     *
     * @return Enterprise_Staging_Model_Staging_Adapter_Item
     */
    public function createRun(Enterprise_Staging_Model_Staging $staging, $event = null)
    {
        parent::createRun($staging, $event);

        $stagingItems = $staging->getMapperInstance()->getStagingItems();
        foreach ($stagingItems as $stagingItem) {
            $item = Mage::getModel('enterprise_staging/staging_item')
                ->loadFromXmlStagingItem($stagingItem);
            $staging->addItem($item);
        }

        $staging->saveItems();

        return $this;
    }
}
