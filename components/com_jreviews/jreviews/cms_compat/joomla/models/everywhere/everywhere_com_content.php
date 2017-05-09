<?php
/**
 * JReviews - Reviews Extension
 * Copyright (C) 2010-2015 ClickFWD LLC
 * This is not free software, do not distribute it.
 * For licencing information visit https://www.jreviews.com
 * or contact sales@jreviews.com
**/

defined( 'MVC_FRAMEWORK') or die( 'Direct Access to this location is not allowed.' );

S2App::import('Helper',array('routes','time'),'jreviews');

class EverywhereComContentModel extends MyModel  {

    const _LISTING_TABLE                = '#__content';

    const _LISTING_ID                   = 'id';

    const _LISTING_TITLE                = 'title';

    const _LISTING_SLUG             	= 'alias';

    const _LISTING_SUMMARY          	= 'introtext';

    const _LISTING_DESCRIPTION      	= 'fulltext';

    const _LISTING_USER_ID              = 'created_by';

    const _LISTING_CREATE_DATE 		    = 'created';

    const _LISTING_MODIFIED             = 'modified';

    const _LISTING_AUTHOR_ALIAS         = 'created_by_alias';

    const _LISTING_STATE                = 'state';

    const _LISTING_METAKEY              = 'Listing.metakey';

    var $_SIMPLE_SEARCH_FIELDS = array();

	var $UI_name = 'Content';

	var $name = 'Listing';

	var $useTable = '#__content AS Listing';

	var $primaryKey = 'Listing.listing_id';

	var $realKey = 'id';

	/**
	 * Used for listing module - latest listings ordering
	 */
	var $dateKey = 'created';

	var $extension = 'com_content';

	var $fields = array(
		'Listing.id'                          =>  'Listing.id AS `Listing.listing_id`',
        'Listing.slug'                        =>  'Listing.alias AS `Listing.slug`',
		'Listing.title'                       =>  'Listing.title AS `Listing.title`',
		'Listing.summary'                     =>  'Listing.introtext AS `Listing.summary`',
		'Listing.description'                 =>  'Listing.fulltext AS `Listing.description`',
		'Listing.images AS `Listing.images`',
		'Listing.hits'                        =>  'Listing.hits AS `Listing.hits`',
		'Listing.cat_id'                      =>  'Listing.catid AS `Listing.cat_id`',
		'Listing.user_id'                     =>  'Listing.created_by AS `Listing.user_id`',
		'Listing.author_alias'                =>  'Listing.created_by_alias AS `Listing.author_alias`',
		'Listing.created'                     =>  'Listing.created AS `Listing.created`',
        'Listing.modified'                    =>  'Listing.modified AS `Listing.modified`',
		'Listing.access'                      =>  'Listing.access AS `Listing.access`',
		'Listing.state'                       =>  'Listing.state AS `Listing.state`',
		'Listing.publish_up'                  =>  'Listing.publish_up AS `Listing.publish_up`',
		'Listing.publish_down'                =>  'Listing.publish_down AS `Listing.publish_down`',
		'Listing.metakey'                     =>  'Listing.metakey AS `Listing.metakey`',
		'Listing.metadesc'                    =>  'Listing.metadesc AS `Listing.metadesc`',
		'Listing.extension'                   =>  '\'com_content\' AS `Listing.extension`',
        'Listing.featured'                    =>  'Field.featured AS `Listing.featured`',
        'Listing.listing_type_id'             =>  'JreviewsCategory.criteriaid AS `Listing.listing_type_id`',
		'Category.id AS `Category.cat_id`',
		'Category.title AS `Category.title`',
        'Category.alias AS `Category.slug`',
        'cat_params'=>'Category.params AS `Category.params`', /* J16 */
		'Directory.dir_id'                    =>  'Directory.id AS `Directory.dir_id`',
		'Directory.title'                     =>  'Directory.desc AS `Directory.title`',
		'Directory.slug'                      =>  'Directory.title AS `Directory.slug`',
		'User.id'                             =>  'User.id AS `User.user_id`',
		'User.realname'                       =>  'User.name AS `User.name`',
		'User.alias'                          =>  'User.username AS `User.username`',
		'User.email'                          =>  'User.email AS `User.email`',
        'Claim.approved'                      =>  'Claim.approved AS `Claim.approved`',
        // User reviews
        'Totals.user_rating AS `Review.user_rating`',
        'Totals.user_rating_count AS `Review.user_rating_count`',
        'Totals.user_criteria_rating AS `Review.user_criteria_rating`',
        'Totals.user_criteria_rating_count AS `Review.user_criteria_rating_count`',
        'Totals.user_comment_count AS `Review.review_count`',
		// Editor reviews
        'Totals.editor_rating AS `Review.editor_rating`',
        'Totals.editor_rating_count AS `Review.editor_rating_count`',
        'Totals.editor_criteria_rating AS `Review.editor_criteria_rating`',
        'Totals.editor_criteria_rating_count AS `Review.editor_criteria_rating_count`',
        'Totals.editor_comment_count AS `Review.editor_review_count`',
        // Media
		'Totals.media_count AS `Listing.media_count`',
		'Totals.video_count AS `Listing.video_count`',
		'Totals.photo_count AS `Listing.photo_count`',
		'Totals.audio_count AS `Listing.audio_count`',
        'Totals.attachment_count AS `Listing.attachment_count`',
        'Listing.media_count_owner'            =>  '(Totals.media_count - Totals.media_count_user) AS `Listing.media_count_owner`',
        'Listing.video_count_owner'            =>  '(Totals.video_count - Totals.video_count_user) AS `Listing.video_count_owner`',
        'Listing.photo_count_owner'            =>  '(Totals.photo_count - Totals.photo_count_user) AS `Listing.photo_count_owner`',
        'Listing.audio_count_owner'            =>  '(Totals.audio_count - Totals.audio_count_user) AS `Listing.audio_count_owner`',
        'Listing.attachment_count_owner'       =>  '(Totals.attachment_count - Totals.attachment_count_user) AS `Listing.attachment_count_owner`',
        'Listing.media_count_user'             =>  'Totals.media_count_user AS `Listing.media_count_user`',
        'Listing.video_count_user'            =>  'Totals.video_count_user AS `Listing.video_count_user`',
        'Listing.photo_count_user'            =>  'Totals.photo_count_user AS `Listing.photo_count_user`',
        'Listing.audio_count_user'            =>  'Totals.audio_count_user AS `Listing.audio_count_user`',
        'Listing.attachment_count_user'       =>  'Totals.attachment_count_user AS `Listing.attachment_count_user`'
	);

	var $joins = array(
        'Totals'=>          "LEFT JOIN #__jreviews_listing_totals AS Totals ON Totals.listing_id = Listing.id AND Totals.extension = 'com_content'",
        'Field'=>           "LEFT JOIN #__jreviews_content AS Field ON Field.contentid = Listing.id",
        'JreviewsCategory'=>"LEFT JOIN #__jreviews_categories AS JreviewsCategory ON JreviewsCategory.id = Listing.catid AND JreviewsCategory.`option` = 'com_content'",
        'Category'=>        "LEFT JOIN #__categories AS Category ON Category.id = Listing.catid AND Category.extension = 'com_content'",
        'Directory'=>       "LEFT JOIN #__jreviews_directories AS Directory ON Directory.id = JreviewsCategory.dirid",
		'User'=>            "LEFT JOIN #__users AS User ON User.id = Listing.created_by",
        'Claim'=>           "LEFT JOIN #__jreviews_claims AS Claim ON Claim.listing_id = Listing.id AND Claim.user_id = Listing.created_by AND Claim.approved = 1"
	);

