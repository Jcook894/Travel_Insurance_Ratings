<?php
/**
 * JReviews - Reviews Extension
 * Copyright (C) 2010-2012 ClickFWD LLC
 * This is not free software, do not distribute it.
 * For licencing information visit http://www.reviewsforjoomla.com
 * or contact sales@reviewsforjoomla.com
**/

defined( 'MVC_FRAMEWORK') or die( 'Direct Access to this location is not allowed.' );

class FieldOptionModel extends MyModel  {
        
    var $name = 'FieldOption';
    
    var $useTable = '#__jreviews_fieldoptions AS `FieldOption`';
    
    var $primaryKey = 'FieldOption.optionid';
    
    var $realKey = 'optionid';
    
    var $fields = array(
        'FieldOption.optionid AS `FieldOption.optionid`',
        'FieldOption.fieldid AS `FieldOption.fieldid`',
        'FieldOption.text AS `FieldOption.text`',
        'FieldOption.value AS `FieldOption.value`',
        'FieldOption.image AS `FieldOption.image`',
        'FieldOption.ordering AS `FieldOption.ordering`',
        'FieldOption.control_field AS `FieldOption.control_field`',
        'FieldOption.control_value AS `FieldOption.control_value`'
    );
    
    /**
     * These are characters that will be removed from the field option value
     *
     * @var array
     */
    var $blackList = array('=','|','!','$','%','^','°','_','&','(',')','*',';',':','@','#','+','.',',','/','\\');
    /**
     * The values in the array will be replaced with a dash "-"
     *
     * @var array
     */
    var $dashReplacements = array(' ','_',',','"',"'");
        
    function afterFind($results)
    {
        if(!is_array($results)) {
            return $results;
        }
                
        foreach($results AS $key=>$result)
        {
            // Process Control Field values
            $results[$key]['ControlValues'] = array();
            if(isset($result['FieldOption']['control_value']) && $result['FieldOption']['control_value'] != '')
            {
                $results[$key]['FieldOption']['control_value'] = explode('*',rtrim(ltrim($result['FieldOption']['control_value'],'*'),'*'));
                $query = "
                    SELECT 
                        Field.fieldid,value,text
                    FROM
                        #__jreviews_fieldoptions AS FieldOption
                    LEFT JOIN
                        #__jreviews_fields AS Field ON FieldOption.fieldid = Field.fieldid                         
                    WHERE 
                        Field.name = " . $this->Quote($result['FieldOption']['control_field']) . " 
                         AND FieldOption.value IN (". $this->Quote($results[$key]['FieldOption']['control_value']) .")"
                ;
                $this->_db->setQuery($query);
                $results[$key]['ControlValues'] = $this->_db->loadAssocList();
            }
        }    
        return $results;
    }   
     
    /**
     * Retrieves option list for 
     *
     * @param unknown_type $fieldid
     * @param unknown_type $limitstart
     * @param unknown_type $limit
     * @param unknown_type $total
     * @return unknown
     */
    function getList($fieldid, $limitstart, $limit, &$total) 
    {
        // get the total number of records
        $query = "SELECT COUNT(*) FROM `#__jreviews_fieldoptions` WHERE fieldid='$fieldid'";
        $this->_db->setQuery( $query );
        $total = $this->_db->loadResult();
    
        $sql = "SELECT * FROM #__jreviews_fieldoptions"
        . "\n WHERE fieldid = '$fieldid' ORDER BY ordering ASC, optionid ASC"
        . "\n LIMIT $limitstart, $limit"
        ;
        $this->_db->setQuery($sql);
        $rows = $this->_db->loadObjectList();

        return $rows;
    }
    
    /**
    * Returns an array of field options for the selected parent field to use as control field values in forms
    */    
    /**
    * Returns an array of field options for the selected parent field to use as control field values in forms
    */    
    function getControlList($fieldid, $valueq) 
    {
        $fieldid = (int) $fieldid;
        
        $query = "
            SELECT 
                optionid, value, text AS label
            FROM 
                #__jreviews_fieldoptions
            WHERE 
                fieldid = {$fieldid}
                " . 
                ($valueq != '' ? 
                    " AND (value LIKE " . $this->Quote($valueq . '%') . ' OR text LIKE ' . $this->Quote($valueq . '%') . ')' : ''
                ) . " 
            ORDER BY text ASC
            LIMIT 15                
        ";
                
        $this->_db->setQuery($query);

        $rows = $this->_db->loadObjectList('optionid');

        if(count($rows) == 15) {
            return $rows;
        }
       
        $option_ids = array_keys($rows);
        
        $query = "
            SELECT 
                value, text AS label
            FROM 
                #__jreviews_fieldoptions
            WHERE 
                fieldid = {$fieldid}
                " . 
                ($valueq != '' ? 
                    " AND (value LIKE " . $this->quoteLike($valueq) . ' OR text LIKE ' . $this->quoteLike($valueq) . ')' : ''
                ) . " 
                " . (!empty($option_ids) ? "AND optionid NOT IN (". implode(',',$option_ids).")" : '') . "
            ORDER BY text ASC
            LIMIT " . (15 - count($rows))                
        ;
        
        $this->_db->setQuery($query);
        $rows_second = $this->_db->loadObjectList();

        if(!empty($rows_second)) {
            $rows = array_merge($rows, $rows_second);
        }

        $rows = array_values($rows);
        return $rows;
    }

