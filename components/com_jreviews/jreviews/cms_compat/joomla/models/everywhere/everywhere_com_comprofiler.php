<?php
/**
 * JReviews - Reviews Extension
 * Copyright (C) 2010-2015 ClickFWD LLC
 * This is not free software, do not distribute it.
 * For licencing information visit https://www.jreviews.com
 * or contact sales@jreviews.com
**/

defined( 'MVC_FRAMEWORK') or die( 'Direct Access to this location is not allowed.' );

class EverywhereComComprofilerModel extends MyModel  {

    const _LISTING_ID                   = 'id';

	var $UI_name = 'Community Builder';

	/**
	 * groups = Core Access Groups. The categories are the Access Groups (i.e. guest, registered, manager, etc.) and you can assign different criterias to each category
	 * fields = CB Custom Field. The categories are the value options of a CB custom fields and you can assign different criterias to each category
	 */
	var $integrationMode = 'groups'; // groups | fields

	/**
	 * Create a field with the name shown below, or change it's value to an existing field name.
	 * The options of this field will be used as categories
	 * Make sure this is a required field and shown at registration time
	 */
	var $cbCustomField = 'cb_membertype';

	var $name = 'Listing';

	var $useTable = '#__users AS Listing';

	var $primaryKey = 'Listing.listing_id';

	var $realKey = 'id';

	/**
	 * Used for listing module - latest listings ordering
	 */
	var $dateKey = 'registerDate';

	var $extension = 'com_comprofiler';

	var $listingUrl = 'index.php?option=com_comprofiler&amp;task=userProfile&amp;user=%s&amp;Itemid=%s';

	var $categoryPrimaryKey = 'id';

