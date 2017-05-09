<?php
/**
 *  $Id: SRBridgeJoomla10.php 81 2009-05-17 12:34:52Z rowan $
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
require_once("SRBridgeDatabaseJoomla10.php");
class SRBridgeJoomla10 extends SRBridgeCMS
{

   	function SRBridgeJoomla10()
   	{
   		global $mosConfig_absolute_path, $mosConfig_live_site, $mosConfig_debug, $database;
		
		$this->SystemToBridge = 'Joomla10';
		
		$this->PathJoomla = $mosConfig_absolute_path;
		$this->PathComponentFrontEnd = "$mosConfig_absolute_path/components/com_simple_review";
		$this->PathComponentAdministrator = "$mosConfig_absolute_path/administrator/components/com_simple_review";
		$this->SiteUrl = $mosConfig_live_site.'/';
		$this->InDebugMode = $mosConfig_debug == '1';
		$this->Database = $database;
		$this->CurrentUser = new SRBridgeUser();
    }
	
	function GetParameter( &$array, $key, $default = null, $type = '')
	{
		if($type == 'int')
		{
			return intval( mosGetParam( $array ,$key, $default ) );			
		}
		
		 return mosGetParam( $array ,$key, $default );
	}
	function Redirect($url, $text)
	{
		$url = str_replace('&amp;', '&', $url);
		mosRedirect($url, $text); 
	}
	
	function RewriteUrl($url)
	{
		return sefRelToAbs($url);
	}
	
	function SetPageTitle($title)
	{
		global $mainframe;
		$mainframe->setPageTitle($title);
	}	
}

class SRBridgeUser extends SRBridgeUserBase
{
	function SRBridgeUser($userNameOrID=null)
	{
		global $acl, $my;
		$this->_Acl =& $acl;
		
		if($userNameOrID == null)
		{
			$this->_User =& $my;			
		}	
		else
		{
			$query = '';
			if(intval($userNameOrID) > 0)
			{
				$query = "SELECT id, name, username, email, usertype  FROM #__users WHERE id = $userNameOrID LIMIT 1;";
			}
			else
			{
				$query = "SELECT id, name, username, email, usertype  FROM #__users WHERE username = $userNameOrID LIMIT 1;";
			}
        	$users = SRBridgeDatabase::Query($query);			
			$this->_User = count($users) == 1 ? $users[0] : null;
			
			if($this->_User != null)
			{
				$this->Name = $this->_User->name;
				$this->UserName = $this->_User->username;
				$this->ID = $this->_User->id;
				$this->IP = getenv('REMOTE_ADDR');
			}
		}
	}
	
	function CanViewAdmin()
	{
						
		return 	$this->_Acl->acl_check('administration', 'edit', 'users', $this->_User->usertype, 'components', 'all') ||
				$this->_Acl->acl_check('administration', 'edit', 'users', $this->_User->usertype, 'components', 'com_simple_review');
		
	}	
	
}
?>