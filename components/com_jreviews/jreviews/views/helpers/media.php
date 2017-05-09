<?php
/**
 * JReviews - Reviews Extension
 * Copyright (C) 2010-2015 ClickFWD LLC
 * This is not free software, do not distribute it.
 * For licencing information visit https://www.jreviews.com
 * or contact sales@jreviews.com
**/

defined( 'MVC_FRAMEWORK') or die( 'Direct Access to this location is not allowed.' );

class MediaHelper extends HtmlHelper {

	var $helpers = array('form');

	var $orderingOptions = array();

    function __construct()
    {
    	if(function_exists('libxml_use_internal_errors'))
    	{
    		libxml_use_internal_errors(true);
    	}

        parent::__construct(); // Make parent class vars available here, like cmsVersion

		$this->orderingOptions = array(
			'newest'		=>__t("Newest",true),
			'oldest'		=>__t("Oldest",true),
            'popular'		=>__t("Most Popular",true),
			'liked'			=>__t("Most Liked",true),
			'ordering'		=>__t("Ordering",true)
		);
    }

	/*
	 * Returns the thumbnail url
	 */
	function makeAPICall($media, $options)
	{
		if(!isset($media['media_id'])) {
			return false;
		}

		$_APIKey = md5(cmsFramework::getConfig('secret'));

		$media_id = $media['media_id'];

		$mode = Sanitize::getString($options,'mode','scale');

		$size = $options['size'];

		$apiProcessing = Configure::read('System.mediaApi');

		if(isset($apiProcessing[$media_id.$mode.$size])) {
			return false;
		}

		$apiProcessing[$media_id.$mode.$size] = 1;

		Configure::write('System.mediaApi', $apiProcessing);

		$data = array(
			'media'=>json_encode(array(
				'api_key'=>$_APIKey,
				'size'=>$size,
				'mode'=>$mode,
				'media_id'=>$media['media_id']
			))
		);

		$out = $this->requestAction('media_upload/generateThumb',array('data'=>$data));

		$out = json_decode($out, true);

		return $out['success'] ? $out['url'] : false;
	}


	function defaultThumb($media, $options, $attributes)
	{
		// Don't run in detail pages
		if($this->action == 'com_content_view' && !Sanitize::getString($this->Config,'media_detail_default')) return false;

		$listing = Sanitize::getVar($options,'listing');

		// Read config settings
		$show_noimage = $this->Config->getOverride('media_list_default_image',Sanitize::getVar($listing['ListingType'],'config'));

		$default_image = $this->Config->getOverride('media_general_default_image_path',Sanitize::getVar($listing['ListingType'],'config'));

		$show_cat_image = $this->Config->getOverride('media_list_category_image',Sanitize::getVar($listing['ListingType'],'config'));

		$cat_image = Sanitize::getString($media,'category');

		$everywhere_image = Sanitize::getString($media,'everywhere');

		$css_size = Sanitize::getBool($options,'css_size');

		if(is_numeric($options['size'])) {
			$options['size'] .= 'x' . $options['size'];
		}

		$size = explode('x',low($options['size']));

		if(!isset($attributes['style']) && $css_size) {

			$attributes['style'] = "width:{$size[0]}px; height:{$size[1]}px;";
		}

		$title = Sanitize::getString($media,'title');

		if($title != '')  {

			$attributes['title'] = $attributes['alt'] = htmlspecialchars($title,ENT_QUOTES,'utf-8');
		}
		elseif ($listing) {

			$attributes['alt'] = Sanitize::getString($listing['Listing'], 'title');
		}

		$attributes['data-listingid'] = $listing['Listing']['listing_id'];

		$image_url = '';

		if($everywhere_image) {

			$image_url = strstr($everywhere_image,'http') ? $everywhere_image : WWW_ROOT . $everywhere_image;
		}
		elseif($show_cat_image && $cat_image != '') {

			$image_url = WWW_ROOT . $cat_image;
		}
		elseif($show_noimage) {

			$image_url = WWW_ROOT . $default_image;
		}

		if($image_url != '') {

			$listing = array('Listing'=>array('summary'=>'<img src="'.$image_url.'" />'));

			$tn_url = $this->embedThumb($listing, $options, $attributes);

			if ($tn_url)
			{
				if(Sanitize::getBool($options,'return_url') || Sanitize::getBool($options,'return_src'))
				{
					return $tn_url;
				}

				return $this->image($tn_url,$attributes);
			}
		}

		return false;
	}

