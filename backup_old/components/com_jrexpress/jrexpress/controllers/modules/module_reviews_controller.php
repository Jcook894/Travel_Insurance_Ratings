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

class ModuleReviewsController extends MyController {
		
	var $uses = array('user','menu','category','review','criteria','vote');
	
	var $helpers = array('routes','paginator','libraries','html','text','time','jreviews','community','rating','thumbnail');
	
	var $components = array('config','access','everywhere');
	
	var $autoRender = false;
	
	var $autoLayout = false;
	
	var $layout = 'module';
			
	function beforeFilter() {
					
		# Call beforeFilter of MyController parent class
		parent::beforeFilter();
		
		# Stop AfterFind actions in Review model
		$this->Review->rankList = false;		
		
	}
	
	// Need to return object by reference for PHP4
	function &getNotifyModel() {
		return $this->Review;
	}	
	
	function index()
	{					
		$this->EverywhereAfterFind = true; // Triggers the afterFind in the Observer Model
		
		// Required for ajax pagination to remember module settings
		$Session = RegisterClass::getInstance('MvcSession');
		$module_id = Sanitize::getInt($this->params,'module_id',Sanitize::getInt($this->data,'module_id'));
		
		$cache_file = 'modules_reviews_'.$module_id.'_'.md5(serialize($this->params));
		
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

		// Initialize variables		
		$id = Sanitize::getInt($this->params,'id');		
		$option = Sanitize::getString($this->params,'option');
		$view = Sanitize::getString($this->params,'view');
		$task = Sanitize::getString($this->params,'task');
		$menu_id = Sanitize::getString($this->params,'Itemid');

		# Read module parameters
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

		$catids_url = Sanitize::getString($this->params['module'],'category');
		$listing_id = Sanitize::getString($this->params['module'],'listing');
			
		if($extension == 'com_content') {
			$dir_id = Sanitize::getString($this->params['module'],'dir');
			$section_id = Sanitize::getString($this->params['module'],'section');
			$criteria_ids = Sanitize::getString($this->params['module'],'criteria');
		} else {		
			$dir_id = null;
			$section_id = null;
			$criteria_ids = null;
		}

		$cat_autodetect = Sanitize::getInt($this->params['module'],'cat_auto');
		
		// This parameter determines the module mode
		$sort = Sanitize::getString($this->params['module'],'reviews_order');
		
		# Category auto detect
		if($cat_autodetect && $extension == 'com_content') 
		{			
			switch($option) {
				
				case 'com_jrexpress':
					
					# Get url params for current controller/action
					$url = Sanitize::getString($this->passedArgs, 'url');
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
	
		# Remove unnecessary fields from model query
//		$this->Review->modelUnbind();
		if($extension != '') {
			$conditions[] =  "Review.mode = '$extension'"; 
		}
				
		# Set conditionals based on configuration parameters
		if($extension == 'com_content') 
		{ // Only works for core articles	
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
			if(Sanitize::getInt($this->params['module'],'cat_auto') && isset($this->Listing) && method_exists($this->Listing,'catUrlParam')) {
				if($catids_url = Sanitize::getInt($this->passedArgs,$this->Listing->catUrlParam())){
					$conditions[] = 'JreviewsCategory.id IN (' . $catids_url. ')';
				}
			} elseif($catids_url) {	
				$conditions[] = 'JreviewsCategory.id IN (' . $catids_url. ')';
			}		
		}
		
		if($listing_id) {	
			$conditions[] = "Review.pid IN ($listing_id)";
		}		
		                                        
		if($extension == 'com_content') 
		{ // Only works for core articles		
			if ( $this->Access->canEdit ) {
				$conditions[] = 'Listing.state >= 0';
			} else {
				$conditions[] = 'Listing.state = 1';
				$conditions[] = '( Listing.publish_up = "'.NULL_DATE.'" OR Listing.publish_up <= "'._CURRENT_SERVER_TIME.'" )';
				$conditions[] = '( Listing.publish_down = "'.NULL_DATE.'" OR Listing.publish_down >= "'._CURRENT_SERVER_TIME.'" )';
			}
			
			//Shows only links users can access
			$conditions[] = 'Listing.access <= ' . $this->_user->gid;
			$conditions[] = 'Listing.catid > 0';				
		}

		$conditions[] = 'Review.published > 0';	
	
		switch($sort) {
			case 'latest':
				$order[] = $this->Review->processSorting('rdate');
				break;
			case 'helpful':
				$order[] = $this->Review->processSorting('helpful');
				break;				
			case 'random':
				$order[] = 'RAND('.$this->params['rand'].')';
				break;
			default:
				$order[] = $this->Review->processSorting('rdate');
				break;	
		}

		$queryData = array(
			'fields'=>array(
//				'Review.mode AS `Review.extension`'
			),
			'joins'=>$joins,
			'conditions'=>$conditions,
			'order'=>$order,
			'limit'=>$this->module_limit,
			'offset'=>$this->module_offset
		);
		
		# Don't run it here because it's run in the Everywhere Observer Component
		$this->Review->runProcessRatings = false;		
		
		// Excludes listing owner info in Everywhere component
		$this->Review->controller = 'module_reviews'; 

//            Configure::write('System.debug',true);		
        $reviews = $this->Review->findAll($queryData);
//            Configure::write('System.debug',false);

		if(Sanitize::getInt($this->params['module'],'ajax_nav',1)) 
		{
			unset($queryData['order']);
			$count = $this->Review->findCount($queryData,'DISTINCT Review.id');				
		} else {
			$count = $this->module_limit;
		}

		# Send variables to view template		
		$this->set(
			array(
				'Access'=>$this->Access,
				'User'=>$this->_user,
				'reviews'=>$reviews,
				'total'=>$count				
				)
		);
		
		$page = $this->render('modules','reviews');
		
		# Save cached version		
/*		if($this->_user->id ===0) {	
			$this->cacheView('modules','reviews',$cache_file, $page);
		}		*/

		if($this->xajaxRequest) {
			$xajax->assign('jr_modContainer'.$module_id,'innerHTML',$page);
			return $xajax;
		} else {
			return $page;
		}				

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