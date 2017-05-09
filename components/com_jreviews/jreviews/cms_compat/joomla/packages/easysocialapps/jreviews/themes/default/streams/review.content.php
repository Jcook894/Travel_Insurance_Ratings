<?php
/**
 * JReviews - Reviews Extension
 * Copyright (C) 2010-2015 ClickFWD LLC
 * This is not free software, do not distribute it.
 * For licencing information visit https://www.jreviews.com
 * or contact sales@jreviews.com
**/

defined( '_JEXEC' ) or die;

$rating = $params['average_rating']; // non-rounded average rating for the review

$rating_type = $params['editor_review'] ? 'Editor' : 'User';

$scale = $params['scale'];

$ratingPercent = number_format(($rating/$scale)*100,0);
?>

<div class="jrActivity">

    <?php if($params['thumb_src'] != ''):?>

	<div class="jrActivityThumb">

        <a href="<?php echo JRoute::_($params['listing_url']);?>">

            <img src="<?php echo $params['thumb_src'];?>" />

        </a>

	</div>

    <?php endif;?>

	<div class="jrActivityContent">

		<div class="jrActivityTitle">

	        <?php if($title != ''):?>

	            <a href="<?php echo JRoute::_($params['listing_url']);?>"><?php echo $title;?></a>

	        <?php endif;?>

	        <?php if($rating > 0):?>

	        	<div class="jrRatingStars<?php echo $rating_type;?>"><div style="width:<?php echo $ratingPercent;?>%">&nbsp;</div></div>

	        <?php endif;?>

		</div>

		<div class="mt-5 jrActivityDesc">

			<blockquote>

				<?php echo $this->html('string.truncater', strip_tags(html_entity_decode($content, ENT_COMPAT, 'UTF-8')) ,$contentlength); ?>

			</blockquote>

		</div>

	</div>

</div>