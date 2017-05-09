<?php
defined( 'MVC_FRAMEWORK') or die;

/**
 * When  upgrading from any version lower than 2.4.13.1 automatically discover and install new add-ons
 */

if(AdminPackagesComponent::paddedVersion($current) < AdminPackagesComponent::paddedVersion('2.4.13.1'))
{
  $Folder = new S2Folder(PATH_APP_ADDONS);

  $contents = $Folder->read(true, true, true);

  if(isset($contents[0]))
  {
    foreach($contents[0] AS $path)
    {
      AdminPackagesComponent::installAddon($path);
    }
  }
}

// Drop old indexes #__jreviews_comments

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

if(in_array('created', $indexes))
{
	$Model->query('DROP INDEX `created` ON #__jreviews_comments');
}

if(in_array('modified', $indexes))
{
	$Model->query('DROP INDEX `modified` ON #__jreviews_comments');
}

if(in_array('userid', $indexes))
{
	$Model->query('DROP INDEX `userid` ON #__jreviews_comments');
}

if(in_array('published', $indexes))
{
	$Model->query('DROP INDEX `published` ON #__jreviews_comments');
}

if(in_array('votes_helpful', $indexes))
{
	$Model->query('DROP INDEX `votes_helpful` ON #__jreviews_comments');
}

if(in_array('votes', $indexes))
{
	$Model->query('DROP INDEX `votes` ON #__jreviews_comments');
}

if(in_array('posts', $indexes))
{
	$Model->query('DROP INDEX `posts` ON #__jreviews_comments');
}

if(in_array('media_count', $indexes))
{
	$Model->query('DROP INDEX `media_count` ON #__jreviews_comments');
}

if(in_array('photo_count', $indexes))
{
	$Model->query('DROP INDEX `photo_count` ON #__jreviews_comments');
}

if(in_array('video_count', $indexes))
{
	$Model->query('DROP INDEX `video_count` ON #__jreviews_comments');
}

if(in_array('audio_count', $indexes))
{
	$Model->query('DROP INDEX `audio_count` ON #__jreviews_comments');
}

if(in_array('attachment_count', $indexes))
{
	$Model->query('DROP INDEX `attachment_count` ON #__jreviews_comments');
}

// Create new indexes

$Model->query('ALTER TABLE `#__jreviews_comments` ADD INDEX  `created` (`created`, `published`, `mode`, `author`)');

$Model->query('ALTER TABLE `#__jreviews_comments` ADD INDEX  `modified` (`modified`,`created`, `published`, `mode`, `author`)');

$Model->query('ALTER TABLE `#__jreviews_comments` ADD INDEX  `userid` (`userid`, `published`, `created`, `mode`, `author`)');

$Model->query('ALTER TABLE `#__jreviews_comments` ADD INDEX  `published` (`published`, `created`, `mode`, `author`)');

$Model->query('ALTER TABLE `#__jreviews_comments` ADD INDEX  `votes_helpful` (`vote_helpful`, `published`, `created`, `mode`, `author`)');

$Model->query('ALTER TABLE `#__jreviews_comments` ADD INDEX  `votes` (`vote_total`, `vote_helpful`, `created`,`mode`, `published`, `author`)');

$Model->query('ALTER TABLE `#__jreviews_comments` ADD INDEX  `posts` (`posts`, `published`, `created`, `mode`, `author`)');

$Model->query('ALTER TABLE `#__jreviews_comments` ADD INDEX  `media_count` (`media_count`, `published`, `created`, `mode`, `author`)');

$Model->query('ALTER TABLE `#__jreviews_comments` ADD INDEX  `video_count` (`video_count`, `published`, `created`, `mode`, `author`)');

$Model->query('ALTER TABLE `#__jreviews_comments` ADD INDEX  `photo_count` (`photo_count`, `published`, `created`, `mode`, `author`)');

$Model->query('ALTER TABLE `#__jreviews_comments` ADD INDEX  `audio_count` (`audio_count`, `published`, `created`, `mode`, `author`)');

$Model->query('ALTER TABLE `#__jreviews_comments` ADD INDEX  `attachment_count` (`attachment_count`, `published`, `created`, `mode`, `author`)');

// Drop old indexes #__content

if( _CMS_NAME == 'joomla')
{
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

	if(in_array('jr_modified', $indexes))
	{
		$Model->query('DROP INDEX `jr_modified` ON #__content');
	}

	$Model->query('ALTER TABLE `#__content` ADD INDEX  `jr_modified` (`modified`, `created`)');
}