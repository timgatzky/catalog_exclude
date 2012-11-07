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

$GLOBALS['TL_DCA']['tl_catalog_items']['config']['oncreate_callback'][] = array('tl_catalog_items_catalog_execlude', 'modifyDCA');


class tl_catalog_items_catalog_execlude extends Backend
{
	/**
	 * @var
	 */
	protected $userField = 'user';
	
	/**
	 * Execlude catalog entries
	 * @param object
	 * @return void
	 */
	public function execludeEntries(DataContainer $dc)
	{
		$this->import('BackendUser', 'User');
		
		// return if user is admin, or in ignore list
		if($this->User->isAdmin || in_array($this->User->username,$GLOBALS['CATALOG_EXECLUDE']['ignore_users']) || in_array($this->User->id,$GLOBALS['CATALOG_EXECLUDE']['ignore_users']) || in_array($this->User->email,$GLOBALS['CATALOG_EXECLUDE']['ignore_users']) )
		{
			return;
		}
		
		// return if no user field exists
		$objFields = $this->Database->prepare("SELECT * FROM tl_catalog_fields WHERE pid=? AND itemTable=?".(in_array('catalog_author_field', $this->Config->getActiveModules()) ? " OR type='author_field'":"") )
						->limit(1)
						->execute($this->Session->get('CURRENT_ID'),'tl_user');
		
		if($objFields->numRows < 1)
		{
			return;
		}
		
		// HOOK set catalog field with user data
		if(strlen($GLOBALS['CATALOG_EXECLUDE']['user_field']))
		{
			$this->userField = $GLOBALS['CATALOG_EXECLUDE']['user_field'];
		}
		
		// fetch all entries that do not belong to the current BE user
		$objEntries = $this->Database->execute("SELECT * FROM ".$dc->table." WHERE ".$this->userField." IN(".$this->User->id.")");
						
		// return if none belongs to the current user
		if($objEntries->numRows < 1)
		{
			return;
		}
		
		$arrEntries = $objEntries->fetchEach('id');
	
		// set filter
		$GLOBALS['TL_DCA'][$dc->table]['list']['sorting']['filter'][] = array('user IN(?)',implode(',',$arrEntries));
	}

	/**
	 * Set callback for a dynamic table dca
	 * @param string
	 * @return string
	 */
	public function modifyDCA($strTable)
	{
		$GLOBALS['TL_DCA'][$strTable]['config']['onload_callback'][] = array('tl_catalog_items_catalog_execlude', 'execludeEntries');
		return $strTable;
	}

}

?>