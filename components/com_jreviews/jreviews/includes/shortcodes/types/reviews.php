<?php
/**
 * JReviews - Reviews Extension
 * Copyright (C) 2010-2016 ClickFWD LLC
 * This is not free software, do not distribute it.
 * For licencing information visit https://www.jreviews.com
 * or contact sales@jreviews.com
**/

defined('_JEXEC') or defined('ABSPATH') or die;

class ReviewsShortCode
{
	var $controller = 'module_reviews';

	var $action = 'index';

	function render($attr)
	{
		$params = array();

		$params['module'] = $attr;

		$params['module_id'] = Sanitize::getString($attr,'scid',rand(1000,10000));

		$params[S2_QVAR_PAGE] = 1;

		// Stops 404 errors from being triggered via shortcode
		$params['data']['module'] = true;

		$params['data']['controller'] = $this->controller;

		$params['data']['action'] = $this->action;

		$params['secret'] = cmsFramework::getConfig('secret');

		$params['token'] = cmsFramework::formIntegrityToken($params,array('module','module_id','form','data'),false);

		$Dispatcher = new S2Dispatcher('jreviews');

		$output = $Dispatcher->dispatch($params);

		unset($Dispatcher);

		return $output;
	}
}