<?php
/**
 * JReviews - Reviews Extension
 * Copyright (C) 2010-2015 ClickFWD LLC
 * This is not free software, do not distribute it.
 * For licencing information visit https://www.jreviews.com
 * or contact sales@jreviews.com
**/

defined( 'MVC_FRAMEWORK') or die( 'Direct Access to this location is not allowed.' );

class EasysocialComponent extends S2Component {

    var $plugin_order = 100;

    var $name = 'easysocial';

    var $title = 'EasySocial';

    var $plugin_type = 'profile';

    var $published = true;

    var $api_actions = false;

    var $activities = array(); // Defined below to use the translation function

    var $inAdmin = false;

    var $trim_words = 75;

    var $tn_mode;

    var $tn_size;

    var $aggregation_period = 15;

    var $_API_PATH;

    public static function addPreviewAttributes($userId)
    {
        $attributes = array();

        $attributes['data-popbox'] = 'module://easysocial/profile/popbox';

        $attributes['data-user-id'] = $userId;

        return $attributes;
    }

    function startup(&$controller)
    {
        $this->inAdmin = defined('MVC_FRAMEWORK_ADMIN');

        $this->c = & $controller;

        $this->_API_PATH = JPATH_ROOT . '/administrator/components/com_easysocial/includes/foundry.php';

        $this->tn_mode = $this->c->Config->jomsocial_tnmode;

        $this->tn_size = $this->c->Config->jomsocial_tnsize;

        if(file_exists($this->_API_PATH) && $this->c->Config->community == $this->name)
        {
            $this->api_actions = true;

            S2App::import('Helper',array('routes','html','media','text'),'jreviews');

            $this->Routes = ClassRegistry::getClass('RoutesHelper');

            $this->Routes->app = 'jreviews';

            isset($controller->Config) and $this->Routes->Config = $controller->Config;;

            $this->Html = ClassRegistry::getClass('HtmlHelper');

            $this->Html->app = 'jreviews';

            $this->Media = ClassRegistry::getClass('MediaHelper');

            isset($controller->Config) and $this->Media->Config = $controller->Config;

            $this->Media->app = 'jreviews';

            $this->Media->name = $controller->name;

            $this->Media->action = $controller->action;

            $this->Text = ClassRegistry::getClass('TextHelper');
        }
        else {

            $this->published = false;
        }
    }

    function plgAfterSave(&$model)
    {
        appLogMessage('**** BEGIN EasySocial Plugin AfterSave', 'database');

        if($this->inAdmin && !in_array($this->c->action,array('_saveModeration','_save'))) {

            return;
        }

        // Load the EasySocial API

        require_once($this->_API_PATH);

        $this->aggregation_period = ES::config()->get('stream.aggregation.duration');

        switch($model->name)
        {
            case 'Discussion':
                $this->_plgDiscussionAfterSave($model);
            break;

            case 'Favorite':
                $this->_plgFavoriteAfterSave($model);
            break;

            case 'Listing':
                $this->_plgListingAfterSave($model);
            break;

            case 'Media':
                $this->_plgMediaAfterSave($model);
            break;

            case 'MediaLike':
                $this->_plgMediaLikeAfterSave($model);
            break;

            case 'Review':
                $this->_plgReviewAfterSave($model);
            break;

            case 'Vote':
                $this->_plgVoteAfterSave($model);
            break;
        }
    }

    function plgBeforeDelete(&$model)
    {
        // Load the EasySocial API

        require_once($this->_API_PATH);

        switch($model->name)
        {
            case 'Discussion':
                $this->_plgDiscussionBeforeDelete($model);
            break;

            case 'Listing':
                $this->_plgListingBeforeDelete($model);
            break;

            case 'Media':
                $this->_plgMediaBeforeDelete($model);
            break;

            case 'Review':
                $this->_plgReviewBeforeDelete($model);
            break;
        }
    }

    function _plgDiscussionBeforeDelete(&$model)
    {
        $post_id = Sanitize::getInt($model->data,'post_id');

        !$post_id and $post_id = Sanitize::getInt($model->data['Discussion'],'discussion_id');

        // Get the post before deleting to make the info available in plugin callback functions
        $post = $model->findRow(array('conditions'=>array('Discussion.discussion_id = ' . $post_id)),array());

        // Delete activity

        ES::stream()->delete($post_id , 'jreviews-discussion');

        // Deduct points

        if($this->api_actions && $post['Discussion']['user_id'] > 0  && $post['Discussion']['approved'] == 1)
        {
            $this->processPoints('discussion.delete',$post['Discussion']['user_id']);
        }
    }

