<?php
/**
 * JReviews - Reviews Extension
 * Copyright (C) 2010-2015 ClickFWD LLC
 * This is not free software, do not distribute it.
 * For licencing information visit https://www.jreviews.com
 * or contact sales@jreviews.com
**/

defined('_JEXEC') or defined('ABSPATH') or die;

if(defined('_JEXEC'))
{
	$filePath = 'cms_compat' . DS . 'joomla' . DS . 'includes' . DS . 'plugins' . DS . 'jreviews.php';

	if(file_exists(JPATH_SITE . DS . 'templates' . DS . 'jreviews_overrides' . DS . $filePath))
	{
    	require_once(JPATH_SITE . DS . 'templates' . DS . 'jreviews_overrides' . DS . $filePath);
	}
	else {
    	require_once(JPATH_SITE . DS . 'components' . DS . 'com_jreviews' . DS . 'jreviews' . DS . $filePath);
	}
}
elseif(defined('ABSPATH'))
{
	$filePath = 'cms_compat' . DS . 'wordpress' . DS . 'includes' . DS . 'plugins' . DS . 'jreviews.php';

	if(file_exists(ABSPATH . DS . 'templates' . DS . 'jreviews_overrides' . DS . $filePath))
	{
    	require_once(ABSPATH . DS . 'jreviews_overrides' . DS . $filePath);
	}
	else {
    	require_once(WP_PLUGIN_DIR . DS . 'jreviews' . DS . 'jreviews' . DS . $filePath);
	}
}
