<?php
/**
 * JReviews - Reviews Extension
 * Copyright (C) 2010-2015 ClickFWD LLC
 * This is not free software, do not distribute it.
 * For licencing information visit https://www.jreviews.com
 * or contact sales@jreviews.com
**/

defined( 'MVC_FRAMEWORK') or die( 'Direct Access to this location is not allowed.' );

class FieldOptionsController extends MyController {

	var $uses = array('field_option');
	var $components = array('config','access');

	function beforeFilter()
    {
		# Init Access
		$this->Access->init($this->Config);
	}
}