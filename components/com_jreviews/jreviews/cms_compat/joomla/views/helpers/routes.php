<?php
/**
 * JReviews - Reviews Extension
 * Copyright (C) 2010-2015 ClickFWD LLC
 * This is not free software, do not distribute it.
 * For licencing information visit https://www.jreviews.com
 * or contact sales@jreviews.com
**/

defined( 'MVC_FRAMEWORK') or die( 'Direct Access to this location is not allowed.' );

class RoutesHelper extends MyHelper
{
    var $Config;

    var $Menu;

    var $helpers = array('html');

    var $routes = array(
        'alphaindex_alldir'=>'index.php?option=com_jreviews&amp;Itemid=%1$s&amp;url=alphaindex/index{_PARAM_CHAR}%2$s/',
        'alphaindex'=>'index.php?option=com_jreviews&amp;Itemid=&amp;url=%1$s_alphaindex_%2$s_d%3$s/',
        'alphaindex_menu'=>'index.php?option=com_jreviews&amp;Itemid=%4$s&amp;url=%1$s_alphaindex_%2$s_d%3$s/', /* _m%4$s */
        'article'=>'index.php?option=com_content&amp;view=article&amp;id=%s&Itemid=%s',
        'content'=>'index.php?option=com_content&amp;view=article&amp;id=%s&amp;catid=%s%s%s', // Itemid is included second to last
        'category'=>'index.php?option=com_jreviews&amp;Itemid=%1$s&amp;url=%2$s/%3$s_c%4$s/', /* _m%1$s */
        'comparison'=>'index.php?option=com_jreviews&Itemid=&url=categories/compare/id:listing_ids/',
        'comparison_menu'=>'index.php?option=com_jreviews&Itemid=%s&id=listing_ids',
        'directory'=>'index.php?option=com_jreviews&amp;Itemid=%4$s&amp;url=%1$s_d%2$s%3$s/',
        'review_discuss'=>'index.php?option=com_jreviews&amp;Itemid=%s&amp;url=discussions/review/id:%s/',
        'listing'=>'index.php?option=com_jreviews&amp;Itemid=%s&amp;url=%s_l%s/extension{_PARAM_CHAR}%s/reviewtype{_PARAM_CHAR}%s',
        'preview'=>'index.php?option=com_jreviews&amp;Itemid=%s&amp;url=preview/id:%s',
        'listing_default'=>'index.php?option=com_jreviews&amp;Itemid=%s&amp;url=%s_l%s',
        'listing_edit'=>'index.php?option=com_jreviews&amp;Itemid=&amp;url=listings/edit/id{_PARAM_CHAR}%s/',
        'listing_edit_menu'=>'index.php?option=com_jreviews&amp;Itemid=%s&amp;id=%s',
        'listing_new'=>'index.php?option=com_jreviews&amp;Itemid=&amp;url=new-listing/',
        'listing_new_category'=>'index.php?option=com_jreviews&amp;Itemid=%s&amp;url=new-listing_c%s/',
        'listing_new_category15'=>'index.php?option=com_jreviews&amp;Itemid=%s&amp;url=new-listing_s%s_c%s/',
        'my_menu'=>'index.php?option=com_jreviews&amp;Itemid=%s&amp;url=menu/user:%s/',
		'mylistings'=>'index.php?option=com_jreviews&amp;Itemid=%s&amp;url=my-listings/user:%s/',
        'favorites'=>'index.php?option=com_jreviews&amp;Itemid=%s&amp;url=favorites/user:%s/',
        'myreviews'=>'index.php?option=com_jreviews&amp;Itemid=%s&amp;url=my-reviews/user:%s/',
        'search'=>'index.php?option=com_jreviews&amp;Itemid=&amp;url=advanced-search/',
        'search_menu'=>'index.php?option=com_jreviews&amp;Itemid=%s',
        'search_results'=>'index.php?option=com_jreviews&amp;Itemid=&amp;url=search-results',
        'search_results_menu'=>'index.php?option=com_jreviews&amp;Itemid=%1$s&amp;url=search-results',
        'review_edit'=>'index.php?option=com_jreviews&amp;url=reviews/edit/id{_PARAM_CHAR}%s&amp;width=800&amp;height=580',
        'review_edit15'=>'index.php?option=com_jreviews&amp;tmpl=component&amp;url=reviews/edit/id{_PARAM_CHAR}%s&amp;width=800&amp;height=580',
        'reviewers'=>'index.php?option=com_jreviews&amp;Itemid=%s&amp;url=reviewers%s#user-%s',
        'reviewers_menu'=>'index.php?option=com_jreviews&amp;Itemid=%s&amp;url=menu%s#user-%s',
        'rss_listings_directory'=>'index.php?option=com_jreviews&amp;Itemid=%s&amp;url=categories/latest/dir:%s/action:xml/',
        'rss_reviews'=>'index.php?option=com_jreviews&amp;Itemid=&amp;url=reviews_%s.rss/',
        'rss_reviews_directory'=>'index.php?option=com_jreviews&amp;Itemid=%s&amp;url=%s_d%s.rss/',
        'rss_reviews_category'=>'index.php?option=com_jreviews&amp;Itemid=%s&amp;url=%s_c%s.rss/',
        'rss_reviews_listing'=>'index.php?option=com_jreviews&amp;Itemid=%4$s&amp;url=%1$s_l%2$s_%3$s.rss/',
        'tag'=>'index.php?option=com_jreviews&amp;Itemid=%1$s&amp;url=tag/%2$s/%3$s/',
        'menu' => 'index.php?option=com_jreviews&amp;Itemid=%s',
        'whois'=>'http://whois.domaintools.com/%s',
/* MEDIA */
		'attachment'=>'index.php?option=com_jreviews&amp;Itemid=%s&amp;url=downloads/',
		'audio'=>'index.php?option=com_jreviews&amp;Itemid=%s&amp;url=audio/',
		'video'=>'index.php?option=com_jreviews&amp;Itemid=%s&amp;url=videos/',
		'photo'=>'index.php?option=com_jreviews&amp;Itemid=%s&amp;url=photos/',
		'media_create'=>'index.php?option=com_jreviews&amp;Itemid=%s&amp;url=upload/%s/',
        'mymedia'=>'index.php?option=com_jreviews&amp;Itemid=%s&amp;url=my-media/user:%s/',
		'listing_media'=>'index.php?option=com_jreviews&amp;Itemid=%s&amp;url=media/listing/id:%s/'
    );

