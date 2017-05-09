<?php
/**
 * JReviews - Reviews Extension
 * Copyright (C) 2010-2011 ClickFWD LLC
 * This is not free software, do not distribute it.
 * For licencing information visit https://www.jreviews.com
 * or contact sales@jreviews.com
**/

defined( 'MVC_FRAMEWORK') or die( 'Direct Access to this location is not allowed.' );

class MenuModel extends MyModel  {

    var $___menu_data = array();

    var $language;

    function __construct()
    {
        parent::__construct();

        $app  = JApplication::getInstance('site');

        $this->language = cmsFramework::getLocale('-');

        if($cache = Configure::read('System.___menu_data'))
        {
            $this->___menu_data = $cache;

            return;
        }

        $JMenu = $app->getMenu();

        $menuList = $JMenu->getMenu();

        usort($menuList, array($this,'sortMenuLinks'));

        // Get itemid for each menu link and store it
        if(!empty($menuList))
        {
            foreach ($menuList as $menu)
            {
                $params = stringToArray($menu->params);

                $m_name = Sanitize::getVar($params,'sef_name')!='' ? Sanitize::getVar($params,'sef_name') : $menu->title;

                $menu->language == '' and $menu->language = '*';

                $m_action = Sanitize::getVar($params,'action');
                $m_dir_id = str_replace(",","-",Sanitize::getVar($params,'dirid'));
                $m_cat_id = str_replace(",","-",Sanitize::getVar($params,'catid'));
                $m_criteria_id = str_replace(",","-",Sanitize::getVar($params,'criteriaid'));

                // Create a variable to get Menu Name from Itemid
                $this->set('jr_itemid_'.$menu->id,$m_name,$menu->language);
                $this->set('jr_menu_'.$m_name,$menu->id,$menu->language);

                $this->set('jr_id_alias_'.$menu->id,$menu->alias,$menu->language);
                $this->set('jr_alias_id_'.$menu->alias,$menu->id,$menu->language);

                $id = explode('id=',$menu->link);

                $component_id = end($id);

                if(strpos($menu->link,'option=com_content&view=category&id=') || strpos($menu->link,'option=com_content&view=category&layout=blog&id='))
                {
                    $menu_link = 'content_category';
                }
                elseif(strpos($menu->link,'option=com_content&view=article&id=') || strpos($menu->link,'option=com_content&task=view&id=')) {
                    $menu_link = 'content_item_link';
                }
                else {
                    $menu_link = $menu->link;
                }

                switch($menu_link)
                {
                    case 'content_category': case 'content_blog_category':

                        if ($component_id)
                        { // Only one category id
                            $this->set('core_category_menu_id_'.$component_id,$menu->id,$menu->language);
                        }
                        else
                        {
                            $cat_ids = explode(",",Sanitize::getVar($params,'categoryid'));
                            $this->set('jr_manyIds_'.$menu->id,1,$menu->language);

                            foreach($cat_ids AS $cat_id) {
                                $this->set('core_category_menu_id_'.$cat_id,$menu->id,$menu->language);
                            }
                        }

                        $params['action'] = 2;

                        $params['catid'] = $component_id;

                        $this->set('menu_params_'.$menu->id,$params,$menu->language);

                        break;

                    case 'content_item_link':
                            $this->set('core_content_menu_id_'.$component_id,$menu->id,$menu->language);

                        break;

                    default:

                        $isJReviewsComp = true;

                        // It's a JReviews menu

                        if($isJReviewsComp && strstr($menu_link,'index.php?option=com_jreviews'))
                        {
                            // Get a JReviews menu with public access to use in ajax requests
                            if($menu->access == 1) {

                                $this->set('jreviews_public',$menu->id,$menu->language);
                            }

                            $this->set('jr_menu_action_'.$m_dir_id,$m_action,$menu->language);

                            $this->set('menu_params_'.$menu->id,$params,$menu->language);

                            $jrParams = json_decode($menu->params);

                            $extension = Sanitize::getString($jrParams,'extension');

                            $dir_id = Sanitize::getInt($jrParams,'dirid');

                            $cat_id = Sanitize::getInt($jrParams,'catid');

                            switch ($m_action)
                            {
                                case '0': // Directory menu

                                    $this->set('jr_directory_menu_id_'.$m_dir_id,$menu->id,$menu->language);
                                    break;

                                case '2': // Category menu

                                    $this->set('jr_category_menu_id_'.$m_cat_id,$menu->id,$menu->language);

                                    break;

                                case '3': // New listing submission

                                    if($dir_id == 0 && $cat_id == 0) {

                                        $this->set('jr_newlisting',$menu->id,$menu->language);
                                    }
                                    elseif($cat_id) {

                                        $this->set('jr_newlisting' . $cat_id, $menu->id, $menu->language);
                                    }

                                    break;

                                case '4': // Top user rated

                                    if($dir_id == 0 && $cat_id == 0) {

                                        $this->set('jr_listing_rating',$menu->id,$menu->language);
                                    }
                                    elseif($cat_id) {

                                        $this->set('jr_listing_rating' . $cat_id, $menu->id, $menu->language);
                                    }

                                    break;

                                case '5': // Top editor rated

                                    if($dir_id == 0 && $cat_id == 0) {

                                        $this->set('jr_listing_editor_rating',$menu->id,$menu->language);
                                    }
                                    elseif($cat_id) {

                                        $this->set('jr_listing_editor_rating' . $cat_id, $menu->id, $menu->language);
                                    }

                                    break;

                                case '6': // Latest listings

                                    if($dir_id == 0 && $cat_id == 0) {

                                        $this->set('jr_listing_rdate',$menu->id,$menu->language);
                                    }
                                    elseif($cat_id) {

                                        $this->set('jr_listing_rdate' . $cat_id, $menu->id, $menu->language);
                                    }

                                    break;

                                case '7': // Most popular listings

                                    if($dir_id == 0 && $cat_id == 0) {

                                        $this->set('jr_listing_rhits',$menu->id,$menu->language);
                                    }
                                    elseif($cat_id) {

                                        $this->set('jr_listing_rhits' . $cat_id, $menu->id, $menu->language);
                                    }

                                    break;

                                case '8': // Most reviewed listings

                                    if($dir_id == 0 && $cat_id == 0) {

                                        $this->set('jr_listing_reviews',$menu->id,$menu->language);
                                    }
                                    elseif($cat_id) {

                                        $this->set('jr_listing_reviews' . $cat_id, $menu->id, $menu->language);
                                    }

                                    break;

                                case '9': // Featured listings

                                    if($dir_id == 0 && $cat_id == 0) {

                                        $this->set('jr_listing_featured',$menu->id,$menu->language);
                                    }
                                    elseif($cat_id) {

                                        $this->set('jr_listing_featured' . $cat_id, $menu->id, $menu->language);
                                    }

                                    break;

                                case '10': // My reviews , generic - no extension or category selected

                                    if($extension == '' && $cat_id == 0) {

                                        $this->set('jr_myreviews',$menu->id,$menu->language);
                                    }

                                    break;

                                case '11':

                                    $m_criteria_id && $this->set('jr_advsearch_'.$m_criteria_id,$menu->id,$menu->language);

                                    !$m_criteria_id && $this->set('jr_advsearch',$menu->id,$menu->language);

                                    break;

                                case '12':

                                    if($dir_id == 0 && $cat_id == 0) {

                                        $this->set('jr_mylistings',$menu->id,$menu->language);
                                    }
                                    elseif($cat_id) {

                                        $this->set('jr_mylistings' . $cat_id, $menu->id, $menu->language);
                                    }

                                    break;

                                case '13':

                                    if($dir_id == 0 && $cat_id == 0) {

                                        $this->set('jr_myfavorites',$menu->id,$menu->language);
                                    }
                                    elseif($cat_id) {

                                        $this->set('jr_myfavorites' . $cat_id, $menu->id, $menu->language);
                                    }

                                    break;

                                case '18':

                                    $this->set('jr_reviewers',$menu->id,$menu->language);

                                    break;

                                case '23':

                                    $this->set('jr_mymedia',$menu->id,$menu->language);

                                    break;

                                case '25': // Alphabetical listings

                                    if($dir_id == 0 && $cat_id == 0) {

                                        $this->set('jr_listing_alpha',$menu->id,$menu->language);
                                    }
                                    elseif($cat_id) {

                                        $this->set('jr_listing_alpha' . $cat_id, $menu->id, $menu->language);
                                    }

                                    break;

                                case '105':

                                    $this->set('jr_viewallreviews'.$extension,$menu->id,$menu->language);

                                    break;

                                default:
                                    $this->set('jr_menu_id_action_'.$m_action,$menu->id,$menu->language);

                                    break;
                            }
                        }
                    break;
                }
            }

            Configure::write('System.___menu_data',$this->___menu_data);
        }
       // prx($this->___menu_data);exit;

    }

