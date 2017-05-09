<?php
/**
 * JReviews - Reviews Extension
 * Copyright (C) 2010-2015 ClickFWD LLC
 * This is not free software, do not distribute it.
 * For licencing information visit https://www.jreviews.com
 * or contact sales@jreviews.com
**/

defined( 'MVC_FRAMEWORK') or die( 'Direct Access to this location is not allowed.' );

use Detection\MobileDetect;

class ThemingComponent extends S2Component
{
    var $c;
    var $ignored_controllers = array(
        'categories'=>array(),
        'com_content'=>array(),
        'listings'=>array(
            'edit',
            '_loadForm'
        )
    );

	function startup(&$controller)
    {
        $this->c = & $controller;

        # Set Theme
        $controller->viewTheme = $controller->Config->template;

        $this->mobileDetect();

        Configure::write('System.isIE',s2isIE());

        $controller->viewImages = S2Paths::get('jreviews', 'S2_THEMES_URL') . Sanitize::getString($controller->Config,'fallback_theme') . _DS . 'theme_images' . _DS;

        if(defined('MVC_FRAMEWORK_ADMIN'))
        {
            return;
        }

        # Dynamic theme setup
        if(
            (isset($this->ignored_controllers[$controller->name]) && empty($this->ignored_controllers[$controller->name]))
            ||
            (isset($this->ignored_controllers[$controller->name]) && in_array($controller->action,$this->ignored_controllers[$controller->name]))
        ) {
             return;
        }
        $this->setSuffix();
    }

    function mobileDetect()
    {
        $mobile_theme = Sanitize::getString($this->c->Config,'mobile_theme');

        if($mobile_theme == '') return;

        if(!Configure::read('System.mobileDetect'))
        {
            Configure::write('System.mobileDetect',true);

			$detect = new MobileDetect();

            $isMobile = $detect->isMobile();

            $isTablet = $detect->isTablet();

            Configure::write('System.isiOS',$detect->isiOS());

            Configure::write('System.isAndroidOS',$detect->isAndroidOS());

			if ($isMobile && !$isTablet) { // Mobile, excluding tablets

                // Add data param to be able to generate different cache filenames for desktop and mobile
                // Same code run in dispatcher to pickup the correct cache file
                $this->c->params['isMobile'] = true;

				Configure::write('System.isMobile',true);

                Configure::write('System.isMobileOS',true);

            	$this->c->viewTheme = $mobile_theme;
            }
            elseif ($isMobile && $isTablet) // Mobile, including tablets
            {
                Configure::write('System.isMobile',false);

                Configure::write('System.isMobileOS',true);
            }
			else { // Not mobile

            	Configure::write('System.isMobile',false);
			}

        }
        elseif(Configure::read('System.isMobile')) {

            $this->c->viewTheme = $mobile_theme;
        }
    }

    static function getImageUrl($filename, $admin = false)
    {
        $path = self::getImage($filename, $admin, 'url');

        $url = str_replace('\\', '/', $path);

        return $url;
    }

    static function getImagePath($filename, $admin = false)
    {
        return self::getImage($filename, $admin, 'path');
    }

    static function getImage($filename, $admin = false, $type)
    {
        $path = '';

        $cache_key = S2CacheKey('jreviews_paths');

        $fileArray = S2Cache::read($cache_key,'_s2framework_core_');

        if(empty($fileArray))
        {
            $paths = S2App::getInstance('jreviews');

            $fileArray = $paths->jreviewsPaths;
        }

        $Config = Configure::read('JreviewsSystem.Config');

        $theme = $admin ? 'default' : Sanitize::getString($Config,'template','default');

        $fallback_theme = Sanitize::getString($Config,'fallback_theme','default');

        $location = $admin ? 'AdminTheme': 'Theme';

        $dir = $type == 'url' ? WWW_ROOT_REL : PATH_ROOT;

        if(isset($fileArray[$location][$theme]['theme_images']) && isset($fileArray[$location][$theme]['theme_images'][$filename]))
        {
            $path = $dir . $fileArray[$location][$theme]['.info']['path'] . 'theme_images' . '/'. $filename;
        }
        elseif(isset($fileArray[$location][$fallback_theme]['theme_images']) && isset($fileArray[$location][$fallback_theme]['theme_images'][$filename]))
        {
            $path = $dir . $fileArray[$location][$fallback_theme]['.info']['path'] . 'theme_images' . '/'. $filename;
        }

        return $path;
    }

