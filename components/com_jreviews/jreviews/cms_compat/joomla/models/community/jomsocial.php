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

    var $useTable = '#__community_users AS Community';

    var $primaryKey = 'Community.user_id';

    var $realKey = 'userid';

    var $community = false;

    static $DEFAULT_AVATAR  = 'components/com_community/assets/user.png';

    static $DEFAULT_AVATAR_THUMB  = 'components/com_community/assets/user_thumb.png';

    static $MALE_AVATAR  = 'components/com_community/assets/user-Male.png';

    static $MALE_AVATAR_THUMB  = 'components/com_community/assets/user-Male-thumb.png';

    static $FEMALE_AVATAR  = 'components/com_community/assets/user-Female.png';

    static $FEMALE_AVATAR_THUMB  = 'components/com_community/assets/user-Female-thumb.png';

    var $avatar_storage;

    var $s3_bucket;

    var $jomsocial_version;

    function __construct(){

        parent::__construct();

        if (file_exists(PATH_ROOT . 'components' . DS . 'com_community' . DS . 'community.php')) {

            require_once( PATH_ROOT . 'components' . DS . 'com_community' . DS . 'libraries' . DS . 'core.php');

            $this->community = true;

            /**
             * Get the JomSocial version because of backward breaking changes we now
             * need to implement different code for different versions
             */

            if(!Configure::read('Community.version'))
            {
                $xml = JFactory::getXML(JPATH_ROOT . DS . 'administrator' . DS . 'components' . DS . 'com_community' . DS . 'community.xml' );

                $version = (string) $xml->version;

                $version_parts = explode('.', $version);

                $major_version = (int) array_shift($version_parts);

                $this->jomsocial_version = $major_version;

                Configure::write('Community.version',$major_version);
            }
            else {

                $this->jomsocial_version = Configure::read('Community.version');
            }

            if($this->jomsocial_version >= 3)
            {
                // Load language file, required for correct display of female/male avatars in JomSocial 3

                $lang = JFactory::getLanguage();

                $lang->load('com_community', JPATH_SITE);
            }

            $cache_key = s2CacheKey('jomsocial_config');

            $JSConfig = S2Cache::read($cache_key, '_s2framework_core_');

            if(false == $JSConfig) {

                // Read the JomSocial configuration to determine the storage location for avatars
                $JSConfig = json_decode($this->query("SELECT params FROM #__community_config WHERE name = 'config'",'loadResult'),true);

                $JSConfigForJReviews = array(
                    'user_avatar_storage'=>Sanitize::getString($JSConfig,'user_avatar_storage'),
                    'storages3bucket'=>Sanitize::getString($JSConfig,'storages3bucket')

                );

                S2Cache::write($cache_key,$JSConfigForJReviews, '_s2framework_core_');
            }


            $this->avatar_storage = Sanitize::getString($JSConfig,'user_avatar_storage');

            $this->s3_bucket = Sanitize::getString($JSConfig,'storages3bucket');

            Configure::write('Community.register_url',CRoute::_('index.php?option=com_community&view=register'));
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

        $avatar and $conditions[] = 'Community.thumb <> "components/com_community/assets/default_thumb.jpg" AND Community.thumb <> "components/com_community/assets/user_thumb.png" AND Community.thumb <> ""';

        $listing_id and $conditions[] = 'Community.'.$this->realKey. ' in (SELECT user_id FROM #__jreviews_favorites WHERE content_id = ' . $listing_id . ')';

        $conditions[] = 'User.block = 0';

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
        if(!$this->community) {
            return $results;
        }

        $owner_ids = $this->__getOwnerIds($results, $modelName, $userKey);

        if(empty($owner_ids)) {
            return $results;
        }

        $profiles = $this->findAll(array(
            'fields'=>array(
                'Community.userid AS `Community.user_id`',
                'Community.avatar AS `Community.avatar`',
                'Community.thumb AS `Community.avatar_thumb`',
//              'User.id AS `User.user_id`',
//              'User.name AS `User.name`',
//              'User.username AS `User.username`'
            ),
            'conditions'=>array($this->realKey . ' IN (' . implode(',',$owner_ids) . ')'),
//          'joins'=>array(
//              'LEFT JOIN #__users AS User ON Community.userid = User.id'
//          )
        ));

        $profiles = $this->changeKeys($profiles,$this->name,'user_id');

        $query = "
            SELECT
                field.name, field.fieldcode, value.value, value.access, value.user_id
            FROM
                #__community_fields AS field
            INNER JOIN
                #__community_fields_values AS value ON field.id = value.field_id AND value.user_id IN (" . implode(',',$owner_ids) . ")
            WHERE
                field.published = 1 AND field.visible >= 1
        ";

        $profile_fields = $this->query($query, 'loadAssocList');

        foreach($profile_fields AS $field)
        {
            $user_id = $field['user_id'];

            if(isset($profiles[$user_id])) {

                if(!isset($profiles[$user_id]['Community']['Field'])) {

                    $profiles[$user_id]['Community']['Field'] = array();
                }

                $field['value'] = JText::_($field['value']);

                $profiles[$user_id]['Community']['Field'][$field['fieldcode']] = $field;

                if($field['fieldcode'] == 'FIELD_GENDER' && $this->jomsocial_version >= 3)
                {
                    switch($field['value'])
                    {
                        case JText::_('COM_COMMUNITY_MALE'):
                        case 'COM_COMMUNITY_MALE':

                             $profiles[$user_id]['Community']['default_avatar_lg'] = self::$MALE_AVATAR;

                             $profiles[$user_id]['Community']['default_avatar_sm'] = self::$MALE_AVATAR_THUMB;

                        break;

                        case JText::_('COM_COMMUNITY_FEMALE'):
                        case 'COM_COMMUNITY_FEMALE':

                             $profiles[$user_id]['Community']['default_avatar_lg'] = self::$FEMALE_AVATAR;

                             $profiles[$user_id]['Community']['default_avatar_sm'] = self::$FEMALE_AVATAR_THUMB;

                        break;

                        default:

                             $profiles[$user_id]['Community']['default_avatar_lg'] = self::$MALE_AVATAR;

                             $profiles[$user_id]['Community']['default_avatar_sm'] = self::$MALE_AVATAR_THUMB;

                        break;
                    }
                }
            }
        }

        # Add avatar_path to Model results
        foreach ($profiles AS $key=>$value)
        {
            if($profiles[$value[$this->name][$userKey]][$this->name]['avatar'] != '')
            {
                $avatar = $profiles[$value[$this->name][$userKey]][$this->name]['avatar'];

                $avatarThumb = $profiles[$value[$this->name][$userKey]][$this->name]['avatar_thumb'];
            }
            elseif($this->jomsocial_version >= 3 && isset($profiles[$value[$this->name][$userKey]][$this->name]['default_avatar_lg']))
            {
                $avatar = $profiles[$value[$this->name][$userKey]][$this->name]['default_avatar_lg'];

                $avatarThumb = $profiles[$value[$this->name][$userKey]][$this->name]['default_avatar_sm'];
            }
            elseif($this->jomsocial_version >= 3)
            {
                $avatar = self::$MALE_AVATAR;

                $avatarThumb = self::$MALE_AVATAR_THUMB;
            }
            else {

                $avatar = self::$DEFAULT_AVATAR;

                $avatarThumb = self::$DEFAULT_AVATAR_THUMB;
            }

            if($this->avatar_storage == 's3' && $avatar != self::$DEFAULT_AVATAR) {

                $avatar = 'http://'.$this->s3_bucket.'.s3.amazonaws.com/' . $avatar;

                $avatarThumb = 'http://'.$this->s3_bucket.'.s3.amazonaws.com/' . $avatarThumb;
            }
            else {

                $avatar = WWW_ROOT. $avatar;

                $avatarThumb = WWW_ROOT. $avatarThumb;
            }

            $profiles[$value[$this->name][$userKey]][$this->name]['community_user_id'] = $value[$this->name]['user_id'];

            $profiles[$value[$this->name][$userKey]][$this->name]['avatar_lg_path'] = $avatar;

            $profiles[$value[$this->name][$userKey]][$this->name]['avatar_path'] = $avatarThumb;

            $profiles[$value[$this->name][$userKey]][$this->name]['url'] = CRoute::_('index.php?option=com_community&view=profile&userid='.$value[$this->name]['user_id']);
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

}