    function __construct()
    {
        parent::__construct('jreviews');

        $this->Config = Configure::read('JreviewsSystem.Config');

        $this->Menu = ClassRegistry::getClass('MenuModel');
    }

    function createLink($title, $url, $attributes)
    {
        $breadcrumb = false;

        if(isset($attributes['breadcrumb']))
        {
            unset($attributes['breadcrumb']);

            $breadcrumb = true;

            $attributes['itemprop'] = 'url';
        }

        $title = $breadcrumb ? '<span itemprop="title">' . $title . '</span>' : $title;

        $link = $this->Html->sefLink($title, $url, $attributes);

        if($breadcrumb)
        {
            return '<span itemscope itemtype="http://data-vocabulary.org/Breadcrumb">' . $link . '</span>';
        }

        return $link;
    }

    function breadcrumb($title)
    {
        return '<span>' . $title . '</span>';
    }

    function parse_url($url)
    {
        if(strpos($url, '?'))
        {
            return parse_url($url);
        }

        $parts = explode('/', $url);

        $temp = $query = $out = array();

        foreach($parts AS $key=>$part)
        {
            if(strpos($part, ':') === false) continue;

            $temp[$key] = str_replace(':', '=', $part);

            unset($parts[$key]);
        }

        $out['path'] = implode('/', $parts);

        if(!empty($temp))
        {
            $out['query'] = implode('&', $temp);
        }

        return $out;
     }

    function currentPage($attributes = array())
    {
        $menu_id = Sanitize::getInt($this->params,'Itemid');

        $url = sprintf($this->routes['menu'], $menu_id);

        $title = $this->Menu->getMenuName($menu_id);

        return $this->createLink($title, $url, $attributes);
    }

    function getActionLink($title, $controller, $action, $params = array(), $attributes = array())
    {
        $menu_id = $this->Menu->getMenuIdByViewParams($controller, array('action'=>$action, 'filters' => Sanitize::getVar($params, 'filters', array())));

        unset($params['filters']);

        $url = sprintf($this->routes['menu'], $menu_id);

        if(!$menu_id)
        {
            $url .= '&url=' . $controller . '/' . $action;
        }

        if(!empty($params))
        {
            $query = http_build_query($params);

            $url .= '&' . $query;
        }

        return $this->Html->link($title, $url, $attributes);
    }

    // Click2search

    function tag($option, $attributes=array())
    {
        $menu_id = '';

        extract($option);

        $field = str_replace('jr_', '', $field);

        $menu_id = Sanitize::getInt($this->params,'Itemid');

        $url = sprintf($this->routes['tag'], $menu_id, $field, $value) ;

        return $url;
    }

    function alphaindex($alpha_title,$alpha_value,$directory,$attributes=array())
    {
        if(empty($directory))
		{
            $menu_id = Sanitize::getInt($this->params,'Itemid');

            return $this->Html->sefLink($alpha_title,sprintf($this->routes['alphaindex_alldir'],$menu_id,$alpha_value),$attributes);
        }
		else {

            if(isset($directory['Directory'])) {

                $first = array_shift($directory);
            }
            else {

                $first = array_shift($directory);

                $first = array_shift($first);
            }

            if(isset($first['Directory'])) {

                $first = $first['Directory'];
            }


			$dir_title = S2Router::sefUrlEncode($first['slug'],__t("and",true));

            $dir_id = $first['dir_id'];

            $menu_id = $first['menu_id'];
        }

        if(is_numeric($menu_id)) {
            $url = sprintf($this->routes['alphaindex_menu'],$dir_title,$alpha_value,$dir_id,$menu_id);
        }
		else {
            $url = sprintf($this->routes['alphaindex'],$dir_title,$alpha_value,$dir_id);
        }
        return $this->Html->sefLink($alpha_title,$url,$attributes);
    }

    function article($article, $attributes = array())
    {
        $article_id = Sanitize::getInt($article['Article'],'article_id');
        if(!$menu_id = Sanitize::getInt($article['Article'],'menu_id'))
        {
            $menuModel = ClassRegistry::getClass('MenuModel');
            $menu_id = $menuModel->get('core_content_menu_id_'.$article_id);
        }

        $url = sprintf($this->routes['article'], $article_id, $menu_id);
        return $this->Html->sefLink($article['Article']['title'],$url,$attributes);
    }

