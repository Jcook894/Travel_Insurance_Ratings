<?php
/**
 * Rapidlauncher Add-on for JReviews
 * Copyright (C) 2010-2016 ClickFWD LLC
 * This is not free software, do not distribute it.
 * For licencing information visit https://www.jreviews.com
 * or contact sales@jreviews.com
**/

defined( 'MVC_FRAMEWORK') or die( 'Direct Access to this location is not allowed.' );

S2App::import('AdminComponent', 'rapidlauncher_media_trait', 'jreviews');

class RapidlauncherVideoEmbedHelperComponent
{
	use RapidlauncherMediaTrait;

	protected $c;

	var $mediaType = 'video';

	protected $media;

	protected $mediaStorage;

	protected $deleteOriginal = true;

	protected $tmpFolder;

	function startup(& $controller)
	{
		$this->c = & $controller;

		$this->media = & $controller->Media;

		$this->mediaStorage = & $controller->MediaStorage;

		$this->tmpFolder =  cmsFramework::getConfig('tmp_path');
	}

	function create($listingId, $data)
	{
		$response = ['success'=>false,'str'=>[]];

		$videoUrl = Sanitize::getString($data, 'url');

		$reviewId = Sanitize::getInt($data, 'review_id');

		$userId = Sanitize::getInt($data, 'user_id');

		$extension = Sanitize::getString($data, 'extension', 'com_content');

		$created = Sanitize::getString($data, 'created');

		$caption = trim(Sanitize::getString($data, 'caption'));

		$mainMedia = Sanitize::getInt($data, 'main_media');

		$mediaFunction = Sanitize::getString($data, 'media_function');

		$hostname = @parse_url($videoUrl, PHP_URL_HOST);

		if(!$hostname)
		{
			return $this->c->response(false, 'Hostname not found.');
		}

		$hostname = explode('.',$hostname);

		array_pop($hostname);

		$videoService = array_pop($hostname);

		// Deal with shortened urls

		if($videoService == 'youtu')
		{
			$videoService = 'youtube';
		}

		if(!S2App::import('Component','media_storage_'.$videoService,'jreviews'))
		{
			return $this->c->response(false, 'Video service not found.');
		}

		$videoServiceClassName = inflector::camelize('media_storage_' . $videoService).'Component';

		$VideoService = new $videoServiceClassName($this->c);

		$photoUrl = '';

		$media_info = [];

		$out = $VideoService->processEmbed($videoUrl);

		if(!$out)
		{
			return $this->c->response(false, 'Video service embed error.');
		}

		$embed = $out['service']; // Used in output response

		$video = $out['Media'];

		$path = $this->grabRemoteFile($out['thumb_url']);

		if($path == '')
		{
			return $this->c->response(false, 'Video service embed error.');
		}

		// Extract filename and extension from path

		$fileName = Sanitize::getString($video, 'filename');

		$relativePath = Sanitize::getString($video, 'rel_path');

		$fileExtension = strtolower(pathinfo($path, PATHINFO_EXTENSION));

		$fileSize = filesize($path);

		$ordering = $this->media->getNewOrdering($listingId, $this->mediaType);

		$photoSize = getimagesize($path);

		$duration = Sanitize::getInt($out['Media'], 'duration');

		if(!$photoUrl = $this->mediaStorage->upload(
			[
				'media_type' => $this->mediaType,
				'embed'	=> true,
				'tmp_path' => $path,
				'rel_path' => $relativePath,
				'filename' => $fileName,
				'file_extension' => $fileExtension
			],
			$this->deleteOriginal)
		)
		{
			return $this->c->response(false, 'Could not grab video frame image.');
		}

		$mediaInfo = ['image' =>
			[
				'format'=>$fileExtension,
				'width'=>$photoSize[0],
				'height'=>$photoSize[1]
			]
		];

		$mediaData = ['Media' =>
			[
				'listing_id'		=> $listingId,
				'review_id'			=> $reviewId,
				'extension'			=> $extension,
				'filename' 			=> $fileName,
				'file_extension'	=> $fileExtension,
				'rel_path' 			=> $relativePath,
				'filesize'			=> $fileSize,
				'media_type'		=> $this->mediaType,
				'main_media'		=> $mainMedia,
				'media_function'	=> $mediaFunction,
				'title'				=> $caption ?: Sanitize::getString($video, 'title'),
				'description'		=> Sanitize::getString($video, 'description'),
				'user_id' 			=> $userId,
				'approved' 			=> 1,
				'published'			=> 1,
				'access'			=> 1,
				'created' 			=> $created,
				'ordering'			=> $ordering,
				'media_info'		=> json_encode($mediaInfo),
				'embed'				=> $videoService,
				'duration'			=> Sanitize::getInt($video, 'duration')
			]
		];

		$this->media->store($mediaData, $callbacks = []);

		return $this->c->response(true, '',
			[
				'media_id' => $mediaData['Media']['media_id'],
				'url' => $photoUrl
			]
		);

		return $response;
	}
}