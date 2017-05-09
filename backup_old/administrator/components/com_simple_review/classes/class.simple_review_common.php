<?php
/**
 *  $Id: class.simple_review_common.php 95 2009-06-13 07:32:23Z rowan $
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
defined('_VALID_MOS')||defined( '_JEXEC' ) or die( 'Direct Access to this location is not allowed.' );
class Simple_Review_Common
{
 
	function RemoveSlashes($text)
	{
		return stripslashes($text);	
	}
	
	function EscapeNewlinesForJS($str)
	{
		$find = array("\r\n", "\r", "\n");
		$replace = array("\\r\\n", "\\r", "\\n");			
		return str_replace($find, $replace, $str);;
	}
	
 	function HTML2Text($document){
		$search = array('@<script[^>]*?>.*?</script>@si',  // Strip out javascript
		               '@<style[^>]*?>.*?</style>@siU',    // Strip style tags properly
		               '@<[\/\!]*?[^<>]*?>@si',            // Strip out HTML tags
		               '@<![\s\S]*?--[ \t\n\r]*>@'        // Strip multi-line comments including CDATA
		);
		$text = preg_replace($search, '', $document);
		return $text;
	}
 
    function IncludeCSS($cssLink)
    {	
	  	?>
	  	  	<script type='text/javascript'>
	  	  		var cssLink = '<?php echo $cssLink;?>';
	  	  		loadSRCSS(cssLink);
			</script>
	  	<?php
	}
	
	function IncludeJavaScript($jsLink)
	{ 	
	?>
		<script type="text/javascript" src="<?php echo $jsLink;?>"></script>	
	<?php
	}
	
	function SendEmail($to, $subject, $body)
	{
	  		$headers  = 'MIME-Version: 1.0' . "\r\n";
			$headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
			
			return mail($to, $subject, $body, $headers);
	}
	
    function CreateToolTip($text, $tooltip, $title='Info')
    {
    	return "<span class='row1Editlinktip'><span><a href='javascript:void(0);' title='$tooltip'>$text</a></span></span>";  		
	}
    

        
        function saveReview($published=false, $isUserReview=false)
         {
           global $database, $my, $sr_global;
          $row = new mosSimple_Review_Review( $database );

          if (!$row->bind( $_POST )) {
            echo "<script> alert('".$row->getError()."'); window.history.go(-1); </script>n";
            exit();
          }

		 /**user review stuff**/
		 if($isUserReview)
		 {
		 	$row->userReview = 1; 
			if($sr_global['allowableTags'] != '')
	        {
				   	$row->content = strip_tags($row->content, $sr_global['allowableTags']);
			}
			
			$row->title1 = htmlentities(strip_tags($row->title1));	 
			$row->title2 = htmlentities(strip_tags($row->title2));
			$row->title3 = htmlentities(strip_tags($row->title3));
			$row->thumbnailURL= htmlentities(strip_tags($row->thumbnailURL));
  			$row->imageURL= htmlentities(strip_tags($row->imageURL));
  			$row->blurb= htmlentities(strip_tags($row->blurb));
		 }

		 if($isUserReview && $sr_global['forceUserReviewTemplate'])
	        {

				//remove all simple tags
				$row->content = preg_replace("/(\{sr_.+\})/", "", $row->content);
				
				//get the categories template
	            $query = "SELECT templateID "
	        	. "\n FROM #__simplereview_category"
	        	. "\n where categoryID = $row->categoryID"
	        	;
	        	$database->setQuery( $query );
	        	$templateID = $database->loadResult();

				//insert the review bases on the template	
	            if($templateID != -1)
	            {
	               $query = "SELECT template "
	        	. "\n FROM #__simplereview_template"
	        	. "\n where templateID = $templateID"
	        	;
	        	 $database->setQuery( $query );
	        	 $template = $database->loadResult();
	        	 $row->content = str_replace("{sr_userReview}", $row->content, $template );
	            }

	        }
	        else
	        {
	            if(!$row->title2)
	            {
	                $row->content = str_replace("{sr_title2}", "", $row->content);
	            }
	            if(!$row->title3)
	            {
	                $row->content = str_replace("{sr_title3}", "", $row->content);
	            }
		  }
          $row->lastModifiedBy=$my->username;
		  $row->lastModifiedByID=$my->id;
		  
          $query = "SELECT NOW() as ts";
          $database->setQuery( $query );
          $rows = $database->loadObjectList();
          $currentTS = $rows[0]->ts;


          $row->lastModifiedDate  = $currentTS;
            //new row
            if(!$row->reviewID)
            {
                $row->createdBy = $my->username;
                $row->createdByID=$my->id;
                $row->createdDate = $currentTS;
            }

            $row->published = $published;
          if (!$row->store())
          {
            echo "<script> alert('".$row->getError()."'); window.history.go(-1); </script>";
            exit();
          }
          return $row;

    }               	
}

