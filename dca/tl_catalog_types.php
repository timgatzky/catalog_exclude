<?php if (!defined('TL_ROOT')) die('You can not access this file directly!');

/**
 * Contao Open Source CMS
 * Copyright (C) 2005-2010 Leo Feyer
 *
 * Formerly known as TYPOlight Open Source CMS.
 *
 * This program is free software: you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation, either
 * version 3 of the License, or (at your option) any later version.
 * 
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU
 * Lesser General Public License for more details.
 * 
 * You should have received a copy of the GNU Lesser General Public
 * License along with this program. If not, please visit the Free
 * Software Foundation website at <http://www.gnu.org/licenses/>.
 *
 * PHP version 5
 * @copyright  Tim Gatzky 2012 
 * @author     Tim Gatzky <info@tim-gatzky.de>
 * @package    catalog_execlude 
 * @license    LGPL 
 * @filesource
 */

$GLOBALS['TL_DCA']['tl_catalog_types']['config']['onload_callback'][] = array('tl_catalog_types_catalog_execlude','countEntries');

class tl_catalog_types_catalog_execlude extends Backend
{
	/**
	 * @var
	 */
	protected $userField = 'user';
	protected $strTemplate = 'be_catalog_execlude_js'; 
	
	/**
	 * Execlude catalog entries that not belong to the current BE user
	 * @param object
	 * @return void or object
	 * called from onload_callback
	 */
	public function countEntries(DataContainer $dc)
	{
		$this->import('BackendUser', 'User');
		// return if user is admin, or in ignore list
		if($this->User->isAdmin || in_array($this->User->username,$GLOBALS['CATALOG_EXECLUDE']['ignore_users']) || in_array($this->User->id,$GLOBALS['CATALOG_EXECLUDE']['ignore_users']) || in_array($this->User->email,$GLOBALS['CATALOG_EXECLUDE']['ignore_users']) )
		{
			return $dc;
		}
		
		$arrSession = $this->Session->getData();
		$arrCatalogs = $arrSession['CURRENT']['IDS'];
		$objCatalogs = $this->Database->prepare("SELECT * FROM ".$dc->table." WHERE id IN(".implode(',',$arrCatalogs).") ORDER BY name")
						->execute();
		if($objCatalogs->numRows < 1)
		{
			return $dc;
		}
		
		// count entries of current BE user
		$arrEntries = array();
		while($objCatalogs->next())
		{
			// check if catalog contains a user field
			if(!$this->Database->fieldExists($this->userField,$objCatalogs->tableName))
			{
				continue;
			}
			
			$objCount = $this->Database->prepare("SELECT COUNT(*) as count FROM ".$objCatalogs->tableName." WHERE ".$this->userField." IN(".$this->User->id.")")
							->execute();
			
			$arrEntries[$objCatalogs->id] = array
			(
				'count'	=> $objCount->count,
			);
		}
		
		// generate mootools script
		$this->Template = new BackendTemplate($this->strTemplate);
		$this->Template->entries = $arrEntries;
		$strBuffer = $this->Template->parse();
		
		$GLOBALS['TL_MOOTOOLS'][] = $strBuffer;
		
		return $dc;
	}
}
?>