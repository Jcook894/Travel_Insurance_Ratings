<?php
/**
 * S2Framework
 * Copyright (C) 2010-2015 ClickFWD LLC
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 * @license       http://www.opensource.org/licenses/mit-license.php The MIT License
**/

defined('MVC_FRAMEWORK') or die;

class cmsFramework {

    const CMS_CODE = 2;

    public static function clearCache() {}

    /*********************************************************************
     * AJAX URI FUNCTIONS
     *********************************************************************/

    public static function getAjaxUri($app = 'jreviews', $use_lang_segment = true)
    {
        $ajaxUri = WWW_ROOT_REL . 'wp-admin/admin-ajax.php?action=' . $app . '_ajax';

        if(defined('MVC_FRAMEWORK_ADMIN'))
        {
            $ajaxUri .= '&side=admin';
        }

        return $ajaxUri;
    }

    public static function getAjaxUriAbs($app = 'jreviews')
    {
        $ajaxUri = WWW_ROOT . 'wp-admin/admin-ajax.php?action=' . $app . '_ajax';

        return $ajaxUri;
    }

    public static function displayAjaxUri($app='jreviews')
    {
        echo self::getAjaxUri($app);
    }

    public static function getVersion()
    {
        global $wp_version;

        return $wp_version;
    }

    public static function getAppVersion($app)
    {
        $plugin = get_file_data( WP_PLUGIN_DIR . DS . $app . DS .$app . '.php', array('Version'=>'Version'), 'plugin' );

        return $plugin['Version'];
    }

    /**
     * Gets the widget parameters for the specified module id
     * @param  [type] $id [description]
     * @return [type]     [description]
     */
    public static function getModuleParams($id)
    {
        preg_match('/(.*)-(\d+)/',$id,$matches);

        $params = array();

        if(!empty($matches))
        {
            $widget_params = get_option('widget_' . $matches[1]);

            $params = Sanitize::getVar($widget_params,$matches[2], array());
        }

        return $params;
    }

    public static function getUser($id = null)
    {
        global $wp_roles;

        $roles = $wp_roles->get_names();

        $group_ids = array(
                'administrator'=>array(7,8),
                'editor'=>array(4,5),
                'author'=>array(3),
                'contributor'=>array(2),
                'subscriber'=>array(2),
                'guest'=>array(1),
                ''=>array(0)
            );

        $core_user = $id ? new WP_User($id) : wp_get_current_user();

        $user = new stdClass;

        $user->id = $core_user->ID;

        $user->name = Sanitize::getString($core_user->data,'display_name');

        $user->username = Sanitize::getString($core_user->data,'user_nicename');

        $user->email = Sanitize::getString($core_user->data,'user_email');

        $user->status = Sanitize::getString($core_user->data,'user_status');

        $user->roles = Sanitize::getVar($core_user,'roles');

        // Set group ids

        $user->gid = array();

        foreach($user->roles AS $role)
        {
            $role_id = null;

            if(isset($group_ids[$role]))
            {
                $user->gid = array_merge($user->gid, $group_ids[$role]);
            }
            elseif(isset($roles[$role]))
            {
                $user->gid[] = $role;
            }
        }

        if(empty($user->roles))
        {
            $user->gid = array(1);

            $user->roles[] = 'guest';
        }

        $user->block = 0;

        return $user;
    }

    public static function getUserViewLevels($user)
    {
        $viewLevels = array(
                'administrator'=>array(1,2,3),
                'editor'=>array(1,2),
                'author'=>array(1,2),
                'contributor'=>array(1,2),
                'subscriber'=>array(1,2),
                'guest'=>array(1),
                ''=>array(1)
            );

        $levels = array();

        foreach($user->roles AS $role)
        {
            // Unrecognized roles will automatically be assigned the same righs as subscribers

            if(!isset($viewLevels[$role]))
            {
                $viewLevels[$role] = $viewLevels['subscriber'];
            }

            $levels = array_merge($levels,$viewLevels[$role]);
        }

        return array_unique($levels);
    }

    public static function getACL()
    {
        $acl = JFactory::getACL();
        return $acl;
    }

