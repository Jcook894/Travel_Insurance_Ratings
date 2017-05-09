<?php
defined( 'MVC_FRAMEWORK') or die;

$Model = new S2Model;

$db_name = cmsFramework::getConfig('db');

$db_prefix = cmsFramework::getConfig('dbprefix');

$query = "
	SELECT
		index_name
	FROM
		information_schema.statistics
	WHERE
		table_schema = '". $db_name ."'
		AND
		table_name = '". str_replace('#__',$db_prefix,'#__jreviews_comments') ."'
";

$indexes = $Model->query($query, 'loadColumn');

// Now create new indexes

if(!in_array('published_user_author',$indexes))
{
	$query = "
		ALTER TABLE `#__jreviews_comments` ADD INDEX  `published_user_author` ( `published`, `userid`, `author`);
	";

	$Model->query($query);
}

// Drop old indexes #__jreviews_comments

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

if(in_array('jr_reviews_votes', $indexes))
{
	$Model->query('DROP INDEX `jr_reviews_votes` ON #__jreviews_reviewer_ranks');
}

$query = "
	ALTER TABLE `#__jreviews_reviewer_ranks` ADD INDEX `jr_reviews_votes` ( `reviews`,`votes_percent_helpful`,`user_id`);
";

$Model->query($query);
