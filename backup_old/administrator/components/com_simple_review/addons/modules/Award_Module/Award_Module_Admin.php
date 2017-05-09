<?php
/**
 *  $Id: Award_Module_Admin.php 122 2009-09-13 12:39:25Z rowan $
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
class Award_Module_Admin extends Module_Base_Admin
{   
	var $_AwardModule;
	function Award_Module_Admin(&$addonManager, &$adminModuleName, &$moduleName)
	{
		$this->addonPath = dirname(__FILE__);
	  	$this->hasCSS=false;
	  	$this->hasLanguage=false;
		$this->hasConfig=false;//admin doesn't actually have config	  
		$this->showOnConfigScreen=false;		
						
	  	parent::Module_Base_Admin($addonManager, $adminModuleName, $moduleName);
		
		$this->_AwardModule =& $this->_AddonManager->GetModule("Award_Module", false);		
		$this->friendlyName = $this->GetString($this->_AwardModule, 'Awards');
		$this->defaultTaskName = $this->GetString($this->_AwardModule, 'AdminDescription');				
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
			$ids = $this->_AddonManager->Bridge->GetParameter( $_REQUEST, 'awardID', array(0) ); 
			$this->_Delete( $ids );
			break;
					  
			case "edit":
			$id = $this->_AddonManager->Bridge->GetParameter( $_REQUEST, 'awardID', array(0) );
			if(is_array($id))
			{
			 	$id = $id[0]; 
			}
			$this->_Edit( intval($id));			
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
		$row = new SRDB_Award( $this->_AddonManager->Database );
		$row->load( null );

        $values['name'] = null;
        $values['imageURL'] = null;

        echo "<form action='index2.php' method='post' name='adminForm' id='adminForm' class='adminForm'>";
        $this->_PrintFormTable($values);
        echo "<input type='hidden' name='awardID' value='$row->awardID' />";
        echo "<input type='hidden' name='option' value='$option' />";
        echo "<input type='hidden' name='task' value='' />";
        echo "<input type='hidden' name='module' value='$this->addonName' />";
        echo "</form>";
    }
	
	function _Save($apply=false)
	{
	  $row = new SRDB_Award($this->_AddonManager->Database );
	
	  if (!$row->bind( $_POST )) {
	    echo "<script> alert('".$row->getError()."'); window.history.go(-1); </script>";
	    exit('Error binding from post.');
	  }
	
	
	  if (!$row->store()) {
	    echo "<script> alert('".$row->getError()."'); window.history.go(-1); </script>";
	    exit('Error storing.');
	  }
	  if($apply)
	  {
	     $this->_AddonManager->Bridge->Redirect( $this->GetURL('edit', "awardID=$row->awardID"), 'Saved'); 
	  }
	  else
	  {	  
	  	$this->_AddonManager->Bridge->Redirect( $this->GetURL('list'), 'Saved');  	  
	  }
	}

	function _Edit($id)
	{
		global $option;
		$row = new SRDB_Award( $this->_AddonManager->Database );
		$row->load( $id );

	    $values['name'] = $row->name; 
	    $values['imageURL'] = $row->imageURL;
	
	    echo "<form action='index2.php' method='post' name='adminForm' id='adminForm' class='adminForm'>";
	    $this->_PrintFormTable($values);
	    echo "<input type='hidden' name='awardID' value='$row->awardID' />";
	    echo "<input type='hidden' name='option' value='$option' />";
	    echo "<input type='hidden' name='task' value='' />";
	    echo "<input type='hidden' name='module' value='$this->addonName' />";
	    echo "</form>";

	}

	function _Delete(&$ids)
	{
	  	$awardTable = SRDB_Award::TableName();
		 if (!is_array( $ids ) || count( $ids ) < 1) {
		    echo "<script> alert('Select an item to delete'); window.history.go(-1);</script>";
		    exit;
		 }
		 if (count( $ids )) {
		    $ids = implode( ',', $ids );
			$query = "DELETE FROM $awardTable WHERE awardID IN ($ids)";
		    if (!SRBridgeDatabase::Query($query)) {
		      die("Turn on debug mode to see details.");
		    }
		 }
		 $this->_AddonManager->Bridge->Redirect( $this->_GetURL("list"), 'Deleted'); 
	}
			
	function _List()
	{
		global $option;
		$awardTable = SRDB_Award::TableName();
				
		$query = "SELECT COUNT(*)"
		. "\n FROM $awardTable";
		$total = SRBridgeDatabase::ScalarQuery($query);
				
		$pageNav = new SRPager($total);
		
		$awardModule =& $this->_AwardModule;
		$rows =& $awardModule->GetAwards('awardID asc', $pageNav->limitStart, $pageNav->limit);
		if (count($rows) == 0)
		{
			echo "<h3>".$this->GetString($this->_AwardModule, 'NoAwards')."</h3>";
		}
		
	  	$formName						= "adminForm";	
	  	$formAction						= "index2.php";
			
		$columnNames					= Array();
		
		//change this to match db.php
		$columnNames[]					= "Award ID";
		$columnNames[]					= "Name";
		$columnNames[]					= "Image URL";
		
		$propertyNames					= Array();
		
		$propertyNames[]				= "awardID";
		$propertyNames[]				= Array('name');
		$propertyNames[]				= Array('imageURL');
		
	    SRControls::ItemListing($formName, $formAction, $columnNames, $propertyNames, $rows, 'list', $this, $pageNav);		             	  
	}  		
			
 	function _PrintFormTable($values=NULL)
     {
		global $option;
	
		//change this to match db.php
		$tip				= $this->GetString($this->_AwardModule, 'FormNameTip');
		$nameTip			= Simple_Review_Common::CreateToolTip($this->GetString($this->_AwardModule, 'FormName'), $tip);
		
		$tip				= $this->GetString($this->_AwardModule, 'FormImageUrlTip');
		$imageTip			= Simple_Review_Common::CreateToolTip($this->GetString($this->_AwardModule, 'FormImageUrl'), $tip);
		
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
		<legend><?php echo $this->GetString($this->_AwardModule, 'Award');?></legend>

		<!--name-->
		<label for="name"><?php echo $nameTip;?></label>
        <input type="text" name="name" id="name" value="<?php echo $values['name']; ?>" /><br/>
        
        <!--template-->
        <label for="imageURL"><?php echo $imageTip;?></label>
	    <input type="text" name="imageURL" id="imageURL" class="big" value="<?php echo $values['imageURL']; ?>"/><br/>       
    
	</fieldset>    

    <?php

      }
	
}
?>	