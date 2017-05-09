<?php
/**
 * ListingResources Addon for JReviews
 * Copyright (C) 2010-2015 ClickFWD LLC
 * This is not free software, do not distribute it.
 * For licencing information visit https://www.jreviews.com
 * or contact sales@jreviews.com
**/

defined( 'MVC_FRAMEWORK') or die;

class AdminRatingsmigratorController extends MyController
{
    var $uses = array('review','field');

    var $helpers = array();

    var $components = array('config','access','everywhere');

    var $autoRender = false;

    var $autoLayout = false;

    var $_DENORMALIZE_LIMIT = 1000;

    var $_LISTING_TYPE_ID_LIMIT = 1000;

    function getEverywhereModel() {
        return $this->Review;
    }

    function beforeFilter()
    {
        ini_set('max_execution_time',999999);
        // parent::beforeFilter();
    }

    function index()
    {
        return $this->render('ratingsmigrator','index');
    }

    // prepare database
    function step1()
    {
        $Model = new S2Model;

        $db_name = cmsFramework::getConfig('db');

        $db_prefix = cmsFramework::getConfig('dbprefix');

        $response = array('success'=>true,'msg'=>'');

        // Make sure the ratings_id column exists in the ratings table

        # Add related field columns to fields table
        $query = "
          SELECT
            count(*)
          FROM
            information_schema.COLUMNS
          WHERE
            TABLE_SCHEMA='{$db_name}' AND TABLE_NAME='{$db_prefix}jreviews_ratings'
            AND COLUMN_NAME='rating_id'";

        $exists = $Model->query($query,'loadResult');

        if(!$exists)
        {
            // Drop old indexes

            $query = "
                SELECT
                    index_name
                FROM
                    information_schema.statistics
                WHERE
                    table_schema = '". $db_name ."'
                    AND
                    table_name = '". str_replace('#__',$db_prefix,'#__jreviews_ratings') ."'
            ";

            $indexes = $Model->query($query, 'loadColumn');

            if(in_array('reviewid', $indexes))
            {
                $Model->query('DROP INDEX `reviewid` ON #__jreviews_ratings');
            }
            if(in_array('review_id', $indexes))
            {
                $Model->query('DROP INDEX `review_id` ON #__jreviews_ratings');
            }

            $Model->query('ALTER TABLE #__jreviews_ratings DROP PRIMARY KEY');

            $Model->query('ALTER TABLE #__jreviews_ratings ADD  `rating_id` INT( 11 ) NOT NULL AUTO_INCREMENT PRIMARY KEY FIRST');

            $Model->query('ALTER TABLE #__jreviews_ratings DROP PRIMARY KEY , ADD PRIMARY KEY (  `rating_id` ,  `reviewid` )');
        }

        # Remove rating duplicate reviewid rows

        $Model->query('ALTER IGNORE TABLE #__jreviews_ratings ADD UNIQUE (reviewid)');

        // Criteria by Listing Type

        $query = "
            CREATE TABLE IF NOT EXISTS `#__jreviews_criteria_ratings` (
              `criteria_id` int(11) NOT NULL AUTO_INCREMENT,
              `title` varchar(255) NOT NULL DEFAULT '',
              `required` tinyint(1) NOT NULL DEFAULT '0',
              `weight` int(2) NOT NULL DEFAULT '0',
              `description` mediumtext NOT NULL,
              `listing_type_id` int(11) NOT NULL DEFAULT '0',
              `ordering` int(11) NOT NULL DEFAULT '0',
              PRIMARY KEY (`criteria_id`)
            ) DEFAULT CHARSET=utf8
        ";

        if(!$Model->query($query))
        {
            $response['msg'] = 'There was a problem creating the #__jreviews_criteria_ratings table';
            $response['success'] = false;
            return cmsFramework::jsonResponse($response);
        }

        // Review Ratings

        $query = "
            CREATE TABLE IF NOT EXISTS `#__jreviews_review_ratings` (
                `listing_id` int(11) NOT NULL DEFAULT '0',
                `review_id` int(11) NOT NULL DEFAULT '0',
                `extension` varchar(50) DEFAULT NULL,
                `criteria_id` int(11) NOT NULL DEFAULT '0',
                `rating` decimal(11,4) NOT NULL DEFAULT '0',
                PRIMARY KEY (`review_id`,`criteria_id`,`listing_id`,`extension`),
                UNIQUE KEY `listing` (`listing_id`,`criteria_id`,`review_id`,`extension`),
                KEY `rating` (`review_id`,`criteria_id`,`rating`)
            ) DEFAULT CHARSET=utf8
        ";

        if(!$Model->query($query))
        {
            $response['msg'] = 'There was a problem creating the #__jreviews_review_ratings table';
            $response['success'] = false;
            return cmsFramework::jsonResponse($response);
        }

        // Listing Ratings

        $query = "
            CREATE TABLE IF NOT EXISTS `#__jreviews_listing_ratings` (
              `listing_id` int(11) NOT NULL DEFAULT '0',
              `extension` varchar(128) NOT NULL DEFAULT '',
              `criteria_id` int(11) NOT NULL DEFAULT '0',
              `user_rating` decimal(11,4) NOT NULL DEFAULT '0',
              `user_rating_count` int(11) NOT NULL DEFAULT '0',
              `user_rating_rank` decimal(11,4) NOT NULL DEFAULT '0',
              `editor_rating` decimal(11,4) NOT NULL DEFAULT '0',
              `editor_rating_count` int(11) NOT NULL DEFAULT '0',
              `editor_rating_rank` decimal(11,4) NOT NULL DEFAULT '0',
              PRIMARY KEY (`listing_id`,`extension`,`criteria_id`),
              KEY `user_rating` (`criteria_id`,`user_rating`,`user_rating_count`),
              KEY `editor_rating` (`criteria_id`,`editor_rating`,`editor_rating_count`)
            ) DEFAULT CHARSET=utf8
        ";

        if(!$Model->query($query))
        {
            $response['msg'] = 'There was a problem creating the #__jreviews_listing_ratings table';
            $response['success'] = false;
            return cmsFramework::jsonResponse($response);
        }

        $listing_totals_columns = $Model->getTableColumns('#__jreviews_listing_totals');

        $listing_totals_columns = array_keys($listing_totals_columns);

        if(!in_array('user_rating_rank',$listing_totals_columns))
        {
            $query = "
                ALTER TABLE #__jreviews_listing_totals ADD COLUMN `user_rating_rank` DECIMAL(9,4) NOT NULL AFTER `user_rating_count`
            ";

            if(!$Model->query($query))
            {
                $response['msg'] = 'There was a problem creating the user_rating_rank column in the #__jreviews_listing_totals table';
                $response['success'] = false;
                return cmsFramework::jsonResponse($response);
            }
        }

        if(!in_array('editor_rating_rank',$listing_totals_columns))
        {
            $query = "
                ALTER TABLE #__jreviews_listing_totals ADD COLUMN `editor_rating_rank` DECIMAL(9,4) NOT NULL AFTER `editor_rating_count`
            ";

            if(!$Model->query($query))
            {
                $response['msg'] = 'There was a problem creating the editor_rating_rank column in the #__jreviews_listing_totals table';
                $response['success'] = false;
                return cmsFramework::jsonResponse($response);
            }
        }

        $review_columns = $Model->getTableColumns('#__jreviews_comments');

        $review_columns = array_keys($review_columns);

        if(!in_array('listing_type_id',$review_columns))
        {
            $query = "
                ALTER TABLE #__jreviews_comments ADD COLUMN `listing_type_id` INT(11) NOT NULL AFTER `pid`
            ";

            if(!$Model->query($query))
            {
                $response['msg'] = 'There was a problem creating the listing_type_id column in the #__jreviews_comments table';
                $response['success'] = false;
                return cmsFramework::jsonResponse($response);
            }
        }

        if(!in_array('rating',$review_columns))
        {
            $query = "
                ALTER TABLE #__jreviews_comments ADD COLUMN `rating` DECIMAL(11, 4) NOT NULL AFTER `author`
            ";

            if(!$Model->query($query))
            {
                $response['msg'] = 'There was a problem creating the rating column in the #__jreviews_comments table';
                $response['success'] = false;
                return cmsFramework::jsonResponse($response);
            }
        }

        // Add new indexes to listing totals

        $db_name = cmsFramework::getConfig('db');

        $db_prefix = cmsFramework::getConfig('dbprefix');

        $query = "

            SELECT
                index_name
            FROM
                information_schema.statistics
            WHERE
                table_schema = '". $db_name ."'
                AND
                table_name = '". str_replace('#__',$db_prefix,'#__jreviews_listing_totals') ."'
        ";

        $indexes = $Model->query($query, 'loadColumn');

        if(in_array('user_rating_rank', $indexes))
        {
            $Model->query('DROP INDEX `user_rating_rank` ON #__jreviews_listing_totals');
        }

        $query = "
            ALTER TABLE `#__jreviews_listing_totals` ADD INDEX `user_rating_rank` ( `user_rating_rank`);
        ";

        $Model->query($query);

        if(in_array('editor_rating_rank', $indexes))
        {
            $Model->query('DROP INDEX `editor_rating_rank` ON #__jreviews_listing_totals');
        }

        $query = "
            ALTER TABLE `#__jreviews_listing_totals` ADD INDEX `editor_rating_rank` ( `editor_rating_rank`);
        ";

        $Model->query($query);

        // Add new indexes to comments

        $query = "

            SELECT
                index_name
            FROM
                information_schema.statistics
            WHERE
                table_schema = '". $db_name ."'
                AND
                table_name = '". str_replace('#__',$db_prefix,'#__jreviews_comments') ."'
        ";

        $indexes = $Model->query($query, 'loadColumn');

        if(in_array('listing', $indexes))
        {
            $Model->query('DROP INDEX `listing` ON #__jreviews_comments');
        }

        $query = "
            ALTER TABLE `#__jreviews_comments` ADD INDEX `listing` ( `pid`, `mode`, `published`, `author`, `userid`, `created`);
        ";

        $Model->query($query);

        return cmsFramework::jsonResponse($response);
    }

