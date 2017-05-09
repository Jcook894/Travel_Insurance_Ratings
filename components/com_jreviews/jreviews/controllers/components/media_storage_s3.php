<?php
/**
 * JReviews - Reviews Extension
 * Copyright (C) 2010-201 ClickFWD LLC
 * This is not free software, do not distribute it.
 * For licencing information visit https://www.jreviews.com
 * or contact sales@jreviews.com
**/

defined( 'MVC_FRAMEWORK') or die( 'Direct Access to this location is not allowed.' );

S2App::import('Vendor','aws'.DS.'aws-autoloader');

use Aws\S3\S3Client;
use Aws\S3\Exception\S3Exception;
use Aws\Common\Exception\MultipartUploadException;
use Aws\S3\Model\MultipartUpload\UploadBuilder;

class MediaStorageS3Component extends S2Component {

	public $name = 's3';

	private $s3;

	private $_APIKey;

	private $_APISecret;

	private $_APIRegion;

	private $_BucketVideo;

	private $_BucketPhoto;

	private $_BucketAudio;

	private $_BucketAttachment;

	private $_BucketVideoCDN = false;

	private $_BucketPhotoCDN = false;

	private $_BucketAudioCDN = false;

	private $_BucketAttachmentCDN = false;

	private $_HEADER_CACHE_CONTROL;

	private $_HEADER_EXPIRES;

	function __construct($controller)
	{
		$this->c = &$controller;

		// S3 credentials

		$this->_APIKey = Sanitize::getString($controller->Config,'media_store_amazons3_key');

		$this->_APISecret = Sanitize::getString($controller->Config,'media_store_amazons3_secret');

		$this->_APIRegion = Sanitize::getString($controller->Config, 'media_store_amazons3_region', 'us-east-1');

		// S3 bucket names

		$this->_BucketVideo = Sanitize::getString($controller->Config,'media_store_amazons3_video');

		$this->_BucketPhoto = Sanitize::getString($controller->Config,'media_store_amazons3_photo');

		$this->_BucketAudio = Sanitize::getString($controller->Config,'media_store_amazons3_audio');

		$this->_BucketAttachment = Sanitize::getString($controller->Config,'media_store_amazons3_attachment');

		// Enable CDN

		$this->_BucketVideoCDN = Sanitize::getString($controller->Config,'media_store_amazons3_video_cdn');

		$this->_BucketPhotoCDN = Sanitize::getString($controller->Config,'media_store_amazons3_photo_cdn');

		$this->_BucketAudioCDN = Sanitize::getString($controller->Config,'media_store_amazons3_audio_cdn');

		$this->_BucketAttachmentCDN = Sanitize::getString($controller->Config,'media_store_amazons3_attachment_cdn');

		// CDN URLs

		$this->_VideoCDNURL = Sanitize::getString($controller->Config,'media_video_cdn_url');

		$this->_PhotoCDNURL = Sanitize::getString($controller->Config,'media_photo_cdn_url');

		$this->_AudioCDNURL = Sanitize::getString($controller->Config,'media_audio_cdn_url');

		$this->_AttachmentCDNURL = Sanitize::getString($controller->Config,'media_attachment_cdn_url');

		// Secure CDN URLs

		$this->_VideoSecureCDNURL = Sanitize::getString($controller->Config,'media_video_cdn_secure_url');

		$this->_PhotoSecureCDNURL = Sanitize::getString($controller->Config,'media_photo_cdn_secure_url');

		$this->_AudioSecureCDNURL = Sanitize::getString($controller->Config,'media_audio_cdn_secure_url');

		$this->_AttachmentSecureCDNURL = Sanitize::getString($controller->Config,'media_attachment_cdn_secure_url');

		// Storage settings

		$this->_HEADER_CACHE_CONTROL = 'max-age=31536000'; // 1year

		$this->_HEADER_EXPIRES = gmdate("D, d M Y H:i:s T",strtotime("+1 year"));

		$this->s3 = S3Client::factory(array(
			// Mar 16, 2017 - Added the v4 signature which is supported in all regions and it is required for new buckets
		    'signature' => 'v4',
		    'region'   => $this->_APIRegion,
		    'version'  => 'latest',
			'credentials' => array(
				'key' => $this->_APIKey,
				'secret' => $this->_APISecret
			)
		));

		// For newerd version of SDK for PHP 5.5 - Use when upgrading the SDK in the future!
		// $this->s3 = $sdk->createS3();
	}

	function getBasePath($media_type)
	{
		return $this->getStorageUrl($media_type, '', array('credentials'=>true));
	}

	/**
	 * Returns the remote url for the media
	 * @param type $media_type
	 * @param type $object relative path to object
	 * @return type
	 */
	function getStorageUrl($media_type, $object = '', $options = array())
	{
		$api = '';

		$use_credentials = Sanitize::getBool($options,'credentials');

		$use_cdn_url = Sanitize::getBool($options,'cdn') && $this->useCdnUrl($media_type);

		$bucket = $this->getBucketName($media_type);

		if($use_credentials)
		{
			$api = rawurlencode($this->_APIKey).':'.urlencode($this->_APISecret).'@';
		}

		if($use_cdn_url)
		{
			$url = $this->getCdnUrl($media_type) . '/' . $object;
		}

		// Mar 1, 2017 - SSL URL was not used on SSL connections
		elseif ($this->isSSL()) {

			if (strpos($bucket, '.') === true)
			{
				$url = 'https://'.$api.'s3.amazonaws.com/'.$bucket.'/'.$object;
			}
			else {
				$url = 'https://'.$api.$bucket.'.s3.amazonaws.com/'.$object;
			}
		}
		else {
			$url = 'http://'.$api.$bucket.'.s3.amazonaws.com/'.$object;
		}

		return $url;
	}