	/**
	 * Used to complete the listing information for reviews based on the Review.pid. The list of fields for the listing is not as
	 * extensive as the one above used for the full listing view
	 */
	var $joinsReviews = array(
		'Listing' => 'LEFT JOIN #__content AS Listing ON Listing.id = Review.pid',
        'Totals' =>  "LEFT JOIN #__jreviews_listing_totals AS Totals ON Totals.listing_id = Review.pid AND Totals.extension = 'com_content'"
		,'JreviewsCategory' => "LEFT JOIN #__jreviews_categories AS JreviewsCategory ON JreviewsCategory.id = Listing.catid AND JreviewsCategory.`option` = 'com_content'"
	);

	var $limit;

	var $offset;

	var $order = array();

    public static $joinListingState = array(
        'INNER JOIN #__content AS Listing ON Listing.id = %s AND Listing.state = 1',
        'INNER JOIN #__categories AS Category ON Listing.catid = Category.id AND Category.published = 1'
        );

	function __construct()
    {
        parent::__construct();

		$this->tag = __t("Listing",true);  // Used in MyReviews page to differentiate from other component reviews

		// Uncomment line below to show tag in My Reviews page
//		$this->fields[] = "'{$this->tag }' AS `Listing.tag`";

        $this->Routes =  ClassRegistry::getClass('RoutesHelper');

        $this->_SIMPLE_SEARCH_FIELDS = array(
                'title'         =>'Listing.' . self::_LISTING_TITLE,
                'introtext'     =>'Listing.' . self::_LISTING_SUMMARY,
                'fulltext'      =>'Listing.' . self::_LISTING_DESCRIPTION
            );
	}

	public static function exists() {

        return (bool) file_exists(PATH_ROOT . 'components' . _DS . 'com_content' . _DS . 'content.php');
	}

	function listingUrl($listing)
    {
		return $this->Routes->content('',$listing,array('return_url'=>true,'sef'=>false));
	}

    // Used to check whether reviews can be posted by listing owners
    function getListingOwner($id)
    {
        $query = "
            SELECT
                Listing." . self::_LISTING_USER_ID . " AS user_id, User.name, User.email
            FROM
                " . self::_LISTING_TABLE . " AS Listing
            LEFT JOIN
                #__users AS User ON Listing." . self::_LISTING_USER_ID . " = User.id
            WHERE
                Listing." . self::_LISTING_ID . " = " . (int) ($id);

        $result = $this->query($query,'loadAssoc');

		return $result;
    }

    function excludeDirectoryFiltering(& $conditions, $dirId)
    {
        if(!$dirId) return;

        $dir_ids = cleanIntegerCommaList($dirId);

        $query = '
            SELECT
                JreviewsCategory.id
            FROM
                #__jreviews_categories AS JreviewsCategory
            LEFT JOIN
                #__categories AS Category On JreviewsCategory.id = Category.id
            WHERE
                JreviewsCategory.dirid IN (' . $dir_ids . ')
                AND JreviewsCategory.`option`= "com_content"
            ';

        $cat_ids = $this->query($query,'loadColumn');

        $conditions['ExcludeCategory.cat_id'] = 'Listing.catid NOT IN (' . cleanIntegerCommaList($cat_ids) . ')';
    }

    function addCategoryFiltering(& $conditions, & $Access, $options = array())
    {
        if (Sanitize::getInt($options,'listing_id') > 0) return;

        $cat_ids = Sanitize::getVar($options,'cat_id');

        $criteria_ids = Sanitize::getVar($options,'criteria_id');

        $dir_ids = Sanitize::getVar($options,'dir_id');

        $cat_ids = cleanIntegerCommaList($cat_ids);

        $criteria_ids = cleanIntegerCommaList($criteria_ids);

        $dir_ids = cleanIntegerCommaList($dir_ids);

        $state = Sanitize::getInt($options,'state');

        $access_param = Sanitize::getString($options,'access');

        $accessLevels =  $access_param ? $access_param : cleanIntegerCommaList($Access->getAccessLevels());

        $catPubState = !$state && $Access->isPublisher() ? 0 : 1;

        $catStateFilter = $catPubState ? 'Category.published >= 0' : 'Category.published = 1';

        $include_children = Sanitize::getBool($options,'children',true);

        if ($cat_ids && !$include_children)
        {
            // Nothing else to do
        }
        elseif ($cat_ids) {
            $cat_ids = $this->getCatIdsFromParentCatId($cat_ids, $catPubState, $accessLevels);
        }
        elseif ($criteria_ids) {
            $cat_ids = $this->getCatIdsFromListingTypeId($criteria_ids, $catPubState, $accessLevels);
        }
        elseif ($dir_ids) {
            $cat_ids = $this->getCatIdsFromDirId($dir_ids, $catPubState, $accessLevels);
        }
        else {

            // Need to make sure the categories are limited to only those setup in JReviews
            // Sept 1, 2016 - Added the state condition and access level filters

            $query = '
                SELECT
                    JreviewsCategory.id
                FROM
                    #__jreviews_categories AS JreviewsCategory
                LEFT JOIN
                    #__categories AS Category On JreviewsCategory.id = Category.id
                WHERE
                    JreviewsCategory.`option`= "com_content"
                    AND ' . $catStateFilter . '
                    AND Category.access IN ( ' . $accessLevels . ')
                ';

            $cat_ids = $this->query($query,'loadColumn');
        }

        if($cat_ids)
        {
            $conditions['Category.cat_id'] = 'Listing.catid IN (' . cleanIntegerCommaList($cat_ids) . ')';
        }
    }

    function addListingFiltering(& $conditions, & $Access, $options = array())
    {
        $User = cmsFramework::getUser();

        $user_id = Sanitize::getInt($options,'user_id');

        // If state is set then force the query to state = 1

        $state = Sanitize::getString($options,'state');

        $access = Sanitize::getInt($options,'access');

        $action =  isset($options['action']) ? $options['action'] : Sanitize::getString($this,'controller_action');

        $access_param = Sanitize::getString($options,'access');

        $access_levels =  $access_param ? $access_param : cleanIntegerCommaList($Access->getAccessLevels());

        // Added check so filtering by rejected listings is possible in the administration
        // or simply if we want to override the listing state

        $stateColumn = self::_LISTING_STATE;

        $stateConditionExists = array_filter($conditions, function($value) use ($stateColumn) {
            return preg_match('/Listing.' . $stateColumn . '/', $value);
        });

        // The order of the conditions is important for the indexes to work, do not change!

        if ($state == 'all')
        {
            if(!$stateConditionExists)
            {
                $conditions['Listing.state'] = 'Listing.state >= 0';
            }
        }
        elseif (!$state && $user_id > 0 && (($action == 'mylistings' && $user_id == $User->id) || $Access->isPublisher()))
        {
            if(!$stateConditionExists)
            {
                $conditions['Listing.state'] = 'Listing.state >= 0';
            }

            # Shows only links users can access

            $conditions['Listing.access'] = 'Listing.access IN ( ' . $access_levels . ')';
        }
        else
        {
            if(!$stateConditionExists)
            {
                $conditions['Listing.state'] = 'Listing.state = 1';
            }

            # Shows only links users can access

            $conditions['Listing.access'] = 'Listing.access IN ( ' . $access_levels . ')';

            $conditions['Listing.publish_up'] = '( Listing.publish_up = "' . NULL_DATE . '" OR Listing.publish_up <= "' . _END_OF_TODAY . '" )';

            $conditions['Listing.publish_down'] = '( Listing.publish_down = "' . NULL_DATE . '" OR Listing.publish_down > "' . _TODAY . '" )';
        }
    }

    function whereModerated()
    {
        return 'Listing.state = 0';
    }

