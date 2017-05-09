<?php
/**
 * JReviews - Reviews Extension
 * Copyright (C) 2010-2017 ClickFWD LLC
 * This is not free software, do not distribute it.
 * For licencing information visit https://www.jreviews.com
 * or contact sales@jreviews.com
**/

defined( 'MVC_FRAMEWORK') or die( 'Direct Access to this location is not allowed.' );

use S2Framework\Libs\Shortcode;

S2App::import('Helper', array('rating', 'form', 'custom_fields'));

class AdvancedFilterThemeComponent extends S2Component {

	protected $c;

	protected $config;

    protected $field;

    protected $category;

    protected $listingType;

    protected $form;

    protected $rating;

    protected $settings;

    protected $autodetectIds;

    protected $customFields;

    protected $catId;

    protected $dirId;

    protected $listingTypeId;

    protected $matchedTags = array();

    static $urlSeparator = '_';

	function startup (& $controller)
	{
		$this->c = & $controller;

		$this->config = & $this->c->Config;

		$this->request = $this->c->params;

		$this->field = $this->c->Field;

		$this->category = $this->c->Category;

		$this->listingType = $this->c->Criteria;

		# Initialize helpers

		$this->form = new FormHelper();

		$this->rating = new RatingHelper();

		$this->customFields = new CustomFieldsHelper();
	}

    function render($theme, $settings, $autodetectIds)
	{
		if (isset($settings['cat_auto']))
		{
        	$settings['autodetect'] = $settings['cat_auto'];
		}

		$this->settings = $settings;

		$this->completeAutodetectIds($autodetectIds);

		$this->config = $this->loadConfigOverride();

		$this->rating->Config = $this->config;

		$this->customFields->Config = $this->config;

		# Replace theme with module theme customization

        $settings_theme_enable = Sanitize::getInt($this->settings,'settings_theme_enable');

        $settings_theme = Sanitize::getVar($this->settings,'settings_theme');

        if($settings_theme_enable && trim($settings_theme) != '')
        {
        	$content = preg_replace('/(<moduletheme>.*?<\/moduletheme>)/si', $settings_theme, $theme);
        }
        else {

        	$content = preg_replace('/(<moduletheme>)(.*?)(<\/moduletheme>)/si', '$2', $theme);
        }

        $shortcode = new Shortcode;

        $content = $shortcode->setTag('reset')->setContent($content)->replace(array($this, 'replaceReset'));

        $content = $shortcode->setTag('filtergroup')->setContent($content)->replace(array($this, 'replaceFilterGroup'));

        $content = $shortcode->setTag('filter')->setContent($content)->replace(array($this, 'replaceFilter'));

        $content = $shortcode->setTag('hiddeninputs')->setContent($content)->replace(array($this, 'replaceHiddenInputs'));

		return $content;
	}

    public function replaceReset($match)
    {
        $attr = Shortcode::getAttributes($match[3]);

        $label = Sanitize::getString($attr, 'label', JreviewsLocale::getPHP('FILTERS_CLEAR_ALL'));

        $displayAs = Sanitize::getString($attr, 'display_as', 'link');

        $displayAsCss = $displayAs == 'button' ? 'jrButton' : '';

    	return sprintf('<div class="jrFieldDiv"><a href="javascript:;" class="jr-filters-reset jrFiltersClearAll %s">%s</a></div>', $displayAsCss, $label);
    }

	public function replaceFilterGroup($match)
	{
		$output = '';

        $attr = Shortcode::getAttributes($match[3]);

        $showFilter = $this->shouldShowFilter($attr);

		if (!$showFilter) return '';

        return $match[5];
    }

	public function replaceFilter($match)
	{
		$output = '';

        $attr = Shortcode::getAttributes($match[3]);

        $name = Sanitize::getString($attr, 'name');

        $this->matchedTags[] = $name;

		switch ($name)
		{
			case 'keywords':
				$output = $this->replaceKeywords($attr);
			break;

			case 'rating':
				$output = $this->replaceRating($attr);
			break;

			case 'categories':
				$output = $this->replaceCategories($attr);
			break;

			case 'listing_types':
				$output = $this->replaceListingTypes($attr);
			break;

			case substr($name, 0, 3) == 'jr_':
				$output = $this->replaceCustomFields($attr);
			break;
		}

		return $output;
	}

