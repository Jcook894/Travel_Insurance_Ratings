<?php
/**
 * JReviews - Reviews Extension
 * Copyright (C) 2010-2012 ClickFWD LLC
 * This is not free software, do not distribute it.
 * For licencing information visit http://www.reviewsforjoomla.com
 * or contact sales@reviewsforjoomla.com
**/

defined( 'MVC_FRAMEWORK') or die( 'Direct Access to this location is not allowed.' );

class EverywhereComponent extends S2Component {
    
    var $name = 'everywhere';
    
    var $everywhereModel = null;   
    
    var $published = true;
    
    var $plugin_order = 0;
    
    var $validObserverModels = array('Review','ReviewReport');
    
    function startup(&$controller) {

        $this->c = & $controller;
        
        $this->app = $this->c->app;

        $this->loadListingModel($this->c);
        
        // Check if there's an observer model in the controller
        if(method_exists($this->c,'getEverywhereModel')) 
        {                                                  
            $this->everywhereModel = & $controller->getEverywhereModel();

            if($this->everywhereModel && in_array($this->everywhereModel->name,$this->validObserverModels)) {
                $this->everywhereModel->addObserver('plgAfterFind',$this);
                $this->everywhereModel->addObserver('plgAfterSave',$this);
            }
        }
    }     
    
    /**
     * Model observer adds listing information for reviews submitted from different extensions
     * Used in my reviews page
     */
    function plgAfterFind(&$model, $results) 
    {
        if(empty($results)) {
            return $results;
        }
             
        switch($this->everywhereModel->name) 
        {
            case 'Review':

                if(isset($this->c->EverywhereAfterFind) &&  $this->c->EverywhereAfterFind === true) 
                {
                    $this->c->action != '_save' and Configure::write('EverywhereReviewModel',true);
                    $extensions = array();
                    $models = array();

                    # Build extension and listing_id array
                    foreach($results AS $result) {
                        $extensions[$result['Review']['extension']][] = $result['Review']['listing_id'];
                        $models[$result['Review']['extension']] = $this->name.'_'.$result['Review']['extension'];
                    }

                    foreach($extensions AS $key=>$value) {
                        $extensions[$key] = array_unique($value);
                    }

                    # Verify model files exist or unset them
                    foreach($models AS $extension=>$EveryWhereModel) {
                        if(!file_exists(S2Paths::get('jreviews', 'S2_MODELS') . $this->name . DS . $EveryWhereModel . '.php')) {
                            unset($models[$extension]);
                        }
                    }

                    $this->__initModels(array_values($models),$this->app);            
                    # Loop through extensions found in the current page
                    foreach($extensions AS $extension=>$listing_ids) 
                    {
                        if(isset($models[$extension])) {
                            if(!is_array($listing_ids)) $listing_ids = array($listing_ids);
                            // Uses the current extension's model to get Listing info for the given listing ids
                            $realKey = $this->{inflector::camelize($models[$extension])}->realKey;     
                            $listings[$extension] = $this->{inflector::camelize($models[$extension])}->findAll(array('conditions'=>'Listing.' . $realKey . ' IN ('. implode(',',$listing_ids) . ')'));                        
                        }                                                                          
                    }                    
                   
                    # Merge the listing data to the Review Model results
                    foreach($results AS $key=>$result) 
                    {
                        if(isset($listings[$result['Review']['extension']]) 
                            && isset($listings[$result['Review']['extension']][$result['Review']['listing_id']])) 
                        {      
                            // Second condition above excludes reviews when the listing is not found for the review (i.e. the category for the listing was removed)
                            if( defined('MVC_FRAMEWORK_ADMIN')
                                ||
                                ($this->c->name == 'reviews' && in_array($this->c->action,array('myreviews','_save','_edit','latest','latest_editor','latest_user')))
                                ||                                 
                                ($this->c->name == 'reviews' && $this->c->action == 'moderation') // Admin controller
                                ||
                                ($this->c->name == 'module_reviews' && $this->c->action == 'index') 
                                ||
                                ($this->c->name == 'community_reviews' && $this->c->action == 'index') 
                                ||
                                ($this->c->name == 'feeds')
                                ||
                                ($this->c->name == 'discussions' && $this->c->action == 'review')
                            )
                            { // Exclude listing owner info because it replaces the reviewer info
                                if(isset($listings[$result['Review']['extension']][$result['Review']['listing_id']]['User']))
                                {
                                    if(isset($listings[$result['Review']['extension']][$result['Review']['listing_id']]['User']))
                                    {
                                        $listings[$result['Review']['extension']][$result['Review']['listing_id']]['ListingUser'] = $listings[$result['Review']['extension']][$result['Review']['listing_id']]['User'];                            
                                        unset($listings[$result['Review']['extension']][$result['Review']['listing_id']]['User']);                                        
                                    }
                                    if(isset($listings[$result['Review']['extension']][$result['Review']['listing_id']]['Community']))
                                    {
                                        $listings[$result['Review']['extension']][$result['Review']['listing_id']]['ListingCommunity'] = $listings[$result['Review']['extension']][$result['Review']['listing_id']]['Community'];                                
                                        unset($listings[$result['Review']['extension']][$result['Review']['listing_id']]['Community']);                                                                            
                                    }
                                }
                                unset($listings[$result['Review']['extension']][$result['Review']['listing_id']]['Rating']);                               
                            }

                            # Merge listing and review arrays. When there are duplicates Review keys trump Listing keys (i.e. Review, Rating, Field, ...)
                            $results[$key] = array_insert($listings[$result['Review']['extension']][$result['Review']['listing_id']], $results[$key]);
                        } 
                        else 
                        {
                            unset($results[$key]); // Removes reviews for extensions without Models
                        }
                    }
                          
                    # Preprocess criteria and rating information
                    $rating_test = current($results);
                    if(isset($rating_test['Rating'])) 
                    {
                        $results = $this->c->Review->processRatings($results);                                
                    }

                    return $results;    
                }
                            
                break;
                
            case 'ReviewReport':
               
                    $extensions = array();
                    $models = array();
                    
                    # Build extension and listing_id array
                    foreach($results AS $result) 
                    {
                        $extensions[$result['Review']['extension']][] = $result['Review']['listing_id'];
                        $models[$result['Review']['extension']] = $this->name.'_'.$result['Review']['extension'];
                    }

                    foreach($extensions AS $key=>$value) 
                    {
                        $extensions[$key] = array_unique($value);
                    }

                    # Verify model files exist or unset them
                    foreach($models AS $extension=>$EveryWhereModel) 
                    {
                        if(!file_exists(S2Paths::get('jreviews','S2_MODELS') . $this->name . DS . $EveryWhereModel . '.php')) {
                            unset($models[$extension]);

                        }
                    }
                    $this->__initModels(array_values($models),$this->app);            
                    
                    # Loop through extensions found in the current page
                    foreach($extensions AS $extension=>$listing_ids) 
                    {
                        if(isset($models[$extension])) 
                        {
                            if(!is_array($listing_ids)) $listing_ids = array($listing_ids);
                            // Uses the current extension's model to get Listing info for the given listing ids
                            $realKey = $this->{inflector::camelize($models[$extension])}->realKey;
                            $listings[$extension] = $this->{inflector::camelize($models[$extension])}->findAll(array('conditions'=>'Listing.' . $realKey . ' IN ('. implode(',',$listing_ids) . ')'));                        
                        }
                    }                    

                    # Merge the listing data to the Review Model results
                    foreach($results AS $key=>$result) 
                    {
                        if(isset($listings[$result['Review']['extension']])) 
                        {
                            $results[$key] = array_insert($results[$key],$listings[$result['Review']['extension']][$result['Review']['listing_id']]);
                        } 
                        else 
                        {
                            unset($results[$key]); // Removes reviews for extensions without Models
                        }
                    }

                    # Preprocess criteria and rating information
                    $rating_test = current($results);
                    if(isset($rating_test['Rating'])) {
                        $results = $this->c->Review->processRatings($results);                                
                    }
                    
                    return $results;
                            
                break;                
            
        }
                
        return $results;
    }

