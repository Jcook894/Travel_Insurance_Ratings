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

class AclModel extends MyModel  {

	function getAccessGroupList($groups = null) 
	{		
		/* Groupids reference */
	//	18 - Registered
	//	19 - Author
	//	20 - Editor
	//	21 - Published
	//	23 - Manager
	//	24 - Administrator
	//	25 - Super Administrator
			
		$whereGroups = $groups ? "\n AND $group_id_column IN ($groups)" : "";
		
		$excludedGroups = array("'ROOT'","'USERS'","'Public Frontend'","'Public Backend'");
		
		$excludedGroups = implode(",",$excludedGroups);

		switch(getCmsVersion()) 
		{
			case CMS_JOOMLA10:
			case CMS_MAMBO46:				
											   
				$group_id_column = 'group_id';			

			break;
			
			case CMS_JOOMLA15:

				$group_id_column = 'id';

			break;		
		}
				
		$query = "SELECT $group_id_column AS value, name AS text"
		. "\n FROM #__core_acl_aro_groups"
		. "\n WHERE name NOT IN ($excludedGroups)"
		. $whereGroups;

		$this->_db->setQuery($query);
		
		$results = $this->_db->loadAssocList();
				
		return $results;	
	}

}