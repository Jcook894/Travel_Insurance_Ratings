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

class ReviewsController extends MyController {
	
	var $uses = array('menu','user','captcha','listing','criteria','review');
	
	var $helpers = array('cache','routes','libraries','html','form','time','jreviews','rating','paginator','community');
	
	var $components = array('config','access','everywhere');

	var $autoRender = true;

	var $autoLayout = true;

	function beforeFilter() {
		
		# Call beforeFilter of MyController parent class
		parent::beforeFilter();
	}
	
	// Need to return object by reference for PHP4
	function &getNotifyModel() {
		return $this->Review;
	}
						
	function edit($params) 
	{			
		$this->autoRender = true;
				
		$review_id = Sanitize::getInt($this->params,'id');
		
		$extension = $this->Review->getReviewExtension($review_id);

		// Dynamic loading Everywhere Model for given extension
		$this->Everywhere->loadListingModel($this,$extension);
		
//		unset($this->Review->joins['listings'],$this->Review->joins['jreviews_categories'],$this->Review->joins['criteria']);
		
		$fields = array(
			'Criteria.id AS `Criteria.criteria_id`',
			'Criteria.criteria AS `Criteria.criteria`',
			'Criteria.tooltips AS `Criteria.tooltips`',
			'Criteria.weights AS `Criteria.weights`'		
		);
		
		$review = $this->Review->findRow(
			array(
				'fields'=>$fields,
				'conditions'=>array('Review.id = ' . $review_id ),
//				'joins'=>$this->Listing->joinsReviews
			)
		);
				
		if (!$this->Access->canEditReview($review['User']['user_id'])) {
			echo cmsFramework::noAccess();
			$this->autoRender = false;
			return;
		}
		
		# Get custom fields for review form is form is shown on page
		$this->set(
			array(
				'User'=>$this->_user,
				'Access'=>$this->Access,
				'review'=>$review,
			)	
		);

		$this->action = 'create'; // Uses the create template			
	
	}
				
