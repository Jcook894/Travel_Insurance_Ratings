<?php
/**
 * JReviews - Reviews Extension
 * Copyright (C) 2010-2015 ClickFWD LLC
 * This is not free software, do not distribute it.
 * For licencing information visit https://www.jreviews.com
 * or contact sales@jreviews.com
**/

defined( 'MVC_FRAMEWORK') or die( 'Direct Access to this location is not allowed.' );

S2App::import('Component', 'base_repository', 'jreviews');

class ListingTypesRepositoryComponent extends BaseRepository {

    protected $modelName = 'listingType';

    protected $c;

    protected $listingType;

    protected $field;

	function startup (& $controller)
	{
		$this->c = & $controller;

        $this->listingType = & $this->c->Criteria;

        $this->field = & $this->c->Field;
	}

    function many($columns = '')
    {
        $queryData = & $this->queryData;

        if(!is_null($this->callbacks))
        {
            $listingTypes = $this->listingType->findAll($queryData, $this->callbacks);
        }
        else {
            $listingTypes = $this->listingType->findAll($queryData);
        }

        return $this->get($listingTypes, $columns);
    }

    function whereFieldExists($field)
    {
        $listingTypeIds = array();

        $groupId = $this->field->query(
            'SELECT groupid FROM #__jreviews_fields WHERE name = ' . $this->field->Quote($field),
            'loadResult'
        );

        $listingTypes = $this->fieldReset(array('Criteria.id', 'Criteria.groupid'))
            ->many('id, groupid');

        foreach($listingTypes AS $type)
        {
            if(in_array($groupId, explode(',', $type['groupid'])))
            {
                $listingTypeIds[] = $type['id'];
            }
        }

        if (!empty($listingTypeIds))
        {
            $this->whereIn('Criteria.id', $listingTypeIds);
        }

        $this->whereIn('Criteria.id', $listingTypeIds);

        return $this;
    }
}