<?php
/**
 * RapidLauncher Addon for JReviews
 * Copyright (C) 2010-2016 ClickFWD LLC
 * This is not free software, do not distribute it.
 * For licencing information visit https://www.jreviews.com
 * or contact sales@jreviews.com
**/

defined( 'MVC_FRAMEWORK') or die( 'Direct Access to this location is not allowed.' );

class RapidlauncherJreviewsCategoryModel extends MyModel  {

    var $name = 'JReviewsCategory';

    public function create($data)
    {
        $query = '
            INSERT IGNORE INTO
                #__jreviews_categories
                (%s)
            VALUES
                (%s)
        ';

        $query = sprintf($query, '`'.implode('`,`',array_keys($data)).'`', $this->Quote($data));

        if($this->query($query))
        {
            return $this->insertid;
        }

        return false;
    }
}