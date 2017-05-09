<?php
defined( 'MVC_FRAMEWORK') or die( 'Direct Access to this location is not allowed.' );

// Optional Criteria Ratings: Populate 'required' field in jos_jreviews_criteria on jreviews upgrade
$query = "SELECT id, criteria, required FROM #__jreviews_criteria";
$this->_db->setQuery($query);
$rows = $this->_db->loadObjectList();

foreach ( $rows as $row )
{
    if ( $row->required == '' )
    {
        $query = "UPDATE #__jreviews_criteria
            SET required = '".str_repeat("1\n", substr_count(trim($row->criteria), "\n") + 1)."'
            WHERE id = {$row->id}"
        ;
        $this->_db->setQuery($query);
        if ( !$this->_db->query() )
        {
            # ERROR
        }
    }
}

// Populate the listing_totals table
App::import('Controller','admin/criterias');
if(class_exists('CriteriasController'))
{
    $Criteria = new CriteriasController('jreviews');
    if ( ! $Criteria->refreshReviewRatings() )
    {
        # handle error!
    }    
}