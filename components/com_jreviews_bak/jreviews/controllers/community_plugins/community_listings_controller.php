<?php
/**
 * JReviews - Reviews Extension
 * Copyright (C) 2010-2011 ClickFWD LLC
 * This is not free software, do not distribute it.
 * For licencing information visit http://www.reviewsforjoomla.com
 * or contact sales@reviewsforjoomla.com
**/

defined( 'MVC_FRAMEWORK') or die( 'Direct Access to this location is not allowed.' );

class CommunityListingsController extends MyController {
	
	var $uses = array('user','menu','criteria','field','favorite');
	
	var $helpers = array('routes','libraries','html','assets','text','jreviews','time','paginator','rating','thumbnail','custom_fields','community');
	
	var $components = array('config','access','everywhere');

	var $autoRender = false; //Output is returned
	
	var $autoLayout = false;
			
	function beforeFilter() {

		# Call beforeFilter of MyController parent class
		parent::beforeFilter();
						
	}
			
	function favorites()
	{		
		// Required for ajax pagination to remember module settings
		$module_id = Sanitize::getString($this->params,'module_id',Sanitize::getString($this->data,'module_id'));
		$extension = 'com_content';
		
        if(!Sanitize::getVar($this->params['module'],'community')) {
			cmsFramework::noAccess();
			return;
		}				
			
		// Automagically load and initialize Everywhere Model
		App::import('Model','everywhere_'.$extension,'jreviews');
		$class_name = inflector::camelize('everywhere_'.$extension).'Model';
		$this->Listing = new $class_name();
		$this->Listing->_user = $this->_user;		
		
        $dir_id = Sanitize::getString($this->params['module'],'dir');
        $section_id = Sanitize::getString($this->params['module'],'section');
        $cat_id = Sanitize::getString($this->params['module'],'category');
        $listing_id = Sanitize::getString($this->params['module'],'listing');        
		$user_id = Sanitize::getInt($this->params,'user',$this->_user->id);
		$sort = Sanitize::getString($this->params['module'],'listings_order');
        $limit = Sanitize::getInt($this->params['module'],'module_limit',5);
        $total = min(50,Sanitize::getInt($this->params['module'],'module_total',10));
		
		if(!$user_id && !$this->_user->id) {
			cmsFramework::noAccess();
			return;					
		}	
							
		# Remove unnecessary fields from model query
		$this->Listing->modelUnbind('Listing.fulltext AS `Listing.description`');
				
		$conditions = array();
		$joins = array();
		
		# Get listings
		$joins[] = 	'INNER JOIN #__jreviews_favorites AS Favorite ON Listing.id = Favorite.content_id AND Favorite.user_id = ' . $user_id;
        
        # Set conditionals based on configuration parameters
        if($extension == 'com_content') 
        { // Only works for core articles 
            $conditions = array_merge($conditions,array(
                '( Listing.publish_up = "'.NULL_DATE.'" OR DATE(Listing.publish_up) <= DATE("'._CURRENT_SERVER_TIME.'") )',
                '( Listing.publish_down = "'.NULL_DATE.'" OR DATE(Listing.publish_down) >= DATE("'._CURRENT_SERVER_TIME.'") )',
                'Listing.catid > 0'
            )); 
                
            if($this->cmsVersion == CMS_JOOMLA15)
            {
                $conditions[] = 'Listing.access <= ' . $this->_user->gid;
            }
            else
            {
                $conditions[] = 'Listing.access IN (' . $this->Access->getAccessLevels() . ')';
            }
            
            $conditions[] = $this->Access->canEditListing() ? 'Listing.state >= 0' :  'Listing.state = 1';
          
            !empty($dir_id) and $conditions[] = 'JreviewsCategory.dirid IN (' . $dir_id . ')';
    
            !empty($section_id) and $conditions[] = 'Listing.sectionid IN (' . $section_id. ')';
    
            if(!empty($cat_id))
            {           
                $conditions[] = $this->cmsVersion == CMS_JOOMLA15 ? 'Listing.catid IN ('.$cat_id.')' : 'ParentCategory.id IN ('.$cat_id.')';
            } 
            else
            {
                unset($this->Listing->joins['ParentCategory']);
            }
        }       
        
        !empty($listing_id) and $conditions[] = "Listing.id IN ($listing_id)";
        
		switch($sort) {
			case 'random':
                srand((float)microtime()*1000000);
                $this->params['rand'] = rand();
				$this->Listing->order = array();
				$order[] = "RAND({$this->params['rand']})";				
				break;
			default:
				$this->Listing->order = array();
				$order[] = "Listing.{$this->Listing->dateKey} DESC";
				break;	
		}

		$queryData = array(
//			'fields' they are set in the model
			'joins'=>$joins,
			'conditions'=>$conditions,
			'order'=>$order,			
			'limit'=>$total
		);

		// This is used in Listings model to know whether this is a list page to remove the plugin tags
		$this->Listing->controller = 'categories';

        // Add custom fields to listings
        $this->Listing->addFields = true;
        
		$listings = $this->Listing->findAll($queryData);

		$count = count($listings);
		
		# Send variables to view template		
		$this->set(
			array(
                'module_id'=>$module_id,
				'listings'=>$listings,
                'total'=>$count,
                'limit'=>$limit
				)	
		);
        
        $this->_completeModuleParamsArray();

        $page = $this->ajaxRequest && empty($listings) ? '' : $this->render('community_plugins','community_myfavorites');
        
        return $this->ajaxRequest ? $this->ajaxResponse($page,false) : $page;
    }
	
