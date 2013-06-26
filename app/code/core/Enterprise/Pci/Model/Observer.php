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
 * Pci observer model
 *
 * Implements hashes upgrading
 */
class Enterprise_Pci_Model_Observer
{
    const ADMIN_USER_LOCKED = 243;

    /**
     * Admin locking and password hashing upgrade logic implementation
     *
     * @param Varien_Event_Observer $observer
     * @throws Mage_Core_Exception
     */
    public function adminAuthenticate($observer)
    {
        $password = $observer->getEvent()->getPassword();
        $user     = $observer->getEvent()->getUser();
        $resource = Mage::getResourceSingleton('enterprise_pci/admin_user');
        $authResult = $observer->getEvent()->getResult();

        // update locking information regardless whether user locked or not
        if ((!$authResult) && ($user->getId())) {
            $now = time();
            $lockThreshold = $this->getAdminLockThreshold();
            $maxFailures = (int)Mage::getStoreConfig('admin/security/lockout_failures');
            if (!($lockThreshold && $maxFailures)) {
                return;
            }
            $failuresNum = (int)$user->getFailuresNum() + 1;
            if ($firstFailureDate = $user->getFirstFailure()) {
                $firstFailureDate = new Zend_Date($firstFailureDate, Varien_Date::DATETIME_INTERNAL_FORMAT);
                $firstFailureDate = $firstFailureDate->toValue();
            }

            $updateFirstFailureDate = false;
            $updateLockExpires      = false;
            // set first failure date when this is first failure or last first failure expired
            if (1 === $failuresNum || !$firstFailureDate || (($now - $firstFailureDate) > $lockThreshold)) {
                $updateFirstFailureDate = $now;
            }
            // otherwise lock user
            elseif ($failuresNum >= $maxFailures) {
                $updateLockExpires = $now + $lockThreshold;
            }
            $resource->updateFaiure($user, $updateLockExpires, $updateFirstFailureDate);
        }

        // check whether user is locked
        if ($lockExpires = $user->getLockExpires()) {
            $lockExpires = new Zend_Date($lockExpires, Varien_Date::DATETIME_INTERNAL_FORMAT);
            $lockExpires = $lockExpires->toValue();
            if ($lockExpires > time()) {
                throw new Mage_Core_Exception(Mage::helper('enterprise_pci')->__('This account is locked.'), self::ADMIN_USER_LOCKED);
            }
        }

        if (!$authResult) {
            return;
        }

        $resource->unlock($user->getId());

        /**
         * Check whether the latest password is expired
         * Side-effect can be when passwords were changed with different lifetime configuration settings
         */
        if ($latestPassword = Mage::getResourceSingleton('enterprise_pci/admin_user')->getLatestPassword($user->getId())) {
            if (isset($latestPassword['expires']) && ((int)$latestPassword['expires'] < time() && Mage::getStoreConfig('admin/security/password_lifetime') !== '')) {
                if ($this->isPasswordChangeForced()) {
                    $message = Mage::helper('enterprise_pci')->__('Your password has expired, you must change it now.');
                } else {
                    $myAccountUrl = Mage::getSingleton('adminhtml/url')->getUrl('adminhtml/system_account/');
                    $message = Mage::helper('enterprise_pci')->__('Your password has expired, please <a href="%s">change it</a>.', $myAccountUrl);
                }
                Mage::getSingleton('adminhtml/session')->addNotice($message);
                if ($message = Mage::getSingleton('adminhtml/session')->getMessages()->getLastAddedMessage()) {
                    $message->setIdentifier('enterprise_pci_password_expired')->setIsSticky(true);
                    Mage::getSingleton('admin/session')->setPciAdminUserIsPasswordExpired(true);
                }
            }
        }

        // upgrade admin password
        if (!Mage::helper('core')->getEncryptor()->validateHashByVersion($password, $user->getPassword())) {
            Mage::getModel('admin/user')->load($user->getId())
                ->setNewPassword($password)->setForceNewPassword(true)
                ->save();
        }
    }

    /**
     * Upgrade API key hash when api user has logged in
     *
     * @param Varien_Event_Observer $observer
     */
    public function upgradeApiKey($observer)
    {
        $apiKey = $observer->getEvent()->getApiKey();
        $model  = $observer->getEvent()->getModel();
        if (!Mage::helper('core')->getEncryptor()->validateHashByVersion($apiKey, $model->getApiKey())) {
            Mage::getModel('acl/user')->load($model->getId())->setNewApiKey($apiKey)->save();
        }
    }

    /**
     * Upgrade customer password hash when customer has logged in
     *
     * @param Varien_Event_Observer $observer
     */
    public function upgradeCustomerPassword($observer)
    {
        $password = $observer->getEvent()->getPassword();
        $model    = $observer->getEvent()->getModel();
        if (!Mage::helper('core')->getEncryptor()->validateHashByVersion($password, $model->getPassword())) {
            $model->changePassword($password, false);
        }
    }

