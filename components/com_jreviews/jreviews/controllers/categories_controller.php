<?php
/**
 * JReviews - Reviews Extension
 * Copyright (C) 2010-2015 ClickFWD LLC
 * This is not free software, do not distribute it.
 * For licencing information visit https://www.jreviews.com
 * or contact sales@jreviews.com
**/

defined( 'MVC_FRAMEWORK') or die( 'Direct Access to this location is not allowed.' );

class CategoriesController extends MyController
{

    var $uses = array('user','menu','criteria','criteria_rating','directory','category','field','media');

    var $helpers = array('assets','routes','libraries','html','text','jreviews','widgets','time','paginator','rating','custom_fields','community','media');

    var $components = array('config','access','feeds','everywhere','media_storage','listings_repository','categories_repository','advanced_search_request');

    var $autoRender = false; //Output is returned

    var $autoLayout = true;

    var $layout = 'listings';

    var $search_no_results = false;

    var $filterResults = false;

    static $urlSeparator = '_';

    function beforeFilter()
    {
        # Call beforeFilter of MyController parent class
        parent::beforeFilter();
    }

    function getPluginModel()
    {
        return $this->Listing;
    }

    function getObserverModel()
    {
        return $this->Listing;
    }

    function alphaindex()
    {
        return $this->listings();
    }

    function category()
    {
        if(!Sanitize::getString($this->params,'cat'))
        {
            return cmsFramework::raiseError(404, __t("Page not found",true));
        }

        return $this->listings();
    }

    function favorites()
    {
        $userId = Sanitize::getInt($this->params,'user',$this->_user->id);

        if (!$userId)
        {
            return $this->render('elements','login');
        }

        $this->ListingsRepository->joins('INNER JOIN #__jreviews_favorites AS Favorite ON Listing.id = Favorite.content_id AND Favorite.user_id = ' . $userId);

        return $this->listings();
    }

    function featured()
    {
        $this->ListingsRepository->orderBy('featured');

        $this->ListingsRepository->featured();

        return $this->listings();
    }

    function alpha()
    {
        $this->ListingsRepository->orderBy('alpha');

        return $this->listings();
    }

    function featuredrandom()
    {
        $this->ListingsRepository->orderBy('random');

        $this->ListingsRepository->featured();

        return $this->listings();
    }

    function latest()
    {
        $this->ListingsRepository->orderBy('rdate');

        return $this->listings();
    }

    function mylistings()
    {
        $user_id = Sanitize::getInt($this->params,'user',$this->_user->id);

        if(!$user_id)
        {
            return $this->render('elements','login');
        }

        $this->ListingsRepository->where('Listing.' . EverywhereComContentModel::_LISTING_USER_ID . ' = '.$user_id);

        return $this->listings();
    }

    function mostreviews()
    {
        $this->ListingsRepository->orderBy('reviews')->where('Totals.user_comment_count > 0');

        return $this->listings();
    }

    function toprated()
    {
        $this->ListingsRepository->orderBy('rating')->where('Totals.user_rating > 0');

        return $this->listings();
    }

    function topratededitor()
    {
        $this->ListingsRepository->orderBy('editor_rating')->where('Totals.editor_rating > 0');

        return $this->listings();
    }

    function popular()
    {
        $this->ListingsRepository->orderBy('rhits');

        return $this->listings();
    }

    function random()
    {
        $this->ListingsRepository->orderBy('random');

        return $this->listings();
    }

