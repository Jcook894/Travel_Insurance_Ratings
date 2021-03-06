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

class FieldsController extends MyController {

	var $uses = array('field','group','acl','criteria');

    var $components = array('config');

	var $helpers = array('html','form','admin/paginator','admin/admin_fields');

	var $autoRender = false;

	var $autoLayout = false;

	function index()
    {
    	$group_id = Sanitize::getInt($this->params,'groupid');

    	$location = Sanitize::getString($this->params,'location','content');

    	$type = Sanitize::getString($this->params,'type',null);

        $title = Sanitize::getString($this->params,'filter_title');

        $groupchange = Sanitize::getInt($this->data,'groupchange');

        $this->action = 'index'; // Required for paginator helper

		// First check if there are any field groups created
		$query = "SELECT count(*) FROM #__jreviews_groups";

		$count = $this->Field->query($query,'loadResult');

		if(!$count) {

			return JreviewsLocale::getPHP('FIELD_GROUPS_NOT_CREATED');
		}

		$lists = array();

		$total = 0;

		$fields = $this->Field->getList(compact('location','type','group_id','title'),$this->offset, $this->limit, $total);

		$fnames = $fids = $optionRelations = $maxLengthArray = array();

		if(!empty($fields))
		{
			foreach($fields AS $field)
			{
				if($field->type == 'banner') continue;

				$fids[] = $field->fieldid;
				$fnames[] = $field->name;
			}

			if(!empty($fnames))
			{
				$maxLengthArray = $this->Field->getMaxDataLength($fnames, $location);
			}

			// Get name of controlling fields in field-option to field-option relations

            $query = "
                SELECT
                    fieldid, control_field, count(*) AS `count`
                FROM
                    #__jreviews_fieldoptions
                WHERE
                    fieldid IN (" . implode(',', $fids) . ")
                    AND
                    control_field <> ''
                GROUP BY control_field
                ORDER BY count DESC
            ";

        	$optionControlFields = $this->Field->query($query, 'loadAssocList');

        	foreach ($optionControlFields AS $foption)
        	{
        		$optionRelations[$foption['fieldid']][] = array(
        			'control_field' => $foption['control_field'],
        			'count' 		=> $foption['count']
        		);
        	}
		}

		$this->set(
			array(
				'location'=>$location,
				'groups'=>$this->Group->getSelectList($location),
				'rows'=>$fields,
				'option_relations'=>$optionRelations,
				'maxLengthArray'=>$maxLengthArray,
				'groupid'=>$group_id,
                'type'=>$type,
				'pagination'=>array(
					'total'=>$total
				)
			)
		);

		return $this->render('fields','index');
	}

	function edit()
    {
		$this->name = 'fields';

		$this->action = 'edit';

		$this->autoRender = false;

		$fieldid = Sanitize::getInt($this->params,'id');

		$fieldParams = array();

		$groupList = array();

		$data_maxlength = '';

		$disabled = "'DISABLED'";

		if($fieldid)
        {
			$field = $this->Field->findRow(array('conditions'=>array('fieldid = ' . $fieldid)));

			$fieldParams = $field['Field']['_params'];

			if($field['Field']['type'] != 'banner') {

				$data_maxlength = $this->Field->getMaxDataLength($field['Field']['name'],$field['Field']['location']);

				$data_maxlength = $data_maxlength[$field['Field']['name']];
			}

			$location = Sanitize::getString($field['Field'],'location','content');

		}
        else {
			$field = $this->Field->emptyModel();

			$location = Sanitize::getString($this->params,'location','content');
		}

		$query = "
            SELECT
                groupid AS value,
                CONCAT(title,' (',name,')') AS text
            FROM
                #__jreviews_groups
            WHERE
                type= " . $this->Quote($location) . "
            ORDER BY
            	ordering"
        ;

        $fieldGroups = $this->Group->query($query, 'loadObjectList');

		if(!$fieldGroups)
        {
            return sprintf(JreviewsLocale::getPHP('FIELD_GROUP_TYPE_NOT_CREATED'),$location);
        }

		$this->set(array(
			'db_version'=>explode('.',$this->Field->getVersion()),
			'field'=>$field,
			'location'=>$location,
			'fieldParams'=>$fieldParams,
			'accessGroups'=>$this->Acl->getAccessGroupList(),
			'fieldGroups'=>$fieldGroups,
            'listingTypes'=>$this->Criteria->getSelectList(),
            'data_maxlength'=>$data_maxlength,
			'demo'=>(int)defined('_JREVIEWS_DEMO')
		));

		return $this->render();
    }

