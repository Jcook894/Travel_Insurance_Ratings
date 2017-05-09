<?php
/**
 * JReviews - Reviews Extension
 * Copyright (C) 2010-2015 ClickFWD LLC
 * This is not free software, do not distribute it.
 * For licencing information visit https://www.jreviews.com
 * or contact sales@jreviews.com
**/

defined( 'MVC_FRAMEWORK') or die( 'Direct Access to this location is not allowed.' );

S2App::import('Helper', array('rating', 'form', 'custom_fields'));

class AdvancedSearchThemeComponent extends S2Component {

	protected $c;

	protected $config;

    protected $field;

    protected $category;

    protected $form;

    protected $rating;

    protected $settings;

    protected $ids;

    protected $customFields;

    protected $replacements = array('search' => array(), 'replace' => array());

    protected $selectDisplayType = array();

    protected $selectDisplaySize = array();

    static $defaultMultiselectSize = 5;

    static $urlSeparator = '_';

	function startup (& $controller)
	{
		$this->c = & $controller;

		$this->config = & $this->c->Config;

		$this->request = $this->c->params;

		$this->field = $this->c->Field;

		$this->category = $this->c->Category;

		# Initialize helpers

		$this->form = new FormHelper();

		$this->rating = new RatingHelper();

		$this->rating->Config = $this->config;

		$this->customFields = new CustomFieldsHelper();

		$this->customFields->Config = $this->config;
	}

    function render($theme, $settings, $ids)
	{
		if (isset($settings['cat_auto']))
		{
        	$settings['autodetect'] = $settings['cat_auto'];
		}

		$this->settings = $settings;

		$this->ids = $ids;

		$inputs = $labels = $select = array();

		$find = $replace = array();

		$date_field = false;

		# Replace theme with module theme customization

        $settings_theme_enable = Sanitize::getInt($this->settings,'settings_theme_enable');

        $settings_theme = Sanitize::getVar($this->settings,'settings_theme');

        if($settings_theme_enable && trim($settings_theme) != '')
        {
        	$output = preg_replace('/(<moduletheme>.*?<\/moduletheme>)/si', $settings_theme, $theme);
        }
        else {

        	$output = preg_replace('/(<moduletheme>)(.*?)(<\/moduletheme>)/si','$2',$theme);
        }

		$this->fieldTags = $this->extractTags($output);

		# Process custom field tag attributes
		foreach($this->fieldTags AS $key=>$value)
		{
			$var = explode('|',$value);

			if(!strstr($value,'_label')) {

				$inputs[$var[0]] = $value;

			}
			elseif (strstr($value,'_label')) {

				$labels[] = substr($value,0,-6);
			}

			// Extract tag properties

			$matches = array();

			$pattern = '/(?P<field>[a-z0-9_|]*)(\soptions=)(?P<options>.*)/i';

			if(preg_match($pattern, $value, $matches))
			{
				$var[0] = $matches['field'];
			}

			// Extract tag properties

			switch(Sanitize::getString($var,0))
			{
				case 'keywords':

					$this->keywords();

					break;

				case 'category':

					$this->categories($value, $var);

					break;

				case 'user_rating':

					$this->userRating();

					break;

				case 'editor_rating':

					$this->editorRating();

					break;

				case 'radius':

					$options = !empty($matches['options']) ? explode(',',$matches['options']) : array(1, 5,10,15,20);

					$tag = isset($matches[0]) ? $matches[0] : 'radius';

					$this->radius($tag, $options);

					break;

				default:

					if (isset($var[1]) && strtolower($var[1]) == 'm')
					{
						$this->selectDisplayType[$var[0]] = 'selectmultiple';
					}
					elseif (isset($var[1]) && strtolower($var[1]) == 's') {

						$this->selectDisplayType[$var[0]] = 'select';
					}

					$this->selectDisplaySize[$var[0]] = isset($var[2]) ? $var[2] : self::$defaultMultiselectSize;

					$this->fields($inputs, $labels);

					break;
			}
		}

		if(!empty($this->replacements))
		{
			$output = str_replace($this->replacements['search'], $this->replacements['replace'], $output);
		}

		return $output;
	}

