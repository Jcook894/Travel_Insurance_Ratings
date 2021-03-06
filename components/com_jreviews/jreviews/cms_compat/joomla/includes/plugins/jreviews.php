<?php
/**
 * JReviews - Reviews Extension
 * Copyright (C) 2010-2015 ClickFWD LLC
 * This is not free software, do not distribute it.
 * For licencing information visit https://www.jreviews.com
 * or contact sales@jreviews.com
**/

defined('_JEXEC') or die;

// Stops /cli/finder_indexer.php from running this file
if(isset($_SERVER['SCRIPT_NAME']) && $_SERVER['SCRIPT_NAME'] == 'finder_indexer.php') {

    return;
}

if(!function_exists('plgContentJreviews')) {

    $option = JRequest::getVar('option', '');

    $view = JRequest::getVar('view', '');

    $layout = JRequest::getVar('layout', '');

    $id = JRequest::getVar('id');

    if ($option != 'com_content' && $option  != 'com_frontpage' && $option != '') {
        return;
    }

    $database = JFactory::getDBO();

    $query = "
        SELECT
            enabled AS published, params
        FROM
            #__extensions
        WHERE
            element = 'jreviews' AND type = 'plugin' AND folder = 'content' LIMIT 1";

    $database->setQuery($query);

    $pluginSetup = current($database->loadObjectList());

    $params = json_decode($pluginSetup->params,true);

    if (!$pluginSetup->published) return;

    $frontpageOff = isset($params['frontpage']) && $params['frontpage'] == 1;

    $blogLayoutOff = isset($params['blog']) && $params['blog'] == 1;

    # Get theme, suffix and load CSS so it's not killed by the built-in cache

    if ($blogLayoutOff && $option=='com_content' && ($view == 'category') && ($layout == 'blog' || $layout == 'blogfull')) {
        return;
    }
    elseif (($frontpageOff && ($view == 'frontpage' || $view == 'featured'))) {
        return ;
    }

    require($root . 'components' . DS . 'com_jreviews' . DS . 'jreviews' . DS . 'framework.php');

    jimport('joomla.plugin.plugin');

    class plgContentJreviews extends JPlugin
    {
        static $pagetitle;

        function __construct(& $subject, $params )
        {
            if(!$this->checkJreviewsCategory($subject, $params)) return;

            parent::__construct( $subject, $params );
        }

        private function checkJreviewsCategory($subject, $params)
        {
            $option = JRequest::getVar('option', '');

            $view = JRequest::getVar('view', '');

            $layout = JRequest::getVar('layout', '');

            $id = (int) JRequest::getVar('id');

            $database = JFactory::getDBO();

            if($view == 'category') {

                $query = "SELECT count(*) FROM #__jreviews_categories WHERE id = " . $id;

                $database->setQuery($query);

                $count = $database->loadResult();

                return $count;
            }
            elseif ($view == 'article') {

                return $this->articleinJReviewsCategory($id);
            }
            elseif ($view == 'featured') {

                return true;
            }
        }

        private function articleinJReviewsCategory($id)
        {
            $database = JFactory::getDBO();

            $query = "SELECT catid FROM #__content WHERE id = " . $id;

            $database->setQuery($query);

            $catid = $database->loadResult();

            if($catid)
            {
                $query = "
                    SELECT
                        count(*)
                    FROM
                        #__jreviews_categories AS Category
                    WHERE
                        Category.option = 'com_content'
                        AND
                        Category.id = " . $catid;

                $database->setQuery($query);

                $count = $database->loadResult();

                return $count;
            }

            return false;
        }

        public function onBeforeRender()
        {
            if(self::$pagetitle)
            {
                $title = self::$pagetitle;

                $pagetitle = cmsFramework::applyPageTitleFormat($title);

                JFactory::getDocument()->title = $pagetitle;
            }
        }

        function onContentBeforeDisplay( $context, &$article, &$params) /*J16*/
        {
            /***********************************************************************
            * BELOW BLOCK HERE BECAUSE J16 DOESN'T MAKE THE WHOLE ARTICLE OBJECT
            * AVAILABLE IN THE ONCONTENTPREPARE CALLBACK IN BLOG LAYOUT PAGES
            ***********************************************************************/
            if (!class_exists('cmsFramework') || !class_exists('Sanitize')) return;

            // Check whether to perform the replacement or not
            $option = Sanitize::getString($_REQUEST, 'option', '');
            $view = Sanitize::getString($_REQUEST, 'view', '');
            $layout = Sanitize::getString($_REQUEST, 'layout', '');
            $id = Sanitize::getInt($_REQUEST,'id');

    		if($option == 'com_content' && $view == 'featured') {
    			if(!$this->articleinJReviewsCategory($article->id)) return;
    		}

            if($option == 'com_content' && ($layout == 'blog' || $view == 'featured'))
            {
                $row = &$article;

                $row->text = &$row->introtext;

                if(
                    (isset($row->params) || isset($row->parameters))
                    && isset($row->id) && $row->id > 0
                    && isset($row->catid) && $row->catid > 0
                ) {
                    $cache_file = S2CacheKey('jreviews_config');

                    $Config = S2Cache::read($cache_file,'_s2framework_core_');

                    $debug = false;

                    $debug_php = Sanitize::getBool($Config,'debug_enable',false);

                    $debug_ipaddress = Sanitize::getString($Config,'debug_ipaddress');

                    if($debug_php &&
                        ($debug_ipaddress == '' || $debug_ipaddress == s2GetIpAddress())) {

                        $debug = true;
                    }

                    $Dispatcher = new S2Dispatcher(array('app'=>'jreviews','debug'=>$debug));

                    if ($option=='com_content' && $view == 'article' & $id > 0)
                    {
                        $url = 'com_content/com_content_view';
                    }
                    elseif ($option=='com_content' && (($layout == 'blog' && $view=='category') || $view == 'featured'))
                    {
                        $url = 'com_content/com_content_blog';
                    }

                    $passedArgs = array(
                        'params'=>$params,
                        'row'=>$row,
                        'component'=>'com_content'
                        );

                    $passedArgs['cat'] = $row->catid;
                    $passedArgs['listing_id'] = $row->id;

                    $output = $Dispatcher->dispatch($url,$passedArgs);

                    if($output)
                    {
                        $row = &$output['row'];
                        unset($params);
                        $params = &$output['params'];
                    }

                    /**
                    * Store a copy of the $listing and $crumbs arrays in memory for use in the onBeforeDisplayContent method
                    */
                    ClassRegistry::setObject(array('listing'=>&$output['listing'],'crumbs'=>&$output['crumbs']),'jreviewsplugin');

                    // Destroy pathway
                    if(!empty($output['crumbs']))
                    {
                        cmsFramework::setPathway(array());
                    }

                    unset($output,$passedArgs,$Dispatcher);
                }
            }
            /***********************************************************************
            * ABOVE BLOCK HERE BECAUSE J! DOESN'T MAKE THE WHOLE ARTICLE OBJECT
            * AVAILABLE IN THE ONCONTENTPREPARE CALLBACK IN BLOG LAYOUT PAGES
            ***********************************************************************/

            if (!class_exists('cmsFramework') || !class_exists('Sanitize')) return;

           // Make sure this is a Joomla article page
            $option = Sanitize::getString($_REQUEST, 'option', '');

            $view = Sanitize::getString($_REQUEST, 'view', '');

            $layout = Sanitize::getString($_REQUEST, 'layout', '');

            $id = Sanitize::getInt($_REQUEST,'id');

            if(!($option == 'com_content' && $view == 'article' && $id)) return;

            /**
            * Retrieve $listing array from memory
            */
            $Config = Configure::read('JreviewsSystem.Config');

            if(Sanitize::getInt($Config,'override_listing_title')) {

                $title = '{title}';
            }
            else {

                $title = trim(Sanitize::getString($Config,'type_metatitle','{title}'));

                $title = $title ?: '{title}';
            }

            // Allow title override via article menu

            // Check if active menu is for an article

            $menu = JApplication::getInstance('site')->getMenu()->getActive();

            if($menu
                && Sanitize::getString($menu->query,'option') == 'com_content'
                && Sanitize::getString($menu->query,'view') == 'article'
                && $params->get('page_title') != ''
                && $id == Sanitize::getInt($menu->query,'id')) {

                self::$pagetitle = $params->get('page_title');
            }
            else {

                self::$pagetitle = $article->title;
            }

            $keywords = trim(Sanitize::getString($Config,'type_metakey'));

            $description = trim(Sanitize::getString($Config,'type_metadesc'));

            $listing = classRegistry::getObject('listing','jreviewsplugin'); // Has all the data that's also available in the detail.thtml theme file so you can create any sort of conditionals with it

            $crumbs = classRegistry::getObject('crumbs','jreviewsplugin');

            if($title != '' || $keywords != '' || $description != '')
            {
                if($listing && is_array($listing))
                {
                    // Get and process all tags

                    $tags = plgContentJreviews::extractTags($title.$keywords.$description);

                    $tags_array = array();

                    foreach($tags AS $tag)
                    {
                        switch($tag)
                        {
                            case 'title':
                                $tags_array['{title}'] = Sanitize::stripAll($listing['Listing'],'title');
                            break;
                            case 'directory':
                                $tags_array['{directory}'] = Sanitize::stripAll($listing['Directory'],'title');
                            break;
                            case 'category':
                                $tags_array['{category}'] = Sanitize::stripAll($listing['Category'],'title');
                            break;
                            case 'metakey':
                                $tags_array['{metakey}'] = Sanitize::stripAll($listing['Listing'],'metakey');
                            break;
                            case 'metadesc':
                                $tags_array['{metadesc}'] = Sanitize::stripAll($listing['Listing'],'metadesc');
                            break;
                            case 'summary':
                                $tags_array['{summary}'] = Sanitize::htmlClean(Sanitize::stripAll($listing['Listing'],'summary'));
                            break;
                            case 'description':
                                $tags_array['{description}'] = Sanitize::htmlClean(Sanitize::stripAll($listing['Listing'],'description'));
                            break;
                            default:
                                if(substr($tag,0,3) == 'jr_' && isset($listing['Field']))
                                {
                                    $fields = $listing['Field']['pairs'];

                                    if(isset($listing['Field']['pairs'][$tag]) && isset($fields[$tag]['text']))
                                    {
                                        $fieldValue = $fields[$tag]['text'][0];

                                        $properties = $fields[$tag]['properties'];

                                        if($fields[$tag]['type'] == 'date')
                                        {
                                            $format = Sanitize::getString($properties,'date_format');

                                            $TimeHelper = ClassRegistry::getClass('TimeHelper');

                                            $fieldValue = $TimeHelper->nice($fieldValue,$format,0);
                                        }
                                        elseif($fields[$tag]['type'] == 'decimal') {

                                            $decimals = Sanitize::getInt($properties,'decimals',2);

                                            $fieldValue = Sanitize::getInt($properties,'curr_format') ? number_format($fieldValue,$decimals,__l('DECIMAL_SEPARATOR',true),__l('THOUSANDS_SEPARATOR',true)) : round($fieldValue,$decimals);
                                        }
                                        elseif($fields[$tag]['type'] == 'integer') {

                                            $fieldValue = Sanitize::getInt($properties,'curr_format') ? number_format($fieldValue,0,__l('DECIMAL_SEPARATOR',true),__l('THOUSANDS_SEPARATOR',true)) : $fieldValue;
                                        }

                                        if(in_array($fields[$tag]['type'],array('integer','decimal')))
                                        {
                                            $fieldValue = str_ireplace('{fieldtext}', $fieldValue, Sanitize::getString($properties,'output_format'));
                                        }

                                        $fields[$tag]['text'][0] = $fieldValue;


                                        $tags_array['{'.$tag.'}'] =  html_entity_decode(implode(", ", $fields[$tag]['text']),ENT_QUOTES,'utf-8');
                                    }
                                    else {

                                        $tags_array['{'.$tag.'}'] = '';
                                    }
                                }
                            break;
                        }
                    }

                    # Process title

                    if($title != '')
                    {
                        $title = strip_tags(str_replace('&amp;','&',str_replace(array_keys($tags_array),$tags_array,$title)));

                         // Works when Joomla cache is disabled
                        self::$pagetitle = $title;

                        // Required for Joomla cache and ClickFWD title patch
                        $params->set('page_title',$title);
                   }

                    # Process description

                    if($description != '')
                    {
                        $description = str_replace(array_keys($tags_array),$tags_array,$description);

                        $article->metadesc = $description;
                    }
                    elseif($article->metadesc == '' && $listing['Listing']['summary'] != '') {

                        $description = $listing['Listing']['summary'];

                        $article->metadesc = $description;
                    }
                    else {

                        $description = $article->metadesc;
                    }

                    # Process keywords

                    if($keywords != '')
                    {
                        $keywords = mb_strtolower(str_replace('&amp;','&',str_replace(array_keys($tags_array),$tags_array,$keywords)),'utf-8');

                        cmsFramework::meta('keywords', $keywords);

                        $article->metakey = $keywords;
                    }
                    elseif($article->metakey != '') {

                        $keywords = $listing['Listing']['metakey'];
                    }
                }
            }
            elseif(
                isset($article->parameters)
                && $article->parameters->get('show_page_title')
                && $article->parameters->get('num_leading_articles') == '' /* run only if it's an article menu */
                && $article->parameters->get('filter_type') == '' /* run only if it's an article menu */
            ) {
                    $title = $article->parameters->get('page_title');

                    if($title != '')
                    {
                        // Works when Joomla cache is disabled
                        self::$pagetitle = $title;

                        // Required for Joomla cache and ClickFWD title patch
                        $params->set('page_title',$title);
                    }
            }

            if($crumbs && !empty($crumbs))
            {
    			array_pop($crumbs); // Remove extra title from breadcrumb because it's automatically appended by Joomla

                cmsFramework::setPathway($crumbs);
            }

            $keywords = $article->metakey;

            if($article->metadesc != '')
            {
                $description = $article->metadesc;
            }
            elseif($listing['Listing']['summary'] != '') {

                $description = $listing['Listing']['summary'];
            }
            elseif($listing['Listing']['description'] != '') {

                $description = $listing['Listing']['description'];
            }

            $article->metadesc = $description = self::prepareMetaDesc($description);

            cmsFramework::meta('description', $description);

            $this->facebookOpenGraph($listing, compact('title','keywords','description'));

            $this->twitterCard($listing, compact('title','keywords','description'));
        }

        function onContentPrepare( $context, &$article, &$params)
        {
    		if($context == 'com_content.article')
            {
    			//Override Joomla article params

            	if(method_exists($params, 'set')) {
    				$params->set('show_item_navigation',0);
    	            $params->set('show_vote',0);
    			}
    			elseif(isset($this->params) && method_exists($this->params,'set')) {
    				$this->params->set('show_item_navigation',0);
                    $this->params->set('show_vote',0);
    			}
    		}

            if (!class_exists('cmsFramework') || !class_exists('Sanitize')) return;

            // Check whether to perform the replacement or not
            $option = Sanitize::getString($_REQUEST, 'option', '');
            $view = Sanitize::getString($_REQUEST, 'view', '');
            $layout = Sanitize::getString($_REQUEST, 'layout', '');
            $id = Sanitize::getInt($_REQUEST,'id');
            if(
                $option == 'com_content'
                &&
                in_array($view,array('article','category','frontpage'))
                && ($layout != '' || in_array($view,array('article','frontpage')))
            )
            {
                $row = &$article;
                if(isset($row->id)
                    && $row->id > 0
                    && isset($row->catid)
                    && $row->catid > 0
                ) {

                    $cache_file = s2CacheKey('jreviews_config');

                    $Config = S2Cache::read($cache_file,'_s2framework_core_');

                    $debug = false;

                    $debug_php = Sanitize::getBool($Config,'debug_enable',false);

                    $debug_ipaddress = Sanitize::getString($Config,'debug_ipaddress');

                    if($debug_php &&
                        ($debug_ipaddress == '' || $debug_ipaddress == s2GetIpAddress())) {

                        $debug = true;
                    }

                    $Dispatcher = new S2Dispatcher(array('app'=>'jreviews','debug'=>$debug));

                    if ($option=='com_content' && $view == 'article' & $id > 0) {

                        $url = 'com_content/com_content_view';

                    } elseif ($option=='com_content' && ((($layout == 'blog' || $layout == 'blogfull') && $view == 'category') || $view == 'frontpage')) {

                        $url = 'com_content/com_content_blog';

                    }

                    $passedArgs = array(
                        'params'=>$params,
                        'row'=>$row,
                        'component'=>'com_content'
                        );

                    $passedArgs['cat'] = $row->catid;

                    $passedArgs['listing_id'] = $row->id;

                    $output = $Dispatcher->dispatch($url,$passedArgs);

                    if($output)
                    {
                        $row = &$output['row'];
                        unset($params);
                        $params = &$output['params'];
                    }

                    /**
                    * Store a copy of the $listing and $crumbs arrays in memory for use in the onBeforeDisplayContent method
                    */
                    classRegistry::setObject(array('listing'=>&$output['listing'],'crumbs'=>&$output['crumbs']),'jreviewsplugin');

                    // Destroy pathway
                    if(!empty($output['crumbs']))
                    {
                        cmsFramework::setPathway(array());
                    }
                    unset($output,$passedArgs,$Dispatcher);
                }
            }
        }

        static function prepareMetaDesc($description, $chars = 500)
        {
            $description = str_replace(array("\n","\r","\r\n","\n\r"), ' ', $description);

            $description = Sanitize::htmlClean(Sanitize::stripAll(array($description),0));

            if(strlen($description) > $chars)
            {
                $pos = strpos($description, ' ', $chars);

                if($pos)
                {
                    $description = substr($description,0,$pos);
                }
            }

            return $description;
        }

        function extractTags($text)
        {
            $pattern = '/{([a-z0-9_|]*)}/i';

            $matches = array();

            $result = preg_match_all( $pattern, $text, $matches );

            if( $result == false ) {
                return array();
            }

            return array_unique(array_values($matches[1]));
        }

        function isFBUserAgent()
        {
            if (
                strpos($_SERVER["HTTP_USER_AGENT"], "facebookexternalhit/") !== false ||
                strpos($_SERVER["HTTP_USER_AGENT"], "Facebot") !== false
            ) {
                return true;
            }

            return false;
        }

        /**
        * Facebook Open Graph implementation
        *
        * @param mixed $listing
        * @param mixed $meta
        */
        function facebookOpenGraph(&$listing, $meta)
        {
            // http://developers.facebook.com/docs/opengraph/

            $option = Sanitize::getString($_REQUEST, 'option', '');

            $view = Sanitize::getString($_REQUEST, 'view', '');

            $id = Sanitize::getInt($_REQUEST,'id');

            // Make sure this is a Joomla article page
            if(!($option == 'com_content' && $view == 'article' && $id)) return;

            $Config = Configure::read('JreviewsSystem.Config');

            if(empty($Config))
            {
                $cache_file = s2CacheKey('jreviews_config');

                $Config = S2Cache::read($cache_file,'_s2framework_core_');
            }

            $facebook_xfbml = Sanitize::getBool($Config,'facebook_opengraph');

            // Make sure FB is enabled and we have an FB App Id
            if(!$facebook_xfbml) return;

            extract($meta);

            $title == '' and $title = $listing['Listing']['title'];

            $image = $width = $height = '';

            if( isset($listing['MainMedia']))
            {
                $file_extension = Sanitize::getString($listing['MainMedia'],'file_extension');

                $image_url = Sanitize::getString($listing['MainMedia'],'media_path');

                if($image_url && $file_extension) $image =  $image_url. '.' . $file_extension;
            }

            if ($image == '')
            {
                $img_src = '/<img[^>]+src[\\s=\'"]+([^"\'>\\s]+(jpg)+)/is';

                preg_match($img_src,$listing['Listing']['summary'],$matches);

                if(isset($matches[1])) $image = $matches[1];
            }

            // Output the OG image dimention tags

            if ($this->isFBUserAgent())
            {
               if (isset($listing['MainMedia']) && isset($listing['MainMedia']['metadata']) && isset($listing['MainMedia']['metadata']['COMPUTED']))
                {
                    $width = Sanitize::getInt($listing['MainMedia']['metadata']['COMPUTED'], 'Width');
                    $height = Sanitize::getInt($listing['MainMedia']['metadata']['COMPUTED'], 'Height');
                }

                if (!$width && !$height && strstr($image,WWW_ROOT))
                {
                    $size = getimagesize($image);
                    $width = $size[0];
                    $height = $size[1];
                }
                elseif (isset($listing['MainMedia']['media_info']['image'])) {
                    $width = Sanitize::getInt($listing['MainMedia']['media_info']['image'], 'width');
                    $height = Sanitize::getInt($listing['MainMedia']['media_info']['image'], 'height');
                }
            }

            $url = cmsFramework::makeAbsUrl($listing['Listing']['url'],array('sef'=>true,'ampreplace'=>true));

            $object_type = Sanitize::getString($listing['ListingType']['config'],'facebook_opengraph_type');

            $tags = array(
                'fb:app_id'=>Sanitize::getString($Config,'facebook_appid'),
                'og:title'=>$title,
                'og:url'=>$url,
                'og:image'=>$image,
                'og:site_name'=>cmsFramework::getConfig('sitename'),
                'og:description'=>$description,
                'og:type'=>$object_type,
                'place:location:latitude'=>Sanitize::getString($Config,'geomaps.latitude'),
                'place:location:longitude'=>Sanitize::getString($Config,'geomaps.longitude')
            );

            if ($width && $height)
            {
                $tags['og:image:width'] = $width;
                $tags['og:image:height'] = $height;
            }

            switch($object_type)
            {
                case 'restaurant.restaurant':

                    $tags['place:location:latitude']                = Sanitize::getString($Config,'geomaps.latitude');
                    $tags['place:location:longitude']               = Sanitize::getString($Config,'geomaps.longitude');
                    $tags['restaurant:contact_info:street_address'] = Sanitize::getString($Config,'geomaps.address1');
                    $tags['restaurant:contact_info:locality']       = Sanitize::getString($Config,'geomaps.city');
                    $tags['restaurant:contact_info:region']         = Sanitize::getString($Config,'geomaps.state');
                    $tags['restaurant:contact_info:postal_code']    = Sanitize::getString($Config,'geomaps.postal_code');
                    $tags['restaurant:contact_info:country_name']   = Sanitize::getString($Config,'geomaps.country',Sanitize::getString($Config,'geomaps.default_country'));

                break;

                case 'business.business':

                    $tags['place:location:latitude']                = Sanitize::getString($Config,'geomaps.latitude');
                    $tags['place:location:longitude']               = Sanitize::getString($Config,'geomaps.longitude');
                    $tags['business:contact_data:street_address']   = Sanitize::getString($Config,'geomaps.address1');
                    $tags['business:contact_data:locality']         = Sanitize::getString($Config,'geomaps.city');
                    $tags['business:contact_data:region']           = Sanitize::getString($Config,'geomaps.state');
                    $tags['business:contact_data:postal_code']      = Sanitize::getString($Config,'geomaps.postal_code');
                    $tags['business:contact_data:country_name']     = Sanitize::getString($Config,'geomaps.country',Sanitize::getString($Config,'geomaps.default_country'));

                break;

                case 'place':

                    $tags['place:location:latitude']                = Sanitize::getString($Config,'geomaps.latitude');
                    $tags['place:location:longitude']               = Sanitize::getString($Config,'geomaps.longitude');

                break;
            }

            // If you want to add other tags using custom fields as the content you can use the syntax below

            // $tags['open-graph-tag'] = 'jr_fieldname';

            # Loop through the tags array to add the additional FB meta tags

            $fields = isset($listing['Field']) ? $listing['Field']['pairs'] : array();

            $format = '<meta property="%s" content="%s" />';

            foreach($tags AS $tag=>$fname)
            {
                $content = '';

                if(substr($fname,0,3) == 'jr_') {
                    // It's a custom field
                    $content = isset($fields[$fname]) ? $fields[$fname]['text'][0] : '';
                }
                elseif($fname != '') {
                    // It's a static text, not a custom field
                    $content = $fname;
                }

                if(trim($content) == '') continue;

                $html = sprintf($format, $tag, htmlspecialchars(strip_tags($content),ENT_COMPAT,'utf-8'));

                cmsFramework::addCustomTag($html, $tag);
            }
        }

        /**
        * Twitter Card implementation
        *
        * @param mixed $listing
        * @param mixed $meta
        */
        function twitterCard(&$listing, $meta)
        {
            $option = Sanitize::getString($_REQUEST, 'option', '');

            $view = Sanitize::getString($_REQUEST, 'view', '');

            $id = Sanitize::getInt($_REQUEST,'id');

            // Make sure this is a Joomla article page
            if(!($option == 'com_content' && $view == 'article' && $id)) return;

            $Config = Configure::read('JreviewsSystem.Config');

            if(empty($Config)) {

                $cache_file = s2CacheKey('jreviews_config');

                $Config = S2Cache::read($cache_file,'_s2framework_core_');
            }

            $twitter_card_type = Sanitize::getString($Config,'twitter_card','summary');

            $twitter_username = Sanitize::getString($Config,'twitter_card_username');

            $twitter_creator_fname = Sanitize::getString($Config,'twitter_creator');

            // Make sure Twitter Cards are enabled and we have a twitter username

            if(!$twitter_card_type || !$twitter_username) return;

            if($twitter_username{0} != '@') $twitter_username = '@' . $twitter_username;

            $twitter_creator = $twitter_username;

            extract($meta);

            $title == '' and $title = $listing['Listing']['title'];

            $images = array();

            if(isset($listing['MainMedia']) && isset($listing['MainMedia']['filesize'])) {

                $file_extension = Sanitize::getString($listing['MainMedia'],'file_extension');

                $images[] = self::getTwitterImageURL($listing['MainMedia']);
            }

            if(empty($images)) {

                $img_src = '/<img[^>]+src[\\s=\'"]+([^"\'>\\s]+(jpg)+)/is';

                preg_match($img_src,$listing['Listing']['summary'],$matches);

                if(isset($matches[1])) $images[] = $matches[1];
            }

            // Process gallery images

            if(isset($listing['Media']) && isset($listing['Media']['photo']))
            {
                $i = 0;

                foreach($listing['Media']['photo'] AS $photo)
                {
                    if(isset($photo['filesize']))
                    {
                        $i++;

                        $images[] = self::getTwitterImageURL($photo);

                        if($i == 3) break; // Max 3 images in gallery in addition to main media
                    }
                }
            }

            // If no images, then force the card to a summary

            if(empty($images)) {

                $twitter_card_type = 'summary';
            }

            $url = cmsFramework::makeAbsUrl($listing['Listing']['url'],array('sef'=>true,'ampreplace'=>true));

            $tags = array(
                'twitter:card'=>$twitter_card_type,
                'twitter:site'=>$twitter_username,
                'twitter:title'=>$title,
                'twitter:description'=>$description,
                'twitter:url'=>$url
            );

            switch($twitter_card_type)
            {
                case 'summary':
                case 'summary_large_image':
                case 'photo':

                    if($images) $tags['twitter:image'] = $images[0];

                    break;

                case 'gallery':

                    foreach($images AS $key=>$image)
                    {
                        $tags['twitter:image'.$key] = $images[$key];
                    }

                    break;

                default:

                    break;
            }

            // Add the creator tag for the allowed types

            $fields = isset($listing['Field']) ? $listing['Field']['pairs'] : array();

            if(!empty($fields) && in_array($twitter_card_type,array('summary_large_image', 'photo', 'gallery'))) {

                if(substr($twitter_creator_fname,0,3) == 'jr_') {

                    // It's a custom field
                    if(isset($fields[$twitter_creator_fname]) && $fields[$twitter_creator_fname]['text'][0] != '')
                    {
                        $twitter_creator = $fields[$twitter_creator_fname]['text'][0];
                    }
                }

                if($twitter_creator{0} != '@') $twitter_creator = '@' . $twitter_creator;

                $tags['twitter:creator'] = $twitter_creator;
            }

            # Loop through the tags array to add the additional FB meta tags

            $format = '<meta name="%s" content="%s" />';

            foreach($tags AS $tag=>$fname)
            {
                $content = '';

                if(substr($fname,0,3) == 'jr_') {
                    // It's a custom field
                    $content = isset($fields[$fname]) ? $fields[$fname]['text'][0] : '';
                }
                elseif($fname != '') {
                    // It's a static text, not a custom field
                    $content = $fname;
                }

                if(trim($content) == '') continue;

                $html = sprintf($format, $tag, htmlspecialchars(strip_tags($content),ENT_COMPAT,'utf-8'));

                cmsFramework::addCustomTag($html, $tag);
            }
        }

        /**
         * Need to limit file size to 1MB. If original is too big we choose the largest thumbnail based on width
         * @param  [type] $media [description]
         * @return [type]        [description]
         */
        function getTwitterImageURL($media, $max_size = 1)
        {
            $thumbnails = Sanitize::getVar($media['media_info'], 'thumbnail', array());

            if($media['filesize'] < $max_size*1000000)
            {
                return $media['media_info']['image']['url'];
            }
            elseif (!empty($thumbnails)) {

                $max_width = 0;
                $max_width_key = 0;

                // Find largest thumbnail based on width
                foreach($media['media_info']['thumbnail'] AS $key=>$thumb)
                {
                    if($thumb['width'] > $max_width)
                    {
                        $max_width = $thumb['width'];

                        $max_width_key = $key;
                    }
                }

                return $media['media_info']['thumbnail'][$max_width_key]['url'];
            }
        }
    }
}
