<?php
/**
 * JReviews - Reviews Extension
 * Copyright (C) 2010-2015 ClickFWD LLC
 * This is not free software, do not distribute it.
 * For licencing information visit https://www.jreviews.com
 * or contact sales@jreviews.com
**/

// no direct access
defined( 'MVC_FRAMEWORK') or die( 'Direct Access to this location is not allowed.' );

class ConfigurationController extends MyController {

	var $helpers = array('admin/admin_settings','html','form','jreviews');

	var $components = array('config');

	var $autoRender = false;

	var $autoLayout = false;

	function beforeFilter()
    {
		# Call beforeFilter of MyAdminController parent class
		parent::beforeFilter();
	}

	function index() {

	    $this->name = 'configuration';

	    // Generate list of available themes

        $themes_config = $themes_fallback = array();

        $Configure = Configure::getInstance('jreviews');

        $App = S2App::getInstance('jreviews');

        $ThemeArray = $App->jreviewsPaths['Theme'];

        foreach($ThemeArray AS $theme_name=>$files)
        {
            if(!isset($themes_config[$theme_name]) && isset($files['.info']) && $files['.info']['configuration'] == 1/* && $files['.info']['mobile'] == 0*/)
                $themes_config[$theme_name] = $files['.info']['title'] . ' (' . $files['.info']['location'] . ')';

            if(!isset($themes_mobile[$theme_name]) && isset($files['.info']) && $files['.info']['configuration'] == 1/* && $files['.info']['mobile'] == 1*/)
                $themes_mobile[$theme_name] = $files['.info']['title'] . ' (' . $files['.info']['location'] . ')';

            if(!isset($themes_fallback[$theme_name]) && isset($files['.info']) && $files['.info']['fallback'] == 1)
                $themes_fallback[$theme_name] = $files['.info']['title'] . ' (' . $files['.info']['location'] . ')';

            if($files['.info']['mobile'] == 1) {
                $themes_config[$theme_name] = $themes_config[$theme_name] . ' -mobile';
                $themes_mobile[$theme_name] = $themes_mobile[$theme_name] . ' -mobile';
            }

            $themes_description[$theme_name] = $files['.info']['description'];
        }

        // Generate list of available profile plugins

        $plugins_list = array();

        $registry = $App->jreviewsPaths;

        $plugins = array_keys($registry['Plugin']);

        foreach($plugins AS $plugin)
        {
            $plugin_name = str_replace('.php','',$plugin);

        	$plugin_class = inflector::camelize($plugin_name).'Component';

            S2App::import('Plugin',$plugin_name);

        	$PluginClass = new $plugin_class();

        	if($PluginClass->getPluginType() == 'profile')
        	{
        		$plugins_list[$PluginClass->getPluginName()] = $PluginClass->getPluginTitle();
        	}
        }

        asort($plugins_list);

        if(_CMS_NAME == 'wordpress')
        {
            if($this->Config->content_title_duplicates == 'category')
            {
                $this->Config->content_title_duplicates = 'yes';
            }
        }

		$this->set(
			array(
				'stats'=>$this->stats,
				'version'=>$this->Config->version,
				'Config'=>$this->Config,
				'themes_config'=>$themes_config,
                'themes_mobile'=>empty($themes_mobile) ? array(''=>'No theme available') : $themes_mobile,
                'themes_fallback'=>$themes_fallback,
                'themes_description'=>$themes_description,
                'plugins_list'=>$plugins_list
			)
		);

        return $this->render();

	}

	function _save()
	{
		$formValues = $this->params['form'];

		$formValues['social_sharing_detail'] = Sanitize::getVar($formValues,'social_sharing_detail',array());

		if (isset($formValues['task']) && $formValues['task'] != "access")
		{
			$formValues['rss_title'] = str_replace("'",' ',$formValues['rss_title']);
			$formValues['rss_description'] = str_replace("'",' ',$formValues['rss_description']);;
		}

		// bind it to the table

		$this->Config->bindRequest($formValues);

        $this->Config->security_image = Sanitize::getVar($formValues,'security_image','');

		$this->Config->store();
	}

    function _updateOne()
    {
        $key = Sanitize::getString($this->params, 'key');

        $value = Sanitize::getString($this->params, 'value');

        $data = (object) array($key => $value);

        $this->Config->store($data);
    }
}