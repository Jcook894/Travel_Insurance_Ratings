<?php
/**
 * JReviews - Reviews Extension
 * Copyright (C) 2010-2015 ClickFWD LLC
 * This is not free software, do not distribute it.
 * For licencing information visit https://www.jreviews.com
 * or contact sales@jreviews.com
**/

defined( 'MVC_FRAMEWORK') or die( 'Direct Access to this location is not allowed.' );

class FieldsController extends MyController {

    var $uses = array('menu','category','field','field_option');

    var $helpers = array();

    var $components = array('everywhere','access','config');

    var $autoRender = false; //Output is returned

    var $autoLayout = false;

    function beforeFilter() {

        $this->Access->init($this->Config);

        parent::beforeFilter();
    }

    /**
    * Used for related listings field
    *
    */
    function relatedListings()
    {
        $id = Sanitize::getInt($this->params,'id');

        $criteria_id = cleanIntegerCommaList(Sanitize::getString($this->params,'listingtype'));

        $valueq = Sanitize::getString($this->params,'value');

        $fname = Sanitize::getString($this->params,'fname');

        $conditions = $joins = array();

        if($valueq != '' || $id > 0)
        {
            $field = $this->Field->findRow(array('conditions'=>array("Field.name = " . $this->Quote($fname))));

            $owner_filter = Sanitize::getBool($field['Field']['_params'],'listing_type_owner',false);

            $listing_order = Sanitize::getString($field['Field']['_params'],'listing_order','latest');

            # Check owner filter and apply only if user is member and not in editor group or above
            if(!$this->Access->isEditor() && $owner_filter && $this->_user->id > 0)
            {
                $conditions[] = 'Listing.' . EverywhereComContentModel::_LISTING_USER_ID . ' = ' . $this->_user->id;
            }
			elseif($owner_filter && $this->_user->id == 0) {

            	return cmsFramework::jsonResponse(array());
			}

            $state = 0; // Show all results. Unpublished ones will only display for publisher user group and above

            $user_id = $this->_user->id;

            $this->Listing->addStopAfterFindModel(array('Community','Favorite','Field','Media','PaidOrder'));

            $this->Listing->addCategoryFiltering($conditions, $this->Access, compact('state','criteria_id'));

            $this->Listing->addListingFiltering($conditions, $this->Access, compact('state','user_id'));

            if($valueq != '')
            {
                $conditions[] = 'Listing.' . EverywhereComContentModel::_LISTING_TITLE . ' LIKE ' . $this->QuoteLike($valueq);
            }

            if($id > 0)
            {
                $conditions[] = 'Listing.' . EverywhereComContentModel::_LISTING_ID . ' != ' . $id;
            }

            $this->Listing->processSorting($listing_order);

            $listings = $this->Listing->findAll(array(
                'conditions'=>$conditions,
                'limit'=>15
                ));

            $rows = array();

            if(!empty($listings))
            {
                foreach($listings AS $key=>$listing)
                {
                    $rows[] = (object) array('value'=>$listing['Listing']['listing_id'], 'label'=>$listing['Listing']['title'] . ' (' . $listing['Category']['title'] . ')');
                }
            }

            return cmsFramework::jsonResponse($rows);
        }
    }

    function _loadValues()
    {
        $field_id = Sanitize::getString($this->params,'field_id');

        $valueq = Sanitize::getString($this->params,'value');

        if($field_id != '') {

            $field_options = $this->FieldOption->getControlList($field_id, $valueq);

            return cmsFramework::jsonResponse($field_options);
        }
    }

