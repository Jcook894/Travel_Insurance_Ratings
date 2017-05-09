<?php
/**
 * JReviews - Reviews Extension
 * Copyright (C) 2006-2008 ClickFWD LLC
 * This is not free software, do not distribute it.
 * For licencing information visit http://www.reviewsforjoomla.com
 * or contact sales@reviewsforjoomla.com
**/

defined( 'MVC_FRAMEWORK') or die( 'Direct Access to this location is not allowed.' );

class DirectoryModel extends MyModel  {
        
    var $name = 'Directory';
    
    var $useTable = '#__jreviews_directories AS Directory';
    
    var $primaryKey = 'Directory.dir_id';
    
    var $realKey = 'id';
    
    var $fields = array(
        'Directory.id AS `Directory.id`',
        'Directory.title AS `Directory.slug`',
        'Directory.desc AS `Directory.title`',
        'Directory.tmpl_suffix AS `Directory.tmpl_suffix`'
    );
            
    function getList() {
                
        $query = "SELECT * from #__jreviews_directories order by id ASC";
        
        $this->_db->setQuery($query);
        
        $rows =  $this->_db->loadObjectList();
        
        return $rows;
    }
    
    function getSelectList($dir_id = null) {
        
        $query = "SELECT Directory.id AS value, Directory.desc AS text"
        . "\n FROM #__jreviews_directories AS Directory"
        . ($dir_id ? "\n WHERE id = " . $dir_id : '')
        . "\n ORDER BY Directory.title ASC"
        ;
        
        $this->_db->setQuery($query);
        
        $results = $this->_db->loadObjectList();

        return $results;        
    }
    
