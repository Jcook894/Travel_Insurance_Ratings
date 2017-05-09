<?php
/**
 * JReviews - Reviews Extension
 * Copyright (C) 2010-2015 ClickFWD LLC
 * This is not free software, do not distribute it.
 * For licencing information visit https://www.jreviews.com
 * or contact sales@jreviews.com
**/

defined( 'MVC_FRAMEWORK') or die( 'Direct Access to this location is not allowed.' );

class CommunityModel extends MyModel  {

    var $name = 'Community';

    var $useTable = '#__social_users AS Community';

    var $primaryKey = 'Community.user_id';

    var $realKey = 'user_id';

    var $community = false;

    static $DEFAULT_AVATAR  = 'media/com_easysocial/defaults/avatars/user/square.png';

    static $DEFAULT_AVATAR_THUMB  = 'media/com_easysocial/defaults/avatars/user/medium.png';

    var $avatar_path = 'media/com_easysocial/avatars/users/';

    var $avatar_path_defaults = 'media/com_easysocial/avatars/defaults/profiles/';

	var $avatar_storage;

	var $s3_bucket;

    var $jomsocial_version;

    function __construct(){

        parent::__construct();

        if (file_exists(PATH_ROOT . 'components' . DS . 'com_easysocial' . DS . 'easysocial.php')) {

            require_once(JPATH_ROOT . '/administrator/components/com_easysocial/includes/foundry.php');

            $this->community = true;

			$cache_key = s2CacheKey('easysocial_config');

			$CommunityConfig = S2Cache::read($cache_key, '_s2framework_core_');

			if(false == $CommunityConfig)
            {
				// Read the configuration to determine the storage location for avatars
				$CommunityConfig = json_decode($this->query("SELECT value FROM #__social_config WHERE type = 'site'",'loadResult'),true);

                if(isset($CommunityConfig['avatars']['storage']))
                {
                    $storagelocal = Sanitize::getString($CommunityConfig['avatars']['storage'],'container',$this->avatar_path);

                    $storagelocal .= '/' . Sanitize::getString($CommunityConfig['avatars']['storage'],'user','users') . '/';
                }
                else {

                    $storagelocal = $this->avatar_path;
                }

				$CommunityConfigForJReviews = array(
					'user_avatar_storage'=>Sanitize::getString($CommunityConfig['storage'],'avatars') == 'amazon' ? 's3' : 'local',
					'storages3bucket'=>Sanitize::getString($CommunityConfig['storage']['amazon'],'bucket'),
                    'storagelocal'=>$storagelocal
				);

				S2Cache::write($cache_key,$CommunityConfigForJReviews, '_s2framework_core_');
			}
            else {

                $CommunityConfigForJReviews = $CommunityConfig;
            }

			$this->avatar_storage = $CommunityConfigForJReviews['user_avatar_storage'];

			$this->s3_bucket = $CommunityConfigForJReviews['storages3bucket'];

            if(Sanitize::getString($CommunityConfigForJReviews,'storagelocal') !== '')
            {
                $this->avatar_path = $CommunityConfigForJReviews['storagelocal'];
            }

            Configure::write('Community.register_url',ESR::registration());
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
            // Need to include the current Model.primary_key to avoid duplicates in the results
            'Community.'.$this->realKey. ' AS `Community.user_id`',
            'Community.'.$this->realKey. ' AS `User.user_id`',
            'User.name AS `User.name`',
            'User.username AS `User.username`'
        );

        $avatar and $conditions[] = 'Avatar.square <> ""';

        $listing_id and $conditions[] = 'Community.'.$this->realKey. ' in (SELECT user_id FROM #__jreviews_favorites WHERE content_id = ' . $listing_id . ')';

        $conditions[] = 'User.block = 0';

        $order = array('RAND('.$rand.')');

        $joins = array(
            'LEFT JOIN #__users AS User ON Community.'.$this->realKey. ' = User.id',
            'LEFT JOIN #__social_avatars AS Avatar ON Community.'.$this->realKey. ' = Avatar.uid'
        );

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

        foreach($results AS $result)
        {
            // Add only if not guests
            if(isset($result[$modelName]) && $result[$modelName][$userKey]) {
                $owner_ids[] = $result[$modelName][$userKey];
            }
        }

        return array_unique($owner_ids);
    }

