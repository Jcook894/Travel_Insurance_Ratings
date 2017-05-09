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

class MenuModel extends MyModel  {

	var $menues = null;
	var $___menu_data = array();	
		
	function __construct()
	{	
		parent::__construct();
		
		$menuList = array();
		
		switch(getCmsVersion()) 
		{
			case CMS_JOOMLA10: 
			case CMS_MAMBO46:
				$select = 'SELECT id,name,type AS menu_type,link,componentid,params,access,published';
				$type = "\n AND (type IN ( 'content_item_link', 'content_section', 'content_blog_section', 'content_category', 'content_blog_category','components' ))";
			break;
			
			case CMS_JOOMLA15:
				$select = 'SELECT id,name,link AS menu_type,link,componentid,params,access,published';
				$type = "\n AND type = 'component'";
				$link = "\n AND ( 
					link LIKE '%option=com_content&view=section%' OR
					link LIKE '%option=com_content&view=section&layout=blog%' OR
					link LIKE '%option=com_content&view=category\%' OR
					link LIKE '%option=com_content&view=category&layout=blog%' OR
					link LIKE '%option=com_content&view=article%'				
				)"
				;
			break;			
		}
		
		// Get all com_content category/section menus and jReviews menus
		$sql = $select
		. "\n FROM #__menu"
		. "\n WHERE published = 1"
//			. $type
//			. $link
		. (getCmsVersion() == CMS_MAMBO46 ? "\n ORDER BY link ASC" : "\n ORDER BY link DESC") /* Note below*/
		;		
		
		# Check for cached version		
		$cache_prefix = 'menu_model';
		$cache_key = $sql;
		if($cache = S2cacheRead($cache_prefix,$cache_key)){
			$menuList = $cache;
		}		
		
		if(empty($menuList))	
		{
			// Mambo4.6, as opposed to Mambo4.5, does not allow using other components Itemids so jReviews
			// Sections and category lists cannot use the section/category table or blog list menu Itemids
			$this->_db->setQuery($sql);
			$menuList = $this->_db->loadObjectList();

			# Send to cache
			S2cacheWrite($cache_prefix,$cache_key,$menuList);
		}