    /**
     * Adds the subcategories to the passed cat IDs
     * @param  [type]  $typeId       [description]
     * @param  integer $pubState     [description]
     * @param  [type]  $accessLevels [description]
     * @return [type]                [description]
     */
    function getCatIdsFromParentCatId($catId, $pubState = 1, $accessLevels)
    {
        $catStateFilter = $pubState ? 'Category.published >= 0' : 'Category.published = 1';

        $catId = cleanIntegerCommaList($catId);

        $catIds = explode(',', $catId);

        $query = array();

        foreach ($catIds AS $id)
        {
            $query[] = '
                SELECT
                    Category.id
                FROM
                    #__categories AS Category,
                    #__categories AS ParentCategory
                WHERE
                    ' . $catStateFilter . '
                    AND Category.extension = "com_content"
                    AND Category.access IN ( ' . $accessLevels . ')
                    AND Category.lft BETWEEN ParentCategory.lft AND ParentCategory.rgt
                    AND ParentCategory.id = ' . $id . '
            ';
        }

        $query = implode(' UNION ALL ', $query);

        $currCatIds = $this->query($query,'loadColumn');

        // Need to make sure the categories are limited to only those setup in JReviews

        $query = '
            SELECT id FROM #__jreviews_categories WHERE `option` = "com_content"
        ';

        $allCatIds = $this->query($query,'loadColumn');

        $finalCatIds = array_intersect($currCatIds, $allCatIds);

        return $finalCatIds;
    }

    function getCatIdsFromListingTypeId($typeId, $pubState = 1, $accessLevels)
    {
        $typeId = cleanIntegerCommaList($typeId);

        $catStateFilter = $pubState ? 'Category.published >= 0' : 'Category.published = 1';

        $query = '
            SELECT
                JreviewsCategory.id
            FROM
                #__jreviews_categories AS JreviewsCategory
            LEFT JOIN
                #__categories AS Category On JreviewsCategory.id = Category.id
            WHERE
                JreviewsCategory.criteriaid IN (' . $typeId . ')
                AND JreviewsCategory.`option`= "com_content"
                AND ' . $catStateFilter . '
                AND Category.access IN ( ' . $accessLevels . ')
            ';

        $catIds = $this->query($query,'loadColumn');

        return $catIds;
    }

    function getCatIdsFromDirId($dirId, $pubState = 1, $accessLevels)
    {
        $dirId = cleanIntegerCommaList($dirId);

        $catStateFilter = $pubState ? 'Category.published >= 0' : 'Category.published = 1';

        $query = '
            SELECT
                JreviewsCategory.id
            FROM
                #__jreviews_categories AS JreviewsCategory
            LEFT JOIN
                #__categories AS Category On JreviewsCategory.id = Category.id
            WHERE
                JreviewsCategory.dirid IN (' . $dirId . ')
                AND JreviewsCategory.`option`= "com_content"
                AND ' . $catStateFilter . '
                AND Category.access IN ( ' . $accessLevels . ')
            ';

        $catIds = $this->query($query,'loadColumn');

        return $catIds;
    }

    function getFieldOptionUsageCount($field, $params)
    {
        $Access = Configure::read('JreviewsSystem.Access');

        // For compatibility with old function arguments where 2nd param was the cat ID
        if (!is_array($params))
        {
            $params = array('cat' => $params);
        }

        $catId = Sanitize::getVar($params, 'cat');

        $typeId = Sanitize::getVar($params, 'listing_type');

        $dirId = Sanitize::getVar($params, 'dir');

        $catId = cleanIntegerCommaList($catId);

        $typeId = cleanIntegerCommaList($typeId);

        $dirId = cleanIntegerCommaList($dirId);

        $accessLevels = $Access->getAccessLevels();

        $accessLevels = cleanIntegerCommaList($accessLevels);

        if ($catId) {
            $catId = $this->getCatIdsFromParentCatId($catId, $pubState = 1, $accessLevels);
        }
        elseif (!$catId && $typeId) {
            $catId = $this->getCatIdsFromListingTypeId($typeId, $pubState = 1, $accessLevels);
        }
        elseif (!$catId && $dirId) {
            $catId = $this->getCatIdsFromDirId($dirId, $pubState = 1, $accessLevels);
        }

        $catId = cleanIntegerCommaList($catId);

        // ADD CAT ID CONDITIONAL
        $query = '
            SELECT
                ' . $field . ' AS value, count(*) AS total
            FROM
                #__jreviews_content AS Field
            LEFT JOIN
                #__content AS Listing ON Listing.id = Field.contentid
            WHERE
                ' . $field . ' <> ""
                ' . ($catId ? 'AND Listing.catid IN ('. $catId .') ' : '') . '
                AND Listing.state = 1
                AND Listing.access IN (' . $accessLevels . ')
                AND ( Listing.publish_up = "'.NULL_DATE.'" OR Listing.publish_up <= "'._END_OF_TODAY.'" )
                AND ( Listing.publish_down = "'.NULL_DATE.'" OR Listing.publish_down > "'._TODAY.'" )
            GROUP BY
                ' . $field . '
            ORDER BY
                ' . $field . '
        ';

        $rows = $this->query($query, 'loadAssocList');

        return $rows;
    }

   /**
     * Reformat
     * @param  &      $data [description]
     * @return [type]       [description]
     */
    function beforeSave(& $data)
    {
        $data = $this->toUTC($data);

        $this->data = $data;
    }

   function toLocal($listing)
    {
        $timezone = Sanitize::getString($listing,'timezone');

        if($timezone == 'UTC' || $timezone == '')
        {
            $publish_down = Sanitize::getString($listing['Listing'],'publish_down');

            $publish_up = Sanitize::getString($listing['Listing'],'publish_up');

            if($publish_down && $publish_down != NULL_DATE)
            {
                $listing['Listing']['publish_down_UTC'] = $listing['Listing']['publish_down'];

                $listing['Listing']['publish_down'] = cmsFramework::dateUTCtoLocal($listing['Listing']['publish_down']);
            }

            if($publish_up && $publish_up != NULL_DATE)
            {
                $listing['Listing']['publish_up_UTC'] = $listing['Listing']['publish_up'];

                $listing['Listing']['publish_up'] = cmsFramework::dateUTCtoLocal($listing['Listing']['publish_up']);
            }

            $listing['timezone'] = 'local';
        }

        return $listing;
    }

    function toUTC($listing)
    {
        $timezone = Sanitize::getString($listing,'timezone');

        if($timezone == 'local')
        {
            $publish_down = Sanitize::getString($listing['Listing'],'publish_down');

            $publish_up = Sanitize::getString($listing['Listing'],'publish_up');

            if($publish_down && $publish_down != NULL_DATE)
            {
                $listing['Listing']['publish_down'] = isset($listing['Listing']['publish_down_UTC']) ? $listing['Listing']['publish_down_UTC'] : cmsFramework::dateLocalToUTC($listing['Listing']['publish_down']);
            }

            if($publish_up && $publish_up != NULL_DATE)
            {
                $listing['Listing']['publish_up'] = isset($listing['Listing']['publish_up_UTC']) ? $listing['Listing']['publish_up_UTC'] : cmsFramework::dateLocalToUTC($listing['Listing']['publish_up']);
            }

            $listing['timezone'] = 'UTC';
        }

        return $listing;
    }

