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

class ReviewReportsController extends MyController {
	
	var $uses = array('review_report','review','listing','criteria','menu');
	
	var $helpers = array('libraries','html','form');
	
	var $components = array('config','access');

	var $autoRender = false;

	var $autoLayout = false;

	function beforeFilter() {
				
		# Init Access
		$this->Access->init($this->Config);
		
		# Set Theme	
		$this->viewTheme = $this->Config->template;		
	}
	
	// Need to return object by reference for PHP4
	function &getNotifyModel() {
		return $this->ReviewReport;
	}	
					
	function create($params)
	{
		$this->autoRender = false;

		$reviewid = Sanitize::getInt($this->params,'id');	

		$this->set(array('review_id'=>$reviewid));
		
		return $this->render('review_report','create');
		
	}
	
	function _save($params) 
	{		
					
		$this->components = array('security');
		$this->__initComponents();
		
		$xajax = new xajaxResponse();
		
		# Validate form token
		if(isset($this->invalidToken)) {
			$xajax->assign("jr_ReviewReportSubmit","disabled",true);
			$xajax->alert('invalidToken');
			return $xajax;
		}		

//		$xajax->alert(print_r($this->data,true));
//		return $xajax;
			
		if($this->Config->user_report) {
		
			# Load the notifications observer model component and initialize it. 
			# Done here so it only loads on save and not for all controlller actions.
			$this->components = array('notifications');
			$this->__initComponents();
			
			$this->data['ReviewReport']['message'] = Sanitize::getString($this->data['ReviewReport'],'message');
			$this->data['ReviewReport']['reviewid'] = $review_id = Sanitize::getInt($this->data['ReviewReport'],'reviewid');

			if ($this->data['ReviewReport']['message'] != '' && $this->data['ReviewReport']['reviewid'] > 0) {

				# Validtion passed
				
				// insert report in table
				$save = $this->ReviewReport->store($this->data);
							
				$reportdiv = "report-".$this->data['ReviewReport']['reviewid'];
		
				$xajax->alert(__t("Your report was submitted, thank you.",true));
				$xajax->clear('reportlink-'.$this->data['ReviewReport']['reviewid'],'class');				
				$xajax->clear('reportlink-'.$this->data['ReviewReport']['reviewid'],'href');
				$xajax->script('tb_remove();');
				$xajax->script("jQuery('#jr_reportLink$review_id').fadeOut('slow');");				
						
			} else {
				
				# Validation failed
				$xajax->assign('jr_ReviewReportToken','value',$this->Security->reissueToken());
				$xajax->alert(__t("The message is empty.",true));
				$xajax->assign("submitButton","disabled",false);
		
			}
		
		}
	
		return $xajax;
	}
		
}

?>