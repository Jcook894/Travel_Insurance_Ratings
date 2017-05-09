<?php
/**
 * RapidLauncher Addon for JReviews
 * Copyright (C) 2010-2016 ClickFWD LLC
 * This is not free software, do not distribute it.
 * For licencing information visit https://www.jreviews.com
 * or contact sales@jreviews.com
**/

defined( 'MVC_FRAMEWORK') or die( 'Direct Access to this location is not allowed.' );

class RapidlauncherDirectoryModel extends MyModel  {

    var $name = 'Directory';

    public function getDirectoryId($title)
    {
        $query = '
            SELECT id FROM #__jreviews_directories WHERE `desc` = ' . $this->Quote($title)
        ;

        $id = $this->query($query, 'loadResult');

        return (int) $id;
    }

    static function directoryName($title)
    {
        return mb_strtolower(trim(str_replace(' ', '-', $title)));
    }

    /**
     * Create a new directory only if another one with the same title doesn't exist
     * @param  [type] $data [description]
     * @return [type]      [description]
     */
    public function create($data)
    {
        $values = array();

        $data = array_filter($data);

        $data['title'] = isset($data['title']) ? $data['title'] : self::directoryName($data['desc']);

        foreach($data AS $key => $value)
        {
            $values[] = $this->Quote($value) . ' AS `' . $key . '`';
        }

        $values = implode(',', $values);

        $query = '
            INSERT INTO
                #__jreviews_directories (%s)
            SELECT * FROM (SELECT %s) AS tmp
            WHERE NOT EXISTS
                ( SELECT `desc`
                    FROM #__jreviews_directories
                  WHERE `desc` = %s
                )
            LIMIT 1
        ';

        $query = sprintf($query, '`'.implode('`,`',array_keys($data)).'`', $values, $this->Quote($data['desc']));

        if($this->query($query))
        {
            return $this->insertid;
        }

        return false;
    }

    public function read($dirId)
    {
        $query = '
            SELECT
                `desc` AS `Title`
            FROM
                #__jreviews_directories
            WHERE
                id IN (' . cleanIntegerCommaList($dirId) . ')
            ORDER BY
                id
        ';

        $rows = $this->query($query, 'loadAssocList');

        return $rows;
    }
}