    function addProfileInfo($results, $modelName, $userKey)
    {
        // If we need to get all user id info for many users at once the following foundry method could be used.
        // ES::users($arrayOfIds);

        if(!$this->community) {
            return $results;
        }

		$owner_ids = $this->__getOwnerIds($results, $modelName, $userKey);

        if(empty($owner_ids)) {
            return $results;
        }

        $config     = ES::config();

        $name_type  = $config->get( 'users.displayName' );

        $profiles = $this->findAll(array(
            'fields'=>array(
				'Community.user_id AS `Community.user_id`',
                'Avatar.avatar_id AS `Avatar.avatar_id`',
                'DefaultAvatar.uid AS `DefaultAvatar.uid`',
                ($name_type == 'realname' ? 'User.name AS `Community.username`' : 'User.username AS `Community.username`'),
				'IF(Avatar.avatar_id>0,DefaultAvatar.square,Avatar.square) AS `Community.avatar`',
                'IF(Avatar.avatar_id>0,DefaultAvatar.medium,Avatar.medium) AS `Community.avatar_thumb`',
                'Avatar.storage AS `Community.storage`',
                '"com_easysocial" AS `Community.extension`'
            ),
            'conditions'=>array(
                $this->realKey . ' IN (' . implode(',',$owner_ids) . ')'
            ),
			'joins'=>array(
                'LEFT JOIN #__users AS User ON Community.user_id = User.id',
                'LEFT JOIN #__social_avatars AS Avatar ON Community.user_id = Avatar.uid AND Avatar.type = "user"',
                'LEFT JOIN #__social_default_avatars AS DefaultAvatar ON Avatar.avatar_id = DefaultAvatar.id'
			)
        ));

        $profiles = $this->changeKeys($profiles,$this->name,'user_id');

        # Add avatar_path to Model results

        // Remove leading slash in avatar path becuse it's already added after the domain

        $this->avatar_path = ltrim($this->avatar_path, "/");

        foreach ($profiles AS $key=>$value)
		{
            if($profiles[$value[$this->name][$userKey]][$this->name]['avatar'] != '')
            {
                $avatar = $profiles[$value[$this->name][$userKey]][$this->name]['avatar'];

                $avatarThumb = $profiles[$value[$this->name][$userKey]][$this->name]['avatar_thumb'];

                if ($value['Avatar']['avatar_id'] > 0)
                {
                    $key = $value['DefaultAvatar']['uid'];

                    if ($this->avatar_storage == 's3' && $value[$this->name]['storage'] == 'amazon')
                    {
                        $avatar = '//'.$this->s3_bucket.'.s3.amazonaws.com/' . $this->avatar_path_defaults . $key . '/' . $avatar;

                        $avatarThumb = '//'.$this->s3_bucket.'.s3.amazonaws.com/' . $this->avatar_path_defaults . $key . '/' . $avatarThumb;
                    }
                    else {
                        $avatar = WWW_ROOT . $this->avatar_path_defaults . $key . '/' . $avatar;

                        $avatarThumb = WWW_ROOT . $this->avatar_path_defaults . $key . '/' . $avatarThumb;
                    }
                }
                elseif($this->avatar_storage == 's3' && $value[$this->name]['storage'] == 'amazon') {

                    $avatar = '//'.$this->s3_bucket.'.s3.amazonaws.com/' . $this->avatar_path . $key . '/' . $avatar;

                    $avatarThumb = '//'.$this->s3_bucket.'.s3.amazonaws.com/' . $this->avatar_path . $key . '/' . $avatarThumb;
                }
                else {

                    $avatar = WWW_ROOT . $this->avatar_path . $key . '/' . $avatar;

                    $avatarThumb = WWW_ROOT . $this->avatar_path . $key . '/' . $avatarThumb;
                }

            }
            else {

                $avatar = WWW_ROOT . self::$DEFAULT_AVATAR;

                $avatarThumb = WWW_ROOT . self::$DEFAULT_AVATAR_THUMB;
            }

            $profiles[$value[$this->name][$userKey]][$this->name]['community_user_id'] = $value[$this->name]['user_id'];

            $profiles[$value[$this->name][$userKey]][$this->name]['avatar_lg_path'] = $avatar;

			$profiles[$value[$this->name][$userKey]][$this->name]['avatar_path'] = $avatarThumb;

            $url = ESR::profile(array(
                'id' => self::getAlias($value[$this->name]['user_id'], $value[$this->name]['username'])
            ));

            $profiles[$value[$this->name][$userKey]][$this->name]['url'] = $url;
        }

        # Add Community Model to parent Model

        foreach ($results AS $key=>$result)
        {
            if(isset($profiles[$results[$key][$modelName][$userKey]])) {

                $results[$key] = array_merge($results[$key], $profiles[$results[$key][$modelName][$userKey]]);
            }
        }

        return $results;
    }

    /**
     * Adapted from /administrator/components/com_easysocial/includes/user/user.php
     */
    public function getAlias($user_id, $screen_name)
    {
        // If sef is not enabled or running SH404, just return the ID-USERNAME prefix.

        $jConfig = ES::jconfig();

        $sh404 = class_exists('shRouter');

        if(!$jConfig->getValue( 'sef' ) || $sh404 )
        {
            return $user_id . ':' . JFilterOutput::stringURLSafe( $screen_name );
        }

        $name = $user_id . ':' . $screen_name;

        // If the name is in the form of an e-mail address, fix it here by using the ID:permalink syntax
        if( JMailHelper::isEmailAddress( $screen_name ) )
        {
            return $user_id . ':' . JFilterOutput::stringURLSafe( $screen_name );
        }

        // Ensure that the name is a safe url.

        $name = JFilterOutput::stringURLSafe( $name );

        return $name;
    }
}
