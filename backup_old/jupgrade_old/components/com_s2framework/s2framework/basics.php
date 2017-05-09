<?php
/**
 * S2Framework
 * Copyright (C) 2010-2012 ClickFWD LLC
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 * @license       http://www.opensource.org/licenses/mit-license.php The MIT License
**/

defined( '_JEXEC') or die( 'Direct Access to this location is not allowed.' );

define('MVC_FRAMEWORK', 1);

/*********************************************************************
 * CONFIGURATION	
 *********************************************************************/
if(isset($_SERVER['SERVER_ADDR']) && $_SERVER['SERVER_ADDR'] == '127.0.0.1') {
	ini_set('display_errors','On');
	error_reporting(E_ALL);
}

if(function_exists('mb_regex_encoding')) mb_regex_encoding('UTF-8'); 

/*********************************************************************
 * DEFINE CMS CONSTANTS	
 *********************************************************************/
if(!defined('CMS_JOOMLA17')) define('CMS_JOOMLA17','CMS_JOOMLA17');
if(!defined('CMS_JOOMLA16')) define('CMS_JOOMLA16','CMS_JOOMLA16');
if(!defined('CMS_JOOMLA15')) define('CMS_JOOMLA15','CMS_JOOMLA15');

if (!defined('DS')) 			define('DS', DIRECTORY_SEPARATOR);
if (!defined('_DS')) 			define('_DS','/');
if (!defined('_PARAM_CHAR')) 	define('_PARAM_CHAR',':');
                        
/**
 * Returns CMS version and loads cms compat library
**/     
require_once (S2_ROOT . DS . 's2framework' . DS . 'libs' . DS . 'cms_compat' . DS . 'joomla.php');

if(!function_exists('getCmsVersion')) 
{
	function getCmsVersion()
    {	
        if(class_exists("JVersion"))
        {
            $version = new JVersion();
            switch($version->RELEASE)
            {
                case 1.5:
                    return CMS_JOOMLA15;
                break;                
                case 1.6:
                case 1.7:
                case 2.5:
				default:
                    return CMS_JOOMLA16;
                break;                
            }
        }		
	}
}

define( 'PATH_ROOT', JPATH_SITE . DS);

define('WWW_ROOT',str_replace('/administrator','',JURI::Base())); // Admin side

if(!defined('_PLUGIN_DIR_NAME')) define('_PLUGIN_DIR_NAME','plugins');		

/*********************************************************************
 * START FILE INCLUSIONS	
 *********************************************************************/
# Load paths
require( dirname(__FILE__)) . DS . 'config' . DS . 'paths.php';

# Load object class. Must be 1st to load
require( S2_LIBS . 'object.php' );

# Load libraries
require( S2_LIBS . 'class_registry.php' );
require( S2_LIBS . 'folder.php' );
require( S2_LIBS . 'cache.php' );
//require( S2_LIBS . 'overloadable.php' );
require( S2_LIBS . 'configure.php' );
require( S2_LIBS . 'sanitize.php' );
require( S2_LIBS . 'string.php' );
require( S2_LIBS . 'inflector.php' );
require( S2_LIBS . 'session.php' );
require( S2_LIBS . 'router.php' );
require( S2_LIBS . 'controller' . DS . 'controller.php' );
require( S2_LIBS . 'controller' . DS . 'component.php' );
require( S2_LIBS . 'view' . DS . 'helper.php' );
require( S2_LIBS . 'view' . DS . 'view.php' );
require( S2_LIBS . 'model' . DS . 'model.php' );

require( S2_FRAMEWORK . DS . 'dispatcher.php' );

/*********************************************************************
 * DEFINE GLOBAL CONSTANTS	
 *********************************************************************/
!defined('PHP5') and define ('PHP5', (phpversion() >= 5));

$now = gmdate('Y-m-d H:i',time());
$today = gmdate('Y-m-d',time());

!defined('_CURRENT_SERVER_TIME') and DEFINE('_CURRENT_SERVER_TIME', $now);

!defined('CURRENT_SERVER_TIME') and DEFINE('CURRENT_SERVER_TIME', $now);

