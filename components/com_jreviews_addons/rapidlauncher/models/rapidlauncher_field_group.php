<?php
/**
 * RapidLauncher Addon for JReviews
 * Copyright (C) 2010-2016 ClickFWD LLC
 * This is not free software, do not distribute it.
 * For licencing information visit https://www.jreviews.com
 * or contact sales@jreviews.com
**/

defined( 'MVC_FRAMEWORK') or die( 'Direct Access to this location is not allowed.' );

class RapidlauncherFieldGroupModel extends MyModel  {

	var $name = 'Group';

	var $useTable = '#__jreviews_groups AS `Group`';

	var $primaryKey = 'Group.group_id';

	var $realKey = 'groupid';

	var $fields = array(
		'Group.groupid AS `Group.group_id`',
		'Group.name AS `Group.name`',
		'Group.title AS `Group.title`',
		'Group.type AS `Group.type`',
		'Group.ordering AS `Group.ordering`',
		'Group.showtitle AS `Group.showtitle`',
        'Group.control_field AS `Group.control_field`',
        'Group.control_value AS `Group.control_value`'
	);

    static function groupName($title)
    {
        return mb_strtolower(trim(str_replace(' ', '-', $title)));
    }

    public function getGroupId($title)
    {
        $names = array();

        $titles = is_array($title) ? $title : array($title);

        foreach($titles AS $key => $val)
        {
            $names[$key] = self::groupName($val);
        }

        $query = 'SELECT groupid FROM #__jreviews_groups WHERE name IN (' . $this->Quote($names) . ')';

        $ids = $this->query($query, 'loadColumn');

        if(is_array($title))
        {
            return $ids;
        }

        $id = array_shift($ids);

        return $id;
    }

    /**
     * Create a new group only if another group with the same group name slug doesn't exist
     * @param  [type] $data [description]
     * @return [type]      [description]
     */
    public function create($data)
    {
        $values = array();

        $data = array_filter($data);

        $name = $data['name'] = isset($data['name']) ? $data['name'] : self::groupName($data['title']);

        foreach($data AS $key => $value)
        {
            $values[] = $this->Quote($value) . ' AS ' . $key;
        }

        $values = implode(',', $values);

        $query = '
            INSERT INTO
                #__jreviews_groups (%s)
            SELECT * FROM (SELECT %s) AS tmp
            WHERE NOT EXISTS
                ( SELECT name
                    FROM #__jreviews_groups
                  WHERE name = %s
                )
            LIMIT 1
        ';

        $query = sprintf($query, implode(',', array_keys($data)), $values, $this->Quote($name));

        $this->query($query);
    }

    public function getGroupFromDirectory($id)
    {
        $query = '
            SELECT
                DISTINCT ListingType.groupid
            FROM
                #__jreviews_criteria AS ListingType
            RIGHT JOIN
                #__jreviews_categories AS JreviewsCategory ON JreviewsCategory.criteriaid = ListingType.id
            WHERE
                JreviewsCategory.dirid IN (' . cleanIntegerCommaList($id) . ')
                AND JreviewsCategory.option = "com_content"
                AND ListingType.groupid <> ""
        ';

        if($groupIds = $this->query($query, 'loadColumn'))
        {
            $groupIds = array_unique(explode(',', implode(',', $groupIds)));
        }

        return $groupIds;
    }

    public function read($groupIds)
    {
        $query = '
            SELECT
                title AS `Title`,
                IF(type = "content","listing",type) AS `Type`,
                showtitle AS `Show Title`
            FROM
                ' . $this->useTable . '

            WHERE
                groupid IN (' . cleanIntegerCommaList($groupIds) . ')
            ORDER BY
                Group.ordering
        ';

        $rows = $this->query($query, 'loadAssocList');

        return $rows;
    }
}
