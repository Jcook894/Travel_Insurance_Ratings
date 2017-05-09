<?php
/**
 * RapidLauncher Addon for JReviews
 * Copyright (C) 2010-2016 ClickFWD LLC
 * This is not free software, do not distribute it.
 * For licencing information visit https://www.jreviews.com
 * or contact sales@jreviews.com
**/

defined( 'MVC_FRAMEWORK') or die;

S2App::Import('Model', ['rapidlauncher_listing', 'rapidlauncher_field', 'rapidlauncher_field_option', 'rapidlauncher_category', 'media'], 'jreviews');

S2App::import('Component', ['base_repository'], 'jreviews');

S2App::import('AdminComponent', [
        'rapidlauncher_listing_field_helper',
        'rapidlauncher_photo_helper',
        'rapidlauncher_video_embed_helper'
    ],
    'jreviews'
);

class RapidlauncherListingHelperComponent extends BaseRepository
{
    protected $modelName = 'listing';

    protected $c;

    protected $listing;

    protected $category;

    protected $fieldOption;

    protected $field;

    protected $config;

    protected $fieldsArray;

    protected $update = false;

    function startup($controller)
    {
        $this->c = $controller;

        $this->listing = ClassRegistry::getClass('RapidlauncherListingModel');

        $this->category = ClassRegistry::getClass('RapidlauncherCategoryModel');

        $this->field = ClassRegistry::getClass('RapidlauncherFieldModel');

        $this->fieldOption = ClassRegistry::getClass('RapidlauncherFieldOptionModel');

        $this->listingFieldHelper = ClassRegistry::getClass('RapidlauncherListingFieldHelperComponent');

        $this->photoHelper = ClassRegistry::getClass('RapidlauncherPhotoHelperComponent');

        $this->videoEmbedHelper = ClassRegistry::getClass('RapidlauncherVideoEmbedHelperComponent');

        $this->listingFieldHelper->startup($controller);

        $this->photoHelper->startup($controller);

        $this->videoEmbedHelper->startup($controller);

        $this->user = cmsFramework::getUser();

        $this->media = ClassRegistry::getClass('MediaModel');
    }

    function setPath($path)
    {
        $this->photoHelper->setPath($path);

        return $this;
    }

