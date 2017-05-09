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

// no direct access
(defined( '_VALID_MOS') || defined( '_JEXEC')) or die( 'Direct Access to this location is not allowed.' );

define('_XAJAX_JREXPRESS', dirname(__FILE__));

// Define xajax functions
$xajaxFunctions[] = 'xajaxDispatch';

if(!function_exists('xajaxDispatch')) {
	function xajaxDispatch() {
	
		# MVC initalization script
		if (!defined('MVC_FRAMEWORK'))  require( dirname(dirname(__FILE__)) . DS . 'com_jrexpress' . DS . 'jrexpress' . DS . 'framework.php' );
	
		$objResponse = new xajaxResponse();
	
		# Debug
		if(S2_DEBUG == 0) {
			error_reporting(0);
		}
	
		# Function parameters
		$args = func_get_args();
	
		$controllerName = (string) array_shift($args);
		
		$action = (string) array_shift($args);
		
		$app = isset($args[0]) && is_string($args[0]) ? array_shift($args) : 'jrexpress';
				
		App::import('Controller',$controllerName,$app);
		
		# remove admin path from controller name
		$controllerClass = inflector::camelize(str_replace(MVC_ADMIN._DS,'',$controllerName)) . 'Controller';
	
		$controller = new $controllerClass($app);
		
		$controller->app = $app;		
					
		$controller->passedArgs = array();
	
		
		if(isset($args[0]))
		{
			$post = S2Dispatcher::parseParamsAjax($args[0]);
			
			if(isset($post['data'])) { // pass form inputs to controller variable
				
				$rawData = $post['data'];
				$data = Sanitize::clean($post['data']);
				$data['__raw'] = $rawData;
				
				$controller->data = $data;
				
			}
					
			$controller->passedArgs = $post;
			$controller->params = $post;
				
		}	
		
		$controller->name = $controllerName;
	
		$controller->action = $action;
	
		$controller->autoLayout = false;
		
		$controller->autoRender = false;
			
		$controller->xajaxRequest = true;
	
		$controller->__initComponents();
	
		if(method_exists($controller,'beforeFilter')) {
			$controller->beforeFilter();
		}		
						
		$objResponse->loadCommands($controller->$action($args));
		
		return $objResponse;
	}
}