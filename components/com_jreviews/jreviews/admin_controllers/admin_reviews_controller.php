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

class AdminReviewsController extends MyController {

	var $uses = array('review','criteria','menu','field','predefined_reply','media');

	var $components = array('config','access','everywhere','admin/admin_notifications','media_storage');

	var $helpers = array('routes','admin/admin_routes','html','admin/paginator','form','time','rating','custom_fields');

	var $autoRender = false;

	var $autoLayout = false;

	function beforeFilter() {

		$this->Access->init($this->Config);

		# Call beforeFilter of MyAdminController parent class
		parent::beforeFilter();

		$this->name = 'reviews';
	}


    // Need to return object by reference for PHP4
    function &getPluginModel(){
        return $this->Review;
    }

	// Need to return object by reference for PHP4
	function &getEverywhereModel() {
		return $this->Review;
	}

    // Need to return object by reference for PHP4
    function &getNotifyModel(){
        return $this->Review;
    }

    function index() {

        $this->action = 'browse';

        return $this->browse();
    }

	function browse()
    {
		$this->EverywhereAfterFind = true; // Triggers the afterFind in the Observer Model

		$this->Review->addStopAfterFindModel(array('Media','Field'));

		$extension = Sanitize::getString($this->params, 'extension');

		$conditions = $fields = $order = $where = array();

		$extensions = $components = array();

		// Get component list and generate select array
        $query = "
            SELECT
                DISTINCTROW `mode`
            FROM
                #__jreviews_comments
        ";

        $review_extensions = $this->Review->query($query,'loadColumn');

        foreach($review_extensions AS $key=>$val)
        {
            S2App::import('Model','everywhere_'.$val) and $val != 'com_content' and $components[] = array('value'=>$val,'text'=>inflector::camelize(str_replace('com_','',$val)));
        }

		$extensions = $components;

		$filter_entry_title =  Sanitize::getString($this->params, 'entry_title');

		$filter_order =  Sanitize::paranoid(Sanitize::getString($this->params, 'filter_order', 0 ));

		$filter_entry_id = array(Sanitize::getVar($this->params,'listing_id'));

		$filter_entry_id = array_filter($filter_entry_id);

		$filter_user = Sanitize::getInt($this->params,'user_id');

		$filter_review_type = Sanitize::getString($this->params,'type');

		// Begin query setup
		unset($this->Review->fields, $this->Review->joins);

		$this->Review->fields = array();

		if(Sanitize::getString($this->params,'referrer') != 'listing') {

			if($filter_entry_title == '') {

				$filter_entry_id = $this->params['listing_id'] = 0;
			}
		}

		// Process search & filtering options
		if($filter_entry_title != '' && empty($filter_entry_id)) {

			// Find all entry ids matching the title search

			if($extension == 'com_content')
            {
				$filter_entry_id = $this->Listing->findIdByTitle($filter_entry_title);
			}
			else {

				// Title search for everywhere extensions
				$className = inflector::camelize('everywhere_'.$extension.'_model');

				$EverywhereListing = ClassRegistry::getClass($className);

				if (method_exists($EverywhereListing, 'findIdByTitle'))
				{
					$filter_entry_id = $EverywhereListing->findIdByTitle($filter_entry_title);
				}
			}
		}
		elseif(!empty($filter_entry_id) && $filter_entry_title == '' && $extension == 'com_content') {

			$this->params['entry_title'] = $this->Listing->findTitleById($filter_entry_id);
		}

		if(!empty($filter_entry_id))
		{
			$conditions[] = "Review.pid IN (" . implode(',', $filter_entry_id) . ")";
		}
		else {

			$conditions[] = "Review.pid > 0";
		}

		if ($filter_user > 0 )
		{
			$conditions[] = "Review.userid = $filter_user";
		}

		if( $filter_review_type == 'user') {

			$conditions[] = 'Review.author = 0';
		}
		elseif ($filter_review_type == 'editor') {

			$conditions[] = 'Review.author = 1';
		}

        // $conditions['published'] = "Review.published >= 0";

		switch($filter_order) {
			case 1:
                unset($conditions['published']);
	   			$conditions[] = "Review.published = 0";
	   			$order[] = "Review.id DESC";
			break;
			case 2:
	   			$conditions[] = "Review.author ='0'";
	   			$order[] = "Review.id DESC";
			break;
			case 3:
	   			$conditions[] = "Review.author ='1'";
	   			$order[] = "Review.id DESC";
			break;
			case 4:
	   			$conditions[] = "Review.media_count > 0";
				break;
			case 5:
                unset($conditions['published']);
	   			$conditions[] = "Review.published = -2";
				break;
			case 6:
                unset($conditions['published']);
	   			$conditions[] = "Review.published = 1";
				break;
			default:
	   			$order[] = "Review.id DESC";
			break;
		}

		$fields = array(
			'Review.id AS `Review.review_id`',
			'Review.pid AS `Review.listing_id`',
			'Review.mode AS `Review.extension`',
			'Review.created AS `Review.created`',
			'Review.modified AS `Review.modified`',
			'Review.userid AS `User.user_id`',
			'Review.name AS `User.name`',
			'Review.username AS `User.username`',
			'Review.email AS `User.email`',
			'Review.ipaddress AS `User.ipaddress`',
			'Review.title AS `Review.title`',
			'Review.author AS `Review.editor`',
			'Review.published AS `Review.published`',
            'Review.owner_reply_approved AS `Review.owner_reply_approved`',
            'Review.owner_reply_text AS `Review.owner_reply_text`',
            'Review.owner_reply_note AS `Review.owner_reply_note`',
            'Review.media_count AS `Review.media_count`'
		);

		if($extension != '') {

			$conditions[] = 'Review.mode = ' . $this->Quote($extension);
		}

		$queryData = array(
			'fields'=>$fields,
			'conditions'=>$conditions,
			'offset'=>$this->offset,
			'limit'=>$this->limit,
			'order'=>$order
		);

		# Don't run it here because it's run in the Everywhere Observer Component
		$this->Review->runProcessRatings = false;

		$reviews = $this->Review->findAll($queryData);

		$count = $this->Review->findCount($queryData);

		$this->set(array(
				'stats'=>$this->stats,
				'reviews'=>$reviews,
				'filter_order'=>$filter_order,
				'extension'=>$extension,
				'extensions'=>$extensions,
				'entry_title'=>$filter_entry_title,
				'pagination'=>array('total'=>$count)
			)
		);

		return $this->render('reviews','browse');
	}