    function getAdvancedOptions($type,$fieldid,$location)
    {
        $this->name = 'fields';
        $this->action = 'advanced_options';
        $fieldParams = array();
        $formBuilderDefinitions = array();

        $script = '';
        $definitions = array();

        if($fieldid)
        {
            $field = $this->Field->findRow(array('conditions'=>array('fieldid = ' . $fieldid)));
            $fieldParams = $field['Field']['_params'];
        }

        # Preselect list/radio values based on current settings
        switch($type)
        {
            case 'integer': case 'decimal':
                $script = "jQuery('#curr_format').val(".Sanitize::getVar($fieldParams,'curr_format',1).");";
            break;
            case 'select': case 'selectmultiple': case 'radiobuttons': case 'checkboxes':
                $script = "jQuery('#options_ordering').val(".Sanitize::getVar($fieldParams,'option_ordering',0).");";
            break;
            case 'textarea': case 'text':
                $script = "jQuery('#allow_html').val(".Sanitize::getVar($fieldParams,'allow_html',0).");";
            break;
            case 'formbuilder':

		        // Get the list of formbuilder definitions

		        $App = S2App::getInstance();

		        $themeRegistry = $App->jreviewsPaths['Theme'];

		        foreach ($themeRegistry AS $theme)
		        {
		        	if (isset($theme['fields_formbuilder']))
		        	{
		        		foreach ($theme['fields_formbuilder'] AS $file => $path)
		        		{
		        			if (pathinfo($file, PATHINFO_EXTENSION) == 'json')
		        			{
								$definition = file_get_contents(PATH_ROOT . $path);
								$definitonArray = json_decode($definition, true);
								if (isset($definitonArray['name']) && $definitonArray['name'] != '') {
		        					$definitions[pathToUrl($path)] = $definitonArray['name'];
								}
		        			}
		        		}
		        	}
		        }
            break;
        }

        if (Sanitize::getVar($fieldParams,'output_format')=='' && !in_array($type,array('website','relatedlisting')))
        {
            $fieldParams['output_format'] = "{FIELDTEXT}";
        }
        else
        {
            $fieldParams['output_format'] = Sanitize::getVar($fieldParams,'output_format');
        }

        $fieldParams['valid_regex'] = !Sanitize::getVar($fieldParams,'valid_regex',0) ? '' : Sanitize::getVar($fieldParams,'valid_regex');

        $fieldParams['date_setup'] = trim(br2nl(stripslashes(Sanitize::getVar($fieldParams,'date_setup'))));

        $paramArray = array(
            'valid_regex',
            'allow_html',
            'click2searchlink',
            'output_format',
            'click2search',
            'click2add',
            'date_format',
            'option_images',
            'listing_type'
        );

        $params = new stdClass();

        foreach($paramArray AS $paramKey)
        {
            $params->$paramKey = null;
        }

        foreach($fieldParams AS $paramKey=>$paramValue)
        {
            $params->$paramKey = $paramValue;
        }

        $params->formBuilderDefinitions = $definitions;

        $this->set(
            array(
                'type'=>$type,
                'location'=>$location,
                'params'=>$params,
                'field_params'=>$fieldParams,
            )
        );

        $page = $this->render();

        return $page;
    }

    function toggleIndex()
    {
       	$response = array('success'=>false,'str'=>array());

		$name = Sanitize::getString($this->params,'id');

		$state = $this->Field->toggleIndex($name);

        $response['success'] = true;

        $response['state'] = $state;

        return cmsFramework::jsonResponse($response);
    }

