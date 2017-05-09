<?php
/**
 * JReviews - Reviews Extension
 * Copyright (C) 2009 ClickFWD LLC
 * This is not free software, do not distribute it.
 * For licencing information visit http://www.reviewsforjoomla.com
 * or contact sales@reviewsforjoomla.com
**/

defined( 'MVC_FRAMEWORK') or die( 'Direct Access to this location is not allowed.' );

class ClaimModel extends MyModel {
		
	var $name = 'Claim';
	
	var $useTable = '#__jreviews_claims AS Claim';

	var $primaryKey = 'Claim.claim_id';
	
	var $realKey = 'claim_id';

    function afterSave($status)
    {
        // Change listing owner if claim is approved
        if($status && $this->data['Claim']['approved'] == 1)  
        {            
            App::import('Model',array('everywhere_com_content','jreviews_content'),'jreviews');
            $Listing = new EverywhereComContentModel();
            $JreviewsContent = new JreviewsContentModel();
            $Listing->store($this->data);
            $JreviewsContent->store($this->data);
        }         
    }
}
