<?php
/**
 * JReviews - Reviews Extension
 * Copyright (C) 2010-2012 ClickFWD LLC
 * This is not free software, do not distribute it.
 * For licencing information visit http://www.reviewsforjoomla.com
 * or contact sales@reviewsforjoomla.com
**/

(defined( '_VALID_MOS') || defined( '_JEXEC')) or die( 'Direct Access to this location is not allowed.' );

# MVC initalization script
if (!defined('DS')) define('DS', DIRECTORY_SEPARATOR);    
require( dirname(__FILE__) . DS . 'framework.php');
        
global $Itemid;
$url = Sanitize::getString($_REQUEST, 'url');
$menu_id = Sanitize::getInt($_REQUEST,'Itemid',$Itemid);
$menu_id = $menu_id == 99999999 ? null : $menu_id;
$menu_params = array();

# Check if this is a custom route
$route['url']['url'] = $url; 
$route = S2Router::parse($route,false,'jreviews');
/*******************************************************************
 *                         ADMIN ROUTING
 ******************************************************************/
if(defined('MVC_FRAMEWORK_ADMIN')) 
{              
    // Ensure user has access to this function
    switch(getCmsVersion()) {
        case 'CMS_JOOMLA15':
            $User = & cmsFramework::getUser();
            $Acl = & cmsFramework::getACL();
            if ($Acl->acl_check( 'administration', 'manage', 'users', $User->usertype, 'components', S2Paths::get('jreviews','S2_CMSCOMP') )) {
                cmsFramework::redirect( 'index.php', JText::_('ALERTNOTAUTH') );
            }
        break;
        case 'CMS_JOOMLA16':
            if (!JFactory::getUser()->authorise('core.manage', S2Paths::get('jreviews','S2_CMSCOMP'))) {
                return JError::raiseWarning(404, JText::_('JERROR_ALERTNOAUTHOR'));
            }        
        break;
        default:
            die('Not authorized');
            break;
    }
    
    // Controller routing
    $act = Sanitize::getString($_REQUEST,'act');
    
    if($act == 'license') {
        $_GET['url'] = 'license';        
    } else {
        $_GET['url'] = Sanitize::getString($_GET,'url','about');        
    }
    
/*******************************************************************
 *                         FRONT-END ROUTING
 ******************************************************************/    
} 
elseif($menu_id && !isset($_POST['data']['controller']) && (!$url || !isset($route['data']['controller']) || preg_match('/^menu\//',$route['url']['url']))) 
{
    // If no task is passed in the url, then this is a menu item and we read the menu parameters        
    $segments = array();
    $url_param = $url;
    $url = str_replace('menu','',$url);
    
    $db = cmsFramework::getDB();
    $query = "SELECT * FROM #__menu WHERE id = " . $menu_id;
    $db->setQuery($query);
    $menu = end($db->loadObjectList());
    $mparams = getCmsVersion()==CMS_JOOMLA16 ? json_decode($menu->params,true) : stringToArray($menu->params);

    if(isset($mparams['action'])) 
    {
        $action = paramsRoute((int) $mparams['action']);

        $_REQUEST['Itemid'] = $_GET['Itemid'] = $menu->id; // For default - home page menu
            
        unset($mparams['action']);
        $menu_params['data'] = $mparams;
        
        $filters = array('dir'=>'dirid','section'=>'sectionid','cat'=>'catid','criteria'=>'criteriaid');
        foreach($filters AS $key=>$key2) {
            $menu_params[$key] = Sanitize::getVar($mparams,$key2);
            is_array($menu_params[$key]) and $menu_params[$key] = implode(',',$menu_params[$key]);
        }
        
//        $menu_params['url'] = 'menu';
        $menu_params['data']['component_menu'] = true;
        $menu_params['data']['controller'] = $action[0];
        $menu_params['data']['action'] = $action[1];
    }
}

$Dispatcher = new S2Dispatcher('jreviews');
echo $Dispatcher->dispatch($menu_params);
unset($db,$User,$menu,$Dispatcher);

function paramsRoute($action) {
    $a = array (
                "100"=>"m",
                "0"=>array('directories','index'),
                "1"=>array('categories','section'),
                "2"=>array('categories','category'),
                "3"=>array('listings','create'),
                "4"=>array('categories','toprated'),
                "5"=>array('categories','topratededitor'),
                "6"=>array('categories','latest'),
                "7"=>array('categories','popular'),
                "8"=>array('categories','mostreviews'),
                "9"=>array('categories','featured'),
                "10"=>array('reviews','myreviews'),
                "11"=>array('search','index'),
                "12"=>array('categories','mylistings'),
                "13"=>array('categories','favorites'),
                "14"=>array('reviews','latest'),
                "15"=>array('reviews','latest_user'),
                "16"=>array('reviews','latest_editor'),
                "17"=>array('discussions','latest'),
                "18"=>array('reviews','rankings'),
                "19"=>array('paidlistings','myaccount'),                
                "20"=>array('paidlistings_plans','index'),                
                "21"=>array('categories','custom')                
                );
    return $a[$action];
}