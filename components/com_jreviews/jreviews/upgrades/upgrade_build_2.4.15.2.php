<?php
defined( 'MVC_FRAMEWORK') or die;

$Model = new S2Model;

$db_name = cmsFramework::getConfig('db');

$db_prefix = cmsFramework::getConfig('dbprefix');

/**
 * Add index to #__categories for improved performance of queries with parent_id conditions
 */
$query = "
	SELECT
		index_name
	FROM
		information_schema.statistics
	WHERE
		table_schema = '". $db_name ."'
		AND
		table_name = '". str_replace('#__',$db_prefix,'#__jreviews_reviewer_ranks') ."'
";

$indexes = $Model->query($query, 'loadColumn');

// Now create new indexes
if(!in_array('jr_reviews_votes',$indexes))
{
	$query = "
		ALTER TABLE `#__jreviews_reviewer_ranks` ADD INDEX  `jr_reviews_votes` ( `reviews`, `votes_percent_helpful` );
	";

	$Model->query($query);

	$query = "
		ALTER TABLE `#__jreviews_reviewer_ranks` ADD INDEX  `jr_rank` ( `rank` );
	";

	$Model->query($query);
}