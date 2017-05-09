<?php
/**
 *  $Id: db.php 67 2009-04-10 05:16:29Z rowan $
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

class SRDB_Template extends SRBridgeDatabaseTable 
{
  // INT AUTO_INCREMENT
  var $templateID=null;

  // varchar 30
  var $name=null;

  //text
  var $template=null;

  function TableName()
  {
  	return "#__simplereview_template";	
  }	

	function SR_Delete($templateID)
	{
		$templateTableName = $this->TableName();	
		$query = "DELETE FROM $templateTableName WHERE templateID = $templateID";
		SRBridgeDatabase::Query($query);	
	} 

  function SRDB_Template( &$db )
  {
	$this->InitTable($this->TableName(), 'templateID', $db );
  }
  
  function load( $oid=null )
  {
  	parent::load($oid);
	if($oid != null)
	{
		$this->EscapeTextFields($this);
	}	
  }
  
  function EscapeTextFields(&$template)
  {
		$template->name = Simple_Review_Common::RemoveSlashes($template->name);
		$template->template = Simple_Review_Common::RemoveSlashes($template->template);
  }  
}

?>