<?php
/**
 * JReviews - Reviews Extension
 * Copyright (C) 2010-2015 ClickFWD LLC
 * This is not free software, do not distribute it.
 * For licencing information visit https://www.jreviews.com
 * or contact sales@jreviews.com
**/

defined( 'MVC_FRAMEWORK') or die( 'Direct Access to this location is not allowed.' );

class FieldModel extends MyModel  {

    var $name = 'Field';

    var $useTable = '#__jreviews_fields AS `Field`';

    var $primaryKey = 'Field.fieldid';

    var $realKey = 'fieldid';

    var $fieldOptions;

    var $fields = array(
        'Field.fieldid AS `Field.fieldid`',
        'Field.name AS `Field.name`',
        'Field.title AS `Field.title`',
        'Field.type AS `Field.type`',
        'Field.required AS `Field.required`',
        'Field.access AS `Field.access`',
        'Field.access_view AS `Field.access_view`',
        'Field.options AS `Field.options`',
        'Field.showtitle AS `Field.showtitle`',
        'Field.description AS `Field.description`',
        'Field.groupid AS `Field.groupid`',
        'Field.location AS `Field.location`',
        'Field.size AS `Field.size`',
        'Field.maxlength AS `Field.maxlength`',
        'Field.cols AS `Field.cols`',
        'Field.rows AS `Field.rows`',
        'Field.contentview AS `Field.contentview`',
        'Field.ordering AS `Field.ordering`',
        'Field.listview AS `Field.listview`',
        'Field.compareview AS `Field.compareview`',
        'Field.listsort AS `Field.listsort`',
        'Field.search AS `Field.search`',
        'Field.published AS `Field.published`',
        'Field.control_field AS `Field.control_field`',
        'Field.control_value AS `Field.control_value`'
    );

    var $_LISTING_TYPE_GROUP_IDS = array('content'=>array(),'review'=>array());

    function afterFind($results)
    {
        if(!is_array($results)) {
            return $results;
        }

        foreach($results AS $key=>$result)
        {
            # Convert field options into _params array
            if(!isset($result['Field']['options']))
            {
                $results[$key]['Field']['_params'] = array();
            }
            else {
                $results[$key]['Field']['_params'] = stringToArray($result['Field']['options']);
            }

            # Process control field values

            $results[$key]['ControlValues'] = array();

            if(isset($result['Field']['control_value']) && $result['Field']['control_value'] != '')
            {
                $results[$key]['Field']['control_value'] = explode('*',rtrim(ltrim($result['Field']['control_value'],'*'),'*'));

                $query = "
                    SELECT
                        Field.fieldid,value,text
                    FROM
                        #__jreviews_fieldoptions AS FieldOption
                    LEFT JOIN
                        #__jreviews_fields AS Field ON FieldOption.fieldid = Field.fieldid
                    WHERE
                        Field.name = " . $this->Quote($result['Field']['control_field']) . "
                         AND FieldOption.value IN (". $this->Quote($results[$key]['Field']['control_value']) .")"
                ;

                $results[$key]['ControlValues'] = $this->query($query, 'loadAssocList');
            }
        }

        return $results;
    }

    /***********************************************************************
    * Process control data when creating/editing field via administration
    * @param mixed $data
    ***********************************************************************/
    function beforeSave(&$data)
    {
        // Convert Control Value array to string
        if(isset($data['Field']['control_value']))
        {
            $control_value = Sanitize::getVar($data['Field'],'control_value');
            $data['Field']['control_value'] = !empty($control_value) ? '*'.implode('*',$control_value).'*' : '';
        }
        else {
            $data['Field']['control_field'] = '';
        }
    }

    /**
     * Returns a breadcrumb style array based on field option to field option relations
     */
    function getBreadCrumbs(&$crumbs, $field_id, $value)
    {
        $query = "
            SELECT
                Field.name AS field,
                ControlField.fieldid AS control_field_id,
                FieldOption.fieldid,
                FieldOption.value,
                FieldOption.text,
                FieldOption.control_field,
                FieldOption.control_value
            FROM
                #__jreviews_fieldoptions AS FieldOption
            LEFT JOIN
                #__jreviews_fields AS Field ON FieldOption.fieldid = Field.fieldid
            LEFT JOIN
                #__jreviews_fields AS ControlField ON FieldOption.control_field = ControlField.name
            WHERE
                FieldOption.fieldid = " . (int) $field_id . "
                AND FieldOption.value =  " . $this->Quote(str_replace('*','',$value))
        ;

        $option = $this->query($query, 'loadAssoc');

        if(!empty($option))
        {
            $option = array_filter($option);

        if($option)
        {
            $crumbs[] = $option;

            $control_field_id = Sanitize::getInt($option, 'control_field_id');

            $control_value = Sanitize::getString($option, 'control_value');

            if($control_field_id > 0 && $control_value != '')
            {
                $option['field'] = str_replace('jr_','', $option['field']);

                    $this->getBreadCrumbs($crumbs, $control_field_id, $control_value);
                }
            }
        }
    }

    function getList($filters, $limitstart, $limit, &$total)
    {
        $type = Sanitize::getString($filters,'type');

        $group_id = Sanitize::getInt($filters,'group_id');

        $location = Sanitize::getString($filters,'location');

        $title = Sanitize::getString($filters,'title');

        // get the total number of records
        $query = "
            SELECT
                count(*)
            FROM
                #__jreviews_fields AS Field
            INNER JOIN
                #__jreviews_groups AS `Group` ON `Group`.groupid = Field.groupid
            WHERE
                Field.location = " . $this->Quote($location)
            . ($group_id > 0 ? " AND Field.groupid = $group_id" : '')
            . ($type != '' ? " AND Field.type = " . $this->Quote($type) : '')
            . ($title != '' ? " AND (Field.title LIKE " . $this->QuoteLike($title) . " OR Field.name LIKE " . $this->QuoteLike($title) . ")": '')
        ;

        $total = $this->query($query, 'loadResult');

        $query = "
            SELECT
                Field.fieldid, Field.groupid, Field.name, Field.title, Field.showtitle, Field.required, Field.type,
                Field.ordering, Field.contentview, Field.listview, Field.listsort, Field.compareview, Field.search,
                Field.control_field, Field.control_value, Field.published, Field.metatitle, Field.metakey, Field.metadesc, Field.maxlength,
                `Group`.title AS group_title, `Group`.name AS group_name
            FROM
                #__jreviews_fields AS Field
            INNER
                JOIN #__jreviews_groups AS `Group` on `Group`.groupid = Field.groupid
            WHERE
                Field.location = " . $this->Quote($location)
                . ($group_id > 0 ? " AND Field.groupid = " . $group_id : '')
                . (!empty($type) ? " AND Field.type = " . $this->Quote($type) : '')
                . ($title != '' ? " AND (Field.title LIKE " . $this->QuoteLike($title) . " OR Field.name LIKE " . $this->QuoteLike($title) . ")": '') . "
            ORDER
                BY Field.groupid, Field.ordering
            LIMIT
                {$limitstart},{$limit}"
        ;

        $fields = $this->query($query, 'loadObjectList');

        if($location == 'content') $location = 'listing';

        $indexes = $this->getIndexes($location);

        foreach($fields AS $key=>$field)
        {
            $fields[$key]->indexed = in_array(Sanitize::getString($field,'name'), $indexes);
        }

        return $fields;
    }

    /**
    * Returns an array of fields that can be used as control fields in forms
    */
    function getControlList($location, $fieldq = '', $fieldid = false)
    {
        if($fieldid > 0 && $fieldq != '') $fieldid = false;

        $query = "
            SELECT
                fieldid AS id, name AS value, CONCAT(name,' ( ',title,' )') AS label
            FROM
                #__jreviews_fields
            WHERE
                location = " . $this->Quote($location) . "
                " .
                ($fieldq != '' ?
                    " AND (name LIKE " . $this->QuoteLike($fieldq) . ' OR title LIKE ' . $this->QuoteLike($fieldq) . ')' : ''
                ) .
                ($fieldid && $fieldid != 'undefined' ?
                    " AND fieldid <> {$fieldid}" : ''
                ) . "
                AND type IN ('select','selectmultiple','checkboxes','radiobuttons')
            ORDER BY title
            LIMIT 15
        ";

        return $this->query($query, 'loadObjectList');
    }

    /**
     * Returns the list of current indexes on the table
     */
    function getIndexes($location = 'listing')
    {
        $table = $location == 'listing' ? 'jreviews_content' : 'jreviews_review_fields';

        $cache_file = S2CacheKey('jreviews_fields_index_' . $location);

        if($cache = S2Cache::read($cache_file,'_s2framework_core')){
            return $cache;
        }

        $db_name = cmsFramework::getConfig('db');

        $db_prefix = cmsFramework::getConfig('dbprefix');

        $query = "
            SELECT
                index_name
            FROM
                information_schema.statistics
            WHERE
                TABLE_SCHEMA='". $db_name . "'
                AND TABLE_NAME='" . $db_prefix . $table . "'
            ";

        $indexes = $this->query($query,'loadColumn');

        S2Cache::write($cache_file, $indexes, '_s2framework_core');

        return $indexes;
    }

    function toggleIndex($name)
    {
        $listing_fields = $this->getFieldNames('listing');

        $location = in_array($name, $listing_fields) ? 'listing' : 'review';

        $table = $location == 'listing' ? '#__jreviews_content' : '#__jreviews_review_fields';

        $indexes = $this->getIndexes($location);

        if(in_array($name, $indexes))
        {
            $query = 'DROP INDEX `'.$name.'` ON ' . $table;

            $this->query($query);

            $state = 0;
        }
        else {

            $query = "ALTER TABLE " . $table . " ADD INDEX  `".$name."` ( `". $name ."` )";

            $this->query($query);

            $state = 1;
        }

        clearCache(S2CacheKey('jreviews_fields_index_' . $location), 'core', '');

        return $state;
    }

    function getFieldNames($location = 'listing', $options = array())
    {
        // Get names of custom fields to eliminate queries on non-existent fields

        $cache_file = s2CacheKey('custom_field_names_' . $location, $options);

        if(!$columns = S2Cache::read($cache_file,'core'))
        {
            $Model = new S2Model;

            if($location == 'listing')
            {
                $table = $Model->getTableColumns('#__jreviews_content');
            }
            else {

                $table = $Model->getTableColumns('#__jreviews_review_fields');
            }

            $where = array();

            $columns = array_keys($table);

            if(Sanitize::getBool($options,'published'))
            {
                $where[] = 'published = 1';
            }

            if(Sanitize::getString($options,'type'))
            {
                $where[] = 'type = ' . $this->Quote($options['type']);
            }

            if(!empty($where))
            {
                $query = '
                    SELECT
                        name
                    FROM
                        #__jreviews_fields
                    WHERE
                        name IN (' . $this->Quote($columns) . ')
                    AND ' . implode(' AND ', $where)
                ;

                $columns = $this->query($query,'loadColumn');
            }

            S2Cache::write($cache_file, $columns, 'core');
        }

        return $columns;
    }