!defined('_TODAY') and DEFINE('_TODAY', $today);

!defined('NULL_DATE') and DEFINE('NULL_DATE', '0000-00-00 00:00:00');

!defined('_NULL_DATE') and DEFINE('_NULL_DATE', '0000-00-00');

!defined('_CURRENT_SERVER_TIME_FORMAT') and DEFINE( '_CURRENT_SERVER_TIME_FORMAT', '%Y-%m-%d %H:%M:%S' );

/*********************************************************************
 *	GLOBAL FUNCTIONS
 *********************************************************************/

class S2Paths {
	
	var $__paths = array();
	
	function &getInstance() {
		static $instance = array();

		if (!isset($instance[0]) || !$instance[0]) {
			$instance[0] = new S2Paths();
		}
		return $instance[0];
	}
	
	function get($app, $key,$default = null) {

		$_this = & S2Paths::getInstance();

		if(isset($_this->__paths[$app][$key])) {		
			return $_this->__paths[$app][$key];
		}
		
		return $default;
	}
	
	function set($app,$key,$value) {
		$_this = & S2Paths::getInstance();
		$_this->__paths[$app][$key] = $value; 		
	}
	
}

/**
 * Returns a translated string if one is found, or the submitted message if not found.
 *
 * @param string $singular Text to translate
 * @param boolean $return Set to true to return translated string, or false to echo
 * @return mixed translated string if $return is false string will be echoed
 */

function __t($singular, $return = false, $js = false) {
	if (!$singular) {
		return;
	}

	if (!class_exists('I18n')) {
        App::import('Core', 'i18n');
	}

    $text = I18n::translate($singular);
    
    if($js){
        $text = str_replace("'", "\'", $text);
        $text = str_replace('"', "'+String.fromCharCode(34)+'", $text);
    }
    	
    if ($return === false) {
		echo $text;
	} else {
		return $text;
	}
}

/**
 * Returns correct plural form of message identified by $singular and $plural for count $count.
 * Some languages have more than one form for plural messages dependent on the count.
 *
 * @param string $singular Singular text to translate
 * @param string $plural Plural text
 * @param integer $count Count
 * @param boolean $return true to return, false to echo
 * @return mixed plural form of translated string if $return is false string will be echoed
 */
    function __n($singular, $plural, $count, $return = false) {
        if (!$singular) {
            return;
        }
        if (!class_exists('I18n')) {
            App::import('Core', 'i18n');
        }

        if ($return === false) {
            echo I18n::translate($singular, $plural, null, 5, $count);
        } else {
            return I18n::translate($singular, $plural, null, 5, $count);
        }
    }
    
/**
 * For locale strings - date, number format
 * Returns a translated string if one is found, or the submitted message if not found.
 */
function __l($singular, $return = false, $js = false) {

	$domain = 'locale';
	if (!$singular) {
		return;
	}
	if (!class_exists('I18n')) {
		require(S2_LIBS . 'I18n.php');
	}
    
   $text =I18n::translate($singular, null, $domain);
    
    if($js){
        $text = str_replace("'", "\'", $text);
        $text = str_replace('"', "'+String.fromCharCode(34)+'", $text);
    }
        
    if ($return === false) {
        echo $text;
    } else {
        return $text;
    }
}

/**
 * For use in administration
 * Returns a translated string if one is found, or the submitted message if not found.
 */
function __a($singular, $return = false, $js = false) {

	$domain = 'admin';
	if (!$singular) {
		return;
	}
	if (!class_exists('I18n')) {
		require(S2_LIBS . 'I18n.php');
	}

   $text = I18n::translate($singular, null, $domain);
    
    if($js){
        $text = str_replace("'", "\'", $text);
        $text = str_replace('"', "'+String.fromCharCode(34)+'", $text);
    }
        
    if ($return === false) {
        echo $text;
    } else {
        return $text;
    }
}

