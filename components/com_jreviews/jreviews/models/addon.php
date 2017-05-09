<?php
/**
 * JReviews - Reviews Extension
 * Copyright (C) 2010-2015 ClickFWD LLC
 * This is not free software, do not distribute it.
 * For licencing information visit https://www.jreviews.com
 * or contact sales@jreviews.com
**/

defined( 'MVC_FRAMEWORK') or die( 'Direct Access to this location is not allowed.' );

class AddonModel extends MyModel  {

	var $name = 'Addon';

	var $useTable = '#__jreviews_addons AS Addon';

	var $primaryKey = 'Addon.id';

	var $realKey = 'id';

	function afterFind($results)
	{
		if(empty($results)) return $results;

		$output = array();

		foreach($results AS $key=>$row)
		{
			$output[$row['Addon']['name']] = $row;

			if(isset($row['Addon']['manifest']))
			{
				$output[$row['Addon']['name']]['Addon']['manifest'] = json_decode($row['Addon']['manifest'],true);
			}
		}

		return $output;
	}

	function afterSave($status)
	{
		if($status)
		{
			clearCache('', 'core');

			$name = Sanitize::getString($this->data['Addon'],'name');

			// Using S2App::import here doesn't work because the registry has not been updated at this point

			$install_controller = PATH_APP_ADDONS . DS . $name . DS . 'admin_controllers' . DS . 'admin_' . $name . '_install_controller.php';

			if(file_exists($install_controller))
			{
				require_once($install_controller);

	            $class = Inflector::camelize('admin_' . $name . '_install_controller');

	            $AddonInstaller = new $class('jreviews');

	            $AddonInstaller->__initComponents();

	            $response = $AddonInstaller->install();

	            return $response;
			}
		}
	}

	function beforeDelete($key, $values, $condition)
	{
		// Remove the add-on folder

		foreach($values AS $val)
		{
			$name = $this->findOne(array('fields'=>array('name'),'conditions'=>array('id = ' . (int) $val)));

			if($name == '') continue;

			$path = PATH_APP_ADDONS . DS . $name;

	        // Run uninstaller
	        if(S2App::import('AdminController','admin_' . $name . '_install','jreviews'))
	        {
	            $class = Inflector::camelize('admin_' . $name . '_install_controller');

	            $AddonInstaller = new $class('jreviews');

	            $AddonInstaller->__initComponents();

	            if(method_exists($AddonInstaller, 'uninstall'))
	            {
	            	$AddonInstaller->uninstall();
	            }
	        }

			$Folder = new s2Folder();

			$Folder->delete($path);
		}
	}

	function afterDelete($key, $values, $condition)
	{
        clearCache(S2CacheKey('jreviews_paths'), 'core', '');
	}
}
