<?php
/**
 * JReviews - Reviews Extension
 * Copyright (C) 2009 ClickFWD LLC
 * This is not free software, do not distribute it.
 * For licencing information visit http://www.reviewsforjoomla.com
 * or contact sales@reviewsforjoomla.com
**/

defined( 'MVC_FRAMEWORK') or die( 'Direct Access to this location is not allowed.' );

class InquiryController extends MyController {
        
    var $uses = array('menu','favorite','captcha','criteria'/*for config overrides*/);
    
    var $helpers = array();
    
    var $components = array('access','config','everywhere');
    
    var $autoRender = false;
    
    var $autoLayout = false;
    
    function beforeFilter(){
        # Call beforeFilter of MyController parent class
        parent::beforeFilter();
    }
            
    function _send() 
    {
        $recipient = '';
        $error = array();
        $response = array();
        
        $this->components = array('security');        
        $this->__initComponents(); 
                
        if($this->invalidToken){
            $error[] = 'jQuery("#jr_inquiryTokenValidation").show();';
            return json_encode(array('error'=>$this->makeJS($error)));
        }
             
		$listing_id = Sanitize::getInt($this->data['Inquiry'],'listing_id');
		
		$overrides = $this->Criteria->getListingTypeOverridesByListingId($listing_id);
		
        if(!$listing_id || !$this->Config->getOverride('inquiry_enable',$overrides)) 
        {    
            return $this->ajaxError(s2Messages::accessDenied());
		}

		// Required fields    
        $fields = array('name','email','text');
//        $fields = array('name','email','phone','text');
        
        foreach($fields AS $id)
        {
            $input_id = '#jr_inquiry'.Inflector::camelize($id).'Validation';
            if($this->data['Inquiry'][$id]=='')
            {
                $error[] = 'jQuery("'.$input_id.'").show();';            
            } else {
                $reponse[] = 'jQuery("'.$input_id.'").hide();';            
            }            
        }
        
        # Validate user's email
        $this->Listing->validateInput($this->data['Inquiry']['email'], "email", "email", __t("You must fill in a valid email address.",true), 1);
        
        # Validate security code
        if ($this->Access->showCaptcha())
        {
            if(!isset($this->data['Captcha']['code'])) 
            {                
                $this->Listing->validateSetError("code", __t("The security code you entered was invalid.",true));
                    
            } elseif($this->data['Captcha']['code'] == '') 
            {    
                $this->Listing->validateSetError("code", __t("You must fill in the security code.",true));
            } 
            else 
            {
                if (!$this->Captcha->checkCode($this->data['Captcha']['code'],$this->ipaddress)) 
                {                    
                    $this->Listing->validateSetError("code", __t("The security code you entered was invalid.",true));                
                }    
            }
         }
         
        # Process validation errors
        $validation = $this->Listing->validateGetErrorArray(); 
        $validation = is_array($validation) ? implode("<br />",$validation) : '';                                         
                                                                    
        if(!empty($error) || $validation != '') 
        {
            if($this->Access->showCaptcha())
            {
                // Replace captcha with new instance
                $captcha = $this->Captcha->displayCode();
                $error[] = "jQuery('.jr_captcha').find('img').attr('src','{$captcha['src']}');";                
                $error[] = "jQuery('.jr_captcha_code').val('');"; 
            }
            
            if($validation != '') {
                $error[] = "jQuery('#jr_inquiryCodeValidation').html('{$validation}').show();";
            }               

            return json_encode(array('error'=>$this->makeJS($error)));                        
        }

        // Now we can send the email        
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
       
        # Get the recipient email
         Configure::write('Cache.query',false);
        $listing = $this->Listing->findRow(array(
            'fields'=>array('User.email AS `Listing.email`'),
            'conditions'=>array('Listing.id = ' . (int)$this->data['Inquiry']['listing_id'])
        ));
        
        $url = cmsFramework::makeAbsUrl($listing['Listing']['url'],array('sef'=>true));
           
        $link = '<a href="'.$url.'">'.$listing['Listing']['title'].'</a>';                                    
                                            
        switch($this->Config->inquiry_recipient)
        {
            case 'owner':
                $recipient = Sanitize::getString($listing['Listing'],'email');
            break;
            case 'admin':
                $recipient =  $configMailFrom;                
            break;                                     
            case 'field':
                if(isset($listing['Field']['pairs'][$this->Config->inquiry_field]))
                {
                    $recipient = $listing['Field']['pairs'][$this->Config->inquiry_field]['value'][0];                                    
                }
            break;
        }       
        
        if($recipient == '') $recipient =  $configMailFrom;                
        if(!class_exists('PHPMailer')) {
            App::import('Vendor','phpmailer' . DS . 'class.phpmailer');
        }   
                    
        $mail = new PHPMailer();                
        $mail->CharSet     = cmsFramework::getCharset();
        $mail->SetLanguage( 'en' , S2_VENDORS . 'phpmailer' . DS . 'language' . DS);            
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
        $mail->addReplyTo(Sanitize::getString($this->data['Inquiry'],'email'));      
        $mail->AddAddress($recipient);

        $mail->Subject = sprintf(__t("New inquiry for: %s",true), $listing['Listing']['title']);
        $mail->Body = sprintf(__t("From: %s",true),Sanitize::getString($this->data['Inquiry'],'name')) . "<br />";
        $mail->Body .= sprintf(__t("Email: %s",true),Sanitize::getString($this->data['Inquiry'],'email')) . "<br />";                
//        $mail->Body .= sprintf(__t("Phone number: %s",true),Sanitize::getString($this->data['Inquiry'],'phone')) . "<br />";
        $mail->Body .= sprintf(__t("Listing: %s",true),$listing['Listing']['title']) . "<br />";
        $mail->Body .= sprintf(__t("Listing link: %s",true),$link) . "<br />";
        $mail->Body .= $this->data['Inquiry']['text'];
                            
        if(!$mail->Send()){
            unset($mail);
            $error[] = 'jQuery("#jr_inquiryTokenValidation").show();';
            return json_encode(array('error'=>$this->makeJS($error)));                     
        }

        $mail->ClearAddresses();
        $bccAdmin = $this->Config->inquiry_bcc;
        if($bccAdmin!='' && $bccAdmin!=$recipient)
        {
            $mail->AddAddress($bccAdmin);
            $mail->Send();            
        }
        unset($mail);        
        
        return json_encode(array('error'=>$this->makeJS($response),'html'=>true));            
        
    }
}