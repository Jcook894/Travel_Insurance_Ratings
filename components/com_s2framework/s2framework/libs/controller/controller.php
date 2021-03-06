<?php
/**
 * S2Framework
 * Copyright (C) 2010-2015 ClickFWD LLC
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 * @license       http://www.opensource.org/licenses/mit-license.php The MIT License
**/

defined( 'MVC_FRAMEWORK') or die( 'Direct Access to this location is not allowed.' );

class S2Controller extends S2Object{

    var $_config;
    var $_user;
    var $language;
    var $itemid;
    var $sef;
    var $ipaddress;
    var $invalidToken = false;

    var $autoLayout = true;
    var $autoRender = true;
    var $plgBeforeRender = array();
    var $plgAfterFilter = array();

    var $app;
    var $assets = array();
    var $name;
    var $data = array();
    var $rawData = array();
    var $helpers = array();
    var $components = array();
    var $uses = array();
    var $layout = 'default';
    var $view = 'View';
    var $viewPath;
    var $viewSite;
    var $viewSuffix = '';
    var $viewVars;
    var $ajaxRequest = false;
    var $isRequested = false;

    // For ajax plugin functionality
    var $beforeAjaxResponse = null;
    var $afterAjaxresponse = null;

    var $cacheAction = false;

    function __construct($params = array())
    {
        # Get ip address
        $this->ipaddress = s2GetIpAddress();

		if(is_string($params)) {
			$this->app = $params;
		}
		else {
			foreach($params AS $prop=>$val) {
				$this->{$prop} = $val;
			}
		}

		# Load models
        $this->__initModels();

		parent::__construct();
    }

    function __initComponents($components = null)
    {
        $comps = !is_null($components) ? $components : $this->components;

        if(!empty($comps))
        {
            S2App::import('Component',$comps,$this->app);

            foreach($comps AS $component)
            {
                # Remove path from component name when using admin components to get class instance
                $component = str_replace(MVC_ADMIN._DS,'',$component);

                if(is_array($component)) {

                    $component = end(explode(DS,$component));
                }

                $method_name = inflector::camelize($component);

                $class_name = $method_name.'Component';

                if(class_exists($class_name))
                {
                    if(!isset($this->{$method_name}))
                    {
                        $this->{$method_name} = new $class_name();

                        if(isset($this->app)) $this->{$method_name}->app = $this->app;

                        if(isset($this->name)) $this->{$method_name}->controller_name = $this->name;

                        if(isset($this->action)) $this->{$method_name}->controller_action = $this->action;

                        if (method_exists($this->{$method_name},'startup'))
                        {
                            $this->{$method_name}->startup($this);
                        }

                        if(method_exists($this->{$method_name},'plgAfterFilter'))
                        {
                            $this->plgAfterFilter[] = $method_name;
                        }
                    }
                }
            }
        }

        $this->plgAfterFilter = array_unique($this->plgAfterFilter);
    }

    function __initModels($models = null) {

        $models = !empty($models) ? $models : $this->uses;

        if(!empty($models)) {

            S2App::import('Model',$models,$this->app);

            foreach($models AS $model) {

                $method_name = inflector::camelize($model);

                $class_name = $method_name.'Model';

				$this->{$method_name} = new $class_name();

				$this->{$method_name}->controller_name = $this->name;

				$this->{$method_name}->controller_action = $this->action;
            }
        }

    }

    /**
     * Delete wildcard files from directory and subdirectories
     *
     * @param string $path
     * @param file with wildcards $match
     * @return string with info on number of files deleted and size
     */
    function __rfr($path,$match){

        static $deld = 0, $dsize = 0;

        $dirs = glob($path."*");
        $files = glob($path.$match);

        if(!empty($files)) {
            foreach($files as $file){
              if(is_file($file)){
                 $dsize += filesize($file);
                 @unlink($file);
                 $deld++;
              }
            }
        }

        if(!empty($dirs)) {
            foreach($dirs as $dir){
              if(is_dir($dir)){
                 $dir = basename($dir) . "/";
                 $this->__rfr($path.$dir,$match);
              }
            }
        }

        return "$deld files deleted with a total size of $dsize bytes";
    }

    function console($var)
    {
        echo $this->makeJS("console.log('".json_encode($var)."');");
    }

    function makeJS($js){
        $js = Sanitize::stripWhitespace($js);
        if($js!=''){
            return '<script type="text/javascript">'.(is_array($js) ? implode('',$js) : $js).'</script>';
        }
        return '';
    }

    function ajaxResponse($response,$javascript=true,$vars=array())
    {
        if(!empty($this->beforeAjaxResponse)) $response = array_unshift($response, $this->beforeAjaxResponse);
        if(!empty($this->afterAjaxResponse)) $response = array_merge($response, $this->afterAjaxResponse);

        if(empty($vars))
        {
            return cmsFramework::jsonResponse(array(
                'response'=>($javascript ? $this->makeJS($response) : $response)
            ));
        }
        $responseObject = array(
            'response'=>($javascript ? $this->makeJS($response) : $response)
        );

        return cmsFramework::jsonResponse(array_merge($responseObject,$vars));
    }

