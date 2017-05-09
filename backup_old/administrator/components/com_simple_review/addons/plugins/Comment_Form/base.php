<?php
/**
 *  $Id: base.php 103 2009-06-14 07:04:19Z rowan $
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

class Comment_Form_Base extends Plugin_Base
{
 var $canComment = false;
 var $sr_user = null;
 var $commentModule = null;

 	function Comment_Form_Base(&$addonManager, &$pluginName, $initialise)
 	{
 	  	$this->hasCSS=true;
		$this->pluginType="Comment_Form";
		$this->dependsOnModules = array("Comment_Module");
		$this->canAttachToModules = array("Review_Module"); 	  				
 	  	parent::Plugin_Base($addonManager, $pluginName, $initialise);
		
		if(!$initialise)
		{
			return;
		}
				
		$this->LoadTemplate($pluginName);					
		$this->commentModule =&  $this->_AddonManager->GetModule("Comment_Module", true);						 	  			
 	  	        
     	$this->canComment = $this->commentModule->_AllowedToComment();
     	$this->sr_user = $this->_AddonManager->Bridge->CurrentUser;

     	$this->_LoadJavaScript();
	}
   

	function _DisplayHeader()
	{	  
	}
	
	function _DisplayFooter()
	{ 
	  
	}
	function _LoadAddonData()
	{
	}

	function _LoadJavaScript()
	{	
		global $option;	
		$jsPath = $this->_AddonManager->Bridge->SiteUrl."administrator/components/$option/addons/plugins/Comment_Form/$this->addonName";
		Simple_Review_Common::IncludeJavaScript("$jsPath/$this->addonName.js");
		$avatarURL = $this->_AddonManager->Bridge->SiteUrl."components/$option/images/avatars";
		$warningMandatoryName = $this->GetString($this->commentModule, 'WarningMandatoryName');
		$warningMandatoryComment = $this->GetString($this->commentModule, 'WarningMandatoryComment');
		$warningMandatoryImageCode = $this->GetString($this->commentModule, 'WarningMandatoryImageCode');
		$warningLength = $this->GetString($this->commentModule, 'WarningLength');
		$warningBounds = $this->GetString($this->commentModule, 'WarningBounds');
		?>
		<script type="text/javascript">
			var avatarURL = '<?php echo $avatarURL;?>'
			var nameNeeded = '<?php echo $warningMandatoryName;?>'
			var commentNeeded = '<?php echo $warningMandatoryComment;?>';
			var commentLength = '<?php echo $warningLength;?>';
			var ratingBounds = '<?php echo $warningBounds;?>';
			var maxrating = '<?php echo Review_Module_MAX_RATING;?>';
			var securityNeeded = '<?php echo $warningMandatoryImageCode;?>';
			var maxLength = '<?php echo Comment_Module_Max_Length;?>';
			var cfc = new CommentFormController(avatarURL, nameNeeded, commentNeeded, maxLength, commentLength, ratingBounds, maxrating, securityNeeded, false);	  
		</script>
	    <?php
	}
    
}

?>