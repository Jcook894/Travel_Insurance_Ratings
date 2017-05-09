<?php
/**
 * @version       1.0.0 August 18, 2013
 * @author        ClickFWD https://www.jreviews.com
 * @copyright     Copyright (C) 2010 - 2013 ClickFWD LLC. All rights reserved.
 * @license       Proprietary
 *
 */
defined('_JEXEC') or die;

if (!defined('DS')) define('DS', DIRECTORY_SEPARATOR);

$JRSEFpluginObject = JPluginHelper::getPlugin('system','jreviews_sef');

$JRSEFpluginParams = json_decode($JRSEFpluginObject->params);

$page_assignment = isset($JRSEFpluginParams->page_assignment) ? $JRSEFpluginParams->page_assignment : 1;

/**
 * Need to override the whole JModuleHelper class because it's not possible to extend it
 */

$JVersion = new JVersion;

$shortVersion = $JVersion->getShortVersion();

if (version_compare($shortVersion, '3.6.1', '>='))
{
    $helperPath = dirname(__FILE__) . DS . 'modulehelper.php';
}
else {
    $helperPath = dirname(__FILE__) . DS . 'modulehelper_old.php';
}

if($page_assignment == 1 && !file_exists(JPATH_PLUGINS . '/system/advancedmodules/modulehelper.php')) {

    require_once $helperPath;
}

if($page_assignment > 0)
{
    JFactory::getApplication()->registerEvent('onPrepareModuleList', 'JReviewsPrepareModuleList');
}

class plgSystemJreviews_sef extends JPlugin
{
    var $canonical_url = false;

    static function prx($var)
    {
        echo '<pre>'.print_r($var,true).'</pre>';
    }

    public function onAfterInitialise()
    {
        $app  = JFactory::getApplication();

        if($app->isAdmin()) {

            // We need to define the constant here for admin side links (i.e. in moderation) to get the correct front-end menu ids

            $use_core_cat_menus = $this->params->get('use_core_cat_menus', 0);

            if($use_core_cat_menus)
            {
                define('JREVIEWS_SEF_PLUGIN',1);
            }

            return;
        }

        if(JFactory::getConfig()->get('sef'))
        {
            $suffix = JFactory::getConfig()->get('sef_suffix');

            // JFactory::getConfig()->set('sef_suffix',0);

            $langFilter = class_exists('plgSystemLanguageFilter') && method_exists($app, 'getLanguageFilter') && $app->getLanguageFilter();

            $this->params->set('sef_suffix', $suffix);

            $router = $app->getRouter();

            require_once dirname(__FILE__) . DS . 'jreviews_router.php';

            $JreviewsRouter = new JReviewsRouter($this->params, $this, $app);

            $JVersion = new JVersion;

            $shortVersion = $JVersion->getShortVersion();

            $JreviewsRouter->setVersion($shortVersion);

            if (version_compare($shortVersion, '3.6.1', '>='))
            {
                // Process JReviews SEF Plugin before the core content router and the language filter plugin
                $router->attachBuildRule(array($JreviewsRouter, 'buildJReviews'), JRouter::PROCESS_BEFORE);

                // Aug 9, 2016 - To fix issue with lang filter introduced in Joomla 3.6.x
                // We need to take the appended language segment, remove it and pre-preped it to the route
                $router->attachBuildRule(array($JreviewsRouter, 'postprocessBuildJReviews'), JRouter::PROCESS_AFTER);
            }
            else {

                $router->attachBuildRule(array($JreviewsRouter, 'buildJReviews'));
            }

            if (version_compare($shortVersion, '3.6.1', '>=') && $this->params->get('parse_first', 0) == 0)
            {
                $router->attachParseRule(array($JreviewsRouter, 'parseJReviews'), JRouter::PROCESS_AFTER);
            }
            else {

                $router->attachParseRule(array($JreviewsRouter, 'parseJReviews'));
            }
        }
    }

    public function onBeforeCompileHead()
    {
        $doc = JFactory::getDocument();

        $app = JFactory::getApplication();

        $request = $app->input;

        $option = $request->get('option');

        if ($app->getName() != 'site'
            || $doc->getType() !== 'html'
            || (!in_array($option, array('com_content', 'com_jreviews')))
        ) {
            return true;
        }

        // Remove canonical tags added by the Joomla sef plugin

        // if($this->canonical_url) {

            foreach($doc->_links AS $url=>$attr)
            {
                if(isset($attr['relation']) && $attr['relation'] == 'canonical') {

                    // Replace the canonical tag with our own version

                    // $doc->_links[$this->canonical_url] = $attr;

                    // Remove the previous canonical tag set by the sef system plugin
                    unset($doc->_links[$url]);
                }
            }
        // }
    }

