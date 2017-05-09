<?php
/**
 * JReviews - Reviews Extension
 * Copyright (C) 2006-2008 ClickFWD LLC
 * This is not free software, do not distribute it.
 * For licencing information visit http://www.reviewsforjoomla.com
 * or contact sales@reviewsforjoomla.com
**/

defined( 'MVC_FRAMEWORK') or die( 'Direct Access to this location is not allowed.' );

class CategoryModel extends MyModel  
{
    var $useTable = '#__categories AS Category';
    var $primaryKey = 'Category.cat_id';
    var $realKey = 'id';
    
    var $fields = array(
        'cat_id'=>'Category.id AS `Category.cat_id`', 
                    'Category.title AS `Category.title`', 
                    'Category.alias AS `Category.slug`', 
                    'Category.level AS `Category.level`', 
                    'Category.params AS `Category.params`', 
                    'Category.parent_id AS `Category.parent_id`', 
                    'JreviewsCategory.criteriaid AS `Category.criteria_id`', 
                    'JreviewsCategory.tmpl AS `Category.tmpl`', 
                    'JreviewsCategory.tmpl_suffix AS `Category.tmpl_suffix`', 
                    'Directory.id AS `Directory.dir_id`', 
                    'Directory.desc AS `Directory.title`', 
                    'Directory.title AS `Directory.slug`',             
                    'ListingType.config AS `ListingType.config`'
    );  
                   
    var $joins = array(
        'INNER JOIN #__jreviews_categories AS JreviewsCategory ON Category.id = JreviewsCategory.id AND JreviewsCategory.option = "com_content"',
        'LEFT JOIN #__jreviews_directories AS Directory ON JreviewsCategory.dirid = Directory.id',
        'LEFT JOIN #__jreviews_criteria AS ListingType ON JreviewsCategory.criteriaid = ListingType.id'
    );
    
    function __construct() 
    {       
        parent::__construct();   
        if($this->cmsVersion == CMS_JOOMLA15)
        {
            $this->fields = array(
                'Category.id AS `Category.cat_id`',
                'Category.section AS `Category.section_id`',
                'Category.title AS `Category.title`',
                'Category.alias AS `Category.slug`',        
                'Category.image AS `Category.image`',
                'Category.image_position AS `Category.image_position`',
                'Category.description AS `Category.description`',        
                'Category.access AS `Category.access`',
                'Category.published AS `Category.published`',
                'Directory.id AS `Category.dir_id`'/*Not sure if necessary...*/, 
                'JreviewsCategory.criteriaid AS `Category.criteria_id`',
                'JreviewsCategory.tmpl AS `Category.tmpl`',
                'JreviewsCategory.tmpl_suffix AS `Category.tmpl_suffix`',
                'Directory.id AS `Directory.dir_id`', 
                'Directory.desc AS `Directory.title`', 
                'Directory.title AS `Directory.slug`',       
                'ListingType.config AS `ListingType.config`'
            );            
        }
    }
    
