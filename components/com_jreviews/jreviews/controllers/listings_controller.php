<?php
/**
 * JReviews - Reviews Extension
 * Copyright (C) 2010-2015 ClickFWD LLC
 * This is not free software, do not distribute it.
 * For licencing information visit https://www.jreviews.com
 * or contact sales@jreviews.com
**/

defined( 'MVC_FRAMEWORK') or die( 'Direct Access to this location is not allowed.' );

class ListingsController extends MyController {

    var $uses = array('article','user','menu','claim','category','jreviews_category','review','favorite','field','field_option','criteria','vote','media');

    var $helpers = array('routes','libraries','html','text','assets','form','time','jreviews','community','editor','custom_fields','rating','paginator','widgets','media');

    var $components = array('config','access','everywhere','media_storage','categories_repository');

    var $formTokenKeys = array('id'=>'listing_id');

    var $formTokenKeysReviews = array('id'=>'review_id','pid'=>'listing_id','mode'=>'extension','criteria_id'=>'criteria_id');

    var $autoRender = false; //Output is returned

    var $autoLayout = true;

    function beforeFilter()
    {
        $this->Access->init($this->Config);

        # Call beforeFilter of MyController parent class
        parent::beforeFilter();

        # Make configuration available in models
        $this->Listing->Config = &$this->Config;
    }

    function getPluginModel() {
        return $this->Listing;
    }

    function getNotifyModel() {
        return $this->Listing;
    }

    function getEverywhereModel() {
        // Completes the review with listing info for each Everywhere component
        return $this->Review;
    }

    // Need to return object by reference for PHP4
/*    function &getObserverModel()
    {
        return $this->Listing;
    }    */

    function detail()
    {
        $this->viewVarsAssets = array('listing');

        $keywords = Sanitize::getString($this->params,'keywords');

        $rating = Sanitize::getInt($this->params,S2_QVAR_RATING_AVG);

        $this->autoRender = false;

        $this->layout = 'detail_reviews';

        # Initialize vars

        $review_fields = array();

        $ratingCriteriaOrderArray = array();

        $menu_id = Sanitize::getInt($this->params,'Itemid');

        $listing_id = Sanitize::getInt($this->params,'id');

        $extension = Sanitize::getString($this->params,'extension');

        if($extension == '' && $menu_id) {

            $menuParams = $this->Menu->getMenuParams($menu_id);

            $extension = Sanitize::getString($menuParams,'extension');
        }

        $extension == '' and $extension = 'com_content';

        $searchOptionsArray = array();

        $listing = $this->Listing->findRow(array('conditions'=>array("Listing.{$this->Listing->realKey} = ". $listing_id)));

        # Override global configuration
        isset($listing['ListingType']) and $this->Config->override($listing['ListingType']['config']);

        $this->params['default_order'] = $this->Config->user_review_order;

        $sort = Sanitize::getString($this->params,'order');

        $user_id = Sanitize::getInt($this->params,'user');

        // generate canonical tag for urls with order param
        $canonical = $sort != '' ? true : false;

        $sort == '' and $sort = $this->Config->user_review_order;

        $this->params['order'] = $sort;

        if(!$listing || empty($listing))
        {
            echo cmsFramework::noAccess();
            $this->autoRender = false;
            return;
        }

        // Make sure variables are set

        $listing['Listing']['summary'] = Sanitize::getString($listing['Listing'],'summary');

        $listing['Listing']['description'] = Sanitize::getString($listing['Listing'],'description');

        $listing['Listing']['metakey'] = Sanitize::getString($listing['Listing'],'metakey');

        $listing['Listing']['metadesc'] = Sanitize::getString($listing['Listing'],'metadesc');

        $listing['Listing']['text'] = $listing['Listing']['summary'] . $listing['Listing']['description'];

        $regex = '/{.*}/';

        $listing['Listing']['text'] = preg_replace( $regex, '', $listing['Listing']['text'] );

        # Get user review data or editor reviews data in multiple editor review mode

        $reviewType = (int) ( $this->Config->author_review && $extension == 'com_content' && Sanitize::getString($this->params,'reviewType','user') == 'editor' );

        if($extension != 'com_content' || $this->Config->user_reviews || $reviewType )
        {
            $conditions = array(
                'Review.pid = '. $listing['Listing']['listing_id'],
                'Review.mode = "'.$extension.'"',
                'Review.published = 1',
                'Review.author = '.$reviewType
            );

            if($user_id && $user_id == $this->_user->id)
            {
                $conditions[] = 'Review.userid = ' . $user_id;
            }
            else {
                unset($this->params['user']);
            }

            $this->limit = Sanitize::getInt($this->data,'limit_special',$this->Config->user_limit);

            $order[] = $this->Review->processSorting($sort);

            $queryData = array(
                'conditions'=>$conditions,
                'offset'=>$this->offset,
                'limit'=>$this->limit,
                'order'=>$order,
                'joins'=>array()
            );

            // Review search

            if($keywords != '')
            {
                $this->_processReviewSearch($queryData);

                $searchOptionsArray = array('keywords'=>array('name'=>'keywords','text'=>$keywords,'value'=>$keywords));
            }

            // Click2search custom fields

            $wheres = array();

            $urlParams = Sanitize::getVar($this->params,'url');

            unset($urlParams['tmpl'],$urlParams['Itemid'],$urlParams['option'],$urlParams['keywords'],$urlParams['url']);

            if($urlParams)
            {
                $fieldNameArray = array();

                $customFields = $this->Field->getFieldNames('review',array('published'=>1));

                foreach($urlParams AS $fname=>$val)
                {
                    if(in_array($fname, $customFields))
                    {
                        $fieldNameArray[$fname] = $val;
                    }
                }

                if(!empty($fieldNameArray))
                {
                    $query = '
                        SELECT
                            fieldid, name, title, type
                        FROM
                            #__jreviews_fields
                        WHERE
                            name IN (' .$this->Quote(array_keys($fieldNameArray)) . ')'
                        ;

                    $fieldTypesArray = $this->Field->query($query, 'loadAssocList', 'name');

                    $this->Field->buildSearchOptionsArray($fieldNameArray, $fieldTypesArray, $searchOptionsArray);

                    $OR_fields = array("select","radiobuttons"); // Single option

                    $AND_fields = array("selectmultiple","checkboxes","relatedlisting"); // Multiple option

                    $whereFields = array();

                    foreach ($fieldNameArray AS $fname=>$value)
                    {
                        $fieldType = $fieldTypesArray[$fname]['type'];

                        $value = urldecode($value);

                        switch($fieldType) {

                            case in_array($fieldType,$OR_fields):

                                $whereFields[] = "ReviewField." . $fname . " = ".$this->Quote('*'.$value.'*') ;

                            break;

                            case in_array($fieldType,$AND_fields):

                                $whereFields[] = "ReviewField." . $fname . " LIKE ".$this->Quote('%*'.$value.'*%');

                            break;

                            case 'decimal':

                                $whereFields[] = "ReviewField." . $fname . " = " . (float) $value;

                            break;

                            case 'integer':

                                $whereFields[] = "ReviewField." . $fname . " = " . (int) $value;

                            break;

                            case 'date':

                                // $whereFields[] = " $key = " . $this->Quote($value);

                            break;

                            default:

                                $whereFields[] = "ReviewField." . $fname . " LIKE " . $this->QuoteLike($value);

                            break;
                        }
                    }

                    if(!empty($whereFields))
                    {
                        $queryData['conditions'][] = '(' . implode(  ') OR (', $whereFields ) . ')';

                        $queryData['joins']['ReviewField'] = 'LEFT JOIN #__jreviews_review_fields AS ReviewField ON ReviewField.reviewid = Review.id';
                    }
                }
            }

            if($rating > 0)
            {
                $this->Review->processRatingRangeSearch($rating, $queryData['conditions']);
            }

            $review_count = $this->Review->findCount($queryData);

            $reviews = $this->Review->findAll($queryData);

            foreach($reviews AS $key=>$review)
            {
                $reviews[$key]['Listing'] = & $listing['Listing'];

                $reviews[$key]['Category'] = & $listing['Category'];
            }
        }

        # Get custom fields for review form if form is shown on page

        $review_fields = $this->Field->getFieldsArrayNew($listing['Criteria']['criteria_id'], 'review');

        # Initialize review array and set Criteria and extension keys

        $review = $this->Review->init();

        $review['Criteria'] = $listing['Criteria'];

        $review['Review']['extension'] = $extension;

        # Get current listing review count for logged in user

        $review_type = Sanitize::getString($this->params,'reviewType','user');

        $listing['User']['user_review_count'] = $listing['User']['editor_review_count'] = 0;

        if($review_type == 'user')
        {
            $listing['User']['user_review_count'] = $this->_user->id == 0 ? 0 : $this->Review->findCount(array(
                    'conditions'=>array(
                        'Review.pid = '.$listing_id,
                        "Review.mode = " . $this->Quote($listing['Listing']['extension']),
                        "Review.published >= 0",
                        "Review.author = 0",
                        "Review.userid = " . (int) $this->_user->id
                    )));
        }
        else {

            $listing['User']['editor_review_count'] = $this->_user->id == 0 ? 0 : $this->Review->findCount(array(
                    'conditions'=>array(
                        'Review.pid = '.$listing_id,
                        "Review.mode = " . $this->Quote($listing['Listing']['extension']),
                        "Review.published >= 0",
                        "Review.author = 1",
                        "Review.userid = " . (int) $this->_user->id
                    )));
        }

        $listing['User']['duplicate_review'] = 0;

        // Override page keys with listing details

        $page = array();

        $page['title_seo']  = $listing['Listing']['title'];

        $page['keywords']  = $listing['Listing']['metakey'];

        $page['description'] = $listing['Listing']['metadesc'];

        if($review_type == 'user') {

            $page['title_seo'] = sprintf(JreviewsLocale::getPHP('LISTING_USER_REVIEWS_TITLE_SEO'),$page['title_seo']);
        }
        elseif($review_type == 'editor') {

            $page['title_seo'] = sprintf(JreviewsLocale::getPHP('LISTING_EDITOR_REVIEWS_TITLE_SEO'),$page['title_seo']);
        }

        /**
         * Generate canonical tag for urls with order param
         */
        $RoutesHelper = ClassRegistry::getClass('RoutesHelper');

        $RoutesHelper->name = $this->name;

        $RoutesHelper->action = $this->action;

        $RoutesHelper->params = $this->params;

        if(Sanitize::getBool($this->Config,'viewallreviews_canonical')) {

            $page['canonical'] = $RoutesHelper->content('',$listing,array('return_url'=>true));
        }
        elseif($canonical) {

            $page['canonical'] = cmsFramework::getCurrentUrl(array('order','listview','tmpl_suffix'));
        }

        $this->set(array(
                'extension'=>$extension,
                'User'=>$this->_user,
                'listing'=>$listing,
                'reviews'=>$review_type == 'user' ? $reviews : array(),
                'editor_review'=>$review_type == 'editor' ? $reviews : array(),
                'review_fields'=>$review_fields,
                'review'=>$review,
                'page'=>$page,
                'pagination'=>array(
                    'total'=>$review_count,
                    'ajax'=>Sanitize::getInt($this->Config, 'paginator_ajax', 0)
                ),
                'ratingCriteriaOrderArray'=>$listing['CriteriaRating'],
                'formTokenKeys'=>$this->formTokenKeysReviews,
                'searchOptionsArray'=>$searchOptionsArray
            )
        );

        return $this->render('listings','detail');
    }

