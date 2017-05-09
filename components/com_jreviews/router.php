<?php
/**
 * JReviews - Reviews Extension
 * Copyright (C) 2010-2014 ClickFWD LLC
 * This is not free software, do not distribute it.
 * For licencing information visit https://www.jreviews.com
 * or contact sales@jreviews.com
**/

defined('_JEXEC') or die( 'Direct Access to this location is not allowed.' );

function JreviewsBuildRoute(&$query)
{
    $segments = array();

    unset($query['view']);

    if(!function_exists('urlencodeParam')) return array();

    if(isset($query['url']))
    {
        $query_string = explode('/',$query['url']);

        // Properly urlencodes the jReviews parameters contained within the url parameter
        foreach($query_string AS $key=>$param)
        {
            $query_string[$key] = urlencodeParam($param);
        }

        $query['url'] = implode('/',$query_string);

        $segments[0] = $query['url'];

        unset($query['url']);
    }

	if(count($segments) == 1 && ($segments[0] == 'menu/' || $segments[0] == 'menu'))
    {
		unset($segments[0]);
	}

    return $segments;
}

function JreviewsParseRoute($segments)
{
    $vars = array();
    # Load own uri to overcome Joomla encoding issues with Greek params
    $uri = JreviewsRouterFn::_getUri();

    // Fix for Joomfish. Remove the language segment from the url
    if(class_exists('JoomFishManager'))
    {
        $lang = JFactory::getLanguage();

        $language = $lang->getTag();

        $jfm = JoomFishManager::getInstance();

        $lang_shortcode = $jfm->getLanguageCode($language);

        if(strstr($uri,'/'.$lang_shortcode.'/'))
        {
            $uri = str_replace('/'.$lang_shortcode.'/','/',$uri);
        }
    }

    $new_segments = JreviewsRouterFn::_parseSefRoute($uri);

    if($new_segments != null && end($new_segments) == 'index.php')
    {
        $new_segments = $segments;
    }

    // Remove Joomla language segment from url
    if(JRequest::getVar('language')!='' && strlen($new_segments[0]) == 2)
    {
        $new_segments[0] = 'index.php';
    }

    # Fix for sef without mod rewrite. Without it the sort urls don't work.

    // Remove the Itemid related segments when mod rewrite is disabled and Itemid exists
    if($new_segments[0] == 'index.php' && (!isset($new_segments[1]) || $new_segments[1] != 'component'))
    {
        foreach($new_segments AS $key=>$segment) {

			if(
				!in_array(str_replace(' ','+',$segment),$segments) /* For J1.7+ */
				&& !in_array($segment,$segments) /* For J1.5 */
				&& !in_array(JreviewsStrReplaceOnce('-',':',urlencode($segment)),$segments) /* Joomla converts dash to colon */
                && !in_array(JreviewsStrReplaceOnce('-',':',$segment),$segments) /* Joomla converts dash to colon */
                ) {
                unset($new_segments[$key]);
            }
        }
    }

    if(count($new_segments) >= 3 && isset($new_segments[0]) && $new_segments[0] == 'index.php' && isset($new_segments[1]) && $new_segments[1] == 'component' && isset($new_segments[2]) && $new_segments[2] == 'jreviews')
    {
        array_shift($new_segments); array_shift($new_segments); array_shift($new_segments);
    }

	if(is_array($new_segments))
    {
		$vars['url'] = implode('/',$new_segments);
	}

	return $vars;
}

function JreviewsStrReplaceOnce($str_pattern, $str_replacement, $string)
{
	$ocurrence = strpos($string, $str_pattern);

	if ($ocurrence !== false){
		return substr_replace($string, $str_replacement, strpos($string, $str_pattern), strlen($str_pattern));
	}

	return $string;
}


class JreviewsRouterFn {

