<?php
/**
 * JReviews - Reviews Extension
 * Copyright (C) 2010-2015 ClickFWD LLC
 * This is not free software, do not distribute it.
 * For licencing information visit https://www.jreviews.com
 * or contact sales@jreviews.com
**/

defined( 'MVC_FRAMEWORK') or die( 'Direct Access to this location is not allowed.' );

class AccessComponent extends S2Component {

    var $gid = null;

    var $is_guest;

    var $guest = array(1);

    var $registered = array(2);

    var $editors = array(4,5,6,7,8); // Includes editor and above

    var $publishers = array(5,6,7,8); // Includes publisher and above

    var $managers = array(6,7,8); // Includes mabager and above

    var $admins = array(7,8); // admin, superadmin

    var $members = array(2,3,4,5,6,7,8); // Registered users and above

    var $guests = array(1,2,3,4,5,6,7,8);

    var $authorizedViewLevels = array();

    // jReviews access
    var $canAddMeta = null;

    var $canAddReview = null;

    var $Config;

	var $_user;

	var $__settings = array(
		'addnewaccess',
		'moderation_item',
		'editaccess',
		'listing_publish_access',
		'listing_delete_access',
		'addnewwysiwyg',
		'addnewmeta',
		'addnewaccess_reviews',
		'moderation_reviews',
		'user_vote_public',
		'editaccess_reviews',
		'media_access_view_photo_listing',
		'media_access_view_video_listing',
		'media_access_view_attachment_listing',
		'media_access_view_audio_listing',
		'media_access_view_photo_review',
		'media_access_view_video_review',
		'media_access_view_attachment_review',
		'media_access_view_audio_review',
		'media_access_submit_photo_listing',
		'media_access_submit_video_listing',
		'media_access_submit_attachment_listing',
		'media_access_submit_audio_listing',
		'media_access_submit_photo_review',
		'media_access_submit_video_review',
		'media_access_submit_attachment_review',
		'media_access_submit_audio_review',
		'media_access_moderate_photo',
		'media_access_moderate_video',
		'media_access_moderate_attachment',
		'media_access_moderate_audio',
        'media_access_upload_url_listing',
        'media_access_upload_url_review',
		'media_access_like_photo',
		'media_access_like_video',
		'media_access_like_attachment',
		'media_access_like_audio',
		'media_access_edit',
		'media_access_delete',
		'media_access_publish',
		'addnewaccess_posts',
		'moderation_posts',
		'post_edit_access',
		'post_delete_access',
		'moderation_owner_replies'
		);

	var $__settings_overrides = array(
		'addnewaccess',
		'addnewaccess_reviews',
		'media_access_view_photo_listing',
		'media_access_view_video_listing',
		'media_access_view_attachment_listing',
		'media_access_view_audio_listing',
		'media_access_view_photo_review',
		'media_access_view_video_review',
		'media_access_view_attachment_review',
		'media_access_view_audio_review',
		'media_access_submit_photo_listing',
		'media_access_submit_video_listing',
		'media_access_submit_attachment_listing',
		'media_access_submit_audio_listing',
		'media_access_submit_photo_review',
		'media_access_submit_video_review',
		'media_access_submit_attachment_review',
		'media_access_submit_audio_review',
		'media_access_moderate_photo',
		'media_access_moderate_video',
		'media_access_moderate_attachment',
		'media_access_moderate_audio',
        'media_access_upload_url_listing',
        'media_access_upload_url_review'
		);

    function __construct()
    {
        parent::__construct();

        $this->_user = cmsFramework::getUser();
    }

    function startup(&$controller)
    {
        $this->authorizedViewLevels = cmsFramework::getUserViewLevels($this->_user);

        $this->gid = $this->getGroupId($this->_user->id);
    }

    function init(&$Config)
    {
        $this->Config = &$Config;

        Configure::write('JreviewsSystem.Access',$this);
    }

    function showCaptcha()
    {
        if($this->Config->security_image && ($this->isGuest() || $this->isRegistered())) {

            return $this->in_groups($this->Config->security_image);
        }

        return false;
    }

    function isGuest()
    {
        return $this->is_guest;
    }