    public static function getAccessLevelList()
    {
        return array(
                array('value'=>1, 'text'=>'Public'),
                array('value'=>2, 'text'=>'Registered'),
                array('value'=>3, 'text'=>'Special')
            );
    }

    public static function getAccessGroupsList($native = false)
    {
        /*
        Groupids reference
        2 - Registered
        3 - Author
        4 - Editor
        5 - Publisher
        6 - Manager
        7 - Administrator
        8 - Super Administrator
        */

        global $wp_roles;

        $groups = array();

        $roles = $wp_roles->get_names();

        if ($native)
        {
            foreach ($roles AS $id => $title)
            {
                $groups[] = array('value' => $id, 'text' => $title);
            }

            return $groups;
        }

        // For now we will continue using the same user group IDs from Joomla for the default groups in WordPress.
        // Any new roles added in WP will use the corresponding slug

        $defaults = array(
                        'administrator' => 7,
                        'author' => 3,
                        'contributor' => 2,
                        'editor' => 4,
                        'subscriber' => 2
                    );

        foreach($roles AS $id=>$name)
        {
            $new_id = isset($defaults[$id]) ? $defaults[$id] : $id;

            $groups[] = array('value'=>$new_id, 'text'=>$name);
        }

        array_unshift($groups, array('value'=>1,'text'=>'Guest'));

        return $groups;
    }

    public static function getDB()
    {
        global $wpdb;

        return $wpdb;
    }

    public static function getMail($html = true)
    {
        $mail = new S2Mail($html);

        return $mail;
    }

    public static function isAdmin()
    {
        return defined('MVC_FRAMEWORK_ADMIN');
    }

    public static function installPackage($file, $target = '')
    {
        $result = false;

        // Unzip the file to the target plugin folder

        if($result = self::packageUnzip($file, $target))
        {
            unlink($file);
        }

        if(file_exists($file)) unlink($file);

        return $result;
    }

    public static function packageUnzip($file,$target)
    {
        WP_Filesystem();

        $result = unzip_file($file, $target);

        return $result;
    }

    public static function getTemplate()
    {
        return JFactory::getApplication()->getTemplate();
    }

    public static function scriptLoaded($name)
    {
        $loaded = array();

        $query_vars = get_query_var('_jreviews',array());

        $scripts = array_keys(Sanitize::getVar($query_vars,'_head_custom',array()));

        foreach($scripts AS $script)
        {
            if(is_numeric($script) || $script == '') continue;

            $script = explode(',',$script);

            $loaded = array_merge($loaded, $script);
        }

        return in_array($name, $loaded);
    }

    public static function addScriptTag($html, $namespace = '', $inline = false)
    {
        if(!strstr($html, '</script>'))
        {
            $html = '<script type="text/javascript">'. $html . '</script>';
        }

        if($inline)
        {
            // Check to only output inline if it was not already output in the head

            $query_vars = get_query_var('_jreviews');

            if(!$query_vars)
            {
                $query_vars = array();
            }

            $custom = Sanitize::getVar($query_vars, '_head_custom', array());

            if(!in_array($namespace, array_keys($custom)))
            {
                echo $html;
            }
        }
        else {
            self::addCustomTag($html, $namespace);
        }
    }

    public static function addCustomTag($html, $namespace = '')
    {
        if(defined('MVC_FRAMEWORK_ADMIN') || did_action('wp_head'))
        {
            echo $html;
        }
        else {

            if($namespace != '' && is_string($namespace) && self::scriptLoaded($namespace)) return;

            $_namespace = is_array($namespace) ? implode(',',$namespace) : $namespace;

            $query_vars = get_query_var('_jreviews');

            if(!$query_vars)
            {
                $query_vars = array();
            }

            $custom = Sanitize::getVar($query_vars, '_head_custom');

            $custom[$_namespace] = trim($html);

            $query_vars['_head_custom'] = $custom;

            set_query_var('_jreviews', $query_vars);
        }
    }