	function displayCoverImg($media)
	{
		if(Sanitize::getString($media, 'media_function') == 'cover_override')
		{
			return $this->image($media['media_info']['image']['url']);
		}

		return false;
	}

	/**
	 *
	 * @param type $listing
	 * @param type $media
	 * @param type $options
	 * @param type $attributes
	 * @return string
	 */
    function thumb($media, $options = array(), $attributes = array())
	{
		// Deal with cover overrides first

		if($coverImage = $this->displayCoverImg($media))
		{
			return $coverImage;
		}

		$ajaxRequest = isset($this->ajaxRequest) && $this->ajaxRequest ? true : false;

		$thumbnailer = $ajaxRequest ? 'api' : Sanitize::getString($options,'thumbnailer','ajax'); // Skips running the thumbnailer for consecutive calls to the same thumb like for rich snippets

		$css_size = Sanitize::getBool($options,'css_size'); // Skips running the thumbnailer for consecutive calls to the same thumb like for rich snippets

		$suppressSize = Sanitize::getBool($options, 'suppress_size', false);

		$showFileIcon = Sanitize::getBool($options, 'show_icon' ,false);

		// Perform some checks on the input to get the correct media array

		// For audio and attachments in list/modules check if main media is set and it' a photo so we can use it' thumbnail
		if(isset($media['Media']))
		{
			if(
				$showFileIcon == false

				&& isset($media['MainMedia'])

				&& isset($media['MainMedia']['media_info'])

				&& in_array(Sanitize::getString($media['MainMedia'],'media_type'),array('photo','video'))

				&& in_array(Sanitize::getString($media['Media'],'media_type'),array('audio','attachment'))
			) {
				// Used to always output the 'alt' attribute for main media

				$media = $media['MainMedia'];
			}
			else {

				$media = $media['Media'];
			}
		}

		do if(!isset($media['media_info'])) {

			$embeddedSrc = isset($options['listing']) ? $this->embedThumb($options['listing'], $options, $attributes) : null;

			if($embeddedSrc) {

				// Force width on external images
				$size = Sanitize::getInt($options,'size',100);

				if(Sanitize::getBool($options,'return_src')) return $embeddedSrc;

				$attributes['style'] = 'width:'.$size.'px; height:auto;';

				$attributes['alt'] = htmlspecialchars(Sanitize::getString($options['listing']['Listing'], 'title'), ENT_QUOTES,'utf-8');

				return $this->name != 'com_content' ? $this->image($embeddedSrc, $attributes) : false;
			}

			return $this->defaultThumb($media, $options, $attributes);

		} while(false);

		// For videos in the process of being encoded, show a temporary thumbnail
		if($media['published'] == 2 && $media['media_type'] == 'video') {

			$media['everywhere'] = Sanitize::getString($this->Config,'media_general_default_video_path');

			return $this->defaultThumb($media, $options, $attributes);
		}

		$sizeArray = array();

		$caption = htmlspecialchars(Sanitize::getString($media,'title'),ENT_QUOTES,'utf-8');

		if(($media['main_media'] || substr($this->name, 0, 7) == 'module_') && $caption == '' && isset($options['listing']))
		{
			$caption = htmlspecialchars(Sanitize::getString($options['listing']['Listing'],'title'),ENT_QUOTES,'utf-8');
		}

		$caption = trim($caption);

		$attributes = array_merge(array('alt'=>$caption,'title'=>$caption,'class'=>'jrMedia'.Inflector::camelize($media['media_type'])),$attributes);

		$attributes = array_filter($attributes);

		$size = Sanitize::getString($options,'size');

		// Fix for settings where height was not required or is not specified
		if(is_numeric($size)) {

			$size .= 'x'.$size;

			$options['size'] = $size;
		}

		if($size != '') {

			$sizeArray = explode('x',low($size));
		}

		$file_ext = $media['file_extension'];

		$filetype_img_url = '';

		switch($media['media_type'])
		{
			case 'attachment':

				$filetype_img_url = ThemingComponent::getImageUrl('filetype/128/'.$file_ext.'.png');

				if (!$filetype_img_url)
				{
					$filetype_img_url = ThemingComponent::getImageUrl('filetype/128/_blank.png');
				}

			break;

			case 'audio':
				$filetype_img_url = ThemingComponent::getImageUrl('filetype/128/audio.png');
				break;
		}

		if($filetype_img_url)
		{
			if(isset($options['dimensions']) && !isset($attributes['style'])) {

				$css_size and $attributes['style'] = 'width:'.$sizeArray[0] .'px; height:' . $sizeArray[1] . 'px;';

				unset($options['dimensions']);
			}
			else {

				// Default size of media type images
				$attributes['style'] = 'width:70px; height:70px;';
			}

			return $this->image($filetype_img_url, $attributes);
		}

		$gotThumb = false;

		// Perform size and availability of thumbnail check, otherwise generate the thumbnail

		// Get original image url, then check for existing thumbs, otherwise create them
		$image_url = $this->mediaSrc($media);

		if(isset($options['skipthumb'])) {

			if(!empty($sizeArray)) {

				$attributes['width'] = $sizeArray[0];

				$attributes['height'] = $sizeArray[1];
			}
			else {

				$attributes['width'] = $media['media_info']['image']['width'];

				$attributes['height'] = $media['media_info']['image']['height'];
			}

			if(isset($options['lazyload'])) {

				$attributes['data-src'] = $image_url;

				$image_url = '#';
			}

			return $this->image($image_url,$attributes);
		}


		$image = Sanitize::getVar($media['media_info'],'image');

		$thumbnail = Sanitize::getVar($media['media_info'],'thumbnail');

		$SizeMode = $options['size'].$options['mode']{0};

		$thumb_in_array = isset($thumbnail[$SizeMode]);

		if($thumb_in_array)
		{
			$thumb_path = str_replace(WWW_ROOT,PATH_ROOT,$thumbnail[$SizeMode]['url']);

			$local_storage = $thumb_in_array ? strstr($thumbnail[$SizeMode]['url'],WWW_ROOT) : false;
		}
		else {

			$thumb_path = '';

			$local_storage = false;
		}

		// In local storage we check for the file to recreate it if not present
		// - this should be removed in favor of global reset function that works with remote storage as well
		if($thumb_in_array
			&& ($local_storage && file_exists($thumb_path)
				||
				!$local_storage
				)
		) {
			// No resizing is necessary
			$tn = $thumbnail[$SizeMode];
			$gotThumb = true;
		}
		else {

			if($thumbnailer == 'api') {

				$image_url = $this->makeAPICall($media, $options);
			}

			// For API calls we display the original image just once to avoid an empty image while the thumbnail is generated
			// That is unless the return_thumburl option is set to override it
			// Useful for plugins and external posting of images (JomSocial, Facebook, Twitter) which don't require showing the
			// thumbnail right away

			if(Sanitize::getBool($options,'return_thumburl') !== true)
			{
				$thumbnailer == 'ajax' and $attributes['data-thumbnail'] = 1;

				if(!$suppressSize)
				{
					$attributes['style'] = "width:{$sizeArray[0]}px;height:{$sizeArray[1]}px;";
				}

				$attributes['data-media-id'] = $media['media_id'];

				$attributes['data-size'] = $options['size'];

				$attributes['data-mode'] = Sanitize::getString($options,'mode','scale');

				$tn['url'] =  $image_url;
			}

			$tn['width'] = $sizeArray[0];

			$tn['height'] = $sizeArray[1];
		}

		if(!$suppressSize && !isset($attributes['style']) && $css_size) {

			$attributes['style'] =  "width: {$tn['width']}px; height:{$tn['height']}px;";
		}

		if(!isset($tn['url']))
		{
			extract(parse_url($media['media_info']['image']['url'])); /* $scheme, $host, $path */

			$path = str_replace('/'.MEDIA_ORIGINAL_FOLDER.'/','/'.MEDIA_THUMBNAIL_FOLDER.'/' . $SizeMode .'/',$path);

			// Rebuild url

			if(isset($scheme) && isset($host)) {

				$tn_url = $scheme . '://' . $host . $path;
			}
			else {

				$tn_url = $path;
			}

		}
		else {
			$tn_url = $tn['url'];
		}

		if(!$tn_url) return '';

		if(Sanitize::getBool($options,'return_src') || Sanitize::getBool($options,'return_url')) {

			return $tn_url;
		}

		if(Sanitize::getBool($options,'lightbox')) {

			$rel = Sanitize::getString($options,'rel','gallery');

			$image = $this->image($tn_url,$attributes);

			return $this->link($image, $image_url, array('sef'=>false,'class'=>'fancybox','rel'=>$rel,'title'=>$caption));
		}

		if(isset($options['lazyload'])) {
			$attributes['data-src'] = $tn_url;
			$tn_url = '#';
		}

		return $this->image($tn_url,$attributes);
	}

