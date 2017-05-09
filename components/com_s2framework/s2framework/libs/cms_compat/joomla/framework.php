<?php
/**
 * S2Framework
 * Copyright (C) 2010-2015 ClickFWD LLC
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 * @license       http://www.opensource.org/licenses/mit-license.php The MIT License
**/

defined('MVC_FRAMEWORK') or die;

class cmsFramework
{
    const CMS_CODE = 1;

    var $scripts;

    var $site_route_init;

    var $sef_plugins = array('sef','sef_advance','shsef','acesef'/*not supported*/);

    public static function clearCache()
    {
        /*
        if(!Configure::read('CMS.clearCache'))
        {
            Configure::write('CMS.clearCache',1);

            $JCache = JFactory::getCache('');

            $JCache->clean();

            // Q Cache

            $path = JPATH_ADMINISTRATOR . '/components'.  DS . 'com_qcache' . DS . 'models' . DS . 'cleanup.php';

            if(file_exists($path))
            {
                require_once($path);

                $QCache = new CleanupModelCleanup();

                $QCache->cleanup_all( true );
            }
        }
        */
    }

    /*********************************************************************
     * AJAX URI FUNCTIONS
     *********************************************************************/

    public static function getAjaxUri($app = 'jreviews', $use_lang_segment = true)
    {
        $JApp = JFactory::getApplication();

        $lang_filter = $use_lang_segment && class_exists('plgSystemLanguageFilter') && method_exists($JApp, 'getLanguageFilter') && $JApp->getLanguageFilter();

        $index_php = 'index.php';

        if($lang_filter)
        {
            // Remove 'index.php' from URI because it breaks the media uploads in Joomla 3.x when the language filter is enabled

            $index_php = '';

            $LangFilterPlugin = JPluginHelper::getPlugin('system','languagefilter');

            if(!empty($LangFilterPlugin))
            {
                $LangParams = json_decode($LangFilterPlugin->params, true);

                $remove_default_prefix = isset($LangParams['remove_default_prefix']) && $LangParams['remove_default_prefix'] == 1;

                if($remove_default_prefix)
                {
                    $JLanguage = JFactory::getLanguage();

                    $default_lang = $JLanguage->getDefault();

                    $curr_lang = $JLanguage->getTag();

                    if($default_lang == $curr_lang)
                    {
                        $lang_filter = false;
                    }
                }
            }
        }

        $lang = cmsFramework::getUrlLanguageCode();

        $_CORE_SEF = cmsFramework::getConfig('sef');

        $_SEF_ADVANCE = class_exists('SEFAdvanceRouter');

        $_SH404SEF = class_exists('shRouter');

        /**
         * Admin Ajax URI
         */

        if(defined('MVC_FRAMEWORK_ADMIN'))
        {
            // Mar 21, 2017 - Changed admin URLs to relative

            $ajaxUri = WWW_ROOT_REL . 'administrator/index.php?option=' . S2Paths::get($app, 'S2_CMSCOMP') . '&format=ajax';

            return $ajaxUri;
        }
        /**
         * SEF disabled Ajax URI
         */
        elseif(!$_CORE_SEF) {

            $ajaxUri = WWW_ROOT_REL . $index_php . '?option=' . S2Paths::get($app, 'S2_CMSCOMP') . '&format=ajax' . ($lang_filter ? '&lang=' . $lang : '');
        }
        /**
         * SEF enabled Ajax URI
         */
        else
        {
            if($_SEF_ADVANCE)
            {
                // Need to include index.php and tmpl param, otherwise SEFAdv. redirects to the JReviews component alias

                $ajaxUri = WWW_ROOT_REL . ($lang_filter ? $lang . '/' : '') . 'index.php?option=' . S2Paths::get($app, 'S2_CMSCOMP') . '&format=ajax&tmpl=component' . ($lang_filter ? '&lang=' . $lang : '');
            }
            elseif($_SH404SEF) {

                // No need to include the language segment because sh404sef icludes it and requires the language filter to be disabled

                $ajaxUri = WWW_ROOT_REL . $index_php . '?option=' . S2Paths::get($app, 'S2_CMSCOMP') . '&format=ajax' . ($lang_filter ? '&lang=' . $lang : '');
            }
            else {

                $ajaxUri = WWW_ROOT_REL . ( $lang_filter ? $lang . '/' : '' ) . $index_php . '?option=' . S2Paths::get($app, 'S2_CMSCOMP') . '&format=ajax' . ($lang_filter ? '&lang=' . $lang : '');
            }
        }

        return $ajaxUri;
    }

    public static function getAjaxUriAbs($app = 'jreviews')
    {
        $ajaxUri = WWW_ROOT . 'index.php?option=' . S2Paths::get($app, 'S2_CMSCOMP') . '&format=raw';

        return $ajaxUri;
    }

    public static function displayAjaxUri($app = 'jreviews')
    {
        echo self::getAjaxUri($app);
    }

    public static function getVersion()
    {
        $version = new JVersion();

        return $version->RELEASE;
    }

    /**
     * Reads the file version from the jreviews
     * @param  [type] $app [description]
     * @return [type]      [description]
     */
    public static function getAppVersion($app)
    {
        $file = PATH_ROOT . 'components' . DS . 'com_' . $app . DS . $app . '.php';

        $headers = array('Version'=>'Version');

        $context = 'plugin';

        $fp = fopen( $file, 'r' );

        $file_data = fread( $fp, 8192 );

        fclose( $fp );

        $file_data = str_replace( "\r", "\n", $file_data );

        foreach ( $headers as $field => $regex )
        {
            if ( preg_match( '/^[ \t\/*#@]*' . preg_quote( $regex, '/' ) . ':(.*)$/mi', $file_data, $match ) && $match[1] )
            {
                $headers[ $field ] = trim(preg_replace("/\s*(?:\*\/|\?>).*/", '', $match[1]));
            }
            else {
                $headers[ $field ] = '';
            }
        }

        return $headers['Version'];
    }

