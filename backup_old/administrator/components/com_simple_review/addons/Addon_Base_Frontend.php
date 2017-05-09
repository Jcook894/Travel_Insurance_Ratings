<?php
/**
 *  $Id: Addon_Base_Frontend.php 101 2009-06-13 16:13:46Z rowan $
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
class Addon_Base_Frontend  extends Addon_Base
{
  	
  	//var $templateFile=null;
  	var $cssFile=null;
	var $templates=null;


 	function Addon_Base_Frontend(&$addonManager, &$addonName, $initialise=true)
 	{ 
 	  	parent::Addon_Base($addonManager, $addonName, $initialise); 	  	
	}
  	
  	function LoadTemplate($templateName)
  	{
	  	$this->_AddonTemplateExists($templateName);
  	 	if($this->templates == null)
  	 	{
  	 	 	$this->templates = array();				
  	 	}
		
		require_once("$this->addonPath/templates/$templateName.html.php") ;
  	 	//$this->templates["$templateName"] =& $this->_CreateTemplate("$templateName.html.php", $useCache); 	 
		$htmlClassName = $templateName.'_Html';
		$this->templates["$templateName"] = new $htmlClassName();
  	}
  	

	function Display()
	{
		$this->_DisplayHeader();
		$this->_DisplayMain();  
		$this->_DisplayFooter();	  
	}  

	function _DisplayHeader()
	{
		echo "<h1>function DisplayHeader not implemented in $this->addonPath</h1>";
		exit();
	}
	
	function _DisplayMain()
	{
		echo "<h1>function DisplayMain not implemented in $this->addonPath</h1>";
		exit();	 	  
	}
	
	function _DisplayFooter()
	{
		echo "<h1>function DisplayFooter not implemented in $this->addonPath</h1>";
		exit();	 
	}
	
	function _LoadMyPlugins($position)
	{
	  	global $addonManager;
		$selectedPlugs = explode("||", constant($this->addonName."_$position") ); 
		foreach ($selectedPlugs as $plug)
		{
		  	 $plugInfo = explode("::", $plug ); 	
		}	  
	}
	
	function DisplayEdit()
	{
		echo "<h1>function DisplayEdit not implemented in $this->addonPath</h1>";
		exit();	  
	}	
	
	    			
	function _AddonTemplateExists($templateName)		 
	{
	  		$templateFile = "$templateName.html.php";
		    if(!file_exists("$this->addonPath/templates/$templateFile"))
		    {
			  SRError::Display( "Could not find file: $templateFile<br/>In directory:<br/>$this->addonPath/templates");
			  exit();
			}	    
	}  
}

?>