<?php
/**
 * JReviews - Reviews Extension
 * Copyright (C) 2010-2015 ClickFWD LLC
 * This is not free software, do not distribute it.
 * For licencing information visit https://www.jreviews.com
 * or contact sales@jreviews.com
**/

defined( 'MVC_FRAMEWORK') or die( 'Direct Access to this location is not allowed.' );

class JreviewsHelper extends MyHelper
{
	var $helpers = array('html','form','time','routes');

    static function orderingOptions($list_options = array())
    {
        $order_options_array = array (
            'featured'          =>__t("Featured",true),
            'alpha'             =>__t("Title",true),
            'rdate'             =>__t("Most recent",true),
            'date'              =>__t("Oldest",true),
            'updated'           =>__t("Last updated",true),
            'rhits'             =>__t("Most popular",true),
            'rating'            =>__t("Highest user rating",true),
            'rrating'           =>__t("Lowest user rating",true),
            'editor_rating'     =>__t("Highest editor rating",true),
            'reditor_rating'    =>__t("Lowest editor rating",true),
            'reviews'           =>__t("Most reviews",true),
            'author'            =>__t("Author",true)
        );

        if(!empty($list_options))
        {
            $order_options_array = array_intersect_key($order_options_array, array_flip($list_options));
        }

        return $order_options_array;
    }

    /**
     * [orderingList description]
     * @param  [type]  $selected [description]
     * @param  array   $fields   [description]
     * @param  array   $criteria [description]
     * @param  boolean $return   Used in the administration to display the default ordering options
     * @return [type]            [description]
     */
	function orderingList($selected = null, $options = array())
	{
        $return = Sanitize::getBool($options,'return',false);

        $fields = Sanitize::getVar($options,'fields',array());

        $criteria = Sanitize::getVar($options,'criteria',array());

        $orderingList = self::orderingOptions($return ? null : $this->Config->list_order_options);

        $list_order_criteria = $this->Config->list_order_criteria;

        if($return)
        {
            return $orderingList;
        }

		if(Configure::read('geomaps.enabled')==true)
        {
			$orderingList['distance'] = __t("Distance",true);

            if($selected=='') $selected = 'distance';
		}

        // Process rating criteria ordering options

        if(!empty($criteria))
        {
            $criterion = reset($criteria);

            if(!$this->Config->user_reviews || $criterion['CriteriaRating']['listing_type_state'] != 1)
            {
                unset($orderingList['reviews'], $orderingList['rating'], $orderingList['rrating']);
            }

            if(!$this->Config->author_review || $criterion['CriteriaRating']['listing_type_state'] != 1)
            {
                unset($orderingList['editor_rating'], $orderingList['reditor_rating']);
            }

            if(!empty($criteria) && count($criteria) > 1 && $criterion['CriteriaRating']['listing_type_state'] == 1)
            {
                if($this->Config->user_reviews && in_array($list_order_criteria,array('all','user')))
                {
                    foreach($criteria AS $criterion)
                    {
                        extract($criterion['CriteriaRating']);

                        $orderingList[S2_QVAR_PREFIX_RATING_CRITERIA . '-' . $criteria_id] = sprintf(__t("Best %s",true), $title);
                    }
                }

                if($this->Config->author_review && in_array($list_order_criteria,array('all','editor')))
                {
                    foreach($criteria AS $criterion)
                    {
                        extract($criterion['CriteriaRating']);

                        $orderingList[S2_QVAR_PREFIX_EDITOR_RATING_CRITERIA . '-' . $criteria_id] = sprintf(__t("Best %s (Editor rated)",true), $title);
                    }
                }
            }
        }

        // Process custom field ordering options

        if(!empty($fields))
        {
            foreach($fields AS $field)
            {
                if($this->Access->in_groups($field['access']))
                {
                    $orderingList[$field['value']] = $field['text'] . ' ' . __t("ASC",true);

                    $orderingList['r' . $field['value']] = $field['text'] . ' ' .  __t("DESC",true);
                }
            }
        }

		$attributes = array(
            'class'=>'jr-list-sort',
			'size'=>'1',
			'onchange'=>"window.location=this.value;return false;"
		);

		return $this->generateFormSelect($orderingList,$selected,$attributes);
	}

