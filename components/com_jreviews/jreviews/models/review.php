<?php
/**
 * JReviews - Reviews Extension
 * Copyright (C) 2010-2015 ClickFWD LLC
 * This is not free software, do not distribute it.
 * For licencing information visit https://www.jreviews.com
 * or contact sales@jreviews.com
**/

defined( 'MVC_FRAMEWORK') or die( 'Direct Access to this location is not allowed.' );

class ReviewModel extends MyModel {

    var $name = 'Review';

    var $useTable = '#__jreviews_comments AS Review';

    var $primaryKey = 'Review.review_id';

    var $realKey = 'id';

    var $conditions = array();

//    var $group = array('Review.id');

    var $runProcessRatings = true;

    var $valid_fields = array(); // Review fields

    var $_SIMPLE_SEARCH_FIELDS = array(
            'title'         =>'Review.title',
            'comment'       =>'Review.comments',
            'owner_reply'   =>'Review.owner_reply_text'
        );

    function __construct()
    {
        parent::__construct();

        if(!class_exists('UserModel'))
        {
            S2App::import('Model','user','jreviews');
        }

        $this->fields = array(
            'Review.id AS `Review.review_id`',
            'Review.pid AS `Review.listing_id`',
            'Review.mode AS `Review.extension`',
            'Review.created AS `Review.created`',
            'Review.modified AS `Review.modified`',
            'Review.userid AS `Review.user_id`',
            'User.' . UserModel::_USER_ID . ' AS `User.user_id`',
            'CASE WHEN CHAR_LENGTH(User. ' . UserModel::_USER_REALNAME . ') THEN User. ' . UserModel::_USER_REALNAME . ' ELSE Review.name END AS `User.name`',
            'CASE WHEN CHAR_LENGTH(User. ' . UserModel::_USER_ALIAS . ') THEN User.' . UserModel::_USER_ALIAS . ' ELSE Review.username END AS `User.username`',
            'Review.email AS `User.email`',
            'Review.ipaddress AS `User.ipaddress`',
            'Rank.rank AS `User.review_rank`',
            'Rank.reviews AS `User.review_count`',
            'Review.title AS `Review.title`',
            'Review.comments AS `Review.comments`',
            'Review.posts AS `Review.posts`',
            'Review.author AS `Review.editor`',
            'Review.published AS `Review.published`',
            'Review.listing_type_id AS `Review.listing_type_id`',
            'IF(Review.rating=0,"na",Review.rating) AS `Rating.average_rating`',
            'Review.vote_helpful AS `Vote.yes`',
            '(Review.vote_total - Review.vote_helpful) AS `Vote.no`',
            '(Review.vote_helpful/Review.vote_total)*100 AS `Vote.helpful`',
            'Review.owner_reply_text AS `Review.owner_reply_text`',
            'Review.owner_reply_approved AS `Review.owner_reply_approved`',
            'Review.owner_reply_created AS `Review.owner_reply_created`',
            'Review.media_count AS `Review.media_count`',
            'Review.video_count AS `Review.video_count`',
            'Review.photo_count AS `Review.photo_count`',
            'Review.audio_count AS `Review.audio_count`',
            'Review.attachment_count AS `Review.attachment_count`'
        );

        $this->joins = array(
            'User'=>'LEFT JOIN #__users AS User ON Review.userid = User.' . UserModel::_USER_ID,
            'Rank'=>'LEFT JOIN #__jreviews_reviewer_ranks AS Rank ON Review.userid = Rank.user_id'
        );
    }

    function addReviewInfo($results, $modelName, $reviewKey)
    {
        // First get the review ids
        foreach($results AS $key=>$row)
        {
            if(isset($row[$modelName][$reviewKey]))
            {
                $review_ids[$row[$modelName][$reviewKey]] = $row[$modelName][$reviewKey];
            }
        }

        if(!empty($review_ids))
        {
            $fields = $this->fields;

            $this->fields = array(
                'Review.id AS `Review.review_id`',
                'Review.title AS `Review.title`',
                'Review.userid AS `Review.user_id`',
                'Review.`mode` AS `Listing.extension`',
                'Review.pid AS `Listing.listing_id`',
            );

            $reviews = $this->findAll(array('conditions'=>array('Review.id IN ('.implode(',',$review_ids).')')),array());

            $reviews = $this->changeKeys($reviews,'Review','review_id');

            foreach($results AS $key=>$row)
            {
                if(isset($reviews[$row[$modelName][$reviewKey]]))
                {
                    $results[$key] = array_merge($results[$key],$reviews[$row[$modelName][$reviewKey]]);
                }
            }
        }

        return $results;
    }