    /**
     * J15 only method  
     * Advanced Search Module
     * Generate Section-Category tree array
     */
    function categoryTree($gid, $settings) 
    {
        # Check for cached version        
        $cache_prefix = 'category_model_categorytree';
        $cache_key = func_get_args();
        if($cache = S2cacheRead($cache_prefix,$cache_key)){
            return $cache;
        }            
        
        $Access = Configure::read('JreviewsSystem.Access');   
        
        # Get module parameters
        $module_id = Sanitize::getInt($settings,'module_id');
        $criteria_id = cleanIntegerCommaList(Sanitize::getString($settings['module'],'criteria_id'));
        $dir_id = cleanIntegerCommaList(Sanitize::getString($settings['module'],'dir_id'));
        $section_id = cleanIntegerCommaList(Sanitize::getString($settings['module'],'section_id'));
        $category_id = cleanIntegerCommaList(Sanitize::getString($settings['module'],'cat_id'));        
        $cat_order_alpha = Sanitize::getInt($settings['module'],'cat_order_alpha',1);
        $section_title = Sanitize::getString($settings['module'],'section_title',1);
        $section_bg = Sanitize::getString($settings['module'],'section_bg','#CCCCCC');
        $category_bg = Sanitize::getString($settings['module'],'category_bg','#FFFFFF');
        $section_color = Sanitize::getString($settings['module'],'section_color','#000000');
        $category_color = Sanitize::getString($settings['module'],'category_color','#000000');
        $option_length = Sanitize::getInt($settings['module'],'option_length','');
        $cat_auto = Sanitize::getInt($settings['module'],'cat_auto');
    
        # Selected categories and sections
        $selOption = explode('_',Sanitize::getString($settings,'cat'));
        $cat_auto and is_numeric($category_id) and $selOption = array($category_id);
        $selSection = Sanitize::getString($settings,'section');
        $cat_auto and is_numeric($section_id) and $selSection = $section_id;
                
        $order = array();
        $conditions = array();
        
        $order[] = $cat_order_alpha ? "Section.title ASC" : "Section.ordering ASC";        
        $order[] = $cat_order_alpha ? "Category.title ASC" : "Category.ordering ASC";
        
            
        if (!$section_id && $dir_id) {
            $conditions[] = "JreviewCategory.dirid IN ($dir_id)";
        } 
        
        if (!$section_id && $criteria_id) {
            $conditions[] = "JreviewCategory.criteriaid IN ($criteria_id)";
        }
        
        if ($section_id) 
        {
            $conditions[] = "Category.section IN ($section_id)";
        } 
        elseif ($category_id) 
        {
            $conditions[] = "Category.section IN (SELECT section FROM #__categories WHERE id IN ({$category_id}))";
        }
        
        $conditions[] = "Category.published = 1";
        $conditions[] = "Category.access <= '". $Access->getAccessId() ."'";
        $conditions[] = "JreviewCategory.option = 'com_content'";
    
        if($cat_auto && $section_id == '' && $category_id == '' && $criteria_id == '' && $dir_id == '')
        {
            array_pop($order);
            $query = "SELECT DISTINCT Category.section AS sectionid,"
            . ($option_length > 0 ? "\n CONCAT(SUBSTR(Section.title,1,".$option_length."),'...') AS section" : "\n Section.title AS section")
            . "\n FROM #__jreviews_categories AS JreviewCategory"
            . "\n LEFT JOIN #__categories AS Category ON Category.id = JreviewCategory.id"
            . "\n LEFT JOIN #__sections AS Section ON Category.section = Section.id"
            . "\n WHERE " . implode(" AND \n", $conditions)
            . "\n ORDER BY " . implode(",",$order)
            ;
        }
        else 
        {
            $query = "SELECT Category.id AS catid, Category.section AS sectionid,"
            . ($option_length > 0 ? "\n CONCAT(SUBSTR(Category.title,1,".$option_length."),'...') AS category," : "\n Category.title AS category,")
            . ($option_length > 0 ? "\n CONCAT(SUBSTR(Section.title,1,".$option_length."),'...') AS section" : "\n Section.title AS section")
            . "\n FROM #__jreviews_categories AS JreviewCategory"
            . "\n LEFT JOIN #__categories AS Category ON Category.id = JreviewCategory.id"
            . "\n LEFT JOIN #__sections AS Section ON Category.section = Section.id"
            . "\n WHERE " . implode(" AND \n", $conditions)
            . "\n ORDER BY " . implode(",",$order)
            ;
        }
            
        $this->_db->setQuery($query);
        $options = $this->_db->loadObjectList();
        
        $selSection > 0 and array_push($selOption,'s'.$selSection);    
        
        // Start building section/category select list
        $categoryList = array();
        $categoryList[] = '<select name="data[categories]" class="jrSelect">';
        $categoryList[] = '<option value="">'.__t("Select Category",true) . '</option>';

        isset($options[0]) and $prevSection = $options[0]->sectionid;

        if($options) 
        {
            foreach($options AS $key=>$option) 
            {
                $selected = '';
                
                if(($option->sectionid == $prevSection && $key > 0) || !$section_title)  
                { // Add categories
                    if(in_array($option->catid,$selOption)) {
                        $selected = 'selected="selected"';
                    }

                    isset($option->catid) and $categoryList[] = '<option value="'.$option->catid.'" style="color:'.$category_color.';background-color:'.$category_bg.'" '.$selected.'>&nbsp;&nbsp;&nbsp;' . stripslashes($option->category) . '</option>';
                } 
                else 
                { // Add section
                    in_array('s'.$option->sectionid,$selOption) and $selected = 'selected="selected"';
        
                    $categoryList[] = '<option value="s'.$option->sectionid.'" style="font-weight:bold;color:'.$section_color.';background-color:'.$section_bg.';" '.$selected.'>'. stripslashes($option->section) . '</option>';

                    $selected = '';

                    if(isset($option->catid)) 
                    {
                        in_array($option->catid,$selOption) and $selected = 'selected="selected"';
                        $categoryList[] = '<option value="'.$option->catid.'" style="color:'.$category_color.';background-color:'.$category_bg.'" '.$selected.'>&nbsp;&nbsp;&nbsp;' . stripslashes($option->category) . '</option>';
                    }
                }
                $prevSection = $option->sectionid;
            }
        }

        $categoryList[] = '</select>';
        
        $categorySelect = implode("\n",$categoryList);
        
        # Send to cache
        S2cacheWrite($cache_prefix,$cache_key,$categorySelect);
                
        return $categorySelect;
    }
    
