<?php
/**
 * JReviews - Reviews Extension
 * Copyright (C) 2010-2015 ClickFWD LLC
 * This is not free software, do not distribute it.
 * For licencing information visit https://www.jreviews.com
 * or contact sales@jreviews.com
**/

defined( 'MVC_FRAMEWORK') or die( 'Direct Access to this location is not allowed.' );

use ClickFWD\Bitly\Bitly;

class TwitterComponent extends S2Component {

    var $plugin_order = 100;

    var $name = 'twitter';

    var $published = false;

/**
* Plugin configuration
*/
    var $tweet_new_listing = false;
    var $tweet_new_review = false;
    var $tweet_new_discussion = false;

    var $activities = array();

    function startup(&$controller)
    {
        $this->c = & $controller;

        $this->inAdmin = defined('MVC_FRAMEWORK_ADMIN');

        $this->published = Sanitize::getBool($this->c->Config,'twitter_enable');

        if($this->published)
        {
            if(version_compare( PHP_VERSION, 5.5, '>='))
            {
                S2App::import('Vendor','twitter' . DS . 'autoload');
            }
            else {

                S2App::import('Vendor','twitter-old' . DS . 'twitteroauth');
            }
        }

        $this->tweet_new_listing = Sanitize::getBool($this->c->Config,'twitter_listings');

        $this->tweet_new_review = Sanitize::getBool($this->c->Config,'twitter_reviews');

        $this->tweet_new_discussion = Sanitize::getBool($this->c->Config,'twitter_discussions');

        $this->tweet_new_photo = Sanitize::getBool($this->c->Config,'twitter_photos');

        S2App::import('Helper',array('routes','html'),'jreviews');

        $this->Routes = ClassRegistry::getClass('RoutesHelper');

        $this->Html = ClassRegistry::getClass('HtmlHelper');

        /**
        * Tweets configuration
        * You can customize the strings below for the Twitter messages
        */
        $this->activities = array(
            'listing_new'=>__t("Listing: %1\$s. %2\$s",true), //#1 category title, #2 listing title
            'review_new'=>__t("Review for: %1\$s. %2\$s",true), //#1 listing title, #2 review title
            'comment_new'=>__t("Discussion on: %1\$s. %2\$s",true), //#1 listing title, #2 comment
            'listing_new_photo'=>__t("New photo for %1\$s",true), //#1 listing title
            'review_new_photo'=>__t("New review photo for %1\$s",true) //#1 listing title
        );
    }

    function plgAfterSave(&$model)
    {
        if($this->inAdmin && !in_array($this->c->action,array('_saveModeration','_save'))) {
            return;
        }

        switch($model->name)
        {
            case 'Discussion':
                if(Sanitize::getInt($model->data['Discussion'],'approved') && $this->tweet_new_discussion)
                {
                    $this->_plgDiscussionAfterSave($model);
                }
            break;

            case 'Listing':
                if(Sanitize::getInt($model->data['Listing'],'state') && $this->tweet_new_listing)
                {
                    $this->_plgListingAfterSave($model);
                }
            break;

            case 'Review':
                if(Sanitize::getInt($model->data['Review'],'published') == 1 && $this->tweet_new_review)
                {
                    $this->_plgReviewAfterSave($model);
                }
            break;

            case 'Media':

                $phpVersionCheck = version_compare( PHP_VERSION, 5.5, '>=');

                if($phpVersionCheck && Sanitize::getBool($model->data,'finished') && Sanitize::getInt($model->data['Media'],'published') == 1 && Sanitize::getInt($model->data['Media'],'approved') == 1 && $this->tweet_new_photo)
                {
                    $this->_plgMediaAfterSave($model);
                }
            break;
        }
    }

