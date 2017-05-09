<?php
/**
 *  $Id: Horizontal_TopN.php 50 2009-03-22 05:12:19Z rowan $
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
class Horizontal_TopN extends TopN_Display_Base
{
	function Horizontal_TopN(&$addonManager, &$pluginName, $initialise)
	{	
	  $this->addonPath = dirname(__FILE__)."/$pluginName";	  
	  parent::TopN_Display_Base($addonManager, $pluginName, $initialise);				
	}
	
	function _DisplayHeader()
	{
		if($this->topReviews == null)
		{
		  		return;  
		}		
		$this->templates[$this->addonName]->_DisplayHeader();
	}
	
	function _DisplayFooter()
	{ 
		$this->templates[$this->addonName]->_DisplayFooter();
	}

}
?>



       
