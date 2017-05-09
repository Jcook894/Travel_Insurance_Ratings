<?php
/**
 * JReviews - Reviews Extension
 * Copyright (C) 2010-2011 ClickFWD LLC
 * This is not free software, do not distribute it.
 * For licencing information visit http://www.reviewsforjoomla.com
 * or contact sales@reviewsforjoomla.com
**/

// no direct access
defined( 'MVC_FRAMEWORK') or die( 'Direct Access to this location is not allowed.' );

class FieldsController extends MyController {
	
	var $uses = array('field','group','acl','criteria');
	
    var $components = array('config');

	var $helpers = array('html','form','paginator','admin/admin_fields');
	
	var $autoRender = false;
	
	var $autoLayout = false;
	
	function index($params = array()) 
    {     
		if(!empty($this->data['Field'])) 
        {
			$location = $this->data['Field']['location'];
			$groupid = $this->data['Field']['groupid'];
            $type = isset($this->data['FieldFilter']) ? Sanitize::getString($this->data['FieldFilter'],'type') : null;
		} 
        else {
			$location = 'content';
			$groupid = 0;
            $type = null;
		}
		
        $groupchange = Sanitize::getInt($this->data,'groupchange');
        
        $this->action = 'index'; // Required for paginator helper

		$limit = $this->limit;
		$limitstart = $groupchange == 1 ? 0 : $this->offset;
        $groupchange == 1 and $this->page = 1;
                
		// First check if there are any field groups created
		$query = "SELECT count(*) FROM #__jreviews_groups";
        
		$this->_db->setQuery($query);
		
		if (!$this->_db->loadResult()) 
		{	
			return __a("You need to create at least one field group using the Field Groups Manager before you can create custom fields.",true);
		}
	
		$lists = array();
	
		$total = 0;

		$rows = $this->Field->getList($location, $groupid, $limitstart, $limit, $total, $type);
	
		$this->set(
			array(
				'location'=>$location,
				'groups'=>$this->Group->getSelectList($location),
				'rows'=>$rows,
				'groupid'=>$groupid,
                'type'=>$type,
				'pagination'=>array(
					'total'=>$total
				)
			)
		);
		
		return $this->render('fields','index');
	}		
		
	function _edit() 
    {
		$this->name = 'fields';
		$this->action = 'edit';
		
		$this->autoRender = false;
		$location = Sanitize::getString($this->data['Field'],'location');
		$group = Sanitize::getInt($this->data['Field'],'groupid');
		$fieldid = Sanitize::getInt($this->data['Field'],'fieldid');
		$limitstart = Sanitize::getInt($this->data,'limitstart');
		$limit = Sanitize::getInt($this->data,'limit');
		$row = new stdClass();
		$fieldParams = array();
				
		$groupList = array();
		$lists = array();
		$locationOptions = array();
		$hidden = array();
		
		$disabled = "'DISABLED'";

		if ($fieldid) 
        {
			$row = $this->Field->findRow(array('conditions'=>array('fieldid = ' . $fieldid)));
			$fieldParams = $row['Field']['_params'];		
		} 
        else 
        {
			$row = $this->Field->emptyModel();
		}
       
		$this->_db->setQuery("
            SELECT 
                    groupid AS value, 
                    CONCAT(title,' (',name,')') AS text 
                FROM 
                    #__jreviews_groups 
                WHERE 
                    type= " . $this->quote($location) . " 
            ORDER BY ordering"
        );

		if (!$fieldGroups = $this->_db->loadObjectList()) 
        {
            $success = false;
            $msg = "To add $location custom fields you first need to create a $location field group.";
            return json_encode(compact('success','msg'));
        }	

		$this->set(array(
			'row'=>$row,
			'accessGroups'=>$this->Acl->getAccessGroupList(),
			'fieldGroups'=>$fieldGroups,
			'location'=>$location,
			'fieldParams'=>$fieldParams,
			'limit'=>$limit,
			'limitstart'=>$limitstart,
            'listingTypes'=>$this->Criteria->getSelectList() 
		));
				
		$page = $this->render();
        
        return json_encode(compact('success','page'));
    }
	