	function mediaSrc($media)
	{
		if(isset($media['Media']))  {
			return $media['Media']['media_info']['image']['url'];
		}

		return $media['media_info']['image']['url'];
	}


	function orderingListListing($selected, $attributes = array())
	{
		if(isset($attributes['exclude'])) {
			foreach($attributes['exclude'] AS $key) {
				unset($options_array[$key]);
			}
		}

		$options_array = $this->orderingOptions;

		unset($attributes['exclude']);

		return $this->generateList($options_array, $selected, $attributes);
	}

	/**
	 * ORDERING AND FILTER LISTS
	 */
	function orderingList($selected, $attributes = array())
	{
		if(isset($attributes['exclude'])) {
			foreach($attributes['exclude'] AS $key) {
				unset($options_array[$key]);
			}
		}

		$options_array = $this->orderingOptions;

		unset($options_array['ordering']);

		unset($attributes['exclude']);

		return $this->generateList($options_array, $selected, $attributes);
	}

	function generateList($orderingList, $selected, $attributes)
	{
		$attributes = array_merge(array(
            'class'=>'jr-list-sort',
			'size'=>'1',
			'onchange'=>"window.location=this.value;return false;"
			)
			, $attributes
		);

		return $this->generateFormSelect('order', $orderingList, $selected, array('lang','order',S2_QVAR_PAGE), $attributes);
	}