	function update()
	{
		$id = Sanitize::getInt($this->params,'id');

		$row = $this->Field->findRow(array('conditions'=>array('Field.fieldid = ' . $id)));

		return cmsFramework::jsonResponse($row);
	}

	function _save()
    {
    	$Model = new S2Model;

    	$response = array('success'=>false,'str'=>array());

		$this->action = 'index';

        $apply = Sanitize::getBool($this->params,'apply',false);

		$isNew = false;

		$group_id = Sanitize::getInt($this->data['Field'],'groupid');

		$location = Sanitize::getString($this->data['Field'],'location');

        $control_value = Sanitize::getVar($this->data['Field'],'control_value');

        $control_field = Sanitize::getVar($this->data['Field'],'control_field');

        if(empty($control_value) || empty($control_field)) {

            $this->data['Field']['control_field'] = $this->data['Field']['control_value'] = '';
        }

		// Begin validation
		if ($location == '') {

			$response['str'][] = 'FIELD_VALIDATE_LOCATION';
		}

		if ($this->data['Field']['type']=='') {

			$response['str'][] = 'FIELD_VALIDATE_TYPE';
		}

		if ($group_id == 0) {

			$response['str'][] = 'FIELD_VALIDATE_GROUP';
		}

		if (str_replace('-','',$this->data['Field']['name']) == '') {

			$response['str'][] = 'FIELD_VALIDATE_NAME';

		}
		else {

			$conditions = array(
				"Field.name = " . $this->Quote('jr_'.$this->data['Field']['name'])
				);

			$is_duplicate = $this->Field->findCount(array('conditions'=>$conditions));

			if($is_duplicate){

				$response['str'][] = 'FIELD_VALIDATE_DUPLICATE';
			}
		}

		if ($this->data['Field']['title'] == '') {

			$response['str'][] = 'FIELD_VALIDATE_TITLE';
		}

		if (!empty($response['str'])) {

			return cmsFramework::jsonResponse($response);
		}


		if ($this->data['Field']['type'] == 'formbuilder')
		{
			$jsonSchema = json_decode($this->data['Field']['params']['json_schema'],true);

			$this->data['__raw']['Field']['params']['json_schema'] = $jsonSchema;

			$formModel = json_decode($this->data['Field']['params']['default'],true);
			$this->data['__raw']['Field']['params']['default'] = $formModel;
		}

		// Convert array settings to comma separated list
        if(isset($this->data['Field']['params']) && !empty($this->data['Field']['params']['listing_type']))
        {
            $this->data['__raw']['Field']['params']['listing_type'] = implode(',',$this->data['Field']['params']['listing_type']);
        } else {
            $this->data['__raw']['Field']['params']['listing_type'] = '';
        }

        if(isset($this->data['Field']['access']) && !empty($this->data['Field']['access']))
        {
            $this->data['Field']['access'] = implode(',',$this->data['Field']['access']);
        } else {
            $this->data['Field']['access'] = 'none';
        }

        if(isset($this->data['Field']['access_view']) && !empty($this->data['Field']['access_view']))
        {
            $this->data['Field']['access_view'] = implode(',',$this->data['Field']['access_view']);
        } else {
            $this->data['Field']['access_view'] = 'none';
        }

		// Process different field options (parameters)
		$params = Sanitize::getVar( $this->data['__raw']['Field'], 'params', '');

		if(is_array( $params ))
        {
        	if (version_compare(PHP_VERSION, '5.4.0', '>=')) {
				$this->data['Field']['options'] = json_encode($params, JSON_UNESCAPED_UNICODE);
        	}
        	else {
        		$this->data['Field']['options'] = json_encode($params);
        	}
			unset($this->data['Field']['params']);
		}

		// If new field, then add jr_ prefix to it.
		if (!Sanitize::getInt($this->data['Field'],'fieldid')) {

			$this->data['Field']['name'] = "jr_".strtolower(Inflector::slug($this->data['Field']['name'],''));

			$isNew = true;
		}

		// Add last in the order for current group if new field

		if ($isNew) {

			$query = "
				SELECT
					MAX(ordering)
				FROM
					#__jreviews_fields
				WHERE
					groupid = " . $group_id;

			$max = $this->Field->query($query, 'loadResult');

			if ($max > 0) $this->data['Field']['ordering'] = $max+1; else $this->data['Field']['ordering'] = 1;
		}

		// If multiple option field type (multipleselect or checkboxes) then force listsort to 0;

		if (in_array($this->data['Field']['type'],array('selectmultiple','checkboxes','formbuilder','banner')))
		{
			$this->data['Field']['listsort'] = 0;
		}

		if (in_array($this->data['Field']['type'],array('formbuilder','banner')))
		{
			$this->data['Field']['search'] = 0;
		}

        $this->data['Field']['description'] = $this->data['__raw']['Field']['description'];

		// First lets create the new column in the table in case it fails we don't add the field
		if ($isNew)
        {
			$added = $this->Field->addTableColumn($this->data['Field'], $this->data['Field']['location']);

			if ($added != '') {

				$response['str'][] = 'DB_ERROR';

            	return cmsFramework::jsonResponse($response);
			}
		}

		// Now let's add the new field to the field list
		$this->Field->store($this->data);

        $response['success'] = true;

        if($apply){

            return cmsFramework::jsonResponse($response);
        }

        if($isNew) {

        	$query = "
            	SELECT
            		count(*)
            	FROM
            		#__jreviews_fields
            	WHERE
            		groupid = " . $group_id . " AND location = '". $location ."'";

            $total = $this->Field->query($query,'loadResult');

            $this->page = ceil($total/$this->limit) > 0 ? ceil($total/$this->limit) : 1;

            $this->offset = ($this->page-1) * $this->limit;

        	$this->params['location'] = $location;

        	$this->params['groupid'] = $group_id;

	        $response['id'] = $this->data['Field']['fieldid'];

	        $response['isNew'] = true;

			$response['html'] = $this->index();

			clearCache('','core');
        }

        return cmsFramework::jsonResponse($response);
	}