    function _processReviewSearch(& $queryData)
    {
        $min_word_chars = 3; // Only words with min_word_chars or higher will be used in any|all query types

        $accepted_query_types = array ('any','all','exact');

        $simplesearch_custom_fields = 1;

        $scope = $this->Review->_SIMPLE_SEARCH_FIELDS;

        $simplesearch_query_type = 'all'; // any|all

        $keywords = urldecode(Sanitize::getString($this->params,'keywords'));

        $ignored_search_words = $keywords != '' ? cmsFramework::getIgnoredSearchWords() : array();

        $words = array_unique(explode( ' ', $keywords));

        // Include custom fields

        if($simplesearch_custom_fields == 1)
        {
            $fields = $this->Field->getTextBasedFieldNames('review');

            // Add the 'Field.' column alias so it's used in the query

            if(!empty($fields))
            {
                array_walk($fields, function(&$item) {
                    $item = 'ReviewField.' . $item;
                });
            }

            // TODO: find out which fields have predefined selection values to get the searchable values instead of reference

            // Merge standard fields with custom fields
            $scope = array_merge($scope, $fields);
        }

        $whereFields = array();

        foreach ($words as $word)
        {
            $whereContentFields = array();

            if(strlen($word) >= $min_word_chars && !in_array($word,$ignored_search_words))
            {
                $word = urldecode(trim($word));

                foreach($scope as $review_field)
                {
                    $whereContentFields[] = $review_field . " LIKE " . $this->QuoteLike($word);
                }

                if(!empty($whereContentFields)){

                    $whereFields[] = " (" . implode(') OR (', $whereContentFields ) . ')';
                }
            }
        }

        if(!empty($whereFields))
        {
            $queryData['conditions'][] = " (" . implode(  ($simplesearch_query_type == 'all' ? ') AND (' : ') OR ('), $whereFields ) . ')';

            $queryData['joins']['ReviewField'] = "LEFT JOIN #__jreviews_review_fields AS ReviewField ON Review.id = ReviewField.reviewid";
        }
    }

