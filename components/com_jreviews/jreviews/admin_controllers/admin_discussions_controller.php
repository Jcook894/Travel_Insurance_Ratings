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

class AdminDiscussionsController extends MyController
{
	var $uses = array('menu','discussion','user','review','criteria','predefined_reply');

	var $components = array('config','access','admin/admin_notifications','everywhere');

	var $helpers = array('html','admin/admin_routes','routes','form','time');

	var $autoRender = false;

	var $autoLayout = true;

	function beforeFilter() {

        $this->Access->init($this->Config);

		# Call beforeFilter of MyAdminController parent class

		parent::beforeFilter();
	}

    // Need to return object by reference for PHP4
    function &getPluginModel(){
        return $this->Discussion;
    }

    // Need to return object by reference for PHP4
    function &getEverywhereModel() {
        return $this->Review;
    }

    // Need to return object by reference for PHP4
    function &getNotifyModel(){
        return $this->Discussion;
    }

	function moderation() {

        $reviews = array();

        $conditions = array();

        $page = '';

        $this->limit = 10;

        $processed = Sanitize::getInt($this->params,'processed');

        $this->offset = $this->offset - $processed;

        $conditions[] = "Discussion.`approved` = 0";

        $conditions[] = "Discussion.review_id IN (SELECT id FROM #__jreviews_comments)";

		$posts = $this->Discussion->findAll(array(
            'fields'=>array(
                'IF(Discussion.user_id = 0,Discussion.email,User.'.UserModel::_USER_EMAIL.') AS `User.email`'
            ),
            'conditions'=>$conditions,
            'offset'=>$this->offset,
            'limit'=>$this->limit,
            'order'=>array('Discussion.discussion_id DESC')
        ));

        $total = $this->Discussion->findCount(array('conditions'=>$conditions));

        if(!empty($posts))
        {
            $predefined_replies = $this->PredefinedReply->findAll(array(
                'fields'=>array('PredefinedReply.*'),
                'conditions'=>array('reply_type = "discussion_post"')
                ));

            // We get all the review ids for the discussion posts
            $review_ids = array();

            foreach($posts AS $post){
                !empty($post['Discussion']['review_id']) and $review_ids[$post['Discussion']['review_id']] = $post['Discussion']['review_id'];
            }

            // For now all posts are for reviews so there's no need to worry about the entry type
            $this->EverywhereAfterFind = true; // Triggers the afterFind in the Observer Model

            $this->Review->runProcessRatings = false;

            $reviews = $this->Review->findAll(array('conditions'=>array('Review.id IN ('.implode(',',array_keys($review_ids)).')')));

            // We merge the posts and review info
            foreach($posts AS $key=>$post)
            {
                unset($reviews[$post['Discussion']['review_id']]['User']); // Otherwise the review user overwrites the comment user
                isset($reviews[$post['Discussion']['review_id']]) and $posts[$key] = array_merge($posts[$key],$reviews[$post['Discussion']['review_id']]);
            }

            $this->set(array(
                'processed'=>$processed,
                'total'=>$total,
                'posts'=>$posts,
                'predefined_replies'=>$predefined_replies
            ));

        }

        return $this->render('discussions','posts');
	}

    function _delete()
    {
        $response = array('success'=>false,'str'=>array());

        $ids = Sanitize::getVar($this->params,'cid', Sanitize::getInt($this->params,'id'));

        if(empty($ids)) {

            return cmsFramework::jsonResponse($response);
        }

		if($delete = $this->Discussion->delete('discussion_id',cleanIntegerCommaList($ids)))
        {
            $response['success'] = true;
        }

        return cmsFramework::jsonResponse($response);
    }

    function _save()
    {
        $response = array();

        $response['success'] = false;

        $this->Discussion->isNew = false;

        if($this->Discussion->store($this->data))
        {
            $response['success'] = true;

            clearCache('', 'views');

            clearCache('', '__data');
        }

        return cmsFramework::jsonResponse($response);
    }

}