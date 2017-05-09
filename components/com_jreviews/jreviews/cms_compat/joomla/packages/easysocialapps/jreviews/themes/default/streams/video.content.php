<?php
/**
 * JReviews - Reviews Extension
 * Copyright (C) 2010-2015 ClickFWD LLC
 * This is not free software, do not distribute it.
 * For licencing information visit https://www.jreviews.com
 * or contact sales@jreviews.com
**/

defined( '_JEXEC' ) or die;

$videos = $params['media'];
?>

<div class="jrActivity">

<?php foreach($videos AS $video):?>

	<div class="jrActivityRow">

	    <?php if($video['thumb_src'] != ''):?>

		<div class="jrActivityThumb">

	        <a href="<?php echo $video['media_url'];?>">

	            <img alt="<?php echo $this->html('string.escape', ($video['title'] != '' || !isset($video['listing_title']) ? $video['title'] : $video['listing_title']) );?>" src="<?php echo $video['thumb_src'];?>" />

				<?php /*<b><?php echo CVideosHelper::toNiceHMS(CVideosHelper::formatDuration($video['duration']));?></b>*/?>

	        </a>

		</div>

	    <?php endif;?>

		<div class="jrActivityContent">

		    <?php if($video['title'] != ''):?>

				<div class="jrActivityTitle">

		            <a href="<?php echo $video['media_url'];?>"><?php echo $video['title'];?></a>

				</div>

		    <?php endif;?>

			<div class="<?php if($video['title'] != ''):?>mt-10	<?php endif;?> jrActivityDesc">

				<?php echo $this->html('string.truncater', html_entity_decode($video['description']) ,$contentlength); ?>

			</div>

		</div>

	</div>

<?php endforeach;?>

</div>