    function create()
    {
        $this->captcha = true;

        $this->autoRender = false;

        $dir_id = Sanitize::getInt($this->params,'dir');

        $cat_id = Sanitize::getString($this->params,'cat');

        $content_id = null;

        $option = 'com_content';

        $categories = array();

        if($cat_id > 0)
        {
            $category = $this->Category->findRow(
                array(
                    'conditions'=>array('Category.' . CategoryModel::_CATEGORY_ID . ' = ' . $cat_id)
                )
            );

            // Override global configuration
            isset($category['ListingType']) and $this->Config->override($category['ListingType']['config']);
        }

        if (!$this->Access->canAddListing()) {

            $this->autoRender = false;

            if($this->_user->id > 0)
            {
                return cmsFramework::noAccess(true);
            }

            $this->layout = 'page';

            $this->autoLayout = true;

            $this->set('access_submit', false);

            return $this->render('elements','login');
        }

        if($cat_id)
        {
            // Find parent categories of pre-selected cat to show the correct category select lists in the form
            $parent_categories = $this->Category->findParents($cat_id);

            foreach($parent_categories AS $key=>$row)
            {
                $categories[$key] = $this->Category->getCategoryList(array(
                    'disabled'=>false,
                    'indent'=>false,
                    'level'=>$row['Category']['level'],
                    'cat_id'=>$row['Category']['cat_id'],
                    'dir_id'=>$dir_id,
                    'listing_type'=>true
                ));
            }

            $tree = $this->Category->getCategoryList(array(
                'disabled'=>false,
                'indent'=>false,
                'cat_id'=>$row['Category']['cat_id'],
                'dir_id'=>$dir_id,
                'listing_type'=>true
            ));

            # Set the theme suffix
            $this->Theming->setSuffix(array('cat_id'=>$cat_id));
        }
        else
        {
            $categories = $this->Category->getCategoryList(array(
                'level'=>1,
                'disabled'=>false,
                'dir_id'=>$dir_id,
                'listing_type'=>true
            ));

            $tree = $this->Category->getCategoryList(array(
                'disabled'=>false,
                'dir_id'=>$dir_id,
                'listing_type'=>true
            ));
        }

        /*
        * Aug 26, 2016 - Added submit listing access check for subcategories of parent categories
        * in order to hide options from the list when there isn't a path in that option that will allow the user
        * to submit a listing
         */

        if (count($tree) > 1)
        {
            $nodes = array();

            $first = current($tree);

            CategoryModel::makeParentChildRelations($tree, $nodes);
        }
        else {
            $nodes = $tree;
        }

        $deepAccess = array();

        foreach ($nodes AS $parent)
        {
            $parent = (array) $parent;

            if ($parent['criteriaid'] == 0)
            {
                $deepAccess[$parent['value']] = array();

                if (isset($parent['children']))
                {
                    $this->CategoriesRepository->parentHasListingSubmitAccess($deepAccess, $parent['value'], $parent['children']);
                }
            }
            else {
                $deepAccess[$parent['value']] = array($parent['value']);
            }
        }

        if(!empty($categories))
        {
            // Remove categories without submit access
            foreach($categories AS $key => $subcategories)
            {
                if(is_array($subcategories))
                {
                    foreach($subcategories AS $subkey=>$row)
                    {
                        $overrides = !is_array($row->config) ? json_decode($row->config,true) : $row->config;

                        $catDeepAccess = Sanitize::getVar($deepAccess, $row->value, array());

                        if (isset($deepAccess[$subkey])
                            &&
                                (
                                    !$this->Access->canAddListing(Sanitize::getVar($overrides,'addnewaccess'))
                                    ||
                                    empty($catDeepAccess)
                                )
                            ) {

                            unset($categories[$key][$subkey]);
                        }

                        else {
                            $categories[$key][$subkey] = (array) $row;
                        }
                    }
                }
                else {

                    $overrides = !is_array($subcategories->config) ? json_decode($subcategories->config,true) : $subcategories->config;

                    $catDeepAccess = Sanitize::getVar($deepAccess, $subcategories->value, array());

                    if(!$this->Access->canAddListing(Sanitize::getVar($overrides,'addnewaccess'))
                            || empty($catDeepAccess)
                        ) {

                        unset($categories[$key]);
                    }
                    else {
                            $categories[$key] = (array) $categories[$key];
                    }
                }
            }
        }

        if (count($categories) == 1)
        {
            reset($categories);
            $cat_id = key($categories);
        }

        $this->set(
            array(
                'menu_id'=>$this->Menu->get($this->app.'_public'), // Public JReviews menu to be used in submit form action
                'submit_step'=>array(1),
                'access_submit'=>true,
                'User'=>$this->_user,
                'categories'=>$categories,
                'listing'=>array('Listing'=>array(
                        'listing_id'=>null,
                        'cat_id'=>$cat_id ? $cat_id : null,
                        'title'=>'',
                        'summary'=>'',
                        'description'=>'',
                        'metakey'=>'',
                        'metadesc'=>''
                    ))
            )
        );

        return $this->render('listings','create');
    }

    function edit()
    {
        $this->autoRender = false;

        $listing_id = Sanitize::getInt($this->params,'id');

        $categories = array();

        Configure::write('ListingEdit',true); // Read in Fields model for PaidListings integration

        $this->Listing->addStopAfterFindModel(array('Community','Media','Favorite'));

        $listing = $this->Listing->findRow(
            array(
                'conditions'=>array('Listing.' . EverywhereComContentModel::_LISTING_ID . ' = ' . $listing_id)
            )
        );

        # Override global configuration
        isset($listing['ListingType']) and $this->Config->override($listing['ListingType']['config']);

        // Clear listing expiration if value is not set
        if($listing['Listing']['publish_down'] == NULL_DATE) {

            $listing['Listing']['publish_down'] = '';
        }

        # Set the theme suffix
        $this->Theming->setSuffix(array('cat_id'=>$listing['Category']['cat_id']));

        if (!$this->Access->canEditListing($listing['Listing']['user_id'])) {

            return cmsFramework::raiseError(404, __t("Page not found",true));
        }

        # Get listing custom fields
        $listing_fields = $this->Field->getFieldsArrayNew($listing['Criteria']['criteria_id'], 'listing', $listing);

        // Show category lists if user is editor or above.
        if ($this->Access->isEditor() && Sanitize::getInt($listing['Criteria'],'criteria_id'))
        {
            $categories = $this->Category->getCategoryList(array(
                'disabled'=>true,
                'type_id'=>array(0,$listing['Criteria']['criteria_id']),
                'listing_type'=>true
                ,'dir_id'=>$listing['Directory']['dir_id'] // Shows only categories from the same directory
            ));

            if(!empty($categories))
            {
                // Remove categories without submit access, but leave the current listing cat id

                foreach($categories AS $key => $row)
                {
                    $overrides = !is_array($row->config) ? json_decode($row->config,true) : $row->config;

                    if($row->value != $listing['Category']['cat_id'] && $row->criteriaid > 0 && !$this->Access->canAddListing($overrides['addnewaccess']))
                    {
                        unset($categories[$key]);
                    }
                }
            }
        }

        // Needed to preserve line breaks when not using wysiwyg editor

        if(!$this->Access->loadWysiwygEditor())
        {
            $listing['Listing']['summary'] = $listing['Listing']['summary'];

            $listing['Listing']['description'] = $listing['Listing']['description'];
        }

        $this->set(
            array(
                'submit_step'=>array(1,2),
                'User'=>$this->_user,
                'listing'=>$listing,
                'categories'=>$categories,
                'listing_fields'=>$listing_fields,
                'formTokenKeys'=>$this->formTokenKeys
            )
        );

        return $this->render('listings','create');
    }