    function isRegistered()
    {
        return $this->in_groups($this->registered);
    }

    function isAdmin()
    {
        return $this->in_groups($this->admins);
    }

    function isEditor()
    {
        return $this->in_groups($this->editors);
    }

    function isManager()
    {
        return $this->in_groups($this->managers);
    }

    function isMember()
    {
        return $this->in_groups($this->members);
    }

    function isPublisher()
    {
        return $this->in_groups($this->publishers);
    }

    function isJreviewsEditor($user_id)
    {
        $jr_editor_ids = is_integer($this->Config->authorids) ? array($this->Config->authorids) : explode(',',$this->Config->authorids);
        if($this->Config->author_review && $user_id > 0 && in_array($user_id,$jr_editor_ids)){
            return true;
        }
        return false;
    }

	/************************************************
	 *					LISTINGS					*
	 ************************************************/

    function canEditListing($owner_id = null, $overrides = null)
    {
         return $this->canMemberDoThis($owner_id, 'editaccess', $overrides);
    }

    function canDeleteListing($owner_id, $overrides = null)
    {
         return $this->canMemberDoThis($owner_id, 'listing_delete_access', $overrides);
    }

	function canPublishListing($owner_id, $overrides = null)
    {
         return $this->canMemberDoThis($owner_id, 'listing_publish_access', $overrides);
    }

    function canClaimListing(&$listing)
    {
        return $this->Config->claims_enable
            && $this->_user->id > 0
            // && ($listing['Listing']['user_id'] != $this->_user->id)
            && $listing['Claim']['approved']<=0
            && (
                $this->Config->claims_enable_userids == ''
                || (
                    $this->Config->claims_enable_userids != ''
                    &&
                    in_array($listing['Listing']['user_id'],explode(',',$this->Config->claims_enable_userids))
                )
            )
        ;
    }

    function canAddListing($override = null)
    {
        $groups = $this->getOverride($override, 'addnewaccess');

        return $groups !='' && $this->in_groups($groups);
    }

    function canAddMeta()
    {
        return $this->Config->addnewmeta!='' && $this->in_groups($this->Config->addnewmeta);
    }

    function loadWysiwygEditor()
    {
        return $this->in_groups(Sanitize::getVar($this->Config,'addnewwysiwyg'));
    }

    function moderateListing($user_id = null)
    {
        if($user_id)
        {
            $User = cmsFramework::getUser($user_id);

            $gid = $User->gid;

            return $this->Config->moderation_item != '' && $this->in_groups($this->Config->moderation_item, $gid);
        }

        return $this->Config->moderation_item != '' && $this->in_groups($this->Config->moderation_item);
    }


	/************************************************
	 *					REVIEWS						*
	 ************************************************/
	function canAddReview($owner_id = null)
    {
        if(
            // First check the access groups
            (!$this->in_groups($this->Config->addnewaccess_reviews) || $this->Config->addnewaccess_reviews == 'none')
            ||
            // If it's not a jReviewsEditor then check the owner listing
            (!$this->isJreviewsEditor($this->_user->id) && $this->Config->user_owner_disable && !is_null($owner_id) && $owner_id != 0 && $this->_user->id == $owner_id)
        ) {
            return false;
        }
        return true;
    }

    function canSeeReviewButton($owner_id = null)
    {
        $registration_enabled = Configure::read('CMS.registration');

        if(
            // Hide the review button if guests aren't allowed to review and user registration is disabled
            ($this->isGuest() && !$registration_enabled && !$this->canAddReview())
            ||
            // Hide the review button if the listing owner isn't allowed to review the listing
            (!$this->isJreviewsEditor($this->_user->id) && $this->Config->user_owner_disable && !is_null($owner_id) && $owner_id != 0 && $this->_user->id == $owner_id)
            ||
            // Hide the review button if the user is logged in, but doesn't have access to submit reviews
            ($this->_user->id > 0 && !$this->canAddReview($owner_id))
        ) {
            return false;
        }

        return true;
    }

    function canEditReview($owner_id, $overrides = null)
    {
        return $this->canMemberDoThis($owner_id,'editaccess_reviews', $overrides);
    }

