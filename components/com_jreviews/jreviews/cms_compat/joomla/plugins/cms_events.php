<?php
/**
 * JReviews - Reviews Extension
 * Copyright (C) 2010-2015 ClickFWD LLC
 * This is not free software, do not distribute it.
 * For licencing information visit https://www.jreviews.com
 * or contact sales@jreviews.com
**/

defined( 'MVC_FRAMEWORK') or die( 'Direct Access to this location is not allowed.' );

class CmsEventsComponent extends S2Component {

    var $plugin_order = 100;

    var $name = 'cms_events';

    var $title = 'CMS Events';

    var $published = true;

    var $inAdmin = false;

    var $_JoomlaDispatcher;

    function startup(&$controller)
    {
        $this->c = & $controller;

        $this->inAdmin = defined('MVC_FRAMEWORK_ADMIN');

        JPluginHelper::importPlugin('jreviews');

        $this->_JoomlaDispatcher = cmsFramework::getVersion() < 3 ? JDispatcher::getInstance() : JEventDispatcher::getInstance();
    }

    function plgBeforeSave(&$model,$data)
    {
        switch($model->name)
        {
            case 'Listing':
                // Trigger onContentAfterSave

                $listing = $this->_getListing($model);

                $is_new = isset($model->isNew) && $model->isNew && ($listing['Listing']['modified'] == NULL_DATE || $listing['Listing']['modified'] == $listing['Listing']['created']);

                $this->_JoomlaDispatcher->trigger('onContentBeforeSave', array('com_jreviews.listing', & $data, $is_new));

            break;
        }

        return $data;
    }

    function plgAfterSave(&$model)
    {
        S2App::import('Helper',array('routes','html','media','text'),'jreviews');

        $this->Routes = ClassRegistry::getClass('RoutesHelper');

        $this->Routes->app = 'jreviews';

        $this->Routes->params = $this->c->params;

        $this->Routes->Config = $this->c->Config;

        $this->Routes->Access = $this->c->Access;

        switch($model->name)
        {
            case 'Discussion':

                if($this->inAdmin && !in_array($this->c->action,array('_saveModeration','_save')))
                {
                    return;
                }

                $this->_plgDiscussionAfterSave($model);

            break;

            case 'Favorite':

                if($this->inAdmin && !in_array($this->c->action,array('_saveModeration','_save')))
                {
                    return;
                }

                $this->_plgFavoriteAfterSave($model);

            break;

            case 'Listing':

                switch ($this->c->action)
                {
                    case '_save':
                    case '_saveModeration':
                            $this->_plgListingAfterSave($model);
                        break;

                    case '_publish':
                        $pks = array($model->data['Listing']['id']);
                        $value = $model->data['Listing']['state'];
                        $this->_JoomlaDispatcher->trigger('onContentChangeState', array('com_jreviews.listing', $pks, $value));
                        break;

                    default:
                        break;
                }

            break;

            case 'Media':

                if($this->inAdmin && !in_array($this->c->action,array('_saveModeration','_save')))
                {
                    return;
                }

                $this->_plgMediaAfterSave($model);

            break;

            case 'MediaLike':

                if($this->inAdmin && !in_array($this->c->action,array('_saveModeration','_save')))
                {
                    return;
                }

                $this->_plgMediaLikeAfterSave($model);

            break;

            case 'Review':

                if($this->inAdmin && !in_array($this->c->action,array('_saveModeration','_save')))
                {
                    return;
                }

                $this->_plgReviewAfterSave($model);

            break;

            case 'Vote':

                if($this->inAdmin && !in_array($this->c->action,array('_saveModeration','_save')))
                {
                    return;
                }

                $this->_plgVoteAfterSave($model);

            break;
        }
    }

    function plgBeforeDelete(&$model)
    {
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
        $post_id = Sanitize::getInt($model->data,'id');

        // Get the post before deleting to make the info available in plugin callback functions
        $post = $model->findRow(array('conditions'=>array('Discussion.discussion_id = ' . $post_id)),array());

        $options = array(array('discussion_id'=>$post_id,'discussion'=>$post));

        $result = $this->_JoomlaDispatcher->trigger('onBeforeReviewCommentDelete', $options);
    }

