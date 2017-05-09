<?php
/**
 *  $Id: db.php 120 2009-09-13 05:37:35Z rowan $
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

class SRDB_Review extends SRBridgeDatabaseTable {
  // INT(11) AUTO_INCREMENT
  var $reviewID=null;
  
  //int fk
  var $categoryID=null;

  //int fk
  var $awardID=null;
  
  //varchar 255
  var $pageName = null;
  var $thumbnailURL=null;
  var $imageURL=null;
  
  //longtext
  var $content=null;
  
  //text
  var $blurb=null;
  
  //varchar 20
  var $score = null;
  
  //userid FK int
  var $createdByID=null;
  //timestamp
  var $createdDate=null;


  //userid fk int 
  var $lastModifiedByID=null;
  //timestamp
  var $lastModifiedDate=null;

  
  //tinyint
  var $published=null;
  var $userReview = null;

  function TableName()
  {
    return '#__simplereview_review';
  }

  function SRDB_Review( &$db ) 
  {
    $this->InitTable( $this->TableName(), 'reviewID', $db );
  }
  
  function load( $oid=null )
  {
  	parent::load($oid);
	if($oid != null)
	{
		$this->EscapeTextFields($this);
	}	
  }
  
  function store($updateNulls=false )
  {		
		$currentTS = SRBridgeDatabase::ScalarQuery('SELECT NOW() as ts');
		$srUser = new SRBridgeUser();		
		$this->lastModifiedByID  =$srUser->ID;
		$this->lastModifiedDate  = $currentTS;
		//new row
		if(!$this->reviewID)
		{
		   $this->createdDate = $currentTS;
		   $this->createdByID = $srUser->ID;
		}		
		
		if($this->published != 1)
		{
			$this->published = 0;		
		}
		
		if($this->pageName != null)
		{
			//$this->pageName = preg_replace('/[^\w\.\-_]/u', '', $this->pageName);
			$this->pageName = rawurlencode($this->pageName);
		}
		
		return parent::store($updateNulls);  	
  }
  
  function EscapeTextFields(&$review)
  {
		$review->thumbnailURL = Simple_Review_Common::RemoveSlashes($review->thumbnailURL);
		$review->imageURL = Simple_Review_Common::RemoveSlashes($review->imageURL);
		$review->content = Simple_Review_Common::RemoveSlashes($review->content);
		$review->blurb = Simple_Review_Common::RemoveSlashes($review->blurb);
  }
  
  function GetReviewListLimited($categoryID = null, $limitStart, $limitEnd=null)
  {
	$tableName = SRDB_Review::TableName(); 
	$catTableName = '#__simplereview_category';
	$reviewTitleTableName = '#__simplereview_review_title';
	$usersTable = '#__users';
	
	$reviewClause = $categoryID == null ? '' : "\nWHERE r.categoryID = $categoryID";
	$limit = $limitEnd == null ? "\nLIMIT $limitStart" : "\nLIMIT $limitStart, $limitEnd";
	
	$query = "SELECT r.reviewID, r.published, r.score, DATE_FORMAT(r.createdDate, '%a %e %b %y') as createdDate, "
			."\n r.lastModifiedDate, c.name as categoryName, u.username as createdBy, "
			."\n rt1.title as title1, rt2.title as title2, rt3.title as title3, r.pageName "
			."\n FROM $tableName AS r"
 			."\n LEFT JOIN $catTableName AS c ON r.categoryID = c.categoryID"
 			."\n LEFT JOIN $usersTable AS u ON r.createdByID = u.id"
			."\n LEFT JOIN $reviewTitleTableName as rt1"
 			."\n ON rt1.reviewID = r.reviewID AND rt1.titleOrder = 1"
 			."\n LEFT JOIN $reviewTitleTableName as rt2"
 			."\n ON rt2.reviewID = r.reviewID AND rt2.titleOrder = 2"
 			."\n LEFT JOIN $reviewTitleTableName as rt3"
 			."\n ON rt3.reviewID = r.reviewID AND rt3.titleOrder = 3"
 			.$reviewClause 		
			."\n ORDER BY r.lastModifiedDate DESC"
			."\n $limit";
			
	return SRBridgeDatabase::Query($query); 
  }  
  
    
  function GetTopReviewList($numberOfTopReviews, $sort = 'r.score desc', $lft, $rgt)
  {
	$tableName = SRDB_Review::TableName(); 
	$catTableName = "#__simplereview_category";
	$reviewTitleTableName = "#__simplereview_review_title";
	$usersTable = "#__users";
	
	$additionalWhere ='';
	if($lft != null && $rgt != null)
	{
		$additionalWhere = "\n AND (c.lft >= $lft AND c.rgt <= $rgt)";
	}
		
	$query = "SELECT r.reviewID, r.published, r.score, "
			."\n DATE_FORMAT(r.createdDate, '"._SR_GLOBAL_DATE_FORMAT."') as createdDate, "
			."\n DATE_FORMAT(r.lastModifiedDate, '"._SR_GLOBAL_DATE_FORMAT."') as lastModifiedDate, "
			."\n r.blurb, r.awardID, r.thumbnailURL,r.createdByID, r.lastModifiedByID, r.imageURL, "
			."\n rt1.title as title1, rt2.title as title2, rt3.title as title3, r.pageName, "
			."\n c.name as categoryName, c.categoryID,"
			."\n u.username as createdBy"			
			."\n FROM $tableName as r" 
			."\n LEFT JOIN $catTableName AS c ON r.categoryID = c.categoryID AND c.published = 1"
			."\n LEFT JOIN $usersTable AS u ON r.createdByID = u.id"
			."\n LEFT JOIN $reviewTitleTableName as rt1"
			."\n ON rt1.reviewID = r.reviewID AND rt1.titleOrder = 1"
			."\n LEFT JOIN $reviewTitleTableName as rt2"
			."\n ON rt2.reviewID = r.reviewID AND rt2.titleOrder = 2"
			."\n LEFT JOIN $reviewTitleTableName as rt3"
			."\n ON rt3.reviewID = r.reviewID AND rt3.titleOrder = 3"			
			."\n WHERE r.published = 1"
			.$additionalWhere
			."\n ORDER BY "	.$sort	
			."\n LIMIT $numberOfTopReviews";	
									

	return SRBridgeDatabase::Query($query); 
  }
  
  function GetFrontEndReviewCount($lft, $rgt, $startCharFilter=null)
  {
  	$tableName = SRDB_Review::TableName(); 
	$catTableName = "#__simplereview_category";
	$reviewTitleTableName = "#__simplereview_review_title";
	
	$query = "SELECT COUNT(r.reviewID)"
			."\n FROM $tableName AS r"
			."\n LEFT JOIN $catTableName AS c ON r.categoryID = c.categoryID"
			."\n LEFT JOIN $reviewTitleTableName as rt1"
			."\n ON rt1.reviewID = r.reviewID AND rt1.titleOrder = 1"			
			."\nWHERE r.published = 1 AND c.lft BETWEEN $lft AND $rgt";		

	if($startCharFilter != null)
	{
		$query.=" AND $startCharFilter";
	}

	return SRBridgeDatabase::ScalarQuery($query); 	
  }
  
  function GetReviewsForCategory($lft, $rgt, $limitStart=null, $limitEnd=null, $orderField='title1', $orderSort='asc', $startCharFilter=null)
  {
 	$tableName = SRDB_Review::TableName(); 
	$catTableName = "#__simplereview_category";
	$reviewTitleTableName = "#__simplereview_review_title";
	$usersTable = "#__users";
	
	$whereClause = "\nWHERE r.published = 1 AND c.lft BETWEEN $lft AND $rgt";
	if($startCharFilter != null)
	{
		$whereClause.=" AND $startCharFilter";
	}
	
	
	$limit = $limitStart == null && $limitEnd == null ? "\n" : "\nLIMIT $limitStart, $limitEnd";
	
	$query = "SELECT r.reviewID, r.published, r.score, DATE_FORMAT(r.createdDate, '"._SR_GLOBAL_DATE_FORMAT."') as createdDate, c.name as categoryName, "
			."\n u.username as createdBy, rt1.title as title1, rt2.title as title2, rt3.title as title3, r.pageName"
			."\n FROM $tableName AS r"
			."\n LEFT JOIN $catTableName AS c ON r.categoryID = c.categoryID"
			."\n LEFT JOIN $usersTable AS u ON r.createdByID = u.id"
			."\n LEFT JOIN $reviewTitleTableName as rt1"
			."\n ON rt1.reviewID = r.reviewID AND rt1.titleOrder = 1"
			."\n LEFT JOIN $reviewTitleTableName as rt2"
			."\n ON rt2.reviewID = r.reviewID AND rt2.titleOrder = 2"
			."\n LEFT JOIN $reviewTitleTableName as rt3"
			."\n ON rt3.reviewID = r.reviewID AND rt3.titleOrder = 3"
			.$whereClause 
			//."\n GROUP BY r.reviewID"						
			."\n ORDER BY $orderField $orderSort"
			.$limit;			

	return SRBridgeDatabase::Query($query);  	  	
  }
  
	function SR_Publish(&$ids, $iPublish=0)
	{
	 		$tableName = SRDB_Review::TableName();
			$query = "UPDATE $tableName SET published='$iPublish' WHERE reviewID IN ($ids)";
			SRBridgeDatabase::NonResultQuery($query);	 
	}  
  
  function GetReview($reviewID)
  {  	
	$reviewTable = SRDB_Review::TableName(); 
	$reviewTitleTableName = "#__simplereview_review_title";
	$usersTable = "#__users";
	$catTable ="#__simplereview_category";

	$query = 	"SELECT r.reviewID, r.categoryID, r.awardID,r.score, r.pageName,"
  				."\n r.content, r.blurb, r.thumbnailURL, r.imageURL, r.createdByID, r.lastModifiedByID, u.name as createdBy, u1.name as lastModifiedBy,"
                ."\n DATE_FORMAT(createdDate, '"._SR_GLOBAL_DATE_FORMAT."') as createdDate,"
                ."\n DATE_FORMAT(lastModifiedDate , '"._SR_GLOBAL_DATE_FORMAT."') as lastModifiedDate,"
				."\n c.name as categoryName"
                ."\n FROM $reviewTable as r" 
				."\n LEFT JOIN $usersTable AS u ON r.createdByID = u.id"
				."\n LEFT JOIN $usersTable AS u1 ON r.lastModifiedByID = u.id"
				."\n LEFT JOIN $catTable as c on r.categoryID = c.categoryID"                
                ."\nwhere r.reviewID=$reviewID and r.published=1 and c.published=1 LIMIT 1";
	
     $review = SRBridgeDatabase::Query($query);
     if(count($review) == 1)
     {  
		$reviewTitles =& new SRDB_Review_Title($this->database);  
     	$review[0]->reviewTitles = $reviewTitles->SR_GetTitles($reviewID);
     	return $review[0];   
     }
	 else
	 {
	 	return null;  
	 }
  }

	function SR_Delete($reviewID)
	{
		$reviewTableName = $this->TableName();
		$titleTableName = SRDB_Review_Title::TableName(); 	

		$query = "\n DELETE FROM $titleTableName WHERE reviewID = $reviewID;"
				."\n DELETE FROM $reviewTableName WHERE reviewID = $reviewID;";
		
		if(_SR_GLOBAL_LOCK_TABLES == 1)
		{
			$query = " LOCK TABLES $reviewTableName write, $titleTableName write; "
					.$query
					." UNLOCK TABLES; ";
		}		
		if(!SRBridgeDatabase::BatchQuery($query))
		{
			SRError::Display("Unable delete review, possibly due to locking issue. Please grant lock permissions to database user (recommended) or turn off Lock Tables in Simple Review Configuration (Advanced tab).",true);								 
		}
	 }

}
 
 class SRDB_Review_Title extends SRBridgeDatabaseTable
 { 
  	//int primary key
  	var $reviewTitleID;
  
  	var $categoryTitleID;
  
  	//int fk
  	var $reviewID;
  	//varchar(255)
  	var $title;
  	
	//int
  	var $titleOrder;

	//text
	var $titleSetup;
  	
	function TableName()
	{
		return '#__simplereview_review_title';
	}
	
	function SRDB_Review_Title( &$db ) 
	{
		$this->InitTable( $this->TableName(), 'reviewTitleID', $db );
	} 	
  	
	function &_GetTitles($reviewID)
	{
		$tableName = SRDB_Review_Title::TableName();
		$query = "SELECT reviewTitleID, title, reviewID, categoryTitleID, titleOrder, titleSetup"
				."\n  FROM $tableName"
		 		."\n WHERE reviewID = $reviewID"
		 		."\n ORDER BY titleOrder ASC";				
        $title = SRBridgeDatabase::Query($query);
        return $title;		
	}
	
  	function &SR_GetTitles($reviewID)
  	{
  		$rawTitles =& $this->_GetTitles($reviewID);        
        $titles = array();
        foreach($rawTitles as $rawTitle)
        {        	
        	$newTitle = new SRDB_Review_Title($database);
        	$newTitle->reviewTitleID  	= $rawTitle->reviewTitleID;
        	$newTitle->categoryTitleID 	= $rawTitle->categoryTitleID;
        	$newTitle->reviewID			= $rawTitle->reviewID;
        	$newTitle->title			= Simple_Review_Common::RemoveSlashes($rawTitle->title);		
			$newTitle->titleOrder		= $rawTitle->titleOrder;        	        	
        	$titles[] = $newTitle;
        }        
        return $titles;
  	}
  	
  	function &SR_GetUnescapedTitles($reviewID)
  	{
  		return $this->_GetTitles($reviewID); 
  	}
  	
  	function SR_ChangeCategoryTitleId($reviewTitleId, $categoryTitleId)
  	{
  		if (!$reviewTitleId || !$categoryTitleId)
  		{
	  		SRError::Display("SRDB_Review_Title::SR_ChangeCategoryTitleId No title id specified.RTID:$reviewTitleId CTID:$categoryTitleId", true); 	
  		}
   		$tableName = SRDB_Review_Title::TableName();
		$query = "\n UPDATE $tableName SET categoryTitleID = $categoryTitleId WHERE reviewTitleID = $reviewTitleId ;";  

		if(_SR_GLOBAL_LOCK_TABLES == 1)
		{
			$query = "LOCK TABLES $tableName write;"
					.$query
					." UNLOCK TABLES;";
		}
				
		if(!SRBridgeDatabase::BatchQuery($query))
		{
			SRError::Display("Unable to continue, possibly due to locking issue. Please grant lock permissions to database user (recommended) or turn off Lock Tables in Simple Review Configuration (Advanced tab).",true);		
		}
  	}
  	
  	function SR_RemoveTitles($titleIdArray)
  	{
  		if(!$titleIdArray || count($titleIdArray) ==0)
  		{
  			SRError::Display('No title ids specified.', true);  			
  		}
  		$reviewTitlesToRemove = implode(",", $titleIdArray);		
  		$tableName = SRDB_Review_Title::TableName();
		$query = "\n DELETE FROM $tableName WHERE reviewTitleID in ($reviewTitlesToRemove);";				  

		if(_SR_GLOBAL_LOCK_TABLES == 1)
		{
			$query = "LOCK TABLES $tableName write;"	
					.$query
					."UNLOCK TABLES;";
		}
				
  		if(!SRBridgeDatabase::BatchQuery($query))
		{
			SRError::Display("Unable to continue, possibly due to locking issue. Please grant lock permissions to database user (recommended) or turn off Lock Tables in Simple Review Configuration (Advanced tab).",true);		
		}  		
  	}
  
  }
  
class SRDB_Review_Title_Text extends SRDB_Review_Title
{
	
}

class SRDB_Review_Title_Rating extends SRDB_Review_Title
{
	function store($updateNulls=false )
	{		
		$ndp = defined('Review_Module_RATING_DECIMAL_PLACES') ? intval(Review_Module_RATING_DECIMAL_PLACES) : 1;		
		$this->title = round($this->title, $ndp);
		return parent::store($updateNulls);  	
	}		
}

class SRDB_Review_Title_Link extends SRDB_Review_Title
{
	function store($updateNulls=false )
	{				
		if(!preg_match('@(https?://([-\w\.]+)+(:\d+)?(/([\w/_\.]*(\?\S+)?)?)?)@', $this->title))
		{
			$this->title = '';
		}
		return parent::store($updateNulls);  	
	}	
}  
  

?>