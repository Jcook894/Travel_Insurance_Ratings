<?php
/**
 * RapidLauncher Addon for JReviews
 * Copyright (C) 2010-2016 ClickFWD LLC
 * This is not free software, do not distribute it.
 * For licencing information visit https://www.jreviews.com
 * or contact sales@jreviews.com
**/

defined( 'MVC_FRAMEWORK') or die( 'Direct Access to this location is not allowed.' );

class RapidlauncherFieldOptionModel extends MyModel  {

    var $name = 'FieldOption';

    var $useTable = '#__jreviews_fieldoptions AS `FieldOption`';

    var $primaryKey = 'FieldOption.optionid';

    var $realKey = 'optionid';

    var $fields = [
        'FieldOption.optionid AS `FieldOption.optionid`',
        'FieldOption.fieldid AS `FieldOption.fieldid`',
        'FieldOption.text AS `FieldOption.text`',
        'FieldOption.value AS `FieldOption.value`',
        'FieldOption.image AS `FieldOption.image`',
        'FieldOption.ordering AS `FieldOption.ordering`',
        'FieldOption.control_field AS `FieldOption.control_field`',
        'FieldOption.control_value AS `FieldOption.control_value`',
    ];

    /**
     * These are characters that will be removed from the field option value
     *
     * @var array
     */
    var $blackList = ['=','|','!','$','%','^','°','_','&','(',')','*',';',':','@','#','+','<','>','.',',','/','\\'];
    /**
     * The values in the array will be replaced with a dash "-"
     *
     * @var array
     */
    var $dashReplacements = [' ','_',',','"',"'"];

    /**
     * Array of custom fields to lookup field info for the current options
     * @var array
     */
    var $fieldsList;

    var $formatTable = [
        'text'              => 'saveFormatText',
        'textarea'          => 'saveFormatText',
        'code'              => 'saveFormatText',
        'email'             => 'saveFormatText',
        'website'           => 'saveFormatText',
        'date'              => 'saveFormatDate',
        'decimal'           => 'saveFormatNumeric',
        'integer'           => 'saveFormatNumeric',
        'select'            => 'saveFormatSingleOption',
        'radiobuttons'      => 'saveFormatSingleOption',
        'selectmultiple'    => 'saveFormatMultipleOption',
        'checkboxes'        => 'saveFormatMultipleOption',
        'relatedlisting'    => 'saveFormatMultipleOption'
    ];

        /**
     * Array of key/values to look up field relations
     * @var array
     */
    var $relations;

    var $newOptionIds = [];

    var $processed = [];

    function reset()
    {
        $this->processed = [];

        return $this;
    }

    function relations(array $relations)
    {
        $this->relations = $relations;

        return $this;
    }

    function setFields(array $fields)
    {
        $this->fieldsList = $fields;

        return $this;
    }

    function import($column, $options)
    {
        $results = [];

        $newOptions = [];

        $optionsText = [];

        $controlFieldName = '';

        $controlFieldValues = [];

        if(isset($this->processed[$column])) return $this->processed[$column];
        // Check for relationships

        $options = $this->parseFieldOptions($options);

        foreach($options AS $option)
        {
            $parts = explode('|', $option);

            $optionsText[] = trim($parts[0]);

            if($controlFieldName = Sanitize::getString($parts, 1))
            {
                if($controlOptions = Sanitize::getString($this->relations, $controlFieldName))
                {
                    // Run the import again to get the control values and leave!

                    $controlFieldValues = array_merge($controlFieldValues, $this->import($controlFieldName, $controlOptions));
                }
            }
        }

        $field = $this->getField($column);

        $fieldOptions = $this->findAll(
            [
                'conditions' => [
                    'FieldOption.fieldid = ' . (int) $field['fieldid'],
                    'FieldOption.text IN (' . $this->Quote($optionsText) . ')'
                ]
            ],
            $callbacks = []
        );

        foreach($optionsText AS $text)
        {
            if($option = $this->findMatch($text, $fieldOptions))
            {
                $results[] = $option['FieldOption'];
            }
            else {

                $newOptions[] = $text;
            }
        }

        // Aumatically insert new options to the database

        $newOptions = array_filter($newOptions);

        if(!empty($newOptions))
        {
            $maxOrdering = $this->getMaxOrdering($field['fieldid']);
        }

        foreach($newOptions AS $text)
        {
            $value = $this->makeValue($text);

            $data = [
                'FieldOption' =>  [
                    'fieldid' => $field['fieldid'],
                    'text' => $text,
                    'value' => $value,
                    'ordering' => $maxOrdering++,
                    'control_field' => $controlFieldName,
                    'control_value' => $this->saveFormat($controlFieldName, $this->valueSeparatedList($controlFieldValues))
                ]
            ];

            if($this->store($data))
            {
                $optionValue = $this->data['FieldOption'];

                $this->newOptionIds[] = $optionValue['optionid'];

                $results[] = $optionValue;
            }
        }

        $this->processed[$column] = $results;

        return $results;
    }