    function _plgDiscussionAfterSave(&$model)
    {
        $post = $this->_getReviewPost($model);

        $is_new = isset($model->isNew) && $model->isNew && $post['Discussion']['modified'] == NULL_DATE;

        // Treat moderated comments as new

        $this->inAdmin and Sanitize::getBool($model->data,'moderation') and $is_new = true;

        if(!$is_new) return;

        // Don't do anything when editing is done by a user other than the owner (i.e. administator)

        if(!$is_new && $this->c->_user->id != $post['User']['user_id']) return;

        if($post['Discussion']['approved'] == 1)
        {
            $listing = $this->_getListingEverywhere($post['Listing']['listing_id'],$post['Listing']['extension']);

            $options = array(array(
                'discussion_id'=>$post['Discussion']['discussion_id'],
                'url'=>$this->Routes->reviewDiscuss('',$post,array('listing'=>$listing,'return_url'=>true)),
                'listing_url'=>$listing['Listing']['url'],
                'discussion'=>$post,
                'listing'=>$listing
            ));

            $result = $this->_JoomlaDispatcher->trigger('onAfterReviewCommentCreate', $options);
        }
    }

    function _plgFavoriteAfterSave(&$model)
    {
        $listing = $this->_getListing($model);

        $action = $this->c->action == '_favoritesDelete' ? 'remove' : 'add';

        $listing_id = $listing['Listing']['listing_id'];

        $options = array(array(
            'listing_id'=>$listing_id,
            'url'=>$listing['Listing']['url'],
            'listing'=>$listing
        ));

        if($action == 'add')
        {
            $result = $this->_JoomlaDispatcher->trigger('onAfterFavoriteAdd', $options);
        }
        else {
            $result = $this->_JoomlaDispatcher->trigger('onAfterFavoriteRemove', $options);
        }
    }

    function _plgListingBeforeDelete(&$model)
    {
        $listing_id = Sanitize::getInt($model->data,'id');

    	$listing = $this->_getListing($model);

        $options = array(array('listing_id'=>$listing_id,'listing'=>$listing));

        $result = $this->_JoomlaDispatcher->trigger('onBeforeListingDelete', $options);
    }

    function _plgListingAfterSave(&$model)
    {
        $listing = $this->_getListing($model);

        $is_new = isset($model->isNew) && $model->isNew && ($listing['Listing']['modified'] == NULL_DATE || $listing['Listing']['modified'] == $listing['Listing']['created']);

        // Treat moderated listings as new

        $this->inAdmin and Sanitize::getBool($model->data,'moderation') and $is_new = true;

        // Trigger onContentAfterSave

        $this->_JoomlaDispatcher->trigger('onContentAfterSave', array('com_jreviews.listing', $listing, $is_new));

        $options = array(array(
            'listing_id'=>$listing['Listing']['listing_id'],
            'url'=>$listing['Listing']['url'],
            'listing'=>$listing
        ));

        if (!$is_new)
        {
            $result = $this->_JoomlaDispatcher->trigger('onAfterListingUpdate', $options);
        }
        elseif ($listing['Listing']['state'] == 1) {
            $result = $this->_JoomlaDispatcher->trigger('onAfterListingCreate', $options);
        }

        $article = array();
    }

    function _plgMediaLikeAfterSave(&$model)
    {
        $conditions = array();

        if(!$this->inAdmin)
        {
            $media_id = Sanitize::getInt($model->data,'media_id');

            $vote = Sanitize::getInt($model->data,'vote');

            $MediaModel = ClassRegistry::getClass('MediaModel');

            $media = $MediaModel->findRow(array('conditions'=>array('Media.media_id = ' . $media_id)),array('afterFind','plgAfterFind'));

            $user_id = Sanitize::getInt($media['Media'],'user_id');

            $listing_id = $media['Media']['listing_id'];

            $extension = $media['Media']['extension'];

            $listing = $this->_getListingEverywhere($listing_id,$extension);

            if(!$listing['Listing']['state']) return;

			$vote = Sanitize::getInt($model->data,'vote');

            $media_url = $this->Routes->mediaDetail('', array('media'=>$media, 'listing'=>$listing), array('return_url'=>true));

            $media_id = $media['Media']['media_id'];

            $options = array(array(
                'media_id'=>$media_id,
                'media_type'=>$media['Media']['media_type'],
                'url'=>$media_url,
                'listing_url'=>$listing['Listing']['url'],
                'media'=>$media,
                'listing'=>$listing
            ));

            if($vote)
            {
                $result = $this->_JoomlaDispatcher->trigger('onAfterMediaLikeYes', $options);
            }
            else {
                $result = $this->_JoomlaDispatcher->trigger('onAfterMediaLikeNo', $options);
            }
        }
    }