	function _save($params) {

		$xajax = new xajaxResponse();

		# Validate form token			
		if(isset($this->invalidToken) && $this->invalidToken) {
			$xajax->alert('Invalid Token');
			return $xajax;
		}
		
		# Load the notifications observer model component and initialize it. 
		# Done here so it only loads on save and not for all controlller actions.		
		$this->components = array('security','notifications');
		$this->__initComponents();
					
//		$xajax->alert(print_r($this->data,true));
//		$xajax->assign("submitButton","disabled",false);
//		return $xajax;
		
		$selected = '';
		$msg = '';
		$msgAlert = '';
		$msgTags = array();
	
		# Clean formValues
		$this->data['Review']['pid'] = $pid = Sanitize::getInt($this->data['Review'],'pid',0);
		
		if(Sanitize::getInt($this->data['Review'],'id') == 0) {
			$isNew = true;	
		} else {
			$isNew = false;
			$this->action = '_edit';
		}
		
		$this->data['Criteria']['id'] = Sanitize::getInt($this->data['Criteria'],'id',0);
		$this->data['Review']['pid'] = Sanitize::getInt($this->data['Review'],'pid');
		$this->data['Review']['name'] = $this->data['Review']['username'] = Sanitize::html($this->data['Review'],'name','',true);
		$this->data['Review']['email'] = Sanitize::html($this->data['Review'],'email','',true);
		$this->data['Review']['title'] = Sanitize::html($this->data['Review'],'title','',true);
		$this->data['Review']['comments'] = Sanitize::html($this->data['Review'],'comments','',true);
		$this->data['Review']['mode'] = Sanitize::html($this->data['Review'], 'mode', 'com_content',true);

//		$xajax->alert(print_r($this->data,true));
//		$xajax->assign("submitButton","disabled",false);
//		return $xajax;

		# Check if user allowed to post new review
		if($isNew) {
			if(method_exists($this->Listing,'getListingOwner')) {
				$listing_owner_id = $this->Listing->getListingOwner($this->data['Review']['pid']);
				if(!$this->Access->canAddReviews($this->_user->id,$listing_owner_id)) {
					$xajax->alert(__t("You are not allowed to review your own listing.",true));
					return $xajax;
				}
			}
		}
		
		# Check for duplicate reviews based on userid and pid if userid > 0
		if (!$this->Config->user_multiple_reviews && $isNew)
		{
			$duplicates_user = 0;
			
			if ( $this->_user->id > 0) 
			{
				$duplicates_user = $this->Review->findCount(
					array(
						'conditions'=>array(
							'Review.pid = ' . $this->data['Review']['pid'],
							"Review.userid = " . $this->_user->id,
							"Review.mode = '" . $this->data['Review']['mode'] . "'"
						)
					)
				);
			}
			
			$duplicates_ipaddress = $this->Review->findCount(
				array(
					'conditions'=>array(
						'Review.pid = ' . $this->data['Review']['pid'],
						"Review.ipaddress = '" . $_SERVER['REMOTE_ADDR'] . "'",
						"Review.mode = '" . $this->data['Review']['mode'] . "'"
					)
				)
			);					 				
			
			if ($duplicates_user + $duplicates_ipaddress > 0) {
				$xajax->alert(__t("You already submitted a review, thank you. We don't allow duplicates.",true));
				$xajax->assign("submitButton","value",__t("Duplicates not allowed.",true));
				$xajax->assign("submitButton","disabled",true);
				return $xajax;
			}
		}
				
		# Validate standard fields
		$this->Review->validateInput($this->data['Review']['name'], "name", "text", __t("You must fill in your name.",true), !$this->_user->id);

		$this->Review->validateInput($this->data['Review']['email'], "email", "email", __t("You must fill in a valid email address.",true), $this->Config->reviewform_email && !$this->_user->id && $isNew);
		
		$this->Review->validateInput($this->data['Review']['title'], "title", "text", __t("You must fill in a title for the review.",true), $this->Config->reviewform_title);
	
		# Validate rating fields
		$criteria_qty = count($this->data['Rating']['ratings']);
		
		$ratingErr = 0;
		
		for ( $i = 0;  $i < $criteria_qty; $i++ ) 
		{
			if (isset($this->data['Rating']['ratings'][$i]) && (!$this->data['Rating']['ratings'][$i] || $this->data['Rating']['ratings'][$i]=='' || $this->data['Rating']['ratings'][$i]=='undefined')) {
				$ratingErr++;
			}
		}
	
		$this->Review->validateInput('', "rating", "text", sprintf(__t("You are missing a rating in %s criteria.",true),$ratingErr), $ratingErr);

		$this->Review->validateInput($this->data['Review']['comments'], "comments", "text", __t("You must fill in your comment.",true), $this->Config->reviewform_comment);

		# Validate security code
		if ($isNew && $this->Access->showCaptcha)
		{
			if(!isset($this->data['Captcha']['code'])) {
				
				$this->Review->validateSetError("code", __t("The security code you entered was invalid.",true));
					
			} elseif ($this->data['Captcha']['code'] == '') {
	
				$this->Review->validateInput($this->data['Captcha']['code'], "code", "text", __t("You must fill in the security code.",true),  1);
	
			} else {

				if (!$this->Captcha->checkCode($this->data['Captcha']['code'],$_SERVER['REMOTE_ADDR'])) {
					
					$this->Review->validateSetError("code", __t("The security code you entered was invalid.",true));
				
				}	
			}
		 }
		 
		# Process validation errors
		$msg = $isNew ? $this->Review->validateGetError() : $this->Review->validateGetErrorAlert();
	
		if ($msg != '') {
			
			# Validation failed	
			// Reissue form token
			$xajax->assign('jr_ReviewToken'.(!$isNew ? 'Edit' : ''),'value',$this->Security->reissueToken());
			
			if ($isNew) {
	
				$xajax->assign("msg", "innerHTML", $msg);
				
				// Replace captcha with new instance
				$captcha = $this->Captcha->displayCode();

				$xajax->assign("captcha","src",$captcha['src']);

				$xajax->assign("code","value","");
	
			} else {
	
				$xajax->alert($msg);
	
			}
	
			if($isNew) {
				$xajax->assign("submitButton","disabled",false);			
				$xajax->assign("cancel","disabled",false);
			} else {
				$xajax->assign("submitButtonEdit","disabled",false);			
				$xajax->assign("cancelEdit","disabled",false);				
			}
			return $xajax;
		}
												
		$savedReview = $this->Review->save($this->data, $this->Access);

		$review_id = $this->data['Review']['id'];
		
		if ($savedReview['err']) {
			// Error on review save
			$xajax->assign( "msg", "innerHTML", $savedReview['err']);
			return $xajax;
		}
	
		# No errors saving review, continue updating display with messages
		
		// Close thickbox window
		if(!$isNew) {
			$xajax->script("tb_remove();");	
		}	

		# NEW USER REVIEW + MODERATION OFF
		if($isNew && !$this->Access->moderateReview && !Sanitize::getVar($this->data['Review'],'author'))
		{			
			$fields = array(
				'Criteria.id AS `Criteria.criteria_id`',
				'Criteria.criteria AS `Criteria.criteria`',
				'Criteria.tooltips AS `Criteria.tooltips`',
				'Criteria.weights AS `Criteria.weights`'			
			);
			
			$joins = $this->Listing->joinsReviews;
			
			$review = $this->Review->findRow(array(
				'fields'=>$fields,
				'conditions'=>'Review.id = ' . $this->data['Review']['id'],
				'joins'=>$joins
			));
	
			$this->set(
				array(
					'User'=>$this->_user,
					'Access'=>$this->Access,
					'reviews'=>array($this->data['Review']['id']=>$review)
				)
			);
			
			$review = $this->render('reviews','reviews');			

			# Effects to hide form, scroll up, show success message and add new review at the top of the list.		
			$xajax->script( "jQuery('#jr_reviewform').remove();");		
			
			$xajax->prepend( "jr_user_reviews","innerHTML", $review );
			
			$xajax->assign( "jr_review_".$this->data['Review']['id'],'style.display','none');
			
			$xajax->script("jQuery('#jr_user_reviews').scrollTo(750,150);");
						
			$linkMsg = '<div id="message_inner_'.$this->data['Review']['id'].'" style="background-color:#FEFF9F;margin:10px;padding:4px;border:1px solid #CCC;text-align:center;font-weight:bold;">'.__t("Your changes were saved.",true).'</div>';
			
			$xajax->insert("jr_review_" . $this->data['Review']['id'],"div","jr_review_message_" . $this->data['Review']['id']);
						
			$xajax->assign( "jr_review_message_" . $this->data['Review']['id'],"innerHTML", $linkMsg );
			
			$xajax->script("setTimeout(function() { jQuery('#jr_review_message_".$this->data['Review']['id']."').fadeOut('slow');}, 3000);");
			
			$xajax->script( "jQuery('#jr_review_".$this->data['Review']['id']."').fadeIn('fast');");

			# Init thickbox
			$xajax->script("tb_init('a.thickbox, area.thickbox, input.thickbox');");					
			
//			$xajax->assign("submitButton","disabled",false);	

			return $xajax;
			
		# NEW USER REVIEW + MODERATION ON			
		} elseif ($isNew && $this->Access->moderateReview && !$this->data['Review']['author']) {
	
			# Effects to hide form, scroll up, show success message and add new review at the top of the list.		
			$xajax->script( "jQuery('#jr_reviewform').remove();");		
						
			$xajax->script("jQuery('#jr_user_reviews').scrollTo(750,150);");
						
			$linkMsg = '<div id="jr_review_message_'.$this->data['Review']['id'].'" style="background-color:#FEFF9F;margin:10px;padding:4px;border:1px solid #CCC;text-align:center;font-weight:bold;">'.__t("Thank you for your submission. It will be published once it is verified.",true).'</div>';
			
			$xajax->prepend( "jr_user_reviews","innerHTML", $linkMsg );
			
			$xajax->script("setTimeout(function() { jQuery('#jr_review_message_".$this->data['Review']['id']."').fadeOut('slow');}, 3000);");			
			
			$xajax->assign("submitButton","disabled",false);
			
			return $xajax;
			
		}

		# USER REVIEW EDIT - update display post save OR EDITOR REVIEW WHEN NOT IN DETAIL PAGE
//		if (!$isNew && (!$this->data['Review']['author'] || ($this->data['Review']['author']) && Sanitize::getString($this->data,'view')!='detail')) {
        if (!$isNew && !$this->data['Review']['author']) 
        {	          
			$xajax->script("tb_remove();return false;");

			appLogMessage('*********Post save query to display updated review info','database');
			
			$fields = array(
				'Criteria.id AS `Criteria.criteria_id`',
				'Criteria.criteria AS `Criteria.criteria`',
				'Criteria.tooltips AS `Criteria.tooltips`',
				'Criteria.weights AS `Criteria.weights`'			
			);
			
			$joins = $this->Listing->joinsReviews;
						
			$review = $this->Review->findRow(array(
				'fields'=>$fields,
				'conditions'=>'Review.id = ' . $this->data['Review']['id'],
				'joins'=>$joins
			));
	
			$this->set(
				array(
					'User'=>$this->_user,
					'Access'=>$this->Access,
					'reviews'=>array($this->data['Review']['id']=>$review)
				)
			);
			
			$review = $this->render('reviews','reviews');			

			# Effects to hide form, scroll up, show success message and add new review at the top of the list.					
			$xajax->script("jQuery('#jr_review_{$this->data['Review']['id']}').scrollTo(750,150);");

			$xajax->assign( "jr_review_{$this->data['Review']['id']}","innerHTML", $review );			
						
			$xajax->assign( "jr_review_".$this->data['Review']['id'],'style.display','none');
						
			$linkMsg = '<div id="message_inner_'.$this->data['Review']['id'].'" style="background-color:#FEFF9F;margin:10px;padding:4px;border:1px solid #CCC;text-align:center;font-weight:bold;">'.__t("Your changes were saved.",true).'</div>';
			
			$xajax->insert("jr_review_" . $this->data['Review']['id'],"div","jr_review_message_" . $this->data['Review']['id']);
						
			$xajax->assign( "jr_review_message_" . $this->data['Review']['id'],"innerHTML", $linkMsg );
			
			$xajax->script("setTimeout(function() { jQuery('#jr_review_message_".$this->data['Review']['id']."').fadeOut('slow');}, 3000);");
			
			# Show updated review, init thickbox
			$xajax->script( "jQuery('#jr_review_".$this->data['Review']['id']."').fadeIn('fast',function(){tb_init('a.thickbox, area.thickbox, input.thickbox');});");			
								
//			$xajax->assign("submitButton","disabled",false);

			return $xajax;
		}
	
		# NEW EDITOR REVIEW + MODERATION TURNED OFF
		if ($isNew && !$this->Access->moderateReview && $this->data['Review']['author']) {
	
			$xajax->alert(__t("Your changes were saved.",true));
			$xajax->script('document.location.reload();');
			
			return $xajax;
			
		# NEW EDITOR REVIEW + MODERATION TURNED ON			
		} elseif($isNew && $this->Access->moderateReview && $this->data['Review']['author']) {
			
			# Effects to hide form, scroll up, show success message and add new review at the top of the list.		
			$xajax->script( "jQuery('#jr_reviewform').remove();");		
						
			$xajax->script("jQuery('#jr_user_reviews').scrollTo(750,150);");
						
			$linkMsg = '<div id="jr_review_message_'.$this->data['Review']['id'].'" style="background-color:#FEFF9F;margin:10px;padding:4px;border:1px solid #CCC;text-align:center;font-weight:bold;">'.__t("Thank you for your submission. It will be published once it is verified.",true).'</div>';
			
			$xajax->prepend( "jr_user_reviews","innerHTML", $linkMsg );
			
			$xajax->script("setTimeout(function() { jQuery('#jr_review_message_".$this->data['Review']['id']."').fadeOut('slow');}, 3000);");			
			
			$xajax->assign("submitButton","disabled",false);
			
			return $xajax;
		}

		# EDIT EDITOR REVIEW
		if (!$isNew && $this->data['Review']['author']) {
						
			$xajax->script("tb_remove();return false;");

			$xajax->alert(__t("Your changes were saved.",true));
						
			$xajax->script('document.location.reload();');
			
			return $xajax;
		}
				
	}	
		
