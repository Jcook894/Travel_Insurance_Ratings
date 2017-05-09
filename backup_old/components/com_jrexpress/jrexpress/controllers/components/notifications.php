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

class NotificationsComponent extends S2Component {
	
	var $notifyModel = null;
	var $validObserverModels = array('Listing','Review','ReviewReport');
	
    function startup(&$controller) {
        $this->controller = & $controller;
        
        $this->notifyModel = & $controller->getNotifyModel();
        
        if(method_exists($this->controller,'getNotifyModel') 
        	&& in_array($this->notifyModel->name,$this->validObserverModels)) 
        {
        	
        	$this->notifyModel->addObserver('afterSaveHook',$this);
        }
    } 	
    
    function afterSaveHook(&$model) {

    	# Read cms mail config settings
    	$configSendmailPath = cmsFramework::getConfig('sendmail');
    	$configSmtpAuth = cmsFramework::getConfig('smtpauth');
    	$configSmtpUser = cmsFramework::getConfig('smtpuser');
    	$configSmtpPass = cmsFramework::getConfig('smtppass');
    	$configSmtpHost = cmsFramework::getConfig('smtphost');
    	$configMailFrom = cmsFramework::getConfig('mailfrom');
    	$configFromName = cmsFramework::getConfig('fromname');
    	$configMailer = cmsFramework::getConfig('mailer');  	
   	
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

			break;
			
			case 'sendmail':
				
				$mail->Sendmail = $configSendmailPath;
				
				break;
				
			default:break;	
		
		}
		
		$mail->isHTML(true);		
				
		$mail->From = $configMailFrom;
		
		$mail->FromName = $configFromName;
						
//    	$model->data[$this->notifyModel->name]['key'] = $value;

		# In this observer model we just use the existing data to send the email notification