    function _getList()
    {
        $limit = Sanitize::getInt($this->params,'limit',15);

        $search = $this->Listing->makeSafe(mb_strtolower(Sanitize::getString($this->params,'search'),'utf-8'));

        $id = Sanitize::getInt($this->params,'id');

        $dirId = Sanitize::getInt($this->params,'dir');

        $typeId = Sanitize::getInt($this->params,'type');

        $catId = Sanitize::getInt($this->params,'cat');

        if (!$id && !$search) return '[]';

        if($id) {

            $conditions = array('Listing.' . EverywhereComContentModel::_LISTING_ID . ' = ' . $id);
        }
        else {

            $conditions = array('Listing.' . EverywhereComContentModel::_LISTING_TITLE . ' LIKE ' . $this->QuoteLike($search));
        }

        $this->Listing->addStopAfterFindModel(array('Field','Media','Favorite','PaidOrder'));

        $this->Listing->addCategoryFiltering($conditions, $this->Access, array('state'=>1, 'criteria_id' => $typeId, 'cat_id' => $catId, 'dir_id' => $dirId));

        $this->Listing->addListingFiltering($conditions, $this->Access, array('state'=>1));

        $listings = $this->Listing->findAll(array(
                'conditions'=>$conditions,
                'limit'=>$limit
            ),array('afterFind'));

        $results = array();

        foreach($listings AS $key=>$listing) {

            extract($listing['Listing']);

            $sefurl = cmsFramework::makeAbsUrl($listing['Listing']['url'],array('sef'=>true));

            if (!$catId)
            {
                $selectLabel = sprintf('%s • %s', $title, $listing['Category']['title']);
            }
            else {

                $selectLabel = $title;
            }

            $results[] = array(
                'id'=>$listing_id,
                'value'=>$selectLabel,
                'title'=>$title,
                'alias'=>$slug,'url'=>$sefurl,
                'category'=>$listing['Category']['title'],
                'user_review_count'=>(int) $listing['Review']['user_rating_count'],
                'editor_review_count'=>(int) $listing['Review']['editor_rating_count']
            );
        }

        return cmsFramework::jsonResponse($results);
    }

    function _favoritesAdd()
    {
        $response = array('success'=>false,'str'=>array());

        if(!$this->_user->id) {

            $response['str'][] = 'ACCESS_DENIED';

            return cmsFramework::jsonResponse($response);
        }

        $listing_id = Sanitize::getInt($this->data,'listing_id');

        $user_id = (int) $this->_user->id;

        // Force plugin loading on Review model
        $this->_initPlugins('Favorite');

        $this->Favorite->data = $this->data;

        // Get favored count
        $favored = $this->Favorite->getCount($listing_id);

        // Insert new and update display
        if ($this->Favorite->add($listing_id,$user_id) > 0)
        {
            $favored++;

            $response['success'] = true;

            $response['count'] = $favored;

            return cmsFramework::jsonResponse($response);
        }

        $response['str'][] = 'DB_ERROR';

        return cmsFramework::jsonResponse($response);
    }

    function _favoritesDelete()
    {
        $response = array('success'=>false,'str'=>array());

        if(!$this->_user->id) {

            $response['str'][] = 'ACCESS_DENIED';

            return cmsFramework::jsonResponse($response);
        }

        $listing_id = Sanitize::getInt($this->data,'listing_id');

        $user_id = $this->_user->id;

        // Get favored count
        $favored = $this->Favorite->getCount($listing_id);

        if ($favored > 0)
        {
            // Force plugin loading on Review model
            $this->_initPlugins('Favorite');

            $this->Favorite->data = $this->data;

            // Delete favorite
            $deleted = $this->Favorite->remove($listing_id, $user_id);

            if($deleted)
            {
                $favored--;

                $response['success'] = true;

                $response['count'] = $favored;

                return cmsFramework::jsonResponse($response);
            }

        }

        $response['str'][] = 'DB_ERROR';

        return cmsFramework::jsonResponse($response);
    }

    function _feature()
    {
        $response = array('success'=>false,'str'=>array());

        $listing_id = $this->data['Listing']['id'] = Sanitize::getInt($this->params,'id');

        # Stop form data tampering
        $formToken = cmsFramework::getCustomToken($listing_id);

        if(!$listing_id || !$this->__validateToken($formToken))
        {
            $response['str'][] = 'ACCESS_DENIED';

            return cmsFramework::jsonResponse($response);
        }

        $response = $this->Listing->feature($listing_id);

        if($response['success'])
        {
            return cmsFramework::jsonResponse($response);
        }

        $response['str'][] = $response['access'] ? 'ACCESS_DENIED' : 'DB_ERROR';

        return cmsFramework::jsonResponse($response);
    }

    function _publish()
    {
        $response = array('success'=>false,'str'=>array());

        $listing_id = $this->data['Listing']['id'] = Sanitize::getInt($this->params,'id');

         # Stop form data tampering
        $formToken = cmsFramework::getCustomToken($listing_id);

        if(!$listing_id || !$this->__validateToken($formToken))
        {
            $response['str'][] = 'ACCESS_DENIED';

            return cmsFramework::jsonResponse($response);
        }

        $response = $this->Listing->publish($listing_id);

        if($response['success'])
        {
            return cmsFramework::jsonResponse($response);
        }

        $response['success'] = false;

        $response['str'][] = !$response['access'] ? 'ACCESS_DENIED' : 'DB_ERROR';

        return cmsFramework::jsonResponse($response);
    }

    function _delete()
    {
        $response = array('success'=>false,'str'=>array());

        $listing_id = $this->data['Listing']['id'] = Sanitize::getInt($this->params,'id');

        # Stop form data tampering
        $formToken = cmsFramework::getCustomToken($listing_id);

        if(!$listing_id || !$this->__validateToken($formToken))
        {
            $response['str'][] = 'ACCESS_DENIED';

            return cmsFramework::jsonResponse($response);
        }

        # Load all listing info because we need to get the override settings
        $listing = $this->Listing->getListingById($listing_id);

        $user_id = $listing['Listing']['user_id'];

        $overrides = $listing['ListingType']['config'];

        # Check access
        if(!$this->Access->canDeleteListing($user_id, $overrides))
        {
            $response['str'][] = 'ACCESS_DENIED';

            return cmsFramework::jsonResponse($response);
        }

        # Delete listing and all associated records
        if($this->Listing->del($listing_id))
        {
            return cmsFramework::jsonResponse(array('success'=>true));
        }

        $response['str'][] = 'DB_ERROR';

        return cmsFramework::jsonResponse($response);
    }