    /**
     * Gets the module parameters for the specified module id
     * @param  [type] $id [description]
     * @return [type]     [description]
     */
    public static function getModuleParams($id)
    {
        $Model = new S2Model;

        $query = "SELECT params FROM #__modules WHERE id = " . (int) $id;

        if($params = $Model->query($query,'loadResult'))
        {
            $params = stringToArray($params);
        }
        else {
            $params = array();
        }

        return $params;
    }

    public static function getUser($id = null)
    {
        $core_user = JFactory::getUser($id);

        $user = clone($core_user);

        $user->group_ids = !empty($user->groups) ? implode(',',array_keys($user->groups)) : ''; /* J16 make group ids easier to compare */

        $user->gid = $user->groups;

        return $user;
    }

    public static function getUserViewLevels($user)
    {
        $levels = JAccess::getAuthorisedViewLevels($user->id);

        return array_unique($levels);
    }

    public static function getACL()
    {
        $acl = JFactory::getACL();

        return $acl;
    }

    public static function getAccessLevelList()
    {
        $Model = new S2Model;

        $query = "
            SELECT
                id AS value, title AS text
            FROM
                #__viewlevels
            ORDER BY
                ordering
            ";

        return $Model->query($query,'loadAssocList');

    }

    public static function getAccessGroupsList()
    {
        /*
        Groupids reference
        18 - Registered
        19 - Author
        20 - Editor
        21 - Published
        23 - Manager
        24 - Administrator
        25 - Super Administrator
        */

        $Model = new S2Model;

        $query = "
            SELECT
                id AS value, title AS text
            FROM
                #__usergroups
            ORDER BY
                id
        ";

        return $Model->query($query,'loadAssocList');
    }

    public static function getDB() {
        $db = JFactory::getDBO();
        return $db;
    }

    public static function getMail($html = true) {

        $mail = JFactory::getMailer();

        $mail->isHTML($html);

        # Read cms mail config settings
        $mailfrom = cmsFramework::getConfig('mailfrom');

        $fromname = cmsFramework::getConfig('fromname');

        $mail->setSender(array($mailfrom, $fromname));

        $mail->addReplyTo($mailfrom, $fromname);

        return $mail;
    }

    public static function getConnection()
    {
        $db = cmsFramework::getDB();
        return $db->getConnection();
    }

    public static function isAdmin()
    {
        return defined('MVC_FRAMEWORK_ADMIN');
    }

    public static function installPackage($file, $target = '')
    {
        $result = false;

        $tmp_folder = cmsFramework::getConfig('tmp_path') . DS . substr(md5(time()),0,15) . DS;

        $Folder = new s2Folder();

        $Folder->mkdir($tmp_folder);

        // Unzip the file to a temporary addon folder and remove the uploaded zip

        if($result = self::packageUnzip($file, $tmp_folder))
        {
            unlink($file);

            $Installer = new JInstaller;

            $result = $Installer->install($tmp_folder);
        }

        // Remove the file and tmp folder

        if(file_exists($file)) unlink($file);

        $Folder->rm($tmp_folder);

        return $result;
    }

    public static function packageUnzip($file, $target)
    {
        jimport( 'joomla.filesystem.file' );

        jimport( 'joomla.filesystem.folder' );

        jimport( 'joomla.filesystem.archive' );

        jimport( 'joomla.filesystem.path' );

        $adapter = JArchive::getAdapter('zip');

        $result = @$adapter->extract($file, $target);

        return $result;
    }

    public static function getTemplate()
    {
        return JFactory::getApplication()->getTemplate();
    }

    public static function scriptLoaded($name)
    {
        $loaded = array();

        $doc = JFactory::getDocument();

        $scripts = array_keys(Sanitize::getVar($doc,'_custom',array()));

        foreach($scripts AS $script)
        {
            if(is_numeric($script) || $script == '') continue;

            $script = explode(',',$script);

            $loaded = array_merge($loaded, $script);
        }

        return in_array($name, $loaded);
    }

    public static function addScriptTag($html, $namespace = '')
    {
        if(!strstr($html, '</script>'))
        {
            $html = '<script type="text/javascript">'. $html . '</script>';
        }

        self::addCustomTag($html, $namespace);
    }

    public static function addCustomTag($html, $namespace = '')
    {
        if($namespace != '' && is_string($namespace) && self::scriptLoaded($namespace)) return;

        $_namespace = is_array($namespace) ? implode(',',$namespace) : $namespace;

        $doc = JFactory::getDocument();

        if(trim($html) != '')
        {
            if($_namespace != '')
            {
                $doc->_custom[$_namespace] = trim($html);
            }
            else {
                $doc->_custom[] = trim($html);
            }
        }
    }

    public static function addScriptDefer($url, $handle, $version = null)
    {
        $defer = true;

        if (strpos($_SERVER['HTTP_USER_AGENT'], 'MSIE 9.') !==false) {
            $defer = false;
        }

        self::addScript($url, $handle, $version, $defer = true, $async = false);
    }

    public static function addScript($url, $handle, $version = null, $defer = false, $async = false)
    {
        if($version != '')
        {
            $url .= '?v=' . $version;
        }

        // Register in header to prevent duplicates
        $registry = ClassRegistry::getObject('javascript');

        if(!isset($registry[$handle]))
        {
            ClassRegistry::setObject($handle,1,'javascript');

            $has_script_tag = strstr($url, '</script');

            if($url != '' && !$has_script_tag /* don't process stuff with script tags */)
            {
                $doc = JFactory::getDocument();

                $doc->addScript($url, 'text/javascript', $defer, $async);
            }
            elseif($url != '' && $has_script_tag)
            {
                self::addCustomTag($url);
            }
        }
    }

