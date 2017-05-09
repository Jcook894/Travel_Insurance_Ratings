<?php
/**
 * S2Framework
 * Copyright (C) 2010-2015 ClickFWD LLC
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 * @license       http://www.opensource.org/licenses/mit-license.php The MIT License
**/

defined( 'MVC_FRAMEWORK') or die;

use Detection\MobileDetect;

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

    var $debug = false;

    function __construct() // $app = 'jreviews', $ajax = false, $disable404 = false
    {
        $options = func_get_args();

        $args = array();

        if(count($options) > 1) {

            $args['app'] = $options[0];
        }
        elseif(!is_array($options[0])) {

            $args['app'] = $options[0];
        }
        else {

            $args = $options[0];
        }

        // Set app
        $this->app = Sanitize::getString($args,'app','jreviews');    // jreviews by default for backwards compatibility

        $this->disable404 = Sanitize::getBool($args,'disable404',$this->disable404);

        // Read debug setting

        $this->debug = Sanitize::getInt($args,'debug',$this->debug) && Sanitize::getInt($_POST,'debug',1) /*&& !defined('MVC_FRAMEWORK_ADMIN')*/;

        !defined('S2_DEBUG') and define('S2_DEBUG',$this->debug);

        $s2Error = s2Error::getInstance();

        $s2Error->init($this->app);

        if($this->debug) {

            error_reporting(E_ALL);

            set_error_handler(array('s2Error','errorHandler'),E_ALL);

            register_shutdown_function(array('s2Error','registerShutdown'));
        }
    }

    function dispatch()
    {
        // Extend functionility of requestAction method
        $viewVars = $passedArgs = null;

        $args = func_get_args();

        if(count($args)==2) {

            $url = $args[0];

            $additionalParams = $args[1];
        }
        elseif(count($args)==1) {

            $url = null;

            $additionalParams = $args[0];
        }
        else {

            $url = null;

            $additionalParams = array();
        }

        if($url !== null && $url != '/') {

            $_REQUEST['url'] = $url;
        }
        elseif(isset($_POST['url']))
        {
            $_REQUEST['url'] = $_POST['url']; // For ajax calls via url param
        }

        if(isset($additionalParams['viewVars']) && is_array($additionalParams['viewVars'])) {

            $viewVars = $additionalParams['viewVars'];

            unset($additionalParams['viewVars']);
        }

        if(isset($additionalParams['passedArgs']) && is_array($additionalParams['passedArgs'])) {

            $passedArgs = $additionalParams['passedArgs'];

            unset($additionalParams['passedArgs']);
        }

        $this->params = array_insert($this->parseParams($_SERVER['REQUEST_URI']),$additionalParams);

        // Sanitize parameters

        if(isset($this->params['data']))
        {
            $rawData = $this->params['data'];
        }

        $module_params = Sanitize::getVar($this->params,'module'); // Prevent sanitize function from stripping

        $requested = Sanitize::getBool($this->params, 'requested');

        $loadAssets = Sanitize::getBool($this->params, 'load_assets');

        // Used when sending out a json response to prevent the method from setting the json header since we are going to be using the resulting json object directly
        // in the script

        $_REQUEST['requested'] = $requested;

        $this->params = Sanitize::clean($this->params);

        if(isset($this->params['data']))
        {
            $this->params['data']['__raw'] = $rawData;
        }

        if(!empty($module_params))
        {
            $this->params['module'] = $module_params;
        }

        $this->controller = Sanitize::getString($this->params['data'],'controller');

        $this->action = Sanitize::getString($this->params['data'],'action','index');

        $cache_url = $this->getUrl();

        $this->here = $this->base . '/' . $cache_url;

        if (!defined('MVC_FRAMEWORK_ADMIN') && $cached = $this->cached($cache_url))
        {
            return $cached;
        }

        if(!$this->controller || (( (!isset($_POST) && !isset($this->params['form'])) || (empty($_POST) && empty($this->params['form']))) && $this->action{0}=='_' && !S2Dispatcher::isAjax()))
        {
            return $this->error404();
        }
        elseif(substr($this->action,0,1)=='__') // Private methods
        {
            return $this->error404();
        }
        else {

            S2App::import('Controller',$this->controller,$this->app);

            # remove admin path from controller name
            $controllerClass = inflector::camelize(str_replace(MVC_ADMIN . _DS,'',$this->controller)) . 'Controller';

            // Check if if the call comes from a module so we don't return a 404 error for modules

            $module = isset($this->params['data']) && Sanitize::getInt($this->params['data'],'module');

            if(!class_exists($controllerClass))
            {
                return $module == true ? false : $this->error404();
            }

            $controller = new $controllerClass(array('app'=>$this->app,'name'=>$this->controller,'action'=>$this->action));

            if($viewVars)
            {
                $controller->set($viewVars);
            }

            $controller->app = $this->app;

            $controller->base = $this->base;

            $controller->here = $this->here;

            $controller->params = & $this->params;

            $controller->name = $this->controller;

            $controller->action = $this->action;

            $controller->ajaxRequest = S2Dispatcher::isAjax();

            $controller->isRequested = $requested;

            $controller->loadAssets = $loadAssets;

            if(!method_exists($controller, $this->action))
            {
                return $this->error404();
            }

            $controller->passedArgs = Sanitize::getVar($this->params,'url');

            if($passedArgs)
            {
                $controller->passedArgs = $passedArgs;
            }

            # Copy post array to data array
            if(isset($this->params['data']))
            {
                $controller->data = $this->params['data'];
            }

            $controller->__initComponents();

            if((isset($this->params['return']) && $this->params['return'] == 1) || $controller->ajaxRequest)
            {
                $controller->autoRender = false;
            }

            if(Sanitize::getInt($this->params,'bare') == 1 || $controller->ajaxRequest)
            {
                $controller->autoLayout = false;
            }

            if(isset($this->params['layout']))
            {
                if ($this->params['layout'] === '')
                {
                    $controller->autoLayout = false;
                }
                else {
                    $controller->layout = $this->params['layout'];
                }
            }

            $controller->beforeFilter();

            # Add ability to override debug in controller

            if(isset($controller->debug))  {

                $s2Error = s2Error::getInstance();

                $s2Error->debug = $controller->debug;
            }

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

    function getUrl()
    {
        $params = array();

        $controller = Sanitize::getString($this->params['data'],'controller');

        $action = Sanitize::getString($this->params['data'],'action');

        $url = $controller.'/'.$action;

        /**
         * Incorporate mobile agent into params
         */
        $detect = new MobileDetect();

        $isMobile = $detect->isMobile();

        $isTablet = $detect->isTablet();

        if ($isMobile && !$isTablet) { // Mobile, excluding tablets

            $this->params['isMobile'] = true;
        }

        // Parse data params

        if(isset($this->params['data']))
        {
            foreach($this->params['data'] AS $key=>$value)
            {
                if(!is_array($value) && !is_object($value) && !in_array($key,array('controller','action')) && $value != '')
                {
                    $params[] = $key.':'.$value;
                }
            }
        }

        // Parse all params that are not arrays, including data params above

        foreach($this->params AS $key=>$value)
        {
            if(!is_array($value) && !is_object($value) && !in_array($key,array('view','layout','option'/*,'Itemid'*/)) && $value != '')
            {
                if(false!=strpos($value,':')) $value = substr($value,0,strpos($value,':'));

                $params[] = $key.':'.$value;
            }
            elseif(is_array($value) && in_array($key,array('tag'))) {

                foreach($value AS $k=>$v)
                {
                    $params[] = $k.':'.$v;
                }
            }
        }

        $output = $url . '/' . md5(implode('/',$params));

        return $output;
    }


    function error404() {

        if(!defined('MVC_FRAMEWORK_ADMIN') && false === $this->disable404) {

            cmsFramework::raiseError( 404, s2Messages::errorGeneric() );

            return false;

        }
        else {

            return s2Messages::submitErrorGeneric();
        }

    }

    /**
    * Detects jQuery ajax request
    *
    */
    static function isAjax()
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
        $params = $query_vars = array();

        $parts = parse_url($fromUrl);

        if(isset($parts['query']))
        {
            parse_str($parts['query'], $query_vars);
        }

        $request_url = Sanitize::getString($_REQUEST,'url');

        if($request_url != '' && Sanitize::getString($query_vars,'url') != $request_url)
        {
            $query_vars['url'] = $_REQUEST['url'];
        }

        // Magic quotes cleanup

        if(ini_get('magic_quotes_gpc') == 1)
        {
            $query_vars = s2_stripslashes_deep($query_vars);

            if(isset($_GET))
            {
                $_GET = s2_stripslashes_deep($_GET);
            }

            if(isset($_POST))
            {
                $_POST = s2_stripslashes_deep($_POST);
            }

            if(isset($_COOKIE))
            {
                $_COOKIE = s2_stripslashes_deep($_COOKIE);
            }

            $_REQUEST = s2_stripslashes_deep($_REQUEST);
        }

        // Initialize $params array with URL query vars

        $params = array_merge($params, $query_vars);

        // Ignore reserved App parameter keys so they are not overwritten by global PHP vars

        unset($params['layout']);

        if(!empty($query_vars))
        {
            $params['url'] = $query_vars;
        }

        if(!isset($query_vars['url']))
        {
            $url = Sanitize::getString($_GET, 'url', Sanitize::getString($_REQUEST,'url'));

            if($url != '')
            {
                $params['url']['url'] = $url;
            }
        }

        $getParams = array(
            'Itemid'
            ,'option'
            ,'index'
            );

        foreach($getParams AS $key)
        {
            if(isset($_REQUEST[$key])) $params['url'][$key] = $_REQUEST[$key];
        }

        $params['data'] = array();

        if(isset($_POST))
        {
            $params['form'] = $_POST;
        }

        if(isset($_REQUEST))
        {
            foreach($_REQUEST AS $key=>$val)
            {
                // Ignore reserved App parameter keys so they are not overwritten

                if(in_array($key,array('url','layout'))) continue;

                // This is for WP because it doesn't pass the 'data' key in ajax uploads in the $_POST array

                if($key == 'data')
                {
                    if(isset($params['form']['data']))
                    {
                        $params['form']['data'] = array_merge($params['form']['data'], $val);
                    }
                    else {

                        $params['form']['data'] = $val;
                    }
                }
                else {

                    if($key == 'action' && $val == 'jreviews_ajax') continue;

                    $params[$key] = $val;
                }
            }
        }

        if(isset($params['form']['data']))
        {
            $params['data'] = Sanitize::stripEscape($params['form']['data']);

            unset($params['form']['data']);
        }

        // foreach($_FILES as $name => $data)
        // {
        //     if($name != 'data')
        //     {
        //         $params['form'][$name] = $data;
        //     }
        // }

        // if(isset($_FILES['data']))
        // {
        //     foreach($_FILES['data'] as $key => $data)
        //     {
        //         foreach($data as $model => $fields)
        //         {
        //             foreach($fields as $field => $value)
        //             {
        //                 $params['data'][$model][$field][$key] = $value;
        //             }
        //         }
        //     }
        // }

        if(isset($params['data']['controller']))
        {
            $params['controller'] = Sanitize::getString($params['data'],'controller');

            $params['action'] = Sanitize::getString($params['data'],'action');
        }

        $Router = S2Router::getInstance();

        $Router->app = $this->app;

        $params = S2Router::parse($params);

        return $params;
    }

/**
 * Outputs cached dispatch view cache
 *
 * @param string $url Requested URL
 * @access public
 */
    function cached($url) {

        S2App::import('Component','config',$this->app);

        $controller = new stdClass();

        if(class_exists('ConfigComponent')) {

            $Config = new ConfigComponent();

            $Config->startup($controller);
        }

        $User = cmsFramework::getUser();

        /**
         * Add support for Router/Actions specific view caching
         * @var [type]
         */
        $current_controller = Inflector::camelize($this->controller);

        $cacheConfig = Configure::read('Cache');

        if (
                (!defined('MVC_FRAMEWORK_ADMIN') && $User->id === 0 && !Sanitize::getBool($cacheConfig,'disable') && Sanitize::getBool($cacheConfig,'view'))
                ||
                (!defined('MVC_FRAMEWORK_ADMIN') && (Sanitize::getBool($cacheConfig,$current_controller)))
            ) {

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

                    S2App::import('Core', 'View',$this->app);
                }

                $controller = null;

                $view = new MyView($controller, false);

                // Pass the configuration object to the view and set the theme variable for helpers

                $view->name = $this->controller;

                $view->action = $this->action;

                $view->page = Sanitize::getInt($this->params,S2_QVAR_PAGE);

                $view->limit = Sanitize::getInt($this->params,'limit');

                $view->Config = $Config;

                $view->viewTheme = $Config->template;

                $view->ajaxRequest = S2Dispatcher::isAjax();

                $out = $view->renderCache($filename, S2getMicrotime());

                return $out;
            }
        }

        return false;
    }
}

