<?php
/**
 * RapidLauncher Addon for JReviews
 * Copyright (C) 2010-2016 ClickFWD LLC
 * This is not free software, do not distribute it.
 * For licencing information visit https://www.jreviews.com
 * or contact sales@jreviews.com
**/

defined( 'MVC_FRAMEWORK') or die( 'Direct Access to this location is not allowed.' );

class RapidlauncherRatingCriteriaModel extends MyModel  {

    var $name = 'RatingCriteria';

    /**
     * Create a new listing type only if another one with the same tile doesn't exist
     * @param  [type] $data [description]
     * @return [type]      [description]
     */
    public function create($data)
    {
        $values = array();

        $data = array_filter($data);

        $criteriaTitle = $data['title'];

        $listingTypeId = $data['listing_type_id'];

        foreach($data AS $key => $value)
        {
            $values[] = $this->Quote($value) . ' AS ' . $key;
        }

        $values = implode(',', $values);

        $query = '
            INSERT INTO
                #__jreviews_criteria_ratings (%s)
            SELECT * FROM (SELECT %s) AS tmp
            WHERE NOT EXISTS
                ( SELECT criteria_id
                    FROM #__jreviews_criteria_ratings
                  WHERE listing_type_id = %d AND title = %s
                )
            LIMIT 1
        ';

        $query = sprintf($query, implode(',', array_keys($data)), $values, $listingTypeId, $this->Quote($criteriaTitle));

        $this->query($query);
    }

    public function read($listingTypeId)
    {
        $query = '
            SELECT
                ListingType.title AS `Listing Type`, Criteria.title AS `Criteria Title`, Criteria.required AS `Required`, Criteria.weight AS `Weight`, Criteria.description AS `Description`
            FROM
                #__jreviews_criteria_ratings AS Criteria
            LEFT JOIN
                #__jreviews_criteria AS ListingType ON ListingType.id = Criteria.listing_type_id
            WHERE listing_type_id IN ('.cleanIntegerCommaList($listingTypeId).')
        ';

        return $this->query($query, 'loadAssocList');
    }
}