    function save(&$data) 
    {           
        $isNew = Sanitize::getInt($data['FieldOption'],'optionid') ? false : true;
        $control_value = '';

        $field_id = Sanitize::getInt($data['FieldOption'],'fieldid');
        // Before saving storing control field info for the field option, 
        // first check if this is a Field Option => Field relationship
        // If it is, then we drop the control field info.
        $query = "SELECT control_field FROM #__jreviews_fields WHERE fieldid = " . $field_id;
        $this->_db->setQuery($query);

        if(($FieldOptionToField = $this->_db->loadResult()) == '' && isset($data['FieldOption']['control_value']) && !empty($data['FieldOption']['control_value']))
        {
            $control_value = Sanitize::getVar($data['FieldOption'],'control_value');
            if(is_array($control_value)) $control_value = array_filter($control_value);
            $data['FieldOption']['control_value'] = !empty($control_value) ? '*'.implode('*',$control_value).'*' : '';
        }
        else {
            $data['FieldOption']['control_field'] = '';   
            $data['FieldOption']['control_value'] = array();            
        }
        
        $data['FieldOption']['value'] = html_entity_decode(urldecode($data['FieldOption']['value']),ENT_QUOTES,'utf-8');
      
        if($isNew) 
        {
            $data['FieldOption']['text'] = html_entity_decode(urldecode($data['FieldOption']['text']),ENT_QUOTES,'utf-8');

            // Remove non alphanumeric characters from option value
//            $data['FieldOption']['value'] = Sanitize::translate(urldecode($data['FieldOption']['value']));
//            $data['FieldOption']['value'] = trim($data['FieldOption']['value']);

			
			$data['FieldOption']['value'] = str_replace($this->blackList,'',$data['FieldOption']['value']);    


			$data['FieldOption']['value'] = str_replace($this->dashReplacements,'-',$data['FieldOption']['value']);                        
            
			$data['FieldOption']['value'] = preg_replace(array('/[-]+/'), array('-'), $data['FieldOption']['value']);            
            
			$data['FieldOption']['value'] = rtrim(ltrim($data['FieldOption']['value'],'-'),'-');
			
			$data['FieldOption']['value'] = mb_strtolower($data['FieldOption']['value'],'UTF-8');

			// If is new checks for duplicate value
            $query = "
                SELECT 
                    optionid, control_field, control_value 
                FROM 
                    #__jreviews_fieldoptions 
                WHERE 
                    fieldid = {$field_id} AND value = " . $this->_db->Quote($data['FieldOption']['value'])
            ;
            $this->_db->setQuery($query);
            $option = $this->_db->loadAssocList();

            if(!empty($option) && $control_value == '') {
                return 'duplicate';
            }
            elseif(!empty($option) && $control_value!='') {
                $option = array_shift($option);                
                $option['control_value'] = explode('*',rtrim(ltrim($option['control_value'],'*'),'*'));
                $value_exists = array_intersect($control_value,$option['control_value']);
                if ($data['FieldOption']['control_field'] == $option['control_field'] && !empty($value_exists)) {
                    return 'duplicate';
                }
                $data['FieldOption']['control_value'] = '*'.implode('*',array_unique(array_merge($control_value,$option['control_value']))).'*';
                $data['FieldOption']['optionid'] = $option['optionid'];
            }

            // Find last option
            if(!isset($data['FieldOption']['optionid'])) 
            {
                $this->_db->setQuery("SELECT max(ordering) FROM #__jreviews_fieldoptions WHERE fieldid = '".$field_id."'");
                $max = $this->_db->loadResult();
                
                if ($max > 0) {
                    $data['FieldOption']['ordering'] = $max+1; 
                } else {        
                    $data['FieldOption']['ordering'] = 1;
                }            
            }
        }
        
        // Make sure there's a control value, otherwise clear the control field as well
        if(empty($data['FieldOption']['control_value'])) {
            $data['FieldOption']['control_field'] = '';    
        }
      
        # store it in the db
        if(!$this->store($data)) { 
            return 'db_error';
        }        
        return 'success';
        
    }
}
