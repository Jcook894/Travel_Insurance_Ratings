<?php
/**
 * Object class, allowing __construct and __destruct in PHP4.
 *
 * Also includes methods for logging and the special method RequestAction,
 * to call other Controllers' Actions from anywhere.
 *
 * PHP versions 4 and 5
 *
 * CakePHP(tm) :  Rapid Development Framework <http://www.cakephp.org/>
 * Copyright 2005-2008, Cake Software Foundation, Inc.
 *                                1785 E. Sahara Avenue, Suite 490-204
 *                                Las Vegas, Nevada 89104
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 *
 * @modified by ClickFWD LLC
 */

class S2Object {

    var $viewSuffix;
    var $viewTheme;
    var $app;
    var $file_prefix = false;

    function __construct()
    {
        if (method_exists($this, '__destruct'))
        {
            register_shutdown_function(array(&$this, '__destruct'));
        }
    }

    function locateThemeFile($action, $file, $ext = '.thtml', $forceFrontEnd = false)
    {
        $file = strtolower($file);

        $fallback_theme = 'default';

        if(isset($this->Config))
        {
            $fallback_theme = Sanitize::getString($this->Config,'fallback_theme','default');
        }
        else
        {
            $Config = Configure::read('JreviewsSystem.Config');

            if(!empty($Config)) $fallback_theme = Sanitize::getString($Config,'fallback_theme','default');
        }

        $path = false;

        $action = strtolower($action);

        $App = S2App::getInstance();

        $suffix = strtolower($this->viewSuffix);

        if(is_string($forceFrontEnd))
        {
            $location = $forceFrontEnd;
        }
        else {

            $location = $forceFrontEnd ? 'Theme' : (defined('MVC_FRAMEWORK_ADMIN') ? 'AdminTheme' : 'Theme');
        }

        $Paths = $App->{$this->app.'Paths'}[$location];

        if(!isset($Paths[$this->viewTheme]))
        {
            $this->viewTheme = 'default';
        }

//        echo 'app: ' . $this->app . '<br />';
//        echo 'theme: ' . $this->viewTheme. '<br />';
//        echo 'suffix: ' . $this->viewSuffix. '<br />';
//        echo $location.DS.$this->viewTheme.DS.$action.DS.$file.$this->viewSuffix.$ext.'<br />';

        $filename = $file . $ext;

        $filename_suffixed = $file . $suffix . $ext;

        if(isset($Paths[$this->viewTheme][$action][$filename_suffixed]))
        {
            // Selected theme w/ suffix
            $path =  $Paths[$this->viewTheme][$action][$filename_suffixed] == $filename_suffixed
                        ?
                        $Paths[$this->viewTheme]['.info']['path'] . $action . DS . $filename_suffixed
                        :
                        $Paths[$this->viewTheme][$action][$filename_suffixed]
                        ;
        }
        elseif(isset($Paths[$fallback_theme][$action][$filename_suffixed]))
        {
            // Fallback theme w/ suffix
           $path =  $Paths[$fallback_theme][$action][$filename_suffixed] == $filename_suffixed
                    ?
                    $Paths[$fallback_theme]['.info']['path'] . $action . DS . $filename_suffixed
                    :
                    $Paths[$fallback_theme][$action][$filename_suffixed]
                    ;
        }
        elseif(isset($Paths['default'][$action][$filename_suffixed]))
        {
            // Default theme w/ suffix
           $path =  $Paths['default'][$action][$filename_suffixed] == $filename_suffixed
                    ?
                    $Paths['default']['.info']['path'] . $action . DS . $filename_suffixed
                    :
                    $Paths['default'][$action][$filename_suffixed]
                    ;
        }
        elseif(isset($Paths[$this->viewTheme][$action][$filename]))
        {
            // Selected theme w/o suffix
            $path = $Paths[$this->viewTheme][$action][$filename] == $filename
                    ?
                    $Paths[$this->viewTheme]['.info']['path'] . $action . DS . $filename
                    :
                    $Paths[$this->viewTheme][$action][$filename]
                    ;
        }
        elseif(isset($Paths[$fallback_theme][$action][$filename]))
        {
            // Fallback theme w/o suffix
            $path = $Paths[$fallback_theme][$action][$filename] == $filename
                    ?
                    $Paths[$fallback_theme]['.info']['path'] . $action . DS . $filename
                    :
                    $Paths[$fallback_theme][$action][$filename]
                    ;
        }
        elseif(isset($Paths['default'][$action][$filename]))
        {
            // Default theme w/o suffix
            $path = $Paths['default'][$action][$filename] == $filename
                    ?
                    $Paths['default']['.info']['path'] . $action . DS . $filename
                    :
                    $Paths['default'][$action][$filename]
                    ;
        }

        return $path ? PATH_ROOT . $path : false;
    }