    /**
    * Returns simple tree array
    * Uses: cat tree in paidlistings
    * @param mixed $options
    */
    function getTree($options = array())
    {
        $nodes = array();
        $json = Sanitize::getBool($options,'json');
        $conditions = Sanitize::getVar($options,'conditions') ? implode(' AND ', $options['conditions']) : false;
        $query = "
            SELECT 
               Section.id AS section_id, Section.title AS section_title, Category.id AS cat_id, Category.title AS cat_title
            FROM 
                #__categories AS Category
            RIGHT JOIN 
                #__sections AS Section ON Section.id = Category.section
            WHERE 
                1 = 1 
            " . ($conditions ? " AND (" . $conditions . ") " : '') . "
             ORDER BY
               Section.title, Category.title        
        ";
        $this->_db->setQuery($query);
        $rows = $this->_db->loadAssocList();
        // Build auxiliary arrays
        foreach($rows AS $row)
        {
            $sections[$row['section_id']] = array(
                "attr"=>array("id"=>"s".$row['section_id']),
                "data"=>$row['section_title'],
                "state"=>"closed"
            );
            $cat = array(
                "attr"=>array("id"=>$row['cat_id']),
                "data"=>$row['cat_title']
            );
            $categories[$row['section_id']][] = $cat;
        }
        foreach($sections AS $section_id=>$section)
        {
            $section['children'] = $categories[$section_id];
            $nodes[] = $section;
        }

        return $json ? json_encode($nodes) : $nodes;    
    }

    /**
     * Checks if core category is setup for jReviews
     */
    function isJreviewsCategory($cat_id) 
    {
        $query = "
            SELECT 
                count(*)
            FROM 
                #__jreviews_categories AS JreviewCategory
            WHERE
                JreviewCategory.id = " . (int) $cat_id . "
                AND
                JreviewCategory.option = 'com_content'
                AND
                JreviewCategory.criteriaid > 0
        ";
        $this->_db->setQuery($query);   
        return $this->_db->loadResult();       
    }
    
    /**
     * Used in Administration in controllers:
     *         admin_listings_controller.php
     * Also used in Frontend listings_controller.php in create function.
     */
    function getList($section_id, $cat_ids = '') 
    {                    
        $Access = Configure::read('JreviewsSystem.Access');   
        
        $query = "
            SELECT 
                Category.id AS value, Category.title AS text, Criteria.config AS config, Criteria.id 
            FROM 
                #__categories AS Category
            RIGHT JOIN 
                #__jreviews_categories AS JreviewsCategory ON JreviewsCategory.id = Category.id AND JreviewsCategory.`option` = 'com_content'
            LEFT JOIN
                #__jreviews_criteria AS Criteria On JreviewsCategory.criteriaid = Criteria.id
            WHERE (
                1 = 1
                " .
                 ( !defined('MVC_FRAMEWORK_ADMIN') ? ' AND Category.published = 1 ': '')
                . "
                AND Category.access <= " . $Access->getAccessId() . "
                AND Category.section IN ({$section_id})
                ". ($cat_ids != '' ? "\n AND Category.id IN ({$cat_ids})" : '') . "
                )
            ORDER 
                BY Category.title
        ";

        $this->_db->setQuery($query);
        
        $categories = $this->_db->loadObjectlist();  
        
        // For admin use return all categories
        if(defined('MVC_FRAMEWORK_ADMIN')) return $categories;
                  
        foreach($categories AS $key=>$cat)
        {
            if($cat->config != '')
            {   
                $config = json_decode($cat->config,true);
                if(!$Access->canAddListing(Sanitize::getVar($config,'addnewaccess')))
                {
                    unset($categories[$key]);
                }
            }
        }
        return $categories;
    }
    