    /**
     * Sorts by link field in descending order
     */
    function sortMenuLinks($a, $b)
    {
        return strcmp($b->link,$a->link);
    }

    function get($property,$default=null)
    {
        if(isset($this->___menu_data[$this->language][$property])) {
            return $this->___menu_data[$this->language][$property];
        }
        elseif(isset($this->___menu_data['*'][$property])) {
            return $this->___menu_data['*'][$property];
        } else {
            return $default;
        }
    }

    function set($property, $value=null, $language = '*')
    {
        if(!isset($this->___menu_data[$language][$property])){
            $this->___menu_data[$language][$property] = $value;
        }
    }

    function getComponentMenuId($extension, $exact = false)
    {
        $exact = $exact ? '' : '%';

        if(!isset($this->___menu_data[$this->language][$extension]))
        {
            $query = '
                SELECT
                    id, language
                FROM
                    #__menu
                WHERE
                    link LIKE "%'.$extension.$exact.'" AND published = 1 AND type = "component"
                ';

            $rows = $this->query($query, 'loadObjectList');

            foreach($rows AS $row)
            {
                $row->language == '' and $row->language = '*';

                $this->___menu_data[$row->language][$extension] = $row->id;
            }
        }

        if(isset($this->___menu_data[$this->language][$extension])) {

            return $this->___menu_data[$this->language][$extension];
        }
        elseif(isset($this->___menu_data['*'][$extension])) {

            return $this->___menu_data['*'][$extension];
        }

        return false;
    }

