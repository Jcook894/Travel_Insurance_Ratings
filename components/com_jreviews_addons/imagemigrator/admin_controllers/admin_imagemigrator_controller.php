<?php

defined( 'MVC_FRAMEWORK') or die( 'Direct Access to this location is not allowed.' );

class AdminImagemigratorController extends MyController
{
    var $uses = array('imagemigrator');

    var $helpers = array('html','form');

    var $components = array('config','access');

    var $autoRender = false;

    var $autoLayout = false;


	function beforeFilter()
	{
		$this->Access->init($this->Config);

        parent::beforeFilter();
	}

    function index()
    {
		cmsFramework::clearSessionNamespace('imagemigrator_addon');

		$this->Imagemigrator->createTable();

		$this->set(array(
			'count'=>$this->Imagemigrator->getListingCount(),
			'User'=>$this->_user
		));

        return $this->render('imagemigrator','index');
    }

    function convert()
    {
		$success = $errors = 0;

		$debug_array = array();

		$debug = Sanitize::getInt($this->params, 'debug', 0);

		$limit = Sanitize::getInt($this->params, 'increment', 3);

		$delay_sec = Sanitize::getInt($this->params, 'delay', 0.5) * 1000000;

		$total_before = $this->Imagemigrator->getListingCount();

		// Iterate through listings
		$listings = $this->Imagemigrator->getListingImages($limit);

		$status = array(
			0=>'<span class="jrStatusLabel jrRed">Error</span>',
			1=>'<span class="jrStatusLabel jrGreen">OK</span>',
			2=>'<span class="jrStatusLabel jrOrange">Already Converted</span>',
			3=>'<span class="jrStatusLabel jrOrange">Skipped</span>'
		);

		$this->Config->debug_enable = false;

		foreach($listings AS $listing)
		{
			$listing_id = $listing['id'];

			$title = $listing['title'];

			$files_array = array();

			// Extract image paths
			$images  = preg_replace('/{(.*)}/','',$listing['images'] );

			$images = array_filter(explode("\n",$images),'trim');

			$listing_failed = false;

			$res_success = false;

			if(empty($images)) {

				$res_success = 0;

				$error = 'Listing without images';

				$debug_array[] = "<div>{$listing_id}. ".'<strong>'.htmlentities($title,ENT_QUOTES,'utf-8')."</strong> - status: ".$status[3]." ". $error ."</div>";

				if(!$res_success) {

					$files_array[] = $error;
				}
			}
			else {

				foreach($images as $image)
				{
					$failed = true;

					$msg = $error = '';

					$name_col = explode("|", $image);

					$image = reset($name_col);

					$images_folder = (cmsFramework::getVersion() == 1.5 ? 'images' . DS . 'stories' : 'images');

					$filepath = PATH_ROOT . $images_folder . DS . trim($image);

					$file_exists = file_exists($filepath);

					$res_success = 0;

					$media_exists = $this->Imagemigrator->mediaExists($listing_id, $filepath);

					if($image != '' && $file_exists && !$media_exists)
					{
						$res = $this->uploadMedia($listing, $filepath);

						if(!empty($res)) {

							$res_success = Sanitize::getInt($res,'success');

						}
						else {

							$res_success = 0;

							$error = 'CURL call failed';
						}

						if(!$res_success) {

							$error = 'Upload failed';

							if(!empty($res['str'])) {

								$msg = json_encode($res['str']);
							}
						}
					}
					elseif($media_exists) {

						$res_success = 2;

						$error = 'Already converted';
					}
					elseif($image != '' && !$file_exists) {

						$res_success = 0;

						$error = 'Original not found';
					}

					$debug_array[] = "<div>{$listing_id}. ".'<strong>'.htmlentities($title,ENT_QUOTES,'utf-8')."</strong> - ".htmlentities($images_folder . DS . trim($image),ENT_QUOTES,'utf-8')." - status: ".$status[$res_success]." ". $msg ."</div>";

					if(!$res_success) {

						$files_array[] = $error . ' | ' . $images_folder . DS . trim($image);

					}
				}
			}

			// Add to tracker so we don't process this listing again

			$this->Imagemigrator->complete($listing_id, $title, !$res_success, $files_array);

			// Update listing processing counts

			if(!$res_success) $errors++; else $success++;
		}

		$total_end = $this->Imagemigrator->getListingCount();

		$debug_array = array_reverse($debug_array);

		usleep($delay_sec);

        return cmsFramework::jsonResponse(array(
			'success'=>$success,
			'errors'=>$errors,
			'remaining'=>max(0,$total_before - $limit),
			'debug'=>$debug ? implode($debug_array) : ''
		));
    }

