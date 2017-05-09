<?php
/**
 * JReviews - Reviews Extension
 * Copyright (C) 2010-2015 ClickFWD LLC
 * This is not free software, do not distribute it.
 * For licencing information visit https://www.jreviews.com
 * or contact sales@jreviews.com
**/

defined( 'MVC_FRAMEWORK') or die;

class AdminAddonsController extends MyController {

    var $uses = array('addon');

    var $helpers = array('assets');

    var $components = array('access','config','admin/admin_packages');

    var $autoRender = false;

    var $autoLayout = true;

    var $layout = 'default';

    function beforeFilter()
    {
        parent::beforeFilter();
    }

    function index()
    {
        return $this->render('addons','index');
    }

    function discover()
    {
        $manifests = array();

        $addons = array();

        // Get a list of installed add-ons from the DB

        $installed = $this->Addon->findAll();

        $names = array_keys($installed);

        $Folder = new S2Folder(PATH_APP_ADDONS);

        $contents = $Folder->read(true, true, true);

        // Compare the two. Manifests not already recorded in the database are new add-ons

        if(isset($contents[0]))
        {
            foreach($contents[0] AS $path)
            {
                $parts = explode(DS, $path);

                $folder = array_pop($parts);

                if(strstr($folder, '_bak')) continue;

                $addon = AdminPackagesComponent::readManifest($path, 'addon');

                if($addon)
                {
                    if(!in_array(pathinfo($path,PATHINFO_FILENAME), $names)) {

                        $addons[] = $addon;
                    }
                }
            }
        }

        $this->set(array(
            'addons'=>$addons
            ));

        return $this->render('addons','discover');
    }

    function _installDiscovered()
    {
        $response = array('success'=>false);

        $ids = Sanitize::getVar($this->params,'cid');

        if(empty($ids)) {

            return cmsFramework::jsonResponse($response);
        }

        foreach($ids AS $name)
        {
            AdminPackagesComponent::installAddon(PATH_APP_ADDONS . DS . $name);

            $response['success'] = true;
        }

        return cmsFramework::jsonResponse($response);
    }

    function manage()
    {
        $addons = $this->Addon->findAll();

        $this->set(array(
            'addons'=>$addons
            ));

        return $this->render('addons','manage');
    }

    function install()
    {
        return $this->render('addons','install');
    }

    function _delete()
    {
        $response = array('success'=>false,'str'=>array());

        $ids = Sanitize::getVar($this->params,'cid');

        if(empty($ids)) {

            return cmsFramework::jsonResponse($response);
        }

        $this->Addon->delete('id',$ids);

        $response['success'] = true;

        return cmsFramework::jsonResponse($response);
    }

    function _processUpload()
    {
        $response = array('success'=>false,'str'=>'UPDATER_ADDON_NOT_INSTALLED');

        S2App::import('Vendor','fileuploader/fileuploader');

        $tmp_path = cmsFramework::getConfig('tmp_path');

        $Folder = new S2Folder($tmp_path);

        // Remove any previously created temporary addon folders

        $oldFolders = $Folder->read();

        foreach($oldFolders[0] AS $old)
        {
            if(preg_match('/^addon_[0-9]*$/',$old))
            {
                $Folder->rm($tmp_path . DS . $old);
            }
        }

        // Save the uploaded file to the tmp folder

        $qqfile = Sanitize::getString($this->params, 'qqfile');

        if($qqfile) {

            $file = new qqUploadedFileXhr();
        }
        elseif (isset($_FILES['qqfile'])) {

            $file = new qqUploadedFileForm();
        }
        else {

            return $response;
        }

        $filename = $file->getName();

        $name = basename($filename, '.zip');

        preg_match('/(?P<name>[a-z.]+)(|_.*)/',$name,$matches);

        if(!$matches) return cmsFramework::jsonResponse($response);

        $name = $matches['name'];

        $tmp_file = cmsFramework::getConfig('tmp_path') . DS . $filename;

        $Folder = new s2Folder();

        if($name != '' && $file->save($tmp_file))
        {
            // Check if add-on is already installed or a folder with the same name exists in the add-ons directory

            if(file_exists(PATH_APP_ADDONS . DS . $name))
            {
                $Folder->rm(PATH_APP_ADDONS . DS . $name);
            }

            // The add-on folder is already in the zip so we don't create it again

            if(cmsFramework::packageUnzip($tmp_file, PATH_APP_ADDONS))
            {
                @unlink($tmp_file);

                if(file_exists(PATH_APP_ADDONS . DS . $name))
                {
                    // Add a record for the add-on to the database

                    $result = AdminPackagesComponent::installAddon(PATH_APP_ADDONS . DS . $name);

                    if($result)
                    {
                        $response['str'] = 'UPDATER_ADDON_INSTALLED';

                        $response['success'] = true;
                    }
                }
            }
        }

        return cmsFramework::jsonResponse($response);
    }
}