    function _plgDiscussionAfterSave(&$model)
    {
        $stream = Sanitize::getInt($this->c->Config,'jomsocial_discussions');

        if($stream || $this->api_actions)
        {
            $post = $this->_getReviewPost($model);
        }

        if($stream)
        {
            // Treat moderated reviews as new
            $this->inAdmin and Sanitize::getBool($model->data,'moderation') and $model->isNew = true;

            if($stream == 1 && (!isset($model->isNew) || !$model->isNew)) return; // Don't run for edits

            if($stream == 1 && $post['Discussion']['modified'] != NULL_DATE) return; // Don't run for edits

            if($stream == 2 && (!isset($model->isNew) || !$model->isNew) && $this->c->_user->id != $post['User']['user_id']) return; // Don't run for edits by users other than the owner of this post

            if(isset($model->isNew) && $post['Discussion']['approved'] == 1)
            {
                $listing = $this->_getListingEverywhere($post['Listing']['listing_id'],$post['Listing']['extension']);

                $extension = $post['Listing']['extension'];

                $review_url = $this->Routes->reviewDiscuss('',$post,array('listing'=>$listing,'return_url'=>true));

                $thumb_src = $this->getActivityThumb($listing);

                $act = array(
                    'context_id'=>$post['Discussion']['discussion_id'],
                    'context'=>'jreviews-discussion',
                    'actor'=>$post['User']['user_id'],
                    'target'=>$post['Review']['user_id'],
                    'title'=>'',
                    'content'=>$post['Discussion']['text'],
                    'verb'=>$model->isNew && $post['Discussion']['modified'] == NULL_DATE ? 'new' : 'edit',
                    'type'=>'full'
                );

                $params = array(
                    'discussion_id'=>$post['Discussion']['discussion_id'],
                    'listing_id'=>$listing['Listing']['listing_id'],
                    'review_id'=>$post['Discussion']['review_id'],
                    'extension'=>$extension,
                    'listing_title'=>$listing['Listing']['title'],
                    'listing_url'=>$listing['Listing']['url'],
                    'review_url'=>$review_url,
                    'thumb_src'=>$thumb_src
                );

                $this->streamPost($act, $params);
            }
        }

        if($this->api_actions && $model->isNew && $post['Discussion']['approved'] == 1)
        {
            $this->processPoints('discussion.add',$post['User']['user_id']);

            $award_text = __t("Commented on a review",true);

            $this->awardBadge('review.discuss.low',$post['User']['user_id'], $award_text);

            $this->awardBadge('review.discuss.medium',$post['User']['user_id'], $award_text);

            $this->awardBadge('review.discuss.high',$post['User']['user_id'], $award_text);
        }
    }

    function _plgFavoriteAfterSave(&$model)
    {
        if($stream = Sanitize::getInt($this->c->Config,'jomsocial_favorites'))
        {
            $listing = $this->_getListing($model);

            $listing_link = $this->Routes->content($listing['Listing']['title'],$listing);

            $thumb_src = $this->getActivityThumb($listing);

           if($stream == 1 && $this->c->action == '_favoritesDelete') return; // Don't run for removals

            $act = array(
                'context_id'=>$listing['Listing']['listing_id'],
                'context'=>'jreviews-favorite',
                'actor'=>$this->c->_user->id,
                'target'=>0,
                'title'=>$listing['Listing']['title'],
                'content'=>$listing['Listing']['summary'] . ' ' . $listing['Listing']['description'],
                'verb'=>$this->c->action == '_favoritesDelete' ? 'remove' : 'add',
                'type'=>'full'
            );

            $params = array(
                'listing_id'=>$listing['Listing']['listing_id'],
                'listing_url'=>$listing['Listing']['url'],
                'thumb_src'=>$thumb_src
            );

            $this->streamPost($act, $params);
        }

        if($this->api_actions && $listing['Listing']['state'] == 1)
        {
            $award_text = __t("Added listing to favorites",true);

            $this->awardBadge('favorite.add.low',$listing['User']['user_id'], $award_text);

            $this->awardBadge('favorite.add.medium',$listing['User']['user_id'], $award_text);

            $this->awardBadge('favorite.add.high',$listing['User']['user_id'], $award_text);
        }
    }

