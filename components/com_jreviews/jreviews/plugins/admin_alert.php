<?php
/**
 * JReviews - Reviews Extension
 * Copyright (C) 2010-2015 ClickFWD LLC
 * This is not free software, do not distribute it.
 * For licencing information visit https://www.jreviews.com
 * or contact sales@jreviews.com
**/

defined( 'MVC_FRAMEWORK') or die( 'Direct Access to this location is not allowed.' );

class AdminAlertComponent extends S2Component {

    var $name = 'admin_alert';

    var $config;

    var $model;

    var $published = true;

    function startup(&$controller)
    {
    	if(!defined('MVC_FRAMEWORK_ADMIN') || $controller->ajaxRequest) return;

    	if($controller->name == 'about')
    	{
    		$this->model = new S2Model;

            $this->config = $controller->Config;

            $this->newRatingsTableCheck();

            $this->rapidLauncherCheck();
    	}
    }

    function newRatingsTableCheck()
    {
        // If install incomplete abort

        $query = 'SHOW TABLES LIKE "%jreviews_config"';

        if(!$this->model->query($query,'loadResult')) return;

        // If missing ratings table, show the admin alert

        $query = 'SHOW TABLES LIKE "%jreviews_review_ratings"';

        if(!$this->model->query($query,'loadResult')):
        ?>
        <div class="jrWarning" style="margin-top:20px;font-size: 14px;color:red;">
            <strong>Go to the Remote Install &amp; Updates page and install the <u>Ratings Migrator Add-on</u> which will assist you in migrating your site data to the new structure used JReviews 2.6.</strong>
        </div>
        <?php
        endif;
    }

    function rapidLauncherCheck()
    {
        if(Sanitize::getString($this->config, 'alert_rapidlauncher')) return;

        $versionCheck = version_compare(PHP_VERSION, '5.5.0', '>=');

        ?>
        <div class="jrInfo" style="margin-top:20px;font-size: 14px;">
            <p>If this is your first JReviews installation check out the free <strong>RapidLauncher Add-on</strong>. With one-click you can setup directories like the ones we have on the demo site.</p>
            <p>
                <?php if($versionCheck):?>
                <a href="javascript:void(0)" onclick="jreviews.menu.load('admin_updater', 'index');" class="jrButton jrBlue">Install the add-on</a>
                <?php else:?>
                <span class="jrStatusLabel jrRed" style="font-size:14px;text-shadow:none;text-transform:none;">Requires PHP 5.5+</span>
                <?php endif;?>
                <a href="javascript:void(0)" onclick="jQuery(this).closest('div').hide(); jreviews.dispatch({method: 'post', controller: 'admin/configuration', action: '_updateOne', data: {key:'alert_rapidlauncher', value: 1}}); return false;" class="jrButton">Don't show again</a>
            </p>

        </div>
        <?php
    }
}