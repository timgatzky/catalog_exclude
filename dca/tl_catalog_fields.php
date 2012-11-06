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



class tl_catalog_fields_catalog_execlude extends Backend
{
	/**
	 * @var
	 */
	protected $userField = 'user';
	protected $strTemplate = 'be_catalog_entries_execlude_js'; 
	
	/**
	 * Execlude catalog entries that not belong to the current BE user
	 * @param object
	 * @param array
	 * @param object
	 * @param object
	 * @return array
	 * called from getCatalogDca HOOK
	 */
	public function execludeEntries($objField, $arrDca, Database_Result $objCatalogResult, Catalog $objCatalog)
	{
		if($objField->itemTable == 'tl_user' || $objField->type == 'author_field')
		{
			$this->import('BackendUser', 'User');
			// return if user is admin, or in ignore list
			if($this->User->isAdmin || in_array($this->User->username,$GLOBALS['CATALOG_EXECLUDE']['ignore_users']) || in_array($this->User->id,$GLOBALS['CATALOG_EXECLUDE']['ignore_users']) || in_array($this->User->email,$GLOBALS['CATALOG_EXECLUDE']['ignore_users']) )
			{
				return $arrDca;
			}
			
			$objCount = $this->Database->execute("SELECT COUNT(*) as count FROM ".$objCatalogResult->tableName);
			// return if catalog is empty
			if($objCount->count < 1)
			{
				return $arrDca;
			}
			
			$tableName = $objCatalogResult->tableName;
			
			$arrFilter = $this->Session->get('filter');
			$arrLimit = explode(',',$arrFilter[$tableName]['limit']);
			$strLimit = $arrFilter[$tableName]['limit'];
			$arrSorting = $this->Session->get('sorting');;
			$strSorting = $arrSorting[$tableName];
			
			#if(count($beFilter[$tableName]) > 0)
			#{
			#	foreach($beFilter[$tableName] as $f => $v)
			#	{
			#		if($f == 'limit')
			#		{
			#			$arrLimit = explode(',', $v);
			#		}
			#	}
			#}
			
			// fetch all entries listed (workaround for Session bug: When entering the page the session won't carry all entries)
			$objCurrentEntries = $this->Database->prepare("SELECT id FROM ".$objCatalogResult->tableName." ".(strlen($strSorting)? " ORDER BY ".$strSorting : "").(strlen($strLimit)? " LIMIT ".$strLimit : ""))
							->execute();
			
			// fetch all entries NOT property of the current BE user
			$objExeclude = $this->Database->prepare("SELECT id FROM ".$objCatalogResult->tableName." WHERE id IN(".implode(',',$objCurrentEntries->fetchEach('id')).") AND user NOT IN(".$this->User->id.") ".(strlen($strSorting)? " ORDER BY ".$strSorting : ""))
							->execute();
			
			$arrExeclude = $objExeclude->fetchEach('id');
			
			// return if all entries belong to the user
			if($objExeclude->numRows < 1) 
			{
				return $arrDca;
			}
			
			// NOT WORKING CORRECTLY! catalog filter is active in BE and only a part of entries is visible
			#$arrSession = $this->Session->getData();
			#$arrEntries = $arrSession['CURRENT']['IDS'];
			#
			#if(count($arrEntries) > 0)
			#{
			#	$arrExeclude = array_intersect($arrEntries,$arrExeclude);
			#}
			
			// store result in session, just for the taste of it (not really nessessary)
			$this->Session->set('catalog_execlude',$objExeclude);
			
			// generate mootools script
			$this->Template = new BackendTemplate($this->strTemplate);
			$this->Template->entries = $arrExeclude;
			$strBuffer = $this->Template->parse();
			
			$GLOBALS['TL_MOOTOOLS'][] = $strBuffer;
		}
		
		return $arrDca;
	}
}
?>