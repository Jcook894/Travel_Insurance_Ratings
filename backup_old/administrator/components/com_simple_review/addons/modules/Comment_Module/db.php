<?php
/**
 *  $Id: db.php 77 2009-05-11 06:19:56Z rowan $
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

class SRDB_Comment extends SRBridgeDatabaseTable 
{
  var $commentID = null;
  var $reviewID  = null;
  var $anonymousName = null;
  var $createdBy = null;//depricated
  var $createdDate = null;
  var $comment = null;
  var $userRating = null;
  var $published = null;
  var $avatar = null;
  var $plainComment = null;//depricated
  var $createdByID = null;
  var $userIP = null;
  
  function SRDB_Comment( &$db ) 
  {
		$this->InitTable($this->TableName(), 'commentID', $db );
  }
  
	/**
	*	overloaded check method
	*
	*	@return boolean True if the object is ok
	*/
	function check() 
	{
		$this->reviewID  = intval($this->reviewID, 10);
		$this->userRating = intval($this->userRating, 10);
		$this->published = intval($this->published, 10);
			   			   
		if(($this->anonymousName == null || $this->anonymousName == "") && 	$this->createdByID == 0)
		{
			return false;	  
		}  	   
		
		if($this->createdByID == 0)
				$this->createdByID = -1;
			
		if($this->avatar == null || $this->avatar == "" || $this->avatar == '-1')
		{
		   $this->avatar = "noimage.png";
		}						
			   
		$this->comment= Simple_Review_Common::HTML2Text($this->comment);
		
		if(strlen($this->comment) > Comment_Module_Max_Length)
		{
			$this->comment = substr($this->comment, 0, Comment_Module_Max_Length) . '...';
		}		   
			   	   
		$this->createdBy = null;
		$this->plainComment = null;	  
		return true;
	}
	
	function load( $oid=null )
	{
		parent::load($oid);
		if($oid != null)
		{
			$this->EscapeTextFields($this);
		}	
	}
	  
	function EscapeTextFields(&$comment)
	{
		$comment->anonymousName = Simple_Review_Common::RemoveSlashes($comment->anonymousName);
		$comment->comment = Simple_Review_Common::RemoveSlashes($comment->comment);
	}	
	
	function TableName()
	{
		return '#__simplereview_comments';	
	}
	 
	function SR_Delete($commentID)
	{
		$commentTableName = $this->TableName();	
		$query = "DELETE FROM $commentTableName WHERE commentID = $commentID";
		SRBridgeDatabase::Query($query);	
	}    
 } 

?>