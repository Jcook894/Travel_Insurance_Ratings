<?php
/**
 * JReviews - Reviews Extension
 * Copyright (C) 2010-2015 ClickFWD LLC
 * This is not free software, do not distribute it.
 * For licencing information visit https://www.jreviews.com
 * or contact sales@jreviews.com
**/

defined( 'MVC_FRAMEWORK') or die( 'Direct Access to this location is not allowed.' );

class CriteriaRatingModel extends MyModel  {

	var $name = 'CriteriaRating';

	var $useTable = '#__jreviews_criteria_ratings AS CriteriaRating';

	var $primaryKey = 'CriteriaRating.criteria_id';

	var $realKey = 'criteria_id';

	var $fields = array(
		'CriteriaRating.criteria_id AS `CriteriaRating.criteria_id`',
		'CriteriaRating.title AS `CriteriaRating.title`',
		'CriteriaRating.required AS `CriteriaRating.required`',
		'CriteriaRating.weight AS `CriteriaRating.weight`',
		'CriteriaRating.description AS `CriteriaRating.description`',
        'CriteriaRating.listing_type_id AS `CriteriaRating.listing_type_id`',
        'CriteriaRating.ordering AS `CriteriaRating.ordering`',
        'ListingType.state AS `CriteriaRating.listing_type_state`'
	);

	var $joins = array(
		'LEFT JOIN #__jreviews_criteria AS ListingType ON ListingType.id = CriteriaRating.listing_type_id'
		);

	function addCriteriaRatings($results)
	{
		// Get all criteria for the current listing types

		$listing_type_ids = array_keys($results);

		if(empty($listing_type_ids)) return $results;

		$criteria = $this->findAll(array(
			'conditions'=>array('CriteriaRating.listing_type_id IN (' . $this->Quote($listing_type_ids) . ')'),
			'order'=>array('CriteriaRating.ordering')
			));

		foreach($criteria AS $criteria_id=>$criterion)
		{
			extract($criterion['CriteriaRating']);

			foreach($criterion['CriteriaRating'] AS $key=>$val)
			{
				$results[$listing_type_id]['CriteriaRating'][$criteria_id]['CriteriaRating'][$key] = $val;
			}

			// For backwards compatibility we also re-create the old format, but include the criteria id as keys

			$results[$listing_type_id]['Criteria']['criteria'][$criteria_id] = $criterion['CriteriaRating']['title'];

			$results[$listing_type_id]['Criteria']['tooltips'][$criteria_id] = $criterion['CriteriaRating']['description'];

			$results[$listing_type_id]['Criteria']['required'][$criteria_id] = $criterion['CriteriaRating']['required'];

			$results[$listing_type_id]['Criteria']['weights'][$criteria_id] = $criterion['CriteriaRating']['weight'];
		}

		return $results;
	}

	function addCriteriaRatingsCategory($results)
	{
		// Get the listing type ids into an array

		$listing_type_ids = array();

		foreach($results AS $key=>$result)
		{
			if(!$result['Criteria']['listing_type_id']) continue;

			$listing_type_ids[$result['Criteria']['listing_type_id']] = $result['Criteria']['listing_type_id'];
		}

		if(empty($listing_type_ids)) return $results;

		$criteria = $this->findAll(array(
			'conditions'=>array('CriteriaRating.listing_type_id IN (' . $this->Quote($listing_type_ids) . ')'),
			'order'=>array('CriteriaRating.ordering')
			));

		// Add the Criteria Rating info to the category array

		foreach($results AS $key=>$result)
		{
			foreach($criteria AS $criteria_id=>$criterion)
			{
				// For backwards compatibility we also re-create the old format, but include the criteria id as keys

				if($criterion['CriteriaRating']['listing_type_id'] == $result['Criteria']['listing_type_id'])
				{
					$results[$key]['Criteria']['criteria'][$criteria_id] = $criterion['CriteriaRating']['title'];

					$results[$key]['Criteria']['tooltips'][$criteria_id] = $criterion['CriteriaRating']['description'];

					$results[$key]['Criteria']['required'][$criteria_id] = $criterion['CriteriaRating']['required'];

					$results[$key]['Criteria']['weights'][$criteria_id] = $criterion['CriteriaRating']['weight'];
				}
			}
		}

		return $results;
	}

	function getMaxOrder($listing_type_id)
	{
		$query = "SELECT MAX(ordering) FROM #__jreviews_criteria_ratings WHERE listing_type_id = " . (int) $listing_type_id;

		return $this->query($query,'loadResult');
	}

    function afterDelete($key, $values, $condition)
    {
    	S2App::import('Model','review','jreviews');

    	$ReviewModel = ClassRegistry::getClass('ReviewModel');

        return $ReviewModel->updateRatingAverages();
    }

    function afterReorder($ids, $extras = array())
    {
    	S2App::import('Model','review','jreviews');

    	$ReviewModel = ClassRegistry::getClass('ReviewModel');

        return $ReviewModel->updateListingTotalAverages();
    }

    function afterSave($status)
    {
    	$criteria_id = Sanitize::getInt($this->data['CriteriaRating'],'criteria_id');

    	$listing_type_id = Sanitize::getInt($this->data['CriteriaRating'],'listing_type_id');

		$query = "
            INSERT IGNORE INTO #__jreviews_review_ratings (
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
					" . $criteria_id . " AS criteria_id,
					0 AS rating
                FROM
                    #__jreviews_review_ratings AS ReviewRating
                INNER JOIN
                    #__jreviews_comments AS Review ON Review.id = ReviewRating.review_id
				WHERE Review.listing_type_id = " . $listing_type_id
		;

		$this->query($query);
    }
}