    function getAdvancedOptions($type,$fieldid,$location) 
    {
        $this->name = 'fields';
        $this->action = 'advanced_options';
        $fieldParams = array();
                    
        $script = '';

        if($fieldid)
        {
            $field = $this->Field->findRow(array('conditions'=>array('fieldid = ' . $fieldid)));
            $fieldParams = stringToArray($field['Field']['options'] );
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
            $params->$paramKey = '';
        }
        
        foreach($fieldParams AS $paramKey=>$paramValue) 
        {
            $params->$paramKey = $paramValue;    
        }
       
        $this->set(
            array(
                'type'=>$type,
                'location'=>$location,
                'params'=>$params,
                'field_params'=>$fieldParams                
            )
        );

		switch($type) {
			case 'text':
			case 'textarea':
			case 'code':
					$script .= 'jQuery("#valid_regex").val("");';
				break;
			case 'email':
					$script .= 'jQuery("#valid_regex").val(".+@.*");';
				break;
			case 'website':
					$script .= 'jQuery("#valid_regex").val("^((ftp|http|https)+(:\/\/)+[a-z0-9_-]+\.+[a-z0-9_-]|[a-z0-9_-]+\.+[a-z0-9_-])");';
				break;
		}
		
        $output = $this->render();
        return array('page'=>$output,'type'=>$type,'location'=>$location,'demo'=>(int)defined('_JREVIEWS_DEMO'),'params'=>$fieldParams,'response'=>$this->makeJS($script));  
    }
        
