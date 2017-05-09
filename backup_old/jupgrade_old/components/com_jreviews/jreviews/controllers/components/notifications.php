<?php
/**
 * JReviews - Reviews Extension
 * Copyright (C) 2010-2012 ClickFWD LLC
 * This is not free software, do not distribute it.
 * For licencing information visit http://www.reviewsforjoomla.com
 * or contact sales@reviewsforjoomla.com
**/

defined( 'MVC_FRAMEWORK') or die( 'Direct Access to this location is not allowed.' );

class NotificationsComponent extends S2Component {
	
    var $name = 'notifications';
    
    var $notifyModel = null;
    
    var $published = true;
	
    var $validObserverModels = array(
        'Listing','Review','Report','OwnerReply','Discussion','Claim'
        );
	
    function startup(&$controller) 
    {
        $this->c = & $controller;
        
        if(method_exists($this->c,'getNotifyModel')) 
        {               
            $this->notifyModel = & $controller->getNotifyModel();
            
            if(method_exists($this->c,'getNotifyModel') 
        	    && in_array($this->notifyModel->name,$this->validObserverModels)) 
            {
        	    
        	    $this->notifyModel->addObserver('plgAfterSave',$this);
            }
        }
    } 	
    
    function plgAfterSave(&$model) 
    {                                                 
        appLogMessage('**** BEGIN Notifications Plugin AfterSave', 'database');
        
    	# Read cms mail config settings
    	$configSendmailPath = cmsFramework::getConfig('sendmail');
    	$configSmtpAuth = cmsFramework::getConfig('smtpauth');
    	$configSmtpUser = cmsFramework::getConfig('smtpuser');
    	$configSmtpPass = cmsFramework::getConfig('smtppass');
    	$configSmtpHost = cmsFramework::getConfig('smtphost');
        $configSmtpSecure = cmsFramework::getConfig('smtpsecure');
        $configSmtpPort = cmsFramework::getConfig('smtpport');
    	$configMailFrom = cmsFramework::getConfig('mailfrom');
    	$configFromName = cmsFramework::getConfig('fromname');
    	$configMailer = cmsFramework::getConfig('mailer');  	
   	
		if($configMailFrom == '') return;
		
		if(!class_exists('PHPMailer')) {
    		App::import('Vendor','phpmailer' . DS . 'class.phpmailer');
		}   
		    		
		$mail = new PHPMailer();				
		$mail->CharSet 	= cmsFramework::getCharset();
		$mail->SetLanguage( 'en' , S2_VENDORS . 'PHPMailer' . DS . 'language' . DS);			
		$mail->Mailer = $configMailer; // Mailer used mail,sendmail,smtp

		switch($configMailer) 
		{
			case 'smtp':	
				$mail->Host = $configSmtpHost;	
				$mail->SMTPAuth = $configSmtpAuth;	
				$mail->Username = $configSmtpUser;		
				$mail->Password = $configSmtpPass;
                $mail->SMTPSecure = $configSmtpSecure != '' ? $configSmtpSecure : ''; 
                $mail->Port = $configSmtpPort;
			break;
			
			case 'sendmail':
				$mail->Sendmail = $configSendmailPath;				
				break;
				
			default:break;			
		}
		
		$mail->isHTML(true);		
		$mail->From = $configMailFrom;		
		$mail->FromName = $configFromName;
        $mail->AddReplyTo($configMailFrom,$configFromName);
        						
 		# In this observer model we just use the existing data to send the email notification
		switch($this->notifyModel->name)
		{
			# Notification for new/edited listings
			case 'Listing':						
 
                if ($this->c->Config->notify_content
                    || $this->c->Config->notify_user_listing
                ) 
                {
                    $this->c->autoRender = false;
                    
                    $listing = $this->_getListing($model); 
                                        
                    $this->c->set(array(
                        'isNew'=>isset($model->data['insertid']),
                        'User'=>$this->c->_user,
                        'listing'=>$listing
                    ));
                }
                else 
                {
                    return;
                }
                
                // Admin listing email
				if ($this->c->Config->notify_content) 
                {
                    $mail->ClearAddresses();
                    $mail->ClearAllRecipients();
                    $mail->ClearBCCs();
                                        
					# Process configuration emails					
					if($this->c->Config->notify_content_emails == '') 
                    {
						$mail->AddAddress($configMailFrom);
					} 
                    else 
                    {
						$recipient = explode("\n",$this->c->Config->notify_content_emails);
                        
						foreach($recipient AS $to) 
                        {
							trim($to)!='' and $mail->AddAddress(trim($to));
						}
					}						
					
					$subject = (isset($model->data['insertid']) ? __t("New listing",true) . ": {$listing['Listing']['title']}" : __t("Edited listing",true) . ": {$listing['Listing']['title']}");
	
					$guest = (!$this->c->_user->id ? ' (Guest)' : " ({$this->c->_user->id})");
					$author = ($this->c->_user->id ? $this->c->_user->name : 'Guest');
				
					$message = $this->c->render('email_templates','admin_listing_notification');					

					$mail->Subject = $subject;

					$mail->Body = $message;
					
					if(!$mail->Send())
					{
					   appLogMessage(array(
					   		"Admin listing message was not sent.",
					   		"Mailer error: " . $mail->ErrorInfo),
					   		'notifications'
					   	);
					}					
				} // End admin listing email
                
                // User listing email - to user submitting the listing as long as he is also the owner of the listing
                if ($this->c->Config->notify_user_listing) 
                {
                    $mail->ClearAddresses();
                    $mail->ClearAllRecipients();
                    $mail->ClearBCCs();
                    
                    //Check if submitter and owner are the same or else email is not sent
                    // This is to prevent the email from going out if admins are doing the editing
                    if($this->c->_user->id == $listing['User']['user_id'])
                    {                        
                       // Process configuration emails                    
                        if($this->c->Config->notify_user_listing_emails != '') 
                        {
                            $recipient = explode("\n",$this->c->Config->notify_user_listing_emails);
                            foreach($recipient AS $bcc) 
                            {
                                trim($bcc)!='' and $mail->AddBCC(trim($bcc));
                            }
                        }   

                        trim($listing['User']['email']) != '' and $mail->AddAddress(trim($listing['User']['email']));

                        $subject = isset($model->data['insertid']) ? sprintf(__t("New listing: %s",true),$listing['Listing']['title']) : sprintf(__t("Edited listing: %s",true),$listing['Listing']['title']);
        
                        $guest = (!$this->c->_user->id ? ' (Guest)' : " ({$this->c->_user->id})");
                        $author = ($this->c->_user->id ? $this->c->_user->name : 'Guest');
                        
                        $message = $this->c->render('email_templates','user_listing_notification');                    

                        $mail->Subject = $subject;

                        $mail->Body = $message;
                        if(!$mail->Send())
                        {
                           appLogMessage(array(
                                   "User listing message was not sent.",
                                   "Mailer error: " . $mail->ErrorInfo),
                                   'notifications'
                               );
                        }
                    }                                            
                } // End user listing email				
				break;
				
			# Notification for new/edited reviews				
			case 'Review':            
                // Perform common actions for all review notifications
                if($this->c->Config->notify_review 
                    ||
                    $this->c->Config->notify_user_review 
                    ||
                    $this->c->Config->notify_owner_review
                ) {
                    $extension = $model->data['Review']['mode'];
                    
                    $review = $this->_getReview($model); 
                    $listing = $review;

                    $entry_title = $listing['Listing']['title'];
                
                    $this->c->autoRender = false;
                
                    $this->c->set(array(
                        'isNew'=>isset($model->data['insertid']),
                        'extension'=>$extension,
                        'listing'=>$listing,
                        'User'=>$this->c->_user,
                        'review'=>$review
                    ));                
                }
                else
                {
                    return;
                }
                
                // Admin review email
				if ($this->c->Config->notify_review) 
                {				
                    $mail->ClearAddresses();
                    $mail->ClearAllRecipients();
                    $mail->ClearBCCs();
					
                    # Process configuration emails
					if($this->c->Config->notify_review_emails == '') 
                    {
						$mail->AddAddress($configMailFrom);
					} 
                    else 
                    {
						$recipient = explode("\n",$this->c->Config->notify_review_emails);
						foreach($recipient AS $to) 
                        {
                            trim($to)!='' and $mail->AddAddress(trim($to));
						}
					}						

					$subject = isset($model->data['insertid']) ? sprintf(__t("New review: %s",true), $entry_title) : sprintf(__t("Edited review: %s",true), $entry_title);
	
					$message = $this->c->render('email_templates','admin_review_notification');

					$mail->Subject = $subject;

					$mail->Body = $message;
					
					if(!$mail->Send())
					{
					   appLogMessage(array(
					   		"Admin review message was not sent.",
					   		"Mailer error: " . $mail->ErrorInfo),
					   		'notifications'
					   	);
					}					
				}
                
                // User review email - sent to review submitter
                if(
                    $this->c->Config->notify_user_review
                    &&
                    $this->c->_user->id == $review['User']['user_id']
                    && 
                    !empty($review['User']['email'])
                ) {                                    
                    $mail->ClearAddresses();
                    $mail->ClearAllRecipients();
                    $mail->ClearBCCs();
                    
                    //Check if submitter and owner are the same or else email is not sent
                    // This is to prevent the email from going out if admins are doing the editing
                    if($this->c->_user->id == $review['User']['user_id'])
                    {                                            
                        // Process configuration emails                    
                        if($this->c->Config->notify_user_review_emails != '') {
                            $recipient = explode("\n",$this->c->Config->notify_user_review_emails);
                            foreach($recipient AS $bcc) 
                            {
                                if(trim($bcc)!='') 
                                {
                                    $mail->AddBCC(trim($bcc));
                                }
                            }
                        } 
                        
                        trim($review['User']['email']) != '' and $mail->AddAddress(trim($review['User']['email']));

                        $subject = isset($model->data['insertid']) ? sprintf(__t("New review: %s",true), $entry_title) : sprintf(__t("Edited review: %s",true), $entry_title);
        
                        $message = $this->c->render('email_templates','user_review_notification');

                        $mail->Subject = $subject;

                        $mail->Body = $message;
                        
                        if(!$mail->Send())
                        {
                           appLogMessage(array(
                                   "User review message was not sent.",
                                   "Mailer error: " . $mail->ErrorInfo),
                                   'notifications'
                               );
                        }
                    }                                                                    
                }                					

                // Listing owner review email
                if (
                    $this->c->Config->notify_owner_review
                    && 
                    isset($listing['ListingUser']['email'])
                    &&
                    !empty($listing['ListingUser']['email'])
                ) 
                {      
                    $mail->ClearAddresses();
                    $mail->ClearAllRecipients();
                    $mail->ClearBCCs();
                                                                      
                    // Process configuration emails                    
                    if($this->c->Config->notify_owner_review_emails != '') 
                    {
                        $recipient = explode("\n",$this->c->Config->notify_owner_review_emails);
                    
                        foreach($recipient AS $bcc) 
                        {
                            trim($bcc) !='' and $mail->AddBCC(trim($bcc));
                        }
                    } 
                    
                    trim($listing['ListingUser']['email']) != '' and $mail->AddAddress(trim($listing['ListingUser']['email']));
                
                    $subject = isset($model->data['insertid']) ? sprintf(__t("New review: %s",true), $entry_title) : sprintf(__t("Edited review: %s",true), $entry_title);
    
                    $message = $this->c->render('email_templates','owner_review_notification');

                    $mail->Subject = $subject;

                    $mail->Body = $message;

                    if(!$mail->Send())
                    {
                       appLogMessage(array(
                               "Listing owner review message was not sent.",
                               "Mailer error: " . $mail->ErrorInfo),
                               'notifications'
                           );
                    }
                }                 	
				break;
	
               # Notification for new owner replies to user reviews
            case 'OwnerReply':
                
                if ( $this->c->Config->notify_owner_reply ) {
                    
                    # Process configuration emails
                    if($this->c->Config->notify_owner_reply_emails == '') 
                    {
                        $mail->AddAddress($configMailFrom);
                    } 
                    else 
                    {
                        $recipient = explode("\n",$this->c->Config->notify_owner_reply_emails);
                        
                        foreach($recipient AS $to) 
                        {
                            trim($to) != '' and $mail->AddAddress(trim($to));
                        }
                    }                    
      
                     # Get review data
                    $this->c->Review->runProcessRatings = false;
                    $review = $this->c->Review->findRow(array(
                        'conditions'=>array('Review.id = ' . (int) $model->data['OwnerReply']['id'])
                    ));
                    
                    $extension = $review['Review']['extension'];

                    # Load jReviewsEverywhere extension model
                    $name =  'everywhere_' . $extension;
                    App::import('Model',$name,'jreviews');
                    $class_name = inflector::camelize('everywhere_'.$extension).'Model';
                    $EverywhereListingModel = new $class_name();
                    
                    # Get the listing title based on the extension being reviewed
                    $listing = $EverywhereListingModel->findRow(array('conditions'=>array("Listing.$EverywhereListingModel->realKey = " . $review['Review']['listing_id'])));

                    $subject = sprintf(__t("Owner review reply submitted for listing %s",true), $listing['Listing']['title']);

                    $this->c->autoRender = false;
                    
                    $this->c->set(array(
                        'User'=>$this->c->_user,
                        'reply'=>$model->data,
                        'review'=>$review,
                        'listing'=>$listing
                    ));
                    
                    $message = $this->c->render('email_templates','admin_owner_reply_notification');
                            
                    $mail->Subject = $subject;

                    $mail->Body = $message;

                    if(!$mail->Send() && _MVC_DEBUG_ERR)
                    {
                       appLogMessage(array(
                               "Owner reply message was not sent.",
                               "Mailer error: " . $mail->ErrorInfo),
                               'notifications'
                           );
                    }                                                
                }
                break;    
                 			
			# Notification for new review reports				
			case 'Report':
				
				if ( $this->c->Config->notify_report ) {
					
					# Process configuration emails
					if($this->c->Config->notify_review_emails == '') 
                    {
						$mail->AddAddress($configMailFrom);
					} 
                    else 
                    {
						$recipient = explode("\n",$this->c->Config->notify_review_emails);
					
                    	foreach($recipient AS $to) 
                        {
                            trim($to) !='' and $mail->AddAddress(trim($to));
						}
					}					

                    # Get review data
                    $this->c->Review->runProcessRatings = false;
                   
                    $review = $this->c->Review->findRow(array(
                        'conditions'=>array('Review.id = ' . (int) $model->data['Report']['review_id'])
                    ),array());

                    $extension = $review['Review']['extension'];

                    # Load jReviewsEverywhere extension model
                    $name =  'everywhere_' . $extension;
                    App::import('Model',$name,'jreviews');
                    $class_name = inflector::camelize('everywhere_'.$extension).'Model';
                    $EverywhereListingModel = new $class_name();
                    
                    # Get the listing title based on the extension being reviewed
                    $listing = $EverywhereListingModel->findRow(array('conditions'=>array("Listing.$EverywhereListingModel->realKey = " . $review['Review']['listing_id'])));
                
					$subject = __t("A new report has been submitted",true);

					$this->c->autoRender = false;
					
					$this->c->set(array(
						'User'=>$this->c->_user,
						'report'=>$model->data,
						'review'=>$review,
						'listing'=>$listing
					));
					
					$message = $this->c->render('email_templates','admin_report_notification');
							
					$mail->Subject = $subject;

					$mail->Body = $message;
					
					if(!$mail->Send() && _MVC_DEBUG_ERR)
					{
					   appLogMessage(array(
					   		"Review report message was not sent.",
					   		"Mailer error: " . $mail->ErrorInfo),
					   		'notifications'
					   	);
					}												
				}
				break; 
                
            case 'Discussion':

                if($this->c->Config->notify_review_post ) 
                {                    
                    # Process configuration emails
                    if($this->c->Config->notify_review_post_emails == '') 
                    {
                        $mail->AddAddress($configMailFrom);
                    } 
                    else 
                    {
                        $recipient = explode("\n",$this->c->Config->notify_review_post_emails);
                    
                        foreach($recipient AS $to) 
                        {
                            trim($to) !='' and $mail->AddAddress(trim($to));
                        }
                    }                    

                     # Get review data
                    $this->c->Review->runProcessRatings = false;
                    $review = $this->c->Review->findRow(array(
                        'conditions'=>array('Review.id = ' . (int) $model->data['Discussion']['review_id'])
                    ));
                    
                    $extension = $review['Review']['extension'];

                    # Load jReviewsEverywhere extension model
                    $name =  'everywhere_' . $extension;
                    App::import('Model',$name,'jreviews');
                    $class_name = inflector::camelize('everywhere_'.$extension).'Model';
                    $EverywhereListingModel = new $class_name();
                    
                    # Get the listing title based on the extension being reviewed
                    $listing = $EverywhereListingModel->findRow(array('conditions'=>array("Listing.$EverywhereListingModel->realKey = " . $review['Review']['listing_id'])));

                    $subject = isset($model->data['insertid']) 
                                ? 
                                    sprintf(__t("New comment for review: %s",true), $review['Review']['title']) 
                                : 
                                    sprintf(__t("Edited comment for review: %s",true), $review['Review']['title']) 
                    ;
                    
                    $this->c->autoRender = false;
                    
                    $this->c->set(array(
                        'User'=>$this->c->_user,
                        'post'=>$this->_getReviewPost($model),
                        'review'=>$review,
                        'listing'=>$listing
                    ));
                    
                    $message = $this->c->render('email_templates','admin_review_discussion_post');
                            
                    $mail->Subject = $subject;

                    $mail->Body = $message;
                    
                    if(!$mail->Send() && _MVC_DEBUG_ERR)
                    {
                       appLogMessage(array(
                               "Review comment message was not sent.",
                               "Mailer error: " . $mail->ErrorInfo),
                               'notifications'
                           );
                    }                                                
                }
                break;   

            case 'Claim':
              
                if ($this->c->Config->notify_claim ) {

                    # Process configuration emails
                    if($this->c->Config->notify_claim_emails == '') 
                    {
                        $mail->AddAddress($configMailFrom);
                    } 
                    else 
                    {
                        $recipient = explode("\n",$this->c->Config->notify_claim_emails);

                        foreach($recipient AS $to) 
                        {
                            trim($to) !='' and $mail->AddAddress(trim($to));
                        }
                    }                    

                     # Get claim data
                    $callbacks = array();
                    $listing = $this->c->Listing->findRow(
                        array(
                            'conditions'=>array('Listing.id = ' . (int) $model->data['Claim']['listing_id'])
                        ),
                        $callbacks
                    );

                    $subject = sprintf(__t("Listing claim submitted for %s",true), $listing['Listing']['title']);

                    $this->c->autoRender = false;
                   
                    $this->c->set(array(
                        'User'=>$this->c->_user,
                        'claim'=>$model->data['Claim'],
                        'listing'=>$listing
                    ));
                   
                    $message = $this->c->render('email_templates','admin_listing_claim');

                    $mail->Subject = $subject;

                    $mail->Body = $message;

                    if(!$mail->Send() && _MVC_DEBUG_ERR)
                    {
                       appLogMessage(array(
                               "Listing claim message was not sent.",
                               "Mailer error: " . $mail->ErrorInfo),
                               'notifications'
                           );
                    }                                                
                }
                break;                                                           			
		}
        
        $this->published = false; // Run once. With paid listings it is possible for a plugin to run a 2nd time when the order is processed together with the listing (free)
    	
        return true;
    }     
	