    function afterFind($results)
    {
        if (empty($results))
        {
            return $results;
        }

        // Read in media model to ignore the Listing Type overrides and also below
        // to ignore the listing title override
        $disable_overrides = Sanitize::getBool($this,'disable_overrides',false);

        S2App::import('Model',array('menu','favorite','field','criteria','review'),'jreviews');

        # Add Menu ID info for each row (Itemid)

        $Menu = ClassRegistry::getClass('MenuModel');

		$results = $Menu->addMenuListing($results);

        # Add listing type and criteria rating info to results array

        $ListingTypes = ClassRegistry::getClass('CriteriaModel');

        $results = $ListingTypes->addListingTypes($results,'Listing');

        # Add custom field info to results array

        if($this->runAfterFindModel('Field'))
        {
            $CustomFields = ClassRegistry::getClass('FieldModel');

            $results = $CustomFields->addFields($results,'listing');
        }

        # Reformat image and criteria info

        $controller =  Sanitize::getString($this,'controller_name');

        $action =  Sanitize::getString($this,'controller_action');

        foreach($results AS $key=>$listing)
        {
            $results[$key] = $listing = $this->toLocal($listing);

            // Add review counts for each rating (range in case scale is higher than 5)

            if(($controller == 'com_content' && $action == 'com_content_view')
                || ($controller == 'listings' && $action == 'detail')
                )
            {
                $ReviewModel = ClassRegistry::getClass('ReviewModel');

                $results[$key]['ReviewRatingCount'] = $ReviewModel->getReviewRatingCounts($key, $listing['Listing']['extension']);
            }

            // Check for guest user submissions

            if(isset($listing['User'])
                && ($listing['User']['user_id'] == 0
                || ($listing['User']['user_id'] == 62 && $listing['Listing']['author_alias']!='')))
            {
                $results[$key]['User']['name'] = $listing['Listing']['author_alias'];
                $results[$key]['User']['username'] = $listing['Listing']['author_alias'];
                $results[$key]['User']['user_id'] = 0;
            }

            if(isset($listing['Listing']['summary']) && $action != 'edit')
            {
                // If stripped version is the same as the non-stripped version then there are no html tags and we display line breaks

                $results[$key]['Listing']['summary'] = strcmp($listing['Listing']['summary'], strip_tags($listing['Listing']['summary'])) == 0
                    ?
                        nl2br($listing['Listing']['summary'])
                    :
                        $listing['Listing']['summary']
                    ;
            }

            if(isset($listing['Listing']['description']) && $action != 'edit')
            {
                // If stripped version is the same as the non-stripped version then there are no html tags and we display line breaks

                $results[$key]['Listing']['description'] = strcmp($listing['Listing']['description'], strip_tags($listing['Listing']['description'])) == 0
                    ?
                        nl2br($listing['Listing']['description'])
                    :
                        $listing['Listing']['description']
                    ;
            }

            // Remove plugin tags everywhere except when editing a listing and in the detail page

            if(isset($results[$key]['Listing']['summary'])
                && $action != 'edit' && $action != 'com_content_view')
            {
                $regex = "#{[a-z0-9]*(.*?)}(.*?){/[a-z0-9]*}#s";

                $results[$key]['Listing']['summary'] = preg_replace( $regex, '', $results[$key]['Listing']['summary'] );
            }

             // Escape quotes in meta tags

            isset($listing['Listing']['metakey']) and $listing['Listing']['metakey'] = htmlspecialchars($listing['Listing']['metakey'],ENT_QUOTES,'UTF-8');

            isset($listing['Listing']['metadesc']) and $listing['Listing']['metadesc'] = htmlspecialchars($listing['Listing']['metadesc'],ENT_QUOTES,'UTF-8');

            # Config overrides
            if(isset($listing['ListingType']))
            {
                if(isset($results[$key]['ListingType']['config']['relatedlistings']))
                {
                    foreach($results[$key]['ListingType']['config']['relatedlistings'] AS $rel_key=>$rel_row)
                    {
                        isset($rel_row['criteria']) and $results[$key]['ListingType']['config']['relatedlistings'][$rel_key]['criteria'] = implode(',',$rel_row['criteria']);
                    }
                }
            }
            else {
                $results[$key]['ListingType'] = array('config' => array());
            }

            $results[$key][$this->name]['url'] = $this->listingUrl($listing);

            // Add detailed rating info

            // If $listing['Rating'] is already set we don't want to overwrite it because it's for individual reviews

            $results[$key]['RatingUser'] = array();

            $results[$key]['RatingEditor'] = array();

            if(!empty($listing['CriteriaRating'])
                && isset($listing['Review'])
                && !isset($listing['Rating'])
                && ($listing['Review']['user_rating_count'] > 0 || $listing['Review']['editor_rating_count'] > 0)
                )
            {
                $ratings = explode(',', $listing['Review']['user_criteria_rating']);

                if($listing['Review']['user_rating_count'] && (count($listing['CriteriaRating']) == count($ratings)))
                {
                    array_walk($ratings, function(&$value) { if((float) $value == 0) $value = 'na'; });

                    $array_keys = array_keys($listing['CriteriaRating']);

                    $array_values = array_values($ratings);

                    $ratings = array_combine($array_keys, $array_values);

                    $ratings_count = explode(',', $listing['Review']['user_criteria_rating_count']);

                    $ratings_count = array_combine(array_keys($listing['CriteriaRating']), array_values($ratings_count));

                    $results[$key]['RatingUser'] = array(
                            'average_rating' => $listing['Review']['user_rating'],
                            'ratings' => $ratings,
                            'criteria_rating_count' => $ratings_count
                        );
                }

                if($listing['Review']['editor_rating_count'] && (count($listing['CriteriaRating']) == count(explode(',', $listing['Review']['editor_criteria_rating_count']))))
                {
                    $ratings = explode(',', $listing['Review']['editor_criteria_rating']);

                    array_walk($ratings, function(&$value) { if((float) $value == 0) $value = 'na'; });

                    $ratings = array_combine(array_keys($listing['CriteriaRating']), array_values($ratings));

                    $ratings_count = explode(',', $listing['Review']['editor_criteria_rating_count']);

                    $ratings_count = array_combine(array_keys($listing['CriteriaRating']), array_values($ratings_count));

                    $results[$key]['RatingEditor'] = array(
                            'average_rating' => $listing['Review']['editor_rating'],
                            'ratings' => $ratings,
                            'criteria_rating_count' => $ratings_count
                        );
                }
            }

            if(isset($listing['Review'])) {

                $results[$key]['Review']['review_count'] = Sanitize::getInt($listing['Review'],'review_count'); // Make sure it's zero if empty
            }

            // Override listing title

            if(isset($listing['ListingType']) && !$disable_overrides)
            {
                $listing = $results[$key];

                $page_title = Sanitize::getString($listing['ListingType']['config'],'type_metatitle');

                $override_listing_title = Sanitize::getInt($listing['ListingType']['config'],'override_listing_title',0);

                if(in_array($controller,array('categories','com_content')) && $override_listing_title && $page_title != '')
                {
                    // Get and process all tags
                    $tags = self::extractTags($page_title);

                    $tags_array = array();

                    foreach($tags AS $tag)
                    {
                        switch($tag)
                        {
                            case 'title':
                                $tags_array['{title}'] = Sanitize::stripAll($listing['Listing'],'title');
                            break;
                            case 'directory':
                                $tags_array['{directory}'] = Sanitize::stripAll($listing['Directory'],'title');
                            break;
                            case 'category':
                                $tags_array['{category}'] = Sanitize::stripAll($listing['Category'],'title');
                            break;
                            default:
                                if(substr($tag,0,3) == 'jr_' && isset($listing['Field']))
                                {
                                    $fields = $listing['Field']['pairs'];

                                    if(isset($listing['Field']['pairs'][$tag]) && isset($fields[$tag]['text']))
                                    {
                                        $fieldValue = $fields[$tag]['text'][0];

                                        $properties = $fields[$tag]['properties'];

                                        if($fields[$tag]['type'] == 'date')
                                        {
                                            $format = Sanitize::getString($properties,'date_format');

                                            $TimeHelper = ClassRegistry::getClass('TimeHelper');

                                            $fieldValue = $TimeHelper->nice($fieldValue,$format,0);
                                        }
                                        elseif($fields[$tag]['type'] == 'decimal') {

                                            $decimals = Sanitize::getInt($properties,'decimals',2);

                                            $fieldValue = Sanitize::getInt($properties,'curr_format') ? number_format($fieldValue,$decimals,__l('DECIMAL_SEPARATOR',true),__l('THOUSANDS_SEPARATOR',true)) : round($fieldValue,$decimals);
                                        }
                                        elseif($fields[$tag]['type'] == 'integer') {

                                            $fieldValue = Sanitize::getInt($properties,'curr_format') ? number_format($fieldValue,0,__l('DECIMAL_SEPARATOR',true),__l('THOUSANDS_SEPARATOR',true)) : $fieldValue;
                                        }

                                        if(in_array($fields[$tag]['type'],array('integer','decimal')))
                                        {
                                            $fieldValue = str_ireplace('{fieldtext}', $fieldValue, Sanitize::getString($properties,'output_format'));
                                        }

                                        $fields[$tag]['text'][0] = $fieldValue;


                                        $tags_array['{'.$tag.'}'] =  html_entity_decode(implode(", ", $fields[$tag]['text']),ENT_QUOTES,'utf-8');
                                    }
                                }
                            break;
                        }
                    }

                    $results[$key]['Listing']['title'] = trim(str_replace('&amp;','&',str_replace(array_keys($tags_array),$tags_array,$page_title)));
                }
            }
        }

        if($this->runAfterFindModel('Favorite') && (!defined('MVC_FRAMEWORK_ADMIN') || MVC_FRAMEWORK_ADMIN == 0)) {

            # Add Favorite info to results array

            $Favorite = ClassRegistry::getClass('FavoriteModel');

            $Favorite->Config = &$this->Config;

            $results = $Favorite->addFavorite($results);
        }

		# Add media info

        if($this->runAfterFindModel('Media') && class_exists('MediaModel'))
		{
			$Media = ClassRegistry::getClass('MediaModel');

			if(!isset($this->Config)) {

                $Config = Configure::read('JreviewsSystem.Config');
			}
			else {

				$Config = & $this->Config;
			}

			$results = $Media->addMedia(
				$results,
				'Listing',
				'listing_id',
				array(
					'sort'=>Sanitize::getString($Config,'media_general_default_order_listing'),
					'extension'=>'com_content',
					'controller'=>Sanitize::getString($this,'controller_name'),
					'action'=>Sanitize::getString($this,'controller_action'),
					'photo_limit'=>Sanitize::getInt($Config,'media_detail_photo_limit'),
					'video_limit'=>Sanitize::getInt($Config,'media_detail_video_limit'),
					'attachment_limit'=>Sanitize::getInt($Config,'media_detail_attachment_limit'),
					'audio_limit'=>Sanitize::getInt($Config,'media_detail_audio_limit'),
                    'photo_layout'=>Sanitize::getString($Config,'media_detail_photo_layout'),
                    'video_layout'=>Sanitize::getString($Config,'media_detail_video_layout'),
                    'disable_overrides'=>Sanitize::getInt($this,'disable_overrides')
				)
			);

		}

        # Add Community info to results array
        // Sep 21, 2016 - Moved last because it makes changes to properties of the EverywhereComContentModel like changing the controller name/action
        // Dec 7, 2016 - It was necessary to add this specific flag and check in the Everywhere Model to prevent an infinite recursion when adding profiles to listings

        $addProfile = !isset($this->addProfile) || (isset($this->addProfile) && $this->addProfile === true);

        if($addProfile && isset($listing['User']) && $this->runAfterFindModel('Community') && !defined('MVC_FRAMEWORK_ADMIN') && class_exists('CommunityModel'))
        {
            $Community = ClassRegistry::getClass('CommunityModel');

            $results = $Community->addProfileInfo($results, 'User', 'user_id');
        }

		$this->clearAllAfterFindModel();

		return $results;
    }

