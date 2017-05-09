<?php
/**
 * JReviews - Reviews Extension
 * Copyright (C) 2010-2015 ClickFWD LLC
 * This is not free software, do not distribute it.
 * For licencing information visit https://www.jreviews.com
 * or contact sales@jreviews.com
**/

defined( 'MVC_FRAMEWORK') or die( 'Direct Access to this location is not allowed.' );

class EverywhereComCommunityAccessModel extends MyModel  {

    const _LISTING_ID                   = 'id';

	var $UI_name = 'JomSocial - Access Groups';

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
	var $extension = 'com_community';

	/**
	 * This is the value stored in the reviews table to differentiate the source of the reviews
	 *
	 * @var string
	 */
	var $extension_alias = 'com_community_access';

	var $listingUrl = 'index.php?option=com_community&view=profile&userid=%s';

	var $categoryPrimaryKey = 'id';

	var $fields = array(
		'Listing.id AS `Listing.listing_id`',
		'Listing.title'=>'Listing.username AS `Listing.title`',
		'Listing.id AS `Listing.user_id`',
		'"0000-00-00" AS `Listing.publish_up`',
		'"0000-00-00" AS `Listing.publish_up`',
		'IF(Listing.block = 0, 1, 0) AS `Listing.state`',
		'Community.thumb AS `Listing.images`',
		"'com_community_access' AS `Listing.extension`",
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

    var $groups_table = array();

    var $groups_title_col;

	// Module controller includes the basic joins for review and rating information and others can be added here
	// depending on the fields used in the query
	var $joinsListingsModule = array();

	var $avatar_storage;

	var $s3_bucket;

    var $default_thumb  = 'components/com_community/assets/user_thumb.png';

    public static $joinListingState = array(
    	'INNER JOIN #__users AS Listing ON Listing.id = %s AND Listing.block = 0'
    	);

	function __construct() {

		parent::__construct();

		if(!self::exists()) return;

        $this->groups_table = '#__usergroups';

        $this->groups_title_col = 'title';

        $this->fields['cat_name'] = "Category.{$this->groups_title_col} AS `Category.title`";

        $this->joins = array(
            'Totals'=>"LEFT JOIN #__jreviews_listing_totals AS Totals ON Totals.listing_id = Listing.id AND Totals.extension = '{$this->extension_alias}'",
            "INNER JOIN #__user_usergroup_map AS UserGroupMap ON UserGroupMap.user_id = Listing.id",
            "LEFT JOIN {$this->groups_table} AS Category ON UserGroupMap.group_id = Category.{$this->categoryPrimaryKey}",
            "INNER JOIN #__jreviews_categories AS JreviewsCategory ON Category.{$this->categoryPrimaryKey} = JreviewsCategory.id AND JreviewsCategory.`option` = '{$this->extension_alias}'",
            'LEFT JOIN #__community_users AS Community ON Listing.id = Community.userid',
            "LEFT JOIN #__users AS User ON User.id = Listing.id"
        );

        $this->joinsReviews = array(
            "LEFT JOIN #__users AS Listing ON Review.pid = Listing.id",
            "INNER JOIN #__user_usergroup_map AS UserGroupMap ON UserGroupMap.user_id = Listing.id",
            "LEFT JOIN {$this->groups_table} AS Category ON UserGroupMap.group_id = Category.{$this->categoryPrimaryKey}",
            "INNER JOIN #__jreviews_categories AS JreviewsCategory ON Category.{$this->categoryPrimaryKey} = JreviewsCategory.id AND JreviewsCategory.`option` = '{$this->extension_alias}'"
        );

        $this->joinsMedia = array(
            // "LEFT JOIN #__users AS Listing ON Media.listing_id = Listing.id",
            "INNER JOIN #__user_usergroup_map AS UserGroupMap ON UserGroupMap.user_id = Listing.id",
            "LEFT JOIN {$this->groups_table} AS Category ON UserGroupMap.group_id = Category.{$this->categoryPrimaryKey}",
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

        // Correct storate handling for avatar
		$cache_key = s2CacheKey('jomsocial_config');

		$JSConfig = S2Cache::read($cache_key, '_s2framework_core_');

        // For JomSocial <= 2.1
        if(!file_exists(PATH_ROOT . 'components/com_community/assets/user_thumb.png')) {

            $this->default_thumb = 'components/com_community/assets/default_thumb.jpg';
        }

		if(false == $JSConfig) {

			// Read the JomSocial configuration to determine the storage location for avatars
			$JSConfig = json_decode($this->query("SELECT params FROM #__community_config WHERE name = 'config'",'loadResult'),true);

			$JSConfigForJReviews = array(
				'user_avatar_storage'=>$JSConfig['user_avatar_storage'],
				'storages3bucket'=>$JSConfig['storages3bucket']

			);

			S2Cache::write($cache_key,$JSConfigForJReviews, '_s2framework_core_');
		}

		$this->avatar_storage = $JSConfig['user_avatar_storage'];

		$this->s3_bucket = $JSConfig['storages3bucket'];
	}

	static public function exists() {

        if (file_exists(PATH_ROOT . 'components' . DS . 'com_community' . DS . 'community.php')) {

            require_once( PATH_ROOT . 'components' . DS . 'com_community' . DS . 'libraries' . DS . 'core.php');

            return true;
		}

		return false;
	}

	function listingUrl($listing) {

		$url = sprintf($this->listingUrl,$listing['Listing']['listing_id']);

		return $url;
	}

    // Used to check whether reviews can be posted by listing owners, owner replies
    function getListingOwner($result_id) {
        $query = "SELECT User.id user_id, User.name, User.email
            FROM #__users AS User ".
            "WHERE User.id = " . (int) $result_id;

        return $this->query($query,'loadAssoc');
    }

	function afterFind($results) {

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

				$imagePath = WWW_ROOT. $images;
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
                Component.{$this->categoryPrimaryKey} AS value, Component.{$this->groups_title_col} as text
		    FROM
                {$this->groups_table} AS Component
		    LEFT JOIN
                #__jreviews_categories AS JreviewCategory ON Component.{$this->categoryPrimaryKey} = JreviewCategory.id AND JreviewCategory.`option` = '{$this->extension_alias}'
		" . ($exclude != '' ? "
                WHERE
                    Component.{$this->categoryPrimaryKey} NOT IN ($exclude)" : ''
        ) . "
		    ORDER BY
                Component.{$this->groups_title_col} ASC
        ";

        $results = $this->query($query,'loadAssocList');

        if(!empty($results))
        {
            foreach($results AS $key=>$value){
                if(in_array($value['text'],array('Public Backend','Public Frontend','ROOT','USERS'))){
                    unset($results[$key]);
                }
            }
        }

		return $results;
	}

	function getUsedCategories()
	{
		$query = "
            SELECT
                Component.{$this->categoryPrimaryKey} AS `Component.cat_id`,Component.{$this->groups_title_col} as `Component.cat_title`, Criteria.title AS `Component.criteria_title`
		    FROM
                {$this->groups_table} AS Component
		    INNER JOIN
                #__jreviews_categories AS JreviewCategory ON Component.{$this->categoryPrimaryKey} = JreviewCategory.id AND JreviewCategory.`option` = '{$this->extension_alias}'
		    LEFT
                JOIN #__jreviews_criteria AS Criteria ON JreviewCategory.criteriaid = Criteria.id
		    LIMIT
                {$this->offset},{$this->limit}
        ";

		appLogMessage("getUsedCategories\n".$this->getQuery(),'everywhere');

		$results = $this->query($query,'loadObjectList');

		appLogMessage($this->getErrorMsg(),'everywhere');

		$results = $this->__reformatArray($results);
		$results = $this->changeKeys($results,'Component','cat_id');

		$query = "SELECT count(JreviewCategory.id)"
		. "\n FROM #__jreviews_categories AS JreviewCategory"
		. "\n WHERE JreviewCategory.`option` = '{$this->extension_alias}'"
		;

		$count = $this->query($query,'loadResult');

		return array('rows'=>$results,'count'=>$count);
	}
}