<?php
/**
 * JReviews - Reviews Extension
 * Copyright (C) 2010-2015 ClickFWD LLC
 * This is not free software, do not distribute it.
 * For licencing information visit https://www.jreviews.com
 * or contact sales@jreviews.com
**/

defined( '_JEXEC' ) or die;

$config = ES::config();

$photos = $params['media'];

$count = count($photos);

// Normalize options
$defaultOptions = array(
	'size' 		=> $config->get('photos.layout.size'),
	'mode'      => $config->get('photos.layout.pattern')=='flow' ? 'contain' : $config->get('photos.layout.mode'),
	'pattern'   => $config->get('photos.layout.pattern'),
	'ratio'     => $config->get('photos.layout.ratio'),
	'threshold' => $config->get('photos.layout.threshold')
);

if (isset($options)) {
	$options = array_merge_recursive($options, $defaultOptions);
} else {
	$options = $defaultOptions;
}
?>

<div class="jrActivity es-photos photos-<?php echo $count;?> es-stream-photos pattern-<?php echo $options['pattern']; ?>">

		<?php foreach($photos as $photo): ?>

		<div class="es-photo es-stream-photo ar-<?php echo $options['ratio']; ?>">

			<a href="<?php echo $photo['media_url']?>">
				<u>
					<b data-mode="<?php echo $options['mode']; ?>" data-threshold="<?php echo $options['threshold']; ?>">
						<img src="<?php echo $photo['orig_src'];?>"
							style="opacity:100;"
							alt="<?php echo $this->html('string.escape', $photo['title']); ?>"
						/>
					</b>
				</u>
			</a>

		</div>

	<?php endforeach; ?>

</div>

