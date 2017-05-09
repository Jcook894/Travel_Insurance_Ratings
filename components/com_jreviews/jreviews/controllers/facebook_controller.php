<?php
/**
 * JReviews - Reviews Extension
 * Copyright (C) 2010-2015 ClickFWD LLC
 * This is not free software, do not distribute it.
 * For licencing information visit https://www.jreviews.com
 * or contact sales@jreviews.com
**/

defined( 'MVC_FRAMEWORK') or die( 'Direct Access to this location is not allowed.' );

/*********************************************
 * Facebook PHP SDK v4
 *********************************************/

// if(!class_exists('Facebook\FacebookSession'))
// {
//     S2App::import('Vendor','facebook4' . DS . 'autoload');
// }

// use Facebook\FacebookSession;
// use Facebook\FacebookRequest;
// use Facebook\GraphObject;
// use Facebook\FacebookRequestException;

/*********************************************
 * Facebook PHP SDK v3
 *********************************************/

if(!class_exists('Facebook') && !class_exists('myapiFacebook'))
{
    S2App::import('Vendor','facebook' . DS . 'facebook');
}

class FacebookController extends MyController {

    var $uses = array('menu','criteria','review','vote','media');

    var $helpers = array();

    var $components = array('access','config','everywhere','media_storage');

    var $autoRender = false;

    var $autoLayout = false;

    const SDK_version = 3;

/**
* FB configuration
* You can customize the strings below for the FB messages
*/
    var $activities = array();

    function beforeFilter(){
        # Call beforeFilter of MyController parent class

        parent::beforeFilter();

        $this->activities = array(
            'listing_new'=>JreviewsLocale::getPHP('FB_NEW_LISTING'),
            'review_new'=>JreviewsLocale::getPHP('FB_NEW_REVIEW'),
            'comment_new'=>JreviewsLocale::getPHP('FB_NEW_REVIEW_COMMENT'),
            'vote_helpful'=>JreviewsLocale::getPHP('FB_NEW_HELPFUL_VOTE')
         );
    }

    function getEverywhereModel()
    {
        switch($this->action)
        {
            case '_postListing':
                return false;
            break;
            case '_postReview':
                return $this->Review;
            break;
            case '_postVote':
                return $this->Vote;
            break;
        }
    }

    function makeUrl($url)
    {
        return cmsFramework::makeAbsUrl($url,array('sef'=>true,'ampreplace'=>true));
    }

    /**
     * Proxy method used with requestAction. Used specifically in the PaidListings add-on
     */

    function postListing()
    {
        return $this->_postListing();
    }

    function _postListing()
    {
        # Check if FB integration for reviews is enabled

        $facebook_integration = Sanitize::getBool($this->Config,'facebook_enable') and Sanitize::getBool($this->Config,'facebook_listings');

        if(!$facebook_integration) return;

        $listing_id = Sanitize::getInt($this->params,'id');

        # First check - listing id

        if(!$listing_id) return;

        # Stop form data tampering

        $formToken = cmsFramework::getCustomToken($listing_id);

        if(!cmsFramework::isAdmin() && !$this->__validateToken($formToken))
        {
            return JreviewsLocale::getPHP('ACCESS_DENIED');
        }

        $fb_session = $this->_getFBSession();

        if($fb_session)
        {
            $this->Everywhere->loadListingModel($this,'com_content');

            $listing = $this->Listing->findRow(array(
                'conditions'=>array('Listing.id = ' . $listing_id)
            ),array('afterFind'));

            $listing_url = $this->makeUrl($listing['Listing']['url']);

            $summary = strip_tags(Sanitize::getString($listing['Listing'],'summary'));

            $description = strip_tags(Sanitize::getString($listing['Listing'],'description'));

            $fbArray = array(
                'message'=>$listing['Listing']['title'],
                'link'=>$listing_url,
                'name'=>$listing['Listing']['title'] . ' - ' . cmsFramework::getConfig('sitename'),
                'description' => $summary != '' ? $summary : $description,
                'actions' => json_encode(array('name'=>cmsFramework::getConfig('sitename'),'link'=>WWW_ROOT))
            );

            if($picture = $this->getImageURL($listing))
            {
                $fbArray['picture'] = $picture;
            }

            return $this->_postToFeed($fb_session,$fbArray);
        }

        return false;
    }

