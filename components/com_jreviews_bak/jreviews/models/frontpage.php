<?php
/**
 * JReviews - Reviews Extension
 * Copyright (C) 2010-2012 ClickFWD LLC
 * This is not free software, do not distribute it.
 * For licencing information visit http://www.reviewsforjoomla.com
 * or contact sales@reviewsforjoomla.com
**/

defined( 'MVC_FRAMEWORK') or die( 'Direct Access to this location is not allowed.' );

class FrontpageModel extends MyModel  {
		
	var $name = 'Frontpage';
	
	var $useTable = '#__content_frontpage AS Frontpage';
	
	var $primaryKey = 'Frontpage.content_id';
	
	var $realKey = 'content_id';
	
	var $fields = array(
		'Frontpage.content_id AS `Frontpage.content_id`',
		'Frontpage.ordering AS `Frontpage.ordering`'
		);
}