    /**
    * Returns a json object of field options used to dynamicaly show and populate dependent fields
    *
    */
    function _loadFieldData($json = true, $_data = array())
    {
        !empty($_data) and $this->data = $_data;

        $fields = $field_options = $selected_values = $group_ids = array();

        $selected_values_autocomplete = array();

        $dependent_fields = $dependent_groups = $control_fields = $fields = $responses = array();

        $location = strtolower(Sanitize::getString($this->data,'fieldLocation','content'));

        $location == 'listing' and $location = 'content';

        $recursive = Sanitize::getBool($this->data,'recursive');

        $field_names = Sanitize::getVar($this->data,'fields');

        $control_field = $field_names = is_array($field_names) ? array_filter($field_names) : array($field_names);

        $page_setup = Sanitize::getInt($this->data,'page_setup',false);

        $control_value = Sanitize::getVar($this->data,'value');

        $entry_id = Sanitize::getInt($this->data,'entry_id');

        $referrer = Sanitize::getString($this->data,'referrer');

        $autocomplete = Sanitize::getBool($this->data,'autocomplete');

        $context = Sanitize::getVar($this->data,'context',array());

        $limit = null;//Sanitize::getInt($this->data,'limit');

        $edit = (bool) $entry_id || is_array($control_value); // In adv. search module we make it work like edit for previously searched values which are passed as an array in $control_value

        // Cached response for adv. search module requests
        if($json == true && $page_setup && in_array($referrer,array('adv_search_module','filtering')))
        {
            // Add the user access groups to the filename algorithm because not all fields are visible to all groups
            $this->data['aid'] = $this->Access->getAccessLevels();

            $cache_file = s2CacheKey('field_data',$this->data);

            if($cache = S2Cache::read($cache_file,'default'))
            {
                return cmsFramework::jsonResponse($cache);
            }
        }

        # Access check
        # Need to pass token to validate the listing id and check user access.

        # Filter passed field names to fix those with double underscores which are checkboxes and radiobuttons
        foreach($field_names AS $key=>$name)
        {
            if(substr_count($name, '_')>1)
            {
                $tmp = explode('_',$name); array_pop($tmp);
                $field_names[$key] = implode('_',$tmp);
            }
        }

        $field_names = array_unique($field_names);

        /**
        * We are in edit mode. Find selected values
        */
        if($page_setup && $entry_id > 0)
        {
            $PaidPlanCategoryModel = ClassRegistry::getClass('PaidPlanCategoryModel');

            $curr_field_values = $fieldValuesSelected = $fieldValuesPaid = array();

            # PaidListings integration
            if($location == 'content' && Configure::read('PaidListings.enabled') && $PaidPlanCategoryModel->isInPaidCategoryByListingId($entry_id))
            {
                // Load the paid_listing_fields table instead of the jos_content table so users can see all their
                // fields when editing a listing

                Configure::write('ListingEdit',false);

                $PaidListingFieldModel = ClassRegistry::getClass('PaidListingFieldModel');

                $fieldValuesPaid = $PaidListingFieldModel->edit($entry_id);

                if($fieldValuesPaid && !empty($fieldValuesPaid)) {

                    $fieldValuesPaid = (array) array_shift($fieldValuesPaid);

                    $fieldValuesPaid['contentid'] = $fieldValuesPaid['element_id'];

                    unset($fieldValuesPaid['element_id'], $fieldValuesPaid['email']);
                }
            }

            $query = $location == 'content' ?
                "SELECT * FROM #__jreviews_content WHERE contentid = " . $entry_id
                :
                "SELECT * FROM #__jreviews_review_fields WHERE reviewid = " . $entry_id
            ;

            if($fieldValuesSelected = $this->Field->query($query,'loadAssoc'))
            {
                foreach ($fieldValuesSelected AS $fname => $value)
                {
                    // Sep 14, 2016 - Updated to prevent stale paid field values from overriding current values. So now the paid value only overwrites the current value
                    // when the current value is empty

                    if (empty($value) && !empty($fieldValuesPaid[$fname])) {
                        $curr_field_values[$fname] = $fieldValuesPaid[$fname];
                    }
                    else {
                        $curr_field_values[$fname] = $value;
                    }
                }
            }

            if(!empty($curr_field_values))
            {
                // Filter out text based fields without pre-defined options

                $query = "
                    SELECT
                        name
                    FROM
                        #__jreviews_fields
                    WHERE
                        name IN (" . $this->Quote($field_names) . ")
                        AND type NOT IN ('text','textarea','email','website','integer','decimal','banner','code','date')
                    ";

                $valid_fields = $this->Field->query($query,'loadColumn');

                // Jan 24, 2017 - Was incorrectly passing all field values through the "asterisk exploder" instead of just those with pre-defined options

                foreach($curr_field_values AS $key=>$val)
                {
                    if(substr($key,0,3) == 'jr_' && in_array($key,$valid_fields))
                    {
                        $selected_values[$key] = $val != '' ? ( is_array($val) ? $val : explode('*',ltrim(rtrim($val,'*'),'*')) ) : array();
                    }
                }
            }

        }
        // New entry default option values
        elseif ($page_setup && $entry_id == 0 && !in_array($referrer,array('adv_search_module','filtering')))
        {
            // Mar 26, 2017 - Filter out fields that are not part of the form, like review fields shown in the new listing form
            $defaultFieldOptions = $this->Field->getDefaultOptionsByFieldName($field_names, $location);

            foreach ($defaultFieldOptions AS $fname => $defaults)
            {
                $selected_values[$fname] = $defaults;
            }
        }
        elseif (is_array($control_value)) {

            $selected_values = $control_value;

            $control_value = '';
        }

       /****************************************************************************************
       *  Control field option selected, so we find all dependent fields and groups
       *  Need to look in FieldOptions, Fields and FieldGroups
       ****************************************************************************************/
        if(!$page_setup)
        {
            # Find dependent FieldOptions
            $query = "
                SELECT
                    DISTINCT Field.name
                FROM
                    #__jreviews_fieldoptions AS FieldOption
                LEFT JOIN
                    #__jreviews_fields AS Field ON Field.fieldid = FieldOption.fieldid AND (
                        Field.published = 1 AND Field.location = " . $this->Quote($location) . "
                    )
                LEFT JOIN
                    #__jreviews_groups AS FieldGroup ON Field.groupid = FieldGroup.groupid
                WHERE
                    Field.published = 1 AND Field.location = " . $this->Quote($location) . "
                    AND FieldOption.control_field = " . $this->Quote($control_field) ." AND FieldOption.control_value LIKE " . $this->QuoteLike('*'.$control_value.'*') . "
                ORDER BY
                    FieldGroup.ordering, Field.ordering
            ";

            $field_names = $this->Field->query($query,'loadColumn');

            # Find dependent Fields
            $query = "
                SELECT
                    DISTINCT Field.name
                FROM
                    #__jreviews_fields AS Field
                LEFT JOIN
                    #__jreviews_groups AS FieldGroup ON Field.groupid = FieldGroup.groupid
                WHERE
                    Field.published = 1 AND Field.location = " . $this->Quote($location) . "
                    AND Field.control_field = " . $this->Quote($control_field) . " AND Field.control_value LIKE " . $this->QuoteLike('*'.$control_value.'*') . "
                ORDER BY
                    FieldGroup.ordering, Field.ordering
            ";

            $dep_field_names = $this->Field->query($query,'loadColumn');

            $field_names = is_array($field_names)
                            ?
                            array_merge($field_names,$dep_field_names)
                            :
                            $dep_field_names;

           # Find depedent Field Groups
           $query = "
                SELECT DISTINCT
                   FieldGroup.groupid
                FROM
                    #__jreviews_groups AS FieldGroup
                LEFT JOIN
                    #__jreviews_fields AS Field ON Field.groupid = FieldGroup.groupid
                WHERE
                    Field.published = 1 AND Field.location = " . $this->Quote($location) . "
                    AND FieldGroup.type = " . $this->Quote($location) . "
                    AND FieldGroup.control_field = ". $this->Quote($control_field) . "
                    AND FieldGroup.control_value LIKE " . $this->QuoteLike('*'.$control_value.'*') . "
                ORDER BY
                    FieldGroup.ordering
           ";

           $group_ids = $this->Field->query($query,'loadColumn');

           !empty($field_names) and $field_names = array_unique($field_names);

           if(empty($field_names) && empty($group_ids))
            {
                return cmsFramework::jsonResponse(compact('control_field','dependent_fields','dependent_groups','data'));
            }
        }

