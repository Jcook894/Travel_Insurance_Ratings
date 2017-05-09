<?php
/**
 * JReviews - Reviews Extension
 * Copyright (C) 2010-2015 ClickFWD LLC
 * This is not free software, do not distribute it.
 * For licencing information visit https://www.jreviews.com
 * or contact sales@jreviews.com
**/

defined( 'MVC_FRAMEWORK') or die( 'Direct Access to this location is not allowed.' );

S2App::import('Helper', 'routes', 'jreviews');

class AdvancedSearchRequestComponent extends S2Component {

	protected $c;

	protected $config;

    /**
    * Avoids issue with this chars when passed through Joomla JRoute
    *
    * @var mixed
    */
    var $keywordReplacementMask= array(
        '&'=>'ampersand'
//        ,'#'=>'poundsign'
    );

    var $keywordReplacementUrl = array(
        'ampersand'=>'%26'
//        ,'poundsign'=>'%23'
    );

    var $illegalChars;

    static $urlSeparator = '_';

	function startup (& $controller)
	{
		$this->config = & $controller->Config;

		$this->ajaxRequest = $controller->ajaxRequest;

		$this->routes = new RoutesHelper;

		$this->illegalChars = array('#','/','?',':',urldecode('%E3%80%80')); // Last one is japanese double space
	}

