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

class AccessComponent extends S2Component {
	
	// Joomla core access
	var $canEdit = null;
	var $canEditOwn = null;
	var $canPublish = null;
	// jReviews access
	var $canAddItem = null;
	var $canAddMeta = null;
	var $canAddReview = null;
	var $canEditItem = null;
	var $canEditReview = null;
	var $moderateItem = null;
	var $moderateReview = null;
	var $canEditWYSIWYG = null; // Deprecated
	var $canVote = null;
	var $showCaptcha = null;
	var $gid = null;
	
	var $Config;
	var $_user;
	var $_db;
	
	function startup(&$controller) {

		$this->_acl = &$controller->_acl;
		$this->_user = &$controller->_user;
		$this->_db = &$controller->_db;
//	    cmsFramework::init($this);
	}

	function init(&$Config) {

		$this->Config = &$Config;

		// Joomla core
		if(getCmsVersion()==CMS_JOOMLA15){
			$this->canEdit 	= $this->_user->authorize( 'com_content', 'edit', 'content', 'all' );
			$this->canEditOwn = $this->_user->authorize( 'com_content', 'edit', 'content', 'own' );
			$this->canPublish = $this->_user->authorize( 'com_content', 'publish', 'content', 'all' );			
		} else {
			$this->canEdit 	= $this->_acl->acl_check( 'action', 'edit', 'users', $this->_user->usertype, 'content', 'all' );
			$this->canEditOwn = $this->_acl->acl_check( 'action', 'edit', 'users', $this->_user->usertype, 'content', 'own' );
			$this->canPublish = $this->_acl->acl_check( 'action', 'publish', 'users', $this->_user->usertype, 'content', 'all' );
		}
		
		// jReviews
		$gid = $this->getgid($this->_user->id);
		$this->gid = (int) $gid;
		
		$this->canEditWYSIWYG = $this->loadWysiwygEditor();
		
		if ($this->Config->moderation_reviews == '') {
			$this->Config->moderation_reviews = 'none';
		}

		if ($this->Config->moderation_item == '') {
			$this->Config->moderation_item = 'none';
		}
		
		if ($this->Config->addnewaccess == '') {
			$this->Config->addnewaccess = 'none';
		}

		if ($this->Config->addnewwysiwyg == '') {
			$this->Config->addnewwysiwyg = 'none';
		}

		if ($this->Config->addnewmeta == '') {
			$this->Config->addnewmeta = 'none';
		}

		if ($this->Config->addnewaccess_reviews == '') {
			$this->Config->addnewaccess_reviews = 'none';
		}

		if ($this->Config->user_vote_public == '') {
			$this->Config->user_vote_public = 'none';
		}

		if ($this->Config->security_image == '') {
			$this->Config->security_image = 'none';
		}		

		$this->moderateItem = !in_array($gid, explode(',',$this->Config->moderation_item)) || $this->Config->moderation_item == 'none' ? 0 : 1;
		
		$this->canAddItem =	!in_array($gid, explode(',',$this->Config->addnewaccess)) || $this->Config->addnewaccess == 'none' ? 0 : 1;
				
		$this->canAddMeta = !in_array($gid, explode(',',$this->Config->addnewmeta)) || $this->Config->addnewmeta == 'none' ? 0 : 1;
		
		$this->moderateReview = !in_array($gid, explode(',',$this->Config->moderation_reviews)) || $this->Config->moderation_reviews == 'none' ? 0 : 1;
		
		// variable below now needs to be called through the canAddReviews method below which includes the listing_id
		$this->canAddReview = !in_array($gid, explode(',',$this->Config->addnewaccess_reviews)) || $this->Config->addnewaccess_reviews == 'none' ? 0 : 1;
		
		$this->canVote = !in_array($gid, explode(',',$this->Config->user_vote_public)) || $this->Config->user_vote_public == 'none' ? 0 : 1;
		
		$this->showCaptcha = in_array($gid, explode(',',$this->Config->security_image)) && $this->Config->security_image != 'none';
	}
	
	function loadWysiwygEditor() {
		
		return !in_array($this->gid, explode(',',$this->Config->addnewwysiwyg)) || $this->Config->addnewwysiwyg == 'none' ? false : true;		
		
	}

	function isJreviewsEditor($user_id) {
		$author_ids = is_integer($this->Config->authorids) ? array($this->Config->authorids) : explode(',',$this->Config->authorids); 
		if($this->Config->author_review && $user_id > 0 && in_array($user_id,$author_ids)){
			return true;
		} else {
			return false;
		}
		
	}
	
	function canAddReviews($user_id,$listing_owner_id) {
		
		// First check the access groups
		if(!in_array($this->gid, explode(',',$this->Config->addnewaccess_reviews)) || $this->Config->addnewaccess_reviews == 'none') {
			return false;
		}
		
		// If it's not a jReviewsEditor then check the owner listing
		if(!$this->isJreviewsEditor($user_id) && $this->Config->user_owner_disable && $user_id == $listing_owner_id) {
			return false;
		}
		
		return true;
	}
	
	function canAddListing($user_id) {
		
		if(!in_array($this->gid, explode(',',$this->Config->addnewaccess)) 
			|| $this->Config->addnewaccess == 'none') {
			return false;
		} else {
			return true;
		}		
	}
	
	function canEditListing($author_id) {
		
		if ($this->_user->id < 1) return false; // Guests can't edit anything

		$gid = $this->gid;

		$allowedGroups = explode(',',$this->Config->editaccess);

		$superusers = array(20,21,23,24,25);

		if (!$gid) 
		{
			return false;
		
		} elseif (in_array($gid,$superusers) && in_array($gid,$allowedGroups)) {
			
			return true;
		
		} elseif ($this->_user->id == $author_id && $author_id >0 && in_array($gid,$allowedGroups)) {
			
			return true;
		
		} else {
			
			return false;
		}
	
	}
	
	function canEditReview($author_id) {
				
		if ($this->_user->id < 1) return false; // Guests can't edit anything

		$gid = $this->gid;

		$allowedGroups = explode(',',$this->Config->editaccess_reviews);

		$superusers = array(20,21,23,24,25);

		if (!$gid) {

			return false;
			
		} elseif (in_array($gid,$superusers) && in_array($gid,$allowedGroups)) {
			
			return true;
		
		} elseif ($this->_user->id == $author_id && $author_id >0 && in_array($gid,$allowedGroups)) {

			return true;

		} else {

			return false;
		}
	}
	
	function getOwnerPerm($authorid) {
				
		if ($this->_user->id < 1) return 0; // Guests can't edit anything

		$gid = $this->gid;

		$allowedGroups = explode(',',$this->Config->editaccess);

		$superusers = array(20,21,23,24,25);

		if (!$gid) {
			$this->canEditItem = 0;
		} elseif (in_array($gid,$superusers) && in_array($gid,$allowedGroups)) {
			$this->canEditItem = 1;
		} elseif ($this->_user->id == $authorid && $authorid >0 && in_array($gid,$allowedGroups)) {
			$this->canEditItem = 1;
		} else {
			$this->canEditItem = 0;
		}

	}

	function in_groups($groups) {

		return (!in_array($this->gid, explode(',',$groups)) ? 0 : 1);

	}

	function getgid($userid) {

		if (!$userid) {
			return 0;
		}

		$query = "SELECT gid FROM #__users WHERE id = " . $userid;
		$this->_db->setQuery($query);
		$gid = $this->_db->loadResult();
		return $gid;
	}
}