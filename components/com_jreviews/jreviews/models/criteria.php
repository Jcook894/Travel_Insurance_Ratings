<?php
/**
 * JReviews - Reviews Extension
 * Copyright (C) 2010-2015 ClickFWD LLC
 * This is not free software, do not distribute it.
 * For licencing information visit https://www.jreviews.com
 * or contact sales@jreviews.com
**/

defined( 'MVC_FRAMEWORK') or die( 'Direct Access to this location is not allowed.' );

class CriteriaModel extends MyModel  {

	var $name = 'Criteria';

	var $useTable = '#__jreviews_criteria AS Criteria';

	var $primaryKey = 'Criteria.criteria_id';

	var $realKey = 'id';

	var $fields = array(
		'Criteria.id AS `Criteria.criteria_id`',
		'Criteria.title AS `Criteria.title`',
		'Criteria.groupid AS `Criteria.group_id`',
		'Criteria.state AS `Criteria.state`',
		'Criteria.search AS `Criteria.search`',
        'Criteria.config AS `ListingType.config`'  # Configuration overrides
	);

	function getList()
	{
		$query = "SELECT * from #__jreviews_criteria order by title ASC";

		$rows = $this->query($query,'loadObjectList');

		return $rows;
	}

	function getSelectList($options = array())
	{
		$criteria_id = Sanitize::getVar($options,'id');

		$searchOnly = Sanitize::getBool($options,'searchOnly',false);

		$withFieldGroupsOnly = Sanitize::getBool($options,'withFieldGroupsOnly',false);

		$query = "
			SELECT
				id AS value, title AS text
			FROM
				#__jreviews_criteria
			WHERE 1 = 1
		". ($criteria_id ? " AND id IN (" . cleanIntegerCommaList($criteria_id) . ")" : '') ."
		". ($searchOnly ? " AND search = 1" : '') . "
		". ($withFieldGroupsOnly ? " AND groupid <> ''" : '') . "
			ORDER BY
				title ASC
		";

		$results = $this->query($query,'loadObjectList');

		return $results;
	}

	/**
	 * Returns criteria set
	 *
	 * @param array $data has extension, cat_id or criteria_id keys=>values
	 */
	function getCriteria($data)
    {
    	$extension = Sanitize::getString($data, 'extension', 'com_content');

		if(isset($data['criteria_id']))
		{
			$conditions = array('Criteria.id = ' . Sanitize::getInt($data,'criteria_id'));

			$joins = array();
		}
		elseif(isset($data['cat_id'])) {

			$conditions = array('JreviewCategory.id = ' . Sanitize::getInt($data,'cat_id'));

			$joins = array("INNER JOIN #__jreviews_categories AS JreviewCategory ON Criteria.id = JreviewCategory.criteriaid AND JreviewCategory.`option` = '{$extension}'");
		}

		$queryData = array('conditions'=>$conditions,'joins'=>$joins);

		$results = $this->findRow($queryData);

		return $results;
	}

	function addListingTypes($results, $model_name)
	{
		$listing_type_ids = array();

		foreach($results AS $key=>$result)
		{
			if(isset($result[$model_name]['listing_type_id']))
			{
				$listing_type_ids[$result[$model_name]['listing_type_id']] = $result[$model_name]['listing_type_id'];
			}

			$results[$key]['CriteriaRating'] = array();
		}

		if($listing_type_ids)
		{
			$listingTypes = $this->findAll(array('conditions'=>array('Criteria.id IN (' . $this->Quote($listing_type_ids) . ')')));

			if($listingTypes)
			{
				foreach($results AS $key=>$result)
				{
					if(isset($listingTypes[$result[$model_name]['listing_type_id']]))
					{
						$results[$key] = array_merge($result, $listingTypes[$result[$model_name]['listing_type_id']]);
					}
				}
			}
		}

		return $results;
	}

    function afterFind($results)
    {
    	S2App::import('Model','criteria_rating','jreviews');

    	$CriteriaRating = ClassRegistry::getClass('CriteriaRatingModel');

    	$results = $CriteriaRating->addCriteriaRatings($results);

    	$listingTypes = array();

        foreach($results AS $key=>$result)
        {
        	$id = Sanitize::getInt($result['Criteria'],'criteria_id');

        	if($id > 0 && !isset($listingTypes[$id]) && isset($result['ListingType']['config']))
        	{
        		$listingTypes[$id] = json_decode($result['ListingType']['config'],true);
        	}

			$results[$key]['ListingType']['config'] = Sanitize::getVar($listingTypes,$id,array());
        }

        return $results;
    }

    function afterSave($ret)
    {
        clearCache('','__data');

        clearCache('','views');
    }
}