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

// no direct access
defined( 'MVC_FRAMEWORK') or die( 'Direct Access to this location is not allowed.' );

class AdminReviewReportsController extends MyController {
	
	var $uses = array('review_report');
	var $components = array('config');	
	var $helpers = array('html');

	var $autoRender = false;
	var $autoLayout = true;		
		
	function beforeFilter() {
		
		# Call beforeFilter of MyAdminController parent class
		parent::beforeFilter();
		
	}
	
	// Need to return object by reference for PHP4
	function &getNotifyModel() {
		return $this->ReviewReport;
	}	
	
	function index() {

		$this->name = 'review_reports';		
		
		$this->autoRender = true;

		$this->components = array('everywhere');
		$this->__initComponents();

		$conditions = array("Review.`mode` = 'com_content'");

		$reports = $this->ReviewReport->findAll(array('conditions'=>$conditions));
		$this->set(array(
			'stats'=>$this->stats,
			'reports'=>$reports
		));
		 	
	}	
		
	function _delete($params) {
	
		$xajax = new xajaxResponse();
	
		$report_id = (int) $this->data['ReviewReport']['id'];
						
		$table = "#__jreviews_report";
		
		$del_id = 'id';
	
		$this->_db->setQuery("DELETE FROM $table WHERE $del_id = " . $report_id);
		
		if (!$this->_db->query()) {
			$xajax->alert($this->_db->getErrorMsg());
			return $xajax;
		}	
		
		$xajax->call('removeRow','review_report'.$report_id);
		
		return $xajax;
	}
		
}