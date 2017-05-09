<?php
/**
 *  $Id: Award_Module.php 90 2009-06-13 06:13:29Z rowan $
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

// ensure this file is being included by a parent file
defined('_VALID_MOS')||defined( '_JEXEC' ) or die( 'Direct Access to this location is not allowed.' );
require_once(dirname(__FILE__ )."/db.php");
class Award_Module extends Module_Base
{
	
	function Award_Module(&$addonManager, &$moduleName, $initialise)
	{
		$this->addonPath = dirname(__FILE__);
		
	  	$this->hasCSS=false;
	  	$this->hasLanguage=true;
	  	$this->hasConfig=false;
		
		$this->_AddonManager =& $addonManager;
		
		parent::Module_Base($addonManager, $moduleName, $initialise);
		
	  	$this->friendlyName = $this->GetString($this, 'Awards');  	
	  	$this->Initialised = $initialise;
	}
	
	function &GetAwards($order = 'awardID asc' ,$limitStart = null, $limitEnd = null)
	{
		return SRDB_Award::GetAwards($order, $limitStart, $limitEnd);
	}	
}
?>