	function getFolderName($media_type)
	{
		return '';
	}

	function getStoragePath($url)
	{
		$parts = @parse_url($url);
		return ltrim($parts['path'],'/');
	}

	function getBucketName($media_type)
	{
		if($media_type == 'video_embed') $media_type = 'video';

		return $this->{'_Bucket'.ucfirst($media_type)};
	}

	function useCdnUrl($media_type)
	{
		if($media_type == 'video_embed') $media_type = 'video';

		return $this->{'_Bucket'.ucfirst($media_type).'CDN'};
	}

	function getCdnUrl($media_type)
	{
		$url = '';

		if($media_type == 'video_embed') $media_type = 'video';

		if(!empty($_SERVER['HTTPS']))
		{
			$url = $this->{'_'.ucfirst($media_type).'SecureCDNURL'};
		}

		if(trim($url) == '' || empty($_SERVER['HTTPS'])) {

			$url = $this->{'_'.ucfirst($media_type).'CDNURL'};
		}

		if(trim($url) == '')
		{
			$scheme = !empty($_SERVER['HTTPS']) ? 'https' : 'http';

			$url = $scheme . '://' . $this->getBucketName($media_type);
		}

		return $url;
	}

	/**
	 * Deliver file to browser for download
	 * @param type $media
	 * @return type
	 */
	public function download($media)
	{
		extract($media['Media']);

		$filepath = $media['Media']['media_path'] . '.' . $media['Media']['file_extension'];

		$fname = $media['Media']['filename'] . '.' . $media['Media']['file_extension'];

		$parts = pathinfo($fname);

		$name = explode('-',$parts['filename']);

		array_pop($name);

		$public_name = implode('-',$name) . '.' . $parts['extension'];

		// Adapted from https://blogs.aws.amazon.com/php/post/Tx2C4WJBMSMW68A/Streaming-Amazon-S3-Objects-From-a-Web-Server

		$this->s3->registerStreamWrapper();

		header('Content-Type: application/force-download');

		header('Content-Disposition: attachment; filename="'.$public_name.'"');

		if (ob_get_level()) {
		    ob_end_flush();
		}

		flush();

		readfile('s3://'.$this->getBucketName($media_type).'/'.$rel_path . $filename . '.' . $file_extension);

		exit;
	}

	/**
	 * @param type $input local file path
	 * @param type $target remote file name
	 * @return type remote path
	 */
	public function upload($media_info, $deleteInput = false)
	{
		$metaHeaders = $requestHeaders = array();

		extract($media_info); // media_type, tmp_path, rel_path, filename, file_extension

		$bucket = $this->getBucketName($media_type);

		$object = $rel_path . $filename . '.' . $file_extension;

		$mime_type = $this->c->MediaUpload->getMimeTypeFromExt($file_extension);

		$input = $this->isFile($tmp_path) ? fopen($tmp_path, 'r') : $tmp_path;

		$params = array(
		    'Bucket' => $bucket,
		    'Key'    => $object,
		    'Body'   => $input,
        	'ACL'    => 'public-read',
        	'CacheControl' => $this->_HEADER_CACHE_CONTROL,
        	'Expires' => $this->_HEADER_EXPIRES
		);

		if($mime_type)
		{
			$params['ContentType'] = $mime_type;
		}

		$result = $this->s3->putObject($params);

		// Multi-part uploads to handle large files
/*
		$uploader = UploadBuilder::newInstance()
		    ->setClient($this->s3)
		    ->setSource($input)
		    ->setBucket($bucket)
		    ->setKey($object)
		    ->setOption('ACL', 'public-read')
		    ->setOption('ContentType', $mime_type)
		    ->setOption('Expires', $this->_HEADER_EXPIRES)
		    ->setOption('CacheControl', $this->_HEADER_CACHE_CONTROL)
		    ->build();

		// Perform the upload. Abort the upload if something goes wrong

		try {
		    $result = $uploader->upload();
		}
		catch (MultipartUploadException $e) {

		    $uploader->abort();

		    $result = false;
		}
*/
		if($result)
		{
			$deleteInput and is_file($tmp_path) and @unlink($tmp_path); // Remove temporary file

			return $this->getStorageUrl($media_type, $object);
		}

		$deleteInput and is_file($tmp_path) and @unlink($tmp_path); // Remove temporary file

		return false;
	}

	/**
	 * Checks if given data is file, handles mixed input
	 * @param  mixed  $value
	 * @return boolean
	 */
    private function isFile($value)
    {
        $value = strval(str_replace("\0", "", $value));

        return is_file($value);
    }

	public function delete($url, $media)
	{
		$media_type = $media['Media']['media_type'];

		return $this->s3->deleteObject(array(
			'Bucket' => $this->getBucketName($media_type),
			'Key' => $this->getStoragePath($url)
		));
	}

	private function isSSL()
	{
		$https = false;

		if (isset($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) === 'on')
		{
			$https = true;
		}
		elseif (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https')
		{
			$https = true;
		}
		elseif (isset($_SERVER['HTTP_FRONT_END_HTTPS']) && $_SERVER['HTTP_FRONT_END_HTTPS'] === 'on')
		{
			$https = true;
		}

		return $https;
	}

}