<?php
/**
 * JReviews - Reviews Extension
 * Copyright (C) 2010-2015 ClickFWD LLC
 * This is not free software, do not distribute it.
 * For licencing information visit https://www.jreviews.com
 * or contact sales@jreviews.com
**/

defined( 'MVC_FRAMEWORK') or die( 'Direct Access to this location is not allowed.' );

class CommunityReviewsController extends MyController {

	var $uses = array('user','menu','category','review','field','criteria','media');

	var $helpers = array('routes','paginator','libraries','html','assets','text','time','jreviews','community','custom_fields','rating','media');

	var $components = array('config','access','everywhere','media_storage');

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
    function &getEverywhereModel() {
        return $this->Review;
    }

	function index()
	{
		$this->EverywhereAfterFind = true; // Triggers the afterFind in the Observer Model

		$module_id = Sanitize::getVar($this->params,'module_id',Sanitize::getVar($this->data,'module_id'));

		if(!Sanitize::getVar($this->params['module'],'community'))
		{
			return cmsFramework::noAccess();
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

        $limit = Sanitize::getInt($this->params['module'],'module_limit',5);

        $total = min(50,Sanitize::getInt($this->params['module'],'module_total',10));

		if(!$user_id && !$this->_user->id)
		{
			return cmsFramework::noAccess();
		}

		$cat_id = Sanitize::getString($this->params['module'],'category');

		$dir_id = Sanitize::getString($this->params['module'],'dir');

		$criteria_id = Sanitize::getString($this->params['module'],'criteria');

		$listing_id = Sanitize::getString($this->params['module'],'listing');

		// This parameter determines the module mode
		$sort = Sanitize::getString($this->params['module'],'reviews_order');

		!empty($extension) and $conditions[] =  "Review.mode = '$extension'";

		$conditions[] = "Review.userid = " . (int) $user_id;

		# Set conditionals based on configuration parameters

		if(isset($this->Listing))
		{
	        $state = 1; // Only published results

	        $this->Listing->addCategoryFiltering($conditions, $this->Access, compact('listing_id','cat_auto','extension','state','cat_id','criteria_id','dir_id'));

	        $this->Listing->addListingFiltering($conditions, $this->Access, compact('state'));
		}

        if($extension == 'com_content')
        {
        	unset($this->Review->joins['JreviewsCategory'], $this->Review->joins['Criteria']);
        }

		!empty($listing_id) and $conditions[] = "Review.pid IN ($listing_id)";

		$conditions[] = 'Review.published > 0';

		switch($sort) {
			case 'latest':
				$order[] = $this->Review->processSorting('rdate');
				break;
			case 'helpful':
				$order[] = $this->Review->processSorting('helpful');
				break;
			case 'random':
                srand((float)microtime()*1000000);
                $this->params['rand'] = rand();
				$order[] = 'RAND('.$this->params['rand'].')';
				break;
			default:
				$order[] = $this->Review->processSorting('rdate');
				break;
		}

		$queryData = array(
			'joins'=>$joins,
			'conditions'=>$conditions,
			'order'=>$order,
			'limit'=>$total
		);

		# Don't run it here because it's run in the Everywhere Observer Component
		$this->Review->runProcessRatings = false;

		$reviews = $this->Review->findAll($queryData);

		$count = count($reviews);

		# Send variables to view template
		$this->set(
			array(
                'module_id'=>$module_id,
				'reviews'=>$reviews,
				'total'=>$count,
                'limit'=>$limit
				)
		);

        $this->_completeModuleParamsArray();

        $page = $this->ajaxRequest && empty($reviews) ? '' : $this->render('community_plugins','community_myreviews');

        return $page;
	}

	function myReviewCount()
	{
		$this->EverywhereAfterFind = true; // Triggers the afterFind in the Observer Model

		$conditions = array();

		$joins = array();

		// Initialize variables

		$id = Sanitize::getInt($this->params,'id');

		$option = Sanitize::getString($this->params,'option');

		$view = Sanitize::getString($this->params,'view');

		$task = Sanitize::getString($this->params,'task');

		$menu_id = Sanitize::getString($this->params,'Itemid');

		# Read module parameters

		$extension = Sanitize::getString($this->params['module'],'extension');

		$user_id = Sanitize::getInt($this->params,'user');

		if(!$user_id)
		{
			return 0;
		}

		$cat_id = Sanitize::getString($this->params['module'],'category');

		$dir_id = Sanitize::getString($this->params['module'],'dir');

		$criteria_id = Sanitize::getString($this->params['module'],'criteria');

		$listing_id = Sanitize::getString($this->params['module'],'listing');

		!empty($extension) and $conditions[] =  "Review.mode = '$extension'";

		$conditions[] = "Review.userid = " . (int) $user_id;

		# Set conditionals based on configuration parameters

		if(isset($this->Listing))
		{
	        $state = 1; // Only published results

	        $this->Listing->addCategoryFiltering($conditions, $this->Access, compact('listing_id','cat_auto','extension','state','cat_id','criteria_id','dir_id'));

	        $this->Listing->addListingFiltering($conditions, $this->Access, compact('state'));
		}

        if($extension == 'com_content')
        {
        	unset($this->Review->joins['JreviewsCategory'], $this->Review->joins['Criteria']);
        }

		!empty($listing_id) and $conditions[] = "Review.pid IN ($listing_id)";

		$conditions[] = 'Review.published > 0';

		$queryData = array(
			'joins'=>$joins,
			'conditions'=>$conditions,
			'session_cache'=>false
		);

		$count = $this->Review->findCount($queryData);

		return $count;
	}

    function _completeModuleParamsArray()
    {
        $params = array(
            'show_numbers'=>false,
            'fields'=>'',
            'show_comments'=>false,
            'comments_words'=>10,
            'tn_mode'=>'crop',
            'tn_size'=>'100x100',
            'tn_show'=>true,
            'tn_position'=>'left',
            'columns'=>1,
            'orientation'=>'horizontal',
            'slideshow'=>false,
            'slideshow_interval'=>6,
            'nav_position'=>'bottom',
            'link_title'=>'{listing_title}'
        );

        $this->params['module'] = array_merge($params, $this->params['module']);
    }

}
