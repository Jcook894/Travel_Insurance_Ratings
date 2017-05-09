<?php
/**
 * RapidLauncher Addon for JReviews
 * Copyright (C) 2010-2016 ClickFWD LLC
 * This is not free software, do not distribute it.
 * For licencing information visit https://www.jreviews.com
 * or contact sales@jreviews.com
**/

defined( 'MVC_FRAMEWORK') or die( 'Direct Access to this location is not allowed.' );

S2App::import('Model', ['jreviews_content', 'rapidlauncher_field', 'rapidlauncher_field_option', 'rapidlauncher_paid_listing_field'], 'jreviews');

class RapidlauncherListingFieldHelperComponent
{
	protected $c;

	protected $listingField;

	protected $fieldOption;

	protected $field;

	protected $paidListingField;

	protected $update = false;

    function startup(& $controller)
    {
    	$this->c = & $controller;

        $this->listingField = ClassRegistry::getClass('JreviewsContentModel');

        $this->field = ClassRegistry::getClass('RapidlauncherFieldModel');

        $this->fieldOption = ClassRegistry::getClass('RapidlauncherFieldOptionModel');

        $this->importPaidField = $this->paidListingsExists() ? ClassRegistry::getClass('RapidlauncherPaidListingFieldModel') : false;
    }

    protected function paidListingsExists()
    {
    	return file_exists(PATH_APP_ADDONS . DS . 'paidlistings' . DS . 'paidlistings.xml');
    }

	function getList()
	{
		return $this->field->getList('listing');
	}

	function create($listingId, Array $row, array $fields)
	{
		$newOptionIds = [];

		$relatedListings = [];

		$this->field->setFields($fields);

        $row = self::extractCustomFields($row);

		$this->fieldOption->relations($row)->setFields($fields);

		foreach($row AS $column => $options)
		{
			// Processing only for multiple option custom fields. Inserts new field options on the fly as necessary.

	        if ($this->field->isFieldOptionType($column))
	        {
	        	$fieldOptions = $this->fieldOption->reset()->import($column, $options);

	        	$options = $this->fieldOption->valueSeparatedList($fieldOptions);
	        }
	        elseif ($this->field->isRelatedListing($column))
	        {
	        	// We store the related listing field name / value pair to process it after all listings have been processed

	        	if($options)
	        	{
					$relatedListings[$column] = explode(',', $options);
	        	}
	        }

	        $row[$column] = $this->fieldOption->saveFormat($column, $options);
		}

		if(!empty($row))
		{
	        $row['contentid'] = $listingId;

	        $data = [
	        	'insert' => true,
	        	'JreviewsContent' => $row
	        ];

	        $optionIds = $this->fieldOption->getNewOptionIds();

	        if($result = $this->listingField->store($data, []))
	        {
	        	// Update Listing paid fields here

	        	if($this->importPaidField)
	        	{
	        		$this->importPaidField->import($row);
	        	}

	        	return $this->c->response(true, '', ['option_ids' => $optionIds, 'relatedlistings' => $relatedListings]);
	        }

	        return $this->c->response(false, '', ['option_ids' => $optionIds]);
		}

		return $this->c->response(true, '', ['option_ids' => []]);
	}

    static function extractCustomFields(array $row)
    {
		$data = array_intersect_key($row, array_flip(preg_grep('/jr_/', array_keys($row))));

		$fields = [];

		foreach($data AS $key => $val)
		{
			if($key = preg_match('/(jr_[^)]+)/', $key, $matches))
			{
				$fields[$matches[0]] = $val;
			}
		}

        return $fields;
    }

    public function addRelatedListings($relatedListings, $listings)
    {
    	foreach ($relatedListings AS $listingId => $fields)
    	{
    		$row['contentid'] = $listingId;

    		foreach ($fields as $column => $options)
    		{
    			$ids = [];

    			foreach($options AS $option)
    			{
    				if(isset($listings[$option]))
    				{
    					$ids[] = $listings[$option]['id'];
    				}
    			}

    			$row[$column] = '*' . implode('*', $ids) . '*';
    		}

    		$data = [
        		'JreviewsContent' => $row
    		];

    		$this->listingField->store($data, []);
    	}
    }
}