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

class ModuleDirectoriesController extends MyController {

	var $uses = array('user','menu','category','directory');

	var $components = array('config','access');

	var $helpers = array('routes','libraries','html','assets','jreviews','tree');

	var $autoRender = false;

	var $autoLayout = true;

	var $layout = 'module';

	function beforeFilter() {

		# Call beforeFilter of MyController parent class
		parent::beforeFilter();

		$this->Directory->Config = & $this->Config;

		# Change render controller/view
		isset($this->params['module']) and $this->viewSuffix = Sanitize::getString($this->params['module'],'tmpl_suffix');
	}

	function index($params)
    {
		$this->action = 'directory'; // Set view file

		# Read module params

		$directoryId = isset($this->params['module']) ? cleanIntegerCommaList(Sanitize::getVar($this->params['module'],'dir_ids')) : '';

		$excludeDirId = isset($this->params['module']) ? Sanitize::getVar($this->params['module'],'exclude_dirid') : array();

		$conditions = array();

		$order = array();

		$dir_id = $cat_id = '';

        $directories = $this->Category->findTree(
            array(
                'level'=>$this->Config->dir_category_levels,
                'menu_id'=>true,
                'dir_id'=>$directoryId,
                'pad_char'=>''
            )
        );

        if($excludeDirId)
        {
        	$directories = array_diff_key($directories, array_flip($excludeDirId));
        }

		if($menu_id = Sanitize::getInt($this->params,'Itemid'))
		{
			$menuParams = $this->Menu->getMenuParams($menu_id);
		}

       	# Category auto detect

        $ids = CommonController::_discoverIDs($this);

        if(isset($ids['dir_id']))
        {
        	if(strstr($ids['dir_id'],','))
        	{
        		unset($ids['dir_id']);
        	}
        }

        extract($ids);

		$this->set(array(
			'directories'=>$directories,
            'dir_id'=>$dir_id,
			'cat_id'=>is_numeric($cat_id) && $cat_id >0 ? $cat_id : false
			)
		);

		return $this->render('modules','directories');
	}
}
