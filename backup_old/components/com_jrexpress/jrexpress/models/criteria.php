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

class CriteriaModel extends MyModel  {
	
	var $name = 'Criteria';
	
	var $useTable = '#__jreviews_criteria AS Criteria';
	
	var $primaryKey = 'Criteria.criteria_id';
	
	var $realKey = 'id';
	
	var $fields = array(
		'Criteria.id AS `Criteria.criteria_id`',
		'Criteria.title AS `Criteria.title`',
		'Criteria.criteria AS `Criteria.criteria`',
		'Criteria.weights AS `Criteria.weights`',
		'Criteria.tooltips AS `Criteria.tooltips`',
		'Criteria.qty AS `Criteria.quantity`',
		'Criteria.groupid AS `Criteria.group_id`',
		'Criteria.state AS `Criteria.state`'
	);
					
	function getList() {
	
		$query = "SELECT * from #__jreviews_criteria order by title ASC";
		
		$this->_db->setQuery($query);
		
		$rows = $this->_db->loadObjectList();
		
		return $rows;
	
	}
	
	function getSelectList($criteria_id = null) {

		$query = "SELECT id AS value, title AS text"
		. "\n FROM #__jreviews_criteria"
		. ($criteria_id ? "\n WHERE id = " . $criteria_id : '')
		. "\n ORDER BY title ASC"
		;
		
		$this->_db->setQuery($query);
		
		$results = $this->_db->loadObjectList();
		
		return $results;
	
	}
	
	/**
	 * Returns criteria set
	 *
	 * @param array $data has extension, cat_id or criteria_id keys=>values
	 */
	function getCriteria($data) {
		if(isset($data['criteria_id'])) {
			$conditions = array('Criteria.id = ' . Sanitize::getInt($data,'criteria_id'));
			$joins = array();
		} elseif(isset($data['cat_id'])) {
			$conditions = array('JreviewCategory.id = ' . Sanitize::getInt($data,'cat_id'));
			$joins = array("INNER JOIN #__jreviews_categories AS JreviewCategory ON Criteria.id = JreviewCategory.criteriaid AND JreviewCategory.`option` = '{$data['extension']}'");			
		}
		$queryData = array('conditions'=>$conditions,'joins'=>$joins);

		$results = $this->findRow($queryData);
		
		if(isset($results['Criteria']['criteria']) && $results['Criteria']['criteria'] != '') {
			$results['Criteria']['criteria'] = explode("\n",$results['Criteria']['criteria']);
		}

		if(isset($results['Criteria']['tooltips']) && $results['Criteria']['tooltips'] != '') {
			$results['Criteria']['tooltips'] = explode("\n",$results['Criteria']['tooltips']);
		}

		if(isset($results['Criteria']['weights']) && $results['Criteria']['weights'] != '') {
			$results['Criteria']['weights'] = explode("\n",$results['Criteria']['weights']);
		}
		return $results;
	}

}