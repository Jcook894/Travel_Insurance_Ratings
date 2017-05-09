<?php
/**
 * JReviews - Reviews Extension
 * Copyright (C) 2010-2015 ClickFWD LLC
 * This is not free software, do not distribute it.
 * For licencing information visit https://www.jreviews.com
 * or contact sales@jreviews.com
**/
// no direct access
defined( 'MVC_FRAMEWORK') or die( 'Direct Access to this location is not allowed.' );

class AdminCriteriaRatingsController extends MyController {

	var $uses = array('criteria_rating','review');

	var $helpers = array();

    var $components = array('access','config');

	var $autoRender = false;

	var $autoLayout = false;

    var $__listings = array();

	function beforeFilter()
    {
		# Call beforeFilter of MyAdminController parent class
		parent::beforeFilter();
	}

    function addCriteria() {

    	$listing_type_id = Sanitize::getInt($this->params,'id');

    	if($listing_type_id)
    	{
    		$order = $this->CriteriaRating->getMaxOrder($listing_type_id) + 1;

			$data = $this->CriteriaRating->emptyModel(array('CriteriaRating'=>array('required'=>1,'listing_type_id'=>$listing_type_id,'ordering'=>$order)));

			if($this->CriteriaRating->store($data))
			{
				$this->set(array('listing_type_id'=>$listing_type_id,'row'=>$data));

				$output = $this->render('listing_types','criteria_row');

				return $output;
			}
    	}
    }

    // Reorder rating criteria for listing type

	function reorder()
    {
		$ordering = Sanitize::getVar($this->data,'order');

		$reorder = $this->CriteriaRating->reorder($ordering);

		return $reorder;
	}

    function _delete()
    {
        $response = array('success'=>false,'str'=>array());

        $id = Sanitize::getVar($this->params,'id');

        if(empty($id)) {

            return cmsFramework::jsonResponse($response);
        }

        // Delete the listing type
        $this->CriteriaRating->delete('criteria_id', $id);

        $response['success'] = true;

        return cmsFramework::jsonResponse($response);
    }
}