    function category()
    {
        # Process parameters whether passed individually or as an array
        $params = func_get_args();

        $node = array();

        $attributes = array();

        $node = array_shift($params);

        if(isset($params) && isset($params[0]) && !isset($params[0]['params']))
        {
            $attributes = array_shift($params);
        }

        count(array_keys($node)) == 1 and $node = array_shift($node);

        $dir_slug = S2Router::sefUrlEncode($node['Directory']['slug'],__t("and",true));

        $dir_id = $node['Directory']['dir_id'];

        $cat_slug = $node['Category']['slug'];

        $cat_id = $node['Category']['cat_id'];

        if(Sanitize::getVar($attributes,'image'))
        {
            $params = json_decode($node['Category']['params'],true);

            $image_path = WWW_ROOT . $params['image'];

            isset($params['image']) and $params['image']!='' and $node['Category']['title'] = $this->Html->image($image_path,array('border'=>0,'alt'=>$node['Category']['title']));

            unset($attributes['image']);
        }

        $menuModel = ClassRegistry::getClass('MenuModel');

       # Check if there's a menu for this category to prevent duplicate urls
        $menu_id = Sanitize::getInt($node['Category'],'menu_id');

        $menu_params = $menuModel->getMenuParams($menu_id);

        $action_id = Sanitize::getInt($menu_params,'action');

        if(!$menu_id)
        {
            $menu_id = $menuModel->getCategory(array('cat_id'=>$cat_id,'dir_id'=>$dir_id));
        }
        elseif($menu_id > 0 && $action_id == 2 && Sanitize::getInt($menu_params,'catid') == $cat_id)
        {
            if(!$menuModel->get('jr_manyIds_'.$menu_id)) {

                $url = sprintf($this->routes['menu'], $menu_id);

                if(Sanitize::getString($attributes,'order') != '') {

                    $url .=  '&order='.$attributes['order'];

                    unset($attributes['order']);
                }

                return $this->createLink($node['Category']['title'],$url,$attributes);
            }
        }

        $menu_params = $menuModel->getMenuParams($menu_id);

        $action_id = Sanitize::getInt($menu_params,'action');

        if($action_id == 2 && $menu_params['catid'] == $cat_id)
        {
            $url = sprintf($this->routes['menu'],$menu_id);
        }
        else {

            if(is_object($menu_params) && $action_id === 0 && cmsFramework::getConfig('sef')) $dir_slug = '';

            $url = $this->routes['category'];

            if(empty($menu_id)) {

                $url = str_replace(array('_m%1$s','&amp;Itemid=%1$s'),'',$url);
            }

            $url = sprintf($url,$menu_id,$dir_slug,$cat_slug,$cat_id);
        }

        if(Sanitize::getString($attributes,'order') != '') {

            $url .=  '&order='.$attributes['order'];

            unset($attributes['order']);
        }

        return $this->createLink($node['Category']['title'],$url,$attributes);
    }

    function click2search($anchor, $format, $attributes = array())
    {
        $format = str_replace('&amp;', '&', $format);

        $menu_id = null;

        $query = '';

        // If it's a complete URL, then treat it as external and don't do anything

        if(strstr($format,'http'))
        {
            return $format;
        }

        // Deal with old style Joomla click2search setting in custom field setup

        if(strstr($format, 'index.php?option=com_jreviews'))
        {
            $old_criteria_format = 'index\.php\?option\=com_jreviews&Itemid\=\{itemid\}&url\=tag\/(?P<fieldname>.*)\/(?P<optionvalue>.*)\/criteria\:\{CRITERIAID\}';

            if(preg_match('#' . $old_criteria_format .'#i', rtrim($format,'/'), $matches))
            {
                $format = 'tag/' . $matches['fieldname'] . '/' . $matches['optionvalue'] . '/?criteria={criteriaid}';
            }
            else {

                $old_cat_format = 'index\.php\?option\=com_jreviews&Itemid\=\{itemid\}&url\=tag\/(?P<fieldname>.*)\/(?P<optionvalue>.*)\/cat\:\{catid\}';

                if(preg_match('#' . $old_cat_format .'#i', rtrim($format,'/'), $matches))
                {
                    $format = 'tag/' . $matches['fieldname'] . '/' . $matches['optionvalue'] . '/?cat={catid}';
                }
            }
        }

        $MenuModel = ClassRegistry::getClass('MenuModel');

        $hardcode_menu_id = Sanitize::getInt($attributes,'menu_id');

        $generic_search_menu_id = $MenuModel->getSearch();

        $criteria_id = Sanitize::getInt($attributes,'criteria_id');

        $cat_id = Sanitize::getInt($attributes,'cat_id');

        if(isset($attributes['current_page']))
        {
            $current_page = $attributes['current_page'];
        }

        unset($attributes['criteria_id'], $attributes['cat_id']);

        $parts = self::parse_url($format);

        if(isset($current_page))
        {
            // Used for breadcrumbs

            $menu_id = Sanitize::getString($this->params, 'Itemid');

            parse_str($parts['query'], $query);

            if(isset($query['url']))
            {
                $parts['path'] = $query['url'];
            }

            $query = '';
        }
        elseif(isset($parts['query'])) {

            parse_str($parts['query'], $query);

            // If the Itemid is specified in the click2search URL format we use it instead of trying to figure out which one to use

            if(isset($query['Itemid']))
            {
                $menu_id = Sanitize::getString($query, 'Itemid');

                unset($query['Itemid'],$query['cat'],$query['criteriaid']);
            }
            elseif(isset($query['cat']) && $cat_id)
            {
                unset($query['cat']);

                unset($query['criteria']);

                $menu_id = $MenuModel->getCategory(array('cat_id'=>$cat_id));
            }
            elseif(isset($query['criteria']) && $criteria_id) {

                unset($query['criteria']);

                $menu_id = $MenuModel->getSearch($criteria_id);

                // Add the criteria ID parameter if a menu was not found
                if(!$menu_id || $menu_id == $generic_search_menu_id)
                {
                    $query['criteria'] = $criteria_id;
                }
            }

            if(!$menu_id && $hardcode_menu_id)
            {
                $menu_id = $hardcode_menu_id;
            }

            $query = http_build_query($query);
        }
        elseif($hardcode_menu_id) {

            $menu_id = $hardcode_menu_id;

            $query = '';
        }

        if(!$menu_id)
        {
            $menu_id = $MenuModel->getSearch();

            $query = '';
        }

        $url = sprintf($this->routes['menu'], $menu_id) . '&url=' . ltrim($parts['path'], '/') . ($query != '' ? '?' . $query :  '');

        $url = $this->createLink($anchor,$url,$attributes);

        $url = str_replace(array('%7B','%7D'), array('{','}'), $url);

        if(strpos($url, '%3F')) {
            $url = urldecode($url); // To convert the curly braces back from their urlencoded version
        }

        return $url;
    }

