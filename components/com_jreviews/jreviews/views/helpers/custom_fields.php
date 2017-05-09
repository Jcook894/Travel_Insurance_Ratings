<?php
/**
 * JReviews - Reviews Extension
 * Copyright (C) 2010-2016 ClickFWD LLC
 * This is not free software, do not distribute it.
 * For licencing information visit https://www.jreviews.com
 * or contact sales@jreviews.com
**/

defined( 'MVC_FRAMEWORK') or die( 'Direct Access to this location is not allowed.' );

class CustomFieldsHelper extends MyHelper
{
    var $helpers = array('html','form','time','routes');

    var $output = array();

    var $types = array(
            'text'=>'text',
            'select'=>'select',
            'radiobuttons'=>'radio',
            'selectmultiple'=>'select',
            'checkboxes'=>'checkbox',
            'website'=>'url',
            'email'=>'email',
            'decimal'=>'number',
            'integer'=>'number',
            'textarea'=>'textarea',
            'code'=>'textarea',
            'date'=>'date',
            'media'=>'',
            'hidden'=>'hidden',
            'relatedlisting'=>'relatedlisting',
            'formbuilder'=>'textarea'
        );

    var $legendTypes = array('radio','checkbox');

    var $multipleTypes = array('selectmultiple','relatedlisting');

    var $multipleOptionTypes = array('select','selectmultiple','checkboxes','radiobuttons');

    var $operatorTypes = array('decimal','integer','date');

    var $user_review_click2search_url;

    var $listingId;

    var $createForm = null;

    var $editForm = null;

    var $searchForm = null;

    function setReviewClick2SearchUrl($listing)
    {
        $view_all_reviews_url = $this->Routes->listing('',$listing,'user',array('return_url'=>true));

        $parts = parse_url($view_all_reviews_url);

        $query = array();

        if(isset($parts['query']))
        {
            parse_str($parts['query'], $query);
        }

        $query['{fieldname}'] = '{fieldtext}';

        $params = http_build_query($query);

        $params = str_replace(array('%7B','%7D'), array('{','}'), $params);

        $url = $parts['path'] . ($params != '' ? '?' . $params :  '');

        $this->user_review_click2search_url = $url;
    }

    function getFieldsForComparison($listings, $fieldGroups) {

        // Generate groups/fields headers
        $groups = $newGroups = array();

        foreach($fieldGroups as $group_title=>$group) {

            $group_name = $group['group_name'];

            $i = 0;

            foreach($group['Fields'] AS $field) {

                $viewAccess = $field['properties']['access_view'];

                if(Sanitize::getBool($field['properties'],'compareview') && $this->Access->in_groups($viewAccess))
                {
                   $i++;

                   $groups[$group_name]['fields'][$i]['name'] = $field['name'];

                    $groups[$group_name]['fields'][$i]['title'] = $field['title'];

                    $groups[$group_name]['group']['id'] = $field['group_id'];

                    $groups[$group_name]['group']['name'] = $group_name;

                    $groups[$group_name]['group']['title'] = $field['group_title'];

                    $groups[$group_name]['group']['group_show_title'] = $field['group_show_title'];
                }
            }
        }

        // Loop through listings and modify groups/fields headers to mark which ones should be removed if empty
        foreach($listings as $listing) {

            foreach($groups AS $gname=>$group) {

                foreach($group['fields'] AS $key=>$field) {

                    if(!empty($listing['Field']['pairs'])
                        && isset($listing['Field']['pairs'][$field['name']])
                        && !empty($listing['Field']['pairs'][$field['name']]['text'])
                        )
                    {
                        $newGroups[$gname]['group'] = $group['group'];

                        $newGroups[$gname]['fields'][$key] = $group['fields'][$key];

                    }
                }

                if(isset($newGroups[$gname]) && isset($newGroups[$gname]['fields'])) {

                    ksort($newGroups[$gname]['fields'], SORT_NUMERIC);
                }
            }
        }

        return $newGroups;
    }


    /**
     *
     * @param type $name
     * @param type $entry
     * @param type $click2search
     * @param type $outputReformat
     * @return type string
     */
    function field($name, &$entry, $click2search = true, $outputReformat = true)
    {
        if(isset($entry['Listing']))
        {
            $this->listingId = $entry['Listing']['listing_id'];
        }

        $name = strtolower($name);

        if(empty($entry['Field']) || !isset($entry['Field']['pairs'][$name]))
        {
            return false;
        }

        $viewAccess = $entry['Field']['pairs'][$name]['properties']['access_view'];

        if(!$this->Access->in_groups($viewAccess))
        {
            return false;
        }

        $values = $this->display($name, $entry, $click2search, $outputReformat);

        if($values === false)
        {
            return '';
        }
        elseif(count($values) == 1)
        {
            return implode('', $values);
        }
        else
        {
            return '<ul class="jrFieldValueList"><li>' . implode('</li><li>', $values) . '</li></ul>';
        }
    }

    function fieldValue($name, &$entry)
    {
        if(isset($entry['Listing']))
        {
            $this->listingId = $entry['Listing']['listing_id'];
        }

        $name = strtolower($name);

        $field = Sanitize::getVar($entry['Field']['pairs'],$name);

        if($field)
        {
            $output = $this->onDisplay($field,false,true,true);

            if(!in_array($field['type'],array('checkboxes','selectmultiple'))) {

                return $output[0];
            }
            else {

                return $output;
            }
        }
        else {

            return false;
        }
    }

