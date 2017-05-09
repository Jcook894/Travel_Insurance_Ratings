<?php
/**
 * JReviews - Reviews Extension
 * Copyright (C) 2010-2011 ClickFWD LLC
 * This is not free software, do not distribute it.
 * For licencing information visit http://www.reviewsforjoomla.com
 * or contact sales@reviewsforjoomla.com
**/

// no direct access
defined( 'MVC_FRAMEWORK') or die( 'Direct Access to this location is not allowed.' );

class AdminPredefinedRepliesController extends MyController {
	
	var $uses = array('predefined_reply');
    
	var $helpers = array();

    var $components = array('config');
    
	var $autoRender = false;

	var $autoLayout = false;		
		
    function beforeFilter() 
    {        
        parent::beforeFilter();
    }
    
	function index() 
    {
        $replies = $this->PredefinedReply->findAll(array(
            'fields'=>array('PredefinedReply.*'),
            'order'=>array('PredefinedReply.reply_id ASC') 
        ));
       	
        $this->set(array(
			'replies'=>$replies
		));
        
        return $this->render('predefined_replies','predefined_replies');
	}	
		
	function _save() 
    {
        $count = $this->PredefinedReply->findCount(array());        
       
        $i = 0;
        foreach($this->data['PredefinedReply'] AS $type=>$row)
        {
            $j = 0;
            foreach($row AS $reply)
            {
                $i++; $j++;
                $data = array('PredefinedReply'=>array(
                    'reply_type'=>$type,
                    'reply_subject'=>$reply['subject'],
                    'reply_body'=>$this->data['__raw']['PredefinedReply'][$type][$j]['body']
                )); 

                $data['PredefinedReply']['reply_id'] = $i;
             
                $this->PredefinedReply->replace('#__jreviews_predefined_replies','PredefinedReply',$data,'reply_id');                                                               
            }
        }         
	}
		
}