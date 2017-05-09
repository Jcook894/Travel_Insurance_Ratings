<?php
/**
 * JReviews - Reviews Extension
 * Copyright (C) 2010-2016 ClickFWD LLC
 * This is not free software, do not distribute it.
 * For licencing information visit https://www.jreviews.com
 * or contact sales@jreviews.com
**/

defined('_JEXEC') or defined('ABSPATH') or die;

class FieldShortCode
{
	var $controller = 'shortcode_field';

	var $action = 'index';

	function render($attr)
	{
		$params = array();

		$params['shortcode'] = $attr;

		// Stops 404 errors from being triggered via shortcode
		$params['data']['module'] = true;

		$params['data']['controller'] = $this->controller;

		$params['data']['action'] = $this->action;

		$Dispatcher = new S2Dispatcher('jreviews');

		$output = $Dispatcher->dispatch($params);

		unset($Dispatcher);

		return $output;
	}
}