    function _plgListingBeforeDelete(&$model)
    {
        if($listing = $this->_getListing($model))
        {
            // Delete activity

            ES::stream()->delete($listing['Listing']['listing_id'] , 'jreviews-listing');

            // Deduct Points

			if($this->api_actions && $listing['Listing']['user_id'] > 0 && $listing['Listing']['state'] == 1 /*&& !$this->_isPaidListing($listing)*/ )
            {
                $this->processPoints('listing.delete',$listing['User']['user_id']);
            }
        }
    }

    function _plgListingAfterSave(&$model)
    {
        $stream = Sanitize::getInt($this->c->Config,'jomsocial_listings');

        $skip_points = Sanitize::getBool($model->data,'skip_points',false);

        if($stream || $this->api_actions)
        {
            $listing = $this->_getListing($model);
        }

        if($stream)
        {
            $modified = strtotime(Sanitize::getString($listing['Listing'],'modified'));

            $created = strtotime(Sanitize::getString($listing['Listing'],'created'));

            // Treat moderated listings as new
            $this->inAdmin and Sanitize::getBool($model->data,'moderation') and $model->isNew = true;

            $publish_up = cmsFramework::dateLocalToUTC($listing['Listing']['publish_up']);

            if($stream == 1 && (!isset($model->isNew) || !$model->isNew)) return; // Don't run for edits

            if($stream == 1 && $modified > $created) return; // Don't run for edits

            if($stream == 2 && (!isset($model->isNew) || !$model->isNew) && $this->c->_user->id != $listing['User']['user_id']) return; // Don't run for edits by users other than the owner of this post

            if(isset($model->isNew) && $listing['Listing']['state'] == 1 && strtotime($publish_up) <= strtotime(_END_OF_TODAY))
            {
                $thumb_src = $this->getActivityThumb($listing);

                $act = array(
                    'context_id'=>$listing['Listing']['listing_id'],
                    'context'=>'jreviews-listing',
                    'actor'=>$listing['User']['user_id'],
                    'target'=>0,
                    'title'=>$listing['Listing']['title'],
                    'content'=>$listing['Listing']['summary'] . ' ' . $listing['Listing']['description'],
                    'verb'=>$model->isNew ? 'new' : 'edit',
                    'type'=>'full'
                );

                $params = array(
                    'cat_title'=>$listing['Category']['title'],
                    'listing_url'=>$listing['Listing']['url'],
                    'thumb_src'=>$thumb_src
                );

                $this->streamPost($act, $params);
            }
        }

		if(!$skip_points && $this->api_actions && isset($model->isNew) && $model->isNew && $listing['Listing']['state'] == 1 /*&& !$this->_isPaidListing($listing)*/)
        {
            $this->processPoints('listing.add',$listing['User']['user_id']);

            $award_text = __t("Submitted a listing",true);

            $this->awardBadge('listing.add.low',$listing['User']['user_id'], $award_text);

            $this->awardBadge('listing.add.medium',$listing['User']['user_id'], $award_text);

            $this->awardBadge('listing.add.high',$listing['User']['user_id'], $award_text);
        }
    }

