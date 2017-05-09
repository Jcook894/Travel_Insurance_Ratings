<?php
defined( 'MVC_FRAMEWORK') or die( 'Direct Access to this location is not allowed.' );

/* SVN FILE: $Id: sanitize.php 6311 2008-01-02 06:33:52Z phpnut $ */
/**
 * Washes strings from unwanted noise.
 *
 * Helpful methods to make unsafe strings usable.
 *
 * PHP versions 4 and 5
 *
 * CakePHP(tm) :  Rapid Development Framework <http://www.cakephp.org/>
 * Copyright 2005-2008, Cake Software Foundation, Inc.
 *								1785 E. Sahara Avenue, Suite 490-204
 *								Las Vegas, Nevada 89104
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @modified by: Alejandro Schmeichler
 * @modify_date: March 18,2008
 * 
 */

/**
 * Data Sanitization.
 *
 * Removal of alpahnumeric characters, SQL-safe slash-added strings, HTML-friendly strings,
 * and all of the above on arrays.
 *
 */
class Sanitize {
	
	function getVar($var,$key,$default=null){
		
		if(is_array($var) && isset($var[$key])) {

			return $var[$key];

		} elseif(is_object($var) && isset($var->{$key})) {
			
			return $var->{$key};
		
		} else {
			
			return $default;
			
		}
			
	}
	
	function getString($var,$key,$default=null) {
		
		return (string) Sanitize::getVar($var,$key,$default);
	
	}
	
	function getInt($var,$key,$default=null) {
		
		return (int) Sanitize::getVar($var,$key,$default);
	
	}
	
	function getFloat($var,$key,$default=null) {
		
		return (float) Sanitize::getVar($var,$key,$default);
	
	}		
		
/**
 * Removes any non-alphanumeric characters.
 *
 * @param string $string String to sanitize
 * @return string Sanitized string
 * @access public
 * @static
 */
	function paranoid($string, $allowed = array()) {
		$allow = null;
		if (!empty($allowed)) {
			foreach ($allowed as $value) {
				$allow .= "\\$value";
			}
		}

		if (is_array($string)) {
			$cleaned = array();
			foreach ($string as $key => $clean) {
				$cleaned[$key] = preg_replace("/[^{$allow}a-zA-Z0-9]/", '', $clean);
			}
		} else {
			$cleaned = preg_replace("/[^{$allow}a-zA-Z0-9]/", '', $string);
		}
		return $cleaned;
	}


/**
 * Returns given string safe for display as HTML. Renders entities.
 *
 * @param string $string String from where to strip tags
 * @param boolean $remove If true, the string is stripped of all HTML tags
 * @return string Sanitized string
 * @access public
 * @static
 */
	function html($var, $key, $default = null, $remove = false) {
		
		$string = Sanitize::getVar($var, $key, $default);
		
		if($string) {

			if ($remove) {
				$string = strip_tags($string);
			} else {
				$patterns = array("/\&/", "/%/", "/</", "/>/", '/"/', "/'/", "/\(/", "/\)/", "/\+/", "/-/");
				$replacements = array("&amp;", "&#37;", "&lt;", "&gt;", "&quot;", "&#39;", "&#40;", "&#41;", "&#43;", "&#45;");
				$string = preg_replace($patterns, $replacements, $string);
			}
		}
		
		return $string;
	}
	
/**
 * Returns given string safe for display as HTML. Renders entities.
 *
 * @param string $string String from where to strip tags
 * @param boolean $remove If true, the string is stripped of all HTML tags
 * @return string Sanitized string
 * @access public
 * @static
 */
	function htmlClean($string, $remove = true) {

		if ($remove) {
			$string = strip_tags($string); 

		} else {
			$patterns = array("/\&/", "/%/", "/</", "/>/", '/"/', "/'/", "/\(/", "/\)/", "/\+/", "/-/");
			$replacements = array("&amp;", "&#37;", "&lt;", "&gt;", "&quot;", "&#39;", "&#40;", "&#41;", "&#43;", "&#45;");
			$string = preg_replace($patterns, $replacements, $string);
		}
		
		return $string;
	}
		
