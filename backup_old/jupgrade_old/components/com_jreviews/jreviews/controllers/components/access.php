<?php
/**
 * JReviews - Reviews Extension
 * Copyright (C) 2009 ClickFWD LLC
 * This is not free software, do not distribute it.
 * For licencing information visit http://www.reviewsforjoomla.com
 * or contact sales@reviewsforjoomla.com
**/

defined( 'MVC_FRAMEWORK') or die( 'Direct Access to this location is not allowed.' );
                     
class AccessComponent extends S2Component {
    
    var $gid = null;    
    var $editors = array(4,5,6,7,8); // Includes editor and above
    var $publishers = array(5,6,7,8); // Includes publisher and above
    var $managers = array(6,7,8); // Includes mabager and above
    var $admins = array(7,8); // admin, superadmin
    var $members = array(2,3,4,5,6,7,8); // Registered users and above
    var $guests = array(1,2,3,4,5,6,7,8);
    
    // jReviews access
    var $canAddMeta = null;
    var $canAddReview = null;
    
    var $Config;
    var $_user; 
    
    function __construct()
    {            
        parent::__construct();
        if($this->cmsVersion == CMS_JOOMLA15)
        {
            $this->editors = array(20,21,23,24,25); // Includes editor and above
            $this->publishers = array(21,23,24,25); // Includes publisher and above
            $this->managers = array(23,24,25); // Includes mabager and above
            $this->admins = array(24,25); // admin, superadmin
            $this->members = array(18,19,20,21,23,24,25); // Registered users and above   
            $this->guests = array(0,18,19,20,21,23,24,25); // Registered users and above            
        }
    }
    function startup(&$controller) 
    {        
        $this->cmsVersion = $controller->cmsVersion;  
        $this->_user = &$controller->_user;
    }

    function init(&$Config) 
    {         
        if(!isset($this->_user)) {
            $this->_user = & cmsFramework::getUser();
        }
        $this->Config = &$Config;
        $this->gid = $this->getGroupId($this->_user->id);
        Configure::write('JreviewsSystem.Access',$this);        
    }
    
    function showCaptcha()
    {                         
        return $this->Config->security_image == '' ? false : $this->in_groups($this->Config->security_image);         
    } 
    
    function loadWysiwygEditor() 
    {                                                                           
        return $this->in_groups(Sanitize::getVar($this->Config,'addnewwysiwyg'));
    }

    function isAuthorized($access)
    {                                 
        return $this->cmsVersion == CMS_JOOMLA15 
            ? 
            $this->_user->aid >= $access 
            : 
            in_array($access,$this->_user->authorisedLevels());    
    }
    
    function getAccessId()
    {           
        return cleanIntegerCommaList($this->_user->aid);    
    }
    
    function getAccessLevels()
    {                    
        $user_access = $this->cmsVersion == CMS_JOOMLA15 ? array($this->_user->aid) : array_unique($this->_user->authorisedLevels());
        return implode(',',array_unique($user_access));        
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
    
    function in_groups($groups) 
    {             
        if($groups == 'all') return true;
        !is_array($groups) and $groups = explode(',',$groups); 
        $check = array_intersect($this->gid,$groups); 
        return !empty($check);
    }
    
    function getGroupId($user_id) 
    {
        $db = cmsFramework::getDB();
        
        if (!$user_id) {
            return $this->cmsVersion == CMS_JOOMLA15 ? array(0) : array(1);
        }
        
        if($this->cmsVersion == CMS_JOOMLA15)
        {
            $query = "
                SELECT 
                    gid 
                FROM 
                    #__users 
                WHERE 
                    id = " . $user_id
            ;
            $db->setQuery($query);
            return array($db->loadResult());
        }
        else
        {
            $query = "
                SELECT 
                    group_id 
                FROM 
                    #__user_usergroup_map 
                WHERE 
                    user_id = " . $user_id
            ;
            $db->setQuery($query);
            return $db->loadResultArray();            
        }
    }
    
    function canAddListing($override = null)
    {           
        $groups = !is_null($override) && $override != -1 ? $override : $this->Config->addnewaccess;
        return $groups !='' && $this->in_groups($groups); 
    }
    
    function canAddMeta()
    {
        return $this->Config->addnewmeta!='' && $this->in_groups($this->Config->addnewmeta);
    }
    
    function moderateListing()
    {
        return $this->Config->moderation_item!='' && $this->in_groups($this->Config->moderation_item);
    }
    
    function canVoteHelpful($reviewer_id = null) 
    {
        if($reviewer_id && $reviewer_id == $this->_user->id) return false;
        return $this->Config->user_vote_public!='' && $this->in_groups($this->Config->user_vote_public);
    }
    
    function canEditListing($owner_id = null)
    {                          
         return $this->canMemberDoThis($owner_id,'editaccess');
    }
    
    function canPublishListing($owner_id)
    {        
         return $this->canMemberDoThis($owner_id,'listing_publish_access');
    }   
    
    function canDeleteListing($owner_id)
    {      
         return $this->canMemberDoThis($owner_id,'listing_delete_access');
    }     

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
    
    function canEditReview($owner_id) 
    {     
        return $this->canMemberDoThis($owner_id,'editaccess_reviews');
    }     
        
    function moderateReview()
    {
        return $this->Config->moderation_reviews!='' && $this->in_groups($this->Config->moderation_reviews);
    }   
     
    function canAddPost()
    {
        return $this->Config->addnewaccess_posts!='' && $this->in_groups($this->Config->addnewaccess_posts);
    }        
    
    function canEditPost($owner_id)
    {        
        return $this->canMemberDoThis($owner_id,'post_edit_access');
    }
    
    function canDeletePost($owner_id)
    {        
        return $this->canMemberDoThis($owner_id,'post_delete_access');
    }
    
    function moderatePost()
    { 
        return $this->Config->moderation_posts!='' && $this->in_groups($this->Config->moderation_posts) ? true : false;   
    }  
    
    function canAddOwnerReply(&$listing,&$review) 
    {            
        return $this->_user->id >0 
            && isset($listing['User']['user_id']) && $this->Config->owner_replies 
            && $review['Review']['editor']==0 
            && $review['Review']['owner_reply_approved']<=0
            && $listing['User']['user_id'] == $this->_user->id
        ;
    } 
    
    function moderateOwnerReply()
    { 
        return $this->Config->moderation_owner_replies !='' && $this->in_groups($this->Config->moderation_owner_replies) ? true : false;   
    }  
    
    function canClaimListing(&$listing) 
    {
        return $this->Config->claims_enable 
            && $this->_user->id > 0
            && ($listing['Listing']['user_id'] != $this->_user->id)
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
 
 // Wrapper functions
    function canMemberDoThis($owner_id ,$config_setting)
    {                            
        $allowedGroups = explode(',',$this->Config->{$config_setting});
        if ($this->_user->id == 0 || empty($this->gid)) {
            return false;            
        } elseif (
            ($this->in_groups($this->editors) && $this->in_groups($allowedGroups))
            ||        
            ($this->_user->id == $owner_id && $owner_id >0 && $this->in_groups($allowedGroups))
        ) {            
            return true;        
        }
        return false;
    }
    
}
