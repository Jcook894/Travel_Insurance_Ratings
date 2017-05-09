<?php
/**
 * S2Framework
 * Copyright (C) 2010-2015 ClickFWD LLC
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 * @license       http://www.opensource.org/licenses/mit-license.php The MIT License
**/


defined( 'MVC_FRAMEWORK') or die( 'Direct Access to this location is not allowed.' );

class S2Model extends S2ModelCore {

    var $insertid;

    function getVersion()
    {
        $db = cmsFramework::getDB();

        $version = $db->getVersion();

        $version = preg_replace('#[^0-9\.]#', '', $version);;

        return $version;
    }

    function makeSafe($text)
    {
        $db = cmsFramework::getDB();

        $dbResource = cmsFramework::getConnection($db);

        if(is_object($dbResource) && get_class($dbResource) == 'mysqli')
        {
            $text = mysqli_real_escape_string( $dbResource, $text );
        }
        else {

            $text = mysql_real_escape_string( $text, $dbResource );
        }

        return $text;
    }

    function getErrorMsg()
    {
        $db = cmsFramework::getDB();

        return $db->getErrorMsg();
    }

    function getQuery()
    {
        $db = cmsFramework::getDB();

        return $db->getQuery();
    }

    function query($query, $type = 'query', $param = '')
    {
        $db = cmsFramework::getDB();

        $message = array();

        $query and $db->setQuery($query);

        if($param != '') {

            $result = $db->{$type}($param);
        }
        else {

            $result = $db->{$type}();
        }

        // Debug

        if(defined('S2_DEBUG') && S2_DEBUG == 1) {

            $debug_query = $this->getQuery();

            s2Error::add($debug_query,'query');

            $debug_query_error = $this->getErrorMsg();

            $debug_query_error and s2Error::add($debug_query_error,'query_error');
        }

        $this->insertid = $db->insertid();

        return $result;
    }

    function insertid()
    {
        return $this->insertid;
    }
}
