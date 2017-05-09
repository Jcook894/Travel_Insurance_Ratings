<?php
/**
 * @version       1.0.0 August 18, 2013
 * @author        ClickFWD https://www.jreviews.com
 * @copyright     Copyright (C) 2010 - 2013 ClickFWD LLC. All rights reserved.
 * @license       Proprietary
 *
 */
defined('_JEXEC') or die;

class JReviewsRouter extends JRouter {

    protected $uri;

    var $remove_article_id = 1;

    var $use_core_cat_menus = 1;

    var $use_jreviews_cat_menu_id = 1;

    var $replacements = array(
        'com_jreviews'=>'jreviews',
        'newlisting'=>'new',
        'viewallreviews'=>'reviews',
        'reviewdiscussions'=>'discussions/review',
        'preview'=>'preview',
        'photos'=>'photos',
        'videos'=>'videos',
        'searchresults'=>'search-results',
        'upload'=>'upload',
    );

    var $sef_suffix;

    var $sef_rewrite;

    var $suffix_str = '.html';

    var $exceptions = array();

    var $plugin;

    var $jmenu;

    var $request;

    var $languages;

    var $languagesSEF;

    var $langFilter;

    var $path;

    /**
     * Joomla version
     * @var string in format 3.6.2
     */
    var $version;

    var $query = array();

    public function __construct($params, $plugin, $app) {

        $this->plugin = & $plugin;

        $this->app = $app;

        $this->request = $this->app->input;

        $this->remove_article_id = $params->get('remove_article_id', 1);

        $this->use_core_cat_menus = $params->get('use_core_cat_menus', 0);

        $exceptions = trim(str_replace(' ','',$params->get('exceptions','com_quick2cart')));

        $this->exceptions = array_filter(array_merge($this->exceptions, explode(',', $exceptions)));

        $this->use_jreviews_cat_menu_id = $params->get('use_jreviews_cat_menu_id', 1);

        $this->replacements['com_jreviews'] = $params->get('replacement_com_jreviews', 'jreviews');

        $this->replacements['newlisting'] = $params->get('replacement_newlisting', 'new');

        $this->replacements['viewallreviews'] = $params->get('replacement_viewallreviews', 'reviews');

        $this->replacements['reviewdiscussions'] = $params->get('replacement_reviewdiscussions', 'discussions/review');

        $this->replacements['photos'] = $params->get('replacement_photos', 'photos');

        $this->replacements['videos'] = $params->get('replacement_videos', 'videos');

        $this->replacements['searchresults'] = $params->get('replacement_searchresults', 'search-results');

        if($this->use_core_cat_menus && !defined('JREVIEWS_SEF_PLUGIN'))
        {
            define('JREVIEWS_SEF_PLUGIN',1);
        }

        $JConfig = JFactory::getConfig();

        $this->sef_rewrite = $JConfig->get('sef_rewrite');

        $this->sef_suffix = $params->get('sef_suffix');

        $JVersion = new JVersion();

        $this->cmsVersion = $JVersion->RELEASE;

        $this->jmenu = $this->app->getMenu();

        // Init multilingual capability

        $this->langFilter = class_exists('plgSystemLanguageFilter') && method_exists($this->app, 'getLanguageFilter') && $this->app->getLanguageFilter();

        $this->languages = JLanguageHelper::getLanguages('lang_code');

        $this->languagesSEF = JLanguageHelper::getLanguages('sef');

        parent::__construct();
    }

    static function prx()
    {
        $vars = func_get_args();

        $output = '';

        foreach($vars AS $var) {

            $output .= '<pre>'.print_r($var,true).'</pre>';
        }

        echo $output;
    }

    static function getArrVal($array, $key, $default = null)
    {
        if(isset($array[$key])) {

            return $array[$key];
        }

        return $default;
    }

    protected function getPath()
    {
        $path = $this->uri->getPath();

        // Remove suffix if enabled

        if ($this->sef_suffix) {

            if ($suffix = pathinfo($path, PATHINFO_EXTENSION)) {

                $path = str_replace('.' . $suffix, '', $path);

                $this->uri->setPath($path);
            }
        }

        $this->path = $path;

        return $path;
    }

    protected function finalizeBuildRoute($route)
    {
        if(!$this->sef_rewrite)
        {
            $route = !strstr($route,'index.php') ? 'index.php/' . $route : $route;
        }

        $route = preg_replace('/(.*)'.$this->suffix_str.'$/','$1',$route);

        if($this->sef_suffix && $this->suffix_str != '' && $route != ''
            && $route !='/' && !stristr($route,'#') ) {

            $route = $route;//. $this->suffix_str;
        }

        return $route;
    }

    /**
     * Change the position of the language segment from last to first
     * Aug 9, 2016 - To fix issue with lang filter introduced in Joomla 3.6.x
     * @param  [type] &$router [description]
     * @param  [type] &$uri    [description]
     * @return [type]          [description]
     */
    public function postprocessBuildJReviews(&$router, &$uri)
    {
        $route = $uri->getPath();

        if ($this->langFilter)
        {
            $lang = $uri->getVar('lang');

            // Jan 19, 2017 - Added additional check because sometimes the above method to get the current
            // locale does not return anything

            if (!$lang)
            {
                $lang = $this->request->get('lang');
            }

            $langSef = isset($this->languages[$lang]) ? $this->languages[$lang]->sef : '';

            if (substr($route, -4, 4) == '/'.$langSef.'/')
            {
                // Remove the language segment from the end of the route (appended by the language filter plugin)

                $route = ltrim(substr($route, 0, strlen($route) - 4), '/');

                // Prepend the language segment if not alrady there

                if (substr($route, 0, 3) !== $langSef.'/')
                {
                    $route = '/'.$langSef.'/' . $route;
                }
            }
        }

        // If it's the home page we can remove the index.php

        if (!self::isAjax() && substr($route, -9, 9) == 'index.php')
        {
            $route = substr($route, 0, strlen($route) - 9);
        }

        $uri->setPath($route);
    }

