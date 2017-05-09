<?php
/**
 * JReviews - Reviews Extension
 * Copyright (C) 2010-2012 ClickFWD LLC
 * This is not free software, do not distribute it.
 * For licencing information visit http://www.reviewsforjoomla.com
 * or contact sales@reviewsforjoomla.com
**/

defined( 'MVC_FRAMEWORK') or die( 'Direct Access to this location is not allowed.' );
                  
class CommunityHelper extends MyHelper {
                                     
    var $helpers = array('html');
    
    function profileLink($name,$user_id,$menu_id) 
    {
        if($user_id > 0) {
            $community_url = Configure::read('Community.profileUrl');
            $url = sprintf($community_url,$user_id,$menu_id);            
            return $this->Html->sefLink($name,$url,array(),false);
        }else {
            return $name;
        }
    }    
    
    function avatar($entry) 
    {
        if(isset($entry['Community']) && $entry['User']['user_id'] > 0) 
        {
            $screenName = $this->screenName($entry,null,false);
                       
            if(isset($entry['Community']['avatar_path']) && $entry['Community']['avatar_path'] != '') {
                return $this->profileLink($this->Html->image($entry['Community']['avatar_path'],array('class'=>'jr_avatar','alt'=>$screenName,'border'=>0)),$entry['Community']['community_user_id'],$entry['Community']['menu_id']);
            } else {
                return $this->profileLink($this->Html->image($this->viewImages.'tnnophoto.jpg',array('class'=>'jr_avatar','alt'=>$screenName,'border'=>0)),$entry['Community']['community_user_id'],$entry['Community']['menu_id']);
            }
        }
    }
    
    function screenName(&$entry, $link = true) 
    {
        // $Config param not being used
        $screenName = $this->Config->name_choice == 'realname' ? $entry['User']['name'] : $entry['User']['username'];

        if($link && !empty($entry['Community']) && $entry['User']['user_id'] > 0) {
            return $this->profileLink($screenName,$entry['Community']['community_user_id'],$entry['Community']['menu_id']);
        } 
        
        $screenName = $screenName == '' ? __t("Guest",true) : $screenName;
        
        return $screenName;
    }

	function socialBookmarks($listing)
	{
        $googlePlusOne = $twitter = $facebook = '';
        
        $facebook_xfbml = Sanitize::getBool($this->Config,'facebook_opengraph') && Sanitize::getBool($this->Config,'facebook_appid');
        $href = cmsFramework::makeAbsUrl($listing['Listing']['url'],array('sef'=>true));

        $twitter = '
            <a href="http://twitter.com/share" data-url="'.$href.'" class="twitter-share-button" data-count="horizontal">Tweet</a>
            <script type="text/javascript">jQuery(document).ready(function(){jQuery.getScript("http://platform.twitter.com/widgets.js");})</script>'
        ;
        
		if($facebook_xfbml) {
            $facebook = '<div class="fb-like" data-show-faces="false" data-href="'.$href.'" data-action="like" data-colorscheme="light" data-layout="button_count"></div>';
        }
        else {
            $facebook = '<script src="http://connect.facebook.net/'.cmsFramework::getLocale().'/all.js#xfbml=1"></script><div class="fb-like" data-layout="button_count" data-show_faces="false"></div>';                
        }
        
        if($this->Config->facebook_send) {
            $facebook .= '<div class="fb-send" data-href="'.$href.'" data-colorscheme="light"></div>';
        }
        
        $googlePlusOne = '
            <g:plusone href="'.$href.'" size="medium"></g:plusone>
            <script type="text/javascript" src="http://apis.google.com/js/plusone.js"></script>
        ';

		return $googlePlusOne . $twitter . $facebook;
	}
    
}