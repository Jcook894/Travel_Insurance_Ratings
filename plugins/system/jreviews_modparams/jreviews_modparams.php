<?php
/**
 * @version       1.0 January 11, 2016
 * @author        ClickFWD https://www.jreviews.com
 * @copyright     Copyright (C) 2010 - 2016 ClickFWD LLC. All rights reserved.
 * @license       Proprietary
 *
 */
defined('_JEXEC') or die;

if (!defined('DS')) define('DS', DIRECTORY_SEPARATOR);

class plgSystemJreviews_modparams extends JPlugin
{
    static function prx($var)
    {
        echo '<pre>'.print_r($var,true).'</pre>';
    }

    public function onContentPrepareForm($form, $data)
    {
        $app  = JFactory::getApplication();

        if(!$app->isAdmin() || !is_object($data) || !property_exists($data, 'module'))
        {
                return;
        }

        if(isset($data->module) && strstr($data->module, 'jreviews'))
        {
            $this->transformCommaListToArray($data);
        }

        return true;
    }

    /**
     * Ensures that values that were previously stored as comma separated lists are not lost
     * when modules are edited now that an array is used to store the same values
     */

    protected function transformCommaListToArray($data)
    {
        $fields = array(
            'dir',
            'dir_id',
            'dir_ids',
            'dirid',
            'category',
            'cat_id',
            'criteria_id'
        );

        foreach($fields AS $name)
        {
            if(isset($data->params[$name]) && !empty($data->params[$name]) && !is_array($data->params[$name]))
            {
                $data->params[$name] = array_filter(explode(',', $data->params[$name]));
            }
        }
    }
}