    function comparison()
    {
        $MenuModel = ClassRegistry::getClass('MenuModel');

        $comparison_url = sprintf($this->routes['comparison']);

        $menu_id = $MenuModel->getMenuIdByAction(103); // Comparison Catch-All menu

        if($menu_id) {

            $comparison_url = sprintf($this->routes['comparison_menu'],$menu_id);
        }

        return WWW_ROOT_REL . str_replace(str_replace('https','http',WWW_ROOT), '' , str_replace('https','http',cmsFramework::route($comparison_url)));
    }

    function content($title,$listing,$attributes = array(),$anchor='')
    {
        if($listing['Listing']['extension'] != 'com_content') {

            return $this->Html->link($title,$listing['Listing']['url'],$attributes);
        }

        $publish_up = isset($listing['Listing']['publish_up_UTC']) ? $listing['Listing']['publish_up_UTC'] : $listing['Listing']['publish_up'];

        $publish_down = isset($listing['Listing']['publish_down_UTC']) ? $listing['Listing']['publish_down_UTC'] : $listing['Listing']['publish_down'];

        if($listing['Listing']['state'] < 1
            ||
            (NULL_DATE != $publish_down && strtotime($publish_down) < strtotime(_TODAY))
            ||
            (NULL_DATE != $publish_up && strtotime($publish_up) > strtotime(_END_OF_TODAY))
        ) {
            return $this->preview($title, $listing, $attributes, $anchor);
        }

        $listing_id = $listing['Listing']['listing_id'];

        $menu_id = Sanitize::getInt($listing['Listing'],'menu_id');

        $cat_id = $listing['Listing']['cat_id'];

        if($menu_id) {

            $menu_id = '&amp;Itemid='.$menu_id;
        }
        else {

            $menu_id = '';
        }

        $listing_slug = Sanitize::getString($listing['Listing'],'slug') != '' ? $listing_id . ':' . $listing['Listing']['slug'] : $listing_id;

        $cat_slug = Sanitize::getString($listing['Category'],'slug') != '' ? $cat_id . ':' . $listing['Category']['slug'] : $cat_id;

		$route = $this->routes['content'];

        // For Joomfish compat
        if(isset($this->params['lang']) && $this->params['lang']!=''){

            $menu_id .= '&amp;lang='.Sanitize::getString($this->params,'lang');
        }

        $url = sprintf($route,$listing_slug,$cat_slug,$menu_id,$anchor!=''?'#'.$anchor:'');

        return $this->Html->link($title,$url,$attributes);
    }

    function preview($title, $listing, $attributes = array(), $anchor = '')
    {
        $listing_id = $listing['Listing']['listing_id'];

        $menu_id = Sanitize::getInt($listing['Category'],'menu_id');

        $listing_slug = Sanitize::getString($listing['Listing'],'slug') != '' ? $listing_id . ':' . S2Router::sefUrlEncode($listing['Listing']['slug']) : $listing_id;

        $route = $this->routes['preview'];

        $attributes['rel'] = 'nofollow';

        // For Joomfish compat
        if(isset($this->params['lang']) && $this->params['lang']!=''){

            $menu_id .= '&amp;lang='.Sanitize::getString($this->params,'lang');
        }

        $url = sprintf($route,$menu_id, $listing_slug, $anchor!=''?'#'.$anchor:'');

        return $this->Html->link($title,$url,$attributes);
    }

    function directory($directory,$attributes = array())
    {
        $dir_title = S2Router::sefUrlEncode($directory['Directory']['slug'],__t("and",true));

        $dir_id = (int) $directory['Directory']['dir_id'];

        if(!($menu_id = Sanitize::getInt($directory['Directory'],'menu_id')))
        {
            // Check if there's a menu for this directory to prevent duplicate urls
            $menuModel = ClassRegistry::getClass('MenuModel');

            $menu_id = $menuModel->getDir($dir_id);
        }

        if($menu_id) {

            $url = sprintf($this->routes['menu'], $menu_id);

            return $this->createLink($directory['Directory']['title'],$url,$attributes);
        }
        else {

            $menu_id = Sanitize::getInt($this->params,'Itemid');

            $menu_id_param = /*$menu_id ? '_m'.$menu_id:*/ '';

            $url = sprintf($this->routes['directory'], $dir_title, $dir_id, $menu_id_param, $menu_id);

            return $this->createLink($directory['Directory']['title'],$url,$attributes);
        }
    }

