<?php
/**
 * JReviews - Reviews Extension
 * Copyright (C) 2010-2016 ClickFWD LLC
 * This is not free software, do not distribute it.
 * For licencing information visit https://www.jreviews.com
 * or contact sales@jreviews.com
**/

defined( 'MVC_FRAMEWORK') or die( 'Direct Access to this location is not allowed.' );

class AutomaticTitlesComponent extends S2Component {

    var $name = 'automatic_titles';

    var $published = true;

    var $plugin_order = 0;

    protected $controller;

    protected $config;

    protected $replaceTitle = false;

    protected $replaceAlias = false;

    protected $listingTitleFormat = false;

    protected $listingAliasFormat = false;

    function startup($controller)
    {
        $this->controller = $controller;

        $this->config = $controller->Config;

        $this->listingTitleFormat = Sanitize::getString($this->config, 'listing_title_format');

        $this->listingAliasFormat = Sanitize::getString($this->config, 'listing_alias_format');

		if(isset($controller->Listing))
		{
        	$controller->Listing->addObserver('plgAfterSave',$this);

        	// If the listing title is disabled and a title format specicied then we populate it with the date/time in order to allow the title validation to pass.

        	if($this->checkTitle() && $controller->data['Listing']['title'] == '')
        	{
        		$controller->data['Listing']['title'] = date("Y-m-d H:i:s");
        	}
		}
    }

    function plgAfterSave(& $model)
    {
    	$title = $alias = '';

    	$isNew = $model->isNew;

    	if(!$this->published($isNew))
		{
			return true;
		}

    	$listingId = Sanitize::getInt($model->data['Listing'], 'id');

        // Flag it as listing edit, to force the paid listing fields values to load as part of the listing
        // even when the order has not been processed. Otherwise we can't access those field values to use them
        // as part of the formatting for title and alias

        Configure::write('ListingEdit', true);

		$listing = $model->findRow(
			array(
				'conditions'=>array('Listing.' . $model::_LISTING_ID . ' = '. $listingId)
			),
			array('afterFind' /* Only need menu id */
		));

		// Check if title should be processed - title input hidden and title format not empty

		if($this->checkTitle())
		{
			$title = $this->process($this->listingTitleFormat, $listing);
		}

		if($this->checkAlias($isNew))
		{
			if($alias = $this->process($this->listingAliasFormat, $listing))
			{
				$alias = S2Router::sefUrlEncode($alias);
			}
		}

        if($title != '' || $alias != '')
        {
            $this->update($model, $listing, $title, $alias);
        }

        return true;
    }

    protected function update($ListingModel, $listing, $title, $alias)
    {
    	$listingId = Sanitize::getInt($listing['Listing'], 'listing_id');

    	$catId = Sanitize::getInt($listing['Listing'], 'cat_id');

        // If the alias has changed, check for duplicates

    	if($alias != '')
    	{
	        $duplicateCount = $ListingModel->findDuplicates($this->config->content_title_duplicates, array(
	            'slug' => $alias,
	            'cat_id' => $catId,
	            'listing_id' => $listingId // Used to exclude this listing from the search
	            ));

	        // If duplicates allowed in the same category and there's a duplicate, append the duplicate count to the listing alias

	        if($duplicateCount > 0)
	        {
	            $alias .= '-' . ($duplicateCount + 1);
	        }
    	}

    	// Perform the update

		$query = 'UPDATE %s AS Listing SET %s WHERE Listing.%s = %d';

		$setQuery = array();

		if($title != '')
		{
			$setQuery[] = ' Listing.'.$ListingModel::_LISTING_TITLE.' = '.$ListingModel->Quote($title);

			$listing['Listing']['title'] = $title;
		}

		if($alias != '')
		{
			$setQuery[] = ' Listing.'.$ListingModel::_LISTING_SLUG.' = '.$ListingModel->Quote($alias);

			$listing['Listing']['alias'] = $alias;
		}

		if(empty($setQuery))
		{
			return true;
		}

		$query = sprintf($query, $ListingModel::_LISTING_TABLE, implode(',', $setQuery), $ListingModel::_LISTING_ID, $listingId);

		if($ListingModel->query($query))
		{
    		$this->controller->set('listing', $listing);
		}
    }