	function orderingListReviews($selected, $options = false)
    {
        $criteria = Sanitize::getVar($options,'criteria',array());

		$orderingList = array(
			'rdate'			=>__t("Most recent",true),
			'date'			=>__t("Oldest",true),
            'updated'       =>__t("Last updated",true),
			'rating'		=>__t("Highest user rating",true),
			'rrating'		=>__t("Lowest user rating",true),
			'helpful'		=>__t("Most helpful",true),
			'rhelpful'		=>__t("Least helpful",true),
            'discussed'     =>__t("Most discussed",true)
		);

        $list_options = $this->Config->review_order_options;

        // Remove rating options when ratings are disabled
        if(empty($criteria)) {
            unset($orderingList['rating'], $orderingList['rrating']);
        }

        if(Sanitize::getBool($options,'return')) return $orderingList;

        if(!empty($list_options))
        {
            $orderingList = array_intersect_key($orderingList, array_flip($list_options));
        }

        // Process rating criteria ordering options

        if(!empty($criteria) && count($criteria) > 1)
        {
            $criterion = reset($criteria);

            if(!empty($criteria))
            {
                if($criterion['CriteriaRating']['listing_type_state'] == 1)
                {
                    foreach($criteria AS $criterion)
                    {
                        extract($criterion['CriteriaRating']);

                        $orderingList[S2_QVAR_PREFIX_RATING_CRITERIA . '-' . $criteria_id] = sprintf(__t("Best %s",true), $title);
                    }
                }
            }
        }

		$attributes = array(
            'class'=>'jr-list-sort',
			'size'=>'1',
			'onchange'=>"window.location=this.value;return false;"
		);

		return $this->generateFormSelect($orderingList,$selected,$attributes);
	}

    function orderingListPosts($selected, $options = array()) {

        $options_array = array(
            'date'            =>__t("Oldest",true),
            'rdate'            =>__t("Most recent",true),
//            'helpful'        =>__t("Most helpful",true),
//            'rhelpful'        =>__t("Least helpful",true)
        );

        if(!empty($options)) {
            foreach($options AS $key) {
                if(isset($options_array[$key])) {
                    $orderingList[$key] = $options_array[$key];
                }
            }

        } else {
            $orderingList = $options_array;
        }

        $attributes = array(
            'class'=>'jr-list-sort',
            'size'=>'1',
            'onchange'=>"window.location=this.value;return false;"
        );

        $excludeParams = array(
            'lang',
            'order',
            S2_QVAR_PAGE
        );

        $attributes['excludeParams'] = $excludeParams;

        return $this->generateFormSelect($orderingList,$selected,$attributes);
    }

	function generateFormSelect($orderingList,$selected,$attributes)
    {
		# Construct new route
		$new_route_page_1 = '';

        $excludeParams = array(
            'id',
            'lang',
            'order',
            S2_QVAR_PAGE
        );

        if(isset($attributes['excludeParams']))
        {
            $excludeParams = $attributes['excludeParams'];

            unset($attributes['excludeParams']);
        }

        if (_CMS_NAME == 'wordpress' && $this->ajaxRequest && $searchUrl = Sanitize::getString($this->viewVars, 'search_url'))
        {
            $this->passedArgs['search_url'] = $searchUrl;
        }

		$new_route = cmsFramework::constructRoute($this->passedArgs,$excludeParams);

		if(Sanitize::getInt($this->params,S2_QVAR_PAGE,1) == 1
			&& preg_match('/^(index.php\?option=com_jreviews&amp;Itemid=[0-9]+)(&amp;url=menu\/)$/i',$new_route,$matches)
		) {
			// Remove menu segment from url if page 1 and it' a menu
			$new_route_page_1 = $matches[1];
		}
        else {

            $new_route_page_1 = $new_route;
        }

		$selectList = array();

        $default_order = Sanitize::getString($this->params,'default_order');

		foreach($orderingList AS $value=>$text)
		{
			// Default order takes user back to the first page
			if($value == $default_order) {

				$selectList[] = array('value'=>cmsFramework::route($new_route_page_1),'text'=>$text);
			}
			else {

				$selectList[] = array('value'=>cmsFramework::route($new_route . '/order' . _PARAM_CHAR . $value),'text'=>$text);
			}

		}

		if($selected == $default_order)
		{
			$selected = cmsFramework::route($new_route_page_1);

		}
		else {

			$selected = cmsFramework::route($new_route . '/order' . _PARAM_CHAR . $selected);
		}

		return $this->Form->select('order',$selectList,$selected,$attributes);
	}