    function moderation()
    {
        $this->params = array();

        // Begin query setup
        $conditions = array();

        $order = array();

        $this->limit = 10;

        $processed = Sanitize::getInt($this->params,'processed');

        $this->offset = $this->offset - $processed;

        $order[] = "Review.created DESC";

        $conditions[] = "Review.published = 0";

        $conditions[] = "Review.pid > 0";

        $queryData = array(
            'fields'=>array('Review.review_note AS `Review.review_note`'),
            'conditions'=>$conditions,
            'order'=>$order,
            'offset'=>$this->offset,
            'limit'=>$this->limit
        );

        $this->EverywhereAfterFind = true; // Triggers the afterFind in the Observer Model

        # Don't run it here because it's run in the Everywhere Observer Component
        $this->Review->runProcessRatings = false;

        $reviews = $this->Review->findAll($queryData);

        # Pre-process all urls to sef
        $this->_getListingSefUrls($reviews);

        $total = $this->Review->findCount(array('conditions'=>$conditions));

       if(!empty($reviews))
        {
            $predefined_replies = $this->PredefinedReply->findAll(array(
                'fields'=>array('PredefinedReply.*'),
                'conditions'=>array('reply_type = "review"')
                ));

            $this->set(array(
            	'processed'=>$processed,
                'reviews'=>$reviews,
                'predefined_replies'=>$predefined_replies,
                'total'=>$total
            ));
        }

        return $this->render('reviews','moderation');

    }

	function edit()
    {
		$this->autoRender = false;

		$this->autoLayout = false;

		$response = array();

		$review_id = Sanitize::getInt($this->params,'id');

        $extension = $this->Review->getReviewExtension($review_id);

        // Dynamic loading Everywhere Model for given extension
        $this->Everywhere->loadListingModel($this,$extension);

		$review = $this->Review->findRow(array(
			'conditions'=>array('Review.id = ' . $review_id ),
		));

        # Override global configuration
        isset($review['ListingType']) and $this->Config->override($review['ListingType']['config']);

		# Get custom fields for review form is form is shown on page
		$review_fields = $this->Field->getFieldsArrayNew($review['Criteria']['criteria_id'], 'review',$review);

		$this->set(
			array(
				'inAdmin'=>true,
				'passedArgs'=>$this->passedArgs,
				'User'=>$this->_user,
				'Access'=>$this->Access,
				'review'=>$review,
				'review_fields'=>$review_fields
			)
		);

		return $this->render('reviews','create');
	}

