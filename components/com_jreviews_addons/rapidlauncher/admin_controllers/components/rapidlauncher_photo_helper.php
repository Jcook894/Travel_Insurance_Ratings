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

class RapidlauncherPhotoHelperComponent
{
	use RapidlauncherMediaTrait;

	protected $c;

	var $mediaType = 'photo';

	protected $media;

	protected $mediaStorage;

	protected $deleteOriginal = false;

	protected $path;

	function startup($controller)
	{
		$this->c = $controller;

		$this->package = Sanitize::getString($controller->params, 'package');

		$this->media = & $controller->Media;

		$this->mediaStorage = & $controller->MediaStorage;
	}

	public function setPath($path)
	{
		$this->path = $path;

		return $this;
	}

	function create($listingId, $data)
	{
		$response = ['success'=>false,'str'=>[]];

		if($url = $this->isRemote(Sanitize::getString($data, 'path')))
		{
			$this->deleteOriginal = true;

			$path = $this->grabRemoteFile($url);
		}
		else {

			$path = $this->path . DS . $this->package . DS . 'photos' . DS . Sanitize::getString($data, 'path');
		}

		if(!file_exists($path))
		{
			return $this->c->response(false, 'File not found.');
		}

		$reviewId = Sanitize::getInt($data, 'review_id');

		$userId = Sanitize::getInt($data, 'user_id');

		$extension = Sanitize::getString($data, 'extension', 'com_content');

		$created = Sanitize::getString($data, 'created');

		$caption = trim(Sanitize::getString($data, 'caption'));

		$imageSize = Sanitize::getString($data, 'size');

		$mediaFunction = Sanitize::getString($data, 'media_function');

		$mainMedia = Sanitize::getInt($data, 'main_media');

		// Extract filename and extension from path

		$fileName = pathinfo($path, PATHINFO_FILENAME);

		$fileExtension = strtolower(pathinfo($path, PATHINFO_EXTENSION));

		$fileSize = filesize($path);

		$relativePath = MEDIA_ORIGINAL_FOLDER . _DS . MediaStorageComponent::getFolderHash($fileName,true);

		$ordering = $this->media->getNewOrdering($listingId, $this->mediaType);

		$photoSize = getimagesize($path);

		$mediaInfo = ['image' =>
			[
				'format'=>$fileExtension,
				'width'=>$photoSize[0],
				'height'=>$photoSize[1]
			]
		];

		if(!$photoUrl = $this->mediaStorage->upload(
			[
				'media_type' => $this->mediaType,
				'tmp_path' => $path,
				'rel_path' => $relativePath,
				'filename' => $fileName,
				'file_extension' => $fileExtension
			],
			$this->deleteOriginal)
		) {
			return $this->c->response(false, 'Problem uploading the photo.');
		}

		$mediaData = ['Media' =>
			[
				'listing_id'		=> $listingId,
				'review_id'			=> $reviewId,
				'extension'			=> $extension,
				'filename' 			=> $fileName,
				'file_extension'	=> $fileExtension,
				'filesize'			=> $fileSize,
				'rel_path' 			=> $relativePath,
				'media_type'		=> $this->mediaType,
				'main_media'		=> $mainMedia,
				'media_function'	=> $mediaFunction,
				'title'				=> $caption,
				'user_id' 			=> $userId,
				'approved' 			=> 1,
				'published'			=> 1,
				'access'			=> 1,
				'created' 			=> $created,
				'ordering'			=> $ordering,
				'media_info'		=> json_encode($mediaInfo)
			]
		];

		$this->media->store($mediaData, $callbacks = []);

		return $this->c->response(true, '',
			[
				'media_id' => $mediaData['Media']['media_id'],
				'url' => $photoUrl
			]
		);
	}
}