(function($){
   $.getCSS = function( url, media ){      
      $(document.createElement('link') ).attr({
          href: url,
          media: media || 'screen',
          type: 'text/css',
          rel: 'stylesheet'
      }).appendTo('head');
   }
})(jQuery);

(function($) {    
    $.fn.scrollTo = function(options,onAfter) {
        var settings = $.extend({}, $.fn.scrollTo.defaults, options);        
        var targetOffset = $(this).offset().top + settings.offset;
        $('html,body').animate({scrollTop: targetOffset}, settings.duration, settings.easing, onAfter);
    }
    
    $.fn.scrollTo.defaults = {
        offset: -30,
        duration: 1000,
        easing: 'swing'
    };
        
})(jQuery);                                                              

/* tinyMCE plugin */
(function($) {
	$.fn.tinyMCE = function(options) 
	{
        try {
              if (window.parent.tinyMCE) 
              {
                    return this.each(function()
                    {
                        window.parent.tinyMCE.execCommand('mceAddControl', false, this.id);
                    });
              }
        } catch (err) {
            var tinyMCE = "";
        }        
	}
   
    $.fn.RemoveTinyMCE = function(options) 
    {   
        try {
              if (window.parent.tinyMCE) 
              {
                    return this.each(function()
                    {
                        window.parent.tinyMCE.execCommand('mceRemoveControl', true, this.id);
                    });
              }
        } catch (err) {
            var tinyMCE = "";
        }         
    }

    $.fn.RemoveJCE = function(options) 
    {
        return this.each(function()
        {
            tinyMCE.execCommand('mceRemoveControl', true, this.id);
        });
    }
})(jQuery);   

/**
* S2Framework functions
*/   
function s2CloseDialog(){
    jQuery('.dialog').dialog('close');    
}
 
/*
* S2Alert
*/ 
function s2Alert(alertText) 
{                
    jQuery('#s2Alert').dialog('destroy').remove();    
    jQuery("body").append('<div id="s2Alert" class="dialog"><p><span class="ui-icon ui-icon-alert" style="float:left; margin:0 7px 20px 0;"></span>'+alertText+'</p></div>');    
    // Open de dialog       
    jQuery('#s2Alert').dialog( {
       dialogClass: 'jrDialog',
       modal: true,
       autoOpen: true,
       width: '400px',
       height: 'auto',
       buttons: {
            'OK': function() {
                jQuery(this).dialog('close');
            }
        }
    });
} 