	function _delete()
    {
        $response = array('success'=>false,'str'=>array());

		$ids = Sanitize::getVar($this->params,'cid');

		if(empty($ids)) {

 			return cmsFramework::jsonResponse($response);
		}

		$ids = cleanIntegerCommaList($ids);

		// need to drop column from #__jreviews_content
		$query = "
			SELECT
				name, type, location
			FROM
				#__jreviews_fields
		 	WHERE
		 		fieldid IN (" . $ids . ")" ;

		$fields = $this->Field->query($query,'loadAssocList');

		$field = reset($fields);

		$location = $field['location'];

		$removed = $this->Field->deleteTableColumn($fields, $location);

		if (!$removed) {

			return cmsFramework::jsonResponse($response);
		}

		$query = "
			DELETE
				Field,
				FieldOption
			FROM
				#__jreviews_fields AS Field
			LEFT JOIN
				#__jreviews_fieldoptions AS FieldOption ON Field.fieldid = FieldOption.fieldid
			WHERE
				Field.fieldid IN (" . $ids . ")
		";

		$this->Field->query($query);

		// Clear cache
		clearCache('', 'views');

		clearCache('', '__data');

 		$response['success'] = true;

 		return cmsFramework::jsonResponse($response);

	}

	function _changeFieldLength()
	{
		$response = array('success'=>false,'str'=>array());

		$field_id = Sanitize::getString($this->params,'id',Sanitize::getInt($this->data,'id'));

		$task = Sanitize::getString($this->params,'task',Sanitize::getString($this->params,'task'));

		$field = $this->Field->findRow(array('conditions'=>array('Field.fieldid = ' . $field_id)));

		$fname = $field['Field']['name'];

		$max_length = $this->Field->getMaxDataLength($fname,$field['Field']['location']);

		$max_length = $max_length[$fname];

		if($task == 'form')
		{
			$this->set(array(
				'field'=>$field,
				'max_length'=>$max_length
			));

			return $this->render('fields','create_fieldlength');
		}

		# Process length change here

		$db_version = explode('.',$this->Field->getVersion());

		if($db_version[0] >= 5 && $db_version[1] >= 0 && $db_version[2] >= 3) {

			$max = 65535;
		}
		else {

			$max = 255;
		}

		$new_maxlength = min($max,Sanitize::getInt($this->data['Field'],'maxlength'));

		if($new_maxlength == 0)
		{
			$response['str'][] = 'FIELD_VALIDATE_ZERO_LENGTH';

			return cmsFramework::jsonResponse($response);
		}

		if($new_maxlength < $max_length)
		{
			$response['str'][] = 'FIELD_VALIDATE_STORED_LENGTH';

			return cmsFramework::jsonResponse($response);
		}


		if($this->Field->modifyTableColumn($field, $new_maxlength))
		{
			$response['success'] = true;

 			$response['maxlength'] = $new_maxlength;

			return cmsFramework::jsonResponse($response);
		}

		$response['str'][] = 'FIELD_LENGTH_CHANGE_FAILED';

		return cmsFramework::jsonResponse($response);
	}

