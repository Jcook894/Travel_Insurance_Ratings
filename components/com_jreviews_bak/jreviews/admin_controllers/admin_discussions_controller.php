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

class AdminDiscussionsController extends MyController 
{
	var $uses = array('menu','discussion','review','criteria','predefined_reply');
	var $components = array('config','admin/admin_notifications','everywhere');	
	var $helpers = array('html','admin/admin_routes','routes','form','time');

	var $autoRender = false;
	var $autoLayout = true;		
		
	function beforeFilter() {
		
		# Call beforeFilter of MyAdminController parent class
		parent::beforeFilter();

	}
	     
    // Need to return object by reference for PHP4    
    function &getPluginModel(){
        return $this->Discussion;
    }  
         
    // Need to return object by reference for PHP4
    function &getEverywhereModel() {
        return $this->Review;
    } 
                                                          
    // Need to return object by reference for PHP4    
    function &getNotifyModel(){
        return $this->Discussion;
    }
    
	function index() {
	
        $reviews = array();
        $page = '';
        
        $conditions = array("Discussion.`approved` = 0");
		$posts = $this->Discussion->findAll(array(
            'fields'=>array(
                'User.email AS `User.email`'
            ), 
            'conditions'=>$conditions,
            'offset'=>$this->offset,
            'limit'=>$this->limit,               
            'order'=>array('Discussion.discussion_id DESC')
        ));
		      
        $total = $this->Discussion->findCount(array('conditions'=>$conditions));
            
        if(!empty($posts))
        { 
            $predefined_replies = $this->PredefinedReply->findAll(array(
                'fields'=>array('PredefinedReply.*'),
                'conditions'=>array('reply_type = "discussion_post"')
                ));
           
            // We get all the review ids for the discussion posts
            $review_ids = array();     
            foreach($posts AS $post){
                !empty($post['Discussion']['review_id']) and $review_ids[$post['Discussion']['review_id']] = $post['Discussion']['review_id'];           
            }                                                      
            
            // For now all posts are for reviews so there's no need to worry about the entry type
            $this->EverywhereAfterFind = true; // Triggers the afterFind in the Observer Model
            $this->Review->runProcessRatings = false;
            
            $reviews = $this->Review->findAll(array('conditions'=>array('Review.id IN ('.implode(',',array_keys($review_ids)).')')));

            // We merge the posts and review info
            foreach($posts AS $key=>$post)
            {
                unset($reviews[$post['Discussion']['review_id']]['User']); // Otherwise the review user overwrites the comment user
                isset($reviews[$post['Discussion']['review_id']]) and $posts[$key] = array_merge($posts[$key],$reviews[$post['Discussion']['review_id']]);            
            }                                                      

            $this->set(array(
                'total'=>$total,
                'posts'=>$posts,
                'predefined_replies'=>$predefined_replies
            ));
        
        }
            
        return $this->render('discussions','posts');
	}	
		    
    function _save() 
    {
        $response = array();
        
        $this->Discussion->isNew = false;
        
        if($this->Discussion->store($this->data))
        { 
            $response[] = "jQuery('#jr_postForm".$this->data['Discussion']['discussion_id']."').slideUp('slow',function(){jQuery(this).html('');});";
            $response[] = "jreviews_admin.menu.moderation_counter('discussion_count');";
        }
                
        clearCache('', 'views');
        clearCache('', '__data');    
        
        return $this->ajaxResponse($response);
    }
		
}