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

class DirectoriesController extends MyController {
	
	var $uses = array('directory');	

    var $helpers = array('html');

    var $components = array('config');

	var $autoRender = false;

	var $autoLayout = false;	
		
	function beforeFilter() 
    {
		# Call beforeFilter of MyAdminController parent class
		parent::beforeFilter();
	}

	function index() 
    {		
		$this->name = 'directories';
		
		$rows = $this->Directory->getList();
	 	
	 	$table = $this->listViewTable($rows);
		
		$this->set(array('table'=>$table));

		return $this->render();
	}
	
	function listViewTable($rows) {
					
		$this->autoRender = false;
			
		$this->set(
			array(
				'rows'=>$rows
			)
		);
		
		return $this->render('directories','table');	
	}	

	function edit() 
    {
		$directory = $this->Directory->emptyModel();

		$this->name = 'directories';
		
		$this->autoRender = true;
		
		$dirid =  Sanitize::getInt( $this->passedArgs, 'dirid', '' );
		
		if ($dirid) {
            $callbacks = array();
			$directory = $this->Directory->findRow(array('conditions'=>array('id = ' . $dirid)),$callbacks);
		}
	
		$this->set(array('directory'=>$directory['Directory']));
	}
	
	function _save($params) 
    {
        $this->action = 'index';

		// Begin validation
		$msg = array();
	
		if (isset($this->data['Directory']['title']) && $this->data['Directory']['title']=='')
			$msg[] = "Please enter a directory name.";
		
		if (isset($this->data['Directory']['desc']) && $this->data['Directory']['desc']=='')
			$msg[] = "Please enter a directory title.";
		
		if (count($msg) > 0) {
            $action = 'error';
            $text = implode("<br />",$msg);
            return $this->ajaxResponse(compact('text','action'),false);                 
		}
	
		$isNew = Sanitize::getInt($this->data['Directory'],'id') ? false : true;
		
		$this->Directory->store($this->data);
	
		// Reloads the whole list to display the new/updated record
	 	$rows = $this->Directory->getList();
		$page = $this->listViewTable($rows);
        $action = 'success';
        $row_id = "directory".$this->data['Directory']['id'];
        return $this->ajaxResponse(compact('page','action','row_id'),false);        	 	
	}	
	
	function delete() 
    {
		$id = Sanitize::getInt($this->data,'entry_id');
        $response = array();
        if(!$id) return $this->ajaxResponse($response,false);
        
		// Check if the criteria is being used by a category
		$this->_db->setQuery("SELECT count(*) FROM #__jreviews_categories WHERE dirid IN ($id)");
		if ($this->_db->loadResult()) 
        {
            $response[] = "jreviews_admin.dialog.close();s2Alert('You have categories using this directory, first you need to delete them or change the directory they have been assigned to.');";
            return $this->ajaxResponse($response);
		}

		if (!$this->Directory->delete('id',$id)) 
        {
            $response[] = "jreviews_admin.dialog.close();s2Alert('".$this->_db->getErrorMsg()."');";
            return $this->ajaxResponse($response);
		}
		
        $response[] = "jreviews_admin.dialog.close();jreviews_admin.tools.removeRow('directory{$id}');";
        return $this->ajaxResponse($response);
	}
}