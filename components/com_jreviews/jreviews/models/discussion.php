<?php
/**
 * JReviews - Reviews Extension
 * Copyright (C) 2010-2015 ClickFWD LLC
 * This is not free software, do not distribute it.
 * For licencing information visit https://www.jreviews.com
 * or contact sales@jreviews.com
**/

defined( 'MVC_FRAMEWORK') or die( 'Direct Access to this location is not allowed.' );

S2App::import('Model','review','jreviews');

class DiscussionModel extends MyModel {

	var $name = 'Discussion';

	var $useTable = '#__jreviews_discussions AS Discussion';

	var $primaryKey = 'Discussion.discussion_id';

	var $realKey = 'discussion_id';

    function __construct()
    {
        parent::__construct();

        if(!class_exists('UserModel'))
        {
            S2App::import('Model','user','jreviews');
        }

        $this->fields = array(
            'Discussion.discussion_id AS `Discussion.discussion_id`',
            'Discussion.type AS `Discussion.type`',
            'Discussion.parent_post_id AS `Discussion.parent_post_id`',
            'Discussion.review_id AS `Discussion.review_id`',
            'Discussion.user_id AS `Discussion.user_id`',
            'Discussion.name AS `Discussion.name`',
            'Discussion.username AS `Discussion.username`',
            'Discussion.ipaddress AS `Discussion.ipaddress`',
            'Discussion.text AS `Discussion.text`',
            'Discussion.created AS `Discussion.created`',
            'Discussion.modified AS `Discussion.modified`',
            'Discussion.approved AS `Discussion.approved`',
            'User.' . UserModel::_USER_ID . ' AS `User.user_id`',
            'Discussion.email As `User.email`',
            'Review.id AS `Review.review_id`',
            'Review.title AS `Review.title`',
            'Review.mode AS `Review.extension`',
            'Review.pid AS `Review.listing_id`',
            'Review.userid AS `Review.user_id`',
            'Review.pid AS `Listing.listing_id`',
            'Review.mode AS `Listing.extension`',
            'CASE WHEN CHAR_LENGTH(User. ' . UserModel::_USER_REALNAME . ') THEN User. ' . UserModel::_USER_REALNAME . ' ELSE Discussion.name END AS `User.name`',
            'CASE WHEN CHAR_LENGTH(User. ' . UserModel::_USER_ALIAS . ') THEN User.' . UserModel::_USER_ALIAS . ' ELSE Discussion.username END AS `User.username`'
        );

        $this->joins = array(
            'user'=>'LEFT JOIN #__users AS User ON Discussion.user_id = User.' . UserModel::_USER_ID,
            'review'=>'INNER JOIN #__jreviews_comments AS Review ON Discussion.review_id = Review.id AND Review.published = 1'
        );
    }

    function afterFind($results)
    {
        if (empty($results)) {
            return $results;
        }

        if(!is_numeric(key($results))){ // There's only one row
            $results = array($results);
        }

        if(!defined('MVC_FRAMEWORK_ADMIN') || MVC_FRAMEWORK_ADMIN == 0) {
            # Add Community info to results array
            if(class_exists('CommunityModel')) {
                $Community = ClassRegistry::getClass('CommunityModel');
                $results = $Community->addProfileInfo($results, 'User', 'user_id');
            }
        }

        return $results;
    }

    function afterSave($status)
    {
        clearCache('','__data');

        clearCache('','views');

        if($status && !isset($this->data['Discussion']['modified']))  // It's a new comment
        {
            switch($this->data['Discussion']['type'])
            {
                case 'review':

                    if($this->data['Discussion']['approved'] == 1) // Increment post count when post is approved
                    {
                        // Update post count in review table

                        $Review = ClassRegistry::getClass('ReviewModel');

                        $Review->updatePostCount($this->data['Discussion']['review_id'],1);
                    }

                break;
            }
        }
    }

    function beforeDelete($key, $values, $condition)
    {
        $post_id = (int) $values;

        // Make all children comments orphans by setting parent column to zero
        $query = "
            UPDATE
                $this->useTable
            SET
                parent_post_id = 0
            WHERE
                parent_post_id = " . $post_id
        ;

        $this->query($query);

        // Delete all reports for this comment
        $query = "
            DELETE FROM #__jreviews_reports WHERE post_id = " . $post_id
        ;

        $this->query($query);

        // Get the post type to update the post count in the related table
        $this->fields = array('Discussion.*');

        // Make post variable available to afterDelete and plg callback methods
        $callbacks = array();

        $this->post = $this->findRow(array('conditions'=>array('Discussion.discussion_id = ' . $values)),$callbacks);

        $this->data['Discussion'] = $this->post['Discussion'];
    }

    function afterDelete($key, $values, $condition)
    {
        switch($this->post['Discussion']['type'])
        {
            case 'review':

              $Review = ClassRegistry::getClass('ReviewModel');

              $Review->updatePostCount($this->post['Discussion']['review_id'],-1);

            break;
        }
    }

    function getPostOwner($post_id)
    {
        $query = "SELECT user_id FROM #__jreviews_discussions WHERE discussion_id = " . $post_id;

        $user_id = $this->query($query, 'loadResult');

        return $user_id;
    }

    function processSorting($selected = null)
    {
        $order = '';

        switch($selected)
        {
          case 'rdate':
            $order = 'Discussion.created DESC';
            break;
          case 'date':
            $order = 'Discussion.created ASC';
            break;
          default:
            $order = 'Discussion.created ASC';
            break;
        }

        return $order;
    }
}