class s2Error {

    var $errors = array();

    var $__errors_all = array();

    var $app;

    var $debug = true;

    var $__init = false;

    static function getInstance() {

        static $instance = array();

        if (!isset($instance[0]) || !$instance[0]) {
            $instance[0] = new s2Error();
        }

        return $instance[0];
    }

    function init($app) {

        $_this = S2Error::getInstance();

        if(!$this->__init) {

            $_this->app = $app;

            $_this->__init = true;
        }
    }

    static function reset() {

        $_this = S2Error::getInstance();

        $_this->__errors_all = array_insert($_this->__errors_all,$_this->errors);

        $_this->errors = array();
    }

    static function add($error, $key) {

        if($key != 'php' ||
            ($key == 'php' &&
                !strstr($error,'loadSetupFile') && /* Joomla 2.5 */
                !strstr($error,'uri.php') && /* Joomla 2.5 */
                !strstr($error,'/libraries/') && /* Joomla 2.5 */
                !strstr($error,'factory.php') && /* Joomla 2.5 */
                !strstr($error,'icon.php') && /* Joomla 2.5 */
                !strstr($error,'application.php') && /* Joomla 2.5 */
                !strstr($error,'CFactory') && /* JomSocial */
                !strstr($error,'com_community') && /* JomSocial */
                !strstr($error,'jomsocial') && /* JomSocial */
                !strstr($error,'WFEditor') /* JCE */
                // && (
                //     strstr($error,'jreviews') ||
                //     strstr($error,'com_s2framework') ||
                //     strstr($error,'templates'.DS.'jreviews_overrides'))
            )
        ) {
            $_this = S2Error::getInstance();

            $md5 = md5($error);

            if(!isset($_this->__errors_all[$key][$md5])) {

                if(in_array($key,array('query','query_error'))) {

                    $error = str_replace('#__',cmsFramework::getConfig('dbprefix'),$error);
                }

                $_this->errors[$key][$md5] = $error;

                appLogMessage($error,$key);
            }
        }
    }