    /**
     * Used in Administration in controllers:
     *         categories_controller.php
     *         themes_controller.php
     */
    function getRows($sectionid, $limitstart=0, $limit, &$total) 
    {
        $where = $sectionid ? "\n AND sec.id = '$sectionid'" : '';
    
        // get the total number of records
        $query = "SELECT COUNT(*) FROM `#__jreviews_categories` AS jrcat"
        . "\n LEFT JOIN #__categories AS cat ON cat.id = jrcat.id"
        . "\n LEFT JOIN #__sections AS sec ON sec.id = cat.section"
        ."\n WHERE jrcat.option = 'com_content'"
        . $where
        ;
        $this->_db->setQuery( $query );
        
        $total = $this->_db->loadResult();
    
        $query = "SELECT jrcat.*, c.title as cat, d.desc as dir, sec.title as section, cr.title as criteria"
         ."\n FROM #__jreviews_categories jrcat"
         ."\n LEFT JOIN #__categories c on jrcat.id = c.id"
         ."\n LEFT JOIN #__sections sec on c.section = sec.id"
         ."\n LEFT JOIN #__jreviews_criteria cr on jrcat.criteriaid = cr.id"
         ."\n LEFT JOIN #__jreviews_directories d on jrcat.dirid = d.id"
         ."\n WHERE jrcat.option = 'com_content'"
         . $where
         ."\n ORDER BY section ASC, cat ASC"
         ."\n LIMIT $limitstart, $limit"
         ;
        
        $this->_db->setQuery($query);
        
        $rows = $this->_db ->loadObjectList();
        
        if(!$rows) {
            $rows = array();
        }
        return $rows;
    }
    
    /**
     * Used in Administration... need to clean up
     * Generates a list of new categories to set up. Used in controllers:
     *         categories_controller.php
     */    
    function getSelectList() 
    {
        # Find category ids already set up
        $query = "SELECT id FROM #__jreviews_categories"
        . "\n WHERE `option` = 'com_content'"
        ;
        $this->_db->setQuery($query);
    
        if($exclude = $this->_db->loadResultArray()) {
            $exclude = implode(',',$exclude);
        } else {
            $exclude = '';
        }
    
        $query = "SELECT Category.id AS value, CONCAT(Section.title,'>>',Category.title) AS text"
        . "\n FROM #__categories AS Category"
        . "\n INNER JOIN  #__sections AS Section ON Category.section = Section.id"
        . ($exclude != '' ? "\n WHERE Category.id NOT IN ($exclude)" : '')
        . "\n ORDER BY Section.title ASC, Category.title ASC"
        ;
        $this->_db->setQuery($query);
        
        $results = $this->_db->loadObjectList();

        return $results;    
    }
    
    /*J15 category and parent section used for theming*/
    function findSectionCat($cat_id) 
    {
        $categories = array();
        $fields = array(
            'JreviewsSection.tmpl AS `Section.tmpl_list`',
            'JreviewsSection.tmpl_suffix AS    `Section.tmpl_suffix`',
            'JreviewsCategory.tmpl AS `Category.tmpl_list`',
            'JreviewsCategory.tmpl_suffix AS `Category.tmpl_suffix`'        
        );
        
        $query = "SELECT " . implode(',',$fields)
        . "\n FROM #__categories AS Category"
        . "\n LEFT JOIN #__jreviews_categories AS JreviewsCategory ON Category.id = JreviewsCategory.id"
        . "\n LEFT JOIN #__sections AS Section ON Category.section = Section.id"
        . "\n LEFT JOIN #__jreviews_sections AS JreviewsSection ON Section.id = JreviewsSection.sectionid"
        . "\n WHERE JreviewsCategory.option = 'com_content' AND Category.id = " . $cat_id
        ;
        
        $this->_db->setQuery($query);
        
        $result = end($this->__reformatArray($this->_db->loadAssocList()));

        $categories[]['Category'] = $result['Section'];
        $categories[]['Category'] = $result['Category'];
        return $categories;
    }
    
