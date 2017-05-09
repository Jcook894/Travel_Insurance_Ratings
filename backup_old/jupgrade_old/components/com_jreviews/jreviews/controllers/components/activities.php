<?php
/**
 * JReviews - Reviews Extension
 * Copyright (C) 2010-2012 ClickFWD LLC
 * This is not free software, do not distribute it.
 * For licencing information visit http://www.reviewsforjoomla.com
 * or contact sales@reviewsforjoomla.com
**/

defined( 'MVC_FRAMEWORK') or die( 'Direct Access to this location is not allowed.' );

/**
* Records and loads activity information for the main user actions
* Listings, reviews, helpful voting, review discussions and owner replies
*/
class ActivitiesComponent extends S2Component {
    
    var $name = 'activities';
    
    var $activityModel = null;
    
    var $published = true;
    
    var $validObserverModels = array('Claim','Listing','Review','OwnerReply','Discussion','Report','Vote');
    
    function startup(&$controller) {

        $this->c = & $controller;
        
        $this->app = $this->c->app;
        
        // Check if there's an observer model in the controller
        if(method_exists($this->c,'getActivityModel')) 
        {                                                  
            $this->activityModel = & $controller->getActivityModel();

            if(in_array($this->activityModel->name,$this->validObserverModels)) {
                $this->activityModel->addObserver('plgAfterFind',$this);
                $this->activityModel->addObserver('plgAfterSave',$this);
            }
        }
    }     
    
    function plgAfterFind(&$model, $results) 
    {        
        if(empty($results)) {
            return $results;
        }

        switch($this->activityModel->name) {
            
            case 'Listing':
                break;
        }
                
        return $results;
    }

