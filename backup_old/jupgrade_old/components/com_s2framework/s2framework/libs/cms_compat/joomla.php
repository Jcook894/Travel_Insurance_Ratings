<?php
/**
 * S2Framework
 * Copyright (C) 2010-2012 ClickFWD LLC
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 * @license       http://www.opensource.org/licenses/mit-license.php The MIT License
**/


(defined('MVC_FRAMEWORK') || defined('JPATH_BASE')) or die( 'Direct Access to this location is not allowed.' );

if(!defined('CMS_JOOMLA17')) define('CMS_JOOMLA17','CMS_JOOMLA17');
if(!defined('CMS_JOOMLA16')) define('CMS_JOOMLA16','CMS_JOOMLA16');
if(!defined('CMS_JOOMLA15')) define('CMS_JOOMLA15','CMS_JOOMLA15');

$version = new JVersion();
switch($version->RELEASE)
{
	case 1.5:
		require_once (S2_ROOT . DS . 's2framework' . DS . 'libs' . DS . 'cms_compat' . DS . 'joomla15.php');
	break; 
	default:
		class cmsFramework extends cmsFrameworkJoomla {
			//
		}
	break;
}
			
class cmsFrameworkJoomla
{ 
    var $scripts;
    var $site_route_init;
    var $sef_plugins = array('sef','sef_advance','shsef','acesef'/*not supported*/);
        
    function getUser() 
    {                                 
        $user = &JFactory::getUser();
        $user->group_ids = !empty($user->groups) ? implode(',',array_keys($user->groups)) : ''; /* J16 make group ids easier to compare */           
        return $user;
    }
    
    function getACL() 
    {
        $acl = &JFactory::getACL();        
        return $acl;
    }

    function getDB() {
        $db = &JFactory::getDBO();        
        return $db;
    }
        
    function getConnection()
    {
        $db = & cmsFramework::getDB();
        return $db->getConnection();
    }
    
    function isAdmin() 
    {        
        global $mainframe;

        if(defined('MVC_FRAMEWORK_ADMIN') /*|| $mainframe->isAdmin()*/) {
            return true;
        } else {
            return false;
        }
    }
    
    function packageUnzip($file,$target)
    {
        jimport( 'joomla.filesystem.file' );
        jimport( 'joomla.filesystem.folder' );
        jimport( 'joomla.filesystem.archive' );
        jimport( 'joomla.filesystem.path' );        
        $extract1 = & JArchive::getAdapter('zip');
        $result = @$extract1->extract($file, $target);        
        if($result!=true)
        {      
            require_once (PATH_ROOT . DS . 'administrator' . DS . 'includes' . DS . 'pcl' . DS . 'pclzip.lib.php');
            require_once (PATH_ROOT . DS . 'administrator' . DS . 'includes' . DS . 'pcl' . DS . 'pclerror.lib.php');
            if ((substr ( PHP_OS, 0, 3 ) == 'WIN')) {
                if(!defined('OS_WINDOWS')) define('OS_WINDOWS',1);
            } else {
                if(!defined('OS_WINDOWS')) define('OS_WINDOWS',0);
            }
            $extract2 = new PclZip ( $file );
            $result = @$extract2->extract( PCLZIP_OPT_PATH, $target );            
        }
        unset($extract1,$extract2);
        return $result;
    }
    
    function getTemplate(){      
        return JFactory::getApplication()->getTemplate();
    }
    
    function addScript($text, $inline=false, $duress = false)
    {
        $scripts = ClassRegistry::getObject('scripts');

        if($text != '' && ($duress || !isset($scripts[md5($text)]))) 
        {
            if($inline) 
            {
                echo $text;
            
            } else 
            {
                $doc =& JFactory::getDocument();
                method_exists($doc,'addCustomTag') and $doc->addCustomTag($text);
            }
            
            $scripts[md5($text)] = true;
            ClassRegistry::setObject('scripts',$scripts);
        }
    }
    
    function getCharset() 
    {        
        return 'UTF-8';
    }
    
    function &getCache($group='')
    {    
        return JFactory::getCache($group);
    }
        
    function cleanCache($group=false)
    {
        $cache =& JFactory::getCache($group);
        $cache->clean($group);
    }    
    
    function getConfig($var, $default = null) 
    {        
        $cmsConfig = ClassRegistry::getClass('JConfig');

        if(isset($cmsConfig->{$var})){
          return $cmsConfig->{$var};
        } else {
          return $default;
        }                             
    }
    
    function getConfigExtension($extension, $var, $default = null) {
        $config = JComponentHelper::getParams( $extension );
        return $config->get($var,$default);        
    }       
    