    /*
    * Loads the new item form with the review form and approriate custom fields
    */
    function _loadForm()
    {
        $this->autoRender = false;

        $this->autoLayout = false;

        $this->plgResponse = $response = array();

        $dateFieldsEntry = $dateFieldsReview = array();

        $isLeaf = false;

        $level = Sanitize::getInt($this->data,'level');

        $cat_id = Sanitize::getInt($this->data,'catid');

        $cat_id_array =  Sanitize::getVar($this->data['Listing'],'catid');

        # No category selected
        if(!$cat_id)
        {
            // Check if there's a new cat id we can use
            $catArray = Sanitize::getVar($this->data['Listing'],'catid',array());

            $catArray = array_slice($catArray, 0, array_search(0, $catArray));

            if(!empty($catArray)) {
                $level = count($catArray);
                $cat_id = array_pop($catArray);
            }
        }

        # Category selected is not leaf. Need to show new category list with children, but clear every list to the right first!
        if(!$this->Category->isLeaf($cat_id))
        {
            $categories = $this->Category->getCategoryList(array('cat_id'=>$cat_id,'level'=>$level+1,'indent'=>false,'disabled'=>false,'listing_type'=>true));

            if(!empty($categories))
            {
                // Remove categories without submit access
                foreach($categories AS $key => $row)
                {
                    $overrides = !is_array($row->config) ? json_decode($row->config,true) : $row->config;

                    if(!$this->Access->canAddListing(Sanitize::getVar($overrides,'addnewaccess')))
                    {
                        unset($categories[$key]);
                    }
                }

                if(!empty($categories))
                {
                    $cat = reset($categories);
                    S2App::import('Helper','form','jreviews');
                    $Form = ClassRegistry::getClass('FormHelper');
                    $attributes = array('id'=>'cat_id'.$cat->level,'class'=>'jr-cat-select jrSelect','size'=>'1');
                    $select_list = $Form->select(
                        'data[Listing][catid][]',
                        array_merge(array(array('value'=>null,'text'=>JreviewsLocale::getPHP('LISTING_SELECT_CAT'))),$categories),
                        null,
                        $attributes
                    );

                    if($level >= 1 && count($cat_id_array) > 1) {
                        $response['level'] = $level - 1;
                    }

                    $response['select'] = $select_list;
                }

                // Get the info for the selected category to check the access

                $category = $this->Category->findRow(array('conditions'=>array('Category.' . CategoryModel::_CATEGORY_ID . ' = ' . $cat_id)));

                if(!$this->Access->canAddListing(Sanitize::getVar($category['ListingType']['config'],'addnewaccess'))) {

                    $response['action'] = 'no_access';

                    return cmsFramework::jsonResponse($response);
                }
            }

            # Checks if this category is setup with a listing type. Otherwise hides the form.
            if(!$this->Category->isJReviewsCategory($cat_id))
            {
                $response['action'] = 'hide_form';

                return cmsFramework::jsonResponse($response);
            }
        }
        else
        {
            $isLeaf = true;
        }

        # Category selected is leaf or set up with listing type, so show form
        if ($cat_id)
        {
            $name_choice = $this->Config->name_choice;

            if ($name_choice == 'alias') {
                $name = $this->_user->username;
            } else {
                $name = $this->_user->name;
            }

            # Get criteria info for selected category
            $category  = $this->JreviewsCategory->findRow(array(
                'conditions'=>array('JreviewsCategory.id = ' . $cat_id,'JreviewsCategory.option = "com_content"')
            ));

            if (!$category['Criteria']['listing_type_id'])
            {
                $response['level'] = 0;

                $response['action'] = 'no_access';

                return cmsFramework::jsonResponse($response);
            }

            isset($category['ListingType']) and $this->Config->override($category['ListingType']['config']);

            # Set theme suffix
            $this->Theming->setSuffix(compact('cat_id'));

            $criteria = $category['Criteria'];

            # Get listing custom fields
            $listing_fields = $this->Field->getFieldsArrayNew($criteria['listing_type_id'], 'listing');

            # Get review custom fields
            $review_fields = $this->Field->getFieldsArrayNew($criteria['listing_type_id'], 'review');

            $this->set(array(
                    'User'=>$this->_user,
                    'name'=>$name,
                    'listing_fields'=>$listing_fields,
                    'review_fields'=>$review_fields,
                    'criteria'=>$criteria,
                    'listing'=>array('Listing'=>array(
                            'listing_id'=>0,
                            'title'=>'',
                            'summary'=>'',
                            'description'=>'',
                            'metakey'=>'',
                            'metadesc'=>'',
                            'cat_id'=>(int) $this->data['Listing']['catid']
                    ))
            ));

            $response['rating_inc'] = Sanitize::getVar($this->Config,'rating_increment',1);

           // Remove cat select lists to the right of current select list if current selection is a leaf
            if($level && $isLeaf)
            {
                $response['level'] = $level - 1;
            }

            $response['action'] = 'show_form';

            $response['html'] = $this->render('listings','create_form');

            $response = array_merge($response,$this->plgResponse /* from plugins */);

            return cmsFramework::jsonResponse($response);
        }

        # No category selected
        $response['level'] = 0;

        $response['action'] = 'hide_form';

        return cmsFramework::jsonResponse($response);
    }

