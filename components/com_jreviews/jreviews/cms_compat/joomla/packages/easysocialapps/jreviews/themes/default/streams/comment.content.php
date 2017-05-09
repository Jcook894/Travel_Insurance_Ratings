<?php
/**
 * JReviews - Reviews Extension
 * Copyright (C) 2010-2015 ClickFWD LLC
 * This is not free software, do not distribute it.
 * For licencing information visit https://www.jreviews.com
 * or contact sales@jreviews.com
**/

defined( '_JEXEC' ) or die;
?>

<div class="jrActivity">

    <?php if($params['thumb_src'] != ''):?>

	<div class="jrActivityThumb">

		<a href="<?php echo $params['review_url'];?>">

			<img src="<?php echo $params['thumb_src'];?>" />

		</a>

	</div>

    <?php endif;?>

	<div class="jrActivityContent">

	    <?php if($title != ''):?>

		<div class="jrActivityTitle">

	        <a href="<?php echo JRoute::_($params['listing_url']);?>"><?php echo $title;?></a>

		</div>

	    <?php endif;?>

		<div class="<?php if($title != ''):?>mt-5 <?php endif;?>jrActivityDesc">

			<blockquote>

				<?php echo $this->html('string.truncater', strip_tags(html_entity_decode($content, ENT_COMPAT, 'UTF-8')) ,$contentlength);?>

			</blockquote>

		</div>

	</div>

</div>