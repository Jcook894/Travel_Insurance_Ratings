<?php
/**
 *  $Id: Language_Module.php 97 2009-06-13 10:19:57Z rowan $
 *
 * 	Copyright (C) 2005-2009  Rowan Youngson
 * 
 *	This file is part of Simple Review.
 *
 *	Simple Review is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation, either version 3 of the License, or
 *  (at your option) any later version.

 *  Simple Review is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.

 *  You should have received a copy of the GNU General Public License
 *  along with Simple Review.  If not, see <http://www.gnu.org/licenses/>.
*/
defined('_VALID_MOS')||defined( '_JEXEC' ) or die( 'Direct Access to this location is not allowed.' );
class Language_Module extends Module_Base
{	
	var $loadedLanguagesByCategory = array();
	var $language = 'en-GB';
	/**
	 * 
	 * @return 
	 * @param object $addonManager
	 * @param object $moduleName
	 * @param boolean $initialise This parameter is ignored and will always be true.
	 */
	function Language_Module(&$addonManager, &$moduleName, $initialise)
	{ 
		$this->friendlyName = "Language List.";
		$this->addonPath = dirname(__FILE__);
		
	  	$this->hasCSS=false;
	  	$this->hasLanguage=true;
	  	$this->hasConfig=false;	
			    				
	  	parent::Module_Base($addonManager, $moduleName, true);
		
		$this->Initialised = true;	  	    	   		
	}	
	
	function GetLanguages()
	{
		$languagePath = "$this->addonPath/languages";
		$allLanguages = array();
		$originalWD = getcwd();
		chdir($languagePath);
		$d=opendir('.');
		while($f = readdir($d)) 
		{  	   	
		   if(!is_dir($f) || $f == "." || $f == ".." ) continue;
	
			$lang = new stdClass();
			$lang->text = ucwords($f);
			$lang->value = $f;
	    	$allLanguages[] = $lang;		
		}
		chdir($originalWD);		
		
		return $allLanguages;
	}
	
	function GetString(&$addon, $key)
	{
		if($addon == null)
		{
			SRError::Display("Addon is null.");
		}
		if(!is_subclass_of ($addon,'Addon_Base'))
		{
			SRError::Display($this->friendlyName.'::GetString $addon must be a subclass of Addon_Base, class:'.get_class($addon), true);
		}
		if(!$addon->hasLanguage)
		{
			return "<span style='color:Red'>$addon->addonName:$key:NoLang</span>";
		}
		if(!array_key_exists($addon->addonName, $this->loadedLanguagesByCategory))
		{
			$file = "$this->addonPath/languages/$this->language/$addon->addonName.php";
			if(!file_exists($file))
			{
				return "<span style='color:Red'>$addon->addonName:$key:NoLangFile</span>";
			}
        	$this->loadedLanguagesByCategory[$addon->addonName] = include_once($file);				
		}
		
		if(!is_array($this->loadedLanguagesByCategory[$addon->addonName]))
		{
			return "<span style='color:Red'>$addon->addonName:$key:LangFileIncorrectFormat</span>";
		}		
		
		$langForCat = $this->loadedLanguagesByCategory[$addon->addonName];
		if(!array_key_exists($key, $langForCat))
		{
			return "<span style='color:Red'>$key</span>";	
		}
		return $langForCat[$key];
	}
	
    function _LoadAddonData(){}
}
?>