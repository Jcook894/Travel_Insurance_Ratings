<?php
/**
 * JReviews - Reviews Extension
 * Copyright (C) 2010-2012 ClickFWD LLC
 * This is not free software, do not distribute it.
 * For licencing information visit http://www.reviewsforjoomla.com
 * or contact sales@reviewsforjoomla.com
**/

defined( 'MVC_FRAMEWORK') or die( 'Direct Access to this location is not allowed.' );

App::import('Controller','common','jreviews');

class ModuleReviewsController extends MyController 
{
	var $uses = array('user','menu','category','review','field','criteria');
	
	var $helpers = array(/*'cache',*/'paginator','routes','libraries','html','assets','text','time','jreviews','community','custom_fields','rating','thumbnail');
	
	var $components = array('config','access','everywhere');
	
	var $autoRender = false;
	
	var $autoLayout = false;
	
	var $layout = 'module';
			
	function beforeFilter() {
		
        Configure::write('ListingEdit',false);       			
		
        # Call beforeFilter of MyController parent class
		parent::beforeFilter();
		
		# Stop AfterFind actions in Review model
		$this->Review->rankList = false;		
		
	}
	   
    // Need to return object by reference for PHP4
    function &getEverywhereModel() {
        return $this->Review;
    }           
	
	function index()
	{			
/*        if($this->_user->id === 0) 
        {
            $this->cacheAction = Configure::read('Cache.expires');        
        }   */
            		
        $this->EverywhereAfterFind = true; // Triggers the afterFind in the Observer Model
		
        if(!isset($this->params['module'])) $this->params['module'] = array(); // For direct calls to the controller
        
		$module_id = Sanitize::getInt($this->params,'module_id',Sanitize::getInt($this->data,'module_id'));
		
		if(empty($this->params)) 
        {
            $query = "SELECT params FROM #__modules WHERE id = " . $module_id;
            $this->_db->setQuery($query);
            $this->params['module'] = stringToArray($this->_db->loadResult());
        }

        $ids = $conditions = $joins = $order = array();

		# Read module parameters
		$extension = Sanitize::getString($this->params['module'],'extension');
        $reviews_type = Sanitize::getString($this->params['module'],'reviews_type');
        $custom_where = Sanitize::getString($this->params['module'],'custom_where');
		$cat_id = Sanitize::getString($this->params['module'],'category');
		$listing_id = Sanitize::getString($this->params['module'],'listing');
        $limit = Sanitize::getInt($this->params['module'],'module_limit',5);
        $total = min(50,Sanitize::getInt($this->params['module'],'module_total',10));
			
		if($extension == 'com_content') {
			$dir_id = Sanitize::getString($this->params['module'],'dir');
			$section_id = Sanitize::getString($this->params['module'],'section');
			$criteria_id = Sanitize::getString($this->params['module'],'criteria');
		} else {		
			$dir_id = null;
			$section_id = null;
			$criteria_id = null;
		}

        # Prevent sql injection
        $token = Sanitize::getString($this->params,'token');
        $tokenMatch = 0 === strcmp($token,cmsFramework::formIntegrityToken($this->params,array('module','module_id','form','data'),false));   
        
        isset($this->params['module']) and $this->viewSuffix = Sanitize::getString($this->params['module'],'tmpl_suffix');
        
		// This parameter determines the module mode
		$sort = Sanitize::getString($this->params['module'],'reviews_order');
		
        if(in_array($sort,array('random'))) {
            srand((float)microtime()*1000000);
            $this->params['rand'] = rand();
        }
                
		# Category auto detect
        if(Sanitize::getInt($this->params['module'],'cat_auto') && $extension == 'com_content') 
		{			
            $ids = CommonController::_discoverIDs($this);
            extract($ids);
        }

		$extension != '' and $conditions[] =  "Review.mode = " . $this->quote($extension); 
				
		# Set conditionals based on configuration parameters
		if($extension == 'com_content') 
		{ 
            $conditions = array_merge($conditions,array(
                'Listing.state = 1',
                '( Listing.publish_up = "'.NULL_DATE.'" OR DATE(Listing.publish_up) <= DATE("'._CURRENT_SERVER_TIME.'") )',
                '( Listing.publish_down = "'.NULL_DATE.'" OR DATE(Listing.publish_down) >= DATE("'._CURRENT_SERVER_TIME.'") )'
            ));   
            
            if($this->cmsVersion == CMS_JOOMLA15)
            {
//                $conditions[] = 'Section.access <= ' . $this->Access->getAccessId();
                $conditions[] = 'Category.access <= ' . $this->Access->getAccessId();
                $conditions[] = 'Listing.access <= ' . $this->Access->getAccessId();
            }
            else 
            {
                $conditions[] = 'Category.access IN (' . $this->Access->getAccessLevels() . ')';
                $conditions[] = 'Listing.access IN ( ' . $this->Access->getAccessLevels() . ')';
            }

            if(!empty($cat_id))
            {        
                if($this->cmsVersion == CMS_JOOMLA15) {
                    $conditions[] = 'Listing.catid IN ('.cleanIntegerCommaList($cat_id).')'; 
                }
                else
                {
                    $this->Review->joins['ParentCategory'] = "LEFT JOIN #__categories AS ParentCategory ON Category.lft BETWEEN ParentCategory.lft AND ParentCategory.rgt";              
                    $conditions[] = 'ParentCategory.id IN ('.cleanIntegerCommaList($cat_id).')';
                }
            } 

            empty($cat_id) and !empty($section_id) and $conditions[] = 'Listing.sectionid IN (' .cleanIntegerCommaList($section_id). ')';

            empty($cat_id) and !empty($dir_id) and $conditions[] = 'JreviewsCategory.dirid IN (' . cleanIntegerCommaList($dir_id) . ')';
    
            empty($cat_id) and !empty($criteria_id) and $conditions[] = 'JreviewsCategory.criteriaid IN (' . cleanIntegerCommaList($criteria_id) . ')';
		} 
        else 
        {
			if(Sanitize::getInt($this->params['module'],'cat_auto') && isset($this->Listing) && method_exists($this->Listing,'catUrlParam')) {
				if($cat_id = Sanitize::getInt($this->passedArgs,$this->Listing->catUrlParam())){
					$conditions[] = 'JreviewsCategory.id IN (' . $cat_id. ')';
				}
			} elseif($cat_id) {	
				$conditions[] = 'JreviewsCategory.id IN (' . cleanIntegerCommaList($cat_id). ')';
			}		
		}
		
		$listing_id and $conditions[] = "Review.pid IN ( ". cleanIntegerCommaList($listing_id) .")";
		                                        
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

        switch($reviews_type)
        {
            case 'all':
            break;
            case 'user':
                $conditions[] = 'Review.author = 0';    
            break;
            case 'editor':
                $conditions[] = 'Review.author = 1';    
            break;
        }
                
        # Custom WHERE
       $tokenMatch and $custom_where and $conditions[] = $custom_where;
                            
		$queryData = array(
			'joins'=>$joins,
			'conditions'=>$conditions,
			'order'=>$order,
			'limit'=>$total
		);
		     
		# Don't run it here because it's run in the Everywhere Observer Component
		$this->Review->runProcessRatings = false;		
		
		// Excludes listing owner info in Everywhere component
		$this->Review->controller = 'module_reviews'; 
     
        $reviews = $this->Review->findAll($queryData);

        $count = count($reviews);
                                
		# Send variables to view template		
		$this->set(
			array(
                'autodetect_ids'=>$ids,
				'reviews'=>$reviews,
				'total'=>$count,
                'limit'=>$limit				
				)
		);
		
        $this->_completeModuleParamsArray();
        
        $page = $this->ajaxRequest && empty($reviews) ? '' : $this->render('modules','reviews');

/*        if($this->_user->id === 0 && $this->ajaxRequest) 
        {
            $path = $this->here;

            $this->here == '/' and $path = 'home';
            
            $cache_fname = Inflector::slug($path) . '.php';
         
            $now = time();

            $cacheTime = is_numeric($this->cacheAction) ? $now + $this->cacheAction : strtotime($this->cacheAction, $now);
         
            $fileHeader = '<!--cachetime:' . $cacheTime . '-->'; 
            
            cache('views' . DS . $cache_fname, $fileHeader . $this->ajaxResponse($page,false), $this->cacheAction);
        }*/
                
        return $this->ajaxRequest ? $this->ajaxResponse($page,false) : $page;
	}
    
    /**
    * Ensures all required vars for theme rendering are in place, otherwise adds them with default values
    */
    
    function _completeModuleParamsArray() 
    {
        $params = array(
            'show_numbers'=>false,
            'show_comments'=>false,
            'comments_words'=>10,
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