    function myListings($title, $user_id, $attributes = array())
    {
        $menu_id_cat = null;

        if($user_id > 0) {

            $cat_id = Sanitize::getInt($attributes,'cat_id');

            unset($attributes['cat_id']);

            $menu_id = $this->Menu->get('jr_mylistings');

            $cat_id and $menu_id_cat = $this->Menu->get('jr_mylistings'.$cat_id);

            $url = sprintf($this->routes[($menu_id > 0 ? 'my_menu' : 'mylistings')],$menu_id,$user_id);

            if($cat_id && !$menu_id_cat) {

                $url .= 'cat:'.$cat_id.'/';
            }

            if(Sanitize::getString($attributes,'order') != '') {

                $url .=  '&order='.$attributes['order'];

                unset($attributes['order']);
            }

            return $this->Html->sefLink($title,$url,$attributes);
        }
    }

    function favorites($title, $user_id, $attributes = array())
    {
        $menu_id_cat = null;

        if($user_id > 0) {

            $cat_id = Sanitize::getInt($attributes,'cat_id');

            unset($attributes['cat_id']);

            $menu_id = $this->Menu->get('jr_myfavorites');

            $cat_id and $menu_id_cat = $this->Menu->get('jr_myfavorites'.$cat_id);

            $url = sprintf($this->routes[($menu_id > 0 ? 'my_menu' : 'favorites')],$menu_id,$user_id);

            if($cat_id && !$menu_id_cat) {

                $url .= 'cat:'.$cat_id.'/';
            }

            if(Sanitize::getString($attributes,'order') != '') {

                $url .= '&order='.$attributes['order'];

                unset($attributes['order']);
            }

            return $this->Html->sefLink($title,$url,$attributes);
        }
    }

    function myReviews($title, $user, $attributes = array())
    {
        $user_id = is_numeric($user) ? $user : $user['user_id'];

        if($user_id > 0)
        {
            $menu_id = $this->Menu->get('jr_myreviews');

            $url = sprintf($this->routes[($menu_id > 0 ? 'my_menu' : 'myreviews')],$menu_id,$user_id);

            return $this->Html->sefLink($title,$url,$attributes);
        }
    }

    function listing($title, &$listing, $reviewType='user', $attributes = array())
    {
        // backwards theme compat
        if(is_array($reviewType)){
            $attributes = $reviewType;
            $reviewType = 'user';
        }

		$Itemid = $menu_id = '';

		$listing_id = $listing['Listing']['listing_id'];

        $order = Sanitize::getString($attributes,'order');

        $user = Sanitize::getString($attributes,'user');

        $rating = Sanitize::getString($attributes,S2_QVAR_RATING_AVG);

        unset($attributes['order'],$attributes['user'],$attributes[S2_QVAR_RATING_AVG]);

        if(defined('JREVIEWS_SEF_PLUGIN')) {

            $listing_title = $listing['Listing']['slug'];
        }
        else {

            $listing_title = S2Router::sefUrlEncode($listing['Listing']['title'],__t("and",true));
        }

		$extension = $listing['Listing']['extension'];

		$criteria_id = $listing['Criteria']['criteria_id'];

        $menu_id = $this->Menu->get('jr_viewallreviews'.$extension);

		if(!$menu_id && $extension == 'com_content')
        {
			$menuParams['cat_id'] = $listing['Category']['cat_id'];

			$menuParams['dir_id'] = $listing['Directory']['dir_id'];

            $menu_id = $this->Menu->getCategory($menuParams);
		}

        if($extension == 'com_content')
        {
            if($reviewType == 'user') {

                $url = sprintf($this->routes['listing_default'], $menu_id, $listing_title, $listing_id);
            }
            else {

                $url = sprintf($this->routes['listing_default'], $menu_id, $listing_title, $listing_id).'/reviewType{_PARAM_CHAR}'.$reviewType;
            }
        }
        else {

            $menu_extension = '';

            if($menu_id)
            {
                $menuParams = $this->Menu->getMenuParams($menu_id);

                $menu_extension = Sanitize::getString($menuParams,'extension');
            }

            if($extension == $menu_extension)
            {
                $url = sprintf($this->routes['listing_default'], $menu_id, $listing_title, $listing_id);
            }
            else {

                $url = sprintf($this->routes['listing_default'], $menu_id, $listing_title, $listing_id).'/extension{_PARAM_CHAR}'.$extension;
            }
        }

        unset($listing['Review']['reviewType']);

        $params = array();

        if($order)
        {
            $params['order'] = $order;
        }

        if($user)
        {
            $params['user'] = $user;
        }

        if($rating)
        {
            $params[S2_QVAR_RATING_AVG] = $rating;
        }

        if(!empty($params))
        {
            $url_params = http_build_query($params, '', '/');

            $url_params = str_replace('=', _PARAM_CHAR, $url_params);

            $url .= '/' . $url_params;
        }

        return $this->Html->link($title,$url,$attributes);
    }

    function listingEdit($title, $listing, $attributes=array())
    {
        $listing_id = $listing['Listing']['listing_id'];

        $menu_id = $this->Menu->getMenuIdByAction(102); // Listing edit Catch-All menu

        if($menu_id) {

            $url = sprintf($this->routes['listing_edit_menu'],$menu_id,$listing_id);

        }
        else {

            $url = sprintf($this->routes['listing_edit'],$listing_id);
        }

        return $this->Html->sefLink($title,$url,$attributes);
    }

