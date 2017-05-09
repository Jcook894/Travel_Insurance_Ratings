<?php
/**
 * JReviews - Reviews Extension
 * Copyright (C) 2010-2015 ClickFWD LLC
 * This is not free software, do not distribute it.
 * For licencing information visit https://www.jreviews.com
 * or contact sales@jreviews.com
**/

defined( 'MVC_FRAMEWORK') or die( 'Direct Access to this location is not allowed.' );

class RatingHelper extends MyHelper
{
    var $helpers = array('routes');

	var $no_rating_text = null; // Default no rating output

	var $rating_average_all = 0;

	var $rating_value = 0;

	var $review_count = 0;

	var $tmpl_suffix;

    /**
     * Rating select list used in review form
     * @param  [type]  $scale   [description]
     * @param  [type]  $default [description]
     * @param  integer $na      [description]
     * @return [type]           [description]
     */
	function options($scale, $default = _JR_RATING_OPTIONS, $na=1)
    {
		$options = array();

		if(in_array($this->Config->rating_selector,array('select','slider')))
        {
			$options = array(''=>$default);
		}

		// recall 1 = Required ; 0 = Not Required = allow N/A
		if ($na == 0 )
		{
			$options['na'] = __t('No rating', true);
		}

        $inc = !$this->Config->rating_increment ? 1 : $this->Config->rating_increment;

        for($i=$inc;$i<=$scale;$i=$i+$inc)

        {
            $options[(string)$i] = (string)$i;
        }

		// You can customize the text of the options by commenting the code above and using the one below:
//      $options['na'] = 'N/A';
//		$options[1] = 'Terrible';
//		$options[2] = 'Not so bad';
//		$options[3] = 'Just ok';
//		$options[4] = 'Good';
//		$options[5] = 'Excellent';

		return $options;
	}

    function ratingSearchOptions($default_text = '')
    {
        $scale = $this->Config->rating_scale;

        $scale_modifier = $scale/5;

        $adjusted_scale = array();

        if($default_text == '')
        {
            $default_text = __t("Select Rating", true);
        }

        for($i=1;$i<=4;$i++)
        {
            $adjusted_scale[$i] = round($i * $scale_modifier, 0);
        }

        $option_text = __t("%s and up", true);

        $options = array(
            ''=>$default_text,
            1=>sprintf($option_text, $adjusted_scale[1]),
            2=>sprintf($option_text, $adjusted_scale[2]),
            3=>sprintf($option_text, $adjusted_scale[3]),
            4=>sprintf($option_text, $adjusted_scale[4]),
        );

        return $options;
    }