    public static function addStyleSheet($handle, $url, $version = '')
    {
        if($url != '')
        {
            if($version != '')
            {
                $url .= '?v=' . $version;
            }

            $doc = JFactory::getDocument();

            $doc->addStyleSheet($url);
        }
    }

    public static function getCharset()
    {
        return 'UTF-8';
    }

    public static function getConfig($var, $default = null)
    {
        switch($var)
        {
            case 'cache_path':

                $value = PATH_ROOT . 'cache';

                break;

            case 'secret':

                $value = defined('_CMS_SECRET_KEY') ? _CMS_SECRET_KEY : false;

                if($value)
                {
                    break;
                }

            default:

                $cmsConfig = ClassRegistry::getClass('JConfig');

                if(isset($cmsConfig->{$var}))
                {
                  $value = $cmsConfig->{$var};
                }
                else {

                  $value = $default;
                }

            break;
        }

        return $value;
    }

    public static function setSessionVar($key,$var,$namespace)
    {
        $session = JFactory::getSession();
        $session->set($key,$var,$namespace);
    }

    public static function getSessionVar($key,$namespace)
    {
        $session = JFactory::getSession();
        return $session->get($key, array(), $namespace);
    }

    public static function clearSessionVar($key,$namespace) {
        if($key != '' && $namespace != '') {
            $session = JFactory::getSession();
            $session->clear($key, $namespace);
        }
    }

    public static function clearSessionNamespace($namespace)
    {
        // No longer possible to clear the entire namespace because Joomla changed the way data is stored in the session and
        // there isn't a method that can be used for this purpose
    }

    /**
    * Used to prevent form data tampering
    *
    */
    public static function getCustomToken()
    {
        $string = '';

        if(func_num_args() > 0)
        {
            $tokens = func_get_args();

            $string = cmsFramework::getConfig('secret') . json_encode($tokens);
        }

        return md5($string);
    }

    public static function formIntegrityToken($entry, $keys, $input = true)
    {
        $string = '';

        $tokens = array();

        !isset($entry['form']) and $entry['form'] = array();

        !isset($entry['data']) and $entry['data'] = array();

        unset($entry['data']['controller'],$entry['data']['action'],$entry['data']['module'],$entry['data']['__raw']);

        // Remove the module extension parameter because it's not possible to know before hand what it will be since it can also be set via auto-detect

        if(isset($entry['module']))
        {
            unset($entry['module']['extension']);
        }

        // Leave only desired $keys from $entry
        $params = array_intersect_key($entry,array_fill_keys($keys,1));

        // Orders the array by keys so the hash will match
        ksort($params);

        // Remove empty elements and cast all values to strings
        foreach($params AS $key=>$param)
        {
            if(is_array($param) && !empty($param)) {

                $param = is_array($param) ? array_filter($param) : false;

                if(!empty($param))
                {
                    $tokens[] = self::array_map_recursive('strval', $param);
                }
            }
            elseif (!empty($param)) {

                $tokens[] = strval($param);
            }
        }

        sort($tokens);

        if(empty($tokens)) return '';

        return $input ?
            '<input class="token-i" type="hidden" name="'.cmsFramework::getCustomToken($tokens).'" value="1" />'
            :
            cmsFramework::getCustomToken($tokens);
    }

    public static function array_map_recursive($callback, $array)
    {
        foreach ($array as $key => $value)
        {
            if (is_array($array[$key])) {
                $array[$key] = self::array_map_recursive($callback, $array[$key]);
            }
            // Nov 22, 2016- skip objects added by 3rd party extensions (like Hikashop) to the request
            elseif (!is_array($array[$key]) && !is_object($array[$key])) {
                $array[$key] = call_user_func($callback, $array[$key]);
            }
        }
        return $array;
    }

    public static function getTokenInput()
    {
        return '<span class="jr_token jr_hidden"><input class="token-s" type="hidden" name="'.cmsFramework::getToken().'" value="1" /></span>';
    }

    public static function getToken($new = false)
    {
        if(class_exists('JUtility') && method_exists('JUtility', 'getToken')) {

            $token = JUtility::getToken($new);
        }
        else {
            $token = JSession::getFormToken();
        }

        return $token;
    }

    public static function getDateFormat($string='DATE_FORMAT_LC3') {

        return JText::_($string);
    }

    public static function dateConvertTimeZone($date, $from_tz, $to_zn, $params = array())
    {
        if($date == '' || $date == NULL_DATE || $date == _NULL_DATE)
        {
            return $date;
        }

        $defaults = array('format'=>'Y-m-d H:i:s');

        $params = array_replace($defaults, $params);

        extract($params);

        $dateTime = new DateTime($date,new DateTimeZone($from_tz));

        $dateTime->setTimeZone(new DateTimeZone($to_zn));

        $utc = $dateTime->format($format);

        return $utc;
    }

    public static function dateLocalToUTC($date = 'now', $params = array())
    {
        $defaults = array('format'=>'Y-m-d H:i:s', 'tz'=>null);

        $params = array_replace($defaults, $params);

        if(is_null($params['tz']))
        {
            $tz = cmsFramework::getConfig('offset');
        }

        return self::dateConvertTimeZone($date, $tz, 'UTC', $params);
    }

