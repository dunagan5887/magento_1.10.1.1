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

class Enterprise_Staging_Model_Mysql4_Staging_Collection extends Mage_Core_Model_Mysql4_Collection_Abstract
{
    protected function _construct()
    {
        $this->_init('enterprise_staging/staging');
    }

    /**
     * Set staging website filter into collection
     *
     * @param   mixed   $stagingWebsiteId (if object must be implemented getId() method)
     * @return  object  Enterprise_Staging_Model_Mysql4_Staging_Collection
     */
    public function addStagingWebsiteToFilter($stagingWebsiteId)
    {
        if (is_object($stagingWebsiteId)) {
            $stagingWebsiteId = $stagingWebsiteId->getId();
        }
        $this->addFieldToFilter('staging_website_id', (int) $stagingWebsiteId);

        return $this;
    }

    /**
     * Joining website name
     *
     * @return Enterprise_Staging_Model_Mysql4_Staging_Collection
     */
    public function addWebsiteName()
    {
        $this->getSelect()->joinLeft(
            array('site'=>$this->getTable('core/website')),
            "main_table.staging_website_id = site.website_id",
            array('name' => 'site.name')
        );

       return $this;
    }

    /**
     * Joining last log id and log action
     *
     * @return Enterprise_Staging_Model_Mysql4_Staging_Collection
     */
    public function addLastLogComment()
    {
        $subSelect1 = clone $this->getSelect();
        $subSelect2 = clone $this->getSelect();

        $subSelect1->reset();
        $subSelect2->reset();

        $subSelect1->from($this->getTable('enterprise_staging/staging_log'), array('staging_id', 'log_id', 'action'))
            ->order('log_id DESC');

        $subSelect2->from(array('t' => new Zend_Db_Expr('(' . $subSelect1 . ')')))
            ->group('staging_id');

       $this->getSelect()->joinLeft(array('staging_log' => new Zend_Db_Expr('(' . $subSelect2 . ')')), 'main_table.staging_id = staging_log.staging_id', array('log_id', 'action'));
       return $this;
    }

    /**
     * Convert items array to array for select options
     *
     * array(
     *      $index => array(
     *          'value' => mixed
     *          'label' => mixed
     *      )
     * )
     *
     * @return array
     */
    public function toOptionArray()
    {
        return parent::_toOptionArray('staging_id', 'name');
    }

    /**
     * Convert items array to hash for select options
     *
     * array($value => $label)
     *
     * @return array
     */
    public function toOptionHash()
    {
        return parent::_toOptionHash('staging_id', 'name');
    }

    /**
     * Set staging is sheduled flag filter into collection
     *
     * @return object Enterprise_Staging_Model_Mysql4_Staging_Collection
     */
    public function addIsSheduledToFilter()
    {
        $this->addFieldToFilter('merge_scheduling_date', array('notnull' => true));
        return $this;
    }
}
