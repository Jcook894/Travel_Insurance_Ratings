jQuery(document).ready(function() 
{
    jQuery('#toolbar-box').remove();
    jQuery('#submenu-box').remove();
    
    jreviews_admin.menu.init();
    
    /* Set jQuery ajax defaults */
    jQuery.ajaxSetup({url: s2AjaxUri,global: true,type: "POST",cache: false});
      
    /* jQuery ajax defaults */
    jQuery("#spinner").ajaxSend( function() { 
        jQuery(this).show();
        jQuery('#s2AjaxResponse').remove();    
        jQuery("body").append('<div id="s2AjaxResponse" style="display:none;"></div>');         
    });
    
    jQuery("#spinner").ajaxComplete(function() {
            jQuery(this).fadeOut();
            jQuery('.ui-dialog-buttonpane :button').each(function() { 
                jQuery(this).removeClass('ui-button ui-corner-all').addClass('ui-button ui-corner-all');
            });            
     });

    /* Review moderation */
    jQuery('#jr_ownerReplyEdit').dialog({
        autoOpen: false,
        modal: true,    
        width:640,
        height: 420
    });          
    
    /* initializes tabs */  
    jQuery("#jr_tabs").tabs();
    
    /* initialize datepicker global defaults */
    jQuery.datepicker.setDefaults({
        showOn: 'both', 
        buttonImage: datePickerImage, 
        buttonImageOnly: true,
        buttonText: 'Calendar',
        dateFormat: 'yy-mm-dd',
        changeMonth: true,
        changeYear: true                
    });
});

