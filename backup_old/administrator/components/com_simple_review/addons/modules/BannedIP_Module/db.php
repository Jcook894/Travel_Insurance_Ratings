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

class SRDB_Banned_IP extends SRBridgeDatabaseTable 
{
  	var $bannedIP=null;
  
  	function SR_Delete($ip)
	{
		$bannedIPTableName = $this->TableName();	
		$query = "DELETE FROM $bannedIPTableName WHERE bannedIP = '$ip'";
		SRBridgeDatabase::Query($query);
	}  

	function TableName()
	{
		return '#__simplereview_banned_ips';
	}  
  
	function SRDB_Banned_IP( &$db ) 
	{
		$this->InitTable($this->TableName(), 'bannedIP', $db );
	}
}

?>