    function _postReview()
    {
        # Check if FB integration for reviews is enabled
        $facebook_integration = Sanitize::getBool($this->Config,'facebook_enable') and Sanitize::getBool($this->Config,'facebook_reviews');

        if(!$facebook_integration) return;

        $review_id = Sanitize::getInt($this->params,'id');

        # First check - review id
        if(!$review_id) return '';

        # Stop form data tampering
        $formToken = cmsFramework::getCustomToken($review_id);

        if(!cmsFramework::isAdmin() && !$this->__validateToken($formToken))
        {
            return JreviewsLocale::getPHP('ACCESS_DENIED');
        }

        $fb_session = $this->_getFBSession();

        if($fb_session)
        {
            $review = $this->Review->findRow(array(
                'conditions'=>array('Review.id = ' . $review_id)
            ),array());

            $this->Everywhere->loadListingModel($this,$review['Review']['extension']);

            $listing = $this->Listing->findRow(array(
                'conditions'=>array('Listing.'.$this->Listing->realKey.' = ' . $review['Review']['listing_id'])
            ),array('afterFind'));

            $review_url = $this->getReviewURL($listing, $review);

            $listing_url = $this->makeUrl($listing['Listing']['url']);

            $message = strip_tags($review['Review']['comments']);

			if($message != '' && $this->Config->facebook_posts_trim > 0)
            {
                S2App::import('Helper','text','jreviews');

                $Text = ClassRegistry::getClass('TextHelper');

            	$message = $Text->truncateWords($message, $this->Config->facebook_posts_trim);
            }

            $summary = strip_tags($listing['Listing']['summary']);

            $description = strip_tags($listing['Listing']['description']);

            $fbArray = array(
                'message'=>$message,
                'link'=>$review_url,
                'name'=>$listing['Listing']['title'] . ' - ' . cmsFramework::getConfig('sitename'),
                'description' => $summary != '' ? $summary : $description,
                'actions' => json_encode(array('name'=>cmsFramework::getConfig('sitename'),'link'=>WWW_ROOT))
            );

            if($picture = $this->getImageURL($listing))
            {
                $fbArray['picture'] = $picture;
            }

            return $this->_postToFeed($fb_session,$fbArray);
        }

        return 'No session or no publish permission';
   }

   function _postVote()
   {
        # Check if FB integration for reviews is enabled

        $facebook_integration = Sanitize::getBool($this->Config,'facebook_enable') && Sanitize::getBool($this->Config,'facebook_votes');

        if(!$facebook_integration) return;

        $review_id = Sanitize::getInt($this->params,'id');

        # First check - review id

        if(!$review_id) return;

        # Stop form data tampering

        $formToken = cmsFramework::getCustomToken($review_id);

        if(!cmsFramework::isAdmin() && !$this->__validateToken($formToken))
        {
            return JreviewsLocale::getPHP('ACCESS_DENIED');
        }

        $fb_session = $this->_getFBSession();

        $review = $this->Review->findRow(array(
            'conditions'=>array('Review.id = ' . $review_id)
        ),array());

        $this->Everywhere->loadListingModel($this,$review['Review']['extension']);

        $listing = $this->Listing->findRow(array(
            'conditions'=>array('Listing.'.$this->Listing->realKey.' = ' . $review['Review']['listing_id'])
        ),array('afterFind'));

        $review_url = $this->getReviewURL($listing, $review);

        $listing_url = $this->makeUrl($listing['Listing']['url']);

        $comments = strip_tags($review['Review']['comments']);

        $summary = strip_tags($listing['Listing']['summary']);

        $description = strip_tags($listing['Listing']['summary']);

        if($comments != '')
        {
            $stream_description = $comments;
        }
        elseif($summary != '') {
            $stream_description = $summary;
        }
        elseif($description != '') {
            $stream_description = $description;
        }

        # Publish stream permission granted so we can post on the user's wall!
        # Begin building the stream $fbArray

        $fbArray = array(
            'message'=>'',
            'link'=>$review_url,
            'name'=>$listing['Listing']['title'] . ' - ' . cmsFramework::getConfig('sitename'),
            'description' => $stream_description,
            'caption'=>sprintf($this->activities['vote_helpful'],$listing['Listing']['title']),
            'actions'=>json_encode(array('name'=>cmsFramework::getConfig('sitename'),'link'=>WWW_ROOT))
        );

        if($picture = $this->getImageURL($listing))
        {
            $fbArray['picture'] = $picture;
        }

        if($this->Config->facebook_optout || !$fb_session) {

            $fbArray['method'] = 'stream.publish';

			$fbArray['display'] = Configure::read('System.isMobile') ? 'touch' : 'popup';

            return cmsFramework::jsonResponse($fbArray);
		}
        elseif($fb_session) {

            return $this->_postToFeed($fb_session,$fbArray);
        }

        return false;
   }

