<?php
/**
 * JReviews - Reviews Extension
 * Copyright (C) 2010-2015 ClickFWD LLC
 * This is not free software, do not distribute it.
 * For licencing information visit https://www.jreviews.com
 * or contact sales@jreviews.com
**/

defined( 'MVC_FRAMEWORK') or die( 'Direct Access to this location is not allowed.' );

S2App::import('Controller','common','jreviews');

class ModuleListingsController extends MyController {

    var $uses = array('menu','field','criteria','media');

    var $helpers = array('paginator','routes','libraries','html','assets','text','jreviews','widgets','time','rating','custom_fields','community','media');

    var $components = array('config','access','everywhere','media_storage','listings_repository','geomaps_search','geomaps_listings','geomaps_geotargeting');

    var $autoRender = false;

    var $autoLayout = true;

    var $layout = 'module';

    var $abort = false;

    var $distance_metric = array();

    var $distance_in = 'mi';

    var $proximityModes = array('proximity', 'proximity_custom', 'proximity_geotargeting');

    var $proximityCenter = array();

    function beforeFilter()
    {
        Configure::write('ListingEdit',false);

        # Call beforeFilter of MyController parent class
        parent::beforeFilter();

        $this->distance_in = Sanitize::getString($this->Config,'geomaps.radius_metric','mi');

        $this->distance_metric = array('mi'=>__t("Miles",true),'km'=>__t("Km",true));
    }

    function getPluginModel()
    {
        return $this->Listing;
    }

