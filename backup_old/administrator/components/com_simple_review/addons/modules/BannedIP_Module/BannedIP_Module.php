<?php
/**
 *  $Id: BannedIP_Module.php 103 2009-06-14 07:04:19Z rowan $
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
require_once(dirname(__FILE__ )."/db.php");
class BannedIP_Module extends Module_Base
{
	function BannedIP_Module(&$addonManager, &$moduleName, $initialise)
	{
		$this->addonPath = dirname(__FILE__);
		
	  	$this->hasCSS=false;
	  	$this->hasLanguage=true;
	  	$this->hasConfig=false;	
			    				
	  	parent::Module_Base($addonManager, $moduleName, $initialise);
		$this->Initialised = $initialise;	  
		
		$this->friendlyName = $this->GetString($this, 'BannedIPList');			    	   		
	}
	
    function _LoadAddonData()
    {	 			        
    }

	function _DisplayHeader()
	{

	}

	function _DisplayMain()
	{	  
	}

	function _DisplayFooter()
	{
	}	
	
	function Display()
	{
	}	
	
	function BanIP($IP, &$error)
	{
	  	if($IP ==-1)
	  	{
		    $error = "An IP needs to be specified.";
		    return false;
		}
		
		if(!preg_match("/\b(?:(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.){3}(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\b/", $IP))
		{
		    $error = "The IP $IP is not valid.";
		    return false;			
		}
		
	  	$table = SRDB_Banned_IP::TableName();
	  	$user = $this->_AddonManager->Bridge->CurrentUser;
	  	if($user->CanViewAdmin())
	  	{
			$query = "SELECT count(*)FROM $table WHERE bannedIP = '$IP'";
			$result = SRBridgeDatabase::ScalarQuery($query);
			if($result > 0)
			{
				$error = "The IP:$IP has already been banned!";
				return false;		  
			}			
			$query = "INSERT INTO $table (bannedIP) VALUES ('$IP')";						 
			if(!SRBridgeDatabase::NonResultQuery($query))
			{			 
				$error = "Unable to save $IP into database.";
				return false; 
			}				 
		}
	  	else
	  	{
		    $error = "No permission.";
		    return false;
		}
		return true;
	}
	
	function BanIPFromFrontEnd()
	{
		global $Itemid;
		$reviewModule =& $this->_AddonManager->GetModule('Review_Module', true);
						
		$IP = $this->_AddonManager->Bridge->GetParameter( $_REQUEST ,'ip', -1);		
		$review =& $reviewModule->Prop_Review();
		
		$error = '';
		$url = $reviewModule->GetURL($review, '', $Itemid, _SR_GLOBAL_SEO);
		if(!$this->BanIP($IP, $error))
		{
			$this->_AddonManager->Bridge->Redirect($url, "An error has occured: $error");	
		}
		else
		{
	    	$this->_AddonManager->Bridge->Redirect($url, "The IP:$IP has been banned!");
		}		
	}			
}

?>