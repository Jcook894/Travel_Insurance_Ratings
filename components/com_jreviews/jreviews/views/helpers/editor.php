<?php
/**
 * JReviews - Reviews Extension
 * Copyright (C) 2010-2015 ClickFWD LLC
 * This is not free software, do not distribute it.
 * For licencing information visit https://www.jreviews.com
 * or contact sales@jreviews.com
 *
 * This is the default display for custom fields
 **/
defined( 'MVC_FRAMEWORK') or die;

if(_CMS_NAME == 'joomla')
{
	jimport('joomla.html.editor');
}

class EditorHelper extends MyHelper
{
	function change_mce_options( $init )
	{
		$toolbar1 = explode(',', $init['toolbar1']);

		$del = array('wp_more','fullscreen');

		$toolbar1 = array_diff($toolbar1, $del);

		// $toolbar1[] = 'wp_fullscreen';

		// $plugins = explode(',', $init['plugins']);

		// $plugins[] = 'wpfullscreen';

		// $init['plugins'] = implode(',', $plugins);

		$init['toolbar1'] = implode(',', $toolbar1);

		return $init;
	}

	function change_qt_options( $init ) {

		$buttons = explode(',', $init['buttons']);

		$del = array('more');

		$buttons = array_diff($buttons, $del);

		$init['buttons'] = implode(',', $buttons);

		return $init;
	}

	function wp_enqueue_editor()
	{
	    wp_enqueue_script( 'wp-fullscreen' );
	}

	function load()
    {
    	switch(_CMS_NAME)
    	{
    		case 'joomla':

    			$name = JFactory::getConfig()->get('editor');

				if($name == 'jckeditor') $name = 'tinymce';

				if (in_array(strtolower($name), array('tinymce', 'jce')))
				{
					$editor = JEditorJReviews::getInstance($name);

					$editor->display('content', '', 0, 0, 0, 0, $buttons = false);
				}
/*
				if (in_array(strtolower($name), array('tinymce', 'jce')))
				{
		    		JEditorJReviews::getInstance($name)->_loadEditor();
				}
*/
    			break;

			case 'wordpress':

				// add_action('wp_enqueue_scripts', array($this, 'wp_enqueue_editor' ));

				add_filter('tiny_mce_before_init', array($this, 'change_mce_options'));

				add_filter('quicktags_settings', array($this, 'change_qt_options'));

				ob_start();

				wp_editor('','jr-editor');

				$html = ob_get_contents();

				ob_end_clean();

				preg_match_all('/(<link.* \/>)/', $html, $matches);

				$matches[0] = array_unique($matches[0]);

				$html = implode("\n", $matches[0]);

				if(defined('MVC_FRAMEWORK_ADMIN'))
				{
					echo $html;
				}
				else {

					cmsFramework::addCustomTag($html, 'editor');
				}

				break;
    	}
	}
}

try {

	if(class_exists('JEditor'))
	{
		class JEditorJReviews extends JEditor {

			public static function getInstance($editor = 'none')
			{
				static $instances;

				if (!isset ($instances)) {
					$instances = array ();
				}

				$signature = serialize($editor);

				if (empty ($instances[$signature])) {
					$instances[$signature] = new JEditorJReviews($editor);
				}

				return $instances[$signature];
			}

			public function _loadEditor($config = array()) {

				return parent::_loadEditor($config);
			}
		}
	}
}
catch ( ReflectionException $e )
{
    //  method does not exist
//    echo $e->getMessage();
}