    function afterFind($results) 
    {        
        $Menu = ClassRegistry::getClass('MenuModel');
        
        $results = $Menu->addMenuCategory($results);
        
        foreach($results AS $key=>$result)
        {
            isset($result['ListingType']['config']) and $results[$key]['ListingType']['config'] = json_decode($result['ListingType']['config'],true);
            !is_array($results[$key]['ListingType']['config']) and $results[$key]['ListingType']['config'] = array();
        }

        return $results;
    }        
    
    /***********************************************************
    * Joomla 16 specific class methods
    ************************************************************/
    
    /** 
    * Recursive method to generate array for jsTree implementation
    * 
    */
    function makeParentChildRelations(&$inArray, &$outArray, $currentParentId = 1) 
    {
        if(!is_array($inArray)) {       
            return;
        }

        if(!is_array($outArray)) {   
            return;
        }

        foreach($inArray as $key => $item) 
        {                      
            $item = (array) $item;         
            $item['attr'] = array('id'=>$item['value']);
            $item['data'] = $item['text'];
            if($item['parent_id'] == $currentParentId) 
            {
                $item['children'] = array();
                CategoryModel::makeParentChildRelations($inArray, $item['children'], $item['value']);
                if(empty($item['children'])) unset($item['children']);
                $outArray[] = $item;
            }
        }
    }
            
    /**
    * Returns array of cat id/title value pairs given a listing type used for creating a tree list
    * Used in search and listing controllers
    * 
    */
    function getCategoryList($options = array()) 
    {  
        $Access = Configure::read('JreviewsSystem.Access');   
        
        if($this->cmsVersion == CMS_JOOMLA15)
        {
                $query = "
                    SELECT 
                        Category.id as value, CONCAT(Section.title,' - ', Category.title) AS text
                    FROM 
                        #__categories AS Category
                    INNER JOIN 
                        #__jreviews_categories AS JreviewsCategory ON Category.id = JreviewsCategory.id AND JreviewsCategory.option = 'com_content'
                    LEFT JOIN 
                        #__sections AS Section on Category.section = Section.id"
                    .(
                        isset($options['type_id']) ? 
                        " WHERE 
                            Category.published = 1
                            AND Category.access <= " . $Access->getAccessId() . "                        
                            AND JreviewsCategory.criteriaid = " . $options['type_id'] 
                        : 
                        ''
                    ). " 
                    
                    ORDER BY 
                        Section.title, Category.title
                ";

                $this->_db->setQuery($query);
                return $this->_db->loadObjectList('value');            
        }

        $options = array_merge(array(
                'indent'=>true,
                'disabled'=>true
            ),
            $options
        );

        $fields = array(
                'Category.id AS value',
                'Category.level AS level',
                'Category.parent_id AS parent_id',
                'JreviewCategory.criteriaid'
        );
		
		// Add listing type config to query
		$listing_type_join = '';
		
		if(isset($options['listing_type'])) {
			$fields[] = 'ListingType.config';
			$listing_type_join = "
				LEFT JOIN
					#__jreviews_criteria AS ListingType ON JreviewCategory.criteriaid = ListingType.id				
			";
		}
		
		unset($options['listing_type']);
        
        Sanitize::getBool($options,'disabled') and $fields[] = 'IF(JreviewCategory.criteriaid = 0,1,0) AS disabled';
        
        $fields[] = Sanitize::getBool($options,'indent') 
            ? 
            "CONCAT(REPEAT('- ', IF(Category.level>0,Category.level - 1,1)), Category.title) AS text"
            : 
            "Category.title AS text"
        ;          
                                                                                                     
        # Category conditions
        $cat_condition = array();
        isset($options['cat_id']) and !empty($options['cat_id']) and $cat_condition[] = "Category.id IN ({$options['cat_id']})";
        isset($options['parent_id']) and !empty($options['parent_id']) and $cat_condition[] = "Category.parent_id IN ({$options['parent_id']})";
 
        $query = "
            SELECT 
                " . implode(',',$fields) . "
            FROM 
                #__categories AS Category
            LEFT JOIN                                                            
                #__categories AS ParentCategory ON Category.lft <= ParentCategory.lft AND Category.rgt >= ParentCategory.rgt
            INNER JOIN 
                #__jreviews_categories AS JreviewCategory ON JreviewCategory.id = Category.id AND JreviewCategory.`option` = 'com_content'
			" . $listing_type_join . "
            WHERE 
                Category.extension = 'com_content'  
                " . (isset($options['level']) && $options['level'] == 1 ? " AND Category.parent_id = 1" : '' ) /* Speeds up top level category list query with thousands of categories */. "
                AND Category.published = 1
                AND ParentCategory.access IN ( {$Access->getAccessLevels()} ) 
                " . 
                (isset($options['level']) && !empty($options['level']) ? " AND Category.level = {$options['level']} " : '' )
                .
                (!empty($cat_condition) ? " AND (" . implode(" OR ", $cat_condition) . ')' : '')
                .
                (isset($options['type_id']) && !empty($options['type_id']) ? " AND JreviewCategory.criteriaid IN (" . ( is_array($options['type_id']) ? implode(',',$options['type_id']) : $options['type_id'] ) . ")" : '' )
                .
                (isset($options['dir_id']) && !empty($options['dir_id']) ? " AND JreviewCategory.dirid IN (" . cleanIntegerCommaList($options['dir_id']). ")" : '' )
                .
                (isset($options['conditions']) ? " AND (" . implode(" AND " , $options['conditions']) . ")" : '')
                . "
            GROUP BY 
                Category.id
            ORDER 
                BY Category.lft        
        ";

        $this->_db->setQuery($query);  

		$rows = $this->_db->loadObjectList('value');
      
        if(isset($options['jstree']) && $options['jstree'])
        {   
            $nodes = array(); 
            $first = current($rows);
            CategoryModel::makeParentChildRelations($rows, $nodes);  
            return json_encode($nodes);
        }
        return $rows;
    }

