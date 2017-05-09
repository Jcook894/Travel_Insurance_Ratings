<?php
/**
 * JReviews - Reviews Extension
 * Copyright (C) 2010-2012 ClickFWD LLC
 * This is not free software, do not distribute it.
 * For licencing information visit http://www.reviewsforjoomla.com
 * or contact sales@reviewsforjoomla.com
**/

defined( 'MVC_FRAMEWORK') or die( 'Direct Access to this location is not allowed.' );

App::import('Controller','common','jreviews');

class ModuleRangeController extends MyController {
	
	var $uses = array('menu');
	
	var $helpers = array('assets','routes','form','html','text');
	
	var $components = array('config','access');

	var $autoRender = false;
	
	var $autoLayout = false;
		
	function beforeFilter() 
    {
        parent::beforeFilter();

		$this->viewSuffix = Sanitize::getString($this->params['module'],'tmpl_suffix');
		
		# Set Theme	
		$this->viewTheme = $this->Config->template;
		$this->viewImages = S2Paths::get($this->app, 'S2_THEMES_URL') . $this->viewTheme . _DS . 'theme_images' . _DS;		
	}
	
	function index()
	{			
		global $Itemid;
		
		$cat_id = null;
		$conditions = array();
		$joins = array();
		$order = array();
        $menu_id = '';

		// Read module params
		$itemid_options = Sanitize::getString($this->params['module'],'itemid_options' );
		$itemid_hc = Sanitize::getInt($this->params['module'],'hc_itemid' );		
		
		$field = Sanitize::paranoid(Sanitize::getString($this->params['module'],'field'),array('_'));
		$custom_params = Sanitize::getString($this->params['module'],'custom_params');
		
		$dir_id = Sanitize::getString($this->params['module'],'dir');
		$section_id = Sanitize::getString($this->params,'section');
		$cat_id = Sanitize::getString($this->params['module'],'cat');
		$criteria_id = Sanitize::getString($this->params['module'],'criteria');
		
		# Set menu id
		switch($itemid_options) 
        {
			case 'none':
				$menu_id = '';
			break;
			case 'current':
			break;
			case 'hardcode':
				$menu_id = $itemid_hc;
			break;
		}
		
		# Category auto detect
		if(Sanitize::getInt($this->params['module'],'catauto')) 
		{			
            $ids = CommonController::_discoverIDs($this);
            extract($ids);
        }
		
		# Send variables to view template		
		$this->set(array(
			'field'=>$field,
            'dir_id'=>$dir_id,
            'section_ids'=>$section_id,
			'category_ids'=>$cat_id,
			'criteria_id'=>$criteria_id,
			'menu_id'=>$menu_id,
			'custom_params'=>$custom_params	
		));

		return $this->render('modules','range');

	}
}