<?php
/**
 * JReviews - Reviews Extension
 * Copyright (C) 2010-2015 ClickFWD LLC
 * This is not free software, do not distribute it.
 * For licencing information visit https://www.jreviews.com
 * or contact sales@jreviews.com
**/
// no direct access
defined( 'MVC_FRAMEWORK') or die( 'Direct Access to this location is not allowed.' );

class ListingTypesController extends MyController {

	var $uses = array('acl','criteria','criteria_rating','review','listing_total');

	var $helpers = array('html','form','jreviews','admin/admin_criterias','admin/admin_settings');

    var $components = array('access','config');

	var $autoRender = false;

	var $autoLayout = false;

    var $__listings = array();

	function beforeFilter()
    {
		# Call beforeFilter of MyAdminController parent class
		parent::beforeFilter();
	}

	function index()
    {
    	$conditions = array();

        if ($filterTitle = Sanitize::getString($this->params,'filter_title'))
        {
            $conditions[] = 'title LIKE ' . $this->QuoteLike($filterTitle);
        }

		$rows = $this->Criteria->findAll(array('conditions'=>$conditions,'order'=>array('Criteria.title')));

	 	$table = $this->listViewTable($rows);

		$this->set(array('table'=>$table));

		return $this->render();
	}

	function listViewTable($rows)
    {
		foreach($rows AS $key=>$row) {

			$groupList = '';

			$rows[$key]['Criteria']['field_groups'] = $this->getGroupListFromIds($row['Criteria']['group_id']);
		}

		$this->set(array(
			'rows'=>$rows
		));

		return $this->render('listing_types','table');

	}