class Simple_Review_Common_Module
{
	function getTop($query,$title1length,$title2length,$title3length, $itemID, $cssClass, $moduleclass_sfx)
	{
		global $database, $mosConfig_live_site, $mosConfig_absolute_path;								
		$adminPath = "$mosConfig_absolute_path/administrator/components/com_simple_review";
		require_once("$adminPath/globals.php");
		require_once("$adminPath/addons/Addon_Base.php");
		require_once("$adminPath/addons/Addon_Base_Frontend.php");
		require_once("$adminPath/addons/Addon_Manager.php");			
		
		$SR_Addon_Manager =& Addon_Manager::Get();
      	
     	$reviewModule  =& $SR_Addon_Manager->GetModule("Review_Module", false);
		 					
		    
	    // Clear content
	    $content = "";
	
	    //get info about the comment and image
	
	    //query the database
	    $database->setQuery($query);
	    
		  if(!$database->query())	  
		  {
	        echo $database->stderr();
	        return false;	    	    
		  }	    
	    $rows =$database->loadObjectList();

		$count = count( $rows );
		if($count > 0)
		{
		  	require_once($mosConfig_absolute_path."/administrator/components/com_simple_review/config.simple_review.php");
			require_once($mosConfig_absolute_path."/components/com_simple_review/simple_review.html.php");
		}

		if($itemID == 0)
		{
		    $database->setQuery("SELECT id FROM #__menu WHERE link like '%index.php?option=com_simple_review%' LIMIT 1");
			if(!$database->query())	  
			 {
		        echo $database->stderr();
		        return false;	    	    
			 }	    
		    $itemID =$database->loadObjectList();
		    $itemID = $itemID[0]->id;
	    }
	    
	
	    //start building up the content
	    $placing = 1;
	    $content = "";
		
		$posCss = $cssClass.'Position'.$moduleclass_sfx;
		$titlesCss = $cssClass.'Titles'.$moduleclass_sfx;
		$scoreCss = $cssClass.'Score'.$moduleclass_sfx;
	    		    
	    foreach($rows as $row)
	    {
	        $reviewURL = $reviewModule->GetURL($row, "", $itemID);
	        // if the title is too big truncate it
	        if(strlen($row->title1) > $title1length)
	        {
	            $row->title1  = substr($row->title1,0,$title1length);
	            $row->title1 .= "...";
	        }
	
	        // if the title is too big truncate it
	        if(strlen($row->title2) > $title2length)
	        {
	            $row->title2  = substr($row->title2,0,$title2length);
	            $row->title2 .= "...";
	        }
	        
	        // if the title is too big truncate it
	        if(strlen($row->title3) > $title3length)
	        {
	            $row->title3  = substr($row->title3,0,$title3length);
	            $row->title3 .= "...";
	        }	        	        

			$titles="";
	        if($title1length > 0)
	        {
	            $titles.="$row->title1 ";
	        }
	        if($title2length > 0)
	        {
	            $titles.="$row->title2 ";
	        }
	        if($title3length > 0)
	        {
	            $titles.=$row->title3;
	        }

			$titles = Simple_Review_Common::RemoveSlashes($titles);

			$content.="<div class='$cssClass$moduleclass_sfx' style='width:100%; overflow: hidden;line-height:12px;height:12px;'>\n"	
		    		." 	<div class='$posCss' style='float:left;font-weight: bold;width:1.5em'>$placing</div>\n"
	        		."	<div class='$titlesCss' style='float:left;width:70%'><a href='$reviewURL'>$titles</a></div>\n"
					."	<div class='$scoreCss' style='font-weight: bold;'>$row->itemShown</div>\n"	        	        
	   				."</div>\n";
					
	        $placing++;
	 	 }
	    return $content;
	}  
}

class zSimple_Review_Users
{
  
  	var $userType = '';
  	var $userID = '-1';
  	
  	var $U_GUEST = 'guest';
  	var $U_REGISTERED = 'registered';
  	var $U_EDITOR = 'editor';
  	var $U_PUBLISHER = 'publisher';
  	var $U_MANAGER = 'manager';
  	var $U_ADMIN = 'administrator';
  	var $U_SUPER_ADMIN = 'super administrator';
  	
  	function Simple_Review_Users($userObject)
  	{
	    $this->userType = strtolower($userObject->usertype);
	    $this->userID = $userObject->id;
	}
	
	function User_Types()
	{
	  	return array ($this->U_GUEST, $this->U_REGISTERED, $this->U_EDITOR, $this->U_PUBLISHER, $this->U_MANAGER, $this->U_ADMIN, $this->U_SUPER_ADMIN);
	}
	
	function User_Types_Select_List($listname, $tag_attribs, $selected)
	{
		$html = "<select name='$listname' class='inputbox' multiple>";

        $alltypes = $this->User_Types();
        foreach ($alltypes as $type)
		{
          $html .= "<option value='$type'";
          if (in_array ($type, $selected)) 
          {
		  	$html.= "selected";
		  }
          $html.= ">$type</option>";
        }

        $html.="</select>";
        
        return $html;
	}
	