    function _getListing(&$model)
    {
        if(isset($this->c->viewVars['listing'])) 
        {
            $listing = $this->c->viewVars['listing'];
        } 
        else 
        {
            $listing_id = isset($model->data['Listing']) ? Sanitize::getInt($model->data['Listing'],'id') : false;
            !$listing_id and $listing_id = Sanitize::getInt($this->c->data,'listing_id');
            if(!$listing_id) return false;
            $listing = $this->c->Listing->findRow(array('conditions'=>array('Listing.id = '. $listing_id)),array('afterFind' /* Only need menu id */));        
            $this->c->set('listing',$listing);    
        } 
        
        if(Sanitize::getInt($model->data['Listing'],'state')) 
        {
            $listing['Listing']['state'] =  $model->data['Listing']['state'];          
        }
        
        return $listing;
    }
        
    function _getReview(&$model)
    {
        if(isset($this->c->viewVars['review']))
        {
            $review = $this->c->viewVars['review'];
        }
        elseif(isset($this->c->viewVars['reviews']))
        {         
            $review = current($this->c->viewVars['reviews']);                    
        }
        else
        {            
            // Get updated review info for non-moderated actions and plugin callback
            $fields = array(
                'Criteria.id AS `Criteria.criteria_id`',
                'Criteria.criteria AS `Criteria.criteria`',
                'Criteria.state AS `Criteria.state`', 
                'Criteria.tooltips AS `Criteria.tooltips`',
                'Criteria.weights AS `Criteria.weights`'            
            );
            
            $joins = $this->c->Listing->joinsReviews;

             // Triggers the afterFind in the Observer Model
            $this->c->EverywhereAfterFind = true;
                                
            $review = $model->findRow(array(
                'fields'=>$fields,
                'conditions'=>'Review.id = ' . $model->data['Review']['id'],
                'joins'=>$joins
                ), array('plgAfterFind' /* limit callbacks */) 
            );  
            
            $this->c->set('review',$review);            
        }
        
        return $review;                    
    }    
    
    function _getReviewPost(&$model)
    {
        if(isset($this->c->viewVars['post']))
        {
            $post = $this->c->viewVars['post'];
        }
        else 
        {
            $post = $model->findRow(array(
                'conditions'=>array(
                    'Discussion.type = "review"',
                    'Discussion.discussion_id = ' . $model->data['Discussion']['discussion_id']
                    ))
            );
            $this->c->set('post',$post);            
        }
        return $post;        
    }       
}