    public static function addScriptDefer($url, $handle, $version = null)
    {
        $defer = true;

        if (strpos($_SERVER['HTTP_USER_AGENT'], 'MSIE 9.') !==false) {
            $defer = false;
        }

        $query_vars = get_query_var('_jreviews');

        if(!$query_vars)
        {
            $query_vars = array();
        }

        $defer_scripts = Sanitize::getVar($query_vars, '_defer_scripts');

        $defer_scripts[$url] = $url;

        $query_vars['_defer_scripts'] = $defer_scripts;

        set_query_var('_jreviews', $query_vars);

        self::addScript($url, $handle, $version, $defer = true);
    }

    public static function addScript($url, $handle, $version = null, $defer = false)
    {
        $url = self::processAssetUrl($url);

        wp_enqueue_script($handle, $url, array('jquery'), $version);
    }

    public static function addStyleSheet($handle, $url, $version = null)
    {
        $url = self::processAssetUrl($url);

        wp_enqueue_style($handle, $url, array(), $version);
    }

    static function processAssetUrl($url)
    {
        $pos = strpos($url, WWW_ROOT_REL);

        if (!strstr($url, WWW_ROOT))
        {
            if ($pos !== false) {
                $url = substr_replace($url, '/', $pos, strlen(WWW_ROOT_REL));
            }
        }

        return $url;
    }

    public static function getCharset()
    {
        return 'UTF-8';
    }

    public static function getConfig($var, $default = null)
    {
        $value = $default;

        switch($var)
        {
            case 'secret':

                $value = defined('_CMS_SECRET_KEY') ? _CMS_SECRET_KEY : NONCE_SALT;

                break;

            case 'host':

                $value = DB_HOST;

            break;

            case 'db':

                $value = DB_NAME;

            break;

            case 'user':

                $value = DB_USER;

            break;

            case 'password':

                $value = DB_PASSWORD;

            break;

            case 'dbprefix':

                $db = cmsFramework::getDB();

                $value = $db->prefix;

            break;

            case 'tmp_path':

                $value = rtrim(S2_TMP, DS);;

            break;

            case 'cache_path':

                $value = S2_CACHE_DATA;

                break;

            case 'sitename':

                $value = get_option('blogname');

            break;

            case 'mailfrom':

                $value = get_option('admin_email');

            break;

            case 'offset':

                $value = get_option('timezone_string');

                if($value == '')
                {
                    $value = get_option('gmt_offset');
                }

            break;

            case 'MetaDesc':

                return '';

            break;
        }

        return $value;
    }

    public static function setSessionVar($key,$var,$namespace)
    {
        if(!isset($_SESSION['__'.$namespace])) {

            $_SESSION['__'.$namespace] = array();
        }

        $_SESSION['__'.$namespace][$key] = $var;
    }

    public static function getSessionVar($key,$namespace)
    {
        $session = $_SESSION;

        return isset($_SESSION['__'.$namespace][$key]) ? $_SESSION['__'.$namespace][$key] : array();
    }

    public static function clearSessionVar($key,$namespace) {
        if(isset($_SESSION['__'.$namespace]) && $key != '') {
            unset($_SESSION['__'.$namespace][$key]);
        }
    }

    public static function clearSessionNamespace($namespace) {
        unset($_SESSION['__'.$namespace]);
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
            $args = func_get_args();

            $string = cmsFramework::getConfig('secret') . implode('',$args);
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
            if(is_array($param) && !empty($param))
            {
                // $param = is_array($param) ? array_filter($param) : false;

                // if(!empty($param))
                // {
                //     $tokens[] = array_map('strval', $param);
                // }
            }
            elseif (!empty($param)){

                $tokens[] = strval($param);
            }
        }

        sort($tokens);

        $string = serialize($tokens);

        if($string == '') return '';

