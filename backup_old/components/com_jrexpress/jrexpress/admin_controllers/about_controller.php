<?php
/*
    JReviews Express - user reviews for Joomla
    Copyright (C) 2009  Alejandro Schmeichler

    JReviews Express is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    JReviews Express is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/

defined( 'MVC_FRAMEWORK') or die( 'Direct Access to this location is not allowed.' );

class AboutController extends MyController {
	
	var $autoRender = true	;
	var $helpers = array('html');
	var $components = array('config');
	
	function beforeFilter() {
				
		# Call beforeFilter of MyAdminController parent class
		parent::beforeFilter();

	}
		
	function index() {
		
		// Usage and setup stats
		$sql = "SELECT count(*) FROM #__jreviews_categories where `option` = 'com_content'";
		$this->_db->setQuery($sql);
		$stats['categories_content'] = $this->_db->loadResult();
	
		$sql = "SELECT count(*) FROM #__content AS content"
		."\n INNER JOIN #__jreviews_categories AS jr_category ON content.catid = jr_category.id"
		."\n WHERE content.state = 1";
		$this->_db->setQuery($sql);
		$stats['entries_published'] = $this->_db->loadResult();
	
		$sql = "SELECT count(*) FROM #__jreviews_comments AS review"
		."\n WHERE review.author = '0' AND review.published = 1"
		."\n AND review.`mode`='com_content'"
		;
		$this->_db->setQuery($sql);
		$stats['reviews_user_published'] = $this->_db->loadResult();
	
		$sql = "SELECT count(*) FROM #__jreviews_comments AS review"
		."\n WHERE review.author = '1' AND review.published = 1"
        ."\n AND review.`mode`='com_content'"
		;
		$this->_db->setQuery($sql);
		$stats['reviews_editor_published'] = $this->_db->loadResult();
	
		$sql = "SELECT count(*) FROM #__jreviews_comments AS review"
		."\n INNER JOIN #__content AS content ON content.id = review.pid"
		."\n WHERE review.author = '1' AND review.published = 0";
		$this->_db->setQuery($sql);
		$stats['reviews_editor_unpublished'] = $this->_db->loadResult();
	
		$sql = "SELECT count(*) FROM #__jreviews_report AS report"
		."\n INNER JOIN #__jreviews_comments AS review ON review.id = report.reviewid";
		$this->_db->setQuery($sql);
		$stats['reviews_reports'] = $this->_db->loadResult();
	
		$sql = "SELECT count(*) FROM #__jreviews_groups";
		$this->_db->setQuery($sql);
		$stats['group_count'] = $this->_db->loadResult();
	
		$sql = "SELECT count(*) FROM #__jreviews_fields";
		$this->_db->setQuery($sql);
		$stats['field_count'] = $this->_db->loadResult();
		
		// Begin isntallation check
		$msg = array();
		$check = "tick.png";
		$fail = "publish_x.png";
		$xajaxErr = 0;
		$xajaxMsg = array();
		$msg[0]["xajax_install"] = $check;
		$msg[0]["xajax_msg"] = "No problem found with the XAJAX Plugin for Joomla Mambot.";
	
# XAJAX MAMBOT/INSTALL CHECK
		$query = "SELECT published,params FROM #__" ._PLUGIN_DIR_NAME . " WHERE element = 'xajax.system'";
		$this->_db->setQuery($query);
	
		if (!$xajaxBot = $this->_db->loadObjectList()) {
			// XAJAX bot not found in DB = not installed
			$xajaxErr = 1;
			$xajaxMsg[] = "Not in Mambots list";
	
		} else {
	
			// XAJAX bot installed - check if published and encoding
			$xajaxBot = $xajaxBot[0];
	
			// Check if published
			if (!$xajaxBot->published) {
				$xajaxErr = 1;
				$xajaxMsg[] = "Unpublished";
			}
	
			// Check Encoding	
			// Read DB encoding
			$xajaxEncoding = '';
			if (!preg_match("/$xajaxEncoding/", cmsFramework::getCharset())) {
				$xajaxErr = 1;
				$encoding = cmsFramework::getCharset();
				$xajaxMsg[] = "Wrong encoding, correct is <strong>$encoding</strong>";
			}
	
		}
	
		// Check for files
		$xajaxInstalled = (file_exists(PATH_ROOT . _PLUGIN_DIR_NAME . DS . 'system' . DS . 'xajax.system.php') && file_exists(PATH_ROOT . _PLUGIN_DIR_NAME . DS . 'system' . DS . 'xajax.system.xml'));
	
		if (!$xajaxInstalled) {
			$xajaxErr = 1;
			$xajaxMsg[] = "Files not found";
		}
	
		if ($xajaxErr) {
			$msg[0]["xajax_install"] = $fail;
			$msg[0]["xajax_install_err"] = "install-err";
			$msg[0]["xajax_msg"] = implode(" | ",$xajaxMsg);
		}
	
# JREXPRESS MAMBOT/INSTALL CHECK

		$installCheck['jreviews.plugin']['status'] = true;
		$installCheck['jreviews.plugin']['files'] = true;
		$installCheck['jreviews.plugin']['db'] = true;
		$installCheck['jreviews.plugin']['published'] = true;
				
		// Database
		$query = "SELECT id, published FROM #__" ._PLUGIN_DIR_NAME . " WHERE element = 'jrexpress'";
		$this->_db->setQuery($query);
	
		if (!$jReviewsBot = $this->_db->loadObjectList()) {		
				$installCheck['jreviews.plugin']['status'] = false;	
				$installCheck['jreviews.plugin']['db'] = false;	
		} else {
			if (!$jReviewsBot[0]->published) {
				$installCheck['jreviews.plugin']['status'] = false;				
				$installCheck['jreviews.plugin']['published'] = false;
			}
		}
	
		// Files
		if(!file_exists(PATH_ROOT . _PLUGIN_DIR_NAME . DS . 'content' . DS . 'jrexpress.php') && !file_exists(PATH_ROOT . _PLUGIN_DIR_NAME . DS . 'content' . DS . 'jrexpress.xml')) {
			$installCheck['jreviews.plugin']['status'] = false;	
			$installCheck['jreviews.plugin']['files'] = false;
		}
	
				
# DB CONTENT FIELDS CHECK
		$installCheck['listing.fields']['status'] = true;

		$jrcontentDB = $this->_db->getTableFields(array('#__jreviews_content'));
		
		if(!empty($jrcontentDB)){
			$jrcontentColumns = array_keys($jrcontentDB['#__jreviews_content']);
		
			$sql = "SELECT name,type FROM #__jreviews_fields WHERE location = 'content'";
			$this->_db->setQuery($sql);
		
			if ($fieldsDB = $this->_db->loadObjectList('name')) {
		
				foreach ($fieldsDB AS $fieldName) {
					if (!in_array($fieldName->name,$jrcontentColumns)) {
						$installCheck['listing.fields']['status'] = false;
					}
				}
			}
		}
	
# DB REVIEW FIELDS CHECK
		$installCheck['review.fields']['status'] = true;
					
		$jrReviewDB = $this->_db->getTableFields(array('#__jreviews_review_fields'));
				
		if(!empty($jrReviewDB)){
			
			$jrReviewColumns = array_keys($jrReviewDB['#__jreviews_review_fields']);
			
			$sql = "SELECT name,type FROM #__jreviews_fields WHERE location = 'review'";
			$this->_db->setQuery($sql);
		
			if ($fieldsDB = $this->_db->loadObjectList('name')) {
		
				foreach ($fieldsDB AS $fieldName) {
					if (!in_array($fieldName->name,$jrReviewColumns)) {
						$installCheck['review.fields']['status'] = false;
					}
				}
			}
		}
	
# SETUP CHECK	
		// Fields check
		$query = "SELECT count(*) FROM #__jreviews_fields";
		$this->_db->setQuery($query);
		$fields = $this->_db->loadResult();
		$msg[0]["fields"] = $fields ? $check : $fail;
	
		// Criteria check
		$query = "SELECT count(*) FROM #__jreviews_criteria";
		$this->_db->setQuery($query);
		$criteria = $this->_db->loadResult();
		$msg[0]["criteria"] = $criteria ? $check : $fail;
	
		// Directory check
		$query = "SELECT count(*) FROM #__jreviews_directories";
		$this->_db->setQuery($query);
		$dir = $this->_db->loadResult();
		$msg[0]["dir"] = $dir ? $check : $fail;
	
		// Directory check
		$query = "SELECT count(*) FROM #__jreviews_categories";
		$this->_db->setQuery($query);
		$cat = $this->_db->loadResult();
		$msg[0]["cat"] = $cat ? $check : $fail;
	
# GD PHP IMAGE LIBRARY CHECK
		$installCheck['gd.extension']['status'] = function_exists("gd_info");
			
		$this->set(
			array(
				'live_site'=>WWW_ROOT,
				'stats'=>$stats,
				'msg'=>$msg[0],
				'installCheck'=>$installCheck,
				'version'=>Configure::read('System.version'),
				'menu_option'=>'about'
			)
		);
	}
}