(function($) 
{         
    $.fn.s2Dialog = function(dialogId,params) 
    {
        // Remove link focus            
        jQuery(this).blur();

        // show loading image
        var $spinner = jQuery(this).siblings('.jr_loadingSmall');
        $spinner.fadeIn();
                
        // Dialog setup
        var buttons = {};
        buttons[jrLanguage['submit']] = function() 
        {  //Submit function
            jQuery('#'+dialogId+'Form .jr_validation').remove();  
			try { 
				params.beforeSubmit(dialogId); 
			} catch(e) {}
            
			jQuery.ajax({
                type: "POST",
                url: s2AjaxUri,
                data: jQuery('#'+dialogId+'Form').serialize(),          
                cache: false, 
                dataType: 'json',
                success: function(s2Out)
                {
                    var $dialogForm = jQuery('#'+dialogId+'Form');
                    switch(s2Out.action){
                        case 'error':
                            jQuery('.ui-dialog-buttonpane').slideUp('medium',function(){
                                $dialogForm.html(s2Out.update_text);
                            }); 
                            jQuery('#s2AjaxResponse').html(s2Out.response);  
                        break;                        
                        case 'validation':
                            $dialogForm.s2ShowValidation(s2Out);
                            jQuery('#s2AjaxResponse').html(s2Out.response);                              
                        break;                        
                        case 'update_page':
                            jQuery('#'+dialogId).dialog('close');
                            jQuery('#'+s2Out.target_id).s2ShowUpdate(s2Out);
                        break;                        
                        case 'update_dialog':
                            jQuery('.ui-dialog-buttonpane').slideUp('medium',function(){$dialogForm.html(s2Out.update_text);});                                                 
                            jQuery('#s2AjaxResponse').html(s2Out.response);  
                        break;
                        default:
                            jQuery('#s2AjaxResponse').html(s2Out.response);                        
						break;
                    }
					try{ 
						jQuery('#valid_fields','#'+dialogId+'Form').remove(); 
						params.afterSubmit(s2Out);
					} 
					catch(e) {}
					$spinner.hide();                  
                }
            });        
        }
        buttons[jrLanguage['cancel']] = function() { 
			jQuery(this).dialog('close');  
			try {
				params.onCancel();
			} catch (err) {}		
		}
            
        // height and width passed in function call
        params.dialog['dialogClass'] = 'jrDialog';
        params.dialog['modal'] = true;
        params.dialog['autoOpen'] = true;
        params.dialog['buttons'] = buttons;
        
        jQuery('.dialog').dialog('destroy').remove();    
        jQuery("body").append('<div id="'+dialogId+'" class="dialog"></div>');
        
        // Complete data with "hidden" fields
        params.dialogData.tmpl = 'component';
        params.dialogData.format = 'raw';
        params.dialogData.Itemid = jrPublicMenu;            
        
        // Make the ajax request to open the dialog
        jQuery.ajax({
            type: "POST",
            url: s2AjaxUri,
            data: params.dialogData,          
            cache: false, 
            dataType: 'json',  
            success: function(s2Out){ 
                switch(s2Out.action){
                    case 'error':
                        delete params.dialog['buttons'];
                        jQuery('#'+dialogId).html(s2Out.update_text).dialog(params.dialog);                
                    break;
                    default:
                        if(s2Out.update_text!=undefined){
                            jQuery('#'+dialogId).html(s2Out.update_text).dialog(params.dialog);
                            jQuery('#s2AjaxResponse').html(s2Out.response);                
                        } else {
                            jQuery('#'+dialogId).html(s2Out.response).dialog(params.dialog);                            
                        }
                    break;                
                }
                // hide loading image            
                $spinner.hide(); 
                if(params.onAfterDisplay) params.onAfterDisplay();                 
            }
        });        
    }    

    $.fn.s2Confirm = function(params,confirmText) 
    {                
        // show loading image
        var $spinner = jQuery(this).siblings('.jr_loadingSmall');
        $spinner.fadeIn();
        
        jQuery('#s2AjaxResponse').remove();    
        jQuery("body").append('<div id="s2AjaxResponse" style="display:none;"></div>'); 
                
        // Start setting up the dialog
        var buttons = {};
        buttons[jrLanguage['submit']] = function() 
        {  
            // Complete data with "hidden" fields
            params.submitData.tmpl = 'component';
            params.submitData.format = 'raw';            
            params.submitData.Itemid = jrPublicMenu;            
            
            //Submit function
            jQuery.ajax({
              type: "POST",
              url: s2AjaxUri,
              data: params.submitData,          
              cache: false, 
              dataType: 'json',
              success: function(s2Out)
                  {
                    switch(s2Out.action)
                        {
                            case 'error':
                                jQuery('.ui-dialog-buttonpane').slideUp('slow',function(){
                                    jQuery('#s2Confirm').html(s2Out.update_text);
                                }); 
                            break; 
                            case 'update_page':
                                jQuery('#s2Confirm').dialog('close');
                                jQuery('#'+s2Out.target_id).s2ShowUpdate(s2Out);
                            break;       
                            case 'update_dialog':
                                jQuery('.ui-dialog-buttonpane').slideUp('slow',function(){
                                    jQuery('#s2Confirm').html(s2Out.update_text);
                                });                                                 
                            break;                                                                            
                            default:
                                jQuery('#s2Confirm').dialog('close');
                            break;                        
                        }
                    jQuery('#s2AjaxResponse').html(s2Out.response);                                                                  
                  }
            });        
        }   
        buttons[jrLanguage['cancel']] = function() { jQuery(this).dialog('close');  }
        
        // height and width passed in function call
        params.dialog['dialogClass'] = 'jrDialog';
        params.dialog['modal'] = true;
        params.dialog['autoOpen'] = true;
        params.dialog['buttons'] = buttons;
        params.dialog['open'] = function(event,ui){$spinner.hide();};
        if(params.dialog['width']==undefined) params.dialog['width']='400px';
        if(params.dialog['height']==undefined) params.dialog['height']='auto';
        
        jQuery('#s2Confirm').dialog('destroy').remove();    
        jQuery("body").append('<div id="s2Confirm" class="dialog"><p><span class="ui-icon ui-icon-alert" style="float:left; margin:0 7px 20px 0;"></span>'+confirmText+'</p></div>');    
        // Open de dialog       
        jQuery('#s2Confirm').dialog(params.dialog);
    }     
    
    $.fn.s2SubmitNoForm = function(controller,action,data,callbacks)
    {         
        jQuery('#s2AjaxResponse').remove();    
        jQuery("body").append('<div id="s2AjaxResponse" style="display:none;"></div>'); 
        var formId = jQuery(this).parents('form:eq(0)').attr('id');
        var $parentForm = jQuery('#'+formId);            
        var $spinner = jQuery(this).siblings('.jr_loadingSmall');
        $spinner.fadeIn();
        jQuery.ajax({
            type: 'POST',
            url: s2AjaxUri,
            data:'url='+controller+'/'+action+'&'+data+jreviews.ajax_params(),
            dataType: 'json',            
            success: function(s2Out)
            {
                if(s2Out.update_text!=undefined){ 
                    s2Alert(s2Out.update_text);
                } 

                switch(s2Out.action){
                    case 'error':
                        s2Alert(s2Out.update_text);
                    break;                        
                    case 'update_page':
                        $parentForm.s2ShowUpdateScroll(s2Out);                                
                    break;       
                    case 'update_element':
                        jQuery('#'+s2Out.target_id).html(s2Out.update_html).fadeIn();
                    break;                 
                }              
                jQuery('#s2AjaxResponse').html(s2Out.response);  
                if(undefined!=callbacks && undefined!=callbacks.onAfterResponse){ 
                    callbacks.onAfterResponse(s2Out);
                }                                                              
              $spinner.hide();
            }
        });    
    }    

    $.fn.s2SubmitForm = function(callbacks) 
    {
        formId = jQuery(this).parents('form:eq(0)').attr('id');
        var $callbacks = callbacks;
        $parentForm = jQuery('#'+formId);     
        jQuery('#s2AjaxResponse').remove();    
        jQuery("body").append('<div id="s2AjaxResponse" style="display:none;"></div>'); 
        jQuery('#'+formId+' button').attr('disabled','disabled');
        jQuery('#'+formId+' button').click(function(){jQuery('#'+formId+' .jr_validation').remove();});
        $parentForm.find('.jr_loadingSmall').fadeIn();
        jQuery('#s2Msg'+formId).remove();
        jQuery.ajax(
        {
            type: 'POST',
            url: s2AjaxUri,
            data: jQuery('#'+formId).serialize(),
            dataType: 'json',            
            success: function(s2Out)
              {
                switch(s2Out.action)
                {
                    case 'error':
                        s2Alert(s2Out.update_text);
                        jQuery('#s2AjaxResponse').html(s2Out.response);                             
                    break;                        
                    case 'validation':
                        $parentForm.s2ShowValidation(s2Out);
                        $parentForm.find('button').removeAttr('disabled');
                        jQuery('#s2AjaxResponse').html(s2Out.response);                                           
                    break;                        
                    case 'update_page':
                        $parentForm.s2ShowUpdateScroll(s2Out);                                
                    break;       
                    case 'update_element':                              
                        jQuery('#'+s2Out.target_id).html(s2Out.update_html)
                        .fadeIn('fast',function(){
                            jQuery('#s2AjaxResponse').html(s2Out.response);                             
                        })
                        .delay(1)
                        .fadeIn(1,function(){
                            if(undefined!=$callbacks && undefined!=$callbacks.onAfterResponse){ 
                                $callbacks.onAfterResponse(s2Out);
                            }
                        });
                    break;
                    default:       
                        jQuery('#s2AjaxResponse').html(s2Out.response);
                    break;                 
                }

/*                if(undefined!=callbacks && undefined!=callbacks.onAfterResponse){ 
                    callbacks.onAfterResponse(s2Out);
                } */
                                        
                $parentForm.find('.jr_loadingSmall').hide();                                                            
              }        
        });
    }
    
    $.fn.s2ShowValidation = function(s2Out)
        {
            jQuery(this).append('<div class="jr_validation">'+s2Out.update_text+'</div>');
        };  
                    
    $.fn.s2ShowUpdateScroll= function(s2Out)
        {     
            $parentForm.scrollTo({duration:400,offset:-100});
            $parentForm.s2ShowUpdate(s2Out);                                                       
        }
            
    $.fn.s2ShowUpdate = function(s2Out)
        {
            var updateMsgTimer = 500;
            function getUpdateMsgDiv(target_id) 
                {
                    return $('<div id="s2Msg'+target_id+'" class="jr_postUpdate jr_hidden"></div>');
                };   

            var $element = jQuery(this);    

            jQuery('#s2Msg'+s2Out.target_id).remove();    
                            
            $element.before(getUpdateMsgDiv(s2Out.target_id));

            $element.fadeOut(500,function()
                {
                    if(s2Out.remove_class==true) {$element.removeClass()};
                    var $MessageElement = jQuery('#s2Msg'+s2Out.target_id);
                    
                    $MessageElement.html(s2Out.update_text).fadeIn(500,function()
                        {
                            // Checks to see which element should be updated with the updated html
                            if(s2Out.update_html!='')
                            { 
                                if(s2Out.target_id_after!=undefined)
                                    {
                                        jQuery('#'+s2Out.target_id_after).after('<div id="After'+s2Out.target_id_after+'" class="jr_hidden"></div>');
                                        jQuery('#After'+s2Out.target_id_after).html(s2Out.update_html).slideDown(1000,function(){
                                            setTimeout(function(){
                                                $MessageElement.slideUp(1000,function(){
                                                    jQuery('#s2AjaxResponse').html(s2Out.response);                                          
                                                });
                                            },updateMsgTimer); 
                                        });                                                                            
                                    }
                                else if (s2Out.target_id_update!=undefined)
                                    {
                                        jQuery('#'+s2Out.target_id_update).html(s2Out.update_html).slideDown(1000,function(){
                                            setTimeout(function(){
                                                $MessageElement.slideUp(1000,function(){
                                                    jQuery('#s2AjaxResponse').html(s2Out.response);                                          
                                                });
                                            },updateMsgTimer);                                         });                                                                            
                                    }                                         
                                else 
                                    {
                                        $element.html(s2Out.update_html).slideDown(1000,function(){
                                        setTimeout(function(){
                                            $MessageElement.slideUp(1000,function(){
                                                jQuery('#s2AjaxResponse').html(s2Out.response);                                          
                                            });
                                        },updateMsgTimer);                                         });                                    
                                    }
                            } else 
                            {
                                  jQuery('#s2AjaxResponse').html(s2Out.response);                                                                                                              
                            }                                   
                        });                                                       
                });  
        };
        
})(jQuery);      