<?php
/**
 * JReviews - Reviews Extension
 * Copyright (C) 2010-2015 ClickFWD LLC
 * This is not free software, do not distribute it.
 * For licencing information visit https://www.jreviews.com
 * or contact sales@jreviews.com
**/

defined( 'MVC_FRAMEWORK') or die( 'Direct Access to this location is not allowed.' );

class EverywhereComMtreeModel extends MyModel  {

    const _LISTING_ID                   = 'link_id';

    const _CATEGORY_ID					= 'cat_id';

    const _CATEGORY_TITLE               = 'cat_name';

	var $UI_name = 'Mosets Tree';

	var $name = 'Listing';

	var $useTable = '#__mt_links AS Listing';

	var $primaryKey = 'Listing.listing_id';

	var $realKey = 'link_id';

	/**
	 * Used for listing module - latest listings ordering
	 */
	var $dateKey = 'publish_up';

	var $extension = 'com_mtree';

	var $extension_alias = 'com_mtree';

	var $listingUrl = 'index.php?option=com_mtree&amp;task=viewlink&amp;link_id=%s&amp;Itemid=%s';

	var $cat_url_param = 'cat_id';

	var $categoryPrimaryKey = 'cat_id';

	var $fields = array(
		'Listing.link_id AS `Listing.listing_id`',
		'Listing.link_name AS `Listing.title`',
		'Listing.user_id AS `Listing.user_id`',
		'"0000-00-00" AS `Listing.publish_up`',
//		'Images.filename AS `Listing.images`',
		"'com_mtree' AS `Listing.extension`",
		'JreviewsCategory.id AS `Listing.cat_id`',
		'Category.cat_name AS `Category.title`',
		'ExtensionCategory.cat_id AS `Category.cat_id`',
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

	/**
	 * Used for detail listing page - not used for 3rd party components
	 */
	var $joins = array(
        'Total'=>"LEFT JOIN #__jreviews_listing_totals AS Totals ON Totals.listing_id = Listing.link_id AND Totals.extension = 'com_mtree'",
		"LEFT JOIN #__mt_cl AS ExtensionCategory ON Listing.link_id = ExtensionCategory.link_id",
		"INNER JOIN #__jreviews_categories AS JreviewsCategory ON ExtensionCategory.cat_id = JreviewsCategory.id AND JreviewsCategory.`option` = 'com_mtree'",
		'LEFT JOIN #__mt_cats AS Category ON JreviewsCategory.id = Category.cat_id',
		'LEFT JOIN #__jreviews_criteria AS Criteria ON JreviewsCategory.criteriaid = Criteria.id',
        "LEFT JOIN #__users AS User ON User.id = Listing.user_id"
	);

	/**
	 * Used to complete the listing information for reviews based on the Review.pid
	 */
	var $joinsReviews = array(
		"LEFT JOIN #__mt_cl AS ExtensionCategory ON Review.pid = ExtensionCategory.link_id",
		"INNER JOIN #__jreviews_categories AS JreviewsCategory ON ExtensionCategory.cat_id = JreviewsCategory.id AND JreviewsCategory.`option` = 'com_mtree'",
		'LEFT JOIN #__mt_cats AS Category ON JreviewsCategory.id = Category.cat_id',
		'LEFT JOIN #__jreviews_criteria AS Criteria ON JreviewsCategory.criteriaid = Criteria.id'
	);

    public static $joinListingState = array(
        'INNER JOIN #__mt_links AS Listing ON Listing.link_id = %s AND Listing.link_published = 1'
        );

	function __construct() {
		parent::__construct();

		$this->tag = __t("MTREE_TAG",true);  // Used in MyReviews page to differentiate from other component reviews

		$this->fields[] = "'{$this->tag }' AS `Listing.tag`";
	}

	static public function exists() {
		return (bool) @ file_exists(PATH_ROOT . 'components' . _DS . 'com_mtree' . _DS . 'mtree.php');
	}


	function listingUrl($listing)
    {
		return sprintf($this->listingUrl,$listing['Listing']['listing_id'],$listing['Listing']['menu_id']);
	}

    // Used to check whether reviews can be posted by listing owners, owner replies
    function getListingOwner($result_id)
    {
        $query = "
            SELECT
                Listing.user_id AS user_id, User.name, User.email
            FROM
                #__mt_links AS Listing
            LEFT JOIN
                #__users AS User ON Listing.user_id = User.id
            WHERE
                Listing.link_id = " . (int) ($result_id);

        return current($this->query($query, 'loadAssocList'));
    }

	function getImage($listing_id)
	{
		$query = "SELECT Image.filename AS image FROM #__mt_images AS Image WHERE Image.link_id = " . $listing_id . " LIMIT 1";

		return $this->query($query, 'loadResult');
	}

	function afterFind($results)
	{

        if (empty($results))
        {
            return $results;
        }

        # Add listing type and criteria rating info to results array

        S2App::import('Model',array('menu','criteria'),'jreviews');

        $ListingTypes = ClassRegistry::getClass('CriteriaModel');

        $results = $ListingTypes->addListingTypes($results,'Listing');

        $controller =  Sanitize::getString($this,'controller_name');

        $action =  Sanitize::getString($this,'controller_action');

		$Menu = ClassRegistry::getClass('MenuModel');

		$menu_id = $Menu->getComponentMenuId($this->extension);

		foreach($results AS $key=>$result)
		{
			$results[$key][$this->name]['menu_id'] = $menu_id;

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

                    $values = array_slice(array_values($ratings), 0, count($result['CriteriaRating']));

                    $ratings = array_combine(array_keys($result['CriteriaRating']), $values);

                    $ratings_count = explode(',', $result['Review']['user_criteria_rating_count']);

                    $ratings_count_values = array_slice(array_values($ratings_count), 0, count($result['CriteriaRating']));

                    $ratings_count = array_combine(array_keys($result['CriteriaRating']), $ratings_count_values);

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

			// Process images
			$images = $this->getImage($result['Listing']['listing_id']);

			$results[$key]['Listing']['images'] = array();

			if($images != '') {
				if ( @file_exists("components/com_mtree/img/listings/o/" . $images) ) {
				    $imagePath = "components/com_mtree/img/listings/o/" . $images;
				} else {
				    $imagePath = "components/com_mtree/img/listings/o/" . $images;
				}
			} else {
				// Put a noimage path here?
				$imagePath = '';//"components/com_mtree/img/listings/o/" . $images;
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
	 * Returns the current page category for category auto-detect functionality in modules
	 */
	function catUrlParam(){
		return $this->cat_url_param;
	}

	# ADMIN functions below
	function getNewCategories()
	{
		$query = "SELECT id FROM #__jreviews_categories WHERE `option` = '{$this->extension}'";

        $exclude = $this->query($query,'loadColumn');

        $exclude = $exclude ? implode(',',$exclude) : '';

		// select b.cat_id,count(a.cat_id) as depth
		// from tkm7_mt_cats a
		// join tkm7_mt_cats b on b.lft between a.lft and a.rgt
		// group by b.cat_id;
		//
		$query = "
			SELECT
				Parent." . self::_CATEGORY_ID . " AS value,
				Parent." . self::_CATEGORY_TITLE . " as text,
                count(Component." . self::_CATEGORY_ID . ") AS depth
			FROM
				#__mt_cats AS Component
		    JOIN
                #__mt_cats AS Parent ON Parent.lft BETWEEN Component.lft and Component.rgt
			LEFT JOIN
				#__jreviews_categories AS JreviewCategory ON Parent.cat_id = JreviewCategory.id AND JreviewCategory.`option` = '{$this->extension}'
			" . ($exclude != '' ? "
	                WHERE
	                    Parent." . self::_CATEGORY_ID . " NOT IN ($exclude)" : ''
	        ) . "
            GROUP BY
            	Parent." . self::_CATEGORY_ID . "
            ORDER BY
				Parent.lft
		";

		$results = $this->query($query, 'loadAssocList');

		foreach($results AS $key => $result) {
			$results[$key]['text'] = str_repeat('--|', $result['depth'] - 1) . ' ' . $result['text'];
		}

		return $results;
	}

	function getUsedCategories()
	{
		$query = "
            SELECT
                Parent." . self::_CATEGORY_ID ." AS `Component.cat_id`,
                Parent." . self::_CATEGORY_TITLE . " as `Component.cat_title`,
                count(Component." . self::_CATEGORY_ID . ") AS `Component.depth`,
                Criteria.title AS `Component.criteria_title`
		    FROM
                #__mt_cats AS Component
		    JOIN
                #__mt_cats AS Parent ON Parent.lft BETWEEN Component.lft and Component.rgt
		    INNER JOIN
                #__jreviews_categories AS JreviewCategory ON Parent." . self::_CATEGORY_ID ." = JreviewCategory.id AND JreviewCategory.`option` = '{$this->extension_alias}'
		    LEFT
                JOIN #__jreviews_criteria AS Criteria ON JreviewCategory.criteriaid = Criteria.id
            GROUP BY
            	Parent." . self::_CATEGORY_ID . "
		    LIMIT
                {$this->offset},{$this->limit}
        ";

		$results = $this->query($query,'loadObjectList');

		$results = $this->__reformatArray($results);

		$results = $this->changeKeys($results,'Component','cat_id');

		foreach($results AS $key => $result)
		{
			$results[$key]['Component']['cat_title'] = str_repeat('--|', $result['Component']['depth'] - 1) . ' ' . $result['Component']['cat_title'];
		}

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