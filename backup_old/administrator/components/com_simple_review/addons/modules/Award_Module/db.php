<?php
/**
 *  $Id: db.php 68 2009-04-19 06:56:19Z rowan $
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
class SRDB_Award extends SRBridgeDatabaseTable 
{
	// INT AUTO_INCREMENT
	var $awardID=null;
	
	// varchar 30
	var $name=null;
	
	//varchar 255
	var $imageURL=null;

	function TableName()
	{
	return '#__simplereview_awards';
	}
	
	function SRDB_Award( &$db ) 
	{
	$this->InitTable($this->TableName(), 'awardID', $db );
	}
	
	function load( $oid=null )
	{
		parent::load($oid);
	if($oid != null)
	{
		$this->EscapeTextFields($this);
	}	
	}
  
	function EscapeTextFields(&$award)
	{
		$award->name = Simple_Review_Common::RemoveSlashes($award->name);
		$award->imageURL = Simple_Review_Common::RemoveSlashes($award->imageURL);		
	}
  
	function &GetAwards($order = 'awardID asc' ,$limitStart = null, $limitEnd = null)
	{
		$awardTable = SRDB_Award::TableName();
		$query = 	 "SELECT awardID, name, imageURL"
					."\nFROM $awardTable"
					."\nORDER BY $order";
					
		if($limitStart != null && $limitEnd != null)	
		{	
			$limitStart = intval($limitStart);
			$limitEnd = intval($limitEnd);	
			$query." LIMIT $limitStart, $limitEnd";
		}
		$rows = SRBridgeDatabase::Query($query);
		
		$awards = array();
		
		if($rows != false)
		{
			foreach($rows as $row)
			{
				SRDB_Award::EscapeTextFields($row);
				$awards[] = $row;
			}
		}
		
		return $awards;					
	}  
}
?>