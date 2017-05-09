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

    var $useTable = '#__kunena_users AS Community';

    var $primaryKey = 'Community.user_id';

    var $realKey = 'userid';

    var $community = false;

    var $profileUrl = 'index.php?option=com_kunena&func=fbprofile&amp;userid=%s&amp;Itemid=%s';

    var $menu_id;

    var $gravatar_check = false;

    function __construct(){

        parent::__construct();

        Configure::write('Community.profileUrl',$this->profileUrl);

        if (file_exists(PATH_ROOT . 'components' . _DS . 'com_kunena' . _DS . 'kunena.php'))
        {
            $this->community = true;

            $Menu = ClassRegistry::getClass('MenuModel');

            $this->menu_id = $Menu->getComponentMenuId('com_kunena&view=user');

            if(!$this->menu_id) {

                $this->menu_id = $Menu->getComponentMenuId('com_kunena&view=home');
            }

            if(!$this->menu_id) {

                $this->menu_id = $Menu->getComponentMenuId('com_kunena');
            }

            // Gravatar Check

            $plugin = JPluginHelper::getPlugin('kunena', 'gravatar');

            if($plugin)
            {
                $params = new JRegistry($plugin->params);

                if($params->get('avatar','0'))
                {
                    $this->gravatar_check = true;//class_exists('KunenaAvatarGravatar');

                    require_once PATH_ROOT . 'plugins/kunena/gravatar/class.php';

                    require_once PATH_ROOT . 'plugins/kunena/gravatar/avatar.php';
                }
            }

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

        $avatar and $conditions[] = 'Community.avatar <> ""';

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

        unset($this->limit);
        unset($this->offset);

        $profiles = $this->findAll(array(
            'fields'=>array('Community.userid AS `Community.user_id`','Community.avatar AS `Community.avatar`'),
            'conditions'=>array($this->realKey . ' IN (' . implode(',',$owner_ids) . ')'),
        ));

        $profiles = $this->changeKeys($profiles,$this->name,'user_id');

        $menu_id = $this->menu_id;

        # Add avatar_path to Model results

        foreach ($profiles AS $key=>$value)
        {
            $profiles[$value[$this->name][$userKey]][$this->name]['community_user_id'] = $value[$this->name]['user_id'];

            if($profiles[$value[$this->name][$userKey]][$this->name]['avatar'] != '')
            {
                $avatar_image = $profiles[$value[$this->name][$userKey]][$this->name]['avatar'];

                $profiles[$value[$this->name][$userKey]][$this->name]['avatar_path'] = WWW_ROOT . 'media' . _DS . 'kunena' . _DS . 'avatars' . _DS . 'resized' . _DS .'size200' . _DS . $avatar_image;
            }
            elseif($this->gravatar_check) {

                $user = KunenaUserHelper::get($value[$this->name]['user_id']);

                $gravatar = new KunenaGravatar($user->email);

                $gravatar->setAvatarSize(80);

                $gravatar->setDefaultImage(false);

                $gravatar->setMaxRating('g');

                $profiles[$value[$this->name][$userKey]][$this->name]['avatar_path'] = $gravatar->buildGravatarURL( true );
            }
            else {

                $avatar_image = 'nophoto.jpg';

                $profiles[$value[$this->name][$userKey]][$this->name]['avatar_path'] = WWW_ROOT . 'media' . _DS . 'kunena' . _DS . 'avatars' . _DS . $avatar_image;
            }

            $profiles[$value[$this->name][$userKey]][$this->name]['community_user_id'] = $value[$this->name]['user_id'];

            $profiles[$value[$this->name][$userKey]][$this->name]['url'] = cmsFramework::route(sprintf($this->profileUrl,$value[$this->name]['user_id'],$menu_id));
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
