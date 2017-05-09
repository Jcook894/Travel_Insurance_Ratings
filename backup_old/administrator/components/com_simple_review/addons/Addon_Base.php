<?php
/**
 *  $Id: Addon_Base.php 117 2009-08-10 13:44:14Z rowan $
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

// ensure this file is being included by a parent file
defined('_VALID_MOS')||defined( '_JEXEC' ) or die( 'Direct Access to this location is not allowed.' );

class Addon_Base
{
  	var $addonName=null;
  	var $addonPath=null;  	
  	var $friendlyName=null;  	
  	
   	var $tmpl = null;
  	
    var $hasCSS=false;
  	var $hasLanguage=false;
  	var $hasConfig=false;
	var $addonType=null;  
	
	var $_AddonManager=null;
	
	var $dependsOnModules = Array();	    	
  
 	function Addon_Base(&$addonManager, &$addonName, $initialise=true)
 	{
 		if($addonManager == null)
		{
			SRError::Display( "An error has occured. ERADDBASE0. Name:$addonName", true); 
		}
 		if(strtolower(get_class($addonManager)) != 'addon_manager')
		{
			SRError::Display( "An error has occured. ERADDBASE1. Name:$addonName Class:".get_class($addonManager), true); 
		}
		
		$this->_AddonManager =& $addonManager;		
		
        $this->_Includeconfig();  
    	//$this->_IncludeLanguageFile();
		$this->addonName = $addonName; 
		 	  
	 	if($initialise)
		{ 	  
        	$this->_IncludeCSS(); 
			$this->_LoadAddonData();      
		}
    }
    
	function Display()
	{
	  
	}   


	function _LoadDependencies()
	{
		SRError::Display( "function Depenencies not implemented in $this->addonPath");
		exit();	  
	}

	function GetURL($task, $parameters="", $useSef=true)
	{
	  	global $option;
	  	if ($parameters == "")
	  		$link =  "index.php?option=$option&amp;module=$this->addonName&amp;task=$task";
	  	else
	  		$link =  "index.php?option=$option&amp;module=$this->addonName&amp;task=$task&amp;$parameters";
			
		return $useSef ? sefRelToAbs($link) : $link;  
	}	

	function _IncludeCSS()
	{
	  		if (!$this->hasCSS)
	  			return;

			$cssFile =  $this->_AddonManager->Bridge->SiteUrl."administrator/components/com_simple_review/addons/$this->addonType/$this->addonName/$this->addonName.css";
			Simple_Review_Common::IncludeCSS($cssFile );
	}
		
	function _IncludeConfig()
	{
	  	if(!$this->hasConfig)
	  	{
			return;
		}	
		$configFile = "$this->addonPath/config.php";
	    if(!file_exists($configFile))
	    {
		  SRError::Display("Could not find file: $configFile<br/>In directory:<br/>$this->addonPath/");
		  exit();
		}
      	require_once($configFile);  		
	}
	
    function _LoadAddonData()
    {
		SRError::Display( "function _LoadAddonData not implemented in $this->addonPath");
		exit();	        
    }
	
	function GetString(&$addon, $key)
	{
		$langMod =& $this->_AddonManager->GetModule('Language_Module', false);
		return $langMod->GetString($addon, $key);
	}
}

?>	    