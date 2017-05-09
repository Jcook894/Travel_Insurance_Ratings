<?php
/**
 * JReviews - Reviews Extension
 * Copyright (C) 2010-2016 ClickFWD LLC
 * This is not free software, do not distribute it.
 * For licencing information visit https://www.jreviews.com
 * or contact sales@jreviews.com
**/

// no direct access
defined( 'MVC_FRAMEWORK') or die( 'Direct Access to this location is not allowed.' );

class AdminSearchController extends MyController {

    var $uses = array('criteria');

    var $helpers = array('form');

    var $autoLayout = false;

    var $autoRender = false;

    private $coreSettings = array(
        'settings.configuration.json',
        'settings.access.json',
        'settings.media.json',
        'settings.predefined-replies.json',
        'settings.theme-manager.json',
        'settings.fields-seo.json',
        'settings.category-seo.json',
        'settings.listing-types.json',
        'settings.listing-types.lang.json',
        'settings.listing-types.seo.json',
    );

    function liveSearch()
    {
        $data = $results = $overrides = $listingTypes = array();

        $term = Sanitize::getString($this->params, 'term');

        $data = $this->loadSearchData();

        if($term && !empty($data))
        {
            $listingTypes = $this->Criteria->getSelectList();

            $results = $this->arraySearch($term, $data);

            if(!empty($results))
            {
                $overridesResults = Sanitize::getVar($results, 'overrides', array());

                if(!empty($overridesResults))
                {
                    $overrides = array('ListingTypes' => $overridesResults);

                    unset($results['overrides']);
                }
            }
        }

        return $this->renderResults($results, $overrides, $listingTypes);
    }

    private function renderResults($results, $overrides, $listingTypes)
    {
        $this->set(array(
            'listingTypes' => $listingTypes,
            'results' => $results,
            'overrides' => $overrides
        ));

        return $this->render('search', 'index');
    }

    private function loadSearchData()
    {
        $data = array();

        $Translate = I18n::getInstance()->l10n;

        $lang = $Translate->languagePath[1];

        $configFilePath = S2Paths::get('jreviews', 'S2_APP_LOCALE') . $lang . DS . 'LC_MESSAGES' . DS;

        $configFilePathDefault = S2Paths::get('jreviews', 'S2_APP_LOCALE') . 'eng' . DS . 'LC_MESSAGES' . DS;

        // Load core files

        $files = $this->coreSettings;

        foreach($files AS $file)
        {
            if($lang != 'eng' && file_exists($configFilePath . $file))
            {
                $settingsConfigurationFile = $configFilePath . $file;
            }
            else {
                $settingsConfigurationFile = $configFilePathDefault . $file;
            }

            $data = array_merge($data, json_decode(file_get_contents($settingsConfigurationFile) ,true));
        }

        // Load addon files

        $Folder = new S2Folder(PATH_APP_ADDONS);

        $addonSettings = $Folder->findRecursive('settings.*\.json');

        $settingsLangArray = array();

        if(!empty($addonSettings))
        {
            // Create an associative array for each setting file using the file name and language as keys

            foreach($addonSettings AS $file)
            {
                preg_match('/locale\/(?P<lang>[a-z].*)\/LC_MESSAGES/', $file, $match);

                $settingsLangArray[pathinfo($file, PATHINFO_FILENAME)][$match['lang']] = $file;
            }

            foreach($settingsLangArray AS $settings => $langSettings)
            {

                if($lang != 'eng' && isset($langSettings[$lang]))
                {
                    $data = array_merge($data, json_decode(file_get_contents($langSettings[$lang]) ,true));
                }
                elseif(isset($langSettings['eng'])) {

                    $data = array_merge($data, json_decode(file_get_contents($langSettings['eng']) ,true));
                }
            }
        }

        return $data;
    }

    private function arraySearch($term, $data)
    {
        $results = array();

        foreach($data AS $page)
        {
            $found = false;

            foreach($page['settings'] AS $setting)
            {
                $cms = Sanitize::getVar($setting, 'cms', array());

                if(!empty($cms) && !in_array(_CMS_NAME, $cms)) continue;

                // $haystack = $setting['title'] . ' ' . $setting['keywords'] . ' ' . $setting['description'];

                $haystack = Sanitize::getString($page, 'keywords') . ' ' . $setting['keywords'] . ' ' . $setting['title'];

                $needle = array_filter(explode(' ', $term));

                if($this->contains_all($haystack, $needle))
                {
                    $setting['menu'] = $page['menu'];

                    $setting['url'] = $page['url'];

                    $setting['tags'] = Sanitize::getVar($setting, 'tags', array());

                    // If the entire settings are overrides, then exclude from the rest of the results and add only to overrides

                    if(Sanitize::getBool($page, 'override'))
                    {
                        $results['overrides'][$setting['name']] = $setting;
                    }
                    else {
                        $results[$page['menu']][$setting['name']] = $setting;

                        if(Sanitize::getBool($setting, 'override'))
                        {
                            $results['overrides'][$setting['name']] = $setting;
                        }
                    }
                }
            }
        }

        return $results;
    }

    private function contains_all($str, array $words)
    {
        foreach($words as $word)
        {
            if(stripos($str,$word) === false)
            {
                return false;
            }
        }

        return true;
    }
}

