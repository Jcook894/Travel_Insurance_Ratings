<?php
  /**
 * GeoMaps Addon for JReviews
 * Copyright (C) 2010-2014 ClickFWD LLC
 * This is not free software, do not distribute it.
 * For licencing information visit http://www.reviewsforjoomla.com
 * or contact sales@reviewsforjoomla.com
**/

defined( 'MVC_FRAMEWORK') or die( 'Direct Access to this location is not allowed.' );

class ImagemigratorpluginComponent extends S2Component
{
    var $name = 'imagemigratorplugin';

    var $published = true;

    function startup(&$controller)
    {
    	if(defined('MVC_FRAMEWORK_ADMIN')) {

        	$controller->assets['js'][] = 'admin/addon_imagemigrator';
    	}

    }

}