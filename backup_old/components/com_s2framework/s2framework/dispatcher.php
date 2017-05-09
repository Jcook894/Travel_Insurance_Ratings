<?php
/**
 * jReviews - Reviews Extension
 * Copyright (C) 2006-2009 Alejandro Schmeichler
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice
 **/

 defined( 'MVC_FRAMEWORK') or die( 'Direct Access to this location is not allowed.' );

class S2Dispatcher extends S2Object 
{	
	
/**
 * Application using the framework
 * @var string
 * @access public
 */

	var $app;

/**	
 * Base URL
 *
 * @var string
 * @access public
 */
	var $base = false;	
/**
 * Current URL
 *
 * @var string
 * @access public
 */
	var $here = false;	
	var $controller;
	var $view = 'View';
	var $params;
	var $xajax_debug = false;
	var $xajax_statusMessage = false;
	var $xajax_waitCursor = false;
	var $disable404 = false;
	
	function __construct($app = 'jreviews', $xajax = false, $disable404 = false) {
					
		// Fixed REQUEST_URI for IIS
		$this->getUri();		
		
		// Set app
		$this->app = $app;	// jreviews by default for backwards compatibility
		
		$this->xajax_debug = S2_XAJAX_DEBUG;
		$this->xajax_statusMessage = S2_XAJAX_STATUS_MESSAGE;
		$this->xajax_waitCursor = S2_XAJAX_WAIT_CURSOR;
		$this->disable404 = $disable404;
		
		if($xajax && !class_exists('xajax')) {
			$this->loadXajax();
		}
		
		// Fixes secondary colons added by J1.5
		if(isset($_GET['url'])) {
			$query_string = explode('/',$_GET['url']);
			foreach($query_string AS $key=>$param) {
				$query_string[$key] = urlencodeParam($param,false);
			}
			$_GET['url'] = implode('/',$query_string);
		}		
	}
	  	
	function dispatch()
	{
		$args = func_get_args();
		
		if(count($args)==2) {
			$url = $args[0];
			$additionalParams = $args[1];
		} elseif(count($args)==1) {
			$url = null;
			$additionalParams = $args[0];			
		} else {
			$url = null;
			$additionalParams = array();
		}
		
		if($url!==null) {
			$_GET['url'] = $url;
		}

		$this->params = array_insert($this->parseParams($_SERVER['REQUEST_URI']),$additionalParams);

		$this->controller = Sanitize::getString($this->params['data'],'controller');

		$this->action = Sanitize::getString($this->params['data'],'action','index');
				
		$cache_url = $this->getUrl();

		$this->here = $this->base . '/' . $cache_url;		

		$cached = $this->cached($cache_url);
		
		if (!defined('MVC_FRAMEWORK_ADMIN') && $cached !== false) {
			return $cached;
		}
		
		if(!$this->controller || ((!isset($_POST) || empty($_POST)) && $this->action{0}=='_')) {

			return $this->error404();
			
		} else {

			App::import('Controller',$this->controller,$this->app);

	    	# remove admin path from controller name
			$controllerClass = inflector::camelize(str_replace(MVC_ADMIN . _DS,'',$this->controller)) . 'Controller';

			$controller = new $controllerClass($this->app);
			
			$controller->app = $this->app;
			$controller->base = $this->base;
			$controller->here = $this->here;					
			$controller->params = & $this->params;
			$controller->name = $this->controller;
			$controller->action = $this->action;

			if(!method_exists($controller, $this->action)) {

				return $this->error404();
							
			}	

			$controller->xajaxRequest = false;
						
			$controller->passedArgs = $this->params['url'];

			# Copy post array to data array			
			if(isset($this->params['data'])) {
				
				$rawData = $this->params['data'];
				$data = Sanitize::clean($this->params['data']);
				$data['__raw'] = $rawData;

				$controller->data = $data;				
			}

			$controller->__initComponents();     
			
			if (in_array('return', array_keys($this->params)) && $this->params['return'] == 1) {
				$controller->autoRender = false;
			}
					
			if (!empty($this->params['bare'])) {
				$controller->autoLayout = false;
			}
					
			if (isset($this->params['layout'])) {
				if ($this->params['layout'] === '') {
					$controller->autoLayout = false;
				} else {
					$controller->layout = $this->params['layout'];
				}
			}			

			if(method_exists($controller,'beforeFilter')) {

				$controller->beforeFilter();
			}

			$output = $controller->{$controller->action}($this->params);			
			
		}

		$controller->output = &$output;
		
		# Instantiate view class and let it handle ouput
		if ($controller->autoRender) {

			$controller->render($controller->name, $controller->action, $controller->layout);

		} else {
			
			$controller->afterFilter();
					
			return $controller->output;
		
		}		
		
	}
	