    protected function published($isNew)
    {
    	return ( $this->checkTitle() || $this->checkAlias($isNew) );
    }

    protected function checkTitle()
    {
		if (!$this->config->listing_title && $this->listingTitleFormat != '')
		{
			return true;
		}

		return false;
    }

    protected function checkAlias($isNew)
    {
		if (
			// Alias format not empty
			$this->listingAliasFormat != ''
			&& (
				// Replace the title when submitting a new listing
				$isNew
				||
				// Replace the title when editing the listing
				($this->config->listing_alias_format_edit && !$isNew && !strstr($this->listingAliasFormat, '{title}'))
			)
		) {
			return true;
		}

		return false;
    }

    protected function process($format, $listing)
    {
        $tags = self::extractTags($format);

        if(empty($tags))
        {
        	return false;
        }

        S2App::import('Helper', 'time', 'jreviews');

        $TimeHelper = ClassRegistry::getClass('TimeHelper');

        $tagsArray = array();

        $tagsArrayCurly = array();

        foreach ($tags AS $tag)
        {
        	$tagsArrayCurly[] = '{'.$tag.'}';

            switch ($tag)
            {
                case 'listing_id':
                    $tagsArray['{listing_id}'] = Sanitize::getInt($listing['Listing'], 'listing_id');
                break;
                case 'title':
                    $tagsArray['{title}'] = Sanitize::stripAll($listing['Listing'], 'title');
                break;
                case 'directory':
                    $tagsArray['{directory}'] = Sanitize::stripAll($listing['Directory'], 'title');
                break;
                case 'category':
                    $tagsArray['{category}'] = Sanitize::stripAll($listing['Category'], 'title');
                break;
                default:
                    if (substr($tag,0,3) == 'jr_' && isset($listing['Field']))
                    {
                        $fields = $listing['Field']['pairs'];

                        if (isset($listing['Field']['pairs'][$tag]) && isset($fields[$tag]['text']))
                        {
                            $fieldValue = $fields[$tag]['text'][0];

                            $properties = $fields[$tag]['properties'];

                            if ($fields[$tag]['type'] == 'date')
                            {
                                $dateFormat = Sanitize::getString($properties, 'date_format');

                                $fieldValue = $TimeHelper->nice($fieldValue, $dateFormat, 0);
                            }
                            elseif ($fields[$tag]['type'] == 'decimal') {

                                $decimals = Sanitize::getInt($properties,'decimals', 2);

                                $fieldValue = Sanitize::getInt($properties,'curr_format') ? number_format($fieldValue,$decimals,__l('DECIMAL_SEPARATOR',true),__l('THOUSANDS_SEPARATOR',true)) : round($fieldValue,$decimals);
                            }
                            elseif ($fields[$tag]['type'] == 'integer') {

                                $fieldValue = Sanitize::getInt($properties,'curr_format') ? number_format($fieldValue,0,__l('DECIMAL_SEPARATOR',true),__l('THOUSANDS_SEPARATOR',true)) : $fieldValue;
                            }

                            if (in_array($fields[$tag]['type'], array('integer', 'decimal')))
                            {
                                $fieldValue = str_ireplace('{fieldtext}', $fieldValue, Sanitize::getString($properties,'output_format'));
                            }

                            $fields[$tag]['text'][0] = $fieldValue;

                            $tagsArray['{'.$tag.'}'] = html_entity_decode(implode(", ", $fields[$tag]['text']),ENT_QUOTES,'utf-8');
                        }
                        else {

                            $tagsArray['{'.$tag.'}'] = '';
                        }
                    }
                break;
            }
        }

        $output = strip_tags(str_replace('&amp;', '&', str_replace(array_keys($tagsArray), $tagsArray, $format)));

        // Run it again to replace any remaining tags with nothing

        $output = str_replace($tagsArrayCurly, '', $output);

        return $output;
	}

    static function extractTags($text)
    {
        $pattern = '/{([a-z0-9_|]*)}/i';

        $matches = array();

        $result = preg_match_all( $pattern, $text, $matches );

        if( $result == false ) {
            return array();
        }

        return array_unique(array_values($matches[1]));
    }
}