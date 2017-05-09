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
 *								1785 E. Sahara Avenue, Suite 490-204
 *								Las Vegas, Nevada 89104
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 * 
 * 
 * @modified by Alejandro Schmeichler
 * @lastmodified 2008-03-12 
 */

class S2Object {

	function S2Object() 
	{
        $args = func_get_args();

        if (method_exists($this, '__destruct')) 
        {
            register_shutdown_function(array(&$this, '__destruct'));
        }
        
        call_user_func_array(array(&$this, '__construct'), $args);
    }

    function __construct() {}  
    
/**
 * Calls a controller's method from any location.
 *
 * @param string $url URL in the form of Cake URL ("/controller/method/parameter")
 * @param array $extra if array includes the key "return" it sets the AutoRender to true.
 * @return mixed Success (true/false) or contents if 'return' is set in $extra
 * @access public
 */
	function requestAction($url, $extra = array()) {

		$app = Sanitize::getString($extra,'app','jreviews');
		$xajax = Sanitize::getVar($extra,'xajax',false);
		unset($extra['app']);
		unset($extra['xajax']);
		
		if (empty($url)) {
			return false;
		}
		if (!class_exists('S2Dispatcher')) {
			require S2_FRAMEWORK . DS . 'dispatcher.php';
		}
		if (in_array('return', $extra, true)) {
			$extra = array_merge($extra, array('return' => 0, 'autoRender' => 1));
		}
		
		$params = array_merge(array('autoRender' => 0, 'return' => 1, 'bare' => 1, 'requested' => 1), $extra);
		
		$disable404 = true;
		$dispatcher = new S2Dispatcher($app,$xajax,$disable404);

		return $dispatcher->dispatch($url, $params);
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