    /**
    * Category Manager, Theme Manager
    * 
    * @param mixed $cat_id
    * @param mixed $offset
    * @param mixed $limit
    * @param mixed $total
    */
    function getReviewCategories($alias, $offset=0, $limit, &$total) 
    {
        $where = $alias ? " AND Category.path LIKE '{$alias}%'" : '';
    
        // get the total number of records
        $query = "
            SELECT 
                COUNT(*) 
            FROM 
                `#__jreviews_categories` AS jrcat
            LEFT JOIN 
                #__categories AS Category ON Category.id = jrcat.id
            WHERE 
                Category.extension = 'com_content'
                AND jrcat.option = 'com_content'"
            . $where
        ;
        $this->_db->setQuery( $query );
        $total = $this->_db->loadResult();
     
        $query = "
            SELECT 
                Category.id AS value, Category.title AS text, Category.level AS level,
                Directory.desc AS dir_title, ListingType.title AS listing_type_title,
                JreviewCategory.*
            FROM 
                #__categories AS Category
                    INNER JOIN #__jreviews_categories AS JreviewCategory ON JreviewCategory.id = Category.id AND JreviewCategory.`option` = 'com_content'
                    LEFT JOIN #__jreviews_criteria AS ListingType ON JreviewCategory.criteriaid = ListingType.id
                    LEFT JOIN #__jreviews_directories AS Directory ON JreviewCategory.dirid = Directory.id
                ,#__categories AS parent
            WHERE 
                Category.extension = 'com_content'
                AND Category.lft BETWEEN parent.lft AND parent.rgt
                AND parent.id =  1
                " . $where . "
            ORDER 
                BY Category.lft        
            LIMIT {$offset}, {$limit}
        ";        
        $this->_db->setQuery($query);
        $rows = $this->_db ->loadObjectList();

        if(!$rows) {
            $rows = array();
        }
        return $rows;
    }
    
    /**
    * Used in category manager for new category setup
    * 
    */
    function getReviewCategoryIds() 
    {
        $query = "
            SELECT 
                id AS cat_id
            FROM 
                #__jreviews_categories
            WHERE 
                `option` = 'com_content'
        ";        
        $this->_db->setQuery($query);
        $rows = $this->_db->loadResultArray();
        
        if(!$rows) {
            $rows = array();
        }
        return $rows;
    }

    /**
    * Used in category manager to get a list of categories not setup for JReviews
    * 
    */
    function getNonReviewCategories() 
    {
        $query = "
            SELECT 
                node.id AS value, node.title AS text, node.level AS level
            FROM 
                #__categories AS node,
                #__categories AS parent
            WHERE 
                node.extension = 'com_content'
                AND node.lft BETWEEN parent.lft AND parent.rgt
                AND parent.id = 1 
            ORDER 
                BY node.lft        
        ";
        $this->_db->setQuery($query);        
        $rows = $this->_db->loadObjectList(); 
        return $rows;
    }
    