	function _save() 
    {
		$this->action = 'index';		
		
		$isNew = false;
		
		// Begin validation
		$msg = array();

		if (!isset($this->data['Field']['location'])) {
			$msg[] = "Please select a location for this field.";
		}	if ($this->data['Field']['type']=='') {
			$msg[] = "Please select a field type.";
		}
		if ($this->data['Field']['groupid']=='') {
			$msg[] = "Please select a field group for this field.";
		}
	
		if ($this->data['Field']['name']=='') {
			$msg[] = "Please enter a field name.";
	
		} else {
			$table = $this->_db->getTableFields(array('#__jreviews_content'));
			$contentFields = array_keys($table['#__jreviews_content']);
	
			$table = $this->_db->getTableFields(array('#__jreviews_review_fields'));
			$reviewFields = array_keys($table['#__jreviews_review_fields']);
	
			$fields = array_merge($contentFields,$reviewFields);

			if(in_array('jr_'.$this->data['Field']['name'], $fields)){
				$msg[] = "A field with that name already exists for either content or reviews. Each custom field name has to be unique.";
			}
		}
	
		if ($this->data['Field']['title']=='') {
			$msg[] = "Please enter a field title.";
		}
	
		if (count($msg) > 0) {
            $action = 'error';
            $text = implode("<br />",$msg);
            return $this->ajaxResponse(compact('text','action'),false); 
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

		if (is_array( $params )) 
        {
			$txt = array();
			foreach ($params as $k=>$v) {
				$v = str_replace("\n","<br />",$v);
				$txt[] = "$k=$v";
			}
	
	 		$this->data['Field']['options'] = implode("\n", $txt );
	 		
			unset($this->data['Field']['params']);
		}
		
		// Add last in the order for current group if new field
		if (!Sanitize::getInt($this->data['Field'],'ordering')) {
			$this->_db->setQuery("select max(ordering) FROM #__jreviews_fields WHERE groupid = '".Sanitize::getInt($this->data['Field'],'groupid')."'");
			$max = $this->_db->loadResult();
			if ($max > 0) $this->data['Field']['ordering'] = $max+1; else $this->data['Field']['ordering'] = 1;
		}
	
		// If new field, then add jr_ prefix to it.
		if (!Sanitize::getInt($this->data['Field'],'fieldid')) {
			$this->data['Field']['name'] = "jr_".$this->data['Field']['name'];
			$isNew = true;
		}
	
		// If multiple option field type (multipleselect or checkboxes) then force listsort to 0;
		if (in_array($this->data['Field']['type'],array("selectmultiple","checkboxes"))) {
			$this->data['Field']['listsort'] = 0;
		}
//        elseif ($this->data['Field']['type'] == 'banner') {
            $this->data['Field']['description'] = $this->data['__raw']['Field']['description'];
//        }
	
		// First lets create the new column in the table in case it fails we don't add the field
		if ($isNew) 
        {
			$added = $this->Field->addTableColumn($this->data['Field']['name'], $this->data['Field']['type'], $this->data['Field']['location']);
			if ($added != '') {
				return ; // insert failed
			}
			
			$this->_db->setQuery(
				"SELECT count(*) FROM #__jreviews_fields"
				."\n WHERE location='".$this->data['Field']['location']."'"
				."\n AND groupid = " . $this->data['Field']['groupid']
			);		
			
			$total = $this->_db->loadResult();
			
			$this->page = ceil($total/$this->limit) > 0 ? ceil($total/$this->limit) : 1;

			$this->offset = ($this->page-1) * $this->limit;
		}

		// Now let's add the new field to the field list
		if (!$text = $this->Field->store($this->data)) {
            $action = 'error';
            return $this->ajaxResponse(compact('text','action'),false); 
		}

        if(Sanitize::getBool($this->data,'apply'))
        {
            $action = 'apply';               
            return $this->ajaxResponse(compact('action'),false);
        }   
        				
        $action = 'success';        	
		$page = $this->index();
        $row_id = "fields".$this->data['Field']['fieldid'];
        return $this->ajaxResponse(compact('action','page','row_id'),false);	
	}	
	
	function _delete() 
    {
        $response = array();
        
		$row_id = Sanitize::getInt($this->data['Field'],'fieldid');
		$location = Sanitize::getString($this->data['Field'],'location');

        if(!$row_id || empty($location)) return $this->ajaxResponse('',false);			
        	
		$tables_rel = array();
	
		$table = "#__jreviews_fields";
		$del_id = 'fieldid';
		
		// delete associated options if any
		$tables_rel = array();
		$tables_rel[] = "#__jreviews_fieldoptions";
		$del_id_rel = "fieldid";
		
		// need to drop column from #__jreviews_content		
		$this->_db->setQuery("SELECT name, type FROM $table WHERE fieldid = " . $row_id);
		
		$fields = $this->_db->loadAssocList();
		$removed = $this->Field->deleteTableColumn($fields, $location);
		
		if (!$removed) {
			$response[] = "s2Alert('{$removed}');";
			return $this->ajaxResponse($response);
		}
				
		$this->_db->setQuery("DELETE FROM $table WHERE $del_id = " . $row_id);
		
		if (!$this->_db->query()) {
            $response[] = "s2Alert('".$this->_db->getErrorMsg()."');";
            return $this->ajaxResponse($response);
		}

		if (count($tables_rel)) {
			foreach ($tables_rel as $table_rel) {
				$this->_db->setQuery("DELETE FROM $table_rel WHERE $del_id_rel = " . $row_id);
				if (!$this->_db->query()) {
                    $response[] = "s2Alert('".$this->_db->getErrorMsg()."');";
                    return $this->ajaxResponse($response);
				}
			}
		}
	
		// Clear cache
		clearCache('', 'views');
		clearCache('', '__data');
		
        $response[] = "jreviews_admin.dialog.close();";
        $response[] = "jreviews_admin.tools.removeRow('fields{$row_id}');";
        return $this->ajaxResponse($response);
	}	
	
    /**
    * Checks if there is a field option=>field relationship
    * 
    */
    function _controlledByCheck()
    {
        $response = array('result'=>0);
        if($field_id = Sanitize::getInt($this->data,'fieldid')) {
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
            $this->_db->setQuery($query);
            $count =  $this->_db->loadResult();
            $response['result'] = $count;
        }
        
        return json_encode($response);
    }  
     
	function _saveOrder() 
    {
		$cid = $this->data['cid']; // array()
		$total = count( $cid );
		$order = $this->data['order']; // array() 
		$limit = Sanitize::getInt($this->data,'limit');
		$limitstart = Sanitize::getInt($this->data,'limitstart');
		$groupid = Sanitize::getInt($this->data,'groupid');
		$location = (string) $this->data['Field']['location'];
	
		$conditions = array();
	
		// update ordering values
		for( $i=0; $i < $total; $i++ ) 
        {
			$field = $this->Field->findRow(array('conditions'=>array('fieldid = ' . $cid[$i])));

			if ($field['Field']['ordering'] != $order[$i]) 
            {
				$field['Field']['ordering'] = $order[$i];
				if (!$error = $this->Field->store($field)) 
                {
                    // There was an error
                }
				// remember to updateOrder this group
				$condition = "groupid = " . $field['Field']['groupid'];

				$found = false;
				
				foreach ( $conditions as $cond )
					if ($cond[1]==$condition) {
						$found = true;
						break;
					} // if
				if (!$found) $conditions[] = array($field['Field']['groupid'], $condition);
			} // if
		} // for
	
		// execute updateOrder for each group
		foreach ( $conditions as $cond ) 
        {
//			$this->Field->findRow(array('conditions'=>array('fieldid = ' . $cond[0])) );
			$this->Field->reorder( $cond[1] );
		} // foreach	
	
	 	$page = $this->index(array($location, $groupid, $limitstart, $limit));
		 	
	 	return $page;
	
	} // saveFieldsOrder
	
	function checkType() 
    {
        $success = true;
        $type = Sanitize::getString($this->data,'type');
        $fieldid = Sanitize::getString($this->data,'fieldid');
        $location = Sanitize::getString($this->data,'location');
        if($type !='' && $location)
        {
            $adv_options = $this->getAdvancedOptions($type,$fieldid,$location);
        }
        else
        {
            $adv_options = array('page'=>'You need to select a field type.','response'=>'');
        }
        return json_encode(compact('success','adv_options'));                       
	}
	
	function _changeOrder() 
    {
		$row_id = (int) $this->data['row_id'];
 		$inc = $this->data['direction'];
  		
		// Move row

		$field = $this->Field->findRow(array('conditions'=>array('fieldid = ' . $row_id)));
		
		$this->Field->Result = $field;
		
		$this->Field->move( $inc,  "groupid = " . $field['Field']['groupid']);

	 	$page = $this->index();
        
        return $page;		
	}
		
}
