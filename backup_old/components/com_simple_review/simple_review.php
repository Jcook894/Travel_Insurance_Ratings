<?php
/**
 *  $Id: simple_review.php 117 2009-08-10 13:44:14Z rowan $
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

/** ensure this file is being included by a parent file */
defined('_VALID_MOS')||defined( '_JEXEC' ) or die('Direct Access to this location is not allowed.');

$adminPath = "";

if(defined( '_JEXEC' ) )
{
	$adminPath = JPATH_COMPONENT_ADMINISTRATOR;
}
else
{
	$adminPath = "$mosConfig_absolute_path/administrator/components/com_simple_review";	
}
require_once("$adminPath/classes/SRBridgeCMS.php");
// Load the HTML class
require_once($mainframe->getPath('front_html'));
require_once("$adminPath/classes/class.simple_review_common.php");
require_once("$adminPath/classes/SRFrontEndRouter.php");
$bridge =& SRBridgeManager::Get();
$jsPath = $bridge->SiteUrl.'components/com_simple_review/javascript';
Simple_Review_Common::IncludeJavaScript("$jsPath/SRCore.js");
$cssFile =  $bridge->SiteUrl.'components/com_simple_review/css/Simple_Review.css';
Simple_Review_Common::IncludeCSS($cssFile );

$router = new SRFrontEndRouter();
$router->Route();

echo "<p><i><small>Powered by <a href='http://simple-review.com'>Simple Review</a></small></i></p>";


/*
function deleteComment($commentID, $reviewID)
{
    global $my, $database, $Itemid;
    $can_delete = (strtolower($my->usertype) == 'editor' || strtolower($my->usertype) == 'publisher' || strtolower($my->usertype) == 'manager' || strtolower($my->usertype) == 'administrator' || strtolower($my->usertype) == 'super administrator' );
    if(!$can_delete)
    {
            echo "<script> alert('You do not have permission to delete user comments!'); window.history.go(-1); </script>n";
            exit();
    }
    $database->setQuery( "DELETE FROM #__simplereview_comments where commentID=$commentID limit 1" );
    if(!$database->query())
    {
        echo "Deletion failed.";
        return;
    }
    mosRedirect("index.php?option=com_simple_review&reviewID=$reviewID&task=displayReview&Itemid=$Itemid", "The comment has been deleted.");
}
*/
?>
