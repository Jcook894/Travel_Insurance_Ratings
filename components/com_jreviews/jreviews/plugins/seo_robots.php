<?php
/**
 * JReviews - Reviews Extension
 * Copyright (C) 2010-2015 ClickFWD LLC
 * This is not free software, do not distribute it.
 * For licencing information visit https://www.jreviews.com
 * or contact sales@jreviews.com
**/

defined( 'MVC_FRAMEWORK') or die( 'Direct Access to this location is not allowed.' );

class SeoRobotsComponent extends S2Component {

    var $published = false;

    function startup(&$controller)
    {
        $this->c = &$controller;

        if(defined('MVC_FRAMEWORK_ADMIN') || $controller->ajaxRequest || $controller->isRequested) return;

        $this->published = true;
    }

    function plgAfterFilter()
    {
        if(!$this->published) return;

        /**
         * LIST PAGES
         */
        if($this->c->name == 'categories')
        {
            $rows = Sanitize::getVar($this->c->viewVars,'listings');

            if(count($rows) == 0) self::noindex_follow();
        }

        /**
         * REVIEW LIST PAGES
         */
        elseif($this->c->name == 'reviews' && in_array($this->c->action,array('custom','latest','latest_user','latest_editor')))
        {
            $rows = Sanitize::getVar($this->c->viewVars,'reviews');

            if(count($rows) == 0) self::noindex_follow();
        }

        /**
         * REVIEW DISCUSSION LIST PAGES
         */
        elseif($this->c->name == 'discussions' && $this->c->action == 'latest')
        {
            $rows = Sanitize::getVar($this->c->viewVars,'posts');

            if(count($rows) == 0) self::noindex_follow();
        }

        /**
         * REVIEW DETAIL/DISCUSSION PAGES
         */
        elseif($this->c->name == 'discussions' && $this->c->action == 'review')
        {
            $rows = Sanitize::getVar($this->c->viewVars,'posts');

            // if(count($rows) == 0) self::noindex_follow();
        }
        /**
         * LISTING VIEW ALL REVIEWS PAGE
         */
        elseif($this->c->name == 'listings' && $this->c->action == 'detail')
        {
            $rows = Sanitize::getVar($this->c->viewVars,'reviews');

            if(count($rows) == 0) self::noindex_follow();
        }

        /**
         * LISTING CREATE PAGES
         */
        elseif($this->c->name == 'listings' && $this->c->action == 'create')
        {
            // self::noindex_follow();
        }

        /**
         * MEDIA LIST PAGES
         */
        elseif($this->c->name == 'media' && $this->c->action == 'mediaList')
        {
            $rows = Sanitize::getVar($this->c->viewVars,'media');

            if(count($rows) == 0) self::noindex_follow();
        }

        /**
         * MEDIA PHOTO GALLERY PAGES
         */
        elseif($this->c->name == 'media' && $this->c->action == 'photoGallery')
        {
            $rows = Sanitize::getVar($this->c->viewVars,'photos');

            if(count($rows) == 0) self::noindex_follow();
        }

        /**
         * MEDIA PHOTO GALLERY PAGES
         */
        elseif($this->c->name == 'media' && $this->c->action == 'videoGallery')
        {
            $rows = Sanitize::getVar($this->c->viewVars,'videos');

            if(count($rows) == 0) self::noindex_follow();
        }

        /**
         * MEDIA UPLOAD PAGES
         */
        elseif($this->c->name == 'media_upload' && $this->c->action == 'create')
        {
            self::noindex_follow();
        }
    }

    static function index_follow()
    {
        cmsFramework::meta("robots","index, follow");
    }

    static function noindex_follow()
    {
        cmsFramework::meta("robots","noindex, follow");
    }
}