<?php
defined( 'MVC_FRAMEWORK') or die;

# Add related field columns to fields table
$query = "
  SELECT
    count(*)
  FROM
    information_schema.COLUMNS
  WHERE
    TABLE_SCHEMA='{$dbname}' AND TABLE_NAME='{$dbprefix}jreviews_fields'
    AND COLUMN_NAME='control_field'";

$exists = $Model->query($query,'loadResult');

if(!$exists) {

  $query = "
    ALTER TABLE  `#__jreviews_fields` ADD  `control_field` VARCHAR( 50 ) NOT NULL ,
    ADD  `control_value` VARCHAR( 255 ) NOT NULL ,
    ADD INDEX ( control_field, control_value );
  ";

  $Model->query($query);
}

# Add related field columns to groups table

$query = "
  SELECT
    count(*)
  FROM
    information_schema.COLUMNS
  WHERE
    TABLE_SCHEMA='{$dbname}' AND TABLE_NAME='{$dbprefix}jreviews_groups'
    AND COLUMN_NAME='control_field'";

$exists = $Model->query($query,'loadResult');

if(!$exists) {

  $query = "
    ALTER TABLE  `#__jreviews_groups` ADD  `control_field` VARCHAR( 50 ) NOT NULL ,
    ADD  `control_value` VARCHAR( 255 ) NOT NULL ,
    ADD INDEX ( control_field, control_value );
  ";

  $Model->query($query);
}

# Add related field columns to fieldoptions table

$query = "
  SELECT
    count(*)
  FROM
    information_schema.COLUMNS
  WHERE
    TABLE_SCHEMA='{$dbname}' AND TABLE_NAME='{$dbprefix}jreviews_fieldoptions'
    AND COLUMN_NAME='control_field'";

$Model->query($query);

$exists = $Model->query($query,'loadResult');

if(!$exists) {

  $query = "
    ALTER TABLE  `#__jreviews_fieldoptions` ADD  `control_field` VARCHAR( 50 ) NOT NULL ,
    ADD  `control_value` VARCHAR( 255 ) NOT NULL ,
    ADD INDEX ( control_field, control_value );
  ";

  $Model->query($query);
}
