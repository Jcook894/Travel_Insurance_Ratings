<?php
/**
 * JReviews - Reviews Extension
 * Copyright (C) 2010-2015 ClickFWD LLC
 * This is not free software, do not distribute it.
 * For licencing information visit https://www.jreviews.com
 * or contact sales@jreviews.com
**/

defined( '_JEXEC' ) or die;

$media = $params['media'];
?>


<div class="jrActivity">

    <?php if($params['thumb_src'] != ''):?>

	<div class="jrActivityThumb">

        <a href="<?php echo $params['listing_url'];?>">

            <img alt="<?php echo $this->html('string.escape', $media['title'] != '' ? $media['title'] : $params['listing_title']);?>" src="<?php echo $params['thumb_src'];?>" />

        </a>

	</div>

    <?php endif;?>

	<div class="jrActivityContent">

		<?php echo $this->html('string.truncater', strip_tags(html_entity_decode($content, ENT_COMPAT, 'UTF-8')) ,$contentlength); ?>

	</div>

</div>

