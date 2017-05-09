<?php
/**
 * @version       1.0 March 1, 2017
 * @author        ClickFWD https://www.jreviews.com
 * @copyright     Copyright (C) 2010 - 2017 ClickFWD LLC. All rights reserved.
 * @license       Proprietary
 *
 */

defined('_JEXEC') or die;

jimport('joomla.plugin.plugin');

class PlgSystemJreviews_articleoverrides extends JPlugin
{
	public function onAfterRoute()
	{
		$app = JFactory::getApplication();
		$doc = JFactory::getDocument();
		$option = JRequest::getCMD('option');
		$view = JRequest::getCMD('view');
		$catId = JRequest::getInt('catid');

		if (!$app->isAdmin() && $option == 'com_content' && $view == 'article' && $catId > 0) {

			$db = JFactory::getDBO();
			$tables = $db->getTableList();

			// Check if #__jreviews_categories table exists
			$table_name = str_replace('#__', $db->getPrefix(), '#__jreviews_categories');

			if (in_array($table_name, $tables)) {

				$catIds = $db->setQuery("SELECT id from #__jreviews_categories")->loadColumn();

				if (in_array($catId, $catIds)) {

					JLoader::register('ContentViewArticle', JPATH_SITE . '/plugins/system/jreviews_articleoverrides/views/view.html.php', true);

				}

			}
		}
	}
}