<?php
/**
 * Core Design Glossary plugin for Joomla! 2.5
 * @author		Daniel Rataj, <info@greatjoomla.com>
 * @package		Joomla
 * @subpackage	Content
 * @category   	Plugin
 * @version		2.5.x.1.0.2
 * @copyright	Copyright (C) 2007 - 2012 Great Joomla!, http://www.greatjoomla.com
 * @license		http://www.gnu.org/copyleft/gpl.html GNU/GPL 3
 * 
 * This file is part of Great Joomla! extension.   
 * This extension is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This extension is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

// no direct access
defined('_JEXEC') or die;

/**
 * Get Joomla! articles
 */
class PlgContentCDGlossaryComContent
{
	private	$catid 			= 	array();
	private $plg_params		=	null;
	
	/**
	 * Constructor
	 * 
	 * @param 	array			$catid
	 * @param 	object			$plg_params
	 * @param 	string			$overrides
	 */
	public function __construct($catid = array(), $plg_params = null)
	{
		$this->catid 		= $catid;
		$this->plg_params 	= $plg_params;
	}
	
	/**
	 * Get Instance
	 * 
	 * @param	array		$catid
	 * @param	object		$plg_params
	 * @param 	string		$overrides
	 * @return 	instance
	 */
	public static function getInstance($catid = array(), $plg_params = null)
	{
		static $instance;
		if ($instance == null)
		{
			$instance = new PlgContentCDGlossaryComContent($catid, $plg_params);
		}
		
		return $instance;
	}
	
	/**
	 * Get Articles
	 * @return array
	 */
	public function getArticles()
	{
		$articles_instance = array();
		
		if ($this->catid and is_array($this->catid))
		{
			$app = JFactory::getApplication();
			$user = JFactory::getUser();
			
			JModel::addIncludePath(dirname(dirname (__FILE__)) . DS . 'models');
			
			require_once JPATH_SITE . DS . 'components' . DS . 'com_content' . DS . 'helpers' . DS . 'route.php';
			
			$model = JModel::getInstance('Articles', 'PlgContentCDGlossaryContentModel', array('ignore_request' => true));
			
			foreach($this->catid as $category)
			{
				if (! $category) continue; // prevent non-existing category
		
				$params = $app->getParams();
				$model->setState('params', $params);
		
				if ((!$user->authorise('core.edit.state', 'com_content')) &&  (!$user->authorise('core.edit', 'com_content')))
				{
					// filter on published for those who do not have edit or edit.state rights.
					$model->setState('filter.published', 1);
				}
		
				$model->setState('filter.language', $app->getLanguageFilter());
		
				// process show_noauth parameter
				if (!$params->get('show_noauth'))
				{
					$model->setState('filter.access', true);
				}
				else {
					$model->setState('filter.access', false);
				}
		
				$model->setState('filter.category_id', $category);
				$model->setState('list.ordering', ContentHelperQuery::orderbySecondary($this->plg_params->get('orderby_sec', '')));
				$model->setState('list.direction', ''); // must be empty to get list.ordering value in correct format
				
				$articles = $model->getItems();
				
				foreach($articles as $article)
				{
					$tmp_article = new stdClass();
					$tmp_article->title = $article->title;
					
					// aliases - introtext
					$tmp_article->aliases = array();
					$tmp_article->aliases []= $article->title;
					
					if (JString::strpos($article->introtext, '{aliases') !== false)
					{
						preg_match('#{aliases\s(.*?)}#', $article->introtext, $aliases);
						
						$tmp_article->aliases = array_merge($tmp_article->aliases, explode('|', $aliases[1]));
					}
					
					$tmp_article->description = $article->introtext;
					
					$tmp_article->readmore = '';
					
					// read more if fulltext
					if ($article->fulltext)
					{
						$tmp_article->readmore = JRoute::_(ContentHelperRoute::getArticleRoute($article->slug, $article->catid));
					}
					
					$articles_instance[] = $tmp_article;
				}
				
			}
		}
		
		return $articles_instance;
	}
}
?>