	function getGroupListFromIds($ids)
	{
		$groupList = '';

		if ($ids != '')
		{
			$groups = explode (",", $ids);

			foreach ($groups as $group) {

				$query = "
					SELECT
						CONCAT(name,' (',IF(type=\"content\",\"listing\",type),')') AS `group`
					FROM
						#__jreviews_groups
					WHERE groupid = $group
				";

				$result = $this->Criteria->query($query, 'loadResult');

				if($result != '') {
					$groupList .= "<li>$result</li>";
				}
			}

			$groupList = "<ul>$groupList</ul>";
		}

		return $groupList;
	}

	function edit()
    {
		$this->name = 'listing_types';

		$this->action = 'edit';

		$this->autoRender = false;

		$criteriaid =  (int) Sanitize::getInt($this->params,'id');

		$reviews = '';

		if($criteriaid)
        {
			$criteria = $this->Criteria->findRow(array('conditions'=>array('id = ' . $criteriaid)));
		}
		else {

			$criteria = $this->Criteria->emptyModel();

			$criteria['Criteria']['state'] = 1;

			$criteria['Criteria']['group_id'] = '';

			$criteria['ListingType'] = array('config'=>array('relatedlistings'=>array(),'relatedreviews'=>array(),'userfavorites'=>array()));

			$criteria['CriteriaRating'] = array();
		}

		// create custom field groups select list
		$query = "
            SELECT
                groupid AS value,
                CONCAT(name,' - ',UPPER(IF(type=\"content\",\"listing\",type))) AS text
            FROM
                `#__jreviews_groups`
            ORDER BY
                type, name
            ";

		$groups = $this->Criteria->query($query,'loadObjectList');

		$this->set(
			array(
				'listingType'=>$criteria,
				'groups'=>$groups,
                'accessGroups' => $this->Acl->getAccessGroupList(),
				'accessLevels' => $this->Acl->getAccessLevelList(),
                'listingTypesList'=>$this->Criteria->getSelectList()
			)
		);

		return $this->render();
	}

	function update()
	{
		$id = Sanitize::getInt($this->params,'id');

		$row = $this->Criteria->findRow(array('conditions'=>array('Criteria.id = ' . $id)));

		// Process columns that are not just plain text
		S2App::import('Helper','admin/admin_criterias','jreviews');

		$AdminCriterias = ClassRegistry::getClass('AdminCriteriasHelper');

		$row['Criteria']['criteria'] = $AdminCriterias->createListFromString(Sanitize::getVar($row['Criteria'],'criteria'));

		$row['Criteria']['field_groups'] = $this->getGroupListFromIds(Sanitize::getVar($row['Criteria'],'group_id'));

		return cmsFramework::jsonResponse($row);
	}

	function _save()
    {
		$this->action = 'index';

		$criteriaid = $this->data['Criteria']['id'];

		$isNew = !$criteriaid ? true : false;

		$reviews = array();

		$response = array('success'=>false,'str'=>array());

        $apply = Sanitize::getBool($this->params,'apply',false);

        $criteriaRatings = Sanitize::getVar($this->data,'CriteriaRating',array());

		// Begin basic validation
		if ($this->data['Criteria']['title'] == '') {

			$response['str'][] = 'LISTING_TYPE_VALIDATE_TITLE';
		}

		$weights = 0;

        foreach($criteriaRatings AS $criteria)
        {
			if ($this->data['Criteria']['state'] == 1 )
			{
				if($criteria['CriteriaRating']['title'] == '') {

					$response['str']['LISTING_TYPE_VALIDATE_CRITERIA'] = 'LISTING_TYPE_VALIDATE_CRITERIA';
				}

				$weights += $criteria['CriteriaRating']['weight'];
			}
        }

		if($weights > 0 && $weights != 100)
		{
			$response['str'][] = 'LISTING_TYPE_VALIDATE_WEIGHTS';
		}

        # Configuration overrides - save as json object

        // Pre-process access overrides first

        $keys = array_keys($this->data['Criteria']['config']);

		$settings = array_keys($this->data['Criteria']['config']);

		$access_settings = $this->Access->__settings_overrides;

		foreach($access_settings AS $setting)
		{
			if(!in_array($setting, $settings))
			{
				$this->data['Criteria']['config'][$setting] = '';
			}
		}

		$this->data['Criteria']['config']['social_sharing_detail'] = Sanitize::getVar($this->data['Criteria']['config'],'social_sharing_detail',array());

        $this->data['Criteria']['config'] = json_encode(Sanitize::getVar($this->data['Criteria'],'config'));

		if($this->data['Criteria']['state'] != 1)
		{
			// if input invalid default to 0

			if(!in_array( $this->data['Criteria']['state'], array(0,2) ) )
			{
				$this->data['Criteria']['state'] = 0;
			}
		}

		if (!empty($response['str']))
        {
        	$response['str'] = array_values($response['str']);

            return cmsFramework::jsonResponse($response);
		}

		foreach($criteriaRatings AS $criteria)
		{
        	$this->CriteriaRating->store($criteria);
		}

        // Convert groupid array to list
        if(isset($this->data['Criteria']['groupid'][0]) && is_array($this->data['Criteria']['groupid'][0])) {

            $this->data['Criteria']['groupid'] = implode(',',$this->data['Criteria']['groupid'][0]);
        }
        elseif(isset($this->data['Criteria']['groupid']) && is_array($this->data['Criteria']['groupid'])) {

            $this->data['Criteria']['groupid'] = implode(',',$this->data['Criteria']['groupid']);
        }
        else {

            $this->data['Criteria']['groupid'] = '';
        }

        if($this->Criteria->store($this->data))
        {
			clearCache('', 'core');
        }

        $response['success'] = true;

        if($apply) {

            return cmsFramework::jsonResponse($response);
        }
        elseif($isNew) {

        	$response['isNew'] = $isNew;
        }

        $response['state'] = $this->data['Criteria']['state'];

        $response['id'] = $this->data['Criteria']['id'];

		$response['html'] = $this->index();

        return cmsFramework::jsonResponse($response);
	}

	function _delete()
    {
        $response = array('success'=>false,'str'=>array());

		$ids = Sanitize::getVar($this->params,'cid');

		if(empty($ids)) {

 			return cmsFramework::jsonResponse($response);
		}

		// Check if the criteria is being used by a category
		$query = '
			SELECT
				id, `option` AS extension
			FROM
				#__jreviews_categories
			WHERE
				criteriaid IN (' . cleanIntegerCommaList($ids) . ')'
		;

		$categories = $this->Review->query($query,'loadAssocList');

		$count = count($categories);

		if ($count) {

			$response['str'][] = 'LISTING_TYPE_REMOVE_NOT_EMPTY';

        	return cmsFramework::jsonResponse($response);
		}

		// Delete the listing type
		$this->Criteria->delete('id', $ids);

		// Now process dependencies

		// Clear cache
		clearCache('', 'views');

		clearCache('', '__data');

		$response['success'] = true;

		return cmsFramework::jsonResponse($response);
	}

	function _copy()
    {
        $response = array('success'=>false,'str'=>array());

        $copies = Sanitize::getInt($this->params,'copies',1);

        $criteriaid = Sanitize::getInt($this->params,'id');

		if (!$criteriaid){

			$response['str'][] = 'LISTING_TYPE_VALIDATE_COPY_SELECT';

            return cmsFramework::jsonResponse($response);
		}

		$query = "CREATE TEMPORARY TABLE temp_table AS SELECT * FROM #__jreviews_criteria WHERE id = " . $criteriaid;

		$this->Criteria->query($query);


		$query = "UPDATE temp_table SET id = 0, title = CONCAT(title,' [COPY]') WHERE id = " . $criteriaid;

		$this->Criteria->query($query);


		$query = "INSERT INTO #__jreviews_criteria SELECT * FROM temp_table";

		$this->Criteria->query($query);

		$new_id = $this->Criteria->insertid();

		$query = "DROP TEMPORARY TABLE temp_table";

		$this->Criteria->query($query);

		// Reloads the whole list to display the new/updated record
		$fieldrows = $this->Criteria->findAll(array('order'=>array('Criteria.title')));

		$response['success'] = true;

        $response['html'] = $this->listViewTable($fieldrows);

        $response['id'] = $new_id;

        return cmsFramework::jsonResponse($response);
	}
}