    static function extractTags($text)
    {
        $pattern = '/{([a-z0-9_|]*)}/i';

        $matches = array();

        $result = preg_match_all( $pattern, $text, $matches );

        if( $result == false ) {
            return array();
        }

        return array_unique(array_values($matches[1]));
    }

	/**
	 * This can be used to add post save actions, like synching with another table
	 *
	 * @param array $model
	 */
    function afterSave($status)
    {
        cmsFramework::clearSessionVar('Listing', 'findCount');

        clearCache('','__data');

        clearCache('','views');

        if(isset($this->name))
        {
            switch($this->name)
            {
                case 'Review':break;
                case 'Listing':

                    $query = "
                        INSERT INTO
                            #__jreviews_listing_totals
                            (listing_id, extension)
                            VALUES
                            (" . $this->data['Listing']['id'] .", 'com_content')
                        ON DUPLICATE KEY UPDATE
                        listing_id = " . $this->data['Listing']['id']. ",
                        extension = 'com_content'
                    ";

                    $this->query($query);

                break;
            }
        }
    }

    function processSorting($order = '', $addCondition = false)
    {
        $matches = array();

        # Order by custom field
        if (false !== (strpos($order,'jr_')))
        {
            $this->__orderByField($order);
        }
        elseif (substr($order, 0, 15) == 'Field.contentid')
        {
            $direction = substr($order, -4) == 'DESC' ? 'DESC' : 'ASC';

            $this->switchFromTable('contentid', $direction);
        }
        elseif(preg_match('/(?P<type>' . S2_QVAR_PREFIX_EDITOR_RATING_CRITERIA . '|' . S2_QVAR_PREFIX_RATING_CRITERIA . ')-(?P<criteria_id>\d+)/', $order,$matches)) {

            $this->order[$order] = $this->__urlToSqlOrderBy($matches);
        }
        else {

            $this->order[$order] = $this->__urlToSqlOrderBy($order, $addCondition);
        }
	}

    function __orderByField($field)
    {
        $direction = 'ASC';

        if (false !== (strpos($field,'rjr_'))) {
            $field = substr($field,1);
            $direction = 'DESC';
        }

        $FieldModel = ClassRegistry::getClass('FieldModel');

        $queryData = array(
            'fields'=>array('Field.fieldid AS `Field.field_id`'),
            'conditions'=>array(
                'Field.name = "'.$field.'"',
//                    'Field.listsort = 1'
                )
        );

        $field_id = $FieldModel->findOne($queryData);

        if ($field_id)
        {
            if(!isset($this->Config)) {

                $Config = Configure::read('JreviewsSystem.Config');
            }
            else {

                $Config = & $this->Config;
            }

            // We switch the main table so the order by is better optimized

            $this->switchFromTable($field, $direction);

            $use_index_hints = !Sanitize::getInt($Config,'db_index_hints_disable');

            if($use_index_hints)
            {
                $indexes = $FieldModel->getIndexes('listing');

                if(in_array($field, $indexes))
                {
                    // We have to use Listing instead of Field due to the main table switch we are doing above

                    $this->useKey = array('Listing'=>$field); // KEY HINT por improved performance
                }
            }
        }
    }

    protected function switchFromTable($field, $direction)
    {
        // We switch the main table so the order by is better optimized

        $this->useTable = '#__jreviews_content AS Field';

        unset($this->joins['Field']);

        $this->joins = array_merge(array('Field'=>"LEFT JOIN " . self::_LISTING_TABLE . " AS Listing ON Field.contentid = Listing." . self::_LISTING_ID), $this->joins);

        $this->fields[] = 'Field.' . $field . ' AS `Field.' . $field . '`';

        $this->order['Field.' . $field] = 'Field.' . $field . ' ' .$direction;

        /**
         * #performance
         * The secondary order significantly reduces the query performance because it is not possible to
         * have an indexed query when the ORDER BY includes columns from two different tables
         */
        // $this->order[] = 'Listing.' . self::_LISTING_CREATE_DATE . ' DESC';

        /**
         * Having a second unique ordering ensures a consistent ordering when the first value is the same for the results
         */
        if ($field !== 'contentid')
        {
            $this->order['Field.' . $field] = 'Field.contentid DESC';
        }
    }