	function myreviews( $params ) 
	{				
		if($this->_user->id === 0) {
			$this->cacheAction = Configure::read('Cache.expires');
		}

		// Set layout
		$this->layout = 'reviews';
		$this->autoRender = false;
						
		 // Triggers the afterFind in the Observer Model
		$this->EverywhereAfterFind = true;
			
		$user_id = Sanitize::getInt($this->params,'user'); 	
		
		if (!$user_id && !$this->_user->id) {
			echo cmsFramework::noAccess();
			$this->autoRender = false;
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

		$this->set(array(
				'Access'=>$this->Access,
				'User'=>$this->_user,
				'reviews'=>$reviews,
				'pagination'=>array(
					'total'=>$count,
					'offset'=>($this->page-1)*$this->limit
				)
			)
		);
		
		return $this->render('reviews','reviews');

	}
		
	/**
	 * Function to display the user rank table based on reviews and usefulness
	 */
	function rankings($params) {

		$this->cacheAction = Configure::read('Cache.expires');
						
		# Get total number of reviewers
		$reviewer_count = $this->Review->getReviewerTotal();
				 
		# Get user rankings
		$rankings = $this->Review->getRankPage($this->page,$this->limit);

		$this->set(array(
			'reviewer_count'=>$reviewer_count,
			'rankings'=>$rankings,
			'pagination'=>array(
				'total'=>$reviewer_count
			)
		));		

	}
	
	function _vote($params) {
		
		$xajax = new xajaxResponse();
		
		$id = Sanitize::getInt($this->data,'review_id');
		$action = Sanitize::getInt($this->data,'action');
				
		$date = date( "Y-m-d H:i:s" );
		$ipaddress = $_SERVER['REMOTE_ADDR'];
	
		switch ($action) {
	
			case 1: //yes vote
	
				$this->_db->setQuery ("SELECT count(*) FROM #__jreviews_votes_tmp WHERE reviewid='$id' AND ipaddress='$ipaddress'");
				$duplicate = $this->_db->loadResult();
	
				if ($duplicate > 0) {
					$xajax->alert(__t("You already voted.",true));
				} else {
					$this->_db->setQuery("insert into #__jreviews_votes_tmp"
					. " \n (reviewid,yes,no,ipaddress,created) values"
					. "\n ('$id','1','0','$ipaddress', '$date')");
					$this->_db->query();
					
					# Hides vote buttons and shows message alert
					$xajax->script("jQuery(this.votebtns).fadeOut('slow',function(){alert('".__t("Thank you for your vote, it will be processed soon.",true)."');});");
				}
	
			break;
	
			case 0: //no vote
	
				$this->_db->setQuery ("SELECT count(*) FROM #__jreviews_votes_tmp WHERE reviewid='$id' AND ipaddress='$ipaddress'");
				$duplicate = $this->_db->loadResult();
	
				if ($duplicate > 0) {
					$xajax->alert(__t("You already voted.",true));
				} else {
					$this->_db->setQuery("insert into #__jreviews_votes_tmp"
					. "\n (reviewid,yes,no,ipaddress,created) values"
					. "\n ('$id','0','1','$ipaddress','$date')");
					$this->_db->query();
					
					# Hides vote buttons and shows message alert
					$xajax->script("jQuery(this.votebtns).fadeOut('slow',function(){alert('".__t("Thank you for your vote, it will be processed soon.",true)."');});");
				}
	
			break;
	
			default: break;
	
		}

		return $xajax;		
				
	}
		
}