    /**
     * Harden admin password change.
     *
     * New password must be minimum 7 chars length and include alphanumeric characters
     * The password is compared to at least last 4 previous passwords to prevent setting them again
     *
     * @param Varien_Event_Observer $observer
     * @throws Mage_Core_Exception
     */
    public function checkAdminPasswordChange($observer)
    {
        /* @var $user Mage_Admin_Model_User */
        $user = $observer->getEvent()->getObject();
        $password = ($user->getNewPassword() ? $user->getNewPassword() : $user->getPassword());
        if ($password && !$user->getForceNewPassword() && $user->getId()) {
            // check whether password was used before
            $resource     = Mage::getResourceSingleton('enterprise_pci/admin_user');
            $passwordHash = Mage::helper('core')->getHash($password, false);
            foreach ($resource->getOldPasswords($user) as $oldPasswordHash) {
                if ($passwordHash === $oldPasswordHash) {
                    Mage::throwException(Mage::helper('enterprise_pci')->__('This password was used earlier, try another one.'));
                }
            }
        }
    }

    /**
     * Save new admin password
     *
     * @param Varien_Event_Observer $observer
     */
    public function trackAdminNewPassword($observer)
    {
        /* @var $user Mage_Admin_Model_User */
        $user = $observer->getEvent()->getObject();
        if ($user->getId()) {
            $password = $user->getNewPassword();
            $passwordLifetime = $this->getAdminPasswordLifetime();
            if ($passwordLifetime && $password && !$user->getForceNewPassword()) {
                $resource     = Mage::getResourceSingleton('enterprise_pci/admin_user');
                $passwordHash = Mage::helper('core')->getHash($password, false);
                $resource->trackPassword($user, $passwordHash, $passwordLifetime);
                Mage::getSingleton('adminhtml/session')->getMessages()->deleteMessageByIdentifier('enterprise_pci_password_expired');
                Mage::getSingleton('admin/session')->unsPciAdminUserIsPasswordExpired();
            }
        }
    }

    /**
     * Get admin lock threshold from configuration
     *
     * @return int
     */
    public function getAdminLockThreshold()
    {
        return 60 * (int)Mage::getStoreConfig('admin/security/lockout_threshold');
    }

    /**
     * Get admin password lifetime
     *
     * @return int
     */
    public function getAdminPasswordLifetime()
    {
        return 86400 * (int)Mage::getStoreConfig('admin/security/password_lifetime');
    }

    /**
     * Force admin to change password
     *
     * @param Varien_Event_Observer $observer
     */
    public function forceAdminPasswordChange($observer)
    {
        if (!$this->isPasswordChangeForced()) {
            return;
        }
        $session = Mage::getSingleton('admin/session');
        if (!$session->isLoggedIn()) {
            return;
        }
        $controller = $observer->getEvent()->getControllerAction();
        if (Mage::getSingleton('admin/session')->getPciAdminUserIsPasswordExpired()) {
            if (!in_array($controller->getFullActionName(), array('adminhtml_system_account_index', 'adminhtml_system_account_save', 'adminhtml_index_logout'))) {
                if (Mage::getSingleton('admin/session')->isAllowed('admin/system/myaccount')) {
                    $controller->getResponse()->setRedirect(Mage::getSingleton('adminhtml/url')->getUrl('adminhtml/system_account/'));
                    $controller->setFlag('', Mage_Core_Controller_Varien_Action::FLAG_NO_DISPATCH, true);
                    $controller->setFlag('', Mage_Core_Controller_Varien_Action::FLAG_NO_POST_DISPATCH, true);
                } else {
                    /*
                     * if admin password is expired and access to 'My Account' page is denied
                     * than we need to do force logout with error message
                     */
                    Mage::getSingleton('admin/session')->unsetAll();
                    Mage::getSingleton('adminhtml/session')->unsetAll();
                    Mage::getSingleton('adminhtml/session')->addError(
                        Mage::helper('enterprise_pci')->__('Your password has expired, please contact administrator.')
                    );
                    $controller->getRequest()->setDispatched(false);
                }
            }
        }
    }

    /**
     * Check whether password change is forced
     *
     * @return bool
     */
    public function isPasswordChangeForced()
    {
        return (bool)(int)Mage::getStoreConfig('admin/security/password_is_forced');
    }

    /**
     * Custom log Encryption Key save action
     *
     * @deprecated after 1.6.0.0
     * @param Varien_Simplexml_Element $config
     * @param Enterprise_Logging_Model_Event $eventModel
     * @return bool
     */
    public function logEncryptionKeySave($config, $eventModel)
    {
        return true;
    }


}
