<?php
/**
 *  $Id: SRBridgeCMS.php 120 2009-09-13 05:37:35Z rowan $
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
require_once("SRBridgeDatabaseBase.php");
class SRBridgeCMS
{
	var $SystemToBridge;
	
	var $PathComponentFrontEnd;
	var $PathComponentAdministrator;
	var $PathJoomla;
	var $SiteUrl;
	var $InDebugMode;	
	var $Database = null;
	var $CurrentUser = null;
	
	function SRBridgeCMS()
	{
	    if(strtolower(get_class($this))=='srbridgecms' || !is_subclass_of ($this,'SRBridgeCMS'))
		{
	        trigger_error('This class is abstract. It cannot be instantiated!',E_USER_ERROR);
	    }		
	}
	
	function GetParameter( &$array, $key, $default = null, $type = ''){}
	function Redirect($url, $text){}
	function RewriteUrl($url){}
	function SetPageTitle($title){}
	function IncludeJQuery(){}
	
	function IncludeBridgeControls()
	{
		require_once("SRBridgeControls$this->SystemToBridge.php");		
	}	

}

class SRBridgeManager
{
	var $_Bridge;
		
	function SRBridgeManager()
	{
		$key = "__SR_BRIDGEMANAGER";
		if(array_key_exists($key, $GLOBALS))
		{
			 trigger_error('This class is a singleton class!',E_USER_ERROR);
		}	
		
		if(defined('_JEXEC'))
		{
			require_once("SRBridgeJoomla15.php");
			$this->_Bridge = new SRBridgeJoomla15();
		}		
		elseif(defined('_VALID_MOS'))
		{
			require_once("SRBridgeJoomla10.php");
			$this->_Bridge = new SRBridgeJoomla10();
		}
		else
		{
			trigger_error('Unable to find bridge.',E_USER_ERROR);
		}	
		
		require_once($this->_Bridge->PathComponentAdministrator.'/globals.php');
		require_once($this->_Bridge->PathComponentAdministrator.'/config.simple_review.php');
		require_once($this->_Bridge->PathComponentAdministrator.'/addons/Addon_Base.php');
		require_once($this->_Bridge->PathComponentAdministrator.'/addons/Addon_Base_Frontend.php');
		require_once($this->_Bridge->PathComponentAdministrator.'/addons/Addon_Manager.php');				
	}
	
    /**
    * Creates a singleton instance of the Bridge.
    * Usage: $SR_Bridge_Manager =& SRBridgeManager::Get();
    */
    function &Get()
    {
		$key = "__SR_BRIDGEMANAGER";
		if(!array_key_exists($key, $GLOBALS) || $GLOBALS[$key] == null)
		{
			 $GLOBALS[$key] = new SRBridgeManager(); 
		}
		$manager =& $GLOBALS[$key];	
		return $manager->_Bridge;
    }	
	
}


class SRBridgeUserBase
{
	var $_User = null;
	var $_Acl = null;
	var $Name = null;
	var $UserName = null;
	var $ID = null;
	var $IP = null;
	
	/**
	 * Creates a wrapped user object.
	 * @return
	 * @param object $userNameOrID[optional] Null for current users, else the username or user id.
	 */
	function SRBridgeUserBase($userNameOrID=null)
	{
	    if(strtolower(get_class($this))=='srbridgeuserbase' || !is_subclass_of ($this,'SRBridgeUserBase'))
		{
	        trigger_error('This class is abstract. It cannot be instantiated!',E_USER_ERROR);
	    }		
	}
	
	/**
	 * Checks if the user can view the admin screen.
	 * @return true is can view, else false.
	 */
	function CanViewAdmin(){}
	
	/**
	 * Checks if a user is currently logged in.
	 * @return 
	 */
	function IsLoggedIn()
	{
		if(intval($this->ID) > 0)
		{
			return true;  
		}
		else
		{
			return false;  
		}
	}	
}
?>