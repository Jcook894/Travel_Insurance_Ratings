<?php
/**
 * JReviews - Reviews Extension
 * Copyright (C) 2010-2015 ClickFWD LLC
 * This is not free software, do not distribute it.
 * For licencing information visit https://www.jreviews.com
 * or contact sales@jreviews.com
**/

defined( 'MVC_FRAMEWORK') or die( 'Direct Access to this location is not allowed.' );

class AdminInstallerComponent {

	protected $controller;

	function startup($controller)
	{
		$this->controller = $controller;
	}

    function check()
    {
		$Model = new S2Model;

        $tables = $Model->getTableList('_jreviews_');

        $prefix = cmsFramework::getConfig('dbprefix');

        if (!in_array($prefix.'jreviews_config', $tables)
        	|| !in_array($prefix.'jreviews_categories', $tables)
        	|| !in_array($prefix.'jreviews_fields', $tables)
        	|| !in_array($prefix.'jreviews_groups', $tables)
        	)
        {
	        $newVersion = cmsFramework::getAppVersion('jreviews');

	        $this->controller->AdminPackages->runUpgradesFiles('jreviews', 'nonexistenttable', PATH_APP, 0, $newVersion);

	        if(_CMS_NAME == 'joomla')
	        {
	            // Install additional packages

	            AdminPackagesComponent::installPackages(PATH_APP . DS . 'cms_compat' . DS . _CMS_NAME);
	        }
        }
    }
}