    /**
     * Shows text values for field options even if they have an image assigned.
     */
    function fieldText($name, &$entry, $click2search = true, $outputReformat = true, $separator = ' &#8226; ')
    {
        $name = strtolower($name);

        if(empty($entry['Field']) || !isset($entry['Field']['pairs'][$name])) {
                return false;
        }

        $entry['Field']['pairs'][$name]['properties']['option_images'] = 0;

        $output = $this->display($name, $entry, $click2search, $outputReformat, false);

        return implode($separator,$output);
    }

    function display($name, &$element, $click2search = true, $outputReformat = true)
    {
        $cat_id = '';

        $MenuModel = ClassRegistry::getClass('MenuModel');

        // Need to pass by reference so the value/text can be modified inside the ::onDisplay method
        $fields = & $element['Field']['pairs'];

        if(isset($element['Listing']))
        {
            if(Sanitize::getInt($element['Listing'],'cat_id') > 0)
            {
                $cat_id = $element['Listing']['cat_id'];
            }
        }

        $criteria_id = $element['Criteria']['criteria_id'];

        $this->output = array();

        if($fields[$name]['type'] == 'email') {

            $click2search = false;

            $output_format = Sanitize::getString($fields[$name]['properties'],'output_format');

            $output_format == '' and $fields[$name]['properties']['output_format'] = '<a href="mailto:{fieldtext}">{fieldtext}</a>';
        }

        // Field specific processing

        $showImage = Sanitize::getInt($fields[$name]['properties'],'option_images',1);

        $this->onDisplay($fields[$name], $showImage);

        if(Sanitize::getBool($fields[$name]['properties'],'formatbeforeclick'))
        {
            # Output reformat
            if ($outputReformat)
            {
                $this->outputReformat($name, $fields, $element);
            }

            # Click2search
            if ($click2search)
            {
                $this->click2Search($fields[$name], $criteria_id, $cat_id);
            }
        }
        else
        {
            # Click2search
            if ($click2search)
            {
                $this->click2Search($fields[$name], $criteria_id, $cat_id);
            }

            # Output reformat
            if ($outputReformat)
            {
                $this->outputReformat($name, $fields, $element);
            }
        }

        return $this->processPhpFormat($name, $element);
    }

    function processPhpFormat($name, & $element)
    {
        $phpFormatThemeFolder = 'fields_phpformat';

        $fields = $element['Field']['pairs'];

        // Check settings-based format stored in DB

        $php_format = Sanitize::getString($fields[$name]['properties'],'php_format');

        if(trim($php_format) == '')
        {
            // Check theme-file-based format

            $php_format_theme = Sanitize::getString($fields[$name]['properties'], 'php_format_theme');

            if (!$php_format_theme || ($php_format_theme && !$this->locateThemeFile($phpFormatThemeFolder, $php_format_theme)))
            {
                return $this->output;
            }
        }

        $php_format = str_replace('\|', '|', $php_format);

        $php_format = preg_replace('#(?<!\\\)\\\n#', "\n", $php_format);

        $php_format = trim(str_replace('[:REGEX_ENTER:]', '\n', $php_format));

        $field = $fields[$name];

        if($field['type'] == 'banner')
        {
            $value = $text = $image = $field['description'];
        }
        else {

            $value = count($fields[$name]['value']) == 1 ? $fields[$name]['value'][0] : $fields[$name]['value'];

            $text = count($fields[$name]['text']) == 1 ? $fields[$name]['text'][0] : $fields[$name]['text'];

            $image = count($fields[$name]['image']) == 1 ? $fields[$name]['image'][0] : $fields[$name]['image'];;
        }

        $User = cmsFramework::getUser();

        $DB = cmsFramework::getDB();

        if ($php_format)
        {
            ob_start();

            $custom_PHP_fn = create_function('$CustomFields, $User, $DB, $Access, $name, $entry, $fields, $field, $value, $text, $image, $output', $php_format);

            $output = $custom_PHP_fn($this, $User, $DB, $this->Access, $name, $element, $fields, $field, $value, $text, $image, $this->output);

            unset($custom_PHP_fn, $User, $DB);

            ob_end_clean();
        }
        else {

            $view = new MyView($this);

            $viewVars = array(
                'CustomFields' => $this,
                'User' => $User,
                'DB' => $DB,
                'Access' => $this->Access,
                'name' => $name,
                'entry' => $element,
                'fields' => $fields,
                'field' => $field,
                'value' => $value,
                'text' => $text,
                'image' => $image,
                'output' => $this->output
            );

            $output = $view->set($viewVars)
                        ->layout('empty')
                        ->render($phpFormatThemeFolder, $php_format_theme);

            if (trim($output) == '') {
                $output = false;
            }
        }

        if(!is_array($output) && $output !== false) {

            $output = array($output);
        }

        return $output;
    }

