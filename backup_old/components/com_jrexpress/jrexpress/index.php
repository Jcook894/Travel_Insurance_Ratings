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

(defined( '_VALID_MOS') || defined( '_JEXEC')) or die( 'Direct Access to this location is not allowed.' );

# MVC initalization script
if (!defined('DS')) define('DS', DIRECTORY_SEPARATOR);	
require( dirname(__FILE__) . DS . 'framework.php');
		
global $Itemid;
$url = Sanitize::getString($_REQUEST, 'url');
$menu_id = Sanitize::getInt($_REQUEST,'Itemid',$Itemid);
$menu_id = $menu_id == 99999999 ? null : $menu_id;
$xajax = Sanitize::getInt($_REQUEST,'xajax');
$menu_params = array();

# Check if this is a custom route
$route['url']['url'] = $url;
$route = S2Router::parse($route,false,'jrexpress');

/*******************************************************************
 * 						ADMIN ROUTING
 ******************************************************************/
if(defined('MVC_FRAMEWORK_ADMIN')) 
{	
	// Instantiate Framework Object
	$cms = new stdClass();	
	cmsFramework::init($cms);
	
	// Ensure user has access to this function
	if ($cms->_acl->acl_check( 'administration', 'manage', 'users', $cms->_user->usertype, 'components', S2Paths::get('jrexpress','S2_CMSCOMP') )) {
		cmsFramework::redirect( 'index.php', JText::_('ALERTNOTAUTH') );
	}
    
    $_GET['url'] = Sanitize::getString($_GET,'url','about');        
	
/*******************************************************************
 * 						FRONT-END ROUTING
 ******************************************************************/	
} elseif ($menu_id && !$xajax && !isset($_POST['data']['controller']) && (!$url || !isset($route['data']['controller']) || preg_match('/^menu\//',$route['url']['url']))) {

	// If no task is passed in the url, then this is a menu item and we read the menu parameters		
	$segments = array();
	$url_param = $url;
	$url = str_replace('menu','',$url);
	
	$cms = new stdClass();
	cmsFramework::init($cms);
	$query = "SELECT * FROM #__menu WHERE id = " . $menu_id;
	$cms->_db->setQuery($query);
	$menu = end($cms->_db->loadObjectList());

	$mparams = stringToArray($menu->params);

	if(isset($mparams['action'])) {
		$action = paramsRoute((int) $mparams['action']);

		$_REQUEST['Itemid'] = $_GET['Itemid'] = $menu->id; // For default - home page menu
			
		unset($mparams['action']);
		$menu_params['data'] = $mparams;
		$menu_params['section'] = $mparams['sectionid'];
		$menu_params['cat'] = $mparams['catid'];
		
//		$menu_params['url'] = 'menu';
		$menu_params['data']['component_menu'] = true;
		$menu_params['data']['controller'] = $action[0];
		$menu_params['data']['action'] = $action[1];
	}
}

$Dispatcher = new S2Dispatcher('jrexpress',true);
echo $Dispatcher->dispatch($menu_params);

function paramsRoute($action) 
{
	$a = array (
                "100"=>"m",
                "3"=>array('listings','create'),
                "4"=>array('categories','toprated'),
                "6"=>array('categories','latest'),
                "7"=>array('categories','popular'),
                "8"=>array('categories','mostreviews'),
                "10"=>array('reviews','myreviews'),
                "12"=>array('categories','mylistings')
                );   
	return $a[$action];
}