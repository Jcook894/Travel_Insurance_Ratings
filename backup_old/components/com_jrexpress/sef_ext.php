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

if (!defined('DS')) define('DS', DIRECTORY_SEPARATOR);

class sef_jrexpress {
	
	var $__Menu;
		
	function &getInstance() {

		static $instance = array();

		if (!isset($instance[0]) || !$instance[0]) 
		{
			$instance[0] =& new sef_jrexpress();
			require( dirname(__FILE__) . DS . 'jrexpress' . DS . 'framework.php');

			App::import('Model','Menu','jrexpress');

			$instance[0]->__Menu = &RegisterClass::getInstance('MenuModel');
		}
		
		return $instance[0];
	}

	function create($string) {

		$_this =& sef_jrexpress::getInstance();
		
		$url = '';
		$hasMenuId = preg_match('/Itemid=([0-9]{1,})/',$string,$menu_id);
		$isMenuLink = strpos($string,'url=menu');
		
		$sefstring = '';
				
		// Process internal "url" parameter
		$temp = explode('&amp;url=', $string);

		if(isset($temp[1])) {
			$url = urldecode($temp[1]);
		}
		
		if (eregi('&amp;url=',$string) && !$isMenuLink) 
		{
			$query_string = explode('/',$url);
			// Properly urlencodes parameters contained within the url parameter
			foreach($query_string AS $key=>$param) {
				$query_string[$key] = urlencodeParam($param);
			}
			$url = implode('/',$query_string);
					
			$sefstring .= $url;
		
		} elseif(isset($menu_id[1]) && ($isMenuLink || $hasMenuId)) {

			$sefstring .= $isMenuLink ? str_replace('menu',$_this->__Menu->getMenuName($menu_id[1]),$url) : $_this->__Menu->getMenuName($menu_id[1]);
		
		} else {
			
			$sefstring = $string;
		
		}
	
		return rtrim($sefstring,'/').'/';
	}

	function revert ($url_array, $pos) {

		$_PARAM_CHAR = ':';
		$url = array();
		$_this =& sef_jrexpress::getInstance();

		global $QUERY_STRING;
		
		// First check if this is a menu link by looking for the menu name to get an Itemid
		if(isset($url_array[$pos+2]) && $menu_id = $_this->__Menu->getMenuId($url_array[$pos+2])) {
			
			$_GET['Itemid'] = $_REQUEST['Itemid'] = $menu_id;
			$QUERY_STRING = "option=com_jrexpress&Itemid=$menu_id";

			for($i=$pos+2;$i<count($url_array);$i++) {
				if($url_array[$i] != '' && false!==strpos($url_array[$i],$_PARAM_CHAR)) {
					$parts = explode($_PARAM_CHAR,$url_array[$i]);
					if(isset($parts[1]) && $parts[1]!='') {
						$url[] = $url_array[$i];
						$_GET[$parts[0]] = $_REQUEST[$parts[0]] = $parts[1];
					}
				}
			}

			$QUERY_STRING .= '&url=menu/' . implode('/',$url);

		} else {

			// Not a menu link, so we use the url named param
			for($i=$pos+2;$i<count($url_array);$i++) {
				if($url_array[$i] != '') {
					$url[] = $url_array[$i];
				}
			}

			$url = implode('/',$url);
	
			if(preg_match('/_m([0-9]+)/',$url,$matches)) {
				$menu_id = $_GET['Itemid'] = $_REQUEST['Itemid'] = $matches[1];
			} else {				
				$menu_id = $_GET['Itemid'] = $_REQUEST['Itemid'] = '';
			}			
			
			$_GET['url'] = $_REQUEST['url'] = $url;
			$_GET['option'] = $_REQUEST['option'] = 'com_jrexpress';

			$QUERY_STRING = "option=com_jrexpress&Itemid=$menu_id&url=$url";
					
		}
//			return $QUERY_STRING;		
	}

}