<?php
/**
 * JReviews - Reviews Extension
 * Copyright (C) 2010-2012 ClickFWD LLC
 * This is not free software, do not distribute it.
 * For licencing information visit http://www.reviewsforjoomla.com
 * or contact sales@reviewsforjoomla.com
**/

defined( 'MVC_FRAMEWORK') or die( 'Direct Access to this location is not allowed.' );

class DirectoriesController extends MyController {
	
	var $uses = array('user','menu','category','directory' /*directory model for J15*/);
	
	var $components = array('config','access');
	
	var $helpers = array('assets','cache','routes','libraries','html','jreviews','tree' /* tree helper for J16*/);
	
	var $layout = 'directory';
    
    var $autoRender = false;
	
	function beforeFilter() 
    {                
		# Call beforeFilter of MyController parent class
		parent::beforeFilter();
		
		$this->Directory->Config = & $this->Config;
	}
		
	function index($params) 
    {
        $this->action = 'directory'; // Trigger assets helper method
        
		if($this->_user->id === 0) {
			$this->cacheAction = Configure::read('Cache.expires');
		}		

		$page = array('title'=>'','show_title'=>0);
		$conditions = array();
		$order = array();
		
        if($menu_id = Sanitize::getInt($this->params,'Itemid')) {
            $menuParams = $this->Menu->getMenuParams($menu_id);        
            $page['title'] = Sanitize::getString($menuParams,'title');
            $page['show_title'] = Sanitize::getString($menuParams,'dirtitle',0);
        }
        
        $override_keys = array(
            'dir_show_alphaindex',
            'dir_cat_images',
            'dir_columns',
            'dir_cat_num_entries',
            'dir_category_hide_empty',
            'dir_category_levels',
            'dir_cat_format'        
        );
        
        if(Sanitize::getBool($menuParams,'dir_overrides')) {
            $overrides = array_intersect_key($menuParams,array_flip($override_keys));
            $this->Config->override($overrides);
        }

		if($this->cmsVersion == CMS_JOOMLA15)
        {
            $directories = $this->Directory->getTree(Sanitize::getString($this->params,'dir'));
        }
        else 
        {
            $directories = $this->Category->findTree(
                array(
                    'level'=>$this->Config->dir_cat_format === 0 ? 2 : $this->Config->dir_category_levels,
                    'menu_id'=>true,
                    'dir_id'=>Sanitize::getString($this->params,'dir'),
                    'pad_char'=>''
                )
            );
        }
					
		$this->set(array(
			'page'=>$page,
			'directories'=>$directories
			)
		);
        
        return $this->render('directories','directory');
	}		
	
}