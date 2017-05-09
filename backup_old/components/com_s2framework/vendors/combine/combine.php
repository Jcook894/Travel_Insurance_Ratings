<?php
/************************************************************************
 * Modified by Alejandro Schmeichler for jReviews 
 *
 * CSS and Javascript Combinator 0.5
 * Copyright 2006 by Niels Leenheer
 *
 * Permission is hereby granted, free of charge, to any person obtaining
 * a copy of this software and associated documentation files (the
 * "Software"), to deal in the Software without restriction, including
 * without limitation the rights to use, copy, modify, merge, publish,
 * distribute, sublicense, and/or sell copies of the Software, and to
 * permit persons to whom the Software is furnished to do so, subject to
 * the following conditions:
 * 
 * The above copyright notice and this permission notice shall be
 * included in all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND,
 * EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF
 * MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND
 * NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE
 * LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION
 * OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION
 * WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 */

if (!defined('DS')) define('DS', DIRECTORY_SEPARATOR);
		
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

$cache 	  = true;
$segments = explode('/',$_SERVER['REQUEST_URI']);
$type = $_GET['type'];
$app = isset($_GET['app']) ? $_GET['app'] : '';
$theme = isset($_GET['theme']) ? $_GET['theme'] : '';
$suffix = isset($_GET['suffix']) ? $_GET['suffix'] : '';
$subdir = $segments[1]!='components' ? $segments[1] . '/' : '';
$PATH_ROOT = realpath(dirname(dirname(dirname(dirname(dirname(__FILE__)))))).DS;

$cssPaths = array(
	$PATH_ROOT. 'templates'.DS.$app.'_overrides'.DS.'views'.DS.'themes'.DS.$theme.DS.'theme_css',
	$PATH_ROOT.'components' .DS. 'com_jreviews' .DS. 'jreviews' .DS. 'views'.DS.'themes'.DS.$theme.DS.'theme_css',	
	$PATH_ROOT.'components' .DS. 'com_jreviews' .DS. 'jreviews' .DS. 'views'.DS.'themes'.DS.'default'.DS.'theme_css'	
);

$jsPaths = array(
	$PATH_ROOT. 'templates'.DS.$app.'_overrides'.DS.'views'.DS.'js',
	$PATH_ROOT.'components' .DS. 'com_jreviews' .DS. 'jreviews' .DS. 'views'.DS.'js'	
);


$cachePath = $PATH_ROOT . DS . 'components' . DS . 'com_s2framework' . DS . 'tmp' . DS . 'cache' . DS . 'assets';

// Determine the directory and type we should use
if(!in_array($type,array('css','javascript'))) {
	header ("HTTP/1.0 503 Not Implemented");
}

$elements = explode(',',str_replace('!',DS,$_GET['files']));

// Determine last modification date of the files
$lastmodified = 0;
$validPaths = array();
while (list(,$element) = each($elements)) {

	if (($type == 'javascript' && substr($element, -3) != '.js') || 
		($type == 'css' && substr($element, -4) != '.css')) {
		header ("HTTP/1.0 403 Forbidden");
		exit;	
	}
	
	switch ($type) {
		case 'css':
			$ext = 'css';
			$name = substr($element, 0, -4);
			$path = fileExistsInPath(array('name'=>$name,'ext'=>$ext,'suffix'=>$suffix),$cssPaths);						
			break;
		case 'javascript':
			$ext = 'js';
			$name = substr($element, 0, -3);;
			$path = fileExistsInPath(array('name'=>$name,'ext'=>$ext,'suffix'=>$suffix),$jsPaths);			
			break;
	};	

	if (substr($path, 0, strlen($PATH_ROOT)) != $PATH_ROOT || !$path) {
		header ("HTTP/1.0 404 Not Found");
		exit;
	}

	$lastmodified = max($lastmodified, filemtime($path));
	$validPaths[] = $path;
}

// Send Etag hash
$hash = $lastmodified . '-' . md5($theme.$suffix.$_GET['files']);
header ("Etag: \"" . $hash . "\"");

if (isset($_SERVER['HTTP_IF_NONE_MATCH']) && 
	stripslashes($_SERVER['HTTP_IF_NONE_MATCH']) == '"' . $hash . '"') 
{
	// Return visit and no modifications, so do not send anything
	header ("HTTP/1.0 304 Not Modified");
	header ('Content-Length: 0');
} 
else 
{
	// First time visit or files were modified
	if ($cache) 
	{
		// Determine supported compression method
		$gzip = false;
		$deflate = false;
		if(isset($_SERVER['HTTP_ACCEPT_ENCODING'])){
			$gzip = strstr($_SERVER['HTTP_ACCEPT_ENCODING'], 'gzip');
			$deflate = strstr($_SERVER['HTTP_ACCEPT_ENCODING'], 'deflate');
		}
		
		// Determine used compression method
		$encoding = $gzip ? 'gzip' : ($deflate ? 'deflate' : 'none');

		// Check for buggy versions of Internet Explorer
		if (!strstr($_SERVER['HTTP_USER_AGENT'], 'Opera') && 
			preg_match('/^Mozilla\/4\.0 \(compatible; MSIE ([0-9]\.[0-9])/i', $_SERVER['HTTP_USER_AGENT'], $matches)) {
			$version = floatval($matches[1]);
			
			if ($version < 6)
				$encoding = 'none';
				
			if ($version == 6 && !strstr($_SERVER['HTTP_USER_AGENT'], 'EV1')) 
				$encoding = 'none';
		}
		
		// Try the cache first to see if the combined files were already generated
		$cacheFile = 'cache-' . $hash . '.' . $type . ($encoding != 'none' ? '.' . $encoding : '');

		if (file_exists($cachePath . DS . $cacheFile)) {

			if ($fp = fopen($cachePath . DS . $cacheFile, 'rb')) {

				if ($encoding != 'none') {
					header ("Content-Encoding: " . $encoding);
				}
			
				header ("Content-Type: text/" . $type);
				header ("Content-Length: " . filesize($cachePath . DS . $cacheFile));
	
				fpassthru($fp);
				fclose($fp);
				exit;
			}
		}
	}

	// Get contents of the files
	$contents = '';
	foreach($validPaths AS $path) {
		if($type == 'css'){
			$imagesPath = '/'.$subdir.str_replace('\\','/',substr($path,strlen($PATH_ROOT)));
			$imagesPath = preg_replace('/[.0-9a-z_-]*\.css/','images',$imagesPath);
			// Update image paths in css
			$contents .= "\n\n" . str_replace('url(images','url('.$imagesPath,file_get_contents($path));
		} else {
			$contents .= "\n\n" . file_get_contents($path);			
		}
	}

	// Send Content-Type
	header ("Content-Type: text/" . $type);
	
	if (isset($encoding) && $encoding != 'none') 
	{
		// Send compressed contents
		$contents = gzencode($contents, 9, $gzip ? FORCE_GZIP : FORCE_DEFLATE);
		header ("Content-Encoding: " . $encoding);
		header ('Content-Length: ' . strlen($contents));
		echo $contents;
	} 
	else 
	{
		// Send regular contents
		header ('Content-Length: ' . strlen($contents));
		echo $contents;
	}

	// Store cache
	if ($cache) {
		if ($fp = fopen($cachePath . DS . $cacheFile, 'wb')) {
			fwrite($fp, $contents);
			fclose($fp);
		}
	}
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