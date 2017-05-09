<?php
/**
 * JReviews - Reviews Extension
 * Copyright (C) 2010-2015 ClickFWD LLC
 * This is not free software, do not distribute it.
 * For licencing information visit https://www.jreviews.com
 * or contact sales@jreviews.com
**/

defined( 'MVC_FRAMEWORK') or die( 'Direct Access to this location is not allowed.' );

class EverywhereComEasysocialEventModel extends MyModel  {

    const _LISTING_ID = 'id';

	var $UI_name = 'EasySocial - Events';

	var $name = 'Listing';

	var $useTable = '#__social_clusters AS Listing';

	var $primaryKey = 'Listing.listing_id';

	var $realKey = 'id';

	/**
	 * Used for listing module - latest listings ordering
	 */
	var $dateKey = 'registerDate';

	/**
	 * This is the component's url option parameter
	 *
	 * @var string
	 */
	var $extension = 'com_easysocial_event';

	/**
	 * This is the value stored in the reviews table to differentiate the source of the reviews
	 *
	 * @var string
	 */
	var $extension_alias = 'com_easysocial_event';

	var $categoryPrimaryKey = 'id';

	var $fields = array(
		'Listing.id AS `Listing.listing_id`',
		'Listing.title'=>'Listing.title AS `Listing.title`',
        'Listing.alias'=>'Listing.alias AS `Listing.slug`',
		'Listing.creator_uid AS `Listing.user_id`',
		'"0000-00-00" AS `Listing.publish_up`',
		'"0000-00-00" AS `Listing.publish_up`',
		'Listing.state AS `Listing.state`',
		'Avatar.large AS `Listing.images`',
		"'com_easysocial_event' AS `Listing.extension`",
		'JreviewsCategory.id AS `Listing.cat_id`',
		'cat_name'=>'Category.title AS `Category.title`',
		'JreviewsCategory.id AS `Category.cat_id`',
        'JreviewsCategory.criteriaid AS `Listing.listing_type_id`',
        // User reviews
        'user_rating'=>'Totals.user_rating AS `Review.user_rating`',
        'Totals.user_rating_count AS `Review.user_rating_count`',
        'Totals.user_criteria_rating AS `Review.user_criteria_rating`',
        'Totals.user_criteria_rating_count AS `Review.user_criteria_rating_count`',
        'Totals.user_comment_count AS `Review.review_count`'
    );

    var $conditions = array('Listing.cluster_type = "event"');

    var $profiles_title_col = 'title';

    /**
     * Used for detail listing page - not used for 3rd party components
     */
    var $joins = array(
        'Totals'=>"LEFT JOIN #__jreviews_listing_totals AS Totals ON Totals.listing_id = Listing.id AND Totals.extension = 'com_easysocial_event'",
        'LEFT JOIN #__social_clusters_categories AS Category ON Listing.category_id = Category.id AND Category.type = "event"',
        "INNER JOIN #__jreviews_categories AS JreviewsCategory ON JreviewsCategory.id = Category.id AND JreviewsCategory.`option` = 'com_easysocial_event'",
        'LEFT JOIN #__social_avatars AS Avatar ON Listing.id = Avatar.uid AND Avatar.type = "event"'
    );

    /**
     * Used to complete the listing information for reviews based on the Review.pid
     */
    var $joinsReviews = array(
        'LEFT JOIN #__social_clusters AS Listing ON Review.pid = Listing.id AND Listing.cluster_type = "event"',
        'LEFT JOIN #__social_clusters_categories AS Category ON Listing.category_id = Category.id AND Category.type = "event"',
        "INNER JOIN #__jreviews_categories AS JreviewsCategory ON JreviewsCategory.id = Category.id AND JreviewsCategory.`option` = 'com_easysocial_event'"
    );

    // var $joinsMedia = array(
    //     'LEFT JOIN #__social_clusters AS Listing ON Media.listing_id = Listing.id AND Listing.cluster_type = "group" AND Media.extension = "com_easysocial_event"',
    //     'LEFT JOIN #__social_clusters_categories AS Category ON Listing.category_id = Category.id AND Category.type = "group"',
    //     "INNER JOIN #__jreviews_categories AS JreviewsCategory ON JreviewsCategory.id = Category.id AND JreviewsCategory.`option` = 'com_easysocial_event'"
    // );

    var $joinsListingsModule = array();

    var $default_thumb  = 'media/com_easysocial/defaults/avatars/event/large.png';

    var $avatar_path = 'media/com_easysocial/avatars/event/';