    /*
    * Used in category manager to show parent cats for filtering
    * 
    * @param mixed $parent_id
    * @param mixed $depth
    */
    function getChildren($parent_id = 1, $depth = null) 
    {
        $query = "
            SELECT 
                node.id, node.alias AS value, node.title AS text
            FROM 
                #__categories AS node,
                #__categories AS parent
            WHERE 
                node.extension = 'com_content'
                AND node.lft BETWEEN parent.lft AND parent.rgt
                AND parent.id = {$parent_id}
                ". ($depth > 0 ? "AND node.level BETWEEN (parent.level + 1) AND (parent.level + " . ($depth) . ")" : '') . "
            ORDER 
                BY node.lft        
        ";
        $this->_db->setQuery($query);
        return $this->_db->loadObjectList('id');
    }  
                
    /**
    * Directories Controller, Categories Controller
    * Generate the category tree array
    */
    function findTree($options = array()) 
    {                                                                
        $fields = array();
        $joins = array();
        $conditions = array();
        $group = array();
        $order = array();
        $having = array();
        
        $Config = Configure::read('JreviewsSystem.Config'); 
        $Access = Configure::read('JreviewsSystem.Access');
        
        // Force straight join for query optimization
        $this->fields['cat_id'] = 'STRAIGHT_JOIN Category.id AS `Category.cat_id`';
        
        $fields[] = 'COUNT(Listing.id) AS `Category.listing_count`';

        $conditions[] = 'Category.published = 1';
//        $conditions[] = "Category.access IN ( {$Access->getAccessLevels()} )";             
        $conditions[] = "ParentCategory.access IN ( {$Access->getAccessLevels()} )";             
        $conditions[] = 'Category.extension = "com_content"';
        isset($options['parent_id']) and !empty($options['parent_id']) and $conditions[] = 'ParentCategory.id = ' . $options['parent_id'];
        isset($options['level']) and !empty($options['level']) and $conditions[] = 'Category.level <= '. $options['level'];
        isset($options['dir_id']) and !empty($options['dir_id']) and $conditions[] = 'Directory.id IN ('.$options['dir_id'].')';
        isset($options['cat_id']) and !empty($options['cat_id']) and $conditions[] = "(Category.id = {$options['cat_id']} OR Category.parent_id = {$options['cat_id']})";
        
        // Join order is important for query optimization
        array_unshift($this->joins,'LEFT JOIN #__content AS Listing ON ParentCategory.id = Listing.catid AND ' . ($Access->isPublisher() ? 'Listing.state >= 0' : 'Listing.state = 1'));
        array_unshift($this->joins,'LEFT JOIN #__categories AS ParentCategory ON Category.lft <= ParentCategory.lft AND Category.rgt >= ParentCategory.rgt');

        $group[] = 'Category.id';
        $order[] = 'Directory.desc';   
        $order[] = 'Category.lft';   

        $Config->dir_category_hide_empty and $having = array('`Category.listing_count` > 0');
        
        $rows = $this->findAll(array(
            'fields'=>$fields,
            'conditions'=>$conditions,
            'joins'=>$joins,
            'group'=>$group,
            'order'=>$order,
            'having'=>$having,
            'limit'=>Sanitize::getInt($options,'limit',null),
            'offset'=>Sanitize::getInt($options,'offset',null)
        ),array());

        if(isset($options['menu_id']) || isset($options['pad']))
        {
            $results = array();
            App::import('Model','menu','jreviews');
            $Menu = ClassRegistry::getClass('MenuModel');
            
            foreach($rows AS $key=>$row) 
            {
                $row['Category']['level'] > 1 and $rows[$key]['Category']['title'] = str_repeat(Sanitize::getVar($options,'pad_char','&nbsp;'),$row['Category']['level']-1) . $row['Category']['title'];            
                if(isset($options['menu_id']))
                {
                    $rows[$key]['Category']['menu_id'] = $Menu->getCategory(array('cat_id'=>$row['Category']['cat_id'],'dir_id'=>$row['Directory']['dir_id']));        
                    $rows[$key]['Directory']['menu_id'] = $Menu->getDir($row['Directory']['dir_id']);        
                    $results[$row['Directory']['dir_id']][$row['Category']['cat_id']] = $rows[$key];
                }
            }          
            unset($Config);
            if(!empty($results)) return $results; 
        }

        unset($Config);
        return $rows;
    }    
    
