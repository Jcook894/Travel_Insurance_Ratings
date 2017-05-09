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

class GroupsController extends MyController {
	
	var $uses = array('group','field');

	var $helpers = array('html','form','paginator');
    
    var $components = array('config');

	var $autoRender = false;
	
	var $autoLayout = false;
		
	function beforeFilter() 
    {
		$this->name = 'groups'; // required for admin controllers
		
		# Call beforeFilter of MyAdminController parent class
		parent::beforeFilter();
	}
		
	function index() 
    {
        $this->action = 'index'; // For pagination after saving
		$type = 'content';

		if(isset($this->data['Group']['type'])) {			
			$type = $this->data['Group']['type'];
		}	
		         
		$limit = $this->limit;
		$limitstart = $this->offset;		
	 	$total = 0;

	 	$rows = $this->Group->getList($type, $limitstart, $limit, $total);

		$this->set(
			array(
				'rows'=>$rows,
				'type'=>$type,
				'pagination'=>array(
					'total'=>$total
				)
			)
		);

		return $this->render('groups','index');
	}
			
	function _save() 
    {
		$isNew = false;

		# Validate
		$msg = array();
		if (!isset($this->data['Group']['type'])) {
			$msg[] = "Please select the type of field you want to create.";
		}
		if ($this->data['Group']['name']=='')
			$msg[] = "Please enter a field group name.";
			
		if ($this->data['Group']['title']=='')
			$msg[] = "Please enter a field group title.";
		
		if (count($msg) > 0) {
            $action = 'error';
            $text = implode("<br />",$msg);
            return $this->ajaxResponse(compact('text','action'),false);  
		}

		# New
		if(!Sanitize::getInt($this->data['Group'],'groupid')) {
			$isNew = true;
			$this->_db->setQuery("SELECT max(ordering) FROM #__jreviews_groups WHERE type='".$this->data['Group']['type']."'");
			$max = $this->_db->loadResult();
			$this->data['Group']['ordering'] = $max + 1;			
		}
		
		$this->Group->store($this->data);
		
		if($isNew) {
			$this->_db->setQuery("SELECT count(*) FROM #__jreviews_groups WHERE type='".$this->data['Group']['type']."'");		
			
			$total = $this->_db->loadResult();
			
			$this->page = ceil($total/$this->limit) > 0 ? ceil($total/$this->limit) : 1;
	
			$this->offset = ($this->page-1) * $this->limit;
		
		}	
	 	
        $action = 'success';              
	 	$page = $this->index();
        $row_id = "fieldgroup".$this->data['Group']['groupid'];
        $fade = false;
        
        return $this->ajaxResponse(compact('action','fade','page','row_id'),false);	 	    
	}
	
	function edit() {
				
		$this->autoRender = true;

		$groupid =  Sanitize::getInt( $this->passedArgs, 'groupid', '' );
		$type = Sanitize::getString( $this->passedArgs, 'type');
		$limitstart = Sanitize::getInt( $this->passedArgs, 'limitstart',$this->offset);
		$limit = Sanitize::getInt( $this->passedArgs, 'limit',$this->limit);
	
		$row = $this->Group->findRow(array('conditions'=>array('groupid = ' . $groupid)));

		$this->set(array(
			'row'=>$row,
			'type'=>$type
		));	
	}
	
	
	function _delete($params) 
    {
        $response = array();
        
		$data = array_shift($params);	

		$group_id = Sanitize::getInt($this->data,'entry_id');

		$queryData = array(
			'conditions'=>array('groupid = ' . $group_id)
		);
		
		$fieldCount = $this->Field->findCount($queryData);
		
		// First check if the group has any fields and force the user to delete the fields first
		if($fieldCount > 0) 
        {
			$response[] = "s2Alert('".sprintf(__a("There are % fields associated with this group. You need to delete those first.",true),$fieldCount)."');";
			return $this->ajaxResponse($response);								
				
		} else {

			$this->Group->delete('groupid',$group_id);
		}
		
		// Clear cache
		clearCache('', 'views');
		clearCache('', '__data');	

        $response[] = "jreviews_admin.dialog.close();";
        $response[] = "jreviews_admin.tools.removeRow('fieldgroup{$group_id}');";
        return $this->ajaxResponse($response);                                
	}	
	
	function _saveOrder($params) {
		
        $response = array();

	   	$cid = $this->data['cid']; // array()
		$total = count( $cid );
		$order = $this->data['order']; // array()
	  	$limit = Sanitize::getInt($this->data,'limit');
		$limitstart = Sanitize::getInt($this->data,'limitstart');
		$type = Sanitize::getString($this->data['Group'],'type','content');
			
		$conditions = array();
	
		// update ordering values
		for( $i=0; $i < $total; $i++ ) {

			$row = $this->Group->findRow(array('conditions'=>array('groupid = ' . $cid[$i])));
			
			if ($row['Group']['ordering'] != $order[$i]) {
				
				$row['Group']['ordering'] = $order[$i];
				
				$row['Group']['groupid'] = $row['Group']['group_id'];
				
				unset($row['Group']['group_id']);
				
				if (!$error = $this->Group->store($row)) {
                    $response[] = "s2Alert('$error');";
					return $this->ajaxResponse($response);
				}
				
				// remember to updateOrder this group
				$condition = "type='{$row['Group']['type']}'";

				$found = false;
				
				foreach ( $conditions as $cond )
					if ($cond[1]==$condition) {
						$found = true;
						break;
					} // if
					
				if (!$found) $conditions[] = array($row['Group']['type'], $condition);
			} // if
		} // for
	
		// execute updateOrder for each group
		foreach ( $conditions as $cond ) {
//			$row = $this->Group->findRow(array('conditions'=>array('groupid = ' . $cond[0])));
			$this->Group->reorder( $cond[1] );
		} // foreach
		
		$page = $this->index();
		
        $this->data['Group']['type'] = $row['Group']['type'];
        
        $text = __a("New ordering saved.",true);
        
        return $this->ajaxResponse(compact('text','page'),false);        	
	}
	
	function _changeOrder() 
    {
		$row_id = Sanitize::getInt($this->params,'entry_id');
		$inc = Sanitize::getVar($this->params,'direction');
		
		// Move row
		$group = $this->Group->findRow(array('conditions'=>array('groupid = ' . $row_id)));

		$group['Group']['groupid'] = $group['Group']['group_id'];
		unset($group['Group']['group_id']);
		
		$this->Group->Result = $group;

		$this->Group->move( $inc,  "type = '{$group['Group']['type']}'" );
        
        $this->data['Group']['type'] = $group['Group']['type'];
	
		return $this->index();
	}
	
	function toggleTitle() 
    {
        $group_id = Sanitize::getInt($this->params,'group_id');
            
        $field = "showtitle";
        $table = "#__jreviews_groups";
        $key = "groupid";
        $this->_db->setQuery( "SELECT $field FROM `$table` WHERE $key = '$group_id'"    );
    
        $state = !$this->_db->loadResult();
        
        $this->_db->setQuery( "UPDATE `$table` SET `$field` = '$state' WHERE $key = '$group_id'" );
    
        if ($this->_db->query()) 
        {
            clearCache('', 'views');
            clearCache('', '__data');
            return (int)$state;
        }        
	}	
}