        # Get info for all fields
        $query = "
            SELECT
                Field.fieldid, Field.groupid, Field.title, Field.name, Field.type, Field.options, Field.control_field, Field.control_value, FieldGroup.name AS group_name
            FROM
                #__jreviews_fields AS Field
            LEFT JOIN
                #__jreviews_groups AS FieldGroup ON Field.groupid = FieldGroup.groupid
            WHERE
                Field.published = 1 AND Field.location = " . $this->Quote($location) . "
                AND (
                    " . (!empty($field_names) ? "Field.name IN (" . $this->Quote($field_names) . ")" : '') . "
                    " . (!empty($field_names) && !empty($group_ids) ? " OR " : '') . "
                    " . (!empty($group_ids) ? "Field.groupid IN (" . $this->Quote($group_ids). ")" : '') . "
                )
            ORDER BY
                FieldGroup.ordering, Field.ordering
        ";


        $curr_form_fields = $this->Field->query($query, 'loadAssocList', 'name');

        if(empty($curr_form_fields)) return cmsFramework::jsonResponse(compact('control_field','dependent_fields','dependent_groups','data'));

        foreach($curr_form_fields AS $key=>$curr_form_field)
        {
            $curr_form_fields[$key]['options'] = stringToArray($curr_form_field['options']);
        }