    function setSessionVar($key,$var,$namespace)
    {
        $session =& JFactory::getSession();
        $session->set($key,$var,$namespace);
    }

    function getSessionVar($key,$namespace)
    {
        $session =& JFactory::getSession();
        return $session->get($key, array(), $namespace);
    }   
     
    function clearSessionVar($key,$namespace) {
        if(isset($_SESSION['__'.$namespace]) && $key != '') {
            unset($_SESSION['__'.$namespace][$key]);
        }
    }
    
    function clearSessionNamespace($namespace) {
        unset($_SESSION['__'.$namespace]);
    } 
         
    /**
    * Used to prevent form data tampering
    *  
    */
    function getCustomToken()
    {                                                        
        $string = '';
        if(func_num_args() > 0) {
            $args = func_get_args();
            $string = cmsFramework::getConfig('secret') . implode('',$args);
        }
        return md5($string);    
    }
    
    function formIntegrityToken($entry, $keys, $input = true)
    {
        $string = '';
        $tokens = array();
        !isset($entry['form']) and $entry['form'] = array();
        !isset($entry['data']) and $entry['data'] = array();
        unset($entry['data']['controller'],$entry['data']['action'],$entry['data']['module'],$entry['data']['__raw']);

        // Leave only desired $keys from $entry
        $params = array_intersect_key($entry,array_fill_keys($keys,1));
 
        // Orders the array by keys so the hash will match
        ksort($params);

        // Remove empty elements and cast all values to strings
        foreach($params AS $key=>$param) {
            if(is_array($param) && !empty($param)) {
                $param = is_array($param) ? array_filter($param) : false;
                if(!empty($param)) {
                    $tokens[] = array_map('strval', $param); 
                }
            } 
            elseif (!empty($param)){
                $tokens[] = strval($param);    
            }
        }

        sort($tokens);

        $string = serialize($tokens);
         
        if($string == '') return '';
        
        return $input ? 
            '<input type="hidden" name="'.cmsFramework::getCustomToken($string).'" value="1" />' 
            : 
            cmsFramework::getCustomToken($string);
    }   
         
    function getTokenInput()
    {
        return '<span class="jr_token jr_hidden">'.JHTML::_( 'form.token' ).'</span>';
    } 
    
    function getToken($new = false) 
    {
        return JUtility::getToken($new);            
    }
    
    function localDate($date = 'now', $offset = null, $format = 'M d Y H:i:s') 
    {        
        if(is_null($offset)) {
            $offset = cmsFramework::getConfig('offset')*3600;
        } else {
            $offset = 0;
        }
        
        if($date == 'now') 
        {
            $date = strtotime(gmdate($format, time()));
        } 
        else 
        {
            $date = strtotime($date);
        }        
        $date = $date + $offset;        
        $date = date($format, $date);        
        return $date;        
    }

/* J16 - deprecated */    
/*    function language() 
    {
        $lang = & JFactory::getLanguage();
        return $lang->getBackwardLang();
    }  */
    
    function isRTL()
    {
        $lang    = & JFactory::getLanguage();
        return $lang->isRTL();
    }
        
    function getIgnoredSearchWords()
    {
        $search_ignore = array();
        $lang = JFactory::getLanguage();
        if(method_exists($lang,'getIgnoredSearchWords')) 
        {
            return $lang->getIgnoredSearchWords();
        }
        
        return $search_ignore;
    }
    
    /**
    * This returns the locale from the Joomla language file
    * 
    */
    function getLocale($separator = '_')
    {
        $lang    = & JFactory::getLanguage();
        $locale = $lang->getTag();
        return str_replace('-',$separator,$locale);    
    }   
     
    /**
    * Used for I18n in s2framework
    * 
    */
    function locale() 
    {
        $lang    = & JFactory::getLanguage();
        $locale = $lang->getTag();     
        $locale = str_replace('_','-',$locale);
        $parts = explode('-',$locale);
        if(count($parts)>1 && strcasecmp($parts[0],$parts[1]) === 0){
            $locale = $parts[0];
        }
        return $locale;
    }
	
	/**
	 * Get url language code
	 */
	function getUrlLanguageCode()
	{
		if(class_exists('JLanguageHelper')) {
			$lang = JLanguageHelper::getLanguages('lang_code');
			return isset($lang[cmsFramework::locale()]) ? $lang[cmsFramework::locale()]->sef : '';
		}
	}	
    
    function listImages( $name, &$active, $javascript=NULL, $directory=NULL ) 
    {
        return JHTML::_('list.images', $name, $active, $javascript, $directory);
    }
    
