<?php
/**
 * JReviews - Reviews Extension
 * Copyright (C) 2010-2016 ClickFWD LLC
 * This is not free software, do not distribute it.
 * For licencing information visit https://www.jreviews.com
 * or contact sales@jreviews.com
**/

defined( 'MVC_FRAMEWORK') or die( 'Direct Access to this location is not allowed.' );

S2App::import('Controller','common','jreviews');

class ModuleFiltersController extends MyController {

	var $uses = array('menu','field','category','criteria');

	var $helpers = array('libraries','html','assets','form','custom_fields','rating');

	var $components = array('config','access','advanced_filter_theme');

	var $autoRender = false;

	var $autoLayout = true;

	var $layout = 'module';

	var $searchMenuId = 0;

	function beforeFilter()
    {
        parent::beforeFilter();

		$this->viewSuffix = Sanitize::getString($this->params['module'],'tmpl_suffix');

		$this->searchMenuId = $this->Menu->get('jr_advsearch');
	}

	function index()
	{
		$settings = $this->params['module'];

		$hideInSearchPage = Sanitize::getInt($settings, 'hide_searchpage', 1);

		// Hide the module in the adv. search page if it's not displaying search results

		if (($hideInSearchPage && $this->isAdvSearchPage()) || !$this->filterIdMatchesModuleId())
		{
			return false;
		}

        $settings_theme_enable = Sanitize::getInt($settings, 'module_theme_enable');

        $settings_theme = Sanitize::getVar($settings, 'module_theme');

        $settings['settings_theme_enable'] = $settings_theme_enable;

        $settings['settings_theme'] = $settings_theme;

        // Menu ID handling for search results

        $settingsMenuId = Sanitize::getInt($settings, 'search_itemid');

        if ($settingsMenuId)
        {
        	$this->set('search_itemid', $settingsMenuId);
        }
        // Always use adv. search page menu ID for results URL
		elseif ($this->searchMenuId) {
        	$this->set('search_itemid', $this->searchMenuId);
		}

        // Convert params to comma list because they are used in the theme file as hidden inputs

		if(isset($this->params['module']['dir_id']) && is_array($this->params['module']['dir_id']))
		{
			$this->params['module']['dir_id'] = implode(',', $this->params['module']['dir_id']);
		}

		if(isset($this->params['module']['criteria_id']) && is_array($this->params['module']['criteria_id']))
		{
			$this->params['module']['criteria_id'] = implode(',', $this->params['module']['criteria_id']);
		}

		if(isset($this->params['module']['cat_id']) && is_array($this->params['module']['cat_id']))
		{
			$this->params['module']['cat_id'] = implode(',', $this->params['module']['cat_id']);
		}

		$autodetectIds = CommonController::_discoverIds($this, $excludeKeys = array('listing_id'));

		$this->set('autodetectIds', $autodetectIds);

		$theme = $this->partialRender('modules', 'filters', $this->layout);

		return $this->AdvancedFilterTheme->render($theme, $settings, $autodetectIds);
	}

	protected function isAdvSearchPage()
	{
		$args = array();

		$tag = Sanitize::getVar($this->params, 'tag', array());

		$click2search = isset($tag['field']) && isset($tag['value']);

		foreach ($this->passedArgs AS $key => $arg)
		{
			if ($key !== 'url')
			{
				$args[$key] = $arg;
			}
		}

		// Mar 13, 2017 - Correctly identify current page as adv. search page when a listing type filter is used
		// Mar 24, 2017 - Click2search page incorrectly identified as adv. search page so the module was being hidden

		$currMenuId = Sanitize::getInt($args, 'Itemid');

		$listingTypeId = $this->Menu->isSearchPage($currMenuId);

		if (!$click2search && count($args) == 2 && $listingTypeId !== false)
		{
			return true;
		}

		return false;
	}

	protected function filterIdMatchesModuleId()
	{
		$moduleId = Sanitize::getString($this->params, 'module_id');

		$moduleId = (int) str_replace('jreviewsfilterswidget-','',$moduleId);

		$filterId = Sanitize::getInt($this->params, 'filter');

		if (!isset($this->params['filter']) || $filterId == $moduleId)
		{
			return true;
		}

		return false;
	}
}