<?php
/**
 * JReviews - Reviews Extension
 * Copyright (C) 2010-2015 ClickFWD LLC
 * This is not free software, do not distribute it.
 * For licencing information visit https://www.jreviews.com
 * or contact sales@jreviews.com
**/

defined( 'MVC_FRAMEWORK') or die( 'Direct Access to this location is not allowed.' );

class SearchController extends MyController {

	var $uses = array('menu','user','field','criteria','category');

	var $helpers = array('assets','html','libraries','custom_fields','form','time','jreviews');

	var $components = array('config','access','advanced_search_request');

	var $autoRender = false; //Output is returned

	var $layout = 'default';

	function beforeFilter() {

		parent::beforeFilter();

	}

	function index()
	{
		$this->action = 'advanced';

		$criteria_id = Sanitize::getInt($this->params,'criteria');

        $menu_id = Sanitize::getInt($this->params,'Itemid');

		$dateFields = array();

		// Check if the criteria list should be limited to specified ids
		$separator = "_"; // For url specified criterias

		$used_criterias = array();

		if($criteria_id > 0) {

			$criterias = array($criteria_id);

		} else {

			if(isset($criteria_id) && is_array($criteria_id))
			{
				$criterias_tmp = explode("_",urldecode($criteriaid));

				for ($i=0;$i<count($criterias_tmp);$i++)
				{
					if ( (int) $criterias_tmp[$i] > 0) {
						$used_criterias[$i] = $criterias_tmp[$i];
					}
				}

				if (count($used_criterias)==1)
				{
					$separator = ","; // For menu param specified criterias
					$criterias_tmp = explode(",",urldecode($criteriaid));
					$used_criterias = array();
					for ($i=0;$i<count($criterias_tmp);$i++) {
						if ( (int) $criterias_tmp[$i] > 0) {
							$used_criterias[$i] = $criterias_tmp[$i];
						}
					}
				}
			}

			if (empty($used_criterias))
			{
				// Find the criteria that has been assigned to com_content categories
				$query = "
					SELECT
						DISTINCTROW criteriaid
					FROM
						#__jreviews_categories
					WHERE
						`option`='com_content'
				";

				$used_criterias = $this->Criteria->query($query,'loadColumn');
			}

			$used_criterias = implode(',', $used_criterias);

			$criterias = $this->Criteria->getSelectList(
				array(
					'id' => $used_criterias,
					'searchOnly' => true,
// Feb 15, 2016 - Commented line below because in cases where only one listing type has field groups
// the others are not displayed in listing type list
					// 'withFieldGroupsOnly' => true
				)
			);

			if (count($criterias) == 1)
			{
				$criterias = array($criterias[0]->value);
			}
		}

        /******************************************************************
        * Process page title and description
        *******************************************************************/
        $page = $this->createPageArray($menu_id);

        $this->set('page',$page);

		// With one listing type, there's no need to select it to see the form.
		if (count($criterias) == 1)
		{
			$criteria_id = $criterias[0];

			# Process custom fields
			$search = 1;

			$searchFields = $this->Field->getFieldsArrayNew($criteria_id, 'listing', null, $search);

            # Get category list for selected listing type
            $categoryList = $this->Category->getCategoryList(array('type_id'=>$criteria_id));

			$this->set(
				array(
					'criteria_id'=>$criteria_id,
					'categoryList'=>$categoryList,
					'searchFields'=>$searchFields
				)
			);

		// If there's more than one criteria show the criteria select list
		}
        elseif (count($criterias) >= 1)
        {
			$this->set(
				array(
					'criterias'=>$criterias
				)
			);
		}

		return $this->render('search','advanced');
	}

	function _process()
    {
    	$url = $this->AdvancedSearchRequest->process($this->data);

        if($this->ajaxRequest)
        {
        	return $url;
        }
        else {

			cmsFramework::redirect($url);
        }
	}

	/*
	* Loads the search form
	*/
	function _loadForm()
	{
        $this->autoLayout = false;

        $response = array();

		$criteria_id = Sanitize::getInt($this->params,'criteria_id');

		$dateFieldsEntry = $categoryList = array();

		if ($criteria_id > 0)
        {
		    # Process custom fields
		    $search = 1;

		    $searchFields = $this->Field->getFieldsArrayNew($criteria_id, 'listing', null, $search);

            $categories = $this->Category->getCategoryList(array(
				'disabled'=>true,
				'type_id'=>array(0,$criteria_id),
				'listing_type'=>true
			));

            $length = count($categories);

            # Loop through categories to determine which ones show in the adv. search because it's possible to
            # have nested parent categories without listing types and we need to include those as well

            $prev = null;

            reset($categories);

			for($i = 0; $i < $length; $i++) {

				$curr = current($categories);

				$show_in_list = false;

				if($length > 1)
				{
					$next = next($categories);

					if($next && $curr->criteriaid == 0 && $next->parent_id !== $curr->value)
					{
						$show_in_list = false;

						if($prev && $prev->criteriaid == 0 && !empty($categoryList))
						{
							$last = array_pop($categoryList);

							if($last->criteriaid > 0)
							{
								$categoryList[] = $last;
							}
						}
					}
					else {

						if($curr->criteriaid == 0 && !$next)
						{
							$show_in_list = false;
						}
						else {
							$show_in_list = true;
						}
					}

	            	// Parent category
	            	if($show_in_list) {

	            		$curr->disabled = 0;

	            		$categoryList[$curr->value] = $curr;
	            	}
				}

				$prev = $curr;
            }

		    $this->set(
			    array(
				    'criteria_id'=>$criteria_id,
				    'categoryList'=>$categoryList,
				    'searchFields'=>$searchFields
			    )
		    );

		    return $this->render('search','advanced_form');
	    }
	}

}