    /**
    * Joomla 1.5 only method
    * 
    * @param string $dir_id
    * @param mixed $module
    * @return string
    */
    function getTree($dir_id, $module = false) 
    {      
        // Clean $dir_id string
        $cleaned = array();
        $section_count = null;
                    
        $bits = explode(',',$dir_id);
        foreach ($bits AS $bit) {
            if(is_numeric($bit)) {
                $cleaned[] = $bit;
            }
        }
 
        $dir_id = implode(',',$cleaned);
        $Config = Configure::read('JreviewsSystem.Config');
        $Access = Configure::read('JreviewsSystem.Access');
        $aid = $Access->getAccessId();
        
        $module and $Config->dir_category_limit = ''; // Ignore category limit setting in dir module
        
        # Check for cached version        
        $cache_prefix = 'directory_mode_gettree';
        $cache_key = array(func_get_args(),$aid,cmsFramework::language());
        if($cache = S2cacheRead($cache_prefix,$cache_key)){
            return $cache;
        }
                    
        # First get list of sections for current dir
        $query = "
            SELECT 
                Category.section AS `Section.section_id`,
                JreviewsCategory.dirid AS `Directory.dir_id`
            FROM 
                #__jreviews_categories AS JreviewsCategory                             
            LEFT JOIN 
                #__jreviews_directories AS Directory ON Directory.id = JreviewsCategory.dirid
            RIGHT JOIN 
                #__categories AS Category ON Category.id = JreviewsCategory.id
            LEFT JOIN 
                #__sections AS Section ON Category.section = Section.id
            WHERE 
                Section.published = 1
                AND Section.access <= {$aid}"
            . ($dir_id ? " AND JreviewsCategory.dirid IN ({$dir_id}) " : '') 
            . "
            AND JreviewsCategory.`option` = 'com_content'
            ORDER BY 
                Directory.`desc` ASC 
            "
        ;  

        $query = $this->_db->setQuery($query);

        $tmp_key = $this->primaryKey;
        $this->primaryKey = 'Section.section_id';
        $sections = $this->_db->loadObjectList();
        $sections = $this->__reformatArray($sections);
        $this->primaryKey = $tmp_key;         
        $section_ids = array_keys($sections);
        
        // Get listing count for sections
        $Config->dir_section_num_entries = $Config->dir_cat_num_entries;   
            
        if($Config->dir_section_num_entries || $Config->dir_category_hide_empty)
        {
            $query = '
                SELECT  
                    Listing.sectionid AS `Section.section_id`, 
                    COUNT(Listing.id) AS `Section.listing_count` 
                FROM 
                    #__content AS Listing
                INNER JOIN
                    #__jreviews_categories AS JreviewsCategory ON JreviewsCategory.id = Listing.catid AND JreviewsCategory.`option` = "com_content"
                WHERE 
                    Listing.sectionid IN ('.implode(',',$section_ids).')  
                    AND Listing.state = 1
                    AND Listing.access <= '.$aid.'
                    AND ( Listing.publish_up = "'.NULL_DATE.'" OR Listing.publish_up <= "'._CURRENT_SERVER_TIME.'" ) 
                    AND ( Listing.publish_down = "'.NULL_DATE.'" OR Listing.publish_down >= "'._CURRENT_SERVER_TIME.'" )
                GROUP BY Listing.sectionid
             ';
            $this->_db->setQuery($query);
            
    //prx($this->_db->getQuery());

            $tmp_key = $this->primaryKey;
            $this->primaryKey = 'Section.section_id';
            $section_count = $this->_db->loadObjectList(); 
            $section_count = $this->__reformatArray($section_count);
            $this->primaryKey = $tmp_key;                             
        }

        # If category limit is zero then don't query the categories
        if($Config->dir_category_limit==='0')
        {
            $tmp_key = $this->primaryKey;
            $this->primaryKey = 'Category.cat_id';    
                        
            $query = '
                SELECT 
                    Directory.id AS `Directory.dir_id`,
                    Directory.`desc` AS `Directory.title`,
                    Directory.title AS `Directory.slug`,                    
                    Section.id AS `Section.section_id`,
                    Section.name  AS `Section.name`,
                    Section.title AS `Section.title`,
                    Section.image AS `Section.image`,
                    Section.published AS `Section.published`,
                    Section.access AS `Section.access`,
                    Section.alias AS `Section.slug`,   
                    Section.ordering AS `Section.ordering`   
                FROM
                    #__jreviews_categories AS JreviewsCategory 
                RIGHT JOIN    
                     #__categories AS Category USING(id)
                LEFT JOIN
                    #__sections AS Section ON Section.id = Category.section
                LEFT JOIN
                    #__jreviews_directories AS Directory ON JreviewsCategory.dirid = Directory.id
                WHERE 
                    Category.published = 1
                    AND Section.access <= '.$aid.'
                    AND Category.access <= '.$aid.'
                    AND Category.section IN ('.implode(',',$section_ids).')         
                    AND JreviewsCategory.`option` = "com_content"                    
                GROUP BY
                    Category.section
                ORDER BY 
                    Directory.`desc` ASC 
            ';    
            $this->_db->setQuery($query);
            $categories = $this->_db->loadObjectList();    
        } 
        else
        {       
            $categories = array();
            $tmp_key = $this->primaryKey;
            $this->primaryKey = 'Category.cat_id';    
            foreach($sections AS $section)
            {                          
                $section_id = $section['Section']['section_id'];
                $query = '
                    SELECT  
                        Directory.id AS `Directory.dir_id`,
                        Directory.`desc` AS `Directory.title`,
                        Directory.title AS `Directory.slug`,                    
                        Section.id AS `Section.section_id`,
                        Section.name  AS `Section.name`,
                        Section.title AS `Section.title`,
                        Section.image AS `Section.image`,
                        Section.published AS `Section.published`,
                        Section.access AS `Section.access`,
                        Section.alias AS `Section.slug`,   
                        Section.ordering AS `Section.ordering`,   
                        Category.id AS `Category.cat_id`,            
                        Category.title AS `Category.title`,
                        Category.image AS `Category.image`,
                        Category.published AS `Category.published`,
                        Category.access AS `Category.access`,
                        Category.alias AS `Category.slug`
                        ' . ($Config->dir_cat_num_entries || $Config->dir_category_hide_empty ? '
                            ,(SELECT 
                              count(*) 
                              FROM #__content AS Listing
                              RIGHT JOIN #__jreviews_categories AS JreviewsCategory ON JreviewsCategory.id = Listing.catid AND JreviewsCategory.`option` = "com_content"
                              WHERE 
                                    Listing.sectionid = ' . $section_id .'
                                    AND Listing.catid = Category.id         
                                    AND Listing.state = 1 
                                    AND Listing.access <= ' . $aid . '
                                    AND ( Listing.publish_up = "'.NULL_DATE.'" OR Listing.publish_up <= "'._CURRENT_SERVER_TIME.'" ) 
                                    AND ( Listing.publish_down = "'.NULL_DATE.'" OR Listing.publish_down >= "'._CURRENT_SERVER_TIME.'" )
                            ) AS `Category.listing_count` ' 
                            : ''
                            ) . '
                    FROM
                        #__jreviews_categories AS JreviewsCategory 
                    RIGHT JOIN    
                         #__categories AS Category USING(id)
                    LEFT JOIN
                        #__sections AS Section ON Section.id = Category.section
                    LEFT JOIN
                        #__jreviews_directories AS Directory ON JreviewsCategory.dirid = Directory.id
                    WHERE 
                        Category.published = 1
                        AND Section.access <= '.$aid.'
                        AND Category.access <= '.$aid.'
                        AND Category.section = '.$section_id.'         
                        AND JreviewsCategory.`option` = "com_content"                    
                    ORDER BY 
                    ' . (!$Config->dir_category_order ? 'Category.ordering' : 'Category.title ASC') . '
                    ' . (!empty($Config->dir_category_limit) ?  ' LIMIT ' . $Config->dir_category_limit : '') . '
                ';
  
                $this->_db->setQuery($query);
                $categories = array_merge($categories,$this->_db->loadObjectList());
            }
        }

        $categories = $this->__reformatArray($categories);
        $this->primaryKey = $tmp_key;
       
