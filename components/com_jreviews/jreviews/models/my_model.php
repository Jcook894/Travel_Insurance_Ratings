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

    var $plgBeforeBeforeSaveTrigger = array();

	var $plgBeforeSaveTrigger = array();

	var $plgAfterSaveTrigger = array();

	var $plgAfterFindTrigger = array();

	var $plgAfterAfterFindTrigger = array();

    var $plgAfterListingSaveValidationTrigger = array();

    var $plgAfterCategoryListTrigger = array();

	var $stopAfterFindModels = array();

	var $runAfterFindModels = array();

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

     function plgBeforeBeforeSave()
    {
        $args = func_get_args();
        return $this->notifyObservers('plgBeforeBeforeSave',$args[0]);
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

    function plgAfterListingSaveValidation()
    {
        return $this->notifyObservers('plgAfterListingSaveValidation');
    }

    function plgAfterCategoryList($results)
    {
        return $this->notifyObservers('plgAfterCategoryList', $results);
    }

    /**
     * Dump the observsers (PHP 5).
     */
    function __destruct()
    {
        unset($this->plgAfterDeleteTrigger);
        unset($this->plgBeforeDeleteTrigger);
        unset($this->plgBeforeBeforeSaveTrigger);
        unset($this->plgBeforeSaveTrigger);
        unset($this->plgAfterSaveTrigger);
        unset($this->plgAfterFindTrigger);
        unset($this->plgAfterAfterFindTrigger);
        unset($this->plgBeforeRenderListingSaveTrigger);
        unset($this->plgAfterListingSaveValidationTrigger);
        unset($this->plgAfterCategoryListTrigger);
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

            if((!isset($observer->published) || $observer->published) && method_exists($observer, $event))
            {
                $results = $observer->{$event}($this,$results);
            }
        }
        return $results;
    }

    /**
     * Register an observer to be notified
     * @param $observer The observer.
     */
    function addObserver($event,&$observer)
    {
        if(!isset($observer->plugin_order)) $observer->plugin_order = 0;
        $this->{$event.'Trigger'}[] = &$observer;
    }

    function cmp( $a, $b )
    {
      if(  $a->plugin_order ==  $b->plugin_order ){ return 0 ; }
      return ($a->plugin_order < $b->plugin_order) ? -1 : 1;
    }


	/***
	 * Auxiliary functions to enable/disable completion of addition model queries in afterFind method
	 */
	function getStopAfterFindModels()
	{
		return $this->stopAfterFindModels;
	}

	function runAfterFindModel($model_name)
	{
		return !isset($this->stopAfterFindModels[$model_name]) || isset($this->runAfterFindModels[$model_name]);
	}

	function addStopAfterFindModel($model_names)
	{
		if(!is_array($model_names)) $model_names = array($model_names);

		foreach($model_names AS $name) {
			$this->stopAfterFindModels[$name] = true;
		}
	}

	function addRunAfterFindModel($model_names)
	{
		if(!is_array($model_names)) $model_names = array($model_names);

		foreach($model_names AS $name) {
			$this->runAfterFindModels[$name] = true;
		}
	}

	function clearAllAfterFindModel()
	{
		$this->stopAfterFindModels = array();

		$this->runAfterFindModels = array();

	}

    /**
     * Everywhere Model methods
     */

    function addCategoryFiltering(& $conditions, & $Access, $options)
    {
        if(Sanitize::getInt($options,'listing_id') > 0) return;

        $params = Sanitize::getVar($this,'passedArgs');

        $extension = Sanitize::getString($options, 'extension');

        $cat_auto = Sanitize::getInt($options, 'cat_auto');

        $cat_id = cleanIntegerCommaList(Sanitize::getString($options,'cat_id'));

        if($extension != '') {

            if($cat_auto && method_exists($this,'catUrlParam')) {

                if($cat_id = Sanitize::getInt($params,$this->catUrlParam())){

                    $conditions[] = 'JreviewsCategory.id IN (' . $cat_id . ')';
                }

            }
            elseif($cat_id) {

                $conditions[] = 'JreviewsCategory.id IN (' . $cat_id. ')';
            }
        }
    }

    function addListingFiltering(& $conditions, & $Access, $options) {}
}