    function __urlToSqlOrderBy($sort, $addCondition = false)
    {
        $order = '';

        if(!isset($this->Config)) {

            $Config = Configure::read('JreviewsSystem.Config');
        }
        else {

            $Config = & $this->Config;
        }

        $use_index_hints = !Sanitize::getInt($Config,'db_index_hints_disable');

        $user_review_bayesian = Sanitize::getInt($Config,'user_review_bayesian',1);

        $editor_review_bayesian = Sanitize::getInt($Config,'editor_review_bayesian',1);

        if(is_array($sort) && isset($sort['type']) && $criteria_id = $sort['criteria_id'])
        {
            $criteria_id = $sort['criteria_id'];

            if($sort['type'] == S2_QVAR_PREFIX_RATING_CRITERIA)
            {
                $sort = 'user_criteria_rating';
            }
            elseif($sort['type'] == S2_QVAR_PREFIX_EDITOR_RATING_CRITERIA)
            {
                $sort = 'editor_criteria_rating';
            }
        }

        /**
         * #performance
         * If you are looking into this file to modify the ordering of different sorting options read carefully
         * We've put a lot of effort into optimizing the listing queries. When you start adding additional ordering
         * columns or ordering columns in tables that are not in the main table in the query then the performance
         * of those queries suffer. Consider "living" with the current ordering in favor of better peformance
         */

        switch($sort)
        {
            case 'featured':

                // We switch the main table so the order by is better optimized

                $this->useTable = '#__jreviews_content AS Field';

                unset($this->joins['Field']);

                $this->joins = array_merge(array('Field'=>"LEFT JOIN " . self::_LISTING_TABLE . " AS Listing ON Field.contentid = Listing." . self::_LISTING_ID), $this->joins);

                /**
                 * #performance
                 * Adding the listing creation date as a secondary ordering reduces the performance of the query
                 * We are using the Listing id (Field.contentid) as the next best thing
                 */

                $order = 'Field.featured DESC, Field.contentid DESC';

                // We have to use Listing instead of Field due to the main table switch we are doing above

                $use_index_hints and $this->useKey = array('Listing'=>'featured'); // KEY HINT por improved performance

            break;


            case 'editor_rating':
            case 'author_rating':

                // We switch the main table so the order by is better optimized

                $this->useTable = '#__jreviews_listing_totals AS Totals';

                $this->joins['Totals'] = "LEFT JOIN " . self::_LISTING_TABLE . " AS Listing ON Totals.listing_id = Listing." . self::_LISTING_ID . " AND Totals.extension = 'com_content'";

                $order = $editor_review_bayesian ? 'Totals.editor_rating_rank DESC, Totals.listing_id DESC' : 'Totals.editor_rating DESC, Totals.listing_id DESC';

                $addCondition and $this->conditions[] = 'Totals.editor_rating > 0';

                // We have to use Listing instead of Field due to the main table switch we are doing above

                $use_index_hints and $this->useKey = array('Listing'=>'editor_rating_rank'); // KEY HINT por improved performance

            break;

            case 'reditor_rating':

                // We switch the main table so the order by is better optimized

                $this->useTable = '#__jreviews_listing_totals AS Totals';

                $this->joins['Totals'] = "LEFT JOIN " . self::_LISTING_TABLE . " AS Listing ON Totals.listing_id = Listing." . self::_LISTING_ID . " AND Totals.extension = 'com_content'";

                $order = $editor_review_bayesian ? 'Totals.editor_rating_rank ASC, Totals.listing_id DESC' : 'Totals.editor_rating ASC, Totals.listing_id DESC';

                $addCondition and $this->conditions[] = 'Totals.editor_rating > 0';

                // We have to use Listing instead of Field due to the main table switch we are doing above

                $use_index_hints and $this->useKey = array('Listing'=>'editor_rating_rank'); // KEY HINT por improved performance

            break;

            case 'rating':

                // We switch the main table so the order by is better optimized

                $this->useTable = '#__jreviews_listing_totals AS Totals';

                $this->joins['Totals'] = "LEFT JOIN " . self::_LISTING_TABLE . " AS Listing ON Totals.listing_id = Listing." . self::_LISTING_ID . " AND Totals.extension = 'com_content'";

                $order = $user_review_bayesian ? 'Totals.user_rating_rank DESC, Totals.listing_id DESC' : 'Totals.user_rating DESC, Totals.listing_id DESC';

                $addCondition and $this->conditions[] = 'Totals.user_rating > 0';

                // We have to use Listing instead of Field due to the main table switch we are doing above

                $use_index_hints and $this->useKey = array('Listing'=>'user_rating_rank'); // KEY HINT por improved performance

            break;

            case 'rrating':

                // We switch the main table so the order by is better optimized

                $this->useTable = '#__jreviews_listing_totals AS Totals';

                $this->joins['Totals'] = "LEFT JOIN " . self::_LISTING_TABLE . " AS Listing ON Totals.listing_id = Listing." . self::_LISTING_ID . " AND Totals.extension = 'com_content'";

                $order = $user_review_bayesian ? 'Totals.user_rating_rank ASC, Totals.listing_id DESC' : 'Totals.user_rating ASC, Totals.listing_id DESC';

                $addCondition and $this->conditions[] = 'Totals.user_rating > 0';

                // We have to use Listing instead of Field due to the main table switch we are doing above

                $use_index_hints and $this->useKey = array('Listing'=>'user_rating_rank'); // KEY HINT por improved performance

            break;

           case 'user_criteria_rating':

                // We switch the main table so the order by is better optimized

                $this->useTable = '#__jreviews_listing_ratings AS ListingRating';

                array_unshift($this->joins, "LEFT JOIN " . self::_LISTING_TABLE . " AS Listing ON ListingRating.listing_id = Listing." . self::_LISTING_ID . " AND ListingRating.extension = 'com_content'");

                $this->joins['Totals'] = "LEFT JOIN #__jreviews_listing_totals AS Totals ON Totals.listing_id = ListingRating.listing_id AND Totals.extension = 'com_content'";

                $order = $user_review_bayesian ? 'ListingRating.user_rating_rank DESC, ListingRating.listing_id DESC' : 'ListingRating.user_rating DESC, ListingRating.listing_id DESC';

                $this->conditions[] = 'ListingRating.user_rating > 0 AND ListingRating.criteria_id = ' . $criteria_id;

                // We have to use Listing instead of Field due to the main table switch we are doing above

                // $use_index_hints and $this->useKey = array('Listing'=>'user_rating_rank'); // KEY HINT por improved performance

            break;

            case 'editor_criteria_rating':

                // We switch the main table so the order by is better optimized

                $this->useTable = '#__jreviews_listing_ratings AS ListingRating';

                array_unshift($this->joins, "LEFT JOIN " . self::_LISTING_TABLE . " AS Listing ON ListingRating.listing_id = Listing." . self::_LISTING_ID . " AND ListingRating.extension = 'com_content'");

                $this->joins['Totals'] = "LEFT JOIN #__jreviews_listing_totals AS Totals ON Totals.listing_id = ListingRating.listing_id AND Totals.extension = 'com_content'";

                $order = $editor_review_bayesian ? 'ListingRating.editor_rating_rank DESC, ListingRating.listing_id DESC' : 'ListingRating.editor_rating DESC, ListingRating.listing_id DESC';

                $this->conditions[] = 'ListingRating.editor_rating > 0 AND ListingRating.criteria_id = ' . $criteria_id;

                // We have to use Listing instead of Field due to the main table switch we are doing above

                // $use_index_hints and $this->useKey = array('Listing'=>'editor_rating_rank'); // KEY HINT por improved performance

            break;

            case 'reviews':

                // We switch the main table so the order by is better optimized

                $this->useTable = '#__jreviews_listing_totals AS Totals';

                $this->joins['Totals'] = "LEFT JOIN " . self::_LISTING_TABLE . " AS Listing ON Totals.listing_id = Listing." . self::_LISTING_ID . " AND Totals.extension = 'com_content'";

                $order = 'Totals.user_comment_count DESC, Totals.listing_id DESC';

                $addCondition and $this->conditions[] = 'Totals.user_comment_count > 0';

                // We have to use Listing instead of Field due to the main table switch we are doing above

                $use_index_hints and $this->useKey = array('Listing'=>'user_comment_count'); // KEY HINT por improved performance

            break;

            case 'date':

                $order = 'Listing.' . self::_LISTING_CREATE_DATE;

                $use_index_hints and $this->useKey = array('Listing'=>'jr_created'); // KEY HINT por improved performance

            break;

            case 'latest':
            case 'rdate':

                $order = 'Listing.' . self::_LISTING_CREATE_DATE . ' DESC';

                $use_index_hints and $this->useKey = array('Listing'=>'jr_created'); // KEY HINT por improved performance

            break;

//			case 'alias':
//				$order = 'Listing.alias DESC';
//				break;

            case 'alpha':

                $order = 'Listing.' . self::_LISTING_TITLE;

                $use_index_hints and $this->useKey = array('Listing'=>'jr_title'); // KEY HINT por improved performance

            break;

            case 'ralpha':

                $order = 'Listing.' . self::_LISTING_TITLE . ' DESC';

                $use_index_hints and $this->useKey = array('Listing'=>'jr_title'); // KEY HINT por improved performance

            break;

            case 'hits':

                $order = 'Listing.hits ASC';

                $use_index_hints and $this->useKey = array('Listing'=>'jr_hits'); // KEY HINT por improved performance

            break;

            case 'rhits':

                $order = 'Listing.hits DESC';

                $use_index_hints and $this->useKey = array('Listing'=>'jr_hits'); // KEY HINT por improved performance

            break;

            case 'order':

                $order = 'Listing.ordering';

                $use_index_hints and $this->useKey = array('Listing'=>'jr_ordering'); // KEY HINT por improved performance

            break;

            case 'author':

                if ($this->Config->name_choice == 'realname')
                {
                    $order = 'User.' . UserModel::_USER_REALNAME . ', Listing.' . self::_LISTING_CREATE_DATE;
                }
                else {

                    $order = 'User.' . UserModel::_USER_ALIAS . ', Listing.' . self::_LISTING_CREATE_DATE;
                }
            break;

            case 'rauthor':

                if ($this->Config->name_choice == 'realname')
                {
                    $order = 'User.' . UserModel::_USER_REALNAME . ' DESC, Listing' . self::_LISTING_CREATE_DATE;
                }
                else {

                    $order = 'User.' . UserModel::_USER_ALIAS . ' DESC, Listing.' . self::_LISTING_CREATE_DATE;
                }

            break;

            case 'random':

                $order = 'RAND()';

            break;

            case 'updated':

                $order = 'Listing.' . self::_LISTING_MODIFIED .' DESC, Listing.' . self::_LISTING_CREATE_DATE .' DESC';

                $use_index_hints and $this->useKey = array('Listing'=>'jr_modified'); // KEY HINT por improved performance

            break;

            case 'distance':
                break;

            default:

                $order = 'Listing.' . self::_LISTING_TITLE;

                $use_index_hints and $this->useKey = array('Listing'=>'jr_title'); // KEY HINT por improved performance

                break;
        }

        return $order;
    }

