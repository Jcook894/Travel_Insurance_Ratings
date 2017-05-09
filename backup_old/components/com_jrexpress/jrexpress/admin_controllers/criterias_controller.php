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

class CriteriasController extends MyController {
	
	var $uses = array('criteria');
	
	var $helpers = array('html','form','admin/admin_criterias');
	
	var $autoRender = false;
	
	var $autoLayout = false;

	function beforeFilter() {
	
		# Call beforeFilter of MyAdminController parent class
		parent::beforeFilter();
		
	}

	function index() {
				
		$xajax = new xajaxResponse();
		
		$rows = $this->Criteria->getList();
	 					
	 	$table = $this->listViewTable($rows);
		
		$this->set(array('table'=>$table));

		$page = $this->render();
		
	 	$xajax->assign("page","innerHTML",$page);
	 	
		$xajax->script("jQuery('#page').fadeIn(1500);");
	 	
		# Init thickbox
		$xajax->script("tb_init('a.thickbox, area.thickbox, input.thickbox');");
		
		$xajax->script("imgLoader = new Image();imgLoader.src = tb_pathToImage;");	 	
		
	 	return $xajax;
	
	}	
	
	function listViewTable($rows) {
			
		foreach($rows AS $key=>$row) {
			
			$groupList = '';
			
			if ($row->groupid != '') 
			{
				$groups = explode (",", $row->groupid);

				foreach ($groups as $group) {
					$this->_db->setQuery("SELECT CONCAT(name,' (',IF(type=\"content\",\"listing\",type),')') AS `group` FROM #__jreviews_groups WHERE groupid = $group");
					$result = $this->_db->loadResult();

					if($result != '') {
						$groupList .= "<li>$result</li>";
					}
				}
				
				$rows[$key]->field_groups = "<ul>$groupList</ul>";
			}

		
		}
						
		$this->set(array(
			'rows'=>$rows
		));
		
		return $this->render('criterias','table');
	
	}
		
	function edit() {
		
		$this->name = 'criterias';
		$this->action = 'edit';
	
		$this->autoRender = false;
				
		$xajax = new xajaxResponse();
		
		$criteriaid =  (int) $this->data['criteria_id'];
		
		if ($criteriaid) {
			$criteria = $this->Criteria->findRow(array('conditions'=>array('id = ' . $criteriaid)));
		} else {
			$criteria = $this->Criteria->emptyModel();
			$criteria['Criteria']['state'] = 1;
			$criteria['Criteria']['group_id'] = '';
		}
		
		// create custom field groups select list
		$this->_db->setQuery("SELECT groupid AS value, CONCAT(IF(type=\"content\",\"listing\",type),':',name) AS text FROM `#__jreviews_groups` ORDER BY type, ordering");
	
		$groups = $this->_db->loadObjectList();
			
		$this->set(		
			array(
				'criteria'=>$criteria['Criteria'],
				'groups'=>$groups
			)		
		);	
		
		$page = $this->render();
				
	 	$xajax->assign("page","innerHTML",$page);
	 	
		$xajax->script("jQuery('#page').fadeIn(1500);");
	 	
	 	return $xajax;
	
	}
	
