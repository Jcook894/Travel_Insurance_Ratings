<?php
/**
 * RapidLauncher Addon for JReviews
 * Copyright (C) 2010-2016 ClickFWD LLC
 * This is not free software, do not distribute it.
 * For licencing information visit https://www.jreviews.com
 * or contact sales@jreviews.com
**/

defined( 'MVC_FRAMEWORK') or die( 'Direct Access to this location is not allowed.' );

S2App::import('Model', 'field', 'jreviews');

class RapidlauncherFieldModel extends MyModel  {

    var $name = 'Field';

    var $useTable = '#__jreviews_fields AS `Field`';

    var $primaryKey = 'Field.name';

    var $realKey = 'fieldid';

    var $fieldOptions;

    var $fields = [
        'Field.fieldid AS `Field.fieldid`',
        'Field.name AS `Field.name`',
        'Field.title AS `Field.title`',
        'Field.type AS `Field.type`',
        'Field.groupid AS `Field.groupid`',
        'FieldGroup.title AS `Field.group_title`'
        // 'Field.control_field AS `Field.control_field`',
        // 'Field.control_value AS `Field.control_value`'
    ];

    var $joins = [
        'LEFT JOIN #__jreviews_groups AS FieldGroup ON Field.groupid = FieldGroup.groupid'
    ];

    protected $fieldOptionTypes = [
        'select',
        'selectmultiple',
        'checkboxes',
        'radiobuttons'
    ];

    protected $fieldsList;

    function __construct()
    {
        $this->fieldModel = ClassRegistry::getClass('FieldModel');
    }

    public function setFields($fields)
    {
        $this->fieldsList = $fields;

        return $this;
    }

    public function getList($location = 'listing')
    {
        $location = $location == 'listing' ? 'content' : $location;

        $fields = $this->findAll(
            [
                'conditions' =>
                [
                    'Field.location = ' . $this->Quote($location),
                    'Field.type != "banner"'
                ],
                'order' => [
                    'Field.groupid, Field.ordering'
                ]
        ],
            $callbacks = []
        );

        return $fields;
    }

    public function isFieldOptionType($column)
    {
        return in_array($this->fieldsList[$column]['Field']['type'], $this->fieldOptionTypes);
    }

    public function isRelatedListing($column)
    {
        return $this->fieldsList[$column]['Field']['type'] == 'relatedlisting';
    }

    function getField($column)
    {
        return $this->fieldsList[$column]['Field'];
    }

    static function fieldName($title)
    {
        return 'jr_'.strtolower(Inflector::slug($title, ''));
    }

    /**
     * Create a new field only if another one with the same name slug doesn't exist
     * @param  [type] $data [description]
     * @return [type]      [description]
     */
    public function create($data)
    {
        $values = array();

        $data = array_filter($data);

        $data['name'] = isset($data['name']) ? $data['name'] : $this->fieldName($data['title']);

        $query = '
            INSERT IGNORE INTO #__jreviews_fields (%s) VALUES (%s)
        ';

        $query = sprintf($query, implode(',', array_keys($data)), $this->Quote($data));

        $this->query($query);

        if($this->insertid > 0)
        {
            $output = $this->fieldModel->addTableColumn($data, 'content');
        }
    }

    public function readBygroupId($id)
    {
        $query = '
            SELECT
                Field.title As `Title`,
                Field.name As `Name`,
                Group.title AS `Group`,
                Field.type AS `Type`,
                Field.required AS `Required`,
                Field.contentview AS `Detail View`,
                Field.listview AS `List View`,
                Field.compareview AS `Compare View`,
                Field.showtitle AS `Show Title`,
                Field.options AS `Options`
            FROM
                #__jreviews_fields AS Field
            LEFT JOIN
                #__jreviews_groups AS `Group` ON Group.groupid = Field.groupid
            WHERE
                Field.location = "content"
                AND Field.groupid IN ('.cleanIntegerCommaList($id).')
            ORDER BY
                Field.groupid, Field.ordering
        ';

        $rows = $this->query($query, 'loadAssocList');

        // Convert options from line breaks to json

        foreach ($rows AS $key => $row)
        {
            if($row['Options']{0} != '{')
            {
                $rows[$key]['Options'] = json_encode(stringToArray($row['Options']));
            }
        }

        return $rows;
    }
}