    /**
     * Model observer for review after save actions
     * It can be used to update other extension tables by adding the afterSave method to the Everywhere Models
     */
    function plgAfterSave(&$model) {

        if(isset($this->c->Listing) && method_exists($this->c->Listing,'afterSave')) {
//            appLogMessage(print_r($model->data,true),'plgAfterSave');
            $this->c->Listing->afterSave($model);
        }
        
//        appLogMessage('Average Rating: '. $model->data['average_rating'],'plgAfterSave');        
//        appLogMessage('New: '. $model->data['new'],'plgAfterSave');
        
        return true;
    }
       
    /**
     * Dynamic Listing Model Loading for jReviewsEverywhere extensions
     * Detects which extension is being used to load the correct Listing model
     *
     * @param object $controller
     * @param string $extension
     */
    function loadListingModel(&$controller, $extension = null) 
    {        
       if(in_array($controller->name,array('admin/reviews','reviews')) && $controller->action == '_save') {
           $extension = Sanitize::getString($controller->data['Review'],'mode');
           !$extension and isset($controller->data['Listing']) and $extension = Sanitize::getString($controller->data['Listing'],'extension');
       } else {
           $extension = $extension ? $extension : Sanitize::getString($controller->params,'extension',Sanitize::getString($controller->data,'extension'));
       }

       if(!$extension && isset($controller->params['module'])) {// Final check for module parameter
           $extension = Sanitize::getString($controller->params['module'],'extension');
       }
       
        $extension == '' 
            and $controller->name != 'facebook' 
            and $controller->name != 'reviews' 
            and $controller->name != 'community_reviews' 
            and $controller->name != 'module_reviews' 
            and $controller->name != 'discussions' 
            // admin controllers
            and $controller->name != 'admin/reviews' 
            and $controller->name != 'admin/admin_owner_replies'
            and $controller->name != 'admin/admin_reports'
            and $controller->name != 'admin/admin_discussions'
            and $extension = 'com_content';
                           
        // Check if in listing detail page and it's a 3rd party component to dynamically load it's Listing model
        if($extension)
        {                 
            $name = $this->name . '_' . $extension;
            App::import('Model',$name,'jreviews');
            $class_name = inflector::camelize($this->name.'_'.$extension).'Model';
            
            if($extension != '' && class_exists($class_name)) 
            {
                $controller->Listing = new $class_name($controller->params);
                
                if(isset($controller->Review) && $controller->action != '_save') {                    
                    unset($controller->Review->joins['listings'],$controller->Review->joins['jreviews_categories'],$controller->Review->joins['criteria']);
                    $controller->Review->joins = array_merge($controller->Review->joins,$controller->Listing->joinsReviews);
					if(isset($controller->Listing->groupReviews) && isset($controller->Listing->groupReviews) != '') {
	                    $controller->Review->group = array_merge($controller->Review->group,$controller->Listing->groupReviews);
					}
                }
				
            } else {
                // Extension used in url doesn't have a plugin so we redirect to 404 error page
                $controller->autoLayout = false;
                $controller->autoRender = true;
                cmsFramework::redirect(cmsFramework::route('index.php?option=com_jreviews&url=404'));
            }
        }
                 
    }    
}