   /**
   * Default display of custom fields
   *
   * @param mixed $entry - listing or review array
   * @param mixed $page - detail or list
   * @param mixed $group_names - group name string or group names array
   */
    function displayAll($entry, $page, $group_names = '')
    {
        if(isset($entry['Listing']))
        {
            $this->listingId = $entry['Listing']['listing_id'];
        }

        if(!isset($entry['Field']['groups'])) return '';

        $groups = array();

        $showFieldsInView = 0;

        $output = '';

        // Pre-processor to hide groups with no visible fields
        if(isset($entry['Field']['pairs']) && !empty($entry['Field']['pairs']))
        {
            foreach($entry['Field']['pairs'] AS $field)
            {
                if($field['properties'][$page.'view'] == 1 && $this->Access->in_groups($field['properties']['access_view'])) {
                    $showFieldsInView++;
                    $showGroup[$field['group_id']] = 1;
                }
            }
        }

        // Check if group name is passed as string to output only the specified group
        if(is_string($group_names))
        {
            $group_name = $group_names;
            if($group_name != '') {
                if(isset($entry['Field']['groups'][$group_name])) {
                    $groups = array($group_name=>$entry['Field']['groups'][$group_name]);
                }
            }
            elseif($showFieldsInView) {
                $groups = $entry['Field']['groups'];
            }
        }
        // Check if group names were passed as array to include or exclude the specified groups
        elseif(is_array($group_names))
        {
            if(!empty($group_names['includeGroups']))
            {
                foreach ($group_names['includeGroups'] as $group_name)
                {
                    if(isset($entry['Field']['groups'][$group_name])) {
                        $groups[$group_name] = $entry['Field']['groups'][$group_name];
                    }
                }
            }

            if(!empty($group_names['excludeGroups']))
            {
                $groups = $entry['Field']['groups'];
                foreach ($group_names['excludeGroups'] as $group_name)
                {
                    if(isset($entry['Field']['groups'][$group_name])) {
                       unset($groups[$group_name]);
                    }
                }
            }
        }

        if(empty($groups)) return '';

        $groups_output = array();

        $fields_output = array();

        foreach($groups AS $group_title=>$group)
        {
            $groups_output[$group['Group']['title']] = '';

            if(isset($showGroup[$group['Group']['group_id']]) || $group_name != '')
            {
                $groups_output[$group['Group']['title']] .= '<div class="jrFieldGroup '.$group['Group']['name'].'">';

                if($group['Group']['show_title'])
                {
                    $groups_output[$group['Group']['title']] .= '<h3 class="jrFieldGroupTitle">' . $group['Group']['title'] . '</h3>';
                }

                foreach($group['Fields'] AS $field)
                {
                    if(($field['properties'][$page.'view'] == 1) && $this->Access->in_groups($field['properties']['access_view']))
                    {
                        $values = $this->display($field['name'], $entry);

                        // If the php output format returns false, hide the field and continue with the next one

                        if(!is_array($values) && $values === false) continue;

                        $fields_output[$group['Group']['title']][] = $field['name'];

                        $groups_output[$group['Group']['title']] .= '<div class="jrFieldRow ' . lcfirst(Inflector::camelize($field['name'])) . '">';

                        $descriptionOutput = Sanitize::getInt($field['properties'],'description_output',0);

                        $groups_output[$group['Group']['title']] .= '<div class="jrFieldLabel' . ($field['properties']['show_title'] ? '' : 'Disabled') . '">'
                                                                    . ($descriptionOutput == 1
                                                                        ?
                                                                        ($field['properties']['show_title'] ? '<span class="jr-more-info">'.$field['title'].'</span>' : '')
                                                                        :
                                                                        ($field['properties']['show_title'] ? $field['title'] : '')
                                                                    )
                                                                    . ($descriptionOutput == 1 ? '<div class="jrFieldDescriptionPopup jrPopup">'.$field['description'].'</div>' : '')
                                                                    . '</div>'
                                                                    ;

                        if(count($values) == 1)
                        {
                            $groups_output[$group['Group']['title']] .= '<div class="jrFieldValue ' . ($field['properties']['show_title'] ? '' : 'jrLabelDisabled') . '">' . $values[0] . '</div>';
                        }
                        else {

                            $relatedClass = $field['type'] == 'relatedlisting' ? ' jrFieldRelated' : '';

                            $groups_output[$group['Group']['title']] .= '<div class="jrFieldValue ' . ($field['properties']['show_title'] ? '' : 'jrLabelDisabled') . '"><ul class="jrFieldValueList' . $relatedClass . '"><li>' . implode('</li><li>', $values) . '</li></ul></div>';
                        }

                         $groups_output[$group['Group']['title']] .= '</div>';
                    }
                }

                $groups_output[$group['Group']['title']] .= '</div>';
            }
        }

        foreach($groups_output AS $key=>$val)
        {
            if(empty($fields_output[$key])) unset($groups_output[$key]);
        }

        $output = '<div class="jrCustomFields">'. implode('',$groups_output) . '</div>';

        return $output;
    }

    /**
     * Returns true if there's a date field. Used to check whether datepicker library is loaded
     *
     * @param array $fields
     * @return boolean
     */
    function findDateField($fields)
    {
        if(!empty($fields))
        {
            foreach($fields AS $group=>$group_fields)
            {
                foreach($group_fields['Fields'] AS $field)
                {
                    if($field['type']=='date')
                    {
                        return true;
                    }
                }
            }
        }
        return false;
    }

    function label($name, &$entry)
    {
            if(empty($entry['Field']) || !isset($entry['Field']['pairs'][$name])) {
                    return null;
            }

            return $entry['Field']['pairs'][$name]['title'];
    }

    function isMultipleOption($name,$element)
    {
        if(isset($element['Field']['pairs'][$name]) && in_array($element['Field']['pairs'][$name]['type'],$this->multipleOptionTypes))
        {
                return true;
        }

        return false;
    }

