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

class AdminRapidlauncherExportController extends AdminRapidlauncherBaseController
{
    var $uses = ['rapidlauncher_menu', 'media'];

    var $helpers = [];

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

    var $package;

    var $zip;

    var $path;

    var $files = [];

    function beforeFilter()
    {
        $this->package = Sanitize::getString($this->params, 'package');

        $this->path = PATH_APP_ADDONS . DS . 'rapidlauncher' . DS . 'exports';

        parent::beforeFilter();
    }

    function export()
    {
        $this->RapidlauncherPackageManager->cleanup($this->path);

        $dirId = Sanitize::getInt($this->data, 'dir');

        if($dirId > 0)
        {
            $dirId = [$dirId];
        }
        else {
            return $this->jsonResponse(false, 'A directory was not selected.');
        }

        $name = Inflector::slug(Sanitize::getString($this->data, 'title'));

        if($name == '') {

            $name = 'directory';
        }

        $this->exportFieldGroups($dirId);

        $this->exportFields($dirId);

        $this->exportDirectories($dirId);

        $this->exportCategories($dirId);

        $this->exportListingTypes($dirId);

        $path = $this->saveZip($name);

        return $this->jsonResponse(true, '', ['url' => pathToUrl($path)]);
     }

    function exportFieldGroups($dirId)
    {
        $rows = $this->RapidlauncherFieldGroupHelper->export($dirId);

        $this->saveCsv('field_groups.csv', $rows);

        return $this->response(true);
    }

    function exportFields($dirId)
    {
        $rows = $this->RapidlauncherFieldHelper->export($dirId);

        $this->saveCsv('fields.csv', $rows);

        return $this->response(true);
    }

    function exportListingTypes($dirId)
    {
        $rows = $this->RapidlauncherListingTypeHelper->export($dirId);

        $this->saveCsv('listing_types.csv', $rows);

        $rows = $this->RapidlauncherRatingCriteriaHelper->export($dirId);

        $this->saveCsv('rating_criteria.csv', $rows);

        return $this->response(true);
    }

    function exportDirectories($dirId)
    {
        $rows = $this->RapidlauncherDirectoryHelper->export($dirId);

        $this->saveCsv('directories.csv', $rows);

        return $this->response(true);
    }

    function exportCategories($dirId)
    {
        $rows = $this->RapidlauncherCategoryHelper->export($dirId);

        $this->saveCsv('categories.csv', $rows);

        return $this->response(true);
    }

    protected function saveCsv($file, $rows)
    {
        $this->files[$file] = $this->RapidlauncherCsvHelper->setPath($this->path)->write($rows);
    }

    protected function saveZip($name)
    {
        $zipPath = $this->path . DS . $name . '.zip';

        $zip = new ZipArchive();

        $zip->open($zipPath, ZIPARCHIVE::CREATE);

        foreach ($this->files AS $file => $contents)
        {
            $zip->addFromString($file, $contents);
        }

        $zip->close();

        return $zipPath;
    }
}
