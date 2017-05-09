<?php
/**
 * JReviews - Reviews Extension
 * Copyright (C) 2010-2015 ClickFWD LLC
 * This is not free software, do not distribute it.
 * For licencing information visit https://www.jreviews.com
 * or contact sales@jreviews.com
**/

defined( 'MVC_FRAMEWORK') or die( 'Direct Access to this location is not allowed.' );

switch(_CMS_NAME)
{
    /**********************************
     *          Joomla                *
     **********************************/

	case 'joomla':

		class UserModel extends MyModel  {

			const _USER_TABLE = '#__users';

			const _USER_ID = 'id';

			const _USER_REALNAME = 'name';

			const _USER_ALIAS = 'username';

			const _USER_EMAIL = 'email';

			const _USER_PASSWORD = 'password';

			const _USER_BLOCKED = 'block';

			var $name = 'User';

			var $useTable = '#__users AS `User`';

			var $primaryKey = 'User.id';

			var $realKey = 'id';

			var $fields = array('*');
		}

		break;

    /**********************************
     *          Wordpress             *
     **********************************/

	case 'wordpress':

		class UserModel extends MyModel  {

			const _USER_TABLE = '#__users';

			const _USER_ID = 'ID';

			const _USER_REALNAME = 'display_name';

			const _USER_ALIAS = 'user_nicename';

			const _USER_EMAIL = 'user_email';

			const _USER_PASSWORD = 'user_pass';

			const _USER_BLOCKED = 'user_status';

			var $name = 'User';

			var $useTable = '#__users AS `User`';

			var $primaryKey = 'User.ID';

			var $realKey = 'ID';

			var $fields = array('*');
		}

		break;
}