    function listings()
    {
        if(Sanitize::getString($this->params,'action') == 'xml')
        {
            $access =  $this->Access->getAccessLevels();

            $feed_filename = S2_CACHE . 'views' . DS . 'jreviewsfeed_'.md5($access.$this->here).'.xml';

            $this->Feeds->useCached($feed_filename,'listings');
        }

        $this->name = 'categories';   // Required for assets helper

        $this->autoRender = false;

        $fieldOrderArray = $ratingCriteriaOrderArray = array();

        $action = Sanitize::paranoid($this->action);

        $dirId = str_replace(array(self::$urlSeparator, ' '),array(',', ''), Sanitize::getString($this->params,'dir'));

        $catId = Sanitize::getString($this->params,'cat');

        $listingTypeId = Sanitize::getString($this->params,'criteria');

        $userId = Sanitize::getInt($this->params,'user',$this->_user->id);

        $index = Sanitize::getString($this->params,'index');

        $sort = Sanitize::getString($this->params,'order');

        $scope = $this->ListingsRepository->getScope();

        $listview = Sanitize::getString($this->params,'listview');

        $tmpl_suffix = Sanitize::getString($this->params,'tmpl_suffix');

        // generate canonical tag for urls with order param

        $canonical = $sort.$listview.$tmpl_suffix != '' ? true : false;

        $menuId = Sanitize::getInt($this->params,'menu',Sanitize::getString($this->params,'Itemid'));

        $menuParams = $this->Menu->getMenuParams($menuId);

        $excludeDirId = Sanitize::getVar($menuParams, 'exclude_dirid', array());

        $total_special = Sanitize::getInt($menuParams,'total_special');

        $limit_special = Sanitize::getInt($menuParams,'limit_special');

        if($limit_special > 0)
        {
            $this->limit = $limit_special;
        }

        if(!in_array($this->action,array('category')) && $total_special > 0)
        {
            $total_special <= $this->limit and $this->limit = $total_special;
        }

        $listings = array();

        $parent_categories = array();

        if($action == 'category' || ($action == 'search' && is_numeric($catId) && $catId > 0))
        {
            $parent_categories = $this->CategoriesRepository->getParents($catId);

            if(!$parent_categories)
            {
                if($this->CategoriesRepository->getError() == 'not_found')
                {
                    return cmsFramework::raiseError(404, __t("Page not found",true));
                }

                return $this->render('elements','login');
            }

            $category = end($parent_categories);

            $dirId = $this->params['dir'] = $category['Directory']['dir_id'];

            $listingTypeId = Sanitize::getInt($category['ListingType'], 'type_id');
        }

        if($this->action == 'category'
                && isset($category)
                && !empty($category)
           )
        {
            if(!$this->Access->isAuthorized($category['Category']['access']) || !$category['Category']['published'])
            {
                return $this->render('elements','login');
            }

            $categories = $this->Category->findChildren($catId, $category['Category']['level']);
        }

        // The default order is used in pagination to remove the parameter from the URL

        if(in_array($this->action,array('category','alphaindex','search','custom'))) {

            $sortDefault = $this->ListingsRepository->getDefaultOrder($includeFields = true);
        }
        else{

            $sortDefault = $this->ListingsRepository->getDefaultOrder($includeFields = false);
        }

        $this->params['default_order'] = $sortDefault;

        // The current page order

        $sort = $sort ?: $sortDefault;

        // Get the criteria ratings if we have the listing type id
        // We use this to generate the order by criteria list

        if(is_numeric($listingTypeId) && $listingTypeId > 0 && in_array($action,array('category','search')))
        {
            $ratingCriteriaOrderArray = $this->CriteriaRating->findAll(array('conditions'=>array('CriteriaRating.listing_type_id = ' . $listingTypeId)));
        }

        // Get the list ordering options

        if(is_numeric($listingTypeId) && $listingTypeId > 0 && in_array($action,array('category','search','alphaindex')))
        {
            $fieldOrderArray = $this->Field->getOrderList($listingTypeId,'listing');
        }

        # Get listings

        # Modify query for correct ordering. Change FIELDS, ORDER BY and HAVING BY directly in Listing Model variables

        if(!$this->search_no_results)
        {
            $alphaindex = $action == 'alphaindex';

            $limit = $this->limit;

            $offset = $this->offset;

            $queryData = array();

            $queryOptions = array_filter(compact(
                    'catId',
                    'listingTypeId',
                    'dirId',
                    'userId',
                    'alphaindex',
                    'index'
                ),
                function($value) {
                    return ($value !== null && $value !== false && $value !== '');
                }
            );

            $listings = $this->ListingsRepository
                            ->addQueryData($queryData) // Doesn't overwrite the search queries if it's a search request
                            ->queryOptions($queryOptions)
                            ->whereDirIdNotIn($excludeDirId)
                            ->withSubcategoryListings($action == 'category' ? $this->Config->list_show_child_listings : true)
                            ->orderBy($sort)
                            ->offset($offset)
                            ->limit($limit)
                            ->many();

            # If only one result then redirect to it

            if(!$this->ajaxRequest && $this->Config->search_one_result && count($listings)==1 && $this->action == 'search' && $this->page == 1)
            {
                $listing = array_shift($listings);

                $url = cmsFramework::makeAbsUrl($listing['Listing']['url'],array('sef'=>true));

                cmsFramework::redirect($url, 302);
            }
        }

        # Prepare Listing count query

        $count = 0;

        if($listings)
        {
            $count = $this->ListingsRepository
                        ->sessionCache(in_array($this->action, array('favorites','mylistings')) ? false : true)
                        ->countColumn(in_array('reviews', $scope) ? 'DISTINCT Listing.id' : '*')
                        ->count();

            if($total_special > 0 && $count > $total_special)
            {
                $count = $total_special;
            }

        }

        # Set the theme layout and suffix

        $this->Theming->setSuffix(array('categories'=>$parent_categories));

        $this->Theming->setLayout(array('categories'=>$parent_categories));

        # Get directory info for breadcrumb if dir id is a url parameter

        $directory = array();

        if(is_numeric($dirId))
        {
            $directory = $this->Directory->findRow(array(
                'fields'=>array(
                    'Directory.id AS `Directory.dir_id`',
                    'Directory.title AS `Directory.slug`',
                    'Directory.desc AS `Directory.title`'
                ),
                'conditions'=>array('Directory.id = ' . $dirId)
            ));
        }

        /******************************************************************
        * Process page title and description
        *******************************************************************/

        $page = $this->createPageArray($menuId);

        switch($action)
        {
            case 'category':

                if(isset($category))
                {
                    $this->pageMetaCategory($page, $category, $parent_categories);

                    // Check if this is a listing submit category or disable listing submissions

                    if(Sanitize::getInt($category['Category'],'criteria_id') == 0) {

                        $this->Config->list_show_addnew = 0;
                    }
                }

                break;

            case 'custom':

                // Ordering should not be included in the page title for custom lists that have a specific odering set

                $custom_params = array();

                parse_str(Sanitize::getString($page['menuParams'],'custom_params'), $custom_params);

                if(Sanitize::getString($page['menuParams'],'custom_order') != '' || isset($custom_params['order']))
                {
                    $sort = '';

                    $page['menuParams']['custom_order'] = ' ';

                    unset($this->params['order']);
                }

                break;

            case 'alphaindex':

                $title = isset($directory['Directory']) ? Sanitize::getString($directory['Directory'],'title','') : '';

                $page['title'] = ($title != '' ? $title . ' - ' . ($index == '0' ? '0-9' : $index) : ($index == '0' ? '0-9' : $index));

                break;

            case 'mylistings':

                $this->pageMetaMyPages($page, $userId, 'mylistings');

                break;

            case 'favorites':

                $this->pageMetaMyPages($page, $userId, 'favorites');
                break;

            case 'list':
            case 'search':

                $menuCatId = Sanitize::getInt($page['menuParams'], 'catid');

                $catId = $catId ?: $menuCatId;

                $this->__seo_fields($page, $catId, $listingTypeId);

                break;

            case 'featured':
            case 'latest':
            case 'mostreviews':
            case 'popular':
            case 'toprated':
            case 'topratededitor':

                break;

            default:

                $page['title'] = Sanitize::getString($page,'title');

                break;
        }

        if(Sanitize::getString($page,'top_description') != '') $page['show_description'] = true;

        // If empty unset the keys so they don't overwrite the ones set via menu

        if(trim(strip_tags(Sanitize::getString($page,'description'))) == '') unset($page['description']);

        if(trim(strip_tags(Sanitize::getString($page,'keywords'))) == '') unset($page['keywords']);

        /******************************************************************
        * Generate SEO canonical tags for sorted pages
        *******************************************************************/

        if($canonical) {

            $page['canonical'] = cmsFramework::getCurrentUrl(array('order','listview','tmpl_suffix'));
        }

        /******************************************************************
        * Generate SEO titles for re-ordered pages (most reviews, top user rated, etc.)
        *******************************************************************/

        if(Sanitize::getString($page,'title_seo') == '' && isset($page['title'])) {

            $page['title_seo'] = $page['title'];
        }

        if(($this->action !='search' || Sanitize::getVar($this->params,'tag')) && isset($this->params['order']) && $sort != '')
        {
            S2App::import('helper','jreviews','jreviews');

            $matches = array();

            $ordering_options = JreviewsHelper::orderingOptions();

            $tmp_order = str_replace('rjr','jr',$sort);

            if(isset($ordering_options[$sort]))
            {
                $page['title_seo'] .= ' ' . sprintf(JreviewsLocale::getPHP('LIST_PAGE_ORDERED_BY_TITLE_SEO'), mb_strtolower($ordering_options[$sort],'UTF-8'));
            }
            elseif(isset($fieldOrderArray[$tmp_order])) {

                if($sort{0} == 'r')
                {

                    $page['title_seo'] .= ' ' . sprintf(JreviewsLocale::getPHP('LIST_PAGE_ORDERED_BY_DESC_TITLE_SEO'), mb_strtolower($fieldOrderArray[$tmp_order]['text'],'UTF-8'));
                }
                else {

                    $page['title_seo'] .= ' ' . sprintf(JreviewsLocale::getPHP('LIST_PAGE_ORDERED_BY_TITLE_SEO'), mb_strtolower($fieldOrderArray[$sort]['text'],'UTF-8'));
                }
            }
            elseif(preg_match('/rating-(?P<criteria>\d+)/', $sort,$matches)) {

                if(isset($matches['criteria']) && isset($ratingCriteriaOrderArray[$matches['criteria']]))
                {
                    $criteria_title = $ratingCriteriaOrderArray[$matches['criteria']]['CriteriaRating']['title'];

                    $page['title_seo'] .= ' ' . sprintf(JreviewsLocale::getPHP('LIST_PAGE_ORDERED_BY_TITLE_SEO'), $criteria_title);
                }
            }
        }

        $this->params['order'] = $sort; // This is the param read in the views so we need to update it

        $fieldCrumbs = array();

        if(isset($this->params['tag']))
        {
            $control_fieldid = Sanitize::getInt($this->params['tag'],'control_fieldid');

            $control_value = Sanitize::getString($this->params['tag'],'control_value');

            $fieldCrumbs = array($this->params['tag']);

            if($control_fieldid > 0 && $control_value != '')
            {
                $this->Field->getBreadCrumbs($fieldCrumbs, $this->params['tag']['control_fieldid'], $this->params['tag']['control_value']);

                $fieldCrumbs = array_reverse($fieldCrumbs);
            }
        }

        // Override the show map setting based on menu parameters

        if(isset($page['menuParams']))
        {
            $menuShowMap = Sanitize::getInt($page['menuParams'], 'show_map', -1);

            if($menuShowMap !== -1)
            {
                $this->Config->{'geomaps.enable_map_list'} = $menuShowMap;
            }
        }

        /******************************************************************
        * Set view (theme) vars
        *******************************************************************/
        $this->set(array(
            'Config'=>$this->Config,
            'User'=>$this->_user,
            'subclass'=>'listing',
            'page'=>$page,
            'directory'=>$directory,
            'category'=>isset($category) ? $category : array(), // Category list
            'categories'=>isset($categories) ? $categories : array(),
            'parent_categories'=>$parent_categories, // Used for breadcrumb
            'cat_id'=>$catId,
            'listings'=>$listings,
            'fieldCrumbs'=>$fieldCrumbs,
            'pagination'=>array(
                'total'=>$count,
                'ajax'=>Sanitize::getInt($this->Config, 'paginator_ajax', 0)
            ),
            'fieldOrderArray'=>$fieldOrderArray,
            'ratingCriteriaOrderArray'=>$ratingCriteriaOrderArray
        ));

        /******************************************************************
        * RSS Feed: caches and displays feed when xml action param is present
        *******************************************************************/

        if(Sanitize::getString($this->params,'action') == 'xml')
        {
            $this->Feeds->saveFeed($feed_filename,'listings');
        }

        return $this->render('listings','listings_' . $this->listview);
    }