	function newIndicator($days, $date)
    {
		return $this->Time->wasWithinLast($days . ' days', $date);
	}

	function userRank($rank) {

		switch ($rank) {
			 case ($rank==1): $toprank = __t("#1 Reviewer",true); break;
			 case ($rank<=10 && $rank>0): $toprank = __t("Top 10 Reviewer",true); break;
			 case ($rank<=50 && $rank>10): $toprank = __t("Top 50 Reviewer",true); break;
			 case ($rank<=100 && $rank>50): $toprank = __t("Top 100 Reviewer",true); break;
			 case ($rank<=500 && $rank>100): $toprank = __t("Top 500 Reviewer",true); break;
			 case ($rank<=1000 && $rank>500): $toprank = __t("Top 1000 Reviewer",true); break;
			 default: $toprank = '';
		}

		return $toprank;

	}

    function listingDetailBreadcrumb($crumbs) {

        if(!$this->Config->breadcrumb_detail_category) {

            $crumbs = array_slice($crumbs,count($crumbs)-2);
        }

        if($this->Config->dir_show_breadcrumb && !empty($crumbs)):?>

        <div class="jrPathway">

            <?php foreach($crumbs AS $crumb):?>

                <?php if($crumb['link']!=''):?>

                    <span itemscope itemtype="http://data-vocabulary.org/Breadcrumb">

                        <a itemprop="url" href="<?php echo $crumb['link'];?>">

                            <span itemprop="title"><?php echo $crumb['name'];?></span>

                        </a>

                    </span>

                <?php else:?>

                    <span><?php echo $crumb['name'];?></span>

                <?php endif;?>

            <?php endforeach;?>

        </div>

        <div class="jrClear"></div>

        <?php endif;

    }

    function listingInfoIcons($listing) {

        $media_photo_show_count = $this->Config->getOverride('media_photo_show_count',$listing['ListingType']['config'])
                                    &&
                                    ($this->Config->getOverride('media_photo_max_uploads_listing',$listing['ListingType']['config']) != '0'
                                    ||
                                    $this->Config->getOverride('media_photo_max_uploads_review',$listing['ListingType']['config']) != '0')
                                    ;

        $media_video_show_count = $this->Config->getOverride('media_video_show_count',$listing['ListingType']['config'])
                                    &&
                                    ($this->Config->getOverride('media_video_max_uploads_listing',$listing['ListingType']['config']) != '0'
                                    ||
                                    $this->Config->getOverride('media_video_max_uploads_review',$listing['ListingType']['config']) != '0')
                                    ;
        $media_attachment_show_count = $this->Config->getOverride('media_attachment_show_count',$listing['ListingType']['config'])
                                    &&
                                    ($this->Config->getOverride('media_attachment_max_uploads_listing',$listing['ListingType']['config']) != '0'
                                    ||
                                    $this->Config->getOverride('media_attachment_max_uploads_review',$listing['ListingType']['config']) != '0')
                                    ;
        $media_audio_show_count = $this->Config->getOverride('media_audio_show_count',$listing['ListingType']['config'])
                                    &&
                                    ($this->Config->getOverride('media_audio_max_uploads_listing',$listing['ListingType']['config']) != '0'
                                    ||
                                    $this->Config->getOverride('media_audio_max_uploads_review',$listing['ListingType']['config']) != '0')
                                    ;
        ?>

        <span class="jrListingStatus">

            <?php if($this->Config->getOverride('list_show_hits',$listing['ListingType']['config'])):?>

                <span title="<?php __t("Views");?>"><span class="jrIconGraph"></span><?php echo $listing['Listing']['hits']?></span>&nbsp;&nbsp;

            <?php endif;?>

            <?php if($media_video_show_count):?>

                <span title="<?php __t("Video count");?>"><span class="jrIconVideo"></span><?php echo (int)$listing['Listing']['video_count'];?></span>&nbsp;&nbsp;

            <?php endif;?>

            <?php if($media_photo_show_count):?>

                <span title="<?php __t("Photo count");?>"><span class="jrIconPhoto"></span><?php echo (int)$listing['Listing']['photo_count'];?></span>&nbsp;&nbsp;

            <?php endif;?>

            <?php if($media_audio_show_count):?>

                <span title="<?php __t("Audio count");?>"><span class="jrIconAudio"></span><?php echo (int)$listing['Listing']['audio_count'];?></span>&nbsp;&nbsp;

            <?php endif;?>

            <?php if($media_attachment_show_count):?>

                <span title="<?php __t("Attachment count");?>"><span class="jrIconAttachment"></span><?php echo (int)$listing['Listing']['attachment_count'];?></span>&nbsp;&nbsp;

            <?php endif;?>

            <?php if($this->Config->getOverride('favorites_enable',$listing['ListingType']['config'])):?>

                <span title="<?php __t("Favorite count");?>"><span class="jrIconFavorite"></span><span class="jr-favorite-<?php echo  $listing['Listing']['listing_id']; ?>"><?php echo (int)$listing['Favorite']['favored'];?></span></span>

            <?php endif;?>

        </span>

        <?php
    }