    public function buildJReviews(& $router, & $uri)
    {
        $query = array_merge($uri->getQuery(true), $this->query);

        $route = $uri->getPath(); // This will be 'index.php' for the home page

        $url = $uri->toString();

        $menu = $joomla_cat_menu = $curr_page_menu = $is_joomla_cat_menu = $params = $action_id = $cat_menu_id = null;

        $curr_page_menu_id = $this->request->get('Itemid');

        if($curr_page_menu_id) {

            $curr_page_menu = $this->jmenu->getItem($curr_page_menu_id);
        }

        // Read URL query parameters

        $option = self::getArrVal($query,'option');

        $view = self::getArrVal($query,'view');

        $menu_id = self::getArrVal($query,'Itemid');

        $menu_id_url = $this->request->get('Itemid', 'INT');

        if($menu_id)
        {
            $menu = $this->jmenu->getItem($menu_id);

            if($menu && $menu->home == 1)
            {
                $query = $uri->getQuery(true);

                if (self::getArrVal($menu->query,'option') != 'com_jreviews')
                {
                    return $uri;
                }
                // For directory pages used as homepage we need to continue parsing the page further below to process correctly the alphaindex links

                elseif (self::getArrVal($menu->query,'view') != 'directory')
                {
                    unset($query['view']);

                    // If page limit is specified in a menu, it is also appearing in the URL even though it's not needed

                    if (isset($query['limit']) && $menu->params->get('limit_special') == $query['limit'])
                    {
                        unset($query['limit']);
                    }

                    $uri->setQuery($query);

                    return $uri;
                }
            }

            if($menu && $menu->type == 'alias')
            {
                $alias_menu_id = $menu->params->get('aliasoptions');

                $menu = $this->jmenu->getItem($alias_menu_id);
            }

            $menu and $action_id = (int) $menu->params->get('action');
        }

        $url_param = rtrim(self::getArrVal($query,'url'),'/');

        $action = self::getArrVal($query,'action');

        $cat_id_query = self::getArrVal($query,'cat');

        // Get category id from URL when a JReviews category menu doens't exit
        if($option == 'com_jreviews' && preg_match('/_c([0-9]+)/',$url_param,$matches)) {

            $cat_id_query = $matches[1];
        }

        $id = $id_backup = self::getArrVal($query,'id');

        $extension = self::getArrVal($query,'extension');

        $tmpl_param = JRequest::getVar('tmpl');

        $page_param = JRequest::getVar('page');

        $order_param = JRequest::getVar('order');

        $is_joomla_cat_url = false;

        /**
         * For JReviews category menus
         */
        /**
         * Skip this build method if conditions below are true
         * 1. It's a 3rd party extension URL
         * 2. It's a JReviews URL without a menu. Replace the component/jreviews segment and leave the rest the same
         * 3. It's an article menu
         */

        if(!in_array($option,array('com_content','com_jreviews'))
            // Content URL without a menu
            || ($option == 'com_content' && !$menu_id)
            // Article URL with a menu
            || ($menu && self::getArrVal($menu->query, 'option') == 'com_content' && in_array($menu->query['view'],array('article','featured')) && $url_param == '')
            // JReviews URL without a menu, as long as it is not a category URL and a Joomla category menu exists
            // And not
            || ($option == 'com_jreviews'
                    && $url_param != ''
                    && !$menu_id
                    && !$cat_id_query
                    && !$extension
                    && $url_param != 'listings/detail' // Not a view all reviews page
                    && !preg_match('/.*_l[0-9]+/',$url_param) // Not a view all reviews page
                    && !strstr($url_param,'.rss') // Not a feed
                    && $action != 'xml' // Not a feed
                )
            // Directory URL
            || ($option == 'com_jreviews' && preg_match('/_d([0-9]+)$/',$url_param, $matches_dir))
            // Category URL
            || ($option == 'com_jreviews' && preg_match('/_c([0-9]+)$/',$url_param, $matches_cat) && !$menu_id)
            // RSS URL in category page without menu
            || ($option == 'com_jreviews' && preg_match('/url=.*_c[0-9]+.*&action=xml|_c([0-9]+)\.rss/',$url,$matches_cat) && !$menu_id)
            )
        {
            $skip = false;

            // Special case for directory links in directory module. They inherit current page Itemid which may not be the correct one.
            if(!empty($matches_dir) && $menu_id)
            {
                $dir_id = $menu->params->get('dirid');

                if($dir_id == $matches_dir[1]) $skip = true;
            }

            // Special case for rss links in category pages
            if(!empty($matches_cat))
            {
                $cat_id_menu = $this->getJoomlaCatMenu($matches_cat[1]);

                if($cat_id_menu) {

                    $skip = true;
                }
            }

            if(!$skip && $option == 'com_jreviews' && $url_param != '')
            {
                $route = $this->replacements['com_jreviews'] . '/' . $url_param;

                unset($query['Itemid'],$query['option'], $query['url']);

                $uri->setQuery($query);

                $uri->setPath($this->finalizeBuildRoute($route));
            }

            if(!$skip && $menu && self::getArrVal($menu->query,'option') == 'com_content' && $url_param != '')
            {
                if($menu->query['view'] == 'article')
                {
                    $route = $this->finalizeBuildRoute($menu->route);

                    unset($query['Itemid'],$query['option']);
                }

                $uri->setQuery($query);

                $uri->setPath($route);
            }

            if(!$skip) return $uri;
        }

        /**
         * Re-write JReviews category URLs to native Joomla category URLs
         */
        if($option == 'com_jreviews')
        {
            /**************************************************************************************
             * If it's a home page menu
             *************************************************************************************/
            if($menu && $menu->home == 1)
            {
                $new_uri = new JURI($url);

                $new_query = $new_uri->getQuery(true);

                unset($new_query['option'],$new_query['Itemid']);

                if(empty($new_query)) {

                    return $uri;
                }
            }

            /**************************************************************************************
             * First we'll set the base route. After that we modify it depending on the type of URL
             *************************************************************************************/

            // It's a JReviews link with the menu id of a Joomla category

            if($menu && $menu->query['option'] != $option) {

                $is_joomla_cat_url = true;

                $cat_id = $cat_id_query > 0 ? $cat_id_query : $menu->query['id'];

                if($menu->query['option'] == 'com_content' && $menu->query['view'] == 'category') {

                    // Run the content router here so we can modify the segments

                    $query = array_merge($query,array(
                        'option'=>'com_content',
                        'view'=>'category',
                        'id'=>$cat_id,
                        'Itemid'=>$menu_id,
                        ));

                    require_once JPATH_SITE . '/components/com_content/router.php';

                    $segments = ContentBuildRoute($query);

                    $slug = str_replace(':','-',implode('/', $segments));

                    $route = $menu->route . ($slug != ''? '/' . $slug : '');

                    if($url_param == 'preview')
                    {
                        $query['id'] = $id_backup;
                    }

                    // Prevent the default content router from running again

                    unset($query['option'], $query['Itemid'], $query['url'], $query['cat']);
                }
            }

            // It's a JReviews category menu. Lets see if there's a Joomla category menu to replace the alias.

            elseif($menu_id && $action_id == 2) {

                $cat_id_menu = $menu->params->get('catid');

                if($cat_id_menu > 0
                        && $url_param != 'search_results' /* don't use category alias for search URLS*/ ) {

                    $joomla_cat_menu = $this->getJoomlaCatMenu($cat_id_menu);

                    if($this->use_core_cat_menus && $joomla_cat_menu) {

                        $route = $joomla_cat_menu->route; // Use the Joomla category route

                    }
                    else {

                        $route = $menu->route; // Use the JReviews category route

                        unset($query['cat']);
                    }
                }

                unset($query['Itemid'],$query['option'],$query['url']);
            }

            // Search URL - need to distinguish between click2search and a real search

            elseif($menu_id && $action_id == 11 && $url_param != '')
            {
                if(in_array($url_param,array('categories/category','categories/search')))
                {
                    $url_param = 'search-results';
                }
            }

            // Reviewer rank. If it has a user anchor, remove the 'menu' segment

            elseif($menu_id && $action_id == 18)
            {
                $route = str_replace('/menu','',$menu->route);

                unset($query['Itemid'],$query['option'],$query['url']);

                $uri->setPath($this->finalizeBuildRoute($route));

                $uri->setQuery($query);

                return $uri;
            }

            // JReviews category menu not found, so we find the Joomla category menu to display that instead

            elseif($cat_id_query && $url_param != 'search-results') {

                $joomla_cat_menu = $this->getJoomlaCatMenu($cat_id_query);

                if($joomla_cat_menu)
                {
                    $menu_id = $joomla_cat_menu->id;

                    $route = $route = $joomla_cat_menu->route;

                    unset($query['cat']);
                }
                else {

                    // Neither JReviews nor Joomla category menus found, so we use the 'com_jreviews' segment

                    $route = $this->replacements['com_jreviews'];
                }

                unset($query['Itemid'],$query['option'],$query['url']);
            }

            // It's a JReviews URL, but not a menu because it has parameters

            elseif(!$menu && $url_param != '') {

                $route = $this->replacements['com_jreviews'];

                unset($query['Itemid'],$query['option'],$query['url']);
            }

            // It's a JReviews menu

            elseif($menu) {

                $route = $menu->route;

                unset($query['Itemid'],$query['option'],$query['url']);

                // Remove extra 'view' param from JReviews URLs because we read it directly from the menu when parsing it

                if($menu->query['option'] == 'com_jreviews')
                {
                    unset($query['view']);
                }
            }

            /************************************************************************
             * We have the base route, now we add any additional segments required
             *************************************************************************/

            $patterns = array(
                'alphaindex',
                'discussions\/review',
                'new-listing',
                'rss',
                '^tag\/',
                '^preview$',
                '^photos$',
                'media\/listing',
                'media\/photoGallery',
                '^videos$',
                'media\/videoGallery',
                '^upload$',
                '^search-results$',
                'listings\/detail',
                'categories\/category',
                'categories\/search',
                '_l[0-9]+$',
            );

            preg_match('/'. implode('|',$patterns) .'/',$url_param,$matches);

            $page_type = !empty($matches) ? $matches[0] : '';

            if(!in_array($page_type,array('alphaindex')))
            {
                preg_match('/(?P<alias>.*)(?P<viewallreviews>_l)(?P<id>[0-9]+|)/',$url_param, $typematch);

                // The regex below was used to fix this bug, but breaks the view all reviews page for Everywhere Extensions (non com_content)
                // So it was commented again on Feb 4, 2016
                // preg_match('/(?P<alias>.*)(?P<viewallreviews>_l[^a-z]+)(?P<id>[0-9]+|)/',$url_param, $typematch);

                if($page_type != 'rss' && isset($typematch['viewallreviews'])) {

                    $page_type = 'viewallreviews';
                }
            }

            if($action == 'xml' && $page_type != 'search-results')
            {
                $page_type = 'rss';
            }

            switch($page_type)
            {
                case 'alphaindex':

                    preg_match('/alphaindex_([\p{L}\s0]{1})+/isu',$url_param, $matches);

                    $index = !empty($matches) ? $matches[1] : self::getArrVal($query, 'index', 0);

                    if ($menu->home == 1)
                    {
                        $route = 'index' . '/' . $index;
                    }
                    else {
                        $route = $menu->route . '/' . 'index' . '/' . $index;
                    }

                    unset($query['index'], $query['dir']);

                break;

                case 'discussions/review':

                    if($is_joomla_cat_url || $action_id == 17) {

                        $route .= '/' . $this->replacements['reviewdiscussions'];

                        $query['id'] = $id;
                    }
                    else {

                        $menu_discussions = $this->jmenu->getItems(array('link'),array('index.php?option=com_jreviews&view=discussions'),true);

                        if($route == $this->replacements['com_jreviews'] && $menu_discussions)
                        {
                            $route = $menu_tmp->route;
                        }

                        $route .= '/' . $url_param;
                    }

                break;

                case 'new-listing':

                    if($menu && $menu->query['option'] == 'com_jreviews' && $action_id === 0)
                    {
                        $route = $menu->route . '/' . $this->replacements['newlisting'] . '/'. $cat_id_query;
                    }
                    else {

                        $route .= '/' . $this->replacements['newlisting'];
                    }

                break;

                case 'categories/search':
                case 'search-results':

                     // With adv. search alias or custom Itemid parameter
                    if($menu && (
                        // Any JReviews menu should be a valid menu to anchor the search results URL
                        $menu->query['option'] == 'com_jreviews'
                        // $action_id == 11
                        // || (!$cat_id_query && $menu->query['option'] == 'com_jreviews')
                        // || ($cat_id_query && $menu->query['option'] == 'com_jreviews' && $menu->params->get('catid') != $cat_id_query)
                        || ($this->use_core_cat_menus && $menu->query['option'] == 'com_content' && $menu->query['view'] == 'category'))
                        )
                    {
                        $route = $menu->route . '/' . $this->replacements['searchresults'];
                    }
                    else {

                        $route = $this->replacements['com_jreviews'] . '/' . $this->replacements['searchresults'];
                    }

                    // Add back cat parameter

                    if($menu && (
                            !isset($query['cat'])
                            &&
                            ($cat_id_query
                            && $menu->query['option'] == 'com_content' && $menu->query['view'] == 'category'
                            && $menu->query['id'] != $cat_id_query)
                            ||
                            ($cat_id_query
                            && $menu->query['option'] == 'com_jreviews'
                            && $menu->params->get('catid') != $cat_id_query)
                            )
                        )
                    {
                        $query['cat'] = $cat_id_query;
                    }

                break;

                case 'tag/':

                    if($cat_id_query && $is_joomla_cat_menu && $cat_id_query != $menu->query['id'])
                    {
                        $route = $this->replacements['com_jreviews'];
                    }
                    elseif($cat_id_query && $is_joomla_cat_menu && $cat_id_query == $menu->query['id'])
                    {
                        unset($query['cat']);
                    }

                    $urlencoded_param = str_replace(' ',urlencode(' '),$url_param);

                    if($menu) {

                        $route = $menu->route . '/' . $urlencoded_param;
                    }
                    else {

                        $route .= '/' . $urlencoded_param;
                    }

                break;

                case 'rss':

                    // Listing detail page review feed
                    if(preg_match('/(?P<alias>.*)_l(?P<id>[0-9]+)_(?P<extension>com_[0-9a-z_]*)[.]rss/', $url_param, $matches))
                    {
                        $article_menu = null;

                        if(isset($matches['id']) && ($extension == 'com_content' || $extension == '')) {

                            $article_menu = $this->getJoomlaArticleMenu($matches['id']);
                        }

                        if($article_menu)
                        {
                            $route = $article_menu->route . '/rss';
                        }
                        elseif($extension == 'com_content') {

                            $route .=  '/' . $matches[1] .'/rss';

                            unset($query['extension']);
                        }
                        else {

                            if($matches['extension'] != 'com_content') {

                                $route .= '/' . $matches['id'] . '-' . $matches['alias'] . '/rss';

                                $query['extension'] = $matches['extension'];
                            }
                            else {

                                $route = str_replace(array(JURI::base(),'/rss','/'.$this->replacements['viewallreviews']),'',JURI::current()) . '/rss';

                                unset($query['extension']);
                            }
                        }
                    }

                    // Feeds in directory page with menu
                    elseif(($curr_page_menu && $action_id === 0 && $curr_page_menu->query['option'] == 'com_jreviews')
                            || ($route == $this->replacements['com_jreviews'] && isset($query['dir']))
                    )
                    {
                        if(strstr($url_param,'categories/latest'))
                        {
                            if($menu && $action_id === 0 && $menu->params->get('dirid') == $query['dir'])
                            {
                                $route = $menu->route . '/rss';
                            }
                            else {

                                $route = str_replace(JURI::base(),'',JURI::current()) . '/rss';
                            }
                        }
                        else {

                            $route .= '/rss/reviews';
                        }

                        unset($query['action'], $query['dir']);
                    }

                    // List page listing and review feeds

                    elseif(strstr($url_param,'.rss') || $action == 'xml')
                    {
                        if($action == 'xml')
                        {
                            $route .= '/rss';
                        }
                        else
                        {
                            $route = str_replace(JURI::base(),'',JURI::current()) . '/rss/reviews';
                        }

                        unset($query['action'], $query['dir'], $query['cat'], $query['order'], $query['page']);
                    }

                break;

                case 'categories/category':

                    unset($query['cat']);

                    $route = str_replace(JURI::base(),'',JURI::current());

                break;

                case 'listings/detail':

                    unset($query['id']);

                    $route = str_replace(JURI::base(),'',JURI::current());

                break;

                case 'preview':

                    $query['id'] = $id_backup;

                    $route .= '/' . $this->replacements['preview'];

                break;

                case 'media/listing':

                    $query['id'] = $id_backup;

                    if($action_id == 101)
                    {
                        $route .= '/media/listing';
                    }
                    else {

                        $route = $this->replacements['com_jreviews'] . '/media/listing';
                    }

                break;

                case 'photos':
                case 'media/photoGallery':

                    $route .= '/' . $this->replacements['photos'];

                break;

                case 'videos':
                case 'media/videoGallery':

                    $route .= '/' . $this->replacements['videos'];

                break;

                case 'upload':

                    $route .= '/' . $this->replacements['upload'];

                break;

                case 'viewallreviews':

                    $article_menu = null;

                    if(isset($typematch['id']) && ($extension == 'com_content' || $extension == '')) {

                        $article_menu = $this->getJoomlaArticleMenu($typematch['id']);
                    }

                    if($article_menu)
                    {
                        $route = $article_menu->route . '/' . $this->replacements['viewallreviews'];

                    }
                    // It's a view all reviews catch all menu
                    elseif($menu && $action_id == 105) {

                        $route .= '/' . $typematch['id'] . '-' . $typematch['alias'];
                    }
                    elseif($menu && $menu->query['option'] == 'com_content' && ($extension == '' || $extension == 'com_content')) {

                        // $route = $menu->route . '/' . preg_replace('/(\_l[0-9]+)/','',$url_param) . '/' . $this->replacements['viewallreviews'];

                        // April 6, 2016 - Replaced line above to fix incorrect view all reviews URLs in detail pages
                        // We want to maintain the exact structure of the current detail page listing URL and append the viewallreviews segment

                        // $route = self::getCurrentPageRoute() . '/' . $this->replacements['viewallreviews'];

                        // June 2, 2016 - Yet another fix
                        // The /reviews segment was being added twice to the view all reviews URL in the 'view all reviews page' for one listing

                        $route = self::getCurrentPageRoute();

                        // Jan 8, 2017 - Fix for sites using a URL suffix (i.e. html) where duplicatement review segments appeard in the URL

                        $route = str_replace($this->suffix_str, '', $route);

                        // Site is in a sub-directory
                        if (WWW_ROOT_REL != '/')
                        {
                            $route = str_replace(WWW_ROOT_REL, '', self::getCurrentPageRoute());
                        }

                    }
                    else {

                        $route .= '/' . $typematch['id'] . '-' . $typematch['alias'];

                        $query['extension'] = $extension;
                    }

                    // Only add the last segment if it's not already there. Required when the link is rendered in 'view all reviews' pages to avoid duplicate segments

                    if(0 !== strcmp('/' . $this->replacements['viewallreviews'], substr($route, -strlen('/' . $this->replacements['viewallreviews']))))
                    {
                        $route .= '/' . $this->replacements['viewallreviews'];
                    }

                break;

                default:

                    if($url_param && !$is_joomla_cat_url) {

                        $route .= '/' . $url_param;

                    // self::prx($url);
                    // self::prx($url_param);
                    }

                break;
            }

            unset($query['option'], $query['Itemid'], $query['url']);
        }
        elseif($menu && $option == 'com_content' && $view == 'article' && self::getArrVal($menu->query, 'option') == 'com_content'
            // && !$menu->query['view'] == 'article'
            )
        {
            // Run the content router here so we can modify the segments

            require_once JPATH_SITE . '/components/com_content/router.php';

            $route_class = 'ContentBuildRoute';

            $segments = $route_class($query);

            $last = array_pop($segments);

            if($last != '')
            {
                if($this->remove_article_id)
                {
                   if(version_compare($this->cmsVersion, '3.3', '>='))
                    {
                        $alias_parts = explode('-', $last);

                        $article_id = array_shift($alias_parts);

                        $slug = implode('-', $alias_parts);
                    }
                    else {

                        list($article_id,$slug) = explode(':',$last);
                    }
                }
                else {

                    $slug = str_replace(':','-',$last);
                }

                // There are subcategories without menus

                if(!empty($segments)) {

                    foreach($segments AS $key=>$val)
                    {
                        $segments[$key] = str_replace(':','-',$val);
                    }
                }

                $segments[] = $slug;

                $route = $menu->route . '/' . implode('/', $segments);

                // Prevent the default content router from running again

                unset($query['option'],$query['view'],$query['Itemid']);
            }
        }
        elseif($menu) {

            $route = 'index.php';
        }

        // Check if this is XMAP running to exclude the tmpl parameter
        $XMAP = $this->request->get('option') == 'com_xmap' && $this->request->get('view') == 'xml';

        if(empty($_POST) && !self::isAjax() && !$XMAP && $tmpl_param != '' && ($page_param > 0 || $order_param != '')) {

            $query['tmpl'] = $tmpl_param;
        }

        if($this->langFilter
            && $menu
            && $menu->language && isset($this->languages[$menu->language])
            && substr($route, 0, strlen($this->languages[$menu->language]->sef) + 1) !== $this->languages[$menu->language]->sef . '/')
        {
            // $route = $this->languages[$menu->language]->sef . '/' . $route;
        }
        else {

            // Aug 9, 2016 - Changes to fix lang filter issues after Joomla 3.6.x

            $lang = $this->request->get('lang');

            $langSef = isset($this->languages[$lang]) ? $this->languages[$lang]->sef : '';

            if(preg_match('/^index.php\/'.$langSef.'/',$url)
                && !preg_match('/^index.php\/'.$langSef.'/',$route)
                && substr($route, 0, strlen($langSef) + 1) !== $langSef . '/')
            {
                $route = $langSef . '/' . $route;
            }
        }

        // Fixes issue with ampersand in keyword when performing a search where the pagination links include the unencoded ampersand

        if(isset($query['keywords']))
        {
            $query['keywords'] = str_replace('&','%26',$query['keywords']);
        }

        $uri->setPath($this->finalizeBuildRoute($route));

        $uri->setQuery($query);

        return $uri;
    }

