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

class MyModel extends S2Model 
{	
   /* Array of observers wanting to be notified when the model is saved. */
    var $afterSaveHookEvent = array();
    var $afterFindHookEvent = array();    
    var $afterAfterFindHookEvent = array();    
    
    /**
     * Override the afterSave callback and notify our observers.
     * Remember that if this method doesn't return true, the model will be
     * tagged as invalid and fail to save.
     */
    function afterSaveHook() 
    {
        return $this->notifyObservers('afterSaveHook');
    }
    
    function afterFindHook($results) 
    {    			
        return $this->notifyObservers('afterFindHook',$results);    	
    }
    
     function afterAfterFindHook($results) 
     {    			
        return $this->notifyObservers('afterAfterFindHook',$results);    	
    }   
        
    /**
     * Dump the observsers (PHP 5).
     */
    function __destruct() 
    {
        unset($this->afterSaveHookEvent);
        unset($this->afterFindHookEvent);
        unset($this->afterAfterFindHookEvent);
    }
    
    /**
     * Notify our observers.
     */
    function notifyObservers() 
    {
    	$results = true;	
    	$args = func_get_args();
    	
    	$event = $args[0];

    	if(isset($args[1])) {
    		$results = $args[1];
    	}

        // The observers must implement the $event(&$model) method.             
        foreach($this->{$event.'Event'} as $observer) {

            $results = $observer->{$event}($this,$results);
        }

        return $results;
    }    
    
    /**
     * Register an observer to be notified during afterSave().
     * @param $observer The observer.
     */
    function addObserver($event,&$observer) 
    {
        $this->{$event.'Event'}[] = &$observer;
    }
    
    /**
     * Returns the tmpl_list and tmpl_suffix variables for the theme engine
     *
     * @return unknown
     */
    function getTemplateSettings() {
    	return array();
    }	
}