<?php
/**
 * JReviews - Reviews Extension
 * Copyright (C) 2010-2015 ClickFWD LLC
 * This is not free software, do not distribute it.
 * For licencing information visit https://www.jreviews.com
 * or contact sales@jreviews.com
**/

defined( 'MVC_FRAMEWORK') or die( 'Direct Access to this location is not allowed.' );

class InstallController extends MyController
{
    var $uses = array('menu','field');

    var $helpers = array('html','admin/admin_routes');

    // var $components = array('config','admin/admin_packages');

    var $autoRender = false;

    var $autoLayout = false;

    var $layout = 'empty';

    function beforeFilter() {}

    function index()
    {
        $current_version = 0;

        $package_log = '';

        $Model = new MyModel;

        $dbprefix = cmsFramework::getConfig('dbprefix');

        if(!$this->ajaxRequest)
        {
            $this->autoLayout = true;
        }

        $this->name = 'install';

        $tables = $Model->getTableList('_jreviews_');

        if(is_array($tables) && in_array($dbprefix . 'jreviews_config',array_values($tables)))
        {
            $query = "SELECT value FROM #__jreviews_config WHERE id = 'jreviews-version'";

            $current_version = $Model->query($query, 'loadResult');
        }

        $new_version = cmsFramework::getAppVersion('jreviews');

        // Run SQL files

        $response = $this->AdminPackages->runUpgradesFiles('jreviews', 'jreviews_categories', PATH_APP, $current_version, $new_version);

        if(_CMS_NAME == 'joomla')
        {
            // Install additional packages

            $package_log = AdminPackagesComponent::installPackages(PATH_APP . DS . 'cms_compat' . DS . _CMS_NAME);

            # Update component id in pre-existing JReviews menus

            $query = "
                SELECT
                    extension_id AS id
                FROM
                    #__extensions
                WHERE
                    element = '".S2Paths::get('jreviews','S2_CMSCOMP')."' AND type = 'component'
            ";

            $id = $Model->query($query, 'loadResult');

            if($id)
            {
                    $query = "
                        UPDATE
                            `#__menu`
                        SET
                            component_id = $id
                        WHERE
                            type IN ('component','components')
                                AND
                            link LIKE 'index.php?option=".S2Paths::get('jreviews','S2_CMSCOMP')."%'
                    ";

                $Model->query($query);
            }
        }

        # Ensure that all field group names are slugs

        $query = "
            SELECT
                groupid, name
            FROM
                #__jreviews_groups
        ";

        $groups = $Model->query($query, 'loadAssocList');

        if(!empty($groups))
        {
            foreach($groups AS $group)
            {
                if(strpos($group['name'],' ')!== false)
                {
                    $name = cmsFramework::StringTransliterate($group['name']).$group['groupid'];

                    $query = "
                        UPDATE
                            #__jreviews_groups
                        SET
                            name = " . $this->Quote($name) . "
                        WHERE
                            groupid = " . $group['groupid']
                        ;

                    $Model->query($query);
                }
            }
        }

        # Clear data and core caches

        clearCache('', '__data');

        clearCache('', 'core');

        if($this->ajaxRequest)
        {
            return json_encode($response);
        }

        $this->set(array(
            'action'=>$response,
            'packages'=>$package_log,
            'license_exists'=>$Model->query("SELECT value FROM #__jreviews_license WHERE id = 'license'", 'loadResult')
        ));

        return $this->render('install','index');
    }

    # Tools to fix installation problems any time
    function _installfix()
    {
        if(!class_exists('JreviewsLocale')) {

            require(S2Paths::get('jreviews', 'S2_APP_LOCALE') . 'admin_locale.php' );
        }

        $task = Sanitize::getString($this->data,'task');

        $msg = '';

        $Model = new S2Model;

        switch($task) {

            case 'fix_content_fields':

                $output = '';

                $table = $Model->getTableColumns('#__jreviews_content');

                $columns = array_keys($table);

                $query = "
                    SELECT
                        name, type, maxlength
                    FROM
                        #__jreviews_fields
                    WHERE
                        location = 'content' AND type != 'banner'";

                $fields = $this->Field->query($query,'loadAssocList','name');

                foreach ($fields AS $field) {

                    if (!in_array($field['name'],$columns)) {

                        $output = $this->Field->addTableColumn($field,'content');
                    }
                }

                $query = "
                    DELETE
                    FROM
                        #__jreviews_fields
                    WHERE
                        name = ''";

                $output = $this->Field->query($query);

                break;

            case 'fix_review_fields':

                $output = '';

                $table = $Model->getTableColumns('#__jreviews_review_fields');

                $columns = array_keys($table);

                $query = "
                    SELECT
                        name, type
                    FROM
                        #__jreviews_fields
                    WHERE
                        location = 'review' AND type != 'banner'";

                $fields = $this->Field->query($query,'loadAssocList','name');

                foreach ($fields AS $field) {

                    if (!in_array($field->name,$columns)) {

                        $output = $this->Field->addTableColumn($field,'review');
                    }
                }

                $query = "
                    DELETE
                    FROM
                        #__jreviews_fields
                    WHERE
                        name = ''";

                $output = $this->Field->query($query);

                break;

            default:
                break;
        }

        cmsFramework::redirect(_CMS_ADMIN_ROUTE_BASE);
    }
}
