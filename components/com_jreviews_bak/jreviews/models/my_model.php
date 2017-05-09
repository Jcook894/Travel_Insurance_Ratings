<?php
/**
 * An observable base model.
 *
 * @author Chris Lewis <chris@silentcitizen.com> - 10/13/2006  * 
 * 
 * @modify_date 03/17/2008
 * @modified_by ClickFWD LLC
**/

defined( 'MVC_FRAMEWORK') or die( 'Direct Access to this location is not allowed.' );

class MyModel extends S2Model {
    
   /* Array of observers wanting to be notified when the model is saved. */
    var $plgAfterDeleteTrigger = array();
    var $plgBeforeDeleteTrigger = array();
    var $plgBeforeSaveTrigger = array();
    var $plgAfterSaveTrigger = array();
    var $plgAfterFindTrigger = array();    
    var $plgAfterAfterFindTrigger = array();    
    
    /**
     * Define plugin callbacks and notify our observers.
     */
    function plgAfterDelete() 
    {
        $args = func_get_args();
        return $this->notifyObservers('plgAfterDelete',$args);
    }
    
    function plgBeforeDelete() 
    {
        $args = func_get_args();
        return $this->notifyObservers('plgBeforeDelete',$args);
    }    
    
    function plgAfterFind($results) 
    {
        return $this->notifyObservers('plgAfterFind',$results);
    }
    
     function plgAfterAfterFind($results) 
     {                             
        return $this->notifyObservers('plgAfterAfterFind',$results);
     }   
    
     function plgBeforeSave() 
    {
        $args = func_get_args();
        return $this->notifyObservers('plgBeforeSave',$args[0]);
    }
    
    function plgAfterSave() 
    {
        return $this->notifyObservers('plgAfterSave');
    }
    
    /**
    * Intercepts the response after all validation checks have completed,
    * custom fields and review are saved.
    */
    function plgBeforeRenderListingSave()
    {
        return $this->notifyObservers('plgBeforeRenderListingSave');
    }    
        
    /**
     * Dump the observsers (PHP 5).
     */
    function __destruct() 
    {
        unset($this->plgAfterDeleteTrigger);
        unset($this->plgBeforeDeleteTrigger);        
        unset($this->plgBeforeSaveTrigger);
        unset($this->plgAfterSaveTrigger);
        unset($this->plgAfterFindTrigger);
        unset($this->plgAfterAfterFindTrigger);
        unset($this->plgBeforeRenderListingSaveTrigger);
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
        
        // Reorder trigger
        usort($this->{$event.'Trigger'},array($this,'cmp'));       
        
        // The observers must implement the $event(&$model) method.             
        foreach($this->{$event.'Trigger'} as $observer) 
        {   
//            $action = isset($observer->c) ? $observer->c->action : 'no-action';
//            echo "[{$action}][{$event}][{$observer->name}][{$observer->plugin_order}]".'<br />';
            (!isset($observer->published) or $observer->published) and $results = $observer->{$event}($this,$results);
        }
        return $results;
    }    
    
    /**
     * Register an observer to be notified during afterSave().
     * @param $observer The observer.
     */
    function addObserver($event,&$observer) 
    {   
        if(!isset($observer->plugin_order)) $observer->plugin_order = 0;                                                                               
        $this->{$event.'Trigger'}[] = &$observer;
    }
    
    /**
     * Returns the tmpl_list and tmpl_suffix variables for the theme engine
     *
     * @return unknown
     */
    function getTemplateSettings() {
        return array();
    } 
    
    function cmp( $a, $b )
    { 
      if(  $a->plugin_order ==  $b->plugin_order ){ return 0 ; } 
      return ($a->plugin_order < $b->plugin_order) ? -1 : 1;
    } 
}
