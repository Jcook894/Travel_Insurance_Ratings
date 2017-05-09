<?php
/**
 * JReviews - Reviews Extension
 * Copyright (C) 2010-2015 ClickFWD LLC
 * This is not free software, do not distribute it.
 * For licencing information visit https://www.jreviews.com
 * or contact sales@jreviews.com
**/

defined( 'MVC_FRAMEWORK') or die( 'Direct Access to this location is not allowed.' );

class EverywhereComEasysocialModel extends MyModel  {

    const _LISTING_ID                   = 'id';

	var $UI_name = 'EasySocial - Profiles';

	var $name = 'Listing';

	var $useTable = '#__users AS Listing';

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
	var $extension = 'com_easysocial';

	/**
	 * This is the value stored in the reviews table to differentiate the source of the reviews
	 *
	 * @var string
	 */
	var $extension_alias = 'com_easysocial';

	var $categoryPrimaryKey = 'id';

	var $fields = array(
		'Listing.id AS `Listing.listing_id`',
		'Listing.title'=>'Listing.username AS `Listing.title`',
		'Listing.id AS `Listing.user_id`',
		'"0000-00-00" AS `Listing.publish_up`',
		'"0000-00-00" AS `Listing.publish_up`',
		'IF(Listing.block = 0, 1, 0) AS `Listing.state`',
		'Avatar.large AS `Listing.images`',
		"'com_easysocial' AS `Listing.extension`",
		'JreviewsCategory.id AS `Listing.cat_id`',
		'cat_name'=>'Category.name AS `Category.title`',
		'JreviewsCategory.id AS `Category.cat_id`',
        'JreviewsCategory.criteriaid AS `Listing.listing_type_id`',
        'User.id AS `User.user_id`',
        'User.name AS `User.name`',
        'User.username AS `User.username`',
        'User.email AS `User.email`',
        // User reviews
        'user_rating'=>'Totals.user_rating AS `Review.user_rating`',
        'Totals.user_rating_count AS `Review.user_rating_count`',
        'Totals.user_criteria_rating AS `Review.user_criteria_rating`',
        'Totals.user_criteria_rating_count AS `Review.user_criteria_rating_count`',
        'Totals.user_comment_count AS `Review.review_count`'
    );

	// Done in the __construct function below because it's CMS dependent for categoryPrimaryKey
	var $joins = array();

	var $joinsReviews = array();

    var $profiles_table = array();

    var $profiles_title_col;

	// Module controller includes the basic joins for review and rating information and others can be added here
	// depending on the fields used in the query
	var $joinsListingsModule = array();

	var $avatar_storage;

	var $s3_bucket;

    var $default_thumb  = 'media/com_easysocial/defaults/avatars/users/large.png';

    var $avatar_path = 'media/com_easysocial/avatars/users/';

    public static $joinListingState = array(
    	'INNER JOIN #__users AS Listing ON Listing.id = %s AND Listing.block = 0'
    	);

