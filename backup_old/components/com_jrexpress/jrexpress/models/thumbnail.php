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
		
		$path = PATH_ROOT . "images/stories/";
		
		$path_tn = PATH_ROOT . "images/stories/jreviews/tn/";
		
		$site = WWW_ROOT . "images/stories/";
		
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
