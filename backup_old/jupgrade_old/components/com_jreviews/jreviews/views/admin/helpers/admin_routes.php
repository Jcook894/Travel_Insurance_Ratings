<?php
/**
 * JReviews - Reviews Extension
 * Copyright (C) 2010-2012 ClickFWD LLC
 * This is not free software, do not distribute it.
 * For licencing information visit http://www.reviewsforjoomla.com
 * or contact sales@reviewsforjoomla.com
**/

defined( 'MVC_FRAMEWORK') or die( 'Direct Access to this location is not allowed.' );

class AdminRoutesHelper extends MyHelper
{	
	var $helpers = array('html');
	
	var $routes = array(
        'user15'=>'index.php?option=com_users&amp;view=user&amp;task=edit&cid[]=%s',
        'user16'=>'index.php?option=com_users&amp;task=user.edit&amp;id=%s' 
	);

	function user($title,$user_id,$attributes) 
    {
        if($user_id == 0) {
            return '"'.$title.'"';
        }		
		$route = $this->cmsVersion == CMS_JOOMLA15 ? $this->routes['user15'] : $this->routes['user16'];			
		$url = sprintf($route,$user_id); 				
        $attributes['sef']=false;
        return $this->Html->link($title,$url,$attributes);

    }
}