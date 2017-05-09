<?php
/**
 * JReviews - Reviews Extension
 * Copyright (C) 2010-2015 ClickFWD LLC
 * This is not free software, do not distribute it.
 * For licencing information visit https://www.jreviews.com
 * or contact sales@jreviews.com
**/

// no direct access
defined( 'MVC_FRAMEWORK') or die( 'Direct Access to this location is not allowed.' );

class AdminMediaController extends MyController {

	var $uses = array('user','menu','acl','media','media_encoding','predefined_reply','review');

	var $helpers = array('html','form','text','time','admin/admin_settings','admin/admin_routes','routes','admin/paginator','media','widgets');

	var $components = array('config','access','media_storage','everywhere','admin/admin_notifications');

	var $autoRender = false;

	var $autoLayout = false;

    function getNotifyModel(){
        return $this->Media;
    }

	function beforeFilter()
    {
		$this->name = 'media';

		$this->Access->init($this->Config);

		if($media_id = Sanitize::getString($this->params,S2_QVAR_MEDIA_CODE,null)) {

			$this->params['media_id'] = $this->data['media_id'] = s2alphaID($media_id,true,5,cmsFramework::getConfig('secret'));
		}

		# Call beforeFilter of MyAdminController parent class
		parent::beforeFilter();
	}

    function getEverywhereModel() {
        return $this->Media;
    }

    function getPluginModel() {
        return $this->Media;
    }

    function index() {

        $this->action = 'browse';

        return $this->browse();
    }

