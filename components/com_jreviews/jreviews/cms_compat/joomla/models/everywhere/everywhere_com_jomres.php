<?php
/**
 * JReviews - Reviews Extension
 * Copyright (C) 2010-2015 ClickFWD LLC
 * This is not free software, do not distribute it.
 * For licencing information visit https://www.jreviews.com
 * or contact sales@jreviews.com
**/

defined( 'MVC_FRAMEWORK') or die( 'Direct Access to this location is not allowed.' );

class EverywhereComJomresModel extends MyModel  {

    const _LISTING_ID                   = 'propertys_uid';

	var $UI_name = 'Jomres';

	var $name = 'Listing';

	var $useTable = '#__jomres_propertys AS Listing';

	var $primaryKey = 'Listing.listing_id';

	var $realKey = 'propertys_uid';

	var $extension = 'com_jomres';

	var $listingUrl = 'index.php?option=com_jomres&amp;task=viewproperty&amp;property_uid=%s&amp;Itemid=%s';

	var $dateKey = 'cdate';

	var $fields = array(
		'Listing.propertys_uid AS `Listing.listing_id`',
		'Listing.property_name AS `Listing.title`',
		'"0000-00-00" AS `Listing.publish_up`',
		'"0000-00-00" AS `Listing.publish_up`',
		'Listing.published AS `Listing.state`',
		'Listing.ptype_id AS `Listing.cat_id`',
        'JreviewsCategory.id AS `Category.cat_id`',
		"'com_jomres' AS `Listing.extension`",
		'Category.ptype AS `Category.title`',
        'JreviewsCategory.criteriaid AS `Listing.listing_type_id`',
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
        'Totals'=>"LEFT JOIN #__jreviews_listing_totals AS Totals ON Totals.listing_id = Listing.propertys_uid AND Totals.extension = 'com_jomres'",
		'LEFT JOIN #__jomres_ptypes AS Category ON Listing.ptype_id = Category.id',
		"INNER JOIN #__jreviews_categories AS JreviewsCategory ON JreviewsCategory.id = Category.id AND JreviewsCategory.`option` = 'com_jomres'"
	);

	/**
	 * Used to complete the listing information for reviews based on the Review.pid
	 */
	var $joinsReviews = array(
		'LEFT JOIN #__jomres_propertys AS Listing ON Review.pid = Listing.propertys_uid',
		'LEFT JOIN #__jomres_ptypes AS Category ON Listing.ptype_id = Category.id',
		"INNER JOIN #__jreviews_categories AS JreviewsCategory ON JreviewsCategory.id = Category.id AND JreviewsCategory.`option` = 'com_jomres'"
	);

	var $joinsMedia = array(
		// 'LEFT JOIN #__jomres_propertys AS Listing ON Media.listing_id = Listing.propertys_uid',
		'LEFT JOIN #__jomres_ptypes AS Category ON Listing.ptype_id = Category.id',
		"INNER JOIN #__jreviews_categories AS JreviewsCategory ON JreviewsCategory.id = Category.id AND JreviewsCategory.`option` = 'com_jomres'"
	);

    public static $joinListingState = array(
        'INNER JOIN #__jomres_propertys AS Listing ON Listing.propertys_uid = %s AND Listing.published = 1'
        );

	function __construct() {

		parent::__construct();

		$this->tag = __t("JOMRES_TAG",true);  // Used in MyReviews page to differentiate from other component reviews

//		$this->fields[] = "'{$this->tag }' AS `Listing.tag`";
	}

	static public function exists() {

		return (bool) @ file_exists(PATH_ROOT . 'components' . _DS . 'com_jomres' . _DS . 'jomres.php');
	}

	function listingUrl($listing) {

		return sprintf($this->listingUrl,$listing['Listing']['listing_id'],$listing['Listing']['menu_id']);

	}

	function getImage($listing_id)
    {
        $property_image = WWW_ROOT . 'jomres' . _DS . 'images' . _DS . 'jrlogo.png';

        if (file_exists(PATH_ROOT . 'jomres' . DS . 'uploadedimages' . DS . $listing_id . DS . 'property' . DS . '0' . DS . $listing_id . '_property_' . $listing_id . '.jpg') )
        {
            return WWW_ROOT . 'jomres' . _DS . 'uploadedimages' . _DS . $listing_id . _DS . 'property' . _DS . '0' . _DS . $listing_id . '_property_' . $listing_id . '.jpg';
        }
        elseif (file_exists(PATH_ROOT . 'jomres' . DS . 'uploadedimages' . DS . $listing_id . '_property_' . $listing_id . '.jpg') )
        {
            return WWW_ROOT . 'jomres' . _DS . 'uploadedimages' . _DS . $listing_id . '_property_' . $listing_id . '.jpg';
        }

        return '';
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

			// Add slug

			// $results[$key]['Listing']['slug'] = S2Router::sefUrlEncode($first['slug'],'');

			// Process component menu id
			$results[$key][$this->name]['menu_id'] = $menu_id;
			$result['Listing']['menu_id'] = $menu_id;

			// Process listing url
			$results[$key][$this->name]['url'] = $this->listingUrl($result);

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

			if($images != '') {
				    $imagePath = $images;
			} else {
				// Put a noimage path here?
				$imagePath = '';//$images;
			}

			$results[$key]['Media'] = $results[$key]['MainMedia'] = array(); // Initialize the Media array

			$results[$key]['MainMedia']['title'] = $results[$key]['Listing']['title'];

			$results[$key]['MainMedia']['media_type'] = 'photo';

			$results[$key]['MainMedia']['everywhere'] = $imagePath;

			// Deprecated way to pass images to the results

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
	 * This can be used to add post review save actions, like synching with another table
	 */
	function afterSave($status) {}

	# ADMIN functions below
	function getNewCategories()
	{
		$query = "SELECT id FROM #__jreviews_categories WHERE `option` = '{$this->extension}'";

        $exclude = $this->query($query,'loadColumn');

        $exclude = $exclude ? implode(',',$exclude) : '';

		$query = "SELECT Component.id AS value,Component.ptype as text"
		. "\n FROM #__jomres_ptypes AS Component"
		. "\n LEFT JOIN #__jreviews_categories AS JreviewCategory ON Component.id = JreviewCategory.id AND JreviewCategory.`option` = '{$this->extension}'"
		. ($exclude != '' ? "\n WHERE Component.id NOT IN ($exclude)" : '')
		. "\n ORDER BY Component.ptype ASC"
		;

        return $this->query($query,'loadAssocList');
	}

	function getUsedCategories()
	{
		$query = "SELECT Component.id AS `Component.cat_id`,Component.ptype as `Component.cat_title`, Criteria.title AS `Component.criteria_title`"
		. "\n FROM #__jomres_ptypes AS Component"
		. "\n INNER JOIN #__jreviews_categories AS JreviewCategory ON Component.id = JreviewCategory.id AND JreviewCategory.`option` = '{$this->extension}'"
		. "\n LEFT JOIN #__jreviews_criteria AS Criteria ON JreviewCategory.criteriaid = Criteria.id"
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

}