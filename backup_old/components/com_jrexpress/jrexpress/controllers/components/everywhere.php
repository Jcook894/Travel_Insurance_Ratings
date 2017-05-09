<?php
/*
    JReviews Express - user reviews for Joomla
    Copyright (C) 2009  Alejandro Schmeichler

    JReviews Express is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    JReviews Express is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/

defined( 'MVC_FRAMEWORK') or die( 'Direct Access to this location is not allowed.' );

class EverywhereComponent extends S2Component {
	
	var $name = 'everywhere';
	
	var $notifyModel = null;
	
	var $validObserverModels = array('Review','ReviewReport');
	
    function startup(&$controller) {

        $this->c = & $controller;
        
        $this->app = $this->c->app;

        $this->loadListingModel($this->c);
        
        // Check if there's an observer model in the controller
        if(method_exists($this->c,'getNotifyModel')) 
        {
	        $this->notifyModel = & $controller->getNotifyModel();
       	
	        if(in_array($this->notifyModel->name,$this->validObserverModels)) {
	        	$this->notifyModel->addObserver('afterFindHook',$this);
        		$this->notifyModel->addObserver('afterSaveHook',$this);
	        }
        }
    } 	
    
    /**
     * Model observer adds listing information for reviews submitted from different extensions
     * Used in my reviews page
     */
    function afterFindHook(&$model, $results) {
    	
    	if(empty($results)) {
    		return $results;
    	}

    	switch($this->notifyModel->name) {
    		
    		case 'Review':

    			if(isset($this->c->EverywhereAfterFind) &&  $this->c->EverywhereAfterFind === true) {
    			
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
    					if(!file_exists(S2Paths::get('jrexpress', 'S2_COMPONENTS') . $this->name . DS . $EveryWhereModel . '.php')) {
    						unset($models[$extension]);
    					}
    				}

					$this->__initModels(array_values($models),$this->app);			

					# Loop through extensions found in the current page
					foreach($extensions AS $extension=>$listing_ids) {
						if(isset($models[$extension])) {
							if(!is_array($listing_ids)) $listing_ids = array($listing_ids);
							// Uses the current extension's model to get Listing info for the given listing ids
							$realKey = $this->{inflector::camelize($models[$extension])}->realKey;
							$listings[$extension] = $this->{inflector::camelize($models[$extension])}->findAll(array('conditions'=>'Listing.' . $realKey . ' IN ('. implode(',',$listing_ids) . ')'));						
                        }
					}					

    				# Merge the listing data to the Review Model results
    				foreach($results AS $key=>$result) {
    					if(isset($listings[$result['Review']['extension']]) 
    						&& isset($listings[$result['Review']['extension']][$result['Review']['listing_id']])) {
    						// Second condition above excludes reviews when the listing is not found for the review (i.e. the category for the listing was removed)
    						if(($this->c->name == 'reviews' && $this->c->action == 'myreviews') ||
    							($this->c->name == 'module_reviews' && $this->c->action == 'index') ||
    							($this->c->name == 'feeds')
    						)
    						{ // Exclude listing owner info because it replaces the reviewer info
	    						unset($listings[$result['Review']['extension']][$result['Review']['listing_id']]['User']);
	    						unset($listings[$result['Review']['extension']][$result['Review']['listing_id']]['Community']);
    						}
							$results[$key] = array_insert($results[$key],$listings[$result['Review']['extension']][$result['Review']['listing_id']]);
    					} else {
    						unset($results[$key]); // Removes reviews for extensions without Models
    					}
    				}
                          
					# Preprocess criteria and rating information
					$rating_test = current($results);
					if(isset($rating_test['Rating'])) {
						$results = $this->c->Review->processRatings($results);    							
					}

			    	return $results;
					
    			}
    						
    			break;
    			
    		case 'ReviewReport':
   			
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
    					if(!file_exists(S2Paths::get('jrexpress','S2_COMPONENTS') . $this->name . DS . $EveryWhereModel . '.php')) {
    						unset($models[$extension]);

    					}
    				}
					$this->__initModels(array_values($models),$this->app);			
					
					# Loop through extensions found in the current page
					foreach($extensions AS $extension=>$listing_ids) {
						if(isset($models[$extension])) {
							if(!is_array($listing_ids)) $listing_ids = array($listing_ids);
							// Uses the current extension's model to get Listing info for the given listing ids
							$realKey = $this->{inflector::camelize($models[$extension])}->realKey;
							$listings[$extension] = $this->{inflector::camelize($models[$extension])}->findAll(array('conditions'=>'Listing.' . $realKey . ' IN ('. implode(',',$listing_ids) . ')'));						
						}
					}					

    				# Merge the listing data to the Review Model results
    				foreach($results AS $key=>$result) {
    					if(isset($listings[$result['Review']['extension']])) {
    						$results[$key] = array_insert($results[$key],$listings[$result['Review']['extension']][$result['Review']['listing_id']]);
    					} else {
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
	function afterSaveHook(&$model) {

		if(method_exists($this->c->Listing,'afterSave')) {
//			appLogMessage(print_r($model->data,true),'afterSaveHook');
			$this->c->Listing->afterSave($model);
		}
		
//		appLogMessage('Average Rating: '. $model->data['average_rating'],'afterSaveHook');		
//		appLogMessage('New: '. $model->data['new'],'afterSaveHook');
		
		return true;
	}
       
    /**
     * Dynamic Listing Model Loading for jReviewsEverywhere extensions
     * Detects which extension is being used to load the correct Listing model
     *
     * @param object $controller
     * @param string $extension
     */
   	function loadListingModel(&$controller, $extension = null) {

   		if($controller->name == 'reviews' && $controller->action == '_save') {
   			$extension = Sanitize::getString($controller->data['Review'],'mode');
   		} else {
	   		$extension = $extension ? $extension : Sanitize::getString($controller->params,'extension',Sanitize::getString($controller->data,'extension'));
   		}
   		
   		if(!$extension && isset($controller->params['module'])) {// Final check for module parameter
   			$extension = Sanitize::getString($controller->params['module'],'extension','com_content');
   		}
                            
   		// Check if in listing detail page and it's a 3rd party component to dynamically load it's Listing model
        if($extension)
        {
			$name = $this->name . '_' . $extension;
        	App::import('Model',$name,'jrexpress');
            $class_name = inflector::camelize($this->name.'_'.$extension).'Model';
        	
			if($extension != '' && class_exists($class_name)) {

				$controller->Listing = new $class_name($controller->params);
				
				if(isset($controller->Review) && $controller->action != '_save') {					
					unset($controller->Review->joins['listings'],$controller->Review->joins['jreviews_categories'],$controller->Review->joins['criteria']);
					$controller->Review->joins = array_merge($controller->Review->joins,$controller->Listing->joinsReviews);
				}
			
			} else {
                // Extension used in url doesn't have a plugin so we redirect to 404 error page
				$controller->autoLayout = false;
				$controller->autoRender = true;
				cmsFramework::redirect(cmsFramework::route('index.php?option=com_jrexpress&url=404'));
			}
        }
	     		
    }
    
}