    function findParents($cat_id)
    {                           
        $query = "
        (SELECT 
                ParentCategory.id AS `Category.cat_id`,
                ParentCategory.lft AS `Category.lft`,
                ParentCategory.title AS `Category.title`,
                ParentCategory.alias AS `Category.slug`,
                ParentCategory.level AS `Category.level`,
                ParentCategory.published AS `Category.published`,
                ParentCategory.access AS `Category.access`,
                ParentCategory.params AS `Category.params`,
                ParentCategory.parent_id AS `Category.parent_id`,
                ParentCategory.metadesc AS `Category.metadesc`, 
                ParentCategory.metakey AS `Category.metakey`, 
                ParentCategory.description AS `Category.description`, 
                JreviewsCategory.criteriaid AS `Category.criteria_id`,
                JreviewsCategory.tmpl AS `Category.tmpl`,
                JreviewsCategory.tmpl_suffix AS `Category.tmpl_suffix`,
                JreviewsCategory.dirid AS `Directory.dir_id`,
                Directory.title AS `Directory.slug`,
                ListingType.config AS `ListingType.config`
            FROM 
                #__categories AS ParentCategory
            INNER JOIN
                #__jreviews_categories AS JreviewsCategory ON JreviewsCategory.id = ParentCategory.id AND JreviewsCategory.`option` = 'com_content'
            LEFT JOIN 
                #__jreviews_directories AS Directory ON JreviewsCategory.dirid = Directory.id
            LEFT JOIN
                #__jreviews_criteria AS ListingType ON ListingType.id = JreviewsCategory.criteriaid
            WHERE 
                ParentCategory.id = " . (int) $cat_id . "
        )
        UNION
        (SELECT 
                ParentCategory.id AS `Category.cat_id`,
                ParentCategory.lft AS `Category.lft`,
                ParentCategory.title AS `Category.title`,
                ParentCategory.alias AS `Category.slug`,
                ParentCategory.level AS `Category.level`,
                ParentCategory.published AS `Category.published`,
                ParentCategory.access AS `Category.access`,
                ParentCategory.params AS `Category.params`,
                ParentCategory.parent_id AS `Category.parent_id`,
                ParentCategory.metadesc AS `Category.metadesc`, 
                ParentCategory.metakey AS `Category.metakey`, 
                ParentCategory.description AS `Category.description`, 
                JreviewsCategory.criteriaid AS `Category.criteria_id`,
                JreviewsCategory.tmpl AS `Category.tmpl`,
                JreviewsCategory.tmpl_suffix AS `Category.tmpl_suffix`,
                JreviewsCategory.dirid AS `Directory.dir_id`,
                Directory.title AS `Directory.slug`,
                ListingType.config AS `ListingType.config`
            FROM 
                #__categories AS Category, 
                #__categories AS ParentCategory
            INNER JOIN
                #__jreviews_categories AS JreviewsCategory ON JreviewsCategory.id = ParentCategory.id AND JreviewsCategory.`option` = 'com_content'
            LEFT JOIN 
                #__jreviews_directories AS Directory ON JreviewsCategory.dirid = Directory.id
            LEFT JOIN
                #__jreviews_criteria AS ListingType ON ListingType.id = JreviewsCategory.criteriaid
            WHERE 
                Category.published
                AND Category.lft BETWEEN ParentCategory.lft AND ParentCategory.rgt
                AND Category.id = " . (int) $cat_id . "
                AND ParentCategory.parent_id > 0
        )
        ORDER BY 
            `Category.lft`
        ";
        $this->_db->setQuery($query);
        $rows = $this->_db->loadObjectList();
        $rows = $this->__reformatArray($rows);
        return $rows;
    }  
  
    function isLeaf($cat_id)
    {
        $query = "
            SELECT 
                count(*)
            FROM 
                #__categories AS Category
            WHERE
                Category.parent_id = " . (int) $cat_id . "
                AND
                Category.extension = 'com_content'
        ";
        $this->_db->setQuery($query);   
        return !$this->_db->loadResult();
    }    
}
