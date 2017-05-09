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

class InstallController extends MyController {
	
	var $helpers = array('html');
	
	var $autoRender = false;
	var $autoLayout = false;
	var $layout = 'empty';
			
	# Run right after component installation
	function index($params) {
		
		$this->autoLayout = true;
		$this->autoRender = true;
		$this->name = 'install';
		
		# Create database tables	
		// Start db upgrade logic
		$action = array();
		$action['db_install'] = true;
		$tables = $this->_db->getTableList();
		$dbprefix = cmsFramework::getConfig('dbprefix');
		$old_build = 0;

		if(is_array($tables) && in_array($dbprefix . 'jreviews_categories',array_values($tables))) {

			// Tables exist so we check the current build and upgrade accordingly, otherwise it's a clean install and no upgrade is necessary
			$query = "SELECT value FROM #__jreviews_config WHERE id = 'version'";
			$this->_db->setQuery($query);
			$old_version = trim(strip_tags($this->_db->loadResult()));

			if($old_version!='') {
				$match = preg_match('/^[0-9]{1}.[0-9]{1}.[0-9]{1}.([0-9].)/',$old_version,$matches);
				if($match) {
					$old_build = $matches[1];
				}
			}
			
			// Get current version number
			$xml = file(S2Paths::get('jrexpress', 'S2_CMS_ADMIN') . 'jrexpress.xml');
				
			foreach($xml AS $xml_line) {
				if(strstr($xml_line,'version')) {
					$new_version = trim(strip_tags($xml_line));
					continue;
				}
			}
			
			preg_match('/^[0-9]{1}.[0-9]{1}.[0-9]{1}.([0-9].)/',$new_version,$matches);
			 
			$new_build = isset($matches[1]) ? $matches[1] : 0;
			
//			echo $old_build . '<br/>' . $new_build; exit;
			
			if($new_build > $old_build) 
			{
				$i = $old_build+1;
				for($i = $old_build+1; $i<=$new_build; $i++) {

					$sql_file = S2Paths::get('jrexpress', 'S2_CMS_ADMIN') . 'upgrade_build'.$i.'.sql';

					if(file_exists($sql_file)) {

						$action['db_install'] = $this->__parseMysqlDump($sql_file,$dbprefix) && $action['db_install'];
					}

				}
			}
			
		} else {

			// It's a clean install so we use the whole sql file
			$sql_file = S2Paths::get('jrexpress', 'S2_CMS_ADMIN') . 'jrexpress.sql';
	
			$action['db_install'] = $this->__parseMysqlDump($sql_file,$dbprefix);

		}				
		
		# Update component id in pre-existing menus
		$query = "SELECT id FROM #__components WHERE admin_menu_link = 'option=".S2Paths::get('jrexpress','S2_CMSCOMP')."'";
		$this->_db->setQuery($query);
		$id = $this->_db->loadResult();
		
		if($id) {
			$query = "UPDATE `#__menu` SET componentid = $id WHERE type IN ('component','components') AND link = 'index.php?option=".S2Paths::get('jrexpress','S2_CMSCOMP')."'";
			$this->_db->setQuery($query);
			$this->_db->query();		
		}
		
		$result = false;
		
		// Install plugin
		$package = PATH_ROOT . 'administrator' . DS . 'components' . DS . 'com_jrexpress' . DS . 'jrexpress.plugin.s2';
		$target = PATH_ROOT . _PLUGIN_DIR_NAME . DS . 'content';
		$target_file = $target . DS . 'jrexpress.php';
		$first_pass = false;	
		if(getCmsVersion() == CMS_JOOMLA15) {
	
			jimport( 'joomla.filesystem.file' );
			jimport( 'joomla.filesystem.folder' );
			jimport( 'joomla.filesystem.archive' );
			jimport( 'joomla.filesystem.path' );
	
			$adapter = & JArchive::getAdapter('zip');
			$result = $adapter->extract ( $package, $target );
		}
		
		if(getCmsVersion() != CMS_JOOMLA15 || (getCmsVersion() == CMS_JOOMLA15 && !file_exists($target_file))) {
			
			require_once ( PATH_ROOT . 'administrator' . DS . 'includes' . DS . 'pcl' . DS . 'pclzip.lib.php');
			require_once (PATH_ROOT . 'administrator' . DS . 'includes' . DS . 'pcl' . DS . 'pclerror.lib.php');
			
			$extract = new PclZip ( $package );
			
			if ((substr ( PHP_OS, 0, 3 ) == 'WIN')) {
				if(!defined('OS_WINDOWS')) define('OS_WINDOWS',1);
			} else {
				if(!defined('OS_WINDOWS')) define('OS_WINDOWS',0);
			}
					
			$result = $extract->extract ( PCLZIP_OPT_PATH, $target );
			
		}	
						
		if (!file_exists($target_file)) 
		{
			$action['plugin_install'] = false;

		} else {
			
			$action['plugin_install'] = true;
			
			// Add/create plugin db entry
			$query = "SELECT id FROM #__" . _PLUGIN_DIR_NAME . " WHERE element = 'jrexpress' AND folder = 'content'";
			
			$this->_db->setQuery($query);
			
			$result = $this->_db->loadResult();
						
			if (!$result || empty($result)) {

				$query = "INSERT INTO #__" . _PLUGIN_DIR_NAME . " (`name`, `element`, `folder`, `access`, `ordering`, `published`, `iscore`, `client_id`, `checked_out`, `checked_out_time`, `params`) "
				. "\n VALUES ('JReviews Express Comment Plugin', 'jrexpress', 'content', 0, 0, 1, 0, 0, 0, '0000-00-00 00:00:00', '');";
				$this->_db->setQuery($query);
				if($this->_db->query()) {
					$action['plugin_install'] = true;
				} else {
					$action['plugin_install'] = false;
				}
			}
		}
		
		# Create image upload and thumbnail folders
		if(!is_dir(PATH_ROOT . 'images' . DS . 'stories' . DS . 'jreviews' . DS) && getCmsVersion() == CMS_JOOMLA15) {
			
				$Config = new JConfig();
	
				if(isset($Config->ftp_enable) && $Config->ftp_enable) {
										
					// set up basic connection
					$conn_id = ftp_connect($Config->ftp_host,$Config->ftp_port);
				
					// login with username and password
					$login_result = ftp_login($conn_id, $Config->ftp_user, $Config->ftp_pass);
					
					ftp_chdir($conn_id,$Config->ftp_root);
					
					ftp_mkdir($conn_id, 'images' . DS . 'stories' . DS . 'jreviews');
	
					ftp_mkdir($conn_id, 'images' . DS . 'stories' . DS . 'jreviews' . DS . 'tn');

					ftp_close($conn_id);
					
					@copy(PATH_ROOT . 'images' . DS. 'stories' . DS . 'index.html', PATH_ROOT . 'images' . DS . 'stories' . DS . 'jreviews' . DS . 'index.html');
					@copy(PATH_ROOT . 'images' . DS. 'stories' . DS . 'index.html', PATH_ROOT . 'images' . DS . 'stories' . DS . 'jreviews' . DS . 'tn' . DS . 'index.html');
					
				}
			
		} 		
		
		if (!is_dir(PATH_ROOT . 'images' . DS . 'stories' . DS . 'jreviews' . DS) ) {			
			
			$result = mkdir(PATH_ROOT .'images' . DS . 'stories' . DS . 'jreviews' . DS , 0777);
			
			if (!$result) {

				$action['thumbnail_dir'] = false;
			
			} else {
				
				$result = mkdir(PATH_ROOT . 'images' . DS . 'stories' . DS . 'jreviews' . DS . 'tn', 0777);

				if (!$result) {
					$action['thumbnail_dir'] = false;
				
				} else {
					@copy(PATH_ROOT . 'images' . DS. 'stories' . DS . 'index.html', PATH_ROOT . 'images' . DS . 'stories' . DS . 'jreviews' . DS . 'index.html');
					@copy(PATH_ROOT . 'images' . DS. 'stories' . DS . 'index.html', PATH_ROOT . 'images' . DS . 'stories' . DS . 'jreviews' . DS . 'tn' . DS . 'index.html');
				}
			}
		} 
		
		if(is_dir(PATH_ROOT . 'images' . DS . 'stories' . DS . 'jreviews' . DS)){
			$action['thumbnail_dir'] = true;
		}
		
		$this->set(array(
			'action'=>$action
		));
		
					
	}
	
