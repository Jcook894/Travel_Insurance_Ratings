<?php
/**
 * JReviews - Reviews Extension
 * Copyright (C) 2010-2015 ClickFWD LLC
 * This is not free software, do not distribute it.
 * For licencing information visit https://www.jreviews.com
 * or contact sales@jreviews.com
**/

defined( '_JEXEC' ) or die;

$activity = 'media_like_'.$media_type . '_'. $item->verb . (!$target ? '_guest' : '');
?>

<div class="jrActivityHeading">

	<?php echo JText::sprintf($activities[$activity], $actorLink, $targetLink);?>

</div>
