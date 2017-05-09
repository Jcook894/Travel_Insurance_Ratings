<?php
/*
    JReviews Express - user reviews for Joomla
    Copyright (C) 2009  Alejandro Schmeichler

    JReviews Express is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    JReviews Express is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/

$app = 'jrexpress';

if (!defined('DS')) define('DS', DIRECTORY_SEPARATOR);

if(!defined('S2_ROOT')) {
	define('S2_ROOT', dirname(dirname(dirname(__FILE__))) . DS . 'com_s2framework');
}

if (!file_exists(S2_ROOT . DS . 's2framework' . DS . 'basics.php')) {
	?>
	<div style="font-size:12px;border:1px solid #000;background-color:#FBFBFB;padding:10px;">
	The S2 Framework required to run JReviews Express is not installed. Please install the com_s2framework component included in the JReviews Express package.
	</div>
	<?php
	exit;
} 

if(!defined('MVC_FRAMEWORK')) require( S2_ROOT . DS . 's2framework' . DS . 'basics.php' );

S2Paths::set($app, 'S2_APP_DIR',$app);	
S2Paths::set($app, 'S2_CMSCOMP','com_'.$app);	
S2Paths::set($app, 'S2_APP', PATH_ROOT . 'components' . DS . 'com_'.$app . DS. $app . DS);
S2Paths::set($app, 'S2_APP_URL', WWW_ROOT . 'components'. _DS . 'com_'.$app . _DS . $app . _DS);	
S2Paths::set($app, 'S2_TMP', S2_ROOT . DS . 'tmp' . DS);	
S2Paths::set($app, 'S2_LOGS', S2Paths::get($app,'S2_TMP') . 'logs' . DS);	
S2Paths::set($app, 'S2_CACHE', S2Paths::get($app,'S2_TMP') . 'cache' . DS);	
S2Paths::set($app, 'S2_APP_CONFIG', S2Paths::get($app,'S2_APP') . 'config' . DS);

S2Paths::set($app, 'S2_MODELS', S2Paths::get($app,'S2_APP') . 'models' . DS);	
S2Paths::set($app, 'S2_CONTROLLERS', S2Paths::get($app,'S2_APP') . 'controllers' . DS);	
S2Paths::set($app, 'S2_COMPONENTS', S2Paths::get($app,'S2_CONTROLLERS') . 'components' . DS);	
S2Paths::set($app, 'S2_VIEWS', S2Paths::get($app,'S2_APP') . 'views' . DS);	
S2Paths::set($app, 'S2_HELPERS', S2Paths::get($app,'S2_VIEWS') . 'helpers' . DS);	
S2Paths::set($app, 'S2_THEMES', S2Paths::get($app,'S2_VIEWS') . 'themes' . DS);	
S2Paths::set($app, 'S2_JS', S2Paths::get($app,'S2_VIEWS') . 'js' . DS);	

S2Paths::set($app, 'S2_VIEWS_URL', S2Paths::get($app,'S2_APP_URL') . 'views' . _DS);
S2Paths::set($app, 'S2_THEMES_URL', S2Paths::get($app,'S2_VIEWS_URL') . 'themes' . _DS);			
S2Paths::set($app, 'S2_IMAGES_URL', S2Paths::get($app,'S2_VIEWS_URL') . 'images' . _DS);			
S2Paths::set($app, 'S2_CSS_URL', S2Paths::get($app,'S2_VIEWS_URL') . 'css' . _DS);			

S2Paths::set($app, 'S2_CMS_ADMIN', PATH_ROOT . 'administrator' . DS . 'components' . DS . S2Paths::get($app,'S2_CMSCOMP') . DS );			
S2Paths::set($app, 'S2_ADMIN_CONTROLLERS', S2Paths::get($app,'S2_APP') . 'admin_controllers' . DS);			
S2Paths::set($app, 'S2_ADMIN_COMPONENTS', S2Paths::get($app,'S2_ADMIN_CONTROLLERS') . 'components' . DS);			
S2Paths::set($app, 'S2_ADMIN_VIEWS', S2Paths::get($app,'S2_APP') . 'views' . DS . 'admin' . DS);			
S2Paths::set($app, 'S2_ADMIN_HELPERS', S2Paths::get($app,'S2_ADMIN_VIEWS') . 'helpers' . DS);
	
S2Paths::set($app, 'S2_ADMIN_VIEWS_URL', S2Paths::get($app,'S2_VIEWS_URL') . 'admin' . _DS);			
S2Paths::set($app, 'S2_CSS_ADMIN_URL', S2Paths::get($app,'S2_ADMIN_VIEWS_URL') . 'css' . _DS);			
S2Paths::set($app, 'S2_JS_ADMIN_URL', S2Paths::get($app,'S2_ADMIN_VIEWS_URL') . 'js' . _DS);

/**
 * Definition for Override paths
 */
