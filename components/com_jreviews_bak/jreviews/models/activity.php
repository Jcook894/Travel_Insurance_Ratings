<?php
/**
 * JReviews - Reviews Extension
 * Copyright (C) 2010-2012 ClickFWD LLC
 * This is not free software, do not distribute it.
 * For licencing information visit http://www.reviewsforjoomla.com
 * or contact sales@reviewsforjoomla.com
**/

defined( 'MVC_FRAMEWORK') or die( 'Direct Access to this location is not allowed.' );

class ActivityModel extends MyModel {
		
	var $name = 'Activity';
	
	var $useTable = '#__jreviews_activities AS Activity';

	var $primaryKey = 'Activity.activity_id';
	
	var $realKey = 'activity_id';
	
	var $fields = array('Activity.*');
	
	var $joins = array();
	
	var $conditions = array();
	
	var $group = array();
}
