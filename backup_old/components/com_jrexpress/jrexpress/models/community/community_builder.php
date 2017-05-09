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

class CommunityModel extends MyModel  {
	
	var $name = 'Community';
	
	var $useTable = '#__comprofiler AS Community';
	
	var $primaryKey = 'Community.id';
	
	var $realKey = 'id';

	var $community = false;

	var $profileUrl = 'index.php?option=com_comprofiler&amp;task=userProfile&amp;user=%s&amp;Itemid=%s';	
		
	function __construct(){	
						
		parent::__construct();
		
		Configure::write('Community.profileUrl',$this->profileUrl);		
		
		if (file_exists(PATH_ROOT . 'components' . _DS . 'com_comprofiler' . _DS . 'comprofiler.php')) {
			$this->community = true;
		}
	
	}
	
	function getListingFavorites($listing_id, $user_id, $passedArgs) 
	{		
		$avatar	= Sanitize::getInt($passedArgs['module'],'avatar',1); // Only show users with avatars
		$count 	= Sanitize::getInt($passedArgs['module'],'module_limit',5);
		$module_id = Sanitize::getInt($passedArgs,'module_id');
		$rand = Sanitize::getFloat($passedArgs,'rand');

		$fields = array(
			'Community.id AS `User.user_id`',
			'User.name AS `User.name`',
			'User.username AS `User.username`'
		);
		$conditions = array(
			'Community.approved = 1',
			'Community.confirmed = 1'			
		);
		
		if($avatar) {
			$conditions[] = 'Community.avatar IS NOT NULL';
		}
		
		if($listing_id) {
			$conditions[] = 'Community.id in (SELECT user_id FROM #__jreviews_favorites WHERE content_id = ' . $listing_id . ')';
		}
		
		$order = array('RAND('.$rand.')');

		$joins = array('LEFT JOIN #__users AS User ON Community.id = User.id');
			
		$profiles = $this->findAll(array(
			'fields'=>$fields,
			'conditions'=>$conditions,
			'order'=>$order,
			'joins'=>$joins
		));
		
		if(Sanitize::getInt($passedArgs['module'],'ajax_nav',1)) {
			$fields = array('count(Community.id)');
			$group = array('Community.id');
			
			$this->count = $this->findCount(array(
				'fields'=>$fields,
				'conditions'=>$conditions,
				'group'=>$group,
				'joins'=>$joins
			));
		} else {
			$this->count = Sanitize::getInt($passedArgs['module'],'module_limit',5);
		}

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
		
		$Menu = registerClass::getInstance('MenuModel');

		$owner_ids = $this->__getOwnerIds($results, $modelName, $userKey);

		if(empty($owner_ids)) {
			return $results;
		}
		
		$profiles = $this->findAll(array(
			'fields'=>array('*'),
			'conditions'=>array('user_id IN (' . implode(',',$owner_ids) . ')'),		
		));

		$profiles = $this->changeKeys($profiles,$this->name,$this->realKey);

		$menu_id = $Menu->getComponentMenuId('com_comprofiler');

		# Add avatar_path to Model results
		foreach ($profiles AS $key=>$value) 
		{	
			if(false === strpos($key,'banned')) 
			{
				$profiles[$value[$this->name][$userKey]][$this->name]['avatar_path'] = '';

				if ($profiles[$value[$this->name][$userKey]][$this->name]['avatar'] != '' && $profiles[$value[$this->name][$userKey]][$this->name]['avatarapproved']) {
					if (file_exists(PATH_ROOT .'images' . DS . 'comprofiler' . DS . 'tn' . $profiles[$value[$this->name][$userKey]][$this->name]['avatar'] )) {
					    $profiles[$value[$this->name][$userKey]][$this->name]['avatar_path'] = WWW_ROOT. 'images' ._DS . 'comprofiler' . _DS . 'tn' . $profiles[$value[$this->name][$userKey]][$this->name]['avatar'];
					} elseif (file_exists(PATH_ROOT .'images' . DS . 'comprofiler' . DS . $profiles[$value[$this->name][$userKey]][$this->name]['avatar'] )) {
					    $profiles[$value[$this->name][$userKey]][$this->name]['avatar_path'] = WWW_ROOT. 'images' ._DS . 'comprofiler' . _DS . $profiles[$value[$this->name][$userKey]][$this->name]['avatar'];
					}
				}
			}
			
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