/**
 * Reads/writes temporary data to cache files or session.
 *
 * @param  string $path	File path within /tmp to save the file.
 * @param  mixed  $data	The data to save to the temporary file.
 * @param  mixed  $expires A valid strtotime string when the data expires.
 * @param  string $target  The target of the cached data; either 'cache' or 'public'.
 * @return mixed  The contents of the temporary file.
 * @deprecated Please use Cache::write() instead
 */
	function cache($path, $data = null, $expires = '+1 day', $target = 'cache') {

		if (Configure::read('Cache.disable')) {
			return null;
		}

		if (!Configure::read('Cache.view')) {
			return null;
		}

		$now = time();

		if (!is_numeric($expires)) {
			$expires = strtotime($expires, $now);
		}

		switch(low($target)) {
			case 'cache':
				$filename = CACHE . $path;
			break;
			case 'public':
				$filename = WWW_ROOT . $path;
			break;
			case 'tmp':
				$filename = TMP . $path;
			break;
		}
		$timediff = $expires - $now;
		$filetime = false;

		if (file_exists($filename)) {
			$filetime = @filemtime($filename);
		}

		if ($data === null) {
			if (file_exists($filename) && $filetime !== false) {				
				if ($filetime + $timediff < $now) {
					@unlink($filename);
				} else {
					$data = @file_get_contents($filename);
				}
			}

		} elseif (is_writable(dirname($filename))) {

			@file_put_contents($filename, $data);
		}
		return $data;
	}
	
/**
 * Used to delete files in the cache directories, or clear contents of cache directories
 *
 * @param mixed $params As String name to be searched for deletion, if name is a directory all files in directory will be deleted.
 *              If array, names to be searched for deletion.
 *              If clearCache() without params, all files in app/tmp/cache/views will be deleted
 *
 * @param string $type Directory in tmp/cache defaults to view directory
 * @param string $ext The file extension you are deleting
 * @return true if files found and deleted false otherwise
 */
	function clearCache($params = null, $type = 'views', $ext = '.php') 
    {
		if (is_string($params) || $params === null) 
        {
			$params = preg_replace('/\/\//', '/', $params);
			$cache = S2_CACHE . $type . DS . $params;

			if (is_file($cache . $ext) && substr(basename($cache . $ext),0,5) != 'index') 
            {
				@unlink($cache . $ext);
				return true;
			} 
            elseif (is_dir($cache)) 
            {
				$files = glob("$cache*");
                 
				if ($files === false) {
					return false;
				}

				foreach ($files as $file) 
                {
					if (is_file($file) && substr(basename($file),0,5) != 'index') {
						@unlink($file);
					}
				}
                
				return true;
			
            } else {
				$cache = array(
					S2_CACHE . $type . DS . '*' . $params . $ext,
					S2_CACHE . $type . DS . '*' . $params . '_*' . $ext
				);

				$files = array();
				while ($search = array_shift($cache)) 
                {
					$results = glob($search);
					if ($results !== false) {
						$files = array_merge($files, $results);
					}
				}
                
				if (empty($files)) {
					return false;
				}
				
                foreach ($files as $file) {
                    if (is_file($file) && substr(basename($file),0,5) != 'index') {
						@unlink($file);
					}
				}
                
				return true;
			}
		} 
        elseif (is_array($params)) 
        {
			foreach ($params as $key => $file) {
				clearCache($file, $type, $ext);
			}
			return true;
		}
		return false;
	}
        
/**
 * Returns microtime for execution time checking
 *
 * @return float Microtime
 */
function S2getMicrotime() {
	list($usec, $sec) = explode(" ", microtime());
	return ((float)$usec + (float)$sec);
}
	
function S2cacheRead($prefix,$key=array()) {

    if((!defined('MVC_FRAMEWORK_ADMIN') || MVC_FRAMEWORK_ADMIN == 0) 
        && Configure::read('Cache.enable') && Configure::read('Cache.query')) 
    {   
        $cacheKey = $prefix.'_'.md5(cmsFramework::getConfig('secret').serialize($key));
        $cache = S2Cache::read($cacheKey);
        if(false !== $cache) {
            return $cache;
        }
    } 
    
    return false; 
}
 
function S2cacheWrite($prefix,$key,$data)   
{
    # Send to cache
    if((!defined('MVC_FRAMEWORK_ADMIN') || MVC_FRAMEWORK_ADMIN == 0) 
        && Configure::read('Cache.enable') && Configure::read('Cache.query')) 
    {
        $cacheKey = $prefix.'_'.md5(cmsFramework::getConfig('secret').serialize($key));
        S2Cache::write($cacheKey,$data);
    }  
}       