    // migrate criteria definitions
    function step2()
    {
        $Model = new S2Model;

        $response = array('success'=>true);

        /**************************************************************************
         *  Convert Listing Type criteria to the new dedicated table format
         **************************************************************************/

        $query = "
            SELECT
                id, criteria, required, weights, tooltips
            FROM
                #__jreviews_criteria
            WHERE
                qty > 0
        ";

        $rows = $Model->query($query,'loadAssocList');

        $queries = array();

        $id = 1;

        foreach($rows AS $row)
        {
            $listing_type_id = $row['id'];

            $criteria = explode("\n", $row['criteria']);

            $criteria = array_filter($criteria);

            if(empty($criteria)) continue;

            $required = explode("\n", $row['required']);

            $weights = array_filter(explode("\n", $row['weights']));

            $tooltips = explode("\n", $row['tooltips']);

            if(empty($weights))
            {
                $weights = array_fill(0, count($criteria), 0);
            }

            $count = count($criteria);

            for($i = 0; $i < $count; $i++)
            {
                if($criteria[$i] == '') break;

                $tooltip = Sanitize::getString($tooltips,$i);

                $queries[] = "
                    INSERT INTO
                        #__jreviews_criteria_ratings
                        (criteria_id, title, required, weight, description, listing_type_id, `ordering`)
                    VALUES
                        (".$id.",".$Model->Quote($criteria[$i]).",'".$required[$i]."','".$weights[$i]."',".$Model->Quote($tooltip).",".$listing_type_id.",".($i+1).")
                    ON DUPLICATE KEY UPDATE
                        title = VALUES(title),
                        required = VALUES(required),
                        weight = VALUES(weight),
                        description = VALUES(description),
                        listing_type_id = VALUES(listing_type_id),
                        `ordering` = VALUES(`ordering`)
                ";

                $id++;
            }
        }

        $result = true;

        foreach($queries AS $query)
        {
            if(!$Model->query($query))
            {
                $response['msg'] = 'There was a problem migrating the criteria definitions ' . $Model->getErrorMsg();
                $response['success'] = false;
                return cmsFramework::jsonResponse($response);
            }
        }

        return cmsFramework::jsonResponse($response);
    }

