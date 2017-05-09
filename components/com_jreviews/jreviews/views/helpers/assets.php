<?php
/**
 * JReviews - Reviews Extension
 * Copyright (C) 2010-2015 ClickFWD LLC
 * This is not free software, do not distribute it.
 * For licencing information visit https://www.jreviews.com
 * or contact sales@jreviews.com
**/

/**
* All required css/js assets are conviniently defined here per controller and controller action (per page)
*/
defined( 'MVC_FRAMEWORK') or die( 'Direct Access to this location is not allowed.' );

class AssetsHelper extends MyHelper
{
    var $helpers = array('html','libraries','custom_fields','editor');

    var $assetParams = array();

    var $useJavascriptLoader = false;

    var $useMinifiedScripts = true;

    var $useMinifiedStyleSheets = true;

    /**
    * These arrays can be set at the controller level
    * and in plugin callbacks with any extra css or js files that should be loaded
    *
    * @var mixed
    */
    var $assets = array('js'=>array(),'css'=>array(),'absurl'=>array());

    function load()
    {
        $assetParams = func_get_args();

        $this->assetParams = array_merge($this->assetParams,$assetParams);

        $methodAction = Inflector::camelize($this->name.'_'.$this->action);

        $methodName = Inflector::camelize($this->name);

        $assets = array('js'=>array(),'css'=>array());

        if(method_exists($this,$methodAction))
        {
            $this->{$methodAction}($assets);

        }
        elseif(method_exists($this,$methodName)) {

            $this->{$methodName}($assets);
        }

        $this->send($assets);
    }

