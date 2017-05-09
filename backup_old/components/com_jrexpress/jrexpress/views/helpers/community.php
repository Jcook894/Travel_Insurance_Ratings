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

class CommunityHelper extends HtmlHelper {
			
	function profileLink($name,$user_id,$menu_id) {
                                              
		if($user_id > 0) {
            $community_url = Configure::read('Community.profileUrl');
			$url = sprintf($community_url,$user_id,$menu_id);			
            return $this->sefLink($name,$url,array(),false);
		}else {
			return $name;
		}
	}	
	
	function avatar($entry) {

		if(isset($entry['Community']) && $entry['User']['user_id'] > 0) 
		{
            $screenName = $this->screenName($entry,null,false);
                       
			if(isset($entry['Community']['avatar_path']) && $entry['Community']['avatar_path'] != '') {
				return $this->profileLink($this->image($entry['Community']['avatar_path'],array('class'=>'jr_avatar','alt'=>$screenName,'border'=>0)),$entry['User']['user_id'],$entry['Community']['menu_id']);
			} else {
				return $this->profileLink($this->image($this->viewImages.'tnnophoto.jpg',array('class'=>'jr_avatar','alt'=>$screenName,'border'=>0)),$entry['User']['user_id'],$entry['Community']['menu_id']);
			}
		}
	}
    
    function screenName(&$entry, $Config, $link = true) {

        $screenName = $this->Config->name_choice == 'realname' ? $entry['User']['name'] : $entry['User']['username'];

        if($link && isset($entry['Community']) && is_array($entry['Community']) && $entry['User']['user_id'] > 0) {
            return $this->profileLink($screenName,$entry['User']['user_id'],$entry['Community']['menu_id']);
        } 
          
        return $screenName;
    }    
	
}