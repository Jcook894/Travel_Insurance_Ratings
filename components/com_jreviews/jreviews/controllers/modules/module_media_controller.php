<?php
/**
 * JReviews - Reviews Extension
 * Copyright (C) 2010-2015 ClickFWD LLC
 * This is not free software, do not distribute it.
 * For licencing information visit https://www.jreviews.com
 * or contact sales@jreviews.com
**/

defined( 'MVC_FRAMEWORK') or die( 'Direct Access to this location is not allowed.' );

S2App::import('Controller','common','jreviews');

class ModuleMediaController extends MyController {

    var $uses = array('menu','field','media','user','review');

    var $helpers = array('libraries','html','assets','paginator','form','routes','text','time','community','media');

    var $components = array('config','access','everywhere','media_storage');

    var $autoRender = false;

    var $autoLayout = true;

    var $layout = 'module';

    var $abort = false;

    function beforeFilter()
    {
        # Call beforeFilter of MyController parent class
        parent::beforeFilter();
    }

    // Need to return object by reference for PHP4
    function &getEverywhereModel() {
        return $this->Media;
    }

	function index()
	{
		$joins = array();

        $this->EverywhereAfterFind = true; // Triggers the afterFind in the Observer Model

        if(!isset($this->params['module'])) $this->params['module'] = array(); // For direct calls to the controller

		$module_id = Sanitize::getString($this->params,'module_id',Sanitize::getString($this->data,'module_id'));

		if(empty($this->params))
        {
            $this->params['module'] = cmsFramework::getModuleParams($module_id);
        }

        $ids = $conditions = $joins = $order = array();

		# Read module parameters
        $cat_auto = Sanitize::getInt($this->params['module'],'cat_auto');

		$extension = Sanitize::getString($this->params['module'],'extension');

        $media_type = Sanitize::getString($this->params['module'],'media_type');

		$cat_id = Sanitize::getVar($this->params['module'],'category');

		$listing_id = Sanitize::getString($this->params['module'],'listing');

        $limit = Sanitize::getInt($this->params['module'],'module_limit',5);

        $total = Sanitize::getInt($this->params['module'],'module_total',10);

        $custom_order = Sanitize::getString($this->params['module'],'custom_order');

        $custom_where = Sanitize::getString($this->params['module'],'custom_where');

        $media_by = Sanitize::getString($this->params['module'],'media_by');

		if($extension == 'com_content') {

			$dir_id = Sanitize::getVar($this->params['module'],'dir');

            $criteria_id = Sanitize::getString($this->params['module'],'criteria');
		}
        else {

            $dir_id = null;

            $criteria_id = null;
		}

        # Prevent sql injection
        $token = Sanitize::getString($this->params,'token');

        $tokenMatch = 0 === strcmp($token,cmsFramework::formIntegrityToken($this->params,array('module','module_id','form','data'),false));

        isset($this->params['module']) and $this->viewSuffix = Sanitize::getString($this->params['module'],'tmpl_suffix');

		// This parameter determines the module mode
		$sort = Sanitize::getString($this->params['module'],'media_order');

		# Category auto detect
        if($cat_auto && $extension == 'com_content')
		{
            $ids = CommonController::_discoverIDs($this);

            extract($ids);
        }

		$extension != '' and $conditions[] =  "Media.Extension = " . $this->Quote($extension);

        if($custom_where != '') {

            $custom_where = str_replace('{user_id}',$this->_user->id,$custom_where);
        }

        if(isset($this->Listing) && $this->Listing->extension == $extension)
        {
            $state = 1; // Only published results

            $this->Listing->addCategoryFiltering($conditions, $this->Access, compact('listing_id','cat_auto','extension','state','cat_id','dir_id','criteria_id'));

            $this->Listing->addListingFiltering($conditions, $this->Access, compact('state'));
        }

		# Set conditionals based on configuration parameters
		if($extension == 'com_content')
		{
            if(isset($click2search))
            {
                $query = "
                    SELECT
                        Field.type
                    FROM
                        #__jreviews_fields AS Field
                    WHERE
                        Field.name = " . $this->Quote($click2search['field']);

                $type = $this->Field->query($query, 'loadResult');

                if(in_array($type,array('select','selectmultiple','checkboxes','radiobuttons')))
                {
                    $conditions[] = "Field." . $click2search['field'] . " LIKE " . $this->QuoteLike('*'.$click2search['value'].'*');
                }
                else {

                    $conditions[] = "Field." . $click2search['field'] . " = " . $this->Quote($click2search['value']);
                }
            }

            // If custom where includes conditions for Field model then we add a join to those tables

            if(
                ($custom_where != '' && (strstr($custom_where,'Field.')))
                ||
                isset($click2search)
            ) {
                $joins['Field'] = "LEFT JOIN #__jreviews_content AS Field ON Field.contentid = Media.listing_id";
            }

            // Perform tag replacement for listing_id
            if($custom_where != '' && Sanitize::getString($this->params,'view') == 'article') {

                $curr_listing_id = Sanitize::getInt($this->params,'id');

                $custom_where = str_replace(
                    array('{listing_id}'),
                    array($curr_listing_id),
                    $custom_where);
            }

            $access_levels = $this->Access->getAccessLevels();

            $conditions[] = 'Media.access IN ( ' . $access_levels . ')';

            if($media_by == 'owner') {

                $conditions[] = "Media.user_id = Listing." . EverywhereComContentModel::_LISTING_USER_ID;
            }
            elseif($media_by == 'user') {

                $conditions[] = "Media.user_id != Listing." . EverywhereComContentModel::_LISTING_USER_ID;
            }
		}

		$listing_id and $conditions[] = "Media.listing_id IN ( ". cleanIntegerCommaList($listing_id) .")";

		$conditions[] = 'Media.published > 0 AND Media.approved > 0';

        # Modify query for correct ordering.
        if($tokenMatch and $custom_order) {

            $order[] = $custom_order;
        }
        else {

            switch($sort) {
                case 'recent':
                    $order[] = $this->Media->processSorting('newest');
                    break;
                case 'liked':
                    $order[] = $this->Media->processSorting('liked');
                    break;
                case 'views':
                    $order[] = $this->Media->processSorting('popular');
                    break;
            }
        }

        switch($media_type)
        {
            case 'all':
            break;
            default:
                $conditions[] = 'Media.media_type = ' . $this->Quote($media_type);
            break;
        }

        # Custom WHERE
        $tokenMatch and $custom_where and $conditions[] = '(' . $custom_where . ')';

		$queryData = array(
			'joins'=>$joins,
			'conditions'=>$conditions,
			'order'=>$order,
			'limit'=>$total,
            'extension'=>$extension
		);

        // Makes sure only media for published listings is shown
        $queryData = $this->Everywhere->createUnionQuery($queryData,array('listing_id'=>'Media.listing_id','extension'=>'Media.extension'));

		$media = $this->Media->findAll($queryData);

        $count = count($media);

		# Send variables to view template
		$this->set(
			array(
                'autodetect_ids'=>$ids,
				'entries'=>$media,
				'total'=>$count,
                'limit'=>$limit
				)
		);

        $this->_completeModuleParamsArray();

        $page = empty($media) ? '' : $this->render('modules','media');

        return $page;
	}