    function compareCatchAll()
    {
        $this->action = 'compare';

        return $this->compare();
    }

    function compare()
    {
        $listings = array();

        $menuId = Sanitize::getInt($this->params,'Itemid');

        $listingType = Sanitize::getInt($this->params,'type');

        $menuParams = $this->Menu->getMenuParams($menuId);

        $is_mobile = Configure::read('System.isMobile');

        $isMenu = false;

        $listingIds = Sanitize::getString($menuParams,'listing_ids');

        if(!empty($listingIds)) {

            $isMenu = true;
        }
        elseif($listingIds = Sanitize::getString($this->params,'id')) {

            $isMenu = false;
        }

        $listingIds = cleanIntegerCommaList($listingIds);

        if(empty($listingIds))
        {
            cmsFramework::raiseError(404, JreviewsLocale::getPHP('COMPARISON_NO_LISTINGS'));
        }

        $listings = $this->ListingsRepository
                        ->whereListingId($listingIds)
                        ->published()
                        ->order('FIELD(Listing.id,' . $listingIds .')')
                        ->many();

        $listing_type_id = array();

        foreach($listings AS $listing) {

            $listing_type_id[$listing['Criteria']['criteria_id']] = $listing['Criteria']['criteria_id'];
        }

        if(count($listing_type_id) > 1)
        {
            return '<div class="jrError">'.JreviewsLocale::getPHP('COMPARISON_VALIDATE_DIFFERENT_TYPES').'</div>';
        }

        $firstListing = reset($listings);

        # Override configuration
        isset($firstListing['ListingType']) and $this->Config->override($firstListing['ListingType']['config']);

        $listingType = $firstListing['Criteria'];

        $listing_type_title = $listingType['title'];

        // Get the list of fields for the chosen listing type to render the groups and field in the correct order

        $fieldGroups = $this->Field->getFieldsArrayNew($listingType['criteria_id']);

        /******************************************************************
        * Process page title and description
        *******************************************************************/

        $page = $this->createPageArray($menuId);

        if($page['title'] == '') {

            $page['show_title'] = true;

            $page['title'] = sprintf(JreviewsLocale::getPHP('COMPARISON_DEFAULT_TITLE'),$listing_type_title);
        }

        if(Sanitize::getInt($menuParams,'action') == '103') {

            $page['title_seo'] = $page['title'];
        }

        $this->set(array(
            'listingType'=>$listingType,
            'Config'=>$this->Config,
            'User'=>$this->_user,
            'fieldGroups'=>$fieldGroups,
            'listings'=>$listings,
            'page'=>$page,
            'isMenu'=>$isMenu
        ));

        if (!$is_mobile)
        {
            return $this->render('listings','listings_compare');
        }
        else {

            return $this->render('listings','listings_blogview');
        }
    }

