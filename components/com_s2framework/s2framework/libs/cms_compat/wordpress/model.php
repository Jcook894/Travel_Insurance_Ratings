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

    function getVersion()
    {
        $db = cmsFramework::getDB();

        $version = $db->db_version();

        return $version;
    }

    function makeSafe($text)
    {
        $db = cmsFramework::getDB();

        $text = $db->_real_escape($text);

        return $text;
    }

    function getErrorMsg()
    {
        $db = cmsFramework::getDB();

        return $db->last_error;
    }

    function getQuery()
    {
        $db = cmsFramework::getDB();

        return $db->last_query;
    }

    function query($query, $type_conv = 'query', $index = '')
    {
        $db = cmsFramework::getDB();

        $param = '';

        switch($type_conv) {

            case 'loadResult':

                $type = 'get_var';

            break;

            case 'loadColumn':

                $type = 'get_col';

            break;

            case 'loadObject':

                $type = 'get_row';

                $param = OBJECT;

            break;

            case 'loadObjectList':

                $type = 'get_results';

                $param = OBJECT;

            break;

            case 'loadAssoc':

                $type = 'get_row';

                $param = ARRAY_A;

            break;

            case 'loadAssocList':

                $type = 'get_results';

                $param = ARRAY_A;

            break;

            default:

                $type = $type_conv;

            break;
        }

        // User table replacement is a fix for multi sites which use a single users table

        $query = str_replace(array('#__users','#__'),array($db->users,$db->prefix), $query);

        $message = array();

        if($param != '') {

            $result = $db->{$type}($query, $param);
        }
        else {

            $result = $db->{$type}($query);
        }

        // Debug

        if(defined('S2_DEBUG') && S2_DEBUG == 1) {

            $debug_query = $this->getQuery();

            s2Error::add($debug_query,'query');

            $debug_query_error = $this->getErrorMsg();

            $debug_query_error and s2Error::add($debug_query_error,'query_error');
        }

        switch($type_conv)
        {
            case 'loadObjectList':
            case 'loadAssocList':

                $result_copy = $result;

                $result = array();

                foreach($result_copy AS $key=>$row)
                {
                    $new_key = $index != '' ? Sanitize::getString($row,$index) : $key;

                    $result[$new_key] = $row;
                }

            break;

            case 'query':

                $this->insertid = $db->insert_id;

            break;
        }

        if($type == 'query')
        {
            if($result === 0) return true;
        }

        return $result;
    }

    function insertid()
    {
        $db = cmsFramework::getDB();

        return $db->insert_id;
    }
}
