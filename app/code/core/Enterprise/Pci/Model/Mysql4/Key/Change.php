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
 * @package     Enterprise_Pci
 * @copyright   Copyright (c) 2010 Magento Inc. (http://www.magentocommerce.com)
 * @license     http://www.magentocommerce.com/license/enterprise-edition
 */

/**
 * Encryption key changer resource model
 *
 * The operation must be done in one transaction
 */
class Enterprise_Pci_Model_Mysql4_Key_Change extends Mage_Core_Model_Mysql4_Abstract
{
    /**
     * @var Enterprise_Pci_Model_Encryption
     */
    protected $_encryptor;

    /**
     * Initialize
     *
     */
    protected function _construct()
    {
        $this->_init('core/config_data', 'config_id');
    }

    /**
     * Re-encrypt all encrypted data in the database
     *
     * @param bool $safe Specifies whether wrapping re-encryption into the database transaction or not
     * @throws Exception
     */

    public function reEncryptDatabaseValues($safe = true)
    {
        $this->_encryptor = clone Mage::helper('core')->getEncryptor();

        // update database only
        if ($safe) {
            $this->beginTransaction();
        }
        try {
            $this->_reEncryptSystemConfigurationValues();
            $this->_reEncryptCreditCardNumbers();
            if ($safe) {
                $this->commit();
            }
        }
        catch (Exception $e) {
            if ($safe) {
                $this->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Change encryption key
     *
     * @param string $key
     * @return string
     * @throws Exception
     */
    public function changeEncryptionKey($key = null)
    {
        // prepare new key, encryptor and new file contents
        $file = Mage::getBaseDir('etc') . DS . 'local.xml';
        if (!is_writeable($file)) {
            throw new Exception(Mage::helper('enterprise_pci')->__('File %s is not writeable.', realpath($file)));
        }
        $contents = file_get_contents($file);
        if (null === $key) {
            $key = md5(time());
        }
        $this->_encryptor = clone Mage::helper('core')->getEncryptor();
        $this->_encryptor->setNewKey($key);
        $contents = preg_replace('/<key><\!\[CDATA\[(.+?)\]\]><\/key>/s', '<key><![CDATA[' . $this->_encryptor->exportKeys() . ']]></key>', $contents);

        // update database and local.xml
        $this->beginTransaction();
        try {
            $this->_reEncryptSystemConfigurationValues();
            $this->_reEncryptCreditCardNumbers();
            file_put_contents($file, $contents);
            $this->commit();
            return $key;
        }
        catch (Exception $e) {
            $this->rollBack();
            throw $e;
        }
    }

    /**
     * Gather all encrypted system config values and re-encrypt them
     *
     */
    protected function _reEncryptSystemConfigurationValues()
    {
        // look for encrypted node entries in all system.xml files
        $paths = Mage::getSingleton('adminhtml/config')->getEncryptedNodeEntriesPaths();

        // walk through found data and re-encrypt it
        if ($paths) {
            $table = $this->getTable('core/config_data');
            $values = $this->_getReadAdapter()->fetchPairs($this->_getReadAdapter()->select()
                ->from($table, array('config_id', 'value'))
                ->where('`path` IN (?)', $paths)
                ->where('`value` <> ?', ''));
            foreach ($values as $configId => $value) {
                $this->_getWriteAdapter()->update($table,
                    array('value' => $this->_encryptor->encrypt($this->_encryptor->decrypt($value))),
                    "config_id = {$configId}");
            }
        }
    }

    /**
     * Gather saved credit card numbers from sales order payments and re-encrypt them
     *
     */
    protected function _reEncryptCreditCardNumbers()
    {
        $table = $this->getTable('sales/order_payment');
        $select = $this->_getWriteAdapter()->select()
            ->from($table, array('entity_id', 'cc_number_enc'));

        $attributeValues = $this->_getWriteAdapter()->fetchPairs($select);
        // save new values
        foreach ($attributeValues as $valueId => $value) {
            $this->_getWriteAdapter()->update($table,
                array('cc_number_enc' => $this->_encryptor->encrypt($this->_encryptor->decrypt($value))),
                "entity_id = {$valueId}");
        }
    }
}