    function listingNew($title, $attributes = array())
    {
        $params = Sanitize::getVar($attributes,'params',array());

        unset($attributes['params']);

        if($cat_id = Sanitize::getInt($params,'cat'))
        {
            unset($params['cat']);
        }
        else {

            $cat_id = Sanitize::getString($this->passedArgs,'cat',Sanitize::getString($this->params,'cat'));
        }

        if($cat_id)
        {
            // Find category specific submit menu
            if( !($menu_id = $this->Menu->getCategory(array('cat_id'=>$cat_id))) )
            {
                $men_id = $this->Config->list_addnew_menuid ? Sanitize::getInt($this->params,'Itemid') : '';
            }

            $url = sprintf($this->routes['listing_new_category'],$menu_id,$cat_id);

        }
        else {

            $menu_id = $this->Menu->get('jr_newlisting');

            if($menu_id) {

                $url = sprintf($this->routes['menu'],$menu_id);
            }
            else {

                $url = sprintf($this->routes['listing_new'],$menu_id);
            }
        }

        $query_params = http_build_query($params, '', '/');

        $query_params = str_replace('=', _PARAM_CHAR, $query_params);

        $url .= $query_params;

        return $this->createLink($title,$url,$attributes);
    }

    function listingsFeed($title = '',$attributes=array())
    {
        $url = rtrim(cmsFramework::constructRoute($this->passedArgs,array('listview')),'/').'/action:xml/';

        $title = sprintf(__t("%s listing feeds",true),$title);

        if(isset($attributes['return_url'])){

            $url = cmsFramework::route($url);

            return $url;
        }
        else {

            $attributes = array_merge(array('title'=>$title,'class'=>'jrFeedListings'),$attributes);

            return $this->Html->link('',$url,$attributes);
        }
    }

    function listingsFeedDirectory($directory,$title='',$attributes=array())
    {
        $dir_id = $directory['Directory']['dir_id'];

        $directory_title = $directory['Directory']['title'];

        $title = $title != '' ? $title : sprintf(__t("%s listing feeds",true),$directory_title);

        $attributes = array_merge(array('title'=>$title,'class'=>'jrFeedListings'),$attributes);

        $id = $directory['Directory']['dir_id'];

        $menu_id = $this->Menu->getDir($id);

        $url = sprintf($this->routes['rss_listings_directory'], $menu_id, $dir_id);

        return $this->Html->link('', $url ,$attributes);
    }

    function search($title,$attributes = array())
    {
        $menu_id = $this->Menu->get('jr_advsearch');

        if($menu_id)
        {
            return $this->Html->sefLink($title,sprintf($this->routes['search_menu'],$menu_id));
        }
        return $this->Html->sefLink($title,sprintf($this->routes['search']));
    }

    function search_results($params, $menu_id = '')
    {
        if($menu_id)
        {
            $url = sprintf($this->routes['search_results_menu'],$menu_id);
        }
        else {

            $url = sprintf($this->routes['search_results']);
        }

        foreach($params AS $key=>$val)
        {
            if(is_array($val))
            {
                $params[$key] = $val;
            }
            else {
                $params[$key] = urlencode($val);
            }
        }

        $url_params = http_build_query($params, '', '/');

        $url_params = str_replace('=', _PARAM_CHAR, $url_params);

        $url = cmsFramework::route($url . '/' . $url_params);

        return $url;
    }

    function reviewers($rank,$user_id, $attributes = array())
    {
		$limit = $this->Config->list_limit;

        $paginate = '';

        $menu_id = '';

        $paginate = '';

        if($rank)
        {
			S2App::import('Helper','jreviews','jreviews');

            $Jreviews = ClassRegistry::getClass('JreviewsHelper');

            $menu_id = $this->Menu->getReviewers();

            $userRank = $Jreviews->userRank($rank);

            $offset = floor($rank/$limit)*$limit;

            if ($offset > 1)
            {
                $page = $offset/$limit + 1;

                $paginate = "/page"._PARAM_CHAR.$page;
            }

            $url = sprintf(($menu_id ? $this->routes['reviewers_menu'] : $this->routes['reviewers']),$menu_id,$paginate,$user_id);

            return $this->Html->sefLink($userRank,$url,$attributes);
        }

    }

    function reviewDiscuss($title, $review, $attributes = array())
    {
        $menu_id = '';

        if(isset($review['Review'])){
            $review = $review['Review'];
        }

        $review_id = $review['review_id'];

        if(isset($attributes['listing']) && !empty($attributes['listing']))
        {
            $listing = $attributes['listing'];

            unset($attributes['listing']);

            if(!isset($listing['Listing']['extension']) || $listing['Listing']['extension'] == 'com_content')
            {
                $menu_id = $this->Menu->getCategory(array('cat_id'=>Sanitize::getInt($listing['Category'],'cat_id')));
            }
        }

        !$menu_id and $menu_id = $this->Menu->getMenuIdByAction(17); // Latest comments menu

        if($review_id > 0)
        {
            $url = sprintf($this->routes['review_discuss'],$menu_id,$review_id);
			return $this->Html->link($title,$url,$attributes);
        }
    }

    function rss($extension = 'com_content', $title = '',$attributes = array())
    {
        $url = sprintf($this->routes['rss_reviews'],$extension);
        return $this->Html->sefLink($title,$url,$attributes);
    }

    function rssDirectory($directory,$title = '',$attributes = array())
    {
        $dir_slug = S2Router::sefUrlEncode($directory['Directory']['slug']);

        $dir_title = $directory['Directory']['title'];

        $id = $directory['Directory']['dir_id'];

        $menu_id = $this->Menu->getDir($id);

        $title = $title != '' ? $title : sprintf(__t("%s review feeds",true),$dir_title);

        $attributes = array_merge(array('class'=>'jrFeedReviews','title'=>$title),$attributes);

        $url = sprintf($this->routes['rss_reviews_directory'],$menu_id,$dir_slug,$id);

        return $this->Html->sefLink('',$url,$attributes);
    }