	function __parseMysqlDump($url,$prefix)
	{		
		$file_content = file($url);
		$sql = array();

		foreach($file_content as $sql_line){
			 if(trim($sql_line) != '' && strpos($sql_line, '--') === false){
			 	$sql[] =  str_replace('#__',$prefix,$sql_line);
			 }
		}
		
		$sql = implode('',$sql);
		
		$sql = explode(';',$sql);
		
		$result = true;
		
		foreach($sql AS $sql_line) 
		{	
			if(trim($sql_line) != '' && trim($sql_line) != ';') {
				$sql_line .= ';';
				$this->_db->setQuery($sql_line);			
				$out = $this->_db->query();
				if(false!==strpos($this->_db->getErrorMsg(),"Can't DROP") || false!==strpos($this->_db->getErrorMsg(),"Duplicate key name")) {
//					echo '<br />';
//					echo $this->_db->getErrorMsg();
//					echo '<p>'.$sql_line.'</p>';
//					echo (int)$result . '<br />';					
				} else {
//					echo '<br />xxxxxxxxxxxxxxxxxx';
//					echo $this->_db->getErrorMsg();
//					echo '<p>'.$sql_line.'</p>';
//					echo (int)$result . '<br />';					
					$result = $out && $result;
				}
			}
		}
		
		return $result;
		
	}
	  	
