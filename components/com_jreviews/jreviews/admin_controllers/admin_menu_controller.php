<?php
/**
 * JReviews - Reviews Extension
 * Copyright (C) 2010-2015 ClickFWD LLC
 * This is not free software, do not distribute it.
 * For licencing information visit https://www.jreviews.com
 * or contact sales@jreviews.com
**/

// no direct access
defined( 'MVC_FRAMEWORK') or die( 'Direct Access to this location is not allowed.' );

class AdminMenuController extends MyController {

    var $uses = array('category', 'criteria', 'directory');

    var $components = array('config');

    var $helpers = array('admin/admin_settings');

    var $autoLayout = false;

    var $autoRender = false;

    var $layout = 'empty';

    function create()
    {
        $menu_types = array();

        $page = Sanitize::getVar($this->params, 'post');

        $paths = array(PATH_APP_ROOT . DS . 'jreviews' . DS . 'cms_compat' . DS . 'wordpress' . DS . 'menus');

        $addon_paths = $this->getAddonPaths();

        if(!empty($addon_paths))
        {
            $paths = array_merge($paths, $addon_paths);
        }

        foreach($paths AS $path)
        {
            $menu_types = array_merge($menu_types,$this->getMenuList($path));
        }

        $this->set(compact('page','menu_types','directories','listingTypes','categories'));

        return $this->render('wp-menu', 'create');
    }

    function loadSettings()
    {
        $path = Sanitize::getString($this->data,'path');

        if($path == '') return '';

        $directories = $categories = $listingTypes = array();

        $xml_path = PATH_ROOT . $path;

        $xml = simplexml_load_file($xml_path);

        $menu_id = Sanitize::getInt($this->data, 'menu_id');

        $fields = array();

        if(isset($xml->fields))
        {
            foreach($xml->fields->children() AS $fieldset)
            {
                $group_title = (string) $fieldset->attributes()->label;

                foreach($fieldset->field AS $input)
                {
                    $name = (string) $input->attributes()->name;

                    $type = (string) $input->attributes()->type;

                    $default = (string) $input->attributes()->default;

                    $label = (string) $input->attributes()->label;

                    $multiple = (string) $input->attributes()->multiple;

                    $text = '';

                    if(in_array($type, array('directory','jreviewscategory','listingtype')))
                    {
                        switch($type)
                        {
                            case 'directory':

                                $options = $this->Directory->getSelectList();

                                break;

                            case 'jreviewscategory':

                                $options = $this->Category->getCategoryList(array('disabled' => false));

                                break;

                            case 'listingtype':

                                $options = $this->Criteria->getSelectList();

                                break;
                        }

                        if($multiple)
                        {
                            $type = 'selectmultiple';
                        }
                        else {

                            $options = array_merge(array(array('value'=>'', 'text'=>'-- Select --')), $options);

                            $type = 'select';
                        }
                    }
                    elseif($type == 'help') {

                        $text = $input;
                    }
                    else {

                        $options = array();

                        $textArray = (array) $input->children();

                        if(isset($textArray['option']))
                        {
                            $textArray = $textArray['option'];

                            $i = 0;

                            foreach($input->option AS $option)
                            {
                                $options[(string) $option->attributes()->value] = $textArray[$i];

                                $i++;
                            }
                        }
                    }

                    $inputArray = compact('name', 'type', 'default', 'label', 'options', 'text');

                    $fields[$group_title][] = $inputArray;
                }
            }
        }

        $this->set(compact('fields','menu_id'));

        $settings = $this->render('wp-menu','settings');

        return $settings;
    }

    function getMenuList($path)
    {
        if(!is_dir($path)) return array();

        $Folder = new S2Folder($path);

        $manifest_paths = $Folder->findRecursive('metadata.xml',true);

        $menus = array();

        foreach($manifest_paths AS $path)
        {
            $pathinfo = pathinfo($path);

            $folders = explode('/wp-views/', $pathinfo['dirname']);

            $last = array_pop($folders);

            $xml = simplexml_load_file($path);

            $title = (string) $xml->title[0];

            $Folder->cd($pathinfo['dirname'] . '/tmpl');

            $menu_paths = $Folder->findRecursive('.*\.xml',true);

            foreach($menu_paths AS $menu_path)
            {
                $menu_name = pathinfo($menu_path, PATHINFO_FILENAME);

                $menu_xml = simplexml_load_file($menu_path);

                $route = (string) $menu_xml->controller[0] . '/' . (string) $menu_xml->action[0];

                $menus[$title][] = array(
                    'route'=>$route,
                    'title'=>(string) $menu_xml->title[0],
                    'name'=>$last . '/tmpl/' . $menu_name,
                    'path'=>str_replace(PATH_ROOT, '', $menu_path)
                    );
            }
        }

        return $menus;
    }

    function getAddonPaths()
    {
        $Folder = new S2Folder(PATH_APP_ADDONS);

        $dir = $Folder->read(true, true, true);

        $paths = $dir[0];

        foreach($paths AS $key=>$path)
        {
            $paths[$key] .= '/cms_compat/wordpress/menus';
        }

        return $paths;
    }
}