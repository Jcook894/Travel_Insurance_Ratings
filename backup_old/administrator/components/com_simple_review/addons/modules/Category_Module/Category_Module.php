<?php
/**
 *  $Id: Category_Module.php 121 2009-09-13 11:05:24Z rowan $
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
class Category_Module extends Module_Base
{
	var $categoryID = null;
    var $lft = null;
    var $rgt = null;
    var $name = null;
	var $displayedChildCategories = null;
    var $displayedCategoriesReviews = null;
    var $isRootLevel = false;
	var $reviewModule = null;
	var $languageModule = null;
	//TODO:replace
    //var $mainTemplate = "CategoryMain";	
    
	var $allowedURLTags = array(
								"/\{sr_itemID\}/", //0
								"/\{sr_categoryID\}/", //1
								"/\{sr_catName\}/", //2
								"/\{sr_pageName\}/" //3	
								);     
    
    var $pageNav;
	var $reviewStartCharFilter = null;
	
	function Category_Module(&$addonManager, &$moduleName, $initialise)
	{
	  	$this->dependsOnModules = array('Review_Module', 'Tag_Module');  	
	  	//$this->addonName = $moduleName;
		$this->addonPath = dirname(__FILE__);
		
	  	$this->hasCSS=true;
	  	$this->hasLanguage=true;
	  	$this->hasConfig=true;
		
		$this->_AddonManager =& $addonManager;
		
		$this->categoryID = $this->GetCategoryIdFromRequest(); 
		
		$this->reviewStartCharFilter = $this->_AddonManager->Bridge->GetParameter( $_POST ,'selectedFilter', null );
		if( !ereg('^Other|[a-zA-Z]$', $this->reviewStartCharFilter) )
		{
			$this->reviewStartCharFilter = null;	
		}

		parent::Module_Base($addonManager, $moduleName, $initialise);
		
	  	$this->friendlyName = $this->GetString($this, 'CategoryList');  	
	  	$this->Initialised = $initialise;
		
		$this->languageModule =& $this->_AddonManager->GetModule('Language_Module', false);
		
		//TODO: enable more options for url
		/*
		$this->_AddonManager->GetModule('Tag_Module', false);
		for($i = 0; $i < Tag_Module_Number_Of_Dynamic_Fields; $i++)
		{
			$allowedURLTags[] = "/\{sr_catTitle$i}\}/";			
		}
		*/
	}
	
	function _LoadSelectedCategory()
	{
			$cat = SRDB_Category::SR_GetPublishedCategory($this->categoryID);
			if($cat == null)
			{
			 	SRError::Display("Unable to find category ($this->categoryID).", true); 
				return;
			}

		 	$this->lft = $cat->lft;
		 	$this->rgt = $cat->rgt;
		 	$this->name = $cat->name;
			
			$this->_AddonManager->Bridge->SetPageTitle($this->name); 
	}
	
    function _LoadAddonData()
    {
      	global $Itemid, $mosConfig_list_limit;
      	$total = 0;      	
		$categoryTable = SRDB_Category::TableName();

		$this->reviewModule =& $this->_AddonManager->GetModule('Review_Module', false);
      	$catDB = new SRDB_Category( $this->_AddonManager->Database ); 
      	
		/*
      	$configFile = "$this->addonPath/config.php";
	    if(file_exists($configFile))
	    {
      		require_once($configFile);  
		}
     	*/
		
		$charFilter = null;
		if($this->reviewStartCharFilter != null)
		{
			if( $this->reviewStartCharFilter == 'Other')
			{
				$charFilter = ' UPPER(LEFT(rt1.title, 1)) NOT BETWEEN  \'A\' and \'Z\' ';
			}
			else
			{
				$charFilter = " UPPER(LEFT(rt1.title, 1)) = '$this->reviewStartCharFilter' ";
			}
		}
		   				
		
      	//setup pathway
		$this->DisplayCategoryPathway(false);
		if($this->categoryID == -1)
		{
			$root = $catDB->SR_GetRoot();
			//only 1 top level cat so skip straight to it
			if($root == null)
			{
			 	SRError::Display("Unable to find root category.", true); 
				return;
			}
		 	$this->isRootLevel  = true;
		 	$this->categoryID = $root->categoryID;
		 	$this->lft = $root->lft;
		 	$this->rgt = $root->rgt;			
						
			$this->_LoadChildCategories($catDB, $this->lft, $this->rgt);			
		}	
		
		$byPass = $this->isRootLevel && Category_Module_SINGLE_CAT_ROOT_BYPASS && $this->displayedChildCategories != null && count($this->displayedChildCategories) == 1;
		if(!$this->isRootLevel || $byPass)
		{		
			if($byPass)
			{
				$cat =& $this->displayedChildCategories[0]->category;
			 	$this->isRootLevel  = false;
			 	$this->categoryID = $cat->categoryID;
			 	$this->lft = $cat->lft;
			 	$this->rgt = $cat->rgt;	
			}
			
			$this->_LoadSelectedCategory();	
			$total = $this->reviewModule->SR_GetFrontEndReviewCount($this->lft, $this->rgt, $charFilter);			 
			$this->_LoadChildCategories($catDB, $this->lft, $this->rgt);
		}
								 		 		       
		//if root or if no reviews	
		if($total == 0)
		{			
			$this->displayedCategoriesReviews = array();			 		        
		}
		else
		{			
			$this->_AddonManager->Bridge->IncludeBridgeControls();	
			$this->pageNav = new SRPager($total, Category_Module_NO_OF_REVIEW_ITEMS);
			
			$limit = null;
			$limitStart = null;
			if($this->pageNav->limit > 0)			
			{
				$limit = $this->pageNav->limit;
				$limitStart = $this->pageNav->limitStart;				
			}
			$reviews = 	$this->reviewModule->SR_GetReviewsForCategory($this->lft, $this->rgt, $limitStart, $limit, Category_Module_REVIEW_SORT_FIELD, Category_Module_REVIEW_SORT_ORDER, $charFilter);	        		 							
			$this->displayedCategoriesReviews = array();
			$isAlt = false;
			
			foreach($reviews as $r)
			{				
				$this->displayedCategoriesReviews[] =& 	$this->_GetDisplayableReview($r, $isAlt);
				$isAlt = !$isAlt;
			}
		}

    }
	
	function _LoadChildCategories(&$catDB, $parentLeft, $parentRight)
	{
		$tempChildCategories = $catDB->SR_FrontEndFullTree($parentLeft, $parentRight);
		$childCatCount = count($tempChildCategories);	
		
		$this->displayedChildCategories = array();	

		if ($childCatCount == 1)
		{
			$this->displayedChildCategories[] =& $this->_GetDisplayableCategory($tempChildCategories[0]);				
		}
		else if ($childCatCount > 1)
		{			
			$prevCat = $tempChildCategories[0];
			$this->displayedChildCategories[] =& $this->_GetDisplayableCategory($prevCat);
			
			for($i = 1; $i < $childCatCount; $i++)
			{
			 	if(	$tempChildCategories[$i]->lft > $prevCat->lft && 
					$tempChildCategories[$i]->rgt < $prevCat->rgt)
			 	{
			 	 	continue;
			 	}
			 	$this->displayedChildCategories[] =& $this->_GetDisplayableCategory($tempChildCategories[$i]);
				
				$prevCat = $tempChildCategories[$i];								
			}	
			
			usort($this->displayedChildCategories, array("Category_Module","_SortCategories"));						 
		}		
	}
	
	function &_GetDisplayableCategory($category)
	{
			$showReviewCount = constant($this->addonName."_SHOW_REVIEW_COUNT");
			$reviewCount = "";   
	        if($showReviewCount)
	        {
	          $reviewCount = $category->reviewCount;
			}
				
			$c = new stdClass();
			$c->imageUrl = $category->categoryImageURL;
			$c->linkUrl = $this->GetURL($category);	
			$c->catName = Simple_Review_Common::RemoveSlashes($category->name);
			$c->catCount = 	$reviewCount;
			$c->catDesc = 	Simple_Review_Common::RemoveSlashes($category->description);	
			$c->category = $category;	
			return $c;
	}
	
	function &_GetDisplayableReview($review, $isAlt=false)
	{
		$r = new stdClass();       	
		$score = "";
    	if (Category_Module_SHOW_RATING)
		{
			$css = $isAlt ? 'smallStarRatingAlt' : 'smallStarRating';
     		$score = (Review_Module_USE_STAR_RATING == 1) ? $this->reviewModule->SR_GetStarRating($review->score, $css) : $review->score;
		}	

		$link = $this->reviewModule->GetURL($review);						 					
     	$title1 = Category_Module_SHOW_TITLE1 ? $this->_CreateTitleDisplay($review->title1, $link, false) : "";
     	$title2 = Category_Module_SHOW_TITLE2 ? $this->_CreateTitleDisplay($review->title2, Category_Module_TITLE2_IS_REVIEW_LINK ? $link : null, Category_Module_TITLE2_LINK) : "";
     	$title3 = Category_Module_SHOW_TITLE3 ? $this->_CreateTitleDisplay($review->title3, Category_Module_TITLE3_IS_REVIEW_LINK ? $link : null, Category_Module_TITLE3_LINK) : "";
     		       			 
	 	$r->title1 = Simple_Review_Common::RemoveSlashes($title1);
		$r->title2 = Simple_Review_Common::RemoveSlashes($title2);
		$r->title3 = Simple_Review_Common::RemoveSlashes($title3);
	 	$r->rating = $score;
		$r->reviewer = Category_Module_SHOW_REVIEWER ? $review->createdBy : "";
		$r->date = Category_Module_SHOW_DATE ? $review->createdDate : "";	
		$r->review = $review;
		
		return $r;	
	}
	
	function _SortCategories($cat1, $cat2)
	{
	    if ($cat1->category->catOrder == $cat2->category->catOrder) {
	        return 0;
	    }
	    return ($cat1->category->catOrder < $cat2->category->catOrder) ? -1 : 1;				
	}

	function Display()
	{
		//TODO: replace
		//$this->LoadTemplate($this->mainTemplate);  	
		
		//$this->_DisplayHeader();
		//$this->_DisplayMain();
		//$this->_DisplayFooter();
		
		$startDetails = new stdClass();		
		if(!$this->isRootLevel)
		{
			$titleRow = $this->GetCategoryTitles($this->categoryID);
			$title1name = Category_Module_SHOW_TITLE1 ? $titleRow[0]->titleName : "";
			$title2name = Category_Module_SHOW_TITLE2 && count($titleRow) > 1 ? $titleRow[1]->titleName : "";
			$title3name  = Category_Module_SHOW_TITLE3 && count($titleRow) > 2 ? $titleRow[2]->titleName : "";
	
			$startDetails->listingTitle = '';
			$startDetails->title1Name = Simple_Review_Common::RemoveSlashes($title1name);
			$startDetails->title2Name = Simple_Review_Common::RemoveSlashes($title2name);
			$startDetails->title3Name = Simple_Review_Common::RemoveSlashes($title3name);  
			$startDetails->rating = Category_Module_SHOW_RATING ? $this->GetString($this->languageModule, 'Rating') : "";
			$startDetails->reviewer =  Category_Module_SHOW_REVIEWER ? $this->GetString($this->languageModule, 'Reviewer') : "";
			$startDetails->date = Category_Module_SHOW_DATE ? $this->GetString($this->languageModule, 'Date') : "";
			
				
		}
		else
		{
			$startDetails->listingTitle = "";
			$startDetails->title1Name = "";
			$startDetails->title2Name = "";
			$startDetails->title3Name = "";  
			$startDetails->rating = "";
			$startDetails->reviewer = "";
			$startDetails->date = "";
		}
		
		$navDetails = new stdClass();
		if($this->pageNav!=null) 
		{
			//TODO:since root level don't include category id
				$link = $this->GetURL($this, null, null, false);
				$navDetails->pager = $this->pageNav->GetPagesLinks($link);	
				$navDetails->text = $this->GetString($this, 'DisplaySelect');	
				$navDetails->select = 	$this->pageNav->GetLimitBox($link);		
				$navDetails->overview = $this->pageNav->GetOverview();							
		}
		else
		{
				$navDetails->pager = '';	
				$navDetails->text = '';	
				$navDetails->select = 	'';		
				$navDetails->overview = '';	
		}		
		
		require_once("$this->addonPath/templates/Default/Default.php");	
		$template = new TemplateCategoryDefault($this, $startDetails, $this->displayedChildCategories, $this->displayedCategoriesReviews, $navDetails);
		$template->Display();
	}

	/**
	 * Displays the categories, including a list of reviews if applicable
	 * @return 
	 */    
    function displayCategories() {
        global $Itemid;
        $title1name = $title2name = $title3name = "";
        $categoryTable = SRDB_Category::TableName();
        echo "<!-- display category start-->";
		$titles = null;		  
		if (!$this->isRootLevel)
		  {
	        $query ="SELECT title1name, title2name, title3name, name ".
	                "FROM $categoryTable where categoryID=$this->categoryID and published=1 order by catOrder";
	        $parentRows = SRBridgeDatabase::Query($query);
	        
	        if($parentRows!=null)
	        {
		        $this->_AddonManager->Bridge->SetPageTitle($parentRows[0]->name);
						    
			    $this->displaySubCategories($this->categoryID);
		        if($parentRows)
		        {
		            $titles = array($parentRows[0]->title1name, $parentRows[0]->title2name, $parentRows[0]->title3name);
		        }
	        	$this->_DisplayReviews($this->categoryID, $titles);	
			}	    
			else
			{
			  echo $this->GetString($this, 'Unpublished');
			}
		  }

      
        echo "<!-- display category end-->";
        
    }  
 
    function _CreateTitleDisplay($title, $link = null, $isExternalLink=false)
	{  
		if($isExternalLink)
		{
			return "<a href='$title'>$title</a>\n";
		}
		
		if($link != null)
		{
			return "<a href='$link'>$title</a>\n";	
		}
		
		return $title;
	} 
    
    function DisplayCategoryPathway($linkLastCategory)
    {
    	//TODO: this
    	/*
       global $mainframe;
        
        $catDB = new SRDB_Category( $this->_AddonManager->Database );

        $breadCrumbs = $catDB->SR_Path($this->categoryID);
        if($breadCrumbs != null && count($breadCrumbs) > 0)
        {
         	foreach($breadCrumbs as $crumb)
         	{
         	 	$link = $this->GetURL($crumb);//Simple_Review_Common::GetCategoryURL($crumb);
                $mainframe->appendPathWay("<a href='$link'>$crumb->name</a>");
         	}         
    	}
*/
    }              

    function _DisplayCategoryListings()
    {
		$rows = $this->childCategories;
		$catCount = count($rows);
		if($rows == null || $catCount == 0)
			return;

		$listingHeading = "";
		if(!$this->isRootLevel)
		{
			$listingHeading =  '<strong>'.$this->GetString($this, 'SubCategories').'</strong>';	
		}
		
		$categories = "";
		$catTypeClass = $this->isRootLevel ? 'catcontainer' : 'subcat';                                
				
		$template =& $this->templates[$this->mainTemplate];					
		
		
		$limitStart = 0;		
		$limit = $catCount;
			
		if($this->isRootLevel)
		{		
			$this->_AddonManager->Bridge->IncludeBridgeControls();
			$this->pageNav = new SRPager($catCount, Category_Module_NO_OF_ITEMS);
			
			if($this->pageNav->limit > 0)			
			{
				$limitStart = $this->pageNav->limitStart;				
				$limit = min($this->pageNav->limit + $limitStart, $catCount);
			}	
		}	

        //foreach($rows as $row)
		for($i = $limitStart; $i < $limit; $i++)
        {
        	$row = $rows[$i];
          	$link = $this->GetURL($row);
          	$img = "";
           	if($row->categoryImageURL)
          	{
          		$img = "<img src='$row->categoryImageURL'/>";
          	}
			
			$showReviewCount = constant($this->addonName."_SHOW_REVIEW_COUNT");
			$reviewCount = "";   
	        if($showReviewCount)
	        {
	          $reviewCount = "($row->reviewCount)";
			}
				
			$c = new stdClass();
			$c->catTypeClassName = $catTypeClass;
			$c->listHeading = $listingHeading;
			$c->image = $img;
			$c->linkUrl = $link;	
			$c->catName = Simple_Review_Common::RemoveSlashes($row->name);
			$c->catCount = 	$reviewCount;
			$c->catDesc = 	Simple_Review_Common::RemoveSlashes($row->description);
								
			$template->AddCategoryListing($c);
					
			$listingHeading = '';					
        }
        
	}
	
 	function CategoryDropDown( 	$sControlName, $bShowNoneEntry=false, $iSelectedIDs=NULL, 
	 							$sAttributes = 'size="1" class="inputbox"', $catIDToExclude=null)
    {
			$catDB = new SRDB_Category( $this->_AddonManager->Database ); 
			
            if($iSelectedIDs == '')
            {
                $iSelected = NULL;
            }
	        
            $rows = $catDB->SR_FullTree();
                                    
            $categories = array();
            foreach($rows as $row)
            {
             	if($row->lft == 1)
             	{
		            if($bShowNoneEntry)
		            {
		                $categories[] = SRHtmlControls::Option( $row->categoryID, 'None');
		            }
		            continue;
				}      
				$path = str_repeat("--", $row->depth - 1);
				$row->name = Simple_Review_Common::RemoveSlashes($row->name);
				
				if($catIDToExclude == null || $row->categoryID != $catIDToExclude)
				{
                	$categories[] = SRHtmlControls::Option( $row->categoryID, "$path $row->name");
				}
            } 
			
			if(count($categories) == 0)
			{
				return null;
			}
			                      
            return SRHtmlControls::SelectList( $categories, $sControlName, $sAttributes, 'value', 'text', $iSelectedIDs );
    }
		
	function GetAllCategories($excludeRoot = true)
	{
			$catDB = new SRDB_Category( $this->_AddonManager->Database); 
			$rows = $catDB->SR_FullTree();
			$categories = array();
			foreach($rows as $row)
            {
             	if($row->lft == 1 && $excludeRoot)
             	{
		            continue;
				}      				
                $categories[] = $row;
            }
            return $categories;
					 
	}
	
	function GetCategoryTitles($categoryID)
	{
			$catTitles =& new SRDB_Category_Title( $this->_AddonManager->Database );
     		return $catTitles->SR_LoadCategoryTitles($categoryID);	 
	}	
	
	function GetCategoryTemplate($categoryID)
	{
			$catDB =& new SRDB_Category($this->_AddonManager->Database);
			return Simple_Review_Common::RemoveSlashes( $catDB->SR_GetCategoryTemplate($categoryID));
			
	}	

	//TODO: merge with Addon_Base_Frontend::GetURL i.e. parameter order
	function GetURL($row, $parameters="", $itemID=null, $useSef=true)
	{
	    global $Itemid;
	  	
	  	$link = Category_Module_URL;
	  		
		$realItemID = $Itemid;
		if($itemID != null && $itemID != $realItemID)
		{
		    $realItemID =  $itemID;
		}		
		
	  	if ($parameters != "")
	  	{
	  	  //include item id in parameters
	  		$link .= $parameters;
	  	}					
					  		  		
		if($row != null)
		{
			$catID = ''; 
			$catName = '';
			$pageName = '';
			
			if(isset($row->isRootLevel) && $row->isRootLevel /* &&  === true*/)
			{
				$link = preg_replace(array('/&category=[^&]*/', '/&catID=[^&]*/'), array('',''), $link);
			}
			else
			{
				$catName = $row->name;
				preg_match_all("/([\w\-]+)/u", $catName, $matches);//remove non ascii letter characters
				$catName = implode('-', $matches[1]);
				$catName = preg_replace('/[\-]{2,}/', '-', $catName);//remove more than one -- in a row
				$catName = preg_replace('/[\-]$/', '', $catName);//if - is at the end then remove it
				
				$catID = $row->categoryID;	
				
				
				$pageName = isset($row->pageName) && $row->pageName ? $row->pageName : $catName;	
										
			}									
		
			$replace = array($realItemID, $catID, $catName, $pageName);
			$link = preg_replace($this->allowedURLTags, $replace, $link);  
		}					
			  	
		return $useSef ? $this->_AddonManager->Bridge->RewriteUrl($link) : $link; 
	}	
	
	/**
	 * Static
	 * @return 
	 */
	function GetCategoryIdFromRequest()
	{		
		$bridge =& SRBridgeManager::Get();	
		$categoryParam = $bridge->GetParameter($_REQUEST ,'category', -1, 'int');
		if($categoryParam != -1)
		{
		  $categoryId = explode("-", $categoryParam);
		  return intval($categoryId[0]);
		}	
		else
		{
			return $bridge->GetParameter($_REQUEST ,'catID', -1, 'int');
		}	
	}
}
?>


       