    function plgAfterSave(&$model) 
    {
        $data = array();
        App::import('Model','activity','jreviews');
        App::import('Helper','routes','jreviews');        
        $Activity = new ActivityModel();
        $Routes = ClassRegistry::getClass('RoutesHelper');
     
        $data['Activity']['user_id'] = $this->c->_user->id;
        $data['Activity']['email'] = $this->c->_user->email;
        $data['Activity']['created'] = gmdate('Y-m-d H:i:s');
        $data['Activity']['ipaddress'] = $this->c->ipaddress;        
        $data['Activity']['activity_new'] = isset($model->data['insertid']) ? 1 : 0;

        switch($this->activityModel->name)
        { 
            case 'Claim':            
                //Get the full listing info to create proper permalinks    
                $model->data['Listing']['id'] = $model->data['Claim']['listing_id'];
                $listing = $this->_getListing($model);  
                $permalink = $Routes->content('',$listing,array('return_url'=>true));
                $permalink = cmsFramework::makeAbsUrl($permalink);

                $data['Activity']['activity_type'] = 'claim';
                $data['Activity']['listing_id'] = $model->data['Claim']['listing_id'];
                $data['Activity']['extension'] = 'com_content';
                $data['Activity']['activity_new'] = 1;  
                $data['Activity']['permalink'] = $permalink;
                $Activity->store($data);                                
               break;
                                  
            case 'Listing':            
                // Skip logging of admin actions on user listings
//                if($this->c->_user->id != $model->data['Listing']['created_by']) break; 
                
                //Get the full listing info to create proper permalinks
                $listing = $this->_getListing($model);  
                $permalink = $Routes->content('',$listing,array('return_url'=>true));
                $permalink = cmsFramework::makeAbsUrl($permalink);
                $data['Activity']['activity_type'] = 'listing';
                $data['Activity']['email'] = Sanitize::getString($model->data,'email');
                $data['Activity']['listing_id'] = $model->data['Listing']['id'];
                $data['Activity']['extension'] = 'com_content';
                $data['Activity']['permalink'] = $permalink;
                $Activity->store($data);                                
               break;
         
            case 'Review':
                // Skip logging of admin actions on user listings
//                if($this->c->_user->id != $model->data['Review']['userid']) break; 
                $data['Activity']['activity_type'] = 'review';
                $data['Activity']['listing_id'] = $model->data['Review']['pid'];            
                $data['Activity']['review_id'] = $model->data['Review']['id'];
                $data['Activity']['extension'] = $model->data['Review']['mode'];
                $data['Activity']['value'] = round(Sanitize::getVar($model->data,'average_rating'),0);
                $data['Activity']['permalink'] = $Routes->reviewDiscuss('',array('review_id'=>$data['Activity']['review_id']),array('return_url'=>true));                
                $Activity->store($data);                                
                break;
                
            case 'OwnerReply':
                // Skip logging of admin actions on user listings
//                if($this->c->_user->id != $model->data['Listing']['created_by']) break; 
                $data['Activity']['activity_type'] = 'owner_reply';
                $data['Activity']['listing_id'] = $model->data['Listing']['listing_id'];            
                $data['Activity']['review_id'] = $model->data['OwnerReply']['id'];
                $data['Activity']['extension'] = $model->data['Listing']['extension'];
                // Editing not yet implemented so all replies are new
                $data['Activity']['activity_new'] = 1;
                $data['Activity']['permalink'] = $Routes->reviewDiscuss('',array('review_id'=>$data['Activity']['review_id']),array('return_url'=>true));                                
                $Activity->store($data);                                
                break;  
                
            case 'Discussion':
                // Skip logging of admin actions on user listings
//                if($this->c->_user->id != $model->data['Discussion']['user_id']) break;

                // Get listing id and extension
                $this->c->_db->setQuery("
                    SELECT 
                        Review.pid AS listing_id, Review.`mode` AS extension
                    FROM 
                        #__jreviews_comments AS Review
                    WHERE 
                        Review.id = " . $model->data['Discussion']['review_id']
                );
                
                // Get listing owner id and check if it matches the current user       
                if($listing = current($this->c->_db->loadAssocList()))
                {                 
                    $data['Activity']['activity_type'] = 'review_discussion';
                    $data['Activity']['listing_id'] = $listing['listing_id'];            
                    $data['Activity']['review_id'] = $model->data['Discussion']['review_id'];
                    $data['Activity']['post_id'] = $model->data['Discussion']['discussion_id'];
                    $data['Activity']['extension'] = $listing['extension'];
                    $data['Activity']['permalink'] = $Routes->reviewDiscuss('',array('review_id'=>$data['Activity']['review_id']),array('return_url'=>true));                                
                    $Activity->store($data);                                
                }
                break;      
                
            case 'Report':
                $data['Activity']['activity_type'] = $model->data['Report']['post_id'] ? 'discussion_report' : 'review_report';
                $data['Activity']['listing_id'] = $model->data['Report']['listing_id'];            
                $data['Activity']['review_id'] = $model->data['Report']['review_id'];
                $data['Activity']['extension'] = $model->data['Report']['extension'];
                // Editing not yet implemented so all replies are new
                $data['Activity']['activity_new'] = 1;
                $data['Activity']['permalink'] = $Routes->reviewDiscuss('',array('review_id'=>$data['Activity']['review_id']),array('return_url'=>true));                                
                $Activity->store($data);                                
                break;             

            case 'Vote':
               // Get listing id and extension
                $this->c->_db->setQuery("
                    SELECT 
                        Review.pid AS listing_id, Review.`mode` AS extension
                    FROM 
                        #__jreviews_comments AS Review
                    WHERE 
                        Review.id = " . $model->data['Vote']['review_id']
                );
                
                // Get listing owner id and check if it matches the current user       
                if($listing = current($this->c->_db->loadAssocList()))
                {                 
                    $data['Activity']['activity_type'] = 'helpful_vote';
                    $data['Activity']['listing_id'] = $listing['listing_id'];            
                    $data['Activity']['review_id'] = $model->data['Vote']['review_id'];
                    $data['Activity']['helpful_vote_id'] = $model->data['Vote']['vote_id'];                    
                    $data['Activity']['extension'] = $listing['extension'];
                    $data['Activity']['value'] = $model->data['Vote']['vote_yes'];
                    $data['Activity']['permalink'] = $Routes->reviewDiscuss('',array('review_id'=>$data['Activity']['review_id']),array('return_url'=>true));                                
                    $Activity->store($data);                                
                }
                               
                break;         
        }
        
        $this->published = false; // Run once. With paid listings it is possible for a plugin to run a 2nd time when the order is processed together with the listing (free)
    }
    
    function _getListing(&$model)
    {
        if(isset($this->c->viewVars['listing'])) 
        {
            $listing = $this->c->viewVars['listing'];
        } 
        else 
        {
            $listing_id = isset($model->data['Listing']) ? Sanitize::getInt($model->data['Listing'],'id') : false;
            !$listing_id and $listing_id = Sanitize::getInt($this->c->data,'listing_id');
            if(!$listing_id) return false;
            $listing = $this->c->Listing->findRow(array('conditions'=>array('Listing.id = '. $listing_id)),array('afterFind' /* Only need menu id */));        
            $this->c->set('listing',$listing);    
        } 
        
        if(Sanitize::getInt($model->data['Listing'],'state')) 
        {
            $listing['Listing']['state'] =  $model->data['Listing']['state'];          
        }
        
        return $listing;
    }    
}
