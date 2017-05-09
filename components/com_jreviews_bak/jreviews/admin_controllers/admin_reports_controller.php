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

class AdminReportsController extends MyController {
    
    var $uses = array('menu','criteria','report','discussion','review');
    var $helpers = array('html','time','admin/admin_routes','routes','rating','custom_fields');
    var $components = array('config','everywhere');

    var $autoRender = false;
    var $autoLayout = true;        
        
    var $response = array();

    function &getEverywhereModel(){
        return $this->Review;
    }   
        
    function beforeFilter() 
    {        
        parent::beforeFilter();
    }
    
    function index() 
    {
        $conditions = array(
            "Report.approved = 0"
        );
        $reports = $this->Report->findAll(array(
            'fields'=>array('Report.*'),
            'conditions'=>$conditions,
            'offset'=>$this->offset,
            'limit'=>$this->limit,               
            'order'=>array('Report.report_id DESC') 
        ));
        
        $total = $this->Report->findCount(array('conditions'=>$conditions));
        
        $this->Review->runProcessRatings = false;                
       
       # Right now reports are for reviews and comments so we always show a link to the discussion page
       # Here we get all review ids to be able to generate the frontend urls
        $review_ids = array();
        foreach($reports AS $key=>$report)
        {
            !empty($report['Report']['review_id']) and $review_ids[] = $report['Report']['review_id'];
        }
        
        $review_ids = array_unique($review_ids);
        
        $this->Review->runProcessRatings = false;                
        $this->EverywhereAfterFind = true; // Triggers the afterFind in the Observer Model
                    
        $reviews = $this->Review->findAll(array(
            'conditions'=>array('Review.id IN ('.implode(',',$review_ids).')')
        ));

        $this->_getReviewSefUrls($reviews);
        
        // Now we merge the report and review arrays
        foreach($reports AS $key=>$report)
        {
            isset($reviews[$report['Report']['review_id']]) and $reports[$key] = array_merge($reports[$key],$reviews[$report['Report']['review_id']]);            
        }

        $this->set(array(
            'reports'=>$reports,
            'total'=>$total
        ));
        
        return $this->render('reports','reports');
    }    
        
    function _save() 
    {
        if($this->data['Report']['approved']==-2)
            {
                $this->Report->delete('report_id',$this->data['Report']['report_id']);            
                    
                $this->response[] = "jQuery('#jr_moderateForm".$this->data['Report']['report_id']."').slideUp('slow',function(){jQuery(this).html('');});";

                $this->response[] = "jreviews_admin.menu.moderation_counter('report_count');";
                
                return $this->ajaxResponse($this->response);
            }
        
        if($this->Report->store($this->data))
        {
            if($this->data['Report']['approved']==0)
                {
                    $this->response[] = "
                        jQuery('#jr_moderateForm".$this->data['Report']['report_id']."').slideUp('slow',function()
                        {
                            jQuery(this).addClass('jr_form').html('".__a("Report will remain in moderation pending further action.",true,true)."').slideDown('normal',function()
                            {
                                jQuery(this).effect('highlight',{},4000);
                                setTimeout(function(){jQuery('#jr_moderateForm".$this->data['Report']['report_id']."').fadeOut(1500)},3000);
                            });
                        });
                    ";                                  
                }
             else 
                {
                    $this->response[] = "jreviews_admin.menu.moderation_counter('report_count');";
                    $this->response[] = "jQuery('#jr_moderateForm".$this->data['Report']['report_id']."').slideUp('slow',function(){jQuery(this).html('');});";
                }       
        }
           
        if($this->action=='_save')
            {
                return $this->ajaxResponse($this->response);
            }
    }
    
    function _deleteModeration()
    {
        $this->data['Report']['approved'] = -2;
        
        $this->data['Report']['report_id'] = (int) $this->data['entry_id'];
        
        $this->response[] = ("jreviews_admin.dialog.close();");
        
        $this->_save();
        
        return $this->ajaxResponse($this->response);
    }
        
}