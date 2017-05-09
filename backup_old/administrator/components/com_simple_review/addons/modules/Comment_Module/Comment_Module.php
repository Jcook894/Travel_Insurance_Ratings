<?php
/**
 *  $Id: Comment_Module.php 103 2009-06-14 07:04:19Z rowan $
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
class Comment_Module extends Module_Base
{
	var $review = null;
	function Comment_Module(&$addonManager, &$moduleName, $initialise)
	{
	  	//$this->addonName = $moduleName;
		$this->friendlyName = "Comments";
		$this->addonPath = dirname(__FILE__);
		
	  	$this->hasCSS=false;
	  	$this->hasLanguage=true;
	  	$this->hasConfig=true;	
			    				
	  	parent::Module_Base($addonManager, $moduleName, $initialise);
		$this->Initialised = $initialise;	  	    	   		
	}
	
    function _LoadAddonData()
    {  
	}
	
	function _AllowedToComment()
	{
	   $reviewID = null;	
	   if(!Comment_Module_Allow)
	   {
	   		echo "<p><b><i>".$this->GetString($this, 'Disabled')."</i></b></p>\n";
            return false;
	   }
	   //TODO:cleanup
	    $bannedIPTable = '#__simplereview_banned_ips';
		$SR_Addon_Manager =& Addon_Manager::Get();
	    $reviewModule  =& $SR_Addon_Manager->GetModule('Review_Module', false);		
 		$reviewID = $reviewModule->GetReviewIdFromRequest();	   
	   
		$userIP = getenv('REMOTE_ADDR'); 
		$hasPreviouslyCommented = $this->_HasPreviouslyCommented($userIP, $reviewID, $this->_AddonManager->Bridge->CurrentUser->ID);
	   	   
		$query = "SELECT count(*)FROM $bannedIPTable WHERE bannedIP = '$userIP'";        
		$result = $result = SRBridgeDatabase::ScalarQuery($query);
		if($result> 0)
		{
		  echo "<div border='1'><h3>".$this->GetString($this, 'WarningBannedIP')."</h3></div>";
		  return false;
		}        
        
        
        if(Comment_Module_One_Per_IP && $hasPreviouslyCommented )
        {
			  echo "<div border='1'><h3>".$this->GetString($this, 'WarningAlreadyCommented')."</h3></div>";
			  return false;
		}        
        
		if(!$this->_AddonManager->Bridge->CurrentUser->IsLoggedIn() && !Comment_Module_Allow_Anonymous)
        {
            echo "<p><b><i>".$this->GetString($this, 'WarningRegisteredOnly')."</i></b></p>\n";
            return false;
        }
        
        return true;
	}

  function _HasPreviouslyCommented($userIP, $reviewID, $userID)
  {
		$commentTable = SRDB_Comment::TableName();
  		if($userID > 0)
  		{
  		  	$clause = "createdByID = $userID";
  		}
  		else
  		{
  			$clause = "userIP = '$userIP'";
  		}
 		$query = "SELECT COUNT(*) FROM $commentTable WHERE $clause and reviewID = $reviewID";      
		$result = SRBridgeDatabase::ScalarQuery($query);
		if($result> 0)
		{
		  return true;
		}  	
		return false;	  
  }
  	
  function AddComment()
  {	
  	global $Itemid;
	$SR_Addon_Manager =& $this->_AddonManager;
  	
	//pre-reqs
	if(!Comment_Module_Allow)
  	{
	    echo "<script> alert('".$this->GetString($this, 'Disabled')."'); window.history.go(-1); </script>";
	    SRError::Display($this->GetString($this, 'Disabled'), true);
	}
    
    if(!$this->_AddonManager->Bridge->CurrentUser->IsLoggedIn() && !Comment_Module_Allow_Anonymous)
    { 
	    echo "<script> alert('".$this->GetString($this, 'WarningRegisteredOnly')."'); window.history.go(-1); </script>";
	    SRError::Display($this->GetString($this, 'WarningRegisteredOnly'), true);	  
	}

  	$postcomobj = new SRDB_Comment($this->_AddonManager->Database);
  	 	
	if (!$postcomobj->bind( $_POST )) 
	{
	        SRError::Display($row->getError(), true);
	}
	
 	if (!$postcomobj->check()) 
	{
	        SRError::Display("Invalid comment.", true);
	} 
	
	$reviewID = $postcomobj->reviewID;			  	

	$userIP = getenv('REMOTE_ADDR'); 
	$postcomobj->userIP = $userIP;
	
	if(!$this->_AllowedToComment())
	{
	    echo "<script> alert('Not Allowed to comment.'); window.history.go(-1); </script>";
	    exit();	  	  
	}
	
    $reviewModule  =& $SR_Addon_Manager->GetModule('Review_Module', true);
 	if($reviewModule == null)
 	{
		SRError::Display("Unable to load review module.", true);
	}

	$postcomobj->userRating = $reviewModule->ValidRating($postcomobj->userRating);
	$hasPreviouslyCommented = $this->_HasPreviouslyCommented($userIP, $postcomobj->reviewID, $this->_AddonManager->Bridge->CurrentUser->ID); 		
	if($hasPreviouslyCommented)
	{
	  	$postcomobj->userRating = -1;
	}	
	elseif($postcomobj->userRating === null)
	{  
	  	$warning = "The rating $postcomobj->userRating must be between 0 and ".Review_Module_MAX_RATING;
	    echo "<script> alert('$warning'); window.history.go(-1); </script>";	  	
	  	SRError::Display($warning, true);
	}
	
	$path = $SR_Addon_Manager->Bridge->PathComponentFrontEnd.'/images/avatars/'.$img;
	$filter = '\.png$|\.gif$|\.jpg$|\.bmp$|\.ico$';
	$img = $postcomobj->avatar;
	if(!$img || !preg_match( "/$filter/", $img ) || !file_exists($path))
	{
		$postcomobj->avatar = 'noimage.png';
	}
	

	/*
    if ($sr_global['securityimage'])
    {
    	//security image by www.waltercedric.com
    	
    	$security_refid = mosGetParam( $_REQUEST ,'security_refid', 0 );
    	$security_try = mosGetParam( $_REQUEST ,'security_try', -1 );

    	if (file_exists("$mosConfig_absolute_path/administrator/components/com_securityimages/server.php")) 
		{
			include ("$mosConfig_absolute_path/administrator/components/com_securityimages/server.php");
		}
		else
		{
		  	echo "<h1>Please install security images</h1>";
		  	exit();
		}
		$checkSecurity = checkSecurityImage($security_refid, $security_try);
    	
    	if(!$checkSecurity)
    	{
               echo "<script> alert('Verification Code Does Not Match.'); window.history.go(-1); </script>n";
               exit();
        }
        
    	//end security image by www.waltercedric.com
    }
    */
    
    $postcomobj->plainComment = null;//depricated
	if (!$postcomobj->store()) {
    	echo "<script> alert('".$postcomobj->getError()."'); window.history.go(-1); </script>n";
    	exit();
  	}
  	
  	$row = new stdClass();
  	$currentReview =& $reviewModule->Prop_Review();

  	$row->reviewID = $currentReview->reviewID;
  	$row->title1 = $currentReview->reviewTitles[0]->title;
  	$titleCount = count($currentReview->reviewTitles);
  	$row->title2 = $titleCount >= 2 ? $currentReview->reviewTitles[1]->title : "";
  	$row->title3 = $titleCount >= 3 ? $currentReview->reviewTitles[2]->title : "";

  	$link = $reviewModule->GetURL($row, "", $Itemid);
	
    if(Comment_Module_Email !='')
    {          	
        $to = Comment_Module_Email;
        $subject = $this->GetString($this, 'EmailSubject');
        $body = "$postcomobj->comment\n<br/>";
        $body.= "<a href='$link'>View comment</a>\n";
	
		if(Simple_Review_Common::SendEmail($to, $subject, $body))
		{
		 	$SR_Addon_Manager->Bridge->Redirect($link, $this->GetString($this, 'Added'));
		}
        else
        {
           echo "<p>".$this->GetString($this, 'EmailUnsent')."</p>";
           return;
        }
    }
	
    $SR_Addon_Manager->Bridge->Redirect($link, $this->GetString($this, 'Added'));       
 } 

}


?>


       
