<?php
/**
 * Ratings Migrator Addon for JReviews
 * Copyright (C) 2010-2015 ClickFWD LLC
 * This is not free software, do not distribute it.
 * For licencing information visit https://www.jreviews.com
 * or contact sales@jreviews.com
**/

defined( 'MVC_FRAMEWORK') or die;

class RatingsmigratorComponent extends S2Component {

    var $published = false;

    function startup(&$controller)
    {
        $this->c = &$controller;

        if(defined('MVC_FRAMEWORK_ADMIN'))
        {
            $controller->assets['js'][] = 'admin/addon_ratingsmigrator';

            $this->published = true;
        }
    }
}