    function getMenuAction($Itemid) {
        return $this->get('jr_menu_action_'.$Itemid, '');
    }

    function getMenuParams($Itemid)
    {
        $params = $this->get('menu_params_'.$Itemid,array());

        // June 2, 2016 - Fix for cover image not displaying in listing view all reviews page with category URLs
        // because they menu params are missing the 'extension' value

        if (Sanitize::getInt($params, 'action') == 2)
        {
            $params['extension'] = 'com_content';
        }

        return $params;
    }

    function getMenuName($Itemid) {
        return $this->get('jr_itemid_'.$Itemid, '');
    }

    function getMenuAlias($Itemid) {
        return $this->get('jr_id_alias_'.$Itemid, '');
    }

    function getMenuIdByAlias($menu_alias) {
        return $this->get('jr_alias_id_'.$menu_alias, '');
    }

    function getMenuId($menu_name) {
        return $this->get('jr_menu_'.$menu_name, '');
    }

    function getMenuIdByViewParams($view, $params = array())
    {
        $attributes = array('link');

        $values = array('index.php?option=com_jreviews&view=' . $view);

        // Check if additional menu filters were set

        $filters = Sanitize::getVar($params, 'filters', array());

        unset($params['filters']);

        foreach($filters AS $key => $val)
        {
            $attributes[] = $key;

            $values[] = $val;
        }

        $menus = JFactory::getApplication()->getMenu()->getItems($attributes, $values, empty($params) ? true : false);

        if($menus && !is_array($menus))
        {
            return $menus->id;
        }
        elseif($menus)
        {
            foreach($menus AS $menu)
            {
                $check = true;

                foreach($params AS $key=>$val)
                {
                    if($menu->params->get($key) != $val)
                    {
                        $check = false;
                    }
                }

                if($check == true)
                {
                    return $menu->id;
                }
            }
        }

        return false;
    }

    function getMenuIdByAction($action_id)
    {
        return $this->get('jr_menu_id_action_'.$action_id, '');
    }

    function isSearchPage($menuId)
    {
        $menuParams = $this->getMenuParams($menuId);

        if (Sanitize::getInt($menuParams,'action') == 11)
        {
            return Sanitize::getInt($menuParams, 'criteriaid');
        }

        return false;
    }

    function getSearch($listingTypeId = '')
    {
        $menu_id = 0;

        if($listingTypeId)
        {
            $menu_id = $this->get('jr_advsearch_' . $listingTypeId);
        }

        if(!$menu_id)
        {
            $menu_id = $this->get('jr_advsearch');
        }

        return $menu_id;
    }

    function getDir($id)
    {
        $menu_id = $this->get('jr_directory_menu_id_'.$id);

        if((int)$menu_id === 0)
        {
            $menu_id = $this->get('jr_directory_menu_id_0','');
        }

        return $menu_id;
    }

