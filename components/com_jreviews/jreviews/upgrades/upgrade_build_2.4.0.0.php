<?php
defined( 'MVC_FRAMEWORK') or die;

# Add media columns to comments table
$query = "
  SELECT
    count(*)
  FROM
    information_schema.COLUMNS
  WHERE
    TABLE_SCHEMA='{$dbname}' AND TABLE_NAME='{$dbprefix}jreviews_comments'
    AND COLUMN_NAME='media_count'";

$exists = $Model->query($query,'loadResult');

if(!$exists) {

  $query = "
	ALTER TABLE  `#__jreviews_comments`
	ADD  `media_count` int(10) unsigned NOT NULL DEFAULT '0',
	ADD  `video_count` int(10) unsigned NOT NULL DEFAULT '0',
	ADD  `photo_count` int(10) unsigned NOT NULL DEFAULT '0',
	ADD  `audio_count` int(10) unsigned NOT NULL DEFAULT '0',
	ADD  `attachment_count` int(10) unsigned NOT NULL DEFAULT '0';
  ";

  $Model->query($query);
}

# Add media columns to listings totals table
$query = "
  SELECT
    count(*)
  FROM
    information_schema.COLUMNS
  WHERE
    TABLE_SCHEMA='{$dbname}' AND TABLE_NAME='{$dbprefix}jreviews_listing_totals'
    AND COLUMN_NAME='media_count'";

$exists = $Model->query($query,'loadResult');

if(!$exists)
{
  $query = "
	ALTER TABLE  `#__jreviews_listing_totals`
	ADD  `media_count` int(10) unsigned NOT NULL DEFAULT '0',
	ADD  `video_count` int(10) unsigned NOT NULL DEFAULT '0',
	ADD  `photo_count` int(10) unsigned NOT NULL DEFAULT '0',
	ADD  `audio_count` int(10) unsigned NOT NULL DEFAULT '0',
	ADD  `attachment_count` int(10) unsigned NOT NULL DEFAULT '0';
  ";

  $Model->query($query);
}

# Add media_id column to reports table
$query = "
  SELECT
    count(*)
  FROM
    information_schema.COLUMNS
  WHERE
    TABLE_SCHEMA='{$dbname}' AND TABLE_NAME='{$dbprefix}jreviews_reports'
    AND COLUMN_NAME='media_id'";

$exists = $Model->query($query,'loadResult');

if(!$exists) {

  $query = "
	ALTER TABLE  `#__jreviews_reports`
	ADD  `media_id` int(11) unsigned NOT NULL DEFAULT '0' AFTER `review_id`;
  ";

  $Model->query($query);
}

# Add ipaddress column to content table
$query = "
  SELECT
    count(*)
  FROM
    information_schema.COLUMNS
  WHERE
    TABLE_SCHEMA='{$dbname}' AND TABLE_NAME='{$dbprefix}jreviews_content'
    AND COLUMN_NAME='ipaddress'";

$exists = $Model->query($query,'loadResult');

if(!$exists) {

  $query = "
	ALTER TABLE `#__jreviews_content`
	ADD `ipaddress` INT(10) UNSIGNED NOT NULL DEFAULT 0  AFTER `email`;
  ";

  $Model->query($query);
}

# Add search column to criteia table
$query = "
  SELECT
    count(*)
  FROM
    information_schema.COLUMNS
  WHERE
    TABLE_SCHEMA='{$dbname}' AND TABLE_NAME='{$dbprefix}jreviews_criteria'
    AND COLUMN_NAME='search'";

$exists = $Model->query($query,'loadResult');

if(!$exists) {

  $query = "
	ALTER TABLE `#__jreviews_criteria`
	ADD `search` tinyint(1) NOT NULL DEFAULT '1';
  ";

  $Model->query($query);
}

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

$indexes = $Model->query($query,'loadColumn');

// Now create new indexes
if(!in_array('media_count',$indexes))
{
	$queries = array(
		"ALTER TABLE `#__jreviews_listing_totals` ADD INDEX  `media_count` (`media_count`);",
		"ALTER TABLE `#__jreviews_listing_totals` ADD INDEX  `video_count` (`video_count`);",
		"ALTER TABLE `#__jreviews_listing_totals` ADD INDEX  `photo_count` (`photo_count`);",
		"ALTER TABLE `#__jreviews_listing_totals` ADD INDEX  `audio_count` (`audio_count`);",
		"ALTER TABLE `#__jreviews_listing_totals` ADD INDEX  `attachment_count` (`attachment_count`);"
	);

	foreach($queries AS $query)
	{
  		$Model->query($query);

		usleep(500000);
	}
}

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
		table_name = '". str_replace('#__',$dbprefix,'#__jreviews_comments') ."'
";

$indexes = $Model->query($query,'loadColumn');

// Now create new indexes
if(!in_array('media_count',$indexes)) {
	$queries = array(
		"ALTER TABLE `#__jreviews_comments` ADD INDEX  `media_count` (`media_count`);",
		"ALTER TABLE `#__jreviews_comments` ADD INDEX  `video_count` (`video_count`);",
		"ALTER TABLE `#__jreviews_comments` ADD INDEX  `photo_count` (`photo_count`);",
		"ALTER TABLE `#__jreviews_comments` ADD INDEX  `audio_count` (`audio_count`);",
		"ALTER TABLE `#__jreviews_comments` ADD INDEX  `attachment_count` (`attachment_count`);"
	);

	foreach($queries AS $query)
	{
  		$Model->query($query);

		usleep(500000);
	}
}

