<?php
/**
 *  $Id: Review_Module_Admin.php 121 2009-09-13 11:05:24Z rowan $
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
class Review_Module_Admin extends Module_Base_Admin
{   
  	var $_ReviewModule;
	function Review_Module_Admin(&$addonManager, &$adminModuleName, &$moduleName)
	{	 	
		$this->addonPath = dirname(__FILE__);
	  	$this->hasCSS=true;
	  	$this->hasLanguage=true;
		$this->hasConfig=true;	
		
		$this->dependsOnModules = array('Template_Module', 'Category_Module', 'Tag_Module');			
						
	  	parent::Module_Base_Admin($addonManager, $adminModuleName, $moduleName);
		
		$this->_ReviewModule =& $addonManager->GetModule('Review_Module', false);
				
		$this->friendlyName = $this->GetString($this->_ReviewModule, 'Reviews');
		$this->defaultTaskName = $this->GetString($this->_ReviewModule, 'AdminDescription');	 	
							
	} 
	
	function DisplayConfiguration(&$srConfig, &$allPlugins)
	{
		$langModule =&  $this->_AddonManager->GetModule('Language_Module', false);
		$control_name="params";
		echo "<table>"; 
					
		//max rating
		$settingName = $this->addonName."_MAX_RATING";
		$ratings = array();
		$ratings[] = 5;
		$ratings[] = 10;
		$ratings[] = 100;
		$ratings[] = 1000;	
		$control = SRHtmlControls::DropDownList($settingName, $ratings,  constant($settingName));
		$text = $this->GetString($this->_ReviewModule, 'ConfigMaxRating');
		$tip = $this->GetString($this->_ReviewModule, 'ConfigMaxRatingTip');
		$srConfig->DisplaySingleTabParam($text, $tip, $control);		
						
		$settingName = $this->addonName."_USE_STAR_RATING";	
		$control = SRHtmlControls::YesNoRadio("params[$settingName]",'class="inputbox"',constant($settingName) );	

	    $text = $this->GetString($this->_ReviewModule, 'ConfigStarRating');
	    $tip  = $this->GetString($this->_ReviewModule, 'ConfigStarRatingTip');		
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
		
		//review URL
		$settingName = $this->addonName."_URL";
	    $control = SRHtmlControls::Text($settingName, constant($settingName), $control_name ,100);
	    $text = $this->GetString($this->_ReviewModule, 'ConfigReviewUrl');
	    $tip  = $this->GetString($this->_ReviewModule, 'ConfigReviewUrlTip');
		$srConfig->DisplaySingleTabParam($text,$tip, $control);		 
		
		echo "</table>"; 	  
	}
	
	function Display($task)
	{
		switch($task)
		{
			case 'changeTemplate':
				$this->_ChangeReviewTemplate(false);
				break;
			
			case 'keepTemplate':
				$this->_ChangeReviewTemplate(true);
				break;  
			
			case 'new':
				$this->_New();
				break;
			
			case 'edit':
				$id = $this->_AddonManager->Bridge->GetParameter( $_REQUEST ,'reviewID', array(0) );
				if(is_array($id))
				{
					$id =$id[0]; 
				}
				$this->_Edit( intval($id));	
				break;
			
			case 'apply':
				$this->_Save(true);
				break;  
				
			case 'save':
				$this->_Save();
				break;
			
			case 'delete':
				$ids = $this->_AddonManager->Bridge->GetParameter( $_REQUEST, 'reviewID', array(0) );
				$this->_Delete($ids);
				break;
			
			case 'unpublish':
				$ids = $this->_AddonManager->Bridge->GetParameter( $_REQUEST, 'reviewID', array(0) );
				$this->_Publish($ids, 0 );
				break;
			
			case 'publish':
				$ids = $this->_AddonManager->Bridge->GetParameter( $_REQUEST, 'reviewID', array(0) );
				$this->_Publish($ids, 1 );
				break;
			
			case 'filter':
				$categoryID = null;
				$ddlCategoryFilter = $this->_AddonManager->Bridge->GetParameter($_POST ,'ddlCategoryFilter', 1, 'int' );
				if($ddlCategoryFilter > 1)
				{
					$categoryID = $ddlCategoryFilter;	
				}
				$this->_Filter($categoryID);
			break;
			
			
			case 'list':
			default:
				$this->_List();
				break;	  
		}
	}
	
  	function _AwardList( $selected=NULL )
  	{
		global $database;
		
		if($selected == '')
		{
		    $selected = NULL;
		}
		
		$awardModule =& $this->_AddonManager->GetModule('Award_Module', false);
		$awards =& $awardModule->GetAwards('name asc');		
		
		$awardOptions = array();
		$awardOptions[] = SRHtmlControls::Option( -1, 'None');
		if(count($awards) > 0)
		{			
		    foreach($awards as $award)
		    {
		        $awardOptions[] = SRHtmlControls::Option( $award->awardID, $award->name);
		    }
		}
		return SRHtmlControls::SelectList( $awardOptions, 'awardID', 'size="1" class="inputbox" ', 'value', 'text', $selected );
    }	
	
	function _New() 
	{
		global $option;
				
		parent::_New();	
								
		$categoryID = $this->_AddonManager->Bridge->GetParameter( $_REQUEST ,'categoryID', null, 'int');
		if(!$categoryID)
		{
		 	$this->_PrintCategorySelectStep();
			return; 
		}
		
		$categoryModule =& $this->_AddonManager->GetModule('Category_Module', false); 	
		
		$tagMod =& $this->_AddonManager->GetModule('Tag_Module', false);
		
		$firstCategory = $categoryModule->GetAllCategories(true);
		if(count($firstCategory) == 0)
		{
			SRError::Display($this->GetString($categoryModule, 'NoCategories'), true);
		}
		$firstCategory = $firstCategory[0];
		
		$row = new SRDB_Review($this->_AddonManager->Database);
		$row->load( null );
		
		$values['categoryID'] = $categoryID;
		$values['awardID'] = null;
		$values['score'] = null;

		$values['thumbnailURL'] = $this->_AddonManager->Bridge->SiteUrl.'components/com_simple_review/images/image_unavailable.gif';
		$values['imageURL'] = null;
		$values['blurb'] = null;
		$values['content'] = null;
		$values['published'] = null;		

		//need to get title names and values
		$values['titles'] = $categoryModule->GetCategoryTitles($categoryID);	

		for($i=0; $i < count($values['titles']); $i++)
		{
			$values['titles'][$i]->title = "";
			$values['titles'][$i]->reviewTitleID = null;
		}
		
			
		$values['content'] = $categoryModule->GetCategoryTemplate($categoryID);
		$values['pageName'] = null;
		
		echo '<table id="srAdminContainer"><tr><td>';
		echo "<form action='index2.php' method='post' name='adminForm' id='adminForm' class='adminForm'>";
		$this->_PrintFormTable($values);
		echo "<input type='hidden' name='reviewID' value='$row->reviewID' />";
		echo "<input type='hidden' name='option' value='$option' />";
		echo "<input type='hidden' name='task' value='' />";
		echo "<input type='hidden' name='module' value='$this->addonName' />";
		echo "</form>";
		
		echo "</td></tr></table>";		       	
        $tagMod->ListTags();  
		
	}
	
	function _List() 
	{
		$reviewTable = SRDB_Review::TableName();
		
		$query = "SELECT COUNT(*)"
		. "\n FROM $reviewTable"
		;
		$total = SRBridgeDatabase::ScalarQuery($query);
		
		$pageNav = new SRPager($total);
		                                       
		$this->_Listing($rows, $pageNav, null);
	}
	
	function _Listing(&$rows, &$pageNav, $categoryID=null)
	{
	  	$formName						= "adminForm";	
	  	$formAction						= "index2.php";
			
		$columnNames					= Array();
		
		$columnNames[]					= "Review ID";
		$columnNames[]					= "Title 1";
		$columnNames[]					= "Title 2";
		$columnNames[]					= "Title 3";		
		$columnNames[]					= "Published";
		$columnNames[]					= "Category";
		$columnNames[]					= "Score";
		$columnNames[]					= "Created By";
		
		$propertyNames					= Array();
		
		$propertyNames[]				= "reviewID";
		$propertyNames[]				= Array('title1');
		$propertyNames[]				= Array('title2');
		$propertyNames[]				= Array('title3');
		$propertyNames[]				= 'published';
		$propertyNames[]				= 'categoryName';
		$propertyNames[]				= 'score';
		$propertyNames[]				= 'createdBy';
		
		$this->_PrintReviewFilter($categoryID);		
		
		# Do the main database query		
		$reviewDB = new SRDB_Review($this->_AddonManager->Database);
		$rows = $reviewDB->GetReviewListLimited($categoryID,$pageNav->limitStart, $pageNav->limit);		
		if ($rows == null && count($rows) == 0)
		{
			echo "<h3>".$this->GetString($this->_ReviewModule, 'NoReviews')."</h3>";
		}	 		
		
	    SRControls::ItemListing($formName, $formAction, $columnNames, $propertyNames, $rows, 'list', $this, $pageNav);		
	}
		
    function _Edit($id)
    {
            global $option;			
            parent::_Edit($id);

                        
		    $row = new SRDB_Review($this->_AddonManager->Database);
	  		$row->load( $id );
	  					  			  
			$reviewModule =& $this->_AddonManager->GetModule('Review_Module', false);
			$categoryModule =& $this->_AddonManager->GetModule('Category_Module', false);
			
			
			$catTitles = $categoryModule->GetCategoryTitles($row->categoryID);
			$reviewTitles =& $reviewModule->SR_GetTitles($row->reviewID, false);

			for($i=0; $i < count($catTitles); $i++)
			{
				//set defaults incase a review title cant be found
				$catTitles[$i]->reviewTitleID = null;
				$catTitles[$i]->title = '';
				$catTitles[$i]->reviewID = null;
				for($j=0; $j < count($reviewTitles); $j++)
				{
					if($catTitles[$i]->categoryTitleID == $reviewTitles[$j]->categoryTitleID)
					{  
						$catTitles[$i]->reviewTitleID = $reviewTitles[$j]->reviewTitleID;
						$catTitles[$i]->title = $reviewTitles[$j]->title;
						$catTitles[$i]->reviewID = $reviewTitles[$j]->reviewID;
						break;
					}
				} 		  
			}
			$values['titles'] = $catTitles;
			
            $values['categoryID'] = $row->categoryID;
            $values['awardID'] = $row->awardID;
            $values['score'] = $row->score;
			$values['published'] = $row->published;
            
            $values['thumbnailURL'] = $row->thumbnailURL;
            $values['imageURL'] = $row->imageURL;
            $values['blurb'] = $row->blurb;
            $values['content'] = $row->content;
			$values['pageName'] = rawurldecode($row->pageName);
           
            echo '<table id="srAdminContainer"><tr><td>';
            echo "<form action='index2.php' method='post' name='adminForm' id='adminForm' class='adminForm'>";

            $this->_PrintFormTable($values);
            
			echo "<input type='hidden' name='createdByID' value='$row->createdByID' />";
			echo "<input type='hidden' name='createdDate' value='$row->createdDate' />";
            echo "<input type='hidden' name='reviewID' value='$row->reviewID' />";
            echo "<input type='hidden' name='userReview' value='$row->userReview' />";
            echo "<input type='hidden' name='option' value='$option' />";
            echo "<input type='hidden' name='module' value='$this->addonName' />";
            echo "<input type='hidden' name='task' value='' />";
            echo '</form>';
            
            echo '</td></tr></table>';
	        $tagMod =& $this->_AddonManager->GetModule('Tag_Module');
	        $tagMod->ListTags();
    }	
	
	function _Filter($categoryID=null)
	{
		$reviewTable = SRDB_Review::TableName();
		
		$query = "SELECT COUNT(*)"
		. "\n FROM $reviewTable";
		
		if($categoryID != null)
		{
			$query.= "\n WHERE categoryID = $categoryID";
		}
		
		$total = SRBridgeDatabase::ScalarQuery($query);
		
		$pageNav = new SRPager($total);
			                                        
		$this->_Listing($rows, $pageNav, $categoryID);		
	} 
	 
	
	function _LoadDependencies()
	{
      		global $option;
            $jsPath = $this->_AddonManager->Bridge->SiteUrl."administrator/components/$option/addons/modules/Tag_Module";
			Simple_Review_Common::IncludeJavaScript("$jsPath/Tag_Module.Admin.js"); 	  
	} 	
	
	function _PrintCategorySelectStep()
	{
		 	global $option;
	
			$categoryModule =& $this->_AddonManager->GetModule('Category_Module', false); 

			$categoryList = $categoryModule->CategoryDropDown('categoryID');
			if($categoryList == null)
			{
				echo $this->GetString($categoryModule, 'NoCategories');
				?>
				<form action='index2.php' method='post' name='adminForm' id='adminForm' class='adminForm'>
				<input type='hidden' name='option' value='<?php echo $option;?>' />
				<input type='hidden' name='task' value='new' />
				<input type='hidden' name='module' value='<?php echo $this->addonName;?>' />
				</form>
				<?php
			 	return null;
			}	 	
			
			$tip				 	= $this->GetString($this->_ReviewModule, 'FormCategoryTip');
			$catListTip				= Simple_Review_Common::CreateToolTip($this->GetString($this->_ReviewModule, 'FormCategory'), $tip);
			?>
			
			<table><tr><td>
				<form action='index2.php' method='post' name='adminForm' id='adminForm' class='adminForm'>
					<fieldset>
						<?php echo $this->GetString($this->_ReviewModule, 'FormCategorySelect');?><br/>
						<label for="categoryID"><?php echo $catListTip;?></label>
						<?php echo $categoryList;?><br/>
						
						<input type="submit" value="Next"/>
						<input type='hidden' name='option' value='<?php echo $option;?>' />
						<input type='hidden' name='task' value='new' />
						<input type='hidden' name='module' value='<?php echo $this->addonName;?>' />
					</fieldset>
				</form>
			</td></tr></table>
			
			
			<?php
	 
	}	
	
	function _PrintFormTable($values=NULL)
     {
       global $option;

       $categoryModule =& $this->_AddonManager->GetModule('Category_Module', false); 
       
       $categoryList = $categoryModule->CategoryDropDown('categoryID', false, $values['categoryID'],'size="1" class="inputbox" onchange="changeReviewTemplate()"');
       if($categoryList == null)
       {
	    	echo $this->GetString($categoryModule, 'NoCategories');
	     	return null;
		}
		$jsPath = $this->_AddonManager->Bridge->SiteUrl."administrator/components/$option/addons/modules/$this->addonName";
		Simple_Review_Common::IncludeJavaScript("$jsPath/$this->addonName.Admin.js");  			
					
	  	$tagModule = $this->_AddonManager->GetModule('Tag_Module', false);  	
	
	    $tip				 	= $this->GetString($this->_ReviewModule, 'FormCategoryTip');
	    $catListTip				= Simple_Review_Common::CreateToolTip($this->GetString($this->_ReviewModule, 'FormCategory'), $tip);
		
	    $tip 					= $this->GetString($this->_ReviewModule, 'FormAwardTip');
	    $awardListTip			= Simple_Review_Common::CreateToolTip($this->GetString($this->_ReviewModule, 'FormAward'), $tip);		
		$awardList				= $this->_AwardList($values['awardID']);
		
	    $tip 					= $this->GetString($this->_ReviewModule, 'FormRatingTip');
	    $ratingTip				= Simple_Review_Common::CreateToolTip($this->GetString($this->_ReviewModule, 'FormRating'), $tip);	
	        
	    $tip 					= $this->GetString($this->_ReviewModule, 'FormThumbnailUrlTip');
	    $thumbnailTip			= Simple_Review_Common::CreateToolTip($this->GetString($this->_ReviewModule, 'FormThumbnailUrl'), $tip);
		
	    $tip 					= $this->GetString($this->_ReviewModule, 'FormImageUrlTip');
	    $imageTip				= Simple_Review_Common::CreateToolTip($this->GetString($this->_ReviewModule, 'FormImageUrl'), $tip);	
		
	    $tip 					= $this->GetString($this->_ReviewModule, 'FormBlurbTip');
	    $blurbTip				= Simple_Review_Common::CreateToolTip($this->GetString($this->_ReviewModule, 'FormBlurb'), $tip);
		
	    $tip 					= $this->GetString($this->_ReviewModule, 'FormReviewTip');
	    $reviewTip				= Simple_Review_Common::CreateToolTip($this->GetString($this->_ReviewModule, 'FormReview') , $tip);	
		
		$tip 					= $this->GetString($this->_ReviewModule, 'FormPublishedTip');
		$publishTip				= Simple_Review_Common::CreateToolTip($this->GetString($this->_ReviewModule, 'FormPublished'), $tip);
		
		$tip 					= $this->GetString($this->_ReviewModule, 'FormPageNameTip');
		$pageNameTip			= Simple_Review_Common::CreateToolTip($this->GetString($this->_ReviewModule, 'FormPageName'), $tip);					
				    
		$tips = new stdClass();
		$tips->catListTip = $catListTip;
		$tips->awardListTip = $awardListTip;
		$tips->ratingTip = $ratingTip;
		$tips->thumbnailTip = $thumbnailTip;
		$tips->imageTip = $imageTip;
		$tips->blurbTip = $blurbTip;
		$tips->reviewTip = $reviewTip;
		$tips->publishTip = $publishTip;
		$tips->pageNameTip = $pageNameTip;
		
		$lists = new stdClass();
		$lists->category = $categoryList;
		$lists->award = $awardList;
		
		require_once("$this->addonPath/templates/Admin/ReviewForm.php");
		$template = new TemplateReviewForm(&$this->_ReviewModule, $tips, $lists);
		
		$template->Display($values);
		
		return true;
    }	
	
	function _PrintReviewFilter($categoryID = null)
	{
		global $option;
		
		if($categoryID == null)
		{
			$categoryID = 1;//default to none
		}
		
		$categoryModule =& $this->_AddonManager->GetModule('Category_Module', false); 
		$languageModule =& $this->_AddonManager->GetModule('Language_Module', false); 
		$category = $this->GetString($categoryModule, 'Category');
		$filter = $this->GetString($languageModule, 'Filter');
		$reviewFilter = $this->GetString($this->_ReviewModule, 'ReviewFilter');
		$ddlCategoryFilter = $categoryModule->CategoryDropDown('ddlCategoryFilter', true, $categoryID); 
		
		?>
		<form action="index2.php" method="post" name="reviewFilterForm"> 
		<div id='srReviewFilter'>
			<fieldset>
				<legend><?php echo $reviewFilter;?></legend>
				<div style='float:left'>
					<label for='ddlCategoryFilter'><?php echo $category;?></label>
					<?php echo $ddlCategoryFilter;?>
				</div>
				<div style='float:left'>
					<input type='submit' value="<?php echo $filter;?>"/>
				</div>
			</fieldset>
			<div style='clear:both'></div>
		</div>
		<input type="hidden" name="option" value="<?php echo $option;?>" />
		<input type="hidden" name="task" value="filter" />
		<input type="hidden" name="boxchecked" value="0" />
		<input type="hidden" name="hidemainmenu" value="0">
		<input type="hidden" name="module" value="<?php echo $this->addonName;?>" />		
		</form>
		<?php
	}	
    
	
	function _ChangeReviewTemplate($keepTemplate=false) 
	{

		$row = new SRDB_Review($this->_AddonManager->Database);
		if (!$row->bind( $_POST ))
		{
			SRError::Display($row->getError(), true);
		}
		
		//changing category for new review
		$isNewReview = !$row->reviewID;
		if($isNewReview)
		{
			$this->_PrepareNewReviewForTemplateChange($row);
		}
		else
		{
			$this->_SaveReviewTitles($row);	
		}
		$this->_ChangeReviewCategory($row, $keepTemplate);										    
	    $this->_Edit($row->reviewID);
		
	}	
	
	/**
	 * If we are changing the template for a new review then we need to get it in the correct state
	 * @return 
	 * @param object $row
	 */
	function _PrepareNewReviewForTemplateChange(&$row)
	{
		if($row->reviewID)
		{
			return;
		}
		if (!$row->store()) {
			SRError::Display($row->getError(), true);
			exit();
		}	
		$this->_SaveReviewTitles($row);		
		
		$am =& Addon_Manager::Get();
		$categoryModule = $am->GetModule('Category_Module', false);	
		$reviewModule = $am->GetModule('Review_Module', false);	
		
		$catTitles = $categoryModule->GetCategoryTitles($row->categoryID);
		$reviewTitles = $reviewModule->SR_GetTitles($row->reviewID, false);
		
		//if changing to a category with more titles then will be missing the +ve difference
		for($i = count($reviewTitles)+1; $i <= count($catTitles); $i++)
		{
	  		 	$revTitle = new SRDB_Review_Title($this->_AddonManager->Database);
	  		 	$revTitle->reviewTitleID = null;
	  		 	$revTitle->categoryTitleID = $catTitles[$i]->categoryTitleID;
	  		 	$revTitle->reviewID = $row->reviewID;
	  		 	$revTitle->title = '';
	  		 	$revTitle->titleOrder = $i;	
	  		 	if (!$revTitle->store(true)) {
					SRError::Display("$this->friendlyName :: _ChangeReviewCategory():Store", true);					
				}			
		}
	}
	
	/**
	 * Changes a reviews template.  $review must not be null.
	 *	 
	 * @param bool $keepTemplate
	 */
	function _ChangeReviewCategory(&$review, $keepTemplate)
	{			
		$am =& Addon_Manager::Get();
		$categoryModule = $am->GetModule('Category_Module', false);
		$templateModule = $am->GetModule('Template_Module', false);
		$reviewModule = $am->GetModule('Review_Module', false);
			
		$templateTable = SRDB_Template::TableName();	
		$categoryTable = SRDB_Category::TableName();
		
		if($review == null)
		{
			SRError::Display('Review has not been set from bind.', true);			
		}
		
		$query = 'SELECT templateID'
		. "\n FROM $categoryTable"
		. "\n where categoryID = {$review->categoryID}";		
		$templateID = SRBridgeDatabase::ScalarQuery($query);			

		$catTitles = $categoryModule->GetCategoryTitles($review->categoryID);
		$catTitleCount = $catTitles ? count($catTitles) : 0;	
		if($catTitleCount == 0)
		{
			SRError::Display('Category has no titles.', true);
		}
		
		$reviewTitles = $reviewModule->SR_GetTitles($review->reviewID, false);
		$reviewTitleCount = $reviewTitles ? count($reviewTitles) : 0;
		if($reviewTitleCount == 0)
		{
			SRError::Display('Review has no titles.', true);
		}
				
		for	($i = 0; $i < $catTitleCount; $i++)
		{
			if ($i < $reviewTitleCount)
			{
				SRDB_Review_Title::SR_ChangeCategoryTitleId($reviewTitles[$i]->reviewTitleID, $catTitles[$i]->categoryTitleID);
			}
			//new category has more titles so blank them
			else
			{
	  		 	$revTitle = new SRDB_Review_Title($this->_AddonManager->Database);
	  		 	$revTitle->reviewTitleID = null;
	  		 	$revTitle->categoryTitleID = $catTitles[$i]->categoryTitleID;
	  		 	$revTitle->reviewID = $review->reviewID;
	  		 	$revTitle->title = '';
	  		 	$revTitle->titleOrder = $i;	
	  		 	if (!$revTitle->store(true)) {
					SRError::Display("$this->friendlyName :: _ChangeReviewCategory():Store", true);					
				}	
			}
		}		
		
		//need to remove rt's which overflow ct's
		if ($reviewTitleCount > $catTitleCount)
		{
			$reviewTitlesToRemove = array();
			for	($i = $catTitleCount; $i < $reviewTitleCount; $i++)
			{
				$reviewTitlesToRemove[] = $reviewTitles[$i]->reviewTitleID;
			}
			SRDB_Review_Title::SR_RemoveTitles($reviewTitlesToRemove);
					
		}		
		
		if(!$keepTemplate)
		{
			if($templateID == -1)
			{
				$review->content = '';
			}
			else
			{
				$query = 'SELECT template'
				. "\n FROM $templateTable"
				. "\n where templateID = $templateID";
				$review->content = SRBridgeDatabase::ScalarQuery($query);
			}
		}
		if (!$review->store()) {
			SRError::Display('Unable to save review.', true);
		}
	}	
	
	
	
	
	function _SaveReviewTitles(&$row)
	{	
	
		$categoryModule =& $this->_AddonManager->GetModule('Category_Module', false); 
		$catTitles =& $categoryModule->GetCategoryTitles($row->categoryID);		   		
		
		$catTitlesByID = array();
		foreach($catTitles as $catTitle)
		{
			$catTitlesByID["$catTitle->categoryTitleID"] = $catTitle;
		}
		
		//attempt to link and save category titles
		for($i=0; $i < count($_POST); $i++)
		{
				if(!array_key_exists("titleID_1_$i", $_POST) || !array_key_exists("title$i", $_POST))
				{
					continue;
				}
			
				$catTitleID = $_POST["titleID_1_$i"];				
				if(array_key_exists("$catTitleID", $catTitlesByID))
				{
					$catTitleID = $_POST["titleID_1_$i"];
				 	$catTitle = $catTitlesByID["$catTitleID"];
	
					$reviewTitleClass = "SRDB_Review_Title_$catTitle->titleType";

					if(!class_exists($reviewTitleClass))
					{
						$reviewTitleClass = 'SRDB_Review_Title_Text';
					}
				 	
				 	$revTitle = new $reviewTitleClass($this->_AddonManager->Database);
				 	$revTitle->reviewTitleID = $_POST["titleID_0_$i"];
				 	$revTitle->categoryTitleID = $_POST["titleID_1_$i"];
				 	$revTitle->reviewID = $row->reviewID;
				 	$revTitle->title = $_POST["title$i"];
				 	$revTitle->titleOrder = $i;
					
					//save to database
					if (!$revTitle->store(true)) {
						SRError::Display($this->friendlyName.'::_Save():Store:'.$catTitle->getError(), true);
						return false;
					}											 
				}			  
		}			
	}
	
	function _Save($apply=false) 
	{
		$row = new SRDB_Review($this->_AddonManager->Database);
		
		if (!$row->bind( $_POST )) 
		{
			SRError::Display($row->getError(), true);
		}
		
		if (!$row->store()) 
		{
			SRError::Display($row->getError(), true);
		}
		
		$this->_SaveReviewTitles($row);

		if($apply)
		{
		 	$this->_AddonManager->Bridge->Redirect( $this->GetURL("edit", "reviewID=$row->reviewID"), 'Saved'); 
		}
		else
		{
			$this->_AddonManager->Bridge->Redirect( $this->GetURL('list'), 'Saved');  
		}
	}
	
	function _Publish(&$ids, $published ) {	  
	  if (!is_array( $ids ) || count( $ids ) < 1) {
	    echo "<script> alert('Select an item to publish'); window.history.go(-1);</script>n";
	    exit;
	  } 
	  if (count( $ids )) {
	    $ids = implode( ',', $ids );
	    $reviewDB = new SRDB_Review($this->_AddonManager->Database);
	    $reviewDB->SR_Publish($ids, $published);
	  }
	  $this->_AddonManager->Bridge->Redirect( $this->GetURL("list"), $published == 0 ? 'Unpublished' : 'Published' );
	}
	
	
	function _Delete(&$ids) 
	{
	  if (!is_array( $ids ) || count( $ids ) < 1) {
	    echo "<script> alert('Select an item to delete'); window.history.go(-1);</script>";
	    exit;
	  }
	  if (count( $ids ))
	  {	   
		$reviewDB = new SRDB_Review($this->_AddonManager->Database); 
	   	foreach($ids as $id)
	   	{
	   		$reviewDB->SR_Delete($id);
	   	}	   		    
	  }

	  $this->_AddonManager->Bridge->Redirect( $this->GetURL("list"), 'Deleted');
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

	  $settingName = $this->addonName."_USE_STAR_RATING";
	  $config .="define('$settingName', '".$params[$settingName]."');\n";  
	  
	  $settingName = $this->addonName."_MAX_RATING";
	  $config .="define('$settingName', '".$params[$settingName]."');\n";  	  	
	  	  	  
	         	
	  $settingName = $this->addonName."_HEAD";	
	  $head = array_key_exists($settingName, $params)  ? implode('||',$params[$settingName]) : '';	     	 
	  $config .= "define('".$settingName."','".$head."');\n";
	  	  
	  $settingName = $this->addonName."_FOOT";	
	  $foot = array_key_exists($settingName, $params)  ?  implode('||',$params[$settingName]) : '';
	  $config .= "define('".$settingName."','".$foot."');\n";
	  	  
	  $settingName = $this->addonName."_URL";	
	  //don't allow review url tag
	  $params[$settingName] = preg_replace("/\{sr_reviewURL\}/", "", $params[$settingName]);
	  $config .="define('$settingName', '".$params[$settingName]."');\n";    

	  $settingName = $this->addonName."_RATING_DECIMAL_PLACES";
	  $config .="define('$settingName', '1');\n"; 

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