<?php
/**
 * JReviews - Reviews Extension
 * Copyright (C) 2010-2012 ClickFWD LLC
 * This is not free software, do not distribute it.
 * For licencing information visit http://www.reviewsforjoomla.com
 * or contact sales@reviewsforjoomla.com
**/

defined( 'MVC_FRAMEWORK') or die( 'Direct Access to this location is not allowed.' );

class JreviewsCategoryModel extends MyModel  {
	
	var $name = 'JreviewsCategory';
	
	var $useTable = '#__jreviews_categories AS JreviewsCategory';
			
	var $primaryKey = 'JreviewsCategory.id';
	
	var $realKey = 'id';
	
	var $fields = array(
		'JreviewsCategory.id AS `JreviewsCategory.id`',
		'JreviewsCategory.dirid AS `JreviewsCategory.dir_id`',
		'JreviewsCategory.criteriaid AS `JreviewsCategory.criteria_id`',
		'JreviewsCategory.tmpl AS `JreviewsCategory.tmpl`',
		'JreviewsCategory.tmpl_suffix AS `JreviewsCategory.tmpl_suffix`',
        'ListingType.id AS `ListingType.criteria_id`',
        'ListingType.title AS `ListingType.title`',
        'ListingType.criteria AS `ListingType.criteria`',
        'ListingType.required AS `ListingType.required`',
        'ListingType.weights AS `ListingType.weights`',
        'ListingType.tooltips AS `ListingType.tooltips`',
        'ListingType.qty AS `ListingType.quantity`',
        'ListingType.groupid AS `ListingType.group_id`',
        'ListingType.state AS `ListingType.state`',
        'ListingType.config AS `ListingType.config`'  # Configuration overrides        
	);

    var $joins = array(
        'LEFT JOIN #__jreviews_criteria AS ListingType ON JreviewsCategory.criteriaid = ListingType.id'
    );
	
}