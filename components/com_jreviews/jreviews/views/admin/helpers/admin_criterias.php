<?php
/**
 * JReviews - Reviews Extension
 * Copyright (C) 2010-2015 ClickFWD LLC
 * This is not free software, do not distribute it.
 * For licencing information visit https://www.jreviews.com
 * or contact sales@jreviews.com
**/

defined( 'MVC_FRAMEWORK') or die( 'Direct Access to this location is not allowed.' );

class AdminCriteriasHelper extends S2Object
{

	function createListFromString ($string, $op = '')
	{
		$list = '';

		if(empty($string)) return '';

		$array = is_array($string) ? $string : explode ("\n",$string);

		foreach ($array as $element)
		{
			$list .= "<li>$element</li>";
		}

		$list = "<ol>$list</ol>";

		if ($op == 'sum')
		{
			$list .= "<center><b>Total:".array_sum($array)."</b></center>";
		}

		return $list;
	}

}
?>