		switch($this->notifyModel->name)
		{
			# Notification for new/edited listings
			case 'Listing':						
                // Admin listing email
				if ($this->controller->Config->notify_content) {
				
					# Process configuration emails					
					if($this->controller->Config->notify_content_emails == '') {
						$mail->AddAddress($configMailFrom);
					} else {
						$recipient = explode("\n",$this->controller->Config->notify_content_emails);
						foreach($recipient AS $to) {
							$mail->AddAddress(trim($to));
						}
					}						
					
					$listing = $model->findRow(array(
						'fields'=>array('User.email AS `User.email`'),
						'conditions'=>array('Listing.id = ' . $model->data['Listing']['id']))
						);
		
					$subject = (isset($model->data['insertid']) ? "New entry: {$listing['Listing']['title']}" : "Edited entry: {$listing['Listing']['title']}");
	
					$guest = (!$this->controller->_user->id ? ' (Guest)' : " ({$this->controller->_user->id})");
					$author = ($this->controller->_user->id ? $this->controller->_user->name : 'Guest');

					App::import('Helper','routes','jrexpress');
					$RoutesHelper = RegisterClass::getInstance('RoutesHelper');
					
					$this->controller->autoRender = false;
					
					$this->controller->set(array(
						'User'=>$this->controller->_user,
						'listing'=>$listing
					));
					
					$message = $this->controller->render('email_templates','admin_listing_notification');					

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
                if ($this->controller->Config->notify_user_listing) {
                                                        
                    $listing = $model->findRow(array(
                        'fields'=>array('User.email AS `User.email`'),
                        'conditions'=>array('Listing.id = ' . $model->data['Listing']['id']))
                        );
        
                    //Check if submitter and owner are the same or else email is not sent
                    // This is to prevent the email from going out if admins are doing the editing
                    if($this->controller->_user->id == $listing['User']['user_id'])
                    {                        
                        // Process configuration emails                    
                        if($this->controller->Config->notify_user_listing_emails != '') {
                            $recipient = explode("\n",$this->controller->Config->notify_user_listing_emails);
                            foreach($recipient AS $bcc) {
                                $mail->AddBCC(trim($bcc));
                            }
                        } 
                        
                        $mail->AddAddress(trim($listing['User']['email']));

                        $subject = isset($model->data['insertid']) ? sprintf(__t("New listing: %s",true),$listing['Listing']['title']) : sprintf(__t("Edited listing: %s",true),$listing['Listing']['title']);
        
                        $guest = (!$this->controller->_user->id ? ' (Guest)' : " ({$this->controller->_user->id})");
                        $author = ($this->controller->_user->id ? $this->controller->_user->name : 'Guest');

                        App::import('Helper','routes','jrexpress');
                        $RoutesHelper = RegisterClass::getInstance('RoutesHelper');
                        
                        $this->controller->autoRender = false;
                        
                        $this->controller->set(array(
                            'isNew'=>isset($model->data['insertid']),
                            'User'=>$this->controller->_user,
                            'listing'=>$listing
                        ));
                        
                        $message = $this->controller->render('email_templates','user_listing_notification');                    

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
                if($this->controller->Config->notify_review || $this->controller->Config->notify_user_review ||
                    $this->controller->Config->notify_owner_review)
                {
                    $extension = $model->data['Review']['mode'];
                    
                    # Load jReviewsEverywhere extension model
                    $name =  'everywhere_' . $extension;
                    App::import('Model',$name,'jrexpress');
                    $class_name = inflector::camelize('everywhere_'.$extension).'Model';
                    $EverywhereListingModel = new $class_name();
                    
                    # Get full review info            		
                    $model->stopAfterFind = true; // Stop afterFind callback because we only need the basic review and user info, not custom fields, etc. Otherwise we get an error because the Criteria is not in the array.
                    /** AfterfindHook problem with line below...unless stopAfterFind is set to true above */
                    $review = $model->findRow(array('conditions'=>array('Review.id = ' . $model->data['Review']['id'])));
                    $model->stopAfterFind = false; // Restore afterFind callback so it runs elsewhere
                }
                
                // Admin review email
				if ($this->controller->Config->notify_review) 
                {				
					# Process configuration emails
					if($this->controller->Config->notify_review_emails == '') {
						$mail->AddAddress($configMailFrom);
					} else {
						$recipient = explode("\n",$this->controller->Config->notify_review_emails);
						foreach($recipient AS $to) {
							$mail->AddAddress($to);
						}
					}						

					# Get the listing title based on the extension being reviewed
					$listing = $EverywhereListingModel->findRow(array('conditions'=>array("Listing.$EverywhereListingModel->realKey = " . $model->data['Review']['pid'])));
				
					$entry_title = $listing['Listing']['title'];
					
					$subject = isset($model->data['insertid']) ? sprintf(__t("New review: %s",true), $entry_title) : sprintf(__t("Edited review: %s",true), $entry_title);
	
					$this->controller->autoRender = false;
					
					$this->controller->set(array(
						'extension'=>$extension,
						'listing'=>$listing,
						'User'=>$this->controller->_user,
						'review'=>$review
					));
					
					$message = $this->controller->render('email_templates','admin_review_notification');

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
                if ($this->controller->Config->notify_user_review) 
                {                                    
                    //Check if submitter and owner are the same or else email is not sent
                    // This is to prevent the email from going out if admins are doing the editing
                    if($this->controller->_user->id == $review['User']['user_id'])
                    {                                            
                        // Process configuration emails                    
                        if($this->controller->Config->notify_user_review_emails != '') {
                            $recipient = explode("\n",$this->controller->Config->notify_user_review_emails);
                            foreach($recipient AS $bcc) {
                                $mail->AddBCC(trim($bcc));
                            }
                        } 
                        
                        $mail->AddAddress(trim($review['User']['email']));

                        # Get the listing title based on the extension being reviewed
                        $listing = $EverywhereListingModel->findRow(array('conditions'=>array("Listing.$EverywhereListingModel->realKey = " . $model->data['Review']['pid'])));
                    
                        $entry_title = $listing['Listing']['title'];

                        $subject = isset($model->data['insertid']) ? sprintf(__t("New review: %s",true), $entry_title) : sprintf(__t("Edited review: %s",true), $entry_title);
        
                        $this->controller->autoRender = false;
                        
                        $this->controller->set(array(
                            'isNew'=>isset($model->data['insertid']),
                            'extension'=>$extension,
                            'listing'=>$listing,
                            'User'=>$this->controller->_user,
                            'review'=>$review
                        ));
                        
                        $message = $this->controller->render('email_templates','user_review_notification');

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
                if ($this->controller->Config->notify_owner_review) 
                {                                    
                    // Process configuration emails                    
                    if($this->controller->Config->notify_owner_review_emails != '') {
                        $recipient = explode("\n",$this->controller->Config->notify_owner_review_emails);
                        foreach($recipient AS $bcc) {
                            $mail->AddBCC(trim($bcc));
                        }
                    } 
                    
                    # Get the listing title based on the extension being reviewed
                    $listing = $EverywhereListingModel->findRow(array('conditions'=>array("Listing.$EverywhereListingModel->realKey = " . $model->data['Review']['pid'])));

                    if(isset($listing['User']['email']))
                    {                
                        $mail->AddAddress(trim($listing['User']['email']));
                    
                        $entry_title = $listing['Listing']['title'];

                        $subject = isset($model->data['insertid']) ? sprintf(__t("New review: %s",true), $entry_title) : sprintf(__t("Edited review: %s",true), $entry_title);
        
                        $this->controller->autoRender = false;
                        
                        $this->controller->set(array(
                            'isNew'=>isset($model->data['insertid']),
                            'extension'=>$extension,
                            'listing'=>$listing,
                            'User'=>$this->controller->_user,
                            'review'=>$review
                        ));
                        
                        $message = $this->controller->render('email_templates','owner_review_notification');

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
                }                 	
				break;
				
			# Notification for new review reports				
			case 'ReviewReport':
				
				if ( $this->controller->Config->notify_report ) {
					
					# Process configuration emails
					if($this->controller->Config->notify_review_emails == '') {
						$mail->AddAddress($configMailFrom);
					} else {
						$recipient = explode("\n",$this->controller->Config->notify_review_emails);
						foreach($recipient AS $to) {
							$mail->AddAddress($to);
						}
					}					

					# Get review data
					$review = $this->controller->Review->findRow(array(
						'conditions'=>array('Review.id = ' . (int) $model->data['ReviewReport']['reviewid'])
					));
					
					$this->controller->Listing->fields = array('Listing.title');
					$this->controller->Listing->joins = array();
					$this->controller->Listing->group = array();
					
					$listing_title = $this->controller->Listing->findOne(array(
						'conditions'=>array('Listing.id = ' . $review['Review']['listing_id'])
					));
						
					$subject = "Review reported for ". $review['Review']['title'];

					$this->controller->autoRender = false;
					
					$this->controller->set(array(
						'User'=>$this->controller->_user,
						'report'=>$model->data,
						'review'=>$review,
						'listing_title'=>$listing_title
					));
					
					$message = $this->controller->render('email_templates','admin_review_report_notification');
							
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
		}
				
    	return true;
    }     
	
}