	function stripEscape($param) {
		
		if (!is_array($param) || empty($param)) {
			if (is_bool($param)) {
				return $param;
			}

			$return = preg_replace('/^[\\t ]*(?:-!)+/', '', $param);
			return $return;
		}
		
		foreach ($param as $key => $value) {
			if (!is_array($value)) {
				$return[$key] = preg_replace('/^[\\t ]*(?:-!)+/', '', $value);
			} elseif($value) {
				foreach ($value as $array => $string) {
					$return[$key][$array] = Sanitize::stripEscape($string);
				}
			}
		}
		
		if(isset($return)) {
			return $return;
		} else {
			return $param;
		}
	}	
		
/**
 * Strips extra whitespace from output
 *
 * @param string $str String to sanitize
 * @access public
 * @static
 */
	function stripWhitespace($str) {
		$r = preg_replace('/[\n\r\t]+/', ' ', $str);
		return preg_replace('/\s{2,}/', ' ', $r);
	}
/**
 * Strips image tags from output
 *
 * @param string $str String to sanitize
 * @access public
 * @static
 */
	function stripImages($str) {
		$str = preg_replace('/(<a[^>]*>)(<img[^>]+alt=")([^"]*)("[^>]*>)(<\/a>)/i', '$1$3$5<br />', $str);
		$str = preg_replace('/(<img[^>]+alt=")([^"]*)("[^>]*>)/i', '$2<br />', $str);
		$str = preg_replace('/<img[^>]*>/i', '', $str);
		return $str;
	}
/**
 * Strips scripts and stylesheets from output
 *
 * @param string $str String to sanitize
 * @access public
 * @static
 */
	function stripScripts($str) {
		return preg_replace('/(<link[^>]+rel="[^"]*stylesheet"[^>]*>)|<script[^>]*>.*?<\/script>|<style[^>]*>.*?<\/style>|<!--.*?-->/i', '', $str);
//		return preg_replace('/(<link[^>]+rel="[^"]*stylesheet"[^>]*>|style="[^"]*")|<script[^>]*>.*?<\/script>|<style[^>]*>.*?<\/style>|<!--.*?-->/i', '', $str);
//		return preg_replace('/(<link[^>]+rel="[^"]*stylesheet"[^>]*>|<img[^>]*>|style="[^"]*")|<script[^>]*>.*?<\/script>|<style[^>]*>.*?<\/style>|<!--.*?-->/i', '', $str);
	}
/**
 * Strips extra whitespace, images, scripts and stylesheets from output
 *
 * @param string $str String to sanitize
 * @access public
 */
	function stripAll($var, $key, $default = null) {
		
		$str = Sanitize::getVar($var, $key, $default);
		
		if($str) {
//			$str = Sanitize::stripWhitespace($str); // This one removes line breaks \n
			
			$str = Sanitize::stripImages($str);
			
			$str = Sanitize::stripScripts($str);

			$str = stripslashes($str);
		}
		
		return $str;
	}
/**
 * Strips the specified tags from output. First parameter is string from
 * where to remove tags. All subsequent parameters are tags.
 *
 * @param string $str String to sanitize
 * @param string $tag Tag to remove (add more parameters as needed)
 * @access public
 * @static
 */
	function stripTags() {
		$params = func_get_args();
		$str = $params[0];

		for ($i = 1; $i < count($params); $i++) {
			$str = preg_replace('/<' . $params[$i] . '[^>]*>/i', '', $str);
			$str = preg_replace('/<\/' . $params[$i] . '[^>]*>/i', '', $str);
		}
		return $str;
	}
/**
 * Sanitizes given array or value for safe input. Use the options to specify
 * the connection to use, and what filters should be applied (with a boolean
 * value). Valid filters: odd_spaces, encode, dollar, carriage, unicode,
 * escape, backslash.
 *
 * @param mixed $data Data to sanitize
 * @param mixed $options If string, DB connection being used, otherwise set of options
 * @return mixed Sanitized data
 * @access public
 * @static
 */
	function clean($data, $options = array()) {
		if (empty($data)) {
			return $data;
		}

		if (is_string($options)) {
			$options = array('connection' => $options);
		} elseif (!is_array($options)) {
			$options = array();
		}

		$options = array_merge(array(
			'connection' => 'default',
			'odd_spaces' => true,
			'html' => true,
			'dollar' => true,
			'carriage' => true,
			'unicode' => true,
			'escape' => false,
			'backslash' => true
		), $options);

		if (is_array($data)) {
			foreach ($data as $key => $val) {
				$data[$key] = Sanitize::clean($val, $options);
			}
			return $data;
			
		} else {

			if ($options['odd_spaces']) {
				$data = str_replace(chr(0xCA), '', str_replace(' ', ' ', $data));
			}
			if ($options['html']) {
				$data = Sanitize::htmlClean($data);

			}
			if ($options['dollar']) {
				$data = str_replace("\\\$", "$", $data);
			}
			if ($options['carriage']) {
				$data = str_replace("\r", "", $data);
			}

			$data = str_replace("'", "'", str_replace("!", "!", $data));

			if ($options['unicode']) {
				$data = preg_replace("/&amp;#([0-9]+);/s", "&#\\1;", $data);
			}
			if ($options['escape']) {
				$data = mysql_real_escape_string($data);
			}
			if ($options['backslash']) {
				$data = preg_replace("/\\\(?!&amp;#|\?#)/", "\\", $data);
			}
			return $data;
		}
	}

}