       /****************************************************************************************
       *  Check if fields have any dependents to avoid unnecessary ajax requests
       *  Three tables need to be checked: fieldoptions, fields, and fieldgroups
       ****************************************************************************************/
       # FieldOptions
       $query = "
            SELECT DISTINCT
                Field.name AS dependent_field, FieldOption.control_field
            FROM
                #__jreviews_fieldoptions AS FieldOption
            LEFT JOIN
                #__jreviews_fields AS Field ON Field.fieldid = FieldOption.fieldid
            WHERE
                Field.published = 1 AND Field.location = " . $this->Quote($location) . "
                AND FieldOption.control_field IN ( ". $this->Quote($page_setup ? array_keys($curr_form_fields) : $control_field) . ")
            " . (!$page_setup ? "AND FieldOption.control_value LIKE " . $this->QuoteLike('*'.$control_value.'*') : '' ) . "
            ORDER BY Field.ordering
       ";

       $controlling_and_dependent_fields = $this->Field->query($query,'loadAssocList');

       # Fields
       $query = "
            SELECT DISTINCT
                Field.name AS dependent_field, Field.control_field
            FROM
                #__jreviews_fields AS Field
            WHERE
                Field.published = 1 AND Field.location = " . $this->Quote($location) . "
                AND Field.control_field IN ( ". $this->Quote($page_setup ? array_keys($curr_form_fields) : $control_field) . ")
            " . (!$page_setup ? "AND Field.control_value LIKE " . $this->QuoteLike('*'.$control_value.'*') : '' ) . "
            ORDER BY Field.ordering
       ";

       $controlling_and_dependent_fields_Fields = $this->Field->query($query,'loadAssocList');

       $controlling_and_dependent_fields = is_array($controlling_and_dependent_fields)
                                            ?
                                            array_merge($controlling_and_dependent_fields,$controlling_and_dependent_fields_Fields)
                                            :
                                            $controlling_and_dependent_fields_Fields;

       # Groups
       $query = "
            SELECT DISTINCT
               FieldGroup.name AS dependent_group, FieldGroup.control_field
            FROM
                #__jreviews_groups AS FieldGroup
            LEFT JOIN
                #__jreviews_fields AS Field ON Field.groupid = FieldGroup.groupid
            WHERE
                Field.published = 1 AND Field.location = " . $this->Quote($location) . "
                AND FieldGroup.type = " . $this->Quote($location) . "
                AND FieldGroup.control_field IN ( ". $this->Quote($page_setup ? array_keys($curr_form_fields) : $control_field) . ")
            " . (!$page_setup ? "AND FieldGroup.control_value LIKE " . $this->QuoteLike('*'.$control_value.'*') : '' ) . "
            ORDER BY
                FieldGroup.ordering
       ";

       $controlling_and_dependent_fields_Groups = $this->Field->query($query,'loadAssocList');

       $controlling_and_dependent_fields = is_array($controlling_and_dependent_fields)
                                            ?
                                            array_merge($controlling_and_dependent_fields,$controlling_and_dependent_fields_Groups)
                                            :
                                            $controlling_and_dependent_fields_Groups;

        #Extract controlling and dependent fields
        foreach($controlling_and_dependent_fields AS $row)
        {
            isset($row['dependent_field']) and $dependent_fields[$row['dependent_field']] = $row['dependent_field'];

            if(isset($row['dependent_group'])) {

                $group_name = str_replace(' ','',$row['dependent_group']);

                $dependent_groups[$group_name] = $group_name;
            }

            $control_fields[$row['control_field']] = $row['control_field'];
        }

        $ids_to_names = $ids_to_names_autocomplete = $ids_to_names_noautocomplete = array();

        $control_fields_array = array();