	function overallRatings($listing, $page, $type = '')
	{
        $editor_reviews = $this->Config->getOverride('author_review',$listing['ListingType']['config']);

        $user_reviews = $this->Config->getOverride('user_reviews',$listing['ListingType']['config']);

        if(!($listing['Criteria']['state'] == 1 && ($editor_reviews || $user_reviews))) {

            return '';
        }

        switch($page) {

            case 'list':

                $show_user_rating = $this->Config->getOverride('list_show_user_rating',$listing['ListingType']['config']);

                $show_editor_rating = $this->Config->getOverride('list_show_editor_rating',$listing['ListingType']['config']);
            break;

            case 'module':

                $show_user_rating = Sanitize::getBool($this->params['module'],'user_rating',1);

                $show_editor_rating = Sanitize::getBool($this->params['module'],'editor_rating',1);
            break;

            default:

                $show_user_rating = true;

                $show_editor_rating = true;
            break;
        }

		$ratings = '<div class="jrOverallRatings">';

		// editor ratings
		if ($show_editor_rating && $editor_reviews && $type != 'user')
        {
            // Rating Styles

            $ratingStyle = $this->Config->getOverride('editor_rating_style', $listing['ListingType']['config']);

            $ratingColor = $this->Config->getOverride('editor_rating_color', $listing['ListingType']['config']);

			$editor_rating = Sanitize::getVar($listing['Review'],'editor_rating');

            $editor_rating_count = Sanitize::getInt($listing['Review'],'editor_rating_count');

            $rating_stars = $this->drawStars($editor_rating, $this->Config->rating_scale, 'editor', $ratingStyle, $ratingColor);

            $rating_value = $this->round($editor_rating,$this->Config->rating_scale);

            $rating_count = ($editor_rating_count > 1) ? ' <span class="rating_count">(<span class="count">' . $editor_rating_count . '</span>)</span>' : '';

            if ($page == 'content') {
                $ratings .= '<div class="jrOverallEditor jrRatingsLarge" title="' . __t("Editor rating", true) . '">';
            } else {
                $ratings .= '<div class="jrOverallEditor" title="' . __t("Editor rating", true) . '">';
            }

            $ratings .= '<span class="jrIconEditor jrRatingLabel"></span>';

            $ratings .= '<div class="jrRatingStars">' . $rating_stars . '</div>';

            $ratings .= '<span class="jrRatingValue">' . $rating_value . $rating_count . '</span>';

            $ratings .= '</div>';
		}

		// user ratings
		if ($page == 'content' && $user_reviews && $type != 'editor')
        {
            // Rating Styles

            $ratingStyle = $this->Config->getOverride('user_rating_style', $listing['ListingType']['config']);

            $ratingColor = $this->Config->getOverride('user_rating_color', $listing['ListingType']['config']);

			$user_rating = Sanitize::getVar($listing['Review'],'user_rating');

            $rating_stars = $this->drawStars($user_rating, $this->Config->rating_scale, 'user', $ratingStyle, $ratingColor);

            $rating_value = $this->round($user_rating,$this->Config->rating_scale);

            $rating_count = Sanitize::getInt($listing['Review'],'user_rating_count');

            if ($rating_count > 0) {

                $ratings .= '<div class="jrOverallUser jrRatingsLarge" title="' . __t("User rating", true) . '">';

                $ratings .= '<span class="jrIconUsers jrRatingLabel"></span>';

                $ratings .= '<div class="jrRatingStars">' . $rating_stars . '</div>';

                $ratings .= '<span class="jrRatingValue">';

                    $ratings .= '<span>' . $rating_value . '</span>';

                    $ratings .= '<span class="jrReviewCount"> (<span class="count">' . $rating_count . '</span>)</span>';

                $ratings .= '</span>';

            } else {

                $ratings .= '<div class="jrOverallUser jrRatingsLarge" title="' . __t("User rating", true) . '">';

                $ratings .= '<span class="jrIconUsers jrRatingLabel"></span>';

                $ratings .= '<div class="jrRatingStars">' . $rating_stars . '</div>';

                $ratings .= '<span class="jrRatingValue">' . $rating_value . ' <span class="rating_count">(<span class="count">' . $rating_count . '</span>)</span></span>';

            }

            $ratings .= '</div>';
		}
		elseif ($show_user_rating && in_array($page,array('list','module')) && $user_reviews && $type != 'editor') {

            // Rating Styles

            $ratingStyle = $this->Config->getOverride('user_rating_style', $listing['ListingType']['config']);

            $ratingColor = $this->Config->getOverride('user_rating_color', $listing['ListingType']['config']);

			$user_rating = Sanitize::getVar($listing['Review'],'user_rating');

			$rating_stars = $this->drawStars($user_rating, $this->Config->rating_scale, 'user', $ratingStyle, $ratingColor);

			$rating_value = $this->round($user_rating,$this->Config->rating_scale);

			$rating_count = Sanitize::getInt($listing['Review'],'user_rating_count');

			$ratings .= '<div class="jrOverallUser" title="' . __t("User rating", true) . '">';

			$ratings .= '<span class="jrIconUsers jrRatingLabel"></span>';

			$ratings .= '<div class="jrRatingStars">' . $rating_stars . '</div>';

			$ratings .= '<span class="jrRatingValue">' . $rating_value . ' <span class="rating_count">(<span class="count">' . $rating_count . '</span>)</span></span>';

			$ratings .= '</div>';
		}

		$ratings .= '</div>';

		return $ratings;
	}

	// Converts numeric ratings into graphical output
	function drawStars($rating, $scale, $type, $style = null, $color = null)
	{
        if ($style == null && $color == null)
        {
            // This only works for single listing pages where the Configuration values already have the overrides included

            $style = Sanitize::getInt($this->Config,$type.'_rating_style',1);

            $color = Sanitize::getString($this->Config,$type.'_rating_color','orange');
        }

        $style = 'jrRatingsStyle'.$style;

        $color = 'jrRatings'.ucfirst($color);

		$ratingPercent = number_format(($rating/$scale)*100,0);

        $type = ucfirst($type);

		if ($rating > 0) {

			return "<div class=\"jrRatingStars$type $style $color\"><div style=\"width:{$ratingPercent}%;\">&nbsp;</div></div>";

		} elseif ($this->no_rating_text) {

			return $this->no_rating_text;
		} else {

			return "<div class=\"jrRatingStars$type $style $color\"><div style=\"width:0%;\">&nbsp;</div></div>";
		}
	}

