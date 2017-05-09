<?php
/**
 *  $Id: Addon_Manager.php 96 2009-06-13 09:31:58Z rowan $
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

/* ensure this file is being included by a parent file */
defined('_VALID_MOS')||defined( '_JEXEC' ) or die('Direct Access to this location is not allowed.');
require_once("Addon_Common.php");
require_once("plugins/base.php");
require_once("modules/base.php");


class Addon_Manager
{
	var $moduleBasePath = null;
	var $pluginBasePath = null;
	var $_LoadedModules = array();
	var $Database = null;
	var $Bridge = null;
    function Addon_Manager()
    {		
		$key = '__SR_Addon_Manager';
		if(array_key_exists($key, $GLOBALS))
		{
			 trigger_error('This class is a singleton class!',E_USER_ERROR);
		}	
	
		$this->Bridge =& SRBridgeManager::Get();
		 
        $this->moduleBasePath = $this->Bridge->PathComponentAdministrator.'/addons/modules';
        $this->pluginBasePath = $this->Bridge->PathComponentAdministrator.'/addons/plugins';		 
		$this->Database =& 	 $this->Bridge->Database;				
    }
    
    /**
    * Creates a singleton instance of the addonmanager.
    * Usage: $SR_Addon_Manager =& Addon_Manager::Get();
    */
    function &Get()
    {
		$key = '__SR_Addon_Manager';
		if(!array_key_exists($key, $GLOBALS) || $GLOBALS[$key] == null)
		{
			 $GLOBALS[$key] = new Addon_Manager(); 			 
		}
		return $GLOBALS[$key];		
    }
    
     /**
     * Return a singleton instance of $moduleName
     * @param string $moduleName the name of the module to load, will be the name of the modules directory.
     * @param boolean $initialise false if you just want an empty object
     * @return null|Module_Base returns null if cannot find the module, else returns an instance of the module.
     */  
   function &GetModule($moduleName, $initialise=false)
   {
     	 $allReadyLoaded =  array_key_exists($moduleName,$this->_LoadedModules);
     	 //may have already loaded it but not initialised it
     	 $needToReload	 =  ($initialise && $allReadyLoaded &&  !$this->_LoadedModules[$moduleName]->Initialised);
		 
     	 $module = null;
   		 if(!$allReadyLoaded || $needToReload)
   		 {
		    $modulePath = "$this->moduleBasePath/$moduleName/$moduleName.php"; 
			if(!file_exists($modulePath))	
			{
				SRError::Display("Unable to load module:$moduleName.", true);
				return $module;  
			} 
			require_once($modulePath); 		   
   		 	$this->_LoadedModules[$moduleName] =& new $moduleName($this, $moduleName, $initialise);
   		 	$this->_LoadDependentModules($this->_LoadedModules[$moduleName]);
   		 }
   		 $module =& $this->_LoadedModules[$moduleName]; 
   		 if($module == null)
   		 {
   		 	SRError::Display("Unable to load module:$moduleName.", true);   		 	
   		 }
   		 return $module;
   }
   
   function _LoadDependentModules($module)
   {
    	foreach($module->dependsOnModules as $dependentModule)
    	{
    		if(array_key_exists($dependentModule,$this->_LoadedModules) == false)
			{
				$this->GetModule($dependentModule, false);
			}  
    	}
   }
              
   /**
   * Returns a new instance of the specified modules administration
   */	
   function &LoadAdminModule($moduleName)
	{
	  	$moduleAdminName = "{$moduleName}_Admin";
	    $modulePath = "$this->moduleBasePath/$moduleName/$moduleAdminName.php";
		$loadedModule = null;   
		if(file_exists($modulePath))	
		{
			require_once($modulePath);
			$loadedModule =& new $moduleAdminName($this, $moduleAdminName, $moduleName);  			
		}	  	
		return $loadedModule;
	} 	

	/**
	* Returns an array full of admin modules
	*/
	function &LoadAdminModules()
	{
		$allModules = Array();
		$originalWD = getcwd();
		chdir( $this->moduleBasePath);
		$d=opendir('.');
		while($f = readdir($d)) 
		{  	     	
		   if(!is_dir($f) || $f == "." || $f == ".." ) continue;
	
	    	//modules start	    	
	    	$adminModule  =& $this->LoadAdminModule($f);
	    		    	  	    		    	  	
	    	if($adminModule == null)
	    	{
	    		continue;  
	    	}
	    	$allModules[] =& $adminModule;		
		}
		chdir($originalWD);
		return $allModules;	   
	}
	
   function &LoadPlugin($pluginName, $group, $initialise=true)
	{
	  
	  	$pluginPath = "$this->pluginBasePath/$group/$pluginName.php";
		if(!file_exists($pluginPath))	
		{
		  	SRError::Display("$pluginName does not exist in group $group");
			return null;  
		}	  	
		require_once("$this->pluginBasePath/$group/base.php");    	
		require_once($pluginPath);
		$plugin =& new $pluginName($this, $pluginName, $initialise);  
		//$plugin->_Database  = $this->_Database;
		//$plugin->_AddonManager  = $this;			
		$this->_LoadDependentModules($plugin); 	
		return $plugin;
	}
	/*
	* Create an array containing all plugins
	*/
	function &LoadPlugins()
	{	
		$allPlugins = Array();
		$originalWD = getcwd(); 
		chdir( $this->pluginBasePath );
		$d=opendir('.');
		while($f = readdir($d)) 
		{  
		  //if(is_dir($f) || $f=="base.php") continue;		     	
		   if(!is_dir($f) || $f == "." || $f == ".." ) continue;
		   
		   	//need to save top level dir before decending into the plugin
			$cwd = getcwd(); 
			
			//move into the plugin dir so is_dir() will work
			chdir("$cwd/$f");
			
			//will search dir for plugins
			$d2 = opendir('.');
			while($f2 = readdir($d2))
			{
			  	if(is_dir($f2) || $f2 == "base.php" || $f2 == "." || $f2 == ".." || $f2 == "index.html") continue;
			  
			  	//strip .php to get the actual class name
			  	$plugName= explode(".php", $f2);
			  	$plugin =   $f."::".$plugName[0];
		    	$allPlugins[] = $plugin;				  			  

			}
			//move back up a level
			chdir($cwd);	
		}
		chdir($originalWD);
		return $allPlugins;		  
	} 	    
}
?>