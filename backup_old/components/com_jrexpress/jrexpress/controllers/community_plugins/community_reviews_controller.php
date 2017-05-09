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

class CommunityReviewsController extends MyController {
		
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
		$module_id = Sanitize::getVar($this->params,'module_id',Sanitize::getVar($this->data,'module_id'));
		
		$cache_file = $module_id.'_'.md5(serialize($this->params));
		
		if($this->xajaxRequest) {
			$xajax = new xajaxResponse();
			$this->params = $Session->get($module_id,null,S2Paths::get('jrexpress','S2_CMSCOMP'));
		} else {
			srand((float)microtime()*1000000);
			$this->params['rand'] = rand();
			$Session->set($module_id,$this->params,S2Paths::get('jrexpress','S2_CMSCOMP'));
		}

		if(!Sanitize::getVar($this->params['module'],'community')) {
			cmsFramework::noAccess();
			return;
		}		
				
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
		$extension = Sanitize::getString($this->params['module'],'extension');
		$user_id = Sanitize::getInt($this->params,'user',$this->_user->id);		
				
		if(!$user_id && !$this->_user->id) {
			cmsFramework::noAccess();
			return;					
		}	
				
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
	
		# Remove unnecessary fields from model query
//		$this->Review->modelUnbind();
		if($extension != '') {		
			$conditions[] =  "Review.mode = '$extension'"; 
		}
				
		$conditions[] = "Review.userid = " . (int) $user_id;
						
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
			if(Sanitize::getInt($this->params['module'],'cat_auto') && method_exists($this->Listing,'catUrlParam')) {
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

		$reviews = $this->Review->findAll($queryData);
		
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
				'total'=>$count,
				'module_id'=>$module_id	
				)
		);
		
		$page = $this->render('community_plugins','community_myreviews');
		
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
}