    function send($assets)
    {
        // In Joomla 3, make sure jQuery loads and it loads before JReviews.

        if(_CMS_NAME == 'joomla' && cmsFramework::getVersion() >= 3)
        {
            JHtml::_('jquery.framework');

            $this->Config->libraries_jquery = true;
        }

        $this->Html->app = $this->app;

        unset($this->viewVars);

        $this->useJavascriptLoader = Sanitize::getInt($this->Config,'libraries_scripts_loader',0);

        $this->useMinifiedScripts = Sanitize::getInt($this->Config,'libraries_scripts_minified',1);

        $this->useMinifiedStyleSheets = Sanitize::getInt($this->Config,'libraries_css_minified',1);

        // Incorporate assets injected in controllers

        if(!empty($this->assets['js'])) {

            $assets['js'] = array_merge($assets['js'],$this->assets['js']);
        }

        if(!empty($this->assets['css'])) {

            $assets['css'] = array_merge($assets['css'],$this->assets['css']);
        }

        $assets['js'] = array_unique($assets['js']);

        $version = explode('.',$this->Config->version);

        $version = array_sum($version);

        /***********************************************************
         *                      LOAD CSS                           *
        /***********************************************************/

        // Add JReviews CSS which needs to load on every page

        $assets['css'][] = 'jr-theme';

        if(!Sanitize::getBool($this->Config,'libraries_jqueryui'))
        {
            array_unshift($assets['css'],'jq-ui'); // Load Jquery UI unless disabled in the configuration
        }

        cmsFramework::isRTL() and $assets['css'][] = 'rtl';

        $assets['css'] = array_unique($assets['css']);

        $custom_styles_version = Sanitize::getString($this->Config,'custom_styles_version');

        $this->Html->css(arrayFilter($assets['css'], $this->Libraries->css()),array('version'=>$version,'minified'=>$this->useMinifiedStyleSheets));

        // Always try loading non-suffixed custom_styles

        $this->Html->css('custom_styles', array('version'=>$custom_styles_version != '' ? $custom_styles_version : $version));

        // Load suffixed custom styles if present

        if($this->viewSuffix != '')
        {
            $this->Html->css('custom_styles' . $this->viewSuffix, array('version'=>$custom_styles_version != '' ? $custom_styles_version : $version));
        }

        /***********************************************************
         *                      LOAD JS                            *
        /***********************************************************/

        // Load locale language object
        $locale_js = pathToUrl(S2_CACHE, true) . 'core/locale-'.cmsFramework::getLocale().'.js';

        // Always load jreviews.js

        array_unshift($assets['js'],'jr-jreviews');

        // Load jQuery UI unless it's disabled. Also load if it's forced via presense in the $assets array
        if(!Sanitize::getBool($this->Config,'libraries_jqueryui') || in_array('jq-ui',$assets['js'])) {

            array_unshift($assets['js'],'jquery/i18n/jquery.ui.datepicker-' . cmsFramework::locale());

            // Remove from current position.
            $index = array_search('jq-ui',$assets['js']);

            if($index) unset($assets['js'][$index]);

            array_unshift($assets['js'],'jq-ui'); // Load Jquery UI unless disabled in the configuration
        }

        // Load jQuery unless it's disabled. Also load if it's forced via presense in the $assets array
        if(!Sanitize::getBool($this->Config,'libraries_jquery') || in_array('jquery',$assets['js']))
        {
            // Remove from current position
            $index = array_search('jquery',$assets['js']);

            if($index) unset($assets['js'][$index]);

            array_unshift($assets['js'],'jquery');
        }

        $jsPaths = array();

        $jsPathsDependent = array();

        if(in_array('jquery',$assets['js'])) {

            $jsDependencies = array(
                'jq-treeview'=>'jquery',
                'jq-scrollable'=>'jquery',
                'geomaps'=>'jr-jreviews',
                'jr-compare'=>'jr-jreviews',
                'jr-fields'=>'jr-jreviews'
            );
        }
        else {

            $jsDependencies = array(
                'geomaps'=>'jr-jreviews',
                'jr-compare'=>'jr-jreviews',
                'jr-fields'=>'jr-jreviews'
            );
        }

        $jsFiles = arrayFilter($assets['js'], $this->Libraries->js());

        $absUrls = Sanitize::getVar($this->assets,'absurl',array());

        $loadedScripts = array();

        if($this->useJavascriptLoader)
        {
            foreach($jsFiles AS $key=>$jsfile)
            {
                if(cmsFramework::scriptLoaded($jsfile)) continue;

                $relative = in_array($jsfile,$absUrls) ? false : true;

                $js_path = $this->locateScript($jsfile,array('admin'=>false,'relative'=>$relative,'minified'=>$this->useMinifiedScripts));

                if($js_path)
                {
                    if(!isset($jsDependencies[$key])) {

                        $loadedScripts[] = $jsfile;

                        $jsPaths[$key] = stripslashes(json_encode(array($key=>$js_path . '?v=' . $version)));
                    }
                    else {

                        $jsPathsDependent[$jsDependencies[$key]][$jsfile] = stripslashes(json_encode(array($key=>$js_path . '?v=' . $version)));
                    }
                }
            }

            if(!cmsFramework::scriptLoaded('locale'))
            {
                $loadedScripts[] = 'locale';

                array_unshift($jsPaths, stripslashes(json_encode(array('locale'=>$locale_js))));
            }

            # Head JS - overwrite the js array so only head.js is loaded

            $jsFiles = array('head.load.min'); // Load script for async loading
        }
        else {

            $assets['js'] = array_unique($assets['js']);

            cmsFramework::addScript($locale_js, 'locale', $version);
        }

        $this->Html->js($jsFiles,array('version'=>$version,'minified'=>$this->useMinifiedScripts,'absUrls'=>$absUrls));

        /**
        * Send cachable scripts to the head tag from controllers and components by adding it to the head array
        */

        if(!empty($this->assets['head-top']))
        {
            $this->assets['head-top'] = array_filter($this->assets['head-top']);

            // Reverse order so JReviews head scripts load before widgets/modules

            $this->assets['head-top'] = array_reverse($this->assets['head-top']);

            foreach($this->assets['head-top'] AS $key=>$head)
            {
                cmsFramework::addScriptTag($head, $key);
            }
        }

        /**
         * Send scripts to head using Javascript Loader
         */

        if(!empty($jsPaths))
        {
            $jsPaths = implode(",",$jsPaths);

            cmsFramework::addScriptTag('head.js('.$jsPaths.');', $loadedScripts);
        }

        if(!empty($jsPathsDependent))
        {
            foreach($jsPathsDependent AS $parent=>$paths)
            {
                $loadedScriptsDependents = array_keys($paths);

                $paths = implode(",",$paths);

                cmsFramework::addScriptTag('head.ready("'.$parent.'",function() {head.js('.$paths.');});', $loadedScriptsDependents);
            }
        }

        /**
        * Send cachable scripts to the head tag from controllers and components by adding it to the head array
        */

        $inline = _CMS_NAME == 'wordpress' && strstr($this->name, 'module_');

        if(!empty($this->assets['head-bottom']))
        {
            $this->assets['head-bottom'] = array_filter($this->assets['head-bottom']);

            foreach($this->assets['head-bottom'] AS $key=>$head)
            {
                cmsFramework::addScriptTag($head, $key, $inline);
            }
        }
    }

/**********************************************************************************
 *  Categories Controller
 **********************************************************************************/
     function Categories(& $assets)
     {
        $assets['js'][] = 'jq-scrollable';
        $assets['js'][] = 'jr-compare';

        $masonryLayout = false;

        $ajaxPagination = Sanitize::getInt($this->Config, 'paginator_ajax');

        $predefinedLayouts = Sanitize::getVar($this->Config, 'list_predefined_layout');

        foreach ($predefinedLayouts AS $view)
        {
            if ($view['layout'] == 'masonry')
            {
                $masonryLayout = true;
                break;
            }
        }

        if(Sanitize::getString($this,'listview') == 'masonry' || ($masonryLayout && $ajaxPagination))
        {
            $assets['js'][] = 'jq-masonry';
        }
     }

/**********************************************************************************
 *  ComContent Controller
 **********************************************************************************/
    function ComContentComContentView(& $assets)
    {
        $assets['js'][] = 'jq-lightbox';
        $assets['js'][] = 'jq-scrollable';
        $assets['js'][] = 'jr-compare';
        $assets['css'][] = 'jq-lightbox';

        if($this->Access->canAddReview() || $this->Access->isEditor())
        {
            if($this->Config->rating_selector == 'stars')
            {
                $assets['js'][] = 'jq-rating';
            }

            $assets['js'][] = 'jr-fields';

            if($this->Config->review_comment_wysiwyg) {
                $this->loadTrixAssets($assets);
            }

            $this->loadFormBuilderAssets($assets, 'review');
        }

        $listing = Sanitize::getVar($this->viewVars,'listing');

        if(!empty($listing) && $listing['Listing']['media_count'] > 0)
        {
    		if($listing['Listing']['photo_count'] > 0
                && in_array($this->Config->media_detail_photo_layout, array('gallery_large','gallery_small')))
    		{
                $assets['js'][] = 'jr-media';
    			$assets['js'][] = 'jq-galleria';
    			$assets['js'][] = 'jq-galleria-classic';
    			$assets['css'][] = 'jq-galleria';
    		}

    		if($listing['Listing']['video_count'] > 0 && $this->Config->media_detail_video_layout == 'video_player')
    		{
                $assets['js'][] = 'jr-media';
    			$assets['js'][] = 'jq-video';
    			$assets['css'][] = 'jq-video';
    		}

            if($listing['Listing']['audio_count'])
            {
                $assets['js'][] = 'jr-media';
                $assets['js'][] = 'jq-audio';
                $assets['js'][] = 'jq-audio.playlist';
            }

            if($listing['Listing']['attachment_count'])
            {
                $assets['js'][] = 'jr-media';
            }
        }
    }