    function _plgMediaLikeAfterSave(&$model)
    {
        $conditions = array();

        $stream = Sanitize::getInt($this->c->Config,'jomsocial_media');

        if(!$stream) return false;

        if(!$this->inAdmin)
        {
            $media_id = Sanitize::getInt($model->data,'media_id');

            $vote = Sanitize::getInt($model->data,'vote');

            $MediaModel = ClassRegistry::getClass('MediaModel');

            $media = $MediaModel->findRow(array('conditions'=>array('Media.media_id = ' . $media_id)),array('afterFind','plgAfterFind'));

            $user_id = Sanitize::getInt($media['Media'],'user_id');

            $listing_id = $media['Media']['listing_id'];

            $review_id = $media['Media']['review_id'];

            $extension = $media['Media']['extension'];

            $listing = $this->_getListingEverywhere($listing_id,$extension);

            if(!$listing['Listing']['state']) return;

            $m = $media;

            $activity_thumb = $this->Media->thumb($m,array(
                'thumbnailer'=>'api',
                'size'=>$this->tn_size,
                'mode'=>$this->tn_mode,
                'return_thumburl'=>true,
                'return_url'=>true
                ));

            $m['ListingType'] = $listing['ListingType'];

            $media_url = $this->Routes->mediaDetail($activity_thumb, array('media'=>$m, 'listing'=>$listing), array('return_url'=>true));

            $mediaArray[$m['Media']['media_id']] = array(
                'media_id'=>$m['Media']['media_id'],
                'title'=>$m['Media']['title'],
                'description'=>$m['Media']['description'],
                'orig_src'=>self::makeRel($m['Media']['media_info']['image']['url']),
                'thumb_src'=>self::makeRel($activity_thumb),
                'media_url'=>$media_url
            );

            $media_type = $media['Media']['media_type'];

            if($media_type == 'video')
            {
                $mediaArray[$m['Media']['media_id']]['duration'] = $m['Media']['duration'];
            }

            $act = array(
                'context_id'=>$media_id,
                'context'=>'jreviews-medialike-'.$media_type,
                'actor'=>$this->c->_user->id,
                'target'=>$media['Media']['user_id'],
                'title'=>$media['Media']['title'],
                'content'=>$media['Media']['description'],
                'verb'=>$vote ? 'yes' : 'no',
                'type'=>'full'
            );

            $params = array(
                'listing_id'=>$listing_id,
                'review_id'=>$review_id,
                'media_id'=>$media_id,
                'extension'=>$extension,
                'media_type'=>$media_type,
                'media_url'=>$media_url,
                'listing_title'=>$listing['Listing']['title'],
                'listing_url'=>$listing['Listing']['url'],
                'media'=>$mediaArray
            );

            $this->streamPost($act, $params);
        }
    }

    function _plgMediaBeforeDelete(&$model)
    {
        $media_id = Sanitize::getInt($model->data['Media'],'media_id');

        $media = $model->data;

        $media_type = $media['Media']['media_type'];

        $review_id = Sanitize::getInt($media['Media'],'review_id');

        $extension = $media['Media']['extension'];

        $context_type = 'jreviews-'.$media_type.'-'.($review_id ? 'review' : 'listing').'-'.$extension;

        // Delete activity

        ES::stream()->delete( $media_id , $context_type);

        // Deduct points

        if($this->api_actions && $media['Media']['published'] == 1 && $media['Media']['approved'] == 1 && $media['Media']['user_id'] > 0)
        {
            $this->processPoints($media_type . '.delete',$media['Media']['user_id']);
        }
    }