	protected function extractTags($view) {

		$pattern = '/{([a-z0-9_|=,\s]*)}/i';

		$matches = array();

		$result = preg_match_all( $pattern, $view, $matches );

		if( $result == false ) {
			return array();
		}

		return array_unique(array_values($matches[1]));
	}

	protected function addReplacement($search, $replace)
	{
		$this->replacements['search'][] = $search;

		$this->replacements['replace'][] = $replace;
	}

	protected function keywords()
	{
		$value = Sanitize::getString($this->request,'keywords','',true);

		$this->addReplacement('{keywords}', '<input type="text" class="jrKeywords" name="data[keywords]" value="'.$value.'" />');

		$this->addReplacement('{keywords_label}', __t("Keywords",true));
	}

	protected function categories($tag, $var)
	{
        $dirId = $catId = $listingTypeId = '';

        $category_attributes = array();

        $autodetect = Sanitize::getInt($this->settings, 'autodetect');

		if(Sanitize::getString($var,1) == 'm')
		{
			$category_attributes['multiple'] = 'multiple';

			$category_attributes['size'] = Sanitize::getInt($var,2,5);
		}

       # Get module params before auto-detect

        $paramCatId = Sanitize::getVar($this->settings, 'cat_id');

        $paramDirId = Sanitize::getVar($this->settings, 'dir_id');

        $paramListingTypeId = Sanitize::getVar($this->settings, 'criteria_id');

        # Category auto detect

        $ids = $this->ids;

        if($autodetect)
        {
            if (isset($ids['cat_id']))
            {
            	$catId = $ids['cat_id'];
            }

            if (isset($ids['criteria_id']))
            {
            	$listingTypeId = $ids['criteria_id'];
            }

            if (isset($ids['dir_id']))
            {
            	$dirId = $ids['dir_id'];
            }
        }

        $options = array(
            'disabled'=>false,
            'cat_id'=>!empty($paramCatId) ? $paramCatId : ($autodetect ? $catId : ''),
            'parent_id'=>!empty($paramCatId) ? $paramCatId : ($autodetect ? $catId : ''),
            'dir_id'=>!empty($paramDirId) ? $paramDirId : ($autodetect ? $dirId : ''),
            'type_id'=>!empty($paramListingTypeId) ? $paramListingTypeId : ($autodetect ? $listingTypeId : '')
        );

        // When the criteria_id is detected we display all categories for the listing type because
        // the first level typically has criteria id = 0 so the list would be empty

        if($autodetect && empty($options['cat_id']) && !$listingTypeId )
        {
            $options['level'] = 1;
        }

        $categories = $this->category->getCategoryList($options);

        // Now get the parent and sibling categories

        if($autodetect && isset($categories[$catId]) && count($categories) == 1)
        {
            $options['parent_id'] = $categories[$catId]->parent_id;

            $categories = $this->category->getCategoryList($options);
        }

        $category_attributes['class'] = 'jrSelect';

        if(isset($category_attributes['multiple']))
        {
			$category_options = $categories;
        }
        else {
			$category_options = array_merge(array(array('value'=>'','text'=>'- '.JreviewsLocale::getPHP('SEARCH_SELECT_CATEGORY').' -')),$categories);
        }

        $categorySelect = $this->form->select(
            'data[categories]',
            $category_options,
            $catId,
            $category_attributes
        );

		$this->addReplacement('{'.$tag.'}', $categorySelect);

		$this->addReplacement('{category_label}', __t("Category",true));
	}

	protected function listingTypes()
	{
		// Listing type select list
		// Needs more work before it can be implemented

		/*
		$listingTypes = $this->Criteria->getSelectList(
			array(
				'searchOnly' => true,
			)
		);

        $listingTypeSelect = $this->form->select(
            'data[criteria]',
            array_merge(array(array('value'=>null,'text'=>'- '.JreviewsLocale::getPHP('SEARCH_SELECT_LISTING_TYPE').' -')),$listingTypes),
            Sanitize::getVar($this->request, 'criteria'),
            array('class' => 'jrSelect')
        );

        $find[] = '{listing_type}';

        $replace[] = $listingTypeSelect;

        $find[] = '{listing_type_label}';

        $replace[] = __t("Listing Type",true);

		*/
	}

