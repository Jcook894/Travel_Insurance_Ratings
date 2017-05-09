<?php
defined( 'MVC_FRAMEWORK') or die;

if(_CMS_NAME != 'joomla') return;

/**
 * Add index to #__categories for improved performance of queries with parent_id conditions
 */
$query = "
	SELECT
		index_name
	FROM
		information_schema.statistics
	WHERE
		table_schema = '". $dbname ."'
		AND
		table_name = '". str_replace('#__',$dbprefix,'#__categories') ."'
";

$indexes = $Model->query($query,'loadColumn');

// Now create new indexes
if(!in_array('jr_parent_id',$indexes))
{
	$query = "
		ALTER TABLE `#__categories` ADD INDEX  `jr_parent_id` ( `parent_id` );
	";

	$Model->query($query);

	usleep(500000);
}

