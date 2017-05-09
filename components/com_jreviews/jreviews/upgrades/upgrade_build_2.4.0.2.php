<?php
defined( 'MVC_FRAMEWORK') or die;

# Add related field columns to fields table
$query = "
  SELECT
    count(*)
  FROM
    information_schema.COLUMNS
  WHERE
    TABLE_SCHEMA='{$dbname}' AND TABLE_NAME='{$dbprefix}jreviews_listing_totals'
    AND COLUMN_NAME='media_count_user'";

$exists = $Model->query($query,'loadResult');

if(!$exists) {

  $query = "
	ALTER TABLE `#__jreviews_listing_totals`
	ADD COLUMN `media_count_user` INT(10) UNSIGNED NOT NULL DEFAULT 0,
	ADD COLUMN `video_count_user` INT(10) UNSIGNED NOT NULL DEFAULT 0,
	ADD COLUMN `photo_count_user` INT(10) UNSIGNED NOT NULL DEFAULT 0,
	ADD COLUMN `audio_count_user` INT(10) UNSIGNED NOT NULL DEFAULT 0,
	ADD COLUMN `attachment_count_user` INT(10) UNSIGNED NOT NULL DEFAULT 0;
  ";

  $Model->query($query);
}
