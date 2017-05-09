<?php
/**
 * JReviews - Reviews Extension
 * Copyright (C) 2010-2015 ClickFWD LLC
 * This is not free software, do not distribute it.
 * For licencing information visit https://www.jreviews.com
 * or contact sales@jreviews.com
**/

class JreviewsLocale {

	var $langArray = array();

	static function getInstance() {

		static $instance = array();

		if (!isset($instance[0]) || !$instance[0])
		{
			$instance[0] = new JreviewsLocale();
		}

		return $instance[0];
	}

	public static function getPHP($key = '') {

		$_this = self::getInstance();

		return $_this->getString('php', $key);
	}

	public static function getJS($key = '') {

		$_this = self::getInstance();

		return $_this->getString('js', $key);
	}

	public static function addStrings($namespace, $strings = array())
	{
		$_this = self::getInstance();

		if(!isset($_this->langArray[$namespace]))
		{
			$_this->langArray[$namespace] = array();
		}

		$_this->langArray[$namespace] = array_replace($_this->langArray[$namespace], $strings);
	}

	public static function getString($namespace, $key)
	{
		$_this = self::getInstance();

		return isset($_this->langArray[$namespace]) ? Sanitize::getString($_this->langArray[$namespace],$key) : '';
	}

	public static function getStrings($namespace = null)
	{
		$_this = self::getInstance();

		return isset($_this->langArray[$namespace]) ? $_this->langArray[$namespace] : $_this->langArray;
	}

	public static function getConstant($constant, $return = false)
	{
		$out = call_user_func('__t', $constant, true);

		if($return)
		{
			return $out;
		}

		echo $out;
	}

	public static function getConstantAdmin($constant, $return = false)
	{
		$out = call_user_func('__a', $constant, true);

		if($return)
		{
			return $out;
		}

		echo $out;
	}
}