    /**
    * Original Joomla public static functions for php4 to process the URI. For php5 the parse_url public static function is used
    * and it messes up the encoding for some greek characters
    */
    public static function _getUri()
    {
        // Determine if the request was over SSL (HTTPS).
        if (isset($_SERVER['HTTPS']) && !empty($_SERVER['HTTPS']) && (strtolower($_SERVER['HTTPS']) != 'off')) {
            $https = 's://';
        }
        else {
            $https = '://';
        }

        /*
         * Since we are assigning the URI from the server variables, we first need
         * to determine if we are running on apache or IIS.  If PHP_SELF and REQUEST_URI
         * are present, we will assume we are running on apache.
         */
        if (!empty($_SERVER['PHP_SELF']) && !empty ($_SERVER['REQUEST_URI']))
        {
            // To build the entire URI we need to prepend the protocol, and the http host
            // to the URI string.
            $theURI = 'http' . $https . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];

            // Since we do not have REQUEST_URI to work with, we will assume we are
            // running on IIS and will therefore need to work some magic with the SCRIPT_NAME and
            // QUERY_STRING environment variables.
            //
        }
        else
        {
            // IIS uses the SCRIPT_NAME variable instead of a REQUEST_URI variable... thanks, MS
            $theURI = 'http' . $https . $_SERVER['HTTP_HOST'] . $_SERVER['SCRIPT_NAME'];

            // If the query string exists append it to the URI string
            if (isset($_SERVER['QUERY_STRING']) && !empty($_SERVER['QUERY_STRING'])) {
                $theURI .= '?' . $_SERVER['QUERY_STRING'];
            }
        }

        // Now we need to clean what we got since we can't trust the server var
        $theURI = urldecode($theURI);
        $theURI = str_replace('"', '&quot;',$theURI);
        $theURI = str_replace('<', '&lt;',$theURI);
        $theURI = str_replace('>', '&gt;',$theURI);
        $theURI = preg_replace('/eval\((.*)\)/', '', $theURI);
        $theURI = preg_replace('/[\\\"\\\'][\\s]*javascript:(.*)[\\\"\\\']/', '""', $theURI);
        return $theURI;
    }

    public static function _parseSefRoute(&$uri)
    {
        $vars    = array();

        $JMenu = JApplication::getInstance('site')->getMenu();

        $parts = JreviewsRouterFn::_parseUri($uri);

        $route  = $parts['path'];

        /*
         * Parse the application route
         */
        if (substr($route, 0, 9) == 'component')
        {
            $segments    = explode('/', $route);
            $route        = str_replace('component/'.$segments[1], '', $route);

         }
        else
        {
            //Need to reverse the array (highest sublevels first)
            $menu = $JMenu->getMenu();

            $items = array_reverse($menu);

            $found = false;

            foreach ($items as $item)
            {
                $length = strlen($item->route); //get the length of the route
                if ($length > 0 && strpos($route.'/', $item->route.'/') === 0 && $item->type != 'menulink') {
                    $route = substr($route, $length);
                    if ($route) {
                        $route = substr($route, 1);
                    }
                    break;
                }
            }
        }

        /*
         * Parse the component route
         */
        if (!empty($route)) {
            $segments = explode('/', str_replace('.html','',$route));
            if (empty($segments[0])) {
                array_shift($segments);
            }
        }

        return $segments;
    }

    public static function _parseUri($uri)
    {
        $parts = array();

        $regex = "<^(([^:/?#]+):)?(//([^/?#]*))?([^?#]*)(\\?([^#]*))?(#(.*))?>";

        $matches = array();

        preg_match($regex, $uri, $matches, PREG_OFFSET_CAPTURE);

        $authority = @$matches[4][0];

        if (strpos($authority, '@') !== false) {
            $authority = explode('@', $authority);
            @list($parts['user'], $parts['pass']) = explode(':', $authority[0]);
            $authority = $authority[1];
        }

        if (strpos($authority, ':') !== false) {
            $authority = explode(':', $authority);
            $parts['host'] = $authority[0];
            $parts['port'] = $authority[1];
        } else {
            $parts['host'] = $authority;
        }

        $install_folder = str_replace('index.php','',$_SERVER['SCRIPT_NAME']);

        $parts['scheme'] = @$matches[2][0];

        $parts['path'] = $install_folder == '/' ? rtrim(@$matches[5][0],'/') : rtrim(str_replace($install_folder,'',@$matches[5][0]),'/');

        $parts['path'] = ltrim($parts['path'],'/');

        if(isset($matches[7])) $parts['query'] = @$matches[7][0];

        if(isset($matches[9])) $parts['fragment'] = @$matches[9][0];

        return $parts;
    }
}