    // Module controller includes the basic joins for review and rating information and others can be added here
    // depending on the fields used in the query

    public static $joinListingState = array();

    var $easysocialApp = array();

	function __construct()
	{
		parent::__construct();

		if(!self::exists()) return;

        $this->tag = __t("COMMUNITY_EVENT_TAG",true);  // Used in MyReviews page to differentiate from other component reviews

        $cache_key = s2CacheKey('easysocial_config');

        $CommunityConfig = S2Cache::read($cache_key, '_s2framework_core_');

        if(false == $CommunityConfig) {

            // Read the JomSocial configuration to determine the storage location for avatars
            $CommunityConfig = json_decode($this->query("SELECT value FROM #__social_config WHERE type = 'site'",'loadResult'),true);

            $CommunityConfigForJReviews = array(
                'user_avatar_storage'=>Sanitize::getString($CommunityConfig['storage'],'avatars') == 'amazon' ? 's3' : 'local',
                'storages3bucket'=>Sanitize::getString($CommunityConfig['storage']['amazon'],'bucket')
            );

            S2Cache::write($cache_key,$CommunityConfigForJReviews, '_s2framework_core_');
        }
        else {

            $CommunityConfigForJReviews = $CommunityConfig;
        }

        $this->avatar_storage = $CommunityConfigForJReviews['user_avatar_storage'];

        $this->s3_bucket = $CommunityConfigForJReviews['storages3bucket'];

        $this->easysocialApp = $this->getApp();
	}

	static public function exists()
	{
        if (file_exists(PATH_ROOT . 'components' . DS . 'com_easysocial' . DS . 'easysocial.php'))
        {
            require_once(JPATH_ROOT . '/administrator/components/com_easysocial/includes/foundry.php');

            return true;
        }

        return false;
	}

    protected function getApp()
    {
        $query = "
            SELECT
                id, alias
            FROM
                #__social_apps
            WHERE
                type = 'apps'
                AND element = 'jreviews'
                AND `group` = 'event'
                AND state = 1
        ";

        $app = $this->query($query, 'loadAssoc');

        return $app;
    }

    /**
     * Group review app URL
     */
	function listingUrl($listing)
	{
        $eventsModel = FD::model('Events');

        $events = $eventsModel->getEvents(array('inclusion' => array(
            $listing['Listing']['listing_id']
        )));

        $event = array_shift($events);

        $url = ESR::events( array( 'layout' => 'item' , 'id' => $event->getAlias()  , 'appId' => $this->easysocialApp['id'] . '-' . $this->easysocialApp['alias'] , 'sef' => false) , false );

        return $url;
	}

    // Used to check whether reviews can be posted by listing owners, owner replies

    function getListingOwner($result_id)
    {
        $query = "
        	SELECT
        		User.id user_id, User.name, User.email
            FROM
            	#__users AS User
            WHERE
            	User.id = " . (int) $result_id
        ;

        $owner = $this->query($query,'loadAssocList');

        return current($owner);
    }