    /*
    * Centralized review delete function
    * @param array $review_ids
    */
    function del($ids)
    {
        if(!is_array($ids)) {
            $ids = array($ids);
        }

        if (!empty($ids))
        {
            foreach($ids AS $id)
            {
                $success = false;

                $this->data['Review']['id'] = $id;

                $this->plgBeforeDelete('Review.id',$id); // Only works for single review deletion

                # delete associated media
                $Media = ClassRegistry::getClass('MediaModel');

                $Media->deleteByReviewId($id);
            }

            // Get listings info before review id is lost. Used to update the listing totals after deletion.
            $query = "
                SELECT
                    DISTINCT Review.pid AS listing_id, Review.mode AS extension
                FROM
                    #__jreviews_comments AS Review
                WHERE
                    Review.id IN (" . cleanIntegerCommaList($ids) . ")"
                ;

            $listings = $this->query($query, 'loadObjectList');

            $query = "
                DELETE
                    Review,
                    FieldReview,
                    ReviewRating,
                    Report,
                    Vote,
                    Discussion
                FROM
                    #__jreviews_comments AS Review
                LEFT JOIN
                    #__jreviews_review_fields AS FieldReview ON FieldReview.reviewid = Review.id
                LEFT JOIN
                    #__jreviews_review_ratings AS ReviewRating ON ReviewRating.review_id = Review.id
                LEFT JOIN
                    #__jreviews_reports AS Report ON Report.review_id = Review.id
                LEFT JOIN
                    #__jreviews_votes AS Vote ON Vote.review_id = Review.id
                LEFT JOIN
                    #__jreviews_discussions AS Discussion ON Discussion.review_id = Review.id
                WHERE
                    Review.id IN (".cleanIntegerCommaList($ids).")
            ";

            if($this->query($query)) {

                $success = true;

                // Clear cache
                cmsFramework::clearSessionVar('Review', 'findCount');

                cmsFramework::clearSessionVar('Discussion', 'findCount');

                cmsFramework::clearSessionVar('Media', 'findCount');

                // Update listing totals
                $err = array();

                foreach ( $listings as $listing )
                {
                    if (!$this->updateRatingAverages(array('listing_id'=>$listing->listing_id, 'extension'=>$listing->extension)) )
                    {
                        $err[] = $listing->listing_id;
                    }
                }

                clearCache('', 'views');

                clearCache('', '__data');
            }
        }

        return $success;
    }

    function getReviewExtension($review_id) {

        $query = "
            SELECT
                Review.`mode`
            FROM
                #__jreviews_comments AS Review
            WHERE
                Review.id = " . (int) $review_id
        ;

        return $this->query($query, 'loadResult');
    }

    static function scaleAdjustedRating($step,$range /* 0 is low, 1 is high */,$round = false)
    {
        $Config = Configure::read('JreviewsSystem.Config');

        $scale = $Config->rating_scale;

        $scale_modifier = $scale/5;

        if(!$step) return $step;

        if(!$range)
        {
            $value = $step == 1 ? 0.5 : $step * $scale_modifier - ($scale_modifier) / 2;
        }
        else {
            $value = $step * $scale_modifier + ($scale_modifier) / 2;
        }

        if($step == 5 && $range)
        {
            $value = min($value, $scale);
        }

        return $round ? round($value,0) : $value;
    }

    function getReviewRatingCounts($listing_id, $extension)
    {
        $Config = Configure::read('JreviewsSystem.Config');

        $scale = $Config->rating_scale;

        $scale_modifier = $scale/5;

        $query = "
            SELECT
                rating_range, count(*) AS count
            FROM
                (SELECT
                    CASE WHEN ROUND(rating,1) > 0 AND rating < " . self::scaleAdjustedRating(1,1) . " THEN 1
                         WHEN ROUND(rating,1) >= " . self::scaleAdjustedRating(2,0) . " AND ROUND(rating,1) < " . self::scaleAdjustedRating(2,1) . " THEN 2
                         WHEN ROUND(rating,1) >= " . self::scaleAdjustedRating(3,0) . " AND ROUND(rating,1) < " . self::scaleAdjustedRating(3,1) . " THEN 3
                         WHEN ROUND(rating,1) >= " . self::scaleAdjustedRating(4,0) . " AND ROUND(rating,1) < " . self::scaleAdjustedRating(4,1) . " THEN 4
                         WHEN ROUND(rating,1) >= " . self::scaleAdjustedRating(5,0) . " THEN 5
                    END as rating_range
                FROM
                    #__jreviews_comments
                WHERE
                pid = " . (int) $listing_id . "
                 AND published = 1
                 AND mode = " . $this->Quote($extension) . "
                 AND rating > 0
                 AND author = 0
                ) AS  ReviewRange
            GROUP BY
                rating_range
        ";

        $results = $this->query($query,'loadAssocList','rating_range');

        $j = 1;

        for($i=1;$i<=5;$i++)
        {
            $from = $scale <= 5 || $i == 1 ? $i : $j * $scale_modifier;

            $to = $i * $scale_modifier;

            $results[$i]['rating_range'] = $scale <= 5 ? $from : $from . '-' . $to;

            if($scale <= 5)
            {
                $results[$i]['rating_range'] = $i;
            }
            elseif($i == 5) {
                $results[$i]['rating_range'] = self::scaleAdjustedRating($i,0,true) . '-' . $scale;
            }
            else {
                $results[$i]['rating_range'] = self::scaleAdjustedRating($i,0,true) . '-' . self::scaleAdjustedRating($i,1,true);
            }

            if(!isset($results[$i]['count']))
            {
                $results[$i]['count'] = 0;
            }

            $j = $i;
        }

        krsort($results,SORT_NUMERIC);

        return $results;
    }

    function getReviewerTotal() {

        return $this->query("SELECT COUNT(*) FROM #__jreviews_reviewer_ranks", 'loadResult');
    }

    function getRankPage($page,$limit)
    {
        # Check for cached version
        $cache_prefix = 'review_model_rankpage';

        $cache_key = func_get_args();

        if($cache = S2cacheRead($cache_prefix,$cache_key)){
            return $cache;
        }

        $offset = (int)($page-1)*$limit;

        S2App::import('Model','user','jreviews');

        $query = '
            SELECT
                User.' . UserModel::_USER_ID . ' AS `User.user_id`,
                User.' . UserModel::_USER_REALNAME . ' AS `User.name`,
                User.' . UserModel::_USER_ALIAS . ' AS `User.username`,
                Rank.reviews AS `Review.count`,
                Rank.votes_percent_helpful AS `Vote.helpful`,
                Rank.votes_total AS `Vote.count`
            FROM
                '. UserModel::_USER_TABLE .  ' AS User
            INNER JOIN
                #__jreviews_reviewer_ranks AS Rank ON Rank.user_id = User.' . UserModel::_USER_ID . '
            ORDER BY
               Rank.rank
            LIMIT
                ' . $offset . ',' . $limit
        ;

        $results = $this->query($query, 'loadObjectList');

        $results = $this->__reformatArray($results);

        # Add Community info to results array
        if(!defined('MVC_FRAMEWORK_ADMIN') && class_exists('CommunityModel'))
        {
            $Community = ClassRegistry::getClass('CommunityModel');

            $results = $Community->addProfileInfo($results, 'User', 'user_id');
        }

        # Send to cache
        S2cacheWrite($cache_prefix,$cache_key,$results);

        return $results;
    }

