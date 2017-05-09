<?php
/**
 * JReviews - Reviews Extension
 * Copyright (C) 2010-2011 ClickFWD LLC
 * This is not free software, do not distribute it.
 * For licencing information visit http://www.reviewsforjoomla.com
 * or contact sales@reviewsforjoomla.com
**/

defined( 'MVC_FRAMEWORK') or die( 'Direct Access to this location is not allowed.' );

class MenuModel extends MyModel  {

    var $___menu_data = array();    
    var $language;
        
    function __construct()
    {    
		parent::__construct();
		
		$this->language = cmsFramework::locale();

		# Check for cached version        
        $cache_file = 'jreviews_menu_'.cmsFramework::locale().'_'.md5(cmsFramework::getConfig('secret'));
        
        if($cache = S2Cache::read($cache_file)){ 
            $this->___menu_data = $cache['___menu_data'];
            return;
        }    
                
        $menuList = array();

        if($this->cmsVersion == CMS_JOOMLA15) 
        {
                $select = "
                    SELECT 
                        id,
                        name,
						alias AS alias,
                        link AS menu_type,
                        link,
                        componentid,
                        params,
                        access,
                        published,
                        '*' AS language
                ";
        } 
        else {
                $select = "
                    SELECT 
                        id, 
                        title AS name,
						alias AS alias,
                        link AS menu_type,
                        link,
                        component_id AS componentid,
                        params,
                        access,
                        published,
                        language
                ";
        }
        
        // Get all com_content category/section menus and JReviews menus
        $sql = $select . "
            FROM #__menu
            WHERE published = 1
            ORDER BY link DESC
        ";
        
        $this->_db->setQuery($sql);
        $menuList = $this->_db->loadObjectList();

        // Get itemid for each menu link and store it
        if(!empty($menuList)) 
        {
            foreach ($menuList as $menu) 
            {
                $params = stringToArray($menu->params);
                $m_name = Sanitize::getVar($params,'sef_name')!='' ? Sanitize::getVar($params,'sef_name') : $menu->name;
                $menu->language == '' and $menu->language = '*';
                
//                function_exists("sefEncode") and $m_name = sefEncode($m_name);
                
                $m_action = Sanitize::getVar($params,'action');
                $m_dir_id = str_replace(",","-",Sanitize::getVar($params,'dirid'));
                $m_cat_id = str_replace(",","-",Sanitize::getVar($params,'catid'));
                $m_section_id = str_replace(",","-",Sanitize::getVar($params,'sectionid'));
                $m_criteria_id = str_replace(",","-",Sanitize::getVar($params,'criteriaid'));
                
                // Create a variable to get Menu Name from Itemid
                $this->set('jr_itemid_'.$menu->id,$m_name,$menu->language);
				$this->set('jr_menu_'.$m_name,$menu->id,$menu->language);

				$this->set('jr_id_alias_'.$menu->id,$menu->alias,$menu->language);
				$this->set('jr_alias_id_'.$menu->alias,$menu->id,$menu->language);

                
                if(strpos($menu->menu_type,'option=com_content&view=section&id=') || strpos($menu->menu_type,'option=com_content&view=section&layout=blog&id=')) 
                {
                    $menu->componentid = end(explode('id=',$menu->menu_type));                            
                    $menu->menu_type = 'content_section';
                } 
                elseif (strpos($menu->menu_type,'option=com_content&view=category&id=') || strpos($menu->menu_type,'option=com_content&view=category&layout=blog&id=')) 
                { 
                    $menu->componentid = end(explode('id=',$menu->menu_type));                            
                    $menu->menu_type = 'content_category';
                } 
                elseif(strpos($menu->menu_type,'option=com_content&view=article&id=') || strpos($menu->menu_type,'option=com_content&task=view&id='))
                {
                    $menu->componentid = end(explode('id=',$menu->menu_type));                    
                    $menu->menu_type = 'content_item_link';                        
                }
                           
                switch($menu->menu_type) 
                {
                    case 'content_section': case 'content_blog_section':
    
                            if ($menu->componentid) 
                            { // Only one section id
                                $this->set('core_section_menu_id_'.$menu->componentid,$menu->id,$menu->language);
                            } else 
                            {
                                $section_ids = explode(",",Sanitize::getVar($params,'sectionid'));
                                $this->set('jr_manyIds_'.$menu->id,1,$menu->language);
                                
                                foreach($section_ids AS $section_id) 
                                {
                                    $this->set('core_section_menu_id_'.$section_id,$menu->id,$menu->language);
                                }
                            }
                        break;  
                    case 'content_category': case 'content_blog_category':
    
                        if ($menu->componentid) 
                        { // Only one category id
                            $this->set('core_category_menu_id_'.$menu->componentid,$menu->id,$menu->language);
                        } 
                        else 
                        {
                            $cat_ids = explode(",",Sanitize::getVar($params,'categoryid'));
                            $this->set('jr_manyIds_'.$menu->id,1,$menu->language);
                            
                            foreach($cat_ids AS $cat_id) {
                                $this->set('core_category_menu_id_'.$cat_id,$menu->id,$menu->language);
                            }
                        }
                        break;     
                    case 'content_item_link':
                            $this->set('core_content_menu_id_'.$menu->componentid,$menu->id,$menu->language);
                            
                        break;                        
                    default: 
                        if ($menu->componentid > 0 && strstr($menu->link,'index.php?option='.S2Paths::get('jreviews','S2_CMSCOMP')))
                        { // It's a JReviews menu
                        
                            $access = $this->cmsVersion == CMS_JOOMLA15 ? 0 : 1;
                            // Get a JReviews menu with public access to use in ajax requests
                            if($menu->access == $access && $menu->published == 1) {
                                $this->set('jreviews_public',$menu->id,$menu->language);
                            }

                            $this->set('jr_menu_action_'.$m_dir_id,$m_action,$menu->language);
                            $this->set('menu_params_'.$menu->id,$params,$menu->language);
                            
                            switch ($m_action) 
                            {
                                case '0': // Directory menu
                                    $this->set('jr_directory_menu_id_'.$m_dir_id,$menu->id,$menu->language);
                                    break;
                                case '1': // Section menu
                                    $this->set('jr_section_menu_id_'.$m_section_id,$menu->id,$menu->language);
                                    break;
                                case '2': // Category menu
                                    $this->set('jr_category_menu_id_'.$m_cat_id,$menu->id,$menu->language);
                                    break;
                                case '10':
                                    $this->set('jr_myreviews',$menu->id,$menu->language);
                                    break;
                                case '11':
                                    $m_criteria_id && $this->set('jr_advsearch_'.$m_criteria_id,$menu->id,$menu->language);
                                    !$m_criteria_id && $this->set('jr_advsearch',$menu->id,$menu->language);
                                    break;
                                case '12':
                                    $this->set('jr_mylistings',$menu->id,$menu->language);
                                    break;
                                case '18':
                                    $this->set('jr_reviewers',$menu->id,$menu->language);
                                    break;
                                default:
                                    $this->set('jr_menu_id_action_'.$m_action,$menu->id,$menu->language);
                                    break;
                            }
                        }
                    break;
                }
            } 
        
            S2Cache::write($cache_file, array('___menu_data'=>$this->___menu_data));
        }
//        prx($this->___menu_data);exit;

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
            if($this->cmsVersion == CMS_JOOMLA15) 
            {
                $query = '
                    SELECT 
                        id
                    FROM 
                        #__menu 
                    WHERE 
                        link LIKE "%'.$extension.$exact.'" AND published = 1 AND type = "component"
                    ';
                
                $this->_db->setQuery($query);
                
                $this->___menu_data['*'][$extension] = $this->_db->loadResult();
            }
            else {
                $query = '
                    SELECT 
                        id, language
                    FROM 
                        #__menu 
                    WHERE 
                        link LIKE "%'.$extension.$exact.'" AND published = 1 AND type = "component"
                    ';
                
                $this->_db->setQuery($query);
                
                $rows = $this->_db->loadObjectList();
                foreach($rows AS $row) {
                    $row->language == '' and $row->language = '*';
                    $this->___menu_data[$row->language][$extension] = $row->id;
                }
            }
        }

