<?php
/**
 * JReviews - Reviews Extension
 * Copyright (C) 2010-2015 ClickFWD LLC
 * This is not free software, do not distribute it.
 * For licencing information visit https://www.jreviews.com
 * or contact sales@jreviews.com
**/

defined( 'MVC_FRAMEWORK') or die( 'Direct Access to this location is not allowed.' );

class CommonController extends MyController {

    var $uses = array('menu');

    var $helpers = array();

    var $components = array('access','config');

    var $autoRender = false; //Output is returned

    var $autoLayout = false;

    function beforeFilter()
    {
        # Call beforeFilter of MyController parent class
        parent::beforeFilter();
    }

    /**
    * Called in community plugins to initialize $Config object if Joomla cache is enabled
    * and JReviews cache has been cleared
    * Don't add anything to this method!
    *
    */
    function index() {}

    /**
    * Category auto-detect
    */
    public static function _discoverIDs(& $controller, $excludeKeys = array())
    {
        $cacheKey = '_discoverIDs_' . implode('-', $excludeKeys);

		if($ids = Configure::read($cacheKey)) {
			return $ids;
		}

        // Initialize variables
        $ids = array();

        $id = Sanitize::getInt($controller->params,'id');

        $cat_id = Sanitize::getString($controller->params,'cat');

        $option = Sanitize::getString($controller->params,'option');

        $click2search = '';

        switch($option)
        {
            case 'com_jreviews':

                # Get url params for current controller/action
                $url = Sanitize::getString($controller->passedArgs,'url');

                switch($url)
                {
                    case 'discussions/review':

                        if ($reviewId = Sanitize::getInt($controller->passedArgs, 'id'))
                        {
                            $ids['review_id'] = $reviewId;
                        }

                    break;

                    case 'listings/detail':

                        $listing_id = $id;

                    break;
                }

                $route['url']['url'] = $url;

                $route = S2Router::parse($route,true,'jreviews');

                isset($route['data']['action']) and $route['data']['action'] == 'search' and $route = $route['url'];

                $dir_id = Sanitize::getString($route,'dir');

                !$cat_id and $cat_id = Sanitize::getString($route,'cat');

                $criteria_id = Sanitize::getString($route,'criteria');

                if ($cat_id != '')
                {
                    $cat_id = CommonController::makeModParamsUsable($cat_id);
                }
                elseif($criteria_id != '')
                {
                    $criteria_id = CommonController::makeModParamsUsable($criteria_id);
                }
                elseif($dir_id != '')
                {
                    $dir_id = CommonController::makeModParamsUsable($dir_id);
                }
                else
                { //Discover the params from the menu_id

                    $menu_id = Sanitize::getString($controller->params,'Itemid');

                    $params = $controller->Menu->getMenuParams($menu_id);

                    $dir_id = Sanitize::getString($params,'dirid');

                    $cat_id = Sanitize::getString($params,'catid');

                    $criteria_id = Sanitize::getString($params,'criteriaid');
                }

                // Click2search URLs

                $tag = Sanitize::getVar($controller->params,'tag');

                if(!empty($tag) && count($tag) == 2)
                {
                    S2App::import('Model','field','jreviews');

                    $tag['field'] = 'jr_' . $tag['field'];

                    $FieldModel = new FieldModel;

                    $fieldList = $FieldModel->getFieldNames();

                    if(in_array($tag['field'], $fieldList))
                    {
                        $click2search = $tag;
                    }
                }

                break;

            case 'com_content':

                    $view = Sanitize::getString($controller->params,'view');

                    $task = Sanitize::getString($controller->params,'task');

                    if ('article' == $view || 'view' == $task)
                    {
                        S2App::import('Model','everywhere_com_content','jreviews');

                        $ListingModel = new EverywhereComContentModel;

                        // If cat id was not available in url then we need to query it, otherwise it was already read above
                        if(!$cat_id)
                        {
                            $cat_id = $ListingModel->getCatID($id);
                        }

                        $listing_id = $id;
                    }
                    elseif ($view=="category")
                    {
                        $cat_id = $id;
                    }
                break;

            default:

                $cat_id = null; // Catid not detected because the page is neither content nor jreviews

                break;
        }

        isset($dir_id) and !empty($dir_id) and $ids['dir_id'] = cleanIntegerCommaList($dir_id);

        isset($cat_id) and !empty($cat_id) and $ids['cat_id'] = cleanIntegerCommaList($cat_id);

        isset($listing_id) and !empty($listing_id) and $ids['listing_id'] = cleanIntegerCommaList($listing_id);

        isset($criteria_id) and !empty($criteria_id) and $ids['criteria_id'] = cleanIntegerCommaList($criteria_id);

        isset($click2search) && !empty($click2search) and $ids['click2search'] = $click2search;

        if (isset($ids['criteria_id']))
        {
            $ids['listing_type_id'] = $ids['criteria_id'];
        }

        if(!empty($excludeKeys))
        {
            $ids = array_diff_key($ids, array_flip($excludeKeys));
        }

		Configure::write($cacheKey, $ids);

		return $ids;
    }

    /**
    * Used in modules
    *
    * @param mixed $param
    * @return string
    */
    public static function makeModParamsUsable($param)
    {
        if(empty($param)) return null;
        $urlSeparator = "_";
        return cleanIntegerCommaList(str_replace($urlSeparator,",",urldecode($param)));
    }

    /**
    * Returns sef urls passed as posted data via curl
    * Used to get front end sef urls from admin side
    *
    */
    function _sefUrl()
    {
        $sef_urls = array();

        $urls = Sanitize::getVar($this->data,'url');

        if(empty($urls)) return;

        foreach($urls AS $key=>$url)
        {
            $sef_urls[$key] = cmsFramework::route($url);
        }

        echo cmsFramework::jsonResponse($sef_urls);
    }

    /**
     * Adds the session token to forms
     * Called via ajax to save unnecessary processing and to avoid issues with cached pages
     */
    function _initForm()
    {
        $form_id = Sanitize::getString($this->data,'form_id');

        $response = array();

        if (!$form_id) return;

        if($this->Access->isGuest())
        {
            $user_session = UserAccountComponent::getUser();

            if(!empty($user_session))
            {
                S2App::import('Component','user_account','jreviews');

                $create_user_account = Sanitize::getBool($this->Config,'user_registration_guest');

                $response['create_account'] = $create_user_account;

                $response['name'] = $user_session['name'];

                $response['username'] = $user_session['username'];

                $response['email'] = $user_session['email'];
            }
        }

        $response['token'] = cmsFramework::getToken();

        return cmsFramework::jsonResponse($response);
    }
 }
