<?php
/**
 * JReviews - Reviews Extension
 * Copyright (C) 2010-2015 ClickFWD LLC
 * This is not free software, do not distribute it.
 * For licencing information visit https://www.jreviews.com
 * or contact sales@jreviews.com
**/

defined( 'MVC_FRAMEWORK') or die( 'Direct Access to this location is not allowed.' );

class ClaimModel extends MyModel {

	var $name = 'Claim';

	var $useTable = '#__jreviews_claims AS Claim';

	var $primaryKey = 'Claim.claim_id';

	var $realKey = 'claim_id';

    var $fields = array('*');

    function addApprovedClaims($results)
    {
        $listing_ids = array_keys($results);

        $query = '
            SELECT
                listing_id, user_id
            FROM
                #__jreviews_claims
            WHERE
                approved = 1
                AND
                listing_id IN (' . cleanIntegerCommaList($listing_ids) . ')
        ';

        $claims = $this->query($query,'loadAssocList','listing_id');

        foreach($results AS $key=>$result)
        {
            if(isset($claims[$key]) && $result['Listing']['user_id'] == $claims[$key]['user_id'])
            {
                $results[$key]['Claim']['approved'] = 1;
            }
        }

        return $results;
    }

    function afterSave($status)
    {
        // Only do this for admin actions

        if($status && $this->data['Claim']['approved'] == 1 && defined('MVC_FRAMEWORK_ADMIN'))
        {
            // Change listing owner if claim is approved

            S2App::import('Model',array('everywhere_com_content',/*'jreviews_content'*/),'jreviews');

            $listing_id = Sanitize::getInt($this->data['Claim'],'listing_id');

            $user_id = Sanitize::getInt($this->data['Claim'],'user_id');

            $query = "
                UPDATE
                    " . EverywhereComContentModel::_LISTING_TABLE . "
                SET
                    " . EverywhereComContentModel::_LISTING_USER_ID . " = " . $user_id . "
                WHERE
                    " . EverywhereComContentModel::_LISTING_ID . " = " . $listing_id;

            if($this->query($query))
            {
                $this->transferMediaOwnership($listing_id, $user_id);

                // Ensure only one claim per listing exists by dissapproving all other existing claims

                $claim_id = Sanitize::getInt($this->data['Claim'],'claim_id');

                $query = "
                    UPDATE
                        #__jreviews_claims
                    SET
                        approved = -1
                    WHERE
                        listing_id = " . $listing_id . "
                        AND
                        claim_id <> " . $claim_id
                ;

                $this->query($query);
            }
        }
    }

    protected function transferMediaOwnership($listingId, $toUserId)
    {
        $query = '
            UPDATE
                #__jreviews_media
            SET
                user_id = %d
            WHERE
                listing_id = %d
        ';

        $this->query(sprintf($query, $toUserId, $listingId));
    }
}