    # Custom List menu - reads custom where and custom order from menu parameters

    function custom() {

        $menuId = Sanitize::getInt($this->params,'Itemid');

        $params = $this->Menu->getMenuParams($menuId);

        $custom_where = Sanitize::getString($params,'custom_where');

        $custom_order = Sanitize::getString($params,'custom_order');

        $custom_params = Sanitize::getString($params,'custom_params');

        $custom_params_array = array();

        parse_str($custom_params, $custom_params_array);

        if(!empty($custom_params_array))
        {
            $this->passedArgs = array_insert($this->passedArgs, $custom_params_array);

            $this->params = array_insert($this->params, $custom_params_array);

            if(!isset($this->params['scope']))
            {
                $this->params['scope'] = 'title_introtext_fulltext';
            }

            if(!isset($this->params['query']))
            {
                $this->params['query'] = 'any';
            }
        }

        $this->ListingsRepository->where($custom_where)->order($custom_order);

        // Prevent data from proximity search from getting into the search conditionals because it was already
        // processed in the GeoMaps add-on

        $jr_lat = Sanitize::getString($this->Config,'geomaps.latitude');

        $jr_lon = Sanitize::getString($this->Config,'geomaps.longitude');

        $search_address_field = Sanitize::getString($this->Config,'geomaps.advsearch_input');

        if($jr_lat && $jr_lon && $search_address_field)
        {
            unset(
                $this->params[$jr_lat]
                ,$this->params[$jr_lon]
                ,$this->params[$search_address_field]
                );
        }

        return $this->search();
    }