	/**
	 * Ensure that there's at least one ID that can be used to detemine which filters to show on the form
	 * @param  [type] $match [description]
	 * @return [type]        [description]
	 */
	public function replaceHiddenInputs($match)
	{
		$autodetect = Sanitize::getBool($this->settings, 'autodetect');

		$catId = $listingTypeId = $dirId = null;

		$output = '';

		if ($autodetect)
		{
			if (!in_array('categories', $this->matchedTags) && !in_array('listing_types', $this->matchedTags))
			{
	        	if ($this->catId > 0)
	        	{
	        		$output .= sprintf('<input type="hidden" name="data[categories]" value="%d" />', $this->catId);
	        	}
	        	elseif ($this->listingTypeId > 0) {
					$output .= sprintf('<input type="hidden" name="data[Search][criteria_id]" value="%d" />', $this->listingTypeId);
	        	}
			}

        	if ($this->dirId > 0 /*&& !$this->catId*/)
        	{
        		$output .= sprintf('<input type="hidden" name="data[dir]" value="%d" />', $this->dirId);
        	}
		}
		else {
			// If any of the basic filters (directory, listing type or category) are specified in the settings, but the
			// category and listing type shortcodes are not used, then we add the values as hidden inputs

	        if ($settingCatId = Sanitize::getVar($this->settings, 'cat_id')) {
	        	$catId = $settingCatId;
	        }
		    elseif ($settingListingTypeId = Sanitize::getVar($this->settings, 'listing_type_id')) {
	        	$listingTypeId = $settingListingTypeId;
			}
			elseif ($settingDirId = Sanitize::getVar($this->settings, 'dir_id')) {
			    $dirId = $settingDirId;
	        }

	        if ($catId && !in_array('categories', $this->matchedTags))
	        {
	        	$output .= sprintf('<input type="hidden" name="data[categories][]" value="%s" />', implode(',',$catId));
	        }

	        if ($listingTypeId && !in_array('listing_types', $this->matchedTags))
	        {
	        	$output .= sprintf('<input type="hidden" name="data[Search][criteria_id]" value="%s" />', implode(',',$listingTypeId));
	        }

	        if ($dirId && !in_array('categories', $this->matchedTags))
	        {
	        	$output .= sprintf('<input type="hidden" name="data[dir]" value="%s" />', implode(',', $dirId));
	        }
		}

        return $output;
	}

	public function replaceListingTypes($attr)
	{
		$label = Sanitize::getString($attr, 'label');

        $displayAs = Sanitize::getString($attr,'display_as','link');

        $splitList = Sanitize::getInt($attr,'split_list',1);

        $listingTypeId = '';

	    if ($settingListingTypeId = Sanitize::getVar($this->settings, 'listing_type_id')) {
        	$listingTypeId = $settingListingTypeId;
		}

        $listingTypes = $this->listingType->getSelectList(array('id'=>$listingTypeId,'searchOnly'=>true));

        if (count($listingTypes) == 1)
        {
        	$type = array_shift($listingTypes);

        	return '<input type="hidden" name="data[Search][criteria_id]" value="'.$type->value.'" />';
        }

		$requestTypeId = Sanitize::getInt($this->request, 'criteria');

		$typeOptions = array_merge(array(array('value'=>'','text'=>'')),$listingTypes);

        $input = $this->form->select(
            'data[Search][criteria_id]',
            $typeOptions,
            $requestTypeId ?: $this->listingTypeId,
            array(
            	'data-display-as' => $displayAs,
            	'data-reload' => 1,
            	'data-split-list' => $splitList,
            	'option_attr_by_key' => array(
            		array('attr' => 'data-listing-type', 'key' => 'value')
            	)
            )
        );

		return $this->renderFilterInput('jr_listing_type', $input, $attr);
	}

