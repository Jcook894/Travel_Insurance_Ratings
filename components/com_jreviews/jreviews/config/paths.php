<?php
defined('MVC_FRAMEWORK') or die;

$Model = new S2Model;

$dbprefix = cmsFramework::getConfig('dbprefix');

$config_table_exists = $Model->query("SHOW TABLES LIKE '" . $dbprefix . "jreviews_config'", 'loadResult');

$debug_overrides_disable = 0;

if($config_table_exists)
{
    $overrides_query = "SELECT value FROM #__jreviews_config WHERE id = 'debug_overrides_disable'";

    $debug_overrides_disable = $Model->query($overrides_query, 'loadResult');
}

$addons_table_exists = $Model->query("SHOW TABLES LIKE '" . $dbprefix . "jreviews_addons'", 'loadResult');

if($addons_table_exists)
{
    $published_addons = $Model->query("SELECT name FROM " . $dbprefix . "jreviews_addons WHERE state = 1 ORDER BY ordering", 'loadColumn');
}

unset($Model);

$basePaths = array(
    S2_LIBS,
    PATH_APP . DS
);

if(file_exists(PATH_APP_ADDONS))
{
    $Folder = new S2Folder(PATH_APP_ADDONS);

    $contents = $Folder->ls();

    $addons = $contents[0];

    if($addons_table_exists)
    {
        $addons = array_intersect($published_addons, $addons);
    }

    foreach($addons AS $addon)
    {
        $basePaths[] = PATH_APP_ADDONS . DS . $addon . DS;
    }
}

if($debug_overrides_disable == 0)
{
    $basePaths['overrides'] =  PATH_APP_OVERRIDES . DS;
}

$relativePaths = array(
    'Lib' => '',
    'Controller' => array(
        'controller', // s2framework
        'controllers',
        'controllers' . DS . 'community_plugins',
        'controllers' . DS . 'modules',
        'controllers' . DS . 'shortcodes',
    ),
    'AdminController' => 'admin_controllers',
    'Component' => array(
        'controller' . DS . 'components', // s2framework
        'controllers' . DS . 'components',
        'repositories',
    ),
    'AdminJob' => array(
        'admin_jobs'
    ),
    'Job' => array(
        'jobs'
    ),
    'AdminComponent' => array(
        'admin_controllers' . DS . 'components',
        'admin_repositories',
        'admin_jobs'
    ),
    'Model' => array(
        'model', // s2framework
        'models',
        'models' . DS . 'community',
        'models' . DS . 'everywhere',
        'cms_compat' . DS . _CMS_NAME . DS . 'models',
        'cms_compat' . DS . _CMS_NAME . DS . 'models' . DS . 'community',
        'cms_compat' . DS . _CMS_NAME . DS . 'models' . DS . 'everywhere'
    ),
    'Helper' => array(
        'view' . DS . 'helpers', // s2framework
        'views' . DS . 'helpers',
        'cms_compat' . DS . _CMS_NAME . DS . 'views' . DS . 'helpers'
    ),
    'AdminHelper' => 'views' . DS . 'admin' . DS . 'helpers',
    'Plugin' => array(
        'plugins',
        'cms_compat' . DS . _CMS_NAME . DS . 'plugins'
        )
);

$themePath = 'views' . DS. 'themes';

$themePathAdmin = 'views' . DS . 'admin' . DS . 'themes';

$jsPath = 'views' . DS . 'js';

$jsPathAdmin = 'views' . DS . 'admin' . DS . 'js';