	function process($request, $options = array())
    {
    	$defaults = array('amp_replace' => false);

    	$options = array_merge($defaults, $options);

		$simpleSearch = Sanitize::getInt($request,'simple_search');

		$keywords = Sanitize::getVar($request,'keywords');

		$listingTypeId = isset($request['Search']) ?  str_replace(array(',',' '), array(self::$urlSeparator,''), Sanitize::getString($request['Search'],'criteria_id')) : null;

		$dirId = str_replace(array(',',' '),array(self::$urlSeparator,''),Sanitize::getString($request,'dir'));

        $catId = str_replace(array(',',' '),array(self::$urlSeparator,''),Sanitize::getString($request,'cat'));

		$order = Sanitize::getVar($request,'order');

		$userRating = array_filter(Sanitize::getVar($request,S2_QVAR_RATING_AVG,array()));

		$editorRating = array_filter(Sanitize::getVar($request,S2_QVAR_EDITOR_RATING_AVG,array()));

		$queryType = Sanitize::getVar($request,'search_query_type');

		$scope = Sanitize::getVar($request,'contentoptions',array('title','introtext','fulltext'));

		$author = Sanitize::getString($request,'author');

		$categories = Sanitize::getVar($request,'categories');

		$menu_id = Sanitize::getInt($request,'menu_id');

        $tmplSuffix = Sanitize::getString($request,'tmpl_suffix');

        $fields = $this->extractFields($request);

        $moduleId = Sanitize::getString($request,'module_id');

        $filterId = Sanitize::getString($request,'filter_id');

        $filterId = str_replace('jreviewsfilterswidget-','',$filterId);

		$usematch = Sanitize::getInt($request, 'usematch');

		$matchAllFields = Sanitize::getVar($request, 'matchall', array());

		if (!empty($matchAllFields))
		{
			$matchAllFields = array_keys($matchAllFields);
			$matchAllFields = array_intersect($matchAllFields, array_keys($fields));
		}

		$moduleParams = cmsFramework::getModuleParams($moduleId);

		$scopeParams = Sanitize::getVar($moduleParams, 'scope');

		// Override form scope with module settings if any scope options are selected in the module

		if (!empty($scopeParams))
		{
			$scope = $scopeParams;
		}

		$sort = '';

        // Replace ampersands with temp string to be replaced back as urlencoded ampersand further below
        $keywords = str_replace(array_keys($this->keywordReplacementMask),array_values($this->keywordReplacementMask),$keywords);

		# Get the Itemid
		$menu_id_param = $menu_id > 0 ? $menu_id : false;

		$urlParams = array();

		// If the scope includes all options then we can treat this as simple search because the user
		// has not tried to filter the results by any of the options
		$reference_scope = array_diff(array('title','introtext','fulltext'),$scope);

        // Some logic to turn adv. search into simple search when only the keywords input is used
        if(!$simpleSearch
        		&& empty($reference_scope)
        		&& $keywords != ''
        		&& empty($author)
        		&& empty($fields)) {

        	$catId = is_array($categories) ? implode(self::$urlSeparator,$categories) : $categories;

            $simpleSearch = true;
        }

		# SIMPLE SEARCH

		if ($simpleSearch)
		{
			# Build the query string

			if (trim($keywords) != '')
			{
                $urlParams['keywords'] = str_replace($this->illegalChars,' ',$keywords);
			}

			!empty($listingTypeId) and $urlParams['criteria'] = $listingTypeId;

			!empty($dirId) and $urlParams['dir'] = $dirId;

            !empty($catId) and $urlParams['cat'] = $catId;

            !empty($tmplSuffix) and $urlParams['tmpl_suffix'] = $tmplSuffix;

            !empty($order) and $urlParams['order'] = $order;

			!empty($userRating) and $urlParams[S2_QVAR_RATING_AVG] = $userRating;

			!empty($editorRating) and $urlParams[S2_QVAR_EDITOR_RATING_AVG] = $editorRating;

			$url = $this->routes->search_results($urlParams, $menu_id_param);

            $url = str_replace(array_keys($this->keywordReplacementUrl),array_values($this->keywordReplacementUrl),$url);

	        if($this->ajaxRequest || $options['amp_replace'])
			{
				$url = str_replace('&amp;','&',$url);
			}

	        return $url;
		}

		# ADVANCED SEARCH

		$urlParams = array();

		!empty($listingTypeId) and $urlParams['criteria'] = $listingTypeId;

		// Search query type

		!empty($queryType) and $urlParams['query'] = $queryType;

		!empty($dirId) and $urlParams['dir'] = $dirId;

		!empty($catId) and  $urlParams['cat'] = $catId;

		// Listing and reviews

		if($keywords)
		{
            if($scope)
            {
                $urlParams['scope'] = implode(self::$urlSeparator,$scope);
            }

            $urlParams['keywords'] = str_replace($this->illegalChars,' ',$keywords);
		}

		if($userRating)
		{
			$urlParams[S2_QVAR_RATING_AVG] = $userRating;
		}

		if($editorRating)
		{
			$urlParams[S2_QVAR_EDITOR_RATING_AVG] = $editorRating;
		}

		// Author

        !empty($author) and $urlParams['author'] = $author;

		// Categories

		if (is_array($categories)) {

			// Remove empty values from array
			foreach ($categories as $index => $value) {

			   if (empty($value)) unset($categories[$index]);
			}

			if (!empty($categories))
            {
				$catId = implode(self::$urlSeparator,$categories);

                !empty($catId) and $urlParams['cat'] = $catId;
			}

		}
		elseif($categories != '') { // Single select category list

			!empty($categories) and $urlParams['cat'] = $categories;
		}

		// If category, directory or criteria filters are not set for this query, then check if the module had any specific categories selected

		if (!isset($urlParams['cat']) && !isset($urlParams['dir']) && !isset($urlParams['criteria']))
		{
			if (!empty($moduleParams['cat_id']))
			{
				$urlParams['cat'] = implode(self::$urlSeparator,$moduleParams['cat_id']);
			}
		}

		// First pass to process numeric values, need to merge operator and operand into one parameter

		$searchFields = $fields;

		if(!empty($fields))
		{
			foreach($fields as $key => $field)
			{
				if (substr($key, -9, 9) == '_operator')
				{
					$operator = $field;

					$operand = substr ($key, 0, -9);

					$search_values = Sanitize::getVar($fields, $operand);

					$value1 = is_array($search_values) ? Sanitize::getVar($search_values,0) : '';

					$value2 = is_array($search_values) ? Sanitize::getVar($search_values,1) : '';

					// If it's a between search, make sure both values are filled, otherwise automatically convert to greater than or less than searches

					if($operator == 'between' && is_array($search_values))
					{
						if($value1 && !$value2) {

							$operator = 'higher';

							$search_values = array($value1);
						}
						elseif (!$value1 && $value2) {

							$operator = 'lower';

							$search_values = array($value2);

							$value1 = $value2;
						}
					}

					if(!$value1 && !$value2) {

						$search_values = null;
					}

					if($search_values)
					{
						$search_values = array_filter($search_values);

						if(is_numeric($value1)) {

							$searchFields[$operand] = $operator . self::$urlSeparator . trim(implode(self::$urlSeparator, $search_values));
						}
						else {

	                    	// Assume it's a date field
							$searchFields[$operand] = $operator . self::$urlSeparator . "date_".implode(self::$urlSeparator, $search_values);
						}
					}
					else {

						$searchFields[$operand] = '';
					}

                    // Remove trailing separator char

                    $searchFields[$operand] = rtrim($searchFields[$operand], self::$urlSeparator);
                }
			}

			// Second pass to process everything

			foreach($searchFields as $key=>$value)
			{
				$key_parts = explode(self::$urlSeparator, $key);

				$imploded_value = '';

				if (substr($key,0,3) == "jr_" && substr($key, -9, 9) != '_operator' && Sanitize::getString($key_parts,2) != 'reset') {

					// multiple option field
					if (is_array($value)) {

						if(is_array($value[0]) && !empty($value[0]) ) {

							$imploded_value = implode(self::$urlSeparator,$value[0]);
						}
						elseif(!is_array($value[0]) && implode('',$value) != '') {

							$imploded_value = implode(self::$urlSeparator,$value);
						}

						if($key != '' && $imploded_value != '') {

							$urlParams[$key] = trim($imploded_value);
						}

					// single option field
					}
					elseif ( !is_array($value) && trim($value) != '') {

						$urlParams[$key] = trim($value);
					}
				}
			}
		} // End isset $request['Field']

        !empty($tmplSuffix) and $urlParams['tmpl_suffix'] = $tmplSuffix;

        $usematch == 1 and $urlParams['usematch'] = 1;

        if ($filterId)
        {
        	$urlParams['filter'] = $filterId;
        }

        if (!empty($matchAllFields))
        {
        	$urlParams['matchall'] = implode(',', $matchAllFields);
        }

		$urlParams['order'] = ($order ? $order : $this->config->list_order_default);

		# Remove empty values from array

		foreach ($urlParams as $index => $value)
		{
		   if (empty($value)) unset($urlParams[$index]);
		}

		$url = $this->routes->search_results($urlParams, $menu_id_param);

        $url = str_replace(array_keys($this->keywordReplacementUrl),array_values($this->keywordReplacementUrl),$url);

        if($this->ajaxRequest || $options['amp_replace'])
        {
        	$url = str_replace('&amp;','&',$url);
        }

        return $url;
	}

	protected function extractFields($request)
	{
		if(isset($request['Field']))
		{
			$fields = Sanitize::getVar($request['Field'], 'Listing', array());

			$fields = array_filter($fields);
		}
		else {

			$fields = array();

			foreach($request AS $key => $val)
			{
				if(substr($key, 0, 3) == 'jr_' && !empty($val)) {
					$fields[$key] = $val;
				}
			}
		}

        return $fields;
	}
}