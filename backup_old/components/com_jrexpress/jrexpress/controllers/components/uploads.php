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
								$msgTags['file_size']['label'] = __t("Some files exceed the allowed size, please correct this and resubmit the form:",true);
								$err = 1;
							}

							// File type check
							if (!in_array($type, $supportedTypes)) {
								$msgTags['file_type']['err'][] = sprintf(__t("%s is not an image file.",true),$name);
								$msgTags['file_type']['label'] = __t("Some files are not images, please correct this and resubmit the form:",true);
								$err = 1;
							}

							if (!$err) {
								$fileKeys[] = $key;
							}
							
						} else {
							
							$this->attack = __t("Possible file upload attack.",true);
						
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
		App::import('Vendor', 'thumbnail' . DS . 'thumbnail.inc','jrexpress');

		foreach ($fileKeys as $key) {

			$tmp_name = $_FILES['image']['tmp_name'][$key];
			$name = basename($_FILES['image']['name'][$key]);

			// Append datetime stamp to file name
			$nameArray = explode (".",$name);

			// Leave only valid characters
			$nameArray[count($nameArray)-2] = preg_replace('/[^0-9a-z]+/i', '', $nameArray[count($nameArray)-2]);
			$nameArray[count($nameArray)-2] = preg_replace('/[^\w\d\s]+/i', '', $nameArray[count($nameArray)-2]);
			$nameArray[count($nameArray)-2] = $nameArray[count($nameArray)-2]."_".time();

			// Prepend contentid
			$name = $listing_id."_".implode(".",$nameArray);

			$uploadfile = $path . $name;

			if (move_uploaded_file($tmp_name, $uploadfile)) {
				
				$images[] = "jreviews/" . $name."|||0||bottom||";
				
				chmod($uploadfile, 0644);				

				// Begin image resizing
				if ($imgMaxWidth > 0) {

					$thumb = new Thumbnail($uploadfile);
					if ($thumb->getCurrentWidth() > $imgMaxWidth) {
						$thumb->resize($imgMaxWidth,$thumb->getCurrentHeight());
					}
					
					$thumb->save($uploadfile);

					$thumb->destruct();

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
?>