S2Paths::set($app, 'S2_APP_OVERRIDES', PATH_ROOT . 'templates' . DS . 'jrexpress_overrides' . DS);
S2Paths::set($app, 'S2_APP_URL_OVERRIDES', WWW_ROOT . 'templates' . _DS . 'jrexpress_overrides' . _DS);
S2Paths::set($app, 'S2_VIEWS_OVERRIDES', S2Paths::get($app,'S2_APP_OVERRIDES') . 'views' . DS);	
S2Paths::set($app, 'S2_HELPERS_OVERRIDES', S2Paths::get($app,'S2_VIEWS_OVERRIDES') . 'helpers' . DS);	
S2Paths::set($app, 'S2_THEMES_OVERRIDES', S2Paths::get($app,'S2_VIEWS_OVERRIDES') . 'themes' . DS);	
S2Paths::set($app, 'S2_JS_OVERRIDES', S2Paths::get($app,'S2_VIEWS_OVERRIDES') . 'js' . DS);	

S2Paths::set($app, 'S2_VIEWS_URL_OVERRIDES', S2Paths::get($app,'S2_APP_URL_OVERRIDES') . 'views' . _DS);
S2Paths::set($app, 'S2_THEMES_URL_OVERRIDES', S2Paths::get($app,'S2_VIEWS_URL_OVERRIDES') . 'themes' . _DS);			

S2Paths::set($app, 'S2_IMAGES_URL_OVERRIDES', S2Paths::get($app,'S2_VIEWS_URL_OVERRIDES') . 'images' . _DS);			
S2Paths::set($app, 'S2_CSS_URL_OVERRIDES', S2Paths::get($app,'S2_VIEWS_URL_OVERRIDES') . 'css' . _DS);			
S2Paths::set($app, 'S2_JS_URL_OVERRIDES', S2Paths::get($app,'S2_VIEWS_URL_OVERRIDES') . 'js' . _DS);			

S2Paths::set($app, 'S2_ADMIN_VIEWS_OVERRIDES', S2Paths::get($app,'S2_APP_OVERRIDES') . 'views' . DS . 'admin' . DS);			
S2Paths::set($app, 'S2_ADMIN_HELPERS_OVERRIDES', S2Paths::get($app,'S2_ADMIN_VIEWS_OVERRIDES') . 'helpers' . DS);			
S2Paths::set($app, 'S2_ADMIN_VIEWS_URL_OVERRIDES', S2Paths::get($app,'S2_VIEWS_URL_OVERRIDES') . 'admin' . _DS);			
S2Paths::set($app, 'S2_CSS_ADMIN_URL_OVERRIDES', S2Paths::get($app,'S2_ADMIN_VIEWS_URL_OVERRIDES') . 'css' . _DS);			
S2Paths::set($app, 'S2_JS_ADMIN_URL_OVERRIDES', S2Paths::get($app,'S2_ADMIN_VIEWS_URL_OVERRIDES') . 'js' . _DS);
                  
// Create the file registry
$Configure =  &Configure::getInstance($app);

$App = &App::getInstance($app);
require_once( dirname(__FILE__) . DS . 'config' . DS . 'core.php' );

# Set app variable in I18n class
App::import('Lib','I18n','jrexpress');
    
$Translate =  & I18n::getInstance();
$Translate->app = $app;
  
# Load app files ...
if(defined('MVC_FRAMEWORK_ADMIN')) {
	App::import( 'admin_controller', 'my', 'jrexpress' );
} else {
	App::import( 'controller', 'my', 'jrexpress' );
}
  
App::import( 'model', 'my_model', 'jrexpress' );
