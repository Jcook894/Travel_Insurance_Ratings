<?php
class AdminAssetsHelper extends MyHelper
{
    var $helpers = array('html','libraries','custom_fields','editor');

    var $assetParams = array();

    var $useJavascriptLoader = true;

    var $useMinifiedScripts = true;

    var $useMinifiedStyleSheets = true;

    /**
    * These arrays can be set at the controller level
    * and in plugin callbacks with any extra css or js files that should be loaded
    *
    * @var mixed
    */
    var $assets = array(
        'js'=>array(
            'jquery'            =>'jquery',
            'jq-ui'             =>'jq-ui',
            'jq-multiselect',
            'jq-rating',
            'jq-video',
            'jq-audio',
            'jq-audio.playlist',
            'jq-uploader',
            'admin/jreviews-all.min',
            // 'admin/admin',
            // 'admin/addon_everywhere',
            'jr-media',
            'jr-fields',
            'trix',
            'jquery/select2',
            'ace/ace.js',
            'json-editor/jsoneditor',
            'json-editor/templates/template-jreviews',
            'json-editor/iconlibs/iconlib-jreviews'
            ),
        'css'=>array(
            'admin/custom-theme/jquery-ui-1.9.2.custom',
            'jq-video',
            'admin/theme',
            'trix',
            'admin/select2'
            ),
        'absurl'=>array()
        );

    function load()
    {
        if($this->name == 'admin_menu') {

            return;
        }

    	if(_CMS_NAME == 'wordpress')
    	{
            unset($this->assets['js']['jq-ui']);

    		$this->useJavascriptLoader = false;
    	}

        $assetParams = func_get_args();

        $this->assetParams = array_merge($this->assetParams,$assetParams);

        $methodAction = Inflector::camelize($this->name.'_'.$this->action);

        $methodName = Inflector::camelize($this->name);

		if(method_exists($this,$methodAction)){

            $this->{$methodAction}();
        }
        elseif(method_exists($this,$methodName)) {

            $this->{$methodName}();
        }
        elseif(!empty($this->assets)) {

            $this->send($this->assets);
        }
    }

    function send($assets)
    {
        # Load javascript libraries
        $this->Html->app = $this->app;

        unset($this->viewVars);

        if(!isset($assets['js'])) {

            $assets['js'] = array();
        }

        if(!isset($assets['css'])) {

            $assets['css'] = array();
        }

        // Incorporate controller set assets before sending
        if(!empty($this->assets['js'])) {

            $assets['js'] = array_merge($this->assets['js'],$assets['js']);
        }

        if(!empty($this->assets['css'])) {

            $assets['css'] = array_merge($assets['css'],$this->assets['css']);
       }

        if(isset($this->Config) && Sanitize::getString($this->Config,'version'))
        {
            $version = explode('.',$this->Config->version);

            $version = array_sum($version);
        }
        else {

            $version = 1;
        }

        /***********************************************************
         *                      LOAD CSS                           *
        /***********************************************************/

        $assets['css'] = array_unique($assets['css']);

        $this->Html->css(arrayFilter($assets['css'], $this->Libraries->css()), array('version'=>$version,'minified'=>$this->useMinifiedStyleSheets));

        /***********************************************************
         *                      LOAD JS                            *
        /***********************************************************/

        // Load locale language object

        $locale_js = class_exists('JreviewsLocale') ? pathToUrl(S2_CACHE, true) . 'core/admin-locale-'.cmsFramework::getLocale().'.js?v=' . $version : '';

         // Check is done against constants defined in those applications
        $assets['js'][] = 'jquery/i18n/jquery.ui.datepicker-' . cmsFramework::locale();

        if((_CMS_NAME == 'joomla' && cmsFramework::getVersion() >= 3)
		      ||
		      _CMS_NAME == 'wordpress')
        {
            unset($assets['js']['jquery']);

            if(_CMS_NAME == 'wordpress')
            {
                // unset($assets['js']['jq.ui']);
            }
        }

        $assets['js'] = array_unique($assets['js']);

        $jsPaths = array();

        $loadedScripts = array();

        if($this->useJavascriptLoader) {

            $jsFiles = arrayFilter($assets['js'], $this->Libraries->js());

            $absUrls = Sanitize::getVar($this->assets,'absurl',array());

            foreach($jsFiles AS $jsfile)
            {
                if(cmsFramework::scriptLoaded($jsfile)) continue;

                $admin_file = substr($jsfile, 0,6) == 'admin/';

                $new_jsfile = $admin_file ? str_replace('admin/','',$jsfile) : $jsfile;

                $relative = in_array($new_jsfile,$absUrls) ? false : true;

                $js_path = $this->locateScript($new_jsfile,array('admin'=>$admin_file,'relative'=>$relative,'minified'=>$this->useMinifiedScripts));

                if($js_path)
                {
                    $loadedScripts[] = $jsfile;

                    $jsPaths[] = $js_path . '?v=' . $version;
                }
            }

            if(!cmsFramework::scriptLoaded('locale'))
            {
                $loadedScripts[] = 'locale';

                array_unshift($jsPaths, $locale_js);
            }

            $assets['js'] = array('head.load.min'); // Load script for async loading
        }
        else {

            if(!$this->useJavascriptLoader)
	    {
		    array_unshift($assets['js'], 'head.load.min');
	    }

            $assets['js'] = array_unique($assets['js']);

            cmsFramework::addScript($locale_js, 'admin-locale');
        }

        $this->Html->js(arrayFilter($assets['js'], $this->Libraries->js()),array('version'=>$version,'minified'=>$this->useMinifiedScripts));

        /**
        * Send cachable scripts to the head tag from controllers and components by adding it to the head array
        */
        if(!empty($this->assets['head-top']))
        {
            foreach($this->assets['head-top'] AS $key=>$head)
            {
                cmsFramework::addScriptTag($head, $key);
            }
        }

        // Send scripts to head using Javascript Loader
        if(!empty($jsPaths))
        {
            $jsPaths = "'".implode("','",$jsPaths)."'";

            cmsFramework::addScriptTag('head.js('.$jsPaths.');', $loadedScripts);
        }

        /**
        * Send cachable scripts to the head tag from controllers and components by adding it to the head array
        */
        if(!empty($this->assets['head-bottom']))
        {
            foreach($this->assets['head-bottom'] AS $key=>$head)
            {
                cmsFramework::addScriptTag($head, $key);
            }
        }
    }
}