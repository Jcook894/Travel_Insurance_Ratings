<?php
/**
 * jReviews - Reviews Extension
 * Copyright (C) 2006-2009 Alejandro Schmeichler
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice
 **/

defined( 'MVC_FRAMEWORK') or die( 'Direct Access to this location is not allowed.' );

class S2Controller extends S2Object{
	
	var $_config;
	var $_acl;
	var $_db;
	var $_user;
	var $language;
	var $itemid;
	var $sef;
	var $ipaddress;
	
	var $autoLayout = true;		
	var $autoRender = true;

	var $app;
	var $name;
	var $data = array();
	var $rawData = array();
	var $uses;
	var $layout = 'default';
	var $view = 'View';
	var $viewPath;
	var $viewSite;
	var $viewSuffix = '';
	var $viewVars;	
	var $xajax = null;
	var $xajaxRequest = false;

	var $cacheAction = false;

	function __construct($app) {

		global $Itemid, $mosConfig_sef, $mosConfig_lang;

		
		cmsFramework::init($this);

/****************** THIS BLOCK CAN PROBABLY BE DELETED ******************/		
		$this->language = $mosConfig_lang;
		
		$this->itemid = $Itemid;
		
		$this->sef = $mosConfig_sef;

		# Get ip address
		$this->ipaddress = $_SERVER['REMOTE_ADDR'];		
/****************** THIS BLOCK CAN PROBABLY BE DELETED ******************/		
			
		$this->app = $app;
				
		# Load models
		$this->__initModels();				

		parent::__construct();
				
	}
				
	function __initComponents()
	{

		if(!empty($this->components))
		{
			App::import('Component',$this->components,$this->app);
			
            foreach($this->components AS $component)
			{			
				# Remove path from component name when using admin components to get class instance
//				$component = str_replace(MVC_ADMIN._DS,'',$component);
				$component = end(explode(DS,$component));
				
				$method_name = inflector::camelize($component);
				
				$class_name = $method_name.'Component';

				if(class_exists($class_name)){
					$this->{$method_name} = new $class_name();

					if (method_exists($this->{$method_name},'startup')){
						if (version_compare(phpversion(), '5.0') < 0) {
							// php4
							$this->{$method_name}->startup($this);						
						} else {	 
							// For some reason this fails in certain PHP5 server environments if $this is passed directly to the startup method	
//							$component = $this;	  // Ale - removed 
							$this->{$method_name}->startup($this);		
                        }
					}
				}
            }
		}
	}	
	
	function __initModels($models = null) {
			
		$models = !empty($models) ? $models : $this->uses;
		
		if(!empty($models)) {

			App::import('Model',$models,$this->app);
					
			foreach($models AS $model) {
								
				$method_name = inflector::camelize($model);

				$class_name = $method_name.'Model';
				
				$this->{$method_name} = new $class_name();

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
			
	function render($action = null, $file = null, $layout = null) 
	{		
		$viewClass = 'MyView';

		$this->__viewClass = & new $viewClass($this);

		$out = $this->__viewClass->render($action, $file, $layout);

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
					App::import('Core', 'View',$this->app);
				}

				$view = new MyView($this, false);

				$view->xajaxRequest = $this->xajaxRequest;
				$view->viewSuffix = $this->viewSuffix;
				$view->name = $this->name;
//				$view->helpers = $this->helpers;
//				$view->layout = $this->layout;
				
				return $view->renderCache($filename, S2getMicrotime());
			}
			
		}
		return false;
	}	
	
	function cacheView($controller, $action, $path, $page) 
	{
		if (Configure::read('Cache.enable') && Configure::read('Cache.view')) 
		{
			if(file_exists(S2Paths::get($this->app,'S2_THEMES') . $this->viewTheme . DS . $controller . DS . $action . $this->viewSuffix . '.thtml')) {
				$viewFileName = S2Paths::get($this->app,'S2_THEMES') . $this->viewTheme . DS . $controller . DS . $action . $this->viewSuffix . '.thtml';				
			} elseif(file_exists(S2Paths::get($this->app,'S2_THEMES') . $this->viewTheme . DS . $controller . DS . $action . '.thtml')) {
				$viewFileName =S2Paths::get($this->app,'S2_THEMES') . $this->viewTheme . DS . $controller . DS . $action . '.thtml';
			} elseif(file_exists(S2Paths::get($this->app,'S2_THEMES') . 'default' . DS . $controller . DS . $action . $this->viewSuffix . '.thtml')){
				$viewFileName = S2Paths::get($this->app,'S2_THEMES') . $this->viewTheme . DS . $controller . DS . $action . '.thtml';				
			} elseif(file_exists(S2Paths::get($this->app,'S2_THEMES') . 'default' . DS . $controller . DS . $action . '.thtml')){
				$viewFileName = S2Paths::get($this->app,'S2_THEMES') . 'default' . DS . $controller . DS . $action . '.thtml';				
			}
				
			App::import('Helper','Cache');
			$Cache = new CacheHelper();
			$Cache->app = $this->app;
			$Cache->here = $path;
			$Cache->cacheAction = Configure::read('Cache.expires');

			$Cache->cache($viewFileName,$page,true,$this->autoRender);
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
		
		} else {			
			$data = array($one => $two);
		
		}

		foreach ($data as $name => $value) {
							
			$this->viewVars[$name] = $value;
							
		}
		
	}
	
	function quote( $text ) {
	    if (phpversion() < '4.3.0') {
	        return '\'' . mysql_escape_string( $text ) . '\'';
	    } else {
	    	$quoted = @mysql_real_escape_string( $text, $this->_db->_resource );
	        if($quoted) {
		        return '\'' . $quoted . '\'';
	        } else {
				$quoted = @mysql_escape_string( $text );        	
		        return '\'' . $quoted . '\'';
	        }
	    }
	}	
	
	function quoteLike( $text ) {
	    if (phpversion() < '4.3.0') {
	        return '\'%' . mysql_escape_string( $text ) . '%\'';
	    } else {
	    	$quoted = @mysql_real_escape_string( $text, $this->_db->_resource );
	        if($quoted) {
		        return '\'%' . $quoted . '%\'';
	        } else {
				$quoted = @mysql_escape_string( $text );        	
		        return '\'%' . $quoted . '%\'';
	        }
	    }
	}
		
	function beforeFilter() {
	}

	function beforeRender() {
	}

	function afterFilter() {
	}	
}