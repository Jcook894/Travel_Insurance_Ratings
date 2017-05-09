<?php
/**
 * JReviews - Reviews Extension
 * Copyright (C) 2010-2016 ClickFWD LLC
 * This is not free software, do not distribute it.
 * For licencing information visit https://www.jreviews.com
 * or contact sales@jreviews.com
**/

defined('_JEXEC') or die;

require_once PATH_APP . '/includes/shortcodes/JReviewsBaseShortCode.php';

class JReviewsShortCode extends JReviewsBaseShortCode
{
	var $overridePath;

	public function __construct()
	{
		$this->overridePath = JPATH_SITE.'/templates/jreviews_overrides';
	}

	public function processAttr(& $attr)
	{
		$Model = new S2Model;

		S2App::import('Model', 'everywhere_com_content', 'jreviews');

		if (isset($attr['listing_id']) && $attr['listing_id'] > 0)
		{
			$attr['listing'] = (int) $attr['listing_id'];
		}
		else {

			// Listing Alias

			if (isset($attr['alias'])) {
				$attr['listing_alias'] = $attr['alias'];
			}

			if (isset($attr['listing_alias']))
			{
				$query = sprintf('SELECT %s FROM %s WHERE %s = "%s"', EverywhereComContentModel::_LISTING_ID, EverywhereComContentModel::_LISTING_TABLE, EverywhereComContentModel::_LISTING_SLUG, $attr['listing_alias']);

				if ($listingId = $Model->query($query, 'loadResult'))
				{
					$attr['listing'] = $listingId;
				}
				else {
					$attr = array();
				}
			}

		}

		parent::processAttr($attr);
	}
}