    function index()
    {
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

        if($this->abort) return '';

        # Read module parameters
        $cat_auto = Sanitize::getInt($this->params['module'],'cat_auto');

        $extension = Sanitize::getString($this->params['module'],'extension');

        $extension = $extension != '' ? $extension : 'com_content';

        $dir_id = Sanitize::getVar($this->params['module'],'dir',array());

        $cat_id = Sanitize::getVar($this->params['module'],'category');

        $listing_id = Sanitize::getString($this->params['module'],'listing');

        $created_by = Sanitize::getString($this->params['module'],'owner');

        $criteria_id = Sanitize::getString($this->params['module'],'criteria');

        $excludeDirId = Sanitize::getVar($this->params['module'], 'exclude_dirid');

        $limit = Sanitize::getInt($this->params['module'],'module_limit',5);

        $compare = Sanitize::getInt($this->params['module'],'compare',0);

        $total = Sanitize::getInt($this->params['module'],'module_total',10);

        $sort = Sanitize::getString($this->params['module'],'listing_order');

        if(in_array($sort,array('random','featuredrandom'))) {

            srand((float)microtime()*1000000);

            $this->params['rand'] = rand();
        }

        # Prevent sql injection
        $token = Sanitize::getString($this->params,'token');

        $tokenMatch = 0 === strcmp($token,cmsFramework::formIntegrityToken($this->params,array('module','module_id','form','data'),false));

        isset($this->params['module']) and $this->viewSuffix = Sanitize::getString($this->params['module'],'tmpl_suffix');

        if(isset($this->Listing))
        {
            $this->Listing->_user = $this->_user;

            $custom_params = Sanitize::getString($this->params['module'],'custom_params');

            $custom_where = Sanitize::getString($this->params['module'],'custom_where');

            // This parameter determines the module mode
            $custom_order = Sanitize::getString($this->params['module'],'custom_order');


            if($extension != 'com_content' && in_array($sort,array('topratededitor','featuredrandom','rhits'))) {
                echo "You have selected the $sort mode which is not supported for components other than com_content. Please read the tooltips in the module parameters for more info on allowed settings.";
                return;
            }

            # Category auto detect

            if($cat_auto && $extension == 'com_content')
            {
                $ids = CommonController::_discoverIDs($this, $excludeKeys = array('listing_id'));

                extract($ids);
            }

            if($extension == 'com_content' && $custom_params)
            {
                $this->processCustomParams($custom_params, $cat_id, $criteria_id, $dir_id, $sort);

                // prx($cat_id, $criteria_id, $dir_id, $sort, $this->Listing->conditions);exit;
            }

            if($custom_where != '') {

                $custom_where = str_replace('{user_id}',$this->_user->id,$custom_where);
            }

            # Set conditionals based on configuration parameters

            if($extension == 'com_content')
            {
                if(isset($click2search))
                {
                    $query = "
                        SELECT
                            Field.type
                        FROM
                            #__jreviews_fields AS Field
                        WHERE
                            Field.name = " . $this->Quote($click2search['field']);

                    $type = $this->Field->query($query, 'loadResult');

                    if(in_array($type,array('select','selectmultiple','checkboxes','radiobuttons')))
                    {
                        $conditions[] = "Field." . $click2search['field'] . " LIKE " . $this->QuoteLike('*'.$click2search['value'].'*');
                    }
                    else {

                        $conditions[] = "Field." . $click2search['field'] . " = " . $this->Quote($click2search['value']);
                    }
                }

                // Perform tag replacement for listing_id to allow for related listing queries

                if(Sanitize::getString($this->params,'view') == 'article') {

                    $curr_listing_id = Sanitize::getInt($this->params,'id');

                    if($custom_where != '') {

                        $custom_where = str_replace(array('{listing_id}'),array($curr_listing_id),$custom_where);
                    }
                }

                // Process proximity search

                if (isset($this->GeomapsSearch) && in_array($sort, $this->proximityModes))
                {
                    $this->processProximitySearch($sort, $currentListing);
                }

                // Remove unnecessary fields from model query

                $this->Listing->modelUnbind(array(
                    'Listing.fulltext AS `Listing.description`',
                    'Listing.metakey AS `Listing.metakey`',
                    'Listing.metadesc AS `Listing.metadesc`',
                    'User.email AS `User.email`'
                ));
            }

            $state = 1; // Only published results

            // Add category filtering only if it hasn't been already applied before via the related listings widget code

            if(!isset($conditions['Category.cat_id']))
            {
                $this->Listing->addCategoryFiltering($conditions, $this->Access, compact('listing_id','cat_auto','extension','state','cat_id','dir_id','criteria_id'));
            }

            $this->Listing->addListingFiltering($conditions, $this->Access, compact('state'));

            $listing_id and $conditions[] = "Listing.{$this->Listing->realKey} IN (". cleanIntegerCommaList($listing_id) .")";

            switch($sort)
            {
                case 'random':

                    $order[] = 'RAND('.$this->params['rand'].')';

                    break;

                case 'featured':

                    $conditions[] = 'Field.featured = 1';

                    break;

                case 'featuredrandom':

                    $conditions[] = 'Field.featured = 1';

                    $order[] = 'RAND('.$this->params['rand'].')';

                    break;

                case 'topratededitor':

//                    $conditions[] = 'Totals.editor_rating > 0';

                	$sort = 'editor_rating';

                    break;
                // Editor rating sorting options dealt with in the Listing->processSorting method
            }

            # Custom WHERE
            $tokenMatch and $custom_where and $conditions[] = '('. $custom_where . ')';

            # Filtering options
            $having = array();

            // Listings submitted in the past x days
            $entry_period = Sanitize::getInt($this->params['module'],'filter_listing_period');

            if($entry_period > 0 && $this->Listing->dateKey)
            {
                $conditions[] = "Listing.{$this->Listing->dateKey} >= DATE_SUB('"._CURRENT_SERVER_TIME."', INTERVAL $entry_period DAY)";
            }

            // Listings with reviews submitted in past x days
            $review_period = Sanitize::getInt($this->params['module'],'filter_review_period');

            if($extension != '' && $review_period > 0)
            {
                $joins[] = "
                    INNER JOIN (
                        SELECT
                            Review.pid, Review.mode, count(*)
                        FROM
                            #__jreviews_comments AS Review
                        WHERE
                            Review.created >= DATE_SUB(CURDATE(), INTERVAL $review_period DAY)
                        GROUP BY
                            Review.pid, Review.mode
                    ) AS Review ON Listing.{$this->Listing->realKey} = Review.pid AND Review.mode = '{$extension}'
                ";
            }

            // Listings with review count higher than

            $filter_review_count = Sanitize::getInt($this->params['module'],'filter_review_count');

            $filter_review_count > 0 and $conditions[] = "Totals.user_rating_count >= " . $filter_review_count;

            // Listings with avg rating higher than

            $filter_avg_rating = Sanitize::getFloat($this->params['module'],'filter_avg_rating');

            $filter_avg_rating > 0 and $conditions[] = 'Totals.user_rating  >= ' . $filter_avg_rating;

            $this->Listing->group = array();

            // Exlude listings without ratings from the results, except for certain referrers (i.e. calendar)
            // Mar 12, 2017 - For calendar module, always load all listings. Do not filter for ratings, reviews

            $referrer = Sanitize::getString($this->params,'referrer','listings');

            $join_direction = $referrer == 'listings' && in_array($sort,array('rating','rrating','topratededitor','reviews')) ? 'INNER' : 'LEFT';

            $ListingModelName = get_class($this->Listing);

            $this->Listing->joins['Totals'] = "$join_direction JOIN #__jreviews_listing_totals AS Totals ON Totals.listing_id = Listing." . $ListingModelName::_LISTING_ID . " AND Totals.extension = " . $this->Quote($extension);

            # Modify query for correct ordering. Change FIELDS, ORDER BY and HAVING BY directly in Listing Model variables
            if($tokenMatch and $custom_order)
            {
                $this->Listing->order[] = $custom_order;
            }
            elseif(empty($order) && $extension == 'com_content' && !in_array($sort, $this->proximityModes))
            {
                // Mar 12, 2017 - For calendar module, always load all listings. Do not filter for ratings, reviews
                $this->Listing->processSorting($sort, $limitResults = ($referrer == 'listings')); // Modifies Listing model order var directly
            }
            elseif(empty($order) && $order = $this->__processSorting($sort, $currentListing))
            {
                $order = array($order);
            }

            $fields = array(
                'Totals.user_rating AS `Review.user_rating`',
                'Totals.user_rating_count AS `Review.user_rating_count`',
                'Totals.user_comment_count AS `Review.review_count`',
                'Totals.editor_rating AS `Review.editor_rating`',
                'Totals.editor_rating_count AS `Review.editor_rating_count`',
                'Totals.editor_comment_count AS `Review.editor_review_count`'
            );

            $queryData = array(
                'fields'=>!isset($this->Listing->fields['editor_rating']) ? $fields : array(),
                'joins'=>$joins,
                'conditions'=>$conditions,
                'having'=>$having
            );

            if(!empty($order) && in_array('noresults',$order)) {

                $listings = array();

                $count = 0;
            }
            else {

                isset($order) and !empty($order) and $queryData['order'] = $order;

                $listings = $this->ListingsRepository
                        ->addQueryData($queryData)
                        ->whereDirIdNotIn($excludeDirId)
                        ->limit($total);
                        // ->many(); // It runs the addCategoryFiltering method again

                $queryData = $this->ListingsRepository->getQueryData();

                $listings = $this->Listing->findAll($queryData);

                if(isset($this->GeomapsListings) && in_array($sort, $this->proximityModes) && $extension == 'com_content')
                {
                    $this->params['module']['listing_order'] = 'proximity';

                    $listings = $this->GeomapsListings->injectDistanceGroup($listings);
                }

                $count = count($listings);
            }

        } // end Listing class check
        else {

            $listings = array();

            $count = 0;
        }

        unset($this->Listing);

        # Send variables to view template
        $this->set(array(
                'autodetect_ids'=>$ids,
                'subclass'=>'listing',
                'listings'=>$listings,
                'compare'=>$compare,
                'total'=>$count,
                'limit'=>$limit
        ));

        $this->_completeModuleParamsArray();

        $page = $this->ajaxRequest && empty($listings) ? '' : $this->render('modules','listings');

        return $page;
    }

