<?php
defined( 'MVC_FRAMEWORK') or die;

/**
 * Modify and add indexes for #__jreviews_comments
 */
// Create copy of content table in case something goes wrong
$query = "
	DROP TABLE IF EXISTS `#__jreviews_comments_copy`;
";

$Model->query($query);

$query = "
	CREATE TABLE `#__jreviews_comments_copy` LIKE `#__jreviews_comments`;
";

$Model->query($query);

$query = "
	INSERT `#__jreviews_comments_copy` SELECT * FROM `#__jreviews_comments`;
";

$Model->query($query);

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

// First modified existing indexes
if(in_array('created',$indexes))
{
	$query = "
		DROP INDEX `created` ON #__jreviews_comments;
	";

	$Model->query($query);

	$query = "
		ALTER TABLE `#__jreviews_comments` ADD INDEX  `created` ( `pid`, `created` );
	";

	$Model->query($query);

	usleep(500000);
}

if(in_array('modified',$indexes))
{
	$query = "
		DROP INDEX `modified` ON #__jreviews_comments;
	";

	$Model->query($query);

	$query = "
		ALTER TABLE `#__jreviews_comments` ADD INDEX  `modified` ( `pid`, `modified`, `created` );
	";

	$Model->query($query);

	usleep(500000);
}

if(in_array('userid',$indexes))
{
	$query = "
		DROP INDEX `userid` ON #__jreviews_comments;
	";

	$Model->query($query);

	$query = "
		ALTER TABLE `#__jreviews_comments` ADD INDEX  `userid` ( `userid`, `published` );
	";

	$Model->query($query);

	usleep(500000);
}

// Now create new indexes
if(!in_array('votes_helpful',$indexes))
{
	$query = "
		ALTER TABLE `#__jreviews_comments` ADD INDEX  `votes_helpful` ( `pid`,`vote_helpful` );
	";

	$Model->query($query);

	usleep(500000);
}

if(!in_array('votes',$indexes))
{
	$query = "
		ALTER TABLE `#__jreviews_comments` ADD INDEX  `votes` ( `userid` ,  `published` ,  `vote_helpful` ,  `vote_total` );
	";

	$Model->query($query);

	usleep(500000);
}

if(!in_array('posts',$indexes))
{
	$query = "
		ALTER TABLE `#__jreviews_comments` ADD INDEX  `posts` ( `pid`,`posts` );
	";

	$Model->query($query);

	usleep(500000);
}

/**
 * Only for Joomla - Add #__content indexes, first backup table
 */

if(_CMS_NAME == 'joomla')
{
	// Create copy of content table in case something goes wrong
	$query = "
		DROP TABLE IF EXISTS `#__content_copy`;
	";

	$Model->query($query);

	$query = "
		CREATE TABLE `#__content_copy` LIKE `#__content`;
	";

	$Model->query($query);

	$query = "
		INSERT `#__content_copy` SELECT * FROM `#__content`;
	";

	$Model->query($query);

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
	if(!in_array('jr_created',$indexes))
	{
		$query = "ALTER TABLE `#__content` ADD INDEX  `jr_created` (  `created` );";

		$Model->query($query);

		usleep(1000000);
	}

	if(!in_array('jr_modified',$indexes))
	{
		$query = "ALTER TABLE `#__content` ADD INDEX  `jr_modified` ( `modified`, `created` );";

		$Model->query($query);

		usleep(1000000);
	}

	if(!in_array('jr_hits',$indexes))
	{
		$query = "ALTER TABLE `#__content` ADD INDEX  `jr_hits` (  `hits` );";

		$Model->query($query);

		usleep(1000000);
	}

	if(!in_array('jr_ordering',$indexes))
	{
		$query = "ALTER TABLE `#__content` ADD INDEX  `jr_ordering` (  `ordering` );";

		$Model->query($query);

		usleep(1000000);
	}

	if(!in_array('jr_title',$indexes))
	{
		$query = "ALTER TABLE `#__content` ADD INDEX  `jr_title` (  `title` ( 10 ) );";

		$Model->query($query);

		usleep(1000000);
	}

	if(!in_array('jr_listing_count',$indexes))
	{
		$query = "ALTER TABLE `#__content` ADD INDEX `jr_listing_count` ( `catid` , `state` , `access` , `publish_up` , `publish_down` );";

		$Model->query($query);

		usleep(1000000);
	}
}