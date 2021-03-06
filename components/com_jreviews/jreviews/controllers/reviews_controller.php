<?php
/**
 * JReviews - Reviews Extension
 * Copyright (C) 2010-2015 ClickFWD LLC
 * This is not free software, do not distribute it.
 * For licencing information visit https://www.jreviews.com
 * or contact sales@jreviews.com
**/

defined( 'MVC_FRAMEWORK') or die( 'Direct Access to this location is not allowed.' );

class ReviewsController extends MyController {

	var $uses = array('article','menu','user','category','criteria','review','field','media');

	var $helpers = array('assets','routes','libraries','html','text','form','time','jreviews','custom_fields','rating','paginator','community','widgets','media');

	/**
	 * Everywhere component startup method automatically loads the Listing Model for the Everywhere component detail page
	 *
	 * @var unknown_type
	 */
	var $components = array('config','access','everywhere','media_storage');

	var $autoRender = false;

	var $autoLayout = true;

    var $formTokenKeys = array('id'=>'review_id','pid'=>'listing_id','mode'=>'extension','criteria_id'=>'criteria_id');

	function beforeFilter()
    {
		# Call beforeFilter of MyController parent class
		parent::beforeFilter();
	}

    // Need to return object by reference for PHP4
    function &getPluginModel() {
        return $this->Review;
    }

    // Need to return object by reference for PHP4
    function &getNotifyModel() {
        return $this->Review;
    }

    // Need to return object by reference for PHP4
    function &getEverywhereModel() {
        return $this->Review;
    }

	function _edit()
	{
        $this->autoLayout = false;

        $response = array();

		$review_id = Sanitize::getInt($this->params,'review_id');

		$extension = $this->Review->getReviewExtension($review_id);

		// Dynamic loading Everywhere Model for given extension
		$this->Everywhere->loadListingModel($this,$extension);

		$review = $this->Review->findRow(array(
            'conditions'=>array('Review.id = ' . $review_id ),
        ));

        # Override global configuration
        isset($review['ListingType']) and $this->Config->override($review['ListingType']['config']);

		if (!$this->Access->canEditReview($review['User']['user_id']))
        {
			return JreviewsLocale::getPHP('ACCESS_DENIED');
		}

        # Set the theme suffix
        if($review['Review']['extension'] == 'com_content')
        {
            $this->Theming->setSuffix(array('listing_id'=>$review['Review']['listing_id']));
        }

		# Get custom fields for review form is form is shown on page
		$review_fields = $this->Field->getFieldsArrayNew($review['Criteria']['criteria_id'], 'review', $review);

        $review['Review']['criteria_id'] = $review['Criteria']['criteria_id']; # Form integrity

		$this->set(
			array(
				'User'=>$this->_user,
				'review'=>$review,
				'review_fields'=>$review_fields,
                'formTokenKeys'=>$this->formTokenKeys
			)
		);

        return $this->render('reviews','create');
	}

