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


class CategoriesController extends MyController {
	
	var $uses = array('category','section','criteria','jreviews_category');
	
	var $helpers = array('html','form','paginator');
		
	var $autoRender = false;
	
	var $autoLayout = false;

		
	function beforeFilter() {
		
		# Call beforeFilter of MyAdminController parent class
		parent::beforeFilter();
		
	}
		
	function index($params=array()) {
		
		$this->autoRender = false;		
				
		$xajax = new xajaxResponse();				
		
		if(!empty($this->data)) {

			$sectionid = Sanitize::getInt($this->data,'sectionid');
		
		} else {
			
			$sectionid = '';
		}	

		$limit = $this->limit;
		$limitstart = $this->offset;		
		$total = 0;
		
		$rows = $this->Category->getRows($sectionid, $limitstart, $limit, $total);
		
		$sections = $this->Section->getList();
	
		$this->set(
			array(
				'rows'=>$rows,
				'sections'=>$sections,
				'sectionid'=>$sectionid,
				'pagination'=>array(
					'total'=>$total
				)				
			)
		);
	 	
		$page = $this->render();
	
		$xajax->assign("page","innerHTML",$page);
		
		$xajax->script("jQuery('#page').fadeIn(1500);");
				
		# Init thickbox
//		$xajax->script("tb_init('a.thickbox, area.thickbox, input.thickbox');");
		
		$xajax->script("imgLoader = new Image();imgLoader.src = tb_pathToImage;");				
		
		return $xajax;
	}	
	
	function create() {
						
		$this->name = 'categories';
		$this->autoRender = true;
				
		$sectionid =  Sanitize::getInt( $this->passedArgs, 'sectionid', '' );
								
		$limit =  Sanitize::getInt( $this->passedArgs, 'limit', cmsFramework::getConfig('list_limit') );
		
		$limitstart =  Sanitize::getInt( $this->passedArgs, 'limitstart', '' );
						
		$this->set(
			array(
				'sectionid'=>$sectionid,
				'limit'=>$limit,
				'limitstart'=>$limitstart,
				'criterias'=>$this->Criteria->getSelectList(),
				'categories'=>$this->Category->getSelectList()
			)
		);		
	}
	
	function edit() {
		
		$this->name = 'categories';
		$this->autoRender = true;
	
		$catid =  Sanitize::getInt( $this->passedArgs, 'catid', '' );
		$sectionid =  Sanitize::getInt( $this->passedArgs,  'sectionid', '' );
		$limit =  Sanitize::getInt( $this->passedArgs, 'limit', cmsFramework::getConfig('list_limit') );
		$limitstart =  Sanitize::getInt( $this->passedArgs, 'limitstart', '' );
						
		$this->Category->runAfterFind = false;
		$category = $this->Category->findRow(array('conditions'=>array('Category.id='.$catid)));
				
		$this->set(
			array(
				'sectionid'=>$sectionid,
				'limit'=>$limit,
				'limitstart'=>$limitstart,
				'criteria'=>(array) end($this->Criteria->getSelectList($category['Category']['criteria_id'])),
				'category'=>$category
			)
		);	

	}	
	
	function _save($params) {
		
		$this->action = 'index';
	
		$this->autoRender = false;
		
		$xajax = new xajaxResponse();

		$msg = array();

		// Begin form validation
		if (!isset($this->data['Category']['criteriaid']) || !$this->data['Category']['criteriaid'])
			$msg[] = "You need to select a criteria set.";
			
		if (isset($this->data['Category']['id']) && 
			(empty($this->data['Category']['id'][0]) || empty($this->data['Category']['id']))) {
			$msg[] = "You need to select one or more categories from the list.";
		}
	
		if (count($msg) > 0) {
			$xajax->alert(implode ("\r\n",$msg));
			return $xajax;
		}
	
		// Update database
		if(is_array($this->data['Category']['id'][0])) {
			$this->data['Category']['id'] = $this->data['Category']['id'][0];
		}
		
		foreach ($this->data['Category']['id'] as $id) 
		{
			$this->_db->setQuery("select id from #__jreviews_categories where id='$id' AND `option` = 'com_content'");

			if(is_null($this->_db->loadResult())) 
			{
				$query = "insert into #__jreviews_categories (id,criteriaid,`option`) "
						 ."values ('".$id."','".$this->data['Category']['criteriaid']."','com_content')";
			
			} else {
				$query = "UPDATE #__jreviews_categories SET criteriaid='".$this->data['Category']['criteriaid']."'"
						 ."\n WHERE id='$id' AND `option`='com_content'";
			
			}
			
			$this->_db->setQuery($query);

			if (!$this->_db->query()) 
			{
				$xajax->alert($this->_db->getErrorMsg());
				return $xajax;
			}
		}
		
	 	$page = $catrows = $this->index();
	
	 	$xajax->script("tb_remove();");
	
	 	$xajax->loadCommands($page);

	 	foreach ($this->data['Category']['id'] as $catid) {	
			$xajax->call("flashRow","category".$catid);
	 	}

		$this->requestAction('common/_clearCache');
	 	
	 	return $xajax;
	}	
	
	function delete($params) {
		
		$cat_ids = '';
		$cat_id = '';
		
	 	$xajax = new xajaxResponse();

		$data = array_shift($params);

		if(isset($data['cid'])) {
			$cat_ids = $data['cid'];
		}
		
		if(isset($data['cat_id'])) {
			$cat_id = $data['cat_id'];
		}
				
		$boxchecked = $data['boxchecked'];
 		
		if ($boxchecked && is_array($cat_ids)) {
	
			foreach ($cat_ids AS $cat_id) {
	
				$removed = $this->JreviewsCategory->delete('id',$cat_id);
			
				if($removed) {
					$xajax->call("removeRow","category$cat_id"); 
				}
			}
	
		} else {
	
			$removed = $this->JreviewsCategory->delete('id',$cat_id);
			
			if($removed) {
				$xajax->call("removeRow","category$cat_id"); 
			}
	
		}
	
		return $xajax;
	}		
}