    function ComContentComContentBlog(& $assets){}

/**********************************************************************************
 *  Community Listings Plugin Controller
 **********************************************************************************/
     function CommunityListings(& $assets)
     {
        $total = Sanitize::getInt($this->viewVars,'total',5);

        $limit = Sanitize::getInt($this->viewVars,'limit',5);

        $page_count = ceil($total/$limit);

        if($page_count > 1)
        {
            $assets['js'][] = 'jq-scrollable';
        }

        if(Sanitize::getInt($this->viewVars,'compare'))
        {
            $assets['js'][] = 'jr-compare';
        }
     }

/**********************************************************************************
 *  Community Reviews Plugin   Controller
 **********************************************************************************/
     function CommunityReviews(& $assets)
     {
        $total = Sanitize::getInt($this->viewVars,'total',5);

        $limit = Sanitize::getInt($this->viewVars,'limit',5);

        $page_count = ceil($total/$limit);

        if($page_count > 1)
        {
            $assets['js'][] = 'jq-scrollable';
        }
     }

/**********************************************************************************
 *  Directories Controller
 **********************************************************************************/
     function DirectoriesDirectory(& $assets){}

/**********************************************************************************
 *  Discussions Controller
 **********************************************************************************/
     function Discussions(& $assets)
     {
        $assets['js'][] = 'jq-lightbox';

        $assets['css'][] = 'jq-lightbox';

        if($this->action == 'review')
        {
            $listing = $this->viewVars['listing'];

            if(Sanitize::getInt($listing['Listing'],'media_count_user') > 0)
            {
                if($listing['Listing']['audio_count_user'] + $listing['Listing']['attachment_count_user'] > 0) {

                    $assets['js'][] = 'jr-media';
                }

                if($listing['Listing']['audio_count_user'])
                {
                    $assets['js'][] = 'jq-audio';
                    $assets['js'][] = 'jq-audio.playlist';
                }
            }
        }

        if($this->Config->discussion_wysiwyg)
        {
            $this->loadTrixAssets($assets);
        }
    }

/**********************************************************************************
 *  Everywhere Controller
 **********************************************************************************/
    function EverywhereIndex(& $assets)
    {
        $assets['js'][] = 'jq-lightbox';
        $assets['css'][] = 'jq-lightbox';

        if($this->Access->canAddReview() || $this->Access->isEditor())
        {
            if($this->Config->rating_selector == 'stars')
            {
                $assets['js'][] = 'jq-rating';
            }

            $assets['js'][] = 'jr-fields';

            $this->loadTrixAssets($assets);
        }
    }