    // add listing type ID to reviews
    function step3()
    {
        $Model = new S2Model;

        $response = array('success'=>true);

        $total = Sanitize::getInt($this->passedArgs,'total');

        $limit = $this->_LISTING_TYPE_ID_LIMIT;

        $offset = Sanitize::getInt($this->passedArgs,'offset');

       // Get total number of review rows

        if(!isset($this->passedArgs['total']))
        {
            $query = "
                SELECT
                    count(*)
                FROM
                    #__jreviews_comments
            ";

            $total = $Model->query($query,'loadResult');
        }

        if($total == 0)
        {
            return cmsFramework::jsonResponse($response);
        }

        $this->EverywhereAfterFind = true; // Triggers the afterFind in the Observer Model

        $this->Review->addStopAfterFindModel(array('Favorite','Media','Field','PaidOrder','Community'));

        $this->Review->runProcessRatings = false;

        $rows = $this->Review->findAll(array(
            'conditions'=>array(),
            'offset'=>$offset,
            'limit'=>$limit,
            'order'=>'Review.id'));

        $count = $Model->query("SELECT count(*) FROM (SELECT * FROM #__jreviews_comments ORDER BY id LIMIT $offset, $limit) AS Review",'loadResult');

        foreach($rows AS $row)
        {
            // Skip rows where a listing type is not found. It happens if the category for which the review was submitted is no longer setup in JReviews or doesn't exist.

            if(!isset($row['Criteria'])) continue;

            $review_id = $row['Review']['review_id'];

            $listing_type_id = $row['Criteria']['criteria_id'];

            if($listing_type_id)
            {
                $query = "
                    UPDATE
                        #__jreviews_comments
                    SET
                        listing_type_id = " . $listing_type_id . "
                    WHERE
                        id = " . $review_id
                ;

                if(!$Model->query($query))
                {
                    $response['msg'] = 'There was a problem updating the listing type ID for review ' . $review_id;
                    $response['success'] = false;
                    return cmsFramework::jsonResponse($response);
                }
            }
        }

        if($count == $limit)
        {
            $response['step'] = 3;

            $response['offset'] = $offset + $limit;

            $response['total'] = $total;

            $response['remaining'] = $total - ($offset + $limit);

            $response['progress'] = round(($offset/$total) * 100);
        }

        return cmsFramework::jsonResponse($response);
    }