    function moderateReview()
    {
        return $this->Config->moderation_reviews != '' && $this->in_groups($this->Config->moderation_reviews);
    }

    function canVoteHelpful($reviewer_id = null)
    {
        if($reviewer_id && $reviewer_id == $this->_user->id) return false;
        return $this->Config->user_vote_public!='' && $this->in_groups($this->Config->user_vote_public);
    }

	/************************************************
	 *					MEDIA GENERAL				*
	 ************************************************/
	function moderateMedia($media_type)
	{
		$setting = Sanitize::getVar($this->Config,'media_access_moderate_'.$media_type);
        return  $setting != '' && $this->in_groups($setting);
	}

	function canManageMedia($media_type, $owner_id = null, $listing_owner_id = null) {

        if($this->_user->id === $listing_owner_id) {

            $owner_id = $listing_owner_id;
        }

		return $this->canEditMedia($media_type,$owner_id) ||
            $this->canDeleteMedia($media_type,$owner_id) ||
            $this->canPublishMedia($media_type, $owner_id);

	}

	function canEditMedia($media_type, $owner_id = null, $listing_owner_id = null)
    {
        if($this->_user->id === $listing_owner_id) {

            $owner_id = $listing_owner_id;
        }

        return $this->canMemberDoThis($owner_id,'media_access_edit');
    }

    function canDeleteMedia($media_type, $owner_id, $listing_owner_id = null)
    {
        if($this->_user->id === $listing_owner_id) {

            $owner_id = $listing_owner_id;
        }

        return $this->canMemberDoThis($owner_id,'media_access_delete');
    }

    function canPublishMedia($media_type, $owner_id, $listing_owner_id = null)
    {

        if($this->_user->id === $listing_owner_id) {

            $owner_id = $listing_owner_id;
        }

        return $this->canMemberDoThis($owner_id,'media_access_publish');
    }

	function canApproveMedia()
	{
		return $this->isEditor();
	}

	function canVoteMedia($media_type, $voter_id = null)
	{
        if($voter_id && $voter_id == $this->_user->id) return false;
		$setting = Sanitize::getVar($this->Config,'media_access_like_'.$media_type);
        return  $setting != '' && $this->in_groups($setting);
	}

	/************************************************
	 *					MEDIA LISTINGS				*
	 ************************************************/
	function canAddAnyListingMedia($owner_id = null, $overrides = array(), $listing_id = null)
	{
		$allowed_types = array();

		if(!is_array($overrides)) {
			$overrides = json_decode($overrides,true);
		}

		$media_types = array('photo','video','attachment','audio');

		foreach($media_types AS $media_type)
		{
			if($this->canAddListingMedia($media_type, $owner_id, $overrides, $listing_id)) {
				array_push($allowed_types,$media_type);
			}
		}

		if(!empty($allowed_types)) return $allowed_types;

		return false;
	}

    function canAddMediaFromUrl($location = 'listing', $override = null)
    {
        $groups = $this->getOverride($override, 'media_access_upload_url_'.$location);

        return $groups !='' && $this->in_groups($groups);
    }

	function canAddListingMedia($media_type, $owner_id, $overrides = array(), $listing_id = null)
	{
        // First check if the max allowed uploads is greater than zero

        $count_override = $this->setOverride($overrides, "media_{$media_type}_max_uploads_listing", -1);

        // Need the (integer) conversion for the conditional to work correctly

        if(is_numeric($count_override) && (int) $count_override === 0) return false;

		$access_override = $this->setOverride($overrides, "media_access_submit_{$media_type}_listing", -1);

        $groups = !empty($access_override) && $access_override != -1 ? $access_override : Sanitize::getVar($this->Config,"media_access_submit_{$media_type}_listing");

        $session_ids = cmsFramework::getSessionVar('listings','jreviews');

        // The listing was already submitted. This is important for guests where we need to use the session id
        // as the owner id to figure out which listings where submitted by the guest user

        if($this->_user->id == 0 && $owner_id == 0 && !is_null($listing_id) && isset($session_ids[$listing_id]) && cmsFramework::getCustomToken((int) $listing_id) == $session_ids[$listing_id]) {

            return $groups !='' && $this->in_groups($groups);

        }

		$check_listing_owner = Sanitize::getString($this->Config,"media_access_submit_{$media_type}_listing_owner");

        if($check_listing_owner == 1 && !$this->isAdmin())
        {
            // Nov 23, 2016 - Replaced the previous guest check because it was not allowing guests to upload media right after submitting a listing

            $userId = class_exists('UserAccountComponent') ? UserAccountComponent::getUserId() : $this->_user->id;

            if ($userId == 0)
            {
                return false;
            }

			return $this->canMemberDoThis($owner_id,"media_access_submit_{$media_type}_listing");
		}

		return $groups !='' && $this->in_groups($groups);
	}