	function User_Auto_Publish_Review()
	{
	  	global $sr_global;
	  	$typesWhoCan = explode("||",$sr_global['autoPublishUserReview']);
	  	$userType = $this->userType;
	  	
	  	if($userType == null || $userType =='')
	  	{
		    $userType = $this->U_GUEST;
		}
	  	
	  	if(in_array($userType, $typesWhoCan))
	  	{
		    return true;
		}
		else
		{
		  	return false;
		}
	}
	
	function User_Can_Add_Review()
	{
	  	global $sr_global;
	  	$typesWhoCan = explode("||",$sr_global['userReview']);
	  	$userType = $this->userType;
	  	
	  	if($userType == null || $userType =='')
	  	{
		    $userType = $this->U_GUEST;
		}
	  	
	  	if(in_array($userType, $typesWhoCan))
	  	{
		    return true;
		}
		else
		{
		  	return false;
		}
	  /*
	    	$can_add = false;
	        switch ($this->userType)
	        {
	            case 'editor':
	            case 'publisher':
	            case 'manager':
	            case 'administrator':
	            case 'super administrator':
	            case 'registered':
	                $can_add = true;
	                break;
	           default:
	                $can_add = false;
	                break;
	        }
			return $can_add;  
		*/	  	
	}
	
	
	function User_Is_Moderator()
	{
	        switch ($this->userType)
	        {
	            case 'editor':
	            case 'publisher':
	            case 'manager':
	            case 'administrator':
	            case 'super administrator':
	                return true;
	                
	        	case 'registered':     
	           	default:
	                return false;
	        }	  	
	}
	
	function User_Can_Edit_Review()
	{
	    	$can_review = false;
	        switch ($this->userType)
	        {
	            case 'editor':
	            case 'publisher':
	            case 'manager':
	            case 'administrator':
	            case 'super administrator':
	                $can_review = true;
	                break;
	                
	        	case 'registered':     
	           	default:
	                $can_review = false;
	                break;
	        }
			return $can_review;	    		    	
	}
	
	
	function User_NameFromID($userID=0)
	{
		$userID = intval($userID);
		if($userID == 0)
		{
			SRError::Display( "UserID must be valid", true); 
			return;  
		}	  
		$query = "SELECT name FROM #__users WHERE id = $userID LIMIT 1;";
        return SRBridgeDatabase::ScalarQuery($query);		
	}
	function User_FromID($userID=0)
	{
		$userID = intval($userID);
		if($userID == 0)
		{
			SRError::Display( "UserID must be valid", true); 
			return;  
		}	  
		$query = "SELECT username FROM #__users WHERE id = $userID LIMIT 1;";
        return SRBridgeDatabase::ScalarQuery($query);		
	}	
	/*
	
	function User_Submitted_Items()
	{
	  global $articleTable, $statusTable;
	  
	  if(!$this->User_Is_Logged_In())
	  {
	  		return null;  
	  }
	  
	  $query = 	"SELECT articleID, title1, published, statusDescription  from $articleTable as art"
	  			."\nLEFT JOIN $statusTable as s ON art.statusID = s.statusID"
	   			."\nWHERE createdByID = $this->userID ORDER BY createdDate";
	  $rows = Simple_Review_Common::queryDatabase($query);
	  if($rows==false || count($rows) <= 0)
	  {
	  		return null;  
	  }
	  
	  return $rows;
	}

	function User_Submitted_Items_To_Review()
	{
	  global $articleTable, $statusTable, $PENDING;
	  
	  if(!$this->User_Can_Review_Articles())
	  {
	    	return null;
	  }

	  $query =   "SELECT art.articleID, art.title1, jusers.name, jusers.username, statusDescription, published"
	  		 	."\nFROM $articleTable AS art"
	  		 	."\nLEFT JOIN #__users AS jusers ON art.createdByID = jusers.id"
	  		 	."\nLEFT JOIN $statusTable as stat ON art.statusID = stat.statusID"
	  		 	."\nWHERE stat.title = '$PENDING' or art.published ='0'"
	  		 	."\nORDER BY createdDate";
	  		 	
	  $rows = Simple_Review_Common::queryDatabase($query);
	  if($rows==false || count($rows) <= 0)
	  {
	  		return null;  
	  }
	  
	  return $rows;
	}
	*/
}
 	class SRError
	{
		function Display($error, $exit=true)
		{
			$bridge =& SRBridgeManager::Get();

			if($bridge->InDebugMode)
			{
				$trace = debug_backtrace();
				echo "<h3>$error</h3>";
				echo '<p>Trace:';
				
				foreach($trace as $t)
				{ 
					echo '<div style="text-align:left;">';
                	echo "File: ".$t['file']." (Line: ".$t['line'].")<br/>"; 
                	echo "Function: ".$t['function']."<br/>"; 
                	echo "Args: ".implode(", ", $t['args']); 
					echo '</div>';
        		} 

				echo '</p>';
			}
			else
			{
			    echo "<h3>An error has occurred in Simple review. Please turn on debug mode in your sites configuration.</h3>";
			}
			if($exit)
			{
				exit();  
			}	
		}   
	} 

?>
