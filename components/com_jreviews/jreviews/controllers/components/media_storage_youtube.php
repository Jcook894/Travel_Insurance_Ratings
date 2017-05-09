<?php
/**
 * JReviews - Reviews Extension
 * Copyright (C) 2010-201 ClickFWD LLC
 * This is not free software, do not distribute it.
 * For licencing information visit https://www.jreviews.com
 * or contact sales@jreviews.com
**/

defined( 'MVC_FRAMEWORK') or die( 'Direct Access to this location is not allowed.' );

class MediaStorageYoutubeComponent extends S2Component {

	public $service = 'Youtube.com';

	private $_APIKey;

	public $_API_VideoSSL = 'https://gdata.youtube.com/feeds/api/videos/%s?alt=jsonc&v=2';

	public $_API_Video = 'http://gdata.youtube.com/feeds/api/videos/%s?alt=jsonc&v=2';

	// Only SSL is supported for V3

	public $_API_V3 = 'https://www.googleapis.com/youtube/v3/videos?part=snippet,contentDetails&id=%s&key=%s';

	private $_CURL_OPTIONS = array(
		CURLOPT_RETURNTRANSFER => 1, // Return content of the url
		CURLOPT_HEADER => 0, // Don't return the header in result
		CURLOPT_HTTPHEADER => array("Content-Type: application/json", "Accept: application/json"),
		CURLOPT_CONNECTTIMEOUT => 0, // Time in seconds to timeout send request. 0 is no timeout.
	//    CURLOPT_FOLLOWLOCATION => 1, // Follow redirects.
		CURLOPT_SSL_VERIFYPEER => 0, // Enabling certificate verification makes the curl call fail on some servers
		CURLOPT_SSL_VERIFYHOST => 2
	);

	private $c;

	function __construct(&$controller)
	{
		parent::__construct();

		$this->c = & $controller;

		$this->_APIKey = Sanitize::getString($controller->Config,'media_youtube_key');
	}

	function getStorageUrl($media_type, $object = '')
	{
		return 'https://www.youtube.com/embed/'.$object;
	}

	static function getVideoId($url)
	{
		if (preg_match('%(?:youtube(?:-nocookie)?\.com/(?:[^/]+/.+/|(?:v|e(?:mbed)?)/|.*[?&]v=)|youtu\.be/)([^"&?/ ]{11})%i', $url, $match)) {

		    return $match[1];
		}

	    return false;
	}

	private function callAPI($url)
	{
		// Initialize session
		$ch = curl_init($url);

		// Set transfer options
		curl_setopt_array($ch, $this->_CURL_OPTIONS);

		// Execute session and store returned results
		$response = curl_exec($ch);

		curl_close($ch);

		return $response;
	}

	function processEmbed($url, $listing = null)
	{
		if(!$video_id = trim(self::getVideoId($url)))
		{
			return false;
		}

		if($this->_APIKey != '')
		{
			$response = $this->callAPI(sprintf($this->_API_V3, $video_id, $this->_APIKey));
		}
		else {

			$response = $this->callAPI(sprintf($this->_API_VideoSSL, $video_id));

			if(empty($response))
			{
				$response = $this->callAPI(sprintf($this->_API_Video, $video_id));
			}
		}

		if(empty($response))
		{
			return false;
		}


		$response = json_decode($response,true);

		/*
		 * some available keys in $response['data']
		 * tags array
		 * category string
		 * duration int - seconds
		 */

		$filename = $video_id . '-v' . time();

		// Added the count check because found a scenario where the API response doesn't fail, but a video is not resturned

		if(!isset($response['error']) && count($response['items']) > 0)
		{
			// Build response array to return it back to the controller
			// Add media to database

			// Process the V3 API response

			if($this->_APIKey)
			{
				$data = $response['items'][0]['snippet'];

				$duration_youtube = $response['items'][0]['contentDetails']['duration'];

				// Convert to seconds

				$matches = array();

				if(preg_match('/([0-9]*)H([0-9]*)M([0-9]*)S$/',$duration_youtube,$matches))
				{
					$duration = $matches[1]*3600 + $matches[2]*60 + $matches[3];
				}
				elseif(preg_match('/([0-9]*)M([0-9]*)S$/',$duration_youtube,$matches)) {

					$duration = $matches[1]*60 + $matches[2];
				}
				elseif(preg_match('/([0-9]*)M$/',$duration_youtube,$matches)) {

					$duration = $matches[1]*60;
				}
				elseif(preg_match('/([0-9]*)S$/',$duration_youtube,$matches)) {

					$duration = $matches[1];
				}

				$out = array(
					'service'  =>$this->service,
					'thumb_url' =>$data['thumbnails']['high']['url'],
					'Media'=>array(
						'filename' => $filename,
						'rel_path' => MEDIA_ORIGINAL_FOLDER . DS . MediaStorageComponent::getFolderHash($filename),
						'media_type'=> 'video',
						'title'=>$data['title'],
						'description'=>$data['description'],
						'embed'=>'youtube',
						'duration'=>$duration
					)
				);

			}
			else {

			// Process the V2 API response

				$out = array(
					'service'  =>$this->service,
					'thumb_url' =>$response['data']['thumbnail']['hqDefault'],
					'Media'=>array(
						'filename' => $filename,
						'rel_path' => MEDIA_ORIGINAL_FOLDER . DS . MediaStorageComponent::getFolderHash($filename),
						'media_type'=> 'video',
						'title'=>$response['data']['title'],
						'description'=>$response['data']['description'],
						'embed'=>'youtube',
						'duration'=>$response['data']['duration']
					)
				);

			}

			return $out;
		}

		return false;

	}

	static function displayEmbed($video_id, $size, $options = array())
	{
		$video_id = preg_replace('/-v[0-9]+$/','',$video_id);

		$src = "https://www.youtube.com/embed/$video_id?rel=0&wmode=opaque";

		if(Sanitize::getBool($options,'return_attr'))
		{
			$attr = array(
				'width'=>$size[0],
				'height'=>$size[1],
				'src'=>$src,
				'frameborder'=>0,
				'allowFullscreen'=>''
			);

			return htmlspecialchars(json_encode($attr),ENT_QUOTES);
		}

		echo '<iframe width="'.$size[0].'" height="'.$size[1].'" src="'.$src.'" frameborder="0" allowfullscreen></iframe>'
;

	}

}