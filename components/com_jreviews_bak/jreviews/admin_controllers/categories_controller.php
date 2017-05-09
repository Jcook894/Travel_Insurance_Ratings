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

class CategoriesController extends MyController {
    
    var $uses = array('category','section'/*J15*/,'criteria','directory','jreviews_category');
    
    var $helpers = array('html','form','paginator');
    
    var $components = array('config');
        
    var $autoRender = false;
    
    var $autoLayout = false;

        
    function beforeFilter() 
    {
        # Call beforeFilter of MyAdminController parent class
        parent::beforeFilter();
    }
        
    function index() 
    {   
        $this->action = 'index'; 
           
        if($this->cmsVersion!=CMS_JOOMLA15) return $this->index_j16();
        
        $this->autoRender = false;        
                
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
        
        return $page;    
    }    

    function create() 
    {
        if(getCmsVersion()!=CMS_JOOMLA15) return $this->create_j16();

        $this->name = 'categories';
        $this->autoRender = true;
                
        $sectionid =  Sanitize::getInt( $this->params, 'sectionid', '' );
                                
        $limit =  Sanitize::getInt( $this->params, 'limit', cmsFramework::getConfig('list_limit') );
        
        $limitstart =  Sanitize::getInt( $this->params, 'limitstart', '' );
                        
        $this->set(
            array(
                'sectionid'=>$sectionid,
                'limit'=>$limit,
                'limitstart'=>$limitstart,
                'criterias'=>$this->Criteria->getSelectList(),
                'directories'=>$this->Directory->getSelectList(),
                'categories'=>$this->Category->getSelectList()
            )
        );                                                    
    }
    
    function edit() 
    {
        $this->name = 'categories';
        $this->autoRender = true;
    
        $catid =  Sanitize::getInt( $this->params, 'catid', '' );
        $sectionid =  Sanitize::getInt( $this->params,  'sectionid', '' );
        $limit =  Sanitize::getInt( $this->params, 'limit', cmsFramework::getConfig('list_limit') );
        $limitstart =  Sanitize::getInt( $this->params, 'limitstart', '' );
                        
        $category = $this->Category->findRow(
            array('conditions'=>array('Category.id='.$catid)),
            array()/*callbacks*/
        );

        $criteria = !$category['Category']['criteria_id'] ? array() : (array) end($this->Criteria->getSelectList($category['Category']['criteria_id']));
        
        $this->set(
            array(
                'sectionid'=>$sectionid,
                'limit'=>$limit,
                'limitstart'=>$limitstart,
                'criteria'=>$criteria,
                'directories'=>$this->Directory->getSelectList(),
                'category'=>$category
            )
        );    
    }    
    
    function _save() 
    {
        if($this->cmsVersion != CMS_JOOMLA15) return $this->_save_j16();
    	$this->action = 'index';
        $this->autoRender = false;
            
        $cat_ids = array();
        $msg = array();

        // Begin form validation
        if (!isset($this->data['Category']['criteriaid']) || !$this->data['Category']['criteriaid'])
            $msg[] = "You need to select a listing type.";
        
        if (!isset($this->data['Category']['dirid']) || !(int)$this->data['Category']['dirid'])
            $msg[] = "You need to select a directory.";
            
        if (isset($this->data['Category']['id']) && 
            (empty($this->data['Category']['id'][0]) || empty($this->data['Category']['id']))) {
            $msg[] = "You need to select one or more categories from the list.";
        }
    
        if (count($msg) > 0) 
            {
                $action = 'error';
                $text = implode("<br />",$msg);
                return $this->ajaxResponse(compact('action','text'),false);
            }
                   
        // Update database
        if(isset($this->data['Category']['id']))
        {
            if(is_array($this->data['Category']['id'][0])) 
                {
                    $this->data['Category']['id'] = $this->data['Category']['id'][0];
                }
            
            foreach ($this->data['Category']['id'] as $id) 
                {
                    $this->_db->setQuery("select id from #__jreviews_categories where id='$id' AND `option` = 'com_content'");

                    if(is_null($this->_db->loadResult())) 
                    {
                        $query = "insert into #__jreviews_categories (id,criteriaid,dirid,`option`) "
                                 ."values ('".$id."','".$this->data['Category']['criteriaid']."','".$this->data['Category']['dirid']."','com_content')";
                    
                    } else {
                        $query = "
                            UPDATE 
                                #__jreviews_categories 
                            SET 
                                criteriaid={$this->data['Category']['criteriaid']},dirid={$this->data['Category']['dirid']}
                            WHERE 
                                id={$id} AND `option`='com_content'
                        ";
                    
                    }
                    
                    $this->_db->setQuery($query);

                    if (!$this->_db->query()) 
                    {
                        return json_encode(array('errorMsg'=>$this->_db->getErrorMsg()));                
                    }
                    
                    $cat_ids[] = array('cat_id'=>$id);
                }
        }
        
        // Render the whole page again to update it        
        $sectionid = Sanitize::getInt($this->data,'sectionid','');
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
        
        $action = 'success';
        $page = $this->render();

        return $this->ajaxResponse(compact('action','page','cat_ids'),false);
    }    
    