    function getTitle($id)
    {
        $query = "
            SELECT
                Listing.title
            FROM
                #__content AS Listing
            WHERE
                Listing.id = " . (int) $id
        ;

        return $this->query($query, 'loadResult');
    }

    function getCatID($id)
    {
        $query = "
            SELECT
                Listing.catid
            FROM
                #__content AS Listing
            RIGHT JOIN
                #__jreviews_categories AS Category ON Listing.catid = Category.id AND Category.option = 'com_content'
            WHERE
                Listing.id = " . (int) $id
        ;

        return $this->query($query, 'loadResult');
    }

    function updateModifiedDate($id)
    {
        $query = "
            UPDATE
                " . self::_LISTING_TABLE . "
            SET
                modified = " . $this->Quote(_CURRENT_SERVER_TIME) . "
            WHERE
                " . self::_LISTING_ID . " = " . (int) $id
        ;

        return $this->query($query);
    }

    function updateExpirationDate($id, $date, $state = null)
    {
        $state = $state != null ? $state : 1;

        $query = "
            UPDATE
                " . self::_LISTING_TABLE . "
            SET
                state = " . (int) $state . ",
                publish_down = " . $this->Quote($date) . "
            WHERE
                " . self::_LISTING_ID . " = " . (int) $id
        ;

        return $this->query($query);
    }

    function updateFeaturedState($id, $state)
    {
        $query = "
            INSERT INTO
                #__jreviews_content (contentid,featured)
            VALUES
                ($id,$state)
            ON DUPLICATE KEY UPDATE
                featured = $state;
        ";

        return $this->query($query);
    }

    function del($ids = array())
    {
        if(!is_array($ids)) {
            $ids = array($ids);
        }

		foreach($ids AS $id)
		{
            $success = false;

            $this->data['Listing']['id'] = $id;

			$this->plgBeforeDelete('Listing.id',$id); // Only works for single listing deletion

			# Delete associated media
			$Media = ClassRegistry::getClass('MediaModel');

			$Media->deleteByListingId($id, 'com_content');
		}

		$query = '
			DELETE
				Listing,
				Frontpage,
				Field,
				ListingTotal,
                ListingRating,
                ReviewRating,
				Claim,
				ReportListing,
				Review,
				FieldReview,
				ReportReview,
				Vote,
				Discussion
			FROM
                ' . self::_LISTING_TABLE .' AS Listing
			LEFT JOIN
				#__content_frontpage AS Frontpage ON Frontpage.content_id = Listing.id
            LEFT JOIN
                #__jreviews_content AS Field ON Field.contentid = Listing.' . self::_LISTING_ID .'
            LEFT JOIN
                #__jreviews_listing_totals AS ListingTotal ON ListingTotal.listing_id = Listing.' . self::_LISTING_ID .' AND ListingTotal.extension = "com_content"
            LEFT JOIN
                #__jreviews_listing_ratings AS ListingRating ON ListingRating.listing_id = Listing.' . self::_LISTING_ID .' AND ListingRating.extension = "com_content"
            LEFT JOIN
                #__jreviews_review_ratings AS ReviewRating ON ReviewRating.listing_id = Listing.' . self::_LISTING_ID .' AND ReviewRating.extension = "com_content"
            LEFT JOIN
                #__jreviews_claims AS Claim ON Claim.listing_id = Listing.' . self::_LISTING_ID .'
            LEFT JOIN
                #__jreviews_reports AS ReportListing ON ReportListing.listing_id = Listing.' . self::_LISTING_ID .' AND ReportListing.extension = "com_content"
            LEFT JOIN
                #__jreviews_comments AS Review On Review.pid = Listing.' . self::_LISTING_ID .' AND Review.mode = "com_content"
            LEFT JOIN
                #__jreviews_review_fields AS FieldReview ON FieldReview.reviewid = Review.id
            LEFT JOIN
                #__jreviews_reports AS ReportReview ON ReportReview.review_id = Review.id
            LEFT JOIN
                #__jreviews_votes AS Vote ON Vote.review_id = Review.id
            LEFT JOIN
                #__jreviews_discussions AS Discussion ON Discussion.review_id = Review.id
            WHERE
                Listing.' . self::_LISTING_ID . ' IN (' . cleanIntegerCommaList($ids) . ')';

		if($this->query($query))
		{
            foreach($ids AS $id) {

                // Trigger plugin callback
                $this->data = $data = array('Listing'=>array('id'=>$id));

                $this->plgAfterDelete($data);

            }

			$success = true;

			// Clear cache
			cmsFramework::clearSessionVar('Listing', 'findCount');

			cmsFramework::clearSessionVar('Review', 'findCount');

			cmsFramework::clearSessionVar('Discussion', 'findCount');

			cmsFramework::clearSessionVar('Media', 'findCount');

			clearCache('', 'views');

			clearCache('', '__data');
		}

		return $success;
    }

