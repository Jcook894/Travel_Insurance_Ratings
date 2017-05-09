<?php
/**
 * JReviews - Reviews Extension
 * Copyright (C) 2010-2015 ClickFWD LLC
 * This is not free software, do not distribute it.
 * For licencing information visit https://www.jreviews.com
 * or contact sales@jreviews.com
**/

defined( 'MVC_FRAMEWORK') or die( 'Direct Access to this location is not allowed.' );

class RecaptchaComponent extends S2Component {

    private static $SITE_VERIFY_URL = 'https://www.google.com/recaptcha/api/siteverify';

    private static $INPUT_NAME = 'g-recaptcha-response';

    var $_SITE_KEY;

    var $_SECRET_KEY;

    var $_THEME = 'light';

    var $credentialsExist = false;

    var $published = true;

    function startup(&$controller)
    {
        $this->c = &$controller;

        $this->initSettings();

        if(defined('MVC_FRAMEWORK_ADMIN')
            ||
            (isset($controller->Access) && !$this->c->Access->showCaptcha())
            ||
            !$this->validCredentials()
            )
        {
            $this->published = false;

            return;
        }

        switch(true)
        {
            case ($controller->name == 'listings'):

                $this->listings();

                break;

            case ($controller->name == 'reviews' && $controller->action == '_save'):

                $this->reviews();

                break;

            case ($controller->name == 'discussions' && $controller->action == '_save'):

                $this->discussions();

                break;

            case ($controller->name == 'inquiry' && $controller->action == '_send'):

                $this->inquiry();

                break;
        }
    }

    private function initSettings()
    {
        $this->_SITE_KEY = $this->c->Config->recaptcha_sitekey;

        $this->_SECRET_KEY = $this->c->Config->recaptcha_secretkey;

        $this->_THEME = $this->c->Config->recaptcha_theme;

        $this->credentialsExist = $this->_SITE_KEY && $this->_SECRET_KEY;
    }

    function validCredentials()
    {
        return $this->credentialsExist;
    }

    function plgAfterFilter()
    {
        if(!$this->published) return;

        // Add the recaptcha markup to the response so it can be used in javascript to add it on the fly

        if($this->c->name == 'common' && $this->c->action == '_initForm')
        {
            $show_captcha = Sanitize::getString($this->c->data,'captcha');

            if($show_captcha)
            {
                $response = json_decode($this->c->output,true);

                $response['captcha']= $this->displayCode();

                $this->c->output = json_encode($response);
            }
        }

        // Load the recaptcha script only when the jr-captcha class is found in the rendered theme
        // Or force it to load in the listing detail page and listing create form when the access settings require it

        if(!$this->c->ajaxRequest && isset($this->c->Access) && $this->c->Access->showCaptcha()
            && (
                (is_string($this->c->output) && strpos($this->c->output, 'jr-captcha') !== false)
                ||
                ($this->c->captcha == true)
            )
        )
        {
            $lang = cmsFramework::getUrlLanguageCode();

            if($lang) {
                $recaptchaSrc = sprintf('https://www.google.com/recaptcha/api.js?hl=%s&render=explicit',$lang);
            }
            else {
                $recaptchaSrc = 'https://www.google.com/recaptcha/api.js?render=explicit';
            }

            cmsFramework::addScriptDefer($recaptchaSrc, 'recaptcha');
        }
    }

    function listings()
    {
        switch($this->c->action)
        {
            case '_loadForm':

                $this->c->set('captcha', $this->displayCode());

            break;

            case '_save':

                $id = Sanitize::getInt($this->c->data['Listing'],'id');

                $isNew = $id == 0 ? true : false;

                if($isNew)
                {
                    $this->validateModel($this->c->Listing);
                }

            break;
        }
    }

    function reviews()
    {
        $id = Sanitize::getInt($this->c->data['Review'],'id');

        $isNew = $id == 0 ? true : false;

        if($isNew)
        {
            $this->validateModel($this->c->Review);
        }
    }

    function discussions()
    {
        $id = Sanitize::getInt($this->c->data['Discussion'],'discussion_id');

        $isNew = $id == 0 ? true : false;

        if($isNew)
        {
            $this->validateModel($this->c->Discussion);
        }
    }

    function inquiry()
    {
        $this->validateModel($this->c->Listing);
    }

    /**
     * ReCaptcha methods
     */

    function getResponse()
    {
        $response = false;

        if(isset($this->c->params['form']))
        {
            $response = Sanitize::getString($this->c->params['form'],self::$INPUT_NAME);
        }

        return $response;
    }

    function displayCode()
    {
        $captcha = sprintf('<div class="g-recaptcha" data-theme="%s" data-sitekey="%s"></div>', $this->_THEME, $this->_SITE_KEY);

        return $captcha;
    }

    function checkCode($response, $ipaddress, $expiration = 7200)
    {
        /**
         * PHP 5.6.0 changed the way you specify the peer name for SSL context options.
         * Using "CN_name" will still work, but it will raise deprecated errors.
         */
        $peer_key = version_compare(PHP_VERSION, '5.6.0', '<') ? 'CN_name' : 'peer_name';

        $params = array(
            'secret'=>$this->_SECRET_KEY,
            'response'=>$response,
            'ipaddress'=>$ipaddress
            );

        $query = http_build_query($params, '', '&');

        /*
        $options = array(
            'http' => array(
                'header' => "Content-type: application/x-www-form-urlencoded\r\n",
                'method' => 'POST',
                'content' => $query,
                // Force the peer to validate (not needed in 5.6.0+, but still works
                'verify_peer' => true,
                // Force the peer validation to use www.google.com
                $peer_key => 'www.google.com',
            ),
        );

        $context = stream_context_create($options);

        $res = file_get_contents(self::$SITE_VERIFY_URL, false, $context);
        */

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, self::$SITE_VERIFY_URL . '?' . $query);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

        curl_setopt($ch, CURLOPT_TIMEOUT, 10);

        curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows; U; Windows NT 6.1; en-US; rv:1.9.2.16) Gecko/20110319 Firefox/3.6.16");

        $res = curl_exec($ch);

        curl_close($ch);

        $res = json_decode($res,true);

        if(Sanitize::getBool($res,'success'))
        {
            return true;
        }
        else {

            return false;
        }
    }

    function validateModel(& $Model)
    {
        $response = $this->validate();

        if(!$response['success'])
        {
            $Model->validateSetError("code", $response['str'], 0);
        }
    }

    function validate()
    {
        $response = array('success'=>false, 'str' => '');

        // Make sure that the current user belongs to one of the groups setup to work with re-captcha
        // before we try to validate the captcha

        if (isset($this->c->Access) && $this->c->Access->showCaptcha())
        {
            $captcha = $this->getResponse();

            $ipaddress = s2GetIpAddress();

            if($captcha == '') {

                return array('success' => false, 'str' => 'VALID_CAPTCHA');
            }
            elseif (!$this->checkCode($captcha,$ipaddress)) {

                return array('success' => false, 'str' => 'VALID_CAPTCHA_INVALID');
            }
        }

        return array('success' => true);
    }
}