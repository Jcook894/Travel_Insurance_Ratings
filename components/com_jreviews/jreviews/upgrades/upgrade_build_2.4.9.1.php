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
if(!in_array('user_editor_photo_counts',$indexes)) {
	$queries = array(
		"ALTER TABLE `#__jreviews_listing_totals` ADD INDEX `user_editor_photo_counts` (`user_comment_count`,`editor_comment_count`,`photo_count`)
;"
	);

	foreach($queries AS $query)
	{
		$Model->query($query);

		usleep(500000);
	}
}