	function mediaTypeFilter($selected)
	{
		$options_array = array(
			''			=>__t("All",true),
			'video'			=>__t("Video",true),
			'photo'			=>__t("Photo",true),
            'attachment'    =>__t("Attachment",true),
			'audio'			=>__t("Audio",true)
		);

        $orderingList = $options_array;

		$attributes = array(
            'class'=>'jr-list-sort',
			'size'=>'1',
			'onchange'=>"window.location=this.value;return false;"
		);

		return $this->generateFormSelect('type', $orderingList, $selected, array('lang','type',S2_QVAR_PAGE), $attributes);
	}

	function generateFormSelect($key, $orderingList, $selected, $params, $attributes)
	{
		# Construct new route
		$args = $this->passedArgs;

		$new_route = cmsFramework::constructRoute($args, $params);

		$selectList = array();

		foreach($orderingList AS $value=>$text)
		{
			if($value !='' && Sanitize::getString($attributes,'default') != $value)
			{
				$selectList[] = array('value'=>cmsFramework::route($new_route . '/' . $key . _PARAM_CHAR . $value),'text'=>$text);
			}
			else {
				$selectList[] = array('value'=>cmsFramework::route($new_route),'text'=>$text);			}
		}

		unset($attributes['default']);

		$selected = cmsFramework::route($new_route . '/' . $key . _PARAM_CHAR . $selected);

		return $this->Form->select($key,$selectList,$selected,$attributes);
	}