    // denormalize ratings
    function step4()
    {
        $Model = new S2Model;

        $response = array('success'=>true);

        $total = Sanitize::getInt($this->passedArgs,'total');

        $limit = $this->_DENORMALIZE_LIMIT;

        $offset = Sanitize::getInt($this->passedArgs,'offset');

        /**************************************************************************
         *  Populate the new #__jreviews_comments.rating column
         **************************************************************************/

        $query = "
            UPDATE
                #__jreviews_comments AS Comment
            SET rating = (
                SELECT
                    ratings_sum/ratings_qty AS rating
                FROM
                    #__jreviews_ratings AS Rating
                WHERE
                    Rating.reviewid = Comment.id )
        ";

        if(!$Model->query($query))
        {
            $response['msg'] = 'There was a problem updating the new rating column in the comments table.';
            $response['success'] = false;
            return cmsFramework::jsonResponse($response);
        }

        /**************************************************************************
         *  Convert comma list review rating format to individual criteria records
         **************************************************************************/

        // Get total number of rating rows

        if(!isset($this->passedArgs['total']))
        {
            $query = "
                SELECT
                    count(*)
                FROM
                    #__jreviews_ratings AS Rating
                RIGHT JOIN
                    #__jreviews_comments AS Review ON Review.id = Rating.reviewid
            ";

            $total = $Model->query($query,'loadResult');
        }

        if($total == 0)
        {
            return cmsFramework::jsonResponse($response);
        }

        // Get the rating criteria per listing type

        $query = "
            SELECT
                *
            FROM
                #__jreviews_criteria_ratings
        ";

        $rows = $Model->query($query,'loadAssocList');

        $generic_to_specific_criteria_by_listing_type = array();

        foreach($rows AS $row)
        {
            $generic_to_specific_criteria_by_listing_type[$row['listing_type_id']][] = $row['criteria_id'];
        }

        // De-normalize all ratings into multiple records

        $query = "
            SELECT
                Review.pid AS listing_id, Review.listing_type_id AS listing_type_id, Review.mode AS extension, Rating.reviewid AS review_id, Rating.ratings
            FROM
                #__jreviews_ratings AS Rating
            LEFT JOIN
                #__jreviews_comments AS Review ON Review.id = Rating.reviewid
            ORDER BY
                Rating.rating_id
            LIMIT " . $offset . "," . $limit
        ;

        $rows = $Model->query($query,'loadAssocList','review_id');

        $count_padding = 0;

        foreach($rows AS $key=>$row)
        {
            if(!$row['listing_id'] || !$row['listing_type_id'])
            {
                $count_padding++;

                continue;
            }

            $ratings = explode(',',$row['ratings']);

            $listing_type_id = $row['listing_type_id'];

            $extension = $row['extension'];

            $rows[$key]['listing_type_id'] = $listing_type_id;

            foreach($ratings AS $rkey=>$rating)
            {
                // If the category id doesn't have an associated listing type ignore it
                if(!isset($generic_to_specific_criteria_by_listing_type[$listing_type_id])
                    // If the number of ratings doesn't match the listing type criteria skip the extra ratings
                    || !isset($generic_to_specific_criteria_by_listing_type[$listing_type_id][$rkey])
                    ) continue;

                $real_criteria_id = (int) $generic_to_specific_criteria_by_listing_type[$listing_type_id][$rkey];

                extract($rows[$key]);

                $listing_id = (int) $listing_id;

                $review_id = (int) $review_id;

                $rating = (float) $rating;

                $query = "
                    INSERT INTO #__jreviews_review_ratings
                        (listing_id,review_id,extension,criteria_id,rating)
                    VALUES
                        ($listing_id,$review_id,'".$extension."',$real_criteria_id,$rating)
                    ON DUPLICATE KEY
                        UPDATE rating = VALUES(rating);
                ";



                if(!$Model->query($query))
                {
                    $response['msg'] = 'There was a problem de-normalizing the comma list ratings at offset,limit ('.$offset.','.$limit.')';
                    $response['success'] = false;
                    return cmsFramework::jsonResponse($response);
                }
            }
        }

        if((count($rows) == $limit))
        {
            $response['step'] = 4;

            $response['offset'] = $offset + $limit;

            $response['total'] = $total;

            $response['remaining'] = $total - ($offset + $limit);

            $response['progress'] = round(($offset/$total) * 100);
        }

        return cmsFramework::jsonResponse($response);
    }