    public static function dateUTCToLocal($date = 'now', $params = array())
    {
        $defaults = array('format'=>'Y-m-d H:i:s', 'tz'=>null);

        $params = array_replace($defaults, $params);

        if(is_null($params['tz']))
        {
            $tz = cmsFramework::getConfig('offset');
        }

        return self::dateConvertTimeZone($date, 'UTC', $tz, $params);
    }

    public static function isRTL()
    {
        $lang    = JFactory::getLanguage();

        return (int) $lang->isRTL();
    }

    public static function getIgnoredSearchWords()
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
    * Adds GTranslate support to load the correct javascript language file.
    *
    */
    public static function getLocaleJavascript($separator = '_')
    {
        // GTranslate support

        if(isset($_SERVER['HTTP_X_GT_LANG']))
        {
            $langCode = $_SERVER['HTTP_X_GT_LANG'];

            return $langCode . '_' . strtoupper($langCode);
        }

        return self::getLocale($separator);
    }

    /**
    * This returns the locale from the Joomla language file
    *
    */
    public static function getLocale($separator = '_')
    {
        $lang    = JFactory::getLanguage();

        $locale = $lang->getTag();

        return str_replace('-',$separator,$locale);
    }

    /**
    * Used for I18n in s2framework
    *
    */
    public static function locale()
    {
        // GTranslate support

        if(isset($_SERVER['HTTP_X_GT_LANG']))
        {
            $langCode = $_SERVER['HTTP_X_GT_LANG'];

            return $langCode;
        }

        $lang    = JFactory::getLanguage();

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
    public static function getUrlLanguageCode()
    {
        if(class_exists('JLanguageHelper')) {
            $lang = JLanguageHelper::getLanguages('lang_code');

            $locale = cmsFramework::getLocale('-');

            // $locale = cmsFramework::locale();

            return isset($lang[$locale]) ? $lang[$locale]->sef : '';
        }
    }

    public static function listImages( $name, &$active, $javascript=NULL, $directory=NULL )
    {
        return JHTML::_('list.images', $name, $active, $javascript, $directory);
    }

    public static function listPositions( $name, $active=NULL, $javascript=NULL, $none=1, $center=1, $left=1, $right=1, $id=false )
    {
        return JHTML::_('list.positions', $name, $active, $javascript, $none, $center, $left, $right, $id);
    }

    public static function applyPageTitleFormat($title)
    {
        $sitename = self::getConfig('sitename');

        $sitename_pagetitle = self::getConfig('sitename_pagetitles');

        if($sitename && $sitename_pagetitle == 1) {

            $title = JText::sprintf('JPAGETITLE', $sitename, $title);
        }
        elseif($sitename && $sitename_pagetitle == 2) {

            $title = JText::sprintf('JPAGETITLE', $title, $sitename);
        }

        return $title;
    }

    public static function meta($type,$text,$inline = false)
    {
        if ($text == '')
        {
            return;
        }

        $text = htmlspecialchars(strip_tags($text),ENT_COMPAT,'utf-8');

        $text = preg_replace('/\s+/', ' ', trim($text));

        if ($inline)
        {
            echo '<meta name="' . $type . '" content="' . $text . '" />';

            return;
        }

        $document = JFactory::getDocument();

        switch($type)
        {
            case 'title':

                $document->setTitle($text);

                break;

            case 'keywords':
            case 'description':
            default:

                if($type == 'description')
                {
                    $document->description = $text;
                }
                else {
                    $document->setMetaData($type,$text);
                }

            break;
        }
    }


    public static function noAccess($return = false)
    {
        $msg =  JText::_('JERROR_ALERTNOAUTHOR');

        if($return) {
            return $msg;
        }

        echo $msg;
    }

    public static function formatDate($date)
    {
        return JHTML::_('date', $date );
    }

    /**
     * Different public static function names used in different CMSs
     *
     * @return unknown
     */
    public static function reorderList()
    {
        return 'reorder';
    }

    public static function redirect($url, $statusCode = 301)
    {
        $url = str_replace('&amp;','&',$url);

        if (headers_sent())
        {
            echo "<script>document.location.href='$url';</script>\n";
        }
        else {

            switch($statusCode)
            {
                case 301:

                    $HTTP = 'HTTP/1.1 301 Moved Permanently';

                    break;

                case 302:

                    $HTTP = 'HTTP/1.1 302 Temporary Redirect';

                    break;
            }

            header( $HTTP );

            header( 'Location: ' . $url );
        }

        exit;
    }

    /**
    * Convert relative urls to absolute for use in feeds, emails, etc.
    */
    public static function makeAbsUrl($url,$options=array())
    {
        $options = array_merge(array('sef'=>false,'ampreplace'=>false),$options);

        $options['sef'] and $url = cmsFramework::route($url);

        $options['ampreplace'] and $url = str_replace('&amp;','&',$url);

        if(!strstr($url,'http')) {

            $url_parts = parse_url(WWW_ROOT);

            $port = Sanitize::getInt($url_parts,'port');

            # If the site is in a folder make sure it is included in the url just once

            if($url_parts['path'] != '')
            {
                if(strcmp($url_parts['path'],substr($url,0,strlen($url_parts['path']))) !== 0) {

                    $url = rtrim($url_parts['path'],'/') . '/' . ltrim($url,'/');
                }
            }

            $url = $url_parts['scheme'] . '://' . $url_parts['host'] . ($port ? ':' . $port : '') . $url;
        }

        return $url;
    }

     /**
    * This public static function is used as a replacement to JRoute::_() to generate sef urls in Joomla admin
    *
    * @param mixed $urls
    * @param mixed $xhtml
    * @param mixed $ssl
    */
    public static function siteRoute($urls, $xhtml = true, $ssl = null)
    {
        !is_array($urls) and $urls = array($urls);

        $sef_urls = array();

        $fields = array();

        foreach($urls AS $key=>$url)
        {
            $fields[] = "data[url][{$key}]=".urlencode($url);
        }

        // Not using tmpl=component causes a 500 renderer error in some Joomla installs

        $target_url = WWW_ROOT . 'index.php?option=com_jreviews&format=raw&tmpl=component&url=common/_sefUrl';

        $response = cmsFramework::curlCall($target_url, $fields);

        // If admin is using HTTPS, it's possible front-end isn't so if we get an empty response we need to try the HTTP protocol before bailing out

        if(!$response && isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on')
        {
            $target_url = str_replace('https','http',$target_url);

            $response = cmsFramework::curlCall($target_url, $fields);
        }

        // Remove any php notices or errors from the ajax response

        $matches = array();

        if($response != '') {

            $response = preg_match('/(\[.*\])|({.*})/',$response,$matches);

            if(isset($matches[0])) {

                $sef_urls = json_decode($matches[0],true);

                return is_array($sef_urls) && count($sef_urls) == 1 ? array_shift($sef_urls) : $sef_urls;
            }
        }

        foreach($urls AS $key=>$url)
        {
            $sef_urls[$key] = WWW_ROOT . $url;
        }

        return is_array($sef_urls) && count($sef_urls) == 1 ? array_shift($sef_urls) : $sef_urls;
    }

    public static function curlCall($url, $fields)
    {
        $useragent = "Ajax Request";

        $fields_string = implode('&',$fields);

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_USERAGENT, $useragent);

        curl_setopt($ch, CURLOPT_URL,$url);

        curl_setopt( $ch, CURLOPT_ENCODING, "" );

        curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );

        curl_setopt( $ch, CURLOPT_AUTOREFERER, 1 );

        curl_setopt( $ch, CURLOPT_POST, count($fields));

        curl_setopt( $ch, CURLOPT_POSTFIELDS, $fields_string);

        curl_setopt( $ch, CURLOPT_CONNECTTIMEOUT, 30);

        $response = curl_exec($ch);

        curl_close($ch);

        return $response;
    }