	function browse()
	{
		$total = 0;

		$conditions = $extensions = $components = array();

		if ($this->EverywhereAddon) {

			$extension = Sanitize::getString($this->params, 'extension');

		} else {

			$extension = 'com_content';
		}


		if($this->EverywhereAddon)
        {
			// Get component list and generate select array
            $query = "
                SELECT
                    DISTINCTROW `extension`
                FROM
                    #__jreviews_media
            ";

            $media_extensions = $this->Media->query($query, 'loadColumn');

            foreach($media_extensions AS $key=>$val)
            {
                S2App::import('Model','everywhere_'.$val) and $val != 'com_content' and $components[] = array('value'=>$val,'text'=>inflector::camelize(str_replace('com_','',$val)));
            }
		}

		$extensions = $components;

		$filter_entry_title =  Sanitize::getString($this->params, 'entry_title');

		$filter_order =  Sanitize::paranoid(Sanitize::getString($this->params, 'filter_order', 0 ));

		$filter_media_type =  Sanitize::paranoid(Sanitize::getString($this->params, 'filter_media_type', ''));

		$filter_media_location =  Sanitize::paranoid(Sanitize::getString($this->params, 'filter_media_location', ''));

		$filter_listing_id = Sanitize::getInt($this->params,'listing_id');

		$filter_review_id = Sanitize::getInt($this->params,'review_id');

		$filter_user = Sanitize::getInt($this->params,'user_id');

        $conditions['published'] = "Media.published >= -1";

		switch($filter_order) {
			case 'created':
                unset($conditions['published']);
	   			$order[] = "Media.id DESC";
			break;
			case 'published':
	   			$conditions['published'] = "Media.published = 1";
	   			$order[] = "Media.id DESC";
			break;
			case 'unpublished':
	   			$conditions['published'] = "Media.published = 0";
	   			$order[] = "Media.id DESC";
			break;
			case 'rejected':
                unset($conditions['published']);
	   			$conditions[] = "Media.approved = -2";
	   			$order[] = "Media.id DESC";
			break;
			default:
	   			$order[] = "Media.id DESC";
			break;
		}

		$filter_media_type and $conditions[]  = 'Media.media_type = ' . $this->Quote($filter_media_type);

		if($filter_media_location == 'review') {

			$conditions[] = 'Media.listing_id > 0 AND Media.review_id > 0';
		}
		elseif($filter_media_location == 'listing') {

			$conditions[] = 'Media.listing_id > 0 AND Media.review_id = 0';
		}

		$filter_user and $conditions[] = 'Media.user_id = ' . $filter_user;

		if($extension!='') {
			$conditions[] = 'Media.extension = ' . $this->Quote($extension);
		}

		$conditions[] = 'Media.media_type IS NOT NULL';

		// Process search & filtering options
		if ($filter_entry_title != '') {

			// Find all entry ids matching the title search

			if ($extension == 'com_content')
            {
				$query = "
					SELECT
						" . EverywhereComContentModel::_LISTING_ID . "
					FROM
						" . EverywhereComContentModel::_LISTING_TABLE . "
					WHERE
						" . EverywhereComContentModel::_LISTING_TITLE . " LIKE " . $this->QuoteLike(trim($filter_entry_title));

				if($filter_listing_id = $this->Media->query($query,'loadColumn'))
				{
				    $filter_listing_id = implode(",",$filter_listing_id);

					if(is_numeric($filter_listing_id)) {

						$this->params['listing_id'] = $filter_listing_id;
					}
                }
            }
			else {

				// Title search for everywhere extensions
			}
		}
		elseif ($extension == 'com_content' && $filter_listing_id) {

			$query = "
				SELECT
					" . EverywhereComContentModel::_LISTING_TITLE . "
				FROM
					" . EverywhereComContentModel::_LISTING_TABLE . "
				WHERE
					" . EverywhereComContentModel::_LISTING_ID . " = " . $filter_listing_id;

			$filter_entry_title  = $this->Media->query($query,'loadResult');
		}
		elseif ($extension == 'com_content' && $filter_review_id) {

			$query = "
				SELECT
					Listing." . EverywhereComContentModel::_LISTING_TITLE . "
				FROM
					#__jreviews_comments AS Review
				LEFT JOIN
					" . EverywhereComContentModel::_LISTING_TABLE . " AS Listing ON Listing." . EverywhereComContentModel::_LISTING_ID . " = Review.pid
				WHERE
					Review.pid = " . $filter_review_id;

			$filter_entry_title = $this->Media->query($query,'loadResult');
		}

		if (isset($filter_review_id) && $filter_review_id) {
			$conditions[] = "Media.review_id IN ($filter_review_id)";
		}

		if (isset($filter_listing_id) && $filter_listing_id) {
			$conditions[] = "Media.listing_id IN ($filter_listing_id)";
		}

		$this->EverywhereAfterFind = true;

		$media = $this->Media->findAll(array(
            'fields'=>array(
                'Review.title AS `Review.title`'
            ),
			'joins'=>array(
				'LEFT JOIN #__jreviews_comments AS Review ON Media.review_id = Review.id'
			),
			'conditions'=>$conditions,
            'offset'=>$this->offset,
            'limit'=>$this->limit,
            'order'=>array('Media.created DESC')
		));

		if(!empty($media)) {

			$total = $this->Media->findCount(array('conditions'=>$conditions));
		}

		$this->set(array(
			'media'=>$media,
			'total'=>$total,
			'filter_order'=>$filter_order,
			'filter_media_type'=>$filter_media_type,
			'filter_media_location'=>$filter_media_location,
			'extension'=>$extension,
			'extensions'=>$extensions,
			'entry_title'=>$filter_entry_title,
			'pagination'=>array('total'=>$total),
			'accessLevels'=>$this->Acl->getAccessLevelList()
		));

		return $this->render('media','browse');
	}

	function download()
	{
		$file_not_found = '<script>window.parent.jQuery.jrAlert(window.parent.jreviews.__t("XMEDIA_DOWNLOAD_NOT_FOUND"));</script>';

		$media_id = Sanitize::getString($this->params,'media_id');

		if(!$media_id)
		{
			die($file_not_found);
		}

		$this->EverywhereAfterFind = true;

		$media = $this->Media->findRow(array('conditions'=>array(
			'Media.media_id = ' . $media_id,
			'Media.media_type = "attachment"'
		)));

		$res = $this->MediaStorage->download($media);

		if($res === false) {

			die($file_not_found);
		}
	}

    function _delete()
    {
        $response = array('success'=>false,'str'=>array());

        $ids = Sanitize::getVar($this->params,'cid');

        if(empty($ids)) {

            return cmsFramework::jsonResponse($response);
        }

		$deleted = $this->Media->del($ids);

        if ($deleted) {
        	$response['success'] = true;
		}

		return cmsFramework::jsonResponse($response);
	}

