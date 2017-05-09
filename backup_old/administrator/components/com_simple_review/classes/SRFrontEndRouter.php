<?php
/**
 *  $Id: SRFrontEndRouter.php 103 2009-06-14 07:04:19Z rowan $
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
class SRFrontEndRouter
{
	var $_AddonManager = null;
	function SRFrontEndRouter()
	{
		$this->_AddonManager =& Addon_Manager::Get();
	}
	function Route()
	{
		global $Itemid;
		
		$Itemid = $this->_AddonManager->Bridge->GetParameter($_REQUEST ,'Itemid', 0, 'int' );
		$task = $this->_AddonManager->Bridge->GetParameter($_REQUEST ,'task', '');		
				
		if($task == '')
		{
			$task = $this->GetMissingTask();
		}		
		
		switch($task)
		{
		    case 'addComment':
				$commentModule  =& $this->_AddonManager->GetModule('Comment_Module');
				$commentModule->AddComment(); 
			    break;
				
			case 'banip':
				$bannedIPModule   =& $this->_AddonManager->GetModule('BannedIP_Module');		
				$bannedIPModule->BanIPFromFrontEnd();
				break;	
			
		    case 'displayCategories':
		      	$categoryModule  =& $this->_AddonManager->GetModule("Category_Module", true);
		      	$categoryModule->Display();  
		        break;	
			
		    case 'displayReview':
		      	$reviewModule  =& $this->_AddonManager->GetModule('Review_Module', true);
		      	$reviewModule->Display(); 
		        break;	
				
		    default:
			    if ($task != '')
			    {
			     	$customModule  =& $this->_AddonManager->GetModule($task, true);
			     	if($customModule != null)
			     	{
			      		$customModule->Display(); 
			      		break;
					}  
			    }     
		     	$categoryModule  =& $this->_AddonManager->GetModule('Category_Module', true);
		    	$categoryModule->Display();  
		    break;				
		}								
	}
	
	function GetMissingTask()
	{
		$reviewParam = $this->_AddonManager->Bridge->GetParameter( $_REQUEST ,'review', -1 );
		if($reviewParam != -1)
		{
		  return 'displayReview';
		}
			
		$categoryParam = $this->_AddonManager->Bridge->GetParameter( $_REQUEST ,'category', -1 );
		if($categoryParam != -1)
		{
		  return 'displayCategories';
		}	
	
		return 'displayCategories';		
	}	
}

?>