    function cleanupReviewRatings($options = array())
    {
        $listing_id = Sanitize::getInt($options,'listing_id');

        $extension = Sanitize::getString($options,'extension');

        if (!$listing_id)
        {
            // Create missing rows from jreviews_review_ratings for reviews that have a zero rating for listing types that can be rated
            $query = '
                INSERT INTO #__jreviews_review_ratings (
                    listing_id,
                    review_id,
                    extension,
                    criteria_id,
                    rating
                )
                    SELECT
                        Review.pid AS listing_id,
                        Review.id AS review_id,
                        Review.mode AS extension,
                        CriteriaRating.criteria_id AS criteria_id,
                        Review.rating AS rating
                    FROM
                        #__jreviews_comments AS Review
                    LEFT JOIN
                        #__jreviews_criteria_ratings AS CriteriaRating ON CriteriaRating.listing_type_id = Review.listing_type_id
                    LEFT JOIN
                        #__jreviews_criteria AS ListingType ON Review.listing_type_id = ListingType.id
                    WHERE
                        ListingType.state = 1
                        AND Review.rating = 0
                    ORDER BY NULL
                ON DUPLICATE KEY UPDATE
                    review_id = VALUES(review_id)
            ';

            $this->query($query);
        }

        // Delete all rating criteria rows for criteria ids that no longer exist

