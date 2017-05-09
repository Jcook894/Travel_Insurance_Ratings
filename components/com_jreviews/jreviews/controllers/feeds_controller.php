<?php
/**
 * JReviews - Reviews Extension
 * Copyright (C) 2010-2015 ClickFWD LLC
 * This is not free software, do not distribute it.
 * For licencing information visit https://www.jreviews.com
 * or contact sales@jreviews.com
**/

defined( 'MVC_FRAMEWORK') or die( 'Direct Access to this location is not allowed.' );

/* This is used for review feeds */
class FeedsController extends MyController {

	var $uses = array('user','menu','media','category','review','field','criteria');

	var $helpers = array('html','routes','media','text','custom_fields');

	var $components = array('config','access','feeds','everywhere','media_storage');

	var $autoRender = false; //Output is returned

	var $autoLayout = false;

	var $encoding = 'utf-8';

	function beforeFilter()
    {
        $this->params['action'] = 'xml';

		# Call beforeFilter of MyController parent class
		parent::beforeFilter();

		# Make configuration available in models
		$this->Listing->Config = &$this->Config;
	}

	// Need to return object by reference for PHP4
	function &getEverywhereModel() {
		return $this->Review;
	}

	function reviews()
	{
        $access =  $this->Access->getAccessLevels();

        $feed_filename = S2_CACHE . 'views' . DS . 'jreviewsfeed_'.md5($access.$this->here).'.xml';

        $this->Feeds->useCached($feed_filename,'reviews');

        $menu_id = Sanitize::getInt($this->params,'Itemid');

        $extension = 'com_content';

        $custom_where = '';

        $custom_order = '';

        $dir_id = '';

        $cat_id = '';

        $action = '';

        $page_title = '';

        if($menu_id)
        {
        	$menuParams = $this->Menu->getMenuParams($menu_id);

        	$extension = Sanitize::getString($menuParams,'extension','com_content');

        	$dir_id = Sanitize::getInt($menuParams,'dirid');

        	$cat_id = Sanitize::getInt($menuParams,'catid');

	        $custom_where = Sanitize::getString($menuParams,'custom_where');

	        $custom_order = Sanitize::getString($menuParams,'custom_order');

	        $action = Sanitize::getInt($menuParams,'action'); // 24 is custom list

	        $page_title = Sanitize::getString($menuParams,'page_title');
        }

		$extension = Sanitize::getString($this->params,'extension',$extension);

		$cat_id = Sanitize::getInt($this->params,'cat',$cat_id);

        $dir_id = Sanitize::getInt($this->params,'dir',$dir_id);

		$listing_id = Sanitize::getInt($this->params,'id');

		$this->encoding = cmsFramework::getCharset();

		$feedPage = null;

		$this->EverywhereAfterFind = true; // Triggers the afterFind in the Observer Model

		$this->limit = $this->Config->rss_limit;

		$rss = array(
			'title'=>$this->Config->rss_title,
			'link'=>WWW_ROOT,
			'description'=>$this->Config->rss_description,
			'image_url'=>WWW_ROOT . "images/stories/" . $this->Config->rss_image,
			'image_link'=>WWW_ROOT
		);

		$conditions = array(
			'Review.published = 1',
			"Review.mode = '$extension'", //Everywhere
		);

		$joins = array();

        if($action == 24 && $custom_where !='')
        {
            $custom_where = str_replace('{user_id}',$this->_user->id,$custom_where);

            $this->Review->conditions[] = '(' . $custom_where . ')';

            // If custom where includes conditions for ReviewField model then we add a join to those tables
            if(strstr($custom_where,'ReviewField.')) {

                $this->Review->joins['ReviewField'] = "LEFT JOIN #__jreviews_review_fields AS ReviewField ON ReviewField.reviewid = Review.id";
            }

            // If custom where includes conditions for Field models then we add a join to those tables
            if($extension = 'com_content' && strstr($custom_where,'Field.'))
            {
                $this->params['data']['extension'] = 'com_content';

                $this->Review->joins['Field'] = "LEFT JOIN #__jreviews_content AS Field ON Field.contentid = Review.pid";
            }
        }

        $custom_order !='' and $action == 24 and $this->Review->order[] = $custom_order;

        if($extension == 'com_content')
        {
            $state = $access = 1;

            $this->Listing->addCategoryFiltering($conditions, $this->Access, compact('access','state','cat_id','dir_id'));

            $this->Listing->addListingFiltering($conditions, $this->Access, compact('access','state'));

            if($cat_id)
            {
            	$feedPage = 'category';
            }
            elseif($dir_id)
            {
            	$feedPage = 'directory';
            }
        }
        elseif($extension != 'com_content')
        {
		    $feedPage = 'everywhere';
        }

		if($listing_id > 0)
		{
			$conditions[] = 'Review.pid = ' . $listing_id;

            $feedPage = 'listing';
		}

		# Don't run it here because it's run in the Everywhere Observer Component

		$this->Review->runProcessRatings = false;

        // Remove unused joins

		unset($this->Review->joins['JreviewsCategory'],$this->Review->joins['Criteria']);

		$queryData = array(
			'conditions'=>$conditions,
			'fields'=>array(
				'Review.mode AS `Review.extension`'
			),
			'joins'=>$joins,
			'limit'=>$this->limit,
			'order'=>array('Review.created DESC')
		);

		$reviews = $this->Review->findAll($queryData);

		$this->set(array(
			'page_title'=>$page_title,
            'feedPage'=>$feedPage,
			'encoding'=>$this->encoding,
			'rss'=>$rss,
			'reviews'=>$reviews
		));

		return $this->Feeds->saveFeed($feed_filename,'reviews');
	}
}
