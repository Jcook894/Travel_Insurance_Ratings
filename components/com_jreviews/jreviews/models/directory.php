<?php
/**
 * JReviews - Reviews Extension
 * Copyright (C) 2010-2015 ClickFWD LLC
 * This is not free software, do not distribute it.
 * For licencing information visit https://www.jreviews.com
 * or contact sales@jreviews.com
**/

defined( 'MVC_FRAMEWORK') or die( 'Direct Access to this location is not allowed.' );

class DirectoryModel extends MyModel  {

    var $name = 'Directory';

    var $useTable = '#__jreviews_directories AS Directory';

    var $primaryKey = 'Directory.dir_id';

    var $realKey = 'id';

    var $fields = array(
        'Directory.id AS `Directory.dir_id`',
        'Directory.desc AS `Directory.title`',
        'Directory.title AS `Directory.name`',
        'Directory.tmpl_suffix AS `Directory.tmpl_suffix`'
    );

    function getList() {

        $query = "SELECT * from #__jreviews_directories order by id ASC";

        $rows = $this->query($query, 'loadObjectList');

        return $rows;
    }

    function getSelectList($dir_id = null) {

        $query = "
            SELECT
                Directory.id AS value, Directory.desc AS text
            FROM
                #__jreviews_directories AS Directory
            " . ($dir_id ? " WHERE id = " . $dir_id : '') . "
            ORDER BY
                Directory.title ASC"
        ;

        $results = $this->query($query,'loadObjectList');

        return $results;
    }

    function afterFind($results)
    {
        if (defined('MVC_FRAMEWORK_ADMIN') || empty($results)) {
            return $results;
        }

        # Add Menu ID info for each row (Itemid)
        $Menu = ClassRegistry::getClass('MenuModel');

        $results = $Menu->addMenuDirectory($results);

        return $results;

    }

    function afterSave($ret)
    {
        clearCache('','__data');

        clearCache('','views');
    }

}