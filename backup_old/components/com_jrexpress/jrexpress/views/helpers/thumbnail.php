<?php
/*
    JReviews Express - user reviews for Joomla
    Copyright (C) 2009  Alejandro Schmeichler

    JReviews Express is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    JReviews Express is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/

defined( 'MVC_FRAMEWORK') or die( 'Direct Access to this location is not allowed.' );

class ThumbnailHelper extends HtmlHelper {
	
	var $quality = 85;
	var $path;
	var $path_tn;
	var $site;
	var $site_tn;
	var $image_size;
	var $catImage = false;
	var $noImage = false;
	
	function __construct() {
		
		App::import('Vendor', 'thumbnail' . DS . 'thumbnail.inc','jrexpress');

		$this->path = PATH_ROOT . 'images'._DS.'stories'._DS;
		$this->path_tn = PATH_ROOT . 'images'._DS.'stories'._DS.'jreviews'._DS.'tn'._DS;
		$this->www = WWW_ROOT . 'images'._DS.'stories'._DS;
		$this->www_tn = $this->www . 'jreviews'._DS.'tn'._DS;		
	}
		
	function lightbox($listing, $position=0, $action='scale', $location = '_', $dimensions = null, $attributes = array()) {
				
//		if(!isset($listing['Listing']['images'][$position]) || !file_exists($this->path.$listing['Listing']['images'][$position]['path'])) {
//			return '';
//		}
		
		if(!$dimensions) {
			$dimensions = array($this->Config->list_image_resize);
		}
		
		$listing_id = $listing['Listing']['listing_id'];
		$image = $listing['Listing']['images'][$position];
		$cat_image = $listing['Listing']['category_image'];

		$thumb = $this->thumb($listing, $position, $action, $location, $dimensions, $attributes);

		if($thumb) {	
			
			// If listing has no images then this is a category or no image and it shouldnt be lightboxed
			if(!isset($listing['Listing']['images'][$position]) || !file_exists($this->path.$listing['Listing']['images'][$position]['path'])) {
				return $thumb;
			}					

			$lightbox = $this->link($thumb,$this->www.$image['path'],array('sef'=>false,'class'=>'thickbox','rel'=>'gallery','title'=>$image['caption']));
			
			return $lightbox;
		}
		
	}

	function thumb($listing, $position=0, $action='scale', $location = '_', $dimensions = null, $attributes = array()) 
    {
        // No JReviews uploaded images, so we search the summary for images - JReviews Express
        if(!isset($listing['Listing']['images'][$position])){
            $img_src = "/\< *[img] *[src]= *[\"\']{0,1}([^\"\'\>]*)/i";
            $img_src = '/<img[^>]+src[\\s=\'"]+([^"\'>\\s]+)/is';
            preg_match($img_src,$listing['Listing']['summary'],$matches);
            if($matches){
                $listing['Listing']['images'][0] = array('path'=>str_replace('images/stories/','',urldecode($matches[1])));
            }
        }
              
		$image = null;
		
		if(!$dimensions) {
			$dimensions = array($this->Config->list_image_resize);
		}

		$listing_id = $listing['Listing']['listing_id'];
		
		if(isset($listing['Listing']['images'][$position]))	
		{
			$image = $listing['Listing']['images'][$position];
		}

		$cat_image = isset($listing['Listing']['category_image']) ? $listing['Listing']['category_image'] : '';

		$output = $this->makeThumb($listing_id, $image, $action, $location, $dimensions, $cat_image, $attributes);

		if($output) {
			return $this->image($output['thumbnail'],$attributes);
		} 
		
		return false;
		
	}
	
	function crop($imagePath, $thumbnailPath, $dimensions) {

		$crop = false;
		$newSize = trim(intval($dimensions[0])) > 0 ? trim(intval($dimensions[0])) : 100;

		$thumb = new Thumbnail($imagePath);

		if ($thumb->error) {
			echo $imagePath.":".$thumb->errmsg."<br />";
			return false;
		}

		$minLength = min($thumb->getCurrentWidth(), $thumb->getCurrentHeight());
		
		$maxLength = max($thumb->getCurrentWidth(), $thumb->getCurrentHeight());

	    // Image is smaller than the specified size so we just rename it and save
		if ($maxLength <= $newSize) {

			$thumb->save($thumbnailPath, $this->quality); //Just rename and save without processing

		} else { // At least one side is larger than specified thumbnail size

			// Both sides are larger than resize length so first we scale and if image is not square we crop
			if ($minLength > $newSize) {
				// Scale smaller size to desired new size
				if ($thumb->getCurrentWidth() < $thumb->getCurrentHeight()) {
					$thumb->resize($newSize,0);
					$crop = true;
				} elseif ($thumb->getCurrentWidth() > $thumb->getCurrentHeight()) {
					$thumb->resize(0,$newSize);
					$crop = true;
				} else {
					$thumb->resize($newSize,$newSize);
				}

				if ($crop) {
		       		$thumb->cropFromCenter($newSize);
				}
			// One size is smaller than the new size, so we only crop the larger size to the new size
			} else {
			    $cropX = intval(($thumb->getCurrentWidth() - $newSize) / 2);
			    $cropY = intval(($thumb->getCurrentHeight()- $newSize) / 2);
	       		$thumb->crop($cropX,$cropY,$newSize,$newSize);
			}

			$thumb->save($thumbnailPath, $this->quality);

		}

		$thumb->destruct();

		if (file_exists($thumbnailPath)) {
			return true;
		} 
		
		return false;

	}	
	
	function scale($imagePath, $thumbnailPath, $dimensions) {

		$imgMaxWidth = min($this->image_size[0],trim(intval($dimensions[0])));
//		$imgMaxHeight = trim(intval($this->size));

		$thumb = new Thumbnail($imagePath);
		
		if ($thumb->error) {
			echo $imagePath.":".$thumb->errmsg."<br />";
			return false;
		}
//		$thumb->resize($imgMaxWidth,$imgMaxHeight);
		$thumb->resize($imgMaxWidth);

		$thumb->save($thumbnailPath, $this->quality);

		$thumb->destruct();

		if (file_exists($thumbnailPath)) {
			return true;
		} 
		return false;
	}

	/**
	 * Creates a thumbnail if it doesn't already exist and returns an array with full paths to original image and thumbnail
	 * returns false if thumbnail cannot be created
	 *
	 * @param int $listing_id listing id
	 * @param array $image array of image path and caption
	 * @param string $action can be 'scale' or 'crop'
	 * @param string $location this variable is used to have different image sizes for lists, details and modules 
	 * @param string $dimensions array of width and height
	 * @param string $cat_image category image name
	 * @param string $no_image noimage image name
	 */
	function makeThumb($listing_id, $image, $action='scale', $location = '_', $dimensions, $cat_image) 
    {

		$imageName = '';
		$this->catImage = false;		
		$this->noImage = false;
		
		if($location != '_') {
			$location = '_'.$location.'_';
		}

		if(isset($image['path'])) 
		{           
			if(isset($image['skipthumb']) && $image['skipthumb']===true) {
				return array('image'=>$image['path'],'thumbnail'=>$image['path']);
			}
			
			$temp = explode( '/', $image['path']);
			$imageName = $temp[count($temp)-1];
			$length = strlen($listing_id);

			if (substr($imageName,0,$length+1) == $listing_id.'_') {
				// Uploaded image already has entry id prepended so we remove it and put it before the content suffix
				$imageName = substr($imageName,$length+1);
			}
			
			$thumbnail = "tn_".$listing_id.$location.$imageName;
	
			$output = array(
							'image'=>$this->www.$image['path'],
							'thumbnail'=>$this->www_tn.$thumbnail
						);
			
			$image_path = trim(isset($image['basepath']) && $image['basepath'] ? $image['path'] : $this->path.$image['path']);

			if ($imageName != '' && file_exists($image_path)) {

				$this->image_size = getimagesize($image_path);
							
				if(file_exists($this->path_tn.$thumbnail)) 
				{ // Tbumbnail exists, so we check if current size is correct
	
					$thumbnailSize = getimagesize($this->path_tn.$thumbnail);

					// Checks the thumbnail width to see if it needs to be resized
					if ($thumbnailSize[0] == $dimensions[0] || $this->image_size[0] <= $dimensions[0]
						|| ($action == 'crop' && $thumbnailSize[0] == $thumbnailSize[1])
					) {
						// No resizing is necessary
						return $output;
					}
				}

				// Create the thumbnail
				if($this->$action($image_path, $this->path_tn.$thumbnail, $dimensions)) {
					return $output;
				}
				
			}
		}
		
		if ($this->Config->list_category_image && $cat_image != '') {
			
			$this->image_size = getimagesize($this->www.$cat_image);
			
			if($this->image_size[0] == min($this->image_size[0],trim(intval($dimensions[0])))) {
				// Image is smaller (narrower) than thumb so no thumbnailing is done
				return array(
					'image'=>$this->www.$cat_image,
					'thumbnail'=>$this->www.$cat_image
				);						
			}

			// Create category thumb
			if ($this->$action($this->path.$cat_image, $this->path_tn.'tn'.$location.$cat_image, $dimensions)) {
				$this->catImage = true;
				return array(
					'image'=>$this->www.$cat_image,
					'thumbnail'=>$this->www_tn.'tn'.$location.$cat_image
				);
			}
		}
			
		// Create no image thumb
		$noImagePath = 	$this->viewImagesPath . $this->Config->list_noimage_filename;
		
		$noImageThumbnailPath = $this->path_tn . _DS . 'tn'.$location.$this->Config->list_noimage_filename;		
		$noImageWww = 	$this->viewImages . $this->Config->list_noimage_filename;

		if ($this->Config->list_noimage_image && $this->Config->list_noimage_filename!= '') {
			
			$thumbExists = file_exists($noImageThumbnailPath);
			
			if($thumbExists) {
				$noImageSize = $this->image_size = getimagesize($noImagePath);

				if($this->image_size[0] == min($this->image_size[0],trim(intval($dimensions[0])))) {
					// Image is smaller (narrower) than thumb so no thumbnailing is done
					return array(
						'image'=>$noImageWww,
						'thumbnail'=>$noImageWww
					);					
				}
				
				if(($noImageSize[0]!=$dimensions[0])) {
					$this->$action($noImagePath,$noImageThumbnailPath, $dimensions);					
				}
			} else {
				$this->$action($noImagePath,$noImageThumbnailPath, $dimensions);
			}

			$this->noImage = true;

			return array(
				'image'=>$noImageWww,
				'thumbnail'=> $this->www_tn . 'tn' . $location . $this->Config->list_noimage_filename
			);
		}
		
		return false;
	}

}