	/**
	 * Renders Like/Dislike and Reporting actions
	 */
	function mediaActions($media)
	{
		if(isset($media['Media'])) {
			extract($media['Media']);
		}
		else {
			extract($media);
		}
		?>
		<div class="jr-media-actions jrMediaActions" data-listing-id="<?php echo $listing_id;?>" data-review-id="<?php echo $review_id;?>" data-media-id="<?php echo s2alphaID($media_id,false,5,cmsFramework::getConfig('secret'));?>" data-extension="<?php echo $extension;?>">

			<?php if($this->Access->canVoteMedia('video')):?>
			<span class="jr-media-like-dislike jrMediaLikeDislike jrButtonGroup">

				<button  class="jr-media-like jrVoteYes jrButton jrSmall" title="<?php __t("I like this",false,true);?>" data-like-action="_like">

					<span class="jrIconThumbUp"></span><span class="jr-count jrButtonText"><?php echo $likes_up; ?></span>

				</button>

				<button class="jr-media-dislike jrVoteNo jrButton jrSmall" title="<?php __t("I dislike this",false,true);?>" data-like-action="_dislike">

					<span class="jrIconThumbDown"></span><span class="jr-count jrButtonText"><?php echo $likes_total - $likes_up; ?></span>

				</button>

			</span>
			<?php endif;?>

		    <button class="jr-report jrReport jrButton jrSmall"  data-listing-id="<?php echo $listing_id;?>" data-review-id="<?php echo $review_id;?>" data-media-id="<?php echo s2alphaID($media_id,false,5,cmsFramework::getConfig('secret'));?>" data-extension="<?php echo $extension;?>">
				<span class="jrIconWarning"></span><?php __t("Report as inappropriate");?>
		    </button>

		 </div>
		 <?php
	}


	/**** FUNCTIONS TO DEAL WITH IMAGES EMBEDDED IN SUMMARY - LEGACY JREVIEWS AND JOOMLA ARTICLE SUPPORT ****/
    function grabImgFromText($text)
    {
        /** Four scenarios for embedded images
        * 1) Located within current site
        * 2) Located in site within a folder of current site
        * 3) Located in a different domain with the same image path structure
        * 4) Located in a different domain with different path structure
        */
        $doc = new DOMDocument();
        @$doc->loadHTML($text);
        $imageTags = $doc->getElementsByTagName('img');
        if($imageTags->length > 0)
        {
            $src = ltrim($imageTags->item(0)->getAttribute('src'),'/');
			return $src;
        }

        return false;
    }

