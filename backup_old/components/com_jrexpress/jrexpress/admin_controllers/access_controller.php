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

defined( 'MVC_FRAMEWORK') or die( 'Direct Access to this location is not allowed.' );

class AccessController extends MyController {
	
	var $uses = array('acl');
	
	var $helpers = array('html','form');
	
	var $components = array('config');	
		
	var $autoRender = true;
	
	var $autoLayout = true;
		
	function beforeFilter() 
	{	
		# Call beforeFilter of MyAdminController parent class
		parent::beforeFilter();
		
	}
		
	function index() {
		
		$this->name = 'access';
	
		$accessGroups = $this->Acl->getAccessGroupList();

		$this->set(
			array(
				'stats'=>$this->stats,
				'version'=>$this->Config->version,
				'Config'=>$this->Config,
				'accessGroups'=>$accessGroups,
				
			)
		);
	}
	
}