    function delete() 
    {
        $cat_ids = array();
        $response = array();
        $boxchecked = Sanitize::getInt($this->params['form'],'boxchecked');;
        $cat_id = Sanitize::getInt($this->params['form'],'cat_id');
        $cat_ids = Sanitize::getVar($this->params['form'],'cid');
        if(!$boxchecked && $cat_id) $cat_ids = array($cat_id);
        if (!empty($cat_ids) ) 
        {
            $query = "
                SELECT
                    COUNT(*)
                FROM
                    #__jreviews_comments AS Review
                INNER JOIN
                    #__content AS Content ON Content.id = Review.pid
                WHERE
                    Review.mode = 'com_content'
                    AND Content.catid IN ( ".implode(',', $cat_ids)." )
            ";
            $this->_db->setQuery($query);
            $reviews = $this->_db->loadResult();
            
            if ( !empty($reviews) )
            {
                $response[] = "jreviews_admin.dialog.close();";
                $response[] = "s2Alert('Some of the categories you are trying to delete have reviews and therefore cannot be deleted. Please choose categories without reviews or delete the reviews first.');";
                return $this->ajaxResponse($response);
            }

            $response[] = 'jreviews_admin.dialog.close();';
            
            foreach ($cat_ids AS $cat_id) 
            { 
                $removed = $this->JreviewsCategory->delete('id',$cat_id);
                $removed and $response[] = "jreviews_admin.tools.removeRow('category{$cat_id}');";
            }
        }
    
        return $this->ajaxResponse($response);
    }               
    
    /*******************************
    * JOOMLA 1.6 specific functions
    ********************************/
    function index_j16() 
    {          
        $this->autoRender = false;        
        $cat_alias = Sanitize::getString($this->data,'cat_alias');
        $total = 0;
        $sections = $this->Category->getChildren(1 /*ROOT*/, 1 /*Depth*/);
        $rows = $this->Category->getReviewCategories($cat_alias, $this->offset, $this->limit, $total);
        
        $this->set(array(
            'rows'=>$rows,
            'sections'=>$sections,
            'sectionid'=>$cat_alias,
            'pagination'=>array('total'=>$total)                
        ));
         
        $page = $this->render();
        
        return $page;    
    }    
    
    function _save_j16() 
    {
        $this->autoRender = false;
        $cat_ids = array();
        $msg = array();

        // Begin form validation
        if (!isset($this->data['Category']['criteriaid']) || $this->data['Category']['criteriaid']=='')
            $msg[] = "You need to select a listing type.";
        
        if (!isset($this->data['Category']['dirid']) || !(int)$this->data['Category']['dirid'])
            $msg[] = "You need to select a directory.";
            
        if (isset($this->data['Category']['id']) && 
            (empty($this->data['Category']['id'][0]) || empty($this->data['Category']['id']))) {
            $msg[] = "You need to select one or more categories from the list.";
        }
    
        if (count($msg) > 0) 
            {
                $action = 'error';
                $text = implode("<br />",$msg);
                return $this->ajaxResponse(compact('action','text'),false);
            }
                   
        // Update database
        if(isset($this->data['Category']['id']))
        {
            if(is_array($this->data['Category']['id'][0])) 
                {
                    $this->data['Category']['id'] = $this->data['Category']['id'][0];
                }
            
            foreach ($this->data['Category']['id'] as $id) 
                {
                    $this->_db->setQuery("select id from #__jreviews_categories where id='$id' AND `option` = 'com_content'");

                    if(is_null($this->_db->loadResult())) 
                    {
                        $query = "insert into #__jreviews_categories (id,criteriaid,dirid,`option`) "
                                 ."values ('".$id."','".$this->data['Category']['criteriaid']."','".$this->data['Category']['dirid']."','com_content')";
                    
                    } else {
                        $query = "
                            UPDATE 
                                #__jreviews_categories 
                            SET 
                                criteriaid={$this->data['Category']['criteriaid']},dirid={$this->data['Category']['dirid']}
                            WHERE 
                                id={$id} AND `option`='com_content'
                        ";
                    
                    }
                    
                    $this->_db->setQuery($query);

                    if (!$this->_db->query()) 
                    {
                        return json_encode(array('errorMsg'=>$this->_db->getErrorMsg()));                
                    }
                    
                    $cat_ids[] = array('cat_id'=>$id);
                }
        }

        $this->action = 'index'; 
        $action = 'success';        
        $page = $this->index_j16();
        return $this->ajaxResponse(compact('action','page','cat_ids'),false);
    }       
    
    function create_j16() 
    {
        $this->name = 'categories';
        $this->autoRender = true;
                
        $sectionid =  Sanitize::getInt( $this->params, 'sectionid', '' );
                                
        $limit =  Sanitize::getInt( $this->params, 'limit', cmsFramework::getConfig('list_limit') );
        
        $limitstart =  Sanitize::getInt( $this->params, 'limitstart', '' );
                        
        $this->set(
            array(
                'sectionid'=>$sectionid,
                'limit'=>$limit,
                'limitstart'=>$limitstart,
                'criterias'=>$this->Criteria->getSelectList(),
                'directories'=>$this->Directory->getSelectList(),
                'review_categories'=>$this->Category->getReviewCategoryIds(),
                'categories'=>$this->Category->getNonReviewCategories()
            )
        );                                                    
    }
}