    /*
    * REQUEST_URI for IIS Servers
    * Version: 1.1
    * Guaranteed to provide Apache-compliant $_SERVER['REQUEST_URI'] variables
    * Please see full documentation at 

    * Copyright NeoSmart Technologies 2006-2008
    * Code is released under the LGPL and maybe used for all private and public code

    * Instructions: http://neosmart.net/blog/2006/100-apache-compliant-request_uri-for-iis-and-windows/
    * Support: http://neosmart.net/forums/forumdisplay.php?f=17
    * Product URI: http://neosmart.net/dl.php?id=7
    */	
	function getUri() {
			    
	    //This file should be located in the same directory as php.exe or php5isapi.dll
	    //ISAPI_Rewrite 3.x
	    if (isset($_SERVER['HTTP_X_REWRITE_URL'])){
	        $_SERVER['REQUEST_URI'] = $_SERVER['HTTP_X_REWRITE_URL'];
	    }
	    //ISAPI_Rewrite 2.x w/ HTTPD.INI configuration
	    else if (isset($_SERVER['HTTP_REQUEST_URI'])){
	        $_SERVER['REQUEST_URI'] = $_SERVER['HTTP_REQUEST_URI'];
	        //Good to go!
	    }
	    //ISAPI_Rewrite isn't installed or not configured
	    else{
	        //Someone didn't follow the instructions!
	        if(isset($_SERVER['SCRIPT_NAME']))
	            $_SERVER['HTTP_REQUEST_URI'] = $_SERVER['SCRIPT_NAME'];
	        else
	            $_SERVER['HTTP_REQUEST_URI'] = $_SERVER['PHP_SELF'];
	        if($_SERVER['QUERY_STRING']){
	            $_SERVER['HTTP_REQUEST_URI'] .=  '?' . $_SERVER['QUERY_STRING'];
	        }
	        //WARNING: This is a workaround!
	        //For guaranteed compatibility, HTTP_REQUEST_URI or HTTP_X_REWRITE_URL *MUST* be defined!
	        //See product documentation for instructions!
	        $_SERVER['REQUEST_URI'] = $_SERVER['HTTP_REQUEST_URI'];
	    }		
	}
	
	function getUrl($uri = null, $base = null) {
		
		$params = array();

		$controller = Sanitize::getString($this->params['data'],'controller');
		
		$action = Sanitize::getString($this->params['data'],'action');
		
		$url = $controller.'/'.$action;
		
		if(isset($this->params['data'])) {
			foreach($this->params['data'] AS $key=>$value) {
				if(!is_array($value) && !is_object($value) && !in_array($key,array('controller','action')) && $value != '') {
					$params[] = $key.':'.$value;
				}
			}
		}

		foreach($this->params AS $key=>$value) {
			if(!is_array($value) && !is_object($value) && !in_array($key,array('view','layout','option','Itemid')) && $value != '') {
				if(false!=strpos($value,':')) $value = substr($value,0,strpos($value,':'));
				$params[] = $key.':'.$value;
			}
		}
		
		$output = $url . '/' . md5(implode('/',$params)); 

		return $output;		
	}
	
			
	function error404() {
		
		if(!defined('MVC_FRAMEWORK_ADMIN') && false === $this->disable404) {
			$controller = new S2Controller($this->app);
			$controller->name = 'errors';
			$controller->action = 'error404';
			$controller->autoLayout = false;
			$controller->render($controller->name, $controller->action, $controller->layout);
			return '';
		} else {
			return 'Invalid request.';
		}		
		
	}	
	