    /**
    * Renders the detailed ratings table
    *
    * @param mixed $review array containing the ratings data
    * @param mixed $type string "user" or "editor"
    */
    function detailedRatings($review, $type, $options = array())
    {
        $show_rating_count = Sanitize::getBool($options,'show_rating_count',false);

        $aggregate_rating = Sanitize::getBool($options,'aggregate_rating',false);

        # Check if ratings enabled
        if($review['Criteria']['state'] != 1) return '';

        // Rating Styles

        $ratingStyle = $this->Config->getOverride($type.'_rating_style', $review['ListingType']['config']);

        $ratingColor = $this->Config->getOverride($type.'_rating_color', $review['ListingType']['config']);

        # Generate rich snippets markup
        if (!$aggregate_rating) {
            $schema_object = 'itemprop="reviewRating" itemscope itemtype="http://schema.org/Rating"';
        } else {
            $schema_object = '';
        }
        $rating_value_property = 'itemprop="ratingValue" ';

        # Disable rich snippets for editor reviews when listings have user reviews
        if (isset($review['Review']['review_count']) && $review['Review']['review_count'] > 0 && $type == 'editor') {
            $schema_object = '';
            $rating_value_property = '';
        }

        # Init vars
        $isReview = !isset($review['RatingUser']); // Is it a user/editor review or a listing?

        $showDetailedCriteriaRatings = !$isReview || (($type == 'user' && $this->Config->user_ratings) || ($type == 'editor' && $this->Config->author_ratings));

        if($type == 'user')
        {
            $search_limit = is_numeric($this->Config->review_search) ? $this->Config->review_search : $this->Config->user_limit;

            $review_count_higher_than_search_limit = isset($review['Review']['review_count']) && $review['Review']['review_count'] > $search_limit;
        }
        else {

            $search_limit = is_numeric($this->Config->review_search) ? $this->Config->review_search : $this->Config->editor_limit;

            $review_count_higher_than_search_limit = isset($review['Review']['editor_review_count']) && $review['Review']['editor_review_count'] > $search_limit;
        }

        $output = '';

        // Conditions below add support for calling this method using the $listing array

        if($type == 'editor' && !$isReview && isset($review['Review']['editor_rating'])) {

            $review['Rating'] = $review['RatingEditor'];
        }
        elseif($type == 'user' && !$isReview && isset($review['Review']['user_rating'])) {

            $review['Rating'] = $review['RatingUser'];
        }

        if(!isset($review['Rating']['average_rating']) ||
            ($this->Config->rating_hide_na && $review['Rating']['average_rating'] == 'na')
            // ||
            // !isset($review['Review'])
            )  {

            return '';
        }

        # Remove all na rated criteria and required criteria when rating is zero which can happen for old reviews if new criterion is added

        foreach($review['Criteria']['criteria'] AS $key=>$value)
        {
            if(!isset($review['Rating']['ratings'][$key])) continue;

            if($this->Config->rating_hide_na)
            {
                if($review['Rating']['ratings'][$key] == 'na') {
                    unset($review['Criteria']['criteria'][$key]);
                }
            }

            if($review['Criteria']['required'][$key] && $review['Rating']['ratings'][$key] == 0)
            {
                unset($review['Criteria']['criteria'][$key]);
            }
        }

        $output .= '<div class="jrTableGrid jrRatingTable">';

        # Only one criteria defined

        if(count($review['Criteria']['criteria']) == 1)
        {
            $title = reset($review['Criteria']['criteria']);

            $count = reset($review['Rating']['criteria_rating_count']);

            $output .= '<div class="jrRow" '.$schema_object.'>';
			$output .=  '<div class="jrCol jrRatingLabel">' . $title . '&nbsp;</div>';
            $output .=  '<div class="jrCol">'. $this->drawStars($review['Rating']['average_rating'], $this->Config->rating_scale, $type, $ratingStyle, $ratingColor) . '</div>';
            $output .=  '<div '.$rating_value_property.'class="jrCol jrRatingValue">' . $this->round($review['Rating']['average_rating'],$this->Config->rating_scale);

            if ($this->Config->rating_scale != 5) {
                $output .= '<meta itemprop="bestRating" content="'.$this->Config->rating_scale.'">';
            }

            // rating count for criteria

            if (
                    ( $this->Config->show_criteria_rating_count == 2
                        || ( $this->Config->show_criteria_rating_count == 1 && in_array(0, $review['Criteria']['required']) )
                    )
                    && $show_rating_count
                )
            {
                $output .= '<span>&nbsp;&nbsp;(' . (int) $count. ')</span>';
            }

            $output .=  '</div>'; // jrCol
            $output .= '</div>'; // jrRow
        }

        # More than one criteria, display detailed ratings
        else
        {
            $output .= '<div class="jrRow" '.$schema_object.'>';
            $output .=  '<div class="jrCol jrRatingLabel">' . __t("Overall rating",true) . '&nbsp;</div>';
            $output .=  '<div class="jrCol">' . $this->drawStars($review['Rating']['average_rating'], $this->Config->rating_scale, $type, $ratingStyle, $ratingColor) . '</div>';
            $output .=  '<div '.$rating_value_property.'class="jrCol jrRatingValue">' . $this->round($review['Rating']['average_rating'],$this->Config->rating_scale) . '</div>';

            if ($this->Config->rating_scale != 5) {
                $output .= '<meta itemprop="bestRating" content="'.$this->Config->rating_scale.'">';
            }

            $output .= '</div>';

            if($showDetailedCriteriaRatings)
            {
                foreach($review['Criteria']['criteria'] AS $key=>$value)
                {
                    if(!$isReview && $review_count_higher_than_search_limit)
                    {
                        $value = $this->Routes->listing($value,$review,$type,array('order'=>($type == 'user' ? S2_QVAR_PREFIX_RATING_CRITERIA : S2_QVAR_PREFIX_EDITOR_RATING_CRITERIA) . '-' . $key));
                    }

                    $output .= '<div class="jrRow">';
                    $output .=  '<div class="jrCol jrRatingLabel">' . $value . '&nbsp;</div>';
                    $output .=  '<div class="jrCol">' . $this->drawStars(Sanitize::getString($review['Rating']['ratings'],$key), $this->Config->rating_scale, $type, $ratingStyle, $ratingColor) . '</div>';
                    $output .=  '<div class="jrCol jrRatingValue">' . $this->round(Sanitize::getString($review['Rating']['ratings'],$key),$this->Config->rating_scale);

                    // rating count for criteria

                    if (($this->Config->show_criteria_rating_count == 2
                            || ( $this->Config->show_criteria_rating_count == 1
                                && in_array(0, $review['Criteria']['required'])))
                            && $show_rating_count
                        )
                    {
                        $output .= '&nbsp;&nbsp;(' . Sanitize::getInt($review['Rating']['criteria_rating_count'],$key) . ')';
                    }

                    $output .=  '</div>';
                    $output .= '</div>';
                }
            }
        }

        $output .= '</div>';
        $output .= '<div class="jrClear"></div>';

        return $output;
    }

