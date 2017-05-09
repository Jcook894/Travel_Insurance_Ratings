<?php
defined('MVC_FRAMEWORK') or die( 'Direct Access to this location is not allowed.' );

/**
 * Methods for displaying presentation data in the view.
 *
 * PHP versions 4 and 5
 *
 * CakePHP(tm) :  Rapid Development Framework <http://www.cakephp.org/>
 * Copyright 2005-2007, Cake Software Foundation, Inc.
 *								1785 E. Sahara Avenue, Suite 490-204
 *								Las Vegas, Nevada 89104
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @license			http://www.opensource.org/licenses/mit-license.php The MIT License
 * 
 * 
 * @modified by Alejandro Schmeichler
 * @lastmodified 2008-03-06 
 */

class MyView extends S2Object{
	
/**
 * Path parts for creating links in views.
 *
 * @var string Base URL
 * @access public
 */
	var $base = null;
/**
 * Stores the current URL (for links etc.)
 *
 * @var string Current URL
 */
	var $here = null;
	
	var $autoLayout	= true;
	var $auto
     = true;
	var $ext = '.thtml';
	var $layout = 'default';
	var $loaded = array();
	var $helpers = array();
	var $viewVars = array();
	var $viewTheme = 'default';
	var $hasRendered = false;
	var $app = 'jreviews';

	# remove unused keys
	var $__passedVars = array('app','viewVars', 'view', 'Access', 'action', 'autoLayout', 'autoRender', 'base', 'cacheAction', 'Config', 'webroot', 'helpers', 'here', 'limit', 'layout', 'modelNames', 'module_limit', 'module_page', 'name', 'offset', 'page', 'pageTitle', 'viewSuffix', 'viewTheme', 'viewPath', 'params', 'data', 'webservices', 'plugin', 'passedArgs', 'rawData', 'xajaxRequest');

	# These are passed to the Helper Object for use in helpers
	var $__helperVars = array('Access','action','app','name','data','page','params','passedArgs','Config','limit','module_limit','module_page', 'viewSuffix', 'viewTheme','viewImages','viewImagesPath','xajaxRequest');

	function __construct(&$controller, $register = true){

		if (is_object($controller)) {
			
			$count = count($this->__passedVars);
			
			for ($j = 0; $j < $count; $j++) {

				if (isset($this->__passedVars[$j]) && isset($controller->{$this->__passedVars[$j]})) 
				{
					$var = $this->__passedVars[$j];
					$this->{$var} = $controller->{$var};
				}
			}
		}

		$inAdmin = defined('MVC_FRAMEWORK_ADMIN') ? true : false;
		
		$theme = Configure::read('Theme.name','default');

		if(!isset($this->viewPath)) {
			if($inAdmin) { // Administration
				$this->viewPaths[] = S2Paths::get($this->app, 'S2_ADMIN_VIEWS_OVERRIDES') . 'themes' . DS . $theme . DS;				
				$this->viewPaths[] = S2Paths::get($this->app, 'S2_ADMIN_VIEWS') . 'themes' . DS . $theme . DS;				
				$this->viewPaths[] = S2Paths::get($this->app, 'S2_ADMIN_VIEWS_OVERRIDES') . 'themes' . DS . 'default' . DS;								
				$this->viewPaths[] = S2Paths::get($this->app, 'S2_ADMIN_VIEWS') . 'themes' . DS . 'default' . DS;
			} else { // Front-end
				$this->viewPaths[] = S2Paths::get($this->app, 'S2_THEMES_OVERRIDES') . $theme . DS;
				$this->viewPaths[] = S2Paths::get($this->app, 'S2_THEMES') . $theme . DS;
				$this->viewPaths[] = S2Paths::get($this->app, 'S2_THEMES_OVERRIDES') . 'default' . DS;
				$this->viewPaths[] = S2Paths::get($this->app, 'S2_THEMES') . 'default' . DS;
			}
		}

		if(!isset($this->viewImages)) {

			if($inAdmin) { // Administration
				$paths = array(
					S2Paths::get($this->app, 'S2_ADMIN_VIEWS_OVERRIDES') . 'themes' . DS . $theme . DS . 'theme_images' . DS,
					S2Paths::get($this->app, 'S2_ADMIN_VIEWS') . 'themes' . DS . $theme . DS . 'theme_images' . DS,
					S2Paths::get($this->app, 'S2_ADMIN_VIEWS_OVERRIDES') . 'themes' . DS . 'default' . DS . 'theme_images' . DS,
					S2Paths::get($this->app, 'S2_ADMIN_VIEWS') . 'themes' . DS . 'default' . DS . 'theme_images' . DS,
				);
				
				$path = fileExistsInPath(array('name'=>'','suffix'=>'','ext'=>''),$paths);
				$this->viewImages = pathToUrl($path);
				$this->viewImagesPath = $path;

			} else { // Front-end

				$paths = array(
					S2Paths::get($this->app, 'S2_VIEWS_OVERRIDES') . 'themes' . DS . $theme . DS . 'theme_images' . DS,
					S2Paths::get($this->app, 'S2_VIEWS') . 'themes' . DS . $theme . DS . 'theme_images' . DS,
					S2Paths::get($this->app, 'S2_VIEWS_OVERRIDES') . 'themes' . DS . 'default' . DS . 'theme_images' . DS,
					S2Paths::get($this->app, 'S2_VIEWS') . 'themes' . DS . 'default' . DS . 'theme_images' . DS,
				);
				
				$path = fileExistsInPath(array('name'=>'','suffix'=>'','ext'=>''),$paths);
				$this->viewImages = pathToUrl($path);
				$this->viewImagesPath = $path;
			}
		}
				
		parent::__construct();		

/*		if ($register) {
			ClassRegistry::addObject('view', $this);
		}		*/
		
	}