    function listPositions( $name, $active=NULL, $javascript=NULL, $none=1, $center=1, $left=1, $right=1, $id=false ) 
    {
        return JHTML::_('list.positions', $name, $active, $javascript, $none, $center, $left, $right, $id);
    }
    
    /**
     * Check for Joomla/Mambo sef status
     *
     * @return unknown
     */
    function mosCmsSef() {
        return false;
    }        
    
    function meta($type,$text) 
    {
        global $mainframe;
        if($text == '') {
            return;
        }

        switch($type) {
            case 'title':
                $document =& JFactory::getDocument();
                $document->setTitle($text);           
                break;            
            case 'keywords':
            case 'description':
            default:    
                $document = & JFactory::getDocument();
                if($type == 'description') {
                    $document->description = htmlspecialchars(strip_tags($text),ENT_QUOTES,'utf-8') ;
                } else {
                    $document->setMetaData($type,htmlspecialchars(strip_tags($text),ENT_QUOTES,'utf-8'));
                }
            break;            
        }        
    }
            
    
    function noAccess() 
    {
        echo JText::_('JERROR_ALERTNOAUTHOR');
    }
    
    function formatDate($date) 
    {
        return JHTML::_('date', $date );
    }
    
    /**
     * Different function names used in different CMSs
     *
     * @return unknown
     */
    function reorderList() 
    {
        return 'reorder';
    }
    
    function redirect($url,$msg = '') 
    {
        $url = str_replace('&amp;','&',$url);        
        if (headers_sent()) {     
            echo "<script>document.location.href='$url';</script>\n";
        } else {
            header( 'HTTP/1.1 301 Moved Permanently' );
            header( 'Location: ' . $url );
        }
        exit;
    }
      
    /**
    * Convert relative urls to absolute for use in feeds, emails, etc.
    */
    function makeAbsUrl($url,$options=array())
    {
        $options = array_merge(array('sef'=>false,'ampreplace'=>false),$options);
        $options['sef'] and $url = cmsFramework::route($url);
        $options['ampreplace'] and $url = str_replace('&amp;','&',$url);
        if(!strstr($url,'http')) {
            $url_parts = parse_url(WWW_ROOT);
            # If the site is in a folder make sure it is included in the url just once
            if($url_parts['path'] != '') {
                if(strcmp($url_parts['path'],substr($url,0,strlen($url_parts['path']))) !== 0) {
                    $url = rtrim($url_parts['path'],'/') . $url;
                }
            }
            $url = $url_parts['scheme'] . '://' . $url_parts['host'] . $url;
       } 
       return $url;        
    }
                
     /**
    * This function is used as a replacement to JRoute::_() to generate sef urls in Joomla admin
    * 
    * @param mixed $urls
    * @param mixed $xhtml
    * @param mixed $ssl
    */
    function siteRoute($urls, $xhtml = true, $ssl = null)
    {   
        !is_array($urls) and $urls = array($urls);
        $sef_urls = array();
        $fields = array();
        
        foreach($urls AS $key=>$url)
        {
            $fields[] = "data[url][{$key}]=".urlencode($url);
        }

        $fields_string = implode('&',$fields);
                       
        $target_url = WWW_ROOT . 'index.php?option=com_jreviews&format=raw&url=common/_sefUrl';
                       
        $useragent="Ajax Request";
                       
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_USERAGENT, $useragent);
        curl_setopt($ch, CURLOPT_URL,$target_url);
        curl_setopt( $ch, CURLOPT_ENCODING, "" );
        curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
        curl_setopt( $ch, CURLOPT_AUTOREFERER, true );
        curl_setopt( $ch, CURLOPT_POST, count($fields));
        curl_setopt( $ch, CURLOPT_POSTFIELDS, $fields_string);
        $response = curl_exec($ch);
        curl_close($ch);

        $sef_urls = json_decode($response,true);  
       
