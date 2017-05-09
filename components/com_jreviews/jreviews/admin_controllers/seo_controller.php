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

class SeoController extends MyController {

	var $uses = array('field','group');

	var $helpers = array('html','form','admin/paginator');

	var $components = array('config');

	var $autoRender = false;

	var $autoLayout = false;

	function index()
    {
    	$group_id = Sanitize::getInt($this->params,'groupid');

    	$location = Sanitize::getString($this->params,'location','content');

    	$type = Sanitize::getString($this->params,'type');

        $title = Sanitize::getString($this->params,'filter_title');

		$lists = array();

		$total = 0;

		$rows = $this->Field->getList(compact('location','type','group_id','title'), $this->offset, $this->limit, $total);

		$this->set(
			array(
				'groups'=>$this->Group->getSelectList('content'),
				'group_id'=>$group_id,
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
    	$Model = new S2Model;

        $fieldid = Sanitize::getInt($this->data,'fieldid');

        $column = Sanitize::getString($this->data,'column');

        $value = Sanitize::getString($this->data,'value');

		$query = "
            UPDATE
                #__jreviews_fields
            SET $column = " . $this->Quote($value) . "
		        WHERE fieldid = $fieldid
		";

		if(!$Model->query($query))
        {
			return false;
		}

		// Clear cache
		clearCache('', 'views');

		clearCache('', '__data');

		return true;
	}

}