    function onDisplay(&$field, $showImage = true, $value = false, $return = false)
    {
        if(empty($field))
        {
            return null;
        }

        $values = array();

        $option = $value ? 'value' : 'text';

        $click2search = Sanitize::getInt($field['properties'],'click2search');

        foreach($field[$option] AS $key=>$text)
        {
            switch($field['type'])
            {
                case 'banner':

                    $text = '{fieldtext}';

                    $field['properties']['output_format'] = Sanitize::getString($field,'description');

                    $field['description'] == '';

                    break;

                case 'date':

                    $format = Sanitize::getString($field['properties'],'date_format');

                    $text = $this->Time->nice($text,$format,0);

                    break;

                case 'integer':

                    if(!$click2search)
                    {
                        $text = Sanitize::getInt($field['properties'],'curr_format') ? number_format($text,0,__l('DECIMAL_SEPARATOR',true),__l('THOUSANDS_SEPARATOR',true)) : $text;
                    }

                    break;

                case 'decimal':

                    $decimals = Sanitize::getInt($field['properties'],'decimals',2);

                    if(!$click2search)
                    {
                        $text = Sanitize::getInt($field['properties'],'curr_format') ? number_format($text,$decimals,__l('DECIMAL_SEPARATOR',true),__l('THOUSANDS_SEPARATOR',true)) : round($text,$decimals);
                    }

                    break;

                case 'email':

                    break;

                case 'website':

                    $text = S2ampReplace($text);

                    !strstr($text,'://') and $text = 'http://'.$text;

                    break;

                case 'code':

                    $text = stripslashes($text);

                    break;

                case 'textarea': case 'text':

                    if(!Sanitize::getBool($field['properties'],'allow_html'))
                    {
                        $text = nl2br($text);
                    }

                    break;

                case 'selectmultiple':
                case 'checkboxes':
                case 'select':
                case 'radiobuttons':

                    $imgSrc = '';

                    if ($showImage && isset($field['image'][$key]) && $field['image'][$key] != '')  // Image assigned to this option
                    {
                        if($imgSrc = $this->locateThemeFile('theme_images',cmsFramework::locale() . '.' . trim($field['image'][$key]),'',true))
                        {
                            $imgSrc = pathToUrl($imgSrc,false /* relative URL */);
                        }
                        elseif ($imgSrc = $this->locateThemeFile('theme_images',trim($field['image'][$key]),'',true))
                        {
                            $imgSrc = pathToUrl($imgSrc,false /* relative URL */);
                        }

                        if ($imgSrc != '')
                        {
                            $text = '<img src="'.$imgSrc.'" title="'.$text.'" alt="'.$text.'" border="0" />';
                        }
                    }

                    break;

                case 'formbuilder':

                    // Need to pass through html_entity_decode in order to be able to json_decode the string into an array

                    $field['value'][0] = html_entity_decode($text);

                    $field['text'][0] = html_entity_decode($text);

                    $text = $field['text'][0];

                break;

                default:

                    $text = stripslashes($text);

                    break;
            }

            $values[] = $text;

            $this->output[] = $text;
        }

        if($return)
        {
            return $values;
        }
    }

    function click2Search($field, $criteria_id, $cat_id)
    {
        if(!Sanitize::getInt($field['properties'],'click2search')) return;

        foreach($this->output AS $key=>$text)
        {
            switch($field['type']) {

                case 'date':

                    $field['value'][$key] = str_ireplace(' 00:00:00','',$field['value'][$key]);

                break;

                case 'integer':

                    $text = Sanitize::getInt($field['properties'],'curr_format') ? number_format($text,0,__l('DECIMAL_SEPARATOR',true),__l('THOUSANDS_SEPARATOR',true)) : $text;

                break;


                case 'decimal':

                    $decimals = Sanitize::getInt($field['properties'],'decimals');

                    $text = Sanitize::getInt($field['properties'],'curr_format') ? number_format($text,$decimals,__l('DECIMAL_SEPARATOR',true),__l('THOUSANDS_SEPARATOR',true)) : round($text,$decimals);

                break;

                case 'text':

                    #Fixed: Click2search URL doesn't work with text custom fields which contain special characters (quotes, ampersands).

                    $field['value'][$key] = html_entity_decode($field['value'][$key]);

                break;
            }

            // Replace tags in click2search URL

            if(in_array($field['properties']['location'],array('listing','content')))
            {
                $click2search_format = Sanitize::stripWhiteSpace(Sanitize::getString($field['properties'],'click2search_format','<a href="{click2searchurl}">{optiontext}</a>'));

                $click2search_format = stripslashes($click2search_format);

                $click2search_url = Sanitize::getString($field['properties'],'click2searchlink','tag/{fieldname}/{optionvalue}/?criteria={criteriaid}');

                $fieldText = $text;

                $fieldValue = urlencode($field['value'][$key]);

                // Fix for quotes and ampersands which are urlencoded from entities
                // - single quote "%26%23039%3B"
                // - ampersand "%26amp%3B"
                // - double quote "%26quot%3B"
                $fieldValue = str_replace(array('%26%23039%3B','%26amp%3B','%26quot%3B'), array('%27','%26','%22'), $fieldValue);

                $url = str_ireplace(
                    array(
                        '{fieldname}',
                        '{fieldtext}',
                        '{optionvalue}',
                        '{optiontext}',
                        '{listing_id}'
                    ),
                    array(
                        substr($field['name'],3),
                        $fieldValue,
                        $fieldValue,
                        urlencode($field['text'][$key]),
                        $this->listingId
                    ),
                    $click2search_url
                );

                $return_url = 1;

                $url = $this->Routes->click2search('', $url, compact('criteria_id','cat_id','return_url'));

                if(substr($url,0,5) == 'index') {

                    $url = cmsFramework::route($url);
                }
                elseif(substr($url,0,4) == 'http') {

                    $url = $url;
                }
                else {

                    $url = WWW_ROOT . ltrim($url, '/');
                }

                $this->output[$key] = str_ireplace(
                    array(
                        '{click2searchurl}',
                        '{fieldtext}',
                        '{optiontext}',
                        '{optionvalue}',
                        '{listing_id}'
                    ),
                    array(
                        $url,
                        $fieldText,
                        $fieldText,
                        $fieldValue,
                        $this->listingId
                    ),
                    $click2search_format
                );
            }
            elseif($this->user_review_click2search_url != '') {

                $url = str_ireplace(
                    array(
                        '{fieldname}',
                        '{fieldtext}',
                        '{listing_id}'
                    ),
                    array(
                        $field['name'],
                        urlencode($field['value'][$key]),
                        $this->listingId
                    ),
                    $this->user_review_click2search_url
                );

                $this->output[$key] = $this->Html->link($text, $url, array('sef'=>false));
            }
        }
    }