jreviews_admin =
    {
        apply: false, 
        dispatch: function(options) 
            {
                var form, $controller, $action;
                var data = undefined != options.controller ? jQuery.param({'data[controller]':'admin/'+options.controller,'data[action]':options.action}) : {};
                if(undefined == options.type) options.type = 'html';
                if(undefined != options.form_id)
                {
                    form = jQuery('#'+options.form_id);
                    if(undefined != options.controller)
                    {
                        $controller = form.find('input[name=data\\[controller\\]]').detach();
                        $action = form.find('input[name=data\\[action\\]]').detach();
                    }
                    data = form.serialize()+'&'+data;
                }
                if(options.data) data = data + '&' + jQuery.param(options.data)   
                jQuery.ajax({
                    type: (undefined!=options.form_id ? 'POST' : 'GET'),
                    url: s2AjaxUri,
                    data: data,
                    success: function(res){ 
                        if(options.onComplete) options.onComplete(res); 
                        if(undefined!=$controller && undefined!=$action) {
                            form.append($controller, $action);
                        }
                    },
                    dataType: options.type
                }); 
            },
        claims:
            {
                moderate: function(form_id)
                    {
                        jQuery.ajax({
                            url: s2AjaxUri+'&url=admin_claims/_save',
                            type: 'POST',
                            data:jQuery('#'+form_id).serialize(),
                            dataType: 'json', 
                            success: function(s2Out){
                                jQuery('#s2AjaxResponse').html(s2Out.response);
                            }
                        });
                    }
            },        
        criteria:
            {
              save: function(form_id)
                  {
                      jreviews_admin.tools.saveUpdateRow(form_id);                      
                  }  
            },
        category:
            {
                submit: function(form_id)
                    {
                        jQuery.post(s2AjaxUri,
                            jQuery('#'+form_id).serialize(),
                            function(s2Out)
                            {  
                                s2Out = s2Out.response;
                                switch(s2Out.action)
                                {
                                    case 'error':
                                        s2Alert(s2Out.text);
                                    break;
                                    case 'success':
                                        jreviews_admin.dialog.close();
                                        jQuery('#page').html(s2Out.page).fadeIn(1500,function(){
                                            jQuery.each(s2Out.cat_ids, function(key,row) {
                                                jQuery('#category'+row.cat_id).effect('highlight',{},4000);   
                                            });
                                        });
                                    break;
                                }
                            },
                            'json'
                        );                        
                    },
                edit: function(params,options)
                    {
                        options.buttons =  {
                            'Submit': function() 
                                {
                                    jreviews_admin.category.submit('jr_categoryForm');                                  
                                },
                            'Cancel': function() { jQuery(this).dialog('close'); }
                            }
                        jreviews_admin.dialog.form('categories','edit', params, options);                        
                    },
                add: function(params,options)
                    {
                        options.buttons =  {
                            'Submit': function() 
                                {
                                    jreviews_admin.category.submit('jr_categoryFormNew');                                  
                                },
                            'Cancel': function() { jQuery(this).dialog('close'); }
                            }
                        jreviews_admin.dialog.form('categories','create', params, options);                        
                    },
                 remove: function()
                 {
                    if(jQuery('#cat_id').val() > 0) jQuery('#boxchecked').val(0);
                    jreviews_admin.dialog.remove('categories','delete','Are you sure you want to remove the selected categories from working with JReviews? . The categories will NOT be deleted, but the review system will no longer work for listings in the selected categories.',{'form_id':'adminForm'});
                 }                                
            },            
        directory:
            {
                submit: function(form_id)
                    {
                        jQuery.post(s2AjaxUri,
                            jQuery('#'+form_id).serialize(),
                            function(s2Out)
                            {  
                                s2Out = s2Out.response;
                                switch(s2Out.action)
                                {
                                    case 'error':
                                        s2Alert(s2Out.text);
                                    break;
                                    case 'success':
                                        jreviews_admin.dialog.close();
                                        jQuery('#title').val('');
                                        jQuery('#desc').val('');
                                        jQuery('#directorytable').html(s2Out.page);
                                        jreviews_admin.tools.flashRow(s2Out.row_id);                                                
                                    break;
                                }
                            },
                            'json'
                        );                        
                    },
                edit: function(params,options)
                    {
                        options.buttons =  {
                            'Submit': function() 
                                {
                                    jreviews_admin.directory.submit('directoryForm');                                  
                                },
                            'Cancel': function() { jQuery(this).dialog('close'); }
                            };
                        jreviews_admin.dialog.form('directories','edit', params, options);                        
                    },
                remove: function(id)
                    {
                        jreviews_admin.dialog.remove('directories','delete','Are you sure you want to delete this directory?',{'data[entry_id]':id});
                    }                          
            },
        discussion:
            {
                moderate: function(form_id)
                    {
                        jQuery.ajax({
                            url: s2AjaxUri+'&url=admin_discussions/_save',
                            type: 'POST',
                            data:jQuery('#'+form_id).serialize(),
                            dataType: 'json', 
                            success: function(s2Out){
                                jQuery('#s2AjaxResponse').html(s2Out.response);
                            }
                        });                        
                    }
            },                
        field:
            {  
                del: function(id)
                {
                    jQuery('#fieldid').value = id;
                    jreviews_admin.dialog.remove('fields','_delete','This action will also delete all the information already stored for this field. Do you want to continue?.',{'form_id':'adminForm','data[Field][fieldid]':id});
                },
                edit: function(response)
                {            
                    if(response.success == false) {s2Alert(response.msg); return false;}
                    if(undefined != response.page) jQuery('#page').html(response.page);
                    if(undefined != response.adv_options) 
                    {                                    
                        var adv_options = response.adv_options;
                        jQuery('#advanced_options').html(adv_options.page);    
                        jQuery('#s2AjaxResponse').html(adv_options.response);
                    }
                },            
                submit: function(form_id)
                    {
                        var formData = jQuery('#'+form_id).serialize();
                        if(jreviews_admin.apply) {
                            formData += encodeURI('&data[apply]=1');
                            jreviews_admin.apply = false;
                        }                        
                        jQuery.post(s2AjaxUri,
                            formData,
                            function(s2Out)
                            {  
                                s2Out = s2Out.response;
                                switch(s2Out.action)
                                {
                                    case 'apply':
                                        jreviews_admin.tools.apply();
                                    break;                                    
                                    case 'error':
                                        s2Alert(s2Out.text);
                                    break;
                                    case 'success':
                                        jQuery('#page').fadeOut('fast',function(){
                                            jQuery(this).html(s2Out.page).fadeIn('fast',function(){
                                                jQuery('#'+s2Out.row_id).effect('highlight',{},4000);                                                   
                                            });                                            
                                        });
                                    break;
                                }
                            },
                            'json'
                        );                        
                    },
                control_setup: function(parentForm,options)
                {
                    var defaults = {
                        ctrlFieldId     :   '',
                        ctrlField       :   'control_field', // class
                        ctrlValueSearch :   'control_value_search', // class
                        ctrlValueCbDiv  :   'control-value-cb-div',
                        ctrlValue       :   'control_value', // class
                        model           :   'FieldOption',
                        multipleValues  : false  
                    };
                    
                    var settings = jQuery.extend(defaults, options);
                    var $control_field = jQuery('.'+settings.ctrlField,'#'+parentForm);
                    var $control_value_search = jQuery('.'+settings.ctrlValueSearch,'#'+parentForm);
                    var $control_value_cb_div = jQuery('.'+settings.ctrlValueCbDiv,'#'+parentForm);
                    var $field_location = jQuery('.field_location','#'+parentForm).val();
                    var fieldid = jQuery('#fieldid').val(); // Exclude current field from list of control fields

                    // Init vars
                    if($control_field.val() == '' && $control_value_search.val()=='' && $control_value_search.data('defaultValue')) {
                        $control_value_search.val($control_value_search.data('defaultValue')).attr('disabled','disabled');
                    } 
                    if($control_value_cb_div.find(':input').length==0) $control_value_cb_div.hide();
                    $control_value_search.data('field_id',settings.ctrlFieldId);
                    $control_field.data('old_val',$control_field.val());
                    // When editing, bind click event to value checkboxes to remove them
                    jQuery('.'+settings.ctrlValueCbDiv +' :input:checkbox','#'+parentForm).bind('change',function(){
                        if($control_value_cb_div.find(':input').length==1) $control_value_cb_div.hide();
                        jQuery(this).parent('label').remove();
                    })
                    
                    // Setup the control field
                    $control_field.bind('blur',function()
                    {
                        if(this.value == '' || this.value != $control_field.data('old_val')) {
                            $control_value_search.val($control_value_search.data('defaultValue')).attr('disabled','disabled'); 
                            $control_value_cb_div.html('').hide();       
                        } else {
                            $control_value_search.removeAttr('disabled');        
                        }
                        $control_field.data('old_val',$control_field.val());
                    })
                    .autocomplete({
                            source: function( request, response ) {
                                if($control_field.data('cache.'+request.term)) {
                                    return response($control_field.data('cache.'+request.term));
                                }
                                else {
                                    jreviews_admin.dispatch({
                                        'type':'json',
                                        'controller':'admin_control_fields',
                                        'action':'_loadFields',
                                        'data': {
                                            "data[limit]": 12,           
                                            "data[field]": request.term,
                                            "data[fieldid]": fieldid,
                                            "data[location]":$field_location 
                                        },
                                        'onComplete':function( data ) { 
                                            $control_field.data('cache.'+request.term, data);
                                            response(data); 
                                        }
                                    });
                                }
                            },
                            minLength: 2,
                            select: function( event, ui ) {
                                // Load relevant field values in new list
                                $control_value_search.data('field_id',ui.item.id).val('');
                                ui.item.id != '' ? 
                                    $control_value_search.removeAttr('disabled').focus()
                                    :
                                    $control_value_search.attr('disabled','disabled');
                                    if(this.value == '' || this.value != $control_field.data('old_val')) {
                                        $control_value_cb_div.html('').hide();       
                                    }                                     
                            }
                    }); 
                      
                    // Setup the control value
                    $control_value_search.autocomplete({
                        source: function( request, response ) {
                            // Make sure
                            // Create array of current selected checkbox values
                            var checkedValues = $control_value_cb_div.find(':input:checked').map(function(i, cb) {
                              return this.value;
                            });

                            if($control_value_search.data('cache.'+request.term)) {       
                                var cachedData = $control_value_search.data('cache.'+request.term);
                                var dataResp = [];
                                jQuery(cachedData).each(function(i,row) {
                                    if(row!=undefined)if(jQuery.inArray(row.value,checkedValues) == -1) dataResp.push(cachedData[i]);    
                                });     
                                response(dataResp);
                            } 
                            else {
                                jreviews_admin.dispatch({
                                    'type':'json',
                                    'controller':'admin_control_fields',
                                    'action':'_loadValues',
                                    'data': {
                                        "data[limit]": 12,          
                                        "data[field_id]": $control_value_search.data('field_id'), 
                                        "data[value]": request.term
                                    },
                                    'onComplete':function( data ) { 
                                        $control_value_search.data('cache.'+request.term, data);
                                        var dataResp = [];
                                        jQuery(data).each(function(i,row) {
                                            if(row!=undefined)if(jQuery.inArray(row.value,checkedValues) == -1) dataResp.push(data[i]);    
                                        });     
                                        response(dataResp);
                                    }
                                });
                            }
                        },
                        search: function( event, ui) { 
                            if($control_value_search.data('field_id') == '') return false;
                        },
                        minLength: 1,
                        select: function( event, ui ) 
                        {   
                            if(ui.item.value != '') {
                                var checkboxAttr = {
                                    'class':settings.ctrlValue+'-'+ui.item.value,
                                    'name':'data['+settings.model+'][control_value][]',
                                    'value':ui.item.value
                                };
                                var checkbox = jQuery('<input type="checkbox" checked="checked" />').attr(checkboxAttr);
                                checkbox.click(function(){
                                    if($control_value_cb_div.find(':input').length==1) $control_value_cb_div.hide();
                                    jQuery(this).parent('label').remove()
                                    $control_value_search.removeData(ui.item.value);                        
                                });
                                var label = jQuery('<label for="'+settings.ctrlValue+'-'+ui.item.value+'" />').css('text-align','left');
                                label.append(checkbox).append('<span>'+ui.item.label+'</span>');
                                $control_value_search.val('').focus();
                                $control_value_cb_div.show().append(label);
                            }
                            jQuery(this).val(''); 
                            return false;
                    }                            
                    });
                    jQuery('.ui-autocomplete').css('white-space','nowrap');
                }                    
            }, 
        fieldoption:
            {
                edit: function(params,options)
                    {
                        options.buttons =  {
                            'Submit': function() 
                                {
                                    jreviews_admin.fieldoption.submit('jr_editFieldOptionsForm');                                  
                                },
                            'Cancel': function() { jQuery(this).dialog('close'); }
                            };
                        jreviews_admin.dialog.form('fieldoptions','edit', params, options);                        
                    },
                submit: function(form_id)
                    {
                        jQuery.post(s2AjaxUri,
                            jQuery('#'+form_id).serialize(),
                            function(s2Out)
                            {  
                                jQuery('#'+form_id+' .jr-validation').remove();                                      
                                if(s2Out.action == 'success') {
                                        jreviews_admin.dialog.close();
                                        jQuery('#optionlist').html(s2Out.page).fadeIn(1500,function(){
                                            jQuery('#option_text,#option_value,#option_image','#'+form_id).val('');
                                            jreviews_admin.tools.flashRow('fieldoption'+s2Out.option_id);   
                                        });
                                }
								else if(s2Out.action == 'error') {
									s2Alert(s2Out.update_text);
								}
                                else {
                                    jQuery(s2Out.validation_ids).each(function(i,v){
                                        var $validation = jQuery('<span class="jr-validation"></span>').html(jQuery('#'+v).data('validation')); 
                                        jQuery('#'+v).after($validation).show();
                                    })
                                }
                            },
                            'json'
                        );                        
                    }                                        
            },                               
        group: 
            {
                changeType: function(element,form_id)
                    {   
                        jQuery('#group_type').val(element.value);
                        jQuery('#page_number').val(1);                        
                        jQuery('#action').val('index');
                        jQuery.post(s2AjaxUri,
                            jQuery('#'+form_id).serialize(),
                            function(s2Out)
                            {                          
                                jQuery('#page').html(s2Out);
                            }
                            ,'html'
                        );                        
                    },
                edit: function(params,options)
                    {
                        options.buttons =  {
                            'Submit': function() {
                                jreviews_admin.tools.saveUpdateRow('groupsForm','jr_formDialog');
                            },
                            'Cancel': function() { jQuery(this).dialog('close'); }
                        };
                        jreviews_admin.dialog.form('groups','edit', params, options);                        
                    },                         
                submit: function(form_id,validation_id)
                    {
                        jreviews_admin.tools.saveUpdateRow(form_id,'jr_groupNew');                                              
                    },
                reorder: function(group_id,direction)
                    {
                        jreviews_admin.tools.reorder('groups','_changeOrder',group_id,direction,'fieldgroup');
                    },
                reorder_page: function()
                    {   
                        jQuery('#action').val('_saveOrder');
                        jQuery.post(s2AjaxUri,jQuery('#adminForm').serialize(),function(s2Out){
                            s2Out = s2Out.response;
                            if(s2Out.page!=undefined)
                            {
                                jQuery('#page').html(s2Out.page);
                            }
                            s2Alert(s2Out.text);
                        },'json');
                    },
                remove: function(group_id,field_count,confirm_text,options)
                    {
                        if(field_count>0){
                            alert("To delete this group you first need to delete all the fields associated with it in the Fields Manager.");
                        } else {
                            jreviews_admin.dialog.remove('groups','_delete',confirm_text,{'data[entry_id]':group_id},options);
                        }                        
                    },                 
                toggleTitle: function(group_id)
                    {
                        jQuery.get(s2AjaxUri+'&url=groups/toggleTitle/group_id:'+group_id,
                            function(s2Out)
                            {       
                                var img = jQuery('#showTitle_'+group_id).find('img');
                                var src = img.attr('src')
                                if(s2Out == '1'){  
                                    img.attr('src',src.replace("status_off","status_on"));
                                } else {
                                    img.attr('src',src.replace("status_on","status_off"));
                                }  
                                jreviews_admin.tools.flashRow('fieldgroup'+group_id);                                                                      
                            }
                            ,'text'
                        );
                    }                    
            },
         listing:
            {
            del: function(id)
            {
                jreviews_admin.dialog.remove('admin_listings','_delete','This action will delete the listing along with its custom fields and reviews. Are you sure you want to continue?',{'data[entry_id]':id});
            },
            edit: function (id,referrer)                         
                {                   
                    if(undefined == referrer) referrer = 'browse';
                    jQuery.get(s2AjaxUri+'&url=admin_listings/edit/id:'+id+'/referrer:'+referrer,function(page){
                        jQuery('#jr_pgContainer').fadeOut('fast',function(){
                            jQuery('#jr_editContainer')
                                .html(page)
                                .fadeIn('fast',function(){
                                    jQuery('#jr_listingTitle').focus();
                                    jQuery('.wysiwyg_editor').RemoveTinyMCE(); /* required so the editor can be added again on new section/category changes*/
                                    jQuery('.wysiwyg_editor').tinyMCE();

                                    jreviews.user.autocomplete(jQuery('#jrUsername','#jr_listingForm'),{'target_user_id':'jrUserId','target_name':'jrUsername','target_email':'jrUserEmail'});
                                    
                                    // Load custom field data
                                    jreviews.controlFieldListing = new jreviewsControlField('jr_listingForm','cat_id');
                                    jreviews.controlFieldListing.loadData({'entry_id':jQuery('#listing_id','#jr_listingForm').val(),'value':false,'page_setup':true});  
                                    jQuery('#page').scrollTo(500);
                                });
                            ;
                        });                           
                    },'html');
                },                
            moderate: function(form_id)
                {
                    jQuery.ajax({
                        url: s2AjaxUri+'&url=admin_listings/_saveModeration',
                        type: 'POST',
                        data:jQuery('#'+form_id).serialize(),
                        dataType: 'json', 
                        success: function(s2Out){
                            jQuery('#s2AjaxResponse').html(s2Out.response);
                        }
                    });                        
                },
            moderateLoadMore: function()
            {
                jQuery('#jr_loadMoreSpinner').css('display','inline');
                var page = parseInt(jQuery('#jr_page').val());
                var new_page = page+1;
                var num_pages = jQuery('#jr_num_pages').val();
                jQuery('#jr_page').val(new_page);
                jQuery.ajax({
                    url: s2AjaxUri+'&url=admin_listings/moderation',
                    type: 'POST',
                    data:jQuery('#jr_pageScroll').serialize(),
                    dataType: 'html', 
                    success: function(s2Out){
                        jQuery('#jr_loadMoreSpinner').css('display','none');
                        jQuery('#jr_loadMore').before(s2Out);
                        if(num_pages == new_page){
                            jQuery('#jr_loadMore').remove();
                        }
                        
                    }
                });                                            
            },    
            submit: function()
            {
                var form = jQuery('#jr_listingForm');
                form.find('#section').val(form.find('#section_id option:selected').text());
                form.find('#category').val(form.find('#cat_id option:selected').text());
                jQuery('.wysiwyg_editor').RemoveTinyMCE();

                /* Add form validation fields */
                var valid_fields = jreviews.form.getValidationFields(form.attr('id')).join(',');
                form.append('<input type="hidden" id="valid_fields" name="data[valid_fields]" value="'+valid_fields+'" />');
                form.submit();
                jQuery('#valid_fields','#jr_listingForm').remove();
            },
            submitAsNew: function() 
            {
                var form = jQuery('#jr_listingForm');
                form.find('#section').val(form.find('#section_id option:selected').text());
                form.find('#category').val(form.find('#cat_id option:selected').text());
                form.append('<input type="hidden" name="data[saveAsNew]" value="1" />');
                jQuery('.wysiwyg_editor').RemoveTinyMCE();
                /* Add form validation fields */
                var valid_fields = jreviews.form.getValidationFields(form.attr('id')).join(',');
                form.append('<input type="hidden" id="valid_fields" name="data[valid_fields]" value="'+valid_fields+'" />');
                form.submit();
                jQuery('#valid_fields','#jr_listingForm').remove();
            },
            setMainImage: function(element,options)
                {
                    jQuery(element).s2SubmitNoForm('admin_listings','_imageSetMain','data[listing_id]='+options.listing_id+'&data[image_path]='+options.image_path+'&'+options.token+'=1');                    
                },
            deleteImage: function(element,options)
                {
                    var data =  {
                        'url':'admin_listings/_imageDelete/',
                        'data[listing_id]':options.listing_id,
                        'data[delete_key]':options.delete_key,
                        'data[image_path]':options.image_path
                    };
                    data[options.token] = 1;
                    jQuery(element).s2Confirm({'dialog':{'title':options.title},'submitData': data},options.text);                    
                }          
            }, 
        listing_type: {
            del: function(id) {
                    jreviews_admin.dialog.remove('listing_types','_delete','Deleting this Listing Type will also remove all reviews for listings that have this listing type. Do you want to continue?',{'data[entry_id]':id});                
            }            
        },                 
        menu:
            {
                init: function()
                    {
                        jQuery('#listing_moderation').click(function() { jreviews_admin.menu.load('admin_listings','moderation')});
                            
                        jQuery('#review_moderation').click(function() { jreviews_admin.menu.load('reviews','moderation')});
                            
                        jQuery('#claims').click(function() { jreviews_admin.menu.load('admin_claims','moderation')});
                      
                        jQuery('#owner_reply_moderation').click(function() { jreviews_admin.menu.load('admin_owner_replies','index')});

                        jQuery('#discussion_moderation').click(function() { jreviews_admin.menu.load('admin_discussions','index')});
                        
                        jQuery('#reports').click(function() { jreviews_admin.menu.load('admin_reports','index')});

                        jQuery('#groups').click(function() { jreviews_admin.menu.load('groups','index')});

                        jQuery('#fields').click(function() { jreviews_admin.menu.load('fields','index')});
                             
                        jQuery('#listing-types').click(function() { jreviews_admin.menu.load('listing_types','index')});
                        
                        jQuery('#categories').click(function() { jreviews_admin.menu.load('categories','index')});

                        jQuery('#directories').click(function() { jreviews_admin.menu.load('directories','index')});

                        jQuery('#configuration').click(function() { jreviews_admin.menu.load('configuration','index')});
                        
                        jQuery('#access').click(function() { jreviews_admin.menu.load('access','index')});

                        jQuery('#themes').click(function() { jreviews_admin.menu.load('themes','index')});
                        
                        jQuery('#seo').click(function() { jreviews_admin.menu.load('seo','index')});

                        jQuery('#predefined_replies').click(function() { jreviews_admin.menu.load('admin_predefined_replies','index')});
                           
                        jQuery('#updater').click(function() { jreviews_admin.menu.load('admin_updater','index')});

                        jQuery('#rebuild-reviewer-ranks').click( function() {jreviews_admin.tools.rebuildReviewerRanks();} ); 

                        jQuery('#clear_cache').click( function() {jreviews_admin.tools.clearCache();} );

                        jQuery('#clear_registry').click( function() {jreviews_admin.tools.clearRegistry();} );
                    },
                load: function(controller,action) 
                    {      
                        jQuery.get(s2AjaxUri,
                            {'data[controller]':'admin/'+controller,'data[action]':action},
                            function(page)
                            {  
                                jQuery('#page').fadeOut('fast').delay(1).queue(function(n) {
                                    jQuery(this).html(page);
                                    n();
                                    if(jQuery('.dialog').is(':data(dialog)')) 
                                    {
                                        jQuery('.dialog').dialog('destroy').remove();                                                                                                                                                      
                                    }                                    
                                }).fadeIn('fast');
                            },
                            'html'
                        ); 
                    },
                load_main: function()
                {
                    jQuery('#jr_adminPage').scrollTo({'duration':250});
                    jQuery('#addon_module').slideUp('slow',function(){jQuery('#main_modules').slideDown('slow');});
                },
                moderation_counter: function(element_id)
                    {
                        $index = jQuery('#'+element_id);
                        var val = parseInt($index.html());
                        $index.html(--val);
                        if(val==0){
                            $index.parents('li').remove();
                        }                     
                }
            },
        report:
            {
                moderate: function(form_id)
                    {
                        jQuery.ajax({
                            url: s2AjaxUri+'&url=admin_reports/_save',
                            type: 'POST',
                            data:jQuery('#'+form_id).serialize(),
                            dataType: 'json', 
                            success: function(s2Out){
                                jQuery('#s2AjaxResponse').html(s2Out.response);
                            }
                        });
                    }
            },
        tools:
            {
                apply: function()
                    {
                        jreviews_admin.tools.statusUpdate("Your changes were applied.");
                    },
                checkAll: function (a,b){
                    b||(b="cb");if(a.form){for(var c=0,d=0,f=a.form.elements.length;d<f;d++){var e=a.form.elements[d];if(e.type==a.type&&(b&&e.id.indexOf(b)==0||!b))e.checked=a.checked,c+=e.checked==!0?1:0}if(a.form.boxchecked)a.form.boxchecked.value=c;return!0}else{for(var e=document.adminForm,c=e.toggle.checked,f=a,g=0,d=0;d<f;d++){var h=e[b+""+d];if(h)h.checked=c,g++}document.adminForm.boxchecked.value=c?g:0}
                },
                rebuildReviewerRanks: function() 
                    {
                        jQuery.get(s2AjaxUri,{'data[controller]':'admin/common','data[action]':'_rebuildReviewerRanks'},function(s2Out){s2Alert(s2Out);});   
                    },
                clearCache: function() 
                    {
                        jQuery.post(s2AjaxUri,{'data[controller]':'admin/common','data[action]':'clearCache'},function(s2Out){s2Alert(s2Out);});   
                    },
                clearRegistry: function() 
                    {
                        jQuery.post(s2AjaxUri,{'data[controller]':'admin/common','data[action]':'clearFileRegistry'},function(s2Out){s2Alert(s2Out);});   
                    },
                flashRow: function(row_id) 
                {
                        jQuery('#'+row_id).effect('highlight',{},4000);    
                },
                saveUpdateRow: function(form_id,validation_id)
                    {
                        if(validation_id!=undefined){
                            $form = jQuery('#'+validation_id);                            
                        } else {
                            $form = jQuery('#'+form_id);                                                        
                        }
                        $form.find('.jr_validation').remove();
                        var formData = jQuery('#'+form_id).serialize();
                        if(jreviews_admin.apply) {
                            formData += encodeURI('&data[apply]=1');
                            jreviews_admin.apply = false;
                        }
                        jQuery.post(s2AjaxUri,
                            formData,
                            function(s2Out)
                            {  
                                s2Out = s2Out.response;
                                switch(s2Out.action)
                                {
                                    case 'apply':
                                        $form.remove('#jrValidation');
                                        jreviews_admin.tools.apply();
                                        //s2Alert(s2Out.text);
                                    break;
                                    case 'error':                               
                                        $form.prepend('<div id="jrValidation" class="ui-widget" style="margin-bottom:10px;"><div style="line-height:1.5em;padding:10px 0 5px 5px;font-size: 12px;" class="jr_validation ui-state-highlight ui-corner-all">'+s2Out.text+'</div></div>');
                                        //s2Alert(s2Out.text);
                                    break;
                                    case 'success':
                                        jreviews_admin.dialog.close();
                                        jQuery('.dialog').dialog('destroy').remove();                                                                                                              
                                        if(s2Out.fade==false)
                                        {
                                            jQuery('#page').html(s2Out.page).fadeIn('normal',function(){
                                                jreviews_admin.tools.flashRow(s2Out.row_id)                                                
                                            });
                                        } else {
                                            jQuery('#page').append('&nbsp;').fadeOut('normal',function()
                                            {                
                                                jQuery('#page').html(s2Out.page).fadeIn('normal',function(){
                                                    jreviews_admin.tools.flashRow(s2Out.row_id)                                                
                                                });
                                            });
                                        }
                                    break;
                                }
                            },
                            'json'
                        );                             
                    },
                slug: function(text, options) 
                    {
                        var defaults = {
                            spaceReplaceChar    :   '', // Replacement char for spaces
                            numbers             :   true
                        };
                        var settings = jQuery.extend(defaults, options);
                        return text.replace(settings.numbers ? /[^a-zA-Z0-9\s]+/g : /[^a-zA-Z\s]+/g,'').replace(/ /g, settings.spaceReplaceChar).toLowerCase();
                    },
                removeRow: function(row_id) 
                    {
                    jQuery('#'+row_id).effect('highlight',{},1000).fadeOut('medium',function(){jQuery(this).remove();});
                    },                    
                reorder: function(controller,action,entry_id,direction,row_prefix)
                    {
                        jQuery.get(s2AjaxUri+'&url='+controller+'/'+action+'/entry_id:'+entry_id+'/direction:'+direction,
                            function(s2Out)
                            {
                                jQuery('#page').html(s2Out).fadeIn('fast',function(){
                                    jreviews_admin.tools.flashRow(row_prefix+entry_id);                                                                                                          
                                });
                            }
                            ,'html'
                        );
                    },
                toggleIcon: function(element_id,state,img_on,img_off)
                {  
                    var img = jQuery('#'+element_id+' img');
                    var src = img.attr('src');
                    parseInt(state) == 1 
                        ? 
                        img.attr('src',src.replace(img_off,img_on))
                        :
                        img.attr('src',src.replace(img_on,img_off))
                    ;
                },
                moderateLoadMore: function(controller,action)
                {
                    jQuery('#jr_loadMoreSpinner').css('display','inline');
                    var page = parseInt(jQuery('#jr_page').val());
                    var new_page = page+1;
                    var num_pages = jQuery('#jr_num_pages').val();
                    jQuery('#jr_page').val(new_page);
                    jQuery.ajax({
                        url: s2AjaxUri+'&url='+controller+'/'+action,
                        type: 'POST',
                        data:jQuery('#jr_pageScroll').serialize(),
                        dataType: 'html', 
                        success: function(s2Out){
                            jQuery('#jr_loadMoreSpinner').css('display','none');
                            jQuery('#jr_loadMore').before(s2Out);
                            if(num_pages == new_page){
                                jQuery('#jr_loadMore').remove();
                            }
                            
                        }
                    });
                },
                statusUpdate: function(msg)
                {
                    jQuery('#status').html(msg).fadeIn('medium').delay(1500).fadeOut('slow');                            
                }                                                
            },
        dialog:
            {   
                close: function()
                    {
                        jQuery('.dialog').dialog('close');    
                    },
                remove: function(controller,action,confirm_text,data,options)
                    {
                        var confirm_element = '<p><span class="ui-icon ui-icon-alert" style="float:left; margin:0 7px 20px 0;"></span>'+confirm_text+'</p>';
                        var defaults = {
                            'title': 'Delete confirmation',
                            'modal': true,
                            'autoOpen': true,
                            'width': '600px',
                            'height': 'auto',                                
                            'buttons':
                                {
                                    'Delete': function() {
                                        var params = {'controller':controller,'action':action,'type':'json','onComplete':function(res){jQuery('#s2AjaxResponse').html(res.response);}};
                                        if(undefined!=data) {
                                            if(undefined!=data.form_id) params.form_id = data.form_id;
                                            params.data = data;
                                        }
                                        jreviews_admin.dispatch(params);
                                    },
                                    'Cancel': function() {
                                        jQuery(this).dialog('close');
                                    }                                    
                                }
                        };
                        var settings = jQuery.extend(defaults, options);
                        jQuery('.dialog').dialog('destroy').remove();    
                        jQuery("body").append('<div id="jr_deleteDialog" class="dialog"></div>');
                        jQuery('#jr_deleteDialog').html(confirm_element).dialog(settings);   
                    },                            
                preview: function(html_id,options)
                    {
                        var dialog_id = 'jr_previewDialog';
                        var defaults = {
                            'modal': true,
                            'autoOpen': true,
                            'buttons': function() {},
                            'width': '600px',
                            'height': 'auto'
                        }
                        var settings = jQuery.extend(defaults, options);
                        jQuery('.dialog').dialog('destroy').remove();    
                        jQuery("body").append('<div id="'+dialog_id+'" class="dialog"></div>');
                        jQuery('#'+dialog_id).html(jQuery('#'+html_id).html()).dialog(settings);                            
                    },
               form: function(controller,action,params,options)
                   {
                            var dialog_id = 'jr_formDialog';
                            
                            var defaults = {
                                'modal': true,
                                'autoOpen': true,
                                'buttons': function() {},
                                'width': '650px',
                                'height': 'auto'
                            }
                            var settings = jQuery.extend(defaults, options);       
                                            
                            if(jQuery('.dialog').is(':data(dialog)')) 
                            {                            
                                jQuery('.dialog').dialog('destroy').remove();    
                            }
                            jQuery("body").append('<div id="'+dialog_id+'" class="dialog"></div>');

                            jQuery('#'+dialog_id).load
                            (
                                s2AjaxUri+'&url='+controller+'/'+action+'&'+params,
                                function(){
                                    jQuery(this).dialog(settings);                            

                                    jQuery('.ui-dialog-buttonpane :button').each(function() { 
                                        jQuery(this).removeClass('ui-button ui-corner-all').addClass('ui-button ui-corner-all');
                                    });
                                }
                            );                   
                   }                                                 
            },
         review:
            {
                del: function(id)
                {
                    jreviews_admin.dialog.remove('reviews','_delete','Are you sure you want to delete this review?',{'data[entry_id]':id});
                },
                moderate: function(form_id)
                    {
                        jQuery.ajax({
                            url: s2AjaxUri+'&url=reviews/_save',
                            type: 'POST',
                            data:jQuery('#'+form_id).serialize(),
                            dataType: 'json', 
                            success: function(s2Out){
                                jQuery('#s2AjaxResponse').html(s2Out.response);
                            }
                        });                        
                    },
                moderateLoadMore: function()
                    {
                        jQuery('#jr_loadMoreSpinner').css('display','inline');
                        var page = parseInt(jQuery('#jr_page').val());
                        var new_page = page+1;
                        var num_pages = jQuery('#jr_num_pages').val();
                        jQuery('#jr_page').val(new_page);
                        jQuery.ajax({
                            url: s2AjaxUri+'&url=reviews/moderation',
                            type: 'POST',
                            data:jQuery('#jr_pageScroll').serialize(),
                            dataType: 'html', 
                            success: function(s2Out){
                                jQuery('#jr_loadMoreSpinner').css('display','none');
                                jQuery('#jr_loadMore').before(s2Out);
                                if(num_pages == new_page){
                                    jQuery('#jr_loadMore').remove();
                                }
                                
                            }
                        });                                            
                    },                    
				submit: function(element) {
                    var $form = jQuery('#reviewFormEdit');
                    var form_id = $form.attr('id');
                    var valid_fields = jreviews.form.getValidationFields(form_id).join(',');
				   $form.append('<input type="hidden" id="valid_fields" name="data[valid_fields]" value="'+valid_fields+'" />');
					jreviews_admin.dispatch({'controller':'reviews','action':'_save','form_id':form_id,'type':'json','onComplete':function(res){jQuery('#s2AjaxResponse').html(res.response);
					jQuery('#valid_fields','#'+form_id).remove();}});
				}
			}
    }