    /**
    * Checks if there are any field option=>field option relationships
    *
    */
    function _controlledByCheck()
    {
        $count = 0;

        if($field_id = Sanitize::getInt($this->params,'id')) {

            $query = "
                SELECT
                    count(*)
                FROM
                    #__jreviews_fields
                WHERE
                    fieldid = " . $field_id . "
                    AND
                    control_field <> ''
            ";

            $count =  $this->Field->query($query,'loadResult');
        }

        return $count;
    }

	function reorder() {

		$ordering = Sanitize::getVar($this->data,'order');

		$reorder = $this->Field->reorder($ordering);

		return $reorder;
	}

	function checkType()
    {
        $success = true;

        $fieldid = Sanitize::getString($this->params,'id');

        $type = Sanitize::getString($this->params,'type');

        $location = Sanitize::getString($this->params,'location');

        if($type !='' && $location)
        {
            $page = $this->getAdvancedOptions($type,$fieldid,$location);

        }
        else {

            $page = '';
        }

        return $page;
	}

    function getList()
    {
        $search = $this->Field->makeSafe(strtolower(Sanitize::getString($this->params,'value')));

        if (!$search) return;

        $types = array();

        $conditions = array("Field.name LIKE '%{$search}%'");

        $field_types = Sanitize::getVar($this->params,'field_types');

        $location = Sanitize::getVar($this->params,'field_location','listing');

        if($location == 'listing') $location = 'content';

        if(!empty($field_types)) {

            $field_types = explode(',',$field_types);

            if(!empty($field_types) && $field_types != '') {
                $conditions[] = "Field.type IN (".$this->Quote($field_types).")";
            }
        }

        if(!empty($location) && $location != '') {
            $conditions[] = "Field.location IN (".$this->Quote($location).")";
        }

        $fields = $this->Field->findAll(array(
            'conditions'=>$conditions
        ));

        $results = array();

        foreach ($fields as $field) {
            $results[] = array('value'=>$field['Field']['name'],'label'=>$field['Field']['title'] . ' ('.$field['Field']['name'].')');
        }

        return cmsFramework::jsonResponse($results);
    }

    function validate()
    {
        $response = array('success'=>false);

        $field = Sanitize::getString($this->params,'field');

        $location = Sanitize::getVar($this->params,'field_location','listing');

        if($field == 'parent_category' || $field == 'category')
        {
            $response['success'] = true;

            return cmsFramework::jsonResponse($response);
        }

        $fieldNames = $this->Field->getFieldNames($location);

        if(in_array($field, $fieldNames))
        {
            $response['success'] = true;
        }

        return cmsFramework::jsonResponse($response);
    }
}
