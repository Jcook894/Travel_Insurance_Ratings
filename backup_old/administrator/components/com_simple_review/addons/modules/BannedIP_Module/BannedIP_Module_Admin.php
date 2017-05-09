<?php
/**
 *  $Id: BannedIP_Module_Admin.php 103 2009-06-14 07:04:19Z rowan $
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
class BannedIP_Module_Admin extends Module_Base_Admin//name this
{   
  	var $_BannedIPModule;
	function BannedIP_Module_Admin(&$addonManager, &$adminModuleName, &$moduleName)
	{
		$this->addonPath = dirname(__FILE__);
	  	$this->hasCSS=false;
	  	$this->hasLanguage=true;
		$this->hasConfig=false;
		$this->showOnConfigScreen=false;
						
	  	parent::Module_Base_Admin($addonManager, $adminModuleName, $moduleName);
		
		$this->_BannedIPModule =& $this->_AddonManager->GetModule("BannedIP_Module", false);
		$this->friendlyName = $this->GetString($this->_BannedIPModule, 'BannedIPs');
		$this->defaultTaskName = $this->GetString($this->_BannedIPModule, 'AdminDescription');
				
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
			$ids = $this->_AddonManager->Bridge->GetParameter($_REQUEST ,'bannedIP',array(0));
			$this->_Delete( $ids );
			break;
					  
			case "edit":
			$id = $this->_AddonManager->Bridge->GetParameter($_REQUEST ,'bannedIP',array(0));
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
		parent::_New();
			
		$row = new SRDB_Banned_IP( $this->_AddonManager->Database );
		$row->load( null );

        $values['bannedIP'] = null;

        echo "<form action='index2.php' method='post' name='adminForm' id='adminForm' class='adminForm'>";
        $this->_PrintFormTable($values);
        echo "<input type='hidden' name='option' value='$option' />";
        echo "<input type='hidden' name='task' value='' />";
        echo "<input type='hidden' name='module' value='$this->addonName' />";
		echo "<input type='hidden' name='oldBannedIP' value='-1' />";
        echo "</form>";
    }
	
	function _Save($apply=false)
	{
   	    $bannedIPTable = SRDB_Banned_IP::TableName();
		$ip = $this->_AddonManager->Bridge->GetParameter($_REQUEST ,'bannedIP', -1); 
		$oldIP = $this->_AddonManager->Bridge->GetParameter($_POST ,'oldBannedIP', -1); 
	    if($ip == -1)
	    {
			$this->_AddonManager->Bridge->Redirect( $this->GetURL("list"), "you need to speficy an IP to ban.");
			return;		      
	    }
	    
		$query = "SELECT count(*)FROM $bannedIPTable WHERE bannedIP = '$ip'";
		$result = SRBridgeDatabase::ScalarQuery($query);
		if($result> 0)
		{
			$this->_AddonManager->Bridge->Redirect($this->_GetURL("list"), "bannedIP=$ip");
			return;		  
		}
		
		if($oldIP != -1 && $ip != $oldIP)
		{
			$query = "UPDATE $bannedIPTable SET bannedIP = '$ip' WHERE bannedIP = '$oldIP'";
		}
		else
		{
			$query = "INSERT INTO $bannedIPTable (bannedIP) VALUES ('$ip')";
		}
		SRBridgeDatabase::ScalarQuery($query);	  
	  
		if($apply)
		{
			$this->_AddonManager->Bridge->Redirect( $this->GetURL("edit", "bannedIP=$ip"), 'Saved');
		}
		else
		{	  
			$this->_AddonManager->Bridge->Redirect( $this->GetURL("list"), 'Saved');  	  
		}
	}

	function _Edit($id)
	{
		global $option;
 		$bannedIPTable = SRDB_Banned_IP::TableName();
 		$query = "SELECT bannedIP"
		. "\n FROM $bannedIPTable";

	    $values['bannedIP'] = SRBridgeDatabase::ScalarQuery($query);
	
	    echo "<form action='index2.php' method='post' name='adminForm' id='adminForm' class='adminForm'>";
	    $this->_PrintFormTable($values);
	    echo "<input type='hidden' name='option' value='$option' />";
	    echo "<input type='hidden' name='task' value='' />";
	    echo "<input type='hidden' name='module' value='$this->addonName' />";
		echo "<input type='hidden' name='oldBannedIP' value='".$values['bannedIP']."' />";
	    echo "</form>";

	}

	function _Delete(&$ids)
	{
		  if (!is_array( $ids ) || count( $ids ) < 1) {
		    echo "<script> alert('Select an item to delete'); window.history.go(-1);</script>";
		    exit;
		  }
		  if (count( $ids )) {
			$ipDB = new SRDB_Banned_IP( $this->_AddonManager->Database ); 
		   	foreach($ids as $ip)
		   	{
		   		$ipDB->SR_Delete($ip);
	   		}
		  }
		  $this->_AddonManager->Bridge->Redirect( $this->GetURL("list"), 'Deleted'); 
	}
			
	function _List()
	{
		$bannedIPTable = SRDB_Banned_IP::TableName();
		
		$query = "SELECT COUNT(*)"
		. "\n FROM $bannedIPTable";
		$total = SRBridgeDatabase::ScalarQuery($query);
		
		$pageNav = new SRPager($total);
		
		# Do the main database query
		//change this to match db.php
		$query = 	 "SELECT bannedIP"
					."\nFROM $bannedIPTable "
					."\nORDER BY bannedIP LIMIT $pageNav->limitStart, $pageNav->limit";
		$rows = SRBridgeDatabase::Query($query);
		if ($rows == false)
		{
			echo "<h3>".$this->GetString($this->_BannedIPModule, 'NoBannedIPs')."</h3>";
		}
		
	  	$formName						= "adminForm";	
	  	$formAction						= "index2.php";
			
		$columnNames					= Array();
		$columnNames[]					= "Banned IP";
		
		$propertyNames					= Array();
		$propertyNames[]				= "bannedIP";
		
	    SRControls::ItemListing($formName, $formAction, $columnNames, $propertyNames, $rows, 'list', $this, $pageNav);		             	  
	}  		
			
 	function _PrintFormTable($values=NULL)
     {
		global $option;
		

		$tip			= $this->GetString($this->_BannedIPModule, 'FormIPTip');
		$ipTip			= Simple_Review_Common::CreateToolTip($this->GetString($this->_BannedIPModule, 'FormIP'), $tip);
		
		$jsPath = $this->_AddonManager->Bridge->SiteUrl."administrator/components/$option/addons/modules/$this->addonName";
		Simple_Review_Common::IncludeJavaScript("$jsPath/$this->addonName.Admin.js");  	       
    ?>

	<script type="text/javascript">
	function submitbutton(pressbutton)
	{
	    validateSRForm(pressbutton);
	}			
	</script>

	<fieldset>
		<legend><?php echo $this->GetString($this->_BannedIPModule, 'BannedIP');?></legend>

		<!--ip-->
		<label for="bannedIP"><?php echo $ipTip;?></label>
        <input type="text" name="bannedIP" id="bannedIP" value="<?php echo $values['bannedIP']; ?>" /><br/>
              
    
	</fieldset>    

    <?php

      }
	
}
?>	