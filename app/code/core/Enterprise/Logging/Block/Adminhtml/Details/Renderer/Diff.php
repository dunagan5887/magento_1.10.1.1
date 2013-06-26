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
 * @package     Enterprise_Logging
 * @copyright   Copyright (c) 2010 Magento Inc. (http://www.magentocommerce.com)
 * @license     http://www.magentocommerce.com/license/enterprise-edition
 */

/**
 * Difference columns renderer
 *
 */
class Enterprise_Logging_Block_Adminhtml_Details_Renderer_Diff
    extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract
{
    /**
     * Render the grid cell value
     *
     * @param Varien_Object $row
     * @return string
     */
    public function render(Varien_Object $row)
    {
        $html = '-';
        $columnData = $row->getData($this->getColumn()->getIndex());
        $specialFlag = false;
        try {
            $dataArray = unserialize($columnData);
            if (is_bool($dataArray)) {
                $html = $dataArray ? 'true' : 'false';
            }
            elseif (is_array($dataArray)) {
                if (isset($dataArray['__no_changes'])) {
                    $html = $this->__('No changes');
                    $specialFlag = true;
                }
                if (isset($dataArray['__was_deleted'])) {
                    $html = $this->__('Item was deleted');
                    $specialFlag = true;
                }
                if (isset($dataArray['__was_created'])) {
                    $html = $this->__('N/A');
                    $specialFlag = true;
                }
                $dataArray = (array)$dataArray;
                if (!$specialFlag) {
                    $html = '<dl>';
                    foreach ($dataArray as $key => $value) {
                        $html .= '<dt>' . $key . '</dt><dd>' . $this->htmlEscape($value) . '</dd>';
                    }
                    $html .= '</dl>';
                }
            }
            else {
                $html = $columnData;
            }
        }
        catch (Exception $e){
            $html = $columnData;
        }
        return $html;
    }
}