	/**
	 * Checks that criteria can actually be saved based on current system information
	 *
	 * @param unknown_type $formValues
	 * @return unknown
	 */
	function _save($params) {
	
		$this->action = 'index';
		
		$xajax = new xajaxResponse();

		$formValues = array_shift($params);
		
		$criteriaid = $this->data['Criteria']['id'];
		$reviews = array();
	
		// Lets remove any blank lines from the new criteria
		$newCriteria = cleanString2Array($this->data['Criteria']['criteria'],"\n");
	
		// Lets remove any blank lines from the new criteria
		$newTooltips = explode("\n",$this->data['Criteria']['tooltips']);
	
		// Begin basic validation
		$msg = array();
	
		if ($this->data['Criteria']['title']=='') {
			$msg[] = "Please enter a criteria set name.";
		}

        // Set state = 1 to use ratings
        $this->data['Criteria']['state'] = 1;

		if ($this->data['Criteria']['criteria']=='') {
			$msg[] = "Please enter at least one criteria to rate your items.";
		}

		if (count($newTooltips) > count($newCriteria)) {
			$msg[] = "There are more tooltips than criteria, please remove the extra tooltips. You may leave blank lines for tooltips if there's a crietria that will not have a tooltip, but the number of lines must match the number of criteria";
		}
	
		if (count($msg) > 0) {
			$xajax->alert(implode("\r\n",$msg));
			return $xajax;
		}

		// If this is a new criteria, proceed to save
		if (!$criteriaid) {

			$xajax->loadCommands($this->saveContinued($formValues));
			return $xajax;
	
		}

		// We are in edit mode so let's check if the number of criteria has changed
		$criteria = $this->Criteria->findRow(array('conditions'=>array('id = ' . $criteriaid)));
	
		if (count($newCriteria) != $criteria['Criteria']['quantity']) {
	
			$query = "SELECT id FROM #__jreviews_categories WHERE criteriaid = '$criteriaid'";
			$this->_db->setQuery($query);
			$cats = $this->_db->loadResultArray();
			$cats = implode(",",$cats);
	
			$query = "SELECT id FROM #__content WHERE catid IN ($cats)";
			$this->_db->setQuery($query);
			$contentids = $this->_db->loadResultArray();
			$contentids = implode(",",$contentids);
	
			$query = "SELECT id FROM #__content WHERE catid IN ($cats)";
			$this->_db->setQuery($query);
			$contentids = $this->_db->loadResultArray();
			$contentids = implode(",",$contentids);
	
			$query = "SELECT count(*) FROM #__jreviews_comments WHERE pid IN ($contentids)";
			$this->_db->setQuery($query);
			$reviews = $this->_db->loadResult();
	
		}
	
		if ($reviews) { // There are reviews so saving is denied.
	
			$xajax->alert("There are $reviews reviews in the system for content using this criteria which prevent you from changing the number of criteria. You can only edit the criteria labels, but not add or remove criteria unless you first delete the existing $reviews reviews.");
	
		} else { // No reviews yet, do whatever you want
			
			$xajax->loadCommands($this->saveContinued());
			
		}
	
		return $xajax;
	}
	
	function saveContinued() {
		
		$xajax = new xajaxResponse();
//		$xajax->alert(print_r($this->data,true));
//		return $xajax;
			
		// Lets remove any blank lines from the new criteria
		$newCriteriaArray = cleanString2Array($this->data['Criteria']['criteria'],"\n");
		$this->data['Criteria']['criteria'] = implode("\n",$newCriteriaArray); //Reconstruct the string using the cleaned-up array
		$this->data['Criteria']['qty'] = count($newCriteriaArray);	
	    $this->data['Criteria']['groupid'] = '';			
		
		$this->Criteria->store($this->data);
			
		$xajax->loadCommands($this->index());

		$xajax->call("flashRow", "criteria".$this->data['Criteria']['id']);
				
		return $xajax;		
	}
	