        foreach($curr_form_fields AS $curr_form_field)
        {
            if($curr_form_field['type'] == 'relatedlisting')
            {
                $curr_form_field['options']['autocomplete'] = 1;

                $curr_form_field['options']['autocomplete.search'] = 1;
            }

            if(in_array($referrer,array('adv_search'/*,'adv_search_module'*/)) &&
                $this->Config->search_field_conversion &&
                Sanitize::getInt($curr_form_field['options'],'autocomplete.search') == 0
                // && isset($value['optionList']) && !empty($value['optionList'])
                )
            {
                switch($curr_form_field['type']) {

                    case 'radiobuttons':
                        $curr_form_field['type'] = 'checkboxes';
                    break;

                    case 'select':
                        $curr_form_field['type'] = 'selectmultiple';
                    break;
                }
            }
            // This conversion is required for dependent fields, so values are pre-selected when using a results URL to load the page
            elseif (in_array($referrer,array('filtering'))) {
                if (in_array($curr_form_field['type'], array('checkboxes','radiobuttons')))
                {
                    $curr_form_field['type'] = 'selectmultiple';
                }
            }

            $ordering = Sanitize::getVar($curr_form_field['options'],'option_ordering',null);

            $fields[$curr_form_field['name']]['name'] = $curr_form_field['name'];

            $fields[$curr_form_field['name']]['type'] = $curr_form_field['type'];

            $fields[$curr_form_field['name']]['group'] = $curr_form_field['group_name'];

            if($autocomplete)
            {
                $fields[$curr_form_field['name']]['autocomplete'] = Sanitize::getVar($curr_form_field['options'],(in_array($referrer,array('adv_search','adv_search_module')) ? 'autocomplete.search' : 'autocomplete'), 0);

                $fields[$curr_form_field['name']]['autocompletetype'] = Sanitize::getVar($curr_form_field['options'],'autocomplete.option_type','link');

                $fields[$curr_form_field['name']]['autocompletepos'] = Sanitize::getVar($curr_form_field['options'],'autocomplete.option_pos','after');
            }
            else {

                $fields[$curr_form_field['name']]['autocomplete'] = 0;
            }

            $fields[$curr_form_field['name']]['title'] = $curr_form_field['title'];

            $entry_id and $fields[$curr_form_field['name']]['selected'] = array();

            !is_null($ordering) and $fields[$curr_form_field['name']]['order_by'] = !$ordering ? 'ordering' : 'text';

            // Add selected value for text fields

            if(isset($selected_values[$curr_form_field['name']]))
            {
                switch($fields[$curr_form_field['name']]['type'])
                {
                    case 'decimal':

                        if(Sanitize::getInt($curr_form_field['options'],'curr_format') && !empty($selected_values[$curr_form_field['name']])) {

                            $decimals = Sanitize::getInt($curr_form_field['options'],'decimals',2);

                            $fields[$curr_form_field['name']]['selected'][0] = round($selected_values[$curr_form_field['name']][0],$decimals);
                        }

                        break;

                    case 'date':

                        if(isset($selected_values[$curr_form_field['name']][0]))
                        {
                            if($selected_values[$curr_form_field['name']][0] == NULL_DATE) {
                                $fields[$curr_form_field['name']]['selected'] = array();
                            }
                            else {
                                $fields[$curr_form_field['name']]['selected'] = array(str_replace(" 00:00:00","",$selected_values[$curr_form_field['name']][0]));
                            }
                        }

                        break;

                    case 'relatedlisting':

                        if(isset($selected_values[$curr_form_field['name']][0]) && $selected_values[$curr_form_field['name']][0] > 0)
                        {
                            $fields[$curr_form_field['name']]['selected'] = $selected_values[$curr_form_field['name']];

                            $fields[$curr_form_field['name']]['options'] = $this->Field->getRelatedListings($selected_values[$curr_form_field['name']], $useKeys = false, $showCategory = true);
                        }

                        break;

					case 'radiobuttons':
					case 'select':
					case 'checkboxes':
					case 'selectmultiple':

						if(!empty($selected_values[$curr_form_field['name']])) {

							$fields[$curr_form_field['name']]['selected'] = $selected_values[$curr_form_field['name']];
						}

					   break;

                    default:

                        $fields[$curr_form_field['name']]['selected'] = $selected_values[$curr_form_field['name']];

                        break;
                }
            }

            // Add control related vars
            // If field is text type, then it has no control and we check the controlBy values
            if($fields[$curr_form_field['name']]['type'] == 'text') {
                $fields[$curr_form_field['name']]['control'] = false;
                $fields[$curr_form_field['name']]['controlled'] = $curr_form_field['control_field'] != '' && $curr_form_field['control_value'];
            }
            else {
                $fields[$curr_form_field['name']]['control'] = $recursive ? true : in_array($curr_form_field['name'],$control_fields);
                $fields[$curr_form_field['name']]['controlled'] = in_array($curr_form_field['name'],$dependent_fields);
            }

            if(in_array($curr_form_field['groupid'],$group_ids)) {
                $fields[$curr_form_field['name']]['controlgroup'] = true;
            }

            // Create an array of field ids to field names used below to save on additional queries.
            // The initial field option values are loaded for the fields in this array
            if(!$page_setup
                || !$fields[$curr_form_field['name']]['autocomplete']
//                || !in_array($referrer,array('listing','review')) // Pre-load list options for control fields in search forms
                || !empty($fields[$curr_form_field['name']]['selected']) // Pre-load list options when editing if the field has any selected options.
                ) {

                if(in_array($fields[$curr_form_field['name']]['type'],array('select','selectmultiple'))) {
                    $ids_to_names[$curr_form_field['fieldid']] = $curr_form_field['name'];
                }

                if(!empty($fields[$curr_form_field['name']]['selected'])
                    && $fields[$curr_form_field['name']]['autocomplete']
                    && in_array($fields[$curr_form_field['name']]['type'],array('select','selectmultiple'))
                    ) {
                        $ids_to_names_autocomplete[$curr_form_field['fieldid']] = $curr_form_field['name'];
                        $selected_values_autocomplete = array_merge($selected_values_autocomplete,$selected_values[$curr_form_field['name']]);
                }
                elseif(!$fields[$curr_form_field['name']]['autocomplete'] && in_array($fields[$curr_form_field['name']]['type'],array('select','selectmultiple'))) {
                        $ids_to_names_noautocomplete[$curr_form_field['fieldid']] = $curr_form_field['name'];
                }
            }
            $control_fields_array[] = $curr_form_field['name'];
        }

//prx($ids_to_names);
//prx($ids_to_names_autocomplete);
//prx($ids_to_names_noautocomplete);

//prx('------------------BEGIN-------------------');
//prx($recursive);
//prx($curr_form_fields);
//prx($fields);
//prx($control_fields);
//prx('------------------END-------------------');

