<?php
defined( 'MVC_FRAMEWORK') or die;

$Model = new S2Model;

$db_name = cmsFramework::getConfig('db');

$db_prefix = cmsFramework::getConfig('dbprefix');

// Drop old indexes #__jreviews_comments

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

if(in_array('listing', $indexes))
{
	$Model->query('DROP INDEX `listing` ON #__jreviews_comments');
}

$query = "
	ALTER TABLE `#__jreviews_comments` ADD INDEX `listing` ( `pid`, `mode`, `published`, `author`, `userid`, `created`)
";

$Model->query($query);

$review_columns = $Model->getTableColumns('#__jreviews_comments');

$review_columns = array_keys($review_columns);

if(in_array('listing_type_id',$review_columns))
{
	if(in_array('published', $indexes))
	{
		$Model->query('DROP INDEX `published` ON #__jreviews_comments');
	}

	$query = "
		ALTER TABLE `#__jreviews_comments` ADD INDEX `published` ( `published`, `listing_type_id`, `pid`, `mode`, `author`, `created`)
	";

	$Model->query($query);
}