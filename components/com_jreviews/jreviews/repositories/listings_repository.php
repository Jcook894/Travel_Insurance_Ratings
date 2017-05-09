<?php
/**
 * JReviews - Reviews Extension
 * Copyright (C) 2010-2015 ClickFWD LLC
 * This is not free software, do not distribute it.
 * For licencing information visit https://www.jreviews.com
 * or contact sales@jreviews.com
**/

defined( 'MVC_FRAMEWORK') or die( 'Direct Access to this location is not allowed.' );

S2App::import('Component', 'base_repository', 'jreviews');

class ListingsRepositoryComponent extends BaseRepository {

    protected $modelName = 'listing';

	protected $c;

	protected $config;

    protected $access;

	protected $listing;

    protected $field;

    protected $menu;

    protected $scope = array();

    protected $fieldsParamsArray = array();

    protected $subcategoryListings = true;

    protected $published = null;

    protected $usematch = false;

    protected $matchAllFields;

	function startup (& $controller)
	{
        if(!isset($controller->Listing) || !$controller->Listing) return;

		$this->c = & $controller;

		$this->config = & $this->c->Config;

        $this->access = & $this->c->Access;

        $this->listing = & $this->c->Listing;

        $this->field = & $this->c->Field;

		$this->menu = & $this->c->Menu;

        $this->listing->controller = $this->c->name;

        $this->listing->action = $this->c->action;

        # Make configuration available in models

        $this->listing->Config = & $this->c->Config;
	}

    function withSubcategoryListings($state = true)
    {
        $this->subcategoryListings = $state;

        return $this;
    }

    function allForUser($userId)
    {
        $this->listing->addListingFiltering($this->queryData['conditions'], $this->access, array('state'=>0,'user'=>$userId,'action'=>'mylistings'));

        return $this;
    }

    function published($state = 1)
    {
        if($state)
        {
            $this->published = $state;
        }
        else {

            $this->where($this->listing->whereModerated());
        }

        return $this;
    }

    function withUnpublished()
    {
        $this->published = 0;

        return $this;
    }

    function withAllStates()
    {
        $this->published = 'all';

        return $this;
    }

    function featured()
    {
        $this->where('Field.featured > 0');

        return $this;
    }

    function getScope()
    {
        return $this->scope;
    }

    function getDefaultOrder($includeFields = true)
    {
        // At this point the override settings should have already been applied

        $field = $this->config->list_order_field;

        $standard = $this->config->list_order_default;

        return $includeFields && $field != '' ? $field : $standard;
    }

    function one()
    {
        $queryData = & $this->queryData;

        if(!empty($this->stopAfterFindModels))
        {
            $this->listing->addStopAfterFindModel($this->stopAfterFindModels);
        }

        if(!is_null($this->callbacks))
        {
            $listing = $this->listing->findRow($queryData, $this->callbacks);
        }
        else {

            $listing = $this->listing->findRow($queryData);
        }

        return $listing;
    }

    function many()
    {
        $this->prepareQueryData();

        $queryData = $this->getQuerydata();

        if(!is_null($this->callbacks))
        {
            $listings = $this->listing->findAll($queryData, $this->callbacks);
        }
        else {
            $listings = $this->listing->findAll($queryData);
        }

        return $listings;
    }

