<?php
/**
 *  $Id$
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

class ReviewTitleHelper
{
	
	function Render($catTitle, $reviewTitle)
	{
		$title = $reviewTitle->title;
		$titleNumber = $reviewTitle->titleOrder;
		$titleName = $catTitle->titleName;
		
		$markup = '';
		switch($catTitle->titleType)
		{
			case 'Link':
				$markup = "<a href='$title' class='title$titleNumber titleLink'>$titleName</a>";
			break;
			
			case 'Rating':
				$reviewModule  =& $this->_AddonManager->GetModule('Review_Module', false);
				$markup = (Review_Module_USE_STAR_RATING == 1) ? $reviewModule->SR_GetStarRating($title) : $title;
				$markup = "<span class='title$titleNumber titleRating'>$markup</span>";
			break;
			
			default:
				$markup = $title;			
		}
		
		return $markup;		
	}	
}
?>