    function rssCategory($category,$title='',$attributes = array())
    {
        $cat_slug = S2Router::sefUrlEncode($category['Category']['slug'],__t("and",true));

        $cat_title = $category['Category']['title'];

        $cat_id = $category['Category']['cat_id'];

        $title = $title != '' ? $title : sprintf(__t("%s review feeds",true),$cat_title);

        $menu_id = $this->Menu->getCategory(array('cat_id'=>$category['Category']['cat_id'],'dir_id'=>$category['Directory']['dir_id']));

        $attributes = array_merge(array('class'=>'jrFeedReviews','title'=>$title),$attributes);

        $url = sprintf($this->routes['rss_reviews_category'],$menu_id,$cat_slug,$cat_id);

        return $this->Html->sefLink('',$url,$attributes);
    }

    function rssListing($listing,$title='',$attributes = array())
    {
        $menu_id = '';


        if(isset($listing['Listing']['slug'])){

            $listing_slug = S2Router::sefUrlEncode($listing['Listing']['slug'],__t("and",true));
        }
        else {
            $listing_slug = S2Router::sefUrlEncode($listing['Listing']['title'],__t("and",true));
        }

        $listing_title = $listing['Listing']['title'];

        $listing_id = $listing['Listing']['listing_id'];

        $extension = $listing['Listing']['extension'];

        if($extension == 'com_content') {

            $menu_id = $this->Menu->getCategory(array('cat_id'=>$listing['Category']['cat_id'],'dir_id'=>$listing['Directory']['dir_id']));
        }

        $title = $title != '' ? $title : sprintf(__t("%s review feeds",true),$listing_title);

        $attributes = array_merge(array('class'=>'jrFeedReviews','title'=>$title),$attributes);

        $url = sprintf($this->routes['rss_reviews_listing'],$listing_slug,$listing_id,$extension,$menu_id);

        return $this->Html->sefLink('',$url,$attributes);
    }

    function whois($ip_address)
    {
        $url = sprintf($this->routes['whois'],$ip_address);
        return $this->Html->link($ip_address,$url,array('sef'=>false,'target'=>'_blank'));
    }

	function mediaCreate($anchor, $object, $attributes = array())
	{
		$params = '';

        $menu_id = $this->Menu->getMenuIdByAction(101); // Media Catch-All menu

		if(isset($object['Review']) && isset($object['Review']['listing_id']) && isset($object['Review']['listing_id']))
		{
			$params = 'id:'.urlencode(base64_encode(
					$object['Review']['listing_id']
					.':'.
					$object['Review']['review_id']
					.':'.
					Sanitize::getString($object['Review'],'extension','com_content')
			)).'/';
		}
		elseif(isset($object['Listing']))
		{
			$params = 'id:'.urlencode(base64_encode(
					$object['Listing']['listing_id']
					.':'.
					Sanitize::getString($object['Listing'],'extension','com_content')
			)).'/';
		}
		elseif($object['Media']) {

			$params = 'id:'.urlencode(base64_encode(
					$object['Media']['listing_id']
					.':'.
					Sanitize::getString($object['Media'],'extension','com_content')
			)).'/';
		}

        $url = sprintf($this->routes['media_create'],$menu_id,$params);

		return $this->Html->link($anchor,$url,$attributes);
	}

	function myMedia($anchor, $user_id, $attributes = array())
	{
        if($user_id > 0) {

            $menu_id = $this->Menu->get('jr_mymedia');

            $url = sprintf($this->routes[($menu_id > 0 ? 'my_menu' : 'mymedia')],$menu_id,$user_id);

            return $this->Html->sefLink($anchor,$url,$attributes);
        }
	}

	function listingMedia($anchor, $listing, $attributes = array())
	{
        $listing_id = $listing['Listing']['listing_id'];

        $listing_alias = $listing_id.':'.$listing['Listing']['slug'];

		$extension = $listing['Listing']['extension'];

        $menu_id = $this->mediaMenuId();

        if(!$menu_id) {

            $cat_id = $listing['Category']['cat_id'];

            $dir_id = $listing['Directory']['dir_id'];

            $menu_id = $this->Menu->getCategory(array('cat_id'=>$cat_id,'dir_id'=>$dir_id));
        }

		$url = sprintf($this->routes['listing_media'], $menu_id, $listing_alias);

		return $this->Html->sefLink($anchor,$url,$attributes);
	}

	function mediaMenuId($options = array()) {

        $use_catch_all = Sanitize::getBool($this->Config,'media_url_catchall');

        if(isset($options['listing'])) {

            $listing = $options['listing'];

            $menu_id = Sanitize::getInt($listing['Category'],'menu_id');
        }
        elseif(isset($options['cat_menu_id']))
        {
            $menu_id = Sanitize::getInt($options,'cat_menu_id');
        }

        if($use_catch_all || (!$use_catch_all && empty($menu_id)))
        {
            $menu_id = $this->Menu->getMenuIdByAction(101); // Media Catch-All menu
        }

		return $menu_id;
	}

