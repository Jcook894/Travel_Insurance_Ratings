<?php
/**
 *  $Id: Comment_Module_Admin.php 90 2009-06-13 06:13:29Z rowan $
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
class Comment_Module_Admin extends Module_Base_Admin
{   
  	var $_CommentModule; 
	function Comment_Module_Admin(&$addonManager, &$adminModuleName, &$moduleName)
	{
		$this->addonPath = dirname(__FILE__);
	  	$this->hasCSS=false;
	  	$this->hasLanguage=true;
		$this->hasConfig=true;
		$this->showOnConfigScreen=true;  		
						
	  	parent::Module_Base_Admin($addonManager, $adminModuleName, $moduleName);
		$this->_CommentModule =& $this->_AddonManager->GetModule("Comment_Module", false);		 	
		$this->friendlyName = $this->GetString($this->_CommentModule, 'Comments');
		$this->defaultTaskName = $this->GetString($this->_CommentModule, 'AdminDescription');	
				
	} 
	

	function DisplayConfiguration(&$srConfig, &$allPlugins)
	{
	  	$control_name="params";

	    echo "<table>"; 
				
		//allow comments
		$settingName = $this->addonName."_Allow";	
		$control = SRHtmlControls::YesNoRadio("params[$settingName]",'class="inputbox"',constant($settingName) );
		$text = $this->GetString($this->_CommentModule, 'ConfigAllow');
		$tip  = $this->GetString($this->_CommentModule, 'ConfigAllowTip');
		$srConfig->DisplaySingleTabParam($text, $tip, $control);		

		//allow anon comments
		$settingName = $this->addonName."_Allow_Anonymous";	
		$control = SRHtmlControls::YesNoRadio("params[$settingName]",'class="inputbox"',constant($settingName) );
		$text = $this->GetString($this->_CommentModule, 'ConfigAllowAnon');
		$tip  = $this->GetString($this->_CommentModule, 'ConfigAllowAnonTip');	
		$srConfig->DisplaySingleTabParam($text, $tip, $control);			

		//one comment per ip
		$settingName = $this->addonName."_One_Per_IP";	
		$control = SRHtmlControls::YesNoRadio("params[$settingName]",'class="inputbox"',constant($settingName) );
		$text = $this->GetString($this->_CommentModule, 'ConfigOnePerIP');
		$tip  = $this->GetString($this->_CommentModule, 'ConfigOnePerIPTip');	
		$srConfig->DisplaySingleTabParam($text, $tip, $control);	
		
		//CAPTCH
		$settingName = $this->addonName."_Use_CAPTCHA";
		$control = SRHtmlControls::YesNoRadio("params[$settingName]",'class="inputbox"',constant($settingName) );
		$text = $this->GetString($this->_CommentModule, 'ConfigCAPTCHA');
		$tip  = $this->GetString($this->_CommentModule, 'ConfigCAPTCHATip');		
		$srConfig->DisplaySingleTabParam($text, $tip, $control);					
		
		//comment email
		$settingName = $this->addonName."_Email";
	    $control = SRHtmlControls::Text($settingName, constant($settingName), $control_name ,40);
	    $text = $this->GetString($this->_CommentModule, 'ConfigEmailNotification');
	    $tip  = $this->GetString($this->_CommentModule, 'ConfigEmailNotificationTip');
		$srConfig->DisplaySingleTabParam($text,$tip, $control);						
		
		//comment max length
		$settingName = $this->addonName."_Max_Length";
	    $control = SRHtmlControls::Text($settingName, constant($settingName), $control_name ,4);
	    $text = $this->GetString($this->_CommentModule, 'ConfigMaxLength');
	    $tip  = $this->GetString($this->_CommentModule, 'ConfigMaxLengthTip');
		$srConfig->DisplaySingleTabParam($text,$tip, $control);								
		
		echo "</table>";
	}
	
	function Display($task)
	{
	  	if(!$task)
	  	{
	  	 	$task="list";
	  	}
		switch ($task)
		{		  
			case "new":
			$this->_New();
			break;
		
			case "apply":
			$this->_Save(true);
			break;
						
			case "save":
			$this->_Save();
			break;
			
			case "delete":
			$ids = mosGetParam( $_REQUEST, 'commentID', array(0) );
			$this->_Delete( $ids );
			break;
					  
			case "edit":
			$id = mosGetParam( $_REQUEST, 'commentID', array(0) );
			if(is_array($id))
			{
			 	$id = $id[0]; 
			}
			$this->_Edit($id);			
			break;
			
			case "list":
			default:
			$this->_List();
			break;  					
		} 	
	}
	
	function _New() 
	{
	  	global $option;
		echo "<h3>".$this->GetString($this->_CommentModule, 'CannotAddFromAdmin')."</h3>";
	    echo "<form action='index2.php' method='post' name='adminForm' id='adminForm' class='adminForm'>";
	    echo "<input type='hidden' name='option' value='$option' />";
	    echo "<input type='hidden' name='task' value='' />";
	    echo "<input type='hidden' name='module' value='$this->addonName' />";
	    echo "</form>";		
    }
	
	function _Save($apply=false)
	{
	  $row = new SRDB_Comment( $this->_AddonManager->Database );
	
	  if (!$row->bind( $_POST )) {
	    echo "<script> alert('".$row->getError()."'); window.history.go(-1); </script>";
	    exit('Error binding from post.');
	  }
	
		$originalcomment = new SRDB_Comment( $this->_AddonManager->Database  ); 
		$originalcomment->load( $row->commentID );		
	
		$row->comment= Simple_Review_Common::HTML2Text($row->comment);

	  //some mysql setups have current time stamp as default on dates, need to do this not to update the ts	
	  $row->createdDate = $originalcomment->createdDate;
	  //wont update nulls		
	  $row->reviewID  = null;
	  $row->anonymousName = null;
	  $row->createdBy = null;//depricated
	  
	  $row->userRating = null;
	  $row->published = null;
	  $row->avatar = null;
	  $row->createdByID = null;
	  $row->userIP = null;
	  
	  $row->plainComment = null; //depricated
	
	
	  if (!$row->store(false)) {
	    echo "<script> alert('".$row->getError()."'); window.history.go(-1); </script>";
	    exit('Error storing.');
	  }
	  if($apply)
	  {
	     $this->_AddonManager->Bridge->Redirect($this->GetURL("edit", "commentID=$row->commentID"), 'Saved');
	  }
	  else
	  {	  
	  	$this->_AddonManager->Bridge->Redirect($this->GetURL("list"), 'Saved');  	  
	  }
	}

	function _Edit($id)
	{
		global $option;
		$row = new SRDB_Comment( $this->_AddonManager->Database ); 
		$row->load( $id );
		$commentToEdit;
		if($row->plainComment)
		{
			$commentToEdit = $row->plainComment;
		}
		else
		{
		 	$commentToEdit = $row->comment;
		}
		
	    $values['comment'] = $commentToEdit;
		$values['reviewID'] = $row->reviewID;
	
	    echo "<form action='index2.php' method='post' name='adminForm' id='adminForm' class='adminForm'>";
	    $this->_PrintFormTable($values);
	    echo "<input type='hidden' name='commentID' value='$row->commentID' />";
	    echo "<input type='hidden' name='option' value='$option' />";
	    echo "<input type='hidden' name='task' value='' />";
	    echo "<input type='hidden' name='module' value='$this->addonName' />";
	    echo "</form>";

	}

	function _Delete(&$ids)
	{

		  if (!is_array( $ids ) || count( $ids ) < 1) {
		    echo "<script> alert('Select an item to delete'); window.history.go(-1);</script>";
		    exit;
		  }
		  if (count( $ids )) {
		    $commentDB = new SRDB_Comment($this->_AddonManager->Database); 
		   	foreach($ids as $id)
		   	{
		   		$commentDB->SR_Delete($id);
		   	}	
		  }
		  $this->_AddonManager->Bridge->Redirect( $this->GetURL("list"), 'Deleted'); 
	}
			
	function _List()
	{
		global $mosConfig_list_limit, $option;
		$commentTable = SRDB_Comment::TableName();
		
		$query = "SELECT COUNT(*)"
		. "\n FROM $commentTable";
		$total = SRBridgeDatabase::ScalarQuery($query);
		
		$pageNav = new SRPager($total);
		
		# Do the main database query
		//change this to match db.php
		$query = 	 "SELECT commentID, comment, userRating"
					."\nFROM $commentTable "
					."\nORDER BY commentID LIMIT $pageNav->limitStart, $pageNav->limit";
		$rows = $rows = SRBridgeDatabase::Query($query);
		if ($rows == false)
		{
			echo "<h3>".$this->GetString($this->_CommentModule, 'NoComments')."</h3>";
		}
		
	  	$formName						= "adminForm";	
	  	$formAction						= "index2.php";
			
		$columnNames					= Array();
		
		//change this to match db.php
		$columnNames[]					= "Comment ID";
		$columnNames[]					= "Rating";
		$columnNames[]					= "Comment";
		
		$propertyNames					= Array();
		
		$propertyNames[]				= "commentID";
		$propertyNames[]				= "userRating";
		$propertyNames[]				= Array('comment');
		
		SRControls::ItemListing($formName, $formAction, $columnNames, $propertyNames, $rows, 'list', $this, $pageNav);		             	  
	}  		
			
 	function _PrintFormTable($values=NULL)
     {
		global $option;
		/*
		$am =& Addon_Manager::Get();
		$reviewModule = $am->GetModule('Review_Module', false);	
		$review = $reviewModule->SR_GetReview($values['reviewID']);
		$reviewUrl = $reviewModule->GetURL($review, '', null, false);*/
		
		//change this to match db.php
		$tip						= $this->GetString($this->_CommentModule, 'FormCommentsContentTip');
		$commentTip			= Simple_Review_Common::CreateToolTip($this->GetString($this->_CommentModule, 'FormCommentsContent'), $tip);
		
		
		$jsPath = $this->_AddonManager->Bridge->SiteUrl."administrator/components/$option/addons/modules/$this->addonName";
		Simple_Review_Common::IncludeJavaScript("$jsPath/$this->addonName.Admin.js");  	       
    ?>

	<script type="text/javascript">
	function submitbutton(pressbutton)
	{
	  	<?php getEditorContents( 'editorcomment', 'plainComment' ) ; ?>
	    validateSRForm(pressbutton);
	}			
	</script>

	<fieldset>
		<legend><?php echo $this->GetString($this->_CommentModule, 'Comment');?></legend>
		
        <!--comment-->
        <label for="plainComment"><?php echo $commentTip;?></label>
	    <?php			 
	          // parameters : areaname, content, hidden field, width, height, rows, cols
	          editorArea( 'editorcomment',  $values['comment'] , 'comment', '80%;', '350', '75', '20' ) ;
	    ?>        
    
	</fieldset>    

    <?php

      }

	function SaveConfiguration($params)
	{
	  $configfile = "$this->addonPath/config.php";
	  @chmod ($configfile, 0766);
	
	  $permission = is_writable($configfile);
	
	  if (!$permission) 
	  {	
	    $mosmsg = "Module Config file not writeable!<br>$configfile";	
	    $this->_AddonManager->Bridge->Redirect("index2.php?option=$option&act=configuration",$mosmsg);
	    return false;
	  }
	  
	  $config  = "<?php\n";
	
	  $config .= "defined('_VALID_MOS')||defined( '_JEXEC' ) or die( 'Direct Access to this location is not allowed.' );\n";
	
	  $config .= "/*\nThe contents of this file are subject to the Mozilla Public License\n"
	            ."Version 1.1 (the \"License\"); you may not use this file except in\n"
	            ."compliance with the License. You may obtain a copy of the License at\n"
	            ."http://www.mozilla.org/MPL/\n\n"
	            ."Software distributed under the License is distributed on an \"AS IS\"\n"
	            ."basis, WITHOUT WARRANTY OF ANY KIND, either express or implied. See the"
	            ."License for the specific language governing rights and limitations\n"
	            ."under the License.\n\n"
	            ."The Original Code is Simple Review.\n\n"
	            ."The Initial Developer of the Original Code is Rowan J Youngson.\n"
	            ."Portions created by Rowan J Youngson are Copyright (C) December 17 2005.\n"
	            ."All Rights Reserved.\n\n"
	            ."Contributor(s): Rowan J Youngson.\n*/\n";

	  $settingName = $this->addonName."_Allow";
	  $config .="define('$settingName', '".$params[$settingName]."');\n";  
	         	
	  $settingName = $this->addonName."_Allow_Anonymous";
	  $config .="define('$settingName', '".$params[$settingName]."');\n"; 
	  
	  $settingName = $this->addonName."_One_Per_IP";
	  $config .="define('$settingName', '".$params[$settingName]."');\n"; 	
	  
	  $settingName = $this->addonName."_Use_CAPTCHA";
	  $config .="define('$settingName', '".$params[$settingName]."');\n"; 	  	  
	  
	  $settingName = $this->addonName."_Email";
	  $config .="define('$settingName', '".$params[$settingName]."');\n"; 	
	  
	  $settingName = $this->addonName."_Max_Length";
	  $maxLength = intval($params[$settingName], 10);
	  if($maxLength <=0)
	  {
	  	$maxLength = 500;
	  }
	  $config .="define('$settingName', '$maxLength');\n"; 	    

	  $config .= "?>";	
	  if ($fp = fopen($configfile, "w")) 
	  {	
	    fputs($fp, $config, strlen($config));	
	    fclose ($fp);	
	  }
	  return true;
	} 	
	
}
?>	