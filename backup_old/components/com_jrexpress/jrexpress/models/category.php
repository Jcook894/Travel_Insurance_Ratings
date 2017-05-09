<?php
/*
    JReviews Express - user reviews for Joomla
    Copyright (C) 2009  Alejandro Schmeichler

    JReviews Express is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    JReviews Express is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/

defined( 'MVC_FRAMEWORK') or die( 'Direct Access to this location is not allowed.' );

class CategoryModel extends MyModel  {
	
	var $useTable = '#__categories AS Category';
	var $primaryKey = 'Category.cat_id';
	var $fields = array(
		'Category.id AS `Category.cat_id`',
		'Category.section AS `Category.section_id`',
		'Category.title AS `Category.title`',
		'Category.image AS `Category.image`',
		'Category.image_position AS `Category.image_position`',
		'Category.description AS `Category.description`',		
		'Category.access AS `Category.access`',
		'Category.published AS `Category.published`',
		'JreviewsCategory.criteriaid AS `Category.criteria_id`',
		'JreviewsCategory.tmpl AS `Category.tmpl`',
		'JreviewsCategory.tmpl_suffix AS `Category.tmpl_suffix`',
		'count(Listing.id) AS `Category.listing_count`' // Listing count
	);
			
	var $joins = array(
		'INNER JOIN #__jreviews_categories AS JreviewsCategory ON Category.id = JreviewsCategory.id',
		'LEFT JOIN #__content AS Listing ON Category.id = Listing.catid AND Listing.state = 1' // Listing count
	);
	
	var $conditions = array('JreviewsCategory.option = "com_content"');
			
	var	$group = array('JreviewsCategory.id'); // Listing count
	
	function __construct() 
    {        
		parent::__construct();
		
		if(getCmsVersion() == CMS_JOOMLA15) {
			// Add listing, category aliases to fields
			$this->fields[] = 'CASE WHEN CHAR_LENGTH(Category.alias) THEN Category.alias ELSE Category.title END AS `Category.slug`';
		} else {
			$this->fields[] = 'Category.name AS `Category.slug`';
		}		
        
        $this->conditions += array(
            'Listing.state = 1',
            '( Listing.publish_up = "'.NULL_DATE.'" OR Listing.publish_up <= "'._CURRENT_SERVER_TIME.'" )',
            '( Listing.publish_down = "'.NULL_DATE.'" OR Listing.publish_down >= "'._CURRENT_SERVER_TIME.'" )'                
        );        
	}
	
	/**
	 * Checks if core category is setup for jReviews
	 */
	function isJreviewsCategory($cat_id) {
		
		# Check for cached version		
		$cache_prefix = 'category_model_isjreviewscategory';
		$cache_key = func_get_args();
		if($cache = S2cacheRead($cache_prefix,$cache_key)){
			return $cache;
		}					

		$query = "SELECT JreviewCategory.id"
		. "\n FROM #__jreviews_categories AS JreviewCategory"
		. "\n WHERE JreviewCategory.`option` = 'com_content' AND JreviewCategory.id = " . (int) $cat_id;
		
		$this->_db->setQuery($query);
		
		$result = $this->_db->loadResult();
	
		# Send to cache
		S2cacheWrite($cache_prefix,$cache_key,$result);
				
		return $result;		
	}
	
	/**
	 * Used in Administration in controllers:
	 * 		admin_listings_controller.php
	 * Also used in Frontend listings_controller.php in create function.
	 */
	function getList($section_id, $cat_ids = '') 
	{					
		$query = "SELECT Category.id AS value, Category.title AS text "
		. "\n FROM #__categories AS Category"
		. "\n RIGHT JOIN #__jreviews_categories AS JreviewsCategory ON JreviewsCategory.id = Category.id AND JreviewsCategory.`option` = 'com_content'"
		. "\n WHERE Category.section IN (" . $section_id . ")"
		. ($cat_ids != '' ? "\n AND Category.id IN ($cat_ids)" : '')
		. "\n ORDER BY Category.title";

		$this->_db->setQuery($query);
		
		return $this->_db->loadObjectlist();	
	}
	
	/**
	 * Used in Administration in controllers:
	 * 		categories_controller.php
	 * 		themes_controller.php
	 */
	function getRows($sectionid, $limitstart=0, $limit, &$total) {
	
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
	
		$query = "SELECT jrcat.*, c.title as cat, sec.title as section, cr.title as criteria"
		 ."\n FROM #__jreviews_categories jrcat"
		 ."\n LEFT JOIN #__categories c on jrcat.id = c.id"
		 ."\n LEFT JOIN #__sections sec on c.section = sec.id"
		 ."\n LEFT JOIN #__jreviews_criteria cr on jrcat.criteriaid = cr.id"
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
	 * 		categories_controller.php
	 */	
	function getSelectList() {
		
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
	
	function getTemplateSettings($cat_id) {
		
		# Check for cached version		
		$cache_prefix = 'category_model_themesettings';
		$cache_key = func_get_args();
		if($cache = S2cacheRead($cache_prefix,$cache_key)){
			return $cache;
		}			
				
		$fields = array(
			'JreviewsSection.tmpl AS `Section.tmpl_list`',
			'JreviewsSection.tmpl_suffix AS	`Section.tmpl_suffix`',
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
		
		# Send to cache
		S2cacheWrite($cache_prefix,$cache_key,$result);
		
		return $result;
	}
	
	function afterFind($results) {
		
		if(!$this->runAfterFind) {
			return $results;
		}
		
		$Menu = registerClass::getInstance('MenuModel');
		
		$results = $Menu->addMenuCategory($results);

		return $results;
		
	}

}