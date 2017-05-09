<?php
/**
 * JReviews - Reviews Extension
 * Copyright (C) 2010-2015 ClickFWD LLC
 * This is not free software, do not distribute it.
 * For licencing information visit https://www.jreviews.com
 * or contact sales@jreviews.com
**/

defined( 'MVC_FRAMEWORK') or die( 'Direct Access to this location is not allowed.' );

class AdminListingsController extends MyController {

	var $name = 'listings';

	var $uses = array('menu','user','criteria','field','jreviews_content','category','predefined_reply','media');

	var $components = array('config','access','everywhere','admin/admin_notifications','media_storage','listings_repository');

	var $helpers = array('html','form','admin/paginator','editor','time','custom_fields','admin/admin_routes','routes');

	var $autoRender = false;

	var $autoLayout = false;

	function beforeFilter() {

		$this->Access->init($this->Config);

		# Call beforeFilter of MyAdminController parent class
		parent::beforeFilter();
	}

    // Need to return object by reference for PHP4
    function &getPluginModel(){
        return $this->Listing;
    }

    // Need to return object by reference for PHP4
    function &getNotifyModel(){
        return $this->Listing;
    }

    function index()
    {
        $this->action = 'browse';

        return $this->browse();
    }

	function browse()
    {
        $listings = array();

        $count = null;

        $filters = Sanitize::getVar($this->params,'filter',array());

        $order = Sanitize::getString($filters,'order','rdate');

        $catId = Sanitize::getInt($filters, 'catid');

        $queryData = $this->Listing->adminBrowseModerationQueryData($filters, $this->Access);

        // Query with filters

        if(!empty($filters))
        {
            $queryOptions = array(
                'userId' => $this->_user->id,
                'catId'  => $catId
            );

            $listings = $this->ListingsRepository
                ->data($queryData)
                ->queryOptions($queryOptions)
                ->orderBy($order)
                ->without('Field, Favorite, PaidOrder')
                ->callbacks('afterFind, plgAfterAfterFind')
                ->limit($this->limit)
                ->offset($this->offset)
                ->many();

            $count = $this->ListingsRepository->count();
        }

        // Query without filters
        else {

            $queryOptions = array(
                'userId' => $this->_user->id
            );

            $listings = $this->ListingsRepository
                            ->data($queryData)
                            ->queryOptions($queryOptions)
                            ->orderBy($order)
                            ->without('Field, Favorite, PaidOrder')
                            ->callbacks('afterFind, plgAfterAfterFind')
                            ->limit(100)
                            ->many();

        }

		# get list of listing owners
		$query = '
            SELECT
                DISTINCT User.' . UserModel::_USER_ID . ' AS value,
                CONCAT( User.' . UserModel::_USER_ALIAS . ' ," (", User.' . UserModel::_USER_REALNAME . ', ")" ) AS text
            FROM
                ' . UserModel::_USER_TABLE . ' AS User
            INNER JOIN
                ' . EverywhereComContentModel::_LISTING_TABLE . ' AS Listing ON User.' . UserModel::_USER_ID . ' = Listing.' . EverywhereComContentModel::_LISTING_USER_ID . '
            ORDER BY
                User.' . UserModel::_USER_ALIAS . '
		';

		$authors = $this->Listing->query($query,'loadObjectList');

        $this->name = 'listings'; // For auto render

		$this->set(
			array(
				'stats'=>$this->stats,
				'User'=>$this->_user,
				'version'=>$this->Config->version,
				'listings'=>$listings,
				'categories'=>$this->Category->getCategoryList(array('disabled'=>false)),
				'authors'=>$authors,
                'filters'=>$filters,
				'pagination'=>array(
					'total'=>$count
				)
			)
		);

        return $this->render('listings','browse');
	}