    /**
     * Add new JReviews tab to all modules to control assignment on JReviews pages
     */
    public function onContentPrepareForm($form, $data)
    {
        $page_assignment = $this->params->get('page_assignment', 1);

        if($page_assignment == 0) return;

        $app  = JFactory::getApplication();

        $input = $app->input;

        if(!$app->isAdmin()
            || ($input->get('option') != 'com_modules' && $input->get('view') != 'module')
            || file_exists(JPATH_PLUGINS . '/system/advancedmodules/modulehelper.php'))
        {
                return;
        }

        if($form->getName() == 'com_modules.module')
        {
            // Load plugin parameters
            $module = JModuleHelper::getModule($data->module);

            $params = new JRegistry($data->params);

            // Check we have a form
            if (!($form instanceof JForm))
            {
                $this->_subject->setError('JERROR_NOT_A_FORM');

                return false;
            }

            // Extra parameters for menu edit
            if ($form->getName() == 'com_modules.module')
            {
                $options = json_decode($params->loadArray(''),true);

                $jreviews_page_hide = isset($options['jreviews_page_hide']) && is_array($options['jreviews_page_hide']) ? $options['jreviews_page_hide'] : array();

                $form->load('
                        <form>
                        <fields name="params" >
                        <fieldset
                        name="jreviews-page-assignment"
                        label="JReviews Page Assignment"
                        >
                        <field
                        name="jreviews_page_hide"
                        type="list"
                        multiple="true"
                        label="Hide Module in "
                        description=""
                        default="' . implode(',',$jreviews_page_hide) . '"
                        >
                                <option value="detail">Detail page</option>
                                <option value="category">Category page</option>
                                <option value="category_alias">Category-alias page</option>
                                <option value="discussion">Review discussion page</option>
                        </field>
                        </fieldset>
                        </fields>
                        </form>
                        ');
            }
        }

        return true;
    }
}

/**
 * Disables modules on-the-fly in pages as specified in the JReviews Module Assignment settings
 */
function JReviewsPrepareModuleList(&$modules)
{
    $app = JFactory::getApplication();

    if(JFactory::getApplication()->getClientId() || !$modules) return;

    $db = JFactory::getDbo();

    $JMenu = $app->getMenu();

    $page = '';

    $disabled_modules = array();

    $input = $app->input;

    $option = $input->get('option');

    $view = $input->get('view');

    $menu_id = $input->get('Itemid');

    $menu = $JMenu->getItem($menu_id);

    if(!$menu) return;

    $query = $menu->query;

    $cache = JFactory::getCache('com_modules', '');

    $cacheid = md5('jreviews_categories');

    $jreviews_categories = $cache->get($cacheid);

    if(!$jreviews_categories)
    {
        $sql = "
            SELECT
                id
            FROM
                #__jreviews_categories
            WHERE
                `option` = 'com_content'
        ";

        $cat_ids = $db->setQuery($sql)->loadObjectList('id');

        $jreviews_categories = array_keys($cat_ids);

        $cache->store($jreviews_categories, $cacheid);
    }

    if($option == 'com_content' && $view == 'article')
    {
        $sql = "
            SELECT
                catid
            FROM
                #__content
            WHERE
                id = " . (int) $input->get('id')
        ;

        $cat_id = $db->setQuery($sql)->loadResult();

        in_array($cat_id, $jreviews_categories) and $page = 'detail';
    }
    elseif(
        isset($query['option']) && isset($query['view'])
        &&
            (
                ($query['option'] == 'com_content' && $query['view'] == 'category')
                ||
                ($query['option'] == 'com_jreviews' && $query['view'] == 'category' && ($menu->params->get('action') == 2 || $input->get('cat') > 0))
            )
    ) {
        $url = $input->get('url');

        if($url == 'discussionsreview')
        {
            $page = 'discussion';
        }
        elseif($url != 'categoriescategory')
        {
            $page = 'category_alias';
        }
        else {

            $page = 'category';
        }
    }

    foreach($modules AS $id=>$module)
    {
        $options = json_decode($module->params,true);

        $jreviews_page_hide = isset($options['jreviews_page_hide']) ? $options['jreviews_page_hide'] : array();

        if(is_string($jreviews_page_hide)) {
            $jreviews_page_hide = array($jreviews_page_hide);
        }

        if(in_array($page,$jreviews_page_hide))
        {
            $module->published = 0;

            $modules[$id] = $module;
        }
    }
 }