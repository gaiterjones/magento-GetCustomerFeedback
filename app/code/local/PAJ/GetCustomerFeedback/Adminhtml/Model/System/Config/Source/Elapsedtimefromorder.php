<?php
/**
 *  Social Media Marketing Module
 *  
 *  Copyright (C) 2011 paj@gaiterjones.com
 *
 *	This program is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation, either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 *  @category   PAJ
 *  @package    SocialMediaMarketing
 *  @license    http://www.gnu.org/licenses/ GNU General Public License
 * 
 *
 */

class PAJ_GetCustomerFeedback_Adminhtml_Model_System_Config_Source_Elapsedtimefromorder
{

    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        return array(
            array('value' => 0, 'label'=>Mage::helper('adminhtml')->__('Immediately after an order')),
            array('value' => 1, 'label'=>Mage::helper('adminhtml')->__('after 1 Day')),
            array('value' => 2, 'label'=>Mage::helper('adminhtml')->__('after 2 Days')),
			array('value' => 3, 'label'=>Mage::helper('adminhtml')->__('after 3 Days')),
			array('value' => 4, 'label'=>Mage::helper('adminhtml')->__('after 4 Days')),
			array('value' => 5, 'label'=>Mage::helper('adminhtml')->__('after 5 Days')),
			array('value' => 6, 'label'=>Mage::helper('adminhtml')->__('after 6 Days')),
			array('value' => 7, 'label'=>Mage::helper('adminhtml')->__('after 7 Days')),
        );
    }

}

?>