/**
 * Gets an environment variable from available sources.
 * Used as a backup if $_SERVER/$_ENV are disabled.
 *
 * @param  string $key Environment variable name.
 * @return string Environment variable setting.
 */
function env($key) {

	if ($key == 'HTTPS') {
		if (isset($_SERVER) && !empty($_SERVER)) {
			return (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on');
		} else {
			return (strpos(env('SCRIPT_URI'), 'https://') === 0);
		}
	}

	if (isset($_SERVER[$key])) {
		return $_SERVER[$key];
	} elseif (isset($_ENV[$key])) {
		return $_ENV[$key];
	} elseif (getenv($key) !== false) {
		return getenv($key);
	}

	if ($key == 'SCRIPT_FILENAME' && defined('SERVER_IIS') && SERVER_IIS === true){
		return str_replace('\\\\', '\\', env('PATH_TRANSLATED') );
	}

	if ($key == 'DOCUMENT_ROOT') {
		$offset = 0;
		if (!strpos(env('SCRIPT_NAME'), '.php')) {
			$offset = 4;
		}
		return substr(env('SCRIPT_FILENAME'), 0, strlen(env('SCRIPT_FILENAME')) - (strlen(env('SCRIPT_NAME')) + $offset));
	}
	if ($key == 'PHP_SELF') {
		return r(env('DOCUMENT_ROOT'), '', env('SCRIPT_FILENAME'));
	}
	return null;
}

function ex($string) {
	echo $string;
}

function prx($array) {
	echo "<pre>";
	print_r($array);
	echo "</pre>";
}

function arrayFilter($keys,$array) 
{
	$result = array();

	if(!empty($keys))
    {
        foreach($keys AS $key) {
            if(isset($array[$key])) {
                $result[$key] = $array[$key];
            } else {
                $result[$key] = $key;
            }
        }
    }
	return $result;
}

/**
 * Replacement function for array_merge_recursive.
 * If a key already exists it is replaced with the $ins key instead of creating an array
 */
function array_insert($arr,$ins) {
	// Loop through all Elements in $ins:
	if (is_array($arr) && is_array($ins)) 
	{
		foreach ($ins as $k => $v) 
		{
			// Key exists in $arr and both Elemente are Arrays: Merge recursively.
			if (isset($arr[$k]) && is_array($v) && is_array($arr[$k])) {
			
				$arr[$k] = array_insert($arr[$k],$v);
			
			} else {
			
				$arr[$k] = $v;
			}
		}
	}

	// Return merged Arrays:
	return $arr;
}

function s2ampReplace( $text )
{
	$text = str_replace( '&&', '*--*', $text );
	$text = str_replace( '&#', '*-*', $text );
	$text = str_replace( '&amp;', '&', $text );
	$text = preg_replace( '|&(?![\w]+;)|', '&amp;', $text );
	$text = str_replace( '*-*', '&#', $text );
	$text = str_replace( '*--*', '&&', $text );

	return $text;
}	

function br2nl($str) { 
	$str = preg_replace("/(\r\n|\n|\r)/", "", $str); return preg_replace("=<br */?>=i", "\n", $str); 
}
	
function spChars(&$value, $key) {
	if ($key[0] != '_') {
	       $value = stripslashes(htmlspecialchars($value)); 
	}
}

/**
 * Recursively strips slashes from all values in an array
 *
 * @param array $value Array of values to strip slashes
 * @return mixed What is returned from calling stripslashes
 */
if(!function_exists('s2_stripslashes_deep')) {
	function s2_stripslashes_deep($value) {
		if (is_array($value)) {
			$return = array_map('s2_stripslashes_deep', $value);
			return $return;
		} elseif(is_string($value)) {
			$return = stripslashes($value);
			return $return ;
		} else {
			return $value;
		}
	}
}

function s2GetIpAddress()
{
    if (!empty($_SERVER['HTTP_CLIENT_IP']))
    {
      $ip = $_SERVER['HTTP_CLIENT_IP'];
    }
    elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR']))
    {
      $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
    }
    else
    {
      $ip = $_SERVER['REMOTE_ADDR'];
    }
    // In some weird cases the ip address returned is repeated twice separated by comma
    if(strstr($ip,','))
    {
        $ip = array_shift(explode(',',$ip));
    }
    return $ip;    
}

function cleanIntegerCommaList($string) {
	$list = explode(',',$string);
	foreach($list AS $key=>$val) {
		if(!is_numeric($val) || $val == '') {
			unset($list[$key]);
		}
	}
	return implode(',',$list);
}

/**
 * Converts string to array and removes empty elements
 */
function stringToArray($string, $separator = "\n") 
{
    if(getCmsVersion()==CMS_JOOMLA16 && !strstr($string,$separator)) {
        $result = json_decode($string,true); 
        if(is_array($result)) return $result;
    }
    
    $out = array();

    $array = explode($separator,$string);
    
    foreach($array as $key => $value) {
        if($value != '') {
            $pos = strpos( $value, '=' );
            $property = trim( substr( $value, 0, $pos ));
            $pvalue = trim( substr( $value, $pos + 1 ) );
            $out[$property] = $pvalue;
        }
    }

    return $out;
}

/**
 * Converts string to array and removes empty elements
 *
 * REMOVE THIS FUNCTION AND USE THE ONE ABOVE
 */
function cleanString2Array($string, $separator = "\n") 
{
	$array = explode($separator,$string);
	foreach($array as $key => $value) {
	  if($value == "") {
	    unset($array[$key]);
	  }
	}

	return $array;
}
	
/**
 * Returns the request uri for ajax requests for each application
 *
 * @param string $app
 * @return ajax request uri
 */
function getAjaxUri($app='jreviews') 
{
	$lang = cmsFramework::getUrlLanguageCode();
	$language = Sanitize::getString($_REQUEST,'language');
	$core_sef = cmsFramework::getConfig('sef') && !function_exists('sefEncode') && !class_exists('shRouter');
	$ie = isset($_SERVER['HTTP_USER_AGENT']) && (strpos($_SERVER['HTTP_USER_AGENT'], 'MSIE') !== false);
	$lang_segment = $ie && $core_sef && $language != '' && $lang != '';
	
    if(defined('MVC_FRAMEWORK_ADMIN'))
    {
         $ajaxUri = WWW_ROOT . 'administrator' . _DS . 'index.php?option='.S2Paths::get($app, 'S2_CMSCOMP').'&format=raw&tmpl=component';                    
    } 
    else 
    {
    	$ajaxUri = WWW_ROOT . ($lang_segment ? $lang . _DS : '') . 'index.php?option='.S2Paths::get($app, 'S2_CMSCOMP').'&format=raw&tmpl=component' . (/* for Joomfish */ $lang != '' ? '&lang='.$lang : '');        
    }

        
    if(defined('MVC_FRAMEWORK_ADMIN')) return str_replace('&amp;','&',$ajaxUri);
    
    return $ajaxUri;
}

function displayAjaxUri($app='jreviews') {
    echo getAjaxUri($app);
}

/**
 * Searches include path for files
 *
 * @param array $file File to look for
 * @param array $paths Paths to look in
 * @param bool $key If set to true it will return the path array key instead of the path
 * @return Full path to file if exists, otherwise false
 */
function fileExistsInPath($file, $paths) {
        
	if(!isset($file['ext'])){
		$file['ext']='';
	}
	if(!isset($file['suffix'])){
		$file['suffix'] = '';
	}
	
	foreach ($paths as $value=>$path) {
		$fullPaths = array();
		$file['ext'] = $file['ext'] != '' ? '.'.ltrim($file['ext'],'.') : '';
		if($file['suffix']!='') {
			$fullPaths[] = rtrim($path,DS) . DS . $file['name'].$file['suffix'].$file['ext'];
		}
		$fullPaths[] = rtrim($path,DS) . DS . $file['name'].$file['ext'];

		foreach($fullPaths AS $fullPath){
			if (file_exists($fullPath)) {
				return $fullPath;
			}
		}
	}
	
	return false;
}

/**
 * Convert path to url
 *
 * @param string $path
 * @return string
 */
function pathToUrl($path, $relative = false) 
{
    $basePath = PATH_ROOT;
    $baseUrl = WWW_ROOT;
    // To eliminate bug where the assets urls have path info from current browser url
    // Could be a conflict with other extensions that use WWW_ROOT as well
    if($pos = strpos($baseUrl,'index.php'))
    {                     
        $baseUrl = substr($baseUrl,0,$pos);
    }    

    if(strstr($path,$basePath))
    {
        $removeBase = substr($path,strlen($basePath));
    } else {
        $removeBase = $path;
    }
    
    // If relative url, get the installation folder
    if($relative) {
        $url_parts = @parse_url($baseUrl);
        if(isset($url_parts) && $url_parts['path'] != _DS && $url_parts['path'] != '') {
            $relativeBaseUrl = $url_parts['path'];
        } 
        else {
            $relativeBaseUrl = _DS;
        }
    }
    
    return ($relative ? $relativeBaseUrl : $baseUrl) . ltrim(str_replace(DS,_DS,$removeBase), _DS);    
}   
	   
function vendor($name) {
	require_once( S2_VENDORS . $name);
}

if(!function_exists('urlencodeParam')) {
	function urlencodeParam($url_param,$urlencode=true)	 
	{
		if(is_string($url_param)) {

			$param = explode(_PARAM_CHAR,$url_param);
			
			$param[0] = urlencode(urldecode(stripslashes($param[0])));
			
			if(isset($param[1])) {
				
				$param[1] = stripslashes($param[1]);

				if($urlencode) {
					$param[1] = urlencode(urldecode(str_replace('//','',$param[1])));
				} else {
					$param[1] = str_replace('//','',$param[1]);				
				}
			}
			
			return implode(_PARAM_CHAR,$param);
		
		} else {
			return $url_param;
		}
	}
}

function arrayToParams($array) {
	$params = array();
	if(is_array($array)) {
		foreach($array AS $key=>$value) {
			if(trim($value)!='')
			$params[] = $key.':'.str_replace(',','_',$value);
		}
		return implode('/',$params);
	} else {
		return '';
	}
	
}

/**
 *
 * @param type $message
 * @param type $file
 * @param type $duress 
 */
function appLogMessage($message, $file, $duress = false) {

	if(Configure::read('System.debug')|| $duress)
	{
		if(is_array($message)) {
			$text = implode("\r\n",$message);
		} else {
			$text = $message;
		}
		
		$text .= "\r\n";
		
		$text = date("F j, Y, g:i a") . '----------------------------------' . "\r\n" . $text;
		
		$filename = S2_LOGS . $file . '.txt';
		$fd = fopen($filename,"a");
		fwrite($fd, $text);
		fclose ($fd);
	}

}

function s2_num_format($number)
{
    return number_format($number,2,__l('DECIMAL_SEPARATOR',true),__l('THOUSANDS_SEPARATOR',true));    
}

/**
 * Case insensitive deep in_array replacement
 */
function deep_in_array($value, $array, $case_insensitive = false)
{
    foreach($array as $item)
    {
        if(is_array($item))
            $ret = deep_in_array($value, $item, $case_insensitive);
        else
            $ret = ($case_insensitive) ? strtolower($item)==strtolower($value) : $item==$value;
        if($ret)
            return $ret;
    }
    return false;
}

/**
 * Convenience method for strtolower().
 *
 * @param string $str String to lowercase
 * @return string Lowercased string
 */
function low($str) {
	return mb_strtolower($str,'utf-8');
}

class s2Messages 
{
    function invalidToken()
    {
        return __t("There was a problem submitting the form (Invalid Token).",true,true);
    }

    function accessDenied()
    {
        return __t("You don't have enough access to perform this action.",true,true);
    }
    
    function submitErrorDb()
    {
        return __t("There as a problem submitting the form (Database error).",true,true);        
    }
    
    function submitErrorGeneric()
    {
        return __t("There was a problem processing the request.",true,true);        
    }

}