    function EverywhereCategory(& $assets){}

/**********************************************************************************
 *  Media Controller
 **********************************************************************************/
	function MediaUploadCreate(& $assets)
	{
        $assets['js'][] = 'jq-uploader';
        $assets['js'][] = 'jr-media';
	}

	function MediaPhotoGallery(& $assets)
    {
        $assets['js'][] = 'jq-galleria';
        $assets['js'][] = 'jq-galleria-classic';
        $assets['js'][] = 'jr-media';
        $assets['css'][] = 'jq-galleria';
	}

    function MediaVideoGallery(& $assets)
    {
        $assets['js'][] = 'jq-video';
        $assets['js'][] = 'jq-scrollable';
        $assets['js'][] = 'jr-media';
        $assets['css'][] = 'jq-video';
    }

	function MediaListing(& $assets)
	{
        $assets['js'][] = 'jq-galleria';
        $assets['js'][] = 'jq-galleria-classic';
        $assets['js'][] = 'jq-video';
        $assets['js'][] = 'jq-audio';
        $assets['js'][] = 'jq-audio.playlist';
        $assets['js'][] = 'jq-masonry';
        $assets['js'][] = 'jr-media';
        $assets['css'][] = 'jq-galleria';
        $assets['css'][] = 'jq-video';
	}

	function MediaMyMedia(& $assets)
	{
		$this->MediaListing($assets);
	}

	function MediaMediaList(& $assets)
	{
        $media_types = array('photo','video','attachment','audio');

        $canEdit = false;

        foreach($media_types AS $media_type)
        {
            if($this->Access->canEditMedia($media_type))
            {
                $canEdit = true;
                break;
            }
        }

        if($canEdit)
        {
            $assets['js'][] = 'jq-galleria';
            $assets['js'][] = 'jq-galleria-classic';
            $assets['js'][] = 'jq-video';
            $assets['js'][] = 'jq-audio';
            $assets['js'][] = 'jq-audio.playlist';
            $assets['js'][] = 'jq-masonry';
            $assets['js'][] = 'jr-media';
            $assets['css'][] = 'jq-galleria';
            $assets['css'][] = 'jq-video';
        }
        else {
            $assets['js'][] = 'jr-media';
        }

        $assets['js'][] = 'jq-masonry';
	}

	function MediaAttachments(& $assets){}

/**********************************************************************************
 *  Listings Controller
 **********************************************************************************/
    function ListingsCreate(& $assets)
    {
        $assets['js'][] = 'jq-rating';
        $assets['js'][] = 'jq-uploader';
        $assets['js'][] = 'jr-fields';
        $assets['js'][] = 'jr-media';

        $this->loadTrixAssets($assets);

        $this->loadFormBuilderAssets($assets, 'listing');

        # Transforms class="jr-wysiwyg-editor" textareas

        if($this->Access->loadWysiwygEditor() && !$this->Config->listing_wysiwyg)
        {
            $this->Editor->load();
        }
    }

    function ListingsEdit(& $assets)
    {
        $this->ListingsCreate($assets);
    }

    function ListingsDetail(& $assets)
    {
        $assets['js'][] = 'jq-lightbox';
        $assets['js'][] = 'jr-compare';
        $assets['css'][] = 'jq-lightbox';

        if($this->Access->canAddReview() || $this->Access->isEditor())
        {
            if($this->Config->rating_selector == 'stars')
            {
                $assets['js'][] = 'jq-rating';
            }

            $assets['js'][] = 'jr-fields';

            if($this->Config->review_comment_wysiwyg) {
                $this->loadTrixAssets($assets);
            }

            $this->loadFormBuilderAssets($assets, 'review');
        }
    }

/**********************************************************************************
 *  Module Filters Controller
 **********************************************************************************/

