<?php
/**
 * JReviews - Reviews Extension
 * Copyright (C) 2010-2015 ClickFWD LLC
 * This is not free software, do not distribute it.
 * For licencing information visit https://www.jreviews.com
 * or contact sales@jreviews.com
**/

defined( 'MVC_FRAMEWORK') or die( 'Direct Access to this location is not allowed.' );

class ModuleFavoriteUsersController extends MyController {

    var $uses = array('user','menu','criteria','favorite','media');

    var $helpers = array('paginator','routes','libraries','html','assets','text','jreviews','community');

    var $components = array('access', 'config','media_storage');

    var $autoRender = false;

    var $autoLayout = true;

    var $layout = 'module';

    function beforeFilter() {

        # Call beforeFilter of MyController parent class
        parent::beforeFilter();
    }

    function index()
    {
        if(!isset($this->Community)) return;

        $module_id = Sanitize::getString($this->params,'module_id',Sanitize::getString($this->data,'module_id'));

        $listing_title = '';

        if(!isset($this->params['module'])) $this->params['module'] = array(); // For direct calls to the controller

        $listing_type_id = Sanitize::getInt($this->params,'listingtype',false);

       # Find the correct set of params to use

        if($this->ajaxRequest && $listing_type_id)
        {
            $listingType = $this->Criteria->getCriteria(array('criteria_id'=>$listing_type_id));

            if(isset($listingType['ListingType']['config']['userfavorites'])) {

                $userfavoritesParams = $listingType['ListingType']['config']['userfavorites'];

                $userfavoritesParams['criteria'] = implode(',',Sanitize::getVar($userfavoritesParams,'criteria',array()));

                $this->params['module'] = array_merge($this->params['module'],$userfavoritesParams);
            }
        }
        elseif($this->ajaxRequest && empty($this->params['module']) && $module_id) {

            $this->params['module'] = cmsFramework::getModuleParams($module_id);
        }

        srand((float)microtime()*1000000);

        $this->params['rand'] = rand();

        isset($this->params['module']) and $this->viewSuffix = Sanitize::getString($this->params['module'],'tmpl_suffix');

        // Read the module parameters
        $img_width         = Sanitize::getInt($this->params['module'],'img_width',50);

        $random_mode    = Sanitize::getString($this->params['module'],'random_mode','Random Users');

        $favorites_mode = Sanitize::getString($this->params['module'],'favorites_mode',JreviewsLocale::getPHP('FAVORITE_OTHER_INTERESTED_USERS'));

        $limit = Sanitize::getInt($this->params['module'],'module_limit',5);

        $total = Sanitize::getInt($this->params['module'],'module_total',10);

        # Get url params for current controller/action
        if(!$this->ajaxRequest) {

            $url = Sanitize::getString($_REQUEST, 'url');

            $route['url']['url'] = $url;

            $route['data'] = array();

            $route = S2Router::parse($route,true,'jreviews');

            # Check if page is listing detail
            $detail = (Sanitize::getString($route['url'],'extension','com_content') == 'com_content'
                        && isset($route['data'])
                        && Sanitize::getString($route['data'],'controller') == 'listings'
                        && Sanitize::getString($route['data'],'action') == 'detail')
                        ||
                        (Sanitize::getString($this->params,'option','com_content')
                            && Sanitize::getString($this->params,'view','article'))
                        ?
                            true
                            :
                            false;

            # Initialize variables
            $listing_id = $detail ? Sanitize::getInt($route,'id') : Sanitize::getInt($this->passedArgs,'id');

            $option = Sanitize::getString($this->passedArgs,'option');

            $view = Sanitize::getString($this->passedArgs,'view');

            $task = Sanitize::getString($this->passedArgs,'task');

            $listing_title = '';
        }
        else {

            $detail = true;

            $listing_id = Sanitize::getInt($this->params,'id');
        }

        # Article auto-detect - only for com_content

        if(!$listing_id) return false;

        if($detail || ('com_content' == $option && ('article' == $view || 'view' == $task))) {

            S2App::import('Model','everywhere_com_content','jreviews');

            $ListingModel = new EverywhereComContentModel;

            $listing_title = $ListingModel->getTitle($listing_id);
        }
        else {

            $listing_id = null;

            // hide the module on non-detail pages
            return false;
        }

        $profiles = $this->Community->getListingFavorites($listing_id, $this->_user->id, $this->params);

        $total = count($profiles);

        $this->set(array(
            'profiles'=>$profiles,
            'listing_title'=>$listing_title,
            'limit'=>$limit,
            'total'=>$total
        ));

        // hide the module if no one added the viewed listing to favorites
        if ($total == 0) return false;

        $this->_completeModuleParamsArray();

        $page = $this->ajaxRequest && empty($profiles) ? '' : $this->render('modules','favorite_users');

        return $page;
    }

    /**
    * Ensures all required vars for theme rendering are in place, otherwise adds them with default values
    */

    function _completeModuleParamsArray()
    {
        $params = array(
            'columns'=>1,
            'orientation'=>'horizontal',
            'slideshow'=>false,
            'slideshow_interval'=>6,
            'nav_position'=>'bottom'
        );

        $this->params['module'] = array_merge($params, $this->params['module']);
    }
}