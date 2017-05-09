<?php
/**
 * JReviews - Reviews Extension
 * Copyright (C) 2010-2012 ClickFWD LLC
 * This is not free software, do not distribute it.
 * For licencing information visit http://www.reviewsforjoomla.com
 * or contact sales@reviewsforjoomla.com
**/

defined( 'MVC_FRAMEWORK') or die( 'Direct Access to this location is not allowed.' );

class AclModel extends MyModel  {

    function getAccessGroupList($groups = null) 
    {        
        /* Groupids reference */
    //    18 - Registered
    //    19 - Author
    //    20 - Editor
    //    21 - Published
    //    23 - Manager
    //    24 - Administrator
    //    25 - Super Administrator
            
        $whereGroups = $groups ? "\n AND id IN ($groups)" : "";

        switch(getCmsVersion()) 
        {
            case CMS_JOOMLA16:
                $query = "
                    SELECT 
                        id AS value, title AS text
                    FROM 
                        #__usergroups"
                    . $whereGroups
                    ;
            break;
            case CMS_JOOMLA15:
                $excludedGroups = array("'ROOT'","'USERS'","'Public Frontend'","'Public Backend'");
                $excludedGroups = implode(",",$excludedGroups);
                $query = "
                    SELECT 
                        id AS value, name AS text
                    FROM 
                        #__core_acl_aro_groups
                    WHERE 
                        name NOT IN ($excludedGroups)"
                    .$whereGroups
                ;
            break;        
        }

        $this->_db->setQuery($query);     
        $results = $this->_db->loadAssocList();
        return $results;    
    }
}