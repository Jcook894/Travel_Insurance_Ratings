<?php
/**
 *  $Id: Addon_Common.php 81 2009-05-17 12:34:52Z rowan $
 *
 * 	Copyright (C) 2005-2009  Rowan Youngson
 * 
 *	This file is part of Simple Review.
 *
 *	Simple Review is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation, either version 3 of the License, or
 *  (at your option) any later version.

 *  Simple Review is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.

 *  You should have received a copy of the GNU General Public License
 *  along with Simple Review.  If not, see <http://www.gnu.org/licenses/>.
*/

// ensure this file is being included by a parent file
defined('_VALID_MOS')||defined( '_JEXEC' ) or die( 'Direct Access to this location is not allowed.' );


//REMOVE THIS CLASS, put into modules
class Addon_Common
{    
  	function GetCategoryTemplate($categoryID)
  	{
  	  		global $database;
			$templateTable = '#__simplereview_template';
			$categoryTable = '#__simplereview_category';
            $query = "SELECT templateID"
                    ."\nFROM $categoryTable"
                    ."\nORDER BY categoryID"
                    ."\nLIMIT 1";

        	$database->setQuery( $query );
        	$templateID = $database->loadResult();
            if($templateID != -1)
            {
               $query = "SELECT template "
			        	."\n FROM $templateTable"
			        	."\n where templateID = $templateID";
        	 $database->setQuery( $query );
        	 $template = $database->loadResult();
        	 return $template;
            }
            else
            {
                return '';
            }	    
	} 
	
  
}

?>