	function loadXajax() 
	{
		// Prevents xajax from loading twice if already loaded by jReviews or BlueFlame Platform
		if(!defined('XAJAX_LOADED') && !defined('XAJAX_VER'))
		{
			define('XAJAX_LOADED',1);
		
			App::import('Vendor','xajax_05rc2' . DS . 'xajax_core' . DS .'xajax.inc');
	
			if(defined('MVC_FRAMEWORK_ADMIN')) {
				$xajax = new xajax('index2.php?option='.S2Paths::get('jreviews','S2_CMSCOMP').'&task=xajax&no_html=1');			
			} else {
				$xajax = new xajax();			
			}
						
			$xajax->setCharEncoding(strtoupper(cmsFramework::getCharset()));

			if(strtolower(cmsFramework::getCharset()) == 'utf-8') {
				$decodeUTF8 = false;
			} else {
				$decodeUTF8 = true;					
			}
	
			/* Set defaults from params */
			$this->xajax_statusMessage 	? $xajax->setFlag('statusMessages',true) : $xajax->setFlag('statusMessages',false);
			$this->xajax_waitCursor		? $xajax->setFlag('waitCursor',true) 	: $xajax->setFlag('waitCursor',false);
			$this->xajax_debug			? $xajax->setFlag('debug',true) 	 	: $xajax->setFlag('debug',false);
			$decodeUTF8 				? $xajax->setFlag('decodeUTF8Input',true) : $xajax->setFlag('decodeUTF8Input',false);

			$xajax->registerFunction('xajaxDispatch');
	
//			ob_start('ob_gzhandler');		// Results in wrong encoding error ni certain servers	
	        
			$xajax->processRequest();
        
			$js = $xajax->getJavascript(S2_VENDORS_URL . 'xajax_05rc2' . _DS);
			
			cmsFramework::addScript($js);	        
		}
	}
	
	/**
	 * Returns array of GET and POST parameters. GET parameters are taken from given URL.
	 *
	 * @param string $fromUrl URL to mine for parameter information.
	 * @return array Parameters found in POST and GET.
	 * @access public
	 */
	function parseParams($fromUrl = '') {

		$params = array();
		$params['data'] = array();
					
		if (isset($_POST)) {
			if (ini_get('magic_quotes_gpc') == 1) {
				if(function_exists('s2_stripslashes_deep'))
					$params['form'] = s2_stripslashes_deep($_POST);
				else 
					$params['form'] = stripslashes_deep($_POST);
			} else {
				$params['form'] = $_POST;
			}
			
			if (isset($params['form']['_method'])) {
				if (isset($_SERVER) && !empty($_SERVER)) {
					$_SERVER['REQUEST_METHOD'] = $params['form']['_method'];
				} else {
					$_ENV['REQUEST_METHOD'] = $params['form']['_method'];
				}
				unset($params['form']['_method']);
			}
		}

		if (isset($params['form']['data'])) {
			$params['data'] = Sanitize::stripEscape($params['form']['data']);
			unset($params['form']['data']);
		}

        if (isset($_GET)) {

            if (ini_get('magic_quotes_gpc') == 1) {
                if(function_exists('s2_stripslashes_deep'))            
                    $url = s2_stripslashes_deep($_GET);
                else 
                    $url = stripslashes_deep($_GET);                    
            } else {
                $url = $_GET;
            }              
            if (isset($params['url'])) {
                $params['url'] = array_merge($params['url'], $url);
            } else {
                $params['url'] = $url;
            }                                                       
            
        }

		foreach ($_FILES as $name => $data) {
			if ($name != 'data') {
				$params['form'][$name] = $data;
			}
		}

		if (isset($_FILES['data'])) {
			foreach ($_FILES['data'] as $key => $data) {
				foreach ($data as $model => $fields) {
					foreach ($fields as $field => $value) {
						$params['data'][$model][$field][$key] = $value;
					}
				}
			}
		}

		if(isset($params['data']['controller'])) {
			$params['controller'] = Sanitize::getString($params['data'],'controller');
			$params['action'] = Sanitize::getString($params['data'],'action');
		}

		$Router =& S2Router::getInstance();
		$Router->app = $this->app;
		$params = S2Router::parse($params);

		foreach($params['url'] AS $key=>$value) {
			if($key!='url') $params[$key] = $value;
		}
					
		return $params;
	}
	