    function _plgMediaAfterSave(&$model)
    {
        $conditions = array();

        $content = '';

        $stream = Sanitize::getInt($this->c->Config,'jomsocial_media');

        if(!$stream) return false;

        if(!$this->inAdmin || ($this->inAdmin && Sanitize::getBool($model->data,'moderation')))
        {
            if(Sanitize::getBool($model->data,'finished')
                && Sanitize::getInt($model->data['Media'],'published') == 1
                && Sanitize::getInt($model->data['Media'],'approved') == 1)
            {
                $media_id = Sanitize::getInt($model->data['Media'],'media_id');

                $listing_id = Sanitize::getInt($model->data['Media'],'listing_id');

                $review_id = Sanitize::getInt($model->data['Media'],'review_id');

                $extension = Sanitize::getString($model->data['Media'],'extension');

                $media_type = Sanitize::getString($model->data['Media'],'media_type');

                $curr_media = $media = $model->findRow(array('conditions'=>array('Media.media_id = ' . $media_id)));

                $user_id = Sanitize::getInt($media['Media'],'user_id');

                $listing = $this->_getListingEverywhere($listing_id,$extension);

                $publish_up = cmsFramework::dateLocalToUTC($listing['Listing']['publish_up']);

                if(!$listing['Listing']['state'] || strtotime($publish_up) > strtotime(_END_OF_TODAY)) return;

                $listing_link = $this->Routes->content($listing['Listing']['title'],$listing);

                $mediaArray = array();

                $thumb_src = '';

                if(!in_array($media_type,array('attachment','audio')))
                {
                    $thumb_src = $this->Media->thumb($media,array(
                        'thumbnailer'=>'api',
                        'size'=>$this->tn_size,
                        'mode'=>$this->tn_mode,
                        'return_thumburl'=>true,
                        'return_url'=>true
                        ));

                    $thumb_src = self::makeRel($thumb_src);

                    $media['ListingType'] = $listing['ListingType'];

                    $media_url = $this->Routes->mediaDetail('', array('media'=>$media, 'listing'=>$listing), array('return_url'=>true));

                    $mediaArray = array(
                        'media_id'=>$media['Media']['media_id'],
                        'title'=>$media['Media']['title'],
                        'description'=>$media['Media']['description'],
                        'orig_src'=>self::makeRel($media['Media']['media_info']['image']['url']),
                        'thumb_src'=>$thumb_src,
                        'media_url'=>$media_url
                    );

                    if($media_type == 'video')
                    {
                        $mediaArray['duration'] = $media['Media']['duration'];
                    }
                }
                else {

                    $content = $listing['Listing']['summary'] . ' ' . $listing['Listing']['description'];

                    if(isset($listing['MainMedia'])) {

                        $thumb_src = $this->getActivityThumb($listing);
                    }

                    $mediaArray = array(
                        'media_id'=>$media['Media']['media_id'],
                        'title'=>$media['Media']['title']
                    );
                }

                $act = array(
                    'context_id'=>$media_id,
                    'context'=>'jreviews-'.$media_type.'-'.($review_id ? 'review' : 'listing').'-'.$extension,
                    'actor'=>$user_id,
                    'target'=>$listing_id,
                    'title'=>'',
                    'content'=>$content,
                    'verb'=>'new',
                    'type'=>'full'
                    );

                $params = array(
                    'listing_id'=>$listing_id,
                    'review_id'=>$review_id,
                    'media_id'=>$media_id,
                    'extension'=>$extension,
                    'media_type'=>$media_type,
                    'listing_title'=>$listing['Listing']['title'],
                    'listing_url'=>$listing['Listing']['url'],
                    'thumb_src'=>$thumb_src,
                    'media'=>$mediaArray
                );

                $options = array('aggregate'=>true);

                $this->streamPost($act, $params, $options);
            }
        }

        if($this->api_actions)
        {
            if(Sanitize::getBool($model->data,'finished')
                && Sanitize::getInt($model->data['Media'],'published') == 1
                && Sanitize::getInt($model->data['Media'],'approved') == 1)
            {
                if(!isset($curr_media))
                {
                    $media_id = Sanitize::getInt($model->data['Media'],'media_id');

                    $media = $model->findRow(array('conditions'=>array('Media.media_id = ' . $media_id)),array());

                    $user_id = Sanitize::getInt($media['Media'],'user_id');
                }
                else {

                    $media = $curr_media;
                }

                $media_type = Sanitize::getString($media['Media'],'media_type');

                $this->processPoints($media_type . '.add',$user_id);

                switch($media_type)
                {
                    case 'photo':
                            $award_text = __t("Uploaded a photo",true);
                        break;
                    case 'video':
                            $award_text = __t("Uploaded a video",true);
                        break;
                    case 'attachment':
                            $award_text = __t("Uploaded an attachment",true);
                        break;
                    case 'audio':
                            $award_text = __t("Uploaded an audio file",true);
                        break;
                }

                $this->awardBadge('media.add.low',$listing['User']['user_id'], $award_text);

                $this->awardBadge('media.add.medium',$listing['User']['user_id'], $award_text);

                $this->awardBadge('media.add.high',$listing['User']['user_id'], $award_text);

                $this->awardBadge($media_type.'.add.low',$listing['User']['user_id'], $award_text);

                $this->awardBadge($media_type.'.add.medium',$listing['User']['user_id'], $award_text);

                $this->awardBadge($media_type.'.add.high',$listing['User']['user_id'], $award_text);
            }
        }
    }

    function _plgReviewBeforeDelete(&$model)
    {
        $review_id = Sanitize::getInt($model->data['Review'],'id');

        $review = $model->findRow(array('conditions'=>array('Review.id = ' . $review_id)),array());

        $context_type = 'jreviews-review-'.$review['Review']['extension'];

        // Delete activity

        ES::stream()->delete($review_id , $context_type);

        // Deduct points

        if($this->api_actions && $review['Review']['published'] == 1 && $review['User']['user_id'] > 0)
        {
            $this->processPoints('review.delete',$review['User']['user_id']);
        }
    }