	function popup()
	{
		$this->set(array(
			'total'=>$this->Imagemigrator->getListingCount()
		));

		return $this->render('imagemigrator','popup');
	}

	function reset()
	{
		$query = "
			DELETE FROM #__jreviews_media WHERE media_id IN (

				SELECT id FROM #__jreviews_image_migrator WHERE error = 1
			)
		";

		$this->Imagemigrator->query($query);

		$query = "DELETE FROM #__jreviews_image_migrator WHERE error = 1";

		$this->Imagemigrator->query($query);
	}

	function uploadMedia($listing, $filepath)
	{
		$listing_id = $listing['id'];

		$title = $listing['title'];

		$user_id = $listing['user_id'];

		$extension = 'com_content';

		$url = WWW_ROOT . 'index.php?option=com_jreviews&url=media_upload/_save&format=raw&tmpl=component';

		$_CURL_OPTIONS = array(
			CURLOPT_NOBODY =>1,
			CURLOPT_HEADER=>0,
			CURLOPT_HTTPHEADER=> array('Expect:'), // Fixes curl post data issue with some servers
			CURLOPT_VERBOSE=>0,
			CURLOPT_POST=>1,
			CURLOPT_CONNECTTIMEOUT => 0,
			CURLOPT_RETURNTRANSFER => 1,
			CURLOPT_AUTOREFERER=>1,
			CURLOPT_POST=>1,
			CURLOPT_BINARYTRANSFER=>1,
			CURLOPT_REFERER =>$_SERVER['SERVER_NAME'],
			CURLOPT_USERAGENT=>"Mozilla/4.0 (compatible;)"
		);

		$post = array(
			"qqfile"=>"@" . $filepath,
			"debug"=>0,
			cmsFramework::getCustomToken(Sanitize::getString($this->Config,'cron_secret'))=>1,
			cmsFramework::formIntegrityToken(
				array(
					'listing_id'=>$listing_id,
					'review_id'=>0,
					'extension'=>$extension,
					'session_id'=>$this->_user->id
					),
				array(
					'listing_id'=>'listing_id',
					'review_id'=>'review_id',
					'extension'=>'extension',
					'session_id'=>'session_id'
					),false)=>1,
			'data[Media][listing_id]'=>$listing_id,
			'data[Media][review_id]'=>0,
			'data[Media][extension]'=>$extension,
			'data[Media][title]'=>'',//$title,
			'data[Media][user_id]'=>$user_id,
			'data[session_id]'=>$this->_user->id,
			'data[clean_filename]'=>0,
			'data[override_createdate]'=>1
		);

		$ch = curl_init($url);

		curl_setopt_array($ch, $_CURL_OPTIONS);

		curl_setopt($ch, CURLOPT_POSTFIELDS, $post);

		$response = json_decode(curl_exec($ch),true);

		$curl_info = curl_getinfo($ch);

		curl_close($ch);

		if($curl_info['http_code'] != 200) {
			return false;
		}

		return $response;
	}

	function errors()
	{
		$errors = $this->Imagemigrator->getErrors();

		$this->set('errors',$errors);

		return $this->render('imagemigrator','errors');
	}
}