    function import($rows)
    {
        $headers = array_shift($rows);

        // Build main media array

        $main_media = $media_function = [];

        $i = 1;

        foreach($headers AS $key => $header)
        {
            if(strstr($header, 'photo') || strstr($header, 'video'))
            {
                $parts = explode('|', $header);

                $headers[$key] = array_shift($parts) . $i;

                $main_media[$i] = array_shift($parts);

                $media_function[$i] = array_shift($parts);

                $i++;
            }
        }

        // Pre-defined order for standard columns

        $headers[0] = RapidlauncherListingModel::_LISTING_TITLE;

        $headers[1] = 'category';

        $headers[2] = RapidlauncherListingModel::_LISTING_SUMMARY;

        $headers[3] = RapidlauncherListingModel::_LISTING_DESCRIPTION;

        // Build media function array

        $fields = $this->field->getList('listing');

        $config = [
            'duplicate_fields' => [RapidlauncherListingModel::_LISTING_TITLE, RapidlauncherListingModel::_LISTING_CAT_ID]
        ];

        $relatedListings = [];

        foreach($rows AS $rowIndex => $row)
        {
            $photos = $videos = array();

            // Remove any data for which there isn't a column

            $row = array_intersect_key($row, $headers);

            // Remove any columns for which there's no data

            $headers = array_intersect_key($headers, $row);

            $row = array_combine($headers, $row);

            // Need to get the category id

            $categoryId = $this->category->getCategoryId($row['category']);

            if(!$categoryId) continue;

            unset($row['category']);

            $row[RapidlauncherListingModel::_LISTING_CAT_ID] = $categoryId;

            $row[RapidlauncherListingModel::_LISTING_USER_ID] = $this->user->id;

            $photos = array_intersect_key($row, array_flip(preg_grep('/^photo/', array_keys($row))));

            $videos = array_intersect_key($row, array_flip(preg_grep('/^video/', array_keys($row))));

            $response = $this->setFields($fields)->setConfig($config)->create($row);

            if($response['success'])
            {
                $listing = $response['result'];

                $listingId = Sanitize::getInt($listing['Listing'], RapidlauncherListingModel::_LISTING_ID);

                /**
                 * Import Custom Field Values
                 */

                $fieldsData = Sanitize::getVar($listing, 'Field', []);

                $res = $this->listingFieldHelper->create($listingId, $row, $fields);

                if($res['success'] && isset($res['relatedlistings']) && !empty($res['relatedlistings']))
                {
                    $relatedListings[$listingId] = $res['relatedlistings'];
                }
                /**
                 * Import photos
                 */

                foreach($photos AS $key => $val)
                {
                    $matches = [];

                    // Extract the number at the end of the key string to match that wth the main_media, media_function array

                    preg_match('#(\d+)$#', $key, $matches);

                    $parts = explode('|', $val);

                    $path = trim(Sanitize::getString($parts, 0));

                    if($path == '') continue;

                    $caption = Sanitize::getString($parts, 1, '');

                    $res = $this->photoHelper->create(
                        $listingId,
                        [
                            'user_id' => Sanitize::getString($listing['Listing'], RapidlauncherListingModel::_LISTING_USER_ID),
                            'created' => Sanitize::getString($listing['Listing'], RapidlauncherListingModel::_LISTING_CREATE_DATE),
                            'main_media' => Sanitize::getInt($main_media, (int) $matches[1]),
                            'media_function' => Sanitize::getString($media_function, $matches[1], ''),
                            'caption' => $caption,
                            'path' => $path
                        ]
                    );
                }

                /**
                 * Import embedded videos
                 */

                foreach($videos AS $key => $val)
                {
                    $matches = [];

                    // Extract the number at the end of the key string to match that wth the main_media array

                    preg_match('#(\d+)$#', $key, $matches);

                    $parts = explode('|', $val);

                    $url = trim(Sanitize::getString($parts, 0));

                    if($url == '') continue;

                    $caption = Sanitize::getString($parts, 1, '');

                    $res = $this->videoEmbedHelper->create(
                        $listingId,
                        [
                            'user_id' => Sanitize::getString($listing['Listing'], RapidlauncherListingModel::_LISTING_USER_ID),
                            'created' => Sanitize::getString($listing['Listing'], RapidlauncherListingModel::_LISTING_CREATE_DATE),
                            'main_media' => Sanitize::getInt($main_media, (int) $matches[1]),
                            'media_function' => '',
                            'caption' => $caption,
                            'url' => $url
                        ]
                    );
                }
            }
        }

        if(!empty($relatedListings))
        {
            // Extract listing titles

            $titles = [];

            foreach($relatedListings AS $fields)
            {
                foreach($fields AS $options)
                {
                    $titles = array_merge($titles, $options);
                }
            }

            $titles = array_unique($titles);

            $listings = $this->listing->getIdsByTitles($titles);

            $this->listingFieldHelper->addRelatedListings($relatedListings, $listings);
        }

        $this->media->updateListingCounts();
    }

    function setConfig($config)
    {
        $this->config = $config;

        return $this;
    }

    function setFields($fields)
    {
        $this->fieldsArray = $fields;

        return $this;
    }

    function create($row)
    {
        // Reset query conditions

        $this->clearQueryData();

        // Look for duplicates and skip import if one found

        if($columns = array_filter(Sanitize::getVar($this->config, 'duplicate_fields', [])))
        {
            // Standard fields

            $columns = array_combine($columns, $columns);

            if(isset($columns[RapidlauncherListingModel::_LISTING_TITLE]) && ($value = Sanitize::getString($row, RapidlauncherListingModel::_LISTING_TITLE)))
            {
                if($value != '')
                {
                    $this->where('Listing.' . RapidlauncherListingModel::_LISTING_TITLE . ' = ' . $this->c->Quote($value));
                }
            }

            if(isset($columns[RapidlauncherListingModel::_LISTING_SLUG]) && ($value = Sanitize::getString($row, RapidlauncherListingModel::_LISTING_SLUG)))
            {
                if($value != '')
                {
                    $this->where('Listing.' . RapidlauncherListingModel::_LISTING_SLUG . ' = ' . $this->c->Quote($value));
                }
            }

            if(isset($columns[RapidlauncherListingModel::_LISTING_CAT_ID]) && ($value = Sanitize::getString($row, RapidlauncherListingModel::_LISTING_CAT_ID)))
            {
                if($value != '')
                {
                    $this->where($this->listing->whereCatId($value));
                }
            }

            if(!empty($this->getConditions()))
            {
                $duplicateCount = $this->count();

                if($duplicateCount > 0)
                {
                    return $this->c->response(false, 'DUPLICATE_LISTING');
                }
            }
        }

        // Add the current date

        if(Sanitize::getString($row, RapidlauncherListingModel::_LISTING_CREATE_DATE) == '')
        {
            $row[RapidlauncherListingModel::_LISTING_CREATE_DATE] = _CURRENT_SERVER_TIME;
        }

        if($result = $this->listing->import($row))
        {
            return $this->c->response(true, '', ['result' => $result]);
        }

        return $this->c->response(false, 'DATABASE_ERROR');
    }
}