    function moderation()
    {
        // Begin query setup
        $conditions = array();

        $order = array();

        $total = 0;

        $predefined_replies = array();

        $this->limit = 10;

        $processed = Sanitize::getInt($this->params,'processed');

        $queryData = $this->Listing->adminBrowseModerationQueryData(array(), $this->Access);

        $queryOptions = array(
            'userId' => $this->_user->id
        );

        $listings = $this->ListingsRepository
                        ->data($queryData)
                        ->queryOptions($queryOptions)
                        ->published(0)
                        ->orderBy('latest')
                        ->offset($this->offset)
                        ->limit($this->limit)
                        ->many();

        $total = $this->ListingsRepository->count();

         # Pre-process all urls to sef
        $this->_getListingSefUrls($listings);

        if(!empty($listings))
        {
            $predefined_replies = $this->PredefinedReply->findAll(array(
                'fields'=>array('PredefinedReply.*'),
                'conditions'=>array('reply_type = "listing"')
                ));
        }

        $this->set(array(
            'processed'=>$processed,
            'listings'=>$listings,
            'predefined_replies'=>$predefined_replies,
            'total'=>$total
        ));

        return $this->render('listings','moderation');
    }

	function edit()
	{
        $this->autoRender = false;

        $this->autoLayout = false;

        $this->name = 'listings';

        $this->action = 'create'; // Same view used for create and edit

        if(!$listing_id = Sanitize::getInt($this->params,'id')) return;

        Configure::write('ListingEdit',true); // Read in Fields model for PaidListings integration

        $listing = $this->Listing->findRow(array(
                'fields'=>array('Field.email AS `Listing.email`'),
                'conditions'=>array('Listing.id = ' . $listing_id)
            )
        );

		# Get listing custom fields
		$listing_fields = $this->Field->getFieldsArrayNew($listing['Criteria']['criteria_id'], 'listing', $listing);

        // Clear listing expiration if value is not set
        if($listing['Listing']['publish_down'] == NULL_DATE) {

            $listing['Listing']['publish_down'] = '';
        }

        $categories = $this->Category->getCategoryList(array(
            'disabled'=>true,
            'type_id'=>array(0,$listing['Criteria']['criteria_id']),
            'listing_type'=>true
            ,'dir_id'=>$listing['Directory']['dir_id'] // Shows only categories from the same directory
        ));

        isset($listing['ListingType']) and $this->Config->override($listing['ListingType']['config']);

 		$this->set(
			array(
				'version'=>$this->Config->version,
				'User'=>$this->_user,
				'Config'=>$this->Config,
				'Access'=>$this->Access,
				'listing'=>$listing,
				'listing_fields'=>$listing_fields,
				'categories'=>$categories,
                'referrer'=>Sanitize::getString($this->params,'referrer') ? Sanitize::getString($this->params,'referrer') : 'browse'
			)
		);

        $page = $this->render('listings','create');

        return $page;
	}

    function _saveModeration()
    {
        $this->Listing->isNew = false;

        $callbacks = array('beforeSave','plgAfterSave');

        if($this->Listing->store($this->data,false,$callbacks))
        {
            // Save the admin note
            if(Sanitize::getString($this->data['JreviewsContent'],'listing_note')!='')
            {
                $this->JreviewsContent->store($this->data);
            }

            return cmsFramework::jsonResponse(array('success'=>true));
        }

        return cmsFramework::jsonResponse(array('success'=>true));
    }