    protected function setActiveMenu($path)
    {
        if($path == '') return false;

        $menu = $this->jmenu->getItems(array('route'),array($path),true);

        if(!$menu) {

            $segments = explode('/', $path);

            array_pop($segments);

            $path = implode('/', $segments);

            if($path != '') {

                $this->setActiveMenu($path);
            }
        }

        else {

            $this->jmenu->setActive($menu->id);
        }
    }

    public function &parseJReviews(&$siteRouter)
    {
        list($siteRouter, $uri) = func_get_args();

        $this->uri = $uri;

        $vars = array();

        $id = $joomla_cat_menu = $page_type = null;

        $query = $uri->getQuery(true);

        $option = self::getArrVal($query, 'option');

        if(!empty($query) && $option != '' && in_array($option, $this->exceptions))
        {
            $vars = $this->returnVars($vars);

            return $vars;
        }

        $query_string = $uri->getQuery();

        $path = $path_orig = $this->getPath();

        $this->setActiveMenu($path);

        $menu = $this->jmenu->getActive();

        $action_id = null;

        $view = self::getArrVal($query,'view');

        $task = self::getArrVal($query,'task');

        $layout = self::getArrVal($query,'layout');

        $extension = self::getArrVal($query,'extension','com_content');

        $segments = explode('/',$path);

        // Fix for sites where not having an Itemid forces the $JMenu->getActive() method to default to the home page

        if(!empty($segments) && $segments[0] == $this->replacements['com_jreviews']) $menu = null;

        if($menu && self::getArrVal($menu->query,'option') == 'com_jreviews')  {

            $action_id = (int) $menu->params->get('action');
        }

        $ignored_action_ids = array(
            // 19, // ('paidlistings','myaccount')
            20, // ('paidlistings_plans','index')
            200 // ('widgetfactory','index')
        );

        // If this is a post request, the home page then let Joomla handle it
        if(
            // Aug 24, 2016 - Re-enabled this condition for Joomla 3.6.2 after site login requests started failing
            // Sep 20, 2016 - second condition checking for 'option' key is to make sure it's a valid post request because the Siteground SuperCache plugin injects a post variable on GET requests!
            (!empty($_POST) && isset($_POST['option']) && version_compare($this->version, '3.6.1', '>='))
                ||
                // Home page
                empty($path)
                ||
                $path == 'index.php'
                // A separator menu
                || ($menu && !isset($menu->query['option']))
                // Any non-content,non-jreviews url menu
                || ($menu && !in_array($menu->query['option'],array('com_content','com_jreviews')))
                // Any content menu that is not 'article' or 'category'
                || ($menu && $menu->query['option'] == 'com_content' && !in_array($menu->query['view'],array('article','category')))
                // Any non-content,non-jreviews url without a menu
                || ($menu && $menu->query['option'] == 'com_content' && (!in_array($menu->query['view'],array('article','category')) || $layout == 'edit' || $task != ''))
                // Or any non-menu content URL that has a 'task' parameter in it and is not an ugly article URL (without a menu)
                || (self::getArrVal($segments,0) == 'component' && self::getArrVal($segments,1) == 'content' && self::getArrVal($segments,2) != 'article' && !in_array($view,array('article','category')))
                // Or any non-menu, non-content and non-jreviews URL
                || (self::getArrVal($segments,0) == 'component' && !in_array(self::getArrVal($segments,1),array('content','jreviews')))
                // Any url that matches a menu route exactly and that is not com_content, except article menus,
                // because we need to process extra segments
                || ($menu && $menu->query['option'] == 'com_jreviews' && $menu->route == $path && $action_id != 2)
                // Article menu
                || ($menu && $menu->query['option'] == 'com_content' && $menu->query['view'] == 'article' && $path == $menu->route)
                // Any JReviews menu with the action ids below should not be processed through the SEF Plugin
                || ($menu && $menu->query['option'] == 'com_jreviews' && in_array($action_id,$ignored_action_ids))
                // Any URL with format = feed and type rss is handled by Joomla
                || (self::getArrVal($query,'format') == 'feed' && self::getArrVal($query,'type') == 'rss')
            ) {

            $vars = $this->returnVars($vars, $path);

            return $vars;
        }

        // First remove any of the extra segments added for additional functionaly: new, feeds, index

        $replacements = str_replace('/','\/',$this->replacements);

        $patterns_simple = array(
            $replacements['reviewdiscussions'],
            'my-reviews',
            $replacements['newlisting'].'$',
            'rss$',
            'rss\/reviews',
            'media\/listing',
            'listings\/edit',
            $replacements['preview'],
            $replacements['photos'],
            $replacements['videos'],
            $replacements['searchresults'],
            $replacements['viewallreviews'],
            $replacements['upload']
        );

        $first = reset($segments);

        if(preg_match('/\/('.implode('|',$patterns_simple).')$|(tag)\/|(index(?!.php))|(index(?!.php))\/|('.$replacements['newlisting'].'$|rss$)/', $path, $matches))
        {
            $page_type = array_pop($matches);

            // Fix for alphaindex links in the home page when directory menu is homepage menu

            if(version_compare($this->version, '3.6.1', '<') && preg_match('/^index\/([\p{L}\s0]{1}).*/isu',$path))
            {
                $menu = $this->jmenu->getItems(array('home'),array(1),true);
            }

            $path = preg_replace('/\/' . str_replace('/','\/',$page_type) . '$/','',$path);

            $path = preg_replace('/\/' . str_replace('/','\/',$page_type) . '\//','/',$path);
        }

        $segments = explode('/',$path);

        // Some further checking of page type
        if(!$page_type)
        {
            switch($action_id)
            {
                case 105:
                    $page_type = $this->replacements['viewallreviews'];
                break;
            }
        }

        /**
         * If it's a Joomla article menu and the $page_type is empty, then let Joomla deal with it
         */
        if($menu && $menu->query['option'] == 'com_content' && $menu->query['view'] == 'article' && $page_type == '')
        {
            $vars = $this->returnVars($vars);

            return $vars;
        }

        // JReviews URL without a menu

        if($first == $this->replacements['com_jreviews'] && !in_array($page_type,array('rss')))
        {
            $segments[0] = 'component/jreviews';

            $path = implode('/',$segments);

            $vars['url'] = implode('/',array_slice($segments, 1));

            $uri->setPath($path);

        }
        // The URL is an exact match to a menu alias

        elseif(
            (count($segments) == 1 && $menu && $menu->query['option'] == 'com_content' && $menu->query['view'] == 'category')
            ||
            ($menu && $menu->route == $path_orig)
            // ||
            // ($menu && $menu->query['option'] == 'com_jreviews' && $action_id === 0)
            ) {

            // Don't do any special processing for non-JReviews categories

            if($menu->query['option'] == 'com_content' && $menu->query['view'] == 'category' && !$this->isJReviewsCategory($menu->query['id']))
            {
                $vars = $this->returnVars($vars);

                return $vars;
            }

            if($this->use_core_cat_menus) {

                // If it's a JReviews category we redirect to the Joomla equivalent if found

                $this->jreviewsCatRedirect($menu);
            }

            // If it's a JReviews non-category menu, then there's no need for further processing
            if(count($segments) == 1 && $menu && $menu->query['option'] == 'com_jreviews' && $action_id != 2)
            {
                $vars = $menu->query;

                return $this->returnVars($vars);
            }

            unset($menu->query['layout']); // It gets confused with the JReviews theme suffix

            $vars = $menu->query;
        }

        // Use native content router when the URL structure cannot be matched directly to a menu

        elseif((count($segments) > 3 && $segments[0] == 'component' && $segments[1] == 'content' && $segments[2] == 'article')
            ||
            ($menu && $menu->query['option'] == 'com_content'
             && !in_array($page_type,array('tag','preview','discussions/review'))
            )) {

            /**
             * It's an Article URL without a menu and without JReviews segments appended for feeds and view all reviews
             * We check if there's a direct category menu created it for it to redirect, otherwise we let the Joomla router deal with it
             */
            if(count($segments) > 3 && $segments[0] == 'component' && $segments[1] == 'content' && $segments[2] == 'article')
            {
                $this->redirectUglyContentArticleURL($page_type);

                if(!in_array($page_type,array('rss',$this->replacements['viewallreviews'])))
                {
                    $vars = $this->returnVars($vars);

                    return $vars;
                }
                else {

                    $content_segments = $this->removeMenuRouteSegments($segments);

                    $vars['view'] = 'article';

                    $last = array_pop($content_segments);

                    $last = preg_replace('/^([0-9]+)(-)(.*)/','$1:$3',$last);

                    $vars['id'] = (int) $last;

                    $vars['catid'] = (int) array_shift($content_segments);
                }
            }

            require_once JPATH_SITE . '/components/com_content/router.php';

            if($menu && $menu->query['option'] == 'com_content' && $menu->query['view'] == 'category')
            {
                if($this->remove_article_id && !in_array($page_type,array('rss/reviews',$this->replacements['newlisting']))) {

                    $segments = $this->addBackID($menu, $segments, $page_type);
                }

                /**
                 * If the current route, minus extra segments, matches exactly an existing menu then
                 * we use the $menu->query array to populate $vars and there's no need for further processing
                 */
                if(implode('/',$segments) == $menu->route) {

                    $vars = $menu->query;
                }
                else
                {
                    $last = array_pop($segments);

                    // Joomla 3.3 doesn't need the hyphen converted to a colon

                    if(version_compare($this->cmsVersion, '3.3', '<'))
                    {
                        $last = preg_replace('/^([0-9]+)(-)(.*)/','$1:$3',$last);
                    }

                    /**
                     * There's a menu matching the exact URL route, excluding the last segment which could be
                     * the article alias or subcategories without menus
                     */
                    if(implode('/',$segments) == $menu->route)
                    {
                        $content_segments = array($last);
                    }
                    else
                    {
                        $content_segments = $this->removeMenuRouteSegments($segments);

                        $content_segments[] = $last;
                    }

                    $vars = ContentParseRoute($content_segments);
                }

                // If it's a subcategory page without a menu we check if it's a JReviews category

                if(self::getArrVal($vars,'view') == 'category' && (int) self::getArrVal($vars,'id') > 0)
                {
                    if(!self::isJReviewsCategory($vars['id']))
                    {
                        $vars = $this->returnVars($vars);

                        return $vars;
                    }
                    // Force the display of a JReviews category page unless it's a feed, new listing, etc. page
                    // that is borroing the category alias
                    elseif($page_type == '') {

                        $page_type = 'category';
                    }
                }
            }

            // It's a Joomla Article menu with additional segments
            elseif($menu && $menu->query['option'] == 'com_content' && $menu->query['view'] == 'article')
            {
                $vars = $menu->query;
            }

            /**
             * Successfully parsed as Article. We are done, return $vars
             */
            if($vars['view'] == 'article' && $vars['id'] > 0 && isset($vars['catid']) && $vars['catid'] > 0
                && !in_array($page_type,array('rss',$this->replacements['viewallreviews'])))
            {
                $canonical_path = $this->getPath();

                $this->setCanonical($canonical_path);

                $vars = $this->returnVars($vars);

                return $vars;
            }

            // It's category based menu, with one extra segment, but and no article id was found

            if($menu && $menu->query['option'] == 'com_content' && $menu->query['view'] == 'category')
            {
                if($vars['view'] == 'article' && $vars['id'] == '0')
                {
                    return JError::raiseError(404, JText::_('JERROR_LAYOUT_PAGE_NOT_FOUND'));
                }
            }
        }

        // Category page or menu based pages

        $menu_query_view = $menu ? self::getArrVal($menu->query,'view') : '';

        $menu_query_option = $menu ? self::getArrVal($menu->query,'option') : '';

        if($menu
            && (
            ($menu_query_option == 'com_content' && $menu_query_view == 'category')
            ||
            ($menu_query_option == 'com_content' && $menu_query_view == 'article')
            ||
            ($menu_query_option == 'com_jreviews')
            // ($menu_query_option == 'com_jreviews' && $action_id == 2) /* category */
            // ||
            // ($menu_query_option == 'com_jreviews' && $action_id === 0) /* directory */
            // ||
            // ($menu_query_option == 'com_jreviews' && $action_id == 11) /* adv. search */
            // ||
            // ($menu_query_option == 'com_jreviews' && $action_id == 101) /* media catch-all */
            )) {

            if($menu_query_view != 'article' ||
                ($menu_query_view == 'article' && in_array($page_type,array('rss',$this->replacements['viewallreviews'])))) {

                $vars['option'] = 'com_jreviews';
            }
            // Need to set the Itemid using the menu id of the Joomla category menu
            // for proper module assignments to Joomla category pages

            $vars['Itemid'] = $menu->id;

            $id = self::getArrVal($vars,'id');

            if($id == 0) unset($vars['id']);

            $last = end($segments);

            if($menu_query_option == 'com_jreviews' && (
                $action_id == 2
                ||
                ($action_id === 0 && preg_match('/_c(?P<catid>[0-9]+)/',$last,$cat_matches))
            ))
            {
                // JReviews Category URL without a menu
                if(isset($cat_matches['catid'])) {

                    $vars['cat'] = $cat_matches['catid'];

                    $page_type = '';
                }
                else {

                    $vars['cat'] = $menu->params->get('catid');
                }
            }
            elseif($id > 0) {

                $vars['cat'] = $vars['id'];
            }
            elseif(isset($menu->query['id'])) {

                $vars['cat'] = $menu->query['id'];
            }

            if(isset($vars['cat']) && $menu_query_option == 'com_content' && $menu_query_view == 'category')
            {
                $jreviews_cat_menu = $this->use_jreviews_cat_menu_id ? $this->getJReviewsCatMenu($vars['cat']) : false;

                if($jreviews_cat_menu)
                {
                    $vars['Itemid'] = $jreviews_cat_menu->id;
                }
            }

            // Avoid missing template issue when base menu is a Joomla blog category menu. It overrides the 'layout' parameter in JReviews
            // that is used to set the Theme Suffix.

            if($menu_query_option == 'com_content' && $menu_query_view == 'category')
            {
                if(self::getArrVal($menu->query,'layout') == 'blog' && self::getArrVal($vars,'layout') == 'blog') unset($vars['layout']);
            }

            switch($page_type) {

                case 'index':

                    $path = $this->getPath();

                    preg_match('/index\/([\p{L}\s0]{1}).*/isu',$path,$matches);

                    $dir_id = $menu->params->get('dirid');

                    $vars['dir'] = $dir_id;

                    $vars['url'] = 'categories/alphaindex';

                    $vars['index'] = $matches[1];

                break;

                case $this->replacements['reviewdiscussions']:

                    // Aug 23, 2016 - Need to set the 'id' sepecifically because Joomla overrides it with the base menu 'id' parameter if one exists for
                    // the type of menu page

                    if ($id = self::getArrVal($query, 'id'))
                    {
                        $vars['id'] = $id;
                    }

                    $vars['url'] = 'discussions/review';

                break;

                case 'media/listing':

                    // Aug 23, 2016 - Need to set the 'id' sepecifically because Joomla overrides it with the base menu 'id' parameter if one exists for
                    // the type of menu page

                    if ($id = self::getArrVal($query, 'id'))
                    {
                        $vars['id'] = $id;
                    }

                    $vars['url'] = 'media/listing';

                break;

                case 'listings/edit':

                    // Aug 23, 2016 - Need to set the 'id' sepecifically because Joomla overrides it with the base menu 'id' parameter if one exists for
                    // the type of menu page

                    if ($id = self::getArrVal($query, 'id'))
                    {
                        $vars['id'] = $id;
                    }

                    $vars['url'] = 'listings/edit';

                break;

                case $this->replacements['newlisting']:

                    if($action_id === 0) {

                        $vars['cat'] = (int) $last;
                    }

                    $vars['url'] = 'listings/create';

                break;

                case 'rss':

                    if($menu_query_option == 'com_content' && self::getArrVal($vars,'view') == 'article') {

                        unset($vars['cat'], $vars['catid']);

                        $vars['url'] = 'feeds/reviews';
                    }
                    /* Directory menu */
                    elseif($menu_query_option == 'com_jreviews' && $action_id === 0)
                    {
                        $vars['dir'] = $menu->params->get('dirid');

                        $vars['url'] = 'categories/latest';

                        $vars['action'] = 'xml';
                    }
                    else {

                        // ClickFWD - May 21, 2015 - Added conditional because othewise all feeds display the same listings

                        if(self::getArrVal($vars, 'view') == 'category')
                        {
                            $vars['url'] = 'categories/category';
                        }

                        $vars['action'] = 'xml';

                        unset($vars['id']);
                    }

                    unset($vars['view']);

                break;

                case 'rss/reviews':

                    $vars['url'] = 'feeds/reviews';

                    unset($vars['id']);

                    if($menu_query_option == 'com_jreviews' && $action_id === 0)
                    {
                        $vars['dir'] = $menu->params->get('dirid');
                    }

                break;

                case $this->replacements['viewallreviews']:

                    unset($vars['cat']);

                    if($menu && $menu_query_option == 'com_jreviews') {

                        $vars['id'] = (int) end($segments);
                    }

                    $vars['url'] = 'listings/detail';

                break;

                case 'tag':

                    $value = array_pop($segments);

                    $field = array_pop($segments);

                    $vars['url'] = 'tag/' . $field . '/' . $value;

                    if($this->use_core_cat_menus) {

                        // If it's a JReviews category we redirect to the Joomla equivalent if found

                        $this->jreviewsCatRedirect($menu, $vars['url']);
                    }

                break;

                case $this->replacements['preview']:

                    // Aug 23, 2016 - Need to set the 'id' sepecifically because Joomla overrides it with the base menu 'id' parameter if one exists for
                    // the type of menu page

                    if ($id = self::getArrVal($query, 'id'))
                    {
                        $vars['id'] = $id;
                    }

                    $vars['url'] = 'com_content/com_content_view';

                    $vars['preview'] = 1;

                break;

                case $this->replacements['photos']:

                    unset($vars['view'],$vars['layout'],$vars['id']);

                    $vars['url'] = 'media/photoGallery';

                break;

                case $this->replacements['videos']:

                    unset($vars['view'],$vars['layout'],$vars['id']);

                    $vars['url'] = 'media/videoGallery';

                break;

                case $this->replacements['searchresults']:

                    $vars['url'] = 'categories/search';

                break;

                case $this->replacements['upload']:

                    // Aug 23, 2016 - Need to set the 'id' sepecifically because Joomla overrides it with the base menu 'id' parameter if one exists for
                    // the type of menu page

                    if ($id = self::getArrVal($query, 'id'))
                    {
                        $vars['id'] = $id;
                    }

                    $vars['url'] = 'media_upload/create';

                break;

                case 'category':

                    $vars['url'] = 'categories/category';

                break;

                case '':

                    if(!empty($segments) && count($segments) > 2)
                    {
                        // Deal with borrowed menu aliases. The additional segments are used as the JReviews url parameter

                        $segments_cp = $segments;

                        $route = '';

                        foreach($segments AS $segment)
                        {
                            array_shift($segments_cp);

                            $route .= '/' . $segment;

                            if('/' . $menu->route == $route)
                            {
                                $vars['url'] = implode('/', $segments_cp);
                            }
                        }
                    }

                    if(empty($vars['url']))
                    {
                        if(isset($vars['cat'])) unset($vars['id']);

                        $vars['url'] = 'categories/category';
                    }

                break;
            }
        }

        // There's no menu
        else {

            $vars['option'] = 'com_jreviews';

            switch($page_type)
            {
                case 'index':

                    $path = $this->getPath();

                    preg_match('/(.*)\/index\/([\p{L}\s0]{1}).*/isu',$path,$matches);

                    if(!$matches) continue;

                    $menu = $this->jmenu->getItems(array('route'),array($matches[1]),true);

                    $dir_id = $menu->params->get('dirid');

                    if($action_id == 0) {

                        $vars['dir'] = $dir_id;
                    }

                    $vars['Itemid'] = $menu->id;

                    $vars['url'] = 'categories/alphaindex';

                    $vars['index'] = $matches[2];

                break;

                case 'media/listing':

                    // Aug 23, 2016 - Need to set the 'id' sepecifically because Joomla overrides it with the base menu 'id' parameter if one exists for
                    // the type of menu page

                    if ($id = self::getArrVal($query, 'id'))
                    {
                        $vars['id'] = $id;
                    }

                    $vars['url'] = 'media/listing';

                break;

                case 'listings/edit':

                    // Aug 23, 2016 - Need to set the 'id' sepecifically because Joomla overrides it with the base menu 'id' parameter if one exists for
                    // the type of menu page

                    if ($id = self::getArrVal($query, 'id'))
                    {
                        $vars['id'] = $id;
                    }

                    $vars['url'] = 'listings/edit';

                break;

                case 'my-reviews':

                    $vars['url'] = 'reviews/myreviews';

                break;

                case $this->replacements['preview']:

                    // Aug 23, 2016 - Need to set the 'id' sepecifically because Joomla overrides it with the base menu 'id' parameter if one exists for
                    // the type of menu page

                    if ($id = self::getArrVal($query, 'id'))
                    {
                        $vars['id'] = $id;
                    }

                    $vars['url'] = 'com_content/com_content_view';

                    $vars['preview'] = 1;

                break;

                case $this->replacements['viewallreviews']:

                    if(!isset($last)) $last = array_pop($segments);

                    $vars['url'] = 'listings/detail';

                    $vars['id'] = (int) $last;

                    $query['extension'] = $extension;

                break;

                case 'rss':

                    if(!isset($last)) $last = array_pop($segments);

                    //  Latest listings feed for directory url without menu

                    if(self::getArrVal($vars,'view') == 'article')
                    {
                        $vars['url'] = 'feeds/reviews';

                        unset($vars['view'], $vars['catid']);
                    }
                    elseif(preg_match('/_d(?P<dirid>[0-9]+)$/',$last,$matches_dir)) {

                        if(isset($matches_dir['dirid'])) {

                            $vars['dir'] = $matches_dir['dirid'];

                            $vars['url'] = 'categories/latest';

                            $vars['action'] = 'xml';
                        }
                    }
                    elseif($extension != 'com_content') {

                        // Everywhere extension detail page review feeds
                        $vars['id'] = (int) $last;

                        $vars['url'] = 'feeds/reviews';

                        $query['extension'] = $extension;

                    }
                    elseif($menu && $menu->query['view'] == 'category') {

                        $vars['Itemid'] = $menu->id;

                        $vars['action'] = 'xml';
                    }

                break;

                case 'rss/reviews':

                    if(preg_match('/_d(?P<dirid>[0-9]+)$/',$last,$matches_dir)) {

                        if(isset($matches_dir['dirid'])) {

                            $vars['dir'] = $matches_dir['dirid'];

                            $vars['url'] = 'feeds/reviews';
                        }
                    }

                break;

                case 'tag':

                    $value = array_pop($segments);

                    $field = array_pop($segments);

                    $vars['url'] = 'tag/' . $field . '/' . $value;

                break;

                case $this->replacements['upload']:

                    // Aug 23, 2016 - Need to set the 'id' sepecifically because Joomla overrides it with the base menu 'id' parameter if one exists for
                    // the type of menu page

                    if ($id = self::getArrVal($query, 'id'))
                    {
                        $vars['id'] = $id;
                    }

                    $vars['url'] = 'media_upload/create';

                break;

                default:

                    if($page_type) {

                        $vars['url'] = $page_type;
                    }
                    elseif(isset($last) && $last !='')
                    {

                        $vars['url'] = $last;
                    }

                break;
            }
        }

        if(count($vars) == 1 && isset($vars['option']) && $vars['option'] == 'com_jreviews')
        {
            // Returning 404 so that JReviews-based home pages with made up segments don't return a valid page

            if($this->jmenu->getDefault()->component == 'com_jreviews')
            {
                return JError::raiseError(404, JText::_('JERROR_LAYOUT_PAGE_NOT_FOUND'));
            }

            // If not a JReviews menu, let 3rd party extensions deal with it

            $vars = array();

            $vars = $this->returnVars($vars);

            return $vars;
        }

        $canonical_path = $this->getPath();

        $this->setCanonical($canonical_path);

        //Set the route
        $uri->setPath('');

        $query_string = JURI::buildQuery($vars) . ($query_string != '' ? '&' . $query_string : '');

        $uri->setQuery($query_string);

        $vars = $this->returnVars($vars);

        return $vars;
    }