    function _plgListingAfterSave(&$model)
    {
        $tweet = '';

        /**
        * Run the query only if necessary. Then set it in the
        * controller (viewVars) to make it available in other plugins
        */
        $listing = $this->_getListing($model);

        // Treat moderated listings as new
        $this->inAdmin and Sanitize::getBool($model->data,'moderation') and $model->isNew = true;

        // Limit running this for new listings. Not deletion of images or other listing actions.
        /**
        * Publish activity to Twitter
        */

        $offset = _CMS_NAME == 'wordpress'  ? get_option('gmt_offset') * 3600 : 0;

        if(isset($model->isNew)
            && $model->isNew && $listing['Listing']['state'] == 1
            && ($listing['Listing']['modified'] == NULL_DATE
                    ||
                    // For WP, the modified date is also filled out on new listings and there's a slight offset with the created date
                    // We use the timezone offset to UTC to reduce the difference between created and modified
                    // Probably need to look into the EverywhereComContent model to reduce this difference
                    (strtotime($listing['Listing']['modified']) - strtotime($listing['Listing']['created'])) <= 10 + $offset /*seconds*/
                )
            )
        {
            $this->c->Config->override($listing['ListingType']['config']);

            $tweet = sprintf(__t($this->activities['listing_new'],true),$listing['Category']['title'],$listing['Listing']['title']);

            $url = $this->Routes->content('',$listing,array('return_url'=>true));

            $url = $this->shortenUrl($url);

            if($tweet!='')
            {
                $this->sendTweet($this->truncateTweet($tweet,$url));
            }
        }
    }

    function _plgReviewAfterSave(&$model)
    {
        $tweet = '';

        /**
        * Run the query only if necessary. Then set it in the
        * controller (viewVars) to make it available in other plugins
        */
        $review = $this->_getReview($model, $model->data['Review']['id']);

        // Treat moderated reviews as new
        $this->inAdmin and Sanitize::getBool($model->data,'moderation') and $model->isNew = true;

        /**
        * Publish activity to Twitter
        */
        $extension = $review['Listing']['extension'];

        if(isset($model->isNew) &&
            $model->isNew && $review['Review']['published'] == 1
            && $review['Review']['modified'] == NULL_DATE
            && ($extension != 'com_content' || ($extension == 'com_content' && $review['Listing']['state'] == 1)))
        {
            $this->c->Config->override($review['ListingType']['config']);

            $reviewTitle = $review['Review']['title'];

            $reviewComments = strip_tags($review['Review']['comments']);

            if($reviewTitle != '' && $reviewComments != '')
            {
                $reviewText = $reviewTitle . ', ' . $reviewComments;
            }
            elseif($reviewTitle!= '' && $reviewComments) {

                $reviewText = $reviewTitle;
            }
            elseif($reviewComments) {

                $reviewText = $reviewComments;
            }

            $tweet = sprintf(__t($this->activities['review_new'],true),$review['Listing']['title'], $reviewText);

            $url = $this->Html->sefLink($review['Listing']['title'],$review['Listing']['url'],array('return_url'=>true));

            $url = $this->shortenUrl($url);

            if($tweet != '') $this->sendTweet($this->truncateTweet($tweet,$url));
        }
    }

    function _plgDiscussionAfterSave(&$model)
    {
        $tweet = '';

        /**
        * Run the query only if necessary. Then set it in the
        * controller (viewVars) to make it available in other plugins
        */
        $post = $this->_getReviewPost($model);

        $listing = $this->_getListingEverywhere($post['Listing']['listing_id'],$post['Listing']['extension']);

        // Treat moderated reviews as new
        $this->inAdmin and Sanitize::getBool($model->data,'moderation') and $model->isNew = true;

        /**
        * Publish activity to Twitter
        */
        if(isset($model->isNew) && $model->isNew && $post['Discussion']['approved'] == 1)
        {
            $this->c->Config->override($listing['ListingType']['config']);

            $tweet = sprintf(__t($this->activities['comment_new'],true),$listing['Listing']['title'], strip_tags($post['Discussion']['text']));

            $url = $this->Routes->reviewDiscuss(__t("review",true),$post,array('listing'=>$listing,'return_url'=>true));

            $url = $this->shortenUrl($url);

            if($tweet!='') $this->sendTweet($this->truncateTweet($tweet,$url));
        }
    }