	public function replaceCategories($attr)
	{
        $dirId = $catId = $listingTypeId = '';

		// Set the default shortcode attributes

		$label = Sanitize::getString($attr, 'label');

        $displayAs = Sanitize::getString($attr,'display_as','link');

        $splitList = Sanitize::getInt($attr,'split_list',0);

        $allowDeselect = Sanitize::getInt($attr,'deselect',0);

        $settingCatId = $settingDirId = $settingListingTypeId = array();

        // Check the module settings to filter the initial category list

        if (!$this->settings['autodetect'])
        {
	        if ($settingCatId = Sanitize::getVar($this->settings, 'cat_id')) {
	        	$catId = $settingCatId;
	        }
		    elseif ($settingListingTypeId = Sanitize::getVar($this->settings, 'listing_type_id')) {
	        	$listingTypeId = $settingListingTypeId;
			}
			elseif ($settingDirId = Sanitize::getVar($this->settings, 'dir_id')) {
			    $dirId = $settingDirId;
	        }
        }

        // Intersect the module settings with the autodetect IDs or IDs in the request

		$requestCatId = Sanitize::getString($this->request, 'cat', $this->catId);

		$requestDirId = Sanitize::getString($this->request, 'dir', $this->dirId);

		$requestListingTypeId = Sanitize::getString($this->request, 'criteria', $this->listingTypeId);

        // - Request dir always overrides other values
		if ($requestDirId)
		{
			// $dirId = $requestDirId;
		}
        // - Autodetect enabled and have a dir id, then use it
		// else
			if ($this->settings['autodetect'] && $requestDirId) {
			$dirId = $this->dirId;
		}
		elseif ($this->settings['autodetect'] && !$this->dirId && !$this->catId && $requestListingTypeId)
		{
			$listingTypeId = $settingListingTypeId = $requestListingTypeId;
		}

        $options = array_filter(array(
            'cat_id'=>$catId,
            'parent_id'=>$catId,
            'dir_id'=>$dirId,
            'type_id'=>$listingTypeId,
            'level'=>1
        ));

        $options['indent'] = false;
        $options['disabled'] = false;

        // If one or more categories or directories are specified via settings we display the category tree for them
        // Unless there's a catId in the request. Then we just get the first level categories because the whole branch for the selected
        // category will be added below

        if ($settingListingTypeId)
        {
        	unset($options['level']);
        	unset($options['parent_id']);
        }

        $categories = $this->category->getCategoryList($options);

        // Listing type specified via settings

        if ($settingListingTypeId)
        {
        	if ($requestCatId)
        	{
	        	$options['level'] = 1;
	        	unset($options['type_id']);
        	}

        	// We need to get all parent categories

        	$this->getParentCategories($categories, $options);

        	// If the request is not for a specific category ID, then we leave only first level categories in the list

        	if (!$requestCatId || !isset($categories[$requestCatId]))
        	{
        		foreach ($categories AS $key => $category)
        		{
        			if ($category->level > 1)
        			{
        				unset($categories[$key]);
        			}
        		}
        	}
        }

        $oneCategory = false;

        if (count($categories) == 1)
        {
        	$optionsCopy = $options;

        	unset($options['level']);

			$cat = reset($categories);

			if ($cat->criteriaid == 0)
			{
        		$oneCategory = true;

				$categories = $this->category->getCategoryList($options);
			}

			$options = $optionsCopy;
        }

        foreach ($categories AS $key => $category)
        {
        	if ($category->level > 1)
        	{
        		$categories[$key]->text = '└'.str_repeat('─',$category->level-2).' '.$category->text;
        	}
        }

        // If a specific category was requested or auto-detected we add the whole branch for it's parent category

        if (!$oneCategory && $requestCatId)
        {
        	$catId = $requestCatId;

        	$parentCategories = $this->category->getParentList($catId);

    		if ($parentCategories)
    		{
    			$parent = reset($parentCategories);

    			$selectedBranch = $this->category->getChildrenList($parent->value);

    			$key = $parent->value;

        		$position = self::findCategoryIndex($categories, $key);

        		if ($position !== false)
        		{
        			array_splice($categories, $position, 1, $selectedBranch);
        		}
    		}
        }

        if (!$requestCatId || !isset($categories[$requestCatId]))
        {
			$categories = array_merge(array((object) array('value'=>'','text'=>JreviewsLocale::getPHP('LISTING_SELECT_CAT'))),$categories);
        }

        $input = $this->form->select(
            'data[categories]',
            $categories,
            $requestCatId ?: $this->catId,
            array(
            	'data-display-as' => $displayAs,
            	'data-reload' => 1,
            	'data-split-list' => $splitList,
            	'data-deselect' => $allowDeselect,
            	'option_attr_by_key' => array(
            		array('attr' => 'data-listing-type', 'key' => 'criteriaid')
            	)
            )
        );

		return $this->renderFilterInput('jr_category', $input, $attr);
	}