    protected function returnVars(& $vars)
    {
        // August 22, 2016 - Joomla 3.6.2 fix for URLs without Itemids
        // By forcing the Itemid to zero we prevent Joomla from assigning the homepage Itemid
        $activeMenu = $this->jmenu->getActive();

        if ( version_compare($this->version, '3.6.1', '>=')
            // Only for pages that are not the home page
            && $this->path != ''
            // Only if the Itemid is not set
            && !isset($vars['Itemid'])
            // Only do this when the active menu has been identified as the home page because this is what Joomla is doing for pages without Itemids
            && $activeMenu && $activeMenu->home == 1
            )
        {
            $vars['Itemid'] = 0;
        }

        // August 9, 2016 - Fix for language filter plugin for Joomla 3.6.x
        // If the variables are not added to the request here, the routing doesn't work

        foreach ($vars AS $key => $val)
        {
            $this->request->set($key, $val);
        }

        return $vars;
    }

    protected static function isAjax()
    {
        return (isset($_SERVER['HTTP_X_REQUESTED_WITH']) &&
                ($_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest'));
    }

    /**
     * Remove the segments that can matched to a menu
     */
    protected function removeMenuRouteSegments($segments)
    {
        $id_segment = null;

        $content_segments = array();

        foreach($segments AS $key=>$seg)
        {
            $segments[$key] = preg_replace('/^([0-9]+)(-)(.*)/','$1:$3',$seg);

            if((int)$segments[$key] || $id_segment) {

                $id_segment = true;

                $content_segments[] = $segments[$key];
            }
        }

        return $content_segments;
    }

    protected function addBackID($menu, $segments, $page_type)
    {
        $segments_bak = $segments;

        // Remove all segments that can be matched directly to the menu route

        // $segments = $this->removeMenuRouteSegments($segments);

        $segments = explode('/',str_replace($menu->route.'/','',implode('/',$segments)));

        // If the remaining segments match exactly the menu route then there's nothing else to do here

        if(implode('/',$segments) == $menu->route)
        {
            return $segments;
        }

        if($menu && $menu->query['option'] == 'com_content' && $menu->query['view'] == 'category')
        {
            $db = JFactory::getDBO();

            if(empty($segments) || count($segments) == 1)
            {
                $cat_id = (int) $menu->query['id'];

                $segments = $segments_bak;
            }
            else {

                // There are subcategories without menus. We need to get the correct cat id.

                $category = array_shift($segments);

                $cat_id = (int) $category;

                $cat_alias = preg_replace('/^([0-9]+)(:|-)(.*)/','$3',$category);

                if(in_array($page_type,array('rss',$this->replacements['reviewdiscussions'])) && $category != $cat_id .'-' . $cat_alias && $category != $cat_id . ':' . $cat_alias)
                {
                    return JError::raiseError(404, JText::_('JGLOBAL_CATEGORY_NOT_FOUND'));
                }
            }

            $last = array_pop($segments);

            $id = (int) $last;

            // ID not present in URL so we query it using the cat id and alias

            if((is_numeric($last) && $id == $last) || (!is_numeric($last) && $id == 0)) {

                $alias = $last;

                $sql = "
                    SELECT
                        id
                    FROM
                        #__content
                    WHERE
                        catid = " . $cat_id . " AND alias = '" . $db->escape($alias) . "'"
                ;

                $id = $db->setQuery($sql)->loadResult();

                if($id)
                {
                    /**
                    * If there's a non-menu cateogry alias, check if a menu exists for the category and redirect
                     */
                    if(isset($category) && $joomla_cat_menu = $this->getJoomlaCatMenu($cat_id))
                    {
                        if($joomla_cat_menu->route != $category)
                        {
                            $url = JURI::base() . $joomla_cat_menu->route . '/' . $alias;

                            header("HTTP/1.1 301 Moved Permanently");

                            header("Location: " . $url);

                            die();
                        }
                    }

                    $segments[] = $id . '-' . $alias;

                    return $segments;
                }
                else {

                    $path = $this->getPath();

                    if(preg_match('/(.*)\/(?P<catid>[0-9]+)-(?P<catalias>.*)$/',$path,$matches))
                    {
                        $path_cat_id = $matches['catid'];

                        $path_cat_alias = $matches['catalias'];

                        if($path_cat_id == $cat_id)
                        {
                            $sql = "
                                SELECT
                                    count(*)
                                FROM
                                    #__categories
                                WHERE
                                    path LIKE '%" . $db->escape($path_cat_alias) . "'
                            ";

                            $exists = $db->setQuery($sql)->loadResult();

                            if($exists)
                            {
                                return $segments_bak;
                            }
                        }
                    }
                }

                if(!in_array($page_type,array('rss',$this->replacements['reviewdiscussions'])))
                {
                    return JError::raiseError(404, JText::_('JGLOBAL_CATEGORY_NOT_FOUND'));
                }
            }

            // The ID is already in the URL. We make sure it's consistent with the cat id and alias and redirect to the no-ID version
            // We should also consider that some listings start with a number
            elseif(!is_numeric($last) && $id > 0)
            {
                $alias = preg_replace('/^([0-9]+)(-)(.*)/','$3',$last);

                $sql = "
                    SELECT
                        catid
                    FROM
                        #__content
                    WHERE
                        id = " . $id . " AND alias = '" . $db->escape($alias) . "'"
                ;

                $db_cat_id = $db->setQuery($sql)->loadResult();

                // The Article ID, CAT ID and ALIAS don't match

                if(!$db_cat_id)
                {
                    // Maybe the number in the URL was not the ID after all and it's an Article that starts with a number

                    $alias = $last;

                    $sql = "
                        SELECT
                            id
                        FROM
                            #__content
                        WHERE
                            catid = " . $cat_id . " AND alias = '" . $db->escape($alias) . "'"
                    ;

                    $real_id = $db->setQuery($sql)->loadResult();

                    if($real_id) {

                        $segments[] = $real_id . '-' . $alias;

                        return $segments;
                    }
                    else {

                        // Check to see if maybe this is a Category URL where last level cat doesn't have a menu

                        $alias = preg_replace('/^([0-9]+)(-)(.*)/','$3',$last);

                        $sql = "
                            SELECT
                                count(*)
                            FROM
                                #__categories
                            WHERE
                                id = " . $id . " AND alias = '" . $db->escape($alias) . "'"
                        ;

                        $cat_exists = $db->setQuery($sql)->loadResult();

                        if($cat_exists)
                        {
                            return $segments_bak;
                        }
                    }

                    if(!in_array($page_type,array('rss',$this->replacements['reviewdiscussions'])))
                    {
                        return JError::raiseError(404, JText::_('JGLOBAL_CATEGORY_NOT_FOUND'));
                    }
                }

                // Remove the listing alias and replace it with
                array_pop($segments_bak);

                $url = JURI::base() . implode('/', $segments_bak) . '/' . $alias;

                header("HTTP/1.1 301 Moved Permanently");

                header("Location: " . $url);

                die();
            }
        }

        return $segments_bak;
    }

    /**
     * Redirects /component/content/article urls to the sef urls when required menus are created
     * @param  [type] $segments       [description]
     * @param  [type] $extra_segments [description]
     * @return [type]                 [description]
     */
    protected function redirectUglyContentArticleURL($page_type)
    {
        $url = null;

        $path = $this->getPath();

        $path = str_replace('component/content/article/', '', $path);

        $segments = explode('/',$path);

        $cat_id = (int) array_shift($segments);

        $id = (int) array_pop($segments);

        if($cat_id && $id) {

            $article_menu = $this->getJoomlaArticleMenu($id);

            if($article_menu) {

                $url = $article_menu->route;
            }
            else {

                $cat_id_menu = $this->getJoomlaCatMenu($cat_id);

                if($cat_id_menu)
                {
                    $db = JFactory::getDBO();

                    $sql = "
                        SELECT
                            alias
                        FROM
                            #__content
                        WHERE
                            id = " . $id . "
                            AND catid = " . $cat_id
                    ;

                    $alias = $db->setQuery($sql)->loadResult();

                    if($alias)
                    {
                        $url = $cat_id_menu->route;

                        if($this->remove_article_id)
                        {
                            $url .= '/' . $alias;
                        }
                        else {

                            $url .= '/' . $id . '-' . $alias;
                        }
                    }
                }
            }
        }

        if($url)
        {
            if(!empty($page_type)) {

                $page_type = '/' . $page_type;
            }

            $query_string = $this->uri->getQuery();

            $query_string =  ($query_string != '' ? '?' . $query_string : '');

            $url = JURI::base() . $url . $page_type . $query_string;

            header("HTTP/1.1 301 Moved Permanently");

            header("Location: " . $url);

            die();
        }
    }

    protected function jreviewsCatRedirect($menu, $extra_segments = '')
    {
        $cat_id = null;

        $path = $this->getPath();

        $action_id = (int) $menu->params->get('action');

        if(($menu->query['option'] == 'com_jreviews' && $action_id == 2)
            ||
            ($menu->query['option'] == 'com_jreviews' && $action_id === 0 && $path != $menu->route))
        {
            $query_string = $this->uri->getQuery();

            $query_string =  ($query_string != '' ? '?' . $query_string : '');

            $extra_segments != '' and $extra_segments = '/' . $extra_segments;

            if($action_id === 0)
            {
                $path = $this->getPath();

                $segments = explode('/', $path);

                $last = end($segments);

                preg_match('/_c(?P<catid>[0-9]+)/', $last, $matches);

                if(isset($matches['catid'])) {

                    $cat_id = $matches['catid'];
                }
            }
            else {

                $cat_id = $menu->params->get('catid');
            }

            if($cat_id) {

                $joomla_cat_menu = $this->getJoomlaCatMenu($cat_id);

                if($joomla_cat_menu)
                {
                    $url = JURI::base() . $joomla_cat_menu->route . $extra_segments . $query_string;

                    header("HTTP/1.1 301 Moved Permanently");
                    header("Location: " . $url);

                    die();
                }
            }
        }
    }

    protected function setCanonical($path)
    {
        $query = $this->uri->getQuery(true);

        $query_string = '';

        $vars = array();

        if(isset($query['page'])) {

            $vars['page'] = $query['page'];

            $query_string = JURI::buildQuery($vars);
        }

        $this->plugin->canonical_url = JURI::base() . $path . ($query_string != '' ? '?' . $query_string : '');
    }

    protected function getJoomlaArticleMenu($id)
    {
        $menu = $this->jmenu->getItems(array('link'),array('index.php?option=com_content&view=article&id='.$id),true);

        if($menu) return $menu; else return false;
    }

    protected function getJoomlaCatMenu($cat_id)
    {
        $menu = $this->jmenu->getItems(array('link'),array('index.php?option=com_content&view=category&layout=blog&id='.$cat_id),true);

        if(!$menu)
        {
            $menu = $this->jmenu->getItems(array('link'),array('index.php?option=com_content&view=category&id='.$cat_id),true);
        }

        return $menu;
    }

    protected function getJReviewsCatMenu($cat_id)
    {
        $menus = $this->jmenu->getItems(array('link'),array('index.php?option=com_jreviews&view=category'),false);

        foreach($menus AS $menu)
        {
            if($menu->params->get('action') == 2 && $menu->params->get('catid') == $cat_id)
            {
                return $menu;
            }
        }

        return false;
    }

    protected function isJReviewsCategory($cat_id)
    {
        $db = JFactory::getDBO();

        $sql = "
            SELECT
                count(*)
            FROM
                #__jreviews_categories
            WHERE
                id = " . (int) $cat_id . " AND `option` = 'com_content'"
        ;

        $count = $db->setQuery($sql)->loadResult();

        return $count;
    }

    static function getCurrentPageRoute()
    {
        if(preg_match('/IIS/',$_SERVER['SERVER_SOFTWARE']))
        {
            $current_url = $_SERVER["HTTP_X_ORIGINAL_URL"];
        }
        else {
            $current_url = $_SERVER["REQUEST_URI"];
        }

        $parts = parse_url($current_url);

        return $parts['path'];
    }

    public function setVersion($version)
    {
        $this->version = $version;
    }
}