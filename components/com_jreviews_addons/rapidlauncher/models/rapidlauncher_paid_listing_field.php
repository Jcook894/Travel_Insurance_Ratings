<?php
/**
 * RapidLauncher Addon for JReviews
 * Copyright (C) 2010-2016 ClickFWD LLC
 * This is not free software, do not distribute it.
 * For licencing information visit https://www.jreviews.com
 * or contact sales@jreviews.com
**/

defined( 'MVC_FRAMEWORK') or die( 'Direct Access to this location is not allowed.' );

class RapidlauncherPaidListingFieldModel extends MyModel  {

    var $name = 'PaidListingField';

    var $useTable = '#__jreviews_paid_listing_fields AS `PaidListingField`';

    var $primaryKey = 'PaidListingField.listing_id';

    var $realKey = 'listing_id';

    var $fields = [
        'PaidListingField.fields'
    ];

    function afterFind($results)
    {
         foreach($results AS $key=>$result)
         {
            $results[$key]['PaidListingField']['fields'] = json_decode($result['PaidListingField']['fields'],true);
         }

        return $results;
    }

    function import($fieldData)
    {
        $coreFields = ['contentid', 'email', 'ipaddress', 'listing_note'];

        $listingId = $fieldData['element_id'] = $fieldData['contentid'];

        foreach($coreFields AS $coreKey)
        {
            unset($fieldData[$coreKey]);
        }

        $currPaidFieldRecord = $this->findRow([
            'conditions'=>array('PaidListingField.listing_id = ' . $listingId)
        ]);

        // We only update existing paid field records and merge the imported field data

        if(!empty($currPaidFieldRecord))
        {

            $fieldData = array_merge($currPaidFieldRecord['PaidListingField']['fields'], $fieldData);

            $fieldData = [
                'insert' => false,
                'PaidListingField' => [
                    'listing_id' => $listingId,
                    'fields' => json_encode($fieldData)
                ]
            ];

            return $this->store($fieldData);
        }

        return false;
    }
}