	function afterFind($results)
	{
        if (empty($results))
        {
            return $results;
        }

        # Add listing type and criteria rating info to results array

        S2App::import('Model','criteria','jreviews');

        $ListingTypes = ClassRegistry::getClass('CriteriaModel');

        $results = $ListingTypes->addListingTypes($results,'Listing');

        $controller =  Sanitize::getString($this,'controller_name');

        $action =  Sanitize::getString($this,'controller_action');

		foreach($results AS $key=>$result)
		{
            // Add review counts for each rating (range in case scale is higher than 5)

            if(($controller == 'everywhere' && $action == 'index')
                ||
                ($controller == 'listings' && $action == 'detail'))
            {
                $ReviewModel = ClassRegistry::getClass('ReviewModel');

                $results[$key]['ReviewRatingCount'] = $ReviewModel->getReviewRatingCounts($key, $result['Listing']['extension']);
            }

            // Add detailed rating info

            // If $listing['Rating'] is already set we don't want to overwrite it because it's for individual reviews

            $results[$key]['RatingUser'] = array();

            if(!empty($result['CriteriaRating'])
                && isset($result['Review'])
                && !isset($result['Rating'])
                && ($result['Review']['user_rating_count'] > 0)
                )
            {
                 if($result['Review']['user_rating_count'] && (count($result['CriteriaRating']) == count(explode(',', $result['Review']['user_criteria_rating_count']))))
                {
                    $ratings = explode(',', $result['Review']['user_criteria_rating']);

                    array_walk($ratings, function(&$value) { if((float) $value == 0) $value = 'na'; });

                    $ratings = array_combine(array_keys($result['CriteriaRating']), array_values($ratings));

                    $ratings_count = explode(',', $result['Review']['user_criteria_rating_count']);

                    $ratings_count = array_combine(array_keys($result['CriteriaRating']), array_values($ratings_count));

                    $results[$key]['RatingUser'] = array(
                            'average_rating' => $result['Review']['user_rating'],
                            'ratings' => $ratings,
                            'criteria_rating_count' => $ratings_count
                        );
                }
            }

			// Process listing url

			$results[$key][$this->name]['url'] = $this->listingUrl($results[$key]);

            // Config overrides

            if(isset($result['ListingType']))
            {
                if(isset($results[$key]['ListingType']['config']['relatedlistings']))
                {
                    foreach($results[$key]['ListingType']['config']['relatedlistings'] AS $rel_key=>$rel_row)
                    {
                        isset($rel_row['criteria']) and $results[$key]['ListingType']['config']['relatedlistings'][$rel_key]['criteria'] = implode(',',$rel_row['criteria']);
                    }
                }
            }

			// Process images

            $images = $result['Listing']['images'];

            unset($results[$key]['Listing']['images']);

            $results[$key]['Listing']['images'] = array();

            if($this->avatar_storage == 's3' && $images != '' && $images != $this->default_thumb) {

                $imagePath = 'http://'.$this->s3_bucket.'.s3.amazonaws.com/' . $images;
            }
            elseif($images != '') {

                $imagePath = WWW_ROOT . $this->avatar_path . $key . _DS . $images;
            }
            else {

                $imagePath = WWW_ROOT . $this->default_thumb;
            }

            $results[$key]['Listing']['images'][] = array(
                'path'=>$imagePath,
                'caption'=>$results[$key]['Listing']['title'],
                'basepath'=>true,
                'skipthumb'=>$images != '' ? false : true
            );

            $results[$key]['MainMedia'] = array(
                'media_type'=>'photo',
                'title'=>$results[$key]['Listing']['title'],
                'everywhere'=>$imagePath
                );
		}

		return $results;
	}

	/**
	 * GROUPS MODE STARTS HERE
	 */
	function getNewCategories()
	{
		$query = "SELECT id FROM #__jreviews_categories WHERE `option` = '{$this->extension_alias}'";

        $exclude = $this->query($query,'loadColumn');

        $exclude = $exclude ? implode(',',$exclude) : '';

        $query = "
            SELECT
                Component.{$this->categoryPrimaryKey} AS value, Component.{$this->profiles_title_col} as text
		    FROM
                #__social_clusters_categories AS Component
		    LEFT JOIN
                #__jreviews_categories AS JreviewCategory ON Component.{$this->categoryPrimaryKey} = JreviewCategory.id AND JreviewCategory.`option` = '{$this->extension_alias}'
            WHERE
                Component.type = 'event'
		" . ($exclude != '' ? "
                    AND
                    Component.{$this->categoryPrimaryKey} NOT IN ($exclude)" : ''
        ) . "
		    ORDER BY
                Component.title ASC
        ";

        $results = $this->query($query,'loadAssocList');

		return $results;
	}

	function getUsedCategories()
	{
		$query = "
            SELECT
                Component.{$this->categoryPrimaryKey} AS `Component.cat_id`,Component.{$this->profiles_title_col} as `Component.cat_title`, Criteria.title AS `Component.criteria_title`
		    FROM
                #__social_clusters_categories AS Component
		    INNER JOIN
                #__jreviews_categories AS JreviewCategory ON Component.{$this->categoryPrimaryKey} = JreviewCategory.id AND JreviewCategory.`option` = '{$this->extension_alias}'
		    LEFT
                JOIN #__jreviews_criteria AS Criteria ON JreviewCategory.criteriaid = Criteria.id
		    LIMIT
                {$this->offset},{$this->limit}
        ";

		$results = $this->query($query,'loadObjectList');

		$results = $this->__reformatArray($results);

		$results = $this->changeKeys($results,'Component','cat_id');

		$query = "
			SELECT
				count(JreviewCategory.id)
			FROM
				#__jreviews_categories AS JreviewCategory
			WHERE
				JreviewCategory.`option` = '{$this->extension_alias}'
		";

		$count = $this->query($query,'loadResult');

		return array('rows'=>$results,'count'=>$count);
	}
}