	function mylistings()
	{		
		// Required for ajax pagination to remember module settings
		$module_id = Sanitize::getString($this->params,'module_id',Sanitize::getString($this->data,'module_id'));

		$extension = 'com_content';

		if(!Sanitize::getVar($this->params['module'],'community')) {
			cmsFramework::noAccess();
			return;
		}				

		// Automagically load and initialize Everywhere Model
		App::import('Model','everywhere_'.$extension,'jreviews');
		$class_name = inflector::camelize('everywhere_'.$extension).'Model';
		$this->Listing = new $class_name();
		$this->Listing->_user = $this->_user;		
		
		$dir_id = Sanitize::getString($this->params['module'],'dir');
		$section_id = Sanitize::getString($this->params['module'],'section');
		$cat_id = Sanitize::getString($this->params['module'],'category');
        $listing_id = Sanitize::getString($this->params['module'],'listing');        
		$user_id = Sanitize::getInt($this->params,'user',$this->_user->id);
		$sort = Sanitize::getString($this->params['module'],'listings_order');
        $limit = Sanitize::getInt($this->params['module'],'module_limit',5);
        $total = min(50,Sanitize::getInt($this->params['module'],'module_total',10));
        
		if(!$user_id && !$this->_user->id) {
			cmsFramework::noAccess();
			return;					
		}	
					
		# Remove unnecessary fields from model query
		$this->Listing->modelUnbind('Listing.fulltext AS `Listing.description`');
				
		$conditions = array();
		$joins = array();
		
		# Get listings
		$conditions[] = 'Listing.created_by = ' . (int) $user_id;

        # Set conditionals based on configuration parameters
        if($extension == 'com_content') 
        { // Only works for core articles    
            !empty($dir_id) and $conditions[] = 'JreviewsCategory.dirid IN (' . $dir_id . ')';
    
            !empty($section_id) and $conditions[] = 'Listing.sectionid IN (' . $section_id. ')';
    
            if(!empty($cat_id))
            {           
                $conditions[] = $this->cmsVersion == CMS_JOOMLA15 ? 'Listing.catid IN ('.$cat_id.')' : 'ParentCategory.id IN ('.$cat_id.')';
            } 
            else
            {
                unset($this->Listing->joins['ParentCategory']);
            }
        }       
        
        !empty($listing_id) and $conditions[] = "Listing.id IN ($listing_id)";
        
        if($extension == 'com_content') 
        { // Only works for core articles        
            if ( $this->Access->canEditListing() ) {
                $conditions[] = 'Listing.state >= 0';
            } else {
                $conditions[] = 'Listing.state = 1';
                $conditions[] = '( Listing.publish_up = "'.NULL_DATE.'" OR Listing.publish_up <= "'._CURRENT_SERVER_TIME.'" )';
                $conditions[] = '( Listing.publish_down = "'.NULL_DATE.'" OR Listing.publish_down >= "'._CURRENT_SERVER_TIME.'" )';
            }
            
            //Shows only links users can access
            if($this->cmsVersion == CMS_JOOMLA15)
            {
//                    $conditions[] = 'Section.access <= ' . $this->Access->getAccessId();
                $conditions[] = 'Category.access <= ' . $this->Access->getAccessId();
                $conditions[] = 'Listing.access <= ' . $this->Access->getAccessId();
            }
            else
            {
                $conditions[] = 'Category.access IN (' . $this->Access->getAccessLevels() . ')';
                $conditions[] = 'Listing.access IN (' . $this->Access->getAccessLevels() . ')';
            }
        }                          

		switch($sort) {
			case 'random':  
                srand((float)microtime()*1000000);
                $this->params['rand'] = rand();
				$this->Listing->order = array();
				$order[] = "RAND({$this->params['rand']})";				
				break;
			default:
				$this->Listing->order = array();
				$order[] = "Listing.{$this->Listing->dateKey} DESC";
				break;	
		}

		$queryData = array(
//			'fields' they are set in the model
			'joins'=>$joins,
			'conditions'=>$conditions,
			'order'=>$order,			
			'limit'=>$total
            );

		// This is used in Listings model to know whether this is a list page to remove the plugin tags
		$this->Listing->controller = 'categories';

        // Add custom fields to listings
        $this->Listing->addFields = true;
                
		$listings = $this->Listing->findAll($queryData);

        $count = count($listings);

		# Send variables to view template		
		$this->set(
			array(
                'module_id'=>$module_id,
				'listings'=>$listings,
                'total'=>$count,
                'limit'=>$limit
				)	
		);

        $this->_completeModuleParamsArray();

        $page = $this->ajaxRequest && empty($listings) ? '' : $this->render('community_plugins','community_mylistings');
        
        return $this->ajaxRequest ? $this->ajaxResponse($page,false) : $page;
	}	
    
    /**
    * Ensures all required vars for theme rendering are in place, otherwise adds them with default values
    */
    
    function _completeModuleParamsArray() 
    {
        $params = array(
            'show_numbers'=>false,
            'summary'=>false,
            'summary_words'=>10,
            'tn_mode'=>'crop',
            'tn_width'=>100,
            'tn_show'=>true,
            'columns'=>1,
            'orientation'=>'horizontal',
            'slideshow'=>false,
            'slideshow_interval'=>6,
            'nav_position'=>'bottom'            
        );    
        
        $this->params['module'] = array_merge($params, $this->params['module']);
    }      
	
}
