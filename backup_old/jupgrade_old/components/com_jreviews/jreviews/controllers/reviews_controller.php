<?php
/**
 * JReviews - Reviews Extension
 * Copyright (C) 2010-2012 ClickFWD LLC
 * This is not free software, do not distribute it.
 * For licencing information visit http://www.reviewsforjoomla.com
 * or contact sales@reviewsforjoomla.com
**/

defined( 'MVC_FRAMEWORK') or die( 'Direct Access to this location is not allowed.' );

class ReviewsController extends MyController {
	
	var $uses = array('menu','user','captcha','criteria','review','field');
	
	var $helpers = array('assets','cache','routes','libraries','html','form','time','jreviews','custom_fields','rating','paginator','community','widgets');
	
	/**
	 * Everywhere component startup method automatically loads the Listing Model for the Everywhere component detail page
	 *
	 * @var unknown_type
	 */
	var $components = array('config','access','everywhere','activities');

	var $autoRender = true;

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
    
    // Need to return object by reference for PHP4
    function &getActivityModel() {
        return $this->Review;
    }     
    				
	function _edit() 
	{			
		$this->autoRender = false;
        $this->autoLayout = false;
        $response = array();
				
		$review_id = Sanitize::getInt($this->params,'review_id');
		
		$extension = $this->Review->getReviewExtension($review_id);

		// Dynamic loading Everywhere Model for given extension
		$this->Everywhere->loadListingModel($this,$extension);
		
		$fields = array(
			'Criteria.id AS `Criteria.criteria_id`',
			'Criteria.criteria AS `Criteria.criteria`',
			'Criteria.state AS `Criteria.state`',
			'Criteria.required AS `Criteria.required`',
			'Criteria.tooltips AS `Criteria.tooltips`',
			'Criteria.weights AS `Criteria.weights`',
            'Criteria.config AS `ListingType.config`'  # Configuration overrides
		);
		
		$review = $this->Review->findRow(
			array(
				'fields'=>$fields,
				'conditions'=>array('Review.id = ' . $review_id ),
			)
		);

        # Override global configuration
        isset($review['ListingType']) and $this->Config->override($review['ListingType']['config']);        

		$review['Criteria']['required'] = explode("\n",Sanitize::getVar($review['Criteria'],'required'));
		
        if ( count($review['Criteria']['required']) != count($review['Criteria']['criteria']) ) # every criteria must have 'Required' set (0 or 1). if not, either it's data error or data from older version of jr, so default to all 'Required'
		{
			$review['Criteria']['required'] = array_fill(0, count($review['Criteria']['criteria']), 1);
		}
				
		if (!$this->Access->canEditReview($review['User']['user_id'])) {
			return $this->ajaxError(s2Messages::accessDenied());
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
				'Access'=>$this->Access,
				'review'=>$review,
				'review_fields'=>$review_fields,
                'formTokenKeys'=>$this->formTokenKeys
			)	
		);