	function __construct()
	{
		parent::__construct();

		if(!self::exists()) return;

        $this->profiles_table = '#__social_profiles';

        $this->profiles_title_col = 'title';

        $this->fields['cat_name'] = "Category.{$this->profiles_title_col} AS `Category.title`";

        $this->joins = array(
            'Totals'=>"LEFT JOIN #__jreviews_listing_totals AS Totals ON Totals.listing_id = Listing.id AND Totals.extension = '{$this->extension_alias}'",
            "INNER JOIN #__social_profiles_maps AS UserProfileMap ON UserProfileMap.user_id = Listing.id",
            "LEFT JOIN {$this->profiles_table} AS Category ON UserProfileMap.profile_id = Category.{$this->categoryPrimaryKey}",
            "INNER JOIN #__jreviews_categories AS JreviewsCategory ON Category.{$this->categoryPrimaryKey} = JreviewsCategory.id AND JreviewsCategory.`option` = '{$this->extension_alias}'",
            'LEFT JOIN #__social_users AS Community ON Listing.id = Community.user_id',
            "LEFT JOIN #__users AS User ON User.id = Listing.id",
            'LEFT JOIN #__social_avatars AS Avatar ON Community.user_id = Avatar.uid AND Avatar.type = "user"'
        );

        $this->joinsReviews = array(
            "LEFT JOIN #__users AS Listing ON Review.pid = Listing.id",
            "INNER JOIN #__social_profiles_maps AS UserProfileMap ON UserProfileMap.user_id = Listing.id",
            "LEFT JOIN {$this->profiles_table} AS Category ON UserProfileMap.profile_id = Category.{$this->categoryPrimaryKey}",
            "INNER JOIN #__jreviews_categories AS JreviewsCategory ON Category.{$this->categoryPrimaryKey} = JreviewsCategory.id AND JreviewsCategory.`option` = '{$this->extension_alias}'"
        );

        $this->joinsMedia = array(
            // "LEFT JOIN #__users AS Listing ON Media.listing_id = Listing.id",
            "INNER JOIN #__social_profiles_maps AS UserProfileMap ON UserProfileMap.user_id = Listing.id",
            "LEFT JOIN {$this->profiles_table} AS Category ON UserProfileMap.profile_id = Category.{$this->categoryPrimaryKey}",
            "INNER JOIN #__jreviews_categories AS JreviewsCategory ON Category.{$this->categoryPrimaryKey} = JreviewsCategory.id AND JreviewsCategory.`option` = '{$this->extension_alias}'"        );

		$this->group[] = "Listing.id";

        $this->tag = __t("COMMUNITY_TAG",true);  // Used in MyReviews page to differentiate from other component reviews

		$this->fields[] = "'{$this->tag }' AS `Listing.tag`";

        # Use name or username based on JReviews config

        $Config = Configure::read('JreviewsSystem.Config');

        if($Config->name_choice == 'realname') {

            $this->fields['Listing.title'] = 'Listing.name AS `Listing.title`';
        }

        unset($Config);

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
	}

	static public function exists()
	{
        if (file_exists(PATH_ROOT . 'components' . DS . 'com_easysocial' . DS . 'easysocial.php')) {

            require_once(JPATH_ROOT . '/administrator/components/com_easysocial/includes/foundry.php');

            return true;
        }

        return false;
	}

	function listingUrl($listing)
	{
        $url = ESR::profile(array(
            'id' => self::getAlias($listing['Listing']['listing_id'], $listing['User']['username']),
            'sef' => false
        ));

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

			// Add slug

			$results[$key]['Listing']['slug'] = str_replace('.', '-', $result['User']['username']);

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
                {$this->profiles_table} AS Component
		    LEFT JOIN
                #__jreviews_categories AS JreviewCategory ON Component.{$this->categoryPrimaryKey} = JreviewCategory.id AND JreviewCategory.`option` = '{$this->extension_alias}'
		" . ($exclude != '' ? "
                WHERE
                    Component.{$this->categoryPrimaryKey} NOT IN ($exclude)" : ''
        ) . "
		    ORDER BY
                Component.{$this->profiles_title_col} ASC
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
                {$this->profiles_table} AS Component
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

    /**
     * Adapted from /administrator/components/com_easysocial/includes/user/user.php
     */
    public function getAlias($user_id, $screen_name)
    {
        // If sef is not enabled or running SH404, just return the ID-USERNAME prefix.

        $jConfig = ES::jconfig();

        $sh404 = class_exists('shRouter');

        if(!$jConfig->getValue( 'sef' ) || $sh404 )
        {
            return $user_id . ':' . JFilterOutput::stringURLSafe( $screen_name );
        }

        $name = $user_id . ':' . $screen_name;

        // If the name is in the form of an e-mail address, fix it here by using the ID:permalink syntax
        if( JMailHelper::isEmailAddress( $screen_name ) )
        {
            return $user_id . ':' . JFilterOutput::stringURLSafe( $screen_name );
        }

        // Ensure that the name is a safe url.

        $name = JFilterOutput::stringURLSafe( $name );

        return $name;
    }

    function findIdByTitle($title)
    {
        $query = '
            SELECT
                id
            FROM
                #__users
            WHERE
                name LIKE ' . $this->QuoteLike(trim($title))
            .' OR username LIKE ' . $this->QuoteLike(trim($title))
            ;

        $id = $this->query($query,'loadColumn');

        return $id;
    }
}