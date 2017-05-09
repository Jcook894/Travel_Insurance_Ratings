<?php
/**
 *  $Id: Template_Module_Admin.php 96 2009-06-13 09:31:58Z rowan $
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
class Template_Module_Admin extends Module_Base_Admin
{   
  	var $_TemplateModule; 
	function Template_Module_Admin(&$addonManager, &$adminModuleName, &$moduleName)
	{
	  	//$this->addonName = $moduleName;
		$this->addonPath = dirname(__FILE__);
	  	$this->hasCSS=false;
	  	$this->hasLanguage=true;
		$this->hasConfig=false;//admin doesn't actually have config	 
		$this->showOnConfigScreen=false; 		
						
	  	parent::Module_Base_Admin($addonManager, $adminModuleName, $moduleName);
		
		$this->_TemplateModule =& $this->_AddonManager->GetModule('Template_Module', false);		 	
		$this->friendlyName = $this->GetString($this->_TemplateModule, 'Templates');
		$this->defaultTaskName = $this->GetString($this->_TemplateModule, 'AdminDescription');				
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
			$ids = $this->_AddonManager->Bridge->GetParameter( $_REQUEST, 'templateID', array(0) );
			$this->_Delete( $ids );
			break;
					  
			case "edit":
			$id = $this->_AddonManager->Bridge->GetParameter( $_REQUEST, 'templateID', array(0) );
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
		$row = new SRDB_Template($this->_AddonManager->Database);
		$row->load( null );

        $values['name'] = null;
        $values['template'] = null;

        echo "<table width=100%><tr><td>";
        echo "<form action='index2.php' method='post' name='adminForm' id='adminForm' class='adminForm'>";
        $this->_PrintFormTable($values);
        echo "<input type='hidden' name='templateID' value='$row->templateID' />";
        echo "<input type='hidden' name='option' value='$option' />";
        echo "<input type='hidden' name='task' value='' />";
        echo "<input type='hidden' name='module' value='$this->addonName' />";
        echo "</form>";
        echo "</td></tr></table>";
        $tagMod =& $this->_AddonManager->GetModule("Tag_Module");
        $tagMod->ListTags(); 
    }
	
	function _Save($apply=false)
	{
	  $row = new SRDB_Template($this->_AddonManager->Database);
	
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
	     $this->_AddonManager->Bridge->Redirect( $this->GetURL("edit", "templateID=$row->templateID"), 'Saved'); 
	  }
	  else
	  {	  
	  	$this->_AddonManager->Bridge->Redirect( $this->GetURL("list"), 'Saved');  	  
	  }
	}

	function _Edit($id)
	{
		global $option;
		$row = new SRDB_Template($this->_AddonManager->Database);
		$row->load( $id );

	    $values['name'] = $row->name;
	    $values['template'] = $row->template;
	
	    echo "<table width=100%><tr><td>";
	    echo "<form action='index2.php' method='post' name='adminForm' id='adminForm' class='adminForm'>";
	    $this->_PrintFormTable($values);
	    echo "<input type='hidden' name='templateID' value='$row->templateID' />";
	    echo "<input type='hidden' name='option' value='$option' />";
	    echo "<input type='hidden' name='task' value='' />";
	    echo "<input type='hidden' name='module' value='$this->addonName' />";
	    echo "</form>";
	    echo "</td></tr></table>";
        $tagMod =& $this->_AddonManager->GetModule("Tag_Module");
        $tagMod->ListTags(); 	    

	}

	function _Delete(&$ids)
	{
	  $templateTable = SRDB_Template::TableName();
		  if (!is_array( $ids ) || count( $ids ) < 1) {
		    echo "<script> alert('Select an item to delete'); window.history.go(-1);</script>";
		    exit;
		  }
		  if (count( $ids )) {
		    $templateDB = new SRDB_Template($this->_AddonManager->Database); 
		   	foreach($ids as $id)
		   	{
		   		$templateDB->SR_Delete($id);
		   	}	
		  }
		  $this->_AddonManager->Bridge->Redirect( $this->GetURL("list"), 'Deleted'); 
	}
			
	function _List()
	{
		global $option;
		$templateTable = SRDB_Template::TableName();

		
		$query = "SELECT COUNT(*)"
		. "\n FROM $templateTable";
		$total = SRBridgeDatabase::ScalarQuery($query);
		
		$pageNav = new SRPager( $total);
		
		# Do the main database query
		$query = 	 "SELECT templateID, name, template"
					."\nFROM $templateTable "
					."\nORDER BY templateID LIMIT $pageNav->limitStart, $pageNav->limit";
		$rows = $rows = SRBridgeDatabase::Query($query);
		if ($rows == false)
		{
			echo "<h3>".$this->GetString($this->_TemplateModule, 'NoTemplates')."</h3>";
		}
		
	  	$formName						= "adminForm";	
	  	$formAction						= "index2.php";
			
		$columnNames					= Array();
		
		$columnNames[]					= "Template ID";
		$columnNames[]					= "Name";
		
		$propertyNames					= Array();
		
		$propertyNames[]				= "templateID";
		$propertyNames[]				= Array('name');
		
	    SRControls::ItemListing($formName, $formAction, $columnNames, $propertyNames, $rows, 'list', $this, $pageNav);		             	  
	}  		
			
	
 	function _PrintFormTable($values=NULL)
     {
		global $option;
		
		$tip				= $this->GetString($this->_TemplateModule, 'FormTemplateNameTip');
		$nameTip			= Simple_Review_Common::CreateToolTip($this->GetString($this->_TemplateModule, 'FormTemplateName'), $tip);
		
		$tip				= $this->GetString($this->_TemplateModule, 'FormTemplateTip');
		$templateTip		= Simple_Review_Common::CreateToolTip($this->GetString($this->_TemplateModule, 'FormTemplate'), $tip);
		
		$jsPath = $this->_AddonManager->Bridge->SiteUrl."administrator/components/$option/addons/modules/$this->addonName";
		Simple_Review_Common::IncludeJavaScript("$jsPath/$this->addonName.Admin.js");
    ?>

	<script type="text/javascript">
	function submitbutton(pressbutton)
	{
	  	<?php SRHtmlControls::EditorGetContents( 'editortemplate', 'template' ); ?>
	    validateSRForm(pressbutton);
	}			
	</script>

	<fieldset>
		<legend><?php echo $this->GetString($this->_TemplateModule, 'Template');?></legend>

		<!--name-->
		<label for="name"><?php echo $nameTip;?></label>
        <input type="text" name="name" id="name" value="<?php echo $values['name']; ?>" /><br/>
        
        <!--template-->
        <label for="template"><?php echo $templateTip;?></label>
	    <?php
	          // parameters : areaname, content, hidden field, width, height, rows, cols
	          SRHtmlControls::EditorInsertArea( 'editortemplate',  $values['template'] , 'template', null, '350', '75', '20' ) ;
	    ?>        
    
	</fieldset>    

    <?php

      }
	
}
?>	