	function delete($params) {
			
		$xajax = new xajaxResponse();

		$data = array_shift($params);
		
		$cid = array($data['row_id']);
		
		$tables_rel = array();
	
		$ids = implode(',', $cid);

		// Check if the criteria is being used by a category
		$this->_db->setQuery("SELECT count(*) FROM #__jreviews_categories WHERE criteriaid IN ($ids)");
		
		if ($count = $this->_db->loadResult()) {
			$xajax->alert("You have $count categories using this criteria, first you need to delete them."
			. " Keep in mind that deleting the criteria will result in the removal of all reviews in content using this criteria."
			. "\r\n\r\nHowever your content and section/category structure will remain intact, even after you remove the category from the"
			. " JReviews Express category manager.");
			return $xajax;
		}

		$this->_db->setQuery("DELETE FROM #__jreviews_criteria WHERE id IN ($ids)");
		$this->_db->query();

		$this->_db->setQuery("SELECT id FROM #__jreviews_categories WHERE criteriaid in ($ids)");
		$catids = $this->_db->loadResultArray();
		$catids = implode(',',$catids);

		// If the criteria is assigned to a category, delete all existing reviews
		if ($catids) {
			
			$this->_db->setQuery("SELECT jc.id as reviewid FROM #__content c"
								."\n INNER JOIN #__jreviews_comments jc on jc.pid = c.id"
								."\n WHERE c.catid IN ($catids)");
			
			$cid = $this->_db->loadResultArray();

			$del_id = 'id';
			$del_id_rel = 'reviewid';
			$tables_rel = array();
			$table = "#__jreviews_comments";
			$tables_rel[] = "#__jreviews_ratings";
			$tables_rel[] = "#__jreviews_votes";
			$tables_rel[] = "#__jreviews_votes_tmp";
			$tables_rel[] = "#__jreviews_report";
		
		} else {
			
			$xajax->call("removeRow",'criteria'.$cid[0]);
			return $xajax;
		
		}

	
		if (count($cid))
		{
			$ids = implode(',', $cid);
			$this->_db->setQuery("DELETE FROM $table WHERE $del_id IN ($ids)");
			if (!$this->_db->query()) {
				$xajax->alert($this->_db->getErrorMsg());
				return $xajax;
			}
	
			if (count($tables_rel)) {
				foreach ($tables_rel as $table_rel) {
					$this->_db->setQuery("DELETE FROM $table_rel WHERE $del_id_rel IN ($ids)");
					if (!$this->_db->query()) {
						$xajax->alert($this->_db->getErrorMsg());
						return $xajax;
					}
				}
			}
	
		}
		
		$xajax->call("removeRow",'criteria'.$cid[0]);
		
		// Clear cache
		clearCache('', 'views');
		clearCache('', '__data');

		return $xajax;
	}	

	function _copy($params) {
	
		$xajax = new xajaxResponse();
		
		$copies = array_shift($params);
		$copies = intval($copies);

		$formValues = array_shift($params);
		$criteriaid = $formValues['criteriaid'];
			
		if (!$criteriaid) {
			$xajax->alert("You didn't select a criteria to copy, try again.");
			return $xajax;
		}
	
		$criteria = $this->Criteria->findRow(array('conditions'=>array('id = ' . $criteriaid)));
        
		$newCriteria = array();
		$newCriteria['Criteria']['title'] = 'Copy of ' . $criteria['Criteria']['title'];
		$newCriteria['Criteria']['criteria'] = $criteria['Criteria']['criteria'];
		$newCriteria['Criteria']['tooltips'] = $criteria['Criteria']['tooltips'];
		$newCriteria['Criteria']['qty'] = $criteria['Criteria']['quantity'];
		$newCriteria['Criteria']['groupid'] = $criteria['Criteria']['group_id'];
		$newCriteria['Criteria']['state'] = $criteria['Criteria']['state'];
	
		// store it in the db
		for ($i=1; $i<=$copies; $i++) {

			if (!$this->Criteria->store($newCriteria)) {
				$xajax->alert($row->getError());
				return $xajax;
			}
			$ids[] = $newCriteria['Criteria']['id'];
			unset($newCriteria['Criteria']['id']);
		}
	
		// Reloads the whole list to display the new/updated record 	
		$fieldrows = $this->Criteria->getList();
	 	
	 	$xajax->script("window.top.hidePopWin();");
		
	 	$xajax->assign("criteriatable","innerHTML",$this->listViewTable($fieldrows));
		
		foreach ($ids as $id) {
			$xajax->call("flashRow", "criteria".$id);
		}
		return $xajax;
	
	}
		                        	
}