    function getControlFields($fieldId, array $values)
    {
        $results = [];

        $fieldOptions = $this->findAll(
            [
                'conditions' => [
                    'FieldOption.fieldid = ' . (int) $fieldId,
                    'FieldOption.value IN (' . $this->Quote($values) . ')'
                ]
            ],
            $callbacks = []
        );

        foreach($fieldOptions AS $option)
        {
            $option = $option['FieldOption'];

            $results[] =
                [
                    'value' => $option['value'],
                    'control_field' => $option['control_field'],
                    'control_value' => $option['control_value']
                ];
        }

        return $results;
    }

    function valueSeparatedList(array $options)
    {
        $optionValues = array_map(function($row) {
            return $row['value'];
        }, $options);

        $optionValues = implode(RAPIDLAUNCHER_ADDON_OPTION_SEPARATOR, $optionValues);

        return $optionValues;
    }

    function textSeparatedList(array $options)
    {
        $optionText = array_map(function($row) {
            return $row['text'];
        }, $options);

        $optionText = implode(RAPIDLAUNCHER_ADDON_OPTION_SEPARATOR, $optionText);

        return $optionText;
    }

    /**
     * Takes the comma separated list of field options and converts it into an array
     * @param  string  $options [description]
     * @return array          [description]
     */
    function parseFieldOptions($optionList)
    {
        $optionList = str_replace('\\' . RAPIDLAUNCHER_ADDON_OPTION_SEPARATOR, '_SEPARATOR_', $optionList);

        $options = explode(RAPIDLAUNCHER_ADDON_OPTION_SEPARATOR, $optionList);

        foreach($options AS $key => $option)
        {
            $options[$key] = trim(str_replace('_SEPARATOR_', RAPIDLAUNCHER_ADDON_OPTION_SEPARATOR, $option));
        }

        return $options;
    }

    function parseFieldOptionOuput($option)
    {
        return str_replace(RAPIDLAUNCHER_ADDON_OPTION_SEPARATOR, '\\' . RAPIDLAUNCHER_ADDON_OPTION_SEPARATOR, $option);
    }

    protected function getField($column)
    {
        return $this->fieldsList[$column]['Field'];
    }

    function getNewOptionIds()
    {
        return $this->newOptionIds;
    }

    protected function getMaxOrdering($fieldId)
    {
        $query = "
            SELECT
                max(ordering)
            FROM
                #__jreviews_fieldoptions
            WHERE
                fieldid = " . (int) $fieldId;

        $max = $this->query($query,'loadResult');

        return $max > 0 ? $max : 1;
    }

    protected function findMatch($title, array $options)
    {
        foreach($options AS $option)
        {
            if($option['FieldOption']['text'] == $title)
            {
                return $option;
            }
        }

        return false;
    }

    protected function makeValue($text)
    {
        $value = html_entity_decode(urldecode($text),ENT_QUOTES,'utf-8');

        $value = str_replace($this->blackList,'',$value);

        $value = str_replace($this->dashReplacements,'-',$value);

        $value = preg_replace(array('/[-]+/'), array('-'), $value);

        $value = rtrim(ltrim($value,'-'),'-');

        $value = mb_strtolower($value,'UTF-8');

        if($value == '')
        {
            $value = strlen($text);
        }

        return $value;
    }

    function saveFormat($name, $value)
    {
        if(!in_array($name, array_keys($this->fieldsList)))
        {
            return $value;
        }

        $fieldType = $this->fieldsList[$name]['Field']['type'];

        $formatMethod = $this->formatTable[$fieldType];

        return $this->{$formatMethod}($value);
    }

    function saveFormatDate($value)
    {
        return $value;
    }

    function saveFormatText($value)
    {
        return $value;
    }

    function saveFormatNumeric($value)
    {
        if($value == '')
        {
            $value = NULL;
        }

        return $this->saveFormatText($value);
    }

    function saveFormatSingleOption($value)
    {
        if($value == '') return '';

        return '*' . $value . '*';
    }

    function saveFormatMultipleOption($value)
    {
        if($value == '') return '';

        $options = explode(RAPIDLAUNCHER_ADDON_OPTION_SEPARATOR, $value);

        return '*' . implode('*', $options) . '*';
    }
}