    function _plgReviewAfterSave(&$model)
    {
        $stream = Sanitize::getInt($this->c->Config,'jomsocial_reviews');

        /**
        * Check if there's something to do and run the query only if necessary. Then set it in the
        * controller (viewVars) to make it available in other plugins
        */
        if($stream || $this->api_actions)
        {
            $review = $this->_getReview($model);
        }

        /**
        * Publish activity to JomSocial stream
        */
        if($stream)
        {
            // Treat moderated reviews as new
            $this->inAdmin and Sanitize::getBool($model->data,'moderation') and $model->isNew = true;

            if($stream == 1 && (!isset($model->isNew) || !$model->isNew)) return; // Don't run for edits

            if($stream == 1 && $review['Review']['modified'] != NULL_DATE) return; // Don't run for edits

            if($stream == 2 && (!isset($model->isNew) || !$model->isNew) && $this->c->_user->id != $review['User']['user_id']) return; // Don't run for edits by users other than the owner of this post

            $publish_up = cmsFramework::dateLocalToUTC($review['Listing']['publish_up']);

            if(isset($model->isNew) && $review['Review']['published'] == 1 && strtotime($publish_up) <= strtotime(_END_OF_TODAY))
            {
                $thumb_src= $this->getActivityThumb($review);

                $act = array(
                    'context_id'=>$review['Review']['review_id'],
                    'context'=>'jreviews-review-'.$review['Review']['extension'],
                    'actor'=>$review['User']['user_id'],
                    'target'=>$review['Listing']['user_id'],
                    'title'=>$review['Review']['title'],
                    'content'=>$review['Review']['comments'],
                    'verb'=>isset($model->isNew) && $model->isNew  && $review['Review']['modified'] == NULL_DATE ? 'new' : 'edit',
                    'type'=>'full'
                );

                $params = array(
                    'listing_id'=>$review['Review']['listing_id'],
                    'review_id'=>$review['Review']['review_id'],
                    'extension'=>$review['Review']['extension'],
                    'listing_title'=>$review['Listing']['title'],
                    'listing_url'=>$review['Listing']['url'],
                    'thumb_src'=>$thumb_src,
                    'scale'=>$this->c->Config->rating_scale,
                    'editor_review'=>$review['Review']['editor'],
                    'average_rating'=>$review['Rating']['average_rating']
                );

                $this->streamPost($act, $params);
            }
        }

        if($this->api_actions)
        {
            if(isset($model->isNew) && $model->isNew && $review['Review']['published'] == 1)
            {
                $this->processPoints('review.add',$review['User']['user_id']);

                if((isset($review['ListingReview']['review_count']) && $review['ListingReview']['review_count'] == 1)
                    || (isset($review['Review']['review_count']) && $review['Review']['review_count'] == 0))
                {
                            ES::badges()->log('com_jreviews', 'review.add.first', 31 , 'test');

                    $this->awardBadge('review.add.first', $review['User']['user_id'], __t("First to review listing",true));
                }

                $award_text = __t("Submitted a review",true);

                $this->awardBadge('review.add.low',$review['User']['user_id'], $award_text);

                $this->awardBadge('review.add.medium',$review['User']['user_id'], $award_text);

                $this->awardBadge('review.add.high',$review['User']['user_id'], $award_text);
            }
        }
    }

    function _plgVoteAfterSave(&$model)
    {
        if($stream = Sanitize::getInt($this->c->Config,'jomsocial_votes'))
        {
            if($stream == 1 && !$model->data['Vote']['vote_yes']) return; // Yes votes only

            !class_exists('ReviewModel') and S2App::import('Model','review','jreviews');

            $ReviewModel = ClassRegistry::getClass('ReviewModel');

            $review_id = $model->data['Vote']['review_id'];

            $review = $ReviewModel->findRow(array('conditions'=>array('Review.id = ' . $review_id)),array());

            $listing = $this->_getListingEverywhere($review['Review']['listing_id'],$review['Review']['extension']);

            $review_url = $this->Routes->reviewDiscuss('',$review,array('listing'=>$listing,'return_url'=>true));

            $thumb_src = $this->getActivityThumb($listing);

            $act = array(
                'context_id'=>$review['Review']['review_id'],
                'context'=>'jreviews-vote-'.$review['Review']['extension'],
                'actor'=>$model->data['Vote']['user_id'],
                'target'=>$review['User']['user_id'],
                'title'=>$review['Review']['title'],
                'content'=>$review['Review']['comments'],
                'verb'=>$model->data['Vote']['vote_yes'] == 1 ? 'yes' : 'no',
                'type'=>'full'
            );

            $params = array(
                'listing_id'=>$review['Review']['listing_id'],
                'review_id'=>$review['Review']['review_id'],
                'extension'=>$review['Review']['extension'],
                'listing_title'=>$listing['Listing']['title'],
                'listing_url'=>$listing['Listing']['url'],
                'review_url'=>$review_url,
                'thumb_src'=>$thumb_src
            );

            $this->streamPost($act, $params);
        }
    }