    function histogram($listing, $type = 'user')
    {
        $scale = $this->Config->rating_scale;

        $type_class = ucfirst($type);

        $total_count = $listing['Review'][$type.'_rating_count'];

        if($type == 'user')
        {
            $search_limit = is_numeric($this->Config->review_search) ? $this->Config->review_search : $this->Config->user_limit;

            $review_count_higher_than_search_limit = isset($listing['Review']['review_count']) && $listing['Review']['review_count'] > $search_limit;
        }
        else {

            $search_limit = is_numeric($this->Config->review_search) ? $this->Config->review_search : $this->Config->editor_limit;

            $review_count_higher_than_search_limit = isset($listing['Review']['editor_review_count']) && $listing['Review']['editor_review_count'] > $search_limit;
        }

        ?>
            <div class="jrTableGrid jrRatingTable">

                <?php
                $ratingColor = 'jrRatings'.ucfirst(Sanitize::getString($this->Config,$type.'_rating_color','orange'));

                foreach($listing['ReviewRatingCount'] AS $rating=>$range):?>

                    <?php
                    $label = sprintf(__n('%s star', '%s stars', is_numeric($range['rating_range']) ? $range['rating_range'] : 10, true), $range['rating_range']);

                    if($range['count'] > 0 && $review_count_higher_than_search_limit)
                    {
                        $label = $this->Routes->listing($label,$listing,$type,array(S2_QVAR_RATING_AVG=>$rating));
                    }

                    $count_percentage = number_format(($range['count']/$total_count)*100,0);
                    ?>

                    <div class="jrRow">
                        <div class="jrCol jrRatingLabel">
                            <?php echo $label;?>
                        </div>

                        <div class="jrCol">
                            <div class="jrRatingBars<?php echo $type_class;?> <?php echo $ratingColor;?>"><div title="<?php echo $count_percentage;?>%" style="width:<?php echo $count_percentage;?>%;">&nbsp;</div></div>
                        </div>

                        <div class="jrCol jrRatingCount">(<?php echo $range['count'];?>)</div>
                    </div>

                <?php endforeach;?>

            </div>
        <?php
    }

