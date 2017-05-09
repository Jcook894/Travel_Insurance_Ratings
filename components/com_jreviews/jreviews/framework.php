<?php
defined('_JEXEC') or defined('ABSPATH') or die;

!defined('DS') and define('DS', DIRECTORY_SEPARATOR);

/**
 * Includes framework and
 * defines all application specific paths
 */

$S2_APP_NAME = 'jreviews';

if(defined('_JEXEC'))
{
    $_CMS = 'joomla';
}
elseif(defined('ABSPATH'))
{
    $_CMS = 'wordpress';
}

require_once(dirname(__FILE__) . DS . 'cms_compat' . DS . $_CMS . DS . 'defines.php');

if (!file_exists(PATH_S2 . DS . 's2framework' . DS . 'basics.php')) {
	?>
	<div style="font-size:12px;border:1px solid #000;background-color:#FBFBFB;padding:10px;">
	The S2 Framework required to run JReviews is not installed. Please install the com_s2framework component included in the JReviews package.
	</div>
	<?php
	exit;
}

require_once(PATH_S2 . DS . 's2framework' . DS . 'basics.php' );

S2Paths::set($S2_APP_NAME, 'S2_CMSCOMP','com_'.$S2_APP_NAME);

S2Paths::set($S2_APP_NAME, 'S2_APP', PATH_APP . DS);

S2Paths::set($S2_APP_NAME, 'S2_TMP', PATH_S2 . DS . 'tmp' . DS);

S2Paths::set($S2_APP_NAME, 'S2_CACHE', S2Paths::get($S2_APP_NAME,'S2_TMP') . 'cache' . DS);

S2Paths::set($S2_APP_NAME, 'S2_APP_CONFIG', S2Paths::get($S2_APP_NAME,'S2_APP') . 'config' . DS);

S2Paths::set($S2_APP_NAME, 'S2_APP_LOCALE', S2Paths::get($S2_APP_NAME,'S2_APP') . 'locale' . DS);

S2Paths::set($S2_APP_NAME, 'S2_MODELS_CMS', S2Paths::get($S2_APP_NAME,'S2_APP') . 'cms_compat' . DS . _CMS_NAME . DS . 'models' . DS );

S2Paths::set($S2_APP_NAME, 'S2_APP_URL', S2_APP_URL);

S2Paths::set($S2_APP_NAME, 'S2_VIEWS_URL', S2Paths::get($S2_APP_NAME,'S2_APP_URL') . 'views' . _DS);

S2Paths::set($S2_APP_NAME, 'S2_THEMES_URL', S2Paths::get($S2_APP_NAME,'S2_VIEWS_URL') . 'themes' . _DS);

$Model = new S2Model;

// Set secret key in global scope as constant

$dbprefix = cmsFramework::getConfig('dbprefix');

// Overcome host restrictions

$Model->query("SET SQL_BIG_SELECTS=1");

$config_table_exists = $Model->query("SHOW TABLES LIKE '" . $dbprefix . "jreviews_config'", 'loadResult');

if(!defined('_CMS_SECRET_KEY') && $config_table_exists)
{
    $secret_key = $Model->query("SELECT value FROM #__jreviews_config WHERE id = 'cms_secret'", 'loadResult');

    if($secret_key != '')
    {
        define('_CMS_SECRET_KEY', $secret_key);
    }
}

if(defined('MVC_FRAMEWORK_ADMIN') && $config_table_exists)
{
    # Set default theme

    $fallback_theme = $Model->query("SELECT value FROM #__jreviews_config WHERE id = 'fallback_theme'", 'loadResult');

    S2Paths::set($S2_APP_NAME, 'S2_FALLBACK_THEME', $fallback_theme != '' ? $fallback_theme : 'default');
}

/**
 * Generate the File Registry
 */

unset($Model);

require_once( dirname(__FILE__) . DS . 'config' . DS . 'core.php' );

$Configure = Configure::getInstance($S2_APP_NAME);

$s2App = S2App::getInstance($S2_APP_NAME);

# Set app variable in I18n class
$import = S2App::import('Lib','I18n');

if(!$import)
{
    $clear = clearCache('','core');

    if(!$clear){
        echo 'You need to delete the file registry in ' . PATH_S2 . '/tmp/cache/core/';
        exit;
    }

    $page = $_SERVER['PHP_SELF'];

    header("Location: " . _CMS_ADMIN_ROUTE_BASE);

    exit;
}

$Translate = I18n::getInstance();

$Translate->app = $S2_APP_NAME;

# Load app files ...
if(defined('MVC_FRAMEWORK_ADMIN'))
{
	S2App::import( 'admin_controller', 'my', 'jreviews' );
}
else {
	S2App::import( 'controller', 'my', 'jreviews' );
}

S2App::import( 'model', 'my_model', 'jreviews' );