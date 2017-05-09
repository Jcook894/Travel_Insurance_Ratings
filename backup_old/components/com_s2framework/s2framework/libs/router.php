<?php
/**
 * jReviews - Reviews Extension
 * Copyright (C) 2006-2009 Alejandro Schmeichler
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice
 **/


defined( 'MVC_FRAMEWORK') or die( 'Direct Access to this location is not allowed.' );

class S2Router extends S2Object {
	
	var $routes = array();
	var $__processed = array();
	var $__translit = array();
	var $app = 'jreviews';
	
	function &getInstance() {
        
		static $instance = array();

		if (!isset($instance[0]) || !$instance[0]) {
			$instance[0] = & new S2Router();
		}
		return $instance[0];
	}
	
	function connect() {
		
		$args = func_get_args();
		$route = $args[0];
		$default = $args[1];
		$named_params = array();

		for($i=2;$i<count($args);$i++) {
			$named_params[] = $args[$i];
		}

		$_this =& S2Router::getInstance();

		$default = array_merge(array('action' => 'index'), $default);

		$_this->routes[] = array($route, $default, $named_params);

	}
		
	function parse($params,$storeRoute = true, $app = 'jreviews') {

		$_this =& S2Router::getInstance();
        
        if($app != 'jreviews') 
        {
            $_this->app = $app;
        }
 
		if(!isset($params['data']))
			$params['data'] = null;

		if(isset($params['url']['url']) && $params['url']['url'] != '') 
		{
			$route = $params['url']['url'];
			
			if(isset($_this->__processed[$route])) {
				$params = $_this->__processed[$route];
				return $params;
			}		

			$segments = explode('/',$params['url']['url']);

			// remove empty segments {
			foreach($segments AS $key=>$value) {
				if(trim($value) == '') {
					unset($segments[$key]);
				}
			}

			$segments = array_merge($segments,array());

			# Fix for weird J1.5 behavior where first dash is converted to colon
			$segments[0] = str_replace(':','-',$segments[0]);
			foreach($segments AS $key=>$segment) {
				if(false!==strpos($segment,_PARAM_CHAR)) {
					if('jr_'==substr($segment,0,3) ) {
						$args = explode(_PARAM_CHAR,$segment);

						if(count($args)>2) {
							$var = $args[0];
							unset($args[0]);
							$segments[$key] = $var . ':' . implode($args,'-');
							$segment = $var . ':' . implode($args,'-');
						}
					}
				}				
			}

			$params['url']['url'] = implode('/',$segments);			
			
			// Process named parameters (i.e. page, limit, order, etc.) 
			foreach($segments AS $key=>$segment) {
				if(false!==strpos($segment,_PARAM_CHAR)) {
					$args = explode(_PARAM_CHAR,$segment);
//					$params['data'][$args[0]] = $args[1]; // Copy to data array
					$params['url'][$args[0]] = $args[1]; // Copy to url array *passedArgs*
					unset($segments[$key]);
				}
			}

			// Admin standard routing
			if(defined('MVC_FRAMEWORK_ADMIN')  && !isset($params['data']['controller']) && file_exists(S2Paths::get($_this->app,'S2_ADMIN_CONTROLLERS')  . $segments[0] . '_controller.php')) {

				$params['data']['controller'] = MVC_ADMIN._DS.$segments[0];
					
				if(isset($segments[1]) && is_string($segments[1]) && !strpos($segments[1],_PARAM_CHAR)) {
						$params['data']['action'] = $segments[1];
				} else {
					$params['data']['action'] = 'index';
				}

			// Frontend standard routing	
			} elseif(!defined('MVC_FRAMEWORK_ADMIN')) {

				// CHeck custom routes first
				S2Router::processCustomRoute($params);
				
				if(!isset($params['data']['controller']) && App::import('Controller',$segments[0],$_this->app)) {				

					$params['data']['controller'] = $segments[0];
					
					if(isset($segments[1]) && is_string($segments[1]) && !strpos($segments[1],_PARAM_CHAR)) {
						$params['data']['action'] = $segments[1];
					} else {
						$params['data']['action'] = 'index';
					}
				}

			}

			if($storeRoute)
				$_this->__processed[$route] = $params;
		}

		return $params;
	}
	
	function processCustomRoute(&$params) 
    {
		$_this =& S2Router::getInstance();
		
		$app = $_this->app;

		// Load app custom routes
		if(empty($_this->routes)) {
                                   
			include S2Paths::get($app,'S2_APP_CONFIG') . 'routes.php';
		}

		$url = rtrim($params['url']['url'],'/') . '/' ;
					
		foreach($_this->routes AS $route) 
		{	
			if(preg_match($route[0],$url,$matches) == 1) 
			{
//prx($matches);
				if(isset($route[2])&&!empty($route[2]))
				{
					if(!is_array($route[2][0])) {
						$route[2] = array($route[2]);
					}
					
					foreach($route[2] AS $map) {
						// $map[0] this is the parameter name or names if it's an array
						// $map[1] this is the value or regex expression

						if($map[1]{0} == '/') { // It's a regex match
							$count = preg_match($map[1],$url,$match);
							
							// Only the first result from the match is used
							if($count == 1 && !is_array($map[0])) {
								$params[$map[0]] = urldecode($match[1]);
					
							// An array of values from the match is used	
							} elseif($count == 1 && count($match)>1) 
							{
								if(is_array($map[0])) {
									
									unset($match[0]);
									
									$match = array_merge(array(),$match); // Reset keys
									
									foreach($map[0] AS $key=>$named_param) {
	
										if(isset($map[2])) {
											$params[$map[2]][$named_param] = urldecode($match[$key]);	
										} else {
											$params[$named_param] = urldecode($match[$key]);
										}
									}
								}
							}
						} else {
							// It's a straight value assignment
							$params[$map[0]] = $map[1];
						}
					}
				}
				
				$params['data']['controller'] = $route[1]['controller'];
				
				$params['data']['action'] = isset($route[1]['action']) ? $route[1]['action'] : 'index';
				
				break;
			}
		}
//prx($params);
//exit;		
	}
	
	function sefUrlEncode($text,$level = 0, $and_replace = 'and')  {
		
		$_this = &S2Router::getInstance();
		
		if(isset($_this->__translit[$text])) {
			return $_this->__translit[$text];
		}		

		$translitText = str_replace('-', ' ', $text);
		
//		$translitText = str_replace('&','-'.$and_replace.'-',$translitText);

		switch($level) {			
			case 0:
			case 1:
				$translitText = htmlentities(utf8_decode($translitText));
				$translitText = preg_replace(
					array('/&szlig;/','/&(..)lig;/', '/&([aouAOU])uml;/','/&(.)[^;]*;/'),
					array('ss',"$1","$1".'e',"$1"),
					$translitText);

				// remove any duplicate whitespace, and ensure characters are alphanumeric
				$translitText = preg_replace(array('/\s+/'), array('-'), $translitText);	
				$translitText = preg_replace(array('/[^A-Za-z0-9\-]/'), array(''), $translitText);	
                $translitText = mb_strtolower($translitText);                
				break;				
                
			case 2:
				App::import('Lib','utf8_to_ascii',$this->app);
				$translitText = utf8_to_ascii($translitText,'');				
				break;	
		}
			
		$_this->__translit[$text] = $translitText;		

        return strtolower($translitText);
    }	
		
	function sefUrlDecode($text) {
		$text = urldecode($text);
		$text = str_replace('-',' ',$text);
		return $text;	
	}

}