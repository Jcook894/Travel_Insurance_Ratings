<?php
// no direct access
defined('_JEXEC') or die;

jimport('joomla.plugin.plugin');

class PlgSystemJreviews_addonmenus extends JPlugin
{
	var $addonViews = array();

	public static function prx() {

		$vars = func_get_args();

		foreach($vars AS $var)
		{
			echo "<pre>";
			print_r($var);
			echo "</pre>";
		}
	}

	public function onAfterRoute()
	{
        $version = new JVersion();

        $folder = (int) $version->RELEASE >= 3 ? 'j3' : 'j2';

		if(JFactory::getApplication()->isAdmin() == true
			&& JRequest::getCMD('option') == 'com_menus'
			&& is_dir(JPATH_SITE . '/components/com_jreviews') && is_dir(JPATH_SITE . '/components/com_jreviews_addons'))
		{
			$lang = JFactory::getLanguage();

			if(file_exists(dirname(__FILE__) . '/models/' . $folder . '/menutypes.php'))
			{
				require_once(dirname(__FILE__) . '/models/' . $folder . '/menutypes.php');
			}

			if(file_exists(dirname(__FILE__) . '/models/' . $folder . '/item.php'))
			{
				require_once(dirname(__FILE__) . '/models/' . $folder . '/item.php');
			}

			$views = JFolder::files(JPATH_SITE . '/components/com_jreviews_addons', 'default.xml$', true, true);

			foreach($views AS $key=>$path)
			{
				if(strstr($path,'_bak')) unset($views[$key]);

				if(preg_match('#com_jreviews_addons/(?<addon>.*)/cms_compat/joomla/menus/views/(?<view>.*)/tmpl#',$path,$matches))
				{
					$lang->load('com_jreviews.sys', JPATH_SITE . '/components/com_jreviews_addons/'.$matches['addon'].'/cms_compat/joomla/menus', null, false, false)
						|| $lang->load('com_jreviews.sys', JPATH_SITE . '/components/com_jreviews_addons/'.$matches['addon'].'/cms_compat/joomla/menus', $lang->getDefault(), false, false);

					$this->addonViews[$matches['view']] = $path;
				}
			}
		}
	}

	public function onAfterGetMenuTypeOptions(&$list, /*MenusModelMenutypes */ $MenuTypes)
	{
		if(is_dir(JPATH_SITE . '/components/com_jreviews') && is_dir(JPATH_SITE . '/components/com_jreviews_addons'))
		{
			$lang = JFactory::getLanguage();

			$paths = JFolder::files(JPATH_SITE . '/components/com_jreviews_addons', 'metadata.xml$', true, true);

			if(!empty($paths))
			{
				foreach($paths AS $path)
				{
					if(strstr($path, '_bak')) continue;

					if(!preg_match('#com_jreviews_addons/(?<addon>.*)/cms_compat/joomla/menus/metadata\.xml#',$path,$matches)) continue;

					$options = $MenuTypes->getTypeOptionsFromXML($path, 'com_jreviews');

					foreach($options AS $option)
					{
						$list['com_jreviews'][] = $option;

						if (isset($option->request)) {

							$MenuTypes->rlu[MenusHelper::getLinkKey($option->request)] = $option->get('title');

							if (isset($option->request['option'])) {
									$lang->load($option->request['option'].'.sys', JPATH_SITE . '/components/com_jreviews_addons/'.$matches['addon'].'/cms_compat/joomla/menus/languages/', null, false, false)
									|| $lang->load($option->request['option'].'.sys', JPATH_SITE . '/components/com_jreviews_addons/'.$matches['addon'].'/cms_compat/joomla/menus/languages/', $lang->getDefault(), false, false);
							}
						}
					}
				}
			}
		}
	}

	public function onAfterGetMenuFormViewPath(&$path, $option, $view, /*MenusModelItem */ $MenuItem)
	{
		if($option == 'com_jreviews' && isset($this->addonViews[$view]))
		{
			$path = $this->addonViews[$view];
		}
	}
}