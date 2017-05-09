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

// Import library dependencies
jimport('joomla.plugin.plugin');
jimport('joomla.utilities.string');

class PlgContentCdGlossary extends JPlugin
{
	/**
	 * Live path
	 * @var string
	 */
	private $livepath = '';
	
	/**
	 * Array of category ID's
	 * @var array
	 */
	private	$catid = array();
	
	/**
	 * Collection of items
	 * @var array
	 */
	private	$items = array();
	
	/**
	 * Glossary term output template
	 * @var string
	 */
	private $term_tmpl = '';
	
	/**
	 * UI Theme
	 * @var string
	 */
	private	$uitheme = 'ui-lightness';
	
	/**
	 * Scriptegrator class
	 * @var object
	 */
	private $JScriptegrator = null;
	
	/**
	 * Joomla! Application
	 * @var object
	 */
	private $application = null;
	
	/**
	 * Joomla! Application Input
	 * @var object
	 */
	private $input = null;
	
	/**
	 * Constructor
	 *
	 * For php4 compatability we must not use the __constructor as a constructor for plugins
	 * because func_get_args ( void ) returns a copy of all passed arguments NOT references.
	 * This causes problems with cross-referencing necessary for the observer design pattern.
	 *
	 * @param object $subject The object to observe
	 * @param object $params  The object that holds the plugin parameters
	 * @since 1.5
	 */
	public function __construct(&$subject, $config)
	{
		parent::__construct($subject, $config);

		$this->loadLanguage();
		
		$this->livepath = JURI::root(true);
		
		$this->application = JFactory::getApplication();
		$this->input = $this->application->input;
	}
	
