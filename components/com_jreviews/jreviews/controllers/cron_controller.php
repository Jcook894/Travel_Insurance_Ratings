<?php
/**
 * JReviews - Reviews Extension
 * Copyright (C) 2010-2015 ClickFWD LLC
 * This is not free software, do not distribute it.
 * For licencing information visit https://www.jreviews.com
 * or contact sales@jreviews.com
**/

defined( 'MVC_FRAMEWORK') or die( 'Direct Access to this location is not allowed.' );

class CronController extends MyController {

    var $uses = array();

    var $helpers = array();

    var $components = array('config');

    var $autoRender = false; //Output is returned

    var $autoLayout = false;

    function beforeFilter()
    {
		// Need to check the secret matches, otherwise don' do anything

		$secret = Sanitize::getString($this->Config,'cron_secret');

		if(strcmp(Sanitize::getString($this->params,'secret'), $secret) !== 0) {

			die(cmsFramework::noAccess());
		}

		# Call beforeFilter of MyController parent class
        parent::beforeFilter();
    }

    function index() {

		// Cron plugins automatically run here

        return __t("Cron ran successfully.",true);
	}


 }
