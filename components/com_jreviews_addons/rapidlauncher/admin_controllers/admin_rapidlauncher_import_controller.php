<?php
/**
 * RapidLauncher Addon for JReviews
 * Copyright (C) 2010-2016 ClickFWD LLC
 * This is not free software, do not distribute it.
 * For licencing information visit https://www.jreviews.com
 * or contact sales@jreviews.com
**/

defined( 'MVC_FRAMEWORK') or die;

S2App::import('AdminController', 'admin_rapidlauncher_base', 'jreviews');

class AdminRapidlauncherImportController extends AdminRapidlauncherBaseController
{
    var $uses = ['rapidlauncher_menu', 'media', 'directory'];

    var $helpers = ['form'];

    var $components = [
        'config',
        'access',
        'media_storage',
        'admin/rapidlauncher_package_manager',
        'admin/rapidlauncher_csv_helper',
        'admin/rapidlauncher_field_group_helper',
        'admin/rapidlauncher_field_helper',
        'admin/rapidlauncher_listing_type_helper',
        'admin/rapidlauncher_rating_criteria_helper',
        'admin/rapidlauncher_category_helper',
        'admin/rapidlauncher_directory_helper',
        'admin/rapidlauncher_listing_helper',
        'admin/rapidlauncher_menu_helper'
    ];

    protected $url = 'https://www.jreviews.com/rapidlauncher/';
    // protected $url = 'http://jreviews.joomla/rapidlauncher/';

    var $package;

    function beforeFilter()
    {
        $this->package = Sanitize::getString($this->params, 'package');

        $this->path = PATH_APP_ADDONS . DS . 'rapidlauncher' . DS . 'imports';

        $this->RapidlauncherPackageManager->setPath($this->path);

        $this->RapidlauncherPackageManager->setUrl($this->url);

        $this->RapidlauncherListingHelper->setPath($this->path);

        parent::beforeFilter();
    }

    function downloadPackage()
    {
        $this->RapidlauncherPackageManager->download($this->package);

        if(_CMS_NAME == 'joomla')
        {
            $this->RapidlauncherMenu->createMenuContainer(
                'Required Joomla Menus',
                'required-joomla-menus',
                'Created by JReviews Rapidlauncher Add-on'
            );

            $this->RapidlauncherMenu->createMenuContainer(
                'JReviews Menus',
                'jreviews-menus',
                'Created by JReviews Rapidlauncher Add-on'
            );
        }

        return $this->jsonResponse(true);
    }

    function upload()
    {
        $this->RapidlauncherPackageManager->setPath($this->path);

        // Save the uploaded file

        if($file = Sanitize::getString($this->params, 'qqfile'))
        {
            if($filename = $this->RapidlauncherPackageManager->upload($file))
            {
                return $this->jsonResponse(true, '', ['package' => $filename]);
            }
        }

        return $this->jsonResponse(false , 'Did not find a file to upload in the request.');
    }

    function createFieldGroups()
    {
        $rows = $this->loadCsv('field_groups.csv');

        if($rows)
        {
            $this->RapidlauncherFieldGroupHelper->import($rows);
        }

        return $this->jsonResponse(true);
    }

    function createFields()
    {
        $rows = $this->loadCsv('fields.csv');

        if($rows)
        {
            $this->RapidlauncherFieldHelper->import($rows);
        }

        return $this->jsonResponse(true);
    }

    function createListingTypes()
    {
        $rows = $this->loadCsv('listing_types.csv');

        $this->RapidlauncherListingTypeHelper->import($rows);

        $rows = $this->loadCsv('rating_criteria.csv');

        if($rows)
        {
            $this->RapidlauncherRatingCriteriaHelper->import($rows);
        }

        return $this->jsonResponse(true);
    }

    function createDirectories()
    {
        $rows = $this->loadCsv('directories.csv');

        if($rows)
        {
            $this->RapidlauncherDirectoryHelper->import($rows);
        }

        return $this->jsonResponse(true);
    }

    function createCategories()
    {
        $rows = $this->loadCsv('categories.csv');

        if($rows)
        {
            $this->RapidlauncherCategoryHelper->import($rows);
        }

        return $this->jsonResponse(true);
    }

    function createListings()
    {
        $rows = $this->loadCsv('listings.csv');

        if($rows)
        {
            $this->RapidlauncherListingHelper->import($rows);
        }

        clearCache('', 'views');
        clearCache('', '__data');
        clearCache('', 'core');

        return $this->jsonResponse(true);
    }

    function createMenus()
    {
        $rows = $this->loadCsv('menus.csv');

        if($rows)
        {
            $this->RapidlauncherMenuHelper->import($rows);
        }

        return $this->jsonResponse(true);
    }

    protected function loadCsv($file)
    {
        $path = $this->path. DS . $this->package;

        if($csv = $this->RapidlauncherCsvHelper->setPath($path)->read($file))
        {
            $rows = $csv->fetchAll();

            return $rows;
        }

        return [];
    }
}