    /**
    * Ensures all required vars for theme rendering are in place, otherwise adds them with default values
    */

    function _completeModuleParamsArray()
    {
        $params = array(
            'show_numbers'=>false,
            'summary'=>false,
            'summary_words'=>10,
            'tn_mode'=>'crop',
            'tn_size'=>'100x100',
            'tn_show'=>true,
            'columns'=>1,
            'orientation'=>'horizontal',
            'slideshow'=>false,
            'slideshow_interval'=>6,
            'nav_position'=>'bottom',
            'media_type_icon'=>1,
            'media_by'=>'all',
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

   /**
    * Modifies the query ORDER BY statement based on ordering parameters
    */
     private function __processSorting($selected)
    {
        $order = '';

        switch ( $selected )
        {
            case 'rating':
                $order = 'Totals.user_rating DESC, Totals.user_rating_count DESC';
                $this->Listing->conditions[] = 'Totals.user_rating > 0';
              break;
            case 'rrating':
                $order = 'Totals.user_rating ASC, Totals.user_rating_count DESC';
                $this->Listing->conditions[] = 'Totals.user_rating > 0';
              break;
            case 'reviews':
                $order = 'Totals.user_comment_count DESC';
                $this->Listing->conditions[] = 'Totals.user_comment_count > 0';
              break;
            case 'rdate':
                $order =  $this->Listing->dateKey ? "Listing.{$this->Listing->dateKey} DESC" : false;
            break;
        }

        return $order;
    }

}
