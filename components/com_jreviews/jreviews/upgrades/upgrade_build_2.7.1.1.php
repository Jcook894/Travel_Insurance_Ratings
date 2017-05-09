<?php
defined( 'MVC_FRAMEWORK') or die;

$Model = new S2Model;

$db_name = cmsFramework::getConfig('db');

$db_prefix = cmsFramework::getConfig('dbprefix');

# Add related field columns to fields table
$query = "
  SELECT
    count(*)
  FROM
    information_schema.COLUMNS
  WHERE
    TABLE_SCHEMA='{$dbname}' AND TABLE_NAME='{$dbprefix}jreviews_media'
    AND COLUMN_NAME='media_function'";

$exists = $Model->query($query,'loadResult');

if(!$exists)
{

  $query = "
	ALTER TABLE `#__jreviews_media`
	ADD COLUMN `media_function` enum('cover','logo') DEFAULT NULL AFTER `media_type`
  ";

  $Model->query($query);
}