    function processPoints($command, $user_id)
    {
        // Don't process for activities initiated by guests

        if(!$user_id) return false;

        ES::points()->assign($command, 'com_jreviews', $user_id);
    }

    function awardBadge($command, $user_id, $text)
    {
        // Don't process for activities initiated by guests

        if(!$user_id) return false;

        ES::badges()->log('com_jreviews', $command, $user_id , $text);
    }

    function streamPost($act, $params, $options = array())
    {
        // Sep 27 2014 - Don't process for activities initiated by guests
        // Guest activities worked before, but EasySocial changed something and it no longer works
        // It also generates some php notices which break ajax requests

        // Mar 12, 2017 - We are back to allowing guest activities because EasySocial is showing them correctly when viewing the newsfeed for "Everyone"
        // if(!$act['actor']) return false;

        $stream = ES::stream();

        $tmpl  = $stream->getTemplate();

        $title = Sanitize::getString($act,'title');

        $content = Sanitize::getString($act,'content');

        $tmpl->setTitle($title);

        $tmpl->setContent($content);

        // $tmpl->setParams($params);

        $tmpl->setActor($act['actor'] , SOCIAL_TYPE_USER);

        $tmpl->setContext($act['context_id'], $act['context'], $params);

        $tmpl->setType($act['type']); // full | mini

        if(Sanitize::getInt($act,'target')) {

            $tmpl->setTarget($act['target']);
        }

        $tmpl->setVerb($act['verb']);

        // Process options
        if(Sanitize::getInt($options,'aggregate')) {

            $tmpl->setAggregate(true, true);
        }

        $tmpl->setPublicStream( 'jreviews.view' );

        $stream->add($tmpl);
    }

    function getActivityThumb($data)
    {
        if(!empty($data['MainMedia']))
        {

            $activity_thumb = $this->Media->thumb(Sanitize::getVar($data,'MainMedia'),array(
                'thumbnailer'=>'api',
                'listing'=>$data,
                'size'=>$this->tn_size,
                'mode'=>$this->tn_mode,
                'return_thumburl'=>true,
                'return_src'=>true,
                ));

            return self::makeRel($activity_thumb);
        }

        return false;
    }

    static function makeRel($url)
    {
        return str_replace(WWW_ROOT, WWW_ROOT_REL, $url);
    }

    function _getListing(&$model)
    {
        if(isset($this->c->viewVars['listing'])
            && count($this->c->viewVars['listing']['Listing']) > 3 /* Need to make sure that the whole listing array is there and not just a few keys */
            )
        {
            $listing = $this->c->viewVars['listing'];
        }
        else
        {
            $listing_id = isset($model->data['Listing']) ? Sanitize::getInt($model->data['Listing'],'id') : false;

            !$listing_id and $listing_id = isset($this->c->data['Listing']) ? Sanitize::getInt($this->c->data['Listing'],'id') : false;

            !$listing_id and $listing_id = Sanitize::getInt($this->c->data,'listing_id');

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

                // No need to add all this extra stuff just to get the listing url
                $ListingModel->addStopAfterFindModel(array('Favorite','Field','PaidOrder'));

                $ListingModel->addRunAfterFindModel(array('Media'));

                $listing = $ListingModel->findRow(array('conditions'=>array('Listing.'.$ListingModel->realKey.' = ' . $listing_id)));

                $this->c->set('listing_'.$extension,$listing);
            }
        }

        return $listing;
    }

	function _isPaidListing($listing)
	{
		if(Configure::read('PaidListings.enabled'))
		{
			S2App::import('Model','paid_plan_category');
			$PaidPlanCategory = ClassRegistry::getClass('PaidPlanCategoryModel');
			return $PaidPlanCategory->isInPaidCategory($listing['Listing']['cat_id']);
		}
		return false;
	}

    function _getReview(&$model)
    {
        $fields = $joins = array();

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

            if(!isset($model->joins['Listing'])) {

                $joins = $this->c->Listing->joinsReviews;
            }

             // Triggers the afterFind in the Observer Model
            $this->c->EverywhereAfterFind = true;

            $review = $model->findRow(array(
                'conditions'=>'Review.id = ' . $model->data['Review']['id'],
                'joins'=>$joins
                ), array('afterFind','plgAfterFind' /* limit callbacks */)
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