    function embedThumb(&$listing, $options = array(), $attributes = array())
    {
    	if (!class_exists('MediaStorageComponent')) return false;

		$quality = Sanitize::getInt($this->Config,'media_general_thumbnail_quality',85);

		$summary = Sanitize::getString($listing['Listing'],'summary');

        if($summary != ''&& $src = $this->grabImgFromText($summary))
		{
			$listing['Listing']['summary'] = Sanitize::stripImages($summary);

		} else {
			return false;
		}

		if(is_numeric($options['size'])) {
		   $options['size'] .= 'x' . $options['size'];
		}

		$size = explode('x',low($options['size']));

		$SizeMode = $options['size'] . $options['mode']{0};

		// Get storage related config settings
		$store_local_path = Sanitize::getString($this->Config,'media_store_local_path');

		$store_thumbnail_folder = Sanitize::getString($this->Config,'media_store_local_thumbnail_folder');

		$store_photos_folder = Sanitize::getString($this->Config,'media_store_local_photo');

		$tn_basepath = PATH_ROOT . $store_local_path . $store_photos_folder . DS . $store_thumbnail_folder . DS . $SizeMode . DS;

        $tn_baseurl = WWW_ROOT . str_replace(DS, _DS, $store_local_path . $store_photos_folder . _DS . $store_thumbnail_folder) . _DS . $SizeMode . _DS;

		$is_absolute_url = substr($src,0,4) == 'http';

		$is_local_image = !$is_absolute_url || strstr($src,WWW_ROOT);

		if(!$is_local_image) {

			return $src;
		}

		if($is_absolute_url) {
			$src = str_replace(WWW_ROOT, '', $src);
		}

		$pathinfo = pathinfo($src);

		extract($pathinfo);

		$orig_path = PATH_ROOT . $src;

		$folder_hash = MediaStorageComponent::getFolderHash($filename);

		// $name = MediaStorageComponent::cleanFileName($filename, 'photo', $listing);

		// Need to use the same filename for thumbnails without any time or random modifiers.
		// Otherwise new thumbnails are generated on every page load
		$name = $filename;

		$tn_path = $tn_basepath . $folder_hash . $name . '.' . $extension;

		$tn_url = $tn_baseurl . str_replace(DS,_DS,$folder_hash) . $name . '.' . $extension;

		if(!file_exists($orig_path)) return false;

		$orig_size = getimagesize($orig_path);

		$thumbnail_exists = file_exists($tn_path);

		// If thumbnail doesn' exist, check orig dimensions vs. thumbnail dimensions to verify if thumbnailing is necessary
		if(!$thumbnail_exists && $orig_size[0] <= $size[0] && $orig_size[1] <= $size[1])
		{
			return WWW_ROOT . $src;
		}
		elseif($thumbnail_exists)
		{
			return $tn_url;
		}

		// Create new folder
		$Folder = new S2Folder($tn_basepath . $folder_hash, true, 0755);

		unset($Folder);

		if(!class_exists('PhpThumbFactory')) {
			S2App::import('Vendor', 'phpthumb' . DS . 'ThumbLib.inc');
		}

		ob_start();

		$Thumb = PhpThumbFactory::create($orig_path, array(
            'jpegQuality'=>$quality,
            'resizeUp'=>false
            ));

		if($options['mode'] == 'crop') {
			$Thumb->adaptiveResize($size[0],$size[1])->save($tn_path);
		}
		else {
			$Thumb->resize($size[0],$size[1])->save($tn_path);
		}

		ob_end_clean();

		if($Thumb->getHasError()) {
			appLogMessage($Thumb->getErrorMessage(), 'thumbnailer');
			return false;
		}

		$new_size = $Thumb->getCurrentDimensions();

		return $tn_url;
    }

	function checkUploadLimit($max, $count)
	{
		if($max == '0'){
			return '';

		}

		if($max == '') {
			return __t("no upload limit");
		}

		return $count >= $max ?

			sprintf(__t("upload limit reached (%s)",true),$max)

			:

			sprintf(__t("%1\$s remaining out of %2\$s",true),(int)$max - (int)$count, $max);
	}

	function getMediaKey($media_id)
	{
		return s2alphaID($media_id,false,5,cmsFramework::getConfig('secret'));
	}


	function formatFileSize($bytes)
	{

		if ($bytes > 0) {
			$unit = intval(log($bytes, 1024));
			$units = array('B', 'KB', 'MB', 'GB');

			if (array_key_exists($unit, $units) === true)
			{
				return sprintf('%d %s', $bytes / pow(1024, $unit), $units[$unit]);
			}
		}

		return $bytes;

	}

}