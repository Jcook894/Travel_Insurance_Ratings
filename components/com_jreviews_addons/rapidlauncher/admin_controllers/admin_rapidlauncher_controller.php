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

class AdminRapidlauncherController extends AdminRapidlauncherBaseController
{
    var $uses = ['rapidlauncher_menu', 'media', 'directory'];

    var $helpers = ['form'];

    var $components = [
        'config',
        'access',
        'admin/rapidlauncher_package_manager',
        'admin/rapidlauncher_csv_helper',
    ];

    protected $url = 'https://www.jreviews.com/rapidlauncher/';
    // protected $url = 'http://jreviews.joomla/rapidlauncher/';

    function beforeFilter()
    {
        $this->RapidlauncherPackageManager->cleanup(PATH_APP_ADDONS . DS . 'rapidlauncher' . DS . 'imports');

        $this->RapidlauncherPackageManager->cleanup(PATH_APP_ADDONS . DS . 'rapidlauncher' . DS . 'exports');

        $this->RapidlauncherPackageManager->setUrl($this->url);

        parent::beforeFilter();
    }

    function index()
    {
        $packages = $this->RapidlauncherPackageManager->readManifest();

        if(!$packages)
        {
            $packages = [];
        }

        $directories = $this->Directory->getSelectList();

        $this->set([
            'directories' => $directories,
            'packages' => $packages
        ]);

        return $this->render('rapidlauncher', 'index');
    }
}
