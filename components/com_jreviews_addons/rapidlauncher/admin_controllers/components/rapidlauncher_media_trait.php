<?php
/**
 * Rapidlauncher Add-on for JReviews
 * Copyright (C) 2010-2016 ClickFWD LLC
 * This is not free software, do not distribute it.
 * For licencing information visit https://www.jreviews.com
 * or contact sales@jreviews.com
**/

defined( 'MVC_FRAMEWORK') or die( 'Direct Access to this location is not allowed.' );

trait RapidlauncherMediaTrait {

	function isRemote($path)
	{
		if(($url = filter_var($path, FILTER_VALIDATE_URL)) && strpos($path, WWW_ROOT) === false)
		{
			return $url;
		}

		return false;
	}

	function grabRemoteFile($url)
	{
		$parts = explode('?', $url);

		$uploadUrl = $parts[0];

		$fileName = pathinfo($uploadUrl, PATHINFO_FILENAME);

		$fileExtension = pathinfo($uploadUrl, PATHINFO_EXTENSION);

		$target = $this->tmpFolder . DS . $fileName . '.'. $fileExtension;

		$options = [
			CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows; U; Windows NT 5.1; pl; rv:1.9) Gecko/2008052906 Firefox/3.0',
			CURLOPT_AUTOREFERER => true,
			// CURLOPT_COOKIEFILE => '',
			// CURLOPT_FOLLOWLOCATION => true,
			CURLOPT_HEADER=> 0,
			CURLOPT_BINARYTRANSFER=>true,
			CURLOPT_SSL_VERIFYPEER=>0,
			CURLOPT_SSL_VERIFYHOST=>0
		];

		$ch = curl_init($url);

		$fp = fopen($target, 'wb');

		curl_setopt_array($ch, $options);

		curl_setopt($ch, CURLOPT_FILE, $fp);

		curl_exec($ch);

		$curl_info = curl_getinfo($ch);

		curl_close($ch);

		fclose($fp);

		if($curl_info['http_code'] == 200 && is_file($target))
		{
			return $target;
		}

		return false;
	}

}