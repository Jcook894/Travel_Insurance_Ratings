<?php
/* Plugin Name: JReviews
Plugin URI: https://www.jreviews.com
Description: JReviews is the Directory & Review System with CCK functionality
Version: 2.7.16.2
Author: ClickFWD LLC
Author URI: https://www.jreviews.com
License: Proprietary
*/

defined('_JEXEC') or defined('ABSPATH') or die;

if (!defined('DS')) define('DS', DIRECTORY_SEPARATOR);

if(defined('_JEXEC'))
{

    require( dirname(__FILE__) . DS . 'jreviews' . DS . 'cms_compat' . DS . 'joomla' . DS . 'bootloader.php');
}
elseif(defined('ABSPATH')) {

	$plugins_dir = str_replace('/', DS, plugin_dir_path(WP_PLUGIN_DIR) . 'plugins');

	$s2framework = $plugins_dir . DS . 's2framework' . DS . 's2framework.php';

	if(!file_exists($s2framework)) return;

    require( dirname(__FILE__) . DS . 'jreviews' . DS . 'cms_compat' . DS . 'wordpress' . DS . 'bootloader.php');

	register_activation_hook(__FILE__, array('JReviews' , 'activation'));

	register_deactivation_hook(__FILE__, array('JReviews', 'deactivation'));
}