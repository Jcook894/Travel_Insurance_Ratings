<?php
/**
 * JReviews - Reviews Extension
 * Copyright (C) 2010-2015 ClickFWD LLC
 * This is not free software, do not distribute it.
 * For licencing information visit https://www.jreviews.com
 * or contact sales@jreviews.com
**/

defined( 'MVC_FRAMEWORK') or die( 'Direct Access to this location is not allowed.' );

class CommunityListingsController extends MyController {

	var $uses = array('user','menu','criteria','field','favorite','media');

	var $helpers = array('routes','libraries','html','assets','text','jreviews','widgets','time','paginator','rating','custom_fields','community','media');

	var $components = array('config','access','everywhere','media_storage');

	var $autoRender = false; //Output is returned

	var $autoLayout = false;

    function getPluginModel()
    {
        return $this->Listing;
    }

	function beforeFilter() {

		# Call beforeFilter of MyController parent class
		parent::beforeFilter();
	}

	function favorites()
	{
		// Required for ajax pagination to remember module settings
		$module_id = Sanitize::getString($this->params,'module_id',Sanitize::getString($this->data,'module_id'));

        if(!Sanitize::getVar($this->params['module'],'community')) {

			return cmsFramework::noAccess();
		}

        $dir_id = Sanitize::getString($this->params['module'],'dir');

        $cat_id = Sanitize::getString($this->params['module'],'category');

        $listing_id = Sanitize::getString($this->params['module'],'listing');

		$user_id = Sanitize::getInt($this->params,'user',$this->_user->id);

		$sort = Sanitize::getString($this->params['module'],'listings_order');

        $limit = Sanitize::getInt($this->params['module'],'module_limit',5);

        $total = min(50,Sanitize::getInt($this->params['module'],'module_total',10));

        $compare = Sanitize::getInt($this->params['module'],'compare',0);

		if(!$user_id && !$this->_user->id)
		{
			return cmsFramework::noAccess();
		}

		$conditions = $joins = $order = array();

		# Get listings
		$joins[] = 	'INNER JOIN #__jreviews_favorites AS Favorite ON Listing.' . EverywhereComContentModel::_LISTING_ID . ' = Favorite.content_id AND Favorite.user_id = ' . $user_id;

        # Set conditionals based on configuration parameters

        $state = 1; // Only published results

        $this->Listing->addCategoryFiltering($conditions, $this->Access, compact('listing_id','extension','state','cat_id','dir_id'));

        $this->Listing->addListingFiltering($conditions, $this->Access, compact('state'));

        !empty($listing_id) and $conditions[] = "Listing.id IN ($listing_id)";

		switch($sort)
		{
			case 'random':

				$this->Listing->order = array();

				$this->Listing->processSorting('random');

				break;

			default:

				$this->Listing->order = array();

				$this->Listing->processSorting('rdate');

				break;
		}

		$queryData = array(
			'joins'=>$joins,
			'conditions'=>$conditions,
			'order'=>$order,
			'limit'=>$total
		);

		$listings = $this->Listing->findAll($queryData);

		$count = count($listings);

		# Send variables to view template
		$this->set(
			array(
                'module_id'=>$module_id,
				'listings'=>$listings,
                'compare'=>$compare,
                'total'=>$count,
                'limit'=>$limit
				)
		);

        $this->_completeModuleParamsArray();

        $page = $this->ajaxRequest && empty($listings) ? '' : $this->render('community_plugins','community_myfavorites');

        return $this->ajaxRequest ? $this->ajaxResponse($page,false) : $page;
    }

    function myFavoriteCount()
    {
        $dir_id = Sanitize::getString($this->params['module'],'dir');

        $cat_id = Sanitize::getString($this->params['module'],'category');

        $listing_id = Sanitize::getString($this->params['module'],'listing');

		$user_id = Sanitize::getInt($this->params,'user');

		if(!$user_id)
		{
			return 0;
		}

		$conditions = $joins = array();

		# Get listings
		$joins[] = 	'INNER JOIN #__jreviews_favorites AS Favorite ON Listing.' . EverywhereComContentModel::_LISTING_ID . ' = Favorite.content_id AND Favorite.user_id = ' . $user_id;

        # Set conditionals based on configuration parameters

        $state = 1; // Only published results

        $this->Listing->addCategoryFiltering($conditions, $this->Access, compact('listing_id','extension','state','cat_id','dir_id'));

        $this->Listing->addListingFiltering($conditions, $this->Access, compact('state'));

        !empty($listing_id) and $conditions[] = "Listing.id IN ($listing_id)";

		$queryData = array(
			'joins'=>$joins,
			'conditions'=>$conditions,
			'session_cache'=>false
			);

		$count = $this->Listing->findCount($queryData);

		return $count;

    }