    function _plgMediaBeforeDelete(&$model) {}

    function _plgMediaAfterSave(&$model)
    {
        if(!$this->inAdmin || ($this->inAdmin && Sanitize::getBool($model->data,'moderation')))
        {

            if(Sanitize::getBool($model->data,'finished')
                && Sanitize::getInt($model->data['Media'],'published') == 1
                && Sanitize::getInt($model->data['Media'],'approved') == 1)
            {
                $media_id = Sanitize::getInt($model->data['Media'],'media_id');

                $listing_id = Sanitize::getInt($model->data['Media'],'listing_id');

                $extension = Sanitize::getString($model->data['Media'],'extension');

                $media = $model->findRow(array('conditions'=>array('Media.media_id = ' . $media_id)),array());

                $listing = $this->_getListingEverywhere($listing_id,$extension);

                $media_url = $this->Routes->mediaDetail('', array('media'=>$media, 'listing'=>$listing), array('return_url'=>true));

                $options = array(array(
                    'media_id'=>$media_id,
                    'media_type'=>$media['Media']['media_type'],
                    'url'=>$media_url,
                    'listing_url'=>$listing['Listing']['url'],
                    'media'=>$media,
                    'listing'=>$listing
                ));

                $result = $this->_JoomlaDispatcher->trigger('onAfterMediaUpload', $options);
	        }
        }
    }

    function _plgReviewBeforeDelete(&$model)
    {
        $review_id = Sanitize::getInt($model->data['Review'],'id');

        $review = $model->findRow(array('conditions'=>array('Review.id = ' . $review_id)),array());

        $options = array(array('review_id'=>$review_id,'review'=>$review));

        $result = $this->_JoomlaDispatcher->trigger('onBeforeReviewDelete', $options);
    }

    function _plgReviewAfterSave(&$model)
    {
        $review = $this->_getReview($model);

        $is_new = isset($model->isNew) && $model->isNew && $review['Review']['modified'] == NULL_DATE;

        // Treat moderated listings as new

        $this->inAdmin and Sanitize::getBool($model->data,'moderation') and $is_new = true;

        if(!$is_new) return;

        // Don't do anything when editing is done by a user other than the owner (i.e. administator)

        if(!$is_new && $this->c->_user->id != $review['User']['user_id']) return;

        if($review['Review']['published'] == 1)
        {
            $options = array(array(
                'review_id'=>$review['Review']['review_id'],
                'url'=>$review['Listing']['url'],
                'review'=>$review
            ));

            $result = $this->_JoomlaDispatcher->trigger('onAfterReviewCreate', $options);
        }
    }

    function _plgVoteAfterSave(&$model)
    {
        !class_exists('ReviewModel') and S2App::import('Model','review','jreviews');

        $ReviewModel = ClassRegistry::getClass('ReviewModel');

        $review_id = $model->data['Vote']['review_id'];

        $review = $ReviewModel->findRow(array('conditions'=>array('Review.id = ' . $review_id)),array());

        $listing = $this->_getListingEverywhere($review['Review']['listing_id'],$review['Review']['extension']);

        $url = $this->Routes->reviewDiscuss('',$review,array('listing'=>$listing,'return_url'=>true));

        $options = array(array(
            'review_id'=>$review_id,
            'url'=>$url,
            'listing_url'=>$listing['Listing']['url'],
            'review'=>$review,
            'listing'=>$listing
        ));

        if($model->data['Vote']['vote_yes'])
        {
            $result = $this->_JoomlaDispatcher->trigger('onAfterReviewVoteYes', $options);
        }
        else {
            $result = $this->_JoomlaDispatcher->trigger('onAfterReviewVoteNo', $options);
        }
    }

    /**
     * Helper methods to query the complete info for a specific object type
     */

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