	private function getParentCategories(& $categories, $options)
	{
        $currentCatIds = array_unique(S2Array::pluck($categories, 'value'));

        $parentCategories = $this->category->findParents($currentCatIds);

        $catIds = array();

        foreach ($parentCategories AS $cat)
        {
        	if ($cat['Category']['level'] == 1)
        	{
        		$catIds[] = $cat['Category']['cat_id'];
        	}
        }

        if ($catIds)
        {
        	$options['cat_id'] = $catIds;

        	$categories = $this->category->getCategoryList($options);
        }
	}

	public function replaceKeywords($attr)
	{
		$label = Sanitize::getString($attr, 'label');

		$value =  Sanitize::getString($this->request,'keywords','',true);

		$placeholder = Sanitize::getString($attr, 'placeholder', $label);

        $reset = Sanitize::getString($attr,'reset',0);

		$input = $this->form->text('data[keywords]', array(
			'value'=>$value,
			'placeholder'=>$placeholder,
			'data-display-as'=>'text',
			'data-reset'=>$reset
			));

		return $this->renderFilterInput('jr_keywords', $input, $attr);
	}

	public function replaceCustomFields($attr)
	{
        $fname = Sanitize::getString($attr,'name');

		$label = Sanitize::getString($attr, 'label');

        $placeholder = Sanitize::getString($attr,'placeholder');

        $displayAs = Sanitize::getString($attr,'display_as','checkbox');

        $matchType = Sanitize::getString($attr,'match_type','all');

        $preview = Sanitize::getString($attr,'preview',1);

        // Adds the clear link for individual filters
        $reset = Sanitize::getString($attr,'reset',0);

        $splitList = $displayAs == 'linkboxed' ? 0 : Sanitize::getInt($attr,'split_list',1);

        $splitOptionLimit = Sanitize::getInt($attr, 'split_option_limit');

        $showFilter = $this->shouldShowFilter($attr);

        $showAll = Sanitize::getInt($attr, 'show_all', 0);

        $showLimit = Sanitize::getInt($attr, 'show_limit', 5);

        /**
         * When usematch is 0, then multiple option field searh defaults to AND, otherwise it defaults to OR unless the field is included
         * in the matchall parameter
         * @var [type]
         */
        $usematch = Sanitize::getInt($this->request, 'usematch');

        /**
         * Used to specify which fields will be searched using AND, the rest will use OR
         * @var array
         */
        $matchAllFields = array_filter(explode(',',Sanitize::getString($this->request, 'matchall')));

        if (!$showFilter) return '';

        // Build the listing array to pre-select searched values

		$selectedValues = array();

		if (isset($this->request[$fname]))
		{
			$selectedValues = explode(self::$urlSeparator, $this->request[$fname]);
		}

		$field = $this->field->getFieldFromName(
			$fname,
			$location = 'listing',
			$selectedValues,
			array(
				'group_by_groups' => false,
				'load_options' => 'none'
			)
		);

		if ($placeholder != '')
		{
			$field['properties']['description_position'] = 4;
			$field['description'] = $placeholder;
		}

		if ($label == '')
		{
			$attr['label'] = $field['title'];
		}

		$field['data'] = array();

		$field['data']['split-list'] = $splitList;

		$field['data']['reset'] = $reset;

		if ($splitList)
		{
			$field['data']['split-option-limit'] = $splitOptionLimit;
		}

		// Set the default match multiple select and checkboxes

		if (in_array($field['type'], array('checkboxes', 'selectmultiple')))
		{
			// Show the match toggle switch for this filter
        	$field['data']['match-switch'] = Sanitize::getInt($attr,'match_switch',1);

        	if ($usematch && in_array($fname, $matchAllFields)) {
				$field['data']['match-all'] = 1;
			}
			elseif (!$usematch || ($usematch && !in_array($fname, $matchAllFields))) {
				$field['data']['match-all'] = $matchType == 'all' ? 1 : 0;
			}
			else {
				$field['data']['match-all'] = 0;
			}
		}

		$field['properties']['autocomplete.search'] = 0;

		if ($preview == 0)
		{
			$field['data']['preview'] = 0;
		}

		switch ($field['type'])
		{
			case 'select':
			case 'selectmultiple':
			case 'checkboxes':
				$field['type'] = $displayAs == 'select' ? 'select' : 'selectmultiple';
				$field['data']['display-as'] = $displayAs;
				$field['data']['show-all'] = $showAll;
				$field['data']['show-limit'] = $showLimit;
			break;

			case 'radiobuttons':
				$field['type'] = 'selectmultiple';
				$field['data']['display-as'] = $displayAs;
				$field['data']['show-all'] = $showAll;
				$field['data']['show-limit'] = $showLimit;
			break;

			case 'integer':
			case 'decimal':
				$field['data']['options'] = Sanitize::getString($attr, 'options');
				$field['data']['display-as'] = 'numberrange';
			break;

			case 'date':
				$field['data']['display-as'] = 'daterange';
			break;

			case 'text':
			case 'textarea':
				$field['type'] = 'text';
				if ($this->addProximitySearch($fname)) {
					$field['data']['display-as'] = 'geosearch';
				}
				else {
					$field['data']['display-as'] = 'text';
				}
			break;

			case 'relatedlisting':
				$field['data']['display-as'] = 'autosuggest';
			break;
		}

		$input = $this->customFields->renderInputFromField($field, $location = 'listing', $search = 'custom', JreviewsLocale::getPHP('SEARCH_SELECT'));

		if ($field['data']['display-as'] == 'geosearch')
		{
			$input .= $this->addRadiusSearch($attr);
		}

		return $this->renderFilterInput($fname, $input, $attr);
	}

