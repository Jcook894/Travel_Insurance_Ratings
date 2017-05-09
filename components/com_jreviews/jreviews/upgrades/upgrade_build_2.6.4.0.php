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

	if(in_array('jr_state', $indexes))
	{
		$Model->query('DROP INDEX `jr_state` ON #__content');
	}

	$query = "
		ALTER TABLE `#__content` ADD INDEX `jr_state` ( `state`, `access`, `publish_up`, `publish_down` );
	";

	$Model->query($query);
}