	/************************************************
	 *					MEDIA REVIEWS				*
	 ************************************************/

    /**
     * Returns an array of media types the user is allowed to upload or false if none
     * @param  [type] $reviewer_id [description] If it's a guest, a session check needs to be done to find the review id
     * @param  array  $overrides   [description]
     * @param  [type] $review_id   If passed, this is a check performed after a review has been submitted
     * @return [type]              [description]
     */
	function canAddAnyReviewMedia($reviewer_id = null, $overrides = array(), $review_id = null)
	{
		$allowed_types = array();

		$media_types = array('photo','video','attachment','audio');

		foreach($media_types AS $media_type)
		{
			if($this->canAddReviewMedia($media_type, $reviewer_id, $overrides, $review_id)) {
				array_push($allowed_types,$media_type);
			}
		}

		if(!empty($allowed_types)) return $allowed_types;

		return false;
	}

    /**
     * [canAddReviewMedia description]
     * @param  [type] $media_type  [description]
     * @param  [type] $reviewer_id [description] If it's a guest, a session check needs to be done to find the review id
     * @param  array  $overrides   [description]
     * @param  [type] $review_id   If passed, this is a check performed after a review has been submitted
     * @return [type]              [description]
     */
	function canAddReviewMedia($media_type, $reviewer_id, $overrides = array(), $review_id = null)
	{
        // First check if the max allowed uploads is greater than zero

        $count_override = $this->setOverride($overrides, "media_{$media_type}_max_uploads_review", -1);

        if($count_override === 0) return false;

        // Then check the access settings

		$access_override = $this->setOverride($overrides, "media_access_submit_{$media_type}_review", -1);

        $groups = !empty($access_override) && $access_override != -1 ? $access_override : Sanitize::getVar($this->Config,"media_access_submit_{$media_type}_review");

        // The review was already submitted. This is important for guests where we need to use the session id
        // as the reviewer id to figure out which reviews where submitted by the guest user

        if(
            ($this->_user->id == 0 && $reviewer_id == 0 && !is_null($review_id))
            ||
            ($this->_user->id == 0 && $reviewer_id > 0 && !is_null($review_id))
        )
        {
            $session_ids = cmsFramework::getSessionVar('reviews','jreviews');

            if(isset($session_ids[$review_id]) && cmsFramework::getCustomToken((int) $review_id) == $session_ids[$review_id])
            {
                return $groups !='' && $this->in_groups($groups);
            }

            return false; // It was a guest and the review id cannot be matched to him
        }

        // Guests and new reviews

        elseif($this->_user->id == 0 && is_null($review_id)) {

            return $groups !='' && $this->in_groups($groups);
        }

        // Logged in users

        elseif($this->_user->id > 0) {

            return $this->canMemberDoThis($reviewer_id,"media_access_submit_{$media_type}_review");
        }
	}

	function getOverride($override, $key) {

		if(is_array($override) && count($override) == 1 && (int) $override[0] == -1) {
			$override = -1;
		}

		return !is_null($override) && $override != -1 ? $override : $this->Config->$key;

	}

	function setOverride($overrides, $key, $default = -1) {

		$value = Sanitize::getVar($overrides,$key,$default);

		if(is_array($value) && count($value) == 1 && (int) $value[0] == -1) {
			$value = -1;
		}

		if((int) $value != -1)  {
			$this->Config->$key = $value;
		}

		return $this->Config->$key;
	}

