<?php
defined( 'MVC_FRAMEWORK') or die;

# Add related field columns to fields table
$query = "
  SELECT
    count(*)
  FROM
    information_schema.COLUMNS
  WHERE
    TABLE_SCHEMA='{$dbname}' AND TABLE_NAME='{$dbprefix}jreviews_categories'
    AND COLUMN_NAME='page_title'";

$exists = $Model->query($query,'loadResult');

if(!$exists)
{

  $query = "
	ALTER TABLE `#__jreviews_categories`
	ADD COLUMN `page_title` varchar(255) NOT NULL,
	ADD COLUMN `title_override` tinyint(1) UNSIGNED NOT NULL DEFAULT 0,
	ADD COLUMN `desc_override` tinyint(1) UNSIGNED NOT NULL DEFAULT 0;
  ";

  $Model->query($query);
}

