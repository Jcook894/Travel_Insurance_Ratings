<?php
/**
 *  $Id: Tag_Module_Admin.php 96 2009-06-13 09:31:58Z rowan $
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

class Tag_Module_Admin extends Module_Base_Admin//name this
{   
  	var $_TagModule;
	function Tag_Module_Admin(&$addonManager, &$adminModuleName, &$moduleName)//name this
	{
		$this->addonPath = dirname(__FILE__);
	  	$this->hasCSS=false;
	  	$this->hasLanguage=true;
		$this->hasConfig=true;//admin doesn't actually have config	 
		
		$this->dependsOnModules = array('Tag_Module');
						
	  	parent::Module_Base_Admin($addonManager, $adminModuleName, $moduleName);
		
		$this->_TagModule =& $addonManager->GetModule('Tag_Module', false);
		
		$this->friendlyName = $this->GetString($this->_TagModule, 'Tags');
		$this->defaultTaskName = $this->GetString($this->_TagModule, 'AdminDescription');
				
	} 
	
	
	function Display($task)
	{
	  	if(!$task)
	  	{
	  	 	$task="list";
	  	}
		switch ($task)
		{		 			
			case "list":
			default:
			$this->_List();
			break;  					
		} 	
	}
			
	function _List()
	{
		global $Itemid;
		
		$SR_Addon_Manager =& Addon_Manager::Get();
				
	  	$tagModule = $SR_Addon_Manager->GetModule('Tag_Module', false);  
		$tagModule->ListTags();           	  
	}  	
	
	function DisplayConfiguration(&$srConfig, &$allPlugins)
	{
		$control_name="params";
		echo "<table>"; 
							
		//Tag_Module_Number_Of_Dynamic_Fields
		$settingName = $this->addonName."_Number_Of_Dynamic_Fields";
	    $control = SRHtmlControls::Text($settingName, constant($settingName), $control_name ,3);
	    $text = $this->GetString($this->_TagModule, 'ConfigNumberDynamic');
	    $tip  = $this->GetString($this->_TagModule, 'ConfigNumberDynamicTip');
		$srConfig->DisplaySingleTabParam($text,$tip, $control);					
		
		echo "</table>"; 	  
	}
	
	function SaveConfiguration($params)
	{
	  global $option;
	  $configfile = "$this->addonPath/config.php";
	  @chmod ($configfile, 0766);
	
	  $permission = is_writable($configfile);
	
	  if (!$permission) 
	  {	
	    $mosmsg = "Module Config file not writeable!<br>$configfile";	
	    $this->_AddonManager->Bridge->Redirect("index2.php?option=$option&act=configuration",$mosmsg);
	    return false;
	  }
	  
	  $config  = "<?php\n";
	
	  $config .= "defined('_VALID_MOS')||defined( '_JEXEC' ) or die( 'Direct Access to this location is not allowed.' );\n";
	
	  $config .= "/*\nThe contents of this file are subject to the Mozilla Public License\n"
	            ."Version 1.1 (the \"License\"); you may not use this file except in\n"
	            ."compliance with the License. You may obtain a copy of the License at\n"
	            ."http://www.mozilla.org/MPL/\n\n"
	            ."Software distributed under the License is distributed on an \"AS IS\"\n"
	            ."basis, WITHOUT WARRANTY OF ANY KIND, either express or implied. See the"
	            ."License for the specific language governing rights and limitations\n"
	            ."under the License.\n\n"
	            ."The Original Code is Simple Review.\n\n"
	            ."The Initial Developer of the Original Code is Rowan J Youngson.\n"
	            ."Portions created by Rowan J Youngson are Copyright (C) December 17 2005.\n"
	            ."All Rights Reserved.\n\n"
	            ."Contributor(s): Rowan J Youngson.\n*/\n";

	  $settingName = $this->addonName."_Number_Of_Dynamic_Fields";
	  $params[$settingName] = intval($params[$settingName]);
	  $params[$settingName] = $params[$settingName] <= 1 ? 1 	: $params[$settingName];
	  $params[$settingName] = $params[$settingName] >= 25 ? 25 	: $params[$settingName];	  
	  $config .="define('$settingName', '".$params[$settingName]."');\n";  
	         	

	  $config .= "?>";	
	  if ($fp = fopen($configfile, "w")) 
	  {	
	    fputs($fp, $config, strlen($config));	
	    fclose ($fp);	
	  }
	  return true;
	} 			
			
	
}
?>	