        /****************************************************************************************
       * Build the fields array for control and controlled fields
       ****************************************************************************************/

       # For FieldOption-FieldOption relationships get field options ordered by a-z ASC to start building the fields array.
        if(!empty($ids_to_names))
        {
            if($edit)
            {
                if(!empty($ids_to_names_autocomplete))
                {
                    $queryDataArray = array('union' => true, 'union_limit' => false);

                    foreach ($ids_to_names_autocomplete AS $fieldId => $fieldName)
                    {
                        $queryDataArray[] = array(
                            'conditions' => array(
                                'Field.published = 1',
                                'Field.location = '.$this->Quote($location),
                                $page_setup ? 'FieldOption.fieldid = ' . $fieldId : '',
                                $page_setup ?
                                    'FieldOption.control_field = ""'
                                    :
                                    "FieldOption.control_field = " . $this->Quote($control_field) ." AND FieldOption.control_value LIKE " . $this->QuoteLike('*'.$control_value.'*'),
                                    !empty($selected_values_autocomplete) ?
                                        "FieldOption.value IN ( " . $this->Quote($selected_values_autocomplete). ")"
                                        :
                                        ''

                            ),
                            'order' => array(
                                '`FieldOption.fieldid`, `FieldOption.text`'
                            ),
                            'limit' => $limit ?: null
                        );
                    }

                    $rows = $this->FieldOption->findAll($queryDataArray);

                    $field_options_ac = S2Array::pluck($rows, 'FieldOption');
                }

                if(!empty($ids_to_names_noautocomplete))
                {
                    $queryDataArray = array('union' => true, 'union_limit' => false);

                    $queryDataArraySelected = array('union' => true, 'union_limit' => false);

                    foreach ($ids_to_names_noautocomplete AS $fieldId => $fieldName)
                    {
                        $queryDataArray[] = array(
                            'conditions' => array(
                                'Field.published = 1',
                                'Field.location = '.$this->Quote($location),
                                $page_setup ? 'FieldOption.fieldid = ' . $fieldId : '',
                                $page_setup ?
                                    'FieldOption.control_field = ""'
                                    :
                                    "FieldOption.control_field = " . $this->Quote($control_field) ." AND FieldOption.control_value LIKE " . $this->QuoteLike('*'.$control_value.'*')
                            ),
                            'order' => array(
                                '`FieldOption.fieldid`, `FieldOption.text`'
                            ),
                            'limit' => $limit ?: null
                        );

                        // Make sure that the selected values are included in the results when a limit is used

                        if ($limit > 0 && isset($selected_values[$fieldName]))
                        {
                            $queryDataArraySelected[] = array(
                                'conditions' => array(
                                    'Field.published = 1',
                                    'Field.location = '.$this->Quote($location),
                                    $page_setup ? 'FieldOption.fieldid = ' . $fieldId : '',
                                    $page_setup ?
                                        'FieldOption.control_field = ""'
                                        :
                                        "FieldOption.control_field = " . $this->Quote($control_field) ." AND FieldOption.control_value LIKE " . $this->QuoteLike('*'.$control_value.'*'),
                                    "FieldOption.value IN (".$this->Quote($selected_values[$fieldName]).")"
                                ),
                                'order' => array(
                                    '`FieldOption.fieldid`, `FieldOption.text`'
                                )
                            );
                        }
                    }

                    $rows = $this->FieldOption->findAll($queryDataArray);

                    $field_options_noac = S2Array::pluck($rows, 'FieldOption');

                    if (!empty($queryDataArraySelected))
                    {
                        $rows = $this->FieldOption->findAll($queryDataArraySelected);

                        $field_options_noac_selected = S2Array::pluck($rows, 'FieldOption');

                        $field_options_noac = array_merge($field_options_noac, $field_options_noac_selected);
                    }
                }

                empty($field_options_ac) and $field_options_ac = array();

                empty($field_options_noac) and $field_options_noac = array();

                $field_options = array_merge($field_options_ac,$field_options_noac);
            }
            else {

                $queryDataArray = array('union' => true, 'union_limit' => false);

                foreach ($ids_to_names AS $fieldId => $fieldName)
                {
                    $queryDataArray[] = array(
                        'conditions' => array(
                            'Field.published = 1',
                            'Field.location = '.$this->Quote($location),
                            $page_setup ? 'FieldOption.fieldid = ' . $fieldId : '',
                            $page_setup ?
                                'FieldOption.control_field = ""'
                                :
                                "FieldOption.control_field = " . $this->Quote($control_field) ." AND FieldOption.control_value LIKE " . $this->QuoteLike('*'.$control_value.'*'),
                        ),
                        'order' => array(
                            '`FieldOption.fieldid`, `FieldOption.text`'
                        ),
                        'limit' => $limit ?: null
                    );
                }

                $rows = $this->FieldOption->findAll($queryDataArray);

                $field_options = S2Array::pluck($rows, 'FieldOption');
            }
        }