    /**
     * Process all querydata options
     * @return [type] [description]
     */
    function prepareQueryData()
    {
        $queryData = $this->getQuerydata();

        $queryOptions = $this->queryOptions;

        $listing_id = Sanitize::getVar($queryOptions, 'listingId');

        $cat_id = Sanitize::getVar($queryOptions, 'catId');

        $criteria_id = Sanitize::getVar($queryOptions, 'listingTypeId');

        $dir_id = Sanitize::getVar($queryOptions, 'dirId');

        $alphaindex = Sanitize::getInt($queryOptions, 'alphaindex');

        $index = Sanitize::getString($queryOptions,'index');

        $user_id = Sanitize::getInt($queryOptions, 'userId');

        $state = Sanitize::getInt($queryOptions, 'state');

        $children = $this->subcategoryListings;

        if($alphaindex && $index != '')
        {
            $queryData['conditions']['Listing.'.EverywhereComContentModel::_LISTING_TITLE] = $index == '0'
                                ?
                                'Listing.' . EverywhereComContentModel::_LISTING_TITLE . ' REGEXP "^[0-9]"'
                                :
                                'Listing.' . EverywhereComContentModel::_LISTING_TITLE . ' LIKE '.$this->c->Quote($index.'%');
        }

        if(!empty($this->stopAfterFindModels))
        {
            $this->listing->addStopAfterFindModel($this->stopAfterFindModels);
        }

        if($cat_id || $dir_id || $criteria_id)
        {
            $this->listing->addCategoryFiltering($queryData['conditions'], $this->access, compact('children','cat_id','dir_id','criteria_id'));
        }
        else {
            $this->listing->addCategoryFiltering($queryData['conditions'], $this->access);
        }

        if($this->published !== null)
        {
            $state = $this->published;

            if ($state) {
                $this->listing->addListingFiltering($queryData['conditions'], $this->access, compact('state'));
            }
            else {
                $this->listing->addListingFiltering($queryData['conditions'], $this->access, compact('state', 'user_id'));
            }
        }
        elseif(!is_null($user_id)) {

            $this->listing->addListingFiltering($queryData['conditions'], $this->access, compact('user_id'));
        }

        foreach($queryData['conditions'] AS $key => $condition)
        {
            // Need to do the replacements even if value is zero to avoid database query errors

            $queryData['conditions'][$key] = str_replace('{user_id}', $user_id, $condition);

            $queryData['conditions'][$key] = str_replace('{listing_id}', $listing_id, $queryData['conditions'][$key]);
        }

        $this->setQueryData($queryData);

        return $this;
    }

    function search($scope, $keywords, $fields, $searchFilters  = array())
    {
        if($keywords != '' && empty($scope) && empty($fields))
        {
            $this->simpleSearch($scope, $keywords, $fields, $searchFilters);
        }
        else {
            $this->advancedSearch($scope, $keywords, $fields, $searchFilters);
        }

        return $this;
    }

    function simpleSearch($scope, $keywords, $fields, $searchFilters = array())
    {
        $keywords = urldecode($keywords);

        $userRatings = Sanitize::getVar($searchFilters, 'userRatings');

        $editorRatings = Sanitize::getVar($searchFilters, 'editorRatings');

        $queryType = Sanitize::getString($searchFilters, 'queryType');

        $author = urldecode(Sanitize::getString($searchFilters, 'author'));

        $this->processRatingConditions($userRatings, $editorRatings)
                ->processKeywordsConditions($queryType, $scope, $keywords)
                ->processAuthorConditions($author);

        return $this;
    }

    function advancedSearch($scope, $keywords, $fields, $searchFilters = array())
    {
        $keywords = urldecode($keywords);

        $userRatings = Sanitize::getVar($searchFilters, 'userRatings');

        $editorRatings = Sanitize::getVar($searchFilters, 'editorRatings');

        $queryType = Sanitize::getString($searchFilters, 'queryType');

        $author = urldecode(Sanitize::getString($searchFilters, 'author'));

        $tag = Sanitize::getVar($searchFilters, 'tag');

        $order = Sanitize::getString($searchFilters, 'order');

        $this->processRatingConditions($userRatings, $editorRatings);

        $scope = $scope ?: $this->listing->_SIMPLE_SEARCH_FIELDS;

        $this->processFieldsConditions($fields, $tag, $order)
                ->processKeywordsConditions($queryType, $scope, $keywords, true)
                ->processAuthorConditions($author);
    }

    function matchAllFields($usematch, $fields)
    {
        $this->usematch = $usematch;

        $this->matchAllFields = array_filter($fields);

        return $this;
    }

    function orderBy($sort, $limitResults = false)
    {
        $queryData = $this->getQuerydata();

        if(empty($this->listing->order) && empty($queryData['order']))
        {
            $this->listing->processSorting($sort, $limitResults);
        }

        return $this;
    }

    function whereListingId($listingId)
    {
        if($listingId = cleanIntegerCommaList($listingId))
        {
            $this->where('Listing.' . EverywhereComContentModel::_LISTING_ID . ' IN (' . $listingId . ')');
        }

        return $this;
    }

    function whereListingSlug($slug)
    {
        if($slug)
        {
            $this->where('Listing.' . EverywhereComContentModel::_LISTING_SLUG . ' = ' . $this->c->Quote($slug));
        }

        return $this;
    }