    function outputReformat($name, &$fields, $element = array(), $return = false)
    {
        $field_names = array_keys($fields);

        // Listing vars
        $title = isset($element['Listing']) && Sanitize::getString($element['Listing'],'title') ?
                    $element['Listing']['title'] : '';

        $alias = isset($element['Listing']) && Sanitize::getString($element['Listing'],'slug') ?
                    $element['Listing']['slug'] : '';

        $category = isset($element['Listing']) && isset($element['Category']) ? Sanitize::getString($element['Category'],'title') : '';

        // Check if there's anything to do
        if ((isset($fields[$name]['properties']['output_format'])
             &&
             trim(strtolower($fields[$name]['properties']['output_format'])) != '{fieldtext}'
             )
                ||
                $fields[$name]['type'] == 'banner'
            )
        {

            $format = Sanitize::stripWhiteSpace($fields[$name]['properties']['output_format']);

            // Remove any references to current field in the output format to avoid an infinite loop
            $format = str_ireplace('{'.$name.'}','{fieldtext}',$format);

            $curr_value = '';

            // Find all custom field tags to replace in the output format
            $matches = array();

            $regex = '/(jr_[a-z0-9]{1,}\|valuenoimage)|(jr_[a-z0-9]{1,}\|value)|(jr_[a-z0-9]{1,})/i';

            preg_match_all( $regex, $format, $matches );

            $matches = $matches[0];

            // Loop through each field and make output format {tag} replacements
            foreach ($this->output AS $key=>$text)
            {
                $text = str_ireplace('{fieldtext}', $text, $format);

                $text = str_ireplace('{fieldtitle}', $fields[$name]['title'], $text);

                $text = str_ireplace('{listing_id}', $this->listingId, $text);

                !empty($title) and $text = str_ireplace('{title}', $title, $text);

                !empty($alias) and $text = str_ireplace('{alias}', $alias, $text);

                !empty($category) and $text = str_ireplace('{category}', $category, $text);

                if(strstr(strtolower($text),'{optionvalue}'))
                {
                    $text = str_ireplace('{optionvalue}',$fields[$name]['value'][$key],$text);
                }

                // Quick check to see if there are custom fields to replace
                if (empty($matches)) {
                    $this->output[$key] = $text;
                }

                foreach($matches AS $curr_key)
                {
                    $backupOutput = $this->output;

                    $this->output = array();

                    $parts = explode('|',$curr_key);

                    $fname = $parts[0];

                    $curr_text = '';

                    if(isset($element['Field']['pairs'][$fname])) {

                        // Read the current value to restore it further below
                        $show_option_image = Sanitize::getInt($element['Field']['pairs'][$fname]['properties'],'option_images');

                        $text_only = isset($parts[1]) && strtolower($parts[1]) == 'valuenoimage';

                        $value_only = $text_only || (isset($parts[1]) && strtolower($parts[1]) == 'value');

                        if($text_only) {

                            $element['Field']['pairs'][$fname]['properties']['option_images'] = 0;
                        }

                        $curr_text = $this->field($fname,$element,!$value_only,!$value_only); //stripslashes($fields[strtolower($curr_key)]['text'][0]);

                        if($text_only) {

                            $element['Field']['pairs'][$fname]['properties']['option_images'] = $show_option_image;

                        }

                        $this->output = $backupOutput;

                    }

                    $text = str_ireplace('{'.$curr_key.'}', $curr_text, $text);
                }

                $this->output[$key] = $text;
            }
        }
    }

    /**
     * Dynamic form creation for custom fields with default layout
     *
     * @param unknown_type $formFields
     * @param unknown_type $fieldLocation
     * @param unknown_type $search
     * @param unknown_type $selectLabel
     * @return unknown
     */
    function makeFormFields(&$formFields, $fieldLocation, $search = null, $selectLabel = 'Select')
    {
        if(!is_array($formFields)) {
            return '';
        }

        $groupSet = array();

        $fieldLocation = Inflector::camelize($fieldLocation);

        foreach($formFields AS $group=>$fields)
        {
            $inputs = array();

            $group_name = isset($fields['group_name']) ? 'group_'.str_replace(' ','',$fields['group_name']) : 'group_';

            foreach ($fields['Fields'] AS $key=>$field)
            {
                if((!$search && $this->Access->in_groups($field['properties']['access'])) || ($search && $this->Access->in_groups($field['properties']['access_view'])))
                {
                    $input = $this->prepareInputFromField($field, $fieldLocation, $search, $selectLabel);

                    $inputs["data[Field][$fieldLocation][$key]"] = $input;

                }

            }

            if (!empty($inputs))
            {
                $groupSet[$group_name] = array(
                    'fieldset'=>true,
                    'legend'=>$group
                );

                foreach($inputs AS $dataKey=>$dataValue) {
                    $groupSet[$group_name][$dataKey] = $dataValue;
                }
            }
        }

        $output = '';

        foreach($groupSet AS $group=>$form) {

            $output .= $this->Form->inputs($form,array('id'=>$group,'class'=>'jrHidden jrFieldsetMargin'));

        }

        $this->reset();

        return $output;
    }

