<?php
/**
 * JReviews - Reviews Extension
 * Copyright (C) 2010-2015 ClickFWD LLC
 * This is not free software, do not distribute it.
 * For licencing information visit https://www.jreviews.com
 * or contact sales@jreviews.com
**/

defined( 'MVC_FRAMEWORK') or die( 'Direct Access to this location is not allowed.' );

class EverywhereComK2Model extends MyModel  {

    const _LISTING_ID                   = 'id';

	var $UI_name = 'K2';

	var $name = 'Listing';

	var $useTable = '#__k2_items AS Listing';

	var $primaryKey = 'Listing.listing_id';

	var $realKey = 'id';

	/**
	 * Used for listing module - latest listings ordering
	 */
	var $dateKey = 'created';

	var $extension = 'com_k2';

	var $listingUrl = 'index.php?option=com_k2&view=item&id=%s:%s&Itemid=%s';

	var $cat_url_param = 'cat_id';

	var $fields = array(
		'Listing.id AS `Listing.listing_id`',
		'Listing.title AS `Listing.title`',
        'Listing.alias AS `Listing.slug`',
        'Listing.published AS `Listing.state`',
		"'com_k2' AS `Listing.extension`",
		'JreviewsCategory.id AS `Listing.cat_id`',
        'Category.id AS `Category.cat_id`',
		'Category.name AS `Category.title`',
        'Category.alias AS `Category.slug`',
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
        'Totals'=>"LEFT JOIN #__jreviews_listing_totals AS Totals ON Totals.listing_id = Listing.id AND Totals.extension = 'com_k2'",
		"LEFT JOIN #__k2_categories AS Category ON Listing.catid = Category.id",
		"INNER JOIN #__jreviews_categories AS JreviewsCategory ON Category.id = JreviewsCategory.id AND JreviewsCategory.`option` = 'com_k2'",
        "LEFT JOIN #__users AS User ON User.id = Listing.created_by"
	);

	/**
	 * Used to complete the listing information for reviews based on the Review.pid
	 */
	var $joinsReviews = array(
        'LEFT JOIN #__k2_items AS Listing ON Review.pid = Listing.id',
        'LEFT JOIN #__k2_categories AS Category ON Listing.catid = Category.id',
        "INNER JOIN #__jreviews_categories AS JreviewsCategory ON Category.id = JreviewsCategory.id AND JreviewsCategory.`option` = 'com_k2'"
	);

	var $joinsMedia = array(
        // 'LEFT JOIN #__k2_items AS Listing ON Media.listing_id = Listing.id',
        'LEFT JOIN #__k2_categories AS Category ON Listing.catid = Category.id',
        "INNER JOIN #__jreviews_categories AS JreviewsCategory ON Category.id = JreviewsCategory.id AND JreviewsCategory.`option` = 'com_k2'"
	);

    public static $joinListingState = array(
        'INNER JOIN #__k2_items AS Listing ON Listing.id = %s AND Listing.published = 1'
        );

	function __construct() {

		parent::__construct();

		$this->tag = __t("K2_TAG",true);  // Used in MyReviews page to differentiate from other component reviews

//		$this->fields[] = "'{$this->tag }' AS `Listing.tag`";
	}

	static public function exists() {

		return (bool) @ file_exists(PATH_ROOT . 'components' . _DS . 'com_k2' . _DS . 'k2.php');
	}


	function listingUrl($listing)
    {
		return sprintf($this->listingUrl,$listing['Listing']['listing_id'],$listing['Listing']['slug'],$listing['Listing']['menu_id']);
	}

    function getImage($listing_id)
    {
        $image = md5('Image'.$listing_id);
        if (@file_exists('media'.DS.'k2'.DS.'items'.DS.'cache'.DS . $image . '_S.jpg')) {
            return $image.'_S.jpg';
        }
        elseif(@file_exists('media'.DS.'k2'.DS.'items'.DS.'cache'.DS . $image . '_L.jpg')){
            return $image.'_L.jpg';
        }
        return false;
    }

