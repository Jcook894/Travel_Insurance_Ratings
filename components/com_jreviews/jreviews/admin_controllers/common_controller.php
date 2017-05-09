<?php
/**
 * JReviews - Reviews Extension
 * Copyright (C) 2010-2015 ClickFWD LLC
 * This is not free software, do not distribute it.
 * For licencing information visit https://www.jreviews.com
 * or contact sales@jreviews.com
**/

// no direct access
defined( 'MVC_FRAMEWORK') or die( 'Direct Access to this location is not allowed.' );

class CommonController extends MyController {

	var $uses = array('review','field','media');

    var $helpers = array('text');

    var $components = array('config', 'everywhere');

    var $autoLayout = false;

    var $autoRender = false;

    function feed()
    {
        if(function_exists('curl_init'))
        {
            if(!class_exists('SimplePie')) {
                S2App::import('Vendor','simplepie/simplepie.inc');
            }

            $feedUrl = "https://forum.jreviews.com/index.php?app=core&module=global&section=rss&type=forums&id=1";

            $feed = new SimplePie();

            $feed->set_feed_url($feedUrl);

            $feed->enable_cache(true);

            $feed->set_cache_location(cmsFramework::getConfig('cache_path'));

            $feed->set_cache_duration(3600);

            $feed->init();

            $feed->handle_content_type();

            $items = $feed->get_items();

            $this->set('items',$items);

            $page = $this->render('about','feed');

        } else {

            $page = 'News feed requires curl';
        }

        echo $page;
    }

    function getStats() {

        # BEGIN STATS COLLECTION
        $stats = array();

        $Model = new MyModel;

        // Usage and setup stats
        $query = "
            SELECT
                count(*)
            FROM
                #__jreviews_categories
            WHERE
                `option` = 'com_content'
        ";

        $categories = (int) $Model->query($query, 'loadResult');

        $stats['categories'] = s2_num_format($categories, 0);

        $listings = $this->Listing->getPublishedCount();

        $stats['listings'] = s2_num_format($listings, 0);

        $query = "
            SELECT
                count(*)
            FROM
                #__jreviews_comments AS review
            WHERE
                review.pid > 0 AND review.author = '0' AND review.published = 1"
            . (@!$this->EverywhereAddon ? "\n AND review.`mode`='com_content'" : '');

        $user_reviews = $Model->query($query, 'loadResult');

        $stats['user-reviews'] = s2_num_format($user_reviews, 0);

        $query = "
            SELECT
                count(*)
            FROM
                #__jreviews_comments AS review
            WHERE
                review.pid > 0 AND review.author = '1' AND review.published = 1"
            . (@!$this->EverywhereAddon ? "\n AND review.`mode`='com_content'" : '');

        $editor_reviews = $Model->query($query, 'loadResult');

        $stats['editor-reviews'] = s2_num_format($editor_reviews, 0);

        $query = "
            SELECT
                count(*)
            FROM
                #__jreviews_groups";

        $groups = $Model->query($query, 'loadResult');

        $stats['groups'] = s2_num_format($groups, 0);

        $query = "
            SELECT
                count(*)
            FROM
                #__jreviews_fields";

        $fields = $Model->query($query, 'loadResult');

        $stats['fields'] = s2_num_format($fields, 0);

        // Media stats
        $query = "
            SELECT
                COUNT(*) AS count, media_type
             FROM
                #__jreviews_media
             WHERE
                approved = 1 AND published = 1
            GROUP BY
                media_type
        ";

        $media_counts = $Model->query($query, 'loadAssocList');

        if(!empty($media_counts))
        {
            foreach($media_counts AS $media) {

                $stats[$media['media_type']] = s2_num_format((int)$media['count'], 0);

            }
        }

        return cmsFramework::jsonResponse($stats);
    }