    function locateStyleSheet($file,$options = array())
    {
        $defaults = array('admin'=>false,'relative'=>false,'minified'=>false,'params'=>'');

        $options = array_insert($defaults, $options);

        extract($options);

        $fallback_theme = 'default';

        if(isset($this->Config))
        {
            $fallback_theme = Sanitize::getString($this->Config,'fallback_theme','default');
        }
        else
        {
            $Config = Configure::read('JreviewsSystem.Config');

            if(!empty($Config))
            {
                $fallback_theme = Sanitize::getString($Config,'fallback_theme','default');
            }
        }

        $url = false;

        // Fix for Windows servers

        $file = str_replace('/', DS, $file);

        $parse = parse_url($file);

        $file = $parse['path'];

        if(substr($file,-3) != '.css')
        {
            $file_min = !strstr($file, '.min') ? $file . '.min.css' : $file . '.css';

            $file = $file . '.css';
        }

        $App = S2App::getInstance($this->app);

        $Paths = $admin ? $App->{$this->app.'Paths'}['AdminTheme'] : $App->{$this->app.'Paths'}['Theme'];

        if(!isset($Paths[$this->viewTheme]))
        {
            $this->viewTheme = 'default';
        }

        if($minified && isset($Paths[$this->viewTheme]['theme_css'][$file_min]))
        {
            $file = $file_min;

            $url = $Paths[$this->viewTheme]['theme_css'][$file_min];
        }
        elseif($minified && isset($Paths[$fallback_theme]['theme_css'][$file_min]))
        {
            $file = $file_min;

            $url = $Paths[$fallback_theme]['theme_css'][$file_min];
        }
        elseif($minified && isset($Paths['default']['theme_css'][$file_min]))
        {
            $file = $file_min;

            $url = $Paths['default']['theme_css'][$file_min];
        }
        elseif(isset($Paths[$this->viewTheme]['theme_css'][$file]))
        {
            $url = $Paths[$this->viewTheme]['theme_css'][$file];
        }
        elseif(isset($Paths[$fallback_theme]['theme_css'][$file]))
        {
            $url = $Paths[$fallback_theme]['theme_css'][$file];
        }
        elseif(isset($Paths['default']['theme_css'][$file]))
        {
            $url = $Paths['default']['theme_css'][$file];
        }

        if($url == '') return '';

        $url = $url == $file
                ?
                    $Paths[$this->viewTheme]['.info']['path'] . 'theme_css/' . $file
                :
                    $url
                ;

        // Fix for Windows servers

        $url = str_replace(DS, '/', $url);

        return ($relative ? WWW_ROOT_REL : WWW_ROOT) . $url;
    }

    function locateScript($file,$options = array())
    {
        $defaults = array('admin'=>false,'relative'=>false,'minified'=>false,'params'=>'');

        $options = array_insert($defaults, $options);

        extract($options);

        $url = $file_min = false;

        // Fix for Windows servers

        $file = str_replace('/', DS, $file);

        $parse = parse_url($file);

        $file = $parse['path'];

        if(substr($file,-3) != '.js')
        {
            $file_min = !strstr($file, '.min') ? $file . '.min.js' : $file . '.js';

            $file = $file . '.js';
        }

        $file = str_replace(DS,_DS,$file);

        $App = S2App::getInstance($this->app);

        $Paths = $admin ? $App->{$this->app.'Paths'}['AdminJavascript'] : $App->{$this->app.'Paths'}['Javascript'];

        if($minified && isset($Paths[$file_min]))
        {
            $url = $Paths[$file_min];
        }
        elseif(isset($Paths[$file]))
        {
            $url = $Paths[$file];
        }

        if($url == '') return '';

        // Fix for Windows servers

        $url = str_replace(DS, '/', $url);

        return ($relative ? WWW_ROOT_REL : WWW_ROOT) . $url;
    }

/**
 * Calls a controller's method from any location.
 *
 * @param string $url URL in the form of Cake URL ("/controller/method/parameter")
 * @param array $extra if array includes the key "return" it sets the AutoRender to true.
 * @return mixed Success (true/false) or contents if 'return' is set in $extra
 * @access public
 */
    function requestAction($url, $extra = array())
    {
        $parentControllerMemory = Sanitize::getString($this, 'name') != '' && Sanitize::getInt($this, 'action') != '';

        if($parentControllerMemory)
        {
            $controllerName = $this->name;

            $controllerAction = $this->action;

        }

        $app = Sanitize::getString($extra,'app','jreviews');

        unset($extra['app']);

        if(empty($url)) {
            return false;
        }

        if(!class_exists('S2Dispatcher'))
        {
            require S2_FRAMEWORK . DS . 'dispatcher.php';
        }

        if(in_array('return', $extra, true))
        {
            $extra = array_merge($extra, array('return' => 0, 'autoRender' => 1));
        }

        $params = array_merge(array('token'=>cmsFramework::formIntegrityToken($extra,array('module','module_id','form','data'),false),'autoRender' => 0, 'return' => 1, 'bare' => 1, 'requested' => 1), $extra);

        $disable404 = true;

        $dispatcher = new S2Dispatcher($app,null,$disable404);

        // Need to unset controller/action data here so the original request doesn't override the new request call

        if(isset($_REQUEST['data']))
        {
            unset($_REQUEST['data']['controller'], $_REQUEST['data']['action']);
        }

        if(isset($_POST['data']))
        {
            unset($_POST['data']['controller'], $_POST['data']['action']);
        }

        unset($_REQUEST['url'], $_POST['url']);

        $url_parts = explode('/', $url);

        if(count($url_parts) > 2 && $url_parts[0] == 'admin')
        {
            $params['data']['controller'] = 'admin/' . $url_parts[1];

            $params['data']['action'] = $url_parts[2];
        }
        else {

            $params['data']['controller'] = $url_parts[0];

            $params['data']['action'] = $url_parts[1];
        }

        $out = $dispatcher->dispatch($params);

        if($parentControllerMemory)
        {
            $this->name = $controllerName;

            $this->action = $controllerAction;
        }

        return $out;
     }

 /**
 * Stop execution of the current script
 *
 * @param $status see http://php.net/exit for values
 * @return void
 * @access public
 */
    function _stop($status = 0) {
        exit($status);
    }
}
