<?php
defined( 'MVC_FRAMEWORK') or die;

if(_CMS_NAME != 'joomla') return;

/**
 * Add #__content indexes, first backup table
 */

$query = "
	SELECT
		index_name
	FROM
		information_schema.statistics
	WHERE
		table_schema = '". $dbname ."'
		AND
		table_name = '". str_replace('#__',$dbprefix,'#__content') ."'
";

$indexes = $Model->query($query,'loadColumn');

# Add core table indexes for JReviews

if(!in_array('jr_created',$indexes)) {

	$Model->query("ALTER TABLE `#__content` ADD INDEX  `jr_created` (  `created` )");

	usleep(1000000);
}

if(!in_array('jr_modified',$indexes)) {

	$Model->query("ALTER TABLE `#__content` ADD INDEX  `jr_modified` (  `modified`, `created` )");

	usleep(1000000);
}

if(!in_array('jr_hits',$indexes)) {

	$Model->query("ALTER TABLE `#__content` ADD INDEX  `jr_hits` (  `hits` )");

	usleep(1000000);
}

if(!in_array('jr_ordering',$indexes)) {

	$Model->query("ALTER TABLE `#__content` ADD INDEX  `jr_ordering` (  `ordering` )");

	usleep(1000000);
}

if(!in_array('jr_title',$indexes)) {

	$Model->query("ALTER TABLE `#__content` ADD INDEX  `jr_title` (  `title` )");

	usleep(1000000);
}

if(!in_array('jr_listing_count',$indexes)) {

	$Model->query("ALTER TABLE `#__content` ADD INDEX `jr_listing_count` ( `catid` , `state` , `access` , `publish_up` , `publish_down` )");

	usleep(1000000);
}

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
if(!in_array('jr_parent_id',$indexes)) {

	$Model->query("ALTER TABLE `#__categories` ADD INDEX  `jr_parent_id` ( `parent_id` )");

	usleep(500000);
}

