<?php
/**
 * RapidLauncher Addon for JReviews
 * Copyright (C) 2010-2016 ClickFWD LLC
 * This is not free software, do not distribute it.
 * For licencing information visit http://www.jreviews.com
 * or contact sales@jreviews.com
**/

defined( 'MVC_FRAMEWORK') or die;

define('RAPIDLAUNCHER_ADDON_OPTION_SEPARATOR', ',');

class AdminRapidlauncherBaseController extends MyController
{
    var $autoLayout = false;

    var $autoRender = false;

	public function beforeFilter()
	{
		if(isset($this->Access) && isset($this->Config))
		{
			$this->Access->init($this->Config);
		}

		parent::beforeFilter();
	}

	public function response($status, $msg = '', $extra = null)
	{
		$response = ['success' => $status, 'msg' => $msg];

		if($extra)
		{
			$response = array_merge($response, $extra);
		}

		return $response;
	}

	public function jsonResponse($status, $msg = '', $extra = null)
	{
		return cmsFramework::jsonResponse($this->response($status, $msg, $extra));
	}
}