    function getVersion()
    {
        $response = array('isNew'=>false, 'version'=>'');

        $session_var = cmsFramework::getSessionVar('new_version','jreviews');

        if(1 == 1 || empty($session_var))
        {
            $localVersion = strip_tags($this->Config->version);

            $version_parts = explode('.',$localVersion);

            $majorVersion = $version_parts[0] . $version_parts[1];

            // Version checker

            $updates_server = 'https://www.jreviews.com/updates_server/';

            $curl_handle = curl_init($updates_server . $majorVersion . '/files.php?jreviewsversion=' . $localVersion);

            curl_setopt($curl_handle, CURLOPT_RETURNTRANSFER, 1); // return instead of echo

            @curl_setopt($curl_handle, CURLOPT_FOLLOWLOCATION, 1);

            curl_setopt($curl_handle, CURLOPT_HEADER, 0);

            $data = curl_exec($curl_handle);

            curl_close($curl_handle);

            $current_versions = json_decode($data,true);

            if($this->Config->updater_betas && isset($current_versions['jreviews']['beta']))
            {
                $current_versions['jreviews'] = array_merge($current_versions['components']['jreviews'],$current_versions['components']['jreviews']['beta']);
            }

            $remoteVersion = $current_versions['components']['jreviews'][_CMS_NAME]['version'];

			if(AdminPackagesComponent::paddedVersion($localVersion) < AdminPackagesComponent::paddedVersion($remoteVersion)) {

                $response['isNew'] = true;
            }

            $response['version'] = $remoteVersion;

            cmsFramework::setSessionVar('new_version',$response,'jreviews');
        }
        else {

            $response = $session_var;
        }

        return cmsFramework::jsonResponse($response);
    }

	function toggleState()
    {
        $response = array('success'=>false,'str'=>array());

        $Model = new MyModel;

		$id = Sanitize::getInt($this->params,'id');

        $key = Sanitize::getString($this->params,'key');

        $field = Sanitize::getString($this->params,'state');

        $clear_registry = Sanitize::getInt($this->params,'clearRegistry');

        $object_type = Sanitize::getString($this->params,'object_type');

        switch($object_type) {

            case 'fieldoption':
                $table = '#__jreviews_fieldoptions';
            break;
            case 'field':
                $table = '#__jreviews_fields';
            break;
            case 'media':
                $table = '#__jreviews_media';
            break;
            default:
                $table = '#__jreviews_' . $object_type;
            break;
        }

		if(!$id || !$table) return cmsFramework::jsonResponse($response);

		$query = "SELECT `$field` FROM `$table` WHERE $key = '$id'";

		$state = $Model->query($query, 'loadResult');

		$state = $state ? 0 : 1;

		$query = "UPDATE `$table` SET `$field` = '$state' WHERE $key = '$id'";

		if(!$Model->query($query)){

		    cmsFramework::jsonResponse($response);
        }

        // Clear cache
        clearCache('', 'views');

        clearCache('', '__data');

        if($clear_registry)
        {
            clearCache(S2CacheKey('jreviews_paths'), 'core', '');
        }

        $response['success'] = true;

        $response['state'] = $state;

        return cmsFramework::jsonResponse($response);
	}

    function loadView() {

        $folder = Sanitize::getString($this->params,'folder');

        $view = Sanitize::getString($this->params,'view');

        return $this->render($folder,$view);
    }

	function _rebuildReviewerRanks()
    {
        return $this->Review->rebuildRanksTable() ?
            JreviewsLocale::getPHP('REVIEWER_RANKS_REBUILT') :
			JreviewsLocale::getPHP('PROCESS_REQUEST_ERROR');
    }

    function _rebuildMediaCounts()
    {
        $listings = $this->Media->updateListingCounts();

        $reviews = $this->Media->updateReviewCounts();

        echo $listings && $reviews ?
            JreviewsLocale::getPHP('MEDIA_COUNTS_UPDATE') :
            JreviewsLocale::getPHP('PROCESS_REQUEST_ERROR');
    }

	function clearCacheRegistry()
    {
        cmsFramework::clearSessionVar('Listing', 'findCount');
        cmsFramework::clearSessionVar('Review', 'findCount');
        cmsFramework::clearSessionVar('Discussion', 'findCount');
        cmsFramework::clearSessionVar('Media', 'findCount');

		clearCache('', 'views');
		clearCache('', '__data');
        clearCache('', 'menu');
        clearCache('', 'core');

        return JreviewsLocale::getPHP('CACHE_REGISTRY_CLEARED');
	}
}