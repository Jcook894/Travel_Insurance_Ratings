<?php
/**
 * JReviews - Reviews Extension
 * Copyright (C) 2009 ClickFWD LLC
 * This is not free software, do not distribute it.
 * For licencing information visit http://www.reviewsforjoomla.com
 * or contact sales@reviewsforjoomla.com
**/

defined( 'MVC_FRAMEWORK') or die( 'Direct Access to this location is not allowed.' );

class UsersController extends MyController {
        
    var $uses = array('menu','user');
    
    var $helpers = array();
    
    var $components = array('config','access');

    var $autoRender = false;
    
    var $autoLayout = false;
                
    function beforeFilter() 
    {        
        # Call beforeFilter of MyController parent class
        parent::beforeFilter();
    }    
    
    function _getList()
    {      
        $this->Access->init($this->Config);

        if(!$this->_user->id || !$this->Access->isEditor()) return '[]';

        $q = $this->User->makeSafe(mb_strtolower(Sanitize::getString($this->data,'value'),'utf-8'));

        if (!$q) return '[]';

        $query = "
            SELECT 
                id AS id, username AS value, name AS name, CONCAT(username,' (',name,')') AS label, email
            FROM 
                #__users
            WHERE 
                name LIKE " . $this->quoteLike($q) . " 
                OR 
                username LIKE " . $this->quoteLike($q) . " 
            LIMIT 15
        ";
        
        $this->_db->setQuery($query);
        $users = $this->_db->loadObjectList();
        return json_encode($users);
    }    
}