    function _plgMediaAfterSave(&$model)
    {
        $mediaTmp = Sanitize::getVar($model->data, 'Media');

        $mediaId = Sanitize::getInt($mediaTmp, 'media_id');

        $media = $model->findRow(array('conditions' => array('Media.media_id = ' . $mediaId)));

        if ($media['Media']['media_type'] != 'photo')
        {
            return false;
        }

        $listingId = Sanitize::getInt($media['Media'], 'listing_id');

        $reviewId = Sanitize::getInt($media['Media'], 'review_id');

        $path = Sanitize::getString($media['Media']['media_info']['image'], 'url');

        $listing = $this->_getListing($model);

        $this->c->Config->override($listing['ListingType']['config']);

        if($listingId && !$reviewId)
        {
            $publish_up = cmsFramework::dateLocalToUTC($listing['Listing']['publish_up']);

            if(!$listing['Listing']['state'] || strtotime($publish_up) > strtotime(_END_OF_TODAY)) return;

            $tweet = sprintf(__t($this->activities['listing_new_photo'],true), $listing['Listing']['title']);

            $url = $this->Routes->content('',$listing,array('return_url'=>true));

            $url = $this->shortenUrl($url);

            $message = $this->truncateTweet($tweet, $url);

        }
        elseif($reviewId) {

            $review = $this->_getReview($model, $reviewId);

            if(!$review['Review']['published']) return;

            $tweet = sprintf(__t($this->activities['review_new_photo'],true), $listing['Listing']['title']);

            $url = $this->Routes->reviewDiscuss('', $review, array('listing'=>$listing,'return_url'=>true));

            $url = $this->shortenUrl($url);

            $message = $this->truncateTweet($tweet, $url);
        }

        $this->sendPhotoTweet($message, $path);
    }

    /*
    * Function is not tested yet. May need to update the twitter library used
    */

    function sendPhotoTweet($message, $path)
    {
        $twitter_oauth = $this->c->Config->twitter_oauth;

        $twitter_oauth = $this->c->Config->twitter_oauth;

        $connection = new \Abraham\TwitterOAuth\TwitterOAuth(trim($twitter_oauth['key']) ,trim($twitter_oauth['secret']), trim($twitter_oauth['token']), trim($twitter_oauth['tokensecret']));

        $media1 = $connection->upload('media/upload', array('media' => $path) );

        $parameters = array(
            'status' => $message,
            'media_ids' => implode(',', array($media1->media_id_string) ),
        );

        $result = $connection->post('statuses/update', $parameters);

        $newApi = method_exists($connection, 'getLastHttpCode');

        if ( ($newApi && $connection->getLastHttpCode() != 200) || (!$newApi && $connection->http_code != 200) ) {
            // There was an error
            return false;
        }

        return true;
    }

    function sendTweet($message)
    {
        $twitter_oauth = $this->c->Config->twitter_oauth;

        if(version_compare( PHP_VERSION, 5.5, '>='))
        {
            $connection = new \Abraham\TwitterOAuth\TwitterOAuth(trim($twitter_oauth['key']) ,trim($twitter_oauth['secret']), trim($twitter_oauth['token']), trim($twitter_oauth['tokensecret']));
        }
        else {
            $connection = new TwitterOAuth(trim($twitter_oauth['key']) ,trim($twitter_oauth['secret']), trim($twitter_oauth['token']), trim($twitter_oauth['tokensecret']));
        }

        $connection->post('statuses/update', array('status' => $message));

        $newApi = method_exists($connection, 'getLastHttpCode');

        if ( ($newApi && $connection->getLastHttpCode() != 200) || (!$newApi && $connection->http_code != 200) ) {
            // There was an error
            return false;
        }

        return true;
    }

    function shortenUrl($url)
    {
        $url = cmsFramework::makeAbsUrl($url,array('sef'=>false,'ampreplace'=>true));

        $token = trim(Sanitize::getString($this->c->Config,'bitly_access_token'));

        if($token != '')
        {
            return $this->shortenUrlV3($url, $token);
        }
        else {

            $user = trim(Sanitize::getString($this->c->Config,'bitly_user'));

            $key = trim(Sanitize::getString($this->c->Config,'bitly_key'));

            if($user != '' && $key != '')
            {
                return $this->shortenUrlV2($url, $user, $key);
            }
        }

        return $url;
    }

    function shortenUrlV3($url, $token)
    {
        $bitly = new Bitly;

        $response = $bitly->setAccessToken($token)->shorten($url);

        if($response['status'] == '200')
        {
            return $response['data']['url'];
        }
        else {
            return $url;
        }
    }

    function shortenUrlV2($url, $user, $key)
    {
        $url = cmsFramework::makeAbsUrl($url, array('sef'=>false,'ampreplace'=>true));

        $bitly = 'http://api.bit.ly/shorten';

        $param = 'version=2.0.1&longUrl='.urlencode($url).'&login='.$user.'&apiKey='.$key.'&format=json&history=1';

        //get the url

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $bitly . "?" . $param);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

