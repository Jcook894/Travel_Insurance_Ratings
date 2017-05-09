<?php
/*********************************************************************
 * DEFINE CMS CONSTANTS
 *********************************************************************/

define('MVC_FRAMEWORK', 1);

define('_PARAM_CHAR',':');

!defined('DS') and define('DS', DIRECTORY_SEPARATOR);

!defined('_DS') and define('_DS','/');

$https = false;

if (isset($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) === 'on')
{
	$https = true;
}
elseif (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https')
{
	$https = true;
}
elseif (isset($_SERVER['HTTP_FRONT_END_HTTPS']) && $_SERVER['HTTP_FRONT_END_HTTPS'] === 'on')
{
	$https = true;
}

$domain = ($https == true
            ?
            'https' : 'http') . '://' . (isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST']
            :
            $_SERVER['SERVER_NAME']);

$folder = dirname($_SERVER['SCRIPT_NAME']);

$folder = str_replace('/administrator','',$folder);

if($folder == DS || $folder == '.') $folder = _DS; // Fixes issue on IIS

$domain .= $folder != '/' ? $folder . '/' : $folder;

define('WWW_ROOT',$domain);

define('WWW_ROOT_REL', rtrim($folder, '/') . '/');

define('PATH_ROOT', JPATH_SITE . DS);

define('PATH_APP_ROOT', PATH_ROOT . 'components' . DS . 'com_jreviews');

define('PATH_APP_REL', 'components' . DS . 'com_jreviews' . DS . 'jreviews');

define('PATH_APP', PATH_ROOT . PATH_APP_REL);

define('PATH_APP_ADDONS',  PATH_ROOT . 'components' . DS . 'com_jreviews_addons');

define('WWW_ADDONS_REL',  'components' . DS . 'com_jreviews_addons');

define('PATH_APP_OVERRIDES', PATH_ROOT . 'templates' . DS . 'jreviews_overrides');

define('PATH_S2', PATH_ROOT . 'components' . DS . 'com_s2framework');

define('_CMS_NAME', 'joomla');

define('_CMS_ADMIN_ROUTE_BASE', 'index.php?option=com_jreviews');

define('S2_CMS_ADMIN', PATH_ROOT . 'administrator/components/com_jreviews/' );

define('S2_APP_URL', WWW_ROOT_REL . 'components/com_jreviews/jreviews/');

// URL PARAMETERS

define('S2_QVAR_PAGE','page');

define('S2_QVAR_MEDIA_CODE','m');

define('S2_QVAR_PREFIX_RATING_CRITERIA','rating');

define('S2_QVAR_PREFIX_EDITOR_RATING_CRITERIA','editor_rating');

define('S2_QVAR_RATING_AVG','rating');

define('S2_QVAR_EDITOR_RATING_AVG','editor_rating');