    // Used to check whether reviews can be posted by listing owners, owner replies
    function getListingOwner($result_id)
    {
        $query = "
            SELECT
                Listing.created_by AS user_id, User.name, User.email
            FROM
                #__k2_items AS Listing
            LEFT JOIN
                #__users AS User ON Listing.created_by = User.id
            WHERE
                Listing.{$this->realKey} = " . (int) ($result_id);

        return $this->query($query,'loadAssoc');
    }

	function afterFind($results) {

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

		$menu_id = $Menu->getComponentMenuId($this->extension);

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
			$images = $this->getImage($result['Listing']['listing_id']);

            $results[$key]['Listing']['images'] = array();

			if($images)
            {
			    $imagePath = WWW_ROOT . 'media/k2/items/cache/' . $images;
			}
            else {

            	// Put a noimage path here?
				$imagePath = '';
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

		$query =
        "SELECT Component.id AS value,"
		. "\n Component.name AS `text`"
        . "\n FROM #__k2_categories AS Component"
		. "\n LEFT JOIN #__jreviews_categories AS JreviewCategory ON Component.id = JreviewCategory.id AND JreviewCategory.`option` = '{$this->extension}'"
		. ($exclude != '' ? "\n WHERE Component.id NOT IN ($exclude)" : '')
		. "\n ORDER BY Component.parent, Component.ordering"
		;

        return $this->query($query,'loadAssocList');
	}

	function getUsedCategories()
	{
		$query = "SELECT Component.id AS `Component.cat_id`,"
        . "\n Component.name AS `Component.cat_title`,"
        . "\n Criteria.title AS `Component.criteria_title`"
		. "\n FROM #__k2_categories AS Component"
		. "\n INNER JOIN #__jreviews_categories AS JreviewCategory ON Component.id = JreviewCategory.id AND JreviewCategory.`option` = '{$this->extension}'"
		. "\n LEFT JOIN #__jreviews_criteria AS Criteria ON JreviewCategory.criteriaid = Criteria.id"
        . "\n ORDER BY Component.parent, Component.ordering"
		. "\n LIMIT $this->offset,$this->limit"
		;

		$results = $this->query($query,'loadObjectList');
        $results = $this->__reformatArray($results);
		$results = $this->changeKeys($results,'Component','cat_id');

		$query = "SELECT count(JreviewCategory.id)"
		. "\n FROM #__jreviews_categories AS JreviewCategory"
		. "\n WHERE JreviewCategory.`option` = '{$this->extension}'"
		;

		$count = $this->query($query,'loadResult');

		return array('rows'=>$results,'count'=>$count);
	}

    function makeTree($results)
    {
        $refs = array();
        $list = array();

        foreach($results as $data)
        {
            $thisref = &$refs[ $data['value'] ];
            $thisref['parent'] = $data['parent'];
            $thisref['text'] = $data['text'];
            $thisref['value'] = $data['value'];

            if ($data['parent'] == 0) {
                $list[ $data['value'] ] = &$thisref;
            } else {
                $refs[ $data['parent'] ]['children'][ $data['value'] ] = &$thisref;
            }
        }

        foreach($refs AS $key=>$ref)
        {
            if(isset($ref['parent']) && $ref['parent']!=0) unset($refs[$key]);
        }
        $tree = array();
        $this->indentChildren($refs,$tree);
        return $tree;
    }

    function indentChildren($arr,&$results,$indent='')
    {
        foreach ($arr as $key=>$v)
        {
            if(isset($v['parent']) && $v['parent']==0) $indent = '';
            $results[$v['value']] = $v;
            $results[$v['value']]['text'] = $indent.$v['text'];
            if (array_key_exists('children', $v)) {
                $indent = '--' . $indent;
                $this->indentChildren($v['children'],$results,$indent);
            }
        }
    }
}