	function mediaDetail($anchor, $options, $attributes = array())
	{
		$media_id = null;

        $param = '';

		$Itemid = $this->mediaMenuId($options);

		$listing = Sanitize::getVar($options,'listing');

		$media = Sanitize::getVar($options,'media');

        $media_by = Sanitize::getString($options,'media_by');

		if(!isset($media['Media']) && !empty($media)) $media = array('Media'=>$media);

		$type = Sanitize::getString($media['Media'],'media_type',Sanitize::getString($options,'media_type'));

		if($type == 'none') { return ''; }

		$typeClass = 'media' . inflector::camelize($type);

		$attributes['class'] = isset($attributes['class']) ? $attributes['class'] . ' ' . $typeClass : $typeClass;

		$url = $this->routes[$type];

		if(!empty($listing) && empty($media))
		{
			if(isset($listing['MainMedia']) && $listing['MainMedia']['media_type'] == $type)
			{

				$media_id = $listing['MainMedia']['media_id'];
			}
			elseif(isset($listing['Media'][$type])) {

				$media_id = $listing['Media'][$type][0]['media_id'];
			}

			$listing_id = $listing['Listing']['listing_id'];

			$extension = $listing['Listing']['extension'];
		}
        elseif(!isset($media['Media']['media_id'])) {

            return $anchor;
        }
		else {

			$media_id = $media['Media']['media_id'];

			$listing_id = $media['Media']['listing_id'];

			$extension = Sanitize::getString($media['Media'],'extension','com_content');
		}

        $photo_layout = isset($media['ListingType']) ? $this->Config->getOverride('media_detail_photo_layout',$media['ListingType']['config']) : false;

        $video_layout = isset($media['ListingType']) ? $this->Config->getOverride('media_detail_video_layout',$media['ListingType']['config']) : false;

		if($type == 'photo' && (in_array($photo_layout, array('contact_lightbox','film_lightbox','gallery_small','gallery_large'))))
		{
			return !empty($listing) ? $this->content($anchor, $listing, $attributes) : $anchor;
		}

        $media_detail_page = Sanitize::getString($this,'name') == 'media' &&
                                $this->action == 'videoGallery' &&
                                !Sanitize::getInt($this->params,'lightbox');

		if(!$media_detail_page /*listing detail page*/ && $type == 'video' && (in_array($video_layout, array('contact_lightbox','film_lightbox','video_player'))))
		{
			return !empty($listing) ? $this->content($anchor, $listing, $attributes) : $anchor;
		}
		elseif($type == 'attachment')
		{
			if(defined('MVC_FRAMEWORK_ADMIN') || $this->Access->isAuthorized($media['Media']['access']))
			{

				return !empty($listing) ? $this->content($anchor, $listing, $attributes) : $anchor;

			}
			else {

				return sprintf(__t("%s - restricted access",true),$anchor);
			}
		}
        elseif($type == 'audio') {

			return !empty($listing) ? $this->content($anchor, $listing, $attributes) : $anchor;
		}

        if($media_id) {

            $param .= S2_QVAR_MEDIA_CODE . ':'.s2alphaID($media_id,false,5,cmsFramework::getConfig('secret')).'/';
        }
        else {

            $param .= 'id:'.urlencode(base64_encode(
                    $listing_id
                    .':'.
                    $extension
            )).'/';
        }

		$url = sprintf($url,$Itemid) . $param;

        if(in_array($type,array('video','photo')) && $media_by != '') {

            $url .= 'by:'.$media_by;
        }

		return $this->Html->link($anchor,$url,$attributes);
	}

    function listLayoutOptions()
    {
        $layout_options = Sanitize::getVar($this->Config,'list_predefined_layout',array());

        $query = array();

        if ($this->ajaxRequest)
        {
            $curr_url = str_replace('&amp;','&',cmsFramework::route(cmsFramework::constructRoute($this->passedArgs)));
        }
        else {
            $curr_url = cmsFramework::getCurrentUrl();
        }

        $parts = $this->parse_url($curr_url);

        if(isset($parts['query']))
        {
            parse_str($parts['query'], $query);
        }

        unset($query['listview'], $query['format']);

        $current_view = Sanitize::getInt($this->params,'layout_index',1);

        $currentOrder = Sanitize::getString($this->params,'order');

        $defaultOrder = Sanitize::getString($this->params,'default_order');

        if($currentOrder != '' && $currentOrder !== $defaultOrder)
        {
            $query['order'] = $currentOrder;
        }
        else {
            unset($query['order']);
        }

        $out = array();

        foreach($layout_options AS $index=>$layout)
        {
            if($layout['layout'] == '') continue;

            $attributes = array('sef'=>false,'class'=>'jrButton');

            // No need to add the layout parameter for the default layout

            $query['listview'] = $index;

            $params = http_build_query($query);

            $url = $parts['path'] . ($params != '' ? '?' . $params :  '');

            if($current_view == $index) {
                $link = '<span class="jrButton jrDisabled"><span class="'. $layout['icon'] . '"></span></span>';
            }
            else {
                $link = $this->Html->link('<span class="'. $layout['icon'] . '"></span>',$url,$attributes);
            }

            $out[] = $link;
        }

        if(count($out) > 1)
        {
            $ajaxPagination = Sanitize::getInt($this->Config, 'paginator_ajax') ? 'data-ajax="1"' : '';

            $out = sprintf('<div class="jr-list-layout jrListLayoutOptions jrButtonGroup" %s>%s</div>', $ajaxPagination, implode('',$out));
        }
        else {

            $out = '';
        }

        return $out;
    }

    function stripDomainPath($url)
    {
        if(substr($url, 0, 2) == '//') return $url;

        $url = str_replace(WWW_ROOT, '', $url);

        if(WWW_ROOT_REL != '/') {

            $url = str_replace(WWW_ROOT_REL, '', $url);
        }

        return ltrim($url, '/');
    }
}