	function _deleteThumb()
	{
        $response = array('success'=>false,'str'=>array());

        $id = Sanitize::getVar($this->params,'id');

        $size = Sanitize::getVar($this->params,'size');

        if(empty($id)) {

            return cmsFramework::jsonResponse($response);
        }

		# Delete media thumb
		$deleted = $this->Media->delThumb($id, $size);

        if ($deleted) {
        	$response['success'] = true;
		}

		return cmsFramework::jsonResponse($response);
	}

	function edit()
	{
		$this->autoRender = false;

		$this->autoLayout = false;

		$mediaEncoding = array();

		$media_id = Sanitize::getInt($this->params,'id');

		$media = $this->Media->findRow(array(
			'conditions'=>array(
				'Media.media_id = ' . $media_id
			)
		));

		if(in_array($media['Media']['media_type'],array('video','audio')) && $media['Media']['embed'] == '') {

			$mediaEncoding = $this->MediaEncoding->findRow(array(
				'conditions'=>array('MediaEncoding.media_id = ' . $media_id)
				));

		}

		$this->set(array(
			'accessLevels'=>$this->Acl->getAccessLevelList(),
			'media'=>$media,
			'mediaEncoding'=>$mediaEncoding
		));

		return $this->render('media','edit_form');
	}

	function config()
	{
		$this->set(
			array(
				'Config'=>$this->Config
			)
		);

        return $this->render();
	}


	function moderation()
	{
        $media = array();

        $predefined_replies = array();

		$total = 0;

        $processed = Sanitize::getInt($this->params,'processed');

		$this->EverywhereAfterFind = true;

		$conditions = array('Media.approved = 0');

		$conditions[] = 'Media.media_type IS NOT NULL';

		$media = $this->Media->findAll(array(
            'fields'=>array(
                'Review.title AS `Review.title`'
            ),
			'joins'=>array(
				'LEFT JOIN #__jreviews_comments AS Review ON Media.review_id = Review.id'
			),
			'conditions'=>$conditions,
            'offset'=>$this->offset,
            'limit'=>$this->limit,
            'order'=>array('Media.created DESC')
		));

		if(!empty($media)) {

			# Pre-process all urls to sef
			$this->_getListingSefUrls($media);


			$predefined_replies = $this->PredefinedReply->findAll(array(
                'fields'=>array('PredefinedReply.*'),
                'conditions'=>array('reply_type = "media"','reply_subject <> ""')
                ));

			$total = $this->Media->findCount(array('conditions'=>$conditions));
		}

		$this->set(array(
            'processed'=>$processed,
			'media'=>$media,
			'predefined_replies'=>$predefined_replies,
			'total'=>$total
		));

		return $this->render('media','moderation');
	}

    function update()
    {
        $id = Sanitize::getInt($this->params,'id');

        $row = $this->Media->findRow(array('conditions'=>array('Media.media_id = ' . $id)));

        return cmsFramework::jsonResponse($row);
    }

	function _save()
	{
		$response = array('success'=>false,'str'=>array());

		$media_id = Sanitize::getInt($this->data['Media'],'media_id');

		if($media_id) {

			$this->Media->store($this->data);

			$response['success'] = true;

			return cmsFramework::jsonResponse($response);
		}
	}

    function _saveModeration()
    {
        $response = array('success'=>false,'str'=>array());

		$this->data['Media']['approved'] == 1 and $this->data['Media']['published'] = 1;

		$this->data['Media']['modified'] = _CURRENT_SERVER_TIME;

		$this->data['finished'] = true; // Read in plugins

		$this->data['moderation'] = true;

		if($this->Media->store($this->data))
        {
        	$response['success'] = true;
        }

		return cmsFramework::jsonResponse($response);
    }

	function _saveConfig()
	{
		if(Sanitize::getString($this->data['Config'],'media_encode_transfer_password') == '') {

			unset($this->data['Config']['media_encode_transfer_password']);
		}

		$this->Config->store($this->data['Config']);
	}

	function setMainMedia()
	{
		$media_id = Sanitize::getInt($this->params,'media_id');

		$media = $this->Media->findRow(array('conditions'=>array('Media.media_id = ' . $media_id)));

		if($this->Media->setMainMedia($media)) {
			return cmsFramework::jsonResponse(array('success'=>true));
		}

		return cmsFramework::jsonResponse(array('success'=>false));
	}
}