	function render($action = null, $file = null, $layout = null) {

		if ($this->hasRendered) {
			return true;
		}
		
		$out = false;
	
		if($action === null) {
			$action = strtolower($this->name);
		} else {
			$action = strtolower($action);			
		}
	
		
		if($file === null) {
			$file = strtolower($this->action);
		} else {
			$this->view = $file;
			$file = strtolower($file);			
		}
		
		// Finds the view file
		$viewPath = @fileExistsInPath(array('name'=>$action . DS . $file,'suffix'=>$this->viewSuffix,'ext'=>$this->ext), $this->viewPaths);
		$out = $this->_render($viewPath, $this->viewVars);

		# Set layout file
		if ($layout === null) {
						
			$layout = $this->layout;
		
		}
		
		if ($out !== false) {
				
			if ($layout && $this->autoLayout) {
				$out = $this->renderLayout($out, $layout);
				if (isset($this->loaded['Cache']) && (($this->cacheAction != false)) && (Configure::read('Cache.view') === true)) {
					$replace = array('<s2:nocache>', '</s2:nocache>');
					$out = str_replace($replace, '', $out);
				}
			}

			$this->hasRendered = true;

			if($this->autoRender) {
				print $out;
			} else {
				return $out;
			}
			
		} else {
			$out = $this->_render($viewFileName, $this->viewVars);
			$msg = __("Error in view %s, got: <blockquote>%s</blockquote>", true);
			trigger_error(sprintf($msg, $viewFileName, $out), E_USER_ERROR);
		}
		return true;		

	}
	
	function renderLayout($content_for_layout, $layout = null) {
		
		$layout_fn = $this->_getLayoutFileName($layout);

		$data_for_layout = array_merge($this->viewVars,
			array(
				'content_for_layout' => $content_for_layout
			)
		);

		if (empty($this->loaded) && !empty($this->helpers)) 
		{
			$loadHelpers = true;
			
		} else {
			$loadHelpers = false;
			$data_for_layout = array_merge($data_for_layout, $this->loaded);
		}

		if (substr($layout_fn, -3) === 'ctp' || substr($layout_fn, -5) === 'thtml') {
			$this->output = MyView::_render($layout_fn, $data_for_layout, $loadHelpers, true);
		} else {
			$this->output = $this->_render($layout_fn, $data_for_layout, $loadHelpers);
		}		
//		$out = $this->_render($layout_fn, $data_for_layout, $loadHelpers);

		if ($this->output === false) 
		{
			$this->output = $this->_render($layout_fn, $data_for_layout);
			trigger_error(sprintf(__("Error in layout %s, got: <blockquote>%s</blockquote>", true), $layout_fn, $this->output), E_USER_ERROR);
			return false;
		}
		
/*		if (!empty($this->loaded)) {
			$helpers = array_keys($this->loaded);
			foreach ($helpers as $helperName) {
				$helper =& $this->loaded[$helperName];
				if (is_object($helper)) {
					if (is_subclass_of($helper, 'Helper') || is_subclass_of($helper, 'helper')) {
						$helper->afterLayout();
					}
				}
			}
		}		*/

		return $this->output;
	}
	
	function _getLayoutFileName($name = null) {

		if ($name === null) {
			$name = $this->layout;
		}

		$file = 'theme_layouts' . DS .$name;

		// Finds the view file
		$layoutPath = @fileExistsInPath(array('name'=>$file,'suffix'=>'','ext'=>$this->ext), $this->viewPaths);				

		return $layoutPath;
	}	

	function renderElement($name, $params = array(), $loadHelpers = false) {
		return $this->element($name, $params, $loadHelpers);
	}
	
