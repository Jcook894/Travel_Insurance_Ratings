<?php
/**
 * JReviews - Reviews Extension
 * Copyright (C) 2010-2015 ClickFWD LLC
 * This is not free software, do not distribute it.
 * For licencing information visit https://www.jreviews.com
 * or contact sales@jreviews.com
**/

defined( 'MVC_FRAMEWORK') or die( 'Direct Access to this location is not allowed.' );

class AdminRoutesHelper extends MyHelper
{
	var $helpers = array('html');

	var $routes = array(
        'dashboard'=>'',
        'about'=>'about',
        'refresh_local_key'=>'&refreshLocalKey=1',
        'clear_cache'=>'common/clearCacheRegistry',
        'listings_browse'=>'admin_listings/browse',
        'reviews_browse'=>'admin_reviews/browse',
        'media_browse'=>'admin_media/browse',
        'inquiry_browse'=>'admin_inquiry/browse',
        'claims_moderation'=>'admin_claims/moderation',
        'discussions_moderation'=>'admin_discussions/moderation',
        'listings_moderation'=>'admin_listings/moderation',
        'media_moderation'=>'admin_media/moderation',
        'media_encoding_response'=>'admin_media_upload/response',
        'owner_replies_moderation'=>'admin_owner_replies/moderation',
        'reports_moderation'=>'admin_reports/moderation',
        'reviews_moderation'=>'admin_reviews/moderation',
        'review_filter_listing'=>'admin_reviews/browse&listing_id=%s&extension=com_content',
        'media_create'=>'admin_media_upload/create&amp;%s',
        'license'=>'license',
        'user'=>'index.php?option=com_users&amp;task=user.edit&amp;id=%s'
	);

	function __construct()
	{
		$this->routes['dashboard'] = _CMS_ADMIN_ROUTE_BASE;

		if(!isset($this->Html))
		{
			S2App::import('Helper','html','jreviews');

			$this->Html = ClassRegistry::getClass('HtmlHelper');
		}
	}

	function dashboard()
	{
		return $this->routes[__FUNCTION__];
	}

	function route($path)
	{
		return $this->routes['dashboard'] . ($this->routes[$path] != '' ? '&url=' . $this->routes[$path] : '');
	}

	function refreshLocalKey()
	{
		return $this->dashboard() . '&refresh_key=1';
	}

	function reviewFilterByListing($id)
	{
		return sprintf($this->route('review_filter_listing'), $id);
	}

	function user($title,$user_id,$attributes)
    {
        if($user_id == 0) {
            return '"'.$title.'"';
        }

		$route = $this->routes['user'];

		$url = sprintf($route,$user_id);

        $attributes['sef']=false;

        return $this->Html->link($title,$url,$attributes);

    }

	/**
	 * MEDIA ROUTES HERE
	 */
	function download($media)
	{
		extract($media);

		$m = s2alphaID($media_id,false,5,cmsFramework::getConfig('secret'));

		$session_token = cmsFramework::getToken();

		$integrity_token = cmsFramework::getCustomToken($media_id, $media_type, $filename, $created);

		return "jreviews.media.download('{$m}','{$integrity_token}','{$session_token}');return false;";
	}

	function mediaCreate($listing_id, $review_id, $extension)
	{
        $params = array();

        if($extension == '') $extension = 'com_content';

        if($review_id)
        {
        	$params['id'] = urlencode(base64_encode($listing_id . ':' . $review_id . ':' . $extension));
        }
        else {

        	$params['id'] = urlencode(base64_encode($listing_id . ':' . $extension));
        }

        $query = http_build_query($params);

		return sprintf($this->route('media_create'), $query);
	}

}