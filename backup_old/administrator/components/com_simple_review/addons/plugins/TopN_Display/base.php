<?php
/**
 *  $Id: base.php 50 2009-03-22 05:12:19Z rowan $
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

class TopN_Display_Base extends Plugin_Base
{
 var $sr_user = null;
 var $topReviews = null;
 var $canDelete = false;
 var $tmpl = null;

 	function TopN_Display_Base(&$addonManager, &$pluginName, $initialise)
 	{
 	  	$this->dependsOnModules = array("Review_Module", "Category_Module", "Tag_Module");
		$this->canAttachToModules = array("Category_Module");
		$this->pluginType="TopN_Display";
				  	
 	  	parent::Plugin_Base($addonManager, $pluginName, $initialise);
		
		if($initialise)
		{
			$this->LoadTemplate($pluginName, true);
		}
	}
   
	function AddTopN(&$r)
	{        		
		$SR_Addon_Manager =& Addon_Manager::Get();
        $reviewModule = $SR_Addon_Manager->GetModule("Review_Module", false);
        $tagModule = $SR_Addon_Manager->GetModule("Tag_Module", true);
		
		$template =& $this->templates[$this->addonName]; 
		
		$details = new stdClass();
		$details->title = 'Click to read the review';
		$details->reviewUrl = $reviewModule->GetURL($r);
		$details->title1 = Simple_Review_Common::RemoveSlashes($r->title1);
		$details->title2 = Simple_Review_Common::RemoveSlashes($r->title2);
		$details->title3 = Simple_Review_Common::RemoveSlashes($r->title3);
		$details->score = $r->score;
		$details->blurb = $tagModule->ReplacePlaceHolders($r, Simple_Review_Common::RemoveSlashes($r->blurb));
		$details->thumbnailUrl = $r->thumbnailURL;

		$template->AddTopN($details);
        
	}
	
	function _DisplayHeader()
	{	  
	}

	function _DisplayMain()
	{	 
	  if($this->topReviews == null)
	  {
	  		return;  
	  }
	  foreach($this->topReviews as $review	)
	  {
			 $this->AddTopN($review);  	    
	  }     	    


	}
	
	function _DisplayFooter()
	{ 
	}
	
	function Display()
	{
	 	$this->_DisplayHeader(); 
		$this->_DisplayMain(); 
		$this->_DisplayFooter(); 		 	  
	}
	
	function _LoadAddonData()	
	{ 
		$SR_Addon_Manager =& Addon_Manager::Get();
		$catModule =& $SR_Addon_Manager->GetModule("Category_Module", false);
		
		$catId = $catModule->GetCategoryIdFromRequest();
		if($catId == -1)
		{
		  	$this->topReviews = null;
			return;  
		}

		$reviewModule = $SR_Addon_Manager->GetModule("Review_Module", false);	
		
		$catLeft = null;
		$catRight = null;
		
		if(Category_Module_N_TOP_REVIEWS_CURRENT_CAT)
		{
			if($catModule->lft && $catModule->rgt)
			{
				$catLeft = $catModule->lft;
				$catRight = $catModule->rgt;				
			}
		}
		
		$topRated=true;//CHANGE HERE if you want latest
		if($topRated)
		{
			$this->topReviews = $reviewModule->GetTopReviewList(Category_Module_N_TOP_REVIEWS,  $catLeft, $catRight);
		}
		else
		{
			$this->topReviews = $reviewModule->GetLatestReviewList(Category_Module_N_TOP_REVIEWS, $catLeft, $catRight);
		}
	}
		    
}

?>