	function parseParamsAjax($params = array()) 
    {
		if (!empty($params) && is_array($params)) {
			if (ini_get('magic_quotes_gpc') == 1) {
				if(function_exists('s2_stripslashes_deep'))
					$params['form'] = s2_stripslashes_deep($params);
				else 
					$params['form'] = stripslashes_deep($params);					
			} else {
				$params['form'] = $params;
			}			
		
			// array check to prevent weird error with form being a mosParameters object in J1.0.x
			if (is_array($params['form']) && isset($params['form']['data'])) {
				$params['data'] = Sanitize::stripEscape($params['form']['data']);
				unset($params['form']['data']);
			}		
		
		} else {
			if (ini_get('magic_quotes_gpc') == 1) {
				return stripslashes($params);
			}
		}

		return $params;
	}
	
/**
 * Outputs cached dispatch view cache
 *
 * @param string $url Requested URL
 * @access public
 */
	function cached($url) {
		
		App::import('Component','config',$this->app);
		
		$controller = new stdClass();

		if(class_exists('ConfigComponent')) {
			$Config = new ConfigComponent();
			$Config->startup($controller);
		}
				
		$User = cmsFramework::getUser();
		
		if ($User->id === 0 && !Configure::read('Cache.disable') && Configure::read('Cache.view') && !defined('MVC_FRAMEWORK_ADMIN')) {

			$path = $this->here;
			
			if ($this->here == '/') {
				$path = 'home';
			}
			
			$path = Inflector::slug($path);

			$filename = CACHE . 'views' . DS . $path . '.php';

			if (!file_exists($filename)) {
				$filename = CACHE . 'views' . DS . $path . '_index.php';
			}

			if (file_exists($filename)) {
				if (!class_exists('MyView')) {
					App::import('Core', 'View',$this->app);
				}
				$controller = null;
				$view = new MyView($controller, false);
				// Pass the configuration object to the view and set the theme variable for helpers
				$view->name = $this->controller;
				$view->action = $this->action;
				$view->Config = $Config;
				$view->viewTheme = $Config->template;
				$view->xajaxRequest = false;
				
				$out = $view->renderCache($filename, S2getMicrotime());
					
				return $out;
				
			}
			
		}
		return false;
	}	

}

if(!function_exists('xajaxDispatch')&& !defined('XAJAX_VER')) 
{
	function xajaxDispatch() {
						
		$objResponse = new xajaxResponse();

		# Debug
		if(Configure::read('System.debug',0)===0) {
			error_reporting(0);
		}

		# Function parameters
		$args = func_get_args();

		$controllerName = (string) array_shift($args);
		
		$action = (string) array_shift($args);
		
		$app = isset($args[0]) && is_string($args[0]) ? array_shift($args) : 'jreviews';
         
        $Router =& S2Router::getInstance();
        $Router->app = $app;
          
		App::import('Controller',$controllerName,$app);

		# remove admin path from controller name
		$controllerClass = inflector::camelize(str_replace(MVC_ADMIN._DS,'',$controllerName)) . 'Controller';

		$controller = new $controllerClass($app);
					
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