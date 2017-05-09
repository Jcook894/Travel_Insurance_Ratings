<?php
/**
 * JReviews - Reviews Extension
 * Copyright (C) 2010-2015 ClickFWD LLC
 * This is not free software, do not distribute it.
 * For licencing information visit https://www.jreviews.com
 * or contact sales@jreviews.com
**/

defined( 'MVC_FRAMEWORK') or die( 'Direct Access to this location is not allowed.' );

class ModuleTotalsController extends MyController {

    var $uses = array('menu','review');

    var $components = array('config', 'access', 'everywhere');

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
        $module_id = Sanitize::getInt($this->params,'module_id',Sanitize::getInt($this->data,'module_id'));

        $this->viewSuffix = Sanitize::getString($this->params['module'],'tmpl_suffix');

        $cache_file = S2CacheKey('modules_totals_'.$module_id,serialize($this->params['module']));

        $page = $this->cached($cache_file);

        if($page) {
            return $page;
        }

        // Initialize variables

        $extension = Sanitize::getString($this->params['module'],'extension','com_content');

        $dirId = Sanitize::getVar($this->params['module'] , 'dirid', array());

        $excludeDirId = Sanitize::getVar($this->params['module'], 'exclude_dirid', array());

        $access_level = cleanIntegerCommaList(Sanitize::getVar($this->params['module'],'access_level',array(1)));

        $conditions_listings = array();

        // Automagically load and initialize Everywhere Model
        S2App::import('Model','everywhere_'.$extension,'jreviews');

        $listing_class = inflector::camelize('everywhere_'.$extension).'Model';

        $conditions_reviews = array('Review.published = 1');

        if($extension == 'com_content')
        {
            $ListingModel = new $listing_class;

            $ListingModel->_user = $this->_user;

            $ListingModel->addCategoryFiltering($conditions_listings, $this->Access, array('state'=>1, 'access'=>$access_level, 'dir_id' => $dirId));

            $ListingModel->addCategoryFiltering($conditions_reviews, $this->Access, array('state'=>1, 'access'=>$access_level, 'dir_id' => $dirId));

            $ListingModel->addListingFiltering($conditions_listings, $this->Access, array('state'=>1, 'access'=>$access_level));

            if($excludeDirId)
            {
                $ListingModel->excludeDirectoryFiltering($conditions_listings, $excludeDirId);

                $ListingModel->excludeDirectoryFiltering($conditions_reviews, $excludeDirId);
            }
        }

        $extension !='' and $conditions_reviews[] = "Review.mode = " . $this->Quote($extension);

        if($ListingModel)
        {
            $listings = $ListingModel->findCount(array('conditions'=>$conditions_listings),'DISTINCT Listing.'.EverywhereComContentModel::_LISTING_ID);

            $reviews = $this->Review->findCount(array('conditions'=>$conditions_reviews),'DISTINCT Review.id');
        }

        # Send variables to view template
        $this->set(array(
                'listing_count'=>isset($listings) ? $listings : 0,
                'review_count'=>isset($reviews) ? $reviews : 0
        ));

        $page = $this->render('modules','totals');

        # Save cached version
        $this->cacheView('modules','totals',$cache_file, $page);

        return $page;
    }
}