    /**
    * Sets the correct view suffix
    *
    * @param mixed $categories
    */
    public function setSuffix($options = array())
    {
        $extension = Sanitize::getString($this->c->params, 'extension', 'com_content');

        switch($this->c->action)
        {
            case 'search':
                $this->c->viewSuffix = Sanitize::getString($this->c->params,'tmpl_suffix',$this->c->Config->search_tmpl_suffix);
                // return;
            break;

            case 'detail': // View all reviews page
                $options['listing_id'] = Sanitize::getInt($this->c->params,'id');
            break;

            case 'create':

                if($this->c->name == 'media_upload')
                {
                    $id = explode(':',base64_decode(urldecode(Sanitize::getString($this->c->params,'id'))));

                    if(!empty($id))
                    {
                        switch(count($id)) {
                            case 2: // Listing
                                $options['listing_id'] = (int) array_shift($id);
                                break;
                            case 3: // Review
                                $options['listing_id'] = (int) array_shift($id);
                                $review_id = (int) array_shift($id);
                                break;
                            default:
                                $has_access = false;
                                break;
                        }

                        $extension = array_shift($id);
                    }
                }
                elseif($this->c->name == 'inquiry')
                {
                    $options['listing_id'] = Sanitize::getInt($this->c->params,'id');
                }

            break;
        }

        # Find cat id

        $listing_id = Sanitize::getInt($options,'listing_id');

        if($extension == 'com_content' && $listing_id && method_exists($this->c->Listing,'getCatID'))
        {
            $options['cat_id'] = $this->c->Listing->getCatID($listing_id);
        }

        # Get cat and parent cat info

        $cat_id = Sanitize::getInt($options,'cat_id');

        if($cat_id)
        {
            S2App::import('Model','category','jreviews');

            $CategoryModel = ClassRegistry::getClass('CategoryModel');

            $options['categories'] = $CategoryModel->findParents($cat_id);
        }

        if(Sanitize::getVar($options,'categories'))
        {
            # Iterate from parent to child and overwrite the suffix if not null
            foreach($options['categories'] AS $category)
            {
                $category['Category']['tmpl_suffix'] != '' and $this->c->viewSuffix = $category['Category']['tmpl_suffix'];
            }
        }

        # Module params, menu params and posted data override previous values
        if(Sanitize::getVar($this->c->params,'module'))
        {
            $this->c->viewSuffix = Sanitize::getString($this->c->params['module'],'tmpl_suffix');
        }

        if($suffix = Sanitize::getString($this->c->data,'tmpl_suffix',Sanitize::getString($this->c->params,'tmpl_suffix')))
        {
            $suffix != '' and $this->c->viewSuffix = $suffix;
        }

        if(isset($this->c->Menu))
        {
            # Nothing yet, so we load the menu params
            $menu_params = $this->c->Menu->getMenuParams(Sanitize::getInt($this->c->params,'Itemid'));

            Sanitize::getVar($menu_params,'tmpl_suffix') != '' and $this->c->viewSuffix = Sanitize::getVar($menu_params,'tmpl_suffix');
        }
    }

    /**
    * Sets the correct view layout
    *
    * @param mixed $categories
    */
    public function setLayout($options = array())
    {
        $layout_options = Sanitize::getVar($this->c->Config,'list_predefined_layout',array());

        $search_layout = $this->c->action == 'search' ? $this->c->Config->search_display_type : null;

        $category_layout = '';

        if(Sanitize::getVar($options,'categories'))
        {
            # Iterate from parent to child and overwrite the suffix if not null
            foreach($options['categories'] AS $category)
            {
                $category['Category']['tmpl'] != '' and $category_layout = $category['Category']['tmpl'];
            }
        }

        # Overrides default view based on menu and url parameter (listview)

        $menu_layout = Sanitize::getString($this->c->data,'listview');

        $selected_view = Sanitize::getInt($this->c->params,'listview',1);

        if(isset($this->c->params['listview']))
        {
            $listview = $this->convertLayoutToListview($layout_options,$selected_view);
        }
        elseif($category_layout) {

            $listview = $this->convertLayoutToListview($layout_options,$category_layout);
        }
        elseif($menu_layout != '') {

            $layout = $this->listTypeConversion($menu_layout);

            $listview = $this->convertLayoutToListview($layout_options,$layout);
        }
        elseif($search_layout != '') {

            $layout = $this->listTypeConversion($search_layout);

            $listview = $this->convertLayoutToListview($layout_options,$layout);
        }
        else {

            $listview = $layout_options[$selected_view]['layout'];
        }

        # Global layout

        $this->c->listview = $this->c->data['listview'] = $listview;

        # Layout can be overriden for certain controller::actions

        if(method_exists($this,$this->c->action)) $this->{$this->c->action}();
    }

    function convertLayoutToListview($layout_options, $layout)
    {
        if(is_string($layout))
        {
            foreach($layout_options AS $index => $option)
            {
                if($option['layout'] == $layout) break;
            }
        }
        elseif(isset($layout_options[$layout]) && $layout_options[$layout] != '') {

            $index = $layout;

            $layout = $layout_options[$layout]['layout'];
        }

        if($layout == '')
        {
            $index = 1;

            $layout = $layout_options[1]['layout'];
        }

        $this->c->params['layout_index'] = $index;

        if($this->viewSuffix == '' && $layout_options[$index]['suffix'] != '') {

            $this->c->viewSuffix = $layout_options[$index]['suffix'];
        }

        return $layout;
    }

    /**
    * Uses the listings_favorite theme file if present
    */
    function favorites()
    {
        $Configure = S2App::getInstance(); // Get file map
        if(
            isset($Configure->jreviewsPaths['Theme'][$this->c->Config->template]['listings']['listings_favorites.thtml'])
            ||
            isset($Configure->jreviewsPaths['Theme']['default']['listings']['listings_favorites.thtml'])
        ){
            $this->c->listview = 'favorites';
        }
    }

    /**
    * Uses the listings_favorite theme file if present
    */
    function mylistings()
    {
        $Configure = S2App::getInstance(); // Get file map
        if(
            isset($Configure->jreviewsPaths['Theme'][$this->c->Config->template]['listings']['listings_mylistings.thtml'])
            ||
            isset($Configure->jreviewsPaths['Theme']['default']['listings']['listings_mylistings.thtml'])
        ){
            $this->c->listview = 'mylistings';
        }
    }

    function listTypeConversion($type)
    {
        switch($type) {

            case 0:
                return 'tableview';
                break;
            case 1:
                return 'blogview';
                break;
            case 2:
                return 'thumbview';
                break;
            case 3:
                return 'masonry';
                break;
            default:
                return 'blogview';
                break;
        }
    }
}