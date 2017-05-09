<?php
/**
 * JReviews - Reviews Extension
 * Copyright (C) 2010-2012 ClickFWD LLC
 * This is not free software, do not distribute it.
 * For licencing information visit http://www.reviewsforjoomla.com
 * or contact sales@reviewsforjoomla.com
**/

defined( 'MVC_FRAMEWORK') or die( 'Direct Access to this location is not allowed.' );

class CommunityModel extends MyModel  {
    
    var $name = 'Community';
    
    var $useTable = '#__community_users AS Community';
    
    var $primaryKey = 'Community.user_id';
    
    var $realKey = 'userid';

    var $community = false;
    
    var $profileUrl = 'index.php?option=com_community&view=profile&userid=%s&Itemid=%s';
        
    var $registerUrl = 'index.php?option=com_community&amp;view=register&amp;Itemid=%s';   
        
    var $menu_id;
    
    var $default_thumb  = 'components/com_community/assets/user_thumb.png';
	
	var $avatar_storage;
	
	var $s3_bucket;
    
    function __construct(){    
                        
        parent::__construct();
        
        Configure::write('Community.profileUrl',$this->profileUrl);
        
        if (file_exists(PATH_ROOT . 'components' . _DS . 'com_community' . _DS . 'community.php')) {
            
            $this->community = true;
        
            $Menu = ClassRegistry::getClass('MenuModel');

            $this->menu_id = $Menu->getComponentMenuId('com_community&view=frontpage');
            
            if(!$this->menu_id)
            {
                $this->menu_id = $Menu->getComponentMenuId('com_community&view=profile');
            }

            if(!$this->menu_id)
            {
                $this->menu_id = $Menu->getComponentMenuId('com_community');
            }
            
            // For JomSocial <= 2.1
            if(!file_exists(PATH_ROOT . 'components/com_community/assets/user_thumb.png')) {
                $this->default_thumb = 'components/com_community/assets/default_thumb.jpg';
            }
			
			$cache_key = 'jomsocial_config_'. md5(cmsFramework::getConfig('secret'));

			$JSConfig = S2Cache::read($cache_key);

			if(false == $JSConfig) {
				
				// Read the JomSocial configuration to determine the storage location for avatars
				$JSConfig = json_decode($this->query("SELECT params FROM #__community_config WHERE name = 'config'",'loadResult'),true);

				$JSConfigForJReviews = array(
					'user_avatar_storage'=>$JSConfig['user_avatar_storage'],
					'storages3bucket'=>$JSConfig['storages3bucket']
					
				);
				
				S2Cache::write($cache_key,$JSConfigForJReviews);
			}        
			
			
			$this->avatar_storage = $JSConfig['user_avatar_storage'];
			
			$this->s3_bucket = $JSConfig['storages3bucket'];

            Configure::write('Community.register_url',sprintf($this->registerUrl,$this->menu_id));
        }
    
    }
    
    function getListingFavorites($listing_id, $user_id, $passedArgs) 
    {                
        $conditions = array();
        $avatar    = Sanitize::getInt($passedArgs['module'],'avatar',1); // Only show users with avatars
        $module_id = Sanitize::getInt($passedArgs,'module_id');
        $rand = Sanitize::getFloat($passedArgs,'rand');
        $limit = Sanitize::getInt($passedArgs['module'],'module_total',10);

        $fields = array(
            'Community.'.$this->realKey. ' AS `User.user_id`',
            'User.name AS `User.name`',
            'User.username AS `User.username`'
        );
                
        $avatar and $conditions[] = 'Community.thumb <> "components/com_community/assets/default_thumb.jpg" AND Community.thumb <> "components/com_community/assets/user_thumb.png" AND Community.thumb <> ""';
        
        $listing_id and $conditions[] = 'Community.'.$this->realKey. ' in (SELECT user_id FROM #__jreviews_favorites WHERE content_id = ' . $listing_id . ')';
        
        $order = array('RAND('.$rand.')');

        $joins = array('LEFT JOIN #__users AS User ON Community.'.$this->realKey. ' = User.id');
                           
        $profiles = $this->findAll(array(
            'fields'=>$fields,
            'conditions'=>$conditions,
            'order'=>$order,
            'joins'=>$joins,
            'limit'=>$limit
        ));

        return $this->addProfileInfo($profiles,'User','user_id');
    }

    function __getOwnerIds($results, $modelName, $userKey) {
        
        $owner_ids = array();
        
        foreach($results AS $result) {
            // Add only if not guests
            if($result[$modelName][$userKey]) {
                $owner_ids[] = $result[$modelName][$userKey];
            }
            
        }

        return array_unique($owner_ids);
    }
    
    function addProfileInfo($results, $modelName, $userKey)
    {

        if(!$this->community) {
            return $results;
        }
        
        $owner_ids = $this->__getOwnerIds($results, $modelName, $userKey);

        if(empty($owner_ids)) {
            return $results;
        }
        
        $profiles = $this->findAll(array(
            'fields'=>array('Community.userid AS `Community.user_id`','Community.thumb AS `Community.avatar`'),
            'conditions'=>array($this->realKey . ' IN (' . implode(',',$owner_ids) . ')'),        
        ));

        $profiles = $this->changeKeys($profiles,$this->name,'user_id');
        $menu_id = $this->menu_id;
        
        # Add avatar_path to Model results
        foreach ($profiles AS $key=>$value) 
		{    
            $thumb = $profiles[$value[$this->name][$userKey]][$this->name]['avatar'] !='' ? $profiles[$value[$this->name][$userKey]][$this->name]['avatar'] : $this->default_thumb;
        
			if($this->avatar_storage == 's3') {
				$thumb = 'http://'.$this->s3_bucket.'.s3.amazonaws.com/' . $thumb;
			}
			else {
				$thumb = WWW_ROOT. $thumb;
			}
			
			$profiles[$value[$this->name][$userKey]][$this->name]['community_user_id'] = $value[$this->name]['user_id'];
            
			$profiles[$value[$this->name][$userKey]][$this->name]['avatar_path'] = $thumb;
        }

        # Add Community Model to parent Model
        foreach ($results AS $key=>$result) {

            if(isset($profiles[$results[$key][$modelName][$userKey]])) {
                $results[$key] = array_merge($results[$key], $profiles[$results[$key][$modelName][$userKey]]);
            }
                
            $results[$key][$this->name]['menu_id'] = $menu_id;
            
        }

        return $results;        
    }    
    
}
