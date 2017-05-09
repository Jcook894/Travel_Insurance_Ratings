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

/*
* Uses nested sets, see http://dev.mysql.com/tech-resources/articles/hierarchical-data.html
*/

class SRDB_Category extends SRBridgeDatabaseTable {
  // INT AUTO_INCREMENT
	  var $categoryID=null;
	  
	  //varchar 255
	  var $pageName = null;	  
	  
	  // varchar 30
	  var $name=null;
	    
	  //int
	  var $catOrder = null;
	  
	  // TINYINT
	  var $published=null;
	  
	  //varchar 255
	  var $description = null;
	  	
	  //varchar 255
	  var $categoryImageURL=null;
	  
	  //int fk
	  var $templateID = null;
	  
	  //tiny int
	  var $userReviews = null;
	  
	  //int, left and right for nested sets
	  var $lft = null;
	  var $rgt = null;
	  
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
			if($this->pageName != null)
			{
				//$this->pageName = preg_replace('/[^\w\.\-_]/u', '', $this->pageName);
				$this->pageName = rawurlencode($this->pageName);
			}
			
			return parent::store($updateNulls);  	
		}	  
	  
	  function EscapeTextFields(&$category)
	  {
			$category->name = Simple_Review_Common::RemoveSlashes($category->name);
			$category->description = Simple_Review_Common::RemoveSlashes($category->description);
			$category->categoryImageURL = Simple_Review_Common::RemoveSlashes($category->categoryImageURL);
	  }  	  
	  
	  function TableName()
	  {
	    return '#__simplereview_category';
	  }
	  
	  function SRDB_Category( &$db ) 
	  {
	  		$this->InitTable($this->TableName(), 'categoryID', $db );
	  }
	  
	  function SR_GetPublishedCategory($catId)
	  {
		  	$tableName = SRDB_Category::TableName();
			$query = "SELECT categoryID, pageName, name, lft, rgt from $tableName WHERE categoryID = $catId and published=1";
			$rows = SRBridgeDatabase::Query($query);	  
			if(count($rows) != 1)
			{
				return null;
			}		
			
			return $rows[0];		
	  }
	  
	  function SR_GetRoot()
	  {
	  		$tableName = SRDB_Category::TableName();
			$query = "SELECT categoryID, lft, rgt from $tableName WHERE name = '"._SR_ADDON_CATEGORY_ROOT."' and published=1";
					  			  				  
			$rows = SRBridgeDatabase::Query($query);		
			
			if(count($rows) == null)
			{
			 	return null;
			}
			
			return $rows[0];				
	  }
	  
	  //values must be populated first
	  function SR_Add($parentCategoryID = null)
	  {
	   		if($parentCategoryID == null)
	   		{
	   			//throw error
				return;    
	   		}
	   		
	   		//lock table for write
			$tableName = $this->TableName();

			if(_SR_GLOBAL_LOCK_TABLES == 1)
			{
				$query = "LOCK TABLES $tableName WRITE;";
				//try lock					
				if(!SRBridgeDatabase::NonResultQuery($query))
				{			 
					SRError::Display("Unable to lock database. Please grant lock permissions to database user (recommended) or turn off Lock Tables in Simple Review Configuration (Advanced tab).",true); 
					return false; 
				}							
			}
							
			$query = "SELECT @myLeft := lft FROM $tableName"
					."\nWHERE categoryID = $parentCategoryID;"
					."\nUPDATE $tableName SET rgt = rgt + 2 WHERE rgt > @myLeft;"
					."\nUPDATE $tableName SET lft = lft + 2 WHERE lft > @myLeft;";	
			 
			//attempt to insert 
			$query  .= "INSERT INTO $tableName "
						."(templateID, 		catOrder, pageName, 			name, 			description, "
						." published, 		categoryImageURL, "
						." userReviews, 		lft, 		rgt)" 
						."\nVALUES "
						."('$this->templateID', 	'$this->catOrder', '$this->pageName', 	'$this->name', '$this->description', "
						." '$this->published', '$this->categoryImageURL', " 
						." '$this->userReviews', @myLeft + 1, @myLeft + 2);";
			//unlock if failed						
			if(!SRBridgeDatabase::BatchQuery($query, false))
			{
				if(_SR_GLOBAL_LOCK_TABLES == 1)
				{
					$query = "UNLOCK TABLES;";			
					SRBridgeDatabase::NonResultQuery($query);
				}			 
				return false; 
			}										 
			
			$this->categoryID = $this->_db->insertid();
			
			if(_SR_GLOBAL_LOCK_TABLES == 1)
			{
				$query = "UNLOCK TABLES;";
				if(!SRBridgeDatabase::NonResultQuery($query))
				{
				 	return false;
				}
			}
			return ($this->categoryID > 0);
	  }
	
	function SR_Update($parentCategoryID = null)
	{
	 
	   		if($parentCategoryID == null)
	   		{
	   			//throw error
				return;    
	   		}
	 
	 	 	$tableName = $this->TableName();
	 		$query = "SELECT lft, rgt FROM $tableName WHERE categoryID = $this->categoryID";
	 		$rowBeforeUpdate = SRBridgeDatabase::Query($query);
			if(count($rowBeforeUpdate) != 1)
			{
			  	SRError::Display("Error getting category with ID:$this->categoryID", true);
			}
			$rowBeforeUpdate=$rowBeforeUpdate[0];
	 		
	 		$oldParent = $this->SR_NodesParent($rowBeforeUpdate->lft, $rowBeforeUpdate->rgt);
	 		
	 		//categories parent has changed so need to update tree
	 		if($oldParent->categoryID != $parentCategoryID)
	 		{
	 		 	$query = "SELECT lft, rgt FROM $tableName WHERE categoryID = $parentCategoryID";
	 			$newParent = SRBridgeDatabase::Query($query);
	 			$newParent = $newParent[0];
	 		 	//move it to the ast child of the new parent category
				$this->SR_MoveSubtree ($rowBeforeUpdate->lft, $rowBeforeUpdate->rgt, $newParent->rgt);
				return true;		 	
	 		}
	 		else
	 		{
	 			$this->lft  = 	$rowBeforeUpdate->lft;
	 			$this->rgt =	$rowBeforeUpdate->rgt;
	 			
				return $this->store(true); 			
			}
			
	}

	/*
	*Delete the category and bubble up the sub categories
	*/
	function SR_Delete($categoryID)
	{
		global $database;
		$SR_Addon_Manager =& Addon_Manager::Get();
		Addon_Manager::Get();
		$reviewModule = $SR_Addon_Manager->GetModule('Review_Module', false); 
		$tableName = SRDB_Category::TableName();
		$reviewTable = SRDB_Review::TableName();
		$query = "SELECT @myLeft := lft, @myRight := rgt, @myWidth := rgt - lft + 1"
				 ."\nFROM $tableName "
				 ."\nWHERE categoryID = $categoryID;"
				 ."DELETE FROM $tableName  WHERE lft = @myLeft;"						
				 ."UPDATE $tableName  SET rgt = rgt - 1, lft = lft - 1 WHERE lft BETWEEN @myLeft AND @myRight;"
				 ."UPDATE $tableName  SET rgt = rgt - 2 WHERE rgt > @myRight;"
				 ."UPDATE $tableName  SET lft = lft - 2 WHERE lft > @myRight;";			
				 
				 
		if(_SR_GLOBAL_LOCK_TABLES == 1)
		{
			$query = "LOCK TABLES $tableName  WRITE;"
					.$query
					."UNLOCK TABLES;";
		}
				 
  		if(!SRBridgeDatabase::BatchQuery($query))
		{
			SRError::Display("Unable to continue, possibly due to locking issue. Please grant lock permissions to database user (recommended) or turn off Lock Tables in Simple Review Configuration (Advanced tab).",true);		
		}  			

		$catTitleName = SRDB_Category_Title::TableName();
		$query = "DELETE  FROM $catTitleName where categoryID=$categoryID";
		SRBridgeDatabase::NonResultQuery($query);
		
		$query = "SELECT reviewID FROM $reviewTable where categoryID=$categoryID";
		$reviews = SRBridgeDatabase::Query($query);
		if($reviews != null && count($reviews) > 0)
		{
			$reviewDB = new SRDB_Review( $database); 
			foreach($reviews as $review)
			{					
				$reviewDB->SR_Delete($review->reviewID);		
			}
		}
	 }
	
	function SR_FullTree()
	{
	 		$tableName = $this->TableName();
			$query =  "SELECT node.categoryID as categoryID, node.pageName as pageName, node.name as name, "
					 ."\nnode.lft as lft, COUNT( parent.name ) AS depth"
					 ."\nFROM $tableName AS node, $tableName AS parent"
					 ."\nWHERE node.lft"
					 ."\nBETWEEN parent.lft"
					 ."\nAND parent.rgt"
					 ."\nGROUP BY node.name"
					 ."\nORDER BY node.lft";
			
            return SRBridgeDatabase::Query($query);	 
	}
	
	function SR_FrontEndFullTree($iLftBounds, $iRightBounds)
	{
	 	$tableName = $this->TableName();
	 	$reviewTable = "#__simplereview_review";//TODO:get this from review module
	 		 	
	 	$categoryTitleTableName = "#__simplereview_category_title";
		$query = "SELECT parent.categoryID, parent.categoryImageURL, parent.pageName, parent.name, parent.description, COUNT( review.reviewID ) AS reviewCount, parent.lft, parent.rgt, parent.catOrder "
						."\n FROM $tableName AS node"
						."\n LEFT JOIN $tableName AS parent on parent.categoryID is not null or parent.categoryID != node.categoryID" 			
						."\n LEFT JOIN $reviewTable AS review ON node.categoryID = review.categoryID AND review.published = 1"							
						."\n WHERE" 
						."\n parent.lft > $iLftBounds AND parent.rgt < $iRightBounds"
						."\n AND node.published = 1"
						."\n AND node.lft BETWEEN parent.lft AND parent.rgt"
						."\n GROUP BY parent.categoryID"
						."\n ORDER BY parent.rgt DESC";										

		return SRBridgeDatabase::Query($query);	 	
	 
	}
	
	function SR_Path($catID)
	{
	 	$tableName = $this->TableName();
		$query = "SELECT parent.name as name, parent.categoryID as categoryID"
				."\nFROM $tableName AS node,"
				."\n$tableName AS parent"
				."\nWHERE node.lft BETWEEN parent.lft AND parent.rgt"
				."\nAND node.categoryID = $catID and parent.lft > 1"
				."\nORDER BY parent.lft; ";
		return SRBridgeDatabase::Query($query);		
	 
	}
	
	function SR_NodesParent($iLft, $iRgt)
	{
	 	$tableName = $this->TableName();
		$query = "SELECT categoryID, pageName, name FROM $tableName AS parent "
				."\nWHERE $iLft > parent.lft AND $iRgt < parent.rgt "
				."\nORDER BY lft DESC LIMIT 1";
		 $parentCategory = SRBridgeDatabase::Query($query);
		 if(count($parentCategory) != 1)
		 {
		  	SRError::Display("Error getting parent category for category with left:$iLft right:$iRgt", false);
		 }
		 return $parentCategory[0];
	}
	
	function SR_Count($bOnlyIncludePublished=false)
	{
	 	$tableName = $this->TableName();
		$query = "SELECT count(*) as count FROM $tableName WHERE lft > 1" ; 
		if($bOnlyIncludePublished)
		{
			$query .= "\nAND published=1"; 
		}
		return intval(SRBridgeDatabase::ScalarQuery($query));
	}
	
	function SR_Listing($iLimitLower=null, $iLimitUpper=null)
	{
	 	$tableName = $this->TableName();
	 	//ignore root cat
	 	$query = "SELECT * FROM $tableName WHERE lft > 1 ORDER BY catOrder, categoryID  ";
	 	if($iLimitLower != null)
	 	{
		 		$query .="\nLIMIT $iLimitLower";
		 		if($iLimitUpper != null)
		 		{
				  	$query .=", $iLimitUpper";				  
				}						 		 
		}		 
		return SRBridgeDatabase::Query($query);	 
	}
	
	/**
	* Publishes or Unpublishes one or more records
	* @param array An array of unique category id numbers
	* @param integer 0 if unpublishing, 1 if publishing
	*/	
	function SR_Publish(&$ids, $iPublish=0)
	{
	 		$tableName = $this->TableName();
			$query = "UPDATE $tableName SET published='$iPublish' WHERE categoryID IN ($ids)";
			SRBridgeDatabase::NonResultQuery($query);	 
	}
	
	function SR_GetSubCatsAndReviewsToUnpublish(&$ids, &$categoryIDs, &$reviewIDs)
	{
			$SR_Addon_Manager =& Addon_Manager::Get();
			$reviewModule = $SR_Addon_Manager->GetModule('Review_Module', false);
			$tableName = $this->TableName();			
			$reviewTable = $reviewModule->TableName();
			$query = 	"SELECT c.categoryID, r.reviewID" 
					."\n FROM $tableName as p, $tableName as c" 
					."\n LEFT JOIN $reviewTable as r on r.categoryID = c.categoryID"
					."\n WHERE c.lft BETWEEN p.lft AND p.rgt and p.categoryID IN ($ids)"
					."\n GROUP BY c.categoryID, r.reviewID";
			$rows = SRBridgeDatabase::Query($query);
			$categoryIDs = array();
			$reviewIDs = array();
									
			foreach($rows as $row)
			{
				if($categoryIDs[$row->categoryID] == null)
				{
					$categoryIDs[$row->categoryID] = 	$row->categoryID;
				}
				if($row->reviewID != null)
				{
					$reviewIDs[$row->reviewID] = $row->reviewID;
				}				
			}	
	}
	
	function SR_GetCategoryTemplate($categoryID)
	{
   		$catTableName = $this->TableName();
   		$templateTableName = '#__simplereview_template';
   		$query = "SELECT template"
   				."\nFROM $templateTableName AS t"
   				."\nLEFT JOIN $catTableName AS c ON t.templateID = c.templateID"
   				."\nWHERE c.categoryID = $categoryID"
				."\nLIMIT 1";
   		return SRBridgeDatabase::ScalarQuery($query);	 
	}	
			
	function SR_MoveSubtree ($iOriginalLeft, $iOriginalRight, $iNewParentLeft)
	{
	 //todo, what if move parent to be a node in its subtree?
	  
	  //get the size of the subtree
	  $subSize = $iOriginalRight - $iOriginalLeft+1;
	  $this->_shiftRLValues($iNewParentLeft, $subSize);
	  
	  if($iOriginalLeft >= $iNewParentLeft){ // src was shifted too?
		$iOriginalLeft += $subSize;
	    $iOriginalRight += $subSize;
	  }
	  /* now there's enough room next to target to move the subtree*/
	  $this->_shiftRLRange($iOriginalLeft, $iOriginalRight, $iNewParentLeft-$iOriginalLeft);
	  /* correct values after source */
	  $this->_shiftRLValues($iOriginalRight, - $subSize);

	}
	
	function _shiftRLValues ($first, $delta)
	{
	 	$tableName = $this->TableName();
	 
		$query = "\nUPDATE $tableName SET lft=lft + $delta WHERE lft >= $first;"
		  		."\nUPDATE $tableName SET rgt=rgt + $delta WHERE rgt >= $first;";
		  		

	  	if(_SR_GLOBAL_LOCK_TABLES == 1)
		{
			$query ="LOCK TABLES $tableName  WRITE;"  
					.$query
					."UNLOCK TABLES;";
		}
	  		
  		if(!SRBridgeDatabase::BatchQuery($query))
		{
			SRError::Display("Unable to continue, possibly due to locking issue. Please grant lock permissions to database user (recommended) or turn off Lock Tables in Simple Review Configuration (Advanced tab).",true);		
		}  
	}
	function _shiftRLRange ($first, $last, $delta)
	{
	   	$tableName = $this->TableName();
	   	$query = "\nUPDATE $tableName SET lft=lft + $delta WHERE lft >= $first AND lft <= $last;"
	  			."\nUPDATE $tableName SET rgt=rgt + $delta WHERE rgt >= $first AND rgt <=$last;";
	  			

	  	if(_SR_GLOBAL_LOCK_TABLES == 1)
	  	{	  			
			$query = "LOCK TABLES $tableName  WRITE;" 
					.$query
					."UNLOCK TABLES;";
		}
  		if(!SRBridgeDatabase::BatchQuery($query))
		{
			SRError::Display("Unable to continue, possibly due to locking issue. Please grant lock permissions to database user (recommended) or turn off Lock Tables in Simple Review Configuration (Advanced tab).",true);		
		}  			
	}		
}

class SRDB_Category_Title extends SRBridgeDatabaseTable {
	// INT AUTO_INCREMENT
	var $categoryTitleID;
	//INT FK
	var $categoryID;
	//varchar 255
	var $titleName;
	//tiny unsigned int
	var $titleOrder;
	
	//enum, text, link, rating, list, option
	var $titleType;
	
	//text
	var $titleSetup;
	
	//tinyint(1)
	var $mandatory;
	
	function TableName()
	{
		return '#__simplereview_category_title';
	}	
	
	function SRDB_Category_Title( &$db ) 
	{
		$this->InitTable( $this->TableName(), 'categoryTitleID', $db );
	} 
	
	function SR_LoadCategoryTitles($categoryID)
	{
   		$tableName = $this->TableName();
   		$query = "SELECT categoryTitleID, categoryID, titleName, titleOrder, titleType, titleSetup, mandatory"
   				."\nFROM $tableName"
   				."\nWHERE categoryID = $categoryID"
   				."\nORDER BY titleOrder";
   		return SRBridgeDatabase::Query($query);
	} 
}

//Maps titleType enum in SRDB_Category_Title 
/*
class SRDB_TitleType
{
	function Title(){return "Title";}
	function Rating(){return "Rating";}
	function Url(){return "Url";}
}
*/

?>