    public static function isCommandLineInterface()
    {
        return php_sapi_name() == 'cli';
    }

    public static function route($link, $options = array())
    {
        $options = array_merge(array('xhtml'=>true,'ssl'=>null),$options);

        extract($options);

        $menu_alias = '';

        // Check if it's a JomSocial URL and use the JomSocial router

        $query = array();

        parse_str(str_replace(array('index.php?','&amp;'),array('','&'),$link), $query);

        if(Sanitize::getString($query,'option') == 'com_community' && class_exists('CRoute'))
        {
            return CRoute::_($link);
        }

        /**
         * Check for SSL system-wide override and disable it for all URLs processed by JReviews
         * This automatically converts relative URLs to absolute in the Joomla route method
         */

        if(defined('S2_NO_SSL') && S2_NO_SSL) {

            $ssl = -1;
        }

        $traditionalUrlParams = true;

        // Add index.php is missing we add it
        if(false === strpos($link,'index.php'))
        {
            $link = 'index.php?' . $link;
        }

        // Check core sef

        $sef = cmsFramework::getConfig('sef');

        $sef_rewrite = cmsFramework::getConfig('sef_rewrite');

        $isJReviewsUrl = strpos($link,'option=com_jreviews');

        /**
         * Process non-JReviews URLS
         */

        if(false === $isJReviewsUrl)
        {
            // SEF URLs disabled
            if(!$sef)
            {
                $url = cmsFramework::isAdmin() || self::isCommandLineInterface() ? cmsFramework::siteRoute($link,$xhtml,$ssl) : JRoute::_($link,$xhtml,$ssl);

                if(false === strpos($url,'http'))
                {
                    $parsedUrl = parse_url(WWW_ROOT);

                    $port = isset($parsedUrl['port']) && $parsedUrl['port'] != '' ? ':' . $parsedUrl['port'] : '';

                    $url = $parsedUrl['scheme'] . '://' . $parsedUrl['host'] . $port . $url;
                }

                return $url;
            }
            // SEF URLs enabled
            else
            {
                $url = cmsFramework::isAdmin() || self::isCommandLineInterface() ? cmsFramework::siteRoute($link,$xhtml,$ssl) : JRoute::_($link,$xhtml,$ssl);

                return $url;
            }
        }

        /**
         * Process JReviews URLs
         */

        $isJReviewsUrl and $link = cmsFramework::reorderUrlParams($link, $traditionalUrlParams);

        // Fixes component menu urls with pagination and ordering parameters when core sef is enabled.
        $link = str_replace('//','/',$link);

        // SEF URLs enabled
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

                    $menu_alias = cmsFramework::isAdmin() || self::isCommandLineInterface() ? cmsFramework::siteRoute($link2,$xhtml,$ssl) : JRoute::_($link2,$xhtml,$ssl);

                    strstr($menu_alias,'index.php') and $menu_alias = str_replace('.html','/',substr($menu_alias,strpos($menu_alias,'index.php'._DS)+10));

                    $menu_alias .= '/';

                    $url_alias_segments = explode('?',$menu_alias);

                    $url_alias_segments_last = array_shift($url_alias_segments);

                    $menu_alias = '/'.ltrim($url_alias_segments_last,'/');
                }
            }

            // Core sef doesn't know how to deal with colons, so we convert them to something else and then replace them again.
            $link = $nonsef_link = str_replace(_PARAM_CHAR,'*@*',$link);

            $sefUrl = cmsFramework::isAdmin() || self::isCommandLineInterface() ? cmsFramework::siteRoute($link,$xhtml,$ssl) : JRoute::_($link,$xhtml,$ssl);

            // Fix for messed up URLs returned by Joomla when the $live_site variable is present in configuration.php

            if(cmsFramework::getConfig('live_site') != '')
            {
                $sefUrl = str_replace(array('/http:/','/http:/'),array('http://','https://'),$sefUrl);
            }

            $sefUrl = preg_replace('/\[(\d+)\]/','[]', $sefUrl);

            $sefUrl = str_replace('%2A%40%2A',_PARAM_CHAR,$sefUrl);

            $sefUrl = str_replace('*@*',_PARAM_CHAR,$sefUrl); // For non sef links

            if(!class_exists('shRouter'))
            {
                // Get rid of duplicate menu alias segments added by the JRoute public static function
                if(strstr($sefUrl,'order:') || strstr($sefUrl,'page:') || strstr($sefUrl,'limit:'))
                {
                    $sefUrl = str_replace(array('/format:html/','.html'),'/',$sefUrl);
                }

                // Get rid of duplicate menu alias segments added by the JRoute public static function
                if($menu_alias != '' && $menu_alias != '/' && !$mod_rewrite)
                {
                    // Need to remove the right slash, otherwise not all duplicates are replaced

                    $menu_alias_mod = rtrim($menu_alias,'/');

                    $sefUrl = str_replace($menu_alias_mod, '--menuAlias--', $sefUrl,$count);

                    $sefUrl = str_replace(str_repeat('--menuAlias--',$count), $menu_alias_mod, $sefUrl);

                }
            }

            $link = $sefUrl;

            // If it's not a JReviews menu url remove the suffix

            $nonsef_link = str_replace('&amp;','&',$nonsef_link);

            if(!defined('JREVIEWS_SEF_PLUGIN') && substr($nonsef_link,0,9) == 'index.php' && (!$Itemid || ($traditionalUrlParams == false && !preg_match('/^index.php\?option=com_jreviews&Itemid=([0-9]+)$/i',$nonsef_link))))
            {
                $link = str_replace('.html','',$sefUrl);
            }
        }

        if(false !== strpos($link,'http'))
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

    public static function constructRoute($passedArgs,$excludeParams = null,$app = 'jreviews')
    {
        $segments = $url_param = array();

        $defaultExcludeParms = array('format','view','language','lang');

        $excludeParams = !empty($excludeParams) ? array_merge($excludeParams,$defaultExcludeParms) : $defaultExcludeParms;

        $arrayParams = array(S2_QVAR_RATING_AVG, S2_QVAR_EDITOR_RATING_AVG);

        $passedArgs = array_filter($passedArgs);

        if(defined('MVC_FRAMEWORK_ADMIN'))
        {
            $base_url = 'index.php?option='.S2Paths::get($app, 'S2_CMSCOMP');
        }
        else {

            $Itemid = Sanitize::getInt($passedArgs,'Itemid') > 0 ? Sanitize::getInt($passedArgs,'Itemid') : '';

            $base_url = 'index.php?option='.S2Paths::get($app, 'S2_CMSCOMP').'&amp;Itemid=' . $Itemid;
        }

        // Get segments without named params
        if(isset($passedArgs['url']))
        {
            $parts = explode('/',$passedArgs['url']);

            foreach($parts AS $bit)
            {
                if(false === strpos($bit,_PARAM_CHAR) && $bit != 'index.php')
                {
                    $segments[] = $bit;
                }
            }
        }

        unset($passedArgs['option'], $passedArgs['Itemid'], $passedArgs['url']);

        if(is_array($excludeParams))
        {
            foreach($excludeParams AS $exclude)
            {
                unset($passedArgs[$exclude]);
            }
        }

        foreach($passedArgs AS $paramName=>$paramValue)
        {
            if(is_string($paramValue) && $paramValue != '')
            {
                $paramValue == 'order' and $paramValue = array_shift(explode('.html',$paramValue));

                $url_param[] = $paramName . _PARAM_CHAR . urlencodeParam($paramValue);
            }
            elseif(is_array($paramValue) && in_array($paramName, $arrayParams)) {

                foreach($paramValue AS $key => $value)
                {
                    $url_param[] = $paramName . '[]' . _PARAM_CHAR . urlencodeParam($value);
                }
            }
        }

        empty($segments) and $segments[] = 'menu';

        $new_route = $base_url . (!empty($segments) ? '&amp;url=' . implode('/',$segments) . '/' . implode('/',$url_param) : '');

        return $new_route;
    }

    public static function reorderUrlParams($url, $traditionalUrlParams = false)
    {
        preg_match_all('/\/([a-z0-9_%\\[\\]]+):([^\/]*)/i',$url,$matches);

        if(empty($matches[0])) return $url;

        $newArray = array_combine($matches[1],$matches[2]);

        $array = $newArray;

        $orderArray = array(
            S2_QVAR_MEDIA_CODE,
            S2_QVAR_PAGE,
            'user',
            'id',
            'order',
            S2_QVAR_RATING_AVG.'[]',
            S2_QVAR_EDITOR_RATING_AVG.'[]',
            'limit',
            'dir',
            'cat',
            'scope',
            'query',
            'criteria',
            'usematch',
            'matchall',
            'listview',
            'filter',
            'tmpl');

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
    public static function setPathway($crumbs)
    {
        $app = JFactory::getApplication();

        $pathway = $app->getPathway();

        foreach($crumbs AS $key=>$crumb)
        {
            $crumbs[$key] = (object)$crumb;
        }

        $pathway->setPathway($crumbs);
    }

    public static function addCrumb($name, $link)
    {
        $app = JFactory::getApplication();

        $pathway = $app->getPathway();

        $pathway->addItem($name, $link);
    }

    public static function UrlTransliterate($string)
    {

        if (cmsFramework::getConfig('unicodeslugs') == 1) {
            $output = JFilterOutput::stringURLUnicodeSlug($string);
        }
        else {
            $output = JFilterOutput::stringURLSafe($string);
        }

        return $output;
    }

    public static function StringTransliterate($string) {
        return JFilterOutput::stringURLSafe($string);
    }

    public static function getCurrentUrl($paramFilter = array())
    {
        if(!empty($paramFilter) && !is_array($paramFilter))
        {
            $paramFilter = array($paramFilter);
        }

        $paramFilter[] = 'format';

        $current_url = $_SERVER["REQUEST_URI"];

        $parts = parse_url($current_url);

        $query = array();

        if(isset($parts['query']))
        {
            parse_str($parts['query'], $query);
        }

        foreach($query AS $key=>$val)
        {
            if(in_array($key, $paramFilter))
            {
                unset($query[$key]);
            }
        }

        $query = http_build_query($query);

        return $parts['path'] . ($query != '' ? '?' . $query : '');
    }

    /**
     * Set json content-type
     */
    public static function jsonResponse($array, $options = array())
    {
        if(isset($_REQUEST['requested']) && $_REQUEST['requested'] == true) {
            unset($_REQUEST['requested']);
            return is_array($array) ? json_encode($array) : $array;
        }

        $defaults = array(
                'encoding'=>'application/json'
            );

        $options = array_merge($defaults, $options);

        $doc = JFactory::getDocument();

        $doc->setMimeEncoding($options['encoding']);

        return is_array($array) ? json_encode($array) : $array;
    }

    public static function raiseError($code, $text) {

        echo JError::raiseError( $code, $text );
    }

    /**
     * From /components/com_users/controllers/user.php
     * We use this updated method to be able to specify the failed login URL using the form input 'return_fail'
     */
    public static function login()
    {
        JSession::checkToken('post') or jexit(JText::_('JInvalid_Token'));

        $app    = JFactory::getApplication();
        $input  = $app->input;
        $method = $input->getMethod();

        // Populate the data array:
        $data = array();

        $data['return']    = base64_decode($app->input->post->get('return', '', 'BASE64'));
        $data['return_fail'] = base64_decode($app->input->post->get('return_fail', '', 'BASE64'));
        $data['username']  = $input->$method->get('username', '', 'USERNAME');
        $data['password']  = $input->$method->get('password', '', 'RAW');
        $data['secretkey'] = $input->$method->get('secretkey', '', 'RAW');

        // Set the return URL if empty.
        if (empty($data['return']))
        {
            $data['return'] = 'index.php?option=com_users&view=profile';
        }

        // Set the return URL in the user state to allow modification by plugins
        $app->setUserState('users.login.form.return', $data['return']);

        // Get the log in options.
        $options = array();
        $options['remember'] = $input->$method->getBool('remember', false);
        $options['return']   = $data['return'];

        // Get the log in credentials.
        $credentials = array();
        $credentials['username']  = $data['username'];
        $credentials['password']  = $data['password'];
        $credentials['secretkey'] = $data['secretkey'];

        // Perform the log in.
        if (true === $app->login($credentials, $options))
        {
            // Success
            if ($options['remember'] == true)
            {
                $app->setUserState('rememberLogin', true);
            }

            $app->setUserState('users.login.form.data', array());
            $app->redirect(JRoute::_($app->getUserState('users.login.form.return'), false));
        }
        else
        {
            // Login failed !
            $data['remember'] = (int) $options['remember'];
            $app->setUserState('users.login.form.data', $data);
            $app->redirect($data['return_fail'] ?: JRoute::_('index.php?option=com_users&view=login', false));
        }
    }

    public static function logoutUrl($return= '/')
    {
        $userToken = JSession::getFormToken();

        return sprintf('index.php?option=com_users&amp;task=user.logout&amp;%s=1&return=%s', $userToken, base64_encode($return));
    }

    public static function resetPasswordUrl()
    {
        require_once JPATH_SITE . '/components/com_users/helpers/route.php';

        return JRoute::_('index.php?option=com_users&view=reset&Itemid=' . UsersHelperRoute::getResetRoute());
    }

    public static function checkPassword($password, $hash)
    {
        return JUserHelper::verifyPassword($password, $hash);
    }

    public static function hashPassword($password)
    {
        return JUserHelper::hashPassword($password);
    }

    public static function allowUserRegistration() {

        return self::getUserConfig('registration');
    }

    public static function getUserConfig($setting)
    {
        $userParams = JComponentHelper::getParams('com_users');

        switch($setting)
        {
            case 'registration':

                return $userParams->get('allowUserRegistration');

                break;

            case 'activation':

                $activation = $userParams->get('useractivation');

                // 0 - none, 1 - self, 2 - admin

                return $activation;

                break;

            case 'admin_notification':

                // Valid for none or self activation

                return self::getUserConfig('activation') == 2 ? false : $userParams->get('mail_to_admin');

                break;

            case 'default_group':

                return $userParams->get('new_usertype');

                break;

            case 'send_password':

                return $userParams->get('sendpassword');

                break;
        }

        return false;
    }

    public static function loginUser($username, $password)
    {
        $App = JFactory::getApplication();

        jimport( 'joomla.plugin.plugin' );

        $App->login(array('username'=>$username,'password'=>$password),array('action'=>'core.login.site'));
    }

    public static function registerUser($data)
    {
        $config = JFactory::getConfig();

        $params = JComponentHelper::getParams('com_users');

        $lang = JFactory::getLanguage();

        $lang->load('com_users');

        $db = self::getDB();

        // Initialise the table with JUser.
        $user = new JUser;

        $activation = Sanitize::getInt($data, 'activation');

        $sendpassword = cmsFramework::getUserConfig('send_password');

        unset($data['activation']);

        if($activation > 0)
        {
            $data['block'] = 1; // Needs to be set to one so the activation link will work

            $data['activation'] = JApplication::getHash(JUserHelper::genRandomPassword());
        }

        // Get the groups the user should be added to after registration.
        $data['groups'] = Sanitize::getVar($data, 'user_groups', array());

        // Get the default new user group, Registered if not specified.

        if (empty($data['groups']))
        {
            $system = $params->get('new_usertype', self::getUserConfig('default_group'));

            $data['groups'][] = $system;
        }

        // $data['usertype'] = 'Registered';

        // Bind the data.
        $user->bind($data);

        // Load the users plugin group.
        JPluginHelper::importPlugin('user');

        // Store the data.
        if(!$user->save()) {
            return false;
        }

        // Compile the notification mail values.
        $data = $user->getProperties();
        $data['fromname']   = $config->get('fromname');
        $data['mailfrom']   = $config->get('mailfrom');
        $data['sitename']   = $config->get('sitename');
        $data['siteurl']    = JUri::root();

       // Set the link to activate the user account.
        $uri = JURI::getInstance();

        $base = $uri->toString(array('scheme', 'user', 'pass', 'host', 'port'));

        $data['activate'] = $base . JRoute::_('index.php?option=com_users&task=registration.activate&token=' . $data['activation'], false);

        // For admin activation

        if($activation == 2)
        {
            $emailSubject = JText::sprintf(
                'COM_USERS_EMAIL_ACCOUNT_DETAILS',
                $data['name'],
                $data['sitename']
            );

            if($sendpassword)
            {
                $emailBody = JText::sprintf(
                    'COM_USERS_EMAIL_REGISTERED_WITH_ADMIN_ACTIVATION_BODY',
                    $data['name'],
                    $data['sitename'],
                    $data['activate'],
                    $data['siteurl'],
                    $data['username'],
                    $data['password_clear']
                );
            }
            else
            {
                $emailBody = JText::sprintf(
                    'COM_USERS_EMAIL_REGISTERED_WITH_ADMIN_ACTIVATION_BODY_NOPW',
                    $data['name'],
                    $data['sitename'],
                    $data['activate'],
                    $data['siteurl'],
                    $data['username']
                );
            }
        }

        // User activation

        elseif($activation == 1)
        {
            $emailSubject = JText::sprintf(
                'COM_USERS_EMAIL_ACCOUNT_DETAILS',
                $data['name'],
                $data['sitename']
            );

            if ($sendpassword)
            {
                $emailBody = JText::sprintf(
                    'COM_USERS_EMAIL_REGISTERED_WITH_ACTIVATION_BODY',
                    $data['name'],
                    $data['sitename'],
                    $data['activate'],
                    $data['siteurl'],
                    $data['username'],
                    $data['password_clear']
                );
            }
            else
            {
                $emailBody = JText::sprintf(
                    'COM_USERS_EMAIL_REGISTERED_WITH_ACTIVATION_BODY_NOPW',
                    $data['name'],
                    $data['sitename'],
                    $data['activate'],
                    $data['siteurl'],
                    $data['username']
                );
            }
        }

        // No activation

        else {

            $emailSubject = JText::sprintf(
                'COM_USERS_EMAIL_ACCOUNT_DETAILS',
                $data['name'],
                $data['sitename']
            );

            if ($sendpassword)
            {
                $emailBody = JText::sprintf(
                    'COM_USERS_EMAIL_REGISTERED_BODY',
                    $data['name'],
                    $data['sitename'],
                    $data['siteurl'],
                    $data['username'],
                    $data['password_clear']
                );
            }
            else
            {
                $emailBody = JText::sprintf(
                    'COM_USERS_EMAIL_REGISTERED_BODY_NOPW',
                    $data['name'],
                    $data['sitename'],
                    $data['siteurl']
                );
            }
        }

        // Send the user email
        $return = JFactory::getMailer()->sendMail($data['mailfrom'], $data['fromname'], $data['email'], $emailSubject, $emailBody);

        if(self::getUserConfig('admin_notification'))
        {
            $emailSubject = JText::sprintf(
                'COM_USERS_EMAIL_ACCOUNT_DETAILS',
                $data['name'],
                $data['sitename']
            );

            $emailBodyAdmin = JText::sprintf(
                'COM_USERS_EMAIL_REGISTERED_NOTIFICATION_TO_ADMIN_BODY',
                $data['name'],
                $data['username'],
                $data['siteurl']
            );

            $query = $db->getQuery(true);

            // Get all admin users
            $query->clear()
                ->select($db->quoteName(array('name', 'email', 'sendEmail')))
                ->from($db->quoteName('#__users'))
                ->where($db->quoteName('sendEmail') . ' = ' . 1);

            $db->setQuery($query);

            try
            {
                $rows = $db->loadObjectList();
            }
            catch (RuntimeException $e)
            {
                die(JText::sprintf('COM_USERS_DATABASE_ERROR'));

                return false;
            }

            // Send mail to all superadministrators id
            foreach ($rows as $row)
            {
                $return = JFactory::getMailer()->sendMail($data['mailfrom'], $data['fromname'], $row->email, $emailSubject, $emailBodyAdmin);

                // Check for an error.
                if ($return !== true)
                {
                    die(JText::_('COM_USERS_REGISTRATION_ACTIVATION_NOTIFY_SEND_MAIL_FAILED'));

                    return false;
                }
            }
        }

        return $user->id;
    }

    public static function getAdminUserManagerUrl()
    {
        return 'index.php?option=com_users&view=users&tmpl=component';
    }
}