    /**
     * This code duplicates in large part the CategoriesController:search method
     * We should try to make it modular so we can re-use it
     */
    function processCustomParams($custom_params, & $cat_id, & $criteria_id, & $dir_id, & $order)
    {
        $urlSeparator = "_"; //Used for url parameters that pass something more than just a value

        $custom_params_array = array();

        $wheres = array();

        $query_type = 'all';

        $min_word_chars = 3; // Only words with min_word_chars or higher will be used in any|all query types

        // Jan 23, 2016 - On some sites the custom params attribute ends up encoded as entities

        $custom_params = html_entity_decode($custom_params,ENT_COMPAT,'utf-8');

        parse_str($custom_params, $custom_params_array);

        // Prevent data from proximity search from getting into the search conditionals because it was already
        // processed in the GeoMaps add-on

        $jr_lat = Sanitize::getString($this->Config,'geomaps.latitude');

        $jr_lon = Sanitize::getString($this->Config,'geomaps.longitude');

        $search_address_field = Sanitize::getString($this->Config,'geomaps.advsearch_input');

        if($jr_lat && $jr_lon && $search_address_field)
        {
            unset(
                $custom_params_array[$jr_lat]
                ,$custom_params_array[$jr_lon]
                ,$custom_params_array[$search_address_field]
                );
        }

        if(isset($custom_params_array['order']))
        {
            $order = $custom_params_array['order'];
        }

        $keywords = urldecode(Sanitize::getString($custom_params_array,'keywords'));

        $ignored_search_words = $keywords != '' ? cmsFramework::getIgnoredSearchWords() : array();

        $scope_params = Sanitize::getString($this->params,'scope','title_introtext_fulltext');

        // Transform scope into DB table columns

        $scope_terms = array_filter(explode($urlSeparator,$scope_params));

        foreach($scope_terms AS $key=>$term)
        {
            switch($term) {

                case 'title':
                case 'introtext':
                case 'fulltext':

                    $scope[$term] = $this->Listing->_SIMPLE_SEARCH_FIELDS[$term];

                    break;

                default:

                    unset($scope[$term]);

                    break;
            }
        }

        // Process keywords

       if($keywords != '' && !empty($scope))
        {
            $allowedContentFields = array('title','introtext','fulltext','reviews','metakey');

            // Only add meta keywords if the db column exists

            if(EverywhereComContentModel::_LISTING_METAKEY != '')
            {
                $scope['metakey'] = EverywhereComContentModel::_LISTING_METAKEY;
            }

            $words = array_unique(explode( ' ', $keywords));

            $whereFields = array();

            foreach($scope as $scope_key=>$contentfield)
            {
                if(in_array($scope_key,$allowedContentFields))
                {
                    $whereContentFields = array();

                    $whereReviewComment = array();

                    $whereReviewTitle = array();

                    foreach ($words as $word)
                    {
                        if(strlen($word) >= $min_word_chars && !in_array($word,$ignored_search_words))
                        {
                            if($contentfield == 'reviews')
                            {
                                $whereReviewComment[] = "Review.comments LIKE ".$this->QuoteLike($word);

                                $whereReviewTitle[] = "Review.title LIKE ".$this->QuoteLike($word);
                            }
                            else {

                                $whereContentFields[] = "$contentfield LIKE ".$this->QuoteLike($word);
                            }
                        }
                    }

                    if($contentfield == 'reviews')
                    {
                        if(!empty($whereReviewTitle))
                        {
                            $whereFields[] = "\n(" . implode( ($query_type == 'all' ? ') AND (' : ') OR ('), $whereReviewTitle ) . ")";
                        }

                        if(!empty($whereReviewComment))
                        {
                            $whereFields[] = "\n(" . implode( ($query_type == 'all' ? ') AND (' : ') OR ('), $whereReviewComment ) . ")";
                        }
                    }
                    elseif(!empty($whereContentFields))
                    {
                        $whereFields[] = "\n(" . implode( ($query_type == 'all' ? ') AND (' : ') OR ('), $whereContentFields ) . ")";
                    }

                }

                if(!empty($whereFields))
                {
                    $wheres[] = '(' . implode(  ') OR (', $whereFields ) . ')';
                }
            }
        }

        // Process ratings

        $user_rating_params = Sanitize::getVar($custom_params_array,S2_QVAR_RATING_AVG,0);

        $editor_rating_params = Sanitize::getVar($custom_params_array,S2_QVAR_EDITOR_RATING_AVG,0);

        if(!is_array($user_rating_params))
        {
            $user_rating_params = array($user_rating_params);
        }

        if(!is_array($editor_rating_params))
        {
            $editor_rating_params = array($editor_rating_params);
        }

        $user_rating_params = array_filter($user_rating_params);

        $editor_rating_params = array_filter($editor_rating_params);

       /****************************************************************************
        * First pass of url params to get all field names and then find their types
        ****************************************************************************/

        $fieldNameArray = array();

        $customFields = $this->Field->getFieldNames('listing',array('published'=>1));

        foreach($custom_params_array as $key=>$val)
        {
            if (substr($key,0,3)=="jr_" && in_array($key,$customFields) && !is_null($val) && $val != '') {
                $fieldNameArray[$key] = $val;
            }
        }

        // Find out the field type to determine whether it's an AND or OR search

        if(!empty($fieldNameArray))
        {
            $query = '
                SELECT
                    name, type
                FROM
                    #__jreviews_fields
                WHERE
                    name IN (' .$this->Quote(array_keys($fieldNameArray)) . ')'
                ;

            $fieldTypesArray = $this->Field->query($query, 'loadAssocList', 'name');
        }

        $OR_fields = array("select","radiobuttons"); // Single option

        $AND_fields = array("selectmultiple","checkboxes","relatedlisting"); // Multiple option

        foreach ($fieldNameArray AS $key=>$value)
        {
            $searchValues = explode($urlSeparator, $value);

            $fieldType = $fieldTypesArray[$key]['type'];

            // Process values with separator for multiple values or operators. The default separator is an underscore
            if (substr_count($value,$urlSeparator)) {

                // Check if it is a numeric or date value
                $allowedOperators = array("equal"=>'=',"higher"=>'>=',"lower"=>'<=', "between"=>'between');
                $operator = $searchValues[0];

                $isDate = false;
                if ($searchValues[1] == "date") {
                    $isDate = true;
                }

                if (in_array($operator,array_keys($allowedOperators)) && (is_numeric($searchValues[1]) || $isDate))
                {
                    if ($operator == "between")
                    {
                        if ($isDate)
                        {
                            @$searchValues[1] = low($searchValues[2]) == 'today' ? _TODAY : $searchValues[2];
                            @$searchValues[2] = low($searchValues[3]) == 'today' ? _TODAY : $searchValues[3];
                        }

                        $low = is_numeric($searchValues[1]) ? $searchValues[1] : $this->Quote($searchValues[1]);
                        $high = is_numeric($searchValues[2]) ? $searchValues[2] : $this->Quote($searchValues[2]);
                        $wheres[] = "\n".$key." BETWEEN " . $low . ' AND ' . $high;
                    }
                    else {
                        if ($searchValues[1] == "date") {
                            $searchValues[1] = low($searchValues[2]) == 'today' ? _TODAY : $searchValues[2];
                        }
                        $value = is_numeric($searchValues[1]) ? $searchValues[1] : $this->Quote($searchValues[1]);
                        $wheres[] = "\n".$key.$allowedOperators[$operator].$value;
                    }
                }
                else {
                    // This is a field with pre-defined options
                    $whereFields = array();

                    if(isset($tag) && $key = 'jr_'.$tag['field']) {
                        // Field value underscore fix
                        if(in_array($fieldType,$OR_fields)) {
                            $whereFields[] = " $key = '*".$this->Quote('*'.urldecode($value).'*');
                        }
                        else {
                            $whereFields[] = " $key LIKE ".$this->Quote('%*'.urldecode($value).'*%');
                        }
                    }
                    elseif(!empty($searchValues))
                    {
                        foreach ($searchValues as $value)
                        {
                            $searchValue = urldecode($value);
                            if(in_array($fieldType,$OR_fields)) {
                                $whereFields[] = " $key = ".$this->Quote('*'.$value.'*') ;
                            }
                            else {
                                $whereFields[] = " $key LIKE ".$this->Quote('%*'.$value.'*%');
                            }
                        }
                    }

                    if (in_array($fieldType,$OR_fields)) { // Single option field
                        $wheres[] = '(' . implode( ') OR (', $whereFields ) . ')';
                    } else { // Multiple option field
                        $wheres[] = '(' . implode( ') AND (', $whereFields ) . ')';
                    }
                }

            }
            else {

                $value = urldecode($value);

                $whereFields = array();

                switch($fieldType) {

                    case in_array($fieldType,$OR_fields):

                        $whereFields[] = " $key = ".$this->Quote('*'.$value.'*') ;

                    break;

                    case in_array($fieldType,$AND_fields):

                        $whereFields[] = " $key LIKE ".$this->Quote('%*'.$value.'*%');

                    break;

                    case 'decimal':

                        $whereFields[] = " $key = " . (float) $value;

                    break;

                    case 'integer':

                        $whereFields[] = " $key = " . (int) $value;

                    break;

                    case 'date':

                        $order = Sanitize::getString($this->params,'order');

                        $begin_week = date('Y-m-d', strtotime('monday last week'));

                        $end_week = date('Y-m-d', strtotime('monday last week +6 days')) . ' 23:59:59';

                        $begin_month = date('Y-m-d',mktime(0, 0, 0, date('m'), 1));

                        $end_month = date('Y-m-t', strtotime('this month')) . ' 23:59:59';

                        $lastseven = date('Y-m-d', strtotime('-1 week'));

                        $lastthirty = date('Y-m-d', strtotime('-1 month'));

                        $nextseven = date('Y-m-d', strtotime('+1 week')) . ' 23:59:59';

                        $nextthirty = date('Y-m-d', strtotime('+1 month')) . ' 23:59:59';

                        switch($value) {

                            case 'future':
                                $whereFields[] = " $key >= " . $this->Quote(_TODAY);
                                $order == '' and $this->Listing->order = array($key . ' ASC');
                            break;
                            case 'today':
                                $whereFields[] = " $key BETWEEN " . $this->Quote(_TODAY) . ' AND ' . $this->Quote(_END_OF_TODAY);
                                $order == '' and $this->Listing->order = array($key . ' ASC');
                            break;
                            case 'week':
                                $whereFields[] = " $key BETWEEN " . $this->Quote($begin_week) . ' AND ' . $this->Quote($end_week);
                                $order == '' and $this->Listing->order = array($key . ' ASC');
                            break;
                            case 'month':
                                $whereFields[] = " $key BETWEEN " . $this->Quote($begin_month) . ' AND ' . $this->Quote($end_month);
                                $order == '' and $this->Listing->order = array($key . ' ASC');
                            break;
                            case '7':
                            case '+7':
                                $whereFields[] = " $key BETWEEN " . $this->Quote(_TODAY) . ' AND ' . $this->Quote($nextseven);
                                $order == '' and $this->Listing->order = array($key . ' ASC');
                            break;
                            case '30':
                            case '+30':
                                $whereFields[] = " $key BETWEEN " . $this->Quote(_TODAY) . ' AND ' . $this->Quote($nextthirty);
                                $order == '' and $this->Listing->order = array($key . ' ASC');
                            break;
                            case '-7':
                                $whereFields[] = " $key BETWEEN " . $this->Quote($lastseven) . ' AND ' . $this->Quote(_END_OF_TODAY);
                                $order == '' and $this->Listing->order = array($key . ' DESC');
                            break;
                            case '-30':
                                $whereFields[] = " $key BETWEEN " . $this->Quote($lastthirty) . ' AND ' . $this->Quote(_END_OF_TODAY);
                                $order == '' and $this->Listing->order = array($key . ' DESC');
                            break;
                            default:
                                $whereFields[] = " $key = " . $this->Quote($value);
                            break;
                        }

                    break;

                    default:

                        if(isset($tag) && $key == 'jr_'.$tag['field'] && $fieldType == 'text')
                        {
                           $whereFields[] = " $key = " . $this->Quote($value);
                        }
                        else {

                           $whereFields[] = " $key LIKE " . $this->QuoteLike($value);
                        }

                    break;
                }

                $wheres[] = " (" . implode(  ') AND (', $whereFields ) . ")";
            }

        } // endforeach

        if(!empty($user_rating_params))
        {
            foreach($user_rating_params AS $user_rating)
            {
                $user_rating = explode(',',$user_rating);

                $user_rating_value = Sanitize::getInt($user_rating,0);

                if(($this->Config->rating_scale > 5 && $user_rating_value < 5) || ($user_rating_value > $this->Config->rating_scale))
                {
                    $user_rating_value = min(4,$user_rating_value) * ($this->Config->rating_scale/5);
                }

                if(count($user_rating) == 1 && $user_rating_value)
                {
                    $wheres[] = "Totals.user_rating >= " . $user_rating_value;
                }
                else {

                    $user_rating_criteria_id = Sanitize::getInt($user_rating,1);

                    if($user_rating_criteria_id)
                    {
                        $table_alias = 'ListingRatingUser' . $user_rating_criteria_id;

                        $this->Listing->joins[$table_alias] = "LEFT JOIN #__jreviews_listing_ratings AS ".$table_alias." ON ".$table_alias.".listing_id = Listing." . EverywhereComContentModel::_LISTING_ID . " AND ".$table_alias.".extension = 'com_content'";

                        $wheres[] = $table_alias . '.user_rating >= ' . $user_rating_value;

                        $wheres[] = $table_alias . '.criteria_id = ' . $user_rating_criteria_id;
                    }
                }
            }
        }

        if(!empty($editor_rating_params))
        {
            foreach($editor_rating_params AS $editor_rating)
            {
                $editor_rating = explode(',',$editor_rating);

                $editor_rating_value = Sanitize::getInt($editor_rating,0);

                if(($this->Config->rating_scale > 5 && $editor_rating_value < 5) || ($editor_rating_value > $this->Config->rating_scale))
                {
                    $editor_rating_value = min(4,$editor_rating_value) * ($this->Config->rating_scale/5);
                }

                if(count($editor_rating) == 1 && $editor_rating_value)
                {
                    $wheres[] = "Totals.editor_rating >= " . $editor_rating_value;
                }
                else {

                    $editor_rating_criteria_id = Sanitize::getInt($editor_rating,1);

                    if($editor_rating_criteria_id)
                    {
                        $table_alias = 'ListingRatingEditor' . $editor_rating_criteria_id;

                        $this->Listing->joins[$table_alias] = "LEFT JOIN #__jreviews_listing_ratings AS ".$table_alias." ON ".$table_alias.".listing_id = Listing." . EverywhereComContentModel::_LISTING_ID . " AND ".$table_alias.".extension = 'com_content'";

                        $wheres[] = $table_alias . '.editor_rating >= ' . $editor_rating_value;

                        $wheres[] = $table_alias . '.criteria_id = ' . $editor_rating_criteria_id;
                    }
                }
            }
        }

        $cat_id_param =  Sanitize::getString($custom_params_array,'cat');

        $criteria_id_param = Sanitize::getString($custom_params_array,'criteria');

        $dir_id_param = Sanitize::getString($custom_params_array,'dir');

        // Determine which categories to include in the queries
        if($cat_id_param)
        {
            $cat_id_param = str_replace($urlSeparator,',',$cat_id_param);

            $category_ids = explode(',',$cat_id_param);

            // Remove empty or nonpositive values from array
            if(!empty($category_ids))
            {
                foreach ($category_ids as $index => $value)
                {
                    if (empty($value) || $value < 1 || !is_numeric($value))
                    {
                        unset($category_ids[$index]);
                    }
                }
            }

            $category_ids = is_array($category_ids) ? implode (',',$category_ids) : $category_ids;

            $category_ids != '' and $cat_id = explode(',', $category_ids);
        }
        elseif($criteria_id_param) {

            $criteria_id_param = str_replace($urlSeparator,',',$criteria_id_param);

            $criteria_id_param != '' and $criteria_id = $criteria_id_param;
        }
        elseif($dir_id_param != '')
        {
            $dir_id_param = str_replace($urlSeparator,',',$dir_id_param);

            $dir_id_param != '' and $dir_id = explode(',', $dir_id_param);
        }

        # Add search conditions to Listing model

        if($wheres != '' ) {

            $this->Listing->conditions = array_merge($this->Listing->conditions,$wheres);
        }
    }