	protected function userRating()
	{
		$user_rating = Sanitize::getVar($this->request,S2_QVAR_RATING_AVG);

		$ratingSelect = $this->form->select(
			'data['.S2_QVAR_RATING_AVG.'][]',
			$this->rating->ratingSearchOptions(),
			$user_rating,
			array(
				'class'=>'jrSelect',
				'selected'=>1
			));

		$this->addReplacement('{user_rating}', $ratingSelect);

		$this->addReplacement('{user_rating_label}', __t("User Rating",true));
	}

	protected function editorRating()
	{
		$editor_rating = Sanitize::getVar($this->request,S2_QVAR_EDITOR_RATING_AVG);

		$ratingSelect = $this->form->select(
			'data['.S2_QVAR_EDITOR_RATING_AVG.'][]',
			$this->rating->ratingSearchOptions(),
			$editor_rating,
			array(
				'class'=>'jrSelect',
				'selected'=>1
			));

		$this->addReplacement('{editor_rating}', $ratingSelect);

		$this->addReplacement('{editor_rating_label}', __t("Editor Rating",true));
	}

	protected function radius($tag, $options)
	{
		$radius_metric = Sanitize::getString($this->config,'geomaps.radius_metric', 'mi');

		$radius = Sanitize::getVar($this->request,'jr_radius');

		$radiusOptions = array();

		foreach($options as $key => $value)
		{
			$radiusOptions[$value] = $value . ' ' . $radius_metric;
		}

		$radiusSelect = $this->form->select('data[Field][Listing][jr_radius]', $radiusOptions, $radius, array(
			'class'=>'jrSelect',
			'selected'=>1
		));

		$replace_srt = isset($matches[0]) ? $matches[0] : 'radius';

		$this->addReplacement('{'.$tag.'}', $radiusSelect);

		$this->addReplacement('{radius_label}', __t("Distance",true));
	}

	protected function fields($inputs, $labels)
	{
		# Get selected values from url

		$entry = array();

		foreach($this->request AS $key=>$value)
		{
			if(substr($key,0,3) == 'jr_') {

				$entry['Field']['pairs'][$key]['value'] = explode(self::$urlSeparator, $value);
			}
		}

		if(isset($this->request['tag']))
		{
			$entry['Field']['pairs']['jr_'.$this->request['tag']['field']]['value'] = array($this->request['tag']['value']);
		}

		$fields = $this->field->getFieldsArrayFromNames(array_keys($inputs),'listing',$entry);

		# Replace label tags and change field type based on view atttributes

		if($fields)
		{
			foreach($fields AS $key=>$group)
			{
				foreach($group['Fields'] AS $name=>$field)
				{
					if(/*isset($field['optionList']) && */isset($this->selectDisplayType[$name]))
				    {
					    $fields[$key]['Fields'][$name]['type'] = $this->selectDisplayType[$name];

					    $fields[$key]['Fields'][$name]['properties']['size'] = $this->selectDisplaySize[$name];
				    }
                    elseif($fields[$key]['Fields'][$name]['type'] == 'textarea')
                    {
                        $fields[$key]['Fields'][$name]['type'] = 'text';
                    }

					if(in_array($name,$labels))
					{
						$this->addReplacement('{'.$name.'_label}', $field['title']);
					}

					if($field['type']=='date')
					{
						$date_field = true;
					}
				}
			}

			$search = 'custom';

			$location = 'listing';

			$formFields = $this->customFields->getFormFields($fields, $location, $search, JreviewsLocale::getPHP('SEARCH_SELECT'));

			# Replace input tags
            foreach($inputs AS $key=>$name)
            {
                if(isset($formFields["data[Field][Listing][{$key}]"])) {

					$this->addReplacement('{'.$inputs[$key].'}', $formFields["data[Field][Listing][{$key}]"]);
                }
            }
		}
	}
}