    function whereCatId($catId, $children = true)
    {
        if($catId = cleanIntegerCommaList($catId))
        {
            $this->listing->addCategoryFiltering($this->queryData['conditions'], $this->access, array('children' => $children, 'cat_id' => $catId));
        }

        return $this;
    }

    function whereDirId($dirId)
    {
        if($dirId = cleanIntegerCommaList($dirId))
        {
            $this->listing->addCategoryFiltering($this->queryData['conditions'], $this->access, array('dir_id' => $dirId));
        }

        return $this;
    }

    function whereDirIdNotIn($dirId)
    {
        if($dirId = cleanIntegerCommaList($dirId))
        {
            $this->listing->excludeDirectoryFiltering($this->queryData['conditions'], $dirId);
        }

        return $this;
    }

    function whereListingTypeId($listingTypeId)
    {
        if($listingTypeId = cleanIntegerCommaList($listingTypeId))
        {
            $this->listing->addCategoryFiltering($this->queryData['conditions'], $this->access, array('criteria_id' => $listingTypeId));
        }

        return $this;
    }

	function processSearchScope($scopeParam)
	{
		$scope = array();

        $scopeParam = array_filter(explode(self::$urlSeparator,$scopeParam));

        foreach($scopeParam AS $key=>$term)
        {
            switch($term) {

                case 'title':
                case 'introtext':
                case 'fulltext':

                    $scope[$term] = $this->listing->_SIMPLE_SEARCH_FIELDS[$term];

                    break;

                default:

                    unset($scope[$term]);

                    break;
            }
        }

        $this->scope = $scope;

        return $scope;
	}

    function buildFieldsParamsArray(& $request, $queryString = '')
    {
        $fieldNameArray = array();

        $customFields = $this->field->getFieldNames('listing',array('published'=>1));

        if($tag = Sanitize::getVar($request,'tag'))
        {
            $click2search_field = substr($tag['field'],0,3) == 'jr_' ? $tag['field'] : 'jr_'.$tag['field'];

            if(!in_array($click2search_field, $customFields))
            {
                return cmsFramework::raiseError(404, s2Messages::submitErrorGeneric());
            }

            if($menu_id = Sanitize::getInt($request,'Itemid'))
            {
                $menuParams = $this->menu->getMenuParams($menu_id);

                $action = Sanitize::getString($menuParams, 'action');

                // If it's an adv. search menu and click2search url, use the menu criteria id
                switch($action) {

                    case '0':

                        if(Sanitize::getInt($menuParams, 'dirid') > 0)
                        {
                            $request['dir'] = Sanitize::getString($menuParams, 'dirid');
                        }

                        break;

                    case '2':

                        !isset($request['cat']) && $request['cat'] = Sanitize::getString($menuParams, 'catid');

                        break;

                    case '11':

                        $request['criteria'] = Sanitize::getString($menuParams, 'criteriaid');

                        break;

                    default:

                        break;
                }

            }

            // Field value underscore fix: remove extra menu parameter not removed in routes regex

            $tag['value'] = preg_replace(array('/_m[0-9]+$/','/_m$/','/_$/'),'',$tag['value']);

            // Below is included fix for dash to colon change in J1.5

            $queryString = $click2search_field. _PARAM_CHAR .str_replace(':','-',$tag['value']) . '/'.$queryString;
        }

        $urlArray = explode ("/", $queryString);

        // Include external parameters for custom fields - this is required for components such as sh404sef

        foreach ($request AS $varName => $varValue)
        {
            if(!is_array($varValue))
            {
                if(substr($varName,0,3) == "jr_" && false === array_search($varName . _PARAM_CHAR . $varValue, $urlArray))
                {
                    $urlArray[] = $varName . _PARAM_CHAR . $varValue;
                }
            }
        }

        foreach ($urlArray as $urlParam)
        {
            // Fixes issue where colon separating field name from value gets converted to a dash by Joomla!
            if(preg_match('/^(jr_[a-z0-9]+)-([\S\s]*)/',$urlParam,$matches))
            {
                $key = $matches[1];

                $value = $matches[2];
            }
            else {
                $param = explode (":", $urlParam);

                $key = $param[0];

                $value = Sanitize::getVar($param,'1',null); // '1' is the key where the value is stored in $param
            }

            if (substr($key,0,3)=="jr_" && in_array($key,$customFields) && !is_null($value) && $value != '')
            {
                $fieldNameArray[$key] = $value;
            }
        }

        $this->fieldsParamsArray = $fieldNameArray;

        return $fieldNameArray;
    }