   function getReviewURL($listing, $review)
   {
        S2App::import('Helper',array('routes'),'jreviews');

        $Routes = ClassRegistry::getClass('RoutesHelper');

        $Routes->app = 'jreviews';

        $Routes->Config = $this->Config;

        $url = $Routes->reviewDiscuss('',$review,array('listing'=>$listing,'return_url'=>true));

        return $url;
   }

   function getImageURL($listing)
   {
        S2App::import('Helper','media','jreviews');

        $MediaHelper = ClassRegistry::getClass('MediaHelper');

        $MediaHelper->app = 'jreviews';

        $MediaHelper->name = $this->name;

        $MediaHelper->action = $this->action;

        $mainMedia = Sanitize::getVar($listing,'MainMedia');

        $main_media_tn_size = Sanitize::getString($this->Config,'media_detail_main_thumbnail_size');

        $main_media_tn_mode = Sanitize::getString($this->Config,'media_detail_main_thumbnail_mode');

        $image_src = $MediaHelper->thumb($mainMedia,array('size'=>$main_media_tn_size,'mode'=>$main_media_tn_mode, 'return_src'=>true));

        /*
        if($listing['Listing']['extension'] != 'com_content' && isset($listing['Listing']['images']) && $listing['Listing']['images'][0])
        {
            $image_src = $listing['Listing']['images'][0]['path'];
        }
        elseif($listing['Listing']['extension'] == 'com_content' && isset($listing['MainMedia'])) {

            $file_extension = Sanitize::getString($listing['MainMedia'],'file_extension');

            $image_url = Sanitize::getString($listing['MainMedia'],'media_path');

            if($image_url && $file_extension) {

                $image_src = $listing['MainMedia']['media_path'].'.'.$listing['MainMedia']['file_extension'];
            }
        }
        */

        return $image_src;
   }

   /**
    * For use with Facebook PHP SDK v3
    */
   function _getFBClass()
   {
        class_exists('Facebook') and $facebook = new Facebook( array(
            'appId'   => trim(Sanitize::getString($this->Config,'facebook_appid')),
            'secret'  => trim(Sanitize::getString($this->Config,'facebook_secret')),
            'cookie'  => true
        ));

        /* Avoid class conflict with myApi extension */
        class_exists('myapiFacebook') and $facebook = new myapiFacebook( array(
            'appId'   => trim(Sanitize::getString($this->Config,'facebook_appid')),
            'secret'  => trim(Sanitize::getString($this->Config,'facebook_secret')),
            'cookie'  => true
        ));

        return $facebook;
   }

   /**
    * For use with Facebook PHP SDK v4
    * @return [type] [description]
    */
   function _getFBSession()
   {
        $app_id = trim(Sanitize::getString($this->Config,'facebook_appid'));

        $app_secret = trim(Sanitize::getString($this->Config,'facebook_secret'));

        $publish_actions = false;

        $permissions = array();

        switch(self::SDK_version)
        {
            case 4:

                $accessToken = Sanitize::getString($this->params,'accessToken');

                FacebookSession::setDefaultApplication($app_id, $app_secret);

                $session = new FacebookSession($accessToken);

                if($session)
                {
                    $request = new FacebookRequest($session, 'GET', '/me/permissions');

                    $response = $request->execute();

                    $permissions = $response->getGraphObject()->asArray();
                }

            break;

            default:

                $session = new Facebook(array('appId'  => $app_id,'secret' => $app_secret));

                $user = $session->getUser();

                if($user)
                {
                    try {
                        $permissions = $session->api('/me/permissions');

                        $permissions = $permissions['data'];
                    }
                    catch (FacebookApiException $e) {
                    }
                }

            break;
        }

        foreach($permissions AS $perm)
        {
            $perm = (array) $perm;

            if($perm['permission'] == 'publish_actions' && $perm['status'] == 'granted')
            {
                $publish_actions = true; break;
            }
        }

        if(!$publish_actions)
        {
                return false;
        }

        return $session;
    }

    function _postToFeed($session, $data)
    {
        switch(self::SDK_version)
        {
            case 4:

                try {

                    $fb = new FacebookRequest($session, 'POST', '/me/feed', $data);

                    $response = $fb->execute()->getGraphObject();

                    return '1';
                }
                catch(FacebookRequestException $e) {

                    return sprintf("Exception occured, code: %s with message: %s",$e->getCode(),$e->getMessage());
                }

            break;

            default:

                try {

                    $response = $session->api('/me/feed','post', $data);

                    return '1';
                }
                catch(FacebookRequestException $e) {

                    return sprintf("Exception occured, code: %s with message: %s",$e->getCode(),$e->getMessage());
                }

            break;
        }
    }
}