    /**
     * Used for deleting of new fields in administration
     */
    function deleteTableColumn($fields, $location) {

        $output = array();

        if ($location == 'content') {
            foreach ($fields as $field)
            {
                if($field['type'] == 'banner') continue;

                $query = "ALTER TABLE #__jreviews_content DROP " . $field['name'];

                if (!$this->query($query))
                {

                    $output[] = "It was not possible to delete the field " . $field['name'] . " from the #__jreviews_content table.";

                }
            }
        }
        elseif ($location == 'review' ) {

            foreach ($fields as $field)
            {
                if($field['type'] == 'banner') continue;


                $query = "ALTER TABLE #__jreviews_review_fields DROP ". $field['name'];

                if (!$this->query($query))
                {

                    $output[] = "It was not possible to delete the field " . $field['name'] . " from the #__jreviews_review_fields table.";

                }
            }
        }

        if (count($output)>0) return implode("\n",$output); else return true;
    }

    /**
     * Used for creation of new fields in administration
     */
    function addTableColumn ($field, $location)
    {
        $Model = new MyModel;

        $output = $dbtype = '';

        $null = 'NOT NULL';

        $name = $field['name'];

        $type = $field['type'];

        $maxlength = Sanitize::getInt($field,'maxlength',255);

        $maxlength == 0 and $maxlength = 255;

        switch ($type)
        {
            case 'text':
                $dbtype = "VARCHAR($maxlength)";
                break;

            case 'website':
            case 'email':
                $dbtype = "VARCHAR(255)";
                break;

            case 'select':
            case 'radiobutton':
            case 'selectmultiple':
            case 'checkboxes':

                $db_version = $Model->getVersion();

                if(version_compare($db_version, '5.0.3', '>='))
                {
                    $dbtype = "VARCHAR($maxlength)";
                }
                else {

                    $dbtype = "TEXT";
                }

                break;

            case 'textarea':
            case 'code':
            case 'formbuilder':
                $dbtype = "TEXT";
                break;

            case 'integer':
                $dbtype = "INT(13)";
                $null = '';
                break;

            case 'relatedlisting':
                $dbtype = "VARCHAR(255)";
                break;

            case 'decimal':
                $dbtype = "DECIMAL(20,7)";
                $null = '';
                break;

            case 'date':
                $dbtype = "DATETIME";
                break;

            case 'banner':
                // This is not an input fields, just output
            break;

        default:
                $dbtype = "VARCHAR(255)";
            break;
        }

        if($dbtype == '') return '';

        if ($location == 'content')
        {
            $query = "ALTER TABLE #__jreviews_content ADD $name $dbtype " . $null;
        }
        elseif ($location == 'review') {

            $query = "ALTER TABLE #__jreviews_review_fields ADD $name $dbtype " . $null;

        }

        if (!$this->query($query))
        {

            $output = "It was not possible to add the new field to the #__jreviews_content table so we could not create the field.";

        }

        return $output;

    }

    function modifyTableColumn($field, $maxlength = 255)
    {
        $old_maxlength = $field['Field']['maxlength'];

        if($old_maxlength == $maxlength) return true;

        $table = $field['Field']['location'] == 'content' ? '#__jreviews_content' : '#__jreviews_review_fields';

        $fname = $field['Field']['name'];

        $query = "
            ALTER TABLE $table CHANGE COLUMN {$fname} {$fname} VARCHAR({$maxlength}) NOT NULL;
        ";

        if($this->query($query,'query')) {

            $field['Field']['maxlength'] = $maxlength;

            if($this->store($field)) {

                return true;
            }
        }

        return false;
    }

    function getMaxDataLength($fname, $location)
    {
        $table = $location == 'content' ? '#__jreviews_content' : '#__jreviews_review_fields';

        if(is_array($fname))
        {
            foreach($fname AS $name)
            {
                $fields[] = "MAX(CHAR_LENGTH(" . $name . ")) AS " . $name;
            }
        }
        else {

            $fields[] = "MAX(CHAR_LENGTH(" . $fname . ")) AS " . $fname;
        }

        $query = "
            SELECT " . implode(',', $fields) . " FROM {$table}
        ";

        $maxlength = $this->query($query,'loadAssoc');

        return $maxlength;
    }

    function tableIntegrityCheck($location) {

        $dbprefix = cmsFramework::getConfig('dbprefix');

        $table = $location == 'content' ? $dbprefix.'jreviews_content' : $dbprefix.'jreviews_review_fields';

        $query = "
            SELECT
                count(name)
              FROM
                #__jreviews_fields
              WHERE
                location = '{$location}' AND type <> 'banner' AND name NOT IN (
                    SELECT
                        column_name
                    FROM
                        information_schema.columns
                    WHERE
                        table_name = '{$table}'
                )
        ";

        $count = $this->query($query,'loadResult');

        return $count == 0 ? true : false;
    }

    ################## CHECK WHICH FUNCTIONS STAY AND WHICH GO

    function _criteria2Groups($listing_type_ids, $type)
    {
        if($type == 'listing') $type = 'content';

        $out = array();

        if (!is_array($listing_type_ids) && $listing_type_ids != '') {

            $listing_type_ids = array($listing_type_ids);
        }

        // Category without a listing type

        if (!$listing_type_ids)
        {
            return $out;
        }

        $listing_type_ids = array_unique($listing_type_ids);

        if(!$listing_type_ids)
        {
            return $out;
        }

        $listing_type_ids = cleanIntegerCommaList($listing_type_ids);

        if(isset($this->_LISTING_TYPE_GROUP_IDS[$type][$listing_type_ids]))
        {
            return $this->_LISTING_TYPE_GROUP_IDS[$type][$listing_type_ids];
        }

        $ListingTypeModel = ClassRegistry::getClass('CriteriaModel');

        $listingTypes = $ListingTypeModel->findAll(array(
                'conditions'=>array('Criteria.id IN ('.$listing_type_ids.')')
        ),array());

        if(!$listingTypes || empty($listingTypes))
        {
            $this->_LISTING_TYPE_GROUP_IDS[$type][$listing_type_ids] = array();

            return $this->_LISTING_TYPE_GROUP_IDS[$type][$listing_type_ids];
        }

        $out = array();

        foreach($listingTypes AS $listingType)
        {
            if($listingType['Criteria']['group_id'] != '')
            {
                $out = array_merge($out, explode(',',$listingType['Criteria']['group_id']));
            }
        }

        if(!empty($out))
        {
            //now leave only the group ids for the current type
            $query = "
                SELECT
                    groupid
                FROM #__jreviews_groups
                WHERE
                    groupid IN (" . cleanIntegerCommaList($out) . ")
                    AND type = " . $this->Quote($type)
            ;

            $out = $this->query($query, 'loadColumn');
        }

        $this->_LISTING_TYPE_GROUP_IDS[$type][$listing_type_ids] = $out;

        return $out;
    }

    protected function extractFieldIds($fields, $field_types = array())
    {
        $field_ids = array();

        foreach ($fields AS $field)
        {
            $field = (array) $field;

            if(in_array($field['Field.type'],$field_types))
            {
                $field_ids[] = $field['Field.id'];
            }
        }

        return $field_ids;

    }

    protected function getFieldOptions($fieldIds, $optionValues = null)
    {
        if (!empty($fieldIds))
        {
            // Get the field options for all multiple choice fields ordered by a-z ASC

            $query = "
                SELECT
                    FieldValue.optionid, FieldValue.fieldid, FieldValue.text, FieldValue.value, FieldValue.image, FieldValue.ordering
                FROM
                    #__jreviews_fieldoptions AS FieldValue
                WHERE
                    FieldValue.fieldid IN (".implode(',',$fieldIds).")
                    " . ($optionValues ?
                        "AND FieldValue.value IN (" . $this->Quote($optionValues) . ")"
                        :
                        '')
                     . "
                ORDER
                    BY FieldValue.text ASC"
            ;

            $rows = $this->query($query, 'loadObjectList');

            $fieldValues = array();

            $fieldOrdering = array();

            foreach ($rows AS $row)
            {
                $this->field_options_alpha[$row->fieldid][$row->value] =
                    array(
                        'optionid'=>$row->optionid,
                        'value'=>$row->value,
                        'text'=>$row->text,
                        'image'=>$row->image,
                        'ordering'=>$row->ordering
                    );

                $this->field_optionsList_alpha[$row->fieldid][$row->value] = $row->text;
            }

            if(!empty($optionValues) && isset($this->field_options_alpha)) {
                $this->field_options_ordering = $this->field_options_alpha;
                $this->field_optionsList_ordering = $this->field_optionsList_alpha;
            }

            # Get the field options for all multiple choice fields ordered by ordering
            $query = "
                SELECT
                    FieldValue.optionid, FieldValue.fieldid, FieldValue.text, FieldValue.value, FieldValue.image, FieldValue.ordering
                FROM
                    #__jreviews_fieldoptions AS FieldValue
                WHERE
                    FieldValue.fieldid IN (".implode(',',$fieldIds).")
                    " . ($optionValues ?
                        "AND FieldValue.value IN (" . $this->Quote($optionValues) . ")"
                        :
                        '')
                     . "
                ORDER
                    BY FieldValue.ordering ASC"
            ;

            $rows = $this->query($query, 'loadObjectList');

            $fieldValues = array();

            $fieldOrdering = array();

