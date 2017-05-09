<?php
/**
 *  $Id: SRBridgeJoomla15.php 120 2009-09-13 05:37:35Z rowan $
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
require_once("SRBridgeDatabaseJoomla15.php");
class SRBridgeJoomla15 extends SRBridgeCMS
{

   	function SRBridgeJoomla15()
   	{
   		$this->SystemToBridge = 'Joomla15';
		
		$this->PathJoomla = JPATH_SITE;
		$this->PathComponentFrontEnd = JPATH_COMPONENT_SITE;
		$this->PathComponentAdministrator = JPATH_COMPONENT_ADMINISTRATOR;
		$this->SiteUrl = JURI::root(false);
		
		$config = new JConfig();

		$this->InDebugMode = $config->debug == '1';
		$this->Database =& JFactory::getDBO();	
		
		$this->CurrentUser = new SRBridgeUser();
    }
	
	function GetParameter( &$array, $key, $default = null, $type = '')
	{
		return JArrayHelper::getValue( $array, $key, $default, $type);
	}
	
	function Redirect($url, $text)
	{
		global $mainframe;
		$url = str_replace('&amp;', '&', $url);
		$mainframe->redirect( $url, JText::_($text) );
	}	
	
	function RewriteUrl($url)
	{
		return JRoute::_($url);
	}	
	
	function SetPageTitle($title)
	{
		$document=& JFactory::getDocument();
		$document->setTitle($title);
	}	
	
	function IncludeJQuery()
	{
		if(_SR_JQuery != '1')
		{
			?>
			<script type="text/javascript">
				if(!jQuery)
				{
					alert('You have chosen not to include the required jQuery library and it is not currently loaded by another component.');
				}
			</script>
			<?php
			return;
		}
		$document = &JFactory::getDocument();
		
		//$document->addScript( 'http://ajax.googleapis.com/ajax/libs/jquery/1.3.2/jquery.min.js');		
		$document->addCustomTag( "<script type='text/javascript' src='http://ajax.googleapis.com/ajax/libs/jquery/1.3.2/jquery.min.js'></script>" );
		$document->addCustomTag( "<script type='text/javascript'>jQuery.noConflict();</script>" );
	}	
}

class SRBridgeUser extends SRBridgeUserBase
{
	function SRBridgeUser($userNameOrID=null)
	{
		$this->Acl =& JFactory::getACL();

		//see joomla1.5\libraries\joomla\user\authorization.php
		$this->Acl->addACL( 'com_simple_review', 'manage', 'users', 'super administrator' );
		$this->Acl->addACL( 'com_simple_review', 'manage', 'users', 'administrator' );

		if($userNameOrID == null)
		{
			$this->_User  =& JFactory::getUser(); 
		}	
		else
		{
			$this->_User =& JFactory::getUser($userNameOrID);						
		}

			
		if($this->_User != null)
		{
			$this->Name = $this->_User->name;
			$this->UserName = $this->_User->username;
			$this->ID = $this->_User->id;
			$this->IP = getenv('REMOTE_ADDR');
		}		
	}
	
	function CanViewAdmin()
	{
		return $this->_User->authorize( 'com_simple_review', 'manage' );		
	}
}
?>