    function ajaxUpdatePage($target_id,$update_text,$update_html='',$vars=array(),$javascript=true)
    {
        $action = 'update_page';
        if(isset($vars['response'])){
            $vars['response'] = ($javascript ? $this->makeJS($vars['response']) : $vars['response']);
        }

        return cmsFramework::jsonResponse(array_merge(compact('action','target_id','update_text','update_html'),$vars));
    }

    function ajaxUpdateElement($target_id,$update_html='',$response=array(),$javascript=true)
    {
        if(!empty($this->beforeAjaxResponse)) $response = array_unshift($response, $this->beforeAjaxResponse);
        if(!empty($this->afterAjaxResponse)) $response = array_merge($response, $this->afterAjaxResponse);

        $action = 'update_element';
        return cmsFramework::jsonResponse(array_merge(compact('action','target_id','update_html'),array('response'=>($javascript ? $this->makeJS($response) : $response))));
    }

    function ajaxUpdateDialog($update_text,$response='',$javascript=true)
    {
        $action = 'update_dialog';
        $responseObject = array(
            'response'=>($javascript ? $this->makeJS($response) : $response)
        );

        return cmsFramework::jsonResponse(array_merge(compact('action','update_text'),$responseObject));
    }

    function ajaxError($error_text,$response='')
    {
        return cmsFramework::jsonResponse(array(
            'action'=>'error',
            'update_text'=>stripslashes($error_text),
            'response'=>$this->makeJS($response)
        ));
    }

    function ajaxValidation($validation_text,$response='')
    {
        return cmsFramework::jsonResponse(array(
            'action'=>'validation',
            'update_text'=>$validation_text,
            'response'=>$this->makeJS($response)
        ));
    }

    function render($action = null, $file = null, $layout = null)
    {
        $this->beforeRender();

        foreach($this->components AS $component)
        {
            $componentName = Inflector::camelize($component);

            if(isset($this->{$componentName}) && method_exists($this->{$componentName}, 'plgBeforeRender'))
            {
                $this->{$componentName}->plgBeforeRender();
            }
        }

        foreach($this->plgBeforeRender AS $plg_name)
        {
            $this->{$plg_name}->plgBeforeRender();
        }

        $viewClass = 'MyView';

        $this->__viewClass = new $viewClass($this);

        $out = $this->__viewClass->render($action, $file, $layout);

//        $this->autoRender = false;

        return $out;
    }

    /**
     * Doesn't trigger any events. Used to partially render theme files to feed them as variables
     * in other theme files
     */
    function partialRender($action = null, $file = null, $layout = null)
    {
        $viewClass = 'MyView';

        $this->__viewClass = new $viewClass($this);

        $layoutTmp = $this->__viewClass->autoLayout;

        $this->__viewClass->autoLayout = !$layout ? false : true;

        $out = $this->__viewClass->render($action, $file, $layout);

        $this->__viewClass->autoLayout = $layoutTmp;

        return $out;
    }

    function cached($path) {

        if (Configure::read('Cache.enable') && Configure::read('Cache.view'))
        {
            $path = Inflector::slug($path);

            $filename = CACHE . 'views' . DS . $path . '.php';

            if (!file_exists($filename)) {
                $filename = CACHE . 'views' . DS . $path . '_index.php';
            }

            if (file_exists($filename)) {

                if (!class_exists('MyView')) {
                    S2App::import('Core', 'View',$this->app);
                }

                $view = new MyView($this, false);

                $view->ajaxRequest = $this->ajaxRequest;
                $view->viewSuffix = $this->viewSuffix;
                $view->name = $this->name;
//                $view->helpers = $this->helpers;
//                $view->layout = $this->layout;

                return $view->renderCache($filename, S2getMicrotime());
            }

        }
        return false;
    }

    function cacheView($controller, $action, $path, $page)
    {
        $viewFileName = '';

        if (Configure::read('Cache.enable') && Configure::read('Cache.view'))
        {
            $viewFileName = $this->locateThemeFile($controller, $action);

            S2App::import('Helper','Cache');

            $Cache = new CacheHelper();

            $Cache->app = $this->app;

            $Cache->here = $path;

            $Cache->cacheAction = Configure::read('Cache.expires');

            if($viewFileName)
            {
                $Cache->cache($viewFileName,$page,true,$this->autoRender);
            }
        }
    }

    /**
     * Send variables to view
     *
     * @param unknown_type $one
     * @param unknown_type $two
     */
    function set($one, $two = null)
    {
        $data = array();

        if (is_array($one)) {

            if (is_array($two)) {
                $data = array_combine($one, $two);
            } else {
                $data = $one;
            }

        }
	else {

            $data = array($one => $two);
        }

        foreach ($data as $name => $value)
	{
            $this->viewVars[$name] = $value;
        }
    }

    function Quote( $values )
    {
    	$Model = new S2Model;

	return $Model->Quote($values);
    }

    function QuoteLike( $text )
    {
        $Model = new S2Model;

        $text = $Model->QuoteLike($text);

        return $text;
    }


    function beforeFilter() {
    }

    function beforeRender() {
    }

    function afterFilter()
    {
        foreach($this->plgAfterFilter AS $component)
        {
            $this->{$component}->plgAfterFilter();
        }
    }
}