            foreach ($rows AS $row)
            {
                $this->field_options_ordering[$row->fieldid][$row->value] =
                    array(
                        'optionid'=>$row->optionid,
                        'value'=>$row->value,
                        'text'=>$row->text,
                        'image'=>$row->image,
                        'ordering'=>$row->ordering
                    );

                $this->field_optionsList_ordering[$row->fieldid][$row->value] = $row->text;
            }
        }
    }

    public function getDefaultOptionsByFieldName($names, $location)
    {
        $defaultOptions = array();

        if (!empty($names))
        {
            // Get the field options for all multiple choice fields ordered by a-z ASC

            $query = "
                SELECT
                    FieldOption.optionid, FieldOption.fieldid, FieldOption.value, Field.name
                FROM
                    #__jreviews_fieldoptions AS FieldOption
                LEFT JOIN #__jreviews_fields AS Field on Field.fieldid = FieldOption.fieldid
                WHERE
                    FieldOption.fieldid IN (SELECT Field.fieldid FROM #__jreviews_fields AS Field WHERE Field.name IN ('".implode("','",$names)."'))
                    AND
                    FieldOption.default = 1"
                . ($location ? " AND Field.location = ".$this->Quote($location) : '')
            ;

            $rows = $this->query($query, 'loadObjectList');

            foreach ($rows AS $option)
            {
                $defaultOptions[$option->name][] = $option->value;
            }
         }

         return $defaultOptions;
    }

    public function getDefaultOptionsByFieldId($fieldIds)
    {
        $defaultOptions = array();

        if (!empty($fieldIds))
        {
            // Get the field options for all multiple choice fields ordered by a-z ASC

            $query = "
                SELECT
                    FieldOption.optionid, FieldOption.fieldid, FieldOption.value
                FROM
                    #__jreviews_fieldoptions AS FieldOption
                WHERE
                    FieldOption.fieldid IN (".implode(',',$fieldIds).")
                    AND
                    FieldOption.default = 1
            ";

            $rows = $this->query($query, 'loadObjectList');

            foreach ($rows AS $option)
            {
                $defaultOptions[$option->fieldid][] = $option->value;
            }
         }

         return $defaultOptions;
    }

    function addFields($entries, $type)
    {
        $this->getFieldsArray($entries, $type);

        switch($type) {
            case 'listing':
                    $field_key = 'listing_id';
                break;
            case 'review':
                    $field_key = 'review_id';
                break;
        }

        foreach($entries AS $key=>$value)
        {
            if(isset($this->custom_fields[$value[inflector::camelize($type)][$field_key]]))
            {
                $entries[$key]['Field']['groups'] = $this->custom_fields[$value[inflector::camelize($type)][$field_key]];

                $entries[$key]['Field']['pairs'] = $this->field_pairs[$value[inflector::camelize($type)][$field_key]];
            }
            else {

                $entries[$key]['Field']['groups'] = '';

                $entries[$key]['Field']['pairs'] = '';
            }

        }
        return $entries;
    }

    /**
     * Creates the custom field group array with group info and fields values and attributes
     *
     * @param array $entries Entry array must have keys for entry id and criteriaid
     */
    function getFieldsArray($elements, $type = 'listing')
    {
        $fields = array();

        $field_pairs = array();

        $element_ids = array();

        $fieldValues = array();

        $rows = array();

        $this->criteria_ids = array(); // Alejandro = for discussion functionality

        //build entry_ids and criteria_ids array

        switch($type)
        {
            case 'listing':

                foreach ($elements AS $key=>$element) {

                    if(isset($element['Criteria']))
                    {
                        $element_ids[] = $element[inflector::camelize($type)]['listing_id'];

                        if($element['Criteria']['criteria_id']!='')
                        {
                            $this->criteria_ids[] = $element['Criteria']['criteria_id'];
                        }
                    }
                }

                break;

            case 'review':

                foreach ($elements AS $element)
                {
                    if(isset($element['Criteria']))
                    {
                        $element_ids[] = $element[inflector::camelize($type)]['review_id'];

                        if($element['Criteria']['criteria_id']!='')
                        {
                            $this->criteria_ids[] = $element['Criteria']['criteria_id'];
                        }
                    }
                }

                break;
        }

        $group_ids = $this->_criteria2Groups($this->criteria_ids, $type);

        $criteria_ids = implode(',',$this->criteria_ids);

        $element_ids = implode(',',array_unique($element_ids));

        if (empty($group_ids)){

            return;
        }

        $field_type = $type == 'listing' ? 'content' : $type;

        // Get field attributes and field values
        $query = '
            SELECT
                Field.fieldid AS `Field.field_id`, Field.groupid AS `Field.group_id`, Field.name AS `Field.name`, Field.title AS `Field.title`,
                Field.showtitle AS `Field.showTitle`, Field.description AS `Field.description`, Field.required AS `Field.required`,
                Field.type AS `Field.type`, Field.location AS `Field.location`, Field.options AS `Field.params`,
                Field.contentview AS `Field.contentView`, Field.listview AS `Field.listView`, Field.compareview AS `Field.compareView`, Field.listsort AS `Field.listSort`,
                Field.search AS `Field.search`, Field.access AS `Field.access`, Field.access_view AS `Field.accessView`,
                Field.published As `Field.published`, Field.ordering AS `Field.ordering`,
                `Group`.groupid AS `Group.group_id`, `Group`.title AS `Group.title`, `Group`.name AS `Group.name`, `Group`.showtitle AS `Group.showTitle`, `Group`.ordering AS `Group.ordering`
         FROM
            #__jreviews_fields AS Field
         LEFT JOIN
            #__jreviews_groups AS `Group` ON `Group`.groupid = Field.groupid
         WHERE
            Field.published = 1
            AND Field.location = ' . $this->Quote($field_type) . '
            AND Field.groupid IN ( ' . cleanIntegerCommaList($group_ids) . ')
        ';

        if($rows = $this->query($query, 'loadObjectList', 'Field.name'))
        {
            foreach ($rows as $key => $row)
            {
                $field_order[$key] = $row->{'Field.ordering'};

                $group_order[$key] =  $row->{'Group.ordering'};
            }

            array_multisort($group_order, SORT_ASC, $field_order, SORT_ASC, $rows);
        }

        if (!$rows || empty($rows)) {
            return;
        }

        # Extract list of field names from array

        $fieldNames = $optionFieldNames = $nonInputFieldNames = $fieldNamesByType = $fieldRows = array();

        $optionFields = array('selectmultiple','checkboxes','select','radiobuttons');

        $inputFields = array('text','textarea','code','integer','decimal','date','email','website');

        $nonInputFields = array('banner');

        foreach($rows AS $key=>$row)
        {
            // Exclude non-input fields, like banner, from forms
            if(!in_array($row->{'Field.type'},$nonInputFields))
            {
                $fieldNames[] = $row->{'Field.name'};
            }
            else {

               $row->{'Field.search'} = 0;

               $nonInputFieldNames[] = $row->{'Field.name'};
            }

            $fieldIds[$row->{'Field.name'}] = $row->{'Field.field_id'};

            $fieldRows[$key] = (array) $row;

            if(in_array($row->{'Field.type'},$optionFields))
            {
                $optionFieldNames[$row->{'Field.name'}] = $row->{'Field.field_id'}; // Used to find the option text for each option value
            }
        }

        // Get field values from current element ids

        switch($type)
        {
            case 'listing':

                /**
                 * PaidListings integration
                 */

                $fieldValues = $fieldValuesPaid = $fieldValuesDefault = array();

                if(Configure::read('ListingEdit') && Configure::read('PaidListings.enabled') && !is_array($element_ids))
                {
                    // Load the paid_listing_fields table instead of the jos_content table so users can see all their
                    // fields when editing a listing

                    Configure::write('ListingEdit',false);

                    $PaidListingFieldModel = ClassRegistry::getClass('PaidListingFieldModel');

                    $fieldValuesPaid = $PaidListingFieldModel->edit($element_ids);
                }

                if(!empty($fieldNames)) {

                    $query = "
                        SELECT
                            Listing.contentid AS element_id," . implode(',',$fieldNames) . "
                        FROM
                            #__jreviews_content AS Listing
                        WHERE
                            Listing.contentid IN (" . $element_ids . ")"
                    ;

                    $fieldValuesDefault = $this->query($query, 'loadObjectList', 'element_id');


                }

                if(!empty($fieldValuesPaid))
                {
                    // Sep 14, 2016 - Updated to prevent stale paid field values from overriding current values. So now the paid value only overwrites the current value
                    // when the current value is empty

                    foreach ($fieldValuesDefault AS $id => $rowObj)
                    {
                        foreach ($rowObj AS $fname => $value)
                        {
                            if (empty($value) && !empty($fieldValuesPaid[$id]->{$fname}))
                            {
                                $fieldValues[$id][$fname] = $fieldValuesPaid[$id]->{$fname};
                            }
                            else {
                                $fieldValues[$id][$fname] = $value;
                            }
                        }

                        $fieldValues[$id] = (object) $fieldValues[$id];
                    }
                }
                else {

                    $fieldValues = $fieldValuesDefault;
                }

                break;

            case 'review':

                if(!empty($fieldNames)) {

                    $query = "
                        SELECT
                            Review.reviewid AS element_id," . implode(',',$fieldNames) . "
                        FROM
                            #__jreviews_review_fields AS Review
                        WHERE
                            Review.reviewid IN (" . $element_ids . ")"
                    ;

                    $fieldValues = $this->query($query, 'loadObjectList', 'element_id');
                }

                break;
        }

// prx($optionFieldNames);
// prx($fieldValues);

        # Now for each option field add array of selected value,text,images
        $elementFields = array();

        $relatedListingIds = array();

        $SelelectedFieldOptionsByValue = array();

        if(!empty($fieldValues))
        {
            foreach($fieldValues AS $key=>$fieldValue)
            {
                $fieldValue = array_filter((array) $fieldValue);

                $fieldOptionValuesTemp = array_intersect_key($fieldValue,$optionFieldNames);

                foreach($fieldOptionValuesTemp AS $fname=>$optionval)
                {
                    $values = !is_array($optionval) ? explode('*',rtrim(ltrim($optionval,'*'),'*')) : $optionval;

                    foreach($values AS $optionval)
                    {
                        if($optionval!='')
                        {
                            $fieldOptionValuesToSearch[$optionval] = $optionval;

                            $fieldOptionFieldIdsToSearch[$optionFieldNames[$fname]] = $optionFieldNames[$fname];
                        }
                    }
                }
            }

            if(!empty($fieldOptionValuesToSearch))
            {
                $query = "
                    SELECT
                        *
                    FROM
                        #__jreviews_fieldoptions
                    WHERE
                        fieldid IN ( " . $this->Quote($fieldOptionFieldIdsToSearch) .")
                        AND
                        value IN ( " . $this->Quote($fieldOptionValuesToSearch) . ")
                    ORDER
                        BY ordering ASC ,optionid ASC
                ";

                $SelectedFieldOptionsArray = $this->query($query, 'loadObjectList', 'optionid');

                # Reformat array, group by field id

                foreach($SelectedFieldOptionsArray AS $option) {

                    $SelelectedFieldOptionsByValue[$option->fieldid][$option->value] = (array) $option;
                }
            }

//prx($nonInputFieldNames);
            $fnameArray = array_keys($rows);

            foreach($fieldValues AS $fieldValue)
            {
                $fieldValue = (array) $fieldValue;

                $fieldvalue = $this->sortArrayByArray($fieldValue, $fnameArray);

                foreach($fnameArray AS $key)
                {
                    $value = '';

                    if(isset($fieldValue[$key]))
                    {
                        $value = $fieldValue[$key];
                    }

                    // Add non-input fields, banner, back to the array
                    elseif(in_array($key,$nonInputFieldNames)) {

                        $value = 'banner';
                    }

                    if($key != 'element_id' && $value != '' && isset($rows[$key]))
                    {
                        $properties = stringToArray($rows[$key]->{'Field.params'});

                        $editor = Sanitize::getInt($properties, 'editor');

                        // Strip html from fields except those where it is allowed
                        if(!is_array($value)
                            && (
                                !in_array($rows[$key]->{'Field.type'},array('text','textarea','code'))
                                ||
                                (in_array($rows[$key]->{'Field.type'},array('text','textarea')) && Sanitize::getBool($properties,'allow_html') == false && !$editor)
                                )
                            ) {

                            $value = htmlspecialchars($value,ENT_QUOTES,'UTF-8');
                        }

                        if($rows[$key]->{'Field.type'} != 'date' || ($rows[$key]->{'Field.type'} == 'date' && $value != NULL_DATE))
                        {
                            $elementFields[$fieldValue['element_id']]['field_id'] = $fieldRows[$key]['Field.field_id'];

                            // text, textarea, code, integer, decimal, website, email

                            if(in_array($rows[$key]->{'Field.type'}, $inputFields))
                            {
                                $elementFields[$fieldValue['element_id']][$key]['Field.text'][] = $value;

                                $elementFields[$fieldValue['element_id']][$key]['Field.value'][] = $value;

                                $elementFields[$fieldValue['element_id']][$key]['Field.image'][] = '';
                            }

                            // relatedlisting

                            elseif(!in_array($rows[$key]->{'Field.type'},$optionFields) )
                            {
                                $selOptions = !is_array($value) ? explode('*',rtrim(ltrim($value,'*'),'*')) : $value;

                                $selOptions = array_filter($selOptions);

                                if($rows[$key]->{'Field.type'} == 'relatedlisting')
                                {
                                    $relatedListingIds = array_merge($relatedListingIds, $selOptions);
                                }

                                foreach($selOptions AS $selOption)
                                {
                                    $elementFields[$fieldValue['element_id']][$key]['Field.text'][] = $selOption;

                                    $elementFields[$fieldValue['element_id']][$key]['Field.value'][] = $selOption;

                                    $elementFields[$fieldValue['element_id']][$key]['Field.image'][] = '';
                                }
                            }

                            // selectmultiple, checkboxes, select, radiobuttons

                            elseif(in_array($rows[$key]->{'Field.type'},$optionFields)) {

                                $fieldOptions = Sanitize::getVar($SelelectedFieldOptionsByValue,$rows[$key]->{'Field.field_id'});

                                $selOptions = !is_array($value) ? explode('*',rtrim(ltrim($value,'*'),'*')) : $value;

                                foreach($selOptions AS $selOption)
                                {
                                    if($selOption != '' && isset($fieldOptions[$selOption]))
                                    {
                                        $elementFields[$fieldValue['element_id']][$key]['Field.value'][] = $fieldOptions[$selOption]['value'];

                                        $elementFields[$fieldValue['element_id']][$key]['Field.text'][] = $fieldOptions[$selOption]['text'];

                                        $elementFields[$fieldValue['element_id']][$key]['Field.image'][] = $fieldOptions[$selOption]['image'];
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }

        // Only banner fields are present so we need to populate the $elementFields array just for them

        if(!empty($fieldRows) && !empty($nonInputFieldNames) && empty($fieldValues) && empty($elementFields)) {

            $ids = explode(',',$element_ids);

            foreach($ids AS $element_id) {

                foreach($fieldRows AS $field_name=>$field_row) {

                    if(!in_array($field_row['Field.type'],$nonInputFields)) continue;

                    $group_ids = $this->_criteria2Groups($elements[$element_id]['Criteria']['criteria_id'], $type);

                    if(!in_array($fieldRows[$field_name]['Field.group_id'], $group_ids)) continue;

                    $elementFields[$element_id]['field_id'] = $field_row['Field.field_id'];

                    $elementFields[$element_id][$field_name]['Field.id'] = $field_row['Field.field_id'];

                    $elementFields[$element_id][$field_name]['Field.text'][] = $field_row['Field.description'];

                    $elementFields[$element_id][$field_name]['Field.value'][] = $field_row['Field.description'];

                    $elementFields[$element_id][$field_name]['Field.image'][] = $field_row['Field.description'];
                }
            }
        }

        $relatedListings = $this->getRelatedListings($relatedListingIds);

        // Reformat array so array keys match element ids
        foreach ($elementFields AS $key=>$elementField)
        {
            $element_id = $key;

            $field_id = $elementField['field_id'];

            unset($elementField['field_id']);

            $field_name = key($elementField);

            $group_ids = $this->_criteria2Groups($elements[$element_id]['Criteria']['criteria_id'], $type);

            foreach($elementField AS $field_name=>$field_options)
            {
                if(!in_array($fieldRows[$field_name]['Field.group_id'], $group_ids)) continue;

                //FieldGroups array

                $fields[$element_id][$fieldRows[$field_name]['Group.name']]['Group']['group_id'] = $fieldRows[$field_name]['Field.group_id'];

                $fields[$element_id][$fieldRows[$field_name]['Group.name']]['Group']['title'] = $fieldRows[$field_name]['Group.title'];

                $fields[$element_id][$fieldRows[$field_name]['Group.name']]['Group']['name'] = $fieldRows[$field_name]['Group.name'];

                $fields[$element_id][$fieldRows[$field_name]['Group.name']]['Group']['show_title'] = $fieldRows[$field_name]['Group.showTitle'];

                $fields[$element_id][$fieldRows[$field_name]['Group.name']]['Fields'][$fieldRows[$field_name]['Field.name']]['id'] = $fieldRows[$field_name]['Field.field_id'];

                $fields[$element_id][$fieldRows[$field_name]['Group.name']]['Fields'][$fieldRows[$field_name]['Field.name']]['group_id'] = $fieldRows[$field_name]['Field.group_id'];

                $fields[$element_id][$fieldRows[$field_name]['Group.name']]['Fields'][$fieldRows[$field_name]['Field.name']]['name'] = $fieldRows[$field_name]['Field.name'];

                $fields[$element_id][$fieldRows[$field_name]['Group.name']]['Fields'][$fieldRows[$field_name]['Field.name']]['type'] = $fieldRows[$field_name]['Field.type'];

                $fields[$element_id][$fieldRows[$field_name]['Group.name']]['Fields'][$fieldRows[$field_name]['Field.name']]['title'] = $fieldRows[$field_name]['Field.title'];

                $fields[$element_id][$fieldRows[$field_name]['Group.name']]['Fields'][$fieldRows[$field_name]['Field.name']]['description'] = $fieldRows[$field_name]['Field.description'];

                // Field values

                $real_value = $value = $text = array();

                if($fieldRows[$field_name]['Field.type'] == 'relatedlisting')
                {
                    foreach($field_options['Field.value'] AS $key=>$id)
                    {
                        if(!isset($relatedListings[$id]))
                        {
                            continue;
                        }

                        $real_value[] = $id;

                        $value[] = $relatedListings[$id]['route'];

                        $text[] = $relatedListings[$id]['text'];

                        $fields[$element_id][$fieldRows[$field_name]['Group.name']]['Fields'][$fieldRows[$field_name]['Field.name']]['value'][] = $relatedListings[$id]['route'];

                        $fields[$element_id][$fieldRows[$field_name]['Group.name']]['Fields'][$fieldRows[$field_name]['Field.name']]['text'][] = $relatedListings[$id]['text'];

                        $fields[$element_id][$fieldRows[$field_name]['Group.name']]['Fields'][$fieldRows[$field_name]['Field.name']]['real_value'][] = $id;
                    }

                    if(!empty($relatedListings) && !empty($value))
                    {
                        array_multisort(
                            $fields[$element_id][$fieldRows[$field_name]['Group.name']]['Fields'][$fieldRows[$field_name]['Field.name']]['text'],
                            $fields[$element_id][$fieldRows[$field_name]['Group.name']]['Fields'][$fieldRows[$field_name]['Field.name']]['value'],
                            $fields[$element_id][$fieldRows[$field_name]['Group.name']]['Fields'][$fieldRows[$field_name]['Field.name']]['real_value'],
                            $text,
                            $value,
                            $real_value
                        );
                    }
                }
                else {

                    $value = $field_options['Field.value'];

                    $text = $field_options['Field.text'];

                    $fields[$element_id][$fieldRows[$field_name]['Group.name']]['Fields'][$fieldRows[$field_name]['Field.name']]['value'] = $value;

                    $fields[$element_id][$fieldRows[$field_name]['Group.name']]['Fields'][$fieldRows[$field_name]['Field.name']]['text'] = $text;
                }

                $fields[$element_id][$fieldRows[$field_name]['Group.name']]['Fields'][$fieldRows[$field_name]['Field.name']]['image'] = $field_options['Field.image'];

                $fields[$element_id][$fieldRows[$field_name]['Group.name']]['Fields'][$fieldRows[$field_name]['Field.name']]['properties']['show_title'] = $fieldRows[$field_name]['Field.showTitle'];

                $fields[$element_id][$fieldRows[$field_name]['Group.name']]['Fields'][$fieldRows[$field_name]['Field.name']]['properties']['location'] = $fieldRows[$field_name]['Field.location'];

                $fields[$element_id][$fieldRows[$field_name]['Group.name']]['Fields'][$fieldRows[$field_name]['Field.name']]['properties']['contentview'] = $fieldRows[$field_name]['Field.contentView'];

                $fields[$element_id][$fieldRows[$field_name]['Group.name']]['Fields'][$fieldRows[$field_name]['Field.name']]['properties']['listview'] = $fieldRows[$field_name]['Field.listView'];

                $fields[$element_id][$fieldRows[$field_name]['Group.name']]['Fields'][$fieldRows[$field_name]['Field.name']]['properties']['compareview'] = $fieldRows[$field_name]['Field.compareView'];

                $fields[$element_id][$fieldRows[$field_name]['Group.name']]['Fields'][$fieldRows[$field_name]['Field.name']]['properties']['listsort'] = $fieldRows[$field_name]['Field.listSort'];

                $fields[$element_id][$fieldRows[$field_name]['Group.name']]['Fields'][$fieldRows[$field_name]['Field.name']]['properties']['search'] = $fieldRows[$field_name]['Field.search'];

                $fields[$element_id][$fieldRows[$field_name]['Group.name']]['Fields'][$fieldRows[$field_name]['Field.name']]['properties']['access'] = $fieldRows[$field_name]['Field.access'];

                $fields[$element_id][$fieldRows[$field_name]['Group.name']]['Fields'][$fieldRows[$field_name]['Field.name']]['properties']['access_view'] = $fieldRows[$field_name]['Field.accessView'];

                //FieldPairs associative array with field name as key and field value as value

                $field_pairs[$element_id][$fieldRows[$field_name]['Field.name']]['field_id'] = $fieldRows[$field_name]['Field.field_id'];

                $field_pairs[$element_id][$fieldRows[$field_name]['Field.name']]['group_id'] = $fieldRows[$field_name]['Field.group_id'];

                $field_pairs[$element_id][$fieldRows[$field_name]['Field.name']]['group_show_title'] = $fieldRows[$field_name]['Group.showTitle'];

                $field_pairs[$element_id][$fieldRows[$field_name]['Field.name']]['group_title'] = $fieldRows[$field_name]['Group.title'];

                $field_pairs[$element_id][$fieldRows[$field_name]['Field.name']]['group_name'] = $fieldRows[$field_name]['Group.name'];

                $field_pairs[$element_id][$fieldRows[$field_name]['Field.name']]['name'] = $fieldRows[$field_name]['Field.name'];

                $field_pairs[$element_id][$fieldRows[$field_name]['Field.name']]['title'] = $fieldRows[$field_name]['Field.title'];

                $field_pairs[$element_id][$fieldRows[$field_name]['Field.name']]['value'] = $value;

                $field_pairs[$element_id][$fieldRows[$field_name]['Field.name']]['text'] = $text;

                if(!empty($real_value))
                {
                    $field_pairs[$element_id][$fieldRows[$field_name]['Field.name']]['real_value'] = $real_value;
                }

                $field_pairs[$element_id][$fieldRows[$field_name]['Field.name']]['image'] = $field_options['Field.image'];

                $field_pairs[$element_id][$fieldRows[$field_name]['Field.name']]['type'] = $fieldRows[$field_name]['Field.type'];

                $field_pairs[$element_id][$fieldRows[$field_name]['Field.name']]['description'] = $fieldRows[$field_name]['Field.description'];

                $field_pairs[$element_id][$fieldRows[$field_name]['Field.name']]['properties']['show_title'] = $fieldRows[$field_name]['Field.showTitle'];

                $field_pairs[$element_id][$fieldRows[$field_name]['Field.name']]['properties']['location'] = $fieldRows[$field_name]['Field.location'];

                $field_pairs[$element_id][$fieldRows[$field_name]['Field.name']]['properties']['contentview'] = $fieldRows[$field_name]['Field.contentView'];

                $field_pairs[$element_id][$fieldRows[$field_name]['Field.name']]['properties']['listview'] = $fieldRows[$field_name]['Field.listView'];

                $field_pairs[$element_id][$fieldRows[$field_name]['Field.name']]['properties']['compareview'] = $fieldRows[$field_name]['Field.compareView'];

                $field_pairs[$element_id][$fieldRows[$field_name]['Field.name']]['properties']['listsort'] = $fieldRows[$field_name]['Field.listSort'];

                $field_pairs[$element_id][$fieldRows[$field_name]['Field.name']]['properties']['search'] = $fieldRows[$field_name]['Field.search'];

                $field_pairs[$element_id][$fieldRows[$field_name]['Field.name']]['properties']['access'] = $fieldRows[$field_name]['Field.access'];

                $field_pairs[$element_id][$fieldRows[$field_name]['Field.name']]['properties']['access_view'] = $fieldRows[$field_name]['Field.accessView'];

                $field_pairs[$element_id][$fieldRows[$field_name]['Field.name']]['properties']['access_view'] = $fieldRows[$field_name]['Field.accessView'];

                $properties = stringToArray($fieldRows[$field_name]['Field.params']);

                if($fieldRows[$field_name]['Field.type'] == 'relatedlisting')
                {
                    $properties['autocomplete'] = 1;

                    $properties['autocomplete.search'] = 1;
                }

                $fields[$element_id][$fieldRows[$field_name]['Group.name']]['Fields'][$fieldRows[$field_name]['Field.name']]['properties'] = array_merge($fields[$element_id][$fieldRows[$field_name]['Group.name']]['Fields'][$fieldRows[$field_name]['Field.name']]['properties'],$properties);

                $field_pairs[$element_id][$fieldRows[$field_name]['Field.name']]['properties'] = array_merge($field_pairs[$element_id][$fieldRows[$field_name]['Field.name']]['properties'],$properties);
            }
        }

        $this->custom_fields = $fields;

        $this->field_pairs = $field_pairs;
    }

    /**
     * Used in forms
     * Creates the custom field group array with group info and fields attributes
     * The selected key is filled if an $entry array with field values is passed
     */
    function getFieldsArrayNew($criteria_ids, $location = 'listing', $entry = null, $search = null)
    {
        $rows = false;

        $defaultOptions = array();

        # Check for cached version
        $cache_prefix = 'field_model_new';
        $cache_key = func_get_args();
        $cache_key['locale'] = cmsFramework::locale();

        if (isset($cache_key[2])) unset($cache_key[2]); // $entry not required to cache the results

        if ($cache = S2cacheRead($cache_prefix,$cache_key)){
            $rows = $cache;
        }

        if (false == $rows || $rows == '') {

            $fields = array();

            $location = ($location == 'listing' ? 'content' : $location);

            $group_ids = $this->_criteria2Groups($criteria_ids,$location);

            if (empty($group_ids))
            {
                return;
            }

            //get field attributes only, no values
            $query = "
                SELECT
                    Field.fieldid AS `Field.id`,
                    Field.groupid AS `Field.groupid`,
                    Field.name AS `Field.name`,
                    Field.title AS `Field.title`,
                    Field.showtitle AS `Field.showTitle`,
                    Field.description AS `Field.description`,
                    Field.required AS `Field.required`,
                    Field.type AS `Field.type`,
                    Field.location AS `Field.location`,
                    Field.options AS `Field.params`,
                    Field.search AS `Field.search`,
                    Field.access AS `Field.access`,
                    Field.access_view AS `Field.accessView`,
                    Field.published As `Field.published`,
                    Field.compareview AS `Field.compareView`,
                    Field.control_field AS `Field.control_field`,
                    Field.control_value AS `Field.control_value`,
                    `Group`.groupid AS `Group.group_id`,
                    `Group`.title AS `Group.title`,
                    `Group`.name AS `Group.name`,
                    `Group`.showtitle AS `Group.showTitle`,
                    `Group`.control_field AS `Group.control_field`,
                    `Group`.control_value AS `Group.control_value`
                FROM
                    #__jreviews_fields AS Field
                INNER JOIN
                    #__jreviews_groups AS `Group` ON (`Group`.groupid = Field.groupid AND
                    `Group`.groupid IN (".cleanIntegerCommaList($group_ids).") AND `Group`.type =  " . $this->Quote($location) . " )
                WHERE
                    Field.published = 1 AND Field.location = " . $this->Quote($location) . "
                    " . ($search ? " AND search = 1" : '') . "
                GROUP BY
                    Field.fieldid
                ORDER
                    BY `Group`.ordering, Field.ordering
            ";

            $rows = $this->query($query, 'loadObjectList');

            # Send to cache
            S2cacheWrite($cache_prefix,$cache_key,$rows);
        }

        if (!$rows || empty($rows))
        {
            return;
        }

        // Extract field ids from array

        /**
        * Select and multiple fields excluded because their option values are loaded via ajax via the control fields feature
        **/

        $fieldIds = $this->extractFieldIds($rows, array(/*'select','selectmultiple',*/'radiobuttons','checkboxes'));

        // Get the field options for multiple choice fields

        $this->getFieldOptions($fieldIds);

        // Process defaults for new entries

        if (!$entry)
        {
            $fieldIdsDefaults = $this->extractFieldIds($rows, array('select','selectmultiple','radiobuttons','checkboxes'));

            $defaultOptions = $this->getDefaultOptionsByFieldId($fieldIdsDefaults);
        }

        // Reformat array and add field options to each multiple choice field

        foreach ($rows AS $row)
        {
            $row = (array) $row;

            $fieldId = $row['Field.id'];

            $field = array(
                'field_id'          => $fieldId,
                'group_id'          => $row['Field.groupid'],
                'group_title'       => $row['Group.title'],
                'group_show_title'  => $row['Group.showTitle'],
                'name'              => $row['Field.name'],
                'type'              => $row['Field.type'],
                'title'             => $row['Field.title'],
                'description'       => $row['Field.description'],
                'required'          => $row['Field.required'],
                'control_field'     => $row['Field.control_field'],
                'control_value'     => $row['Field.control_value'],
                'properties'        => array(
                    'show_title'        => $row['Field.showTitle'],
                    'location'          => $row['Field.location'],
                    'search'            => $row['Field.search'],
                    'access'            => $row['Field.access'],
                    'access_view'       => $row['Field.accessView'],
                    'compareview'       => $row['Field.compareView'],
                    'published'         => $row['Field.published']
                )
            );

            $fields[$row['Group.title']]['group_id'] = $row['Group.group_id'];
            $fields[$row['Group.title']]['group_name'] = $row['Group.name'];
            $fields[$row['Group.title']]['control_field'] = $row['Group.control_field'];
            $fields[$row['Group.title']]['control_value'] = $row['Group.control_value'];

            $field['default_options'] = Sanitize::getVar($defaultOptions, $fieldId, array());

            /*
             * $entry is passed when editing listing or review and includes the existing field values
             */

            if (is_array($entry) && !empty($entry['Field']['pairs']) && isset($entry['Field']['pairs'][$row['Field.name']]['value']))
            {
                $field['selected'] = $entry['Field']['pairs'][$row['Field.name']]['value'];
            }

            /*
             * Also populate selected values for default options when not editing. This is necessary for checkbox and radio fields
             * which do not get updated via ajax
             */

            if (!$entry && isset($defaultOptions[$fieldId]) && in_array($field['type'], array('radiobuttons','checkboxes')))
            {
                $field['selected'] = $defaultOptions[$fieldId];
            }

            $properties = stringToArray($row['Field.params']);

            $field['properties'] = array_merge($field['properties'], $properties);

            if(isset($this->field_options_alpha[$row['Field.id']]))
            {
                $ordering = isset($field['properties']['option_ordering']) && $field['properties']['option_ordering'] ? 'alpha' : 'ordering';

                $method = 'field_options_'.$ordering;

                $methodList = 'field_optionsList_'.$ordering;

                $field['options'] = $this->{$method}[$row['Field.id']];

                $field['optionList'] = $this->{$methodList}[$row['Field.id']];
            }

            $fields[$field['group_title']]['Fields'][$field['name']] = $field;
        }

        return $fields;
    }

    public function getFieldFromName($fname, $location = 'listing',  $selectedValues = null, $params = array())
    {
        $fields = $this->getFieldsArrayFromNames($fname, $location, $selectedValues, $params);

        $field = array_shift($fields);

        return $field;
    }

    /**
     * Used in dynamic forms
     * Creates an array of fields with attributes and options using an array of field names as input
     */
    public function getFieldsArrayFromNames($names, $location = 'listing',  $selectedValues = null, $params = array())
    {
        $defaults = array('group_by_groups' => true, 'load_options' => false);

        $params = array_merge($defaults, $params);

        if(empty($names) || (count($names) == 1 && $names[0] == 'category'))
        {
            return array();
        }

        // For single field we first need to do some data manipulation

        if (!is_array($names))
        {
            $selectedValues = array($names => array('value' => $selectedValues));

            $names = array($names);
        }

        if (isset($selectedValues['Field']))
        {
            $selectedValues = $selectedValues['Field']['pairs'];
        }

        $rows = false;

        # Check for cached version
        $cache_prefix = 'field_model_names';

        $cache_key = func_get_args();

        $cache_key['locale'] = cmsFramework::locale();

        if (isset($cache_key[2])) unset($cache_key[2]); // $entry not required to cache the results

        if ($cache = S2cacheRead($cache_prefix,$cache_key))
        {
            $rows = $cache;
        }

        if(false == $rows || $rows == '')
        {
            $location = ($location == 'listing' ? 'content' : $location);

            foreach($names AS $name) {
                $quoted_names[] = "'".$name."'";
            }

            $quoted_names = implode(',',$quoted_names);

            // Get field attributes only, no values

            $query = '
                SELECT
                    Field.fieldid AS `Field.id`,
                    Field.name AS `Field.name`,
                    Field.title AS `Field.title`,
                    Field.type AS `Field.type`,
                    Field.description AS `Field.description`,
                    Field.options AS `Field.params`,
                    Field.required AS `Field.required`,
                    `Group`.groupid AS `Group.group_id`,
                    `Group`.title AS `Group.title`
                FROM
                    #__jreviews_fields AS Field
                INNER JOIN
                    #__jreviews_groups AS `Group` ON (`Group`.groupid = Field.groupid AND `Group`.groupid AND `Group`.type = "'.$location.'" )
                WHERE
                    Field.name IN ('.$quoted_names.') AND Field.location = "'.$location.'"
                ORDER BY Group.ordering, Field.ordering
            ';

            $rows = $this->query($query, 'loadObjectList');

            # Send to cache
            S2cacheWrite($cache_prefix,$cache_key,$rows);
        }

        if (!$rows || empty($rows))
        {
            return;
        }

        // Extract field ids from array

        if ($params['load_options'] == 'none')
        {
            // Used in advanced filtering module where all option-field types are converted to select
            $preloadFieldOptions = array();
        }
        elseif ($params['load_options']) {
            $preloadFieldOptions = array('select','selectmultiple','radiobuttons','checkboxes');
        }
        else {
            $preloadFieldOptions = array(/*'select','selectmultiple',*/'radiobuttons','checkboxes');
        }

        $fieldIds = $this->extractFieldIds($rows, $preloadFieldOptions);

        // Get the field options for multiple choice fields

        $this->getFieldOptions($fieldIds);

        // Reformat array and add field options to each multiple choice field

        $fieldGroups = array();

        $fields = array();

        foreach ($rows AS $row)
        {
            $row = (array) $row;

            $fname = $row['Field.name'];

            $fieldId = $row['Field.id'];

            $field = array(
                'field_id' => $fieldId,
                'name' => $fname,
                'type' => $row['Field.type'],
                'title' => $row['Field.title'],
                'required' => $row['Field.required'],
                'description' => $row['Field.description'],
                'properties' => stringToArray($row['Field.params'])
            );

            # $entry is passed when editing listing or review and includes the existing field values
            if(is_array($selectedValues) && isset($selectedValues[$fname]['value']))
            {
                $field['selected'] = $selectedValues[$fname]['value'];
            }

            if(isset($this->field_options_alpha[$fieldId]))
            {
                $ordering = isset($field['properties']['option_ordering']) && $field['properties']['option_ordering'] ? 'alpha' : 'ordering';

                $method = 'field_options_'.$ordering;

                $methodList = 'field_optionsList_'.$ordering;

                $field['options'] = $this->{$method}[$fieldId];

                $field['optionList'] = $this->{$methodList}[$fieldId];
            }

            $fieldGroups[$row['Group.title']]['Fields'][$fname] = $field;

            $fields[] = $field;
        }

        if ($params['group_by_groups'])
        {
            return $fieldGroups;
        }

        return $fields;
    }

    /**
     * Used in simplesearch to use only the text-based custom fields for searches
     * @return array
     */
    function getTextBasedFieldNames($location = 'content')
    {
        # Check for cached version
        $cache_prefix = 'text_fieldnames_' . $location;

        $cache_key = func_get_args();

        if($location == 'listing') $location = 'content';

        if($cache = S2cacheRead($cache_prefix,$cache_key)){
            $fields = $cache;
        }

        $query = "
            SELECT
                name
            FROM
                #__jreviews_fields
            WHERE
                location = " . $this->Quote($location) . "
                AND
                published = 1
                AND
                search = 1
                AND
                type IN ('formbuilder','select','selectmultiple','text','textarea','radiobuttons','checkboxes');";

        $fields = $this->query($query,'loadColumn');

        # Send to cache
        S2cacheWrite($cache_prefix,$cache_key,$fields);

        return $fields;
    }

    /**
     * Auxiliary method to group all search options field and and field title and option text
     * @return [type] [description]
     */
    function buildSearchOptionsArray($fieldValues, $fieldTypes, & $searchOptionsArray)
    {
        $optionFields = array("select","radiobuttons","selectmultiple","checkboxes");

        $field_ids = array();

        $field_values = array();

        foreach($fieldValues AS $fname=>$value)
        {
            $field_ids[$fieldTypes[$fname]['fieldid']] = $fieldTypes[$fname]['fieldid'];

            $field_values[$value] = $value;
        }

        $query = '
            SELECT
                Field.name, FieldOption.text
            FROM
                #__jreviews_fieldoptions AS FieldOption
            LEFT JOIN
                #__jreviews_fields AS Field ON Field.fieldid = FieldOption.fieldid
            WHERE
                FieldOption.fieldid IN (' . $this->Quote($field_ids). ')
                AND FieldOption.value IN (' . $this->Quote($field_values). ')
            ';

        $options = $this->query($query,'loadAssocList','name');

        foreach($options AS $fname=>$option)
        {
            $searchOptionsArray[$fname] = array(
                'name'=>$fname,
                'title'=>$fieldTypes[$fname]['title'],
                'text'=>$option['text'],
                'value'=>$fieldValues[$fname],
                );
        }

        return $searchOptionsArray;
    }

    /**
     * This is the list of values used for the order select list
     */
    function getOrderList($listing_type_ids, $location)
    {
        $list = array();
        $field_order_array = array();

        if($location == 'listing') {
            $location = 'content';
        }

        // Check the number of criteria defined and if more than one check if all have a common group
        // A common set of groups means the criteria is different because of the ratings, not the fields
        // This is to show the custom fields in the dropdown select list for search results if the fields are common to all entries

        $query = "
            SELECT
                count(DISTINCT groupid)
            FROM
                #__jreviews_criteria
            WHERE
                groupid != ''
        ";

        $criteria_groups = $this->query($query, 'loadResult');

        if($criteria_groups == 1 || count($listing_type_ids) == 1)
        {
            $group_ids = $this->_criteria2Groups($listing_type_ids, $location);

            if(!empty($group_ids))
            {
                // Need to include fieldid for multilingual support Joomfish
                $query = "
                    SELECT
                        Field.fieldid, Field.title AS text, Field.name AS value, Field.access_view AS access
                    FROM
                        #__jreviews_fields AS Field
                    WHERE
                        Field.location = ". $this->Quote($location) . "
                        AND Field.listsort = 1
                        AND Field.groupid IN (" . cleanIntegerCommaList($group_ids) . ")"
                ;

                if($list = $this->query($query, 'loadObjectList'))
                {
                    foreach ($list AS $key=>$row)
                    {
                        $field_order_array[$row->value] = (array) $row;
                    }
                }
            }
        }

        return $field_order_array;
    }

    function processNewFieldOptions($options, $field)
    {
        $processedOptions = array();
        // Process new field options and modify the $data array
        $FieldOption = ClassRegistry::getClass('FieldOptionModel');
        $click2add = Sanitize::getBool($field['_params'],'click2add');
        !is_array($options) and $options = array($options);
        foreach($options AS $option)
        {
            if($click2add && strstr($option,'|click2add'))
            {
                $data = array();
                $option_parts = explode('|',$option);

                // Build array to pass to the FieldOptions model
                $data['FieldOption']['fieldid'] = Sanitize::getInt($field,'fieldid');
                $data['FieldOption']['value'] = Sanitize::stripAll($option_parts,0);
                $data['FieldOption']['text'] = trim(Sanitize::getString($option_parts,0));
                // If it's a dependent field add the relevant control field data
                if(count($option_parts) == 4) {
                    $option_parts[3] == 'null' and $option_parts[3] = '';
                    $controlledBy = $data['FieldOption']['controlledBy'] = array($option_parts[2]=>$option_parts[3]);
                    $control_field = key($controlledBy);
                    $control_value = is_array(current($controlledBy)) ?  array_values(current($controlledBy)) : array(current($controlledBy));
                    if($control_field != '' && $control_value != '**')
                    {
                        $data['FieldOption']['control_field'] = $control_field;
                        $data['FieldOption']['control_value'] = $control_value;
                    }
                }
                if(in_array($FieldOption->save($data),array('success','duplicate'))) {
                    $processedOptions[] = $data['FieldOption']['value'];
                }
            }
            else {
                $processedOptions[] = $option;
            }
        }

        return array_unique($processedOptions);
    }

    function save(&$data, $location = 'listing', $isNew, &$validFields = array())
    {
        $msg = '';

        $fieldLocation = inflector::camelize($location);

        // Check if there are custom fields to save or exit
        if (isset($data['Field'])
            &&
            (!is_array($data['Field'][$fieldLocation]) || count($data['Field'][$fieldLocation]) == 1
            )
        ) {
            return $msg;
        }

       if(!empty($validFields))
       {
            S2App::import('Model','field_option','jreviews');

            $validFieldNames = array_keys($validFields);

            foreach ($validFieldNames AS $fieldName)
            {
                $inputValue = '';

                $validField = isset($validFields[$fieldName]) ? $validFields[$fieldName] : false;

                if ( ($validField && $validField['valid'])
                    &&
                    (
                        $validField['type'] != 'code'
                        || ($validField['type'] == 'code' && Sanitize::getVar($data['__raw']['Field'][$fieldLocation],$fieldName,'') != ''))
                    )
                {
                    switch($validField['type'])
                    {
                        case 'selectmultiple': case 'checkboxes':
                        case 'select': case 'radiobuttons':
                            //Checks for types with options
                            $options = $this->processNewFieldOptions(Sanitize::getVar($data['Field'][$fieldLocation],$fieldName),$validField);
                            $inputValue = '*'.implode('*',$options).'*';
                            break;
                        case 'code':
                            // Affiliate code left unfiltered
                            $inputValue = Sanitize::getString($data['__raw']['Field'][$fieldLocation],$fieldName,'');
                        break;
                        case 'decimal':
                            $inputValue = Sanitize::getString($data['Field'][$fieldLocation],$fieldName);
                            $inputValue = $inputValue == '' ? null : $inputValue;
                        break;
                        case 'integer':
                            $inputValue = Sanitize::getString($data['Field'][$fieldLocation],$fieldName);
                            $inputValue = $inputValue == '' ? null : (int) $inputValue;
                        break;
                        case 'relatedlisting':
                            $inputValue = Sanitize::getVar($data['Field'][$fieldLocation],$fieldName);
                            $inputValue = $inputValue == '' ? null : '*' . implode('*', $inputValue) . '*';
                        break;
                        case 'date':
                            if(Sanitize::getString($data['Field'][$fieldLocation],$fieldName) != '' && Sanitize::getString($data['Field'][$fieldLocation],$fieldName) != null)
                            {
                                $inputValue = strftime( _CURRENT_SERVER_TIME_FORMAT, strtotime(Sanitize::getString($data['Field'][$fieldLocation],$fieldName)));
                            }
                            else
                            {
                                $inputValue = '';
                            }
                        break;
                        case 'textarea': case 'text':

                            $allowHtml = Sanitize::getInt($validField['_params'], 'allow_html', 0);

                            $editor = Sanitize::getInt($validField['_params'], 'editor', 0);

                            if ($allowHtml || $editor)
                            {
                                $inputValue = Sanitize::stripScripts(Sanitize::getString($data['__raw']['Field'][$fieldLocation],$fieldName,''));
                                $inputValue = stripslashes($inputValue);
                            }
                            else
                            {
                                $inputValue = Sanitize::getString($data['Field'][$fieldLocation],$fieldName,'');
                            }
                        break;
                        case 'formbuilder':
                            $inputValue = Sanitize::getVar($data['Field'][$fieldLocation],$fieldName);
                            if ($inputValue != '') {
                                $inputValueArray = json_decode($inputValue, true);
                                $inputValueString = implode('', S2Array::flatten($inputValueArray));
                                if (empty($inputValueString)) {
                                    $inputValue = '';
                                }
                                else {
                                    $inputValue = json_encode($inputValueArray);
                                }
                            }
                        break;
                        case 'website':
                            $inputValue = Sanitize::stripScripts(Sanitize::getVar($data['Field'][$fieldLocation],$fieldName));
                            $inputValue = str_replace('&amp;','&',$inputValue);
                        break;
                        case 'email':
                            $inputValue = Sanitize::stripScripts(Sanitize::getVar($data['Field'][$fieldLocation],$fieldName));
                        break;
                        default:
                            $inputValue = Sanitize::getVar($data['Field'][$fieldLocation],$fieldName);
                        break;
                    }

                    # Modify form post arrays to current values
                    if($inputValue === '' || $inputValue === '**') {
                        $inputValue = '';
                    }

                    $data['Field'][$fieldLocation][$fieldName] = $inputValue;
                }
                elseif($validField) {

                    switch($validField['type'])
                    {
                        case 'decimal':
                        case 'integer':
                        case 'relatedlisting':
                            $data['Field'][$fieldLocation][$fieldName] = null;
                        break;
                        default:
                            $data['Field'][$fieldLocation][$fieldName] = '';
                        break;
                    }
                }

                // Debug custom fields array
                $msg .=  "{$validField['name']}=>{$inputValue}"."<br />";
            }
        }

        # Need to check if jreviews_content or jreviews_reviews record exists to decide whether to insert or update the table
        if($location == 'review')
        {
            S2App::import('Model','jreviews_review_field','jreviews');

            $JreviewsReviewFieldModel = new JreviewsReviewFieldModel();

            $recordExists = $JreviewsReviewFieldModel->findCount(array(
                'conditions'=>array('JreviewsReviewField.reviewid= ' . $data['Field']['Review']['reviewid']),
                'session_cache'=>false
            ));
        }
        else
        {
            S2App::import('Model','jreviews_content','jreviews');

            $JreviewsContentModel = new JreviewsContentModel();

            $recordExists = $JreviewsContentModel->findCount(array(
                'conditions'=>array('JreviewsContent.contentid = ' . $data['Listing']['id']),
                'session_cache'=>false
            ));
        }

        $dbAction = $recordExists ? 'update' : 'insert';

        if($location == 'review')
        {
            $result = $this->$dbAction('#__jreviews_review_fields',$fieldLocation,$data['Field'],'reviewid');
        }
        else
        {
            if(Configure::read('PaidListings.enabled') && Sanitize::getInt($data,'paid_category'))
            {
                // PaidListings integration - saves all fields to jreviews_paid_listing_fields table and removes unpaid fields from jreviews_content table

                $PaidListingField = ClassRegistry::getClass('PaidListingFieldModel');

                $PaidListingField->save($data);
            }

            $result = $this->$dbAction('#__jreviews_content',$fieldLocation,$data['Field'],'contentid');
         }

         return $result;
    }

    /**
     * Stop form tampering by adding back any required fields that are not present in the form post data
     * Fields may not be present because they were removed, or in the case of multipe choice fields there isn't a default value
     * @param [type] $data          [description]
     * @param [type] $fieldLocation [description]
     * @param [type] $Access        [description]
     */
    function addBackEmptyRequiredFields(& $data, $fieldLocation, $Access)
    {
        $fields = array();

        $expectedAndRequiredFields = array();

        $expectedAndOptionalFields = array();

        // Get a list of required fields expected to be received

        $fieldGroups = $this->getFieldsArrayNew($data['Criteria']['id'], $fieldLocation);

        // If there are no fields in this listing type, then there's nothing to do here

        if (empty($fieldGroups)) {
            return;
        }

        $fieldGroupRelations = array();

        foreach ($fieldGroups AS $group)
        {
            $fields = array_merge($fields, $group['Fields']);

            $fieldGroupRelations[$group['group_id']] = array(
                'control_field' => $group['control_field'],
                'control_value' => $group['control_value']
            );
        }

        // Filter by required and access group

        foreach ($fields AS $field)
        {
            if ($Access->in_groups($field['properties']['access']) && $field['type'] != 'banner')
            {
                if ($field['required'])
                {
                    $expectedAndRequiredFields[$field['name']] = $field;
                }
                else {
                    $expectedAndOptionalFields[$field['name']] = $field;
                }

            }
        }

        $submittedFields = & $data['Field'][ucfirst($fieldLocation)];

        // Now that we have the expected and submitted field names, we need to check if any of the
        // expected fields are controlled in a relationship. If the parent is not active, then the dependent fields should be removed

        /**
         * Required fields should be removed from the form if not active
         * @var [type]
         */
        foreach ($expectedAndRequiredFields AS $fname => $field)
        {
            $fieldIsActive = $this->fieldIsActive($field, $submittedFields, $fieldGroupRelations);

            if ($fieldIsActive && !isset($submittedFields[$fname]))
            {
                $submittedFields[$fname] = '';
            }
            // If the field depends on another field and is not active, then it should not even be in the form
            elseif (!$fieldIsActive && isset($submittedFields[$fname]))
            {
                unset($submittedFields[$fname]);
            }
        }

        /**
         * Optional fields that are not active should be forced to an empty value to clear previously stored values
         */
        foreach ($expectedAndOptionalFields AS $fname => $field)
        {
            $fieldIsActive = $this->fieldIsActive($field, $submittedFields, $fieldGroupRelations);

            if (!$fieldIsActive)
            {
                $submittedFields[$fname] = '';
            }
        }
    }

    /**
     * Used in the addBackEmptyRequiredFields method to check if a given field should be considered as active in the form
     */
    protected function fieldIsActive($field, $submittedFields, $fieldGroupRelations)
    {
        /**
         * Check if this field is inside a group that is controlled by another field option
         */
        $groupControlField = Sanitize::getString($fieldGroupRelations[$field['group_id']], 'control_field');

        $groupControlValue = Sanitize::getString($fieldGroupRelations[$field['group_id']], 'control_value');

        if ($groupControlField != '' && $groupControlValue != '')
        {
            // First lets check if the control field has ANY values selected

            $controlValueSelected = Sanitize::getVar($submittedFields, $groupControlField);

            if (empty($controlValueSelected))
            {
                return false;
            }

            if (!is_array($controlValueSelected)) {
                $controlValueSelected = array($controlValueSelected);
            }

            // Now lets check if any of the selected values match the control value for this field

            $groupControlValues = explode('*',rtrim(ltrim($groupControlValue,'*'),'*'));

            foreach ($groupControlValues AS $controlValue)
            {
                if (in_array($controlValue, $controlValueSelected))
                {
                    return true;
                }
            }

            return false;
        }

        /**
         * Check the fieldOption to Field relation
         */

        $fieldRelation = $field['control_field'] != '' ? true : false;

        // This entire field (not options) is controlled by another field option, so lets check if that is active

        if ($fieldRelation)
        {
            // First lets check if the control field has ANY values selected

            $controlValueSelected = Sanitize::getVar($submittedFields, $field['control_field']);

            if (empty($controlValueSelected))
            {
                return false;
            }

            if (!is_array($controlValueSelected)) {
                $controlValueSelected = array($controlValueSelected);
            }

            // Now lets check if any of the selected values match the control value for this field

            $fieldControlValues = explode('*',rtrim(ltrim($field['control_value'],'*'),'*'));

            foreach ($fieldControlValues AS $fieldControlValue)
            {
                if (in_array($fieldControlValue, $controlValueSelected))
                {
                    return true;
                }
            }

            return false;
        }

        /**
         * Check the fieldOption to fieldOption relation
         */

        // Get the list of fields that have control over this field

        $query = '
            SELECT DISTINCT
                FieldOption.control_field
            FROM
                #__jreviews_fieldoptions AS FieldOption
            LEFT JOIN
                #__jreviews_fields AS Field ON FieldOption.fieldid = Field.fieldid
            WHERE
                Field.name = ' . $this->Quote($field['name'])
        ;

        $controlFields = array_filter($this->query($query, 'loadColumn'));

        // Field doesn't have any controls over it, so it is active by default

        if (empty($controlFields))
        {
            return true;
        }

        // There could be several control fields, so we need to loop through them

        $activeControlFields = array();

        foreach ($controlFields AS $controlFieldName)
        {
            // Check if an option was selected for the control field before moving forward

            $controlValueSelected = Sanitize::getVar($submittedFields, $controlFieldName);

            if (!empty($controlValueSelected))
            {
                // Make sure the selected values is an array because we loop through it below
                if (!is_array($controlValueSelected))
                {
                    $controlValueSelected = array($controlValueSelected);
                }

                $activeControlFields[] = array('field' => $controlFieldName, 'value' => $controlValueSelected);
            }
        }

        // If none of the control fields for this field have selected values, then this field is not active

        if (empty($activeControlFields))
        {
            return false;
        }

        // Now we need to check if any of the control field selected values actually have control over this field

        foreach ($activeControlFields AS $activeControlField)
        {
            $controlFieldName = $activeControlField['field'];

            foreach ($activeControlField['value'] AS $selectedValue)
            {
                $query = '
                    SELECT
                        count(*)
                    FROM
                        #__jreviews_fieldoptions AS FieldOption
                    WHERE
                        FieldOption.control_field = ' . $this->Quote($controlFieldName) . '
                        AND FieldOption.control_value LIKE '.$this->Quote('%*'.$selectedValue.'*%')
                ;

                $fieldIsActive = $this->query($query, 'loadResult') > 0 ? true : false;

                // If at least one control field/option is selected, then the field is active
                if ($fieldIsActive)
                {
                    return true;
                }
            }
        }

        return false;

        // prx($field['name'], (int) $fieldRelation, (int) $optionRelation);
    }

    function validate(&$data, $fieldLocation, $Access)
    {
        $valid_fields = array();

        $valid_fields_str = Sanitize::getString($data,'valid_fields');

        if(!isset($data['Field']) && !$valid_fields_str) {
            return;
        }

        $location = $fieldLocation == 'listing' ? 'content' : 'review';

        $query = "
            SELECT
                groupid
            FROM
                #__jreviews_criteria
            WHERE
                id = " . (int) $data['Criteria']['id'];

        $groupids = $this->query($query, 'loadResult');

        if ($groupids)
        {
            appLogMessage("*********Validate fields",'database');

            # PaidListings integration to remove hidden fields from validation
            $plan_fields = isset($data['Paid']) ? explode(",",Sanitize::getString($data['Paid'],'fields')) : '';

            !empty($plan_fields) and $plan_fields =  "'" . implode("','", $plan_fields) . "'";

            $queryData = array(
                    'conditions'=>array(
                        'Field.groupid IN (' . $groupids . ')',
                        'Field.published = 1',
                        "Field.location = " . $this->Quote($location),
                        "Field.type <> 'banner'"
                    )
                );

            if($location == 'content')
            {
                $plan_fields != '' and $queryData['conditions'][] = "Field.name IN (" . $plan_fields . ")";
            }

            $fields = $this->findAll($queryData);

            if (!$fields) {
                return;
            }

            // PaidListings - override field opton limit for paid categories

            if (S2App::import('Component','paid_option_limit_form','jreviews'))
            {
                $PaidOptionLimit = ClassRegistry::getClass('PaidOptionLimitFormComponent');

                $PaidOptionLimit->applyLimits($data, $fields);
            }

            $fields_for_validation = array_unique(explode(',',$valid_fields_str));

            $fieldLocation = inflector::camelize($fieldLocation);

            $submittedFields = & $data['Field'][$fieldLocation];

            // For automatic geocoding of coordinates with GeoMaps
            $Config = Configure::read('JreviewsSystem.Config');

            $latField = Sanitize::getString($Config,'geomaps.latitude');

            $lonField = Sanitize::getString($Config,'geomaps.longitude');

            $checkCoordinates = $latField != '' && $lonField != '';

            foreach ($fields as $field)
            {
                // Check validation only for displayed fields *access rights*
                if(
                    // June 9, 2016 - No longer using the browser based 'valid_fields' because this can be tampered with
                    // in_array($field['Field']['name'],$fields_for_validation)
                    isset($submittedFields[$field['Field']['name']])
                    &&
                    ($Access->in_groups($field['Field']['access'])
                        ||
                        ($checkCoordinates && in_array($field['Field']['name'],array($latField,$lonField)))
                    ))
                {

                    $value = isset($data['Field'][$fieldLocation]) ? Sanitize::getVar($data['Field'][$fieldLocation],$field['Field']['name'],'') : '';
                    /*
                    Was previously the line below. Changes made so that required checkbox fields without a checked value are properly validated
                     */
                    // $value = Sanitize::getVar($data['Field'][$fieldLocation],$field['Field']['name'],'');

                    $label = sprintf(__t("Please enter a valid value for %s.",true),$field['Field']['title']);

                    $name = $field['Field']['name'];

                    $type = $field['Field']['type'];

                    $required = $field['Field']['required'];

                    $valid_fields[$name] = $field['Field'];

                    $valid_fields[$name]['valid'] = true;

                    $regex = '';

                    if(!isset($field['Field']['_params']['valid_regex'])) {

                        switch($field['Field']['type']) {
                            case 'integer':
                            case 'relatedlisting':
                                $regex = "^[0-9]+$";
                                break;
                            case 'decimal':
                                $regex = "^(\.[0-9]+|[0-9]+(\.[0-9]*)?)$";
                                break;
                            case 'website':
                                $regex = "^((ftp|http|https)+(:\/\/)+[a-z0-9_-]+\.+[a-z0-9_-]|[a-z0-9_-]+\.+[a-z0-9_-])";
                                break;
                            case 'email':
                                $regex = ".+@.*";
                                break;
                            default:
                                $regex = '';
                                break;
                        }

                    } elseif ($type != 'date') {

                        $regex = $field['Field']['_params']['valid_regex'];
                    }

                    if (!is_array($value))
                    {
                        if ($type == 'formbuilder')
                        {
                            $required = false;

                            /**
                             * It's not a good solution so for now we are not going to use it
                             */
                            /*
                            if ($required)
                            {
                                $formDef = Sanitize::getVar($field['Field']['_params'], 'form_definition', array());
                                $modelData = json_decode($value, true);
                                $hasKey = false;
                                foreach ($formDef AS $row)
                                {
                                    if (isset($row['key']))
                                    {
                                        $hasKey = isset($row['key']) ? true : false;
                                        // This line causes a fatal error in php 5.4
                                        if (empty(Sanitize::getVar($modelData, $row['key'], array()))) {
                                            $this->validateSetError($name, $label);
                                            break;
                                        }
                                    }
                                }

                                if (!$hasKey && (empty($modelData)))
                                {
                                    $this->validateSetError($name, $label);
                                }
                            }
                            */
                        }
                        else {
                            $this->validateInput($value, $name, $type, $label, $required, $regex);
                        }
                    }
                    elseif($type == 'selectmultiple' && is_array($value[0])) {

                        $data['Field'][$fieldLocation][$field['Field']['name']] = $data['Field'][$fieldLocation][$field['Field']['name']][0];

                        $value = $value[0];

                        $value = trim(implode(',',$value));

                        $this->validateInput($value, $name, $type, $label, $required, $regex);
                    }
                    elseif($type == 'relatedlisting') {

                        foreach($value AS $val)
                        {
                            $this->validateInput($val, $name, $type, $label, $required, $regex);
                        }
                    }

                    // Validation for maximum number of selected options

                    if (in_array($type, array('selectmultiple','checkboxes','relatedlisting')) && isset($field['Field']['_params']))
                    {
                        $maxOptions = Sanitize::getString($field['Field']['_params'], 'max_options');

                        if ($maxOptions != '' && count($value) > $maxOptions)
                        {
                            $label = sprintf(__n('You can only select %d option for %s', 'You can only select %d options for %s', $maxOptions, true), $maxOptions, $field['Field']['title']);

                            $this->validateSetError($name, $label);
                        }
                    }

                }
                elseif($Access->in_groups($field['Field']['access'])) {

                    $valid_fields[$field['Field']['name']] = $field['Field'];

                    $valid_fields[$field['Field']['name']]['valid'] = false;
                }
            }

            return $valid_fields;
        }
    }

    function getRelatedListings($ids, $useKeys = true, $showCategory = false)
    {
        $results = array();

        // Remove zeroes

        $ids = array_filter($ids,'is_numeric');

        // Remove duplicates

        $ids = array_unique($ids);

        if(empty($ids)) return array();

        S2App::import('Model','everywhere_com_content','jreviews');

        $Listing = ClassRegistry::getClass('EverywhereComContentModel');

        $Listing->addStopAfterFindModel(array('Community','Favorite','Media','PaidOrder'));

        $listings = $Listing->getListingById($ids);

        $i = 0;

        foreach($listings AS $key=>$listing)
        {
            $results[$key] = array(
                'text'=>$listing['Listing']['title'] . ($showCategory ? ' (' . $listing['Category']['title'] . ')' : ''),
                'value'=>$key,
                'ordering'=>$i++,
                'route'=>cmsFramework::route($listing['Listing']['url'])
                );
        }

        if(!$useKeys)
        {
            $results = array_values($results);
        }

        return $results;
    }

    function sortArrayByArray($array,$orderArray) {
        $ordered = array();
        foreach($orderArray as $key) {
            if(array_key_exists($key,$array)) {
                $ordered[$key] = $array[$key];
            }
            unset($array[$key]);
        }
        return $ordered + $array;
    }
}