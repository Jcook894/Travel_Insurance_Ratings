<?php
/**
 *  $Id: simple_review.html.php 81 2009-05-17 12:34:52Z rowan $
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


class HTML_Simple_Review
{
        
    function printOverlibBlobCode($content)
    {
 	   //preg_match_all("/([\w\-\s]+)/", $content, $matches);
	   //$blob = implode('', $matches[1]);   
	      
       $blob = strip_tags($content);
       $specialChars = array("\n", "\r", "\t", "\0", "'", "\"");
       $blob = str_replace($specialChars, " ", $blob);
       
       $length = strlen($blob);       
       $blob = substr($blob, 0, 250);
        
       if($length > 250)
       {
            $blob.="...";
        }

        return "onMouseOver=\"return overlib('<table><tr><td>$blob</td></tr></table>', CAPTION, 'Review Blob', BELOW, RIGHT);\" onMouseOut=\"return nd();\"";
    }
    function avgUserScore($reviewID)
    {
        global $database;
        $query = "SELECT avg( userRating ) as avg FROM #__simplereview_comments WHERE reviewID = $reviewID and userRating!=-1";
        $avg = SRBridgeDatabase::ScalarQuery($query);	
        if($avg ==false)
        {
		  return null;
		}
        return number_format($avg,1);
    }
     /*   
    function userReview($option, $row=NULL)
    {
        global $sr_global, $my, $mosConfig_absolute_path,$mosConfig_live_site, $database, $SR_LANG;
        echo "<!-- user review start-->";
        $can_add = false;

		$sr_user = new Simple_Review_Users($my);
		$can_add = $sr_user->User_Can_Add_Review();
        
        if( !$can_add )
        {
            echo $SR_LANG['USER_REVIEW_PERMISSION'];
            return;
        }

		//echo '<div STYLE="overflow: auto;">';
		require_once("scripts/simple_review.scripts.php");
		require_once("scripts/dynamicfields.scripts.php");
        mosCommonHTML::loadOverlib();

        require_once($mosConfig_absolute_path."/administrator/components/com_simple_review/classes/class.simple_review_review.html.php");
        require_once($mosConfig_absolute_path."/administrator/components/com_simple_review/classes/class.simple_review_common.php");


            //$values;
            $values = Simple_Review_Common::loadReviewValues($row);
            
            HTML_Simple_Review_Review::genReviewSubmitScript();

            echo "<table width=100%>";
            
            echo "<tr><td><p>";
            if($sr_global['forceUserReviewTemplate'])
            {	 
              $values['content'] = ''; 	
            }
            else
            {
              echo "<div id='help'>";
			  Simple_Review_Common::printTagList();
			  echo "<div>";
			}
            echo "<br/></p></td><td>";
            
            echo "<tr><td>";
            
            echo "<form action='index.php' method='post' name='adminForm' id='adminForm'>";
            $formTable = HTML_Simple_Review_Review::printFormTable($values, true);
            if($formTable!=null)
            {
            echo "<input type='hidden' name='option' value='$option' />";
            echo "<input type='hidden' name='task' value='saveUserReview' />";
            echo "<INPUT TYPE='Submit' NAME='submitButton' VALUE='Submit Review'>";
            }
            echo "</form>";
	            
            echo "</td><td>";
            echo "</td></tr></table>";
            
            //echo "</div>";
            echo "<!-- user review end-->";
    }*/
    
    //do not edit!
    function displaySignature()
    {
      global $sr_global;
        echo $sr_global['sr_sig'];
    }
    
}

?>