        App::import('Model','menu','jreviews');
        $Menu = ClassRegistry::getClass('MenuModel');

        $results = array();

        foreach($categories AS $key=>$row) 
        {
            $section_id = $row['Section']['section_id'];
            $section_title = $row['Section']['title'];
            isset($row['Category']) and $cat_id = $row['Category']['cat_id'];
            isset($row['Category']) and $cat_title = $row['Category']['title'];
            $dirKey = $row['Directory']['dir_id'].':'.$row['Directory']['slug'];
            $dir_id = $row['Directory']['dir_id'];

            if(!isset($rows[$dirKey]['Directory'])) {
                $results[$dirKey]['Directory']['title'] = $row['Directory']['title'];
                $results[$dirKey]['Directory']['slug'] = $row['Directory']['slug'];
                $results[$dirKey]['Directory']['dir_id'] = $row['Directory']['dir_id'];
                $results[$dirKey]['Directory']['menu_id'] = $Menu->getDir($dir_id);                
            }
            
            // Section data
            $sectionKey = $section_id.':'.$section_title;
            $results[$dirKey]['Sections'][$sectionKey]['section_id'] = $row['Section']['section_id'];
            $results[$dirKey]['Sections'][$sectionKey]['title'] = $row['Section']['title'];
            $results[$dirKey]['Sections'][$sectionKey]['slug'] = $row['Section']['slug'];
            $results[$dirKey]['Sections'][$sectionKey]['image'] = $row['Section']['image'];
            $results[$dirKey]['Sections'][$sectionKey]['image'] = $row['Section']['image'];
            $results[$dirKey]['Sections'][$sectionKey]['published'] = $row['Section']['published'];
            $results[$dirKey]['Sections'][$sectionKey]['access'] = $row['Section']['access'];
            $results[$dirKey]['Sections'][$sectionKey]['ordering'] = $row['Section']['ordering'];
            $results[$dirKey]['Sections'][$sectionKey]['menu_id'] = $Menu->getSection($section_id, $dir_id);
            $results[$dirKey]['Sections'][$sectionKey]['dir_id'] = $dir_id;
            if(!empty($section_count) && isset($section_count[$row['Section']['section_id']]))
            {
                $results[$dirKey]['Sections'][$sectionKey]['listing_count'] = $section_count[$row['Section']['section_id']]['Section']['listing_count'];
            }
            else 
            {
                $results[$dirKey]['Sections'][$sectionKey]['listing_count'] = 0;                
            }
            // Category data
            if(!empty($row['Category']))
            {
                $catKey = $cat_id.':'.$cat_title;
                $results[$dirKey]['Sections'][$sectionKey]['Categories'][$catKey]['cat_id'] = $cat_id;
                $results[$dirKey]['Sections'][$sectionKey]['Categories'][$catKey]['title'] = $row['Category']['title'];
                $results[$dirKey]['Sections'][$sectionKey]['Categories'][$catKey]['slug'] = $row['Category']['slug'];
                $results[$dirKey]['Sections'][$sectionKey]['Categories'][$catKey]['image'] = $row['Category']['image'];                                                    
                $results[$dirKey]['Sections'][$sectionKey]['Categories'][$catKey]['published'] = $row['Category']['published'];                                                    
                $results[$dirKey]['Sections'][$sectionKey]['Categories'][$catKey]['access'] = $row['Category']['access'];                                                                                                    
                $results[$dirKey]['Sections'][$sectionKey]['Categories'][$catKey]['menu_id'] = $Menu->getCategory($cat_id, $section_id, $dir_id);            
                $results[$dirKey]['Sections'][$sectionKey]['Categories'][$catKey]['dir_id'] = $dir_id;
                isset($row['Category']['listing_count']) and $results[$dirKey]['Sections'][$sectionKey]['Categories'][$catKey]['listing_count'] = $row['Category']['listing_count'];
            }
        }
        
        foreach($results AS $dir_id=>$sections)
        {
            $sections['Sections'] = &$results[$dir_id]['Sections'];
            $section_sort = $Config->dir_section_order ? 'sort_sections_alpha' : 'sort_sections_ordering';
            uasort($sections['Sections'], array($this,$section_sort));
        }

        # Send to cache
        S2cacheWrite($cache_prefix,$cache_key,$results);    
        
        return $results;
    }
    
    function sort_sections_ordering($s1,$s2)
    {                                     
        return (strcmp (strtolower($s1['ordering']), strtolower($s2['ordering']))); 
    }

    function sort_sections_alpha($s1,$s2)
    {                                     
        return (strcmp (strtolower($s1['title']), strtolower($s2['title']))); 
    }
    
    function afterFind($results) 
    {
        if (empty($results)) {
            return $results;
        }

        # Add Menu ID info for each row (Itemid)
        $Menu = ClassRegistry::getClass('MenuModel');
        $results = $Menu->addMenuDirectory($results);
        
        return $results;
        
    }

}