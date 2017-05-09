<?php
/**
 * jReviews - Reviews Extension
 * Copyright (C) 2010-2015 ClickFWD LLC
 * This is not free software, do not distribute it.
 * For licencing information visit https://www.jreviews.com
 * or contact sales@jreviews.com
**/

defined('_JEXEC') or die;

abstract class JreviewsModuleHelper
{
    static function getArrVal($array, $key, $default = null)
    {
        if(isset($array[$key])) {

            return $array[$key];
        }

        return $default;
    }

	public static function renderModule($params)
	{
		require_once JPATH_SITE . '/components/com_jreviews/jreviews/framework.php';

		$jparams = array();

		$jparams['module'] = $params->toArray();

		$jparams['module_id'] = $params->get('module_id');

		$jparams[S2_QVAR_PAGE] = 1;

		$jparams['data']['module'] = true;

		$jparams['data']['controller'] = $params->get('controller');

		$jparams['data']['action'] = $params->get('action','index');

		$jparams['secret'] = cmsFramework::getConfig('secret');

		// Ensure that module output is not marked as 'requested' because it prevents CSS and Javascript dependencies from loading

		$jparams['requested'] = 0;

		$jparams['token'] = cmsFramework::formIntegrityToken($jparams,array('module','module_id','form','data'),false);

		$Dispatcher = new S2Dispatcher('jreviews');

		$output = $Dispatcher->dispatch($jparams);

		unset($Dispatcher);

		return $output;
	}

	public static function getCacheID($module, $params, $salt_keys = array('url','cat','criteria','dir'))
	{
		$default_salt_keys = array('view_levels','module_type','module_id','module_params','extension','option');

		$salt_keys = array_merge($default_salt_keys, $salt_keys);

		$User = JFactory::getUser();

		/**
		 * First get all possible params from the active menu
		 */
        $app  = JApplication::getInstance('site');

        $menu = $app->getMenu()->getActive();

		$id = $cat = $criteria = $dir = $extension = null;

		if($menu)
		{
			$option = $menu->query['option'];

			switch($option)
			{
				case 'com_jreviews':

					$cat = $menu->params->get('catid');

					!$cat and $criteria = $menu->params->get('criteriaid');

					!$cat and !$criteria and $dir = $menu->params->get('dirid');

					$extension = $menu->params->get('extension');

				break;
			}
		}

		/**
		 * Get any additional params from the request and override the menu params
		 */

		$JInput = JFactory::getApplication()->input;

		$module_type = $module->module;

		$module_id = $module->id;

		$module_params = $params->toArray();

		$view_levels = $User->getAuthorisedViewLevels();

		$Itemid = $JInput->get('Itemid');

		$url = $JInput->get('url');

		// $view = $JInput->get('view');

		$option = $JInput->get('option');

		$id = $JInput->get('id');

		$cat = $JInput->get('cat', $cat);

		$extension = $JInput->get('extension', $extension);

		!$cat and $cat = $JInput->get('catid');

		!$cat and $criteria = $JInput->get('criteria', $criteria);

		!$cat and !$criteria and $dir = $JInput->get('dir', $dir);

		/**
		 * To avoid redundant caching, check if the auto detect setting exists
		 * If exists and disabled, unset the dynamic variables so only one instance is cached
		 */

		if(isset($module_params['cat_auto'])
			&& (!$module_params['cat_auto'] || ($module_params['cat_auto'] == 1 && self::getArrVal($module_params,'extension') == ''))
		) {
			$salt_keys = array_diff($salt_keys, array('url','cat','criteria','dir'));
		}

		$salt = compact($salt_keys);

		$cacheid = md5(serialize($salt));

		return $cacheid;
	}
}