<?php
/**
 * RapidLauncher Addon for JReviews
 * Copyright (C) 2010-2016 ClickFWD LLC
 * This is not free software, do not distribute it.
 * For licencing information visit https://www.jreviews.com
 * or contact sales@jreviews.com
**/

defined( 'MVC_FRAMEWORK') or die( 'Direct Access to this location is not allowed.' );

class RapidlauncherListingTypeModel extends MyModel  {

    var $name = 'ListingType';

    public function getListingTypeId($title)
    {
        $query = '
            SELECT id FROM #__jreviews_criteria WHERE title = ' . $this->Quote($title)
        ;

        $id = $this->query($query, 'loadResult');

        return (int) $id;
    }

    /**
     * Create a new listing type only if another one with the same title doesn't exist
     * @param  [type] $data [description]
     * @return [type]      [description]
     */
    public function create($data)
    {
        $values = array();

        foreach($data AS $key => $value)
        {
            $values[] = $this->Quote($value) . ' AS ' . $key;
        }

        $values = implode(',', $values);

        $query = '
            INSERT INTO
                #__jreviews_criteria (%s)
            SELECT * FROM (SELECT %s) AS tmp
            WHERE NOT EXISTS
                ( SELECT title
                    FROM #__jreviews_criteria
                  WHERE title = %s
                )
            LIMIT 1
        ';

        $query = sprintf($query, implode(',', array_keys($data)), $values, $this->Quote($data['title']));

        if($res = $this->query($query))
        {
            return $this->insertid;
        }

        return false;
    }

    public function getListingTypeFromDirectory($dirId)
    {
        $query = '
            SELECT
                DISTINCT criteriaid
            FROM
                #__jreviews_categories
            WHERE
                criteriaid > 0
                AND dirid IN ('.cleanIntegerCommaList($dirId).')
        ';

        return $this->query($query, 'loadColumn');
    }

    public function read($listingTypeId)
    {
        $output = [];

        $query = '
            SELECT
                DISTINCTROW ListingType.title,
                ListingType.config,
                ListingType.groupid,
                ListingType.state,
                ListingType.search
            FROM
                #__jreviews_criteria AS ListingType
            WHERE
                id IN ('.cleanIntegerCommaList($listingTypeId).')
        ';

        if($rows = $this->query($query, 'loadAssocList'))
        {
            $groupIds = [];

            foreach($rows AS $key => $row)
            {
                $rows[$key]['groupid'] = explode(',', $row['groupid']);

                $groupIds = array_merge($groupIds, $rows[$key]['groupid']);
            }

            $groupIds = array_unique($groupIds);

            $groups = $this->readGroups($groupIds);

            foreach($rows AS $key => $row)
            {
                $output[] = [
                    'ListingType' => $row['title'],
                    'Groups' => implode(',', array_intersect_key($groups, array_flip($row['groupid']))),
                    'Config' => $row['config'],
                    'State' => $row['state'],
                    'Search' => $row['search']
                ];
            }
        }

        return $output;
    }

    public function readGroups($groupIds)
    {
        $query = '
            SELECT
                groupid, title
            FROM
                #__jreviews_groups
            WHERE
                type = "content"
                AND groupid IN ('.cleanIntegerCommaList($groupIds).')
        ';

        $rows = $this->query($query, 'loadAssocList');

        $groups = [];

        foreach($rows AS $row)
        {
            $groups[$row['groupid']] = $row['title'];
        }

        return $groups;
    }
}