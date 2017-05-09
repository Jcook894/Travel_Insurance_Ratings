<?php
/**
 * RapidLauncher Addon for JReviews
 * Copyright (C) 2010-2016 ClickFWD LLC
 * This is not free software, do not distribute it.
 * For licencing information visit https://www.jreviews.com
 * or contact sales@jreviews.com
**/

defined( 'MVC_FRAMEWORK') or die( 'Direct Access to this location is not allowed.' );

class RapidlauncherListingModel extends MyModel  {

    const _LISTING_TABLE                = '#__content';

    const _LISTING_ID                   = 'id';

    const _LISTING_CAT_ID               = 'catid';

    const _LISTING_TITLE                = 'title';

    const _LISTING_PUBLISHED            = 'state';

    const _LISTING_ACCESS               = 'access';

    const _LISTING_SLUG                 = 'alias';

    const _LISTING_SUMMARY              = 'introtext';

    const _LISTING_DESCRIPTION          = 'fulltext';

    const _LISTING_USER_ID              = 'created_by';

    const _LISTING_CREATE_DATE          = 'created';

    const _LISTING_MODIFIED             = 'modified';

    const _LISTING_AUTHOR_ALIAS         = 'created_by_alias';

    const _LISTING_METAKEY              = 'metakey';

    const _LISTING_METADESC             = 'metadesc';

    const _LISTING_PUBLISH              = 'publish_up';

    const _LISTING_EXPIRE               = 'publish_down';

    const _IGNORE                       = 'ignore';

    var $name = 'Listing';

    var $useTable = '#__content AS Listing';

    var $primaryKey = 'Listing.listing_id';

    var $realKey = 'id';

    function beforeSave(& $data) {}

    function import($row)
    {
        $data = [];

        $listingData = $this->extractStandardFields($row);

        $listingData['Listing'][self::_LISTING_PUBLISHED] = 1;

        $listingData['Listing'][self::_LISTING_ACCESS] = 1;

        $listingData['Listing']['language'] = '*';

        // Generate title alias

        if(!isset($listingData['Listing'][self::_LISTING_SLUG]))
        {
            $listingData['Listing'][self::_LISTING_SLUG] = S2Router::sefUrlEncode($listingData['Listing'][self::_LISTING_TITLE]);
        }
        else {
            $listingData['Listing'][self::_LISTING_SLUG] = S2Router::sefUrlEncode($listingData['Listing'][self::_LISTING_SLUG]);
        }

        // If a duplicate alias already exists, then we append a counter to the title alias before importing the row

        $duplicateAlias = $this->findCount(
            [
                'session_cache' => false,
                'conditions' => [ 'Listing.'. self::_LISTING_SLUG . ' = ' . $this->Quote($listingData['Listing'][self::_LISTING_SLUG]) ]
            ]
        );

        if($duplicateAlias > 0)
        {
            $listingData['Listing'][self::_LISTING_SLUG] .= '-' . ($duplicateAlias + 1);
        }

        // Store the new record

        $result = $this->store($listingData);

        if($listingId = Sanitize::getInt($listingData['Listing'], self::_LISTING_ID))
        {
            $this->createListingTotalsRow($listingId);
        }

        return $result ? $this->data : false;
    }

    protected function extractStandardFields($row)
    {
        $data = [];

        $standardFields = [
            self::_LISTING_ID,
            self::_LISTING_CAT_ID,
            self::_LISTING_TITLE,
            self::_LISTING_SLUG,
            self::_LISTING_SUMMARY,
            self::_LISTING_DESCRIPTION,
            self::_LISTING_USER_ID,
            self::_LISTING_CREATE_DATE,
            self::_LISTING_MODIFIED,
            self::_LISTING_AUTHOR_ALIAS,
            self::_LISTING_METAKEY,
            self::_LISTING_METADESC,
            self::_LISTING_PUBLISH,
            self::_LISTING_EXPIRE,
        ];

        foreach($standardFields AS $field)
        {
            $value = Sanitize::getString($row, $field);

            if(isset($row[$field]) && $value != '')
            {
                $data['Listing'][$field] = $value;
            }
        }

        return $data;
    }

    function whereCatId($catId)
    {
        $catId = cleanIntegerCommaList($catId);

        $includeChildren = true;

        if($catId && !$includeChildren)
        {
            // Nothing else to do
        }
        elseif($catId) {

            $catId = explode(',', $catId);

            $query = [];

            foreach($catId AS $id)
            {
                $query[] = '
                    SELECT
                        Category.id
                    FROM
                        #__categories AS Category,
                        #__categories AS ParentCategory
                    WHERE
                        Category.extension = "com_content"
                        AND Category.lft BETWEEN ParentCategory.lft AND ParentCategory.rgt
                        AND ParentCategory.id = ' . $id . '
                ';
            }

            $query = implode(' UNION ALL ', $query);

            $currentCatIds = $this->query($query,'loadColumn');

            // Need to make sure the categories are limited to only those setup in JReviews

            $query = '
                SELECT id FROM #__jreviews_categories WHERE `option` = "com_content"
            ';

            $allCatIds = $this->query($query,'loadColumn');

            $results = array_intersect($currentCatIds, $allCatIds);
        }

        if($catId)
        {
            return 'Listing.' . self::_LISTING_CAT_ID . ' IN (' . cleanIntegerCommaList($catId) . ')';
        }

        return false;
    }

    protected function createListingTotalsRow($listingId)
    {
        $query = "
            INSERT INTO
                #__jreviews_listing_totals
                (listing_id, extension)
                VALUES
                (" . $listingId .", 'com_content')
            ON DUPLICATE KEY UPDATE
                listing_id = " . $listingId. ",
                extension = 'com_content'
        ";

        $this->query($query);
    }

    public function getIdsByTitles($titles)
    {
        $query = '
            SELECT
                id, title
            FROM
                #__content
            WHERE
                title IN ('.$this->Quote($titles).')
        ';

        return $this->query($query, 'loadAssocList', 'title');
    }

    public function getTitlesByIds($ids)
    {
        $query = '
            SELECT
                id, title
            FROM
                #__content
            WHERE
                id IN ('.cleanIntegerCommaList($ids).')
        ';

        return $this->query($query, 'loadAssocList', 'id');
    }
}