        return is_array($sef_urls) && count($sef_urls) == 1 ? array_shift($sef_urls) : $sef_urls;
    }    
    
    function route($link, $xhtml = true, $ssl = null) 
    {   
        $menu_alias = '';
        $traditionalUrlParams = defined('URL_PARAM_JOOMLA_STYLE') || class_exists('shRouter') /* force Joomla style urls for sh404sef*/;
	
        if(false===strpos($link,'index.php') && false===strpos($link,'index2.php')) 
        {
                $link = 'index.php?'.$link;
        }

        // Check core sef
        $sef = cmsFramework::getConfig('sef');
        $sef_rewrite = cmsFramework::getConfig('sef_rewrite');

		$isJReviewsUrl = strpos($link,'option=com_jreviews');
		
        if(false===$isJReviewsUrl && !$sef) 
        {                    
            $url = cmsFramework::isAdmin() ? cmsFramework::siteRoute($link,$xhtml,$ssl) : JRoute::_($link,$xhtml,$ssl);
            if(false === strpos($url,'http')) {
                $parsedUrl = parse_url(WWW_ROOT);
                $port = isset($parsedUrl['port']) && $parsedUrl['port'] != '' ? ':' . $parsedUrl['port'] : '';
                $url = $parsedUrl['scheme'] . '://' . $parsedUrl['host'] . $port . $url;
            }
            return $url;
        }
        elseif(false===$isJReviewsUrl) 
        {
            $url = cmsFramework::isAdmin() ? cmsFramework::siteRoute($link,$xhtml,$ssl) : JRoute::_($link,$xhtml,$ssl);
            return $url;
        }
		
		$isJReviewsUrl and $link = cmsFramework::reorderUrlParams($link, $traditionalUrlParams);
		
        // Fixes component menu urls with pagination and ordering parameters when core sef is enabled.
        $link = str_replace('//','/',$link);

        if($sef) 
        {   
            $mod_rewrite = cmsFramework::getConfig('sef_rewrite');

            preg_match('/Itemid=([0-9]+)/',$link,$matches);
            $Itemid = Sanitize::getInt($matches,1);

			// Mod Rewrite is not enabled
            if(!$mod_rewrite)
            {                
                if(isset($matches[1]) && is_numeric($matches[1])) {
                    $link2 = 'index.php?option=com_jreviews&Itemid='.$matches[1];
                    $menu_alias = cmsFramework::isAdmin() ? cmsFramework::siteRoute($link2,$xhtml,$ssl) : JRoute::_($link2,$xhtml,$ssl);
                    strstr($menu_alias,'index.php') and $menu_alias = str_replace('.html','/',substr($menu_alias,strpos($menu_alias,'index.php'._DS)+10));
                    $menu_alias .= '/';
                    $menu_alias = '/'.ltrim(array_shift(explode('?',$menu_alias)),'/');
                }
            }

            // Core sef doesn't know how to deal with colons, so we convert them to something else and then replace them again.
            $link = $nonsef_link = str_replace(_PARAM_CHAR,'*@*',$link);
            $sefUrl = cmsFramework::isAdmin() ? cmsFramework::siteRoute($link,$xhtml,$ssl) : JRoute::_($link,$xhtml,$ssl);
            $sefUrl = str_replace('%2A%40%2A',_PARAM_CHAR,$sefUrl); 
            $sefUrl = str_replace('*@*',_PARAM_CHAR,$sefUrl); // For non sef links

            if(!class_exists('shRouter')) 
            {
                // Get rid of duplicate menu alias segments added by the JRoute function
                if(strstr($sefUrl,'order:') || strstr($sefUrl,'page:') || strstr($sefUrl,'limit:')) {
                    $sefUrl = str_replace(array('/format:html/','.html'),'/',$sefUrl);
                }
                
                // Get rid of duplicate menu alias segments added by the JRoute function
                if($menu_alias != '' && $menu_alias != '/' && !$mod_rewrite) {
                    $sefUrl = str_replace( $menu_alias, '--menuAlias--', $sefUrl,$count);
                    $sefUrl = str_replace(str_repeat('--menuAlias--',$count), $menu_alias, $sefUrl);
                }
            }           

            $link = $sefUrl;    

			// If it's not a JReviews menu url remove the suffix        
            $nonsef_link = str_replace('&amp;','&',$nonsef_link);   

			if(substr($nonsef_link,0,9) == 'index.php' && (!$Itemid || ($traditionalUrlParams == false && !preg_match('/^index.php\?option=com_jreviews&Itemid=([0-9]+)$/i',$nonsef_link)))) 
            {
                $link = str_replace('.html','',$sefUrl);    
            }
        } 

		if(false!==strpos($link,'http')) 
            {
                return $link;
            } 
        else 
            {
                $parsedUrl = parse_url(WWW_ROOT);
                $port = isset($parsedUrl['port']) && $parsedUrl['port'] != '' ? ':' . $parsedUrl['port'] : '';                
                $www_root = $parsedUrl['scheme'] . '://' . $parsedUrl['host'] . $port . ($sef ? _DS : $parsedUrl['path']);                
                return $www_root . ltrim($link, _DS);
            } 
    }
        
     function constructRoute($passedArgs,$excludeParams = null, $app = 'jreviews') 
    {
        $segments = $url_param = array();
        $defaultExcludeParms = array('format','view','language','lang');
        
        $excludeParams = !empty($excludeParams) ? array_merge($excludeParams,$defaultExcludeParms) : $defaultExcludeParms;
       
        if(defined('MVC_FRAMEWORK_ADMIN')) 
        {
            $base_url = 'index.php?option='.S2Paths::get($app, 'S2_CMSCOMP');                    
        } else 
        {
            $Itemid = Sanitize::getInt($passedArgs,'Itemid') > 0 ? Sanitize::getInt($passedArgs,'Itemid') : '';
            $base_url = 'index.php?option='.S2Paths::get($app, 'S2_CMSCOMP').'&amp;Itemid=' . $Itemid;
        }

        // Get segments without named params
        if(isset($passedArgs['url'])) {
            $parts = explode('/',$passedArgs['url']);
            foreach($parts AS $bit) {
                if(false===strpos($bit,_PARAM_CHAR) && $bit != 'index.php') {
                    $segments[] = $bit;
                }
            }
        }
        
        unset($passedArgs['option'], $passedArgs['Itemid'], $passedArgs['url']);
        if(is_array($excludeParams)) {
            foreach($excludeParams AS $exclude) {
                unset($passedArgs[$exclude]);        
            }
        }
        
        foreach($passedArgs AS $paramName=>$paramValue) {
            if(is_string($paramValue) && !empty($paramValue)){
                $paramValue == 'order' and $paramValue = array_shift(explode('.html',$paramValue));
                $url_param[] = $paramName . _PARAM_CHAR . urlencodeParam($paramValue);
            }
        }   

        empty($segments) and $segments[] = 'menu';

        $new_route = $base_url . (!empty($segments) ? '&amp;url=' . implode('/',$segments) . '/' . implode('/',$url_param) : '');
        
        return $new_route;    
    }
    
	function reorderUrlParams($url, $traditionalUrlParams = false) 
	{
		preg_match_all('/\/([a-z_]+):([^\/]*)/i',$url,$matches);
	
		if(empty($matches[0])) return $url;

		$newArray = array_combine($matches[1],$matches[2]);

		$array = $newArray;
		
		$orderArray = array('page','order','limit','dir','section','cat','scope','query','criteria');
		
		$ordered = array();
		
		foreach($orderArray as $key) {
			if(array_key_exists($key,$array)) {
				$ordered[$key] = $array[$key];
				unset($array[$key]);
			}
		}

		$newArray = $ordered + $array;

		$newParams = '';

		foreach($newArray AS $key=>$val) 
		{
			$newParams .= $traditionalUrlParams ? '&amp;' . $key . '=' . $val : $key . _PARAM_CHAR . $val . '/';
		} 

		$url = $traditionalUrlParams 
				? 
					preg_replace('/(.*)&amp;url=menu[^:]*\/(.*)|(.*&amp;url=[^:]*)\/(.*)/','$1$3'.$newParams,$url)
				:
					preg_replace('/(.*url=[^:]*\/)(.*)/','$1'.$newParams,$url)
		;

		return $url;
	}

	
    /**
    * Overrides CMSs breadcrumbs
    * $paths is an array of associative arrays with keys "name" and "link"
    */   
    function setPathway($crumbs) 
    {   
        $app = JFactory::getApplication();
        $pathway = $app->getPathway();
        foreach($crumbs AS $key=>$crumb)
        {
            $crumbs[$key] = (object)$crumb;
        }
        $pathway->setPathway($crumbs);        
    }
    
    function UrlTransliterate($string)
    {

        if (cmsFramework::getConfig('unicodeslugs') == 1) {
            $output = JFilterOutput::stringURLUnicodeSlug($string);
        }
        else {
            $output = JFilterOutput::stringURLSafe($string);
        }

        return $output;        
    }
    
    function StringTransliterate($string) {
        return JFilterOutput::stringURLSafe($string);
    }
    
    /**
    * Original Joomla functions for php4 to process the URI. For php5 the parse_url function is used
    * and it messes up the encoding for some greek characters
    */
    function _getUri()
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
    
    function _parseSefRoute(&$uri)
    {
        $vars    = array();
        $app    = JFactory::getApplication();
        $menu   = $app->getMenu(true);

        $parts = cmsFramework::_parseUri($uri);
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
            $items = array_reverse($menu->getMenu());
           
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
    
    function _parseUri($uri)
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
        $parts['query'] = @$matches[7][0];
        $parts['fragment'] = @$matches[9][0];    
       
        return $parts;    
    }    	
}

