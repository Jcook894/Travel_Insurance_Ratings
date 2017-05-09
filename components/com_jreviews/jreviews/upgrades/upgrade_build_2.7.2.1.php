<?php
defined( 'MVC_FRAMEWORK') or die;

$Model = new S2Model;

$query = "ALTER TABLE `#__jreviews_fields` CHANGE `access` `access` TEXT;";

$Model->query($query);

$query = "ALTER TABLE `#__jreviews_fields` CHANGE `access_view` `access_view` TEXT;";

$Model->query($query);
