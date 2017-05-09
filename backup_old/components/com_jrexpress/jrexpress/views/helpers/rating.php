<?php
/*
    JReviews Express - user reviews for Joomla
    Copyright (C) 2009  Alejandro Schmeichler

    JReviews Express is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    JReviews Express is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/

defined( 'MVC_FRAMEWORK') or die( 'Direct Access to this location is not allowed.' );

class RatingHelper extends MyHelper
{	
	var $no_rating_text = null; // Default no rating output
	var $rating_average_all = 0;
	var $rating_value = 0;
	var $review_count = 0;
	var $tmpl_suffix;
	
	function options($scale, $default = _JR_RATING_OPTIONS) {
				
		$options = array();
		
		if($this->Config->rating_selector== 'select'){
			$options = array(''=>$default);
		}
		
		for($i=1;$i<=$scale;$i++) {
			$options[$i] = $i;
		}
		
		// You can customize the text of the options by commenting the code above and using the one below:
//		$options[1] = 'Terrible'; 
//		$options[2] = 'Not so bad';
//		$options[3] = 'Just ok';
//		$options[4] = 'Good';
//		$options[5] = 'Excellent';
		
		return $options;
	}
	
	// Converts numeric ratings into graphical output
	function drawStars($rating, $scale, $graphic, $type) 
	{
		$round_to = $scale > 10 ? 0 : 1;		
		
		$rating_graphic = $graphic ? 'rating_star_' : 'rating_bar_';
		
		$class = $rating_graphic . $type; // builds the class based on graphic and rating type
		
		$ratingPercent = number_format(($rating/$scale)*100,0);

		if ($rating > 0) {
			
			return "<div class=\"$class\"><div style=\"width:{$ratingPercent}%;\">&nbsp;</div></div>";
		
		} elseif ($this->no_rating_text) {

			return $this->no_rating_text;
		} else {

			return "<div class=\"$class\"><div style=\"width:0%;\">&nbsp;</div></div>";
		}
	}
	
    function round($value, $scale) {
        $value = ceil($value * 100) / 100; // extra math forces ceil() to work with decimals
        $round = $scale > 10 ? 0 : 1;
        return number_format($value,$round);
    }

	function getRank($userid,$rank,$limit,$Itemid) {

		$pag_start = '';
		$start = floor($rank/$limit)*$limit;
		
		switch ($rank) {
			 case ($rank==1): $user_rank = _JR_RANK_TOP1; break;
			 case ($rank<=10 && $rank>0): $user_rank = _JR_RANK_TOP10; break;
			 case ($rank<=50 && $rank>10): $user_rank = _JR_RANK_TOP50; break;
			 case ($rank<=100 && $rank>50): $user_rank = _JR_RANK_TOP100; break;
			 case ($rank<=500 && $rank>100): $user_rank = _JR_RANK_TOP500; break;
			 case ($rank<=1000 && $rank>500): $user_rank = _JR_RANK_TOP1000; break;
			 default: $user_rank = '';
		}

		if ($start > 1) {
			$pag_start = "&amp;limit=$limit&amp;limitstart=$start";
		}


		if ($user_rank != '') {
			$url = $this->link($user_rank,'index.php?option='.S2Paths::get('jrexpress','S2_CMSCOMP').'&amp;task=reviewrank&amp;user='.$userid.$pag_start.'#$userid');
			return $url;
		}
	}	
	
}