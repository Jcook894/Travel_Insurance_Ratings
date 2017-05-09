<?php
/**
 * JReviews - Reviews Extension
 * Copyright (C) 2010-2015 ClickFWD LLC
 * This is not free software, do not distribute it.
 * For licencing information visit https://www.jreviews.com
 * or contact sales@jreviews.com
**/

defined( 'MVC_FRAMEWORK') or die( 'Direct Access to this location is not allowed.' );

class LibrariesHelper extends MyHelper
{
	function js()
	{
		$javascriptLibs = array();
		$javascriptLibs['jquery']               =   'jquery/jquery-1.11.1.min';
		$javascriptLibs['jq-ui']          		=   'jquery/jquery-ui-1.9.2.custom.min';
		$javascriptLibs['jq-json']			    = 	'jquery/json.min';
		$javascriptLibs['jq-jsoncookie']		= 	'jquery/jquery.extjasoncookie-0.2.min';
		$javascriptLibs['jq-rating']			= 	'jquery/ui.stars.min';
		$javascriptLibs['jq-scrollable']        =   'bxslider-4/jquery.bxslider.min';
		$javascriptLibs['jq-lightbox']          =   'jquery/jquery.magnific-popup.min';
		$javascriptLibs['jq-treeview']			= 	'jquery/jquery.treeview.min';
		$javascriptLibs['jq-multiselect']		= 	'jquery/jquery.multiselect.min';
		$javascriptLibs['jq-masonry']			= 	'jquery/jquery.masonry.min';
		$javascriptLibs['jq-calendar']			= 	'jquery/jquery.clndr.min';
		$javascriptLibs['jq-uploader'] 			= 	'fileuploader/fileuploader';
		$javascriptLibs['jq-galleria'] 			= 	'galleria/galleria-1.3.5.min';
		$javascriptLibs['jq-galleria-classic'] 	= 	'galleria/galleria.classic.min';
		$javascriptLibs['jq-video']				= 	'video-js/video.min';
		$javascriptLibs['jq-audio']				= 	'jplayer/jquery.jplayer.min';
		$javascriptLibs['jq-audio.playlist']	= 	'jplayer/jplayer.playlist.min';
		$javascriptLibs['moment']				= 	'moment/moment-2.10.6.min.js';
		$javascriptLibs['hogan']				= 	'hogan/hogan-3.0.2.min.js';
		$javascriptLibs['trix'] 				=   'trix/trix.min.js';

		if(!isset($this->Config) || empty($this->Config))
		{
			$this->Config = Configure::read('JreviewsSystem.Config');
		}

		if(Sanitize::getBool($this->Config,'libraries_scripts_minified') && !defined('MVC_FRAMEWORK_ADMIN'))
		{
			$javascriptLibs['jr-jreviews'] 		=	'jreviews-all.min';
		}
		else {
			$javascriptLibs['jr-jreviews'] 		=	'jreviews';
			$javascriptLibs['jr-media'] 		= 	'jreviews.media';
			$javascriptLibs['jr-filters'] 		= 	'jreviews.filters';
			$javascriptLibs['jr-fields'] 		= 	'jreviews.fields';
			$javascriptLibs['jr-compare'] 		= 	'jreviews.compare';
		}


		return $javascriptLibs;
	}

	function css()
	{
		$styleSheets = array();
		$styleSheets['jq-ui']              		=   'jquery_ui_theme/jquery-ui-1.9.2.custom';
		$styleSheets['jq-lightbox']             =   'magnific/magnific-popup';
		$styleSheets['jq-treeview'] 			= 	'treeview/jquery.treeview';
		$styleSheets['jq-galleria'] 			= 	'galleria/galleria.classic';
		$styleSheets['jq-video']				= 	'video-js/video-js.min';
		$styleSheets['jr-theme']				= 	'theme';
		$styleSheets['trix']					= 	'trix/trix';

		if(!isset($this->Config) || empty($this->Config))
		{
			$this->Config = Configure::read('JreviewsSystem.Config');
		}

		if(Sanitize::getBool($this->Config,'libraries_jqueryui') && !defined('MVC_FRAMEWORK_ADMIN'))
		{
			unset($styleSheets['jq-ui']);
		}

		return $styleSheets;
	}
}