    function ModuleFilters(& $assets)
    {
        $assets['js'][] = 'jr-filters';
        $assets['js'][] = 'jr-fields';
    }

/**********************************************************************************
 *  Module Advanced Search Controller
 **********************************************************************************/
    function ModuleAdvancedSearch(& $assets)
    {
        $assets['js'][] = 'jr-fields';
    }

/**********************************************************************************
 *  Module Directories Controller
 **********************************************************************************/
    function ModuleDirectories(& $assets)
    {
        $assets['js'][] = 'jq-treeview';
        $assets['css'][] = 'jq-treeview';
    }

/**********************************************************************************
 *  Module Favorite Users Controller
 **********************************************************************************/
    function ModuleFavoriteUsers(& $assets)
    {
        $total = Sanitize::getInt($this->viewVars,'total',5);

        $limit = Sanitize::getInt($this->viewVars,'limit',5);

        $page_count = ceil($total/$limit);

        if($page_count > 1)
        {
            $assets['js'][] = 'jq-scrollable';
        }
    }

/**********************************************************************************
 *  Module Fields Controller
 **********************************************************************************/
    function ModuleFields(& $assets){}

/**********************************************************************************
 *  Module Range Controller
 **********************************************************************************/
    function ModuleRange(& $assets){}

/**********************************************************************************
 *  Module Listings Controller
 **********************************************************************************/
    function ModuleListings(& $assets)
    {
        $total = Sanitize::getInt($this->viewVars,'total',5);

        $limit = Sanitize::getInt($this->viewVars,'limit',5);

        $page_count = $limit > 0 ? ceil($total/$limit) : 0;

        if($page_count > 1)
        {
            $assets['js'][] = 'jq-scrollable';
        }

        if(Sanitize::getInt($this->viewVars,'compare'))
        {
            $assets['js'] = array('jq-scrollable','jr-compare');
        }
    }

/**********************************************************************************
 *  Module Reviews Controller
 **********************************************************************************/
    function ModuleReviews(& $assets)
    {
        $total = Sanitize::getInt($this->viewVars,'total',5);

        $limit = Sanitize::getInt($this->viewVars,'limit',5);

        $page_count = ceil($total/$limit);

       if($page_count > 1)
        {
            $assets['js'][] = 'jq-scrollable';
        }
    }

/**********************************************************************************
 *  Module Media Controller
 **********************************************************************************/
    function ModuleMedia(& $assets)
    {
        $total = Sanitize::getInt($this->viewVars,'total',5);

        $limit = Sanitize::getInt($this->viewVars,'limit',5);

        $page_count = ceil($total/$limit);

       if($page_count > 1)
        {
            $assets['js'][] = 'jq-scrollable';
        }
    }

/**********************************************************************************
 *  Module Calendar Controller
 **********************************************************************************/
    function ModuleCalendar(& $assets)
    {
        $assets['js'][] = 'moment';
        $assets['js'][] = 'moment/i18n/' . strtolower(cmsFramework::locale()) . '.min';
        $assets['js'][] = 'hogan';
        $assets['js'][] = 'jq-calendar';
    }

/**********************************************************************************
 *  Reviews Controller
 **********************************************************************************/
    function ReviewsCreate(& $assets){}

    function ReviewsLatest(& $assets)
    {
        if($this->Access->canAddReview() || $this->Access->isEditor())
        {
            if($this->Config->rating_selector == 'stars')
            {
                $assets['js'][] = 'jq-rating';
            }

            $assets['js'][] = 'jr-fields';

            if($this->Config->review_comment_wysiwyg) {
                $this->loadTrixAssets($assets);
            }

            $this->loadFormBuilderAssets($assets, 'review');
        }
    }

    function ReviewsMyReviews(& $assets)
    {
        if($this->Access->canAddReview() || $this->Access->isEditor())
        {
            if($this->Config->rating_selector == 'stars')
            {
                $assets['js'][] = 'jq-rating';
            }

            $assets['js'][] = 'jr-fields';

            if($this->Config->review_comment_wysiwyg) {
                $this->loadTrixAssets($assets);
            }

            $this->loadFormBuilderAssets($assets, 'review');
        }
    }

    function ReviewsRankings(& $assets){}

/**********************************************************************************
 *  Search Controller
 **********************************************************************************/
    function SearchAdvanced(& $assets)
    {
        $assets['js'][] = 'jr-fields';
    }

/**********************************************************************************
 *  Helper Methods
 **********************************************************************************/

    /**
     * Loads required libraries for the FormBuilder custom field
     */
    protected function loadFormBuilderAssets(& $assets, $referrer = null)
    {
        if ($referrer == 'listing')
        {
            $assets['js'][] = 'json-editor/jsoneditor';
            $assets['js'][] = 'json-editor/templates/template-jreviews';
            $assets['js'][] = 'json-editor/iconlibs/iconlib-jreviews';
        }
    }

    protected function loadTrixAssets(& $assets)
    {
        $assets['js'][] = 'trix';
        $assets['css'][] = 'trix';
    }
}