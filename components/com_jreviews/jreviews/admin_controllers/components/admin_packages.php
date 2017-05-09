<?php
/**
 * JReviews - Reviews Extension
 * Copyright (C) 2010-2015 ClickFWD LLC
 * This is not free software, do not distribute it.
 * For licencing information visit https://www.jreviews.com
 * or contact sales@jreviews.com
**/

defined( 'MVC_FRAMEWORK') or die( 'Direct Access to this location is not allowed.' );

S2App::import('Model','addon','jreviews');

class AdminPackagesComponent extends S2Component {

    var $c;

    function startup(&$controller)
    {
        $this->c = & $controller;
    }

    public static function readManifest($path, $type = '')
    {
        $array = array();

        $manifest_paths = array();

        $info_paths = array();

        $xml_manifest = false;

        $orig_path = $path;

        if(is_dir($orig_path))
        {
            $Folder = new S2Folder($orig_path);

            $manifest_paths = $Folder->findRecursive('.*\.xml');

            if(!empty($manifest_paths))
            {
                $path = $manifest_paths[0];
            }
        }

        $parts = pathinfo($path);

        if(file_exists($path) && Sanitize::getString($parts,'extension') == 'xml')
        {
            $xml = simplexml_load_file($path);

            $data = json_encode($xml);

            $array = json_decode($data, true);

            if(!empty($array) && $xml->getName() == $type)
            {
                $xml_manifest = true;

                $array['manifest'] = $data;
            }
            else {

                $array = array();
            }
        }

        // Backwards compatibility with .info.php files

        if(!$xml_manifest && is_dir($orig_path)) {

            $Folder = new S2Folder($orig_path);

            $info_paths = $Folder->findRecursive('.*\.info.php');

            if(!empty($info_paths))
            {
                $path = $info_paths[0];
            }
            else {

                return false;
            }

            ob_start();

            include($path);

            $array = json_decode(ob_get_contents(), true);

            ob_end_clean();

            $array['title'] = $array['name'];

            $array['name'] = str_replace('.info','',pathinfo($path, PATHINFO_FILENAME));

            $array['author'] = '';

            $array['created'] = '';

            $array['beta'] = Sanitize::getInt($array,'is_beta');

            $array['manifest'] = json_encode($array);
        }

        if(!empty($array))
        {
            $array['path'] = $path;

            return $array;
        }

        return false;
    }

    public static function installAddon($path)
    {
        if($addon = AdminPackagesComponent::readManifest($path, 'addon'))
        {
            $AddonModel = new AddonModel();

            $data = array('Addon'=>array(
                'title'=>$addon['title'],
                'name'=>$addon['name'],
                'manifest'=>$addon['manifest'],
                'state'=>1
                ));

            // Check if already installed so we update instead

            $id = $AddonModel->findOne(array(
                'fields'=>array('id'),
                'conditions'=>array('name = "' . $addon['name']. '"')
                ));

            if($id) {

                $data['Addon']['id'] = $id;
            }

            return $AddonModel->store($data);
        }

        return false;
    }

    public static function removeAddon($name)
    {
        $AddonModel = new AddonModel();

        $id = $AddonModel->findOne(array(
            'fields'=>array('Addon.id'),
            'conditions'=>array('Addon.name = ' . $AddonModel->Quote($name))
        ));

        if($id)
        {
            return $AddonModel->delete('id', array($id));
        }

        return false;
    }

    public function upgradeAddon()
    {
        $addon_name = $this->c->addon_name;

        $current_version = Sanitize::getString($this->c->Config, $addon_name . '-version');

        $_addon = AdminPackagesComponent::readManifest(PATH_APP_ADDONS . DS . $addon_name, 'addon');

        $new_version = $_addon['version'];

        $ref_table = Sanitize::getString($this->c,'addon_ref_table');

        $response = $this->runUpgradesFiles($addon_name, $ref_table, PATH_APP_ADDONS . DS . $addon_name . DS, $current_version, $new_version);

        // Install additional packages

        $response['packages'] = self::installPackages(PATH_APP_ADDONS . DS . $addon_name . DS . 'cms_compat' . DS . _CMS_NAME);

        return $response;
    }