    static function appCheck($path, $errornum) {

        // return $errornum != E_STRICT;
        return true;

        $_this = S2Error::getInstance();

        $path = str_replace(realpath(PATH_ROOT), '', $path);

        return strstr($path,$_this->app);
    }

    static function errorHandler($errno, $errstr, $errfile, $errline)
    {
        // if(error_reporting() == 0) return false;

        if(s2Error::appCheck($errfile, $errno)) {

            s2Error::add("[$errno] $errstr on line $errline in file $errfile",'php');
        }

        return true;
    }

    static function registerShutdown()
    {
        $error = error_get_last();

        $_this = S2Error::getInstance();

        if(!$_this->debug) return;

        if(!empty($error)) {

            extract($error);

            if(s2Error::appCheck($file, $type) && ($type == E_ERROR || $type == E_PARSE)) {

                S2Error::add("[$type] $message on line $line in file $file",'php');
            }

        }

        if(empty($_this->errors)
            || (empty($_this->errors['php']) && S2Dispatcher::isAjax())) {

            return false;
        }

        ?>

        <?php if(!S2Dispatcher::isAjax()):?>
        <style>
        .s2Debug {
            margin: 50px 0;
            background-color: white;
            color: black;
            border: 1px dashed silver;
            padding: 10px;
            position: relative;
        }
        .s2Debug h1 {
            background-color: #2C2C2C;
            color: white;
            padding: 10px;
            margin: 0;
            font-size: 16px;
            line-height: 1em;
        }
        .s2Debug h3 {
            background-color: #DDD;
            color: black;
            font-size: 14px;
            padding: 5px;
            text-decoration: none;
            margin: 0px;
        }
        .s2Debug ul li, .s2Debug ol li {
            margin: 5px 1.5em;
        }
        .s2Debug ul, .s2Debug ol {
            margin: 0 1.5em 1.5em 1.5em;
        }
        .s2Debug ul {
            list-style-type: disc;
        }
        .s2Debug ol {
            list-style-type: decimal;
        }
        </style>
        <?php endif;?>

        <div class="s2Debug">

            <h1>S2Framework Debug Console</h1>

            <?php if(isset($_this->errors['php'])):?>

            <h3>PHP Errors</h3>

            <ul>

                <?php foreach($_this->errors['php'] AS $error):?>
                <li><?php echo $error;?></li>
                <?php endforeach;?>

            </ul>

            <?php endif;?>

            <?php if(isset($_this->errors['query_error'])):?>

            <h3>Database Query Errors</h3>

            <ol>

                <?php foreach($_this->errors['query_error'] AS $error):?>
                <li><?php echo $error;?></li>
                <?php endforeach;?>

            </ol>

            <?php endif;?>


            <?php if(isset($_this->errors['query'])):?>

            <h3>Database Queries (<?php echo count($_this->errors['query']);?>)</h3>

            <ol>

                <?php foreach($_this->errors['query'] AS $error):?>
                <li><?php echo $error;?></li>
                <?php endforeach;?>

            </ol>

            <?php endif;?>

        </div>

        <?php

        s2Error::reset();

        return false;
    }

}