    function prepareInputFromField($field, $fieldLocation, $search, $selectLabel)
    {
        $autoComplete = false;

        if ($field['type'] == 'banner') return false;

        if ($field['type'] == 'formbuilder' && $search) return false;

        $addFieldLabel = !$search || $search == 'page';

        // Convert radio button to checkbox if multiple search is enabled in the config settings

        if($search && $this->Config->search_field_conversion && $field['type']=='radiobuttons')
        {
            $field['type'] = 'checkboxes';
        }

        $input = array(
            'class' => $field['name'],
            'type' => $this->types[$field['type']],
            'before' => '',
            'after' => '',
            'div' => false
        );

        if ($addFieldLabel)
        {
            // Field label

            $input['label']['text'] = $field['title'];

            $input['label']['class'] = 'jrLabel';

            // Required indicator

            if ($field['required'])
            {
                $input['label']['text'] .= '<span class="jrIconRequired"></span>';
            }
        }

        // Check for AutoCompleteUI

        if((!$search && Sanitize::getString($field['properties'],'autocomplete') ==1 )
            ||
            ($search && Sanitize::getString($field['properties'],'autocomplete.search') == 1))
        {
            $autoComplete = true;
            $input['class'] .= ' jrAutoComplete';
            $input['data-field'] = htmlentities(json_encode(array('name'=>$field['name'],'id'=>$field['field_id'])),ENT_QUOTES,'utf-8');
        }

        // $input['div'] = array();

        //  Add tooltip

        $tooltipPosition = Sanitize::getInt($field['properties'],'description_position');

        $description = Sanitize::getString($field,'description',null);

        if (!$search && $description)
        {
            switch($tooltipPosition) {
                case 0:
                case 1:
                    $input['label']['text'] .= '<span class="jrIconInfo jr-more-info">&nbsp;</span><div class="jrPopup">'.$description.'</div>';
                break;
                case 2:
                    $input['between'] = '<div class="jrFieldDescription jrAbove">'.$description.'</div>';
                break;
                case 3:
                    $input['after'] = '<div class="jrClear jrFieldDescription jrBelow">'.$description.'</div>';
                break;
                case 4:
                    $input['placeholder'] = __t($description,true,true);
                    break;
            }
        }
        elseif ($search && $tooltipPosition == 4 && $description) {
            $input['placeholder'] = __t($description,true,true);
        }

        if (in_array($this->types[$field['type']],$this->legendTypes))
        {
            // Input styling

            $input['option_class'] = 'jr-option jrFieldOption';
        }

        // Add search hints for multiple choice fields

        if ($search == 'page')
        {
            switch($field['type']) {

                case 'radiobuttons':
                case 'select':

                    if($this->Config->search_field_conversion) {

                        $input['before'] = '<span class="jrFieldBefore">'.JreviewsLocale::getPHP('SEARCH_RESULTS_MATCH_ANY').'</span>';
                    }
                break;

                case 'selectmultiple':
                case 'checkboxes':

                        $input['before'] = '<span class="jrFieldBefore">'.JreviewsLocale::getPHP('SEARCH_RESULTS_MATCH_ALL').'</span>';
                break;
            }

            if($this->Config->search_field_conversion
                && Sanitize::getInt($field['properties'],'autocomplete.search') == 0
                // && isset($field['optionList']) && !empty($field['optionList'])
                )
            {
                switch($field['type']) {

                    case 'radiobuttons':
                        $field['type'] = 'checkboxes';
                    break;

                    case 'select':
                        $field['type'] = 'selectmultiple';
                    break;
                }
            }
        }

        // Assign field classes and other field type specific changes

         switch ($field['type'])
         {
            case 'decimal':
                $input['class'] .= ' jrDecimal';
            break;
            case 'integer':
                $input['class'] .= ' jrInteger';
            break;
            case 'code':
                $input['class'] .= ' jrCode';
            break;
            case 'website':
                $input['class'] .= ' jrWebsite';
            break;
            case 'email':
                $input['class'] .= ' jrEmail';
            break;
            case 'text':
                $input['class'] .= ' jrText';
            break;
            case 'relatedlisting':
                $input['class'] .= ' jrRelatedListing';
                $input['data-listingtype'] = Sanitize::getString($field['properties'],'listing_type');
            break;
            case 'textarea':
                $input['class'] .= ' jrTextArea';
                if(!$search && Sanitize::getInt($field['properties'],'editor'))
                {
                    $input['class'] .= ' jrHidden';
                    $input['id'] = $field['name'] . '-editor';
                    $input['after'] .= '<trix-editor input="'. $field['name'] . '-editor' .'"></trix-editor>';
                }
            break;
            case 'formbuilder':
                if (version_compare(PHP_VERSION, '5.4.0', '>='))
                {
                    $jsonSchema = json_encode(Sanitize::getVar($field['properties'],'json_schema', array()), JSON_UNESCAPED_UNICODE);
                }
                else {
                    $jsonSchema = json_encode(Sanitize::getVar($field['properties'],'json_schema', array()));
                }
                $field['selected'] = isset($field['selected']) ? $field['selected']: json_encode(Sanitize::getVar($field['properties'],'default', new stdClass));
                $input['class'] .= ' jrHidden';
                $input['jr-model'] = '';
                $input['before'] .= '<div jr-formbuilder class="jrFormBuilder">';
                $input['after'] .= sprintf('
                            <textarea jr-schema>%s</textarea>
                            <div jr-form></div>
                        </div>
                ', $jsonSchema
                );
            break;
            case 'select':
                $input['class'] .= ' jrSelect';
            break;
            case 'selectmultiple':
                $input['class'] .= ' jrSelectMultiple';
            break;
            case 'date':
                $input['class'] .= ' jr-date jrDate';
                if ($search)
                {
                    $input['readonly'] = 'readonly';
                }
                $yearRange = Sanitize::getString($field['properties'],'year_range');
                $minDate = Sanitize::getString($field['properties'],'min_date');
                $maxDate = Sanitize::getString($field['properties'],'max_date');
                $input['data-yearrange'] = $yearRange != '' ? $yearRange : 'c-10:c+10';
                $input['data-mindate'] = $minDate != '' ? $minDate : '';
                $input['data-maxdate'] = $maxDate != '' ? $maxDate : '';
            break;
        }

        // Add data attributes

        $dataAttributes = Sanitize::getVar($field, 'data', array());

        foreach ($dataAttributes AS $dataAttrKey => $dataAttrValue)
        {
            $input['data-'.$dataAttrKey] = $dataAttrValue;
        }

        if (in_array($field['type'],$this->multipleTypes))
        {
            $input['multiple'] = 'multiple';

            if( ($size = Sanitize::getInt($field['properties'],'size')) )
            {
                $input['size'] = $size;
            }
        }

        if (isset($field['optionList']) && $field['type'] == 'select')
        {
            $field['optionList'] = array(''=>$selectLabel) + $field['optionList'];
        }

        if (isset($field['optionList']))
        {
            $input['options'] = $field['optionList'];
        }

        // Add click2add capability for select lists
        // Autosuggest fields are excluded because the autcomplete widget automatically adds the button

        $click2add = Sanitize::getInt($field['properties'],'click2add');

        if($autoComplete && !$search && $this->types[$field['type']] == 'select' && $click2add)
        {
            $input['data-click2add'] = 1;
        }
        elseif (!$autoComplete && !$search && $this->types[$field['type']] == 'select' && $click2add) {

            $input['data-click2add'] = 1;

            $input['style'] = 'float:left;';

            $click2AddLink = $this->Form->button('<span class="jrIconNew"></span>'.__t("Add",true),array('class'=>'jr-click2add-new jrButton jrLeft'));

            $click2AddInput = $this->Form->text(
                'jrFieldOption'.$field['field_id'],
                array('class'=>'jrFieldOptionInput','data-fid'=>$field['field_id'],'data-fname'=>$field['name'])
            );

            $click2AddButton = $this->Form->button(__t("Submit",true),array('div'=>false,'class'=>'jr-click2add-submit jrButton'));

            $input['after'] =
                $click2AddLink
                . "<div class='jr-click2add-option jrNewFieldOption'>"
                . $click2AddInput . ' '
                . $click2AddButton
                . "<span class=\"jrLoadingSmall jrHidden\"></span>"
                . '</div>'
                . $input['after']
                ;
        }

        // Prefill values when editing or after a search

        $selected = Sanitize::getVar($field,'selected',array());

        $selectedOperator = Sanitize::getString($selected, 0, '');

        if(!empty($selected))
        {
            if (!$search)
            {
                $input['value'] = $field['selected'];
            }
            elseif (!empty($field['selected'])) {

                if(in_array($field['type'],$this->operatorTypes) && in_array($selectedOperator, array('between','lower','higher')))
                {
                    array_shift($selected);

                    if ($field['type'] == 'date')
                    {
                        array_shift($selected);
                    }

                    $selected = array_values($selected);

                    $input['value'] = $selected[0];
                }
                else {

                    $input['value'] = $field['selected'];

                    $input['data-selected'] = implode('_',$field['selected']);
                }
            }
        }

        // Add default options for new forms

        if (!$this->editForm && !empty($field['default_options']))
        {
            $input['value'] = $field['default_options'];

            $input['data-selected'] = implode('_',$field['default_options']);
        }

        // Add search operator fields for date, decimal and integer fields

        if($search && in_array($field['type'],$this->operatorTypes))
        {
            $options = array(
                'equal'=>'=',
                'higher'=>'&gt;=',
                'lower'=>'&lt;='
                ,'between'=>__t("between",true)
            );

            $input['multiple'] = true; // convert field to array input for range searches

            $attributes = array('id'=>$field['name'].'high','multiple'=>true);

            switch($field['type'])
            {
                case 'integer':
                    $attributes['class'] = 'jrInteger';
                break;
                case 'decimal':
                    $attributes['class'] = 'jrDecimal';
                break;
                case 'date':
                    $attributes['class'] = 'jr-date jrDate';
                    $selected = array_values($selected);
                break;
            }

            $showHighRange = $selectedOperator == 'between';

            $showHighRange and $attributes['value'] = Sanitize::getString($selected, 1);

            // This is the high value input in a range search
            $input['after'] = '<span '.(!$showHighRange ? 'class="jrHidden"' : '').'>&nbsp;'.$this->Form->text("data[Field][Listing][".$field['name']."]",$attributes).'</span>';

            $input['between'] = $this->Form->select("data[Field][Listing][".$field['name']."_operator]",$options,$selectedOperator,array('class'=>'jr-search-range jrSearchOptions'));
        }

        if ($addFieldLabel)
        {
            $input['div'] = 'jrFieldDiv ' . lcfirst(Inflector::camelize($field['name']));
        }

        if ($search)
        {
            $input['data-search'] = 1;
        }

        return $input;
    }

