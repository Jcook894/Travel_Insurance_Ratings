<?php
defined( 'MVC_FRAMEWORK') or die;

if(_CMS_NAME == 'joomla')
{
	$Model = new S2Model;

	$db_name = cmsFramework::getConfig('db');

	$db_prefix = cmsFramework::getConfig('dbprefix');

	// Drop old indexes #__content

	$query = "
		SELECT
			index_name
		FROM
			information_schema.statistics
		WHERE
			table_schema = '". $db_name ."'
			AND
			table_name = '". str_replace('#__',$db_prefix,'#__content') ."'
	";

	$indexes = $Model->query($query, 'loadColumn');

	if(in_array('jr_title', $indexes))
	{
		$Model->query('DROP INDEX `jr_title` ON #__content');
	}

	$query = "
		ALTER TABLE `#__content` ADD INDEX `jr_title` ( `title`);
	";

	$Model->query($query);
}