	function mylistings()
	{
		// Required for ajax pagination to remember module settings
		$module_id = Sanitize::getString($this->params,'module_id',Sanitize::getString($this->data,'module_id'));

		if(!Sanitize::getVar($this->params['module'],'community')) {
			return cmsFramework::noAccess();
		}

		$dir_id = Sanitize::getString($this->params['module'],'dir');

		$cat_id = Sanitize::getString($this->params['module'],'category');

        $listing_id = Sanitize::getString($this->params['module'],'listing');

		$user_id = Sanitize::getInt($this->params,'user',$this->_user->id);

		$sort = Sanitize::getString($this->params['module'],'listings_order');

        $limit = Sanitize::getInt($this->params['module'],'module_limit',5);

        $total = min(50,Sanitize::getInt($this->params['module'],'module_total',10));

        $compare = Sanitize::getInt($this->params['module'],'compare',0);

		if(!$user_id && !$this->_user->id) {
			cmsFramework::noAccess();
			return;
		}

		$conditions = $joins = $order = array();

		# Get listings

		$conditions[] = 'Listing.' . EverywhereComContentModel::_LISTING_USER_ID . ' = ' . (int) $user_id;

        # Set conditionals based on configuration parameters

        $state = 1; // Only published results

        $this->Listing->addCategoryFiltering($conditions, $this->Access, compact('listing_id','extension','state','cat_id','dir_id'));

        $this->Listing->addListingFiltering($conditions, $this->Access, compact('state'));

        !empty($listing_id) and $conditions[] = "Listing." . EverywhereComContentModel::_LISTING_ID . " IN ($listing_id)";

		switch($sort)
		{
			case 'random':

				$this->Listing->order = array();

				$this->Listing->processSorting('random');

				break;

			default:

				$this->Listing->order = array();

				$this->Listing->processSorting('rdate');

				break;
		}

		$queryData = array(
			'joins'=>$joins,
			'conditions'=>$conditions,
			'order'=>$order,
			'limit'=>$total
            );

		$listings = $this->Listing->findAll($queryData);

        $count = count($listings);

		# Send variables to view template
		$this->set(
			array(
                'module_id'=>$module_id,
				'listings'=>$listings,
                'compare'=>$compare,
                'total'=>$count,
                'limit'=>$limit
				)
		);

        $this->_completeModuleParamsArray();

        $page = $this->ajaxRequest && empty($listings) ? '' : $this->render('community_plugins','community_mylistings');

        return $page;
	}

	function myListingCount()
	{
		$extension = 'com_content';

		$dir_id = Sanitize::getString($this->params['module'],'dir');

		$cat_id = Sanitize::getString($this->params['module'],'category');

        $listing_id = Sanitize::getString($this->params['module'],'listing');

		$user_id = Sanitize::getInt($this->params,'user');

		if(!$user_id) {

			return 0;
		}

		$conditions = $joins = array();

		# Get listings

		$conditions[] = 'Listing.' . EverywhereComContentModel::_LISTING_USER_ID . ' = ' . (int) $user_id;

        # Set conditionals based on configuration parameters

        $state = 1; // Only published results

        $this->Listing->addCategoryFiltering($conditions, $this->Access, compact('listing_id','extension','state','cat_id','dir_id'));

        $this->Listing->addListingFiltering($conditions, $this->Access, compact('state'));

        !empty($listing_id) and $conditions[] = "Listing." . EverywhereComContentModel::_LISTING_ID . " IN ($listing_id)";

		$queryData = array(
			'joins'=>$joins,
			'conditions'=>$conditions,
			'session_cache'=>false
            );

		$count = $this->Listing->findCount($queryData);

        return $count;
	}

    /**
    * Ensures all required vars for theme rendering are in place, otherwise adds them with default values
    */

    function _completeModuleParamsArray()
    {
        $params = array(
            'show_numbers'=>false,
            'fields'=>'',
            'summary'=>false,
            'summary_words'=>10,
            'show_category'=>true,
            'tn_mode'=>'crop',
            'tn_size'=>'100x100',
            'tn_show'=>true,
            'tn_position'=>'left',
            'columns'=>1,
            'orientation'=>'horizontal',
            'slideshow'=>false,
            'slideshow_interval'=>6,
            'nav_position'=>'bottom'
        );

        $this->params['module'] = array_merge($params, $this->params['module']);
    }

}