    // calculate rating averages for each criteria
    function step5()
    {
        $Model = new S2Model;

        $response = array('success'=>true);

        $query = "
            INSERT INTO #__jreviews_listing_ratings (
                listing_id,
                extension,
                criteria_id,
                user_rating,
                user_rating_count,
                editor_rating,
                editor_rating_count
            )
                SELECT
                    Review.pid AS listing_id,
                    ReviewRating.extension AS extension,
                    ReviewRating.criteria_id AS criteria_id,
                    SUM(IF(Review.author=0,ReviewRating.rating,0))/SUM(IF(Review.author=0,1,0)) AS user_rating,
                    SUM(IF(Review.author=0,1,0)) AS user_rating_count,
                    SUM(IF(Review.author=1,ReviewRating.rating,0))/SUM(IF(Review.author=1,1,0)) AS editor_rating,
                    SUM(IF(Review.author=1,1,0)) AS editor_rating_count
                FROM
                    #__jreviews_review_ratings AS ReviewRating
                INNER JOIN
                    #__jreviews_comments AS Review ON Review.id = ReviewRating.review_id
                LEFT JOIN
                    #__jreviews_criteria_ratings AS CriteriaRating ON CriteriaRating.criteria_id = ReviewRating.criteria_id
                WHERE
                    Review.published = 1
                GROUP BY
                    Review.pid,
                    ReviewRating.extension,
                    ReviewRating.criteria_id
                ORDER BY NULL
            ON DUPLICATE KEY UPDATE
                user_rating = VALUES(user_rating),
                user_rating_count = VALUES(user_rating_count),
                editor_rating = VALUES(editor_rating),
                editor_rating_count = VALUES(editor_rating_count)
        ";

