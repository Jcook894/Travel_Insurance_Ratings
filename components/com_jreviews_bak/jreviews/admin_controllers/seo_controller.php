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

class SeoController extends MyController {
	
	var $uses = array('field','group');
	
	var $helpers = array('html','form','paginator');
	
	var $components = array('config');
		
	var $autoRender = false;
	
	var $autoLayout = false;
		
	function index() 
    {
	    $groupid = Sanitize::getInt($this->data,'groupid',0);
		$type = isset($this->data['Field']) ? Sanitize::getString($this->data['Field'],'type') : null;
			
		$lists = array();
	
		$location = 'content';
		$limit = $this->limit;
		$limitstart = $this->offset;		
		$total = 0;
	
        // Will show up for all custom fields
		$rows = $this->Field->getList($location, $groupid, $limitstart, $limit, $total, $type);

		$this->set(
			array(
				'groups'=>$this->Group->getSelectList('content'),
				'group_id'=>$groupid,
                'type'=>$type,
				'rows'=>$rows,
				'pagination'=>array(
					'total'=>$total
				)
			)		
		);
		
		return $this->render();
	}

	function saveInPlace() 
    {
        $column = Sanitize::getString($this->data,'column');
        $fieldid = Sanitize::getInt($this->data,'fieldid');
        $value = Sanitize::getString($this->data,'text');        
	
		$this->_db->setQuery("
            UPDATE 
                #__jreviews_fields 
                    SET $column = " . $this->quote($value) . "
		        WHERE fieldid = $fieldid
		");
	
		if (!$this->_db->query()) 
        {
			return false;
		}
		
		// Clear cache
		clearCache('', 'views');
		clearCache('', '__data');
		
		return true;
	}	
		
}