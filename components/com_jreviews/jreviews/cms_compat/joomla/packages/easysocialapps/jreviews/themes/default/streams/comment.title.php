<?php
/**
 * JReviews - Reviews Extension
 * Copyright (C) 2010-2015 ClickFWD LLC
 * This is not free software, do not distribute it.
 * For licencing information visit https://www.jreviews.com
 * or contact sales@jreviews.com
**/

defined( '_JEXEC' ) or die;

$listingLink = '<a href="'.JRoute::_($params['listing_url']).'">'.$params['listing_title'].'</a>';
?>

<div class="jrActivityHeading">

	<?php echo JText::sprintf($activities['discussion_'.$item->verb], $actorLink, $listingLink, '<a href="'.$params['review_url'].'">','</a>');?>

</div>