        # For FieldOption-Field relationships get field options ordered by a-z ASC to start building the fields array.
        if(!$page_setup /*&& empty($field_options) */&& !empty($ids_to_names))
        {
            $queryDataArray = array('union' => true, 'union_limit' => false);

            foreach ($ids_to_names AS $fieldId => $fieldName)
            {
                $queryDataArray[] = array(
                    'conditions' => array(
                        'Field.published = 1',
                        'Field.location = '.$this->Quote($location),
                        $page_setup ? 'FieldOption.fieldid = ' . $fieldId : '',
                        $page_setup ?
                            'Field.control_field = ""'
                            :
                            "Field.control_field = " . $this->Quote($control_field) ." AND Field.control_value LIKE " . $this->QuoteLike('*'.$control_value.'*'),
                    ),
                    'order' => array(
                        '`FieldOption.fieldid`, `FieldOption.text`'
                    ),
                    'limit' => $limit ?: null
                );
            }

            $rows = $this->FieldOption->findAll($queryDataArray);

            $field_options_OptionToField = S2Array::pluck($rows, 'FieldOption');

            $field_options = array_merge($field_options,$field_options_OptionToField);
        }

        $field_options = $this->filterOptionsByContext($field_options, $context);

        foreach($field_options AS $field_option)
        {
            $field_id = $field_option['fieldid'];

            $field_name = $field_option['name'];

            unset($field_option['fieldid'],$field_option['name']);

            if(isset($ids_to_names[$field_id]))
            {
                $fields[$ids_to_names[$field_id]]['options'][] = $field_option;

                if(isset($selected_values[$field_name]))
                {
                    $fields[$ids_to_names[$field_id]]['selected'] = $selected_values[$field_name];
                }
            }
        }