    protected function processRatingConditions($userRatingsParams, $editorRatingsParams)
    {
        $queryData = & $this->queryData;

        if(!is_array($userRatingsParams))
        {
            $userRatingsParams = array($userRatingsParams);
        }

        if(!is_array($editorRatingsParams))
        {
            $editorRatingsParams = array($editorRatingsParams);
        }

        $userRatingsParams = array_filter($userRatingsParams);

        $editorRatingsParams = array_filter($editorRatingsParams);

        if(!empty($userRatingsParams))
        {
            foreach($userRatingsParams AS $user_rating)
            {
                $user_rating = explode(',',$user_rating);

                $user_rating_value = Sanitize::getInt($user_rating,0);

                if(($this->config->rating_scale > 5 && $user_rating_value < 5) || ($user_rating_value > $this->config->rating_scale))
                {
                    $user_rating_value = min(4,$user_rating_value) * ($this->config->rating_scale/5);
                }

                if(count($user_rating) == 1 && $user_rating_value)
                {
                    $queryData['conditions'][] = "Totals.user_rating >= " . $user_rating_value;
                }
                else {

                    $user_rating_criteria_id = Sanitize::getInt($user_rating,1);

                    if($user_rating_criteria_id)
                    {
                        $table_alias = 'ListingRatingUser';

                        $this->listing->joins[$table_alias] = "LEFT JOIN #__jreviews_listing_ratings AS ".$table_alias." ON ".$table_alias.".listing_id = Listing." . EverywhereComContentModel::_LISTING_ID . " AND ".$table_alias.".extension = 'com_content'";

                        $queryData['conditions'][] = $table_alias . '.user_rating >= ' . $user_rating_value;

                        $queryData['conditions'][] = $table_alias . '.criteria_id = ' . $user_rating_criteria_id;
                    }
                }
            }
        }

        if(!empty($editorRatingsParams))
        {
            foreach($editorRatingsParams AS $editor_rating)
            {
                $editor_rating = explode(',',$editor_rating);

                $editor_rating_value = Sanitize::getInt($editor_rating,0);

                if(($this->config->rating_scale > 5 && $editor_rating_value < 5) || ($editor_rating_value > $this->config->rating_scale))
                {
                    $editor_rating_value = min(4,$editor_rating_value) * ($this->config->rating_scale/5);
                }

                if(count($editor_rating) == 1 && $editor_rating_value)
                {
                    $queryData['conditions'][] = "Totals.editor_rating >= " . $editor_rating_value;
                }
                else {

                    $editor_rating_criteria_id = Sanitize::getInt($editor_rating,1);

                    if($editor_rating_criteria_id)
                    {
                        $table_alias = 'ListingRatingEditor';

                        $this->listing->joins[$table_alias] = "LEFT JOIN #__jreviews_listing_ratings AS ".$table_alias." ON ".$table_alias.".listing_id = Listing." . EverywhereComContentModel::_LISTING_ID . " AND ".$table_alias.".extension = 'com_content'";

                        $queryData['conditions'][] = $table_alias . '.editor_rating >= ' . $editor_rating_value;

                        $queryData['conditions'][] = $table_alias . '.criteria_id = ' . $editor_rating_criteria_id;
                    }
                }
            }
        }

        return $this;
    }