        return $input ?
            '<input class="token-i" type="hidden" name="'.cmsFramework::getCustomToken($string).'" value="1" />'
            :
            cmsFramework::getCustomToken($string);
    }

    public static function getTokenInput($action = 'form-token')
    {
        return '<span class="jr_token jr_hidden"><input class="token-s" type="hidden" name="'.self::getToken($action).'" value="1" /></span>';
    }

    public static function getToken($action = 'form-token', $new = false)
    {
        $token = wp_create_nonce($action);

        return $token;
    }

    public static function getDateFormat($string = 'd F Y') {

        return $string;
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

    public static function dateOffset($date, $offset, $params = array())
    {
       if($date == '' || $date == NULL_DATE || $date == _NULL_DATE)
        {
            return $date;
        }

        $defaults = array('format'=>'Y-m-d H:i:s');

        $params = array_replace($defaults, $params);

        extract($params);

        $new = date($format, strtotime($date) + 3600 * $offset);

        return $new;
    }

    public static function dateLocalToUTC($date = 'now', $params = array())
    {
        $defaults = array('format'=>'Y-m-d H:i:s', 'tz'=>null);

        $params = array_replace($defaults, $params);

        if(is_null($params['tz']))
        {
            $tz = cmsFramework::getConfig('offset');

            if(is_numeric($tz))
            {
                return self::dateOffset($date, -$tz, $params);
            }
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

            if(is_numeric($tz))
            {
                return self::dateOffset($date, $tz, $params);
            }
        }

        return self::dateConvertTimeZone($date, 'UTC', $tz, $params);
    }

    public static function isRTL()
    {
        return (int) is_rtl();
    }

    public static function getIgnoredSearchWords()
    {
        $search_ignore = array();

        // $lang = JFactory::getLanguage();

        // if(method_exists($lang,'getIgnoredSearchWords'))
        // {
        //     return $lang->getIgnoredSearchWords();
        // }

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
        $locale = get_locale();
        return str_replace('-',$separator,$locale);
    }

    /**
    * Used for I18n in s2framework
    *
    */
    public static function locale()
    {
        $locale = get_locale();
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
        // return JHTML::_('list.images', $name, $active, $javascript, $directory);
    }

    public static function listPositions( $name, $active=NULL, $javascript=NULL, $none=1, $center=1, $left=1, $right=1, $id=false )
    {
        // return JHTML::_('list.positions', $name, $active, $javascript, $none, $center, $left, $right, $id);
    }

    /**
     * Check for Joomla/Mambo sef status
     *
     * @return unknown
     */
    public static function mosCmsSef() {
        return false;
    }

    public static function applyPageTitleFormat($title)
    {
        $title = str_replace('&amp;','&',$title);
        return $title;
    }

    public static function meta($type,$text,$inline = false)
    {
        if($text == '')
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

        $query_vars = get_query_var('_jreviews');

        if(!$query_vars) {

            $query_vars = array();
        }

        switch($type) {

            case 'title':

                // $text .= ' | ';

                $query_vars['title_seo'] = $text;

                set_query_var('_jreviews', $query_vars);

                break;

            case 'keywords':

            case 'description':

            default:

                self::addCustomTag('<meta name="' . $type . '" content="' . $text . '" />', $type);

            break;
        }
    }


    public static function noAccess($return = false)
    {
        $msg =  "You don't have enough access to view this page";

        if($return) {
            return $msg;
        }

        echo $msg;
    }

    public static function formatDate($date)
    {
        // return JHTML::_('date', $date );
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

            # If the site is in a folder make sure it is included in the url just once

            if($url_parts['path'] != '') {

                if(strcmp($url_parts['path'],substr($url,0,strlen($url_parts['path']))) !== 0) {

                    $url = rtrim($url_parts['path'],'/') . '/' . ltrim($url,'/');
                }

            }

            $url = $url_parts['scheme'] . '://' . $url_parts['host'] . $url;
       }

       return $url;
    }

     /**
    * Required in Joomla to gerate SEF URLs from the administration. Here for compatibility purposes.
    *
    * @param mixed $urls
    * @param mixed $xhtml
    * @param mixed $ssl
    */
    public static function siteRoute($urls, $xhtml = true, $ssl = null)
    {
        return $urls;
    }

    public static function route($url, $scheme = 'relative')
    {
        // Ugly fix for pagination issue when there's a list as the home page

        if($url == rtrim(WWW_ROOT,'/')) {
            $url = WWW_ROOT;
        }

        $pattern = array('/%5B(\d+)\%5D/','/%5B\%5D/','/%2C/');

        $replace = array('[]','[]',',');

        $url = self::reorderUrlParams($url, true);

        if(strstr($url, WWW_ROOT))
        {
            $url = preg_replace($pattern,$replace,$url);

            return $url;
        }

        $url = home_url($url);

        $url = preg_replace($pattern,$replace,$url);

        return $url;
    }

    public static function constructRoute($passedArgs, $excludeParams = null, $app = 'jreviews')
    {
        $segments = $url_param = array();

        $defaultExcludeParms = array(
                                'pagename',
                                'menu_alias',
                                'menu_title',
                                'format',
                                'view',
                                'language',
                                'lang',
                                'category_name',
                                'option',
                                'Itemid',
                                'id'
                                // 'index'
                                );

        if(!in_array(Sanitize::getString($passedArgs,'url'), array('categories/search','search-results')))
        {
            $defaultExcludeParms[] = 'cat';
        }

        $excludeParams = !empty($excludeParams) ? array_merge($excludeParams,$defaultExcludeParms) : $defaultExcludeParms;

        $arrayParams = array(S2_QVAR_RATING_AVG, S2_QVAR_EDITOR_RATING_AVG);

        $searchUrl = Sanitize::getString($passedArgs, 'search_url');

        unset($passedArgs['search_url']);

        if(defined('MVC_FRAMEWORK_ADMIN'))
        {
            $base_url = 'wp-admin/admin.php?page='.S2Paths::get($app, 'S2_CMSCOMP');
        }
        else {

            if ($searchUrl)
            {
                $parts = parse_url($searchUrl);
                $path = $parts['path'];
                $base_url = $parts['scheme'].'://'.$parts['host'].$parts['path'];
            }
            else {
                global $wp;

                $base_url = home_url( $wp->request ) ;
            }
        }

        unset($passedArgs['url']);

        if(is_array($excludeParams))
        {
            foreach($excludeParams AS $exclude) {

                unset($passedArgs[$exclude]);
            }
        }

        foreach($passedArgs AS $paramName=>$paramValue)
        {
            if(is_string($paramValue) && $paramValue!='')
            {
                $paramValue == 'order' and $paramValue = array_shift(explode('.html',$paramValue));

                $url_param[$paramName] = str_replace('+', ' ', urlencodeParam($paramValue));
            }
            elseif(is_array($paramValue) && in_array($paramName, $arrayParams)) {

                foreach($paramValue AS $key => $value)
                {
                    $url_param[$paramName . '[]'] = str_replace('+', ' ', urlencodeParam($value));
                }
            }
        }

        $params = '';

        foreach($url_param AS $key=>$val)
        {
            $params .= '/'. $key . _PARAM_CHAR . $val;
        }

        $new_route = rtrim($base_url,'/') . (!empty($url_param) ? $params : '');

        return $new_route;
    }

    public static function reorderUrlParams($url, $traditionalUrlParams = false)
    {
        $base_url = $url;

        preg_match_all('/\/([a-z0-9_%\\[\\]]+):([^\/]*)/i',$url,$matches);

        if(empty($matches[0])) return $url;

        foreach ($matches[0] AS $param)
        {
            $base_url = str_replace($param, '', $base_url);
        }

        $paramsArray = array_combine($matches[1],$matches[2]);

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

        foreach($orderArray as $key)
        {
            if(array_key_exists($key,$paramsArray))
            {
                $ordered[$key] = $paramsArray[$key];

                unset($paramsArray[$key]);
            }
        }

        $paramsArray = $ordered + $paramsArray;

        $permalink_setting = get_option('permalink_structure');

        $last = substr($permalink_setting, -1);

        $url = rtrim($base_url, '/') . ($last == '/' ? '/' : '') . '?' . http_build_query($paramsArray);

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

    public static function addCrumb($name, $link){}

    public static function UrlTransliterate($string)
    {
        return sanitize_title($string);
    }

    public static function StringTransliterate($string) {
        return sanitize_title($string);
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

    public static function raiseError($code, $text) {

        status_header($code);

        return $text;
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

        @header( 'Content-Type: application/json; charset=' . get_option( 'blog_charset' ) );

        return is_array($array) ? json_encode($array) : $array;
    }

    public static function logoutUrl($return= '/')
    {
        return wp_logout_url( $return );
    }

    public static function resetPasswordUrl()
    {
        return wp_lostpassword_url();
    }

    public static function checkPassword($password, $hash)
    {
        return wp_check_password($password, $hash);
    }

    public static function hashPassword($password)
    {
        return wp_hash_password($password);
    }


    public static function allowUserRegistration()
    {
        return self::getUserConfig('registration');
    }

    public static function getUserConfig($setting)
    {
        switch($setting)
        {
            case 'registration':

                return get_option('users_can_register');

                break;

            case 'activation':

                // WordPress doesn't have self activation in the core so we disable the post registration message
                // that says an activation email will be sent to complete the registration

                return 0;

                break;

            case 'admin_notification':

                // Valid for none or self activation

                return 1;

                break;

            case 'default_group':

                return get_option('default_role');

                break;

            case 'send_password':

                return 1;

                break;
        }

        return false;
    }

    public static function loginUser($username, $password)
    {
        $creds = array();
        $creds['user_login'] = $username;
        $creds['user_password'] = $password;
        $creds['remember'] = true;

        wp_signon( $creds, $secure_cookie  = false);
    }

    /**
     * Creates a new WordPress User
     * @param  array $data array('username','name','email','password')
     * @return [type]       [description]
     */
    public static function registerUser($data)
    {
        $user_login = trim(Sanitize::getString($data,'username'));

        $user_email = trim(Sanitize::getString($data,'email'));

        $user_pass = Sanitize::getString($data,'password');

        $name = trim(Sanitize::getString($data,'name'));

        $user_id = username_exists($user_login);

        if(!$user_id && email_exists($user_email) == false )
        {
            $user_pass = $user_pass ? $user_pass : wp_generate_password( $length = 12, $include_standard_special_chars = false );

            $name = explode(' ',$name);

            $first_name = array_shift($name);

            $last_name = !empty($name) ? implode(' ',$name) : '';

            // Only one role allowed per user
            $userGroups = Sanitize::getVar($data, 'user_groups', array());

            $role = array_shift($userGroups);

            $userData = compact('user_login','user_email','user_pass','first_name','last_name');

            if ($role)
            {
                $userData['role'] = $role;
            }

            $user_id = wp_insert_user($userData);

            if(is_wp_error($user_id))
            {
                return false;
            }

            // New user created

            else {

                // Send email with password

                wp_new_user_notification($user_id, $deprecated = null, $notify = 'both'); // admin | both

                return $user_id;
            }
        }

        return false;
    }

    static function getAdminUserManagerUrl()
    {
        // wp-admin/
        return 'users.php';
    }
}

/**
 * Mail wrapper class for WP
 */
class S2Mail
{
    var $html = true;

    var $Subject;

    var $Body;

    var $ErrorInfo;

    var $to = array();

    var $bcc = array();

    var $reply_to = array();

    function __construct($html)
    {
        add_filter('wp_mail_content_type', array( & $this, 'setHTMLContentType'));
    }

    function AddAddress($address)
    {
        $this->to[] = $address;
    }

    function AddBCC($address)
    {
        $this->bcc[] = $address;
    }

    function AddReplyTo($email, $name)
    {
        $this->reply_to = array($email, $name);
    }

    function ClearAddresses()
    {
        $this->to = array();
    }

    function ClearAllRecipients() {}

    function ClearReplyTos() {}

    function ClearBCCs()
    {
        $this->bcc = array();
    }

    function setHTMLContentType()
    {
        return 'text/html';
    }

    function Send()
    {
        $headers = array();

        if(!empty($this->bcc))
        {
            foreach($this->bcc AS $address)
            {
                $headers[] = 'Bcc: ' . $address;
            }
        }

        add_action( 'phpmailer_init', array($this,'clear_replytos') );

        $result = wp_mail($this->to, $this->Subject, $this->Body, $headers);

        if(!$result)
        {
            $this->ErrorInfo = 'There was a problem sending the notification';
        }

        remove_filter('wp_mail_content_type', array( & $this, 'setHTMLContentType'));

        return $result;
    }

    function clear_replytos( $phpmailer )
    {
        if(!empty($this->reply_to))
        {
            $phpmailer->clearReplyTos();

            $phpmailer->addReplyTo($this->reply_to[0], $this->reply_to[1]);
        }
    }

}