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
define('S2_CORE_INCLUDE_PATH','components' . DS . 'com_s2' . DS . 's2framework');	
if (!defined('DS')) define('DS', DIRECTORY_SEPARATOR);
require( dirname(__FILE__) . DS . 'jrexpress' . DS. 'framework.php');

function jrexpressBuildRoute(&$query){
	
	global $Menu;

	$segments = array();
	if(isset($query['url'])) {
		
		$query_string = explode('/',$query['url']);
		// Properly urlencodes parameters contained within the url parameter
		foreach($query_string AS $key=>$param) {
			$query_string[$key] = urlencodeParam($param);
		}
		$query['url'] = implode('/',$query_string);
		$segments[0] = $query['url'];
		unset($query['url']);
	}

	return $segments;
}

function jrexpressParseRoute($segments)
{
	$vars = array();
	$vars['url'] = implode('/',$segments);

	return $vars;
}
