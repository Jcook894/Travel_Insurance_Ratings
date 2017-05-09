<?php
/**
 *  $Id: Review_Module.php 117 2009-08-10 13:44:14Z rowan $
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
require_once(dirname(__FILE__ )."/Review_Title_Helper.php");
class Review_Module extends Module_Base
{
	var $_review = null;
	var $_CachedStarRatings = array();
	//var $reviewTitles = null; //dont use, use $this->_review->reviewTitles
	var $allowedURLTags = array(
								"/\{sr_itemID\}/", //0
								"/\{sr_reviewID\}/", //1
								"/\{sr_titles\}/", //2
								//"/\{sr_catName\}/" //3
								"/\{sr_title1\}/", //3
								"/\{sr_title2\}/", //4
								"/\{sr_title3\}/", //5
								"/\{sr_pageName\}/" //6								
								);
															
	function Review_Module(&$addonManager,&$moduleName, $initialise)
	{ 
	  	//$this->addonName = $moduleName;
		$this->addonPath = dirname(__FILE__);
		
	  	$this->hasCSS=true;
	  	$this->hasLanguage=true;
	  	$this->hasConfig=true;	
			    				
	  	parent::Module_Base($addonManager, $moduleName, $initialise);
		
		$this->friendlyName = $this->GetString($this, 'ReviewList');		
		$this->Initialised = $initialise;
		
		//TODO: enable more options for url
		/*
		$this->_AddonManager->GetModule('Tag_Module', false);
		for($i = 0; $i < Tag_Module_Number_Of_Dynamic_Fields; $i++)
		{
			$allowedURLTags[] = "/\{sr_catTitle$i}\}/";		
			$allowedURLTags[] = "/\{sr_title$i}\}/";			
		}
		*/			  	    	   		
	}
	
	function &Prop_Review()
	{
			return $this->_review;
	}
	
	function GetReviewIdFromRequest()
	{
		$task = null;		
		$reviewParam = $this->_AddonManager->Bridge->GetParameter($_REQUEST ,'review', -1);
		if($reviewParam != -1)
		{
			$reviewId = explode("-", $reviewParam);
			$reviewId = intval($reviewId[0]);
		}
		else
		{
			$reviewId = $this->_AddonManager->Bridge->GetParameter($_REQUEST ,'reviewID', -1, 'int');
		}	
		return $reviewId;		
	}
	
    function _LoadAddonData()
    {
		$reviewID= $this->GetReviewIdFromRequest();

		if(intval($reviewID) < 1)
		{
		 	SRError::Display("could not find review with ID '$reviewID'", true);
		}
		
		$this->_review = $this->SR_GetReview($reviewID );
		SRDB_Review::EscapeTextFields($this->_review);
		if($this->_review == null)
		{
			SRError::Display("could not find review with ID '$reviewID'", true);		
		}

		$this->_review->title1 = null;
		$this->_review->title2 = null;
		$this->_review->title3 = null;
		
		$pageTitle = "";
		for($i = 0; $i < count($this->_review->reviewTitles); $i++)
		{
			$title = $this->_review->reviewTitles[$i]->title;
			$pageTitle .=  	Simple_Review_Common::HTML2Text($title) . ' ';
			
			if($i == 0)
			{
				$this->_review->title1 = $title;
			}
			elseif($i == 1)
			{
				$this->_review->title2 = $title;
			}
			elseif($i == 2)
			{
				$this->_review->title3 = $title;
			}		
		}
		$this->_AddonManager->Bridge->SetPageTitle($pageTitle);  
		
        		 			        
    }

	function Display()
	{
	  	echo "<!-- display review start-->";
	  	echo "<div id='reviewcontainer'>";
		$this->_DisplayHeader();
		$this->_DisplayMain();
		$this->_DisplayFooter();
		echo "</div>";
		echo "<!-- display review end-->";
	}

	function _DisplayHeader()
	{
	  	$catModule = $this->_AddonManager->GetModule("Category_Module", false);
	  	$catModule->catID = $this->_review->categoryID;
	  	$catModule->displayCategoryPathway(true);
	  	parent::_DisplayHeader();
	}

	function _DisplayMain()
	{	 
	  	$SR_Addon_Manager =& Addon_Manager::Get();
	  	parent::_DisplayMain();
	  	$tagModule = $SR_Addon_Manager->GetModule("Tag_Module", true);
		$this->_review->content = $tagModule->ReplacePlaceHolders($this->_review, $this->_review->content); 					
      	echo $this->_DoMambots($this->_review->content);  	
	}

	function _DisplayFooter()
	{
	  	parent::_DisplayFooter(); 
	}
	
	//TODO: merge with Addon_Base_Frontend::GetURL i.e. parameter order	
	function GetURL($row, $parameters="", $itemID=null, $useSef=true)
	{
	    global $Itemid;
	  	
	  	$link = Review_Module_URL;
	  			
	  	if ($parameters != "")
	  	{
	  	  	//include item id in parameters
	  		$link .= $parameters;
	  	}
		
		$titles = $row->title1;
		if($row->title2)
		{
			  $titles.="-$row->title2";
		}
		if($row->title3)
		{
			  $titles.="-$row->title3";
		}
						
		preg_match_all('/([\w\-]+)/u', $titles, $matches); 
				
		$titles = implode('-', $matches[0]);		
		$titles = preg_replace('/[\-]{2,}/', '-', $titles);
		$titles = preg_replace('/[\-]$/', '', $titles);//if - is at the end then remove it
		
		$realItemID = $Itemid;
		if($itemID != null && $itemID != $realItemID)
		{
		    $realItemID =  $itemID;
		}
		
		if(!$row->pageName)
		{
			$row->pageName = $titles;
		}
		
		$replace = array($realItemID, $row->reviewID, $titles, $row->title1, $row->title2, $row->title3, $row->pageName);
		
		$link = preg_replace($this->allowedURLTags, $replace, $link);

		return $useSef ? $this->_AddonManager->Bridge->RewriteUrl($link) : $link;   
	}		         

	
	function TableName()
	{
		return SRDB_Review::TableName();		
	}
	
  function ValidRating($rating)
  {
  		if(!is_numeric($rating))
  		{
		    return null;
		}
		$rating = round($rating, 2);		
		if($rating < 0 || $rating > Review_Module_MAX_RATING)
		{
		  	return null;
		}
		return doubleval($rating);
	} 
	
	function &SR_GetTitles($reviewID, $stripSlashes = true)
	{
		$reviewTitles =& new SRDB_Review_Title($this->_AddonManager->Database);
		if($stripSlashes)
		{	
			$titles =& $reviewTitles->SR_GetTitles($reviewID);
		}
		else
		{
     		$titles =& $reviewTitles->SR_GetUnescapedTitles($reviewID);	
		} 
		return $titles;
	}
	
	function SR_GetReviews($categoryID = null, $limitStart=null, $limitEnd=null)
	{
		$reviewDB =& new SRDB_Review($this->_AddonManager->Database);
		return $reviewDB->GetReviewList($categoryID, $limitStart, $limitEnd);
	}
	
	function GetTopReviewList($numberOfTopReviews, $catLeft = null, $catRight = null)
	{
		$reviewDB =& new SRDB_Review($this->_AddonManager->Database);
		return $reviewDB->GetTopReviewList($numberOfTopReviews, 'r.score desc', $catLeft, $catRight);				
	}
	
	function GetLatestReviewList($numberOfTopReviews, $catLeft = null, $catRight = null)
	{
		$reviewDB =& new SRDB_Review($this->_AddonManager->Database);
		return $reviewDB->GetTopReviewList($numberOfTopReviews, 'r.createdDate desc', $catLeft, $catRight);				
	}	
	
	function SR_GetReviewsForCategory($lft, $rgt, $limitStart=null, $limitEnd=null, $orderField='title1', $orderSort='asc', $startCharFilter=null)
	{
		$reviewDB =& new SRDB_Review($this->_AddonManager->Database);
		return $reviewDB->GetReviewsForCategory($lft, $rgt, $limitStart, $limitEnd, $orderField, $orderSort, $startCharFilter);
	}	
	
	function SR_GetFrontEndReviewCount($lft, $rgt, $startCharFilter=null)
	{
		$reviewDB =& new SRDB_Review($this->_AddonManager->Database);
		return $reviewDB->GetFrontEndReviewCount($lft, $rgt, $startCharFilter);		
	}
	
	function SR_GetReview($reviewID)
	{
		$reviewDB =& new SRDB_Review($this->_AddonManager->Database);
		return $reviewDB->GetReview($reviewID);
	}	
	
	
	function SR_GetStarRating($rating, $cssClass='largeStarRating')
	{
		$ratingAsInt = intval($rating);
		$key = ($rating == $ratingAsInt) ?  $ratingAsInt :  $ratingAsInt + 0.5; 
		$key = "$key$cssClass";
		$ratingHtml = array_key_exists($key, $this->_CachedStarRatings) ? $this->_CachedStarRatings[$key] : null;

		if($ratingHtml == null)
		{
			$rating = (5.0 / (float)Review_Module_MAX_RATING) * (float)$rating;

			$ratingHtml = "<span class='$cssClass'>";

			for($i=1; $i <= $rating; $i++)
			{
				$ratingHtml.= '<span class="starFull"></span>';	
			} 

			if(($i-1) != $rating)
			{
				$ratingHtml.= '<span class="starHalf"></span>';	
			} 
			
			for($i = ceil($rating); $i < 5; $i++)
			{
				$ratingHtml.= '<span class="starEmpty"></span>';	
			}
			
			$ratingHtml.='</span>';	
			
			$this->_CachedStarRatings[$key] = $ratingHtml;	
		}
		
		return $ratingHtml;
	}


	/**
	 * Changes a reviews template.  $this->_review must not be null.
	 *	 
	 * @param bool $keepTemplate
	 */
	function SR_ChangeCategory($keepTemplate)
	{			
		$templateTable = SRDB_Template::TableName();
		$categoryTable = SRDB_Category::TableName();
		
		if($this->_review == null)
		{
			SRError::Display('Review has not been set from bind.', true);			
		}
		
		$query = 'SELECT templateID'
		. "\n FROM $categoryTable"
		. "\n where categoryID = {$this->_review->categoryID}";		
		$templateID = SRBridgeDatabase::ScalarQuery($query);
				
		$am =& Addon_Manager::Get();
		$categoryModule = $am->GetModule('Category_Module', false);

		$catTitles = $categoryModule->GetCategoryTitles($this->_review->categoryID);
		$catTitleCount = $catTitles ? count($catTitles) : 0;	
		if($catTitleCount == 0)
		{
			SRError::Display('Category has no titles.', true);
		}
		
		$reviewTitles = $this->SR_GetTitles($this->_review->reviewID, false);
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
	  		 	$revTitle->reviewID = $this->_review->reviewID;
	  		 	$revTitle->title = '';
	  		 	$revTitle->titleOrder = $i;	
	  		 	if (!$revTitle->store(true)) {
					SRError::Display("$this->friendlyName :: SR_ChangeCategory():Store", true);					
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
				$this->_review->content->content = '';
			}
			else
			{
				$query = 'SELECT template'
				. "\n FROM $templateTable"
				. "\n where templateID = $templateID";
				$this->_review->content = SRBridgeDatabase::ScalarQuery($query);
			}
		}
		if (!$this->_review->store()) {
			SRError::Display('Unable to save review.', true);
		}
	}
}
?>


       