    function liveFilter()
    {
        $this->filterResults = true;

        return $this->liveSearch();
    }

    function liveSearch()
    {
        $this->Config->search_one_result = 0;

        $menuId = Sanitize::getInt($this->data,'menu_id');

        $menuId = $menuId ?: $this->Menu->get('jr_advsearch');

        $searchData = $this->passedArgs['data'];

        $referrer = Sanitize::getString($searchData, 'referrer');

        if($referrer == 'submitListing')
        {
            if($keywords = Sanitize::getString($searchData, 'keywords'))
            {
                $this->data['module_id'] = 'submitListing';

                $searchData['search_query_type'] = 'all';

                $searchData['keywords'] = $keywords;

                $searchData['contentoptions'][] = 'title';

                $searchData['menu_id'] = $this->Menu->get('jr_advsearch');
            }
            else {
                return '';
            }
        }

        $url = $this->AdvancedSearchRequest->process($searchData, array('amp_replace' => true));

        // Make the search URL available in theme files

        $this->set('search_url', $url);

        $params = parse_url($url, PHP_URL_QUERY);

        parse_str($params, $searchParams);

        $this->params = array_merge($this->params, $searchParams);

        /**
         * It's a full page update with results instead of the compact live search results from the module
         */
        if ($this->filterResults)
        {
            $this->action = 'search';

            $this->passedArgs = $searchParams;

            $this->passedArgs['url'] = 'search-results';

            $this->passedArgs['option'] = 'com_jreviews';

            $this->params['Itemid'] = $this->passedArgs['Itemid'] = $menuId;
        }

        // Make sure that the filters from the menu are used for specific types of menus

        if($menuId)
        {
            $menuParams = $this->Menu->getMenuParams($menuId);

            $action = Sanitize::getString($menuParams,'action');

            switch($action) {

                case '2':

                    if(Sanitize::getString($this->params, 'cat') == '' && isset($menuParams['catid'])) {
                        $this->params['cat'] = $menuParams['catid'];
                    }

                    break;

                case '11':

                    if(Sanitize::getString($this->params, 'criteria') == '' && isset($menuParams['criteriaid'])) {
                        $this->params['criteria'] = $menuParams['criteriaid'];
                    }

                    break;
            }
        }

        // Prevent data from proximity search from getting into the search conditionals because it was already
        // processed in the GeoMaps add-on

        $jrLat = Sanitize::getString($this->Config,'geomaps.latitude');

        $jrLon = Sanitize::getString($this->Config,'geomaps.longitude');

        $searchAddressField = Sanitize::getString($this->Config,'geomaps.advsearch_input');

        if($jrLat && $jrLon && $searchAddressField)
        {
            unset(
                $this->params[$jrLat]
                ,$this->params[$jrLon]
                ,$this->params[$searchAddressField]
                );
        }

        return $this->search();
    }

