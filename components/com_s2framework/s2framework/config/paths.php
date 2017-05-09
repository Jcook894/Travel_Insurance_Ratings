<?php
/**
 * S2Framework
 * Copyright (C) 2010-2015 ClickFWD LLC
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 * @license       http://www.opensource.org/licenses/mit-license.php The MIT License
**/

defined('MVC_FRAMEWORK') or die;

/*********************************************************************
 * DEFINE PATHS
 *********************************************************************/
define('MVC_ADMIN', 'admin');

define('S2_APP_DIR', $S2_APP_NAME);
define('S2_APP', PATH_APP . DS . $S2_APP_NAME . DS);
define('S2_TMP', PATH_S2 . DS . 'tmp' . DS);
define('S2_LOGS', S2_TMP . 'logs' . DS);
define('S2_CACHE', S2_TMP . 'cache' . DS);
define('S2_MODELS', S2_APP . 'models' . DS);
define('S2_CONTROLLERS', S2_APP . 'controllers' . DS );
define('S2_COMPONENTS', S2_CONTROLLERS . 'components' . DS);
define('S2_VIEWS', S2_APP . 'views' . DS);
define('S2_HELPERS', S2_VIEWS . 'helpers' . DS);

// Define framework paths common to all applications

define('S2_FRAMEWORK', PATH_S2 . DS . 's2framework');
define('S2_LIBS', S2_FRAMEWORK . DS . 'libs' . DS);
define('S2_VENDORS', PATH_S2 . DS . 'vendors' . DS);
define('S2_CACHE_DATA', S2_CACHE . '__data' . DS);

// Cake compatibility definitions

define('APP_DIR', S2_APP_DIR);
define('WEBROOT_DIR', WWW_ROOT);
define('APP_PATH', S2_APP);
define('CACHE',S2_CACHE);
define('MODELS',S2_MODELS);
define('BEHAVIORS',S2_MODELS . 'behaviors');
define('CONTROLLERS',S2_CONTROLLERS);
define('COMPONENTS',S2_COMPONENTS);
define('VIEWS',S2_VIEWS);
define('HELPERS',S2_HELPERS);
define('APP',S2_APP);
define('TMP',S2_TMP);
define('LIBS', S2_LIBS);
define('VENDORS', S2_VENDORS);