	function element($name, $params = array(), $loadHelpers = false) {
		
		// Finds the view file
		$elementPath = @fileExistsInPath(array('name'=>'elements' . DS . $name ,'suffix'=>$this->viewSuffix,'ext'=>$this->ext), $this->viewPaths);				

		if ($elementPath) {
			
			$params = array_merge_recursive($params, $this->loaded);

			return $this->_render($elementPath, array_merge($this->viewVars, $params), $loadHelpers);
		
		}

	}
	
	function renderControllerView($controller, $name, $params = array(), $loadHelpers = false) {

		// Finds the view file
		$viewPath = @fileExistsInPath(array('name'=>$controller . DS . $name ,'suffix'=>$this->viewSuffix,'ext'=>$this->ext), $this->viewPaths);				
		
		if ($viewPath) {
			
			$params = array_merge_recursive($params, $this->loaded);

			return $this->_render($viewPath, array_merge($this->viewVars, $params), $loadHelpers);
		
		}
	}	

	function _render($___viewFn, $dataForView, $loadHelpers = true, $cached = false) {
                       
		// Load and initialize helper classes
		if ($loadHelpers && !(empty($this->helpers))) {
			
			App::import('Helper',$this->helpers,$this->app);

			foreach($this->helpers AS $helper)
			{								
				$helper = str_replace(MVC_ADMIN._DS,'',$helper);
					
				$method_name = inflector::camelize($helper);
				
				$class_name = $method_name.'Helper';

				if (!isset($this->loaded[$method_name])) {

					${$method_name} = & new $class_name($this->app);

					$this->loaded[$method_name] = & ${$method_name};

					# Pass View vars to Helper Object
					foreach($this->__helperVars AS $helperVar) {
						
						if(isset($this->$helperVar)) {
							${$method_name}->$helperVar = $this->$helperVar;
						}						
					}
				}
			}
		}

		if (!empty($dataForView)) {
			extract($dataForView, EXTR_SKIP);
		}

		if(!file_exists($___viewFn)) {
			return '<br />The template file ' . $___viewFn . ' is missing.';
		}

		ob_start();

		if (Configure::read() > 0) {
			include ($___viewFn);
		} else {
			@include ($___viewFn);
		}

		$out = ob_get_clean();

		if (isset($this->loaded['Cache']) && (($this->cacheAction != false)) && (Configure::read('Cache.view') === true)) {
			if (is_a($this->loaded['Cache'], 'CacheHelper')) {

				$cache =& $this->loaded['Cache'];
				$cache->base = $this->base;
				$cache->here = $this->here;
				$cache->helpers = $this->helpers;
				$cache->action = $this->action;
				$cache->controllerName = $this->name;
				$cache->layout	= $this->layout;
				$cache->cacheAction = $this->cacheAction;

				$cache->cache($___viewFn, $out, $cached);
			}
		}

		// Remove the nocache from all pages for xhtml compliance
		if (Configure::read('Cache.disable') || !Configure::read('Cache.view')) {
			$replace = array('<s2:nocache>', '</s2:nocache>');
			$out = str_replace($replace, '', $out);
		}

		return $out;
	}
	
function &_loadHelpers(&$loaded, $helpers, $parent = null) {
	             
	App::import('Helper',$this->helpers,$this->app);
                                   
	foreach($helpers AS $helper)
	{								
		$helper = str_replace(MVC_ADMIN._DS,'',$helper);
			
		$method_name = inflector::camelize($helper);
		
		$class_name = $method_name.'Helper';

		if (!isset($this->loaded[$method_name])) {
                                   
			${$method_name} = & new $class_name($this->app);
			$loaded[$method_name] = & ${$method_name};

			# Pass View vars to Helper Object
			foreach($this->__helperVars AS $helperVar) {
				if(isset($this->$helperVar)) {
					$loaded[$method_name]->$helperVar = $this->$helperVar;
				}						
			}
		}
	}

	return $loaded;
	
}
	
/**
 * Render cached view
 *
 * @param string $filename the cache file to include
 * @param string $timeStart the page render start time
 */
	function renderCache($filename, $timeStart) {
		
		ob_start();

		include ($filename);

		if (Configure::read() > 0 && $this->layout != 'xml') {
			echo "<!-- Cached Render Time: " . round(S2getMicrotime() - $timeStart, 4) . "s -->";
		}
		
		$out = ob_get_clean();

		if (preg_match('/^<!--cachetime:(\\d+)-->/', $out, $match)) {

			if (time() >= $match['1']) {
				@unlink($filename);
				unset ($out);
				return false;
			} else {
				if ($this->layout === 'xml') {
					header('Content-type: text/xml');
				}
				$out = str_replace('<!--cachetime:'.$match['1'].'-->', '', $out);
				// Remove the nocache from all pages for xhtml compliance
				$replace = array('<s2:nocache>','</s2:nocache>');
				$out = str_replace($replace,'', $out);
				return $out;
			}
		}
	}	
	
}