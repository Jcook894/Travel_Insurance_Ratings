<?php
/**
 * JReviews - Reviews Extension
 * Copyright (C) 2010-2012 ClickFWD LLC
 * This is not free software, do not distribute it.
 * For licencing information visit http://www.reviewsforjoomla.com
 * or contact sales@reviewsforjoomla.com
**/

defined( 'MVC_FRAMEWORK') or die( 'Direct Access to this location is not allowed.' );

class CustomFieldsHelper extends MyHelper
{		
    var $helpers = array('html','form','time');

    var $output = array();

    var $form_id = null; // Used to create unique element ids

    var $types = array(
            'text'=>'text',
            'select'=>'select',
            'radiobuttons'=>'radio',
            'selectmultiple'=>'select',
            'checkboxes'=>'checkbox',
            'website'=>'text',
            'email'=>'text',
            'decimal'=>'text',
            'integer'=>'text',
            'textarea'=>'textarea',
            'code'=>'textarea',
            'date'=>'date',
            'media'=>'',
    'hidden'=>'hidden',
    'relatedlisting'=>'text'
    );

    var $legendTypes = array('radio','checkbox');

    var $multipleTypes = array('selectmultiple');

    var $multipleOptionTypes = array('select','selectmultiple','checkboxes','radiobuttons');		

    var $operatorTypes = array('decimal','integer','date');

        
    function getFieldsForComparison($listings) {

            $groups = array();

            foreach ($listings as $listing) {

                    if(!empty($listing['Field']['pairs']))
        {
            foreach($listing['Field']['pairs'] AS $field)	
                            {
                                    $viewAccess = $field['properties']['access_view'];               
                if($field['properties']['compareview'] && $this->Access->in_groups($viewAccess))
                {
                    $groups[$field['group_name']]['fields'][$field['name']]['name'] = $field['name'];
                    $groups[$field['group_name']]['fields'][$field['name']]['title'] = $field['title'];
                                            $groups[$field['group_name']]['group']['id'] = $field['group_id'];
                                            $groups[$field['group_name']]['group']['name'] = $field['group_name'];
                                            $groups[$field['group_name']]['group']['title'] = $field['group_title'];
                                            $groups[$field['group_name']]['group']['group_show_title'] = $field['group_show_title'];	
                                    }
            }
        }
            }
            return $groups;	
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
        $name = strtolower($name);
        if(empty($entry['Field']) || !isset($entry['Field']['pairs'][$name])) {
            return false;            
        }

        $viewAccess = $entry['Field']['pairs'][$name]['properties']['access_view'];
        if(!$this->Access->in_groups($viewAccess)){
            return false;        
        }
        
        $values = $this->display($name, $entry, $click2search, $outputReformat);		
		
        if(count($values) == 1) {
            return $values[0];
        }   
        else {
            return '<ul class="fieldValueList"><li>' . implode('</li><li>', $values) . '</li></ul>';
        }                             
    }
	
