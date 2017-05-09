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

class ModuleTotalsController extends MyController {
	
    var $uses = array('menu','review');
	
    var $components = array('config');
    
	var $autoRender = false;
	
	var $autoLayout = false;
	
	var $layout = 'module';
		
	function beforeFilter() {					
		# Call beforeFilter of MyController parent class
		parent::beforeFilter();		
	}
	
	function index()
	{	
        $module_id = Sanitize::getInt($this->params,'module_id',Sanitize::getInt($this->data,'module_id'));

		$this->viewSuffix = Sanitize::getString($this->params['module'],'tmpl_suffix');
		
        $cache_file = 'modules_totals_'.$module_id.'_'.md5(serialize($this->params['module']));

        $page = $this->cached($cache_file);

        if($page) {
            return $page;
        }                		
		
        // Initialize variables		
		$extension = 'com_content';

		// Automagically load and initialize Everywhere Model
		App::import('Model','everywhere_'.$extension,'jrexpress');
		$class_name = inflector::camelize('everywhere_'.$extension).'Model';
  
        $conditions = array(); 
        
        if($extension!=''){
            $conditions[] = "Review.mode = '".$extension."'";
        }
                    
        if(class_exists($class_name)) {        
		    $this->Listing = new $class_name();
		    $this->Listing->_user = $this->_user;						    			    		    		    		                
			$listings = $this->Listing->findCount(array(),'DISTINCT Listing.'.$this->Listing->realKey);
            $reviews = $this->Review->findCount(array('conditions'=>$conditions),'DISTINCT Review.id');   
        }
        		    
		# Send variables to view template		
		$this->set(array(
				'listing_count'=>isset($listings) ? $listings : 0,
                'review_count'=>isset($reviews) ? $reviews : 0
		));
		
		$page = $this->render('modules','totals');
        
        # Save cached version
        $this->cacheView('modules','totals',$cache_file, $page);
        
        return $page;
	}	
}