    function _publish()
	{
		$include_reject_state = true;

        $result = $this->Review->publish(Sanitize::getInt($this->params,'id'), $include_reject_state);

        return json_encode($result);
    }

    function update()
    {
        $id = Sanitize::getInt($this->params,'id');

        $row = $this->Review->findRow(array('conditions'=>array('Review.id = ' . $id)));

        return cmsFramework::jsonResponse($row);
    }

    function _saveModeration()
    {
        $response = array('success'=>false,'str'=>array());

    	// This is a review being moderated
       	$this->Review->store($this->data);

       	if($this->data['Review']['published'] != 0)
       	{
            // Update listing totals
            !$this->Review->updateRatingAverages(array('listing_id'=>$this->data['Listing']['listing_id'], 'extension'=>$this->data['Listing']['extension']));

            clearCache('', 'views');

            clearCache('', '__data');
        }

        return cmsFramework::jsonResponse($response);
    }

	function _save()
    {
        $response = array('success'=>false,'str'=>array());

		Configure::write('EverywhereMediaModel',true); // Skip Media afterFind code in EverywhereModel

		# Clean formValues
		$this->data['Review']['pid'] = $pid = Sanitize::getInt($this->data['Review'],'pid',0);

		$this->data['Criteria']['id'] = Sanitize::getInt($this->data['Criteria'],'id',0);

		$this->data['Review']['id'] = Sanitize::getInt($this->data['Review'],'id',0);

		$this->data['Review']['title'] = Sanitize::getString($this->data['Review'],'title','');

        $comments = Sanitize::stripScripts(Sanitize::getString($this->data['__raw']['Review'],'comments',''));

        $this->data['Review']['comments'] = stripslashes($comments);

		$this->data['Review']['mode'] = Sanitize::getString($this->data['Review'], 'mode', 'com_content');

        $listingType = $this->Criteria->findRow(array('conditions'=>array('Criteria.id = ' . $this->data['Criteria']['id'])));

        // Complete the data with the Listing Type info

        $this->data = array_insert($this->data,$listingType);

		# Validate rating fields

		if($listingType['Criteria']['state'] == 1 ) //ratings enabled
		{
			$criteria_qty = count($listingType['CriteriaRating']);

			$ratingErr = 0;

            if(!isset($this->data['Rating']))
            {
                $ratingErr = $criteria_qty;
            }
            else {
                foreach($listingType['CriteriaRating'] AS $i=>$row)
                {
                    if (!isset($this->data['Rating']['ratings'][$i])
                        ||
                        (empty($this->data['Rating']['ratings'][$i])
                            || $this->data['Rating']['ratings'][$i] == 'undefined'
                            || (float)$this->data['Rating']['ratings'][$i] > $this->Config->rating_scale)
                    ) {
                        $ratingErr++;
                    }
                }
            }

			$this->Review->validateInput('', "rating", "text", sprintf(__t("You are missing a rating in %s criteria.",true),$ratingErr), $ratingErr);
		}

		# Validate custom fields

        // June 9, 2016 - Form passed validation when a required field was removed directly from the
        // Stop tampering with field names - removal of inputs from the form

        $this->Field->addBackEmptyRequiredFields($this->data, 'review', $this->Access);

		$review_valid_fields = $this->Field->validate($this->data,'review',$this->Access);

		$this->Review->validateErrors = array_merge($this->Review->validateErrors,$this->Field->validateErrors);

		# Process validation errors
		$validation = $this->Review->validateGetErrorArray();

		if(!empty($validation)) {

			$response['str'] = $validation;

			return cmsFramework::jsonResponse($response);
		}

        // Make the criteria rating definition available in the review model to process the ratings

        $this->data['CriteriaRating'] = $listingType['CriteriaRating'];

		$savedReview = $this->Review->save($this->data, $this->Access, $review_valid_fields);

		// Error check
		if ( !$savedReview ){

			$response['str'][] = 'DB_ERROR';

			return cmsFramework::jsonResponse($response);
		}

		$response['success'] = true;

		return cmsFramework::jsonResponse($response);
	}

