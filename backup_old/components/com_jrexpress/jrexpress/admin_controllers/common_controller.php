<?php
/*
    JReviews Express - user reviews for Joomla
    Copyright (C) 2009  Alejandro Schmeichler

    JReviews Express is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    JReviews Express is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/

// no direct access
defined( 'MVC_FRAMEWORK') or die( 'Direct Access to this location is not allowed.' );

class CommonController extends MyController {
				
	function toggleIcon($params) {
	
		$xajax = new xajaxResponse();
		
		$cid = array_shift($params);
		$tagname = array_shift($params);
		$field = array_shift($params);
		$table = array_shift($params);
		$key = array_shift($params);
		
		$this->_db->setQuery( "SELECT $field FROM `$table` WHERE $key = '$cid'"	);

		$state = $this->_db->loadResult();
		
		$state = ($state ? '0' : '1');
	
		$img = ($state) ? "images/tick.png" : "images/publish_x.png";
	
		$this->_db->setQuery( "UPDATE `$table` SET `$field` = '$state' WHERE $key = '$cid'" );
	
		if (!$this->_db->query()) {

			$xajax->alert($this->controller->_db->getErrorMsg());
			
			return $xajax;
		
		}
	
		$xajax->call("new Effect.Highlight","fields".$cid, array("duration" => "2"));
		
		$xajax->assign($tagname.$cid, "src", $img );
	
		// Clear cache
		clearCache('', 'views');
		clearCache('', '__data');
				
		return $xajax;
	}
	
	function toggleState($params) {

		$xajax = new xajaxResponse();		

		$cid = array_shift($params);
		$table = array_shift($params);
		$key = array_shift($params);		
		$field = array_shift($params);
	
		$this->_db->setQuery( "SELECT $field FROM `$table` WHERE $key = '$cid'"	);
		$state = $this->_db->loadResult();
		$state = ($state ? '0' : '1');
	
		switch ($state) {
			case 1:
				$img = '<img src="'.WWW_ROOT.'administrator/images/publish_g.png" border="0"/>';
			break;
			case 0:
				$img = '<img src="'.WWW_ROOT.'administrator/images/publish_x.png" border="0"/>';
			break;
			default: break;
		}
	
		$this->_db->setQuery( "UPDATE `$table` SET `$field` = '$state' WHERE $key = '$cid'");
	
		if (!$this->_db->query()) {
			$xajax->alert($this->_db->getErrorMsg());
			return $xajax;
		}
		
		$xajax->assign("pubImg_".$cid, "innerHTML", $img );		
				
		// Clear cache
		clearCache('', 'views');
		clearCache('', '__data');
		
		return $xajax;
	}	
	
	function _clearCache() {
		
		if($this->xajaxRequest) {
			$xajax = new xajaxResponse();
		}
		
		clearCache('', 'views');
		clearCache('', '__data');		
		clearCache('', 'assets');		
		
		if($this->xajaxRequest) {
			$xajax->alert(__a("The cache has been cleared.",true));
			return $xajax;
		}
	}
    
    function _clearFileRegistry() {
        
        if($this->xajaxRequest) {
            $xajax = new xajaxResponse();
        }
        
        clearCache('', 'core');
        
        if($this->xajaxRequest) {
            $xajax->alert(__a("The file registry has been cleared.",true));
            return $xajax;
        }
    }    
	
}
?>