	public function replaceRating($attr)
	{
        $type = Sanitize::getString($attr,'type');

        $label = Sanitize::getString($attr,'label');

        $reset = Sanitize::getString($attr,'reset',0);

		$selectedValues = Sanitize::getVar($this->request,S2_QVAR_RATING_AVG,'');

		if ($type == 'user')
		{
			$name = 'data['.S2_QVAR_RATING_AVG.'][]';
		}
		else {
			$name = 'data['.S2_QVAR_EDITOR_RATING_AVG.'][]';
		}

		$ratingsList = $this->rating->ratingSearchOptions();

		$ratingsList[''] = '';

        $ratingStyle = 'jrRatingsStyle'.Sanitize::getInt($this->config,$type.'_rating_style',1);

        $ratingColor = 'jrRatings'.ucfirst(Sanitize::getString($this->config,$type.'_rating_color','orange'));

		$input = $this->form->select(
			$name,
			array_reverse($ratingsList,true),
			$selectedValues,
			array(
				'class'=>'jrSelect',
				'data-display-as'=>'rating',
				'data-class'=>'jrRatingStars'.ucfirst($type) . ' ' . $ratingStyle . ' ' . $ratingColor,
				'data-reset'=>$reset
			)
		);

		return $this->renderFilterInput('jr_rating', $input, $attr);
	}

	public function addProximitySearch($fname)
	{
		if (class_exists('GeomapsComponent') && Sanitize::getString($this->config, 'geomaps.advsearch_input') == $fname) {
			return true;
		}

		return false;
	}

	protected function addRadiusSearch($attr)
	{
		$radiusMetric = Sanitize::getString($this->config,'geomaps.radius_metric', 'mi');

		$default = Sanitize::getFloat($attr, 'radius_default', 10);

		$min = Sanitize::getFloat($attr, 'radius_min', 10);

		$max = Sanitize::getFloat($attr, 'radius_max', 50);

		$step = Sanitize::getFloat($attr, 'radius_step', 10);

		$radius = Sanitize::getInt($this->request,'jr_radius',$default);

		$radiusInput = sprintf('<input type="hidden" name="data[Field][Listing][jr_radius]" data-min="%d" data-max="%d" data-step="%d" data-metric="%s" value="%d" />', $min, $max, $step, $radiusMetric, $radius);

		return $radiusInput;
	}

	/**
	 * If we have the category id, then we can also get the directory and listing type ids
	 */
	public function completeAutodetectIds($autodetectIds)
	{
		$this->catId = Sanitize::getInt($autodetectIds, 'cat_id');

		$this->dirId = Sanitize::getInt($autodetectIds, 'dir_id');

		$this->listingTypeId = Sanitize::getInt($autodetectIds, 'listing_type_id');

		if ($this->catId)
		{
			if ($category = $this->category->findRow(array('conditions' => array('Category.'.CategoryModel::_CATEGORY_ID.'='.$this->catId))))
			{
				$this->catId = $category['Category']['cat_id'];
				$this->dirId = $category['Directory']['dir_id'];
				$this->listingTypeId = $category['ListingType']['id'];
			}
		}

		return array('cat_id' => $this->catId, 'dir_id' => $this->dirId, 'listing_type_id' => $this->listingTypeId);
	}