    /**
    * Ensures all required vars for theme rendering are in place, otherwise adds them with default values
    */

    function _completeModuleParamsArray()
    {
        $params = array(
            'show_numbers'=>false,
            'fields'=>'',
            'radius'=>'',
            'distance'=>1,
            'summary'=>false,
            'summary_words'=>10,
            'show_category'=>true,
            'user_rating'=>1,
            'editor_rating'=>1,
            'tn_mode'=>'crop',
            'tn_size'=>'100x100',
            'tn_show'=>1,
            'tn_position'=>'left',
            'columns'=>1,
            'orientation'=>'horizontal',
            'slideshow'=>false,
            'slideshow_interval'=>6,
            'nav_position'=>'bottom',
            'custom_link_position'=>'top-right',
            'custom_link_1_url'=>'',
            'custom_link_1_text'=>'',
            'custom_link_2_url'=>'',
            'custom_link_2_text'=>'',
            'custom_link_3_url'=>'',
            'custom_link_3_text'=>''
        );

        $this->params['module'] = array_merge($params, $this->params['module']);

        $this->params['module']['summary_words'] = $this->params['module']['summary_words'] ?: 10;

        $this->params['module']['tn_mode'] = $this->params['module']['tn_mode'] ?: 'crop';

        $this->params['module']['tn_show'] = (int) $this->params['module']['tn_show'] === 1 ?: 0;

        $this->params['module']['tn_size'] = $this->params['module']['tn_size'] ?: '100x100';

        $this->params['module']['columns'] = $this->params['module']['columns'] ?: 1;
    }

