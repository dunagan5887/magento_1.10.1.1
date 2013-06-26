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
 * @package     Enterprise_GiftCardAccount
 * @copyright   Copyright (c) 2010 Magento Inc. (http://www.magentocommerce.com)
 * @license     http://www.magentocommerce.com/license/enterprise-edition
 */

class Enterprise_GiftCardAccount_Model_Mysql4_Pool extends Enterprise_GiftCardAccount_Model_Mysql4_Pool_Abstract
{
    protected function _construct()
    {
        $this->_init('enterprise_giftcardaccount/pool', 'code');
    }

    public function saveCode($code)
    {
        $field = $this->getIdFieldName();
        $this->_getWriteAdapter()->insert(
            $this->getMainTable(),
            array(
                $field=>$code
            )
        );
    }

    public function exists($code)
    {
        $select = $this->_getReadAdapter()->select();
        $select->from($this->getMainTable(), $this->getIdFieldName());
        $select->where($this->getIdFieldName() . ' = ?', $code);

        if ($this->_getReadAdapter()->fetchOne($select) === false){
            return false;
        }
        return true;
    }
}
