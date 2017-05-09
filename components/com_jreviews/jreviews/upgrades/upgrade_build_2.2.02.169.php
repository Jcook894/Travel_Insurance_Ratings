<?php
defined( 'MVC_FRAMEWORK') or die;

/**
 * Add index to #__jreviews_listing_totals
 */
$query = "
	SELECT
		index_name
	FROM
		information_schema.statistics
	WHERE
		table_schema = '". $dbname ."'
		AND
		table_name = '". str_replace('#__',$dbprefix,'#__jreviews_listing_totals') ."'
";

$indexes = $Model->query($query, 'loadColumn');

// Now create new indexes
if(!in_array('user_rating',$indexes))
{
	$queries = array(
		"ALTER TABLE  `#__jreviews_listing_totals` ADD INDEX `user_rating` (  `user_rating` ,  `user_rating_count` );",
		"ALTER TABLE  `#__jreviews_listing_totals` ADD INDEX `editor_rating` (  `editor_rating` ,  `editor_rating_count` );",
		"ALTER TABLE  `#__jreviews_listing_totals` ADD INDEX `user_comment_count` (  `user_comment_count` );",
		"ALTER TABLE  `#__jreviews_listing_totals` ADD INDEX `editor_comment_count` (  `editor_comment_count` );"
	);

	foreach($queries AS $query)
	{
		$Model->query($query);

		usleep(500000);
	}
}

/**
 * Add index to #__jreviews_directories
 */
$query = "
	SELECT
		index_name
	FROM
		information_schema.statistics
	WHERE
		table_schema = '". $dbname ."'
		AND
		table_name = '". str_replace('#__',$dbprefix,'#__jreviews_directories') ."'
";

$indexes = $Model->query($query, 'loadColumn');

// Now create new indexes
if(!in_array('title',$indexes)) {
	$queries = array(
		"ALTER TABLE  `#__jreviews_directories` ADD INDEX `title` ( `title` ( 35 ) );"
	);

	foreach($queries AS $query)
	{
		$Model->query($query);

		usleep(500000);
	}
}

