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

class CategoriesController extends MyController {
	
	var $uses = array('user','menu','criteria','listing','section','category');
	var $helpers = array('cache','routes','libraries','html','text','jreviews','time','paginator','rating','thumbnail','community');
	var $components = array('config','access');

	var $autoRender = false; //Output is returned
	var $autoLayout = true;
		
	function beforeFilter() {
				
		# Call beforeFilter of MyController parent class
		parent::beforeFilter();
		
		# Change layout based on task
		if(Sanitize::paranoid($this->action) == 'search') {
			$this->layout = 'search_results';
		} else {
			$this->layout = 'listings';
		}
		
		# Make configuration available in models
		$this->Listing->Config = &$this->Config;	
	}
    
    function afterFilter() 
    {
        parent::afterFilter();
    }    
	
	// Need to return object by reference for PHP4
	function &getObserverModel() {
		return $this->Listing;
	}	
	
	function latest() { $this->listings(); }	

	function mostreviews() { $this->listings(); }	
		
	function toprated() { $this->listings(); }

	function popular() { $this->listings(); }	
       
    function random() { $this->listings(); }    
		
	function listings()
	{		
		if($this->_user->id === 0 && $this->action != 'search') {
			$this->cacheAction = Configure::read('Cache.expires');
		}

		$this->autoRender = false;

		$action = Sanitize::paranoid($this->action);
		$section_id = Sanitize::getString($this->params,'section');
		$cat_id = Sanitize::getString($this->params,'cat');
		$user_id = Sanitize::getInt($this->params,'user',$this->_user->id);
		$index = Sanitize::getString($this->params,'index');
		$sort = $this->params['order'] = Sanitize::getString($this->params,'order',$this->Config->list_order_default);
		$menu_id = Sanitize::getInt($this->params,'menu',Sanitize::getString($this->params,'Itemid'));
		    
		$listings = array();
		$count = 0;
		
		# Remove unnecessary fields from model query
		$this->Listing->modelUnbind('Listing.fulltext AS `Listing.description`');
				
		$conditions = array();
		$joins = array();
		
		# Get listings
	
		# Modify and perform database query based on lisPage type
        if($cat_id != '' && $cat_id > 0) {
            $conditions[] = 'Listing.catid IN ('.$cat_id.')';
        }
        if($section_id != '' && $section_id > 0) {
            $conditions[] = 'Listing.sectionid IN ('.$section_id.')';
        }                                        
        
		if ($this->action == 'mylistings' && $user_id == $this->_user->id)
        {
			$conditions[] = 'Listing.state >= 0';
		} else {
            $conditions[] = 'Listing.state = 1';
        }

		# Shows only links users can access
		$conditions[] = 'Listing.access <= ' . $this->_user->gid;
		$conditions[] = 'Listing.catid > 0';		

		$queryData = array(
//			'fields' they are set in the model
			'joins'=>$joins,
			'conditions'=>$conditions,
			'limit'=>$this->limit,
			'offset'=>$this->offset
		);
	     
		# Modify query for correct ordering. Change FIELDS, ORDER BY and HAVING BY directly in Listing Model variables
		$this->Listing->processSorting($action,$sort);		

		// This is used in Listings model to know whether this is a list page to remove the plugin tags
		$this->Listing->controller = 'categories';
							
		$listings = $this->Listing->findAll($queryData);

		unset($queryData['joins']);
		$this->Listing->joins = array(
			"LEFT JOIN #__jreviews_comments AS Review ON Listing.id = Review.pid AND Review.published = 1 AND Review.mode = 'com_content'",
			"INNER JOIN #__jreviews_categories AS JreviewsCategory ON Listing.catid = JreviewsCategory.id AND JreviewsCategory.`option` = 'com_content'",
			"LEFT JOIN #__jreviews_content AS Field ON Field.contentid = Listing.id"
		);			
        
		if(!isset($this->Listing->count)) { 
			$count = $this->Listing->findCount($queryData,'DISTINCT Listing.id');
        } else {
			$count = $this->Listing->count;
		}

		if(Sanitize::getInt($this->data,'total_special') && Sanitize::getInt($this->data,'total_special') < $count) {
			$count = Sanitize::getInt($this->data,'total_special');
		}
		        
		# Set page array - title, description, image	
		$name_choice = ($this->Config->name_choice == 'alias' ? 'username' : 'name');
		
		$page['show_title'] = 1;
		$page['show_description'] = 1;

		switch($action) {
			case 'latest':
			case 'mostreviews':
			case 'popular':	
			case 'toprated':
				$menuParams = $this->Menu->getMenuParams($menu_id);
				$page['show_title'] = Sanitize::getInt($menuParams,'dirtitle');
				$page['title'] = Sanitize::getString($menuParams,'title');
				if(!$page['title'] && isset($this->Menu->menues[$menu_id])) {
					$page['title'] = $this->Menu->menues[$menu_id]->name;					
				}
				break;	
			default:
				$page['title'] = $this->Menu->getMenuName($menu_id);
				break;		
		}

		# Category ids to be used for ordering list
		$cat_ids = array();

		# Send variables to view template		
		$this->set(
			array(
				'Config'=>$this->Config,
				'Access'=>$this->Access,
				'User'=>$this->_user,
				'subclass'=>'listing',
				'page'=>$page,
				'section'=>isset($section) ? $section : array(), // Section list
				'category'=>isset($category) ? $category : array(), // Category list
				'categories'=>isset($categories) ? $categories : array(),
				'listings'=>$listings,
				'pagination'=>array('total'=>$count))
		);

        echo $this->render('listings','listings_' . $this->tmpl_list);
	}
}