		// Get itemid for each menu link and store it
		if(is_array($menuList)) 
		{
			foreach ($menuList as $menu) {
				
				$this->menues[$menu->id] = $menu;

				$params = stringToArray($menu->params);
							
				$paramsArray = explode("\n",$menu->params);
							
				if(Sanitize::getVar($params,'sef_name')!=''){
					$m_name = Sanitize::getVar($params,'sef_name');
				} else {
					$m_name = $menu->name;
				}
				
				if (function_exists("sefEncode")) {
					$m_name = sefEncode($m_name);
				}
				
				$m_action = Sanitize::getVar($params,'action');
				$m_dir_id = 1;
				$m_cat_id = str_replace(",","-",Sanitize::getVar($params,'catid'));
				$m_section_id = str_replace(",","-",Sanitize::getVar($params,'sectionid'));

				// Create a variable to get Menu Name from Itemid
				$this->set('jr_itemid_'.$menu->id,$m_name);
				$this->set('jr_menu_'.$m_name,$menu->id);
				
				# Fix for change in menu structure in J1.5
				if(getCmsVersion() == CMS_JOOMLA15) 
				{
					if(strpos($menu->menu_type,'option=com_content&view=section&id=') || strpos($menu->menu_type,'option=com_content&view=section&layout=blog&id=')) {
						$menu->componentid = end(explode('id=',$menu->menu_type));							
						$menu->menu_type = 'content_section';
					} elseif (strpos($menu->menu_type,'option=com_content&view=category&id=') || strpos($menu->menu_type,'option=com_content&view=category&layout=blog&id=')) {
						$menu->componentid = end(explode('id=',$menu->menu_type));							
						$menu->menu_type = 'content_category';
					} elseif(strpos($menu->menu_type,'option=com_content&view=article&id=') || strpos($menu->menu_type,'option=com_content&task=view&id=')){
						$menu->componentid = end(explode('id=',$menu->menu_type));					
						$menu->menu_type = 'content_item_link';						
					}
				}	
							
				switch($menu->menu_type) 
				{
					case 'content_section': case 'content_blog_section':
	
							if ($menu->componentid) { // Only one section id
	
								$this->set('core_section_menu_id_'.$menu->componentid,$menu->id);
//								$this->set('jr_section_menu_id_'.$menu->componentid,$menu->id);							
							} else {
								
								$section_ids = explode(",",Sanitize::getVar($params,'sectionid'));
								$this->set('jr_manyIds_'.$menu->id,1);
								
								foreach($section_ids AS $section_id) {
									$this->set('core_section_menu_id_'.$section_id,$menu->id);
//									$this->set('jr_section_menu_id_'.$section_id,$menu->id);
								}
							}
						break;
					case 'content_category': case 'content_blog_category':
	
						if ($menu->componentid) { // Only one category id
	
							$this->set('core_category_menu_id_'.$menu->componentid,$menu->id);
//							$this->set('jr_category_menu_id_'.$menu->componentid,$menu->id);							
							
						} else {
								$cat_ids = explode(",",Sanitize::getVar($params,'categoryid'));
								$this->set('jr_manyIds_'.$menu->id,1);
								
								foreach($cat_ids AS $cat_id) {
									$this->set('core_category_menu_id_'.$cat_id,$menu->id);
//									$this->set('jr_category_menu_id_'.$cat_id,$menu->id);
								}
							}
	
						break;
					case 'content_item_link':
							$this->set('core_content_menu_id_'.$menu->componentid,$menu->id);
							
						break;						
					default:
						if ($menu->link == 'index.php?option='.S2Paths::get('jrexpress','S2_CMSCOMP'))
						{ // It's a jReviews menu

							// Get a jReviews menu with public access to use in xajax requests
							if($menu->access == 0 && $menu->published == 1) {
								$this->set('jreviews_public',$menu->id);
							}
													
							$menuParams = array();
							
							foreach($paramsArray AS $parameter) {
								
								$menuParams[current(explode('=',$parameter))] = end(explode('=',$parameter));
								
							}

							$this->set('jr_menu_action_'.$m_dir_id,$m_action);
							$this->set('menu_params_'.$menu->id,$menuParams);
							
							switch ($m_action) {
								case '1': // Section menu
									$this->set('jr_section_menu_id_'.$m_section_id,$menu->id);
									break;
								case '2': // Category menu
									$this->set('jr_category_menu_id_'.$m_cat_id,$menu->id);
									break;
								default:
									break;
	
							}
	
						}
					break;
				}
			}
		}
//		prx($this->___menu_data);

	}

	function get($property,$default=null) 
	{	
		if(isset($this->___menu_data[$property])) {
			return $this->___menu_data[$property];
		} else {
			return $default;
		}
	}

	function set($property, $value=null ) 
	{
		if(!isset($this->___menu_data[$property])){ 
			$this->___menu_data[$property] = $value;
		}
	}
	
	function getComponentMenuId($extension) 
	{
		if(!isset($this->___menu_data[$extension])) {
			
			$query = 'SELECT id FROM #__menu WHERE link LIKE "%'.$extension.'%" AND published = 1';
			
			$this->_db->setQuery($query);
			
			$this->___menu_data[$extension] = $this->_db->loadResult();
		}
		
		return $this->___menu_data[$extension];
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

	function getMenuId($menu_name) {
		return $this->get('jr_menu_'.$menu_name, '');
	}

	function getSection($id, $listing = false) {
		if($listing) {
			$core = $this->get('core_section_menu_id_'.$id);
			if($core!='') {
				return $core;
			} elseif (getCmsVersion() == CMS_JOOMLA15 && cmsFramework::getConfig('sef') == 1) {
				// There's a problem with J1.5 core sef urls with Itemids from non-core menus, so we make sure the jReviews menu ids are not used
				return false;
			}
		} 

		return $this->get('jr_section_menu_id_'.$id);			
		
	}

	function getCategory($id,$section_id,$listing = false) {

		if($listing) {
			$core = $this->get('core_content_menu_id_'.$listing,'');
			if($core!='') {
				return $core;
			}
			$core = $this->get('core_category_menu_id_'.$id, $this->getSection($section_id, $listing));
			if($core!='') {
				return $core;
			} elseif (getCmsVersion() == CMS_JOOMLA15 && cmsFramework::getConfig('sef') == 1) {
				// There's a problem with J1.5 core sef urls with Itemids from non-core menus, so we make sure the jReviews menu ids are not used
				return false;
			}
		}		

		return $this->get('jr_category_menu_id_'.$id, $this->getSection($section_id));			
		
	}
	
	function addMenuListing($results) {

		foreach ($results AS $key=>$row) {
			$results[$key]['Listing']['menu_id'] = $this->getCategory($row['Listing']['cat_id'],$row['Listing']['section_id'],$row['Listing']['listing_id']);
			$results[$key]['Section']['menu_id'] = $this->getCategory($row['Listing']['cat_id'],$row['Listing']['section_id']);
			$results[$key]['Category']['menu_id'] =	$this->getCategory($row['Listing']['cat_id'],$row['Listing']['section_id']);
		}
		return $results;
			
	}
	
	function addMenuSection($results) {

		foreach ($results AS $key=>$value) {
			$results[$key]['Section']['menu_id'] = $this->getSection($value['Section']['section_id']);
		}

		return $results;
			
	}		
		
	function addMenuCategory($results) {

		foreach ($results AS $key=>$value) {
			$results[$key]['Category']['menu_id'] = $this->getCategory($value['Category']['cat_id'], $value['Category']['section_id']);
		}

		return $results;
			
	}
}