<?php
/**
 *  $Id: Addon_Base_Admin.php 77 2009-05-11 06:19:56Z rowan $
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

/** ensure this file is being included by a parent file */
defined('_VALID_MOS')||defined( '_JEXEC' ) or die('Direct Access to this location is not allowed.');
class Addon_Base_Admin extends Addon_Base
{
  	var $adminAddonName =  null;
  	var $defaultTaskName = null;
  	var $showOnConfigScreen = true;
  	
 	function Addon_Base_Admin(&$addonManager, &$adminAddonName, &$addonName)
 	{
 		$bridge =& SRBridgeManager::Get();
		$bridge->IncludeBridgeControls();
		
 	  	parent::Addon_Base($addonManager, $addonName);
 	  	
 	  	$this->adminAddonName = $adminAddonName;
		        
	}	
	function Display($task = "")
	{
		SRError::Display( "function: Display not implemented in $this->addonPath", true);	  
	} 	 	
	function DisplayConfiguration(&$srConfig, &$allPlugins)
	{
		SRError::Display("function: DisplayConfiguration not implemented in $this->addonPath", true);	  
	} 

	function &_FilterAttachablePluginsInfo(&$allPluginInfo)
	{
		$SR_Addon_Manager =& Addon_Manager::Get();
		$attachablePluginsInfo = array();
		foreach($allPluginInfo as $pluginInfo)
		{
			$plugMetadata = explode("::", $pluginInfo ); 	
			$plugin =& $SR_Addon_Manager->LoadPlugin($plugMetadata[1], $plugMetadata[0], false);
			if(in_array($this->addonName,$plugin->canAttachToModules))
			{
				$attachablePluginsInfo[] = $pluginInfo;
			}
		}
		
		return $attachablePluginsInfo;
	}

	/*
	* Position should be HEAD, MID or FOOD
	*/
	function _LoadPluginConfiguration(&$allPluginsInfo, $position="HEAD")
	{
	  	 $selected =& $this->_CurrentSelectedPlugins($position);
		 
		 $attachablePluginsInfo =& $this->_FilterAttachablePluginsInfo($allPluginsInfo);
		 
		 $pluginCount = count($attachablePluginsInfo);
		 
		 $selectedName = $this->addonName."_$position";
		 $selectedID = $selectedName;
		 
		 $allName = $this->addonName."ALL_$position";
		 $allID = $allName;			 
		 
		 ?>		 
		 <script language="JavaScript" type="text/javascript">
		 SRAddToPluginList("<?php echo $selectedID;?>");
		 </script>	
		 <?php
		 $config = "<table><tr><td>\n";
		 $config .= SRHtmlControls::SelectListBasic($selectedName,$selected, "id='$selectedID' size='$pluginCount' MULTIPLE ", $selected);
		 $config .= "\n</td><td>\n";
		
		 $config .= "<input class='button' type='button' value='>>' onclick=\"SRdelSelectedFromList('$selectedID');\" title='Remove' />\n";		
			 		
		 $config .= "<br />\n";			 		
			 		
		 $config .= "<input class='button' type='button' value='<<' onclick=\"SRaddSelectedToList('$allID','$selectedID');\" title='Add' />\n";

		 $config .= "\n</td><td>\n";
				 		 
		 $config .= SRHtmlControls::SelectListBasic($allName,$attachablePluginsInfo, "id='$allID' size='$pluginCount'", array());
		 $config .= "\n</td></tr></table>\n";
		 		 
		 return $config;

	}

	
	/*
	* Position should be string "HEAD" or "FOOT"
	*/	
	function &_CurrentSelectedPlugins($position)
	{			
		$selected = constant($this->addonName."_$position");
		if($selected == "")
		{
			$selected = array();
		}
		else
		{
			$selected = explode("||", constant($this->addonName."_$position") );
		}	
		return $selected;	  	  
	}
	
	function GetURL($task, $parameters="", $useSef=true)
	{
	  	global $option;
	  	if ($parameters == "")
	  		return "index2.php?option=$option&amp;module=$this->addonName&amp;task=$task";
	  	else
	  		return "index2.php?option=$option&amp;module=$this->addonName&amp;task=$task&amp;$parameters";
	}
	
	function SaveConfiguration($params)
	{
	  SRError::Display("function: SaveConfiguration not implemented in $this->addonPath", true);
	} 
			
    function _LoadAddonData()
    {
		$this->_IncludeConfig();    
    }
	
	/*
	*Subclasses should implement the below 6 CRUD methods
	*/
	function _List()
	{
	  
	}

	function _New()
	{
	 //mosCommonHTML::loadOverlib(); 
	}
	function _Edit($id)
	{	
	  //mosCommonHTML::loadOverlib();  
	}
	function _Delete(&$ids)
	{
	  
	}
	function _Publish(&$ids, $published)
	{
	  
	}

	function _Save($apply=false)
	{
	  
	}			 
  
} 

class Module_Base_Admin extends Addon_Base_Admin
{
 	function Module_Base_Admin(&$addonManager, &$adminAddonName, &$addonName)
 	{
 	  	$this->addonType =_SR_ADDON_TYPE_MODULE; 
        parent::Addon_Base_Admin($addonManager, $adminAddonName, $addonName);
	}	 	
}

?>