        $response = curl_exec($ch);

        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        curl_close($ch);

        if ($httpcode != 200)
        {
            return $url;
        }

        $json = @json_decode($response,true);

        return $json['results'][$url]['shortUrl'];
    }

    function truncateTweet($text,$url)
    {
        $max_chars = 140;

        S2App::import('Helper','text');

        $TextHelper = ClassRegistry::getClass('TextHelper');

        $truncate = strlen($url) + 1 ; // +1 for space in between text and url

        if($hashtag = Sanitize::getString($this->c->Config, 'twitter_hashtag'))
        {
            $truncate += strlen($hashtag) + 1;
        }

        $text = $TextHelper->truncate($text, $max_chars - $truncate, '…');

        if($hashtag)
        {
             $text .= ' ' . $hashtag;
        }

        $text .= ' ' . $url;

        return $text;
    }

    function _getListing(&$model)
    {
        if(isset($this->c->viewVars['listing']))
        {
            $listing = $this->c->viewVars['listing'];
        }
        else {

            $listing_id = isset($model->data['Listing']) ? Sanitize::getInt($model->data['Listing'],'id') : false;

            $listing_id = isset($this->c->data['Listing']) ? Sanitize::getInt($this->c->data['Listing'],'id') : false;

            !$listing_id and $listing_id = Sanitize::getInt($this->c->data, 'listing_id');

            if(!$listing_id  && isset($model->data['Media']))
            {
                $listing_id = Sanitize::getInt($model->data['Media'], 'listing_id');
            }

            if(!$listing_id) return false;

            $listing = $this->c->Listing->findRow(array('conditions'=>array('Listing.id = '. $listing_id)),array('afterFind' /* Only need menu id */));

            $this->c->set('listing',$listing);
        }

        if(isset($model->data['Listing']) && Sanitize::getInt($model->data['Listing'],'state'))
        {
            $listing['Listing']['state'] =  $model->data['Listing']['state'];
        }

        return $listing;
    }

    function _getListingEverywhere($listing_id,$extension)
    {
        if(isset($this->c->viewVars['listing_'.$extension]))
        {
           $listing = $this->c->viewVars['listing_'.$extension];
        }
        else
        {
            // Automagically load and initialize Everywhere Model
            S2App::import('Model','everywhere_'.$extension,'jreviews');

            $class_name = inflector::camelize('everywhere_'.$extension).'Model';

            if(class_exists($class_name)) {

                $ListingModel = new $class_name();

                $ListingModel->addRunAfterFindModel(array('Media'));

                $listing = $ListingModel->findRow(array('conditions'=>array('Listing.'.$ListingModel->realKey.' = ' . $listing_id)));

                $this->c->set('listing_'.$extension,$listing);
            }
        }
        return $listing;
    }

    function _getReview(&$model, $reviewId = null)
    {
        $fields = $joins = array();

        !class_exists('ReviewModel') and S2App::import('Model','review','jreviews');

        $ReviewModel = $model->name != 'Review' ? ClassRegistry::getClass('ReviewModel') : $model;

        if(isset($this->c->viewVars['review']))
        {
            $review = $this->c->viewVars['review'];
        }
        elseif(isset($this->c->viewVars['reviews']))
        {
            $review = current($this->c->viewVars['reviews']);
        }
        else
        {
            $joins = array();

            if(!isset($ReviewModel->joins['Listing']) && isset($this->c->Listing)) {

                $joins = $this->c->Listing->joinsReviews;
            }

            $reviewId = $reviewId ?: $model->data['Review']['id'];

             // Triggers the afterFind in the Observer Model

            $this->c->EverywhereAfterFind = true;

            $review = $ReviewModel->findRow(array(
                'conditions'=>'Review.id = ' . $reviewId,
                'joins'=>$joins
                ), array('plgAfterFind' /* limit callbacks */)
            );

            $this->c->set('review',$review);
        }

        return $review;
    }

    function _getReviewPost(&$model)
    {
        if(isset($this->c->viewVars['post']))
        {
            $post = $this->c->viewVars['post'];
        }
        else
        {
            $post = $model->findRow(array(
                'conditions'=>array(
                    'Discussion.type = "review"',
                    'Discussion.discussion_id = ' . $model->data['Discussion']['discussion_id']
                    ))
            );
            $this->c->set('post',$post);
        }
        return $post;
    }
}