    function liveSearchResults()
    {
        $showNoResults = true;

        // Get Joomla module/WP widget parameters

        $moduleId = Sanitize::getString($this->data, 'module_id');

        if($moduleId == 'submitListing')
        {
            $settings = Sanitize::getVar($this->Config, 'submit_listing_livesearch', array(
                'results_limit' => 5,
                'results_columns' => 1,
                'tn_show' => 1,
                'summary' => 0,
                'summary_words' => 10,
                'show_category' => 1,
                'fields' => '',
                'editor_rating' => 0,
                'user_rating' => 0,
                'results_tmpl_suffix' => ''
            ));

            $settings['before_text'] = JreviewsLocale::getConstant(Sanitize::getString($this->Config, 'lang_listing_form_title_search'), true);

            $showNoResults = false;
        }
        else {
            $settings = cmsFramework::getModuleParams($moduleId);
        }

        $this->params['module'] = $settings;

        $limit = Sanitize::getInt($settings, 'results_limit',5);

        $this->viewSuffix = Sanitize::getString($settings, 'results_tmpl_suffix');

        $dirId = str_replace(array(self::$urlSeparator, ' '), array(',', ''), Sanitize::getString($this->params,'dir'));

        $catId = Sanitize::getString($this->params, 'cat');

        $listingTypeId = Sanitize::getString($this->params, 'criteria');

        $userId = Sanitize::getInt($this->params, 'user', $this->_user->id);

        $sort = Sanitize::getString($this->params, 'order');

        $sortDefault = $this->ListingsRepository->getDefaultOrder();

        $sort = $sort ?: $sortDefault;

        $queryOptions = array_filter(compact(
            'catId',
            'listingTypeId',
            'dirId',
            'userId'
        ));

        $listings = $this->ListingsRepository
                        ->queryOptions($queryOptions)
                        ->without('Favorite')
                        ->orderBy($sort)
                        ->offset(0)
                        ->limit($limit)
                        ->many();

        $count = $this->ListingsRepository->sessionCache(false)->count();

        if(!$count && !$showNoResults)
        {
            return '';
        }

        $this->set(array(
            'listings'=>$listings,
            'distance'=>1,
            'count'=>$count
            ));

        $output = $this->render('listings','listings_search_results');

        return $output;
    }

    function search()
    {
        $queryString = Sanitize::getString($this->passedArgs,'url');

        if(isset($this->params['tag']))
        {
            $this->passedArgs['tag'] = $this->params['tag'];
        }

        $catId = Sanitize::getString($this->params, 'cat');

        $listingTypeId = Sanitize::getString($this->params, 'criteria');

        $dirId = Sanitize::getString($this->params, 'dir');

        $fields = $this->ListingsRepository->buildFieldsParamsArray($this->params, $queryString);

        $scope = $this->ListingsRepository->processSearchScope(Sanitize::getString($this->params,'scope'));

        $userId = $this->_user->id;

        $queryType = Sanitize::getString($this->params, 'query');

        $keywords = Sanitize::getString($this->params, 'keywords');

        $userRatings = Sanitize::getVar($this->params,S2_QVAR_RATING_AVG,0);

        $editorRatings = Sanitize::getVar($this->params,S2_QVAR_EDITOR_RATING_AVG,0);

        $author = urldecode(Sanitize::getString($this->params, 'author'));

        $order = Sanitize::getString($this->params, 'order');

        $tag = Sanitize::getVar($this->params, 'tag');

        $author = urldecode(Sanitize::getString($this->params, 'author'));

        $matchAllFields = array();

        if ($usematch = Sanitize::getInt($this->params, 'usematch'))
        {
            $matchAllFields = explode(',',Sanitize::getString($this->params, 'matchall'));
        }

        $limit = $this->limit;

        $offset = $this->offset;

        $searchFilters = compact(
            'userId',
            'queryType',
            'userRatings',
            'editorRatings',
            'tag',
            'author'
        );

        $this->ListingsRepository
                ->matchAllFields($usematch, $matchAllFields)
                ->search($scope, $keywords, $fields, $searchFilters)
                ->offset($offset)
                ->limit($limit);

        if ($catId)
        {
            $catId = explode(',', str_replace(self::$urlSeparator, ',', $catId));

            $catId = array_filter($catId);

            $catId = implode(',', $catId);

            $this->params['cat'] = $catId;
        }

        if ($listingTypeId) {

            $listingTypeId = str_replace(self::$urlSeparator, ',', $listingTypeId);

            $this->params['criteria'] = $listingTypeId;
        }

        if ($dirId)
        {
            $dirId = str_replace(self::$urlSeparator, ',', $dirId);

            $this->params['dir'] = $dirId;
        }

        # Add search conditions to Listing model

        if ((
            count($this->ListingsRepository->getConditions()) == 0
            &&
            $dirId == ''
            &&
            $catId == ''
            &&
            $listingTypeId == ''
            )
         &&
         !Sanitize::getBool($this->Config,'search_return_all',false))
        {
            $this->search_no_results = true;
        }

        if($this->action == 'liveSearch')
        {
            $out = $this->liveSearchResults();
        }
        else {

            $out = $this->listings();
        }

        return $out;
    }