    public function runUpgradesFiles($name, $ref_table, $path, $current, $new)
    {
        $response = array('success'=>true,'str'=>array());

        $Model = new S2Model;

        $tables = $Model->getTableList();

        $dbname = cmsFramework::getConfig('db');

        $dbprefix = cmsFramework::getConfig('dbprefix');

        $upgrade_path = $path . DS . 'upgrades';

        $force_upgrade = Sanitize::getInt($_GET,'sql') == 1 || $current == 0;

        // If there isn't a reference DB table, then there's nothing to do here

        if(!$ref_table || !is_dir($upgrade_path))
        {
            return $response;
        }

        if(is_array($tables) && in_array($dbprefix . $ref_table, array_values($tables)))
        {
            // Read upgrades folder
            $Folder = new S2Folder($upgrade_path);

            $exclude = array('.', $name . '.sql', $name . '.php', 'index.html');

            $files = $Folder->read(true,$exclude);

            $files = array_pop($files);

            if(!empty($files))
            {
                // Re-order by version

                foreach($files AS $file) {

                    // get the version number from the filename
                    $pathinfo = pathinfo($file);

                    $extension = $pathinfo['extension'];

                    $pathparts = explode('_',$pathinfo['filename']);

                    $version = array_pop($pathparts);

                    $filesVersion[self::paddedVersion($version).$extension] = $file;
                }

                ksort($filesVersion);

                foreach($filesVersion AS $file)
                {
                    // get the version number from the filename
                    $pathinfo = pathinfo($file);

                    $extension = $pathinfo['extension'];

                    $pathparts = explode('_',$pathinfo['filename']);

                    $version = array_pop($pathparts);

                    $filepath = $upgrade_path . DS . $file;

                    // Run upgrade files if the upgrade file version is higher than the current installed version

                    if($force_upgrade == 1
                        ||
                        self::paddedVersion($current) == 0
                        ||
                        (self::paddedVersion($version) > self::paddedVersion($current))
                    ) {
                        if($extension == 'sql')
                        {
                            try {

                                $response['success'] = self::parseMysqlDump($filepath);

                                $response['str'][$file] = true;

                            }
                            catch(Exception $e) {

                                $response['success'] = false;

                                $response['str'][$file] = $e->getMessage();
                            }
                        }

                        if($extension == 'php')
                        {
                            try {
                                include($filepath);

                                $response['success'] = true;;

                            }
                            catch(Exception $e) {

                                $response['success'] = false;

                                $response['str'][$file] = $e->getMessage();
                            }
                        }
                    }
               }

               if($response['success']) {

                   $this->c->Config->store((object) array($name . '-version'=>$new));
               }
            }
        }
        else {

            # It's a clean install so we use the complete sql file

            $file = $name .'.sql';

            $filepath = $path . DS . 'upgrades' . DS . $file;

            if(file_exists($filepath))
            {
                try {

                    $response['success'] = self::parseMysqlDump($filepath);

                    $response['str'][$file] = true;
                }
                catch(Exception $e) {

                    $response['success'] = false;

                    $response['str'][$file] = $e->getMessage();
                }
            }

            $file = $name .'.php';

            $filepath = $path . DS . 'upgrades' . DS . $file;

            if(file_exists($filepath))
            {
                try {
                    include($filepath);

                    $response['success'] = true;;

                }
                catch(Exception $e) {

                    $response['success'] = false;

                    $response['str'][$file] = $e->getMessage();
                }
            }

           if($response['success']) {

                $this->c->Config->store((object) array($name . '-version'=>$new));
           }
        }

        return $response;
    }

    static function installPackages($path)
    {
        $log = array();

        $_PACKAGES_PATH = $path . DS . 'packages' . DS;

        if(!file_exists($_PACKAGES_PATH . 'packages.json') || _CMS_NAME != 'joomla')
        {
            return $log;
        }

        $packages = json_decode(file_get_contents($_PACKAGES_PATH . 'packages.json'),true);

        $Model = new S2Model;

        $log = array();

        /**
         * Process Modules
         */

        if(isset($packages['modules']))
        {
            $Installer = new JInstaller;

            foreach($packages['modules'] AS $params)
            {
                $result = false;

                extract($params);

                if(file_exists($_PACKAGES_PATH . 'modules' . DS . $name))
                {
                    $result = $Installer->install($_PACKAGES_PATH . 'modules' . DS . $name);
                }

                $log[] = array('name'=>$title,'status'=>$result,'type'=>'module');
            }
        }

        /**
         * Process Plugins
         */

        if(isset($packages['plugins']))
        {
            $Installer = new JInstaller;

            foreach($packages['plugins'] AS $params)
            {
                $result = false;

                extract($params);

                if(file_exists($_PACKAGES_PATH . 'plugins' . DS . $type . DS . $name))
                {
                    $result = $Installer->install($_PACKAGES_PATH . 'plugins' . DS . $type . DS . $name);
                }

                $log[] = array('name'=>$title,'status'=>$result,'type'=>'plugin');

                // Automatically publish the plugin if the state param is set to 1

                if(Sanitize::getInt($params,'state') == 1)
                {
                    $query = "
                        UPDATE
                            #__extensions
                        SET
                            enabled = 1
                        WHERE
                            type = 'plugin' AND element = " . $Model->Quote($name) . " AND folder = " . $Model->Quote($type)
                    ;

                    $Model->query($query);
                }
            }
        }

        /**
         * Process EasySocial Apps
         */
        if(file_exists(JPATH_ADMINISTRATOR . '/components/com_easysocial/includes/foundry.php')
            && isset($packages['easysocialapps']))
        {
            require_once(JPATH_ADMINISTRATOR . '/components/com_easysocial/includes/foundry.php');

            if (class_exists('ES'))
            {
                $EasySocialInstaller  = ES::get( 'Installer' );

                foreach($packages['easysocialapps'] AS $params)
                {
                    $result = false;

                    extract($params);

                    if(file_exists($_PACKAGES_PATH . 'easysocialapps' . DS . $name))
                    {
                        $EasySocialInstaller->load($_PACKAGES_PATH . 'easysocialapps' . DS . $name);

                        $result = $EasySocialInstaller->install();
                    }

                    $log[] = array('name'=>$title,'status'=>$result,'type'=>'plugin');
                }
            }
        }

        return $log;
    }