	/************************************************
	 *					REVIEW COMMENTS				*
	 ************************************************/

    function canAddPost()
    {
        return $this->Config->addnewaccess_posts!='' && $this->in_groups($this->Config->addnewaccess_posts);
    }

    function canEditPost($owner_id, $override = null)
    {
        return $this->canMemberDoThis($owner_id,'post_edit_access',$override);
    }

    function canDeletePost($owner_id, $override = null)
    {
        return $this->canMemberDoThis($owner_id,'post_delete_access',$override);
    }

    function moderatePost()
    {
        return $this->Config->moderation_posts!='' && $this->in_groups($this->Config->moderation_posts) ? true : false;
    }

	/************************************************
	 *					OWNER REPLIES				*
	 ************************************************/

	function canAddOwnerReply(&$listing,&$review)
    {
        return $this->Config->owner_replies
            && (isset($listing['Claim']) && $listing['Claim']['approved'] == 1 || $this->isEditor()) // Only approved claims or editor group and above
            && $this->_user->id >0
            && isset($listing['Listing']['user_id'])
            && $listing['Listing']['user_id'] == $this->_user->id
            && $review['Review']['editor']==0
            && $review['Review']['owner_reply_approved']<=0
        ;
    }

    function canDeleteOwnerReply($listing)
    {
        return $this->isManager() ||
            ($this->_user->id > 0
            && isset($listing['Listing']['user_id']) && $this->Config->owner_replies
            && $listing['Listing']['user_id'] == $this->_user->id)
        ;
    }

    function moderateOwnerReply()
    {
        return $this->Config->moderation_owner_replies !='' && $this->in_groups($this->Config->moderation_owner_replies) ? true : false;
    }


	// Wrapper functions

    function canMemberDoThis($owner_id, $config_key, $override = null)
    {
        $setting = $override ? $this->getOverride(Sanitize::getVar($override,$config_key), $config_key) : $this->Config->{$config_key};

        $allowedGroups = is_array($setting) ?
                            $setting
                            :
                            explode(',',$setting);

        // For submissions with newly created user accounts using JReviews account creation features
        // We get the user's id and gid

        $new_user_id = class_exists('UserAccountComponent') ? UserAccountComponent::getUserId() : 0;

        if($new_user_id && $owner_id == $new_user_id) {

            $gid = $this->getGroupId($new_user_id);

            return $this->in_groups($allowedGroups, $gid);
        }

        // For guests we do a standard check against access groups

        elseif($this->_user->id == 0 || empty($this->gid)) {

            return $this->in_groups($allowedGroups);

        }

        // For any other user we check if they are in the editor group or if they are the owner of the submission

        elseif (
            ($this->in_groups($this->editors) && $this->in_groups($allowedGroups))
            ||
            ($this->_user->id == $owner_id && $owner_id > 0 && $this->in_groups($allowedGroups))
        ) {

            return true;
        }

        return false;
    }

    function isAuthorized($access)
    {
        return in_array($access,$this->authorizedViewLevels);
    }

    function getAccessLevels()
    {
		return implode(',',$this->authorizedViewLevels);
    }

    function in_groups($groups, $gid = null)
    {
        $gid = is_null($gid) ?  $this->gid : $gid;

        if($groups == 'all') return true;

        // Extra check for when view cache is enabled

        if(is_null($gid)) $gid = array(1);

        !is_array($groups) and $groups = explode(',',$groups);

        $check = array_intersect($gid,$groups);

        return !empty($check);
    }

    function getGroupId($user_id)
    {
        if($groups = cmsFramework::getSessionVar('gid','jreviews')) {

            return $groups;
        }

        if (!$user_id)
        {
            return array(1);
        }

        $User = cmsFramework::getUser($user_id);

        // Check if the gid variable has any values, otherwise get the group ids using the getAuthorisedGroups method

        if($User->gid)
        {
            $groups = $User->gid;
        }
        else {

            $groups = $User->getAuthorisedGroups();

            // Makes it consistent with the output of JUser::gid

            unset($groups[0]);
        }

        cmsFramework::setSessionVar('gid',$groups,'jreviews');

        return $groups;
    }
}