	function _save()
    {
        $this->autoLayout = false;

        $response = array('success'=>false,'str'=>array());

        # Done here so it only loads on save and not for all controlller actions.
        $this->components = array('security','notifications');

        $this->__initComponents();

        # Validate form token
		if($this->invalidToken) {

            $response['str'][] = 'INVALID_TOKEN';

            return cmsFramework::jsonResponse($response);
        }

		# Clean formValues

		$review_id = Sanitize::getInt($this->data['Review'],'id',0);

        $referrer = Sanitize::getString($this->data,'referrer','detail');

		$this->data['Review']['pid'] = $pid = Sanitize::getInt($this->data['Review'],'pid',0);

		if($review_id == 0) {

			$isNew = $this->Review->isNew = true;

		}
        else {

        	$isNew = $this->Review->isNew = false;

        	$this->action = '_edit';
		}

        $this->data['isNew'] = $isNew;

		$response['is_new'] = $isNew;

		$this->data['Criteria']['id'] = Sanitize::getInt($this->data['Criteria'],'id',0);

        $this->data['Review']['id'] = Sanitize::getInt($this->data['Review'],'id');

		$this->data['Review']['pid'] = Sanitize::getInt($this->data['Review'],'pid');

		$this->data['Review']['email'] = Sanitize::html($this->data['Review'],'email','',true);

		$this->data['Review']['title'] = Sanitize::html($this->data['Review'],'title','',true);

        if($this->Config->review_comment_wysiwyg) {
            $comments = Sanitize::stripScripts(Sanitize::getString($this->data['__raw']['Review'],'comments',''));
            $this->data['Review']['comments'] = stripslashes($comments);
        }
        else {
            $this->data['Review']['comments'] = Sanitize::html($this->data['Review'],'comments','',true);
        }

		$this->data['Review']['mode'] = Sanitize::html($this->data['Review'], 'mode', 'com_content',true);

        $this->data['Review']['listing_type_id'] = $this->data['Criteria']['id'];

        # Stop form data tampering
        $formData = $this->data['Review'] + array('criteria_id'=>Sanitize::getInt($this->data['Criteria'],'id'));

        // Check tampering with IDs
        $formToken = cmsFramework::formIntegrityToken($formData,array_keys($this->formTokenKeys),false);

        if(!$this->__validateToken($formToken)) {
            $response['str'][] = 'ACCESS_DENIED';
            return cmsFramework::jsonResponse($response);
        }

        // June 9, 2016 - Form passed validation when a required field was removed directly from the
        // Stop tampering with field names - removal of inputs from the form

        $this->Field->addBackEmptyRequiredFields($this->data, 'review', $this->Access);

        # Override configuration

        $listingType = $this->Criteria->findRow(array('conditions'=>array('Criteria.id = ' . $this->data['Criteria']['id'])));

        isset($listingType['ListingType']) and $this->Config->override($listingType['ListingType']['config']);

        // Complete the data with the Listing Type info

        $this->data = array_insert($this->data,$listingType);

        if($isNew || (!$isNew && !$this->Access->isManager())) {

            $this->data['Review']['name'] = $this->data['Review']['username'] = Sanitize::html($this->data['Review'],'name','',true);
        }

        // Check if user allowed to post new review
        if($isNew)
        {
            if(method_exists($this->Listing,'getListingOwner'))
            {
                $owner = $this->Listing->getListingOwner($this->data['Review']['pid']);

                if(!$this->Access->canAddReview($owner['user_id']))
                {
					$response['str'][] = 'REVIEW_NOT_OWN_LISTING';

                    return cmsFramework::jsonResponse($response);
                }
            }

            // Get reviewer type, for now editor reviews don't work in Everywhere components
            $this->data['Review']['author'] = $this->data['Review']['mode'] != 'com_content' ?
                0 :
                (int) $this->Access->isJreviewsEditor($this->_user->id)
            ;
        }
        else {

            $currentReview = $this->Review->findRow(array('conditions'=>array('Review.id = ' . $review_id)),array());

            if(!$this->Access->canEditReview($currentReview['User']['user_id'])) {

                $response['str'][] = 'ACCESS_DENIED';

				return cmsFramework::jsonResponse($response);
            }

            $this->data['Review']['author'] = $currentReview['Review']['editor'];
        }

        $response['review_type'] = $this->data['Review']['author'] ? 'editor' : 'user';

        # If we are in multiple editor review mode, and this editor has already posted an editor review,
		# he is not allowed to post any kind of review.
		# if we are in single-editor-review mode, his review will become a user review.
		if ( $isNew && $this->data['Review']['mode'] == 'com_content' && $this->data['Review']['author'] )
	    {
		    if ($this->Review->findCount(array('conditions'=>array(
				    'Review.pid = ' . $this->data['Review']['pid'],
				    'Review.author = 1',
				    "Review.mode = '" . $this->data['Review']['mode'] . "'",
				    $this->Config->author_review == 2 ? 'Review.userid = '.$this->_user->id : '1 = 1'

		    ))))
		    {
			    if ( $this->Config->author_review == 2 )
		        {
                    $response['str'][] = 'REVIEW_DUPLICATE';

					return cmsFramework::jsonResponse($response);

                }
                else {

                    $this->data['Review']['author'] = 0;
			    }
		    }
	    }

        # check for duplicate reviews
        $is_jr_editor = $this->Access->isJreviewsEditor($this->_user->id);

       	$is_duplicate = false;

        // It's a guest so we only care about checking the IP address if this feature is not disabled and
        // server is not localhost
        if(!$this->_user->id)
        {
            if(!$this->Config->review_ipcheck_disable && $this->ipaddress != '127.0.0.1' && $this->ipaddress != '::1')
            {
                // Do the ip address check everywhere except in localhost
               $is_duplicate = (bool) $this->Review->findCount(array(
				   'conditions'=>array(
						'Review.pid = '.$this->data['Review']['pid'],
						"Review.ipaddress = '{$this->ipaddress}'",
						"Review.mode = '{$this->data['Review']['mode']}'",
						"Review.published >= 0"
					),
				    'session_cache'=>false
				));
            }
        }
        elseif(
            (!$is_jr_editor && !$this->Config->user_multiple_reviews)  // registered user and one review per user allowed when multiple reviews is disabled
            ||
            ($is_jr_editor && $this->Config->author_review == 2) // editor and one review per editor allowed when multiple editor reviews is enabled
        )
        {
            $is_duplicate = (bool) $this->Review->findCount(array(
				'conditions'=>array(
					'Review.pid = '.$this->data['Review']['pid'],
					"(Review.userid = {$this->_user->id}" .
						(
							$this->ipaddress != '127.0.0.1' && $this->ipaddress!= '::1' && !$this->Config->review_ipcheck_disable && !$is_jr_editor //&& (!$is_jr_editor || !$this->Config->review_ipcheck_disable)
						?
							" OR Review.ipaddress = '{$this->ipaddress}') "
						:
							')'
						),
					"Review.mode = '{$this->data['Review']['mode']}'",
					"Review.published >= 0"
				),
				'session_cache'=>false
			));
        }

		if($isNew && $is_duplicate)
        {
            $response['str'][] = 'REVIEW_DUPLICATE';

			return cmsFramework::jsonResponse($response);
        }

		# Validate standard fields
        $username = Sanitize::getString($this->data,'username');

        $register_guests = Sanitize::getBool($this->viewVars,'register_guests');

		$this->Review->validateInput($this->data['Review']['name'], "name", "text", 'VALIDATE_NAME', !$this->_user->id && (($register_guests && $username) || $this->Config->reviewform_name == 'required' ? true : false));

		$this->Review->validateInput($this->data['Review']['email'], "email", "email", 'VALIDATE_EMAIL', (($register_guests && $username) || $this->Config->reviewform_email == 'required' ? true : false) && !$this->_user->id && $isNew);

		$this->Review->validateInput($this->data['Review']['title'], "title", "text", 'REVIEW_VALIDATE_TITLE', ($this->Config->reviewform_title == 'required' ? true : false));

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

			$this->Review->validateInput('', "rating", "text", array('REVIEW_VALIDATE_CRITERIA',$ratingErr), $ratingErr);
		}

		# Validate custom fields
		$review_valid_fields = $this->Field->validate($this->data,'review',$this->Access);

		$this->Review->validateErrors = array_merge($this->Review->validateErrors,$this->Field->validateErrors);

		$this->Review->validateInput($this->data['Review']['comments'], "comments", "text", 'REVIEW_VALIDATE_COMMENT', ($this->Config->reviewform_comment == 'required' ? true : false));

		# Process validation errors

		$validation = $this->Review->validateGetErrorArray();

		if (!empty($validation))
        {
			$response['str']  = $validation;

            return cmsFramework::jsonResponse($response);
        }

        // Make the criteria rating definition available in the review model to process the ratings

        $this->data['CriteriaRating'] = $listingType['Criteria']['state'] == 1 ? $listingType['CriteriaRating'] : array();

		$savedReview = $this->Review->save($this->data, $this->Access, $review_valid_fields);

		$review_id = Sanitize::getInt($this->data['Review'],'id');

		$response['review_id'] = $review_id;

		// Error on review save
        if (!Sanitize::getBool($savedReview,'success')) {

            $response['str'][] = 'DB_ERROR';

			return cmsFramework::jsonResponse($response);
		}

        $joins = $this->Listing->joinsReviews;

         // Triggers the afterFind in the Observer Model
        $this->EverywhereAfterFind = true;

        if(isset($this->viewVars['reviews']))
        {
            $review = current($this->viewVars['reviews']);
        }
        else {

            $this->Review->runProcessRatings = true;

            $review = $this->Review->findRow(array(
                'conditions'=>'Review.id = ' . $review_id,
                'joins'=>$joins
            ));
        }

        $this->set(
            array(
                'reviewType'=>'user',
                'User'=>$this->_user,
                'review'=>$review,
                'listing'=>$review
            )
        );

		// Everything below is a successful submission
		$response['success'] = true;

        // Process moderated actions
        if(
            ($isNew && $this->Access->moderateReview() && !$this->data['Review']['author'])
            // New user review + moderation on
            ||
            (!$isNew && ($this->Config->moderation_review_edit && $this->Access->moderateReview()) && !$this->data['Review']['author'])
            // Edited user review + moderation on
            ||
            ($isNew && $this->Config->moderation_editor_reviews && $this->data['Review']['author'])
            // Editor review + moderation on
            ||
            (!$isNew && ($this->Config->moderation_editor_review_edit && $this->Config->moderation_editor_reviews && $this->Access->moderateReview()) && $this->data['Review']['author'])
            // Edited editor review + moderation on, uses the review moderation as an extra check for when other groups edit the editor reviews
        )
            {
				$update_html = $this->render('reviews','review_moderation');

				$response['moderation'] = true;

				$response['html'] = $update_html;

				return cmsFramework::jsonResponse($response);
            }

        $fb_checkbox = Sanitize::getBool($this->data,'fb_publish');

        $facebook_integration = Sanitize::getBool($this->Config,'facebook_enable')
            && Sanitize::getBool($this->Config,'facebook_reviews')
            && $fb_checkbox;


		$response['moderation'] = false;

		// Process non moderated actions
        # New user review
		if($isNew && !$this->data['Review']['author'])
	    {
            $response['html'] = $this->render('reviews','review_layout');

            # Facebook wall integration
			if($facebook_integration) {

            	$token = cmsFramework::getCustomToken($review_id);

				$response['facebook'] = true;

				$response['token'] = $token;
			}

            return cmsFramework::jsonResponse($response);
	    }

		# Edited user review
        if (!$isNew && (!$this->data['Review']['author'] || $referrer == 'list'))
        {
            if($referrer == 'list') {

                $response['html'] = $this->render('reviews','review_list_layout');
            }
            else {

                $response['html'] = $this->render('reviews','review_layout');
            }

			return cmsFramework::jsonResponse($response);
	    }

		# Editor review, new and edited
		if($this->data['Review']['author'])
        {
			$response['html'] = '';

            # Facebook wall integration
			if($facebook_integration) {

            	$token = cmsFramework::getCustomToken($review_id);

				$response['facebook'] = true;

				$response['token'] = $token;
			}

			return cmsFramework::jsonResponse($response);
        }
	}

    function latest_editor()
    {
        $this->params['type'] = 'editor';

        return $this->latest('editor');
    }

    function latest_user()
    {
        $this->params['type'] = 'user';

        return $this->latest('user');
    }

    function latest()
    {
        $this->action = 'latest';

        $page = array();

        $menu_id = Sanitize::getInt($this->params,'Itemid');

        $extension = Sanitize::getString($this->params['data'],'extension');

        $cat_id = Sanitize::getString($this->params['data'],'catid');

		$this->params['default_order'] = 'rdate';

		$sort = Sanitize::getString($this->params,'order');

        $total_special = Sanitize::getInt($this->data,'total_special');

        if($total_special > 0) {

            $total_special <= $this->limit and $this->limit = $total_special;
        }

        // generate canonical tag for urls with order param
        $canonical = $sort != '' ? true : false;

        if($sort == '') {
            $sort = $this->params['default_order'];
        }

        // Set layout

        $this->layout = 'reviews';

        $this->autoRender = false;

         // Triggers the afterFind in the Observer Model
        $this->EverywhereAfterFind = true;

        $conditions = array('Review.published = 1');

        $extension and $conditions[] = "Review.mode = " . $this->Quote($extension);

        if(isset($this->Listing))
        {
            $state = 1;

            $this->Listing->addCategoryFiltering($conditions, $this->Access, compact('extension','state', 'cat_id'));

            $this->Listing->addListingFiltering($conditions, $this->Access, compact('state'));
        }

        if($extension == 'com_content')
        {
            unset($this->Review->joins['JreviewsCategory'], $this->Review->joins['Criteria']);
        }

        $queryData = array(
            'conditions'=>$conditions,
            'fields'=>array(
                'Review.mode AS `Review.extension`'
            ),
            'offset'=>$this->offset,
            'limit'=>$this->limit
        );

        # Modify query for correct ordering

        if($this->action != 'custom' || ($this->action == 'custom' && empty($this->Review->order)))
        {
            $queryData['order'] = $this->Review->processSorting($sort);
        }

        if($sort == 'rating' || $sort == 'rrating')
        {
            $queryData['conditions'][] = 'Review.rating > 0';
        }

        switch(Sanitize::getString($this->params,'type'))
        {
            case 'user':
                $queryData['conditions'][] = 'Review.author = 0';
            break;
            case 'editor':
                $queryData['conditions'][] = 'Review.author = 1';
            break;
            default:
            break;
        }

        # Don't run it here because it's run in the Everywhere Observer Component

        $this->Review->runProcessRatings = false;

        // Sep 25, 2016 - added to exclude reviews from unpublished categories and listings
        if ($extension == '')
        {
            $queryData = $this->Everywhere->createUnionQuery($queryData,array('listing_id'=>'Review.pid','extension'=>'Review.mode'));
        }

        $reviews = $this->Review->findAll($queryData);

        if(empty($reviews))
        {
            return $this->render('reviews','reviews_noresults');
        }

        $count = $this->Review->findCount($queryData);

        if($total_special > 0 && $total_special < $count)
        {
            $count = $total_special;
        }

        /******************************************************************
        * Process page title and description
        *******************************************************************/
        $page = $this->createPageArray($menu_id);

        /******************************************************************
        * Generate SEO canonical tags for sorted pages
        *******************************************************************/
        if($canonical) {

            $page['canonical'] = cmsFramework::getCurrentUrl('order');
        }

        $this->set(array(
                'User'=>$this->_user,
                'reviews'=>$reviews,
                'pagination'=>array(
                    'total'=>$count,
                    'offset'=>($this->page-1)*$this->limit,
                    'ajax'=>Sanitize::getInt($this->Config, 'paginator_ajax', 0)
                )
                ,'page'=>$page
            )
        );

        return $this->render('reviews','reviews');
    }

    # Custom List menu - reads custom where and custom order from menu parameters
    function custom()
    {
        $menu_id = Sanitize::getInt($this->params,'Itemid');

        $params = $this->Menu->getMenuParams($menu_id);

        $extension = Sanitize::getString($params,'extension');

        $custom_where = Sanitize::getString($params,'custom_where');

        $custom_order = Sanitize::getString($params,'custom_order');

        if($custom_where !='') {

            $custom_where = str_replace('{user_id}',$this->_user->id,$custom_where);

            $this->Review->conditions[] = '(' . $custom_where . ')';

            // If custom where includes conditions for ReviewField model then we add a join to those tables
            if(strstr($custom_where,'ReviewField.')) {

                $this->Review->joins['ReviewField'] = "LEFT JOIN #__jreviews_review_fields AS ReviewField ON ReviewField.reviewid = Review.id";
            }

            // If custom where includes conditions for Field models then we add a join to those tables
            if(strstr($custom_where,'Field.'))
            {
                $this->params['data']['extension'] = 'com_content';

                $this->Review->joins['Field'] = "LEFT JOIN #__jreviews_content AS Field ON Field.contentid = Review.pid";
            }
        }

        $custom_order !='' and $this->Review->order[] = $custom_order;

        return $this->latest();
    }

	function myreviews( $params )
	{
        // Set layout
        $this->layout = 'reviews';

        $this->autoRender = false;

		 // Triggers the afterFind in the Observer Model
		$this->EverywhereAfterFind = true;

        $user_id = Sanitize::getInt($this->params,'user',$this->_user->id);

        $page = $this->createPageArray();

        $extension = Sanitize::getString($page['menuParams'],'extension');

        $cat_id = Sanitize::getString($page['menuParams'],'catid');

        if(!$user_id)
        {
            $this->set(array(
                'pagination'=>array(
                    'total'=>0,
                    'offset'=>($this->page-1)*$this->limit
                )
            ));

            return $this->render('elements','login');
        }

		if (!$user_id)
        {
			$user_id = $this->_user->id;
		}

        $conditions = array(
            'Review.userid= '. $user_id,
            'Review.published = 1'
        );

        $extension != '' and $conditions[] = 'Review.mode = ' . $this->Quote($extension);

        if(isset($this->Listing))
        {
            $state = 1;

            $this->Listing->addCategoryFiltering($conditions, $this->Access, compact('state', 'cat_id'));

            $this->Listing->addListingFiltering($conditions, $this->Access, compact('state'));
        }

        if($extension == 'com_content')
        {
            unset($this->Review->joins['JreviewsCategory'], $this->Review->joins['Criteria']);
        }

		$queryData = array(
			'conditions'=>$conditions,
			'fields'=>array(
				'Review.mode AS `Review.extension`'
			),
			'offset'=>$this->offset,
			'limit'=>$this->limit,
			'order'=>array('`Review.created` DESC')
		);

		# Don't run it here because it's run in the Everywhere Observer Component
		$this->Review->runProcessRatings = false;

        // Sep 25, 2016 - added to exclude reviews from unpublished categories and listings
        if ($extension == '')
        {
            $queryData = $this->Everywhere->createUnionQuery($queryData,array('listing_id'=>'Review.pid','extension'=>'Review.mode'));
        }

		$reviews = $this->Review->findAll($queryData);

		// if(empty($reviews)) {

		// 	return $this->render('reviews','reviews_noresults');
		// }

		$count = $this->Review->findCount($queryData);

        /******************************************************************
        * Process page title and description
        *******************************************************************/

        $name_choice = constant('UserModel::_USER_' . strtoupper($this->Config->name_choice) );

        $user_name = '';

        if($user_id > 0)
        {
            $this->User->fields = array();

            $user_name = $this->User->findOne(
                array(
                    'fields'=>array('User.' . $name_choice . ' AS `User.name`'),
                    'conditions'=>array('User.id = ' . $user_id)
                )
            );

        }
        elseif($this->_user->id > 0) {

            $user_name = $this->_user->{$name_choice};
        }

        if($user_name == '')
        {
            return cmsFramework::raiseError( 404, s2Messages::errorGeneric() );
        }

		$page['title'] =
            $page['title_seo'] =
                $page['description'] = sprintf(JreviewsLocale::getPHP('REVIEW_WRITTEN_BY'),$user_name);

		$this->set(array(
				'User'=>$this->_user,
				'reviews'=>$reviews,
				'pagination'=>array(
					'total'=>$count,
					'offset'=>($this->page-1)*$this->limit,
                    'ajax'=>1
				)
                ,'page'=>$page
			)
		);

		return $this->render('reviews','reviews');
	}

	/**
	 * Function to display the user rank table based on reviews and usefulness
	 */
	function rankings()
    {
        $this->layout = 'reviewers';

		$menu_id = Sanitize::getInt($this->params,'menu',Sanitize::getString($this->params,'Itemid'));

		# Get total number of reviewers
		$reviewer_count = $this->Review->getReviewerTotal();

		# Get user rankings
        $rankings = $this->Review->getRankPage($this->page,$this->limit);

        $menu_id = Sanitize::getInt($this->params,'Itemid');

        /******************************************************************
        * Process page title and description
        *******************************************************************/
        $page = $this->createPageArray($menu_id);

		$this->set(array(
			'reviewer_count'=>$reviewer_count,
			'rankings'=>$rankings,
			'pagination'=>array(
				'total'=>$reviewer_count,
                'ajax'=>1
			),
            'page'=>$page
		));

        return $this->render('reviews', 'rankings');
	}
}
