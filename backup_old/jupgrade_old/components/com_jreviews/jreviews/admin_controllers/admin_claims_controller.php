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

class AdminClaimsController extends MyController 
{
    var $uses = array('claim','predefined_reply','menu','field','criteria');
    var $components = array('config','everywhere','admin/admin_notifications');    
    var $helpers = array('html','routes','form','time','rating','custom_fields');

    var $autoRender = false;
    var $autoLayout = false;        
        
    var $__loaded = array();
        
    function beforeFilter() 
    {                                    
        # Call beforeFilter of MyAdminController parent class
        parent::beforeFilter();
    }
                                                  
    // Need to return object by reference for PHP4    
    function &getNotifyModel(){
        return $this->Claim;
    }        
    
    function moderation() 
    {    
        $reviews = array();
        $predefined_replies = array();        
        
        $conditions = array(
                "Claim.approved = 0"
                //,"Claim.claim_text <> ''" /*allow claims without text*/
            );
            
        $claims = $this->Claim->findAll(array(
            'fields'=>array(
                'Claim.*',
                'User.name AS `Claim.name`',        
                'User.email AS `Claim.email`'
            ),
            'conditions'=>$conditions,
            'joins'=>array(
                'LEFT JOIN #__users AS User ON User.id = Claim.user_id'
            ), 
            'offset'=>$this->offset,
            'limit'=>$this->limit,                
            'order'=>array('Claim.created DESC')
        ));

        $total = $this->Claim->findCount(array('conditions'=>$conditions));
         
       if(!empty($claims))
       {
            $predefined_replies = $this->PredefinedReply->findAll(array(
                'fields'=>array('PredefinedReply.*'),
                'conditions'=>array('reply_type = "claim"')
                ));           

            // Complete the listing info for claim
            // First get listing ids
            $listing_ids = array();
            foreach($claims AS $key=>$claim)
            {
                $listing_ids[] = $claim['Claim']['listing_id'];
            }                                            
            
            $listings = $this->Listing->findAll(array(
                'conditions'=>array(
                    'Listing.id IN (' . implode(',',$listing_ids) . ')'
                )
            ),array('afterFind'));      

            # Pre-process all urls to sef
            $this->_getListingSefUrls($listings);
            
            foreach($claims AS $key=>$claim)
            {       
                if(isset($listings[$claim['Claim']['listing_id']]))
                {
                    $claims[$key] = array_merge($listings[$claim['Claim']['listing_id']],$claim);                                
                } 
                else 
                {
                    // The listing no longer exists, don't show the claim
                    unset($claims[$key]);
                }
            }                                            
       }        
        
        $this->set(array(
            'claims'=>$claims,
            'predefined_replies'=>$predefined_replies,
            'total'=>$total
        ));

        return $this->render('claims','claims');
    }    
    
    function _deleteModeration() 
    {                    
        $response = array();
        
        $entry_id = Sanitize::getInt($this->data,'entry_id');
        
        $deleted = $this->Claim->delete('claim_id',$entry_id);
        
        if ($deleted) 
        {
            $response[] = "jreviews_admin.dialog.close();";
            $response[] = "jQuery('#jr_moderateForm".$entry_id."').fadeOut(1500,function(){jQuery(this).html('');});";
            $response[] = "jreviews_admin.menu.moderation_counter('claim_count');";            
        }
            
        return $this->ajaxResponse($response);
    }    
            
    function _save() 
    {
        $response = array();

        if($this->Claim->store($this->data))
        {
            if($this->data['Claim']['approved']==0)
            {
                $response[] = "
                    jQuery('#jr_moderateForm".$this->data['Claim']['claim_id']."').slideUp('slow',function()
                    {
                        jQuery(this).addClass('jr_form').html('".__a("Claim will remain in moderation pending further action.",true,true)."').slideDown('normal',function()
                        {
                            jQuery(this).effect('highlight',{},4000);
                            setTimeout(function(){jQuery('#jr_moderateForm".$this->data['Claim']['claim_id']."').fadeOut(1500)},3000);
                        });
                    });
                ";  
                                        
            } else {
                $response[] = "jQuery('#jr_moderateForm".$this->data['Claim']['claim_id']."').slideUp('slow',function(){jQuery(this).html('');});";                
                $response[] = "jreviews_admin.menu.moderation_counter('claim_count');";
            }       
        }
           
        clearCache('', 'views');
        clearCache('', '__data');    
                        
        return $this->ajaxResponse($response);
    }
        
}