	protected function loadConfigOverride()
	{
		$listingType = $this->listingType->getCriteria(array('criteria_id'=>$this->listingTypeId, 'cat_id'=>$this->catId));

		if ($listingType)
		{
			$this->config->override($listingType['ListingType']['config']);
		}

		return $this->config;
	}

	/**
	 * Determine if the filter is shown on the form based on the include/exclude attributes
	 * @param  [type] $attr [description]
	 * @return [type]       [description]
	 */
	protected function shouldShowFilter($attr)
	{
		// If there there aren't any show/hide

		$check = $this->anyIncludeOrExcludeIds($attr);

		if (!$check) return true;

		$show = false;

		// Categories

		$showCat = self::showCheck($attr, 'cat', $this->catId);

		if ($showCat !== null)
		{
			$show = $show || $showCat;
		}

		// Categories

		$showDir = self::showCheck($attr, 'dir', $this->dirId);

		if ($showDir !== null)
		{
			$show = $show || $showDir;
		}

		// Listing types

		$showType = self::showCheck($attr, 'listing_type', $this->listingTypeId);

		if ($showType !== null)
		{
			$show = $show || $showType;
		}

		return $show;
	}

	protected function anyIncludeOrExcludeIds($attr)
	{
		$showCat = self::toArray($attr, 'show_cat');
		$hideCat = self::toArray($attr, 'hide_cat');
		$showDir = self::toArray($attr, 'show_dir');
		$hideDir = self::toArray($attr, 'hide_dir');
		$showListingType = self::toArray($attr, 'show_listing_type');
		$hideListingType = self::toArray($attr, 'hide_listing_type');

		return !empty($showCat)
				||
				!empty($hideCat)
				||
				!empty($showDir)
				||
				!empty($hideDir)
				||
				!empty($showListingType)
				||
				!empty($hideListingType);
	}

	static function showCheck($attr, $type, $id)
	{
		// $fname = $attr['name'];

		$includes = self::toArray($attr, 'show_'.$type);

		$excludes = self::toArray($attr, 'hide_'.$type);

		$show = false;

		if ($id && empty($includes) && empty($excludes))
		{
			$show = null;
		}
		elseif($id) {

			if (!empty($includes) && in_array($id, $includes))
			{
				$show = true;
			}

			if (!empty($excludes) && in_array($id, $excludes))
			{
				$show = false;
			}
		}
		elseif (!$id) {

			$show = null;
		}

		return $show;
	}

	static function findCategoryIndex($categories, $catId)
	{
		$index = 0;

		foreach ($categories AS $cat)
		{
			if ($cat->value == $catId)
			{
				return $index;
			}

			$index++;
		}

		return false;
	}

	static function toArray($attr, $key)
	{
		$value = Sanitize::getString($attr, $key);

		return array_filter(explode(',', $value));
	}

	protected function renderFilterInput($fname, $input, $attr)
	{
		$label = '';

		$filterLabel = Sanitize::getString($attr, 'label');

		$slideoutEnabled = Sanitize::getInt($attr, 'slideout', 1) && !empty($filterLabel);

		$slideoutClass = $slideoutEnabled ? 'jrFilterSlideout' : 'jrFilterNoSlideout';

		$autoOpen = Sanitize::getInt($attr, 'auto_open');

		if (!empty($filterLabel))
		{
			$label = '<div class="jr-filter-label'. (!$slideoutEnabled ? ' jr-no-slideout' : '') .' jrFilterLabel"><h4 class="jrFilterName">'.__t($filterLabel,true).'</h4></div>';
		}

		return '<div class="jr-filter-wrap jrFieldDiv jrFilterFieldWrap '.$slideoutClass. ' jrClear '.lcfirst(Inflector::camelize($fname)).'Filter" data-auto-open="'.$autoOpen.'">'
				.$label
				.$input
				.'</div>'
			;
	}
}