   /**
    * Modifies the query ORDER BY statement based on ordering parameters
    */
    private function __processSorting($selected, & $listing)
    {
        $order = '';

        switch ( $selected )
        {
            case 'rating':

                $order = 'Totals.user_rating_rank DESC';

                $this->Listing->conditions[] = 'Totals.user_rating > 0';
              break;

            case 'rrating':

                $order = 'Totals.user_rating_rank ASC';

                $this->Listing->conditions[] = 'Totals.user_rating > 0';
              break;

            case 'reviews':

                $order = 'Totals.user_comment_count DESC';

                $this->Listing->conditions[] = 'Totals.user_comment_count > 0';
              break;

            case 'rdate':

                $order =  $this->Listing->dateKey ? "Listing.{$this->Listing->dateKey} DESC" : false;

            break;

            case 'proximity': // Proximity to current listing
            case 'proximity_custom': // Proximity to custom center point
            case 'proximity_geotargeting':

                if (empty($this->proximityCenter))
                {
                    return 'noresults';
                }

                $latField = Sanitize::getString($this->Config,'geomaps.latitude');

                $lonField = Sanitize::getString($this->Config,'geomaps.longitude');

                $this->ListingsRepository->fields($this->GeomapsSearch->radiusSearchDistanceField($this->proximityCenter));

                // Jan 10, 2017 - Commented lines below because the radiusSearchQueryConditions method and the distance ordering
                // are already run in the processProximitySearch method below via GeomapsSearch::addProximitySearch

                // $radius = Sanitize::getInt($this->params['module'],'radius', 20);

                // $this->ListingsRepository->where($this->GeomapsSearch->radiusSearchQueryConditions($this->proximityCenter, $radius));

                // $this->ListingsRepository->order('`Geomaps.distance` DESC');

                $this->Listing->having[] = '`Geomaps.distance` >= 0';

            break;
        }

        return $order;
    }

