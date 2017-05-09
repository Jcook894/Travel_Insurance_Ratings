<?php
/**
 * JReviews - Reviews Extension
 * Copyright (C) 2010-2012 ClickFWD LLC
 * This is not free software, do not distribute it.
 * For licencing information visit http://www.reviewsforjoomla.com
 * or contact sales@reviewsforjoomla.com
**/

defined( 'MVC_FRAMEWORK') or die( 'Direct Access to this location is not allowed.' );

class AdminNotificationsComponent extends S2Component {
	
	var $notifyModel = null;
	
    function startup(&$controller) {

        $this->controller = & $controller;        
        
        if(method_exists($this->controller,'getNotifyModel')) 
        {        	
            $this->notifyModel = & $controller->getNotifyModel();
        	$this->notifyModel->addObserver('plgAfterSave',$this);
        }
    } 	
    
    function plgAfterSave(&$model) 
    {
        if(!isset($model->data['Email']) || !Sanitize::getInt($model->data['Email'],'send')) {return false;}
        
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
						
//    	$model->data[$this->notifyModel->name]['key'] = $value;

        $model->data['Email']['body'] = urldecode($model->data['__raw']['Email']['body']); // Send html email

		# In this observer model we just use the existing data to send the email notification
		switch($this->notifyModel->name)
		{
            // Notification for claims moderation
            case 'Claim':            

                if($model->data['Email']['subject']!=''){
                    $subject = $model->data['Email']['subject'];
                    $subject = str_ireplace('{name}',$model->data['Email']['name'],$subject);                
                    $subject = str_ireplace('{listing_title}',$model->data['Email']['listing_title'],$subject); 
                } 
                else 
                {                    
                    switch($model->data['Claim']['approved'])
                    {
                        case 1:
                            $subject = __a("Your claim has been approved",true);
                        break;
                        case -1:
                            $subject = __a("Your claim has been rejected",true);
                        break;
                        case 0:
                            $subject = __a("Your claim has been reviewed, but still pending moderation",true);
                        break;
                    }  
                }
                          
                // Get permalink
                $listing_id = $model->data['Listing']['id'];
                $listing = $this->controller->Listing->findRow(array(
                    'conditions'=>'Listing.id = ' . $listing_id
                ),array('afterFind'));

                $permalink = cmsFramework::makeAbsUrl($listing['Listing']['url'],array('sef'=>true));

                $message = $model->data['Email']['body'];
                $message = str_ireplace('{name}',$model->data['Email']['name'],$message);                
                $message = str_ireplace('{listing_title}',$model->data['Email']['listing_title'],$message); 
                $message = str_ireplace(array('{link}','{url}'),$permalink,$message); 
                
                if($message != '')
                {
                    $mail->Subject = $subject;

                    // Convert line breaks to br tags if html code not found on the message body
                    $mail->Body = nl2br($message);
                    
                    $mail->AddAddress($model->data['Email']['email']);
                    
                    if(!$mail->Send())
                    {
                       appLogMessage(array(
                               "Admin claim moderation message was not sent.",
                               "Mailer error: " . $mail->ErrorInfo),
                               'notifications'
                        );
                    }                    
                }                  
            break;
            
			# Notification for discussion post moderation
			case 'Discussion':            
					
                if($model->data['Email']['subject']!=''){
                    $subject = $model->data['Email']['subject'];
                    $subject = str_ireplace('{name}',$model->data['Email']['name'],$subject);                
                    $subject = str_ireplace('{review_title}',$model->data['Email']['review_title'],$subject);                     
                } 
                else 
                {
                    switch($model->data['Discussion']['approved'])
                    {
                        case 1:
                            $subject = __a("Your comment has been approved",true);
                        break;
                        case -1:
                            $subject = __a("Your comment has been rejected",true);
                        break;
                    }                                            
                }
					
                // Get permalink
                $this->controller->EverywhereAfterFind = true; 
                $this->controller->Review->runProcessRatings = false;
                    
                $review = $this->controller->Review->findRow(array(
                    'conditions'=>array('Review.id = ' . $model->data['Discussion']['review_id'])
                ));
                
                $this->controller->viewVars['review'] = $review; // Make it available to other plugins
                
                App::import('helper','routes','jreviews');
                $Routes = ClassRegistry::getClass('RoutesHelper');
                
                $permalink = $Routes->reviewDiscuss('',$review,array('listing'=>$review,'return_url'=>true));

                $permalink = cmsFramework::makeAbsUrl($permalink);

				$message = $model->data['Email']['body'];
                $message = str_ireplace('{name}',$model->data['Email']['name'],$message);                
                $message = str_ireplace(array('{link}','{url}'),$permalink,$message);                
                $message = str_ireplace('{review_title}',$model->data['Email']['review_title'],$message);  

                if($message != '')
                {
                    $mail->Subject = $subject;

                    // Convert line breaks to br tags if html code not found on the message body
                    $mail->Body = nl2br($message);
                    
                    $mail->AddAddress($model->data['Email']['email']);
                    
                    if(!$mail->Send())
                    {
                       appLogMessage(array(
                               "Admin post discussion moderation message was not sent.",
                               "Mailer error: " . $mail->ErrorInfo),
                               'notifications'
                        );
                    }                    
                }                
    	    break;
    
            // Notification for listing moderation
            case 'Listing':                            

                if(Sanitize::getInt($model->data,'moderation'))
                {
                    if($model->data['Email']['subject']!='')
                    {
                        $subject = $model->data['Email']['subject'];
                        $subject = str_ireplace('{name}',$model->data['Email']['name'],$subject);                
                        $subject = str_ireplace('{listing_title}',$model->data['Email']['listing_title'],$subject);                          
                    } 
                    else 
                    {                    
                        switch($model->data['Listing']['state'])
                        {
                            case 1:
                                $subject = __a("Your listing has been approved",true);
                            break;
                            case -2:
                                $subject = __a("Your listing has been rejected",true);
                            break;
                            case 0:
                                $subject = __a("Your listing has been reviewed, but it is still pending moderation",true);
                            break;
                        }  
                    }

                    // Get permalink
                    $listing_id = $model->data['Listing']['id'];
                    $listing = $this->controller->Listing->findRow(array(
                        'conditions'=>'Listing.id = ' . $listing_id
                    ),array('afterFind'));

                    $permalink = cmsFramework::makeAbsUrl($listing['Listing']['url'],array('sef'=>true));
        
                    $message = $model->data['Email']['body'];
                    $message = str_ireplace('{name}',$model->data['Email']['name'],$message);                
                    $message = str_ireplace(array('{link}','{url}'),$permalink,$message); 
                    $message = str_ireplace('{listing_title}',$model->data['Email']['listing_title'],$message);                          
                    
                    if($message != '')
                    {
                        $mail->Subject = $subject;

                        // Convert line breaks to br tags if html code not found on the message body
                        $mail->Body = nl2br($message);                        
                        $mail->AddAddress($model->data['Email']['email']);                        
                        if(!$mail->Send())
                        {
                           appLogMessage(array(
                                   "Admin listing moderation message was not sent.",
                                   "Mailer error: " . $mail->ErrorInfo),
                                   'notifications'
                            );
                        }                    
                    }                  
                    
                }           
            break;
                
            // Notification for reviews moderation
            case 'Review':  
                                    
                if(Sanitize::getInt($model->data,'moderation'))
                {
                    if($model->data['Email']['subject']!=''){
                        $subject = $model->data['Email']['subject'];
                        $subject = str_ireplace('{name}',$model->data['Email']['name'],$subject);                
                        $subject = str_ireplace('{listing_title}',$model->data['Email']['listing_title'],$subject); 
                        $subject = str_ireplace('{review_title}',$model->data['Email']['review_title'],$subject);                                                                             
                    } 
                    else 
                    {                    
                        switch($model->data['Review']['published'])
                        {
                            case 1:
                                $subject = __a("Your review has been approved",true);
                            break;
                            case -1:
                                $subject = __a("Your review has been rejected",true);
                            break;
                            case 0:
                                $subject = __a("Your review has been reviewed, but still pending moderation",true);
                            break;
                        }  
                    }
                                 
                    // Get permalink
                    $this->controller->EverywhereAfterFind = true; 
                    $this->controller->Review->runProcessRatings = false;

                    $review_id = $model->data['Review']['id'];
                    $review = $this->controller->Review->findRow(array(
                        'conditions'=>array('Review.id = ' . $review_id)
                    ));
                    
                    $this->controller->viewVars['review'] = $review; // Make it available to other plugins
                    
                    App::import('helper','routes','jreviews');
                    $Routes = ClassRegistry::getClass('RoutesHelper');
                    $permalink = $Routes->reviewDiscuss('',$review,array('listing'=>$review,'return_url'=>true));
                    $permalink = cmsFramework::makeAbsUrl($permalink);
                                     
                    $message = $model->data['Email']['body'];
                    $message = str_ireplace('{name}',$model->data['Email']['name'],$message);                
                    $message = str_ireplace(array('{link}','{url}'),$permalink,$message); 
                    $message = str_ireplace('{listing_title}',$model->data['Email']['listing_title'],$message);  
                    $message = str_ireplace('{review_title}',$model->data['Email']['review_title'],$message);  
                                        
                    if($message != '')
                    {
                        $mail->Subject = $subject;

                        // Convert line breaks to br tags if html code not found on the message body
                        $mail->Body = nl2br($message);                        
                        $mail->AddAddress($model->data['Email']['email']);                        
                        if(!$mail->Send())
                        {
                           appLogMessage(array(
                                   "Admin review moderation message was not sent.",
                                   "Mailer error: " . $mail->ErrorInfo),
                                   'notifications'
                            );
                        }                    
                    }                  
                    
                }           
            break;
                        
            // Notification for owner reply to reviews moderation
            case 'OwnerReply':            
                if($model->data['Email']['subject']!=''){
                    $subject = $model->data['Email']['subject'];
                    $subject = str_ireplace('{name}',$model->data['Email']['name'],$subject);                
                    $subject = str_ireplace('{listing_title}',$model->data['Email']['listing_title'],$subject); 
                    $subject = str_ireplace('{review_title}',$model->data['Email']['review_title'],$subject);                      
                } 
                else 
                {                    
                    switch($model->data['OwnerReply']['owner_reply_approved'])
                    {
                        case 1:
                            $subject = __a("Your reply has been approved",true);
                        break;
                        case -1:
                            $subject = __a("Your reply has been rejected",true);
                        break;
                        case 0:
                            $subject = __a("Your reply has been reviewed, but still pending moderation",true);
                        break;
                    }  
                }
                     
                // Get permalink
                $this->controller->EverywhereAfterFind = true; 
                $this->controller->Review->runProcessRatings = false;
                
                $review_id = $model->data['OwnerReply']['id'];
                
               $review = $this->controller->Review->findRow(array(
                    'conditions'=>array('Review.id = ' . $review_id)
                ));
                
                $this->controller->viewVars['review'] = $review; // Make it available to other plugins
                
                App::import('helper','routes','jreviews');
                $Routes = ClassRegistry::getClass('RoutesHelper');
                
                $permalink = $Routes->reviewDiscuss('',$review,array('listing'=>$review,'return_url'=>true));
                $permalink = cmsFramework::makeAbsUrl($permalink);

                $message = $model->data['Email']['body'];                
                $message = str_ireplace('{name}',$model->data['Email']['name'],$message);                
                $message = str_ireplace(array('{link}','{url}'),$permalink,$message); 
                $message = str_ireplace('{listing_title}',$model->data['Email']['listing_title'],$message); 
                $message = str_ireplace('{review_title}',$model->data['Email']['review_title'],$message); 
                
                if($message != '')
                {
                    $mail->Subject = $subject;

                    // Convert line breaks to br tags if html code not found on the message body
                    $mail->Body = nl2br($message);
                    
                    $mail->AddAddress($model->data['Email']['email']);
                    
                    if(!$mail->Send())
                    {
                       appLogMessage(array(
                               "Admin owner reply moderation message was not sent.",
                               "Mailer error: " . $mail->ErrorInfo),
                               'notifications'
                        );
                    }                    
                }                  
       
            break;
        }
				
        unset($mail);
    	return true;
    }     
	
}