        if(!$Model->query($query))
        {
            $response['msg'] = 'There was a problem updating the #__jreviews_listing_ratings table with individual criteria rating averages per listing.';
            $response['success'] = false;
            return cmsFramework::jsonResponse($response);
        }

        return cmsFramework::jsonResponse($response);
    }

    // calculate overal rating average
    function step6()
    {
        $Model = new S2Model;

        $response = array('success'=>true);

        // Delete zero ID record just in case
        $query = "
            DELETE FROM
                #__jreviews_listing_totals
            WHERE
                listing_id = 0
        ";

        // Reset all rating and review info because we are updating below

        $Model->query($query);

        $query = "
            UPDATE
                #__jreviews_listing_totals AS Total
            INNER JOIN (
                SELECT
                    listing_id, extension
                FROM
                    #__jreviews_listing_totals AS Total
                LEFT JOIN
                    #__jreviews_comments AS Review ON  Review.pid = Total.listing_id AND Review.mode = Total.extension
                WHERE
                    Review.id IS NULL
            ) AS TotalNoReviews ON Total.listing_id = TotalNoReviews.listing_id AND Total.extension = TotalNoReviews.extension
            SET
                Total.user_rating = 0,
                Total.user_rating_rank = 0,
                Total.user_criteria_rating = '',
                Total.user_criteria_rating_count = '',
                Total.user_rating_count = 0,
                Total.user_comment_count = 0,
                Total.editor_rating = 0,
                Total.editor_rating_rank = 0,
                Total.editor_criteria_rating = '',
                Total.editor_criteria_rating_count = '',
                Total.editor_rating_count = 0,
                Total.editor_comment_count = 0
        ";

        if(!$Model->query($query))
        {
            return false;
        }

        $query = "
            INSERT INTO #__jreviews_listing_totals (
                listing_id,
                extension,
                user_rating,
                user_rating_count,
                user_comment_count,
                user_criteria_rating,
                user_criteria_rating_count,
                editor_rating,
                editor_rating_count,
                editor_comment_count,
                editor_criteria_rating,
                editor_criteria_rating_count
            )
            SELECT
                listing_id,
                extension,
                IF(user_weight>0 AND user_rating>0,(SUM(user_rating * user_weight)/SUM(user_weight)),SUM(user_rating)/SUM(IF(user_rating>0,1,0))) AS user_rating,
                user_rating_count,
                user_comment_count,
                GROUP_CONCAT(user_rating ORDER BY criteria_order) AS user_criteria_rating,
                GROUP_CONCAT(user_criteria_rating_count ORDER BY criteria_order) AS user_criteria_rating_count,
                IF(editor_weight>0 AND editor_rating>0,(SUM(editor_rating * editor_weight)/SUM(editor_weight)),SUM(editor_rating)/SUM(IF(editor_rating>0,1,0))) AS editor_rating,
                editor_rating_count,
                editor_comment_count,
                GROUP_CONCAT(editor_rating ORDER BY criteria_order) AS editor_criteria_rating,
                GROUP_CONCAT(editor_criteria_rating_count ORDER BY criteria_order) AS editor_criteria_rating_count
            FROM (
                SELECT
                    Review.pid AS listing_id,
                    Review.mode AS extension,
                    ReviewRating.criteria_id,
                    CriteriaRating.ordering AS criteria_order,
                    IFNULL( ROUND(SUM(IF(Review.author=0,ReviewRating.rating,0))/SUM(IF(Review.author=0,1,0)), 4), 0) AS user_rating,
                    SUM(IF(Review.author=0 AND ReviewRating.rating>0,1,0)) AS user_rating_count,
                    SUM(IF(Review.author=0 AND ReviewRating.rating>0,1,0)) AS user_criteria_rating_count,
                    SUM(IF(Review.author=0,1,0)) AS user_comment_count,
                    " /* Need to nullify weights if the rating is zero so when dividing by sum of weighs in weighed average these are ignored */ ."
                    IF(IFNULL(ROUND(SUM(IF(Review.author=0,ReviewRating.rating,0))/SUM(IF(Review.author=0 AND ReviewRating.rating>0,1,0)), 4), 0)=0,0,CriteriaRating.weight) AS user_weight,
                    IFNULL( ROUND(SUM(IF(Review.author=1,ReviewRating.rating,0))/SUM(IF(Review.author=1,1,0)), 4), 0) AS editor_rating,
                    SUM(IF(Review.author=1 AND ReviewRating.rating>0,1,0)) AS editor_criteria_rating_count,
                    SUM(IF(Review.author=1 AND ReviewRating.rating>0,1,0)) AS editor_rating_count,
                    SUM(IF(Review.author=1,1,0)) AS editor_comment_count,
                    " /* Need to nullify weights if the rating is zero so when dividing by sum of weighs in weighed average these are ignored */ ."
                    IF(IFNULL(ROUND(SUM(IF(Review.author=1,ReviewRating.rating,0))/SUM(IF(Review.author=1 AND ReviewRating.rating>0,1,0)), 4), 0)=0,0,CriteriaRating.weight) AS editor_weight
                FROM
                    #__jreviews_comments AS Review
                LEFT JOIN
                    #__jreviews_review_ratings AS ReviewRating ON Review.id = ReviewRating.review_id
                LEFT JOIN
                    #__jreviews_criteria_ratings AS CriteriaRating ON CriteriaRating.criteria_id = ReviewRating.criteria_id
                WHERE
                    Review.published = 1
                GROUP BY
                    ReviewRating.listing_id,
                    ReviewRating.extension,
                    ReviewRating.criteria_id
            ) AS ListingTotal
            GROUP BY
                listing_id,
                extension
            ORDER BY NULL
            ON DUPLICATE KEY UPDATE
                user_rating = VALUES(user_rating),
                user_rating_count = VALUES(user_rating_count),
                user_comment_count = VALUES(user_comment_count),
                user_criteria_rating = VALUES(user_criteria_rating),
                user_criteria_rating_count = VALUES(user_criteria_rating_count),
                editor_rating = VALUES(editor_rating),
                editor_rating_count = VALUES(editor_rating_count),
                editor_comment_count = VALUES(editor_comment_count),
                editor_criteria_rating = VALUES(editor_criteria_rating),
                editor_criteria_rating_count = VALUES(editor_criteria_rating_count)
        ";

        if(!$Model->query($query))
        {
            $response['msg'] = 'There was a problem updating the #__jreviews_listing_totals table with overal rating averages per listing.';
            $response['success'] = false;
            return cmsFramework::jsonResponse($response);
        }

        return cmsFramework::jsonResponse($response);
    }

    // calculate Bayesian rating for individual criteria for each listing
    function step7()
    {
        $Model = new S2Model;

        $response = array('success'=>true);

        /**************************************************************************
         *  Update the average bayesian rating ranks
         **************************************************************************/

        $avg_user = $avg_editor = array();

        $query = "
            SELECT
                criteria_id
            FROM
                #__jreviews_criteria_ratings
        ";

        $criteria_ids = $Model->query($query,'loadColumn');

        foreach($criteria_ids AS $criteria_id)
        {
            $query = "
                SELECT
                    AVG(user_rating_count) AS count, AVG(user_rating) AS rating
                FROM
                    #__jreviews_listing_ratings
                WHERE
                    user_rating_count > 0
            ";

            $avg_user = $Model->query($query,'loadAssoc');

            $query = "
                SELECT
                    AVG(editor_rating_count) AS count, AVG(editor_rating) AS rating
                FROM
                    #__jreviews_listing_ratings
                WHERE
                    editor_rating_count > 0
            ";

            $avg_editor = $Model->query($query,'loadAssoc');

            $query = "
                UPDATE
                    #__jreviews_listing_ratings
                SET
                    user_rating_rank = IF(user_rating = 0,0, ((".$avg_user['count']*$avg_user['rating'].") + (user_rating_count * user_rating)) / (".$avg_user['count']." + user_rating_count)),
                    editor_rating_rank = IF(editor_rating = 0,0,((".$avg_editor['count']*$avg_editor['rating'].") + (editor_rating_count * editor_rating)) / (".$avg_editor['count']. " + editor_rating_count))
                WHERE
                    criteria_id = " . $criteria_id
            ;

            if(!$Model->query($query))
            {
                $response['msg'] = 'There was a problem updating the #__jreviews_listing_ratings table with the Bayesian ratings.';
                $response['success'] = false;
                return cmsFramework::jsonResponse($response);
            }

        }

        return cmsFramework::jsonResponse($response);
    }

    // calculate Bayesian rating for listing average rating
    function step8()
    {
        $Model = new S2Model;

        $response = array('success'=>true,'complete'=>true);

        /**************************************************************************
         *  Update the average bayesian rating ranks
         **************************************************************************/

        $query = "
            SELECT
                AVG(user_rating_count) AS count, AVG(user_rating) AS rating
            FROM
                #__jreviews_listing_totals
            WHERE
                user_rating_count > 0
        ";

        $avg_user = $Model->query($query,'loadAssoc');

        $query = "
            SELECT
                AVG(editor_rating_count) AS count, AVG(editor_rating) AS rating
            FROM
                #__jreviews_listing_totals
            WHERE
                editor_rating_count > 0
        ";

        $avg_editor = $Model->query($query,'loadAssoc');

        $query = "
            UPDATE
                #__jreviews_listing_totals
            SET
                user_rating_rank = IF(user_rating = 0,0, ((".$avg_user['count']*$avg_user['rating'].") + (user_rating_count * user_rating)) / (".$avg_user['count']." + user_rating_count)),
                editor_rating_rank = IF(editor_rating = 0,0,((".$avg_editor['count']*$avg_editor['rating'].") + (editor_rating_count * editor_rating)) / (".$avg_editor['count']. " + editor_rating_count))
        ";

        if(!$Model->query($query))
        {
            $response['msg'] = 'There was a problem updating the #__jreviews_listing_totals table with the Bayesian ratings.';
            $response['success'] = false;
            return cmsFramework::jsonResponse($response);
        }

        return cmsFramework::jsonResponse($response);
    }
}