        $query = "
            DELETE
                ReviewRating
            FROM
                #__jreviews_review_ratings AS ReviewRating
            WHERE
                ReviewRating.criteria_id NOT IN (SELECT criteria_id FROM #__jreviews_criteria_ratings)
        ";

        $this->query($query);

        $query = "
            DELETE
                ListingRating
            FROM
                #__jreviews_listing_ratings AS ListingRating
            WHERE
                ListingRating.criteria_id NOT IN (SELECT criteria_id FROM #__jreviews_criteria_ratings)
        ";

        $this->query($query);

        // Reset back to zero all rating related info for listings that no longer have reviews

        $where = '';

        if($listing_id && $extension != '')
        {
            $where = " WHERE (Total.listing_id = " . (int) $listing_id . " AND Total.extension = " . $this->Quote($extension) . ")";
        }

        // Reset all rating and review info for listings without reviews
        // Nov 7, 2016 - Added the key hint to the #__jreviews_comments LEFT JOIN for improved performance when MySQL does not automatically use the 'listing' key
        $query = "
            UPDATE
                #__jreviews_listing_totals AS Total
            INNER JOIN (
                SELECT
                    listing_id, extension
                FROM
                    #__jreviews_listing_totals AS Total
                LEFT JOIN
                    #__jreviews_comments AS Review USE INDEX (listing) ON Review.pid = Total.listing_id AND Review.mode = Total.extension
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
            " . $where . "
        ";

        return $this->query($query);
    }

    function updateReviewRatingAverage($options = array())
    {
        $where = '';

        $listing_id = Sanitize::getInt($options,'listing_id');

        $extension = Sanitize::getString($options,'extension');

        // Only update individual review ratings when performing a global update
        // Not when adding new review or listing

        if(empty($options))
        {
            $query = "
                UPDATE
                    #__jreviews_comments AS Review
                INNER JOIN (
                    SELECT
                        ReviewRating.review_id AS review_id,
                        IF(SUM(CriteriaRating.weight) > 0,
                            SUM(ReviewRating.rating * CriteriaRating.weight)/SUM(IF(ReviewRating.rating>0,CriteriaRating.weight,0)),
                            SUM(ReviewRating.rating)/SUM(IF(ReviewRating.rating>0,1,0))
                        ) AS rating
                    FROM
                        #__jreviews_review_ratings AS ReviewRating
                    LEFT JOIN
                        #__jreviews_criteria_ratings AS CriteriaRating ON CriteriaRating.criteria_id = ReviewRating.criteria_id
                    WHERE 1 = 1
                    " . ( $where != '' ? " AND ( " . $where . ")" : '') . "
                    GROUP BY
                        ReviewRating.review_id
                ) AS Rating ON Rating.review_id = Review.id
                SET Review.rating = Rating.rating
            ";

            return $this->query($query);
        }

        return true;
    }

    function updateListingCriteriaAverages($options = array())
    {
        $where = '';

        $listing_id = Sanitize::getInt($options,'listing_id');

        $extension = Sanitize::getString($options,'extension');

        $listing_type_id = Sanitize::getInt($options,'listing_type_id');

        if($listing_type_id)
        {
            $where = " AND (Review.listing_type_id = " . (int) $listing_type_id . ")";
        }

        if($listing_id && $extension != '')
        {
            $where = " AND (ReviewRating.listing_id = " . (int) $listing_id . " AND ReviewRating.extension = " . $this->Quote($extension) . ")";
        }

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
                " . $where . /*
                    // Commented because otherwise criteria withour ratings is not updated
                    AND ReviewRating.rating > 0
                */
                "GROUP BY
                    ReviewRating.listing_id,
                    ReviewRating.extension,
                    ReviewRating.criteria_id
                ORDER BY NULL
            ON DUPLICATE KEY UPDATE
                user_rating = VALUES(user_rating),
                user_rating_count = VALUES(user_rating_count),
                editor_rating = VALUES(editor_rating),
                editor_rating_count = VALUES(editor_rating_count)
        ";

        return $this->query($query);
    }

    /**
     * Run on delete, publish of reviews before 'updateListingTotalAverages' to ensure that if no published reviews
     * are left the totals will reflect this
     * @param  (int) $id The listing ID
     */
    function resetListingRatingTotals($listing_id, $extension)
    {
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
            VALUES (" . (int) $listing_id . "," . $this->Quote($extension) . ",0,0,0,'',0,0,0,0,'',0)
            ON DUPLICATE KEY UPDATE
                user_rating = 0,
                user_rating_count = 0,
                user_comment_count = 0,
                user_criteria_rating = '',
                user_criteria_rating_count = 0,
                editor_rating = 0,
                editor_rating_count = 0,
                editor_comment_count = 0,
                editor_criteria_rating = '',
                editor_criteria_rating_count = 0
        ";

        return $this->query($query);
    }

    function updateListingTotalAverages($options = array())
    {
        $where = '';

        $listing_id = Sanitize::getInt($options,'listing_id');

        $extension = Sanitize::getString($options,'extension');

        if($listing_id && $extension != '')
        {
            // Reset ratings totals for the specified listing

            $this->resetListingRatingTotals($listing_id, $extension);

            $where = " AND (Review.pid = " . (int) $listing_id . " AND Review.mode = " . $this->Quote($extension) . ")";
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
                " /* Calculation for count below takes into account whether some or all optional rating criteria were rated at all */ ."
                MAX(user_rating_count) AS user_rating_count,
                MAX(user_comment_count) AS user_comment_count,
                GROUP_CONCAT(user_rating ORDER BY criteria_order) AS user_criteria_rating,
                GROUP_CONCAT(user_criteria_rating_count ORDER BY criteria_order) AS user_criteria_rating_count,
                IF(editor_weight>0 AND editor_rating>0,(SUM(editor_rating * editor_weight)/SUM(editor_weight)),SUM(editor_rating)/SUM(IF(editor_rating>0,1,0))) AS editor_rating,
                " /* Calculation for count below takes into account whether some or all optional rating criteria were rated at all */ ."
                MAX(editor_rating_count) AS editor_rating_count,
                MAX(editor_comment_count) AS editor_comment_count,
                GROUP_CONCAT(editor_rating ORDER BY criteria_order) AS editor_criteria_rating,
                GROUP_CONCAT(editor_criteria_rating_count ORDER BY criteria_order) AS editor_criteria_rating_count
            FROM (
                SELECT
                    Review.pid AS listing_id,
                    Review.mode AS extension,
                    ReviewRating.criteria_id,
                    CriteriaRating.ordering AS criteria_order,
                    IFNULL( ROUND(SUM(IF(Review.author=0,ReviewRating.rating,0))/SUM(IF(Review.author=0 AND ReviewRating.rating>0,1,0)), 4), 0) AS user_rating,
                    SUM(IF(Review.author=0 AND ReviewRating.rating>0,1,0)) AS user_rating_count,
                    SUM(IF(Review.author=0 AND ReviewRating.rating>0,1,0)) AS user_criteria_rating_count,
                    SUM(IF(Review.author=0,1,0)) AS user_comment_count,
                    " /* Need to nullify weights if the rating is zero so when dividing by sum of weighs in weighed average these are ignored */ ."
                    IF(IFNULL(ROUND(SUM(IF(Review.author=0,ReviewRating.rating,0))/SUM(IF(Review.author=0 AND ReviewRating.rating>0,1,0)), 4), 0)=0,0,CriteriaRating.weight) AS user_weight,
                    IFNULL( ROUND(SUM(IF(Review.author=1,ReviewRating.rating,0))/SUM(IF(Review.author=1 AND ReviewRating.rating>0,1,0)), 4), 0) AS editor_rating,
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
                " . $where . "
                GROUP BY
                    # We use the Review table in group by so that comments without ratings are also included in the results
                    Review.pid,
                    Review.mode,
                    ReviewRating.criteria_id
                ORDER BY NULL
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

        $result =  $this->query($query);

        return $result;
    }

    function updateBayesianAverages($options = array())
    {
        $Config = Configure::read('JreviewsSystem.Config');

        $scale = $Config->rating_scale;

        $listing_id = Sanitize::getInt($options,'listing_id');

        $extension = Sanitize::getString($options,'extension');

        $user_review_bayesian_tuning = Sanitize::getString($Config,'user_review_bayesian_tuning',0.25);

        $editor_review_bayesian_tuning = Sanitize::getString($Config,'editor_review_bayesian_tuning',0.25);

        $bayesian_exclude_unrated = Sanitize::getInt($Config,'bayesian_exclude_unrated',0);

        /**
         * We use half the rating scale as the smoothing target because for sites with high averages the formula doesn't add good ranks for ratings that are close to each other
         */

        $smoothingFactor = $scale/2;

        /**************************************************************************
         *  Add missing listing total rows for com_content listings
         **************************************************************************/

        S2App::import('Model','listing_total','jreviews');

        $ListingTotalModel = ClassRegistry::getClass('ListingTotalModel');

        $ListingTotalModel->completeRows('com_content', $listing_id);

        /**************************************************************************
         *  For individual criteria
         **************************************************************************/

        $avg_user = $avg_editor = array();

        $query = "
            SELECT
                criteria_id
            FROM
                #__jreviews_criteria_ratings
        ";

        $criteria_ids = $this->query($query,'loadColumn');

        // Don't do anything if there aren't any rating criteria in the system

        if(!$criteria_ids) return;

        $where = '';

        if($listing_id && $extension != '')
        {
            $where = " AND (listing_id = " . (int) $listing_id . " AND extension = " . $this->Quote($extension) . ")";

        }

        /*
        $query = "
            SELECT
                criteria_id,
                SUM(user_rating * user_rating_count)/SUM(user_rating_count) AS user,
                SUM(editor_rating * editor_rating_count)/SUM(editor_rating_count) AS editor
            FROM
                #__jreviews_listing_ratings
            WHERE
                criteria_id IN (" . $this->Quote($criteria_ids) . ")
            " . $where . "
            " . ($bayesian_exclude_unrated ? ' AND user_rating > 0 AND editor_rating > 0' : '') . "
            GROUP BY criteria_id
        ";

        $avg_criteria_rating = $this->query($query,'loadAssocList','criteria_id');
        */

        $m_user = (float) $user_review_bayesian_tuning;

        $m_editor = (float) $editor_review_bayesian_tuning;

        foreach($criteria_ids AS $criteria_id)
        {
            // if(!isset($avg_criteria_rating[$criteria_id])) continue;

            /*
            http://masanjin.net/blog/how-to-rank-products-based-on-user-input
            s=(Rv+Cm)/(v+m)
            R is the average vote for the item,
            v is the number of votes,
            C is the smoothing target,
            m is a tuning parameter that controls how quickly the score moves away from C as the number of votes increase
            if we set C to the average vote over all items (or over some representative class of items), we get the behavior we wanted above:
            low-vote items start life near the middle of the herd, not near the bottom, and make their way up or down the list as the votes come in
             */

            if(!$bayesian_exclude_unrated)
            {
                $query = "
                    UPDATE
                        #__jreviews_listing_ratings
                    SET
                        user_rating_rank = IF(user_rating = 0, 0, (user_rating * user_rating_count + ".$smoothingFactor*$m_user.")/(user_rating_count + ".$m_user.") ),
                        editor_rating_rank = IF(editor_rating = 0, 0, (editor_rating * editor_rating_count + ".$smoothingFactor*$m_editor.")/(editor_rating_count + ".$m_editor.") )
                    WHERE
                        criteria_id = " . $criteria_id
                    . $where
                ;
            }
            else {

                $query = "
                    UPDATE
                        #__jreviews_listing_ratings
                    SET
                        user_rating_rank = (user_rating * user_rating_count + ".$smoothingFactor*$m_user.")/(user_rating_count + ".$m_user."),
                        editor_rating_rank = (editor_rating * editor_rating_count + ".$smoothingFactor*$m_editor.")/(editor_rating_count + ".$m_editor.")
                    WHERE
                        criteria_id = " . $criteria_id
                    . $where
                ;
            }

            if(!$this->query($query))
            {
                return false;
            }
        }

        /**************************************************************************
         *  For listing overall rating
         **************************************************************************/

        /*
        $query = "
            SELECT
                SUM(user_rating * user_rating_count)/SUM(user_rating_count) AS user,
                SUM(editor_rating * editor_rating_count)/SUM(editor_rating_count) AS editor
            FROM
                #__jreviews_listing_totals
            WHERE 1 = 1
            " . ($bayesian_exclude_unrated ? ' AND user_rating > 0 AND editor_rating > 0' : '')
        ;

        $avg_rating = $this->query($query,'loadAssoc');
        */

        /*
        http://masanjin.net/blog/how-to-rank-products-based-on-user-input
        s=(Rv+Cm)/(v+m)
        R is the average vote for the item,
        v is the number of votes,
        C is the smoothing target,
        m is a tuning parameter that controls how quickly the score moves away from C as the number of votes increase
        if we set C to the average vote over all items (or over some representative class of items), we get the behavior we wanted above:
        low-vote items start life near the middle of the herd, not near the bottom, and make their way up or down the list as the votes come in
         */

        $m_user = (float) $user_review_bayesian_tuning;

        $m_editor = (float) $editor_review_bayesian_tuning;

        if($bayesian_exclude_unrated)
        {
            $query = "
                UPDATE
                    #__jreviews_listing_totals
                SET
                    user_rating_rank = IF(user_rating = 0, 0, (user_rating * user_rating_count + ".$smoothingFactor*$m_user.")/(user_rating_count + ".$m_user.")),
                    editor_rating_rank = IF(editor_rating = 0, 0, (editor_rating * editor_rating_count + ".$smoothingFactor*$m_editor.")/(editor_rating_count + ".$m_editor."))
                WHERE
                    1 = 1
                " . $where
            ;
        }
        else {
            $query = "
                UPDATE
                    #__jreviews_listing_totals
                SET
                    user_rating_rank = (user_rating * user_rating_count + ".$smoothingFactor*$m_user.")/(user_rating_count + ".$m_user."),
                    editor_rating_rank = (editor_rating * editor_rating_count + ".$smoothingFactor*$m_editor.")/(editor_rating_count + ".$m_editor.")
                WHERE
                    1 = 1
                " . $where
            ;
        }

        return $this->query($query);
    }

    /**
     * Updates rating calculations after any kind of reviews update (save, publish, delete, change weights etc.)
     * @return    boolean
     */
    function updateRatingAverages($options = array())
    {
        $listing_id = Sanitize::getInt($options,'listing_id');

        $extension = Sanitize::getString($options,'extension');

        // Clean up - removes orphaned ReviewRating and ListingRating rows and resets rating and review totals in ListingTotals for listings without reviews

        if(!$this->cleanupReviewRatings($options))
        {
            return false;
        }

        // Recalculates review rating averages

        if(!$this->updateReviewRatingAverage($options))
        {
            return false;
        }

        // Recalculates listing rating averages by criteria

        if(!$this->updateListingCriteriaAverages($options))
        {
            return false;
        }

        // Recalculates listing rating/review totals by criteria

        if(!$this->updateListingTotalAverages($options))
        {
            return false;
        }

        // Recalculates bayesian averages per criteria and for each listing

        if(!$this->updateBayesianAverages($options))
        {
            return false;
        }

        return true;
    }

    function processSorting($selected = null)
    {
        $order = '';

        if(preg_match('/' . S2_QVAR_PREFIX_RATING_CRITERIA . '-(?P<criteria_id>\d+)/', $selected, $matches))
        {
            if($criteria_id = $matches['criteria_id'])
            {
                $selected = 'criteria_rating';
            }
        }

        switch ( $selected )
        {
            case 'rdate':
                $order = '`Review.created` DESC';
                break;

            case 'date':
                $order = '`Review.created` ASC';
                break;

            case 'rating':
                $order = '`Rating.average_rating` DESC, `Review.created` DESC';
                break;

            case 'rrating':
                $order = '`Rating.average_rating` ASC, `Review.created` DESC';
                break;

            case 'helpful':
                $order = '`Vote.helpful` DESC, `Rating.average_rating` DESC';
                break;

            case 'rhelpful':
              $order = '`Vote.helpful` ASC, `Rating.average_rating` DESC';
                break;

            case 'discussed':
                $order = '`Review.posts` DESC, `Rating.average_rating` DESC';
                break;

            case 'updated':
                $order = '`Review.modified` DESC, `Review.created` DESC';
                break;

            case 'criteria_rating':

                    $this->useTable = '#__jreviews_review_ratings AS ReviewRating';

                    $order = 'ReviewRating.rating DESC, ReviewRating.review_id DESC';

                    $this->conditions[] = 'ReviewRating.rating > 0 AND ReviewRating.criteria_id = ' . $criteria_id;

                    array_unshift($this->joins, "LEFT JOIN #__jreviews_comments AS Review ON Review.id = ReviewRating.review_id");

                break;

            default:
                $order = '`Review.created` DESC';
                break;
        }

        return $order;
    }

    function processRatingRangeSearch($rating, & $conditions)
    {
        $Config = Configure::read('JreviewsSystem.Config');

        $scale = $Config->rating_scale;

        // Jan 8, 2017 - Fix for high range criteria rating search when scale is higher than 5

        if(self::scaleAdjustedRating($rating,1) < $scale)
        {
            $conditions[] = "(Review.rating >= " . self::scaleAdjustedRating($rating,0) . " AND Review.rating < " . self::scaleAdjustedRating($rating,1) . ")";
        }
        else {
            $conditions[] = "(Review.rating >= " . self::scaleAdjustedRating($rating,0) . " AND Review.rating <= " . self::scaleAdjustedRating($rating,1) . ")";
        }
    }

    function publish($review_id, $include_reject_state = false)
    {
        $result = array('success'=>false,'state'=>null,'access'=>true);

        $review_id = (int) $review_id;

        if(!$review_id) return $result;

        # Load current listing publish state and author id
        $this->runProcessRatings = false;

        $review = $this->findRow(array('conditions'=>array('Review.id = ' . $review_id)));

        if($review)
        {
            # Check access
            $Access = Configure::read('JreviewsSystem.Access');

            if(!$Access->isManager())
            {
                $result['access'] = false;
                return $result;
            }

            $data['Review']['id'] = $review['Review']['review_id'];

            $data['Review']['mode'] = $review['Review']['extension'];

            $data['Review']['pid'] = $review['Review']['listing_id'];

            // Define toggle states
            if($include_reject_state) {

                if($review['Review']['published'] == 1) {

                    $data['Review']['published'] = $result['state'] = 0;
                }
                elseif($review['Review']['published'] == 0) {

                    $data['Review']['published'] = $result['state'] = -2;
                }
                elseif($review['Review']['published'] == -2) {

                    $data['Review']['published'] = $result['state'] = 1;
                }
            }
            else {

                $data['Review']['published'] = $result['state'] = (int)!$review['Review']['published'];
            }

            # Update state
            if($this->store($data))
            {
                // clear cache
                clearCache('', 'views');
                clearCache('', '__data');

                $result['success'] = true;
            }
        }

        return $result;
    }

    function updatePostCount($review_id,$value)
    {
        if($value != 0)
        {
            $query = "
                UPDATE
                    #__jreviews_comments AS Review
                SET
                    Review.posts = Review.posts " . ($value == 1 ? '+1' : '-1') . "
                WHERE
                    Review.id = ". (int) $review_id
            ;

            return $this->query($query);
        }
    }


    /**
     * Updates votes count in the relevant review and calls the user rank update method
     * Called by afterSave method of the votes model
     *
     * @param int $reviewId cleaned in the votes controller
     * @param int $voteYes cleaned in the votes controller
     * @return bool Notice that there is no error handling for this yet in s2
     */
    function updateVoteHelpfulCount($reviewId, $voteYes)
    {
        $query = "
            UPDATE
                #__jreviews_comments
            SET
                vote_helpful = vote_helpful + $voteYes,
                vote_total = vote_total + 1
            WHERE
                id = $reviewId
        ";

         return (bool) $this->query($query);
    }

    /**
     * Rebuilds the user ranks table
     * Done by admin request or periodically
     *
     */
    function rebuildRanksTable()
    {
        S2App::import('Model', 'User', 'jreviews');

       $Config = Configure::read('JreviewsSystem.Config');

        // DELETE users that don't have any published reviews
        // DELETE guests which have no place in the ranks
        // DELETE users that no longer exist in the system

        $query = '
            DELETE
            FROM
                #__jreviews_reviewer_ranks
            WHERE
                user_id = 0
                OR user_id NOT IN (
                    SELECT
                        userid
                    FROM
                        #__jreviews_comments
                    WHERE
                        published = 1
                )
                OR user_id NOT IN (
                    SELECT ' . UserModel::_USER_ID . ' FROM ' . UserModel::_USER_TABLE . '
                )
        ';

        $this->query($query);

        // Update table with review and vote totals

        $query1 = '
            INSERT INTO
                #__jreviews_reviewer_ranks (user_id, reviews, votes_percent_helpful, votes_total)
                (
                    SELECT
                        userid AS user_id,
                        COUNT(*) AS reviews,
                        (SUM(vote_helpful)/SUM(vote_total)) AS votes_percent_helpful,
                        SUM(vote_total) AS votes_total
                    FROM
                        #__jreviews_comments AS Review
                    RIGHT JOIN
                        ' . UserModel::_USER_TABLE . ' AS User ON User.' . UserModel::_USER_ID . ' = Review.userid
                    WHERE
                        userid > 0
                        AND published = 1
                        ' . ( Sanitize::getInt($Config,'editor_rank_exclude') ? ' AND author = 0' : '' ) . '
                    GROUP BY
                        userid
                    ORDER BY
                        NULL
                )
            ON DUPLICATE KEY UPDATE
                reviews = VALUES(reviews),
                votes_percent_helpful = VALUES(votes_percent_helpful),
                votes_total = VALUES(votes_total);
        ';

        // UPDATE reviewer rank

        $query2 = "
            INSERT INTO
                #__jreviews_reviewer_ranks (user_id, rank)
                (
                    SELECT
                        user_id,
                        @curRank := @curRank + 1 AS rank
                    FROM
                        #__jreviews_reviewer_ranks, (SELECT @curRank := 0) r
                    ORDER BY
                        reviews DESC, votes_percent_helpful DESC
                )
            ON DUPLICATE KEY UPDATE
                    rank = VALUES(rank);
        ";

        if($this->query($query1))
        {
            appLogMessage('*******Reviewer ranks table rebuilt successfully at '.strftime(_CURRENT_SERVER_TIME_FORMAT, time()).' (unix time '.time().')', 'database');

            return $this->query($query2);
        }

        return false;
    }

    function save(&$data,$Access,$validFields = array())
    {
        $Config = Configure::read('JreviewsSystem.Config');

        $User = cmsFramework::getUser();

        $userid = $User->id;

        $this->valid_fields = $validFields;

        $referrer = Sanitize::getString($data,'referrer'); // Comes from admin editing

        # Check if this is a new review or an updated review
        $review_id = isset($data['Review']) ? Sanitize::getInt($data['Review'],'id') : 0;

        $isNew = $review_id ? false : true;

        $output = array("success" =>false, "reviewid" => '', "author" => 0 );

        # If new then assign the logged in user info. Zero if it's a guest
        if ($isNew) {

            # Validation passed, so proceed with saving review to DB

            $data['Review']['ipaddress'] = s2GetIpAddress();

            $data['Review']['userid'] = $userid;

            $data['Review']['created'] = gmdate('Y-m-d H:i:s');
        }

        # Edited review
        if(!$isNew)
        {
            appLogMessage('*********Load current info because we are editing the review','database');

            // Load the review info
            $row = $this->findRow(array(
                'conditions'=>array('Review.id = ' . $review_id)
            ));

            $data['ratings_col_empty'] = empty($row['Rating']['ratings']); // Used in afterFind

            // Capture ip address of reviewer
            if ($userid == $row['User']['user_id']) {

                $data['Review']['ipaddress'] = s2GetIpAddress();
            }

            $referrer != 'moderation' && $data['Review']['modified'] = gmdate('Y-m-d H:i:s'); // Capture last modified date

            $data['Review']['author'] = $row['Review']['editor'];
        }

        # Complete user info for new reviews
        if ($isNew && $userid > 0)
        {
            $data['Review']['name'] = $User->name;

            $data['Review']['username'] = $User->username;

            $data['Review']['email'] = $User->email;
        }
        elseif(!$isNew && !$Access->isManager()) {

            unset($data['Review']['name']);

            unset($data['Review']['username']);

            unset($data['Review']['email']);
        }

        if(!defined('MVC_FRAMEWORK_ADMIN'))
        {
            $data['Review']['published'] = (int) ! (
                    ( $Access->moderateReview() && $isNew && !$data['Review']['author'] )
                ||    ( $Config->moderation_editor_reviews && $isNew && $data['Review']['author'] )
                ||    ( $Access->moderateReview() && $Config->moderation_review_edit && !$isNew && !$data['Review']['author'] )
                ||    ( $Access->moderateReview() && $Config->moderation_editor_review_edit && !$isNew && $data['Review']['author'] )
            );
        }

        $data['new'] = $isNew ? 1 : 0;

        // Calculate the (weighted) average rating for the review

        if(isset($data['Criteria']) && Sanitize::getInt($data['Criteria'],'state') == 1 && isset($data['Rating']))
        {
            $ratings = array_filter($data['Rating']['ratings'],'is_numeric');

            if(!empty($ratings))
            {
                $weights = array_intersect_key($data['Criteria']['weights'], $ratings);

                $ratings_qty = count($ratings);

                $weights_sum = array_sum($weights);

                if($weights_sum == 0)
                {
                    $data['Review']['rating'] = array_sum($ratings)/count($ratings);
                }
                else {

                    $weighted_average = 0;

                    foreach ($ratings  as $key=>$rating)
                    {
                        $weighted_average += $rating * $weights[$key] / $weights_sum;
                    }

                    $data['Review']['rating'] = $weighted_average;
                }
            }
       }

        # Save standard review fields

        appLogMessage('*******Save standard review fields','database');

        $save = $this->store($data);

        if(!$save) {

            appLogMessage('*******There was a problem saving the review fields','database');

        }
        else {

            $output['success'] = true;
        }

        return $output;
    }

    /**
    * Saves review ratings, fields and recalculates listing totals
    *
    * @param mixed $status
    */
    function afterSave($status)
    {
        $isNew = Sanitize::getBool($this->data,'new');

        $moderation = Sanitize::getBool($this->data,'moderation');

        $ratings_col_empty = Sanitize::getBool($this->data,'ratings_col_empty');

        clearCache('','__data');

        clearCache('','views');

        // Update the last modified date for the listing for sitemap purposes

        if(($isNew || $moderation) && Sanitize::getInt($this->data['Review'],'published') && Sanitize::getString($this->data['Review'],'mode') == 'com_content')
        {
            $ListingModel = ClassRegistry::getClass('EverywhereComContentModel');

            $ListingModel->updateModifiedDate($this->data['Review']['pid']);
        }

        if(isset($this->data['Criteria']) && Sanitize::getInt($this->data['Criteria'],'state') == 1 && isset($this->data['Rating']))
        {
           // Insert/update records in the review_ratings table

            $ratings = $this->data['Rating']['ratings'];

            $criteria_rating_rows = array();

            $listing_id = $this->data['Review']['pid'];

            $review_id = $this->data['Review']['id'];

            $extension = $this->Quote($this->data['Review']['mode']);

            foreach($ratings AS $criteria_id=>$rating)
            {
                $rating = $rating == 'na' ? 0 : $rating;

                $criteria_rating_rows[] = "({$listing_id},{$review_id},{$extension},{$criteria_id},{$rating})";
            }

            $query = "
                INSERT INTO #__jreviews_review_ratings (listing_id, review_id, extension, criteria_id, rating)
                      VALUES
                      " . implode(',', $criteria_rating_rows) . "
                ON DUPLICATE KEY UPDATE
                     criteria_id = VALUES(criteria_id),
                     rating = VALUES(rating)
            ";

            if(!$this->query($query)) return false;

            // Update rating averages

            if(!$this->updateRatingAverages(array('listing_id'=>$this->data['Review']['pid'], 'extension'=>$this->data['Review']['mode'])))
            {
                return false;
            }
        }
        // If it's a comment, then only update the listing totals
        else {

            // Recalculates listing rating/review totals by criteria

            if(!$this->updateListingTotalAverages(array('listing_id'=>$this->data['Review']['pid'], 'extension'=>$this->data['Review']['mode'])))
            {
                return false;
            }
        }

        # Save custom fields
        appLogMessage('*******Save review custom fields','database');

        $this->data['Field']['Review']['reviewid'] = $this->data['Review']['id'];

        S2App::import('Model','field','jreviews');

        $FieldModel = ClassRegistry::getClass('FieldModel');

        if(count($this->data['Field']['Review'])> 1 && !$FieldModel->save($this->data, 'review', $isNew, $this->valid_fields))
        {
            return false;
        }
    }

    function afterFind($results)
    {
        if (empty($results)) {
            return $results;
        }

        S2App::import('Model',array('criteria','field'),'jreviews');

        # Add Community Builder info to results array
        if(!defined('MVC_FRAMEWORK_ADMIN') && class_exists('CommunityModel'))
        {
            $Community = ClassRegistry::getClass('CommunityModel');

            $results = $Community->addProfileInfo($results, 'User', 'user_id');
        }

        # Add listing type and criteria rating info to results array

        $ListingTypes = ClassRegistry::getClass('CriteriaModel');

        $results = $ListingTypes->addListingTypes($results,'Review');

        # Add individual criteria ratings and reformat the array

        $review_ids = array_keys($results);

        $query = "
            SELECT
                review_id, criteria_id, IF(rating=0,'na',rating) AS rating
            FROM
                #__jreviews_review_ratings
            WHERE
                review_id IN (" . $this->Quote($review_ids) . ")
        ";

        $ratings = $this->query($query,'loadAssocList');

        foreach($results AS $key=>$result)
        {
            $results[$key]['Rating']['ratings'] = array();
        }

        foreach($ratings AS $rating_row)
        {
            extract($rating_row);

            $results[$review_id]['Rating']['ratings'][$criteria_id] = $rating == 0 ? 'na' : $rating;

            $results[$review_id]['Rating']['criteria_rating_count'][$criteria_id] = 1;
        }

        # Add media info

        if($this->runAfterFindModel('Media') && class_exists('MediaModel'))
        {
            $Config = ClassRegistry::getClass('ConfigComponent');

            $Media = ClassRegistry::getClass('MediaModel');

            $results = $Media->addMedia(
                $results,
                'Review',
                'review_id',
                array(
                    'sort'=>Sanitize::getString($Config,'media_general_default_order_listing'),
                    'controller'=>Sanitize::getString($this,'controller_name'),
                    'action'=>Sanitize::getString($this,'controller_action'),
                    'photo_limit'=>Sanitize::getInt($Config,'media_review_photo_limit'),
                    'video_limit'=>Sanitize::getInt($Config,'media_review_video_limit'),
                    'attachment_limit'=>Sanitize::getInt($Config,'media_review_attachment_limit'),
                    'audio_limit'=>Sanitize::getInt($Config,'media_review_audio_limit'),
                )
            );
        }

        if($this->runAfterFindModel('Field'))
        {
            # Add custom field info to results array

            $CustomFields = new FieldModel();

            $results = $CustomFields->addFields($results,'review');
        }

        $this->clearAllAfterFindModel();

        return $results;
    }

    // For backwards compatibility so old code doesn't break
    // All processing is already done in the afterFind method

    function processRatings($results)
    {
        return $results;
    }
}