    function feature($listing_id)
    {
        $listing_id = (int) $listing_id;

        $result = array('success'=>false,'state'=>null,'access'=>true);

        if(!$listing_id) return $result;

        # Check access
        $Access = Configure::read('JreviewsSystem.Access');

        if(!$Access->isManager())
        {
            $result['access'] = false;
            return $result;
        }

        # Load current listing featured state
        $query = "
            SELECT
                Listing.id, Field.featured AS state
            FROM
                #__content AS Listing
            LEFT JOIN
                #__jreviews_content AS Field ON Field.contentid = Listing.id
            WHERE
                Listing.id = " . $listing_id
        ;

        $listing = $this->query($query, 'loadAssocList');

        if($row = end($listing))
        {
            $new_state = $result['state'] = (int) !$row['state'];

            $res = $this->updateFeaturedState($listing_id, $new_state);

            if($res)
            {
                // Clear cache
                clearCache('', 'views');
                clearCache('', '__data');
                $result['success'] = true;
            }
        }

        return $result;
    }

    function publish($listing_id, $include_reject_state = false)
    {
        $result = array('success'=>false,'state'=>null,'access'=>true);

        $listing_id = (int) $listing_id;

        if(!$listing_id) return $result;

        # Load current listing publish state and author id

        $listing = $this->getListingById($listing_id);

        if($listing)
        {
            $user_id = $listing['Listing']['user_id'];

            $state = $listing['Listing']['state'];

            $overrides = $listing['ListingType']['config'];

            # Check access
            $Access = Configure::read('JreviewsSystem.Access');

            if(!$Access->canPublishListing($user_id, $overrides))
            {
                $result['access'] = false;

                return $result;
            }

            $data['Listing']['id'] = $listing_id;

            // Define toggle states
            if($include_reject_state) {

                if($state == 1) {

                    $data['Listing']['state'] = $result['state'] = 0;
                }
                elseif($state == 0) {

                    $data['Listing']['state'] = $result['state'] = -2;
                }
                elseif($state == -2) {

                    $data['Listing']['state'] = $result['state'] = 1;
                }
            }
            else {

                $data['Listing']['state'] = $result['state'] = (int)!$state;
            }

            // Set previous state in $data so it can be used in plugin events
            $data['state_prev'] = $listing['Listing']['state'];

            # Update listing state
            if($this->store($data,false,array('plgAfterSave')))
            {
                // clear cache
                clearCache('', 'views');
                clearCache('', '__data');

                $result['success'] = true;
            }
        }

        return $result;
    }

    /**
    * Gets the most basic listing info to construct the urls for them
    *
    * @param mixed $id
    */
    function getListingById($id)
    {
        $this->addStopAfterFindModel(array('Favorite','Media','Field','PaidOrder'));

        $listings = $this->findAll(array(
            'conditions'=>array('Listing.' . self::_LISTING_ID . ' IN (' . cleanIntegerCommaList($id) . ')')
        ));

        return is_array($id) ? $listings : array_shift($listings);
    }

    /***********************************************************
     *                      ADMIN                              *
     ***********************************************************/

    function adminBrowseModerationQueryData($filters, & $Access)
    {
        $conditions = array();

        if(!empty($filters))
        {
            $conditions = $this->adminBrowseFilters($filters, $Access);
        }

        unset($this->fields['User.email']);

        // Feb 1, 2016 - Reverted the condition for the email to show the User table email if available
        // because otherwise after a claim is approved the email shown is still that of the previous user

        $queryData = array(
                'fields'=>array(
                    'ViewLevel.title AS `Listing.view_level`',
                    'IF(User.' . UserModel::_USER_EMAIL.' <> "", User.' . UserModel::_USER_EMAIL . ', Field.email) AS `User.email`',
                    'Field.listing_note AS `Field.listing_note`',
                    'Field.ipaddress AS `Listing.ipaddress`'
                ),
                'conditions'=>$conditions,
                'joins'=>array(
                    "LEFT JOIN #__viewlevels AS ViewLevel ON ViewLevel.id = Listing.access"
                )
            );

        return $queryData;
    }

    function adminBrowseFilters($filters, & $Access)
    {
        $filter_authorid    = Sanitize::getInt($filters,'authorid');

        $filter_state        = Sanitize::getString($filters,'state');

        $title              = Sanitize::getString($filters,'title');

        $conditions        = array();

        $filter_authorid > 0 and $conditions[] = "Listing." . self::_LISTING_USER_ID . " = $filter_authorid";

        $title != '' and $conditions[] = "LOWER( Listing." . self::_LISTING_TITLE . " ) LIKE " . $this->QuoteLike($title);

        switch($filter_state)
        {
            case 'published':

                $conditions[] = "Listing.state = 1";

                break;

            case 'pending':
            case 'unpublished':

                $conditions[] = "Listing.state = 0";

                break;

            case 'expired':

                $conditions[] = '( Listing.publish_down != "' . NULL_DATE . '" AND Listing.publish_down < "' . _CURRENT_SERVER_TIME . '" )';

            break;

            case 'featured':

                $conditions[] = "Field.featured = 1";

                break;

            case 'media_count':

                $conditions[] = "Totals.media_count > 0";

                break;

            case 'rejected':

                $conditions[] = "Listing.state = -2";

                break;

            default:

                // $conditions[] = "Listing.state >= 0";

                break;
        }

        return $conditions;
    }

    function getModerationCount()
    {
        $count = $this->getStatusCount(0);

        return $count;
    }

    function getPublishedCount()
    {
        $count = $this->getStatusCount(1);

        return $count;
    }

    function getStatusCount($status)
    {
        $query = '
            SELECT
                count(*)
            FROM
                ' . self::_LISTING_TABLE . '
            WHERE
                state = '  . $status . '
                AND catid IN (
                    SELECT id FROM #__jreviews_categories WHERE `option` = "com_content"
                )
        ';

        $count = $this->query($query, 'loadResult');

        return $count;
    }

    /**
     * [findDuplicates description]
     * @param  [type] $method  [yes|no|category]
     * @param  [type] $filters [description]
     * @return [type]          [description]
     */
    function findDuplicates($method, $filters)
    {
        $listing_id = Sanitize::getInt($filters,'listing_id');

        $cat_id = Sanitize::getInt($filters,'cat_id');

        $slug = Sanitize::getString($filters,'slug');

        $title = Sanitize::getString($filters,'title');

        $conditions = array();

        if($title)
        {
            $conditions[] = 'Listing.'. self::_LISTING_TITLE . ' = ' . $this->Quote($title);
        }

        if($slug)
        {
            $conditions[] = 'Listing.'. self::_LISTING_SLUG . ' LIKE ' . $this->Quote($slug . '%');
        }

        switch($method)
        {
            // Checks for duplicates in the same category
            case 'category':

            // Allows duplicates, so we need to check if they exist and append a number to the listing alias
            case 'yes':

                $conditions[] = 'Listing.catid = ' . $cat_id;

                if($listing_id)
                {
                    $conditions[] = 'Listing.id <> ' . $listing_id;
                }

            break;

            // Checks for duplicates all over the place
            case 'no':

                if($listing_id)
                {
                    $conditions[] = 'Listing.id <> ' . $listing_id;
                }

            break;

            // Anything goes. Running duplicate checks is not necessary
            case 'yes_alias_duplication':

                return 0;

            break;

        }

        $duplicate_count = $this->findCount(array('session_cache'=>true,'conditions'=>$conditions));

        return $duplicate_count;
    }

    function findTitleById($id)
    {
        $query = '
            SELECT
                ' . self::_LISTING_TITLE . '
            FROM
                ' . self::_LISTING_TABLE . '
            WHERE
                ' . self::_LISTING_ID . ' = ' . (int) $id;

        $title = $this->query($query, 'loadResult');

        return $title;
    }

    function findIdByTitle($title)
    {
        $query = '
            SELECT
                ' . self::_LISTING_ID . '
            FROM
                ' . self::_LISTING_TABLE . '
            WHERE
                ' . self::_LISTING_TITLE . ' LIKE ' . $this->QuoteLike(trim($title));

        $id = $this->query($query,'loadColumn');

        return $id;
    }
}
