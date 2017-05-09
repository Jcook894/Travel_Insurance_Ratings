<?php
defined( 'MVC_FRAMEWORK') or die;

$Model = new S2Model;

$dbname = cmsFramework::getConfig('db');

$dbprefix = cmsFramework::getConfig('dbprefix');

$query = "
  SELECT
    count(*)
  FROM
    information_schema.COLUMNS
  WHERE
    TABLE_SCHEMA='{$dbname}' AND TABLE_NAME='{$dbprefix}jreviews_fieldoptions'
    AND COLUMN_NAME='default'";

$exists = $Model->query($query,'loadResult');

if(!$exists)
{

  $query = "
	ALTER TABLE #__jreviews_fieldoptions ADD COLUMN `default` TINYINT(1) UNSIGNED NOT NULL DEFAULT 0 AFTER `control_value`
  ";

  $Model->query($query);
}

$query = "
  SELECT
    count(*)
  FROM
    information_schema.COLUMNS
  WHERE
    TABLE_SCHEMA='{$dbname}' AND TABLE_NAME='{$dbprefix}jreviews_fieldoptions'
    AND COLUMN_NAME='description'";

$exists = $Model->query($query,'loadResult');

if(!$exists)
{

  $query = "
	ALTER TABLE #__jreviews_fieldoptions ADD COLUMN `description` TEXT NOT NULL AFTER `default`;
  ";

  $Model->query($query);
}

$query = "
	SELECT
		index_name
	FROM
		information_schema.statistics
	WHERE
		table_schema = '". $dbname ."'
		AND
		table_name = '". str_replace('#__',$dbprefix,'#__jreviews_fieldoptions') ."'
";

$indexes = $Model->query($query,'loadColumn');

# Add core table indexes for JReviews

if(!in_array('default',$indexes)) {

	$Model->query("ALTER TABLE `#__jreviews_fieldoptions` ADD INDEX  `default` (  `default` )");

	usleep(1000000);
}
