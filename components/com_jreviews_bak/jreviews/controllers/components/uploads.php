<?php
/**
 * JReviews - Reviews Extension
 * Copyright (C) 2010-2012 ClickFWD LLC
 * This is not free software, do not distribute it.
 * For licencing information visit http://www.reviewsforjoomla.com
 * or contact sales@reviewsforjoomla.com
**/

defined( 'MVC_FRAMEWORK') or die( 'Direct Access to this location is not allowed.' );

class UploadsComponent extends S2Component {
	
	var $msgTags;
	var $fileKeys;
	var $images;
	var $attack = null;
	var $success;
	
	function startup(&$controller) {
		$this->Config = &$controller->Config;
	}
	
	function checkImageCount($images) {
	
		if ($this->Config->content_images_total_limit) 
		{			
			if (!is_array($images)) { // Mambo 4.5 compat
				$images = explode("\n",trim($images));
			}
			
			foreach($images AS $key=>$image) {
				if(trim($image) == '') {
					unset($images[$key]);
				}
			}
			
			$current_images = count($images);
			
			$new_images = count($this->fileKeys);
			
			$total_allowed = $this->Config->content_images;

			if ($current_images + $new_images > $total_allowed) {
				return false;
			}		
		}
		
		return true;
 		
	}
	
	function validateImages() {
		
		if (isset($_FILES)) 
		{

			$supportedTypes =  array(
		    'image/gif',   // Opera, Moz, MSIE
		    'image/jpeg',  // Opera, Moz
		    'image/png',   // Opera, Moz
		    'image/pjpeg', // MSIE
		    'image/x-png'  // MSIE
			);

			$max_file_size = $this->Config->content_max_imgsize; // in Kbytes
			$msgTags = array();
			$err = 0;
			$fileKeys = array();

			if (isset($_FILES['image']['error'])) 
			{

				foreach ($_FILES['image']['error'] as $key=>$error) {

					$tmp_name = $_FILES['image']['tmp_name'][$key];
					$name = basename($_FILES['image']['name'][$key]);
					$size = $_FILES['image']['size'][$key];
					$type = $_FILES['image']['type'][$key];

					if ($name != '') { //ignore if field left empty
			
						if ($error == UPLOAD_ERR_OK && is_uploaded_file($tmp_name) ) {

							$err = 0;

							// File size check
							if ($size/1024 > $max_file_size) {
								$msgTags['file_size']['err'][] = $name.' '.sprintf(__t("is %s Kb.",true), number_format($size/1024,0));
								$msgTags['file_size']['label'] = __t("Some files exceed the allowed size, please correct this and resubmit the form:",true,true);
								$err = 1;
							}

							// File type check
                            $image_info = getimagesize($_FILES['image']['tmp_name'][$key]); // Checks if file is an actual image
                            $mimeType = isset($image_info['mime']) ? $image_info['mime'] : null;
							if (!$image_info || !in_array($mimeType, $supportedTypes)) {
								$msgTags['file_type']['err'][] = sprintf(__t("%s is not a supported image file.",true,true),$name);
								$msgTags['file_type']['label'] = __t("Some files are not images, please correct this and resubmit the form:",true,true);
								$err = 1;
							}

							if (!$err) {
								$fileKeys[] = $key;
							}
							
						} else {
							
							$this->attack = __t("Could not upload file.",true,true);
						
						}

					} // end if ($name!='')

				} // end foreach

			}

			if (!empty($fileKeys) && !$this->attack) {
				$this->success = true;
			} else {
				$this->success = false;
			}

			$this->fileKeys = $fileKeys;
			$this->msgTags = $msgTags;

		}	// end if isset
	
	} // End validate images

	function uploadImages($listing_id, $path) {
		
		$imgMaxWidth = $this->Config->content_max_imgwidth;

		$fileKeys = $this->fileKeys;
		
		$images = array();

		// Load thumbnail library
        App::import('Vendor', 'phpthumb' . DS . 'ThumbLib.inc');

		foreach ($fileKeys as $key) {

			$tmp_name = $_FILES['image']['tmp_name'][$key];

			$name = basename($_FILES['image']['name'][$key]);

			$fileParts = pathinfo($name);
			
			// Remove special chars, lowercase and trim
			$filename = trim(strtolower(Sanitize::translate($fileParts['filename'])));
			
			// Remove any duplicate whitespace, and ensure all characters are alphanumeric
			$filename = preg_replace(array('/\s+/','/\_+/','/\./','/[^A-Za-z0-9\-]/','/\-+/'), array('-','-','-','','-'), $filename);

			// Append datetime stamp to file name
			$filename = $filename."-".time();

			// Prepend contentid
			$filename = $listing_id."_".$filename.".".$fileParts['extension'];

			$uploadfile = $path . $filename;

			if (move_uploaded_file($tmp_name, $uploadfile)) {
				
				$images[] = "jreviews/" . $filename."|||0||bottom||";
				
				chmod($uploadfile, 0644);				

				// Begin image resizing
				if ($imgMaxWidth > 0) {
                    $thumb = PhpThumbFactory::create($uploadfile);
                    extract($thumb->getCurrentDimensions()); /* $width, $height */
                    $thumb->resize($imgMaxWidth,$height)->save($uploadfile);
				}
			}
		}

		$this->images = $images;

	}

	function getMsg() {
		
		$msg = '';
		$msgTags = $this->msgTags;

		if (!empty($msgTags)) 
		{
			foreach ($msgTags as $attrib) {

				$msg .= '<span>'.$attrib["label"].'</span>';
				
				$msg .= "<ul><li>".implode("</li><li>",$attrib["err"])."</li></ul>";
			
			}		
		}

		return $this->attack ? $this->attack."<br />".$msg : $msg;
	}

}