	var $fields = array(
		'Listing.id AS `Listing.listing_id`',
		'Listing.title'=>'Listing.username AS `Listing.title`',
		'Listing.id AS `Listing.user_id`',
		'"0000-00-00" AS `Listing.publish_up`',
		'"0000-00-00" AS `Listing.publish_up`',
		'CommunityBuilder.avatar AS `Listing.images`',
		'CommunityBuilder.avatarapproved AS `Listing.images_approved`',
		"'com_comprofiler' AS `Listing.extension`",
		'JreviewsCategory.id AS `Listing.cat_id`',
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

	// Module controller includes the basic joins for review and rating information and others can be added here
	// depending on the fields used in the query
	var $joinsListingsModule = array();

    public static $joinListingState = array(
    	'INNER JOIN #__users AS Listing ON Listing.id = %s AND Listing.block = 0',
    	'INNER JOIN #__comprofiler AS CommunityBuilder ON CommunityBuilder.user_id = %s AND CommunityBuilder.approved = 1 AND CommunityBuilder.confirmed = 1'
    	);

	function __construct() {

		parent::__construct();

        switch($this->integrationMode)
        {
			case 'groups':

                $this->fields['Category.title'] = 'Category.title AS `Category.title`';

                $this->joins = array(
                    'Totals'=>"LEFT JOIN #__jreviews_listing_totals AS Totals ON Totals.listing_id = Listing.id AND Totals.extension = 'com_comprofiler'",
                    'CategoryMap' => "LEFT JOIN #__user_usergroup_map AS CategoryMap ON Listing.id = CategoryMap.user_id",
                    'JreviewsCategory'=> "INNER JOIN #__jreviews_categories AS JreviewsCategory ON CategoryMap.group_id = JreviewsCategory.id AND JreviewsCategory.`option` = 'com_comprofiler'",
                    'Category'=>"LEFT JOIN #__usergroups AS Category ON CategoryMap.group_id = Category.{$this->categoryPrimaryKey}",
                                'LEFT JOIN #__comprofiler AS CommunityBuilder ON Listing.id = CommunityBuilder.id',
                                "LEFT JOIN #__users AS User ON User.id = Listing.id"
                );

                $this->group[] = 'Listing.id';

                $this->joinsReviews = array(
                    "LEFT JOIN #__users AS Listing ON Review.pid = Listing.id",
                    'CategoryMap' => "LEFT JOIN #__user_usergroup_map AS CategoryMap ON Listing.id = CategoryMap.user_id",
                    'JreviewsCategory'=> "INNER JOIN #__jreviews_categories AS JreviewsCategory ON CategoryMap.group_id = JreviewsCategory.id AND JreviewsCategory.`option` = 'com_comprofiler'",
                    'Category'=>"LEFT JOIN #__usergroups AS Category ON CategoryMap.group_id = Category.{$this->categoryPrimaryKey}"
                );

				break;

			case 'fields':

				$this->fields = array(
					'Listing.id AS `Listing.listing_id`',
                    'Listing.title'=>'Listing.username AS `Listing.title`',
					'CommunityBuilder.avatar AS `Listing.images`',
					'CommunityBuilder.avatarapproved AS `Listing.images_approved`',
					"'com_comprofiler' AS `Listing.extension`",
					'JreviewsCategory.id AS `Listing.cat_id`',
					'Category.fieldtitle AS `Category.title`',
					'JreviewsCategory.id AS `Category.cat_id`',
        			'JreviewsCategory.criteriaid AS `Listing.listing_type_id`',
			        'user_rating'=>'Totals.user_rating AS `Review.user_rating`',
			        'Totals.user_rating_count AS `Review.user_rating_count`',
			        'Totals.user_criteria_rating AS `Review.user_criteria_rating`',
			        'Totals.user_criteria_rating_count AS `Review.user_criteria_rating_count`',
			        'Totals.user_comment_count AS `Review.review_count`'
				);

                $this->joins = array(
                    'Totals'=>"LEFT JOIN #__jreviews_listing_totals AS Totals ON Totals.listing_id = Listing.id AND Totals.extension = 'com_comprofiler'",
                    'Category'=>"LEFT JOIN #__core_acl_aro_groups AS Category ON Listing.gid = Category.{$this->categoryPrimaryKey}",
                    'JreviewsCategory'=>"INNER JOIN #__jreviews_categories AS JreviewsCategory ON Listing.gid = JreviewsCategory.id AND JreviewsCategory.`option` = 'com_comprofiler'",
			                            'LEFT JOIN #__comprofiler AS CommunityBuilder ON Listing.id = CommunityBuilder.id',
			                            "LEFT JOIN #__users AS User ON User.id = Listing.id"
                );

				$this->joins = array(
                    'Totals'=>"LEFT JOIN #__jreviews_listing_totals AS Totals ON Totals.listing_id = Listing.id AND Totals.extension = 'com_comprofiler'",
							'INNER JOIN #__comprofiler AS CommunityBuilder ON Listing.id = CommunityBuilder.id',
							"LEFT JOIN #__users AS User ON User.id = Listing.id",
							"LEFT JOIN #__comprofiler_field_values AS Category ON CommunityBuilder.{$this->cbCustomField} = Category.fieldtitle",
							"INNER JOIN #__jreviews_categories AS JreviewsCategory ON Category.fieldvalueid = JreviewsCategory.id AND JreviewsCategory.`option` = 'com_comprofiler'"
				);

				$this->joinsReviews = array(
//					"LEFT JOIN #__users AS Listing ON Review.pid = Listing.id",
					'INNER JOIN #__comprofiler AS CommunityBuilder ON Review.pid = CommunityBuilder.id',
					"LEFT JOIN #__comprofiler_field_values AS Category ON CommunityBuilder.{$this->cbCustomField} = Category.fieldtitle",
					"INNER JOIN #__jreviews_categories AS JreviewsCategory ON Category.fieldvalueid = JreviewsCategory.id AND JreviewsCategory.`option` = 'com_comprofiler'"				);


				$this->joinsMedia = array(
//					"LEFT JOIN #__users AS Listing ON Review.pid = Listing.id",
					'INNER JOIN #__comprofiler AS CommunityBuilder ON Media.listing_id = CommunityBuilder.id',
					"LEFT JOIN #__comprofiler_field_values AS Category ON CommunityBuilder.{$this->cbCustomField} = Category.fieldtitle",
					"INNER JOIN #__jreviews_categories AS JreviewsCategory ON Category.fieldvalueid = JreviewsCategory.id AND JreviewsCategory.`option` = 'com_comprofiler'"				);

				break;
		}

		$this->tag = __t("COMMUNITY_BUILDER_TAG",true);  // Used in MyReviews page to differentiate from other component reviews

		$this->fields[] = "'{$this->tag }' AS `Listing.tag`";

        # Use name or username based on JReviews config
        $Config = Configure::read('JreviewsSystem.Config');
        if($Config->name_choice == 'realname') {
            $this->fields['Listing.title'] = 'Listing.name AS `Listing.title`';
        }
        unset($Config);
	}

	static public function exists() {

		return (bool) @ file_exists(PATH_ROOT . 'components' . _DS . 'com_comprofiler' . _DS . 'comprofiler.php');
	}

	function listingUrl($listing) {

		return sprintf($this->listingUrl,$listing['Listing']['listing_id'],$listing['Listing']['menu_id']);

	}

    // Used to check whether reviews can be posted by listing owners, owner replies
    // Used to check whether reviews can be posted by listing owners, owner replies
    function getListingOwner($result_id)
    {
        $query = "SELECT User.id user_id, User.name, User.email
            FROM #__users AS User ".
            "WHERE User.id = " . (int) $result_id;

        return $this->query($query,'loadAssoc');
    }

	function afterFind($results)
    {
        if (empty($results))
        {
            return $results;
        }

        # Add listing type and criteria rating info to results array

        S2App::import('Model',array('criteria','menu'),'jreviews');

        $ListingTypes = ClassRegistry::getClass('CriteriaModel');

        $results = $ListingTypes->addListingTypes($results,'Listing');

		# Find Itemid for component
		$Menu = ClassRegistry::getClass('MenuModel');

		$menu_id = $Menu->getComponentMenuId($this->extension, true);

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

			// Process component menu id
			$results[$key][$this->name]['menu_id'] = $menu_id;

			// Process listing url
			$results[$key][$this->name]['url'] = $this->listingUrl($results[$key]);

            # Config overrides

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

			if($images != '')
			{
				if ( @file_exists("images/comprofiler" . $images) )
				{
				    $imagePath = "images/comprofiler" . $images;
				}
				else {

				    $imagePath = "images/comprofiler/" . $images;
				}

			}
			else {
				// Put a noimage path here?
				$imagePath = ''; //"images/comprofiler/" . $images;
			}

			$results[$key]['Listing']['images'][] = array(
				'path'=>$imagePath,
				'caption'=>$results[$key]['Listing']['title'],
				'basepath'=>true
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
	 * ADMIN FUNCTIONS BELOW
	 */
	function getNewCategories()
	{
		$call = 'getNewCategories'.$this->integrationMode;
		return $this->$call();
	}

	function getUsedCategories()
	{
		$call = 'getUsedCategories'.$this->integrationMode;
		return $this->$call();
	}

	/**
	 * GROUPS MODE STARTS HERE
	 */
	function getNewCategoriesgroups()
	{
		$query = "SELECT id FROM #__jreviews_categories WHERE `option` = '{$this->extension}'";

        $exclude = $this->query($query,'loadColumn');

        $exclude = $exclude ? implode(',',$exclude) : '';

        $table = '#__usergroups';

        $key = 'title';

        $query = "
            SELECT
                Component.{$this->categoryPrimaryKey} AS value,Component.{$key} as text
            FROM
                {$table} AS Component
            LEFT JOIN
                #__jreviews_categories AS JreviewCategory ON Component.{$this->categoryPrimaryKey} = JreviewCategory.id AND JreviewCategory.`option` = '{$this->extension}'
            ". ($exclude != '' ? "\n WHERE Component.{$this->categoryPrimaryKey} NOT IN ($exclude)" : '') ."
            ORDER BY
                Component.{$key} ASC
        ";

        $results = $this->query($query,'loadAssocList');

        return $results;
	}

	function getUsedCategoriesgroups()
	{
        $table = '#__usergroups';

        $key = 'title';

        $query = "
            SELECT
                Component.{$this->categoryPrimaryKey} AS `Component.cat_id`,Component.{$key} as `Component.cat_title`, Criteria.title AS `Component.criteria_title`
            FROM
                {$table} AS Component
            INNER JOIN
                #__jreviews_categories AS JreviewCategory ON Component.{$this->categoryPrimaryKey} = JreviewCategory.id AND JreviewCategory.`option` = '{$this->extension}'
            LEFT JOIN
                #__jreviews_criteria AS Criteria ON JreviewCategory.criteriaid = Criteria.id
            LIMIT
                $this->offset,$this->limit
        ";

		appLogMessage("getUsedCategories\n".$this->getQuery(),'everywhere');

		$results = $this->query($query,'loadObjectList');

		appLogMessage($this->getErrorMsg(),'everywhere');

		$results = $this->__reformatArray($results);
		$results = $this->changeKeys($results,'Component','cat_id');

		$query = "SELECT count(JreviewCategory.id)"
		. "\n FROM #__jreviews_categories AS JreviewCategory"
		. "\n WHERE JreviewCategory.`option` = '{$this->extension}'"
		;

		$count = $this->query($query,'loadResult');

		return array('rows'=>$results,'count'=>$count);
	}

	/**
	 * FIELDS MODE STARTS HERE
	 */
	function getNewCategoriesfields()
	{
		$query = "SELECT id FROM #__jreviews_categories WHERE `option` = '{$this->extension}'";

        $exclude = $this->query($query,'loadColumn');

        $exclude = $exclude ? implode(',',$exclude) : '';

		$query = "
			SELECT Component.fieldvalueid AS value, Component.fieldtitle as text
			FROM #__comprofiler_field_values AS Component
			INNER JOIN #__comprofiler_fields AS Field ON Component.fieldid = Field.fieldid AND Field.name = '" . $this->cbCustomField . "'"
		. ($exclude != '' ? "\n WHERE Component.fieldvalueid NOT IN ($exclude)" : '')
		. "\n ORDER BY Component.fieldtitle ASC"
		;

        return $this->query($query,'loadAssocList');
	}

	function getUsedCategoriesfields()
	{
		$query = "SELECT Component.fieldvalueid AS `Component.cat_id`, Component.fieldtitle as `Component.cat_title`, Criteria.title AS `Component.criteria_title`"
		. "\n FROM #__comprofiler_field_values AS Component"
		. "\n INNER JOIN #__jreviews_categories AS JreviewCategory ON Component.fieldvalueid = JreviewCategory.id AND JreviewCategory.`option` = '{$this->extension}'"
		. "\n LEFT JOIN #__jreviews_criteria AS Criteria ON JreviewCategory.criteriaid = Criteria.id"
		. "\n LIMIT $this->offset,$this->limit"
		;

		appLogMessage("getUsedCategories\n".$this->getQuery(),'everywhere');

		$results = $this->query($query,'loadObjectList');

		appLogMessage($this->getErrorMsg(),'everywhere');

		$results = $this->__reformatArray($results);
		$results = $this->changeKeys($results,'Component','cat_id');

		$query = "SELECT count(JreviewCategory.id)"
		. "\n FROM #__jreviews_categories AS JreviewCategory"
		. "\n WHERE JreviewCategory.`option` = '{$this->extension}'"
		;

		$count = $this->query($query,'loadResult');

		return array('rows'=>$results,'count'=>$count);
	}
}