    private function processProximitySearch(& $sort, & $currentListing)
    {
        $radius = Sanitize::getInt($this->params['module'],'radius', 20);

        if (!$radius) {
            $radius = 20;
        }

        switch ($sort)
        {
            // Proximity to custom center
            case 'proximity_custom':

                $lat = Sanitize::getFloat($this->params['module'],'custom_lat');

                $lng = Sanitize::getFloat($this->params['module'],'custom_lon');

                if ($lat != 0 && $lng != '')
                {
                    $this->proximityCenter = compact('lat', 'lng');
                }

                break;

            // Proximity to current listing
            case 'proximity':

                    $lat = Sanitize::getString($this->Config,'geomaps.latitude');

                    $lon = Sanitize::getString($this->Config,'geomaps.longitude');

                    // Listing Type Widgets

                    if ($this->ajaxRequest)
                    {
                        if($lat == '' || $lon == '')
                        {
                            return 'noresults';
                        }
                        else {

                            $lat_value = isset($currentListing['Field']['pairs'][$lat]) ? Sanitize::getVar($currentListing['Field']['pairs'][$lat]['value'],0) : 0;

                            $lon_value = isset($currentListing['Field']['pairs'][$lon]) ? Sanitize::getVar($currentListing['Field']['pairs'][$lon]['value'],0) : 0;

                            if(!$lat_value || !$lon_value) return 'noresults';

                            $this->proximityCenter = array('lat'=>$lat_value,'lng'=>$lon_value);
                        }
                    }

                    // Detail page

                    elseif(Sanitize::getString($this->params,'view') == 'article')
                    {
                        $curr_listing_id = Sanitize::getInt($this->params,'id');

                        if(!$this->ajaxRequest && $sort == 'proximity')
                        {
                            $lat = Sanitize::getString($this->Config,'geomaps.latitude');

                            $lon = Sanitize::getString($this->Config,'geomaps.longitude');

                            if($lat != '' && $lon != '') {

                                $query = "
                                    SELECT {$lat},{$lon} FROM #__jreviews_content WHERE contentid = {$curr_listing_id}
                                ";

                                $row = $this->Listing->query($query,'loadAssoc');

                                if($row[$lat] != '' && $row[$lon] != '') {

                                    $currentListing['Field']['pairs'][$lat]['value'][0] = $row[$lat];

                                    $currentListing['Field']['pairs'][$lon]['value'][0] = $row[$lon];

                                    $this->Listing->conditions[] = 'Listing.' . EverywhereComContentModel::_LISTING_ID . ' <> ' . $curr_listing_id;

                                    $this->proximityCenter = array('lat'=>$row[$lat],'lng'=>$row[$lon]);
                                }
                            }
                        }
                    }
                    // Non-detail page
                    elseif(!$this->ajaxRequest && $sort == 'proximity') {
                        $sort = 'rdate';
                    }

                break;

            // Proximimty to user's location
            case 'proximity_geotargeting':

                $ipAddress = s2GetIpAddress();

                if ($location = $this->GeomapsGeotargeting->getLocationByIP($ipAddress))
                {
                    if($location['center']['lat'] && $location['center']['lng'])
                    {
                        $this->proximityCenter = $location['center'];
                    }
                }

                break;
        }

        if (!empty($this->proximityCenter))
        {
            $request = array(
                'jr_radius' => $radius,
                'jr_latitude' => $this->proximityCenter['lat'],
                'jr_longitude' => $this->proximityCenter['lng']
            );

            $this->GeomapsSearch->addProximitySearch($request);
        }
    }

