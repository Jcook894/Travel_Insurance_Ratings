<?php
/**
 *  $Id: Category_Module_Admin.php 122 2009-09-13 12:39:25Z rowan $
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
class Category_Module_Admin extends Module_Base_Admin
{   
	var $_CatModule;
	function Category_Module_Admin(&$addonManager, &$adminModuleName, &$moduleName)
	{
		$this->addonPath = dirname(__FILE__);
	  	$this->hasCSS=true;
	  	$this->hasLanguage=true;
		$this->hasConfig=true;
			
		$this->dependsOnModules = array('Category_Module', 'Tag_Module', 'Template_Module');		
							
	  	parent::Module_Base_Admin($addonManager, $adminModuleName, $moduleName);
		
		$this->_CatModule =& $this->_AddonManager->GetModule("Category_Module", false);		 	
		$this->friendlyName = $this->GetString($this->_CatModule, 'Categories');
		$this->defaultTaskName = $this->GetString($this->_CatModule, 'AdminDescription');		
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
			$ids = $this->_AddonManager->Bridge->GetParameter( $_REQUEST, 'categoryID', array(0) );
			$this->_Delete( $ids );
			break;
					  		  
			case "edit":
			$id = $this->_AddonManager->Bridge->GetParameter( $_REQUEST, 'categoryID', array(0));
			if(is_array($id))
			{
			 	$id = $id[0]; 
			}			
			$this->_Edit( intval($id));			
			break;
			
			case "unpublish":
			$id = $this->_AddonManager->Bridge->GetParameter( $_REQUEST, 'categoryID', array(0) );
			$this->_Publish($id, 0 );
			break;
			
			case "publish":
			$id = $this->_AddonManager->Bridge->GetParameter( $_REQUEST, 'categoryID', array(0) );
			$this->_Publish($id, 1 );
			break;	

			case "list":
			default:	
			$this->_List();
			break;
		} 
	} 

	function _Delete( &$ids ) {
	  if (!is_array( $ids ) || count( $ids ) < 1) {
	    echo "<script> alert('Select an item to delete'); window.history.go(-1);</script>";
	    exit;
	  }
	  if (count( $ids ))
	  {	   
		$catDB = new SRDB_Category( $this->_AddonManager->Database ); 
	   	foreach($ids as $id)
	   	{
	   		$catDB->SR_Delete( intval($id));
	   	}	   		    
	  }
	  $this->_AddonManager->Bridge->Redirect( $this->GetURL("list"), 'Deleted');
	}

	function _List()
	{
			global $option;
			$categoryTable = SRDB_Category::TableName();
			
			
			$catDB = new SRDB_Category( $this->_AddonManager->Database );
			$total = $catDB->SR_Count(false);
			
			$pageNav = new SRPager($total);
			
			$rows = $catDB->SR_Listing($pageNav->limitStart, $pageNav->limit);

			if ($rows == false) 
			{
				echo "<h3>".$this->GetString($this->_CatModule, 'NoCategories')."</h3>";
		  	}	 

			$formName						= "adminForm";	
			$formAction						= "index2.php";
				
			$columnNames					= Array();
			
			$columnNames[]					= "Category ID";
			$columnNames[]					= "Name";
			$columnNames[]					= "Published";
			
			$columnNames[]					= "Description";
						
			$propertyNames					= Array();
			
			$propertyNames[]				= "categoryID";
			$propertyNames[]				= Array('name');
			$propertyNames[]				= 'published';
			
			$propertyNames[]				= 'description';
			
			SRControls::ItemListing($formName, $formAction, $columnNames, $propertyNames, $rows, 'list', $this, $pageNav);							   
	}

	function _LoadDependencies()
	{
      		global $option;
            $jsPath = $this->_AddonManager->Bridge->SiteUrl."administrator/components/$option/addons/modules/Tag_Module";
			Simple_Review_Common::IncludeJavaScript("$jsPath/Tag_Module.Admin.js"); 	  
	} 
	
    function _DisplayCategoryForm(&$row, $isNew=true)
    {
            global  $option;
			$categoryTable = SRDB_Category::TableName();
            
            $catDB = new SRDB_Category( $this->_AddonManager->Database ); 

            $values['categoryID']= -1;
            $values['parentCategoryID']= null;
            $values['name'] = null;
            $values['templateID'] = null;
            $values['description'] = null;
            $values['title1name'] = null;
            $values['title2name'] = null;
            $values['title3name'] = null;
            $values['published'] = null;
            $values['categoryImageURL'] = '';//$this->_AddonManager->Bridge->SiteUrl."components/$option/images/category.png";
     		$values['userReviews'] = 0;
			$values['pageName'] = null;
     		
	        $catCount = $catDB->SR_Count(false);	
	        	                
     		$values['catOrder'] = $catCount+1;
     		
            if($isNew)
            {
             	$catTitle =& new SRDB_Category_Title( $this->_AddonManager->Database );
             	$catTitle->categoryTitleID = 0;
             	$catTitle->categoryID = $row->categoryID;
             	$catTitle->titleName = "";
             	$catTitle->titleOrder = 1;
				$catTitle->titleType = 'Text';
				$catTitle->mandatory = 1;
				$values['titleNames'][] = $catTitle;
            }
            else
            {
	            $parentCategory = $catDB->SR_NodesParent($row->lft, $row->rgt);
	            
                $values['categoryID']= $row->categoryID;
				$values['parentCategoryID'] = intval($parentCategory->categoryID);                
                $values['name'] = $row->name;
                $values['templateID'] = $row->templateID;
                $values['description'] = $row->description;
                $values['published'] = $row->published;
                $values['categoryImageURL'] = $row->categoryImageURL;
     			$values['userReviews'] = $row->userReviews;
     			$values['catOrder'] = $row->catOrder;
				$values['pageName'] = rawurldecode($row->pageName);
     			
     			$catTitles =& new SRDB_Category_Title( $this->_AddonManager->Database );
     			$values['titleNames'] = $catTitles->SR_LoadCategoryTitles($row->categoryID);
     			
            }            

            echo '<form action="index2.php" method="post" name="adminForm" id="adminForm" class="adminForm">';

            $this->_PrintFormTable($values);
            
            echo "<input type='hidden' name='categoryID' value='$row->categoryID' />";
            echo "<input type='hidden' name='option' value='$option' />";
            echo "<input type='hidden' name='module' value='$this->addonName' />";
            echo "<input type='hidden' name='task' value='' />";
            echo "</form>";
    }

     function _PrintFormTable($values=null)
     {
       	global $option;
		
       	$SR_Addon_Manager =& $this->_AddonManager;
				
	  	$tagModule =& $SR_Addon_Manager->GetModule("Tag_Module", false);  	
		$tagModule->InitDynamicFields('sr_dynamic_title');
		
		$templateModule =& $SR_Addon_Manager->GetModule("Template_Module", false);   
		
		$catIDToExclude = $values['categoryID'] > 0 ? $values['categoryID'] : null;
		
		$catModule =& $this->_CatModule;  
       	
		$tip 			= $this->GetString($catModule, 'FormParentCategoryTip');
		$catListTip 	= Simple_Review_Common::CreateToolTip($this->GetString($catModule, 'FormParentCategory'), $tip);
		$catList = $catModule->CategoryDropDown('parentCategoryID', true, $values['parentCategoryID'], 'size="1" class="inputbox"', $catIDToExclude);
	   
		$tip 			= $this->GetString($catModule, 'FormNameTip');
		$catNameTip		= Simple_Review_Common::CreateToolTip($this->GetString($catModule, 'FormName'), $tip);
	   
		$tip 			= $this->GetString($catModule, 'FormTemplateTip');
		$catTemplateTip	= Simple_Review_Common::CreateToolTip($this->GetString($catModule, 'FormTemplate'), $tip);
		$catTemplate	= $templateModule->TemplateList( $values['templateID'] );	   	

		$tip 			= $this->GetString($catModule, 'FormDescriptionTip');
		$descTip		= Simple_Review_Common::CreateToolTip($this->GetString($catModule, 'FormDescription'), $tip);
		
		$tip 			= $this->GetString($catModule, 'FormTitle1Tip');
		$title1Tip		= Simple_Review_Common::CreateToolTip($this->GetString($catModule, 'FormTitle1'), $tip);
		
		$tip 			= $this->GetString($catModule, 'FormImageUrlTip');	
		$catImgTip		= Simple_Review_Common::CreateToolTip($this->GetString($catModule, 'FormImageUrl'), $tip);
		
		$tip 			= $this->GetString($catModule, 'FormPublishedTip');
		$publishTip		= Simple_Review_Common::CreateToolTip($this->GetString($catModule, 'FormPublishedTip'), $tip);	
		
		$tip 			= $this->GetString($catModule, 'FormAllowUserReviewsTip');
		$userReviewTip	= Simple_Review_Common::CreateToolTip($this->GetString($catModule, 'FormAllowUserReviews'), $tip);
		
		$tip 			= $this->GetString($catModule, 'FormOrderTip');
		$orderTip		= Simple_Review_Common::CreateToolTip($this->GetString($catModule, 'FormOrder'), $tip);
		
		$tip 			= $this->GetString($catModule, 'FormPageNameTip');
		$pageNameTip	= Simple_Review_Common::CreateToolTip($this->GetString($catModule, 'FormPageName'), $tip);
		
		$tip 			= $this->GetString($catModule, 'FormMandatoryTip');
		$mandatoryTip	= Simple_Review_Common::CreateToolTip($this->GetString($catModule, 'FormMandatory'), $tip);						

		$jsPath = $this->_AddonManager->Bridge->SiteUrl."administrator/components/$option/addons/modules/$this->addonName";
		Simple_Review_Common::IncludeJavaScript("$jsPath/$this->addonName.Admin.js");  
		
		$tips = new stdClass();
		$tips->catListTip = $catListTip;
		$tips->catNameTip = $catNameTip;
		$tips->catTemplateTip = $catTemplateTip;
		$tips->descTip = $descTip;
		$tips->title1Tip = $title1Tip;
		$tips->catImgTip = $catImgTip;
		$tips->publishTip = $publishTip;
		$tips->userReviewTip = $userReviewTip;
		$tips->orderTip = $orderTip;
		$tips->pageNameTip = $pageNameTip;
		$tips->mandatoryTip = $mandatoryTip;

		$lists = new stdClass();
		$lists->parentCategories = $catList;
		$lists->templates = $catTemplate;

		require_once("$this->addonPath/templates/Admin/CategoryForm.php");
		$template = new TemplateCategoryForm($this->_CatModule, $tips, $lists);
		
		$template->Display($values);
    ?>
 
    <?php

      }
		
	function DisplayConfiguration(&$srConfig, &$allPlugins)
	{
		$catModule =& $this->_CatModule;
		$langModule =& $this->_AddonManager->GetModule('Language_Module', false);
	  	$control_name="params";
	      //$this->tmpl->displayParsedTemplate("body");
	    echo '<table>'; 
		
		//amount of top N Reviews 
		$settingName = $this->addonName."_N_TOP_REVIEWS";
	    $control = SRHtmlControls::Text($settingName, constant($settingName), $control_name ,3);
	    $text = $this->GetString($catModule, 'ConfigNumberTop');
	    $tip  = $this->GetString($catModule, 'ConfigNumberTopTip');
		$srConfig->DisplaySingleTabParam($text,$tip, $control);
		
		//limit Top N results to current category
		$settingName = $this->addonName."_N_TOP_REVIEWS_CURRENT_CAT";
		$control = SRHtmlControls::YesNoRadio("params[$settingName]", 'class="inputbox"', constant($settingName));
	    $text = $this->GetString($catModule, 'ConfigTopCurrentCat');
	    $tip  = $this->GetString($catModule, 'ConfigTopCurrentCatTip');		
		$srConfig->DisplaySingleTabParam($text, $tip, $control);		
		
		//review order
		$settingName = $this->addonName."_REVIEW_SORT_FIELD";
		$order = array();
		$order[] = 'r.createdDate';
		$order[] = 'r.lastModifiedDate';
		$order[] = 'r.score';				
		$order[] = 'title1';
		$order[] = 'title2';
		$order[] = 'title3';			
		
		$displayItems = array();
		$displayItems[] = 'Created Date';
		$displayItems[] = 'Last Modified Date';
		$displayItems[] = 'Review Score';
		$displayItems[] = 'Review Title 1';
		$displayItems[] = 'Review Title 2';
		$displayItems[] = 'Review Title 3';

		$control = SRHtmlControls::DropDownList($settingName, $order, constant($settingName),$displayItems);
		$text = $this->GetString($catModule, 'ConfigSortField');
		$tip  = $this->GetString($catModule, 'ConfigSortFieldTip');
		$srConfig->DisplaySingleTabParam($text, $tip, $control);			
		
		//descending order
		$settingName = $this->addonName."_REVIEW_SORT_ORDER";
		$control = SRHtmlControls::CheckBox($settingName, $settingName, 'desc', constant($settingName) == 'desc');
		$text = $this->GetString($catModule, 'ConfigSortOrder');
		$tip  = $this->GetString($catModule, 'ConfigSortOrderTip');
		$srConfig->DisplaySingleTabParam($text, $tip, $control);	
		
		//show review count
		$settingName = $this->addonName."_SHOW_REVIEW_COUNT";	
		$control = SRHtmlControls::YesNoRadio("params[$settingName]",'class="inputbox"',constant($settingName) );		
	    $text = $this->GetString($catModule, 'ConfigReviewCount');
	    $tip  = $this->GetString($catModule, 'ConfigReviewCountTip');		
		$srConfig->DisplaySingleTabParam($text, $tip, $control);
		
		//bypass root on single cat
		$settingName = $this->addonName."_SINGLE_CAT_ROOT_BYPASS";
		$control = SRHtmlControls::YesNoRadio("params[$settingName]", 'class="inputbox"', constant($settingName));
	    $text = $this->GetString($catModule, 'ConfigSingleByPass');
	    $tip  = $this->GetString($catModule, 'ConfigSingleByPassTip');		
		$srConfig->DisplaySingleTabParam($text, $tip, $control);					
		
		
		?>
		<tr><td colspan='2'><h3><?php echo $this->GetString($catModule, 'ConfigReivewListing');?></h3></td></tr>
		<tr><td colspan='2'><?php echo $this->GetString($catModule, 'ConfigReivewListingTip');?><br/></td></tr>
		<?php
				
		//title 1
		$settingName = $this->addonName."_SHOW_TITLE1";
		$control = SRHtmlControls::YesNoRadio("params[$settingName]", 'class="inputbox"', constant($settingName));
	    $text = $this->GetString($catModule, 'ConfigTitle1');
	    $tip  = $this->GetString($catModule, 'ConfigTitle1Tip');		
		$srConfig->DisplaySingleTabParam($text, $tip, $control);
		
		//title 2
		$settingName = $this->addonName."_SHOW_TITLE2";
		$control = SRHtmlControls::YesNoRadio("params[$settingName]", 'class="inputbox"', constant($settingName));
	    $text = $this->GetString($catModule, 'ConfigTitle2');
	    $tip  = $this->GetString($catModule, 'ConfigTitle2Tip');		
		$srConfig->DisplaySingleTabParam($text, $tip, $control);
		
		//title 3
		$settingName = $this->addonName."_SHOW_TITLE3";
		$control = SRHtmlControls::YesNoRadio("params[$settingName]", 'class="inputbox"', constant($settingName));
	    $text = $this->GetString($catModule, 'ConfigTitle3');
	    $tip  = $this->GetString($catModule, 'ConfigTitle3Tip');		
		$srConfig->DisplaySingleTabParam($text, $tip, $control);				
		
		//rating
		$settingName = $this->addonName."_SHOW_RATING";
		$control = SRHtmlControls::YesNoRadio("params[$settingName]", 'class="inputbox"', constant($settingName));
	    $text = $this->GetString($catModule, 'ConfigRating');
	    $tip  = $this->GetString($catModule, 'ConfigRatingTip');		
		$srConfig->DisplaySingleTabParam($text, $tip, $control);	
		
		//reviewer
		$settingName = $this->addonName."_SHOW_REVIEWER";
		$control = SRHtmlControls::YesNoRadio("params[$settingName]", 'class="inputbox"',constant($settingName));
	    $text = $this->GetString($catModule, 'ConfigReviewer');
	    $tip  = $this->GetString($catModule, 'ConfigReviewerTip');		
		$srConfig->DisplaySingleTabParam($text, $tip, $control);	
		
		//date
		$settingName = $this->addonName."_SHOW_DATE";
		$control = SRHtmlControls::YesNoRadio("params[$settingName]", 'class="inputbox"', constant($settingName));
	    $text = $this->GetString($catModule, 'ConfigDate');
	    $tip  = $this->GetString($catModule, 'ConfigDateTip');		
		$srConfig->DisplaySingleTabParam($text, $tip, $control);		
		
		//title2 external link
		$settingName = $this->addonName."_TITLE2_LINK";
		$control = SRHtmlControls::YesNoRadio("params[$settingName]", 'class="inputbox"', constant($settingName));
	    $text = $this->GetString($catModule, 'ConfigTitle2Link');
	    $tip  = $this->GetString($catModule, 'ConfigTitle2LinkTip');		
		$srConfig->DisplaySingleTabParam($text, $tip, $control);		
		
		//title3 external link
		$settingName = $this->addonName."_TITLE3_LINK";
		$control = SRHtmlControls::YesNoRadio("params[$settingName]", 'class="inputbox"', constant($settingName));
	    $text = $this->GetString($catModule, 'ConfigTitle3Link');
	    $tip  = $this->GetString($catModule, 'ConfigTitle3LinkTip');		
		$srConfig->DisplaySingleTabParam($text, $tip, $control);			
		
		
		//title2 review link
		$settingName = $this->addonName."_TITLE2_IS_REVIEW_LINK";
		$control = SRHtmlControls::YesNoRadio("params[$settingName]", 'class="inputbox"', constant($settingName));
	    $text = $this->GetString($catModule, 'ConfigTitle2IsReviewLink');
	    $tip  = $this->GetString($catModule, 'ConfigTitle2IsReviewLinkTip');		
		$srConfig->DisplaySingleTabParam($text, $tip, $control);				
		
		//title3 review link
		$settingName = $this->addonName."_TITLE3_IS_REVIEW_LINK";
		$control = SRHtmlControls::YesNoRadio("params[$settingName]", 'class="inputbox"', constant($settingName));
	    $text = $this->GetString($catModule, 'ConfigTitle3IsReviewLink');
	    $tip  = $this->GetString($catModule, 'ConfigTitle3IsReviewLinkTip');		
		$srConfig->DisplaySingleTabParam($text, $tip, $control);						

		//number of top level categories items
		$settingName = $this->addonName."_NO_OF_ITEMS";
		$control = SRHtmlControls::Text($settingName, constant($settingName), $control_name ,3);
	    $text = $this->GetString($catModule, 'ConfigNumberItems');
	    $tip  = $this->GetString($catModule, 'ConfigNumberItemsTip');		
		$srConfig->DisplaySingleTabParam($text, $tip, $control);		
		
		//number of review items
		$settingName = $this->addonName."_NO_OF_REVIEW_ITEMS";
		$control = SRHtmlControls::Text($settingName, constant($settingName), $control_name ,3);
	    $text = $this->GetString($catModule, 'ConfigNumberReviewItems');
	    $tip  = $this->GetString($catModule, 'ConfigNumberReviewItemsTip');		
		$srConfig->DisplaySingleTabParam($text, $tip, $control);			

		$positions = Array("HEAD", "FOOT");
		foreach($positions as $pos)
		{
		  $description = "Plugin$pos";
		  $control = $this->_LoadPluginConfiguration($allPlugins, $pos);
		  $srConfig->DisplaySingleTabParamLine($description, $control);
		}		
		
		?> 
	     <tr>
	     <td colspan='2'>
		 	<h3><?php echo $this->GetString($langModule, 'AdvancedOptions');?></h3>
		 	<?php echo $this->GetString($langModule, 'AdvancedOptionsTip');?>
		 </td>
	     </tr>
		<?php 
		
		//category URL
		$settingName = $this->addonName."_URL";
	    $control = SRHtmlControls::Text($settingName, constant($settingName), $control_name ,100);
	    $text = $this->GetString($catModule, 'ConfigReviewUrl');
	    $tip  = $this->GetString($catModule, 'ConfigReviewUrlTip');
		$srConfig->DisplaySingleTabParam($text,$tip, $control);	
				
		echo "</table>";
	}
		
	function SaveConfiguration($params)
	{
	  global $option;
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
	  
	  //TODO:still needed? should move into top reviews
	  $settingName = $this->addonName."_N_TOP_REVIEWS";
	  $config .="define('$settingName', '".$params[$settingName]."');\n";  
	  
	  $settingName = $this->addonName."_N_TOP_REVIEWS_CURRENT_CAT";
	  $config .="define('$settingName', '".$params[$settingName]."');\n";   
	       
	  $settingName = $this->addonName."_SHOW_REVIEW_COUNT";	    
	  $config .= "define('$settingName','".$params[$settingName]."');\n";	
	
	  //Category_Module_HEAD	 
	  $settingName = $this->addonName."_HEAD";	  
	  $head = array_key_exists($settingName, $params)  ? implode('||',$params[$settingName]) : '';	 
	  $config .= "define('".$settingName."','".$head."');\n";
	  	  
	  //Category_Module_FOOT
	  $settingName = $this->addonName."_FOOT";	
	  $foot = array_key_exists($settingName, $params)  ?  implode('||',$params[$settingName]) : '';
	  $config .= "define('".$settingName."','".$foot."');\n";			  

	  $settingName = $this->addonName."_SHOW_TITLE1";
	  $config .="define('$settingName', '".$params[$settingName]."');\n";  

	  $settingName = $this->addonName."_SHOW_TITLE2";
	  $config .="define('$settingName', '".$params[$settingName]."');\n";  
	  
	  $settingName = $this->addonName."_SHOW_TITLE3";
	  $config .="define('$settingName', '".$params[$settingName]."');\n";  
	  
	  $settingName = $this->addonName."_SHOW_RATING";
	  $config .="define('$settingName', '".$params[$settingName]."');\n";  
	  
	  $settingName = $this->addonName."_SHOW_REVIEWER";
	  $config .="define('$settingName', '".$params[$settingName]."');\n";  	  	  	  	  
	  
	  $settingName = $this->addonName."_SHOW_DATE";
	  $config .="define('$settingName', '".$params[$settingName]."');\n";
	  
	  $settingName = $this->addonName."_TITLE2_LINK";
	  $config .="define('$settingName', '".$params[$settingName]."');\n";	  	  

	  $settingName = $this->addonName."_TITLE3_LINK";
	  $config .="define('$settingName', '".$params[$settingName]."');\n";
	  
	  $settingName = $this->addonName."_NO_OF_ITEMS";
	  $config .="define('$settingName', '".$params[$settingName]."');\n";

	  $settingName = $this->addonName."_NO_OF_REVIEW_ITEMS";
	  $config .="define('$settingName', '".$params[$settingName]."');\n";

	  $settingName = $this->addonName."_URL";
	  $config .="define('$settingName', '".$params[$settingName]."');\n";	  
	  
	  $settingName = $this->addonName."_REVIEW_SORT_FIELD";
	  $config .="define('$settingName', '".$params[$settingName]."');\n";	
	  
	  $settingName = $this->addonName."_REVIEW_SORT_ORDER";
	  $sortOrder = array_key_exists($settingName, $params)  ?  'desc' : 'asc';	
	  $config .="define('$settingName', '".$sortOrder."');\n";	 	   
	  

	  $settingName = $this->addonName."_SINGLE_CAT_ROOT_BYPASS";
	  $config .="define('$settingName', '".$params[$settingName]."');\n";
	  
	  $settingName = $this->addonName."_TITLE2_IS_REVIEW_LINK";
	  $config .="define('$settingName', '".$params[$settingName]."');\n";  
	  
	  $settingName = $this->addonName."_TITLE3_IS_REVIEW_LINK";
	  $config .="define('$settingName', '".$params[$settingName]."');\n";	  

	  $config .= "?>";	
	  if ($fp = fopen($configfile, "w")) 
	  {	
	    fputs($fp, $config, strlen($config));	
	    fclose ($fp);	
	  }
	  return true;
	} 

    function _New()
    {
            parent::_New();            
            $row = new SRDB_Category( $this->_AddonManager->Database );
			$row->load( null );
            $this->_DisplayCategoryForm($row, true);
    }

    function _Edit($id)
    {
      		global $option;
            parent::_Edit($id);  
            $row =& new SRDB_Category( $this->_AddonManager->Database );
			$row->load( $id );
            $this->_DisplayCategoryForm($row, false);
    }
    	
    	//TODO:check query clause
     function _JSCategorySelectList($parentCategoryID, $categoryID='')
        {
                $categoryTable = SRDB_Category::TableName();
                if($categoryID !='')
                {
                    $query = "SELECT * FROM $categoryTable where categoryID != $categoryID";		
                }
                else
                {
                    $query = "SELECT * FROM $categoryTable where categoryID != $categoryID";
                }
                $rows = SRBridgeDatabase::Query($query);
         echo "<select name='parentCategoryID' class='inputbox' size='1'>";
            if($parentCategoryID <= 0)
            {
        	  echo "<option value='-1' selected='selected'>none</option>";
            }
            else
            {
              echo "<option value='-1'>none</option>";
            }
        	//load possible parent categories
        	foreach($rows as $r)
        	{
                if($parentCategoryID >0 && $parentCategoryID== $r->categoryID)
                {
                echo "<option value='".$r->categoryID."' selected='selected'>".$r->name."</option>";
                }
                else
                {
                echo "<option value='".$r->categoryID."'>".$r->name."</option>";
                }
            }

        echo "</select>";

	}	   
		
	/**
	* Publishes or Unpublishes one or more records
	* @param array An array of unique category id numbers
	* @param integer 0 if unpublishing, 1 if publishing
	* @param string The current url option
	*/
	function _Publish(&$ids, $published ) 
	{
		$categoryTable = SRDB_Category::TableName();
		if (!is_array( $ids ) || count( $ids ) < 1) {
			$action = $publish ? 'publish' : 'unpublish';
			echo "<script> alert('Select an item to $action'); window.history.go(-1);</script>";
			exit;
		}
	
		$ids = implode( ',', $ids );
		
		$catDB = new SRDB_Category( $this->_AddonManager->Database );
		if($published == 0)
		{
			$categoryIDs;
			$reviewIDs;			
			$catDB->SR_GetSubCatsAndReviewsToUnpublish($ids, $categoryIDs, $reviewIDs);
			$ids = implode( ',', $categoryIDs );

			if($reviewIDs != null && count($reviewIDs > 0))
			{
				$reviewIDs = implode( ',', $reviewIDs );
				$reviewDB = new SRDB_Review( $this->_AddonManager->Database );
	    		$reviewDB->SR_Publish($reviewIDs, 0);
			}
		}
	
		
		$catDB->SR_Publish($ids,$published); 
		$this->_AddonManager->Bridge->Redirect( $this->GetURL("list"), $published == 0 ? 'Unpublished' : 'Published' );
	} 	
	
	function _Save($apply=false) {
	  $categoryTable = SRDB_Category::TableName();
	  
	  $row = new SRDB_Category( $this->_AddonManager->Database );
	  $parentCategoryID = $this->_AddonManager->Bridge->GetParameter($_POST ,'parentCategoryID', -1, 'int');  	  
	  if (!$row->bind( $_POST )) {
			SRError::Display($this->friendlyName."::_Save():Bind:".$row->getError(), true);
			return false;
	  }
		  		  	  
	  $bNewCat = $row->categoryID < 1;
		  
	  if(!$row->templateID)
	  {
	    $row->templateID  = -1;
	  }
	  
	  //TODO:needs to come from somewhere when implemented.
	  $row->userReviews=0;
	  	  
	  if($bNewCat)
	  {	  
	  	if(!$row->SR_Add($parentCategoryID)){
		   SRError::Display($this->friendlyName."::_Save():Add:".$row->getError(), true);
		   return false;
		}	  	
	  }
	  else
	  {
	   	if(!$row->SR_Update($parentCategoryID)){
	   	 	SRError::Display($this->friendlyName."::_Save():Update:".$row->getError(), true);
	   	 }
	  }	
	  
	  //attempt to link and save category titles
	  for($i=0; $i < count($_POST); $i++)
	  {
	  		if(array_key_exists("title$i", $_POST))
	  		{
	  		 
	  		 	$catTitle = new SRDB_Category_Title($this->_AddonManager->Database);
	  		 	$catTitle->categoryTitleID = $_POST["titleID_0_$i"];
	  		 	$catTitle->categoryID = $row->categoryID;
	  		 	$catTitle->titleName = $_POST["title$i"];
	  		 	$catTitle->titleOrder = $i;
				$catTitle->mandatory = $this->_AddonManager->Bridge->GetParameter($_POST ,"title$i".'Mandatory', 0, 'int');				
				$catTitle->titleType = $_POST["title$i".'Type'];
				
				//save to database
				if (!$catTitle->store(true)) {
					SRError::Display($this->friendlyName."::_Save():Store:".$catTitle->getError(), true);
					return false;
				}											 
	  		}			  
	  }	
	
	  if($apply)
	  {
	    	$this->_AddonManager->Bridge->Redirect( $this->GetURL("edit", "categoryID=$row->categoryID"), 'Saved'); 
	  }
	  else
	  {
	  		$this->_AddonManager->Bridge->Redirect( $this->GetURL("list"), 'Saved');
	  }
	}	

}
?>