    function renderInputFromField($field, $fieldLocation = 'listing', $search = null, $selectLabel = 'Select' )
    {
        if ($input = $this->prepareInputFromField($field, $fieldLocation, $search, $selectLabel))
        {
            $fieldLocation = Inflector::camelize($fieldLocation);

            $fname = $field['name'];

            $input['div'] = false;

            return $this->Form->input("data[Field][$fieldLocation][$fname]", $input);
        }

        return false;
    }

    /**
     * Dynamic form creation for custom fields using custom layout - {field tags} in view file
     *
     * @param unknown_type $formFields
     * @param unknown_type $fieldLocation
     * @param unknown_type $search
     * @param unknown_type $selectLabel
     * @return array of form inputs for each field
     */
    function getFormFields(&$formFields, $fieldLocation = 'listing', $search = null, $selectLabel = 'Select' ) {

        if(!is_array($formFields)) {
            return '';
        }

        $groupSet = array();

        $inputs = array();

        $fieldLocation = Inflector::camelize($fieldLocation);

        foreach($formFields AS $group=>$fields)
        {
            foreach($fields['Fields'] AS $fname => $field)
            {
                if ($input = $this->prepareInputFromField($field, $fieldLocation, $search, $selectLabel))
                {
                    $inputs["data[Field][$fieldLocation][$fname]"] = $input;
                }
            }

            $groupSet[$group] = array(
                'fieldset'=>false,
                'legend'=>false
            );

            foreach($inputs AS $dataKey=>$dataValue) {
                $groupSet[$group][$dataKey] = $dataValue;
            }
        }

        $output = array();

        foreach($groupSet AS $group=>$form)
        {
            $output = array_merge($output,$this->Form->inputs($form,null,null,true));
        }

        $this->reset();

        return $output;
    }