    function searchOptionLabels($searchOptionsArray)
    {
        $optionLabels = array();

        foreach($searchOptionsArray AS $fname=>$option) {

            $optionLabels[] = sprintf('<li><a href="%s">%s <span class="jrIconNo"></span></a></li>', cmsFramework::getCurrentUrl(array($fname)), $option['text']);
        }

        $optionLabels = '<ul class="jrSearchTags">'.implode('',$optionLabels).'</ul>';

        return $optionLabels;
    }

    function listingStatusLabels($listing) {

        $com_content = Sanitize::getString($listing['Listing'],'extension') == 'com_content';

        if(!$com_content) return '';

        $unpublished = $com_content && $listing['Listing']['state'] < 1;

        $expired = $com_content && $listing['Listing']['publish_down'] != NULL_DATE && strtotime($listing['Listing']['publish_down']) < strtotime(_CURRENT_SERVER_TIME);
        ?>

        <span class="jrStatusIndicators">

            <?php if($expired || $unpublished):?>

                <?php if($unpublished):?><span class="jrStatusLabel jrOrange"><?php __t("Pending Moderation");?></span><?php endif;?>

                <?php if($expired):?><span class="jrStatusLabel jrRed"><?php __t("Expired");?></span><?php endif;?>

            <?php else:?>

                <?php if($this->Config->getOverride('list_featured',$listing['ListingType']['config']) && $listing['Listing']['featured']):?>

                    <span class="jrStatusFeatured"><?php JreviewsLocale::getConstant($this->Config->getOverride('lang_listing_featured',$listing['ListingType']['config']));?></span>

                <?php endif;?>

                <?php if($this->Config->getOverride('list_new',$listing['ListingType']['config']) && $this->newIndicator($this->Config->getOverride('list_new_days',$listing['ListingType']['config']),$listing['Listing']['created'])):?>

                    <span class="jrStatusNew"><?php __t("New");?></span>

                <?php endif;?>

                <?php if($this->Config->getOverride('list_hot',$listing['ListingType']['config']) && $this->Config->getOverride('list_hot_hits',$listing['ListingType']['config']) <= $listing['Listing']['hits']):?>

                    <span class="jrStatusHot"><?php __t("Hot");?></span>

                <?php endif;?>

            <?php endif;?>

        </span>

        <?php
    }

    function listingDetailFeed($listing) {

        if($listing['Criteria']['state'] && $this->Config->rss_enable && ($listing['Review']['review_count'] + $listing['Review']['editor_review_count']) > 0):?>

            <div class="jrRSS"><ul class="jrFeeds"><li><?php echo $this->Routes->rssListing($listing);?></li></ul></div>

        <?php endif;
    }

    function loadPosition($position_name, $position_array)
    {

        if(isset($position_array[$position_name])) {

            foreach($position_array[$position_name] as $position) {

                echo $position;

            }

        }
    }

    function loadModulePosition($position, $container = 'div', $style = 'xhtml')
    {
        if(_CMS_NAME == 'joomla')
        {
	        $document   = JFactory::getDocument();

            $type = $document->getType();

	        $renderer   = $document->setType('html')->loadRenderer('module');

	        $params     = array('style'=>$style);

	        $modules    = JModuleHelper::getModules($position);

	        $contents = '';

	        foreach ($modules as $module)  {
	            $contents .= $renderer->render($module, $params);
	        }

            $document->setType($type);

	        if ($container == 'tr' && $contents != '') {

	            echo '<tr class="jrCustomModule"><td colspan="3">'.$contents.'</td></tr>';

	        } else if ($contents != '') {

	            echo '<div class="jrCustomModule jrClear">'.$contents.'</div>';

	        }
        }
        elseif(_CMS_NAME == 'wordpress') {

            dynamic_sidebar($position);
        }
    }
}
