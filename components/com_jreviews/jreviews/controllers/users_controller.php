<?php
/**
 * JReviews - Reviews Extension
 * Copyright (C) 2010-2015 ClickFWD LLC
 * This is not free software, do not distribute it.
 * For licencing information visit https://www.jreviews.com
 * or contact sales@jreviews.com
**/

defined( 'MVC_FRAMEWORK') or die( 'Direct Access to this location is not allowed.' );

class UsersController extends MyController {

    var $uses = array('menu','user','registration');

    var $helpers = array('jreviews','routes');

    var $components = array('config','access');

    var $autoRender = false;

    var $autoLayout = false;

    function beforeFilter()
    {
        $this->Access->init($this->Config);

        # Call beforeFilter of MyController parent class
        parent::beforeFilter();
    }

    function _getUsername()
    {
        $user_id = Sanitize::getInt($this->params,'id');

        $query = '
            SELECT
                ' . UserModel::_USER_ALIAS . '
            FROM
                ' . UserModel::_USER_TABLE . '
            WHERE
                ' . UserModel::_USER_ID . ' = ' . $user_id
        ;

        $username = $this->User->query($query,'loadResult');

        return $username;
    }

    function _getList()
    {
        $limit = Sanitize::getInt($this->params,'limit',15);

        $q = mb_strtolower(Sanitize::getString($this->params,'q'),'utf-8');

        if (!$q) return '[]';

        $query = '
            SELECT
                ' . UserModel::_USER_ID . ' AS id,
                ' . UserModel::_USER_ALIAS . ' AS value,
                ' . UserModel::_USER_REALNAME . ' AS name,
                CONCAT(' . UserModel::_USER_ALIAS . ', " (", ' . UserModel::_USER_REALNAME . ' ,")") AS label,
                ' . UserModel::_USER_EMAIL . ' AS email
            FROM
                ' . UserModel::_USER_TABLE . '
            WHERE
                ' . UserModel::_USER_REALNAME . ' LIKE ' . $this->QuoteLike($q) . '
                OR
                ' . UserModel::_USER_ALIAS . ' LIKE ' . $this->QuoteLike($q) . '
            LIMIT ' . $limit
        ;

        $users = $this->User->query($query,'loadObjectList');

        return cmsFramework::jsonResponse($users);
    }

    function _validateUsername()
    {
        $username = Sanitize::getString($this->params,'value');

        if($username != '')
        {
            $count = $this->User->findCount(array(
                'conditions'=>array(
                    'User.' . UserModel::_USER_ALIAS . ' = ' . $this->Quote($username)
                ),
                'session_cache'=>false
            ));

            if($count == 0)
            {
                $success = true;

                $text = JreviewsLocale::getPHP('USERNAME_VALID');
            }
            else {

                $success = false;

                $text = JreviewsLocale::getPHP('USERNAME_INVALID');
            }

            return cmsFramework::jsonResponse(array('success'=>$success,'text'=>$text));
        }
    }

    function _validateEmail()
    {
        $email = Sanitize::getString($this->params,'value');

        $this->User->validateInput($email, "email", "email", 'VALIDATE_EMAIL', 1);

        if(!empty($this->User->validateErrors))
        {
            $success = false;

            $text = JreviewsLocale::getPHP('EMAIL_INVALID');

            return cmsFramework::jsonResponse(array('success'=>$success,'text'=>$text));
        }

        if($email != '')
        {
            $count = $this->User->findCount(array(
                'conditions'=>array(
                    'User.' . UserModel::_USER_EMAIL . ' = ' . $this->Quote($email)
                ),
                'session_cache'=>false
            ));

            if($count == 0)
            {
                $success = true;

                $text = JreviewsLocale::getPHP('EMAIL_VALID');
            }
            else {

                $success = false;

                $text = JreviewsLocale::getPHP('EMAIL_INVALID');
            }

            return cmsFramework::jsonResponse(array('success'=>$success,'text'=>$text));
        }
    }

    function _clearUserInfo()
    {
        $user = cmsFramework::getSessionVar('user', 'jreviews');

        if(!empty($user))
        {
            if($userId = Sanitize::getInt($user, 'user_id'))
            {
                $this->Registration->del($userId);
            }
        }

        cmsFramework::clearSessionVar('user','jreviews');

        cmsFramework::clearSessionVar('gid','jreviews');
    }
}