// SEO Manager edit-in-place    
jQuery.fn.makeEIP = function() 
{
    jQuery(this).click(function(i)
    {
        var allEIP = jQuery("a.eip");
        allEIP.next('div').remove();
        allEIP.show();
        var type = jQuery(this).attr('data-type'),
            fieldid = jQuery(this).attr('data-fieldid'),
            column = jQuery(this).attr('data-column'),
            inputid = column+fieldid,
            div = jQuery('<div></div>').attr({id:'#div_'+inputid}),
            button = jQuery('<button />').attr({
                    'class':'ui-button-small ui-corner-all',
                    'innerHTML':'Save'
                })
                .bind('click',function(){
                    jreviews_admin.dispatch({'controller':'seo','action':'saveInPlace','data':{
                        'data[fieldid]':fieldid,
                        'data[column]':column,
                        'data[text]':jQuery('#'+inputid).val()},
                        'onComplete':function(){
                            var input = jQuery('#'+inputid); 
                            input.parent().prev('a').html(input.val()!='' ? input.val() : '[edit]').show();
                            input.parent().remove();
                        }});
                    return false;
                });
             
        switch(type)                                                                                
        {
            case 'text':
                var input = jQuery('<input type="'+type+'" />').attr({id:inputid,'value':jQuery(this).html()});    
            break;
            case 'textarea':
                button.css({'position':'relative','top':'-4em'});
                var input = jQuery('<textarea></textarea>').attr({id:inputid,style:'width:75%;height:5em;','value':jQuery(this).html()});    
            break;
        }
        jQuery(div).html(input).append('&nbsp;').append(button);
        jQuery(this).hide().after(div);
        if(jQuery('#'+inputid).val()=='[edit]') {jQuery('#'+inputid).val('');}
        jQuery(input).focus();
    });
};
 
