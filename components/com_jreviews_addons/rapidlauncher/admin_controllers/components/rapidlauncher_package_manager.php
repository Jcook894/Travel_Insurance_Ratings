<?php
/**
* RapidLauncher Addon for JReviews
* Copyright (C) 2010-2016 ClickFWD LLC
* This is not free software, do not distribute it.
* For licencing information visit https://www.jreviews.com
* or contact sales@jreviews.com
**/

defined( 'MVC_FRAMEWORK') or die;

S2App::import('AdminComponent', 'rapidlauncher_media_trait', 'jreviews');

S2App::import('Vendor',
	[
		'fileuploader/fileuploader'
	],
	'jreviews'
);

class RapidlauncherPackageManagerComponent
{
	use RapidlauncherMediaTrait;

	protected $url;

	protected $path;

	protected $tmpPath;

	public function setUrl($url)
	{
		$this->url = $url;

		return $this;
	}

	public function setPath($path)
	{
		$this->path = $path;

		return $this;
	}

	public function startup($controller)
	{
		$this->tmpFolder = cmsFramework::getConfig('tmp_path');
	}

	public function readManifest()
	{
		$manifestUrl = $this->url . 'packages.json';

		$ch = curl_init();

		curl_setopt($ch, CURLOPT_URL, $manifestUrl);

		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

		curl_setopt($ch, CURLOPT_TIMEOUT, 10);

        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);

        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);

		$res = curl_exec($ch);

		curl_close($ch);

		$res = json_decode($res,true);

		return Sanitize::getVar($res, 'packages');
	}

	public function download($package)
	{
		if(!$package) return false;

		$tmp = $this->grabRemoteFile($this->url . $package . '.zip');

		$result = cmsFramework::packageUnzip($tmp, $this->path . DS . $package);

		if(file_exists($tmp))
		{
			@unlink($tmp);
		}

		return $result;
	}

	function upload($file)
	{
        $uploader = new qqUploadedFileXhr();

        if($uploader->save($this->path . DS . $file))
        {
			$result = cmsFramework::packageUnzip($this->path . DS . $file, $this->path . DS . 'directory');

        	return 'directory';
        }

        return false;
	}

	public function cleanup($path)
	{
		if(!$path || !file_exists($path)) return false;

        $files = glob($path . DS . '*');

        if ($files === false)
        {
            return false;
        }

        foreach ($files as $file)
        {
            if (is_file($file))
            {
            	if(basename($file) != 'index.html')
            	{
                	@unlink($file);
            	}
            }
            else {

            	if(basename($file) != '')
            	{
	            	$folder = $path . DS . basename($file);

	            	$this->cleanup($folder);

	            	@rmdir($folder);
            	}
            }
        }
	}
}