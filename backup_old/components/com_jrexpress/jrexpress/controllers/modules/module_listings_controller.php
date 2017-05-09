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

class ModuleListingsController extends MyController {
	
	var $uses = array('menu','criteria');
	
	var $helpers = array('routes','paginator','libraries','html','text','jreviews','time','rating','thumbnail','community');
	
	var $components = array('config','access');

	var $autoRender = false;
	
	var $autoLayout = false;
	
	var $layout = 'module';
		
	function beforeFilter() {
					
		# Call beforeFilter of MyController parent class
		parent::beforeFilter();
		
	}
	
	function index()
	{	
		// Required for ajax pagination to remember module settings
		$Session = RegisterClass::getInstance('MvcSession');
		$module_id = Sanitize::getInt($this->params,'module_id',Sanitize::getInt($this->data,'module_id'));

		$cache_file = 'modules_listings_'.$module_id.'_'.md5(serialize($this->params));
					
		if($this->xajaxRequest) {
			$xajax = new xajaxResponse();
			$this->params = $Session->get('module_params'.$module_id,null,S2Paths::get('jrexpress','S2_CMSCOMP'));
		} else {
			srand((float)microtime()*1000000);
			$this->params['rand'] = rand();
			$Session->set('module_rand'.$module_id,$this->params['rand'],S2Paths::get('jrexpress','S2_CMSCOMP'));
			$Session->set('module_params'.$module_id,$this->params,S2Paths::get('jrexpress','S2_CMSCOMP'));
		}
		
		$this->viewSuffix = Sanitize::getString($this->params['module'],'tmpl_suffix');
				
		$conditions = array();
		$joins = array();
		$order = array();
		$having = array();

		// Initialize variables		
		$id = Sanitize::getInt($this->params,'id');		
		$option = Sanitize::getString($this->params,'option');		
		$view = Sanitize::getString($this->params,'view');
		$task = Sanitize::getString($this->params,'task');
		$menu_id = Sanitize::getString($this->params,'Itemid');

		# Read module parameters
		$dir_id = Sanitize::getString($this->params['module'],'dir');
		$section_id = Sanitize::getString($this->params['module'],'section');
		$catids_url = Sanitize::getString($this->params['module'],'category');
		$listing_id = Sanitize::getString($this->params['module'],'listing');
		$criteria_ids = Sanitize::getString($this->params['module'],'criteria');
		$limit = Sanitize::getString($this->params['module'],'module_limit',5);
		$extension = Sanitize::getString($this->params['module'],'extension','com_content');
		$extension = $extension != '' ? $extension : 'com_content';

		# Cached version
/*		if($this->_user->id ===0) {
			
			$page = $this->cached($cache_file);
	
			if($page && $this->xajaxRequest) {
				$xajax->assign('jr_modContainer'.$module_id,'innerHTML',$page);
				return $xajax;
			} elseif ($page) {
				return $page;
			}
		}		*/

		// Automagically load and initialize Everywhere Model
		App::import('Model','everywhere_'.$extension,'jrexpress');
		$class_name = inflector::camelize('everywhere_'.$extension).'Model';
        
        if(class_exists($class_name)) {
        
		    $this->Listing = new $class_name();
		    $this->Listing->_user = $this->_user;
						    
		    // This parameter determines the module mode
		    $sort = Sanitize::getString($this->params['module'],'listing_order');
		    $custom_order = Sanitize::getString($this->params['module'],'custom_order');
		    $custom_where = Sanitize::getString($this->params['module'],'custom_where');
		    
		    if($extension != 'com_content' && in_array($sort,array('topratededitor','featuredrandom','rhits'))) {
			    echo "You have selected the $sort mode which is not supported for components other than com_content. Please read the tooltips in the module parameters for more info on allowed settings.";			
			    return;
		    }
		    
		    # Category auto detect
		    if(Sanitize::getInt($this->params['module'],'cat_auto') && $extension == 'com_content') 
		    { // Only works for core articles		
			    switch($option) {
				    
				    case 'com_jrexpress':
					    
					    # Get url params for current controller/action
					    $url = Sanitize::getString($this->passedArgs,'url');
					    $route['url']['url'] = $url;
					    $route = S2Router::parse($route,true,'jrexpress');
    //					$route = $route['url'];

					    $dir_id = Sanitize::getString($route,'dir');
					    $section_id = Sanitize::getString($route,'section');
					    $cat_id = Sanitize::getString($route,'cat');
					    $criteria_ids = Sanitize::getString($route,'criteria');

					    if ($cat_id != '') {
					    
						    $category_ids = $this->modDash_MakeParamsUsable($cat_id);
						    $category_ids = explode (",",$category_ids);
						    $this->modDash_CleanArray($category_ids);
						    $catids_url = implode (",",$category_ids);					
		    
					    } elseif ($section_id != '') {
						    
						    $catids_url = $this->modDash_Section2Cat($section_id);
						    
					    } elseif($criteria_ids != '')	{ // check criteriaids {
	    
							    $criteriaids_url = $this->modDash_MakeParamsUsable($criteria_ids);
							    $catids_url = $this->modDash_Criteria2Cat($criteria_ids);

					    } else { //Discover the params from the menu_id
												    
						    $params = $this->Menu->getMenuParams($menu_id);

						    $dir_id = Sanitize::getString($params,'dirid');
						    $catids_url = Sanitize::getString($params,'catid');
						    $section_id = Sanitize::getString($params,'sectionid');
																    
					    }

					    break;
		    
				    case 'com_content':
		    
					    if ('article' == $view || 'view' == $task) {
		    
						    $sql = "SELECT catid FROM #__content WHERE id = " . $id;
						    $this->_db->setQuery($sql);
						    $catids_url = $this->_db->loadResult();
						    
						    // Exclude listing from dashboard
    //						$conditions[] = 'Listing.id NOT IN ('.$id.')';
		    
					    } elseif ($view=="section") {
		    
						    $catids_url = $this->modDash_Section2Cat($id);
		    
					    } elseif ($view=="category") {
		    
						    $catids_url = $id;
		    
					    }
		    
					    break;
		    
				    default:
		    
    //					$catids_url = null; // Catid not detected because the page is neither content nor jreviews
		    
					    break;
			    }
		    }
			    
		    # Set conditionals based on configuration parameters
		    if($extension == 'com_content') 
		    { // Only works for core articles
			    
			    // Remove unnecessary fields from model query
			    $this->Listing->modelUnbind('Listing.fulltext AS `Listing.description`');		
					    
			    if($dir_id) {
				    $conditions[] = 'JreviewsCategory.dirid IN (' . $dir_id . ')';
			    }
	    
			    if($section_id) {	
				    $conditions[] = 'Listing.sectionid IN (' . $section_id. ')';
			    }
	    
			    if($catids_url) {	
				    $conditions[] = 'Listing.catid IN (' . $catids_url. ')';
			    }
		    } else {
			    if(Sanitize::getInt($this->params['module'],'cat_auto') && method_exists($this->Listing,'catUrlParam')) {
				    if($catids_url = Sanitize::getInt($this->passedArgs,$this->Listing->catUrlParam())){
					    $conditions[] = 'JreviewsCategory.id IN (' . $catids_url. ')';
				    }
			    } elseif($catids_url) {	
				    $conditions[] = 'JreviewsCategory.id IN (' . $catids_url. ')';
			    }			
		    }
		    
		    if($listing_id) {	
			    $conditions[] = "Listing.{$this->Listing->realKey} IN ($listing_id)";
		    }		
		    switch($sort) {
			    case 'random':
				    $order[] = 'RAND('.$this->params['rand'].')';				
				    break;
			    case 'featuredrandom':
				    $conditions[] = 'featured > 0';				
				    $order[] = 'RAND('.$this->params['rand'].')';
				    break;
			    // Other sorting options dealt with in the Listing->processSorting method					
		    }

		    # Custom WHERE
		    if($custom_where) {
			    $conditions[] = $custom_where;
		    }
            
            # Filtering options
            $having = array();
            // Listings submitted in the past x days
            $entry_period = Sanitize::getInt($this->params['module'],'filter_listing_period');
            if($entry_period > 0 && $this->Listing->dateKey){
                $conditions[] = "Listing.{$this->Listing->dateKey} >= DATE_SUB(CURDATE(), INTERVAL $entry_period DAY)";
            }
            // Listings with reviews submitted in past x days
            $review_period = Sanitize::getInt($this->params['module'],'filter_review_period');
            if($review_period > 0){
                $conditions[] = "Review.created >= DATE_SUB(CURDATE(), INTERVAL $review_period DAY)";
            }
            // Listings with review count higher than
            $filter_review_count = Sanitize::getInt($this->params['module'],'filter_review_count');
            if($filter_review_count > 0){
               $having[] = "(COUNT(Review.id)-SUM(Review.author)) >= $filter_review_count";
            }
            // Listings with avg rating higher than
            $filter_avg_rating = Sanitize::getFloat($this->params['module'],'filter_avg_rating');
            if($filter_avg_rating > 0){
//               $having[] = "`Review.user_rating` >= " . (float)$filter_avg_rating;
                $having[] = '(sum(Rating.ratings_sum-Review.author*Rating.ratings_sum)/sum(Rating.ratings_qty-Review.author*Rating.ratings_qty))  >= ' . (float)$filter_avg_rating;
            }

		    $this->Listing->group = array();

		    unset($this->Listing->fields['editor_rating'],$this->Listing->fields['editor_rating_exists'],$this->Listing->fields['user_rating'],$this->Listing->fields['editor_rating_exists'],$this->Listing->fields['review_count'],$this->Listing->fields['review_count_exists']);
            
		    if(in_array($sort,array('rating','rrating','topratededitor','reviews'))) {
			    // Exlude listings without ratings from the results
			    $join_direction = 'INNER';
		    } else {
			    $join_direction = 'LEFT';
		    }
		    
		    if($extension == 'com_content') 
		    { // Only for core article because it's added below as well
			    unset($this->Listing->joins['Review'],$this->Listing->joins['Rating']);
		    }
		    
		    $joins = array(
			    'Review'=>"$join_direction JOIN #__jreviews_comments AS Review ON Listing.{$this->Listing->realKey} = Review.pid AND Review.published = 1 AND Review.mode = '{$extension}'",
			    'Rating'=>"$join_direction JOIN #__jreviews_ratings AS Rating ON Review.id = Rating.reviewid",
		    );
		    # Modify query for correct ordering. Change FIELDS, ORDER BY and HAVING BY directly in Listing Model variables
		    if($custom_order) {
			    $this->Listing->order[] = $custom_order;			
		    } elseif(empty($order) && $extension == 'com_content') {
			    $this->Listing->processSorting($sort,'');			
		    } elseif(empty($order)) {
                if($order = $this->_processSorting($sort)){
			        $order = array($order);	
                }
		    }		
		    
		    $queryData = array(
			    'fields'=>array(
				    'sum(Review.author*(Rating.ratings_sum/Rating.ratings_qty)) AS `Review.editor_rating`',
				    'IF(sum(Review.author*(Rating.ratings_sum/Rating.ratings_qty))>0,1,0) AS `Review.editor_rating_exists`',
				    '(sum(Rating.ratings_sum-Review.author*Rating.ratings_sum)/sum(Rating.ratings_qty-Review.author*Rating.ratings_qty)) AS `Review.user_rating`',
				    'IF((sum(Rating.ratings_sum-Review.author*Rating.ratings_sum)/sum(Rating.ratings_qty-Review.author*Rating.ratings_qty))>0,1,0) AS `Review.user_rating_exists`',
				    '(count(Review.id)-sum(Review.author)) AS `Review.review_count`',
				    'IF ((count(Review.id)-sum(Review.author))>0,1,0) AS `Review.review_count_exists`'
			    ),
			    'joins'=>$joins,
			    'conditions'=>$conditions,
                'limit'=>$this->module_limit,
			    'offset'=>$this->module_offset,
			    'group'=>array(
				    'Listing.'.$this->Listing->realKey
			    ),
                'having'=>$having
		    );	

            if(isset($order) && !empty($order)){
                $queryData['order'] = $order;
            }

            $listings = $this->Listing->findAll($queryData);

		    if(Sanitize::getInt($this->params['module'],'ajax_nav',1)) 
		    {
			    unset(
				    $queryData['joins']['Section'],
				    $queryData['joins']['Category'],
				    $queryData['joins']['Criteria'],
				    $queryData['joins']['User'],
				    $queryData['order']
			    );

//                unset($queryData['having']);
			    $count = $this->Listing->findCount($queryData,'DISTINCT Listing.'.$this->Listing->realKey);		
		    } else {
			    
			    $count = $this->module_limit;
		    }
        } // end Listing class check
        else {
            $listings = array();
            $count = 0;
        }		    

		# Send variables to view template		
		$this->set(array(
				'Access'=>$this->Access,
				'User'=>$this->_user,
				'subclass'=>'listing',
				'section'=>isset($section) ? $section : array(),
				'categories'=>isset($categories) ? $categories : array(),
				'listings'=>$listings,
				'total'=>$count
		));
		
		$page = $this->render('modules','listings');

		# Save cached version
/*		if($this->_user->id ===0) {	
			$this->cacheView('modules','listings',$cache_file, $page);
		}*/
				
		if($this->xajaxRequest) {
			$xajax->assign('jr_modContainer'.$module_id,'innerHTML',$page);
			return $xajax;
		} else {
			return $page;
		}		

	}
	