    protected function __seo_fields(&$page, $cat_id = null, $listingTypeId = null)
    {
        $category = $parent_category = $listing_type = '';

        $listingType = null;

        if($tag = Sanitize::getVar($this->params,'tag'))
        {
            $field = 'jr_'.$tag['field'];
//            $value = $tag['value'];
            // Field value underscore fix: remove extra menu parameter not removed in routes regex
            $value = preg_replace(array('/_m[0-9]+$/','/_m$/','/_$/','/:/'),array('','','','-'),$tag['value']);

            $query = "
                SELECT
                    fieldid,
                    type,
                    metatitle,
                    options,
                    metakey,
                    metadesc
                FROM
                    #__jreviews_fields
                WHERE
                    name = ".$this->Quote($field)." AND `location` = 'content'
            ";


            $field = $this->Field->query($query, 'loadObjectList');

            if($field)
            {
                $field = array_shift($field);

                $params = stringToArray($field->options);

                $multichoice = array('select','selectmultiple','checkboxes','radiobuttons');

                $option = array();

                if(in_array($field->type,$multichoice))
                {
                    $query = "
                        SELECT
                            FieldOption.optionid,
                            FieldOption.text,
                            FieldOption.description,
                            FieldOption.control_field,
                            FieldOption.control_value,
                            Field.fieldid AS control_fieldid
                        FROM
                            #__jreviews_fieldoptions AS FieldOption
                        LEFT JOIN
                            #__jreviews_fields AS Field ON FieldOption.control_field = Field.name
                        WHERE
                            FieldOption.fieldid = "  . (int) $field->fieldid . "
                            AND FieldOption.value = " . $this->Quote(stripslashes($value))
                        ;

                    $option = $this->Field->query($query,'loadAssoc');

                    if(!$option)
                    {
                        return cmsFramework::raiseError(404, __t("Page not found",true));
                    }

                    $this->params['tag'] = array_merge($this->params['tag'], $option);

                    $fieldValue = $option['text'];
                }
                elseif(in_array($field->type,array('decimal','integer')))
                {
                    if($field->type == 'integer')
                    {
                        $fieldValue = Sanitize::getInt($params,'curr_format') ? number_format($value,0,__l('DECIMAL_SEPARATOR',true),__l('THOUSANDS_SEPARATOR',true)) : $value;
                    }
                    else
                    {
                        $decimals = Sanitize::getInt($params,'decimals',2);

                        $fieldValue = Sanitize::getInt($params,'curr_format') ? number_format($value,$decimals,__l('DECIMAL_SEPARATOR',true),__l('THOUSANDS_SEPARATOR',true)) : round($value,$decimals);
                    }

                    $fieldValue = str_ireplace('{fieldtext}', $fieldValue, $params['output_format']);

                    $fieldValue = strip_tags(urldecode($fieldValue));
                }
                else {

                    $fieldValue = urldecode($value);
                }

                $description = Sanitize::getString($option,'description') ?: $field->metadesc;

                if($cat_id
                    && ( stristr($field->metatitle.$field->metakey.$description,'{category}')
                        || stristr($field->metatitle.$field->metakey.$description,'{parent_category}'))
                    )
                {
                    if($categories = $this->Category->findParents($cat_id)) {

                        $category_array = array_pop($categories);

                        $category = $category_array['Category']['title'];

                        if(!empty($categories)) {

                            $parent_category_array = array_pop($categories);

                            $parent_category = $parent_category_array['Category']['title'];

                        }

                    }

                    $listingType = $this->Criteria->getCriteria(array('cat_id'=>$cat_id));
                }

                if ($listingTypeId && !$listingType)
                {
                    $listingType = $this->Criteria->getCriteria(array('criteria_id'=>$listingTypeId));
                }

                if ($listingType)
                {
                    $listing_type = $listingType['Criteria']['title'];
                }

                $search = array('{fieldvalue}','{category}','{parent_category}','{listing_type}');

                $replace = array($fieldValue, $category, $parent_category, $listing_type);

                $page['title'] = $page['title_seo'] = $field->metatitle == '' ? $fieldValue : trim(str_ireplace($search,$replace,$field->metatitle));

                $page['keywords'] = $page['menuParams']['menu-meta_keywords'] = trim(str_ireplace($search,$replace,$field->metakey));

                $page['description'] = $page['menuParams']['menu-meta_description'] = trim(str_ireplace($search,$replace,$description));

                $page['show_title'] = $this->Config->seo_title;

                $page['show_description'] = $this->Config->seo_description;

                if($page['show_description']) {

                    $page['top_description'] = $page['description'];
                }
            }
        }
    }