    static function uninstallPackages($path)
    {
        $log = array();

       if(_CMS_NAME != 'joomla') return $log;

        $_PACKAGES_PATH = $path . DS . 'packages' . DS;

        if(!file_exists($_PACKAGES_PATH . 'packages.json'))
        {
            return $log;
        }

        $packages = json_decode(file_get_contents($_PACKAGES_PATH . 'packages.json'),true);

        $Model = new S2Model;

        $Installer = new JInstaller;

        if(isset($packages['modules']))
        {
            foreach($packages['modules'] AS $params)
            {
                extract($params);

                $element = $name;

                $query = "
                    SELECT
                        extension_id
                    FROM
                        #__extensions
                    WHERE
                        type = 'module' AND element = " . $Model->Quote($element) . "
                ";

                $id = $Model->query($query,'loadResult');

                $result = $Installer->uninstall('module', $id);

                $log[] = array('name'=>Sanitize::getString($params,'title'),'status'=>$result,'type'=>'module');
            }
        }

        if(isset($packages['plugins']))
        {
            foreach($packages['plugins'] AS $plugin=>$params)
            {
                $part = explode('/', $module);

                $element = array_pop($part);

                $type = Sanitize::getString($params,'type');

                $query = "
                    SELECT
                        extension_id
                    FROM
                        #__extensions
                    WHERE
                        type = 'plugin' AND element = " . $Model->Quote($element) . " AND folder = " . $Model->Quote($type)
                ;

                $id = $Model->query($query,'loadResult');

                $result = $Installer->uninstall('plugin', $id);

                $log[] = array('name'=>Sanitize::getString($params,'title'),'status'=>$result,'type'=>'plugin');
            }
        }

        return $log;
    }

    static function parseMysqlDump($file)
    {
        $dbprefix = cmsFramework::getConfig('dbprefix');

        $Model = new S2Model;

        $file_content = file($file);

        $sql = array();

        foreach($file_content as $sql_line)
        {
             if(trim($sql_line) != '' && strpos($sql_line, '--') === false)
             {
                 $sql[] =  str_replace('#__',$dbprefix, $sql_line);
             }
        }

        $sql = implode('',$sql);

        $sql = explode(';',$sql);

        $result = true;

        foreach($sql AS $sql_line)
        {
            if(trim($sql_line) != '' && trim($sql_line) != ';')
            {
                $sql_line .= ';';

                $out = $Model->query($sql_line);

                if(
                    strstr(strtolower($Model->getErrorMsg()),"drop")
                    ||
                    strstr(strtolower($Model->getErrorMsg()),"duplicate")
                    ||
                    strstr(strtolower($Model->getErrorMsg()),"already exists")
                ) {
//                    echo '1.<br />';
//                    echo $Model->getErrorMsg();
//                    echo '<p>'.$sql_line.'</p>';
//                    echo (int)$result . '<br />';
                }
                else
                {
//                    echo '2.<br />';
//                    echo $Model->getErrorMsg();
//                    echo '<p>'.$sql_line.'</p>';
//                    echo (int)$result . '<br />';

                    $result = $out && $result;
                }

               $Model->getErrorMsg() != '' and appLogMessage($Model->getErrorMsg(),'install',true);
            }
        }

        return $result;
    }

    /**
     * Returns padded version number to be able to compare across major and minor versions and different builds
     * @param  string $version dotted version number
     * @return integer
     */
    public static function paddedVersion($version) {

        // Need to pad 3rd and 4th numbers to 3 digits
        $current_array = explode('.',$version);

        isset($current_array[2]) and $current_array[2] = str_pad($current_array[2],3,0,STR_PAD_LEFT);

        isset($current_array[3]) and $current_array[3] = str_pad($current_array[3],3,0,STR_PAD_LEFT);

        $version = implode('',$current_array);

        return (int) $version;
    }

    public static function isNewVersion($current, $new)
    {
        return self::paddedVersion($current) < self::paddedVersion($new);
    }
}