    function _save()
    {
        $this->autoRender = false;

        $this->autoLayout = false;

        $response = array('success'=>false,'str'=>array());

        $validation = '';

        $mediaForm = '';

        $listing_id = Sanitize::getInt($this->data['Listing'],'id',0);

        $isNew = $this->Listing->isNew = $listing_id == 0 ? true : false;

        $user_id = $this->_user->id;

        $this->data['isNew'] = $isNew;

        $this->data['email'] = Sanitize::getString($this->data,'email');

        $this->data['name'] = Sanitize::getString($this->data,'name');

        $this->data['categoryid_hidden'] = Sanitize::getInt($this->data['Listing'],'categoryid_hidden');

        $cat_id = Sanitize::getVar($this->data['Listing'],'catid');

        if(is_array($cat_id)) {

            $cat_id = array_filter($cat_id);

            $this->data['Listing']['catid'] = (int) array_pop($cat_id);
        }
        else {

            $this->data['Listing']['catid'] = (int) $cat_id; /*J16*/
        }

        $this->data['Listing']['title'] = Sanitize::getString($this->data['Listing'],'title','');

        $this->data['Listing']['created_by_alias'] = Sanitize::getString($this->data,'name','');

        if($isNew)
        {
            $this->data['Listing']['language'] = '*';

            $this->data['Listing']['access'] = 1;
        }

        $category_id = $this->data['Listing']['catid'] ? $this->data['Listing']['catid'] : $this->data['categoryid_hidden'];

        # Get criteria info
        $listingType = $this->Criteria->findRow(array(
            'conditions'=>array('Criteria.id =
                (SELECT criteriaid FROM #__jreviews_categories WHERE id = '.(int) $category_id.' AND `option` = "com_content")
            ')
        ));

        if(!$listingType)
        {
            $response['str'][] = 'LISTING_SUBMIT_CAT_INVALID';

            return cmsFramework::jsonResponse($response);
        }

        $this->data['Criteria']['id'] = $listingType['Criteria']['criteria_id'];

        # Override global configuration

        isset($listingType['ListingType']) and $this->Config->override($listingType['ListingType']['config']);

        // Complete the data with the Listing Type info

        $this->data = array_insert($this->data,$listingType);

        # Perform access checks
        if($isNew && !$this->Access->canAddListing())
        {
            $response['success'] = false;

            $response['str'][] = 'LISTING_SUBMIT_DISALLOWED';

            return cmsFramework::jsonResponse($response);
        }
        elseif(!$isNew)
        {
            $listing_owner = $this->Listing->getListingOwner($listing_id);

            if(!$this->Access->canEditListing($listing_owner['user_id']))
            {
                $response['str'][] = 'ACCESS_DENIED';

                return cmsFramework::jsonResponse($response);
            }
        }

        # Load the notifications observer model component and initialize it.
        # Done here so it only loads on save and not for all controlller actions.
        $this->components = array('security', 'automatic_titles', 'notifications');

        $this->__initComponents();

        if($this->invalidToken == true)
        {
            $response['str'][] = 'INVALID_TOKEN';

            return cmsFramework::jsonResponse($response);
        }

        $this->data['Listing']['id'] = $listing_id;

        // June 9, 2016 - Form passed validation when a required field was removed directly from the form
        // Stop tampering with field names - removal of inputs from the form

        $this->Field->addBackEmptyRequiredFields($this->data, 'listing', $this->Access);

        # Override configuration

        $category = $this->Category->findRow(array('conditions'=>array('Category.' . CategoryModel::_CATEGORY_ID . ' = ' . $this->data['Listing']['catid'])));

        $this->Config->override($category['ListingType']['config']);

        if($this->Access->loadWysiwygEditor() || $this->Access->isEditor())
        {
            $this->data['Listing']['introtext'] = html_entity_decode(Sanitize::getVar($this->data['__raw']['Listing'],'introtext'),ENT_QUOTES,cmsFramework::getCharset());

            $this->data['Listing']['fulltext'] = html_entity_decode(Sanitize::getVar($this->data['__raw']['Listing'],'fulltext'),ENT_QUOTES,cmsFramework::getCharset());

            // Less restrictive on server side clean up with Joomla Editors and above.
            // This allows iframes and other tags allowed via the editor so they are not removed server side.

            if(!$this->Access->isEditor()) {

                $this->data['Listing']['introtext'] = Sanitize::stripScripts(Sanitize::getVar($this->data['Listing'],'introtext'));

                $this->data['Listing']['fulltext'] = Sanitize::stripScripts(Sanitize::getVar($this->data['Listing'],'fulltext'));
            }
        }
        else {

            $this->data['Listing']['introtext'] = Sanitize::stripAll($this->data['Listing'],'introtext','');

            if(isset($this->data['Listing']['fulltext']))
            {
                $this->data['Listing']['fulltext'] = Sanitize::stripAll($this->data['Listing'],'fulltext','');
            } else {
                $this->data['Listing']['fulltext'] = '';
            }
        }

        $this->data['Listing']['introtext'] = str_replace( '<br>', '<br />', $this->data['Listing']['introtext'] );

        $this->data['Listing']['fulltext']     = str_replace( '<br>', '<br />', $this->data['Listing']['fulltext'] );

        if($this->Access->canAddMeta())
        {
            $this->data['Listing']['metadesc'] = Sanitize::getString($this->data['Listing'],'metadesc');

            $this->data['Listing']['metakey'] = Sanitize::getString($this->data['Listing'],'metakey');
        }

        // Title alias handling

        $title = Sanitize::getString($this->data['Listing'], 'title');

        $slug = '';

        $alias = Sanitize::getString($this->data['Listing'],'alias');

        if($alias == '')
        {
            $slug = S2Router::sefUrlEncode($title);

        }
        else {

            // Alias filled in so we convert it to a valid alias

            $slug = S2Router::sefUrlEncode($alias);
        }

        if(trim(str_replace('-','',$slug)) == '')
        {
            $slug = date("Y-m-d-H-i-s");
        }

        if($slug != ''
            &&
            (
                $isNew
                ||
                (!$isNew && $this->Access->isAdmin())
            )
        )
        {
            $this->data['Listing']['alias'] = $slug;
        }

        # Check for duplicates

        $duplicate_count = $this->Listing->findDuplicates($this->Config->content_title_duplicates, array(
            'title' =>$title,
            'cat_id'=>$this->data['Listing']['catid'],
            'listing_id'=>!$isNew ? $listing_id : ''
            ));

        // If duplicates allowed in the same category and there's a duplicate,
        //  append the duplicate count to the listing alias

        if($isNew && $duplicate_count > 0 && in_array($this->Config->content_title_duplicates, array('yes', 'category')))
        {
            $this->data['Listing']['alias'] .= '-' . ($duplicate_count + 1);

            $duplicate_count = 0;
        }

        // Limit check to new listings only. Otherwise listings with the same title, but different alias,
        // that are edited by registered users will throw a false duplicate response

        if($isNew && $duplicate_count && $this->data['Listing']['title'] != '')
        {
            $response['str'][] = 'LISTING_SUBMIT_DUPLICATE';

            return cmsFramework::jsonResponse($response);
        }

        // Review form display check logic used several times below
        $revFormSetting = $this->Config->content_show_reviewform;

        if($revFormSetting == 'noteditors' && !$this->Config->author_review) {

            $revFormSetting = 'all';
        }

        $revFormEnabled = !isset($this->data['review_optional'])
            && $this->Access->canAddReview()
            && $isNew
            && (   ($revFormSetting == 'all' && ($this->Config->author_review || $this->Config->user_reviews))
                || ($revFormSetting == 'authors'  && $this->Access->isJreviewsEditor($user_id))
                || ($revFormSetting == 'noteditors' && !$this->Access->isJreviewsEditor($user_id))
            );

        // Validation of content default input fields
        !$this->data['Listing']['catid'] and $this->Listing->validateSetError("sec_cat", 'LISTING_VALIDATE_SELECT_CAT');

        // Validate only if it's a new listing
        if ($isNew)
        {
            if (!$this->_user->id) {

                $username = Sanitize::getString($this->data,'username') != '' || Sanitize::getInt($this->Config,'content_username');

                $register_guests = Sanitize::getBool($this->viewVars,'register_guests');

                $this->Listing->validateInput(Sanitize::getString($this->data,'name'), "name", "text", 'VALIDATE_NAME', ($register_guests && $username) || $this->Config->content_name == "required" ? 1 : 0);

                $this->Listing->validateInput(Sanitize::getString($this->data,'username'), "username", "username", 'VALIDATE_USERNAME', $register_guests && $username);

                $this->Listing->validateInput(Sanitize::getString($this->data,'email'), "email", "email", 'VALIDATE_EMAIL', ($register_guests && $username)  || $this->Config->content_email == "required" ? 1 : 0);

                $this->data['name'] = Sanitize::getString($this->data,'name','');

                $this->data['email'] = Sanitize::getString($this->data,'email','');

            } else {

                $this->data['name'] = $this->_user->name;

                $this->data['email'] = $this->_user->email;
            }
        }

        $this->Listing->validateInput($this->data['Listing']['title'], "title", "text", 'LISTING_VALIDATE_TITLE', 1);

        # Validate listing custom fields
        $listing_valid_fields = $this->Field->validate($this->data,'listing',$this->Access);

        $this->Listing->validateErrors = array_merge($this->Listing->validateErrors,$this->Field->validateErrors);

        $this->Listing->validateInput($this->data['Listing']['introtext'], "introtext", "text", 'LISTING_VALIDATE_SUMMARY', $this->Config->content_summary == "required" ? 1 : 0);

        $this->Listing->validateInput($this->data['Listing']['fulltext'], "fulltext", "text", 'LISTING_VALIDATE_DESCRIPTION', $this->Config->content_description == "required" ? 1 : 0);

        # Validate review custom fields
        if ($revFormEnabled && $listingType['Criteria']['state'])
        {
            // June 9, 2016 - Form passed validation when a required field was removed directly from the
            // Stop tampering with field names - removal of inputs from the form

            $this->Field->addBackEmptyRequiredFields($this->data, 'review', $this->Access);

            // Review inputs
            $this->data['Review']['userid'] = $user_id;

            $this->data['Review']['email'] = $this->data['email'];

            $this->data['Review']['name'] = $this->data['name'];

            $this->data['Review']['username'] = Sanitize::getString($this->data,'name','');

            $this->data['Review']['title'] = Sanitize::getString($this->data['Review'],'title');

            $this->data['Review']['location'] = Sanitize::getString($this->data['Review'],'location'); // deprecated

            if($this->Config->review_comment_wysiwyg) {
                $comments = Sanitize::stripScripts(Sanitize::getString($this->data['__raw']['Review'],'comments',''));
                $this->data['Review']['comments'] = stripslashes($comments);
            }
            else {
                $this->data['Review']['comments'] = Sanitize::html($this->data['Review'],'comments','',true);
            }

            // Review standard fields
            $this->Listing->validateInput($this->data['Review']['title'], "rev_title", "text", 'REVIEW_VALIDATE_TITLE', ($this->Config->reviewform_title == 'required' ? true : false));

            if($listingType['Criteria']['state'] == 1 ) //ratings enabled
            {
                $criteria_qty = count($listingType['CriteriaRating']);

                $ratingErr = 0;

                if(!isset($this->data['Rating']))
                {
                    $ratingErr = $criteria_qty;
                }
                else {
                    foreach($listingType['CriteriaRating'] AS $i=>$row)
                    {
                        if (!isset($this->data['Rating']['ratings'][$i])
                            ||
                            (empty($this->data['Rating']['ratings'][$i])
                                || $this->data['Rating']['ratings'][$i] == 'undefined'
                                || (float)$this->data['Rating']['ratings'][$i] > $this->Config->rating_scale)
                        ) {
                            $ratingErr++;
                        }
                    }
                }

                $this->Listing->validateInput('', "rating", "text", array('REVIEW_VALIDATE_CRITERIA',$ratingErr), $ratingErr);
            }

            // Review custom fields
            $this->Field->validateErrors = array(); // Clear any previous validation errors

            $review_valid_fields = $this->Field->validate($this->data,'review',$this->Access);

            $this->Listing->validateErrors = array_merge($this->Listing->validateErrors,$this->Field->validateErrors);

            $this->Listing->validateInput($this->data['Review']['comments'], "comments", "text", 'REVIEW_VALIDATE_COMMENT',  ($this->Config->reviewform_comment == 'required' ? true : false));

        } // if ($revFormEnabled && $listingType['Criteria']['state'])

        # Get all validation messages

        $this->Listing->plgAfterListingSaveValidation();

        $validation = $this->Listing->validateGetErrorArray();

        # Validation failed
        if(!empty($validation))
        {
            $response['str'] = $validation;

            // Transform textareas into wysiwyg editors
            if($this->Access->loadWysiwygEditor())
            {
                $response['editor'] = true;
            }

            return cmsFramework::jsonResponse($response);
        }

        // PUBLISH UP - for both new and edited listings to ensure the time stamp is correct

        $publish_up = cmsFramework::dateUTCtoLocal(_CURRENT_SERVER_TIME); // Because it's automatically converted back to UTC on save

        $publish_up_value = Sanitize::getString($this->data['Listing'],'publish_up');

        if(Sanitize::getInt($this->Config,'listing_publication_date') && $publish_up_value != '')
        {
            $publish_up = $publish_up_value;
        }

        if($isNew || (!$isNew && $publish_up_value != ''))
        {
            $this->data['Listing']['publish_up'] = $publish_up;
        }

        // PUBLISH DOWN - for both new and edited listings to ensure the time stamp is correct

        $publish_down = NULL_DATE;

        $publish_down_value = Sanitize::getString($this->data['Listing'],'publish_down');

        if(Sanitize::getInt($this->Config,'listing_expiration_date') && $publish_down_value != '')
        {
            $publish_down = $publish_down_value;
        }

        if($isNew || (!$isNew && $publish_up_value != ''))
        {
            $this->data['Listing']['publish_down'] = $publish_down_value;
        }

        $moderateListing = $this->Access->moderateListing();

        # Validation passed, continue...
        if ($isNew)
        {
            $this->data['Listing']['created_by'] = $user_id;

            $this->data['Listing']['created'] = _CURRENT_SERVER_TIME;

            // EMAIL AND IP ADDRESS

            $this->data['Field']['Listing']['email'] = $this->data['email'];

            $this->data['Field']['Listing']['ipaddress'] = ip2long($this->ipaddress == '::1' ? '127.0.0.1' : $this->ipaddress);

            // If visitor, assign name field to content Alias
            if (!$user_id) {

                $this->data['Listing']['created_by_alias'] = $this->data['name'];
            }

            // Check moderation settings
            $this->data['Listing']['state'] = (int) !$moderateListing;

            // If listing moderation is enabled, then the review is also moderated
            if(!$this->data['Listing']['state']){

                $this->Config->moderation_reviews = $this->Config->moderation_editor_reviews = $this->Config->moderation_item;
            }

        }
        else {

            if($this->Config->moderation_item_edit) // If edit moderation enabled, then check listing moderation, otherwise leave state as is
            {
                $this->data['Listing']['state'] = (int) !$moderateListing;
            }

            $this->data['Listing']['modified'] = _CURRENT_SERVER_TIME;

            $this->data['Listing']['modified_by'] = $this->_user->id;
        }

        # Save listing

        $this->data['timezone'] = 'local';

        $savedListing = $this->Listing->store($this->data, false, array('plgBeforeBeforeSave','beforeSave','plgBeforeSave',/*'afterSave','plgAfterSave'*/));

        $listing_id = $this->data['Listing']['id'];

        if(!$savedListing)
        {
            $response['str'][] = 'DB_ERROR';
        }

        # Set as approved Claim if claims are disabled
        if($isNew && !$this->Config->claims_enable && $user_id > 0) {

            $claimData = array('Claim'=>array(
                    'listing_id'=>$listing_id,
                    'user_id'=>$user_id,
                    'created'=>_CURRENT_SERVER_TIME,
                    'approved'=>1
                ));

            $this->Claim->store($claimData);
        }

        // Error on listing save
        if(!empty($response['str']))
        {
            return cmsFramework::jsonResponse($response);
        }

        # Save listing custom fields

        $this->data['Field']['Listing']['contentid'] = $this->data['Listing']['id'];

        $this->Field->save($this->data, 'listing', $isNew, $listing_valid_fields);

        # Begin insert review in table

        if($revFormEnabled && $listingType['Criteria']['state'])
        {
            $this->data['Review']['id'] = 0;

            // Get reviewer type, for now editor reviews don't work in Everywhere components
            $this->data['Review']['author'] = (int) $this->Access->isJreviewsEditor($user_id);

            $this->data['Review']['mode'] = 'com_content';

            $this->data['Review']['pid'] = $listing_id;

            $this->data['Review']['listing_type_id'] = $listingType['Criteria']['criteria_id'];

            // Make the criteria rating definition available in the review model to process the ratings

            $this->data['CriteriaRating'] = $listingType['Criteria']['state'] == 1 ? $listingType['CriteriaRating'] : array();

            // Force plugin loading on Review model

            $this->_initPlugins('Review');

            $this->Review->isNew = true;

            $savedReview = $this->Review->save($this->data, $this->Access, $review_valid_fields);
        }

         # Before render callback - PaidListings

        $facebook = false;

        $fb_checkbox = Sanitize::getBool($this->data,'fb_publish');

        $process_media_limit_overrides = false; // For paid listings. The limit is applied to the global listing type config var

        if($isNew && isset($this->Listing->plgBeforeRenderListingSaveTrigger))
        {
            $process_media_limit_overrides = true;

            $plgBeforeRenderListingSave = $this->Listing->plgBeforeRenderListingSave();

            switch($plgBeforeRenderListingSave)
            {
                case '0': $this->data['Listing']['state'] = 1; break;

                case '1': $this->data['Listing']['state'] = 0; break;

                case '': break;

                default:

                    $facebook = true; // For paid plans we  publish to wall

                    $plgBeforeRenderListingSave['success'] = true;

                    $plgBeforeRenderListingSave['moderation'] = !isset($this->data['Listing']['state']) || $this->data['Listing']['state'];

                    # Need to run this again to trigger the afterSave plugins making the Field data available to them

                    $this->Listing->store($this->data, false, array('beforeSave',/*'plgBeforeBeforeSave','plgBeforeSave',*/'afterSave','plgAfterSave'));

                    return cmsFramework::jsonResponse($plgBeforeRenderListingSave);

                break;
            }
        }

        # Need to run this again to trigger the afterSave plugins making the Field data available to them

        $this->Listing->store($this->data, false, array('beforeSave',/*'plgBeforeBeforeSave','plgBeforeSave',*/'afterSave','plgAfterSave'));

        $listing = $this->Listing->findRow(array(
                'conditions'=>array('Listing.' . EverywhereComContentModel::_LISTING_ID . ' = ' . $listing_id)
            ),array('afterFind' /* Only need menu id */));

        $this->set(array(
            'isNew'=>$isNew,
            'upload_object'=>'listing',
            'listing_id'=>$listing_id,
            'listing'=>$listing,
            'review_id'=>0,
            'extension'=>'com_content',
            'formTokenKeys'=>array('listing_id','review_id','extension')
        ));

        if($process_media_limit_overrides)
        {
            $media_types = array('photo','video','attachment','audio');

            // Override media counts with those for the selected paid plan.
            // Necessary here because PaidListings overrides the global settings, not the listing type settings.

            foreach($media_types AS $media_type)
            {
                $listing['ListingType']['config']['media_'.$media_type.'_max_uploads_listing'] = $this->Config->{'media_'.$media_type.'_max_uploads_listing'};
            }
        }

        // Checks for media upload form as 2nd step

        if($isNew && ($allowed_media_types = $this->Access->canAddAnyListingMedia($user_id, $listing['ListingType']['config'], $listing_id)))
        {
            $tos_article = null;

            # Terms & Conditions for media uploads
            if(Sanitize::getBool($this->Config,'media_general_tos') && $tos_id = Sanitize::getInt($this->Config,'media_general_tos_articleid'))
            {
                $tos_article = $this->Article->findRow(array('conditions'=>array('Article.' . ArticleModel::_ARTICLE_ID . ' = ' . $tos_id)));
            }

            $this->set(array(
                'referrer'=>'new-listing',
                'tos_article'=>$tos_article,
                'allowed_types'=>$allowed_media_types,
                'upload_object'=>'listing',
                'session_id'=>$user_id,
                'User'=>$this->_user
            ));

            $mediaForm = $this->partialRender('media','create');
        }

        $response['success'] = true;

        $response['is_new'] = $isNew;

        // Do partial render of the post listing submit theme to include in the response

        $response['html'] = $this->render('listings', 'listings_submit_actions');

        $response['listing_id'] = $listing_id;

        # Moderation disabled
        if ($facebook || !isset($this->data['Listing']['state']) || $this->data['Listing']['state'])
        {
            $facebook_integration = Sanitize::getBool($this->Config,'facebook_enable')
                && Sanitize::getBool($this->Config,'facebook_listings')
                && $fb_checkbox;

            $response['moderation'] = false;

            isset($mediaForm) and $response['mediaForm'] = $mediaForm;

            if($facebook_integration)  {

                $token = cmsFramework::getCustomToken($listing_id);

                $response['facebook'] = true;

                $response['token'] = $token;
            }

            return cmsFramework::jsonResponse($response);
        }

        if($isNew && $mediaForm != '') {

          $response['mediaForm'] = $mediaForm;

        }

        $response['moderation'] = true;

        return cmsFramework::jsonResponse($response);
    }
}
