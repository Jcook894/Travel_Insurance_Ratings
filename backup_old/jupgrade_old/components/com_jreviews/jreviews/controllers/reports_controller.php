<?php
/**
 * JReviews - Reviews Extension
 * Copyright (C) 2010-2012 ClickFWD LLC
 * This is not free software, do not distribute it.
 * For licencing information visit http://www.reviewsforjoomla.com
 * or contact sales@reviewsforjoomla.com
**/

defined( 'MVC_FRAMEWORK') or die( 'Direct Access to this location is not allowed.' );

class ReportsController extends MyController {
	
	var $uses = array('report','review','menu');
	
	var $helpers = array('libraries','html','form');
	
	var $components = array('config','access','notifications','activities');

	var $autoRender = true;

	var $autoLayout = false;

	function beforeFilter() 
    {				
        # Init Access
        $this->Access->init($this->Config);		
		# Set Theme	
		$this->viewTheme = $this->Config->template;		
	}
	
	// Need to return object by reference for PHP4
	function &getNotifyModel() {
		return $this->Report;
	}	 
    
    // Need to return object by reference for PHP4
    function &getActivityModel() {
        return $this->Report;
    }                  
					
	function create()
	{
        $this->autoRender = false;
        $this->autoLayout = false;
                
        if($this->Config->user_report) 
        {
            return $this->ajaxResponse($this->render('reports','create'),false);
        } else {
            return s2Messages::accessDenied();
        }
    }
	
	function _save() 
	{				
        $this->autoRender = false;
        $this->autoLayout = false;
        $response = array();
        		
		# Validate form token
        $this->components = array('security');
        $this->__initComponents();
		if($this->invalidToken) {
            return $this->ajaxError(s2messages::invalidToken());
		}		

		if($this->Config->user_report) 
        {	   
			$this->data['Report']['report_text'] = Sanitize::getString($this->data['Report'],'report_text');
            $listing_id = $this->data['Report']['listing_id'] = Sanitize::getInt($this->data['Report'],'listing_id');
			$review_id = $this->data['Report']['review_id'] = Sanitize::getInt($this->data['Report'],'review_id');
            $post_id = $this->data['Report']['post_id'] = Sanitize::getInt($this->data['Report'],'post_id');
            $extension = $this->data['Report']['extension'] = Sanitize::getString($this->data['Report'],'extension');
       
			if ($this->data['Report']['report_text'] != '') {

                $this->data['Report']['user_id'] = $this->_user->id;
                $this->data['Report']['ipaddress'] = $this->ipaddress;
                $this->data['Report']['created'] = date('Y-m-d H:i:s');
                $this->data['Report']['approved'] = 0;                

                if($this->_user->id)
                {
                    $this->data['Report']['name'] = $this->_user->name;
                    $this->data['Report']['username'] = $this->_user->username;
                    $this->data['Report']['email'] = $this->_user->email;            
                } else {
                    $this->data['Report']['name'] = 'Guest';
                    $this->data['Report']['username'] = 'guest';                    
                }

				if($this->Report->store($this->data))
                {    
                    $update_text = __t("Your report was submitted, thank you.",true);    
                    $response[] =  "jQuery('#jr_reportLink".($post_id > 0 ? $post_id : $review_id)."').remove();";           
                    return $this->ajaxUpdateDialog($update_text,$response);                    
                } 
                
                return $this->ajaxError(s2Messages::submitErrorDb());
			} 
           
			# Validation failed
            return $this->ajaxValidation(__t("The message is empty.",true),$response);
		}
	}
}