    function fieldValue($name,&$entry) {
        $name = strtolower($name);
        if(isset($entry['Field']['pairs'][$name])){
            return $this->onDisplay($entry['Field']['pairs'][$name],false,true,true);
        } else {
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
        $Itemid = $catid = '';
        $MenuModel = ClassRegistry::getClass('MenuModel');
        $fields = $element['Field']['pairs'];
        
        if(isset($element['Listing'])) {
            
            $click2searchUrl = Sanitize::getString($fields[$name]['properties'],'click2searchlink');
            
            if(Sanitize::getInt($element['Category'],'menu_id') > 0 && strstr(strtolower($click2searchUrl),'{catid}')) {
                $Itemid = $element['Category']['menu_id'];        
                $fields[$name]['properties']['click2searchlink'] = str_ireplace(array('/cat:{catid}','/criteria:{criteriaid}'),'',$click2searchUrl);
            } 
            else {
                $MenuModel = ClassRegistry::getClass('MenuModel');
                if(strstr(strtolower($click2searchUrl),'{criteriaid}')) {
                    $Itemid = $MenuModel->get('jr_advsearch_'.$element['Criteria']['criteria_id']);
                   $fields[$name]['properties']['click2searchlink'] = str_ireplace('/criteria:{criteriaid}','',$click2searchUrl);
                } 
                if(empty($Itemid)) {
                    $Itemid = $MenuModel->get('jr_advsearch');
                }
            }
            if(Sanitize::getInt($element['Listing'],'cat_id') > 0) {
                $catid = $element['Listing']['cat_id'];        
            } 
        }    

        $criteriaid = $element['Criteria']['criteria_id'];	
        $this->output = array();

        if($fields[$name]['type'] == 'email') {
            $click2search = false;
            $output_format = Sanitize::getString($fields[$name]['properties'],'output_format');
            $output_format == '' and $fields[$name]['properties']['output_format'] = '<a href="mailto:{FIELDTEXT}">{FIELDTEXT}</a>';
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
            if (in_array($fields[$name]['properties']['location'],array('listing','content')) 
                && ($click2search && $fields[$name]['properties']['click2search'])) 
                {
                $this->click2Search($fields[$name], $criteriaid, $catid, $Itemid);        
            }
        }
        else
        {
            # Click2search
            if (in_array($fields[$name]['properties']['location'],array('listing','content')) 
                && ($click2search && Sanitize::getString($fields[$name]['properties'],'click2search'))) 
            {
                $this->click2Search($fields[$name], $criteriaid, $catid, $Itemid);        
            }
            # Output reformat        
            if ($outputReformat) 
            {                            
                $this->outputReformat($name, $fields, $element);
            }
        }

        return $this->output;
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

        $output .= '<div class="jr_customFields">';

        foreach($groups AS $group_title=>$group)
        { 
            if(isset($showGroup[$group['Group']['group_id']]) || $group_name != '')
            {
                $output .= '<div class="fieldGroup '.$group['Group']['name'].'">';

                $group['Group']['show_title'] and $output .= '<h3 class="fieldGroupTitle">' . $group['Group']['title'] . '</h3>';

                foreach($group['Fields'] AS $field)
                {
                    if(($field['properties'][$page.'view'] == 1) && $this->Access->in_groups($field['properties']['access_view']))
                    {
                        $output .= '<div class="fieldRow ' . $field['name'] . '">'; 							 

                        $output .= '<div class="fieldLabel' . ($field['properties']['show_title'] ? '' : 'Disabled') . '">' . ($field['properties']['show_title'] ? $field['title'] : '') . '</div>';

                        $values = $this->display($field['name'], $entry);

                        if(count($values) == 1) {
                            $output .= '<div class="fieldValue ' . ($field['properties']['show_title'] ? '' : 'labelDisabled') . '">' . $values[0] . '</div>';
                        }   
                        else {
                            $output .= '<div class="fieldValue ' . ($field['properties']['show_title'] ? '' : 'labelDisabled') . '"><ul class="fieldValueList"><li>' . implode('</li><li>', $values) . '</li></ul></div>';
                        }                             

                         $output .= '</div>';
                    }
                }

                $output .= '</div>';
            }
        }

        $output .= '</div>';     
        
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
	
    function label($name, &$entry) {

            if(empty($entry['Field']) || !isset($entry['Field']['pairs'][$name])) {
                    return null;
            }

            return $entry['Field']['pairs'][$name]['title'];

    }	

    function isMultipleOption($name,$element) {
            if(isset($element['Field']['pairs'][$name]) && in_array($element['Field']['pairs'][$name]['type'],$this->multipleOptionTypes)) {
                    return true;
            }
            return false;
    }

    function onDisplay(&$field, $showImage = true, $value = false, $return = false) {

        if(empty($field)) {
            return null;
        } 

        $values = array();

        $option = $value ? 'value' : 'text';
        foreach($field[$option] AS $key=>$text) 
        {                        
            switch($field['type']) 
            {
                case 'banner':
                    $text = '{FIELDTEXT}';
                    $field['properties']['output_format'] = Sanitize::getString($field,'description');
                    $field['description'] == '';
                    break;
                case 'date':
                    $format = Sanitize::getString($field['properties'],'date_format');
                    $text = $this->Time->nice($text,$format,0);
                    break;
                case 'integer':
                    $text = Sanitize::getInt($field['properties'],'curr_format') ? number_format($text,0,__l('DECIMAL_SEPARATOR',true),__l('THOUSANDS_SEPARATOR',true)) : $text;
                    break;
                case 'decimal':
                    $text = Sanitize::getInt($field['properties'],'curr_format') ? number_format($text,2,__l('DECIMAL_SEPARATOR',true),__l('THOUSANDS_SEPARATOR',true)) : round($text,2);
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
                        if($imgSrc = $this->locateThemeFile('theme_images',cmsFramework::locale() . '.' . $field['image'][$key],'',true)) {
                            $imgSrc = pathToUrl($imgSrc,true);
                        } elseif ($imgSrc = $this->locateThemeFile('theme_images',$field['image'][$key],'',true)) {
                            $imgSrc = pathToUrl($imgSrc,true);
                        }

                        if ($imgSrc != '') {
                            $text = '<img src="'.$imgSrc.'" title="'.$text.'" alt="'.$text.'" border="0" />';
                        }										
                    }
                    break;
                default:
                    $text = stripslashes($text);
                    break;				
            }

            $values[] = $text;
            $this->output[] = $text;

        }

        if($return){
            return $values;
        }
    }

    function click2Search($field, $criteriaid, $catid, $Itemid) 
    {	
        if (isset($field['properties']['click2search'])) 
        {
            $Itemid = $Itemid ? $Itemid : '';

            if(isset($field['properties']['click2searchlink']) && $field['properties']['click2searchlink'] != '') {
                    $click2searchlink = $field['properties']['click2searchlink'];
            } else {
                    $click2searchlink = 'index.php?option='.S2Paths::get('jreviews','S2_CMSCOMP').'&amp;Itemid={ITEMID}&amp;url=tag/{FIELDNAME}/{FIELDTEXT}/criteria'._PARAM_CHAR.'{CRITERIAID}';
            }

            foreach ($this->output AS $key=>$text) 
            {
                if($field['type'] == 'date') {

                        $field['value'][$key] = str_ireplace(' 00:00:00','',$field['value'][$key]);	
                }

                $url = $click2searchlink;
                
                if($Itemid>0) {
                    $url = str_ireplace('{ITEMID}',$Itemid,$url);
                } else {
                    $url = str_ireplace(array('_m{ITEMID}','&Itemid={ITEMID}'),'',$url);					
                }

                $url = str_ireplace(
                    array(
                        '{FIELDNAME}',
                        '{FIELDTEXT}',
                        '{CRITERIAID}',
                        '{CATID}'
                    ),
                    array(
                        substr($field['name'],3),
                        urlencode($field['value'][$key]),
                        urlencode($criteriaid),
                        urlencode($catid)
                    ),
                    $url
                );

                $url = s2ampReplace($url);
                $url = cmsFramework::route($url);
                $this->output[$key] = "<a href=\"$url\">$text</a>";		
            }
        }		
    }	

    function outputReformat($name, &$fields, $element = array(), $return = false) 
    {
        $field_names = array_keys($fields);

        // Listing vars
        $title = isset($element['Listing']) ? $element['Listing']['title'] : '';                
        $category = isset($element['Listing']) && isset($element['Category']) ? Sanitize::getString($element['Category'],'title') : '';                
        $section = isset($element['Listing']) && isset($element['Section']) ? Sanitize::getString($element['Section'],'title') : '';                

        // Check if there's anything to do
        if ((isset($fields[$name]['properties']['output_format']) 
             && 
             trim($fields[$name]['properties']['output_format']) != '{FIELDTEXT}'
             )
                || 
                $fields[$name]['type'] == 'banner'
            )
        {

            $format = $fields[$name]['properties']['output_format'];

			// Remove any references to current field in the output format to avoid an infinite loop
			$format = str_ireplace('{'.$name.'}','{fieldtext}',$format);
			
            $curr_value = '';

            // Find all custom field tags to replace in the output format
            $matches = array();			
            $regex = '/(jr_[a-z]{1,}\|value)|(jr_[a-z]{1,})/i';
            preg_match_all( $regex, $format, $matches );	
            $matches = $matches[0];
            
            // Loop through each field and make output format {tag} replacements
            foreach ($this->output AS $key=>$text) 
            {
                $text = str_ireplace('{fieldtext}', $text, $format);
                $text = str_ireplace('{fieldtitle}', $fields[$name]['title'], $text);

                !empty($title) and $text = str_ireplace('{title}', $title, $text);                    

                !empty($category) and $text = str_ireplace('{category}', $category, $text);                    

                !empty($section) and $text = str_ireplace('{section}', $section, $text);                    

                strstr(strtolower($text),'{optionvalue}') and $text = str_ireplace('{optionvalue}',$fields[$name]['value'][$key],$text);

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
                    $value_only = isset($parts[1]) && strtolower($parts[1]) == 'value';
                    $curr_text = $this->field($fname,$element,!$value_only,!$value_only); //stripslashes($fields[strtolower($curr_key)]['text'][0]);
                    
                    $this->output = $backupOutput;                    
                    
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
            $group_name = 'group_'.str_replace(' ','',$fields['group_name']);
            
			foreach($fields['Fields'] AS $key=>$value) 
			{
				if((!$search && $this->Access->in_groups($value['properties']['access'])) || ($search && $this->Access->in_groups($value['properties']['access_view']))) 
                {
                    $autoComplete = false;
                    
                    if($value['type'] == 'banner') continue;
                    
                    if($search 
                        && $this->Config->search_field_conversion 
                        && Sanitize::getInt($value['properties'],'autocomplete')==0
                        && isset($value['optionList']) && !empty($value['optionList'])
                        ) 
                    {
                        switch($value['type']) {
                            case 'radiobuttons':
                                $value['type'] = 'checkboxes';
                            break;
                            case 'select':
                                $value['type'] = 'selectmultiple';
                            break;
                        }
                    } 				

 					$inputs["data[Field][$fieldLocation][$key]"] = array(
						'id'=>		$value['name'],
						'type'=>	$this->types[$value['type']]
					);

                    // Check for AutoCompleteUI
                    if((!$search && Sanitize::getString($value['properties'],'autocomplete') == 1 )
                        ||
                        ($search && Sanitize::getString($value['properties'],'autocomplete.search') == 1)) {
                        $autoComplete = true;
                        $inputs["data[Field][$fieldLocation][$key]"]['class'] = 'jrAutoComplete'; 
                        $inputs["data[Field][$fieldLocation][$key]"]['data-field'] = htmlentities(json_encode(array('name'=>$value['name'],'id'=>$value['field_id'])),ENT_QUOTES,'utf-8');                        
                    }          

                    !$search and $inputs["data[Field][$fieldLocation][$key]"]['data-click2add'] = Sanitize::getInt($value['properties'],'click2add');                        

                    //  Assign field classes and other field type specific changes
                    switch($value['type']){
                        case 'decimal': 
                            $inputs["data[Field][$fieldLocation][$key]"]['class'] = 'jrDecimal';
                        break;
                        case 'integer':
                            $inputs["data[Field][$fieldLocation][$key]"]['class'] = 'jrInteger';
                        break;
                        case 'code':
                            $inputs["data[Field][$fieldLocation][$key]"]['class'] = 'jrCode';                        
                        break;
                        case 'website':
                            $inputs["data[Field][$fieldLocation][$key]"]['class'] = 'jrWebsite';                        
                        break;
                        case 'email':
                            $inputs["data[Field][$fieldLocation][$key]"]['class'] = 'jrEmail';                        
                        break;
                        case 'text':
                            $inputs["data[Field][$fieldLocation][$key]"]['class'] = 'jrText';                        
                        break;
                        case 'relatedlisting':
                            $inputs["data[Field][$fieldLocation][$key]"]['class'] = 'jrRelatedListing';                        
                            $inputs["data[Field][$fieldLocation][$key]"]['data-listingtype'] = Sanitize::getString($value['properties'],'listing_type');                        
                        break;
                        case 'textarea':
                            $inputs["data[Field][$fieldLocation][$key]"]['class'] = 'jrTextArea';                        
                        break;
                        case 'select':
                            if(isset($inputs["data[Field][$fieldLocation][$key]"]['class'])) {
                                $inputs["data[Field][$fieldLocation][$key]"]['class'] .= ' jrSelect';                        
                            }
                            else {
                                $inputs["data[Field][$fieldLocation][$key]"]['class'] = 'jrSelect';                        
                            }
                        break;
                        case 'selectmultiple':
                            if(isset($inputs["data[Field][$fieldLocation][$key]"]['class'])) {
                                $inputs["data[Field][$fieldLocation][$key]"]['class'] .= ' jrSelectMultiple';                        
                            }
                            else {
                                $inputs["data[Field][$fieldLocation][$key]"]['class'] = 'jrSelectMultiple';                        
                            }
                        break;
                        case 'date':
                            $inputs["data[Field][$fieldLocation][$key]"]['class'] = 'jrDate';
                            $yearRange = Sanitize::getString($value['properties'],'year_range');
                            $inputs["data[Field][$fieldLocation][$key]"]['data-yearrange'] = $yearRange != '' ? $yearRange : 'c-10:c+10';                        
                        break;
                    }
                        
					$inputs["data[Field][$fieldLocation][$key]"]['label']['text'] = $value['title'];
                    $inputs["data[Field][$fieldLocation][$key]"]['label']['class'] = "jrLabel";      

                    # Add tooltip
                    if(!$search && Sanitize::getString($value,'description',null)) {
                        switch(Sanitize::getInt($value['properties'],'description_position')) {
                            case 0:
                            case 1: 
                                $inputs["data[Field][$fieldLocation][$key]"]['label']['text'] .= '<span class="jr_infoTip">&nbsp;</span><div class="jr_tooltipBox">'.$value['description'].'</div>';
                            break;
                            case 2: 
                                $inputs["data[Field][$fieldLocation][$key]"]['between'] = '<div class="jrFieldDescription">'.$value['description'].'</div>';
                            break;
                            case 3: 
                                $inputs["data[Field][$fieldLocation][$key]"]['after'] = '<div class="jrFieldDescription">'.$value['description'].'</div>';
                            break;
                        }
                    }
								
					if(!$search && $value['required']){
						$inputs["data[Field][$fieldLocation][$key]"]['label']['text'] .= '<span class="required">'.__t("*",true).'</span>';
					}
					
					if(in_array($value['type'],$this->multipleTypes)) 
					{
						$inputs["data[Field][$fieldLocation][$key]"]['multiple'] = 'multiple';		
					}
					
					if(isset($value['optionList']) && $value['type'] == 'select') 
					{					
						$value['optionList'] = array(''=>$selectLabel) + $value['optionList'];
					}
					
					if(isset($value['optionList'])){
						$inputs["data[Field][$fieldLocation][$key]"]['options'] = $value['optionList'];
	
					}
	
					# Add click2add capability for select lists
					if(!$autoComplete && $fieldLocation == 'Listing' && !$search && $this->types[$value['type']] == 'select' && $value['properties']['click2add'])
					{
                        $inputs["data[Field][$fieldLocation][$key]"]['style'] = 'float:left;';
                                                
                        $click2AddLink = $this->Form->button(__t("Add",true),array('style'=>'float:left','class'=>'jrButton','onclick'=>"jQuery('#click2Add_{$value['field_id']}').toggle('fast');"));
                 
						$click2AddInput = $this->Form->text(
							'jrFieldOption'.$value['field_id'],
							array('id'=>'jrFieldOption'.$value['field_id'],'class'=>'jrFieldOptionInput','data-fid'=>$value['field_id'],'data-fname'=>$value['name'])
						);
						
						$click2AddButton = $this->Form->button(
							__t("Submit",true),
							array(
								'onclick'=>"jreviews.field.addOption(this);",
								'div'=>false,
								'id'=>'submitButton'.$value['field_id'],
								'class'=>'jrButton'
							)
						);
						
						$inputs["data[Field][$fieldLocation][$key]"]['after'] = 
						  $click2AddLink
						. "<div id='click2Add_{$value['field_id']}' class='jr_newFieldOption'>"
						. $click2AddInput . ' '
						. $click2AddButton
						. "<span class=\"jr_loadingSmall jr_hidden\"></span>"
						. '</div>'
						;
					}	
					
					# Prefill values when editing                 
					if(isset($value['selected'])) {
						$inputs["data[Field][$fieldLocation][$key]"]['value'] = $value['selected'];
					}
					
					# Add search operator fields for date, decimal and integer fields
					if($search && in_array($value['type'],$this->operatorTypes)) 
					{
						$options = array(
							'equal'=>'=',
							'higher'=>'&gt;=',
							'lower'=>'&lt;='
                            ,'between'=>__t("between",true)
						);		
						
                        $inputs["data[Field][$fieldLocation][$key]"]['multiple'] = true; // convert field to array input for range searches                        
                                                                              
                        $attributes = array('id'=>$key.'high','multiple'=>true);
                        
                        switch($value['type']) {
                            case 'integer':
                                $attributes['class'] = 'jrInteger';
                            break;
                            case 'decimal':
                                $attributes['class'] = 'jrDecimal';
                            break;
                            case 'date':
                                $attributes['class'] = 'jrDate';
                            break;
                        }

                        // This is the high value input in a range search
                        $inputs["data[Field][$fieldLocation][$key]"]['after'] = '<span id="'.$key.'highDiv" style="display:none;">&nbsp;'.$this->Form->text("data[Field][Listing][{$key}]",$attributes).'</span>';                        
						$inputs["data[Field][$fieldLocation][$key]"]['between'] = $this->Form->select("data[Field][Listing][{$key}_operator]",$options,null,array('class'=>'jrSearchOptions input','onchange'=>"jreviews.search.showRange(this,'{$key}high');"));				
					}
					
					# Input styling
					$inputs["data[Field][$fieldLocation][$key]"]['div'] = 'jr_fieldDiv ' . $value['name']; 					
									
					if(in_array($this->types[$value['type']],$this->legendTypes)) {
						// Input styling
						$inputs["data[Field][$fieldLocation][$key]"]['option_class'] = 'jr_fieldOption';
						
						if(!isset($inputs["data[Field][$fieldLocation][$key]"]['after'])) {
							$inputs["data[Field][$fieldLocation][$key]"]['after'] = '';
						}
						
						$inputs["data[Field][$fieldLocation][$key]"]['after'] = $this->Html->div('clr',' ') . $inputs["data[Field][$fieldLocation][$key]"]['after']; // To break the float					
					} 
					
				} // end access check
			} // end foreach

			if(!empty($inputs)) 
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
			$output .= $this->Form->inputs($form,array('id'=>$group,'class'=>'jr_hidden'));
		}

		return $output;
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
		
		$fieldLocation = Inflector::camelize($fieldLocation);

		foreach($formFields AS $group=>$fields) {

			$inputs = array();

			foreach($fields['Fields'] AS $key=>$value) 
			{
				$autoComplete = false;
                
                // Convert radio button to checkbox if multiple search is enabled in the config settings
				if($search && $this->Config->search_field_conversion && $value['type']=='radiobuttons') {
					$value['type'] = 'checkboxes';
				}

				$inputs["data[Field][$fieldLocation][$key]"] = array(
					'id'=>		$value['name'],
                    'type'=>	$this->types[$value['type']]
				);		

                // Check for AutoCompleteUI
                if((!$search && Sanitize::getString($value['properties'],'autocomplete') ==1 )
                    ||
                    ($search && Sanitize::getString($value['properties'],'autocomplete.search') == 1)) {
                    $autoComplete = true;
                    $inputs["data[Field][$fieldLocation][$key]"]['class'] = 'jrAutoComplete'; 
                    $inputs["data[Field][$fieldLocation][$key]"]['data-field'] = htmlentities(json_encode(array('name'=>$value['name'],'id'=>$value['field_id'])),ENT_QUOTES,'utf-8');                        
                }      
                                    
				$inputs["data[Field][$fieldLocation][$key]"]['div'] = array();
               
				# Add tooltip
                if(!$search && Sanitize::getString($value,'description',null)) {
                    switch(Sanitize::getInt($value['properties'],'description_position')) {
                        case 0:
                        case 1: 
                            $inputs["data[Field][$fieldLocation][$key]"]['label']['text'] .= '<span class="jr_infoTip">&nbsp;</span><div class="jr_tooltipBox">'.$value['description'].'</div>';
                        break;
                        case 2: 
                            $inputs["data[Field][$fieldLocation][$key]"]['between'] = '<div class="jrFieldDescription">'.$value['description'].'</div>';
                        break;
                        case 3: 
                            $inputs["data[Field][$fieldLocation][$key]"]['after'] = '<div class="jrFieldDescription">'.$value['description'].'</div>';
                        break;
                    }
                }
							
                //  Assign field classes and other field type specific changes
                 switch($value['type']){
                    case 'decimal': 
                        $inputs["data[Field][$fieldLocation][$key]"]['class'] = 'jrDecimal';
                    break;
                    case 'integer':
                        $inputs["data[Field][$fieldLocation][$key]"]['class'] = 'jrInteger';
                    break;
                    case 'code':
                        $inputs["data[Field][$fieldLocation][$key]"]['class'] = 'jrCode';                        
                    break;
                    case 'website':
                        $inputs["data[Field][$fieldLocation][$key]"]['class'] = 'jrWebsite';                        
                    break;
                    case 'email':
                        $inputs["data[Field][$fieldLocation][$key]"]['class'] = 'jrEmail';                        
                    break;
                    case 'text':
                        $inputs["data[Field][$fieldLocation][$key]"]['class'] = 'jrText';                        
                    break;
                    case 'relatedlisting':
                        $inputs["data[Field][$fieldLocation][$key]"]['class'] = 'jrRelatedListing';                        
                        $inputs["data[Field][$fieldLocation][$key]"]['data-listingtype'] = Sanitize::getString($value['properties'],'listing_type');                        
                    break;
                    case 'textarea':
                        $inputs["data[Field][$fieldLocation][$key]"]['class'] = 'jrTextArea';                        
                    break;
                    case 'select':
                        if(isset($inputs["data[Field][$fieldLocation][$key]"]['class'])) {
                            $inputs["data[Field][$fieldLocation][$key]"]['class'] .= ' jrSelect';                        
                        }
                        else {
                            $inputs["data[Field][$fieldLocation][$key]"]['class'] = 'jrSelect';                        
                        }
                    break;
                    case 'selectmultiple':
                        if(isset($inputs["data[Field][$fieldLocation][$key]"]['class'])) {
                            $inputs["data[Field][$fieldLocation][$key]"]['class'] .= ' jrSelectMultiple';                        
                        }
                        else {
                            $inputs["data[Field][$fieldLocation][$key]"]['class'] = 'jrSelectMultiple';                        
                        }
                    break;  
                    case 'date':
                        $inputs["data[Field][$fieldLocation][$key]"]['class'] = 'jrDate';
                        $inputs["data[Field][$fieldLocation][$key]"]['readonly'] = 'readonly';
                        $yearRange = Sanitize::getString($value['properties'],'year_range');
                        $inputs["data[Field][$fieldLocation][$key]"]['data-yearrange'] = $yearRange != '' ? $yearRange : 'c-10:c+10';                        
                    break;                    
                }
                
				if(in_array($value['type'],$this->multipleTypes)) 
				{
					$inputs["data[Field][$fieldLocation][$key]"]['multiple'] = 'multiple';		
					if( ($size = Sanitize::getInt($value['properties'],'size')) ) { 
                        $inputs["data[Field][$fieldLocation][$key]"]['size'] = $size; 
                    }		
				}
				
				if(isset($value['optionList']) && $value['type'] == 'select') 
				{					
					$value['optionList'] = array(''=>$selectLabel) + $value['optionList'];
				}
				
				if(isset($value['optionList'])){
					$inputs["data[Field][$fieldLocation][$key]"]['options'] = $value['optionList'];

				}

				# Add click2add capability for select lists
				if(!$autoComplete  && !$search && $fieldLocation == 'Listing' && $this->types[$value['type']] == 'select' && $value['properties']['click2add'])
				{       
                    $inputs["data[Field][$fieldLocation][$key]"]['style'] = 'float:left;';
                                            
                    $click2AddLink = $this->Form->button(__t("Add",true),array('style'=>'float:left','class'=>'jrButton','onclick'=>"jQuery('#click2Add_{$value['field_id']}').toggle('fast');"));
             
                    $click2AddInput = $this->Form->text(
                        'jrFieldOption'.$value['field_id'],
                        array('id'=>'jrFieldOption'.$value['field_id'],'class'=>'jrFieldOptionInput','data-fid'=>$value['field_id'],'data-fname'=>$value['name'])
                    );
					
					$click2AddButton = $this->Form->button(
						__t("Submit",true),
						array(
							'onclick'=>"submitOption({$value['field_id']},'{$value['name']}');",
							'div'=>false,
							'id'=>'submitButton'.$value['field_id'],
							'class'=>'jrButton'
						)
					);
					
					$inputs["data[Field][$fieldLocation][$key]"]['after'] = 
					  $click2AddLink
					. "<div id='click2Add_{$value['field_id']}' class='jr_newFieldOption'>"
					. $click2AddInput . ' '
					. $click2AddButton

					. '</div>'
					;
				}	
			
				# Prefill values when editing
				if(isset($value['selected'])) 
                {
					if(in_array($value['type'],$this->operatorTypes) && $value['selected'][0] == 'between')
                    {
                        $inputs["data[Field][$fieldLocation][$key]"]['value'] = $value['selected'][2];
                    } 
                    else {
                        $inputs["data[Field][$fieldLocation][$key]"]['value'] = $value['selected'];   
                        $inputs["data[Field][$fieldLocation][$key]"]['data-selected'] = implode('_',$value['selected']);                        
                    }
				}
				
				# Add search operator fields for date, decimal and integer fields
				if($search && in_array($value['type'],$this->operatorTypes)) 
				{
                    $options = array(
                        'equal'=>'=',
                        'higher'=>'&gt;=',
                        'lower'=>'&lt;='
                        ,'between'=>__t("between",true)
                    );        
                    
                    $inputs["data[Field][$fieldLocation][$key]"]['multiple'] = true; // convert field to array input for range searches                        
                                                                          
                    $attributes = array('id'=>$key.'high','multiple'=>true);
                    
                    switch($value['type']) {
                        case 'integer':
                            $attributes['class'] = 'jrInteger';
                        break;
                        case 'decimal':
                            $attributes['class'] = 'jrDecimal';
                        break;
                        case 'date':
                            $attributes['class'] = 'jrDate';
                        break;
                    }
                                            
                    $showHighRange = isset($value['selected']) && is_array($value['selected']) && $value['selected'][0] == 'between';
                    $showHighRange and $attributes['value'] = $value['selected'][3];
                    $showHighRangeStyle = $showHighRange ? '' : ' style="display:none;"';
                    $selectedOperator = isset($value['selected'][0]) ? $value['selected'][0] : '';

                    // This is the high value input in a range search
                    $inputs["data[Field][$fieldLocation][$key]"]['after'] = '<span id="'.$key.'highDiv" '.$showHighRangeStyle.'>&nbsp;'.$this->Form->text("data[Field][Listing][{$key}]",$attributes).'</span>';                                                
					$inputs["data[Field][$fieldLocation][$key]"]['between'] = $this->Form->select("data[Field][Listing][{$key}_operator]",$options,$selectedOperator,array('class'=>'jrSearchOptions input','onchange'=>"jreviews.search.showRange(this,'{$key}high');"));								                
                }
				
				if(in_array($this->types[$value['type']],$this->legendTypes)) {
					// Input styling
					$inputs["data[Field][$fieldLocation][$key]"]['option_class'] = 'jr_fieldOption';
					
					$inputs["data[Field][$fieldLocation][$key]"]['after'] = $this->Html->div('clr',' '); // To break the float					
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
		foreach($groupSet AS $group=>$form) {
			$output = array_merge($output,$this->Form->inputs($form,null,null,true));
		}
            
		return $output;
	}	

}

//		return $this->Form->inputs
//			(
//				array(
//					'fieldset'=>true,
//					'legend'=>'Group XYZ',
//					'data[Field][jr_text]'=>
//					array(
//						'label'=>array('for'=>'jr_text','text'=>'Text Field'),
//						'id'=>'jr_text',
//						'type'=>'text',
//						'size'=>'10',
//						'maxlength'=>'100',
//						'class'=>'{required:true}'
//					),
//					'data[Field][jr_select]'=>
//					array(
//						'label'=>array('for'=>'select','text'=>'Select Field'),
//						'id'=>'select',
//						'type'=>'select',
//						'options'=>array('1'=>'1','2'=>'2'),
//						'selected'=>2
//					),
//					'data[Field][jr_selectmultiple]'=>
//					array(
//						'label'=>array('for'=>'selectmultiple','text'=>'Multiple Select Field'),
//						'id'=>'selectmultiple',
//						'type'=>'select',
//						'multiple'=>'multiple',
//						'size'=>'2',
//						'options'=>array('1'=>'email','2'=>'asdfasdf'),
//						'value'=>array(1,2)
//					),
//					'data[Field][jr_checkbox]'=>
//					array(
//						'label'=>false,
//						'legend'=>'Checkboxes',
//						'type'=>'checkbox',
//						'options'=>array('1'=>'Option 1','2'=>'Option 2'),
//						'value'=>array(2),
//						'class'=>'{required:true,minLength:2}'
//					),
//					'data[Field][jr_radio]'=>
//					array(
//						'legend'=>'Radio Buttons',
//						'type'=>'radio',
//						'options'=>array('1'=>'Option 1','2'=>'Option 2'),
//						'value'=>1,
//						'class'=>'{required:true}'
//					)		
//					
//				)	
//			);		

?>