    function _delete()
    {
        $response = array('success'=>false,'str'=>array());

		$ids = Sanitize::getVar($this->params,'cid', Sanitize::getInt($this->params,'id'));

		if(empty($ids)) {

 			return cmsFramework::jsonResponse($response);
		}

        $deleted = $this->Review->del($ids);

        if ($deleted) {
        	$response['success'] = true;
		}

		return cmsFramework::jsonResponse($response);
    }

	function changeReviewType()
    {
		$response = array('success'=>false,'msg'=>'','str'=>array());

		$response['success'] = false;

        $id = Sanitize::getInt($this->params,'id');

        if(!$id) {

        	return cmsFramework::jsonResponse($response);
        }

        $query = "
        	SELECT
        		pid AS listing_id, mode AS extension, author AS editor
        	FROM
        		#__jreviews_comments
        	WHERE
        		id = " . $id;

        $review = $this->Review->query($query,'loadObject');

        $query = "
        	SELECT
        		count(*)
        	FROM
        		#__jreviews_comments
        	WHERE
        		pid = " . $review->listing_id . " AND mode = " . $this->Quote($review->extension);

		$editor_reviews = $this->Review->query($query,'loadResult');

		if($editor_reviews || (!$editor_reviews && $this->Config->author_review < 2) || $this->Config->author_review == 2) {

			$query = "
				UPDATE
					#__jreviews_comments
				SET
					author = IF(author = 1, 0, 1)
				WHERE
					id = " . $id;

			if(!$this->Review->query($query)) {

	        	$response['str'][] = 'DB_ERROR';

	        	return cmsFramework::jsonResponse($response);
			}

			$query = "
				SELECT
					pid AS listing_id, mode AS extension
				FROM
					#__jreviews_comments
				WHERE
					id = " . $id;

			$listing = $this->Review->query($query,'loadObject');

			// Update listing totals
			if (!$this->Review->updateRatingAverages(array('listing_id'=>$listing->listing_id, 'extension'=>$listing->extension)) ){

				$response['msg'] = 2;
			}

			$response['success'] = true;

			$response['state'] = $review->editor ? 0 : 1;

			// Clear cache
			clearCache('', 'views');
			clearCache('', '__data');
		}
		else {
			$response['msg'] = 1;
		}

	    return cmsFramework::jsonResponse($response);
	}

	function recalculateRatingRanks()
	{
		$response = $this->rebuildStep5();

        $response = json_decode($response,true);

        return $response['success'] ?
            JreviewsLocale::getPHP('RATINGS_RECALCULATED') :
			JreviewsLocale::getPHP('PROCESS_REQUEST_ERROR');
	}

	function rebuildReviewRatings()
	{
		return $this->render('reviews','rebuild');
	}

    // Clean up - removes orphaned ReviewRating and ListingRating rows and resets rating and review totals in ListingTotals for listings without reviews

	function rebuildStep1()
	{
        $response = array('success'=>true);

		$response['success'] = $this->Review->cleanupReviewRatings();

		return cmsFramework::jsonResponse($response);
	}

    // Recalculates review rating averages

	function rebuildStep2()
	{
        $response = array('success'=>true);

		$response['success'] = $this->Review->updateReviewRatingAverage();

		return cmsFramework::jsonResponse($response);
	}

    // Recalculates listing rating averages by criteria

	function rebuildStep3()
	{
        $response = array('success'=>true);

		$response['success'] = $this->Review->updateListingCriteriaAverages();

		return cmsFramework::jsonResponse($response);
	}

    // Recalculates listing rating/review totals by criteria

	function rebuildStep4()
	{
        $response = array('success'=>true);

		$response['success'] = $this->Review->updateListingTotalAverages();

		return cmsFramework::jsonResponse($response);
	}

    // Recalculates bayesian averages per criteria and for each listing

	function rebuildStep5()
	{
        $response = array('success'=>true,'complete'=>true);

		$response['success'] = $this->Review->updateBayesianAverages();

		return cmsFramework::jsonResponse($response);
	}
}