   /**
    * Modifies the query ORDER BY statement based on ordering parameters
    *
    */
 	function _processSorting($selected) {

		$order = '';

		switch ( $selected ) {
		  	case 'rating':
		  		$order = "`Review.user_rating_exists` DESC, `Review.user_rating` DESC, `Review.review_count` DESC" . ($this->Listing->dateKey ? ", Listing.{$this->Listing->dateKey} DESC" : '');
		  		break;
		  	case 'rrating':
		  		$order = '`Review.user_rating_exists` DESC, `Review.user_rating` ASC, `Review.review_count` DESC, Listing.hits DESC';
		  		break;
		  	case 'reviews':
		  		$order = "`Review.review_count_exists` DESC, `Review.review_count` DESC, `Review.user_rating` DESC" . ($this->Listing->dateKey ? ", Listing.{$this->Listing->dateKey} DESC" : '');
		  		break;
			case 'rdate':
				$order =  $this->Listing->dateKey ? "Listing.{$this->Listing->dateKey} DESC" : false;
				break;
		}
	
		return $order;
	}
	
	function modDash_Criteria2Cat($criteriaid) {
		$query = "SELECT DISTINCT id FROM #__jreviews_categories"
		." \n WHERE criteriaid IN ($criteriaid) "
		." \n AND `option` = 'com_content'";
		$this->_db->setQuery($query);
		$catids = implode(",",$this->_db->loadResultArray());
		return $catids;
	}

	function modDash_Section2Cat($sectionid) {
		$sectionid = $this->modDash_MakeParamsUsable($sectionid);
		$sql = "SELECT DISTINCT category.id FROM #__categories AS category"
		. "\n INNER JOIN #__jreviews_categories AS jr_category ON category.id = jr_category.id AND jr_category.option = 'com_content'"
		. "\n WHERE category.section IN ($sectionid)"
		;

		$this->_db->setQuery($sql);
		$catids = implode(",",$this->_db->loadResultArray());
		return $catids;
	}

	function modDash_CleanArray(&$array) {
		// Remove empty or nonpositive values from array
		foreach ($array as $index => $value) {
		   if (empty($value) || $value < 1 || !is_numeric($value)) unset($array[$index]);
		}
	}

	function modDash_MakeParamsUsable($param) {
		$urlSeparator = "_";
		return str_replace($urlSeparator,",",urldecode($param));
	}
	
}
