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

class AdminRoutesHelper extends MyHelper
{	
	var $helpers = array('html');
	
	var $routes = array(
		'user10'=>'index2.php?option=com_users&task=editA&hidemainmenu=1&id=%s',	
        'user15'=>'index.php?option=com_users&view=user&task=edit&cid[]=%s',    
	);

	function user($title,$user_id,$attributes) {
		
		switch(getCmsVersion()) {
			case CMS_JOOMLA10: 
			case CMS_MAMBO46:
				$route = $this->routes['user10'];
				$url = sprintf($route,$user_id); 
			break;			
			case CMS_JOOMLA15:
				$route = $this->routes['user15'];			
				$url = sprintf($route,$user_id); 				
			break;						
		}

		return $this->Html->sefLink($title,$url,$attributes);
	}
}