    protected function pageMetaCategory(& $page, $category, $parent_categories)
    {
        $menu_action = 2;

        $page['title'] = $page['title'] ?: $category['Category']['title'];

        // Could be a direct category menu or a menu for a parent category

        $menu_exists = !empty($page['menuParams']) && isset($page['menuParams']['action']);

        $menu_is_for_this_category = $menu_exists
                                        && $page['menuParams']['action'] == $menu_action
                                        && $page['menuParams']['catid'] == $category['Category']['cat_id'];

        $menu_page_title = Sanitize::getString($page['menuParams'],'page_title');

        $menu_page_heading = Sanitize::getString($page['menuParams'],'page_heading');

        $menu_show_page_heading = Sanitize::getBool($page['menuParams'],'show_page_heading');

        // Prevent the show_page_heading menu param from disabling the display of the category title

        if (!isset($page['menuParams']['show_page_heading']) || $page['menuParams']['show_page_heading'] == '')
        {
            $page['show_title'] = true;
        }

        // Ensure the correct title is displayed in subcategory pages when the subcategory doesn't have its own menu
        if(!$menu_is_for_this_category) {

            $page['title'] = $category['Category']['title'];

            $page['title_seo'] = $category['Category']['title_seo'];
        }
        else {

            // Menu page settings override everything else

            if($menu_page_title != '') {

                $page['title_seo'] = $menu_page_title;
            }
            else {

                $page['title_seo'] = $category['Category']['title_seo'];
            }

            if($menu_page_heading != '') {

                $page['title'] = $menu_page_heading;
            }
        }

        if(Sanitize::getString($page,'top_description') == '')  {

            $page['top_description'] = $category['Category']['description'];
        }

        // if($menu_not_for_this_category || Sanitize::getString($category['Category'],'metadesc') != '' && Sanitize::getString($page,'description') == '') {
        // If category doesn't have a menu, but the meta data is available from the Joomla category manager we use it
        if(($menu_is_for_this_category && Sanitize::getString($page['menuParams'],'menu-meta_description') == '')
            ||
            (!$menu_is_for_this_category && Sanitize::getString($category['Category'],'metadesc') != '')) {

            $page['description'] =  Sanitize::htmlClean($category['Category']['metadesc']);

            // Ensure menu params doesn't override Joomla category manager setting
            $page['menuParams']['menu-meta_description'] = '';
        }

        // If category doesn't have a menu, but the meta data is available from the Joomla category manager we use it
        if(($menu_is_for_this_category && Sanitize::getString($page['menuParams'],'menu-meta_keywords') == '')
            ||
            (!$menu_is_for_this_category && Sanitize::getString($category['Category'],'metakey') != '')) {
        // if($menu_not_for_this_category || Sanitize::getString($category['Category'],'metakey') != '' && Sanitize::getString($page,'keywords') == '') {

            $page['keywords'] =  Sanitize::htmlClean($category['Category']['metakey']);

            // Ensure sure menu params doesn't override Joomla category manager setting
            $page['menuParams']['menu-meta_keywords'] = '';
        }

        // Process Category SEO Manager title, keywords and description
        $page['description'] = str_replace('{category}',$category['Category']['title'],Sanitize::getString($page,'description'));

        $page['keywords'] = str_replace('{category}',$category['Category']['title'],Sanitize::getString($page,'keywords'));

        $matches1 = $matches2 = $matches3 = array();

        $tags = $replacements = array();

        if(!empty($parent_categories) &&
            (
                preg_match('/{category[0-9]+}/',$page['description'],$matches1)
                || preg_match('/{category[0-9]+}/',$page['keywords'],$matches2)
                || preg_match('/{category[0-9]+}/',$page['title_seo'],$matches3)
            )
        ) {
            $matches = array_merge($matches1,$matches2,$matches3);

            if(!empty($matches)) {

                $i = 0;

                foreach($parent_categories AS $category) {

                    $i++;

                    $tags[] = '{category'.$i.'}';

                    $replacements[] = $category['Category']['title'];
                }
            }
        }

        $tags[] = '{category}';

        $replacements[] = $category['Category']['title'];

        if($menu_page_heading == '') {

            $page['title'] = str_replace($tags,$replacements,$category['Category']['title_override'] ? $page['title_seo'] : $page['title']);
        }

        $page['title_seo'] = str_replace($tags,$replacements,$page['title_seo']);

        $page['description'] = str_replace($tags,$replacements,$page['description']);

        $page['keywords'] = str_replace($tags,$replacements,$page['keywords']);

        $page['top_description'] = str_replace($tags,$replacements,$page['top_description']);

        // Category image

        if($categoryParams = Sanitize::getString($category['Category'],'params')) {

            $categoryParams = json_decode($categoryParams);

            $page['image'] = Sanitize::getString($categoryParams,'image');

        }
    }

    protected function pageMetaMyPages(& $page, $userId, $type)
    {
        $name_choice = constant('UserModel::_USER_' . strtoupper($this->Config->name_choice) );

        $user_name = '';

        if($userId > 0)
        {
            $this->User->fields = array();

            $user_name = $this->User->findOne(
                array(
                    'fields'=>array('User.' . $name_choice . ' AS `User.name`'),
                    'conditions'=>array('User.' . UserModel::_USER_ID . ' = ' . $userId)
                )
            );

        }
        elseif($this->_user->id > 0) {

            $user_name = $this->_user->{$name_choice};
        }

        if($user_name == '')
        {
            return cmsFramework::raiseError( 404, s2Messages::errorGeneric() );
        }

        $page['show_title'] = 1;

        switch($type)
        {
            case 'mylistings':

                    $page['title'] = $page['title_seo'] = sprintf(JreviewsLocale::getPHP('LIST_PAGE_LISTINGS_BY_TITLE_SEO'),$user_name);

                break;

            case 'favorites':

                    $page['title'] = $page['title_seo'] = sprintf(JreviewsLocale::getPHP('LIST_PAGE_FAVORITES_BY_TITLE_SEO'),$user_name);

                break;
        }
    }
}