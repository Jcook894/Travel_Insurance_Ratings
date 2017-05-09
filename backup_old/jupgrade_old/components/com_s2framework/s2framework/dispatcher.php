<?php
/**
 * S2Framework
 * Copyright (C) 2010-2012 ClickFWD LLC
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 * @license       http://www.opensource.org/licenses/mit-license.php The MIT License
**/

defined( 'MVC_FRAMEWORK') or die( 'Direct Access to this location is not allowed.' );

/*
* REQUEST_URI for IIS Servers
* Version: 1.1
* Guaranteed to provide Apache-compliant $_SERVER['REQUEST_URI'] variables
* Please see full documentation at 

* Copyright NeoSmart Technologies 2006-2008
* Code is released under the LGPL and may be used for all private and public code

* Instructions: http://neosmart.net/blog/2006/100-apache-compliant-request_uri-for-iis-and-windows/
* Support: http://neosmart.net/forums/forumdisplay.php?f=17
* Product URI: http://neosmart.net/dl.php?id=7
*/    
                
//This file should be located in the same directory as php.exe or php5isapi.dll
//ISAPI_Rewrite 3.x
if(preg_match('/IIS/',$_SERVER['SERVER_SOFTWARE'])) 
{
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
        if(isset($_SERVER['QUERY_STRING'])){
            $_SERVER['QUERY_STRING'] != '' and $_SERVER['HTTP_REQUEST_URI'] .=  '?' . $_SERVER['QUERY_STRING'];
        }
        //WARNING: This is a workaround!
        //For guaranteed compatibility, HTTP_REQUEST_URI or HTTP_X_REWRITE_URL *MUST* be defined!
        //See product documentation for instructions!
        $_SERVER['REQUEST_URI'] = $_SERVER['HTTP_REQUEST_URI'];
    } 
}    

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
    var $disable404 = false;
    
    function __construct($app = 'jreviews', $ajax = false, $disable404 = false) 
    {                    
        // Set app
        $this->app = $app;    // jreviews by default for backwards compatibility
        
        $this->disable404 = $disable404;
        
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
        } elseif(isset($_REQUEST['url'])) {
            $_GET['url'] = $_REQUEST['url']; // Non-latin characters are wrong in $_GET array
        }
  
        if(isset($_POST['url'])) $_GET['url'] = $_POST['url']; // For ajax calls via url param

        $this->params = array_insert($this->parseParams($_SERVER['REQUEST_URI']),$additionalParams);

        // Sanitize parameters
        if(isset($this->params['data'])) $rawData = $this->params['data'];
        $this->params = Sanitize::clean($this->params);
        if(isset($this->params['data'])) $this->params['data']['__raw'] = $rawData;
        
        $this->controller = Sanitize::getString($this->params['data'],'controller');

        $this->action = Sanitize::getString($this->params['data'],'action','index');
                
        $cache_url = $this->getUrl();
                   
        $this->here = $this->base . '/' . $cache_url;        

        if (!defined('MVC_FRAMEWORK_ADMIN') && $cached = $this->cached($cache_url)) 
        {  
            return $cached;
        }

        if(!$this->controller || ((!isset($_POST) || empty($_POST)) && $this->action{0}=='_' && !$this->isAjax())) 
        {
            return $this->error404();
        } 
        elseif(substr($this->action,0,1)=='__') // Private methods
        {
            return $this->error404();
        }
        else {

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
            $controller->ajaxRequest = $this->isAjax();

            if(!method_exists($controller, $this->action)) 
            {
                return $this->error404();                            
            }    
           
            $controller->passedArgs = $this->params['url'];

            # Copy post array to data array            
            if(isset($this->params['data'])) {
                $controller->data = $this->params['data'];                
            }                
                  
            $controller->__initComponents();     
        
            if ((in_array('return', array_keys($this->params)) && $this->params['return'] == 1) || $controller->ajaxRequest) {
                $controller->autoRender = false;
            }
                    
            if (!empty($this->params['bare']) || $controller->ajaxRequest) {
                $controller->autoLayout = false;
            }
                    
            if (isset($this->params['layout'])) {
                if ($this->params['layout'] === '') {
                    $controller->autoLayout = false;
                } else {
                    $controller->layout = $this->params['layout'];
                }
            }            

            $controller->beforeFilter();        
            
            $output = $controller->{$controller->action}($this->params);            
        }

        $controller->output = &$output;
        
        # Instantiate view class and let it handle ouput
        if ($controller->autoRender) 
        {            
            $controller->render($controller->name, $controller->action, $controller->layout);

            $controller->afterFilter();

        } else 
        {        
            $controller->afterFilter();
                    
            $out = $controller->output;        
                 
            return $out;
        }    
        
    }
    
    function getUrl($uri = null, $base = null) 
    {
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
            elseif(is_array($value) && in_array($key,array('tag'))) {
                foreach($value AS $k=>$v) {
                    $params[] = $k.':'.$v;
                }
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
    
    /**
    * Detects jQuery ajax request
    * 
    */
    function isAjax()
    {
        return (isset($_SERVER['HTTP_X_REQUESTED_WITH']) &&
        ($_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest'));
    }    
    
    /**
     * Returns array of GET and POST parameters. GET parameters are taken from given URL.
     *
     * @param string $fromUrl URL to mine for parameter information.
     * @return array Parameters found in POST and GET.
     * @access public
     */
    function parseParams($fromUrl = '') 
    {
        $params = array();
        $params['data'] = array();
        
        isset($_COOKIE) and ini_get('magic_quotes_gpc') == 1 and $_COOKIE = s2_stripslashes_deep($_COOKIE);

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

        if (isset($_GET))
        {
            if (ini_get('magic_quotes_gpc') == 1) {
                    $url = s2_stripslashes_deep($_GET);
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
    
/**
 * Outputs cached dispatch view cache
 *
 * @param string $url Requested URL
 * @access public
 */
    function cached($url) {
                   
        App::import('Component','config',$this->app);
        
		if(!Configure::read('System.mobileDetect') && App::import('Vendor','mobile_detect' . DS . 'Mobile_Detect'))
		{
			$detect = new Mobile_Detect();
			if ($detect->isMobile() && !$detect->isIpad()) { // It is mobile
				Configure::write('System.isMobile',true);
				$this->here .= '.mob';
			}
			else { // Not mobile
				Configure::write('System.isMobile',false);
			}
			Configure::write('System.mobileDetect',true);
		}

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
                $view->page = Sanitize::getInt($this->params,'page');
                $view->limit = Sanitize::getInt($this->params,'limit');
                $view->Config = $Config;
                $view->viewTheme = $Config->template;
                $view->ajaxRequest = $this->isAjax();                
                $out = $view->renderCache($filename, S2getMicrotime());
                return $out;
            }
            
        }
        return false;
    }    
}