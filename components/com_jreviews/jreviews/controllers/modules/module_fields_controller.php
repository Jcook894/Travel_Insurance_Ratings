<?php
/**
 * JReviews - Reviews Extension
 * Copyright (C) 2010-2015 ClickFWD LLC
 * This is not free software, do not distribute it.
 * For licencing information visit https://www.jreviews.com
 * or contact sales@jreviews.com
**/

defined( 'MVC_FRAMEWORK') or die( 'Direct Access to this location is not allowed.' );

S2App::import('Controller','common','jreviews');

class ModuleFieldsController extends MyController {

	var $uses = array('menu','category','field','field_option');

	var $helpers = array('routes','form','html','assets','text','custom_fields');

	var $components = array('config','access','everywhere');

	var $autoRender = false;

	var $autoLayout = true;

    var $layout = 'module';

    var $drill_down = false;

	function beforeFilter()
    {
		parent::beforeFilter();

        $this->viewSuffix = Sanitize::getString($this->params['module'],'tmpl_suffix');
	}

	function index()
	{
        $cat_id = null;

        $conditions = array();

        $joins = array();

        $order = array();

        // Read module params

        $filter_type = Sanitize::getString($this->params['module'],'itemid_options' );

        $cat_id = Sanitize::getString($this->params['module'],'cat');

        $criteria_id = Sanitize::getString($this->params['module'],'criteria');

        $field = Sanitize::paranoid(Sanitize::getString($this->params['module'],'field'),array('_'));

        if(!$field) return;

        $option_length = Sanitize::getInt($this->params['module'],'fieldoption_length');

        $custom_params = Sanitize::getString($this->params['module'],'custom_params');

        $sort = Sanitize::paranoid(Sanitize::getString($this->params['module'],'fieldoption_order'));

        $this->drill_down = Sanitize::getString($this->params['module'],'drill_down',false);

        $drill_down_fields = explode(',',str_replace(' ','',Sanitize::getString($this->params['module'],'drill_down_fields')));

        $drill_down_fields = array_filter($drill_down_fields);

        if(!empty($drill_down_fields))
        {
            array_unshift($drill_down_fields, $field);
        }

        $def_control_field = Sanitize::getString($this->params['module'],'control_field');

        $def_control_value = Sanitize::getString($this->params['module'],'control_value');

        # Limit field options displayed only to those that have listings for the current filter type

        if(in_array($filter_type,array('category','criteria')))
        {
            $ids = CommonController::_discoverIDs($this);

            extract($ids);
        }

        /**
         * Get the field options that are being used in listings and their count
         */

        $reset_cat_id_after_count_query = false;

        // Build conditions based on dir, listing type and cat filters

        // Category id - get all subcategories cat ids in the same branch

        if(!empty($cat_id))
        {
        	$categories = array();

        	$parent_cat_ids = explode(',', $cat_id);

        	foreach($parent_cat_ids AS $parent_cat_id)
        	{
                if($children = $this->Category->getCategoryList(array('cat_id'=>$parent_cat_id,'current'=>true,'indent'=>false,'disabled'=>false)))
        		{
					$categories = array_merge($categories, array_keys($children));
        		}
        	}

        	if(!empty($categories))
        	{
        		$cat_id = implode(',', $categories);
        	}
        }

        // Directory - get all cat ids from #__jreviews_categories

        if(empty($cat_id) && !empty($dir_id))
        {
        	$query = 'SELECT id FROM #__jreviews_categories WHERE dirid IN (' . $dir_id . ')';

        	if($cat_id = $this->Field->query($query,'loadColumn'))
        	{
        		$cat_id = implode(',', $cat_id);

                $reset_cat_id_after_count_query = true;
        	}
        }

		// Listing Type - get all cat ids from #__jreviews_categories

        if(empty($cat_id) && !empty($criteria_id)) {

        	$query = 'SELECT id FROM #__jreviews_categories WHERE criteriaid IN (' . $criteria_id . ')';

        	if($cat_id = $this->Field->query($query,'loadColumn'))
        	{
        		$cat_id = implode(',', $cat_id);

                $reset_cat_id_after_count_query = true;
        	}
        }

        // Need a criteria id. If not specified in the module settings, we can get it from the category id if one is detected

        if($filter_type == 'criteria' && empty($criteria_id) && $cat_id > 0) {

            $query = "
                SELECT
                    criteriaid
                FROM
                    #__jreviews_categories
                WHERE
                    id = " . (int) $cat_id . "
            ";

            $criteria_id = $this->Category->query($query,'loadResult');
        }

        /**
         * Get the field options
         */

        // If drill-down enabled, we have to display the dependent field options

        if(($this->drill_down && !isset($this->params['tag']))
            ||
            ($this->drill_down
                && isset($this->params['tag'])
                && !empty($drill_down_fields)
                && !in_array('jr_' . Sanitize::getString($this->params['tag'],'field'), $drill_down_fields)
            )
        )
        {
            $this->drill_down = false;
        }

        $dep_field_options = array();

        if($this->drill_down)
        {
            $control_field = 'jr_' . Sanitize::getString($this->params['tag'],'field');

            $control_value = Sanitize::getString($this->params['tag'],'value');

            // Find the dependent field

            $query = "
                SELECT
                    Field.name, FieldOption.value
                FROM
                    #__jreviews_fieldoptions AS FieldOption
                LEFT JOIN
                    #__jreviews_fields AS Field ON Field.fieldid = FieldOption.fieldid
                WHERE
                    FieldOption.control_field = " . $this->Quote($control_field) . "
                    AND
                    FieldOption.control_value LIKE " . $this->QuoteLike('*'.$control_value.'*')
                ;

            $dependents = $this->Field->query($query, 'loadAssocList', 'value');

            if(!$dependents) return false;

            $dep_field = reset($dependents);

            $field = $dep_field['name'];

            $dep_field_options = array_keys($dependents);
        }

        $rows = $this->Listing->getFieldOptionUsageCount($field, array('cat'=>$cat_id));

        // We need to do this, otherwise the click2search links will use the category for URLs even though one was not available

        if($reset_cat_id_after_count_query)
        {
            $cat_id = null;
        }

        $values = array();

        // hide the module if there are no field options

        if(empty($rows)) return false;

        foreach($rows AS $row)
        {
            $options = rtrim(ltrim($row['value'],'*'),'*');

            $options = explode('*', $options);

            foreach($options AS $option)
            {
                if($this->drill_down && !in_array($option, $dep_field_options)) continue;

                if(isset($values[$option]))
                {
                    $values[$option] += $row['total'];
                }
                else {
                    $values[$option] = $row['total'];
                }
            }
        }

        /**
         * Get the 'Text' of field options and merge with the option count
         */

		$this->FieldOption->modelUnbind(array(
            'FieldOption.value AS `FieldOption.value`',
            'FieldOption.fieldid AS `FieldOption.fieldid`',
            'FieldOption.ordering AS `FieldOption.ordering`',
            'FieldOption.optionid AS `FieldOption.optionid`',
            'FieldOption.text AS `FieldOption.text`',
        ));

        array_unshift($this->FieldOption->fields,'FieldOption.optionid AS `FieldOption.optionid`', 'FieldOption.value AS `FieldOption.value`');

        if($option_length)
        {
            $fields[] = 'IF(CHAR_LENGTH(FieldOption.text)>'.$option_length.',CONCAT(SUBSTR(FieldOption.text,1,'.$option_length.'),"..."),FieldOption.text) AS `FieldOption.text`';
        }
        else {
            $fields[] = 'FieldOption.text AS `FieldOption.text`';
        }

        if($sort !== 'count')
        {
            $order[] = 'FieldOption.'.$sort;
        }

        $conditions = array(
				'Field.name = ' . $this->Quote($field)
        	);

        if($this->drill_down && $dep_field_options)
        {
            $conditions[] = 'FieldOption.value IN (' . $this->Quote($dep_field_options) . ')';
        }

        if(!isset($this->params['tag']) && $def_control_field != '' && $def_control_value != '')
        {
            $conditions[] = 'FieldOption.control_field = ' . $this->Quote($def_control_field);

            $conditions[] = 'FieldOption.control_value LIKE ' . $this->QuoteLike('*' . $def_control_value . '*');
        }

        $fieldOptions = $this->FieldOption->findAll(array(
            'fields'=>$fields,
            'conditions'=>$conditions,
            'order'=>$order
        ));

        $highest_count = 0;

        $counts = $titles = array();

        foreach($fieldOptions AS $key=>$option)
        {
        	extract($option['FieldOption']);

        	if(isset($values[$value]))
        	{
        		$fieldOptions[$key]['FieldOption']['count'] = $values[$value];

                if ($values[$value] > $highest_count) {
                    $highest_count = $values[$value];
                }

                $counts[$key] = $values[$value];

                $titles[$key] = $text;
        	}
        	else {
        		unset($fieldOptions[$key]);
        	}
        }

        // Sort the options with count descending

        if($sort == 'count')
        {
            array_multisort($counts, SORT_DESC, $titles, SORT_ASC, $fieldOptions);
        }

        switch($filter_type)
        {
            case 'category' && $cat_id:

                $url_format = 'tag/{fieldname}/{optionvalue}/?cat={catid}';

                break;

            case 'criteria' && ($criteria_id || $cat_id):

                $url_format = 'tag/{fieldname}/{optionvalue}/?criteria={criteriaid}';

                break;

            case 'search':

                $url_format = 'tag/{fieldname}/{optionvalue}/';

                break;

            case 'hardcode':
            default:

                $url_format = 'tag/{fieldname}/{optionvalue}/';

                break;
        }

		# Send variables to view template

        $this->set(array(
            'field'=>$field,
            'url_format'=>$url_format,
            'criteria_id'=>$criteria_id,
            'cat_id'=>$cat_id,
            'field_options'=>$fieldOptions,
            'highest_count'=>$highest_count,
            'custom_params'=>$custom_params
        ));

        return $this->render('modules','fields');
	}
}