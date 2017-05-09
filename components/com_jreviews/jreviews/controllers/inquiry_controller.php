<?php
/**
 * JReviews - Reviews Extension
 * Copyright (C) 2010-2015 ClickFWD LLC
 * This is not free software, do not distribute it.
 * For licencing information visit https://www.jreviews.com
 * or contact sales@jreviews.com
**/

defined( 'MVC_FRAMEWORK') or die( 'Direct Access to this location is not allowed.' );

class InquiryController extends MyController {

    var $uses = array('menu','inquiry');

    var $helpers = array('form');

    var $components = array('access','config','everywhere');

    var $autoRender = false;

    var $autoLayout = false;

    function beforeFilter(){
        # Call beforeFilter of MyController parent class
        parent::beforeFilter();
    }

    function create()
    {
        $this->autoRender = false;

        $this->autoLayout = false;

        $listing = array();

        $listing_id = Sanitize::getInt($this->params, 'id');

        $listing['Listing']['listing_id'] = $listing_id;

        $User = cmsFramework::getUser();

        $this->set(array(
            'User'=>$User,
            'listing'=>$listing
            ));

        return $this->render('inquiries','create');
    }

    function _send()
    {
        $recipient = '';

        $response = array('success'=>false,'str'=>array());

        $validation = array();

        $this->components = array('security');

        $this->__initComponents();

        if($this->invalidToken){

            $response['str'][] = 'INVALID_TOKEN';

            return cmsFramework::jsonResponse($response);
        }

		$listing_id = Sanitize::getInt($this->data['Inquiry'],'listing_id');

        $from_email = Sanitize::getString($this->data['Inquiry'],'from_email');

        // Maybe can change it so it says 'Site name on behalf of inquiry name'
        $from_name = Sanitize::getString($this->data['Inquiry'],'from_name');

        $message = Sanitize::getString($this->data['Inquiry'],'message');

        if(!$listing_id)
        {
            $response['str'][] = 'ACCESS_DENIED';

            return cmsFramework::jsonResponse($response);
		}

		// Required fields
        $inputs  = array('from_name','from_email','message');

//        $inputs = array('from_name','from_email','phone','message');

        foreach($inputs AS $key=>$input)
        {
            if($this->data['Inquiry'][$input] != '')
            {
                unset($inputs[$key]);
            }
        }

        # Validate user's email
        if(!in_array('from_email',$inputs)) {
            $this->Listing->validateInput($from_email, "from_email", "email", 'VALIDATE_EMAIL', 1);
        }

        # Process validation errors

        $validation = $this->Listing->validateGetErrorArray();

        if(!empty($validation) || !empty($inputs))
        {
            $response['success'] = false;

            $response['inputs'] = $inputs;

            $response['str'] = $validation;

            return cmsFramework::jsonResponse($response);
        }

        Configure::write('Cache.query',false);

        $this->Listing->addStopAfterFindModel(array('Community','Favorite','Media','PaidOrder'));

        $listing = $this->Listing->findRow(array(
            'conditions'=>array('Listing.' . EverywhereComContentModel::_LISTING_ID . ' = ' . $listing_id)
        ), $callbacks = array('afterFind'));

        switch($this->Config->inquiry_recipient)
        {
            case 'owner':
                $recipient = Sanitize::getString($listing['User'],'email');
            break;
            case 'field':
                if(isset($listing['Field']['pairs'][$this->Config->inquiry_field]))
                {
                    $recipient = $listing['Field']['pairs'][$this->Config->inquiry_field]['value'][0];
                }
            break;
            case 'admin':
            default:
                $recipient = cmsFramework::getConfig('mailfrom');
            break;
        }

        $to_email = trim($recipient);

        $mail = cmsFramework::getMail();

        $mail->ClearReplyTos();

        $mail->AddReplyTo($from_email, $from_name);

        $mail->AddAddress($recipient);

        $mail->Subject = sprintf(JreviewsLocale::getPHP('INQUIRY_TITLE'), $listing['Listing']['title']);

        $this->set(array(
            'fromName' => $from_name,
            'fromEmail' => $from_email,
            'listing' => $listing,
            'message' => $message
        ));

        $body = $this->partialRender('email_templates','inquiry','email');

        $mail->Body = $body;

        $bccAdmin = array_filter(explode("\n",Sanitize::getString($this->Config,'inquiry_bcc')));

        foreach($bccAdmin AS $bcc)
        {
            if($bcc != $recipient)
            {
                $mail->AddBCC($bcc);
            }
        }

        if(!$mail->Send())
        {
            unset($mail);

            $response['str'][] = 'PROCESS_REQUEST_ERROR';

            return cmsFramework::jsonResponse($response);
        }

        $mail->ClearAddresses();

        unset($mail);

        $extra_fields = array_diff_key($this->data['Inquiry'],array_flip(array('listing_id','from_email','from_name','message')));

        $User = cmsFramework::getUser();

        $data = array('Inquiry'=>array(
                'listing_id'=>$listing_id,
                'created'=>_CURRENT_SERVER_TIME,
                'from_email'=>$from_email,
                'from_name'=>$from_name,
                'to_email'=>$recipient,
                'user_id'=>$User->id,
                'message'=>$message,
                'extra_fields'=>json_encode($extra_fields),
                'ipaddress'=>ip2long($this->ipaddress)
            ));

        $this->Inquiry->store($data);

        $response['success'] = true;

        return cmsFramework::jsonResponse($response);
    }
}