    /**
    * Renders the detailed ratings table
    *
    * @param mixed $review array containing the ratings data
    * @param mixed $type string "user" or "editor"
    */
    function compareRatings($listing, $type)
    {
        // Rating Styles

        $ratingStyle = $this->Config->getOverride($type.'_rating_style', $listing['ListingType']['config']);

        $ratingColor = $this->Config->getOverride($type.'_rating_color', $listing['ListingType']['config']);

		if($type == 'editor')
        {
            $average_rating = $listing['Review']['editor_rating'];

			$criteria_rating_count = explode(',',$listing['Review']['editor_criteria_rating_count']);

            $listing['Rating'] = $listing['RatingEditor'];
        }
		else {

            $average_rating = $listing['Review']['user_rating'];

			$criteria_rating_count = explode(',',$listing['Review']['user_criteria_rating_count']);

			$listing['Rating'] = $listing['RatingUser'];
		}

        # Remove all na rated criteria
        if($this->Config->rating_hide_na)
        {
            foreach($listing['Criteria']['criteria'] AS $key=>$value)
            {
                if($listing['Rating'][$key] == 'na') { unset($listing['Criteria']['criteria'][$key]); }
            }
        }

        // Only one criterion defined
        if(count($listing['Criteria']['criteria']) == 1)
        {
            return '<div class="itemUserRating jrCompareField">' . $this->drawStars($average_rating, $this->Config->rating_scale, $type, $ratingStyle, $ratingColor) . '</div>';
        }

        // More than one criterion, display detailed ratings
        $output = '<div class="itemUserRating jrCompareField">' . $this->drawStars($average_rating, $this->Config->rating_scale, $type, $ratingStyle, $ratingColor) . '</div>';

        $i = 0;

        foreach ($listing['Criteria']['criteria'] AS $key=>$value)
        {
            $ratingValue = isset($listing['Rating']['ratings']) ? Sanitize::getString($listing['Rating']['ratings'],$key) : 0;
            $output .= '<div class="itemUserRating' . $i . ' jrCompareField' . (fmod($i, 2) ? '' : ' alt') . '">';
            $output .= $this->drawStars($ratingValue, $this->Config->rating_scale, $type, $ratingStyle, $ratingColor);
            $output .= '</div>';
            $i++;
        }
        return $output;
    }

    /**
    * Renders the detailed ratings table
    *
    * @param mixed $review array containing the ratings data
    * @param mixed $type string "user" or "editor"
    */
    function compareRatingsHeader($listing, $type)
    {
        $isReview = isset($listing['Review']); // It's user or editor review
        $showDetailedCriteriaRatings = ($type == 'user' && $this->Config->list_compare_user_ratings) || ($type == 'editor' && $this->Config->list_compare_editor_ratings);

        # Remove all na rated criteria
        if($this->Config->rating_hide_na)
        {
            foreach($listing['Criteria']['criteria'] AS $key=>$value)
            {
                if($listing['Rating']['ratings'][$key] == 'na') { unset($listing['Criteria']['criteria'][$key]); }
            }
        }

        // Only one criterion defined
        if(count($listing['Criteria']['criteria']) == 1)
        {
            return '<div class="itemUserRating jrCompareField">' . $listing['Criteria']['criteria'][0] . '</div>';
        }

        // More than one criterion, display detailed ratings
        if($showDetailedCriteriaRatings)
        {
            $output = '<div class="itemUserRating jrCompareField">' . __t("Overall rating",true) . '</div>';

            $i = 0;
            foreach($listing['Criteria']['criteria'] AS $key=>$value) {
                $output .= '<div class="itemUserRating' . $i . ' jrCompareField' . (fmod($i, 2) ? '' : ' alt') . '">';
                $output .= $value;
                $output .= '</div>';
                $i++;
            }
            return $output;
        }

        return '<div class="itemUserRating jrCompareField">' . __t("Overall rating",true) . '</div>';
    }

	function round($value, $scale)
	{
		if(is_numeric($value)) {
			$value = ceil($value * 100) / 100; // extra math forces ceil() to work with decimals
		        $round = $scale > 10 ? 0 : 1;
		        return number_format($value,$round);
		} else {
		 	return empty($value) ? '0.0' : '<span class="jr_noRating" title="'.__t('Not rated', true).'">'.__t('N/A', true).'</span>';
		}
	}
}