    protected function processFieldsConditions($fields, $tag, $order)
    {
        $queryData = & $this->queryData;

        if(!empty($fields))
        {
            $query = '
                SELECT
                    name, type
                FROM
                    #__jreviews_fields
                WHERE
                    name IN (' .$this->c->Quote(array_keys($fields)) . ')'
                ;

            $fieldTypesArray = $this->field->query($query, 'loadAssocList', 'name');
        }

        $OR_fields = array("select","radiobuttons"); // Single option

        $AND_fields = array("selectmultiple","checkboxes","relatedlisting"); // Multiple option

        // August 22, 2016 - The two arrays below are used to determine how the condition for the queries is written
        // The two above are used to determine how the values are grouped into AND or OR statements

        $SINGLE_OPTION_fields = array('select', 'radiobuttons');

        $MULTIPLE_OPTION_fields = array('selectmultiple', 'checkboxes', 'relatedlisting');

        foreach ($fields AS $key=>$value)
        {
            $fname = $key;

            $searchValues = explode(self::$urlSeparator, $value);

            $fieldType = $fieldTypesArray[$key]['type'];

            // Process values with separator for multiple values or operators. The default separator is an underscore
            if (substr_count($value, self::$urlSeparator)) {

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

                        $low = is_numeric($searchValues[1]) ? $searchValues[1] : $this->c->Quote($searchValues[1]);

                        $high = is_numeric($searchValues[2]) ? $searchValues[2] : $this->c->Quote($searchValues[2]);

                        $queryData['conditions'][] = "\n Field." .$key . " BETWEEN " . $low . ' AND ' . $high;
                    }
                    else {

                        if ($searchValues[1] == "date")
                        {
                            $searchValues[1] = low($searchValues[2]) == 'today' ? _TODAY : $searchValues[2];
                        }

                        $value = is_numeric($searchValues[1]) ? $searchValues[1] : $this->c->Quote($searchValues[1]);

                        $queryData['conditions'][] = "\n Field." .$key . $allowedOperators[$operator] . $value;
                    }
                }
                else {
                    // This is a field with pre-defined options
                    $whereFields = array();

                    if(isset($tag) && $key = 'jr_'.$tag['field'])
                    {
                        // Field value underscore fix
                        if(in_array($fieldType, $SINGLE_OPTION_fields))
                        {
                            $whereFields[] = "Field." . $key . " = '*" . $this->c->Quote('*'.urldecode($value).'*');
                        }
                        else {
                            $whereFields[] = "Field." . $key . " LIKE " . $this->c->Quote('%*'.urldecode($value).'*%');
                        }
                    }
                    elseif(!empty($searchValues))
                    {
                        foreach ($searchValues as $value)
                        {
                            $searchValue = urldecode($value);

                            if(in_array($fieldType, $SINGLE_OPTION_fields))
                            {
                                $whereFields[] = "Field." . $key . " = " . $this->c->Quote('*'.$value.'*') ;
                            }
                            else {
                                $whereFields[] = "Field." . $key . " LIKE " . $this->c->Quote('%*'.$value.'*%');
                            }
                        }
                    }

                    // April 10, 2016 - Changed the parenthesis below because for the OR statements
                    // the conditions where not being grouped together inside the same parenthesis

                    // Nov 8, 2016 - Implemented selective match types for individual fields based on request parameters

                    // Multiple option field

                    if (in_array($fieldType, $AND_fields) && (!$this->usematch || ($this->usematch && in_array($fname, $this->matchAllFields))))
                    {
                        $queryData['conditions'][] = '(' . implode( ' AND ', $whereFields ) . ')';
                    }

                    // Single option field

                    elseif (in_array($fieldType, $OR_fields) || $this->usematch)
                    {
                        $queryData['conditions'][] = '(' . implode( ' OR ', $whereFields ) . ')';
                    }
                }
            }
            else {

                $value = urldecode($value);

                $whereFields = array();

                switch($fieldType) {

                    case in_array($fieldType, $SINGLE_OPTION_fields):

                        $whereFields[] = "Field." . $key . " = ".$this->c->Quote('*'.$value.'*') ;

                    break;

                    case in_array($fieldType, $MULTIPLE_OPTION_fields):

                        $whereFields[] = "Field." . $key . " LIKE ".$this->c->Quote('%*'.$value.'*%');

                    break;

                    case 'decimal':

                        $whereFields[] = "Field." . $key . " = " . (float) $value;

                    break;

                    case 'integer':

                        $whereFields[] = "Field." . $key . " = " . (int) $value;

                    break;

                    case 'date':

                        $begin_week = date('Y-m-d', strtotime('monday this week'));

                        $end_week = date('Y-m-d', strtotime('monday this week +6 days'));

                        $begin_month = date('Y-m-d',mktime(0, 0, 0, date('m'), 1));

                        $end_month = date('Y-m-t', strtotime('this month'));

                        $lastseven = date('Y-m-d', strtotime('-1 week'));

                        $lastthirty = date('Y-m-d', strtotime('-1 month'));

                        $nextseven = date('Y-m-d', strtotime('+1 week'));

                        $nextthirty = date('Y-m-d', strtotime('+1 month'));

                        switch($value) {

                            case 'future':
                                $whereFields[] = "Field." . $key . " >= " . $this->c->Quote(_TODAY);
                                $order == '' and $this->listing->order = array($key . ' ASC');
                            break;
                            case 'today':
                                $whereFields[] = "Field." . $key . " BETWEEN " . $this->c->Quote(_TODAY) . ' AND ' . $this->c->Quote(_END_OF_TODAY);
                                $order == '' and $this->listing->order = array($key . ' ASC');
                            break;
                            case 'week':
                                $whereFields[] = "Field." . $key . " BETWEEN " . $this->c->Quote($begin_week) . ' AND ' . $this->c->Quote($end_week);
                                $order == '' and $this->listing->order = array($key . ' ASC');
                            break;
                            case 'month':
                                $whereFields[] = "Field." . $key . " BETWEEN " . $this->c->Quote($begin_month) . ' AND ' . $this->c->Quote($end_month);
                                $order == '' and $this->listing->order = array($key . ' ASC');
                            break;
                            case '7':
                            case '+7':
                                $whereFields[] = "Field." . $key . " BETWEEN " . $this->c->Quote(_TODAY) . ' AND ' . $this->c->Quote($nextseven);
                                $order == '' and $this->listing->order = array($key . ' ASC');
                            break;
                            case '30':
                            case '+30':
                                $whereFields[] = "Field." . $key . " BETWEEN " . $this->c->Quote(_TODAY) . ' AND ' . $this->c->Quote($nextthirty);
                                $order == '' and $this->listing->order = array($key . ' ASC');
                            break;
                            case '-7':
                                $whereFields[] = "Field." . $key . " BETWEEN " . $this->c->Quote($lastseven) . ' AND ' . $this->c->Quote(_END_OF_TODAY);
                                $order == '' and $this->listing->order = array($key . ' DESC');
                            break;
                            case '-30':
                                $whereFields[] = "Field." . $key . " BETWEEN " . $this->c->Quote($lastthirty) . ' AND ' . $this->c->Quote(_END_OF_TODAY);
                                $order == '' and $this->listing->order = array($key . ' DESC');
                            break;
                            default:
                                $whereFields[] = "Field." . $key . " = " . $this->c->Quote($value);
                            break;
                        }

                    break;

                    default:

                        if(isset($tag) && $key == 'jr_'.$tag['field'] && $fieldType == 'text')
                        {
                           $whereFields[] = "Field." . $key . " = " . $this->c->Quote($value);
                        }
                        else {

                           $whereFields[] = "Field." . $key . " LIKE " . $this->c->QuoteLike($value);
                        }

                    break;
                }

                $queryData['conditions'][] = " (" . implode(  ') AND (', $whereFields ) . ")";
            }
        }

        return $this;
    }

    protected function processKeywordsConditions($queryType, $scope, $keywords, $advancedSearch = false)
    {
        $queryData = & $this->queryData;

        $simplesearch_custom_fields = 1 ; // Search custom fields in simple search

        $queryType = $advancedSearch ? $queryType : Sanitize::getString($this->config,'search_simple_query_type','all'); // any|all

        $min_word_chars = 3; // Only words with min_word_chars or higher will be used in any|all query types

        $ignored_search_words = $keywords != '' ? cmsFramework::getIgnoredSearchWords() : array();

        $fieldNames = array();

        if(empty($scope))
        {
            $scope = $this->listing->_SIMPLE_SEARCH_FIELDS;
        }

        if(EverywhereComContentModel::_LISTING_METAKEY != '')
        {
            $scope['metakey'] = EverywhereComContentModel::_LISTING_METAKEY;
        }

        $words = array_unique(explode( ' ', $keywords));

        // Include custom fields

        if(!$advancedSearch && $simplesearch_custom_fields == 1)
        {
            $fieldNames = $this->field->getTextBasedFieldNames();

            $fieldNames = array_combine($fieldNames, $fieldNames);

            // Add the 'Field.' column alias so it's used in the query

            if(!empty($fieldNames))
            {
                array_walk($fieldNames, function(&$item) {
                    $item = 'Field.' . $item;
                });
            }
            // TODO: find out which fields have predefined selection values to get the searchable values instead of reference

            // Merge standard fields with custom fields
            $scope = array_merge($scope, $fieldNames);
        }

        $whereFields = array();

        $allowedContentFields = array_merge(array('title','introtext','fulltext','reviews','metakey'), array_keys($fieldNames));

        // Only add meta keywords if the db column exists

        if(EverywhereComContentModel::_LISTING_METAKEY != '')
        {
            $scope['metakey'] = EverywhereComContentModel::_LISTING_METAKEY;
        }

        switch ($queryType)
        {
            case 'exact':

                foreach ($scope as $scope_key => $contentfield)
                {
                    if (in_array($scope_key,$allowedContentFields))
                    {
                        $w = array();

                        if ($contentfield == 'reviews')
                        {
                            $w[] = " Review.comments LIKE " . $this->c->QuoteLike($keywords);

                            $w[] = " Review.title LIKE " . $this->c->QuoteLike($keywords);
                        }
                        else {

                            $w[] = " $contentfield LIKE ".$this->c->QuoteLike($keywords);
                        }

                        $whereContentOptions[]     = "\n" . implode( ' OR ', $w);
                    }
                }

                $queryData['conditions'][] = implode( ' OR ', $whereContentOptions);

                break;

            case 'any':
            case 'all':
            default:

                $words = array_unique(explode( ' ', $keywords));

                $whereFields = array();

                foreach ($scope as $scope_key=>$contentfield)
                {
                    if (in_array($scope_key, $allowedContentFields)) {
                    {
                        $whereContentFields = array();

                        $whereReviewComment = array();

                        $whereReviewTitle = array();

                        foreach ($words as $word)
                        {
                            if (strlen($word) >= $min_word_chars && !in_array($word,$ignored_search_words))
                            {
                                if($contentfield == 'reviews')
                                {
                                    $whereReviewComment[] = "Review.comments LIKE " .$this->c->QuoteLike($word);

                                    $whereReviewTitle[] = "Review.title LIKE ".$this->c->QuoteLike($word);
                                }
                                else {

                                    $whereContentFields[] = "$contentfield LIKE ".$this->c->QuoteLike($word);
                                }
                            }
                        }

                        if ($contentfield == 'reviews')
                        {
                            if (!empty($whereReviewTitle))
                            {
                                $whereFields[] = "\n(" . implode( ($queryType == 'all' ? ') AND (' : ') OR ('), $whereReviewTitle ) . ")";
                            }

                            if (!empty($whereReviewComment))
                            {
                                $whereFields[] = "\n(" . implode( ($queryType == 'all' ? ') AND (' : ') OR ('), $whereReviewComment ) . ")";
                            }
                        }
                        elseif (!empty($whereContentFields)) {

                            $whereFields[] = "\n(" . implode( ($queryType == 'all' ? ') AND (' : ') OR ('), $whereContentFields ) . ")";
                        }

                    }
                }
            }

            if(!empty($whereFields))
            {
                $queryData['conditions'][] = '(' . implode(  ' OR ', $whereFields ) . ')';

                if(in_array('reviews', $scope))
                {
                    $queryData['joins'][] = 'LEFT JOIN #__jreviews_comments AS Review ON Listing.' . EverywhereComContentModel::_LISTING_ID . ' = Review.pid AND Review.published = 1 AND Review.mode = "com_content"';

                    // Group By required due to one to many relationship between listings => reviews table

                    $queryData['group'][] = 'Listing.' . EverywhereComContentModel::_LISTING_ID;
                }
            }

            break;
        }

        return $this;
    }

    protected function processAuthorConditions($author)
    {
        $queryData = & $this->queryData;

        // Process author field
        if ($author && $this->config->search_item_author)
        {
            if(_CMS_NAME == 'joomla')
            {
                $queryData['conditions'][] = "
                    (
                        User." . UserModel::_USER_REALNAME . " LIKE ".$this->c->QuoteLike($author)." OR
                        User." . UserModel::_USER_ALIAS . " LIKE ".$this->c->QuoteLike($author)." OR
                        Listing." . EverywhereComContentModel::_LISTING_AUTHOR_ALIAS . " LIKE ".$this->c->QuoteLike($author) .
                    ")";
            }
            else
            {
                $queryData['conditions'][] = "
                    (
                        User." . UserModel::_USER_REALNAME . " LIKE ".$this->c->QuoteLike($author)." OR
                        User." . UserModel::_USER_ALIAS . " LIKE ".$this->c->QuoteLike($author) .
                    ")";
            }

            $queryData['joins'][] = "LEFT JOIN #__users AS User ON User." . UserModel::_USER_ID . " = Listing." . EverywhereComContentModel::_LISTING_USER_ID;
        }

        return $this;
    }
}