<?php

/**
 * JReviews - Reviews Extension
 * Copyright (C) 2010-2015 ClickFWD LLC
 * This is not free software, do not distribute it.
 * For licencing information visit https://www.jreviews.com
 * or contact sales@jreviews.com.
 **/
defined('MVC_FRAMEWORK') or die('Direct Access to this location is not allowed.');

S2App::import('Controller', 'common', 'jreviews');

class ModuleReviewsController extends MyController
{
    public $uses = array('user','menu','category','review','field','criteria','media');

    public $helpers = array('paginator','routes','libraries','html','assets','text','time','jreviews','community','custom_fields','rating','media');

    public $components = array('config','access','everywhere','media_storage','listings_repository');

    public $autoRender = false;

    public $autoLayout = true;

    public $layout = 'module';

    public function beforeFilter()
    {
        Configure::write('ListingEdit', false);

        # Call beforeFilter of MyController parent class
        parent::beforeFilter();

        # Stop AfterFind actions in Review model
        $this->Review->rankList = false;
    }

    public function getEverywhereModel()
    {
        return $this->Review;
    }

    public function index()
    {
        $this->EverywhereAfterFind = true; // Triggers the afterFind in the Observer Model

        if (!isset($this->params['module']))
        {
            $this->params['module'] = array();
        }

        // For direct calls to the controller

        $ids = $currentListing = $conditions = $joins = $order = $having = array();

        $module_id = Sanitize::getString($this->params,'module_id',Sanitize::getString($this->data,'module_id'));

        if(!isset($this->params['module'])) $this->params['module'] = array(); // For direct calls to the controller

       # Find the correct set of params to use
        if(Sanitize::getInt($this->params,'listing_id'))
        {
            $currentListing = $this->__processListingTypeWidgets($conditions);
        }
        elseif($this->ajaxRequest && empty($this->params['module']) && $module_id)
        {
            $this->params['module'] = cmsFramework::getModuleParams($module_id);
        }

        # Read module parameters
        $cat_auto = Sanitize::getInt($this->params['module'], 'cat_auto');

        $extension = Sanitize::getString($this->params['module'], 'extension');

        $reviews_type = Sanitize::getString($this->params['module'], 'reviews_type');

        $dir_id = Sanitize::getVar($this->params['module'], 'dir', array());

        $cat_id = Sanitize::getVar($this->params['module'], 'category', array());

        $listing_id = Sanitize::getString($this->params['module'], 'listing');

        $excludeDirId = Sanitize::getVar($this->params['module'], 'exclude_dirid');

        $limit = Sanitize::getInt($this->params['module'], 'module_limit', 5);

        $total = Sanitize::getInt($this->params['module'], 'module_total', 10);

        $custom_order = Sanitize::getString($this->params['module'], 'custom_order');

        $custom_where = Sanitize::getString($this->params['module'], 'custom_where');

        $criteria_id = Sanitize::getVar($this->params['module'], 'criteria');

        if ($custom_where != '')
        {
            $custom_where = str_replace('{user_id}', $this->_user->id, $custom_where);
        }

        # Prevent sql injection
        $token = Sanitize::getString($this->params, 'token');

        $tokenMatch = 0 === strcmp($token, cmsFramework::formIntegrityToken($this->params, array('module', 'module_id', 'form', 'data'), false));

        isset($this->params['module']) and $this->viewSuffix = Sanitize::getString($this->params['module'], 'tmpl_suffix');

        // This parameter determines the module mode
        $sort = Sanitize::getString($this->params['module'], 'reviews_order');

        if (in_array($sort, array('random')))
        {
            srand((float) microtime() * 1000000);
            $this->params['rand'] = rand();
        }

        # Category auto detect
        if ($cat_auto && $extension == 'com_content')
        {
            $ids = CommonController::_discoverIDs($this, $excludeKeys = array('listing_id'));

            extract($ids);
        }

        if ($extension != '')
        {
            $conditions[] = 'Review.mode = '.$this->Quote($extension);
        }

        // If custom where includes conditions for ReviewField model then we add a join to those tables

        if ($custom_where != '' && strstr($custom_where, 'ReviewField.'))
        {
            $joins['ReviewField'] = 'LEFT JOIN #__jreviews_review_fields AS ReviewField ON ReviewField.reviewid = Review.id';
        }

        # Set conditionals based on configuration parameters

        if (isset($this->Listing) && $extension == 'com_content')
        {
            $state = 1; // Only published results

            $this->Listing->addCategoryFiltering($conditions, $this->Access, compact('listing_id', 'cat_auto', 'extension', 'state', 'cat_id', 'dir_id', 'criteria_id'));

            $this->Listing->addListingFiltering($conditions, $this->Access, compact('state'));

            $this->ListingsRepository->whereDirIdNotIn($excludeDirId);

            $listingConditions = $this->ListingsRepository->getQueryData();

            $listingConditions = $listingConditions['conditions'];

            $conditions = array_merge($conditions, $listingConditions);

            if (isset($click2search)) {
                $query = '
                    SELECT
                        Field.type
                    FROM
                        #__jreviews_fields AS Field
                    WHERE
                        Field.name = '.$this->Quote($click2search['field']);

                $type = $this->Field->query($query, 'loadResult');

                if (in_array($type, array('select', 'selectmultiple', 'checkboxes', 'radiobuttons'))) {
                    $conditions[] = 'Field.'.$click2search['field'].' LIKE '.$this->QuoteLike('*'.$click2search['value'].'*');
                }
                else {
                    $conditions[] = 'Field.'.$click2search['field'].' = ' . $this->Quote($click2search['value']);
                }
            }

            // If custom where includes conditions for Field model then we add a join to those tables

            if (
                ($custom_where != '' && (strstr($custom_where, 'Field.')))
                ||
                isset($click2search)
            ) {
                $joins['Field'] = 'LEFT JOIN #__jreviews_content AS Field ON Field.contentid = Review.pid';
            }

            // Perform tag replacement for listing_id
            if ($custom_where != ''
                && (Sanitize::getString($this->params, 'view') == 'article' || Sanitize::getString($this->passedArgs, 'url') == 'discussions/review')
                ) {
                $curr_listing_id = Sanitize::getInt($this->params, 'id');

                $custom_where = str_replace(
                    array('{listing_id}'),
                    array($curr_listing_id),
                    $custom_where);
            }

            // Remove unused joins

            unset($this->Review->joins['JreviewsCategory'], $this->Review->joins['Criteria']);
        }

        $listing_id and $conditions[] = 'Review.pid IN ( '.cleanIntegerCommaList($listing_id).')';

        $conditions[] = 'Review.published > 0';

        # Modify query for correct ordering.
        if ($tokenMatch and $custom_order) {
            $order[] = $custom_order;
        } else {
            switch ($sort) {
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
        }

        switch ($reviews_type) {
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
        $tokenMatch and $custom_where and $conditions[] = '('.$custom_where.')';

        $queryData = array(
            'joins' => $joins,
            'conditions' => $conditions,
            'order' => $order,
            'limit' => $total,
        );

        # Don't run it here because it's run in the Everywhere Observer Component
        $this->Review->runProcessRatings = false;

        // Sep 25, 2016 - added to exclude reviews from unpublished categories and listings
        if ($extension == '')
        {
            $queryData = $this->Everywhere->createUnionQuery($queryData,array('listing_id'=>'Review.pid','extension'=>'Review.mode'));
        }

        $reviews = $this->Review->findAll($queryData,array('afterFind','plgAfterFind'));

        $count = count($reviews);

        # Send variables to view template
        $this->set(
            array(
                'autodetect_ids' => $ids,
                'reviews' => $reviews,
                'total' => $count,
                'limit' => $limit,
                )
        );

        $this->_completeModuleParamsArray();

        $page = $this->ajaxRequest && empty($reviews) ? '' : $this->render('modules', 'reviews');

        return $page;
    }

    /**
     * Ensures all required vars for theme rendering are in place, otherwise adds them with default values.
     */
    public function _completeModuleParamsArray()
    {
        $params = array(
            'show_numbers' => false,
            'fields' => '',
            'show_comments' => false,
            'comments_words' => 10,
            'tn_mode' => 'crop',
            'tn_size' => '100x100',
            'tn_show' => true,
            'tn_position' => 'left',
            'columns' => 1,
            'orientation' => 'horizontal',
            'slideshow' => false,
            'slideshow_interval' => 6,
            'nav_position' => 'bottom',
            'link_title' => '{listing_title}',
            'custom_link_position'=>'top-right',
            'custom_link_1_url'=>'',
            'custom_link_1_text'=>'',
            'custom_link_2_url'=>'',
            'custom_link_2_text'=>'',
            'custom_link_3_url'=>'',
            'custom_link_3_text'=>''
        );

        $this->params['module'] = array_merge($params, $this->params['module']);
    }

    private function __processListingTypeWidgets(& $conditions)
    {
        $extension = Sanitize::getString($this->params['module'],'extension');

        $extension = $extension != '' ? $extension : 'com_content';

        if($extension != 'com_content') return;

        $widget_type = Sanitize::getString($this->params, 'type', 'owner');

        $key  = Sanitize::getInt($this->params,'key');

        $listing_id = Sanitize::getInt($this->params,'listing_id');

        S2App::import('Model', 'everywhere_com_content', 'jreviews');

        $listingModel = new EverywhereComContentModel;

        $listingModel->controller_name = 'module_reviews';

        $listingModel->controller_action = 'index';

        # Process Listing Type Related Listings settings

        $listing = $listingModel->findRow(array('conditions'=>array('Listing.' . EverywhereComContentModel::_LISTING_ID . ' = ' . $listing_id)));

        $listingTypeSettings = is_array($listing['ListingType']['config'][$widget_type])
            ?
                $listing['ListingType']['config'][$widget_type][$key]
            :
                $listing['ListingType']['config'][$widget_type]
            ;

        if(method_exists($this,'__'.$widget_type)) {

            $this->{'__'.$widget_type}($listing, $listingTypeSettings, $conditions);
        }

        unset($this->params['module']['custom_where'],$this->params['module']['custom_order']);

        // Required for processing of {listing_id} tag in custom where;

        $this->params['view'] = 'article';

        $this->params['id'] = $this->params['listing_id'];

        $this->params['module'] = array_merge($this->params['module'],$listingTypeSettings);

        // Ensures token validation will pass since we are reading the paramaters directly from the database
        $this->params['token'] = cmsFramework::formIntegrityToken($this->params,array('module','module_id','form','data'),false);

        return $listing;
    }

    private function __relatedreviews(&$listing, &$settings, &$conditions)
    {
        $match = Sanitize::getString($settings, 'match', 'owner');

        $created_by = $listing['User']['user_id'];

        $listing_id = $listing['Listing']['listing_id'];

        $filterListingTypeId = Sanitize::getVar($settings, 'criteria');

        switch($match)
        {
            case 'owner':

                // The listing owner matches the current listing owner

                $conditions[] = 'Review.userid = ' . $created_by;

                $conditions[] = 'Review.pid <> ' . $listing_id;

                if($filterListingTypeId)
                {
                    $conditions[] = 'Review.listing_type_id IN (' . cleanIntegerCommaList($filterListingTypeId). ')';
                }

                break;

            case 'listing_type':

                // Only filters by listing type

                $conditions[] = 'Listing.' . EverywhereComContentModel::_LISTING_ID . ' <> ' . $listing_id;

                break;
        }
    }
}