    private function __processListingTypeWidgets(&$conditions)
    {
        $extension = Sanitize::getString($this->params['module'],'extension');

        $extension = $extension != '' ? $extension : 'com_content';

        if($extension != 'com_content') return;

        $widget_type = Sanitize::getString($this->params,'type');

        $key  = Sanitize::getInt($this->params,'key');

        $listing_id = Sanitize::getInt($this->params,'listing_id');

        $listingModel = clone($this->Listing);

        unset($this->Listing->joins['ParentCategory']);

        # Process Listing Type Related Listings settings

        $listing = $this->Listing->findRow(array('conditions'=>array('Listing.' . EverywhereComContentModel::_LISTING_ID . ' = ' . $listing_id)));

        $this->Listing = $listingModel;

        unset($listingModel);

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

    private function __relatedlistings(&$listing, &$settings, &$conditions)
    {
        $match = Sanitize::getString($settings,'match');

        $curr_fname = Sanitize::getString($settings,'curr_fname');

        $match_fname = Sanitize::getString($settings,'match_fname');

        $created_by = $listing['User']['user_id'];

        $listing_id = $listing['Listing']['listing_id'];

        $cat_id = $listing['Category']['cat_id'];

        $criteria_id = $listing['Criteria']['criteria_id'];

        $title = $listing['Listing']['title'];

        switch($match)
        {
            case 'id':

                // Specified field matches the current listing id
                if($curr_fname != '')
                {
                    $conditions[] = "`Field`.{$curr_fname} LIKE " . $this->QuoteLike('*'.$listing_id.'*');

                    $conditions[] = 'Listing.' . EverywhereComContentModel::_LISTING_ID . ' <> ' . $listing_id;
                }
                else {

                    $this->abort = true;
                }

                break;

            case 'about':

                // Specified field matches the current listing id
                if($curr_fname != '' && ($field = Sanitize::getVar($listing['Field']['pairs'],$curr_fname)))
                {
                    $value = $field['type'] == 'relatedlisting' ? $field['real_value'] : $field['value'];

                    $conditions[] = "Listing." . EverywhereComContentModel::_LISTING_ID . " IN (" . cleanIntegerCommaList($value) . ')';
                }
                else {

                    $this->abort = true;
                }

                break;

            case 'field':

                // Specified field matches the current listing field of the same name
                $field_conditions = array();

                if($curr_fname != '' && ($field = Sanitize::getVar($listing['Field']['pairs'],$curr_fname)))
                {
                    foreach($field['value'] AS $key=>$value) {

                        if(in_array($field['type'],array('selectmultiple','checkboxes'))) {

                            $field_conditions[] = "`Field`.{$curr_fname} LIKE " . $this->QuoteLike('*'.$value.'*');
                        }
                        elseif(in_array($field['type'],array('select','radiobuttons'))) {

                            $field_conditions[] = "`Field`.{$curr_fname} = " . $this->Quote('*'.$value.'*');
                        }
                        elseif($field['type'] == 'relatedlisting') {

                            $value = '*' . $field['real_value'][$key] . '*';

                            $field_conditions[] = "`Field`.{$curr_fname} LIKE " . $this->QuoteLike($value);
                        }
                        else {

                            $field_conditions[] = "`Field`.{$curr_fname} = " . $this->Quote($value);
                        }
                    }

                    !empty($field_conditions) and $conditions[] = '(' . implode(' OR ', $field_conditions). ')';

                    $conditions[] = 'Listing.' . EverywhereComContentModel::_LISTING_ID . ' <> ' . $listing_id;
                }
                else {

                    $this->abort = true;
                }

                break;

            case 'diff_field':

                // Specified field matches a different field in the current listing
                $curr_listing_fname = $match_fname;

                $search_listing_fname = $curr_fname;

                $field_conditions = array();

                if($curr_listing_fname != '' && $search_listing_fname != '' && ($curr_field = Sanitize::getVar($listing['Field']['pairs'],$curr_listing_fname))) {

                    if(!($search_field = Sanitize::getVar($listing['Field']['pairs'],$search_listing_fname))) {

                        // Need to query the field type

                        $query = "
                            SELECT
                                fieldid AS field_id, type
                            FROM
                                #__jreviews_fields
                            WHERE
                                name = " . $this->Quote($search_listing_fname);

                        $search_field = $this->Field->query($query, 'loadAssoc');
                    }

                    foreach($curr_field['value'] AS $key=>$value)
                    {
                        if(in_array($search_field['type'],array('selectmultiple','checkboxes'))) {

                            $field_conditions[] = "`Field`.{$search_listing_fname} LIKE " . $this->QuoteLike('*'.$value.'*');
                        }
                        elseif(in_array($search_field['type'],array('select','radiobuttons'))) {

                            $field_conditions[] = "`Field`.{$search_listing_fname} = " . $this->Quote('*'.$value.'*');
                        }
                        elseif($search_field['type'] == 'relatedlisting' && $curr_field['type'] == 'relatedlisting') {

                            $value = $curr_field['real_value'][$key];

                            $field_conditions[] = "`Field`.{$search_listing_fname} LIKE '%*" . $value . "*%'";
                        }
                        elseif($search_field['type'] == 'relatedlisting' && in_array($curr_field['type'],array('selectmultiple','checkboxes','select','radiobuttons'))) {

                            $field_conditions[] = "`Field`.{$search_listing_fname} = " . (int) $value;
                        }
                        else {
                            $field_conditions[] = "`Field`.{$search_listing_fname} = " . $this->Quote($value);
                        }
                    }

                    !empty($field_conditions) and $conditions[] = '(' . implode(' OR ', $field_conditions). ')';

                    $conditions[] = 'Listing.' . EverywhereComContentModel::_LISTING_ID . ' <> ' . $listing_id;

                }
                else {

                    $this->abort = true;
                }

                break;

            case 'title':

                // Specified field matches the current listing title
                if($curr_fname != '') {

                    // Need to find out the field type. First check if the field exists for this listing type
                    if(!($field = Sanitize::getVar($listing['Field']['pairs'],$curr_fname))) {

                        // Need to query the field type

                        $query = "SELECT fieldid AS field_id,type FROM #__jreviews_fields WHERE name = " . $this->Quote($curr_fname);

                        $field = $this->Listing->query($query,'loadAssocList');

                        $field = array_shift($field);
                    }

                    switch($field['type'])
                    {
                        case 'relatedlisting':

                            $this->abort = true;
                        break;

                        case 'text':

                            $conditions[] = "`Field`.{$curr_fname} = " . $this->Quote($title);
                        break;

                        case 'select':
                        case 'selectmultiple':
                        case 'radiobuttons':
                        case 'checkboxes':

                            # Need to find the option value using the option text
                            $query = "
                                SELECT
                                    value
                                FROM
                                    #__jreviews_fieldoptions
                                WHERE
                                    fieldid = " . (int) $field['field_id'] . "
                                    AND
                                    text = " . $this->Quote($title);

                           $value = $this->Listing->query($query,'loadResult');

                           if($value != '') {

                                if(in_array($field['type'],array('select','radiobuttons'))) {

                                    $conditions[] = "`Field`.{$curr_fname} = " . $this->Quote('*'.$value.'*');
                                }
                                else {

                                    $conditions[] = "`Field`.{$curr_fname} LIKE " . $this->QuoteLike('*'.$value.'*');
                                }
                           }
                           else {

                               $this->abort = true;
                           }
                        break;
                    }

                    $conditions[] = 'Listing.' . EverywhereComContentModel::_LISTING_ID . ' <> ' . $listing_id;
                }

                break;

            case 'owner':

                // The listing owner matches the current listing owner

                $conditions[] = 'Listing.' . EverywhereComContentModel::_LISTING_USER_ID . ' = ' . $created_by;

                $conditions[] = 'Listing.' . EverywhereComContentModel::_LISTING_ID . ' <> ' . $listing_id;

                break;

            case 'listing_type':

                // Only filters by listing type

                $conditions[] = 'Listing.' . EverywhereComContentModel::_LISTING_ID . ' <> ' . $listing_id;

                break;

            case 'cat_auto':

                // Listing category matches the current listing category

                $this->Listing->addCategoryFiltering($conditions, $this->Access, array('cat_id'=>$cat_id, 'children'=>true));

                $conditions[] = 'Listing.' . EverywhereComContentModel::_LISTING_ID . ' <> ' . $listing_id;

                break;
        }
    }
}
