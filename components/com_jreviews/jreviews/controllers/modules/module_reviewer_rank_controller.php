<?php
/**
 * JReviews - Reviews Extension
 * Copyright (C) 2010-2016 ClickFWD LLC
 * This is not free software, do not distribute it.
 * For licencing information visit https://www.jreviews.com
 * or contact sales@jreviews.com
**/

defined( 'MVC_FRAMEWORK') or die( 'Direct Access to this location is not allowed.' );

class ModuleReviewerRankController extends MyController {

    var $uses = array('menu','review');

    var $components = array('config', 'access', 'everywhere');

    var $helpers = array('routes', 'html', 'community');

    var $autoRender = false;

    var $autoLayout = true;

    var $layout = 'module';

    function beforeFilter() {
        # Call beforeFilter of MyController parent class
        parent::beforeFilter();
    }

    public function getEverywhereModel()
    {
        return $this->Review;
    }

    function index()
    {
        if (!isset($this->params['module']))
        {
            $this->params['module'] = array();
        }

        # Read module parameters

        $limit = Sanitize::getInt($this->params['module'], 'module_limit', 10);

        $total = Sanitize::getInt($this->params['module'], 'module_total', 10);

        $this->viewSuffix = Sanitize::getString($this->params['module'],'tmpl_suffix');

        # Get total number of reviewers
        $reviewer_count = $this->Review->getReviewerTotal();

        # Get user rankings
        $rankings = $this->Review->getRankPage($page = 1, $total);

        $this->set(array(
            'reviewer_count'=>$reviewer_count,
            'rankings'=>$rankings,
            'total' => $total,
            'limit' => $limit
        ));

        $this->_completeModuleParamsArray();

        return $this->render('modules','reviewer_rank');
    }

    /**
     * Ensures all required vars for theme rendering are in place, otherwise adds them with default values.
     */
    public function _completeModuleParamsArray()
    {
        $params = array(
            'show_numbers' => true,
            'tn_show' => true,
            'tn_position' => 'left',
            'columns' => 1,
            'orientation' => 'horizontal',
            'slideshow' => false,
            'slideshow_interval' => 6,
            'nav_position' => 'bottom',
            'custom_link_position'=>'top-right',
            'custom_link_1_url'=>'',
            'custom_link_1_text'=>'',
            'custom_link_2_url'=>'',
            'custom_link_2_text'=>'',
            'custom_link_3_url'=>'',
            'custom_link_3_text'=>''
        );

        $this->params['module'] = array_merge($params, $this->params['module']);
    }
}