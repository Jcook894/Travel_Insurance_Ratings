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

# Only run in frontend
if(isset($_SERVER['REQUEST_URI']) && strpos($_SERVER['REQUEST_URI'],'administrator')) {
	return;
}

if ((string) @$_GET['option'] != 'com_content' && (string) @$_GET['option'] != 'com_frontpage' && (string) @$_GET['option'] != '') {
	return;
}

# MVC initalization script
if (!defined('DS')) define('DS', DIRECTORY_SEPARATOR);
require('components' . DS . 'com_jrexpress' . DS . 'jrexpress' . DS . 'framework.php');

cmsFramework::init($CMS);
		
$option = Sanitize::getString($_REQUEST, 'option', '');
$task = Sanitize::getString($_REQUEST, 'task', '');
$view = Sanitize::getString($_REQUEST, 'view', '');
$layout = Sanitize::getString($_REQUEST, 'layout', '');
$id = explode(':',Sanitize::getInt($_REQUEST,'id'));
$id = $id[0];


$query = "SELECT published,params FROM #__plugins WHERE element = 'jrexpress' AND folder = 'content' LIMIT 1";
$CMS->_db->setQuery($query);
$jrbot = current($CMS->_db->loadObjectList());

$params = stringToArray($jrbot->params);
$published = $jrbot->published;

if ((int) !$published) {
	return;
}
$frontpageOff =  Sanitize::getVar($params,'frontpage');
$blogLayoutOff =  Sanitize::getVar($params, 'blog');

if($blogLayoutOff && $option=='com_content' && ($view == 'category' || $view == 'section') && ($layout == 'blog' || $layout == 'blogfull')) {
    return;
} elseif (($frontpageOff && $view == 'frontpage')) {
	return ;
}

/* Register the Plugin */
$mainframe->registerEvent( 'onPrepareContent', 'jrexpress_content' );

function jrexpress_content(&$row,&$params,&$page) 
{
	if(isset($row->id)) {
		// Check whether to perform the replacement or not
		$option = Sanitize::getString($_REQUEST, 'option', '');
		$view = Sanitize::getString($_REQUEST, 'view', '');
		$layout = Sanitize::getString($_REQUEST, 'layout', '');
		$id = explode(':',Sanitize::getInt($_REQUEST,'id'));
		$id = $id[0];
		
		$Dispatcher = new S2Dispatcher('jrexpress',true);
	
		if ($option=='com_content' && $view == 'article' & $id > 0) {
			
			$_GET['url'] = 'com_content/com_content_view';
		
		} elseif ($option=='com_content' && ((($layout == 'blog' || $layout == 'blogfull') && ($view=='category' || $view=='section')) || $view == 'frontpage')) {

			$_GET['url'] = 'com_content/com_content_blog';
	
		} 
	
		$passedArgs = array(
		    'params'=>$params,
		    'row'=>$row,
		    'component'=>'com_content'
		    );
	
		$passedArgs['cat'] = $row->catid;
		$passedArgs['section'] = $row->sectionid;
		$passedArgs['listing_id'] = $row->id;
		
		$output = $Dispatcher->dispatch($passedArgs);
	
		if($output){
			$row = $output['row'];
			$params = $output['params'];
		}
	}
}