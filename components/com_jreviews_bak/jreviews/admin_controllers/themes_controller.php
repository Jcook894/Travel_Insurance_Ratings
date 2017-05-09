<?php
/**
 * JReviews - Reviews Extension
 * Copyright (C) 2010-2011 ClickFWD LLC
 * This is not free software, do not distribute it.
 * For licencing information visit http://www.reviewsforjoomla.com
 * or contact sales@reviewsforjoomla.com
**/

// no direct access
defined( 'MVC_FRAMEWORK') or die( 'Direct Access to this location is not allowed.' );

class ThemesController extends MyController 
{
	var $uses = array('directory','category','section','jreviews_section','jreviews_category');

	var $helpers = array('html','form','paginator');

    var $components = array('config');
    
	var $autoRender = false;
	
	var $autoLayout = false;

	function index() 
    {             
        if($this->cmsVersion == CMS_JOOMLA16) return $this->categories_j16();
        
        $task = Sanitize::getString($this->data,'task');
        switch($task)
        {
            case 'categories':
                return $this->categories();                    
            break;
            case 'sections':
                return $this->sections();                    
            break;
            default:
                return $this->render();
            break;
        }
	} 
		
	function sections() 
    {
		$this->action = 'sections';			
						
		$sectionid = '';
			
		if(isset($this->data['sectionid'])) {
			$sectionid = $this->data['sectionid'];				
		}
				
		$lists = array();
	
	
		$limit = $this->limit;
		$limitstart = $this->offset;	
		$total = 0;
	
		$rows = $this->Section->getRows($limitstart,$limit,$total);
		
		$this->set(
			array(
				'rows'=>!empty($rows) ? $rows : array(),
				'lists'=>$lists,
				'pagination'=>array(
					'total'=>$total
				)
			)
		);

        return $this->render();                        
	}
	
	function categories() 
    {
        if($this->cmsVersion==CMS_JOOMLA16) return $this->categories_j16();
		
        $this->action = 'categories';
						
		$sectionid = '';
			
		if(isset($this->data['sectionid'])) {
			$sectionid = $this->data['sectionid'];				
		}
				
		$lists = array();
	
		$limit = $this->limit;
		$limitstart = $this->offset;	
		$total = 0;
	
		$rows = $this->Category->getRows($sectionid, $limitstart, $limit, $total);
				
		$this->set(
			array(
				'rows'=>!empty($rows) ? $rows : array(),
				'sections'=>$this->Section->getList(),
				'sectionid'=>$sectionid,
				'pagination'=>array(
					'total'=>$total
				)				
			)
		);
	 		
        return $this->render();                        
	}
    
	function saveSection() 
    {
        $response = array();	
		// Get current list of section ids in the jreviews table
		$query = "SELECT sectionid FROM #__jreviews_sections";
		$this->_db->setQuery($query);
		$section_ids = $this->_db->loadResultArray();

        $tmpl = $this->data['tmpl'];

		$section_ids_update = array();

		foreach ($tmpl as $secid=>$value) 
        {
			$section = $this->JreviewsSection->findRow(array('conditions'=>array('sectionid = ' . $secid)));

			$tmpl_name = $value['name'];
			$tmpl_suffix = $value['suffix'];
	
			if (@in_array($secid,$section_ids) && ($tmpl_name != $section['JreviewsSection']['tmpl'] || $tmpl_suffix != $section['JreviewsSection']['tmpl_suffix'])) {

				$query = "UPDATE #__jreviews_sections"
				. "\n SET tmpl='$tmpl_name',tmpl_suffix='$tmpl_suffix'"
				. "\n WHERE sectionid = '$secid'";
				$this->_db->setQuery($query);
				$this->_db->query($query);
				$section_ids_update[] = $secid;
	
			} elseif (@!in_array($secid,$section_ids)) {
				
				$query = "INSERT INTO #__jreviews_sections"
				. "\n (sectionid,tmpl,tmpl_suffix) VALUES ('$secid','$tmpl_name','$tmpl_suffix')";
				$this->_db->setQuery($query);
				$this->_db->query($query);
				if ($tmpl_name!='' || $tmpl_suffix!='') {
					$section_ids_update[] = $secid;
				}
	
			}
		}
	
		// Clear cache
		clearCache('', 'views');
		clearCache('', '__data');
	
		$page = $this->sections();
	
        foreach ($section_ids_update as $secid) 
        {
            $response[] = "jreviews_admin.tools.flashRow('section{$secid}');";
        }
        
        return $this->ajaxResponse($response,true,compact('page'));
	}
	
	function saveCategory() 
    {
		$response = array();
        $tmpl = $this->data['tmpl'];
		
		$catids = array();
		
		foreach ($tmpl as $catid=>$value)
		{
	
			$category = $this->JreviewsCategory->findRow(array('conditions'=>array('JreviewsCategory.id = ' . $catid,'JreviewsCategory.option = "com_content"')));
			
			$tmpl = $value['name'];
			$suffix = $value['suffix'];
	
			if ($category['JreviewsCategory']['tmpl'] != $tmpl || $category['JreviewsCategory']['tmpl_suffix'] != $suffix ) 
			{
				$catids[] = $catid;
				$query = "UPDATE #__jreviews_categories SET tmpl = '$tmpl', tmpl_suffix = '$suffix' WHERE id = '$catid' AND `option` = 'com_content'";
				$this->_db->setQuery($query);
				if ( !$this->_db->query() ) 
                {
					$response[] = "s2Alert('".$database->getErrorMsg()."');";
					return $this->ajaxResponse($response);
				}
	
			}
		}
	
		// Clear cache
		clearCache('', 'views');
		clearCache('', '__data');
	
		$page = $this->categories();
	
		foreach ($catids as $catid) 
        {
			$response[] = "jreviews_admin.tools.flashRow('category{$catid}');";
		}
		
		return $this->ajaxResponse($response,true,compact('page'));
	}	

    /*******************************
    * JOOMLA 1.6 specific functions
    ********************************/
    function categories_j16() 
    {
        $this->action = 'categories';
        $cat_alias = Sanitize::getString($this->data,'cat_alias');
        $lists = array();
        $total = 0;
        $sections = $this->Category->getChildren(1 /*ROOT*/, 1 /*Depth*/);
        $rows = $this->Category->getReviewCategories($cat_alias, $this->offset, $this->limit, $total);
                
        $this->set(array(
            'rows'=>$rows,
            'sections'=>$sections,
            'sectionid'=>$cat_alias,
            'pagination'=>array('total'=>$total)                
        ));
             
        return $this->render();                        
    }    
	
}