    function getCategory()
    {
        $core = null;

        $jr = null;

        $cat_id = $dir_id = 0;

        # Process parameters whether passed individually or as an array
        $params = func_get_args();

        extract(array_shift($params));

        // Process article urls using Joomla core menus
        if(!empty($listing) || defined('JREVIEWS_SEF_PLUGIN'))
        {
            if(!empty($listing))
            {
                $core = $this->get('core_content_menu_id_'.$listing,'');

                if($core!='') return $core;
            }

            $core = $this->get('core_category_menu_id_'.$cat_id);

            if($core!='') return $core;

            $parent_cat_ids = $this->get('parent_cat_ids_'.$cat_id);

            if(!$parent_cat_ids)
            {
                $parent_cat_ids = $this->getParentCatIds($cat_id);

                if(!$parent_cat_ids) return false;

                $this->set('parent_cat_ids_'.$cat_id,$parent_cat_ids);
            }

            # Loop through parent categories to find the correct Itemid
            foreach($parent_cat_ids AS $pcat_id)
            {
                $parent_cat_id = Sanitize::getInt($pcat_id,'cat_id');

                $tmp = $this->get('core_category_menu_id_'.$parent_cat_id);

                if($tmp)
                {
                    $this->set('core_category_menu_id_'.$parent_cat_id, $tmp,$this->language);

                    $core = $tmp;
                }
            }

            if($core) return $core;

            if(cmsFramework::getConfig('sef') == 1 && !empty($listing)) {

                // There's a problem with core sef urls having Itemids from non-core menus, so we make sure the JReviews menu ids are not used
                return false;
            }
        }

        // Process JReviews category urls using JReviews menus
        $parent_cat_ids = $this->get('parent_cat_ids_'.$cat_id);

        if(!$parent_cat_ids)
        {
            $parent_cat_ids = $this->getParentCatIds($cat_id);

            $this->set('parent_cat_ids_'.$cat_id,$parent_cat_ids);
        }

        # Loop through parent jr categories to find the correct Itemid
        foreach($parent_cat_ids AS $pcat_id)
        {
            $cat_id = Sanitize::getInt($pcat_id,'cat_id');

            if($cat_id) {

                $tmp = $this->get('jr_category_menu_id_'.$cat_id);

                if($tmp)
                {
                    $this->set('jr_category_menu_id_'.$cat_id,$tmp,$this->language);

                    $jr = $tmp;
                }
            }
        }

        if($jr) return $jr;

        return $this->getDir($dir_id);
    }

    function getParentCatIds($cat_id)
    {
        $cat_id = (int) $cat_id;

        if(!$cat_id)
        {
            return array();
        }

        # Check for cached version
        $cache_file = S2CacheKey('jreviews_menu_cat',cmsFramework::getCustomToken($cat_id));

        if($cache = S2Cache::read($cache_file,'_menu_')){
            return $cache['___menu_cat'];
        }

        $query = "
            SELECT
                path
            FROM
                #__categories
            WHERE
                id = " . $cat_id
        ;

        $path = $this->query($query, 'loadResult');

        if(!$path)
        {
            return array();
        }

        $ancestors = array($this->Quote($path));

        $parts = explode('/', $path);

        while($parts)
        {
            array_pop($parts);

            if(empty($parts)) break;

            array_unshift($ancestors, $this->Quote(implode('/', $parts)));
        }

        if(empty($ancestors))
        {
            return array();
        }

        $query = '
            SELECT
                id AS cat_id, lft
            FROM
                #__categories
            WHERE
                path IN ( ' . implode(',', $ancestors) . ')'
        ;

        $rows = $this->query($query, 'loadObjectList');

        S2Cache::write($cache_file, array('___menu_cat'=>$rows),'_menu_');

        return $rows;
    }

    function getReviewers()
    {
        return $this->get('jr_reviewers');
    }

    function addMenuListing($results)
    {
        foreach ($results AS $key=>$row)
        {
            $dir_id = isset($row['Directory']) ? Sanitize::getInt($row['Directory'],'dir_id') : null;

            $results[$key]['Listing']['menu_id'] = $this->getCategory(array(
                'cat_id'=>$row['Listing']['cat_id'],
                'dir_id'=>$dir_id,
                'listing'=>$row['Listing']['listing_id']
            ));

            $results[$key]['Category']['menu_id'] =   $this->getCategory(array('cat_id'=>$row['Listing']['cat_id'],'dir_id'=>$dir_id));

            $results[$key]['Category']['menu_id_base'] = $this->get('jr_category_menu_id_'.$row['Listing']['cat_id']);

            $results[$key]['Directory']['menu_id'] =  $this->getDir($dir_id);
        }

        return $results;
    }

    function addMenuCategory($results)
    {
        foreach ($results AS $key=>$value)
        {
            $results[$key]['Category']['menu_id'] = $this->getCategory(array('cat_id'=>$value['Category']['cat_id'], 'dir_id'=>Sanitize::getInt($value['Category'],'dir_id',Sanitize::getInt($value['Directory'],'dir_id'))));
        }

        return $results;
    }

    function addMenuDirectory($results)
    {
         foreach ($results AS $key=>$value) {
            $results[$key]['Directory']['menu_id'] = $this->getDir($value['Directory']['dir_id']);
        }

        return $results;
    }
}
