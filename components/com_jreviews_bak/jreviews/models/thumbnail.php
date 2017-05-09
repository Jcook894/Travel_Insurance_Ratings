<?php
/**
 * JReviews - Reviews Extension
 * Copyright (C) 2010-2012 ClickFWD LLC
 * This is not free software, do not distribute it.
 * For licencing information visit http://www.reviewsforjoomla.com
 * or contact sales@reviewsforjoomla.com
**/

defined( 'MVC_FRAMEWORK') or die( 'Direct Access to this location is not allowed.' );

class ThumbnailModel extends MyModel  {
	
	var $useTable = '';
	
	/**
	 * Deletes listing thumbnails
	 *
	 * @param unknown_type $data
	 * @return unknown
	 */
	function delete(&$data){
		
		$error = false;

		if (is_array($data['Listing']['images'])) { // Mambo 4.5 compat
			$imgString = implode( "\n",$data['Listing']['images']);
		} else {
			$imgString = $data['Listing']['images'];		
		}

		$imageArray = explode("\n",trim($imgString));
		
		$path = PATH_ROOT . _JR_PATH_IMAGES;
		
		$path_tn = PATH_ROOT . _JR_PATH_IMAGES . 'jreviews' . DS . 'tn' . DS;
		
		$site = WWW_ROOT . _JR_WWW_IMAGES;
		
		$site_tn = $site . "jreviews/tn/";
				
		// delete originals
		foreach ($imageArray AS $image) {			

			$image = explode('|',$image);
			$image = trim($image[0]);
			if($image != '' && file_exists($path.$image)) {
				if(@!unlink($path.$image)) {
					$error = true;
				}
			}
		}
		
		//delete thumbs
		$dh = dir($path_tn);
		while($filename = $dh->read()) {
			if(preg_match('/^tn_'.$data['Listing']['id'].'_/', $filename)) {
				$matching[] = $filename; // array of thumbnail filenames
			}
		}
		
		$dh->close();
		
		if(!empty($matching)) {
			foreach($matching AS $thumb) {
				if(@!unlink($path_tn.$thumb)) {
					$error = true;
				}
			}
		}

		return $error;	
	}
	
}
