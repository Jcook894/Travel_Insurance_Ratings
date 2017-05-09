<?php
/**
 *  $Id: Standard_Comment_Form.php 103 2009-06-14 07:04:19Z rowan $
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
class Standard_Comment_Form extends Comment_Form_Base
{
	function Standard_Comment_Form(&$addonManager, &$pluginName, $initialise)
	{		
	  	//$this->pluginName = $pluginName;
		$this->addonPath = dirname(__FILE__)."/$pluginName";
				
		parent::Comment_Form_Base($addonManager, $pluginName, $initialise);				
	}
	
	function _AvatarSelectList()
	{
	  	if(!$this->canComment)
	  	{
		    return false;
		}	
		$SR_Addon_Manager =& Addon_Manager::Get();
		$SR_Addon_Manager->Bridge->IncludeBridgeControls();
		  	
	    $path = $SR_Addon_Manager->Bridge->PathComponentFrontEnd.'/images/avatars';
		$filter = '\.png$|\.gif$|\.jpg$|\.bmp$|\.ico$';
		
		$allImages = array();

		$image = new stdClass();
		$image->text = '- '. $this->GetString($this->commentModule, 'NoAvatar') .' -';
		$image->value = '-1';
    	$allImages[] = $image;	
		
		$originalWD = getcwd();
		chdir($path);
		$d=opendir('.');
		while($f = readdir($d)) 
		{  	   	
			if(is_dir($f) || $f == "." || $f == ".." || $f == "noimage.png" || !preg_match( "/$filter/", $f )) continue;

			$friendlyName = explode(".", $f);
			$image = new stdClass();
			$image->text = ucwords($friendlyName[0]);
			$image->value = $f;
	    	$allImages[] = $image;		
		}
		chdir($originalWD);	
														
		return SRHtmlControls::SelectList( $allImages, 'avatar', 'class="inputbox" onchange="cfc.UpdateAvatar();"', 'value', 'text',  -1, 'avatarselect', false);
	}
	
	function _DisplayMain()
	{
	  	global $Itemid, $option;
		
		$reviewID = null;
		$SR_Addon_Manager =& Addon_Manager::Get();
	    $reviewModule  =& $SR_Addon_Manager->GetModule('Review_Module', false);		
 		$reviewID = $reviewModule->GetReviewIdFromRequest();	
			  	
	  	if(!$this->canComment)
	  	{
		    return false;
		}	
		$submitLink = $SR_Addon_Manager->Bridge->RewriteUrl("index.php?option=com_simple_review&amp;Itemid=$Itemid");

		$currentUser = "";
		$u =  $this->sr_user;
		if( $u->IsLoggedIn() )
		{
			$currentUser = "<tr><td colspan='2'><input type='hidden' name='createdByID' value='".$u->ID."'/></td></tr>\n";
		}
		else
	    {
	      $currentUser = "<tr><td>".$this->GetString($this->commentModule, 'FormCommentsName')."</td><td>"
	      				."<input type='text' size='25' maxsize='25' name='anonymousName'/>"
		  				."</td></tr>\n";
	    }			
	
		$hasPreviouslyCommented = $this->commentModule->_HasPreviouslyCommented($u->IP, $reviewID, $u->ID);
		$ratingInput ="";
		if(!$hasPreviouslyCommented)
		    {
		        $ratingInput = "<input type='text' size='7' maxsize='7' name='userRating' /> / <b>"
					 		 .Review_Module_MAX_RATING
		         			 ."</b>\n<input type='hidden' name='requireUserRating' value='1'>";
		    }
		else
			{
				$ratingInput =  $this->GetString($this->commentModule, 'AlreadyRated')
				  				.'<input type="hidden" name="userRating" value="-1">'
				  				.'<input type="hidden" name="requireUserRating" value="0">';
			}
		$body = "Standard_Comment_Form_Body";
		
		$template =& $this->templates[$this->addonName];
		$avatarURL = $SR_Addon_Manager->Bridge->SiteUrl."components/$option/images/avatars/noimage.png";
		$details = new stdClass();
		$details->userCommentIntro = $this->GetString($this->commentModule, 'FormIntro');
		$details->allowUserComments =  Comment_Module_Allow_Anonymous;
		$details->submitLink = $submitLink;
		$details->reviewID = $reviewID;
		$details->itemID = $Itemid;
		$details->comment = $this->GetString($this->commentModule, 'FormCommentsContent');
		$details->userCommentRating = $this->GetString($this->commentModule, 'FormCommentsRating');
		$details->userCommentAvatar = $this->GetString($this->commentModule, 'FormCommentsAvatar');
		$details->currentUser = $currentUser;
		$details->ratingInput = $ratingInput;
		$details->addComment = $this->GetString($this->commentModule, 'Add');
		$details->avatarSelectList = $this->_AvatarSelectList();
		$details->initialImage = $avatarURL;
		$details->securityImage = '';
	
		$template->DisplayForm($details);	
	}
	

}



       
