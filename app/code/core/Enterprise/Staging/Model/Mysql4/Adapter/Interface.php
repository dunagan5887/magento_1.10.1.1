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

/**
 * Staging Resource Adapter Interface
 *
 * @category   Enterprise
 * @package    Enterprise_Staging
 * @author     Magento Core Team <core@magentocommerce.com>
 */
interface Enterprise_Staging_Model_Mysql4_Adapter_Interface
{
    public function checkfrontendRun(Enterprise_Staging_Model_Staging $staging, $event = null);
    public function createRun(Enterprise_Staging_Model_Staging $staging, $event = null);
    public function updateRun(Enterprise_Staging_Model_Staging $staging, $event = null);
    public function backupRun(Enterprise_Staging_Model_Staging $staging, $event = null);
    public function mergeRun(Enterprise_Staging_Model_Staging $staging, $event = null);
    public function rollbackRun(Enterprise_Staging_Model_Staging $staging, $event = null);

}
