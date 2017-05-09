<?php
/**
 *  $Id: base.php 85 2009-05-25 14:29:52Z rowan $
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

class Module_Base extends Addon_Base_Frontend
{
	var $Initialised = false;
 	function Module_Base(&$addonManager, &$addonName, $initialise)
 	{
 	  	$this->addonType =_SR_ADDON_TYPE_MODULE; 
        parent::Addon_Base_Frontend($addonManager, $addonName, $initialise);
	}
	//TODO:
	function _DoMambots(&$content)
	{
	  	global $_MAMBOTS, $mainframe;
	  	$params = null;
	  	/*
		*example of adding params	
	  	$menu = $mainframe->get( 'menu' );
		$params = new mosParameters( $menu->params );
		$params->set( 'intro_only', 0 );
		$params->set( 'popup', 0 );
		*/
		
		if(method_exists($mainframe, "get"))
		{
			$menu = $mainframe->get( 'menu' );
			
			//TODO:seems to be null for Joomla 1.5
			if($menu == null)
			{
				return $content;
			}
			
			$params = new mosParameters( $menu->params );
			//dont do mos page break
			$params->set( 'intro_only', 1 );
	
			$_MAMBOTS->loadBotGroup( 'content' );
			$row =& new stdClass();
			$row->text =&  $content;
			$_MAMBOTS->trigger( 'onPrepareContent', array( &$row, &$params ), true );
			return $row->text;
		}
		else
		{
		 	return $content;
		}
		
		
	} 		 	
	
	function _DisplayHeader()
	{
		$this->_LoadMyPlugins('HEAD');
	}
	
	function _DisplayMain()
	{		
	}

	function _DisplayFooter()
	{
		$this->_LoadMyPlugins('FOOT');
	}
	
	function _LoadMyPlugins($position)
	{
	  	$selectedPlugs = constant($this->addonName."_$position");

		if($selectedPlugs == "")
			return;	
		$selectedPlugs = explode("||", $selectedPlugs ); 
		foreach ($selectedPlugs as $plug)
		{
		  	 $plugInfo = explode("::", $plug ); 	
			 $plugObj =& $this->_AddonManager->LoadPlugin($plugInfo[1], $plugInfo[0]);
			 $plugObj->Display();
		}	  
	}	
}

?>