        if(isset($this->___menu_data[$this->language][$extension])) {
            return $this->___menu_data[$this->language][$extension];
        }    

        return $this->___menu_data['*'][$extension];
    }    

    function getMenuAction($Itemid) {
        return $this->get('jr_menu_action_'.$Itemid, '');
    }
    
    function getMenuParams($Itemid) {
        return $this->get('menu_params_'.$Itemid,array());
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

    function getMenuIdByAction($action_id)
    {
        return $this->get('jr_menu_id_action_'.$action_id, '');
    }
    
    function getDir($id) 
    {
        $menu_id = $this->get('jr_directory_menu_id_'.$id);
        if((int)$menu_id === 0) {
            $menu_id = $this->get('jr_directory_menu_id_0','');            
        }
        return $menu_id;
    }

    function getSection($id,$dir_id, $listing = false) 
    {
        if($listing) {
            $core = $this->get('core_section_menu_id_'.$id);
            if($core!='') {
                return $core;
            } elseif ($this->cmsVersion == CMS_JOOMLA15 && cmsFramework::getConfig('sef') == 1) {
                // There's a problem with J1.5 core sef urls with Itemids from non-core menus, so we make sure the jReviews menu ids are not used
                return false;
            }
        } 

        return $this->get('jr_section_menu_id_'.$id, $this->getDir($dir_id));            
        
    }

    function getCategory() 
    {
        $core = null;
        $jr = null;
        
        # Process parameters whether passed individually or as an array
        $params = func_get_args();            

        $keys = array('cat_id','section_id','dir_id','listing');
        if(count($params)>1)
        {  // Individual parameters 
             while($params)
             ${array_shift($keys)} = array_shift($params);
        }
        else 
        {
            extract(array_shift($params));   
        }   

        // Process article urls using Joomla core menus
        if(!empty($listing)) 
        {
            $core = $this->get('core_content_menu_id_'.$listing,'');

            if($core!='') return $core;

            $core = $this->cmsVersion == CMS_JOOMLA15 
                ? 
                $this->get('core_category_menu_id_'.$cat_id, $this->getSection($section_id,$dir_id, $listing)) 
                : 
                $this->get('core_category_menu_id_'.$cat_id);

            if($core!='') return $core;
        
            if($this->cmsVersion != CMS_JOOMLA15)
            {
                $parent_cat_ids = $this->get('parent_cat_ids_'.$cat_id);
                
                if(!$parent_cat_ids)
                {
                    $parent_cat_ids = $this->getParentCatIds($cat_id);
                    $this->set('parent_cat_ids_'.$cat_id,$parent_cat_ids);
                }
                
                # Loop through parent categories to find the correct Itemid
                foreach($parent_cat_ids AS $pcat_id)
                {         
                    $tmp = $this->get('core_category_menu_id_'.$pcat_id->cat_id);
                    if($tmp) 
                    {
                        $this->set('core_category_menu_id_'.$pcat_id->cat_id,$tmp,$this->language);
                        $core = $tmp;
                    }
                }
                if($core) return $core;
            }
            
            if(cmsFramework::getConfig('sef') == 1) {
                // There's a problem with core sef urls having Itemids from non-core menus, so we make sure the JReviews menu ids are not used
                return false;
            }
        }        

        // Process JReviews category urls using JReviews menus
        if($this->cmsVersion == CMS_JOOMLA15)
        {             
            return $this->get('jr_category_menu_id_'.$cat_id, $this->getSection($section_id,$dir_id));
        }

        $parent_cat_ids = $this->get('parent_cat_ids_'.$cat_id);
 
        if(!$parent_cat_ids)
        {
            $parent_cat_ids = $this->getParentCatIds($cat_id);
            $this->set('parent_cat_ids_'.$cat_id,$parent_cat_ids);
        }

        # Loop through parent jr categories to find the correct Itemid
        foreach($parent_cat_ids AS $pcat_id)
        {         
            $tmp = $this->get('jr_category_menu_id_'.$pcat_id->cat_id);
            if($tmp) 
            {
                $this->set('jr_category_menu_id_'.$pcat_id->cat_id,$tmp,$this->language);
                $jr = $tmp;
            }
        }
        if($jr) return $jr;        
        
        return $this->getDir($dir_id);
    }
    
    function getParentCatIds($cat_id)
    {             
		# Check for cached version        
        $cache_file = 'jreviews_menu_cat_'.cmsFramework::locale().'_'.cmsFramework::getCustomToken($cat_id);
		
        if(Configure::read('Cache.query') && $cache = S2Cache::read($cache_file)){ 
            return $cache['___menu_cat'];
        }
		
        $query = "
        (
            SELECT 
                ParentCategory.id AS cat_id,
                ParentCategory.lft AS lft
            FROM 
                #__categories AS Category, 
                #__categories AS ParentCategory
            INNER JOIN
                #__jreviews_categories AS JreviewsCategory ON JreviewsCategory.id = ParentCategory.id
            WHERE 
                (
                    ParentCategory.id = " . (int) $cat_id . " AND ParentCategory.published = 1
                )
        )
        UNION
        (
            SELECT 
                ParentCategory.id AS cat_id,
                ParentCategory.lft AS lft
            FROM
                #__categories AS Category,
                #__categories AS ParentCategory
            INNER JOIN
                #__jreviews_categories AS JreviewsCategory ON JreviewsCategory.id = ParentCategory.id
            WHERE
                (            
                    Category.published = 1
                    AND Category.lft BETWEEN ParentCategory.lft AND ParentCategory.rgt
                    AND Category.id = " . (int) $cat_id . "
                    AND ParentCategory.parent_id > 0
                )
            ORDER BY 
                Category.lft
        )
        ";
        
        
		$rows = $this->query($query, 'loadObjectList');
		
		$last = array_shift($rows);
		
		array_push($rows, $last);
		
        Configure::read('Cache.query') and S2Cache::write($cache_file, array('___menu_cat'=>$rows));
		
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
            $section_id = Sanitize::getInt($row['Listing'],'section_id');
            $dir_id = isset($row['Directory']) ? Sanitize::getInt($row['Directory'],'dir_id') : null;
            $results[$key]['Listing']['menu_id'] = $this->getCategory($row['Listing']['cat_id'],$section_id,$dir_id,$row['Listing']['listing_id']);
            isset($row['Section']) and /*J16*/ $results[$key]['Section']['menu_id'] = $this->getCategory($row['Listing']['cat_id'],$section_id,$row['Directory']['dir_id']);
            $results[$key]['Category']['menu_id'] =   $this->getCategory($row['Listing']['cat_id'],$section_id,$dir_id);
         
        }
        return $results;
    }
    
    function addMenuSection($results) 
    {
        foreach ($results AS $key=>$value) {
            $results[$key]['Section']['menu_id'] = $this->getSection($value['Section']['section_id'], $value['Section']['dir_id']);
        }

        return $results;
    }        
        
    function addMenuCategory($results) 
    {
        foreach ($results AS $key=>$value) 
        {
            $results[$key]['Category']['menu_id'] = $this->getCategory($value['Category']['cat_id'], Sanitize::getInt($value['Category'],'section_id'), Sanitize::getInt($value['Category'],'dir_id',Sanitize::getInt($value['Directory'],'dir_id')));
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
