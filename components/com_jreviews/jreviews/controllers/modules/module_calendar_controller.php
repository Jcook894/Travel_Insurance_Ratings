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

class ModuleCalendarController extends MyController {

    var $uses = array('menu','field','media');

    var $components = array('config', 'access','everywhere', 'media_storage');

    var $autoRender = false;

    var $autoLayout = true;

    var $layout = 'module';

    function beforeFilter() {
        # Call beforeFilter of MyController parent class
        parent::beforeFilter();
    }

    function index()
    {
        $module_id = Sanitize::getInt($this->params,'module_id',Sanitize::getInt($this->data,'module_id'));

        $this->viewSuffix = Sanitize::getString($this->params['module'],'tmpl_suffix');

        return $this->render('modules', 'calendar');
    }

    function _getEvents()
    {
        $response = array('success' => false, 'results' => array());

        $startField = Sanitize::getString($this->params,'start_field');

        $endField = Sanitize::getString($this->params,'end_field');

        $startDate = Sanitize::getString($this->params,'start_date');

        $endDate = Sanitize::getString($this->params,'end_date');

        $hidePast = Sanitize::getInt($this->params,'hide_past');

        // Validate calendar field names to make sure the fields exist in the database

        if($startField != '' || $endField != '')
        {
            $calendarFieldNames = $this->Field->getFieldNames('listing', array('published' => true, 'type' => 'date'));

            if(($startField != '' && !in_array($startField, $calendarFieldNames)) || ($endField != '' && !in_array($endField, $calendarFieldNames)))
            {
                return cmsFramework::jsonResponse($response);
            }
        }

        if($startField != '' && isset($this->Listing))
        {
            $conditions = array();

            $fields = $this->Listing->fields;

            $this->Listing->fields = array(
                "Listing.id AS `Listing.id`",
                "Field.{$startField} AS `Field.{$startField}`"
            );

            if ($endField != '') {
                $this->Listing->fields[] = "Field.{$endField} AS `Field.{$endField}`";
            }

            $conditions[] = "Field.{$startField} <> '0000-00-00 00:00:00'";

            if ($startDate != '') {
                $conditions[] = "Field.{$startField} >= " . $this->Quote($startDate);
            }

            if ($endDate != '') {
                $conditions[] = "Field.{$startField} <= " . $this->Quote($endDate);
            }

            if ($hidePast) {
                $conditions[] = "Field.{$startField} >= " . $this->Quote(_TODAY);
            }

            // Include only published listings

            if (_CMS_NAME == 'joomla') {

                $conditions[] = "Listing.state = 1";

            } elseif (_CMS_NAME == 'wordpress') {

                $conditions[] = "Listing.post_status = 'publish'";

            }

            $results = $this->Listing->findAll(
                array(
                    'fields'=>$this->Listing->fields,
                    'conditions'=> $conditions),
                $callbacks = array()
            );

            $this->Listing->fields = $fields;

            if (!$results)
            {
                return cmsFramework::jsonResponse($response);
            }

            # Convert Date Fields to Local
            foreach($results AS $key=>$value)
            {
                if ($results[$key]['Field'][$startField] != NULL_DATE) {
                    $results[$key]['Field'][$startField] = cmsFramework::dateUTCtoLocal($results[$key]['Field'][$startField]);
                }

                if (isset($results[$key]['Field'][$endDate]) && $results[$key]['Field'][$endDate] != NULL_DATE) {
                    $results[$key]['Field'][$endDate] = cmsFramework::dateUTCtoLocal($results[$key]['Field'][$endDate]);
                }
            }

            $response['results'] = $results;

            $response['success'] = true;

            return cmsFramework::jsonResponse($response);

        }

        return cmsFramework::jsonResponse($response);
    }
}