    function fieldOptionFormat($option, $url, $params)
    {
        $displayMode = Sanitize::getString($params, 'display_mode');

        $showImage = Sanitize::getString($params, 'show_image');

        $showCount = Sanitize::getBool($params, 'show_count');

        $text = Sanitize::getString($option['FieldOption'], 'text');

        $image = Sanitize::getString($option['FieldOption'], 'image');

        $count = Sanitize::getInt($option['FieldOption'], 'count');

        $imageSrc = '';

        $imageTag = '';

        if($displayMode == 'select')
        {
            $output = $text;

            if($showCount)
            {
                $output .= ' (' . $count . ')';
            }
        }
        else {

            $output = '<span class="jrFieldOptionText">'. $text . '</span>';

            if($showImage && $image != '') {

                if($imgSrc = $this->locateThemeFile('theme_images',cmsFramework::locale() . '.' . trim($image),'',true))
                {
                    $imgSrc = pathToUrl($imgSrc,false /* relative URL */);
                }
                elseif($imgSrc = $this->locateThemeFile('theme_images',trim($image),'',true))
                {
                    $imgSrc = pathToUrl($imgSrc,false /* relative URL */);
                }

                if($imgSrc != '' )
                {
                    $imageTag = '<span class="jrFieldOptionImage"><img src="'.$imgSrc.'" title="'.$text.'" alt="'.$text.'" border="0" /></span>';

                    switch($showImage)
                    {
                        case 'image_only':

                            $output = $imageTag;

                            break;

                        case 'before':

                            $output = $imageTag . $text;

                            break;

                        case 'after':

                            $output .= $imageTag;

                            break;
                    }
                }
            }

            if($showCount)
            {
                $output .= ' <span class="jrFieldOptionCount">(' . $count . ')</span>';
            }
        }

        return $output;
    }

    public function createOrEdit($isNew)
    {
        $this->createForm = $isNew ? true : false;

        $this->editForm = !$this->createForm;

        return $this;
    }

    protected function reset()
    {
        $this->createForm = null;

        $this->editForm = null;

        $this->searchForm = null;
    }

}

//      return $this->Form->inputs
//          (
//              array(
//                  'fieldset'=>true,
//                  'legend'=>'Group XYZ',
//                  'data[Field][jr_text]'=>
//                  array(
//                      'label'=>array('for'=>'jr_text','text'=>'Text Field'),
//                      'id'=>'jr_text',
//                      'type'=>'text',
//                      'size'=>'10',
//                      'maxlength'=>'100',
//                      'class'=>'{required:true}'
//                  ),
//                  'data[Field][jr_select]'=>
//                  array(
//                      'label'=>array('for'=>'select','text'=>'Select Field'),
//                      'id'=>'select',
//                      'type'=>'select',
//                      'options'=>array('1'=>'1','2'=>'2'),
//                      'selected'=>2
//                  ),
//                  'data[Field][jr_selectmultiple]'=>
//                  array(
//                      'label'=>array('for'=>'selectmultiple','text'=>'Multiple Select Field'),
//                      'id'=>'selectmultiple',
//                      'type'=>'select',
//                      'multiple'=>'multiple',
//                      'size'=>'2',
//                      'options'=>array('1'=>'email','2'=>'asdfasdf'),
//                      'value'=>array(1,2)
//                  ),
//                  'data[Field][jr_checkbox]'=>
//                  array(
//                      'label'=>false,
//                      'legend'=>'Checkboxes',
//                      'type'=>'checkbox',
//                      'options'=>array('1'=>'Option 1','2'=>'Option 2'),
//                      'value'=>array(2),
//                      'class'=>'{required:true,minLength:2}'
//                  ),
//                  'data[Field][jr_radio]'=>
//                  array(
//                      'legend'=>'Radio Buttons',
//                      'type'=>'radio',
//                      'options'=>array('1'=>'Option 1','2'=>'Option 2'),
//                      'value'=>1,
//                      'class'=>'{required:true}'
//                  )
//
//              )
//          );

?>