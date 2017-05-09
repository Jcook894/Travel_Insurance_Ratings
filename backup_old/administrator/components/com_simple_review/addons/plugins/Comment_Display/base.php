<?php
/**
 *  $Id: base.php 105 2009-06-14 07:18:36Z rowan $
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

class Comment_Display_Base extends Plugin_Base
{
 var $sr_user = null;
 var $comments = null;
 var $canDelete = false;
 var $tmpl_body = "Comment_Display_Body";
//  var $tmpl = null;
	var $_CommentModule;
 	function Comment_Display_Base(&$addonManager, &$pluginName, $initialise)
 	{
		$this->hasCSS=true;
		$this->pluginType="Comment_Display";
		$this->dependsOnModules = array("Comment_Module");		
		$this->canAttachToModules = array("Review_Module");
		
		parent::Plugin_Base($addonManager, $pluginName, $initialise);
		
		if(!$initialise)
		{
			return false;
		}
		
		$this->LoadTemplate($pluginName);
		
  	
     	$this->sr_user = $this->_AddonManager->Bridge->CurrentUser;
		//TODO: this is temp
        $this->canDelete = $this->sr_user->CanViewAdmin(); 	
		
		$this->_CommentModule  =& $this->_AddonManager->GetModule('Comment_Module', false);	
	}
   
	
	function _DisplayHeader()
	{	  
	}

	function _DisplayMain()
	{
		global $Itemid;
        if( count($this->comments ) == 0)
        {
            echo '<p>'.$this->GetString($this->_CommentModule, 'NoComments').'</p>';
            return;
        }
		  foreach($this->comments as $comment	)
		  {
		    
			      if($this->canDelete)
			      {			             	
		               	 $link = "index.php?option=com_simple_review";
		                 $admin = "<a href='$link&amp;commentID=$comment->commentID&amp;reviewID=$comment->reviewID&amp;task=deleteComment&amp;Itemid=$Itemid'>Delete?</a>";
						 			
			             if($comment->userIP)
			             {
			                   $banIPLink = "$link&amp;task=banip&amp;ip=$comment->userIP&amp;reviewID=$comment->reviewID&amp;Itemid=$Itemid";
							   $banIPLink = $this->_AddonManager->Bridge->RewriteUrl($banIPLink);
							   $admin .= " User IP:$comment->userIP <a href='$banIPLink'>Ban IP?</a>";
						  }
							 				 
			        } 
					else
					{
					  $admin = "";
					}
					
					if($comment->userRating>=0)
					{
						$comment->userRating = "$comment->userRating / " .Review_Module_MAX_RATING;
					}
					else
					{
					  $comment->userRating = "";
					}
					
						 	    
	    	     if($comment->createdByID !=-1)
	             {	                
	                if(_SR_GLOBAL_USE_REAL_NAME && $comment->createdByID != -1 )
	                {                  
	    	            $createdBy = Simple_Review_Users::User_NameFromID($comment->createdByID);				  		  
					} 
					else
					{
						$createdBy = Simple_Review_Users::User_FromID($comment->createdByID);						
					}
					$comment->commenter = $createdBy;
					
	             }
	             else
	             {
	                 $comment->commenter =  $comment->anonymousName. ' ('.$this->GetString($this->_CommentModule, 'Guest').')';
					 $comment->commenter  = Simple_Review_Common::RemoveSlashes($comment->commenter  );
	             }
				 $comment->admin = $admin;
				 $comment->comment = Simple_Review_Common::RemoveSlashes($comment->comment  );				 
				 $this->templates[$this->addonName]->AddComment($comment); 	    
		  }     
	}
	
	function _DisplayFooter()
	{ 
	}
	
	function Display()
	{
	 	$this->_DisplayHeader(); 
		$this->_DisplayMain(); 
		$this->_DisplayHeader(); 		 	  
	}
	
	function _LoadAddonData()
	{
		$SR_Addon_Manager =& Addon_Manager::Get();
	    $reviewModule  =& $this->_AddonManager->GetModule('Review_Module', false);		
 		$reviewID = $reviewModule->GetReviewIdFromRequest();
		
        $query = "Select anonymousName,  createdBy,  createdByID,userRating, comment,commentID, reviewID, avatar,plainComment,".
                 "DATE_FORMAT(createdDate, '"._SR_GLOBAL_DATE_FORMAT."') as createdDate, userIP ".
                 "from #__simplereview_comments where reviewID=$reviewID order by #__simplereview_comments.createdDate desc";
        
        $this->comments = SRBridgeDatabase::Query($query);	  
	}
		    
}

?>