	function _save($params)
    {
		$this->autoLayout = false;

		$this->autoRender = false;

		$response = array('success'=>false,'str'=>array());

		$validation = '';

		if($saveAsNew = Sanitize::getInt($this->data,'saveAsNew')) {
            $this->data['Listing']['id'] = 0;
        }

		$listing_id = Sanitize::getInt($this->data['Listing'],'id',0);

        # Listing inputs
        $isNew = $this->Listing->isNew = $listing_id == 0 ? true : false;

        $cat_id = Sanitize::getVar($this->data['Listing'],'catid');

        $clean_cat_ids = array_filter($cat_id);

        $this->data['Listing']['catid'] = is_array($cat_id) ? (int) array_pop($clean_cat_ids) : (int) $cat_id; /*J16*/

        # Get the current listing info before saving
		$listing = $this->Listing->findRow(array('conditions'=>array('Listing.id = ' . $listing_id)),array()/*callbacks*/);

        if($listing['Listing']['user_id'] == 0 && $this->data['Listing']['created_by'] > 0)
        { // Listing owner changed from guest to registered user so we clear guest info
            $this->data['Listing']['created_by_alias'] = '';
            $this->data['Field']['Listing']['email'] = '';
        }
        if(!is_numeric($this->data['Listing']['created_by']))
        {
            unset($this->data['Listing']['created_by']);
        }

        $this->data['timezone'] = 'local';

        $this->data['Listing']['language'] = '*';

        $this->data['Listing']['access'] = 1;

		$this->data['Listing']['catid'] = Sanitize::getInt($this->data['Listing'],'catid');

		$this->data['Listing']['title'] = Sanitize::getString($this->data['Listing'],'title','');

        if ($this->Access->loadWysiwygEditor())
        {
            $this->data['Listing']['introtext'] = html_entity_decode(Sanitize::getVar($this->data['__raw']['Listing'],'introtext'),ENT_QUOTES,cmsFramework::getCharset());

            $this->data['Listing']['fulltext'] = html_entity_decode(Sanitize::getVar($this->data['__raw']['Listing'],'fulltext'),ENT_QUOTES,cmsFramework::getCharset());
        }
        else {

            $this->data['Listing']['introtext'] = Sanitize::stripAll($this->data['Listing'],'introtext','');

            if(isset($this->data['Listing']['fulltext']))
            {
                $this->data['Listing']['fulltext'] = Sanitize::stripAll($this->data['Listing'],'fulltext','');
            } else {
                $this->data['Listing']['fulltext'] = '';
            }
        }

        isset($this->data['Listing']['introtext']) and $this->data['Listing']['introtext'] = str_replace( '<br>', '<br />', $this->data['Listing']['introtext'] );

        isset($this->data['Listing']['fulltext']) and $this->data['Listing']['fulltext'] 	= str_replace( '<br>', '<br />', $this->data['Listing']['fulltext'] );

		# Meta data
		$this->data['Listing']['metadesc'] = Sanitize::getString($this->data['Listing'],'metadesc');

        $this->data['Listing']['metakey'] = Sanitize::getString($this->data['Listing'],'metakey');

        // Title alias handling

        $slug = '';

        $alias = Sanitize::getString($this->data['Listing'],'alias');

        if($alias == '')
        {
            $slug = S2Router::sefUrlEncode($this->data['Listing']['title']);

        }
        else {

            // Alias filled in so we convert it to a valid alias

            $slug = S2Router::sefUrlEncode($alias);
        }

        if(trim(str_replace('-','',$slug)) == '')
        {
            $slug = date("Y-m-d-H-i-s");
        }

        $slug != '' and $this->data['Listing']['alias'] = $slug;

		// Validation of content default input fields
		$this->Listing->validateInput($this->data['Listing']['title'], "title", "text", 'LISTING_VALIDATE_SELECT_CAT', 1);

        # Get criteria info
        $criteria = $this->Criteria->findRow(array(
            'conditions'=>array('Criteria.id =
                (SELECT
                    criteriaid
                FROM
                    #__jreviews_categories
                WHERE
                    id = '.$this->data['Listing']['catid'].' AND `option` = "com_content")
            ')
        ));

        if(!$criteria)
        {
            $response['str'][] = 'LISTING_SUBMIT_CAT_INVALID';

            return cmsFramework::jsonResponse($response);
        }

        $this->data['Criteria']['id'] = $criteria['Criteria']['criteria_id'];

        // June 9, 2016 - Form passed validation when a required field was removed directly from the form
        // Stop tampering with field names - removal of inputs from the form

        $this->Field->addBackEmptyRequiredFields($this->data, 'listing', $this->Access);

		$listing_valid_fields = $this->Field->validate($this->data,'listing',$this->Access);

		$this->Listing->validateErrors = array_merge($this->Listing->validateErrors,$this->Field->validateErrors);

		# Get all validation messages
		$validation = $this->Listing->validateGetErrorArray();

        # Validation failed
        if(!empty($validation))
        {
            $response['str'] = $validation;

            // Transform textareas into wysiwyg editors
            if($this->Access->loadWysiwygEditor())
            {
                $response['editor'] = true;
            }

            return cmsFramework::jsonResponse($response);
        }

		if($isNew)
		{
			$this->data['Listing']['created'] = cmsFramework::dateUTCtoLocal(_CURRENT_SERVER_TIME);

			$this->data['Listing']['created_by'] = $this->_user->id ? $this->_user->id : 0;

			// Check moderation settings
			$this->data['Listing']['state'] = 0;
		}
        else
        {
			$this->data['Listing']['modified'] = cmsFramework::dateUTCtoLocal(_CURRENT_SERVER_TIME);

    		$this->data['Listing']['modified_by'] = $this->_user->id;
		}

        // PUBLISH UP

        $publish_up = cmsFramework::dateUTCtoLocal(_TODAY);

        $publish_up_value = Sanitize::getString($this->data['Listing'],'publish_up');

        if($publish_up_value != '') {

            $publish_up = $publish_up_value;
        }

        $this->data['Listing']['publish_up'] = $publish_up;

        // PUBLISH DOWN

        $publish_down = NULL_DATE;

        $publish_down_value = Sanitize::getString($this->data['Listing'],'publish_down');

        if($publish_down_value != '') {

            $publish_down = $publish_down_value;
        }

        $this->data['Listing']['publish_down'] = $publish_down;

        if(Sanitize::getString($this->data,'referrer')=='moderation')
        {
            $this->data['Listing']['state'] = 0;
        }

		# Save listing
		$savedListing = $this->Listing->store($this->data);

        $listing_id = $this->data['Listing']['id'];

        if(!$savedListing)
        {
            $response['str'][] = 'DB_ERROR';
        }

        // Error on listing save
        if(!empty($response['str']))
        {
            return cmsFramework::jsonResponse($response);
        }

		# Save listing custom fields
        $this->data['Field']['Listing']['contentid'] = $this->data['Listing']['id'];

		$this->Field->save($this->data, 'listing', $isNew, $listing_valid_fields);

        $response['success'] = true;

        if($isNew) {

            $response['isNew'] = true;

            $response['id'] = $this->data['Listing']['id'];

            $response['html'] = $this->index();
        }

        return cmsFramework::jsonResponse($response);
    }

    function update()
    {
        $id = Sanitize::getInt($this->params,'id');

        $row = $this->Listing->findRow(array('conditions'=>array('Listing.id = ' . $id)));

        return cmsFramework::jsonResponse($row);
    }

	function _publish()
    {
        $include_reject_state = true;

	    $result = $this->Listing->publish(Sanitize::getInt($this->params,'id'), $include_reject_state);

		return json_encode($result);
	}

	function _feature()
    {
        $result = $this->Listing->feature(Sanitize::getInt($this->params,'id'));

        return json_encode($result);
	}

    function _delete()
    {
        $response = array('success'=>false,'str'=>array());

        $ids = Sanitize::getVar($this->params,'cid');

        if(empty($ids)) {

            return cmsFramework::jsonResponse($response);
        }

		# Delete listing and all associated records
		$deleted = $this->Listing->del($ids);

        if ($deleted) {
        	$response['success'] = true;
		}

		return cmsFramework::jsonResponse($response);
	}

	function _changeAccess()
    {
        $data = array();

        $max = 3;

        $min = 1;

		$id = Sanitize::getInt($this->params,'id');

        if(!$id) return json_encode(array('success'=>false));

		$state = $this->Listing->query("SELECT access FROM #__content WHERE id = " . $id, 'loadResult');


        $state++; // Increase by one for next one

        $state > $max and $state = $min;

        $data['Listing']['id'] = $id;

        $data['Listing']['access'] = $state;

		if (!$this->Listing->store($data) )
        {
            return json_encode(array('success'=>false));
		}
        else
        {
			// Clear cache
			clearCache('', 'views');
			clearCache('', '__data');
            return json_encode(array('success'=>true,'state'=>$state));
        }
	}
}