	# Tools to fix installation problems any time
	function _installfix() {
	
		// Load fields model
//		App::import('Model','field','jrexpress');
//		$FieldModel = new FieldModel();
		
		$task = Sanitize::getString($this->data,'task');

		$msg = '';
		$mambot_error = 0;

		switch($task) {
					
			case 'fix_install_jreviews':
	
				$query = "SELECT id,published FROM #__"._PLUGIN_DIR_NAME." WHERE element = 'jrexpress' AND folder = 'content'";
				$this->_db->setQuery($query);
	
				$jReviewsMambot = $this->_db->loadObjectList(); 
				
				if (!$jReviewsMambot || empty($jReviewsMambot)) {
					// Install in DB
					$this->_db->setQuery( "INSERT INTO #__"._PLUGIN_DIR_NAME." (`name`, `element`, `folder`, `access`, `ordering`, `published`, `iscore`, `client_id`, `checked_out`, `checked_out_time`, `params`) VALUES ('JReviews Express Comment Plugin', 'jrexpress', 'content', 0, 0, 1, 0, 0, 0, '0000-00-00 00:00:00', '');");
					$this->_db->query();
	
				} else {
					// Publish
					$jReviewsMambot = $jReviewsMambot[0];
					if (!$jReviewsMambot->published) {
						$this->_db->setQuery("UPDATE #__"._PLUGIN_DIR_NAME." SET published = '1' WHERE id='{$jReviewsMambot->id}'");
						$this->_db->query();
					}
	
				}
	
				if (!file_exists(PATH_ROOT . _PLUGIN_DIR_NAME . DS . 'content' . DS . 'jrexpress.php')) {
					
					$package = PATH_ROOT . 'administrator' . DS . 'components' . DS . 'com_jrexpress' . DS . 'jrexpress.plugin.s2';
					$target = PATH_ROOT . _PLUGIN_DIR_NAME . DS . 'content';
					$target_file = $target . DS . 'jrexpress.php';
						
					if(getCmsVersion() == CMS_JOOMLA15) {
				
						jimport( 'joomla.filesystem.file' );
						jimport( 'joomla.filesystem.folder' );
						jimport( 'joomla.filesystem.archive' );
						jimport( 'joomla.filesystem.path' );
				
						$adapter = & JArchive::getAdapter('zip');
						$result = $adapter->extract ($package, $target);
									
					}
					
					if(!file_exists($target_file)) {
						
						require_once ( PATH_ROOT . 'administrator' . DS . 'includes' . DS . 'pcl' . DS . 'pclzip.lib.php');
						require_once (PATH_ROOT . 'administrator' . DS . 'includes' . DS . 'pcl' . DS . 'pclerror.lib.php');
						
						$extract = new PclZip ( $package );
						
						if ((substr ( PHP_OS, 0, 3 ) == 'WIN')) {
							if(!defined('OS_WINDOWS')) define('OS_WINDOWS',1);
						} else {
							if(!defined('OS_WINDOWS')) define('OS_WINDOWS',0);
						}
								
						$result = $extract->extract ( PCLZIP_OPT_PATH, $target );
						
					}
					
					if (!$result) {
						$mambot_error = true;
					} else {
						$mambot_error = false;
					}
				}
	
				if ($mambot_error) {
			    	$msg = "It was not possible to copy the mambot/plugin files. Make sure the /"._PLUGIN_DIR_NAME."/content folder is writable and try again.";
			    }
	
				break;
		
			case 'fix_content_fields':
	
				$output = '';
				$rows = $this->_db->getTableFields(array('#__jreviews_content'));
				$columns = array_keys($rows['#__jreviews_content']);
	
				$sql = "SELECT name,type FROM #__jreviews_fields WHERE location = 'content'";
				$this->_db->setQuery($sql);
				$fields = $this->_db->loadObjectList('name');
	
				$missing = array();
				
				foreach ($fields AS $field) {
					if (!in_array($field->name,$columns)) {
						$output = $FieldModel->addTableColumn($field->name,$field->type,'content');
					}
				}
				
				$query = "DELETE FROM #__jreviews_fields WHERE name = ''";
				$this->_db->setQuery($query);
				$output = $this->_db->query();
	
				if ($output != '') {
					$msg = "There was a problem fixing one or more of the content fields";
				}
	
				break;
	
			case 'fix_review_fields':
	
				$output = '';
				$rows = $this->_db->getTableFields(array('#__jreviews_review_fields'));
				$columns = array_keys($rows['#__jreviews_review_fields']);
	
				$sql = "SELECT name,type FROM #__jreviews_fields WHERE location = 'review'";
				$this->_db->setQuery($sql);
				$fields = $this->_db->loadObjectList('name');
	
				$missing = array();
				foreach ($fields AS $field) {
					if (!in_array($field->name,$columns)) {
						$output = $FieldModel->addTableColumn($field->name,$field->type,'review');
					}
				}
	
				$query = "DELETE FROM #__jreviews_fields WHERE name = ''";
				$this->_db->setQuery($query);
				$output = $this->_db->query();
								
				if ($output != '') {
					$msg = "There was a problem fixing one or more of the review fields";
				}
	
				break;
	
			default:
				break;
	
		}
	
		cmsFramework::redirect("index2.php?option=com_jrexpress",$msg);
	}	
	
}