	/**
	 * Call onContentPrepare function
	 * Method is called by the view.
	 *
	 * @param	string	The context of the content being passed to the plugin.
	 * @param	object	The content object.  Note $article->text is also available
	 * @param	object	The content params
	 * @param	int		The 'page' number
	 * @since	1.6
	 */
	public function onContentPrepare($context, &$article, &$params, $page = 0)
	{
		// not article
		if($context !== 'com_content.article')
		{
			$article->text = $this->cleanTextOutput($article->text);
			return false;
		}
		
		// disable plugin in article
		if(JString::strpos($article->text, '{!glossary}') !== false)
		{
			$article->text = $this->cleanTextOutput($article->text);
			return false;
		}
		
		$this->catid = $this->params->get('categories', '');
		
		if(!$this->catid or !is_array($this->catid)) return false;
		
		// we're currently browsing the glossary category
		if(in_array($article->catid, $this->catid))
		{
			$article->text = $this->cleanTextOutput($article->text);
			return false;
		}
		
		// enable only in selected categories
		if($enable_in_categories = $this->params->get('enable_in_categories', ''))
		{
			if(is_array($enable_in_categories) and !in_array( (int) $article->catid, $enable_in_categories))
			{
				$article->text = $this->cleanTextOutput($article->text);
				return false;
			}
		} else {
			$article->text = $this->cleanTextOutput($article->text);
			return false;
		}
		
		$this->items = $this->getItems();
		
		// no terms
		if(!$this->items) return false;
		
		// get array of titles for search in current article
		$titles = array();
		foreach($this->items  as $item)
		{
			$titles  = array_merge($titles, $item->aliases);
		}
		
		$case_sensitive = ( (int) $this->params->get('search_case_sensitive', 0) === 0 ? 'i' : '');
		
		$regex = '/\b(' . implode('|', $titles) . ')\b/s' . $case_sensitive;
		
		// no string in article - text must by bypassed by "strip_tags" function
		// for example find "php" searches also links with href "index.php"
		if(preg_match($regex, strip_tags($article->text)) === 0) return false;
		
		// Scriptegrator check
		try
		{
			if (!class_exists('JScriptegrator'))
			{
			    throw new Exception(JText::_('PLG_CONTENT_CDGLOSSARY_ENABLE_SCRIPTEGRATOR'), 404);
			}
			
			$this->JScriptegrator = JScriptegrator::getInstance('2.5.x.2.1.9');
			$this->JScriptegrator->importLibrary(
			array(
				'phpjs',
				'jQuery',
				'jQueryUI' => array('uitheme' => $this->uitheme)
			));
			
			if ($message = $this->JScriptegrator->getError())
			{
			    throw new Exception($message, 500);
			}
		}
		catch (Exception $e)
		{
			$application->enqueueMessage($e->getMessage(), 'error');
			return false;
		}
		
		// get template
		if($layoutpath = $this->getLayoutPath('view'))
		{
			ob_start();
				require $layoutpath;
				$this->term_tmpl .= $this->JScriptegrator->compressHTML( ob_get_contents());
			ob_end_clean();
		}
		
		$document = JFactory::getDocument(); // set document
		$layout = $this->params->get('layout', 'default');

		// plugin JS & CSS
		$document->addScript($this->livepath . '/plugins/content/' . $this->_name . '/tmpl/' . $layout . '/js/' . $this->_name . '.js');
		$document->addStyleSheet($this->livepath . '/plugins/content/' . $this->_name . '/tmpl/' . $layout . '/css/' . $this->_name . '.css');

		// add CSS stylesheet for RTL languages
		if($document->direction === 'rtl')
		{
			$document->addStyleSheet($this->livepath . '/plugins/content/' . $this->_name . '/tmpl/' . $layout . '/css/' . $this->_name . '_rtl.css');
		}
		
		static $once;
		if(!$once)
		{
			$script_options = array();
			$script_options[] = 'uitheme : ' . "'" . $this->uitheme . "'";
			$script_options[] = 'term_corners : ' . ( ( int) $this->params->get('term_corners', 1) ? 'true' : 'false');
			$script_options[] = 'sticky : ' . ( ( int) $this->params->get('tooltip_sticky', 0) ? 'true' : 'false');
			$script_options[] = 'animationOpen : ' . "'" . $this->params->get('tooltip_animationOpen', 'show') . "'";
			$script_options[] = 'animationClose : ' . "'" . $this->params->get('tooltip_animationClose', 'hide') . "'";
			$script_options[] = 'trackMouse : ' . ( ( int) $this->params->get('tooltip_trackMouse', 1) ? 'true' : 'false');
			
			$document->addScriptDeclaration("
			<!--
			if (typeof(jQuery) === 'function')
			{
				jQuery(document).ready(function($){
					if ($.fn." . $this->_name . ")
					{
						$('." . $this->_name . "_term')." . $this->_name . "({
							" . implode( ", ", $script_options) . "
						});
					}
				});
			}
			// -->
			");
			$once = 1;
		}
		
		foreach($titles as $title)
		{
			$wordreg = preg_replace('/([\.\*\+\(\)\[\]])/','\\\\\1', $title);
			$article->text = preg_replace('/(<)([^>]*)('.("$wordreg").')([^<]*)(>)/se' . $case_sensitive, "'\\1' . preg_replace('/' . (\"$wordreg\") . '/$case_sensitive', '###' , '\\2\\3\\4').'\\5'", stripslashes($article->text));
			
			$article->text = preg_replace_callback('/\b' . $wordreg . '\b/s' . $case_sensitive, array($this, 'replacer'), stripslashes($article->text));
			$article->text = preg_replace('/###/s' . $case_sensitive, $wordreg, $article->text);
			
		}
		
		$article->text = $this->cleanTextOutput($article->text);
		
		return true;
	}
	
	/**
	 * Clean text
	 * @param 	string		 $text
	 * @return 	void
	 */
	private function cleanTextOutput($text = '')
	{
		$regex = '{!glossary}';
		$regex .= '|';
		$regex .= '{aliases.*?}';
		return preg_replace('#' . $regex . '#is', '', $text);
	}
	
	/**
	 * Replacer
	 *
	 * @param $match
	 * @return string
	 */
	private function replacer(&$match)
	{
		$term_title = trim( ( isset($match[0]) ? $match[0] : ''));
		
		if(! $term_title) return false;
		
		if(( int) $this->params->get('search_only_first', 0))
		{
			static $term_title_static;
			
			// founded - I can't use simple "===" because of case insensitive strings
			if(stripos($term_title_static, $term_title) === 0)
			{
				return $term_title;
				// end
			} else {
				$term_title_static = $term_title;
			}
		}
		
		$term = new stdClass();
		foreach($this->items as $item)
		{
			foreach($item->aliases as $key=>$item_title)
			{
				if(stripos($item_title, $term_title) === 0)
				{
					$term->title = $item->aliases[0]; // only the first is the title
					$term->description = $item->description;
					if($item->readmore)
					{
						$term->description .= '<span class="' . $this->_name . '_tooltip_readmore">';
							$term->description .= '<a href="' . $item->readmore . '">' . JText::_('COM_CONTENT_READ_MORE_TITLE', true) . '</a>';
						$term->description .= '</span>';
					}
				}
			}
		}
		
		$array_search = array(
			'{$title}',
			'{$description}',
			'{$term}'
		);
		$array_replace = array(
			strip_tags($term->title),
			((int) $this->input->get->get('print', 0, 'int') ? strip_tags($term->description) : htmlspecialchars($term->description)),
			$term_title
		);
		
		return str_replace($array_search, $array_replace, $this->term_tmpl);
		
	}
	
	/**
	 * Get Glossary items
	 * 
	 * @return	array
	 */
	private function getItems()
	{
		$extension = $this->input->get->get('option', 'com_content', 'cmd');
		
		$classfile = dirname(__FILE__) . DS . 'extension' . DS . 'plg_' . $this->_type . '_' . $this->_name . '_' . $extension . '.php';
		
		if(!JFile::exists($classfile))
		{
			return false;
		}
		
		require_once $classfile;
		
		$classname = ucfirst('plg') . ucfirst($this->_type) . 'CDGlossary' . '' . implode('', array_map('ucfirst', explode('_', $extension)));
		
		if(is_callable( array($classname, 'getInstance')))
		{
			$getInstance = call_user_func(array($classname, 'getInstance'), $this->catid, $this->params);
			
			if($articles = $getInstance->getArticles())
			{
				return $articles;
			}
		}
		
	}
	
	/**
	 * Get Layout
	 *
	 * @param 		$file
	 * @return 		string
	 */
	private function getLayoutPath($file = '')
	{
		if (!$file) return false;
		 
		$layout = $this->params->get('layout', 'default');
		$type = 'html';
		
		if ((int) $this->input->get->get('print', 0, 'int'))
		{
			$type = 'print';
		}
		
		$tmpldir = dirname(__FILE__) . DS  . 'tmpl' . DS . $layout;
		 
		$filepath = $tmpldir . DS . $file . '.' . $type .  '.php';
		
		if ($type !== 'html' and !JFile::exists($filepath))
		{
			$type = 'html';
			$filepath = $tmpldir . DS . $file . '.' . $type .  '.php';
		}
		
		if (!JFile::exists( $filepath))
		{
			return false;
		}

		jimport('joomla.filesystem.path');
		return JPath::clean($filepath);
		 
	}
}
?>