        if($page_setup)
        {
            $control_field = array_values($control_fields_array);

            $dependent_fields = array();
        }
        else {

            $control_field = $control_field;

            $dependent_fields = array_values($dependent_fields);
        }

        # Edit mode or default values: for each control field that has a selected value find dependent field options

        foreach ($selected_values AS $key=>$val)
        {
            if (!empty($val) && $val != '' && in_array($key,$field_names))
            {
                foreach ($val AS $selected)
                {
                    $res = $this->_loadFieldData(false,array('recursive'=>true,'fields'=>$key,'value'=>array_shift($val),'fieldLocation'=>$location,'limit' => $limit));

                    if (is_array($res))
                    {
                        $responses[$res['control_field'][0]][$res['control_value']] = $res;

                        foreach ($res['fields'] AS $res_fields)
                        {
                            if (isset($selected_values[$res_fields['name']]) && !empty($res_fields['options']) && empty($fields[$res_fields['name']]['options']))
                            {
                                $fields[$res_fields['name']] = $res_fields;

                                $fields[$res_fields['name']]['selected'] = $selected_values[$res_fields['name']];
                            }
                        }
                    }
                    elseif ($fields[$key]['type'] != 'text') {

                        $responses[$key][$selected] = array(
                            'location'=>$location,
                            'control_field'=>array($key),
                            'control_value'=>$selected,
                            'dependent_groups'=>array(),
                            'dependent_fields'=>array(),
                            'fields'=>array()
                        );
                    }
                }
            }
        }

/** DEBUG **/
//if($json) {prx(compact('page_setup','control_field','control_value','dependent_fields','dependent_groups','fields','responses'));}
//if($json && !$page_setup) {prx(compact('page_setup','control_field','control_value','dependent_fields','dependent_groups','fields','responses'));}

        $dependent_groups = array_values($dependent_groups);

        $location = $location == 'content' ? 'Listing' : 'Review';

        if($json) {

            $response = compact('page_setup','location','control_field','control_value','dependent_groups','dependent_fields','fields','responses');
        }

        if($json == true && $page_setup && in_array($referrer,array('adv_search_module','filtering')))
        {
            S2Cache::write($cache_file, $response, 'default');
        }

        return $json
            ?
            cmsFramework::jsonResponse($response)
            :
            compact('location','control_field','control_value','dependent_groups','dependent_fields','fields');
    }

    /**
     * Shows only the field options that are being used in the current request context (cat ID, type ID, dir ID)
     * @param  [type] $fieldOptions [description]
     * @param  [type] $context      [description]
     * @return [type]               [description]
     */
    protected function filterOptionsByContext($fieldOptions, $context)
    {
        $options = array();

        $catId = Sanitize::getInt($context,'cat');

        $typeId = Sanitize::getInt($context,'listing_type');

        $dirId = Sanitize::getInt($context,'dir');

        if (!$catId && !$typeId && !$dirId)
        {
            return $fieldOptions;
        }

        $fieldNames = array_unique(S2Array::pluck($fieldOptions, 'name'));

        $values = array();

        foreach ($fieldNames AS $fname)
        {
            $options = $this->Listing->getFieldOptionUsageCount($fname, array('cat'=>$catId, 'listing_type'=>$typeId, 'dir'=> $dirId));

            foreach($options AS $row)
            {
                $options = rtrim(ltrim($row['value'],'*'),'*');

                $options = explode('*', $options);

                if(isset($values[$fname]))
                {
                    $values[$fname] = array_unique(array_merge($values[$fname], $options));
                }
                else {
                    $values[$fname] = $options;
                }
            }
        }

        foreach($fieldOptions AS $key => $fieldOption)
        {
            if (!isset($values[$fieldOption['name']]) || !in_array($fieldOption['value'], $values[$fieldOption['name']]))
            {
                unset($fieldOptions[$key]);
            }
        }

        return $fieldOptions;
    }

 }
