<?php
/**
 *  $Id: Tag_Module.php 117 2009-08-10 13:44:14Z rowan $
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
class Tag_Module extends Module_Base
{
	function Tag_Module(&$addonManager, &$moduleName, $initialise)
	{
		$this->addonPath = dirname(__FILE__);
		
	  	$this->hasCSS=false;
	  	$this->hasLanguage=true;
	  	$this->hasConfig=true;	
		$this->addonType =_SR_ADDON_TYPE_MODULE; 	    				
	  	parent::Module_Base($addonManager, $moduleName, $initialise);	
		$this->Initialised = $initialise;  	    
					
		$this->friendlyName = $this->GetString($this, 'Tags');
		$this->defaultTaskName = $this->GetString($this, 'AdminDescription');   		
	}
	
    function _LoadAddonData()
    {	 			        
    }

	function Display()
	{
	}

	function _DisplayHeader()
	{

	}

	function _DisplayMain()
	{	  
	}

	function _DisplayFooter()
	{
	}

	function ListTags()
	{

            ?>
            <table style='text-align:left;border-style: solid; border-width: 1px;'>
            <tr class='altOdd'>            
            <th colspan='2'><?php echo $this->GetString($this, 'Tags');?></th>
            </tr>
            
            <tr>
			<td colspan='2'><?php echo $this->GetString($this, 'Introduction');?></td>
            </tr>
            
            <tr class='altOdd'>
			<th><u><?php echo $this->GetString($this, 'Tag');?></u></th> <th><u><?php echo $this->GetString($this, 'Description');?></u></th>
            </tr>            
            
            <tr class='altEven'>
            <th>{sr_reviewURL}</th> <td><?php echo $this->GetString($this, 'TagReviewUrl');?></td>
            </tr>
            
            <tr class='altOdd'>
            <th>{sr_rating}</th> <td><?php echo $this->GetString($this, 'TagRating');?></td>
            </tr>
            
            <tr class='altEven'>
            <th>{sr_avgUserRating}</th> <td><?php echo $this->GetString($this, 'TagAvgUserRating');?></td>
            </tr>
            
            <tr class='altOdd'>
            <th>{sr_ratingBox}</th> <td><?php echo $this->GetString($this, 'TagRatingBox');?></td>
            </tr>
            
            <tr class='altEven'>
            <th>{sr_userRatingBox}</th> <td><?php echo $this->GetString($this, 'TagUserRatingBox');?></td>
            </tr>
            
            <tr class='altOdd'>
            <th>{sr_maxRating}</th> <td><?php echo $this->GetString($this, 'TagMaxRating');?></td>
            </tr>
            			
			<tr class='altEven'>
			<th>{sr_award}</th> <td><?php echo $this->GetString($this, 'TagAward');?></td>
			</tr>
			
            <tr class='altOdd'>
            <th>{sr_title1}, {sr_title2}, ... </th> <td><?php echo $this->GetString($this, 'TagTitles');?></td>
            </tr>
                                    
            <tr class='altEven'>
            <th>{sr_blurb}</th> <td><?php echo $this->GetString($this, 'TagBlurb');?></td>
            </tr>
            
            <tr class='altOdd'>
            <th>{sr_thumbnailURL}</th> <td><?php echo $this->GetString($this, 'TagThumbnailUrl');?></td>
            </td>
            
            <tr class='altEven'>
            <th>{sr_imageURL}</th> <td><?php echo $this->GetString($this, 'TagImageUrl');?></td>
            </tr>
            
            <tr class='altOdd'>
            <th>{sr_createdBy}</th> <td><?php echo $this->GetString($this, 'TagCreatedBy');?></td>
            </tr>
            
            <tr class='altEven'>
            <th>{sr_createdDate}</th> <td><?php echo $this->GetString($this, 'TagCreatedDate');?></td>
            </tr>
            
            <tr class='altOdd'>
            <th>{sr_lastModifiedBy}</th> <td><?php echo $this->GetString($this, 'TagLastModifiedBy');?></td>
            </tr>
            
            <tr class='altEven'>            
            <th>{sr_lastModifiedDate}</th> <td><?php echo $this->GetString($this, 'TagLastModifiedDate');?></td>
            </tr>
            
            <tr class='altOdd'>
            <th>{sr_categoryID}</th><td><?php echo $this->GetString($this, 'TagCategoryID');?></td>
            </tr>
            
            <tr class='altEven'>
            <th>{sr_catName}</th><td><?php echo $this->GetString($this, 'TagCategoryName');?></td>
            </tr>
            
            <tr class='altOdd'>
            <th>{sr_catTitle1}, {sr_catTitle2}, ... </th> <td><?php echo $this->GetString($this, 'TagCategoryTitles');?></td>
            </tr>            
            
            </table>
            <?php

	}

	function InitDynamicFields($dynamicPrefix)
	{
	 
	}

	function _AddDynamicFields(&$search, &$replace, &$row)
	{	    
		$SR_Addon_Manager =& Addon_Manager::Get();
	  	$reviewModule = $SR_Addon_Manager->GetModule("Review_Module", false);
	  	$categoryModule = $SR_Addon_Manager->GetModule("Category_Module", false);

	  	$reviewTitles = $reviewModule->SR_GetTitles($row->reviewID);
	  	$catTitles = $categoryModule->GetCategoryTitles($row->categoryID);
	  	

		for($i=0; $i < count($catTitles); $i++)
			{
				$titleName = $catTitles[$i]->titleName;
				$title = "";
				$idx = $i + 1;
				for($j=0; $j < count($reviewTitles); $j++)
				{
					if($catTitles[$i]->categoryTitleID == $reviewTitles[$j]->categoryTitleID)
					{  
						$title = ReviewTitleHelper::Render($catTitles[$i], $reviewTitles[$j]);
						break;
					}
				} 
				$search[] = "/\{sr_catTitle$idx\}/"; 
		  		$replace[] = $titleName;
		  		
		  		$search[] = "/\{sr_title$idx\}/";
		  		$replace[] = $title; 		  
			}	  		  		  
	}
	 
	function ReplacePlaceHolders($reviewRow, $textToParse)
	{
		$awardTable = '#__simplereview_awards';
		$reviewModule  =& $this->_AddonManager->GetModule('Review_Module', false);
		$langModule =& $this->_AddonManager->GetModule('Language_Module', false);
		
     	if($reviewModule == null)
     	{
			SRError::Display("Unable to load review module.", true);
		}
		$outOf = $this->GetString($reviewModule, 'OutOf');
        $scoreBox =     "<div id='scoreBox'>"
					  ."      <div><span class='ratingHeading'>Rating</span></div>"
                      ."      <div><span class='rating'>$reviewRow->score</span></div>"
                      ."      <div id='scoreBoxText'><span class='ratingText'> ".$outOf.Review_Module_MAX_RATING."</span></div>"
                      ."  </div>";
                      
        $awardImage = "";
        if($reviewRow->awardID != -1)
        {
            $query = "select imageURL from $awardTable where awardID=$reviewRow->awardID;";
            $awardImage = SRBridgeDatabase::ScalarQuery($query);
            
        }
        $createdBy = "";	
        $lastModifiedBy = "";
        
        if($reviewRow->createdByID != -1 )
        {  
			$user = new SRBridgeUser($reviewRow->createdByID);
        	$createdBy = _SR_GLOBAL_USE_REAL_NAME ?  $user->Name : $user->UserName;               		  		  
		}   

        if($reviewRow->lastModifiedByID != -1 )
        { 
			$user = new SRBridgeUser($reviewRow->lastModifiedByID);  
        	$lastModifiedBy = _SR_GLOBAL_USE_REAL_NAME ?  $user->Name : $user->UserName;    		  
		}   				             
        
        $noImageAvailable = $this->_AddonManager->Bridge->SiteUrl.'components/com_simple_review/images/image_unavailable.gif';
        $thumbnailURL = '';
        if($reviewRow->thumbnailURL)
        {
            $thumbnailURL = $reviewRow->thumbnailURL;
        }
        else
        {
            $thumbnailURL = $noImageAvailable;
        }
		   
		$imageURL="";
        if($reviewRow->imageURL)
        {
            $imageURL = $reviewRow->imageURL;
        }
        else
        {
            $imageURL = $noImageAvailable;
        }				
		
		$categoryID = '';
		if($reviewRow->categoryID)
		{
			$categoryID = $reviewRow->categoryID;
		}
		
		$userScoreBox ="";
		$avg = HTML_Simple_Review::avgUserScore($reviewRow->reviewID);
		if($avg != null)
		{
            $userScoreBox =     "<div id='userScoreBox'>"
					  	  ."      <div><span class='ratingHeading'>User Rating</span></div>"			
                          ."      <div><span class='rating'>$avg</span></div>"
                          ."      <div id='userScoreBoxText'><span class='ratingText'> ".$outOf.Review_Module_MAX_RATING."</span></div>"
                          ."  </div>";				  
		}
		else
		{
		  	$avg = $langModule->GetString($langModule, 'NotAvailable');	
            $userScoreBox =     "<div id='userScoreBox'>"
                          ."      <div><span class='rating'>".$langModule->GetString($langModule, 'NotAvailable')."</span></div>"
                          ."      <div id='userScoreBoxText'><span class='ratingText'> "
						  .$outOf.Review_Module_MAX_RATING."</span></div>"
                          ."  </div>";				  			  
		}  
		
		$score = (Review_Module_USE_STAR_RATING == 1) ? $reviewModule->SR_GetStarRating($reviewRow->score) : $reviewRow->score;
		
		$pageName = $reviewRow->pageName != null ? $reviewRow->pageName : '';
		              
        $search = array(
		"/\{sr_score\}/", //0
		"/\{sr_maxScore\}/", //1
		"/\{sr_scoreBox\}/", //2
		"/\{sr_userReview\}/", //3
		"/\{sr_award\}/", //4
		"/\{sr_reviewURL\}/", //5
		"/\{sr_blurb\}/", //6
		"/\{sr_thumbnailURL\}/", //7
		"/\{sr_imageURL\}/", //8
		"/\{sr_createdBy\}/", //9
		"/\{sr_createdDate\}/", //10
		"/\{sr_lastModifiedBy\}/", //11
		"/\{sr_lastModifiedDate\}/", //12
		"/\{sr_avgUserScore\}/", //13
		"/\{sr_userScoreBox\}/", //14	
		"/\{sr_catName\}/", //15
		"/\{sr_rating\}/", //16			
		"/\{sr_maxRating\}/", //17
		"/\{sr_ratingBox\}/", //18	
		"/\{sr_avgUserRating\}/", //19
		"/\{sr_userRatingBox\}/", //20		
		"/\{sr_categoryID\}/",//21
		"/\{sr_pageName\}/"//22			
		);
        
        
        $replace = array(
		$score, //0
		Review_Module_MAX_RATING, //1
		$scoreBox, //2
		"",//3  will be ignored in non-user submitted reviews
		$awardImage, //4
		$reviewModule->GetURL($reviewRow), //5
		$reviewRow->blurb, //6
		$thumbnailURL, //7
		$imageURL, //8
		$createdBy, //9
		$reviewRow->createdDate, //10
		$lastModifiedBy, //11
		$reviewRow->lastModifiedDate, //12
		$avg, //13
		$userScoreBox, //14
		$reviewRow->categoryName,//15
		$score, //16
		Review_Module_MAX_RATING, //17
		$scoreBox, //18
		$avg, //19
		$userScoreBox, //20		
		$categoryID, //21
		$pageName //22	
		);
		
		$this->_AddDynamicFields($search, $replace, $reviewRow);                
        
        return preg_replace($search, $replace, $textToParse);			 	    
	  
	} 
}
?>


       
