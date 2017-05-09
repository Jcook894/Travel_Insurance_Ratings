<?php
/**
 *  $Id: base.php 79 2009-05-11 13:50:57Z rowan $
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

class Plugin_Base extends Addon_Base_Frontend
{
	var $pluginType = null;
	var $canAttachToModules = null;
	//var $pluginTemplate = null;
 	function Plugin_Base(&$addonManager, &$pluginName, $initialise)
 	{
 	  	$this->addonType =_SR_ADDON_TYPE_PLUGIN; 
        parent::Addon_Base_Frontend($addonManager, $pluginName, $initialise);
	}
	
	function _IncludeCSS()
	{
	  		if (!$this->hasCSS)
	  			return;

			$cssFile =  $this->_AddonManager->Bridge->SiteUrl."administrator/components/com_simple_review/addons/$this->addonType/$this->pluginType/$this->addonName/$this->addonName.css";
			Simple_Review_Common::IncludeCSS($cssFile );
	}


	function LoadTemplate($templateName)
	{
		$this->_AddonTemplateExists($templateName);

		require_once($this->addonPath . "/$templateName" . '.html.php') ;
		
		$htmlClassName = $this->addonName.'_Html';
 	 	if($this->templates == null)
  	 	{
  	 	 	$this->templates = array();				
  	 	}		
		$this->templates["$templateName"] =	new $htmlClassName();
	}

	/*
	function _IncludeTemplate()
	{
		$this->_AddonTemplateExists($this->addonName);
		require_once($this->addonPath . "/$this->addonName" . '.html.php') ;
		$htmlClassName = $this->addonName.'_Html';
		$this->pluginTemplate = new $htmlClassName();
	}*/

	function &z_CreateTemplate($templateFileName, $useCache) {
		$tmpl =& patFactory::createTemplate();
	
		// Set which directory contains the template-files.
		$tmpl->setBasedir("$this->addonPath");
		if($useCache)
		{
			/**
			 * Use a template cache based on file system
			*/
			$tmpl->useTemplateCache( 'File', array(
			                                        'cacheFolder' => "$this->addonPath",
			                                        'lifetime' => 60 )
			                                      ); 
		}
		
		// Set which template-file to read.
		$tmpl->readTemplatesFromFile($templateFileName); 		
		return $tmpl;
	} 
	

	function _AddonTemplateExists($templateName)		 
	{
	  		$templateFile = "$templateName.html.php";
		    if(!file_exists("$this->addonPath/$templateFile"))
		    {
			  SRError::Display( "Could not find file: $templateFile<br/>In directory:<br/>$this->addonPath");
			  exit();
			}	    
	}  	
  		 	
}

?>