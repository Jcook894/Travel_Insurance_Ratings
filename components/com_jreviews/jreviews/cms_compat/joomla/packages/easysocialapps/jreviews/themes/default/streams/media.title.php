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

$title = JText::sprintf($activities['media_'.$media_type], $actorLink, $listingLink);

if($media_count == 1) {

    $title = preg_replace('/^(.*)({single})(.*)({\/single})({multiple}.*{\/multiple})(.*)$/','$1$3$6',$title);
}
else {

    $title = preg_replace('/^(.*)({single}.*{\/single})({multiple})({count})(.*)({\/multiple})(.*)$/','$1$4$5$7',$title);

    $title = str_replace('{count}',$media_count,$title);
}
?>

<div class="jrActivityHeading">

	<?php echo $title;?>

</div>