/* Configuration functions */
function clearSelect(name) {
    var element = document.getElementById(name);
    count = element.length;
    for (i=0; i < count; i++) {
        element.options[i].selected = '';
    }
}

function addNewCriteria(rowId)
{
    var tbody = document.getElementById('criteria_list').tBodies[0]; 
    var row = document.createElement('tr');
    row.setAttribute('id', rowId);
    var cell1 = document.createElement('td'); 
    var inp1 = document.createElement('input'); 
    inp1.setAttribute('name','data[Criteria][criteria]['+rowId+']');
    inp1.setAttribute('size',35);
    cell1.appendChild(inp1);
    var cell2 = document.createElement('td');
    cell2.style.textAlign = 'center';
    var inp2a = document.createElement('input');
    inp2a.setAttribute('name','data[Criteria][required]['+rowId+']');
    inp2a.setAttribute('type','hidden');
    inp2a.setAttribute('value','0');
    cell2.appendChild(inp2a);
    var inp2b = document.createElement('input');
    inp2b.setAttribute('name','data[Criteria][required]['+rowId+']'); // if checked, will override first 'Required' element value
    inp2b.setAttribute('type','checkbox');
    inp2b.setAttribute('value','1');
    inp2b.setAttribute('id', 'required'+rowId);
    /*inp2b.onclick = function () { disableWeight(row.getAttribute('id')) }; // must be done exactly in this format for IE*/
    cell2.appendChild(inp2b); // append first to overcome IE bug with 'checked'
    inp2b.setAttribute('checked','checked');
    var cell3 = document.createElement('td'); 
    var inp3 = document.createElement('input'); 
    inp3.setAttribute('name','data[Criteria][weights]['+rowId+']');
    inp3.setAttribute('size',5);
    inp3.setAttribute('id', 'weight'+rowId);
    inp3.onkeyup = function () { sumWeights() }; // must be done exactly in this format for IE
    cell3.appendChild(inp3);
    var cell4 = document.createElement('td');
    var inp4 = document.createElement('input'); 
    inp4.setAttribute('name','data[Criteria][tooltips]['+rowId+']');
    inp4.setAttribute('size',50);
    cell4.appendChild(inp4);
    var cell5 = document.createElement('td');
    var inp5 = document.createElement('button'); 
    inp5.innerHTML = 'Remove';
    inp5.setAttribute('class','ui-button');
    inp5.setAttribute('className','ui-button'); // IE
    // inp5.setAttribute('onclick', 'removeCriteria('+rowId+')'); works only in FF
    inp5.onclick = function () { removeCriteria(row.getAttribute('id'));return false; }; // must be done exactly in this format for IE
    cell5.appendChild(inp5);
    row.appendChild(cell1);
    row.appendChild(cell2); 
    row.appendChild(cell3); 
    row.appendChild(cell4); 
    row.appendChild(cell5); 
    tbody.appendChild(row);
    return ++rowId;
}
function removeCriteria(rowId)
{
    var tbl=document.getElementById('criteria_list').tBodies[0];

    for ( var i = 1; i < tbl.rows.length; i++ ) // we don't need the first row, it's titles
    {    
        if ( tbl.rows.length == 2 ) // there is only one input row - don't remove it, clean it
        {
            var inputs = tbl.rows[1].getElementsByTagName("input");
            
            for ( var j = 0; j < inputs.length; j++ )
            {
                if ( inputs[j].type == 'checkbox' ) // 'Required' checkbox defaults to yes
                {
                    inputs[j].checked = true;
                }
                
                else if ( inputs[j].type != 'button' )
                {
                    inputs[j].value = '';
                //    inputs[j].disabled = false;
                }
                
            }
            
            return;
        }
        
        if ( tbl.rows[i].getAttribute('id') == rowId )
        {    
            var deltr = tbl.rows[i];
            break;
        }
    }
    
    tbl.removeChild(deltr);
    
    sumWeights();
}
function sumWeights()
{
    var tbl = document.getElementById('criteria_list').tBodies[0].rows;
    var sumw = 0;
    
    for ( var i = 1; i < tbl.length; i++ ) // no title row
    {
        sumw += document.getElementById('weight'+tbl[i].id).value * 1; // using tbl[i].id allows it to work even when rows are removed and ID's get scrambled
    }
    
    document.getElementById('title_weights').style.display = 'inline';
    document.getElementById('sum_weights').innerHTML = ( isNaN(sumw) ? 'Invalid' : ( sumw == 0 ? 'No weights' : sumw) );
    
    document.getElementById('sum_weights').style.color = sumw == 100 ? 'blue' : 'black';
}

/* Predefined email reply functions */
function showCannedResponse(recordId, predefinedReplyId, suffix){
    if(predefinedReplyId!=''){
        jQuery('#jr_emailBody'+suffix+recordId).val( jQuery('#jr_cannedResponse'+suffix+predefinedReplyId).html() );
        jQuery('#jr_emailSubject'+recordId).val(jQuery('#jr_cannedResponseSelect'+recordId+' option:selected').text());
    } else {
        jQuery('#jr_emailBody'+suffix+recordId).val('');
    }
}