        return $this->ajaxUpdateDialog($this->render('reviews','create'));
	}
				
	function _save() 
    {         
        $this->autoRender = false;
        $this->autoLayout = false;
        $response = array();

        # Done here so it only loads on save and not for all controlller actions.        
        $this->components = array('security','notifications');
        $this->__initComponents();
        
        # Validate form token			
		if($this->invalidToken) {
            return $this->ajaxError(s2Messages::invalidToken());
        }
	
		$selected = '';
		$msg = '';
		$msgAlert = '';
		$msgTags = array();
	
		# Clean formValues
		$review_id = Sanitize::getInt($this->data['Review'],'id',0);
        $this->data['Review']['pid'] = $pid = Sanitize::getInt($this->data['Review'],'pid',0);
		
		if($review_id == 0) {
			$isNew = $this->Review->isNew = true;	
		} else {
			$isNew = $this->Review->isNew = false;
			$this->action = '_edit';
		}
		
		$this->data['Criteria']['id'] = Sanitize::getInt($this->data['Criteria'],'id',0);
		$this->data['Criteria']['state'] = Sanitize::getInt($this->data['Criteria'],'state',0);
		$this->data['Review']['pid'] = Sanitize::getInt($this->data['Review'],'pid');
		$this->data['Review']['email'] = Sanitize::html($this->data['Review'],'email','',true);
		$this->data['Review']['title'] = Sanitize::html($this->data['Review'],'title','',true);
		$this->data['Review']['comments'] = Sanitize::html($this->data['Review'],'comments','',true);
		$this->data['Review']['mode'] = Sanitize::html($this->data['Review'], 'mode', 'com_content',true);

        # Override configuration
        $listing_type = $this->Criteria->findRow(array('conditions'=>array('Criteria.id = ' . $this->data['Criteria']['id'])));
        isset($listing_type['ListingType']) and $this->Config->override($listing_type['ListingType']['config']);
        
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
                                return $this->ajaxUpdatePage('jr_review0Form',__t("You are not allowed to review your own listing.",true));                                       
                            }
                    }

                // Get reviewer type, for now editor reviews don't work in Everywhere components
                $this->data['Review']['author'] = $this->data['Review']['mode'] != 'com_content' ?
                    0 : 
                    (int) $this->Access->isJreviewsEditor($this->_user->id)
                ;                 
            }
        else
            {
                $currentReview = $this->Review->findRow(array('conditions'=>array('Review.id = ' . $review_id)),array());
   
                # Stop form data tampering 
                $formData = $this->data['Review'] + array('criteria_id'=>Sanitize::getInt($this->data['Criteria'],'id'));
                $formToken = cmsFramework::formIntegrityToken($formData,array_keys($this->formTokenKeys),false);

                if (!$this->Access->canEditReview($currentReview['User']['user_id']) || !$this->__validateToken($formToken)) {
                    return $this->ajaxError(s2Messages::accessDenied());
                }                
                $this->data['Review']['author'] = $currentReview['Review']['editor'];  
            }

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
                            return $this->ajaxUpdatePage('jr_review0Form',__t("You already submitted a review.",true));                   
				        }
				    else
				        {
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

		if ($isNew && $is_duplicate) 
        {
            return $this->ajaxUpdatePage('jr_review0Form',__t("You already submitted a review.",true));                   
        }
				
		# Validate standard fields
		$this->Review->validateInput($this->data['Review']['name'], "name", "text", __t("You must fill in your name.",true), !$this->_user->id && ($this->Config->reviewform_name == 'required' ? true : false));
		$this->Review->validateInput($this->data['Review']['email'], "email", "email", __t("You must fill in a valid email address.",true), ($this->Config->reviewform_email == 'required' ? true : false) && !$this->_user->id && $isNew);		
		$this->Review->validateInput($this->data['Review']['title'], "title", "text", __t("You must fill in a title for the review.",true), ($this->Config->reviewform_title == 'required' ? true : false));
	
		if ($listing_type['Criteria']['state'] == 1 ) //ratings enabled
		{
			# Validate rating fields
			$criteria_qty = $listing_type['Criteria']['quantity'];
			$ratingErr = 0;
            
            if(!isset($this->data['Rating']))
            {
                $ratingErr = $criteria_qty;
            }
            else
            {
                for ( $i = 0;  $i < $criteria_qty; $i++ ) 
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
		$review_valid_fields = $this->Field->validate($this->data,'review',$this->Access);
		$this->Review->validateErrors = array_merge($this->Review->validateErrors,$this->Field->validateErrors);	
		$this->Review->validateInput($this->data['Review']['comments'], "comments", "text", __t("You must fill in your comment.",true), ($this->Config->reviewform_comment == 'required' ? true : false));

		# Validate security code
		if ($isNew && $this->Access->showCaptcha())
		{
			if(!isset($this->data['Captcha']['code'])) 
            {				
				$this->Review->validateSetError("code", __t("The security code you entered was invalid.",true));
					
			} elseif ($this->data['Captcha']['code'] == '') 
            {	
				$this->Review->validateInput($this->data['Captcha']['code'], "code", "text", __t("You must fill in the security code.",true),  1);
			} 
            else 
            {
				if (!$this->Captcha->checkCode($this->data['Captcha']['code'],$this->ipaddress)) 
                {					
					$this->Review->validateSetError("code", __t("The security code you entered was invalid.",true));				
				}	
			}
		 }
		 
		# Process validation errors
		$validation = $this->Review->validateGetErrorArray();        
		if (!empty($validation)) 
        {
            if ($isNew && $this->Access->showCaptcha()) 
            {	
                // Replace captcha with new instance
                $captcha = $this->Captcha->displayCode();
                $response[] = "jQuery('.jr_captcha').find('img').attr('src','{$captcha['src']}');";                
                $response[] = "jQuery('.jr_captcha_code').val('');"; 
			} 
            return $this->ajaxValidation(implode('<br />',$validation),$response);
        }

		$savedReview = $this->Review->save($this->data, $this->Access, $review_valid_fields);  
		$review_id = $this->data['Review']['id'];

		// Error on review save 
        if (Sanitize::getString($savedReview,'err')) {
			return $this->ajaxError($savedReview['err']);
		}
	      
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
                $target_id = $isNew ? 'jr_review0Form' : 'jr_review_'.$review_id;
                $update_text = __t("Thank you for your submission. It will be published once it is verified.",true);
                return $this->ajaxUpdatePage($target_id,$update_text,'');                   
            }            
          
        // Get updated review info for non-moderated actions and plugin callback
        $fields = array(
            'Criteria.id AS `Criteria.criteria_id`',
            'Criteria.criteria AS `Criteria.criteria`',
            'Criteria.state AS `Criteria.state`', 
            'Criteria.tooltips AS `Criteria.tooltips`',
            'Criteria.weights AS `Criteria.weights`'            
        );
        
        $joins = $this->Listing->joinsReviews;
         
         // Triggers the afterFind in the Observer Model
        $this->EverywhereAfterFind = true;
        
        if(isset($this->viewVars['reviews']))
        {
            $review = current($this->viewVars['reviews']);                    
        } 
        else
        {
            $this->Review->runProcessRatings = true;
            $review = $this->Review->findRow(array(
                'fields'=>$fields,
                'conditions'=>'Review.id = ' . $this->data['Review']['id'],
                'joins'=>$joins
            ), array('afterFind' /* limit callbacks*/));
        }                            

        $this->set(
            array(
                'reviewType'=>'user',                
                'User'=>$this->_user,
                'Access'=>$this->Access,
                'reviews'=>array($review['Review']['review_id']=>$review)
            )
        );   
        
        $response = array();
        $fb_checkbox = Sanitize::getBool($this->data,'fb_publish');
        $facebook_integration = Sanitize::getBool($this->Config,'facebook_enable') 
            && Sanitize::getBool($this->Config,'facebook_reviews')  
            && $fb_checkbox;
		// Process non moderated actions
        # New user review
		if($isNew && !$this->data['Review']['author'])
    	    {			
			    $remove_class = true;
                $target_id = 'jr_user_reviews'; 
                $update_text = __t("Thank you for your submission.",true);
                $update_html = $this->render('reviews','reviews');            
			
                # Facebook wall integration
                $token = cmsFramework::getCustomToken($review['Review']['review_id']);
                $facebook_integration and $response[] = "
                    jQuery.get(s2AjaxUri+jreviews.ajax_params()+'&url=facebook/_postReview/id:{$review['Review']['review_id']}&{$token}=1');
                ";
                
                return $this->ajaxUpdatePage($target_id,$update_text,$update_html,compact('response','remove_class'));
		    } 

		# Edited user review
        if (!$isNew && !$this->data['Review']['author'])
            {	          
                // Setup vars for post submit effects
                $target_id = 'jr_review_'.$review_id;                    
                $update_text = __t("Your changes were saved.",true);
			    $update_html = $this->render('reviews','reviews');			
                  
			    return $this->ajaxUpdatePage($target_id,$update_text,$update_html);
		    }
		
		# New editor review
		if ($isNew && $this->data['Review']['author']) 
            {	
                $target_id = 'jr_review_'.$review_id;                    
                $update_text = (Sanitize::getInt($review['Criteria'],'state')!=2) ? __t("Thank you for your submission. Refresh the page to see your review.",true) : __t("Thank you for your submission. Refresh the page to see your comment.",true);
                
                # Facebook wall integration
                $token = cmsFramework::getCustomToken($review['Review']['review_id']);
                
                $facebook_integration and $response[] = "
                    jQuery.get(s2AjaxUri+jreviews.ajax_params()+'&url=facebook/_postReview/id:{$review['Review']['review_id']}&{$token}=1');
                ";                
                
                return $this->ajaxUpdatePage($target_id,$update_text,'',compact('response'));
            }        			

		# Edited editor review
		if (!$isNew && $this->data['Review']['author']) 
            {				
                $target_id = 'jr_review_'.$review_id;                    
                $update_text = __t("Your changes were saved, refresh the page to see them.",true);                                
                return $this->ajaxUpdatePage($target_id,$update_text);
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
        if($this->_user->id === 0) {
            $this->cacheAction = Configure::read('Cache.expires');
        }

        $page = array();
        $menu_id = Sanitize::getInt($this->params,'Itemid');
		
		$this->params['default_order'] = 'rdate';

		$sort = Sanitize::getString($this->params,'order',$this->params['default_order']);
        
        // Set layout
        $this->layout = 'reviews';
        $this->autoRender = false;
                        
         // Triggers the afterFind in the Observer Model
        $this->EverywhereAfterFind = true;
        
        $conditions = array('Review.published = 1');
        
        $extension = Sanitize::getString($this->params['data'],'extension');
        $extension and $conditions[] = "Review.mode = " . $this->quote($extension);
                                                                                       
        $cat_ids = cleanIntegerCommaList(Sanitize::getString($this->params['data'],'catid'));
        $section_id = Sanitize::getString($this->params['data'],'sectionid');

        if(!empty($cat_ids))
        {        
            if($extension == 'com_content') {
                if($this->cmsVersion == CMS_JOOMLA15) {
                    $conditions[] = 'Listing.catid IN (' .$cat_ids. ')'; 
                }
                else
                {
                    $this->Review->joins['ParentCategory'] = "LEFT JOIN #__categories AS ParentCategory ON Category.lft BETWEEN ParentCategory.lft AND ParentCategory.rgt";              
                    $conditions[] = 'ParentCategory.id IN (' .$cat_ids. ')';
                }
            }
            elseif($extension != '') {
                $conditions[] = 'JreviewsCategory.id IN (' .$cat_ids. ')';
            }
        }
        
        if($extension == 'com_content') 
        {
            $dir_id = Sanitize::getString($this->params['data'],'dirid');
            $section_id = Sanitize::getString($this->params['data'],'sectionid');
            $criteria_id = Sanitize::getString($this->params['data'],'criteriaid');            

            empty($cat_id) and !empty($section_id) and $conditions[] = 'Listing.sectionid IN (' .cleanIntegerCommaList($section_id). ')';
            empty($cat_id) and !empty($dir_id) and $conditions[] = 'JreviewsCategory.dirid IN (' . cleanIntegerCommaList($dir_id) . ')';
            empty($cat_id) and !empty($criteria_id) and $conditions[] = 'JreviewsCategory.criteriaid IN (' . cleanIntegerCommaList($criteria_id) . ')';
        }

        $queryData = array(
            'conditions'=>$conditions,
            'fields'=>array(
                'Review.mode AS `Review.extension`'
            ),
            'offset'=>$this->offset,
            'limit'=>$this->limit,
            'order'=>$this->Review->processSorting($sort)                    
        );
        
        if($sort == 'rating' || $sort == 'rrating')
        {
            $queryData['conditions'][] = 'Rating.ratings_sum > 0';
        }

        switch(Sanitize::getString($this->params,'type'))
        {
            case 'user':
                $queryData['conditions'][] = 'Review.author = 0';
                $title =  __t("Latest user reviews",true);   
            break;
            case 'editor':
                $queryData['conditions'][] = 'Review.author = 1';
                $title =  __t("Latest editor reviews",true);   
            break;
            default:
                $title =  __t("Latest reviews",true);               
            break;
        }

        # Don't run it here because it's run in the Everywhere Observer Component
        $this->Review->runProcessRatings = false;

        $reviews = $this->Review->findAll($queryData);

        if(empty($reviews)) {
            return __t("No reviews were found.",true);
        }
            
        $count = $this->Review->findCount($queryData);
        
        $menuParams = $this->Menu->getMenuParams($menu_id);
        $page['show_title'] = Sanitize::getInt($menuParams,'dirtitle');
        $page['title'] = $page['title_seo'] = trim(Sanitize::getString($menuParams,'title')) != '' ? Sanitize::getString($menuParams,'title') : $this->Menu->getMenuName($menu_id);

        if (!$page['show_title']) {
            $page['title'] = $title;
        } 
        
        $this->action = 'latest';
        
        $this->set(array(
                'Access'=>$this->Access,
                'User'=>$this->_user,
                'reviews'=>$reviews,
                'pagination'=>array(
                    'total'=>$count,
                    'offset'=>($this->page-1)*$this->limit
                )
                ,'page'=>$page
            )
        );
        
        return $this->render('reviews','reviews');
    }  
        	
	function myreviews( $params ) 
	{			
		if($this->_user->id === 0) {
			$this->cacheAction = Configure::read('Cache.expires');
		}

        $page = array();
        
		// Set layout
		$this->layout = 'reviews';
		$this->autoRender = false;
						
		 // Triggers the afterFind in the Observer Model
		$this->EverywhereAfterFind = true;
			
        $user_id = Sanitize::getInt($this->params,'user',$this->_user->id);        
		
        if(!$user_id)
        {
            echo $this->render('elements','login');
            return;
        }
	
		if (!$user_id) {
			$user_id = $this->_user->id;
		}

		$queryData = array(
				'conditions'=>array(
				'Review.userid= '. $user_id,
				'Review.published = 1',
//				'Review.mode = \'com_content\'', // Need to find reviews for all components
			),
			'fields'=>array(
				'Review.mode AS `Review.extension`'
			),
			'offset'=>$this->offset,
			'limit'=>$this->limit,
			'order'=>array('Review.created DESC')					
		);

		# Don't run it here because it's run in the Everywhere Observer Component
		$this->Review->runProcessRatings = false;

		$reviews = $this->Review->findAll($queryData);

		if(empty($reviews)) {
			return __t("No reviews were found.",true);
		}
			
		$count = $this->Review->findCount($queryData);

        $review = current($reviews);
        
		App::import('Helper','community','jreviews');
        
		$Community = ClassRegistry::getClass('CommunityHelper');
        
		$Community->Config = &$this->Config;
        
		$page['title'] = $page['title_seo'] = $page['description'] = sprintf(__t("Reviews written by %s",true),$Community->screenName($review,false));
         
		$this->set(array(
				'Access'=>$this->Access,
				'User'=>$this->_user,
				'reviews'=>$reviews,
				'pagination'=>array(
					'total'=>$count,
					'offset'=>($this->page-1)*$this->limit
				)
                ,'page'=>$page
			)
		);
		
		return $this->render('reviews','reviews');
	}  
		
	/**
	 * Function to display the user rank table based on reviews and usefulness
	 */
	function rankings($params) {

		$this->cacheAction = Configure::read('Cache.expires');
						
		$menu_id = Sanitize::getInt($this->params,'menu',Sanitize::getString($this->params,'Itemid'));
		
		# Get total number of reviewers
		$reviewer_count = $this->Review->getReviewerTotal();
				 
		# Get user rankings
        $rankings = $this->Review->getRankPage($this->page,$this->limit);

		
		$menuParams = $this->Menu->getMenuParams($menu_id);
		
		$page['title'] = $page['title_seo'] = trim(Sanitize::getString($menuParams,'title')) != '' ? Sanitize::getString($menuParams,'title') : $this->Menu->getMenuName($menu_id);

		$this->set(array(
			'reviewer_count'=>$reviewer_count,
			'rankings'=>$rankings,
			'pagination'=>array(
				'total'=>$reviewer_count
			),
			'page'=>$page
		));		
	}
	
	/**
	 * Periodically calls the review model method that rebuilds the user ranks table
	 * Called exclusively via ajax
	 *
	 */
	function _rebuildRanksTable()
	{
		# has the rebuilding interval expired?
		if ( $this->Config->ranks_rebuild_last + $this->Config->ranks_rebuild_interval * 3600 > time() )	{
            return;            
        }
		
		$this->Review->rebuildRanksTable();	
	}
}
