<?php
/**
 * JReviews - Reviews Extension
 * Copyright (C) 2010-2011 ClickFWD LLC
 * This is not free software, do not distribute it.
 * For licencing information visit http://www.reviewsforjoomla.com
 * or contact sales@reviewsforjoomla.com
**/

defined( 'MVC_FRAMEWORK') or die( 'Direct Access to this location is not allowed.' );

class AccessController extends MyController {
	
	var $uses = array('acl');
	
	var $helpers = array('html','form');
	
	var $components = array('config');	
		
	var $autoRender = false;
	
	var $autoLayout = false;
		
	function beforeFilter() 
	{	
		# Call beforeFilter of MyAdminController parent class
		parent::beforeFilter();
	}
		
	function index() 
    {
		$this->name = 'access';
	
		$accessGroups = $this->Acl->getAccessGroupList();

		$this->set(
			array(
				'stats'=>$this->stats,
				'version'=>$this->Config->version,
				'Config'=>$this->Config,
				'accessGroups'=>$accessGroups,
				
			)
		);
        
        return $this->render();
	}
	
}