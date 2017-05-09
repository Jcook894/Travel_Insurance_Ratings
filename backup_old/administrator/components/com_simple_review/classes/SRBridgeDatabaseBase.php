<?php
/**
 *  $Id: SRBridgeDatabaseBase.php 66 2009-04-10 03:45:58Z rowan $
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

/**
 * Class to perform Database actions.
 */
class SRBridgeDatabaseBase
{
	/**
	 * CMS specific implementation to perform the batch query.
	 * @return mixed A database resource if successful, FALSE if not.
	 * @param object $database the database object.
	 * @param string $query The SQL query to execute.
	 * @param boolean $useTransaction[optional] True to wrap the batch in a transaction.
	 */
	function _QueryBatch(&$database, $query, $useTransaction=false)
	{
		trigger_error('This class function has not been implemented!',E_USER_ERROR);
	}
	
	/**
	 * Performs a collection of SQL queries.
	 * @return mixed A database resource if successful, FALSE if not.
	 * @param string $query The SQL query to execute.
	 * @param boolean $useTransaction[optional] True to wrap the batch in a transaction.
	 */
	function BatchQuery($query, $useTransaction=false)
    {
    	$bridge =& SRBridgeManager::Get();
		$database =& $bridge->Database;

		if ($database->getErrorNum()) 
		{
		    if($bridge->InDebugMode)
		    {
				echo $database->getErrorNum();
			}
			else
			{
				echo "<b>An error has occured. ERQD1</b>";  
			}
			return false;
		}
	
        $database->setQuery( $query );
        $success = SRBridgeDatabase::_QueryBatch($database, $query, $useTransaction);
        if (!$success || $database->getErrorNum())
		{
		  	if($bridge->InDebugMode)
		  	{
		    	echo $database->stderr();
		    }
		    else
		    {
		        echo "<b>An error has occured. ERQD2</b>"; 
		    }
		    return false;
		}
		return true;						  
	}	
	
	/**
	 * Performs a query which does not return a result.
	 * @param string $query The SQL query to execute.
	 */
    function NonResultQuery($query)
    {
		$bridge =& SRBridgeManager::Get();
		$database =& $bridge->Database;
		
		if ($database->getErrorNum()) 
		{
		    if($bridge->InDebugMode)
		    {
				echo $database->getErrorNum();
			}
			else
			{
				SRError::Display( "An error has occured. ERSQD1", false);  
			}
			return false;
		}
	
        $database->setQuery( $query );
        if (!$database->query() || $database->getErrorNum())
		{
		  	if($bridge->InDebugMode)
		  	{
		    	echo $database->stderr();
		    }
		    else
		    {
		        SRError::Display("An error has occured. ERSQD2", false); 
		    }
		    return false;
		}
			
		return true;			  
	} 

	/**
	 * Queries the Database.
	 * @return The result of the query. Array of objects.
	 * @param string $query The SQL query to execute.
	 */
	function Query($query)
    {
		$bridge =& SRBridgeManager::Get();
		$database =& $bridge->Database;
						
		if ($database->getErrorNum()) 
		{
		    if($bridge->InDebugMode)
		    {
				$error = $database->getErrorNum();
				echo $error;
			}
			else
			{
				echo "<b>An error has occured. ERQD1</b>";  
			}
			return false;
		}
	
        $database->setQuery( $query );
        $rows = $database->loadObjectList();
        if ($database->getErrorNum())
		{
		  	if($bridge->InDebugMode)
		  	{
		    	echo $database->stderr();
		    }
		    else
		    {
		        echo "<b>An error has occured. ERQD2</b>"; 
		    }
		    return false;
		}
		
		return $rows;	
	}

	/**
	 * Returns the first field of the first row returned by the query
	 * @return The value returned in the query or null if the query failed.
	 * @param string $query The SQL query to execute.
	 */
    function ScalarQuery($query)
    {
		$bridge =& SRBridgeManager::Get();
		$database =& $bridge->Database;
		
		if ($database->getErrorNum()) 
		{
		    if($bridge->InDebugMode)
		    {
				echo $database->getErrorNum();
			}
			else
			{
				SRError::Display( "An error has occured. ERSQD1", false);  
			}
			return false;
		}
	
        $database->setQuery( $query );
        $rows = $database->loadResult();
        if ($database->getErrorNum())
		{
		  	if($bridge->InDebugMode)
		  	{
		    	echo $database->stderr();
		    }
		    else
		    {
		        SRError::Display("An error has occured. ERSQD2", false); 
		    }
		    return false;
		}
			
		return $rows;			  
	}

}
?>