<?php
/**
 * JReviews - Reviews Extension
 * Copyright (C) 2010-2015 ClickFWD LLC
 * This is not free software, do not distribute it.
 * For licencing information visit https://www.jreviews.com
 * or contact sales@jreviews.com
**/

defined( 'MVC_FRAMEWORK') or die;

class AclModel extends MyModel  {

    function getAccessGroupList()
    {
        return cmsFramework::getAccessGroupsList();
    }

	function getAccessLevelList()
	{
        return cmsFramework::getAccessLevelList();
	}
}