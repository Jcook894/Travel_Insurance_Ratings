/**
 * JReviews - Reviews Extension
 * Copyright (C) 2010-2011 ClickFWD LLC
 * This is not free software, do not distribute it.
 * For licencing information visit http://www.reviewsforjoomla.com
 * or contact sales@reviewsforjoomla.com
**/ 
jreviews = 
{                            
    controlFieldListing: {}, // Make the class instance available to other scripts
    ajax_params: function() 
        {
            return '&Itemid='+jrPublicMenu; 
        },
    ajax_init: function()
        {
            jQuery(document).ready(function() 
                {               
                    /* Set jQuery ajax defaults */
                    jQuery.ajaxSetup({
                      url: s2AjaxUri, // pass controller/action as hidden fields in form data[controller],data[action]
                      global: true,
                      cache: false
                    });
                    
                    /* jQuery ajax actions */
                    jQuery().ajaxSend( function( r, s ) {
                        jQuery('#s2AjaxResponse').remove();    
                        jQuery("body").append('<div id="s2AjaxResponse" style="display:none;"></div>'); 
                    });
                    
                    jQuery().ajaxStop( function( r, s ) {
                    });
                });          
        }, 
    getScript: function(script,callback)
    {
        jQuery.ajax({type: "GET",url: script, success: function(){if(undefined!=callback) callback();},dataType: "script", cache: true});            
    }, 
    dispatch: function(options) 
        {       
            options = options || {};
            var method = undefined!=options.form_id ? 'POST' : 'GET';
            if(undefined!=options.method) method = options.method; 
            var data = undefined != options.controller ? jQuery.param({'data[controller]':options.controller,'data[action]':options.action}) : {};
            var type = options.type || "json";
            if(undefined != options.form_id)
            {
                var form = jQuery('#'+options.form_id);
                if(undefined != options.controller)
                {
                    form.find('input[name=data\\[controller\\]], input[name=data\\[action\\]]').remove();
                }
                data = form.serialize()+'&'+data;
            }
            if(options.data) data = data + '&' + jQuery.param(options.data);   
            jQuery.ajax({type: method, url: s2AjaxUri, data: data, success: function(res){if(options.onComplete) options.onComplete(res);}, dataType: options.type}); 
        },      
    datepicker: function() 
        {              
            if('undefined' != typeof jQuery.datepicker){
				
				try {
					jreviews.datepickerClear();
				} catch (err) {}
				
                jQuery.datepicker.setDefaults({
                    showOn: 'both', 
                    buttonImage: datePickerImage, 
					showButtonPanel: true,
                    buttonImageOnly: true,
                    buttonText: 'Calendar',
                    dateFormat: 'yy-mm-dd',
                    changeMonth: true,
                    changeYear: true
                });    
                jQuery('.jrDate').each(function() {
                    jQuery(this).datepicker({
                        'yearRange':jQuery(this).data('yearrange')
                    });
                });
            }
        },
		
	datepickerClear: function() {
	   var old_fn = jQuery.datepicker._updateDatepicker;

	   jQuery.datepicker._updateDatepicker = function(inst) {
		  old_fn.call(this, inst);

		  var buttonPane = jQuery(this).datepicker("widget").find(".ui-datepicker-buttonpane");

          if(buttonPane.find('.clearDate').length == 0) {
			  jQuery("<button class='clearDate' type='button' class='ui-datepicker-clean ui-state-default ui-priority-primary ui-corner-all'>"+jrLanguage["clearDate"]+"</button>").appendTo(buttonPane).click(function(ev) {
				  jQuery.datepicker._clearDate(inst.input);
			  });
		  }
	   }
	},		
    discussion:
        {
            edit: function(element,options)
                {
                    jQuery(element).s2Dialog('jr_postEdit',{dialog:{width:'640px',height:'auto',title:options.title},dialogData:{url:'discussions/_edit/post_id:'+options.discussion_id}});                    
                },
            remove: function(element,options) 
                {
                    jQuery(element).s2Confirm(
                            {dialog:{title:options.title},
                             submitData:{'url':'discussions/_delete/post_id:'+options.discussion_id+'/token:'+options.token}
                            },options.text
                    );                    
                },    
            submit: function(element)
                {
                    jQuery(element).s2SubmitForm();
                },
            cancel: function(element,options)
                {
                    jQuery('#jr_postCommentFormOuter'+options.discussion_id).slideUp('slow',function(){
                        jQuery('#jr_postCommentAction'+options.discussion_id).slideDown('slow');
                    });                    
                },
            parentCommentPopOver: function()
                {				
					jQuery('.jrPopOver').each(function() {
						var post_id = jQuery(this).attr('name');
						var contentBox = jQuery(this).next();
						jQuery(this).tooltip({
							position: 'top left',
							tipClass: 'jr_tooltipBoxLight',
							delay: 500,
							opacity: 0.95,
							effect: 'slide',
							offset: [0, 10],
                            relative: true,
                            onBeforeShow: function(content){
                                if (contentBox.html() == "") {
                                    contentBox.html('<span class="jr_loadingMedium"></span>');
                                    jQuery.ajax({
                                        url: s2AjaxUri+'&url=discussions/getPost/'+post_id+jreviews.ajax_params(),
                                        type: 'GET',
                                        dataType: 'html',
                                        success: function(response){contentBox.html(response);}
                                    });
                                }
                            }							
						});
					});					
                },                
            showForm: function(element,options)
                {
                    jQuery(element).parents('div:eq(0)').slideUp('slow',function(){
                        jQuery('#jr_postCommentHeader'+options.discussion_id).css('display','block');
                        jQuery('#jr_postCommentFormOuter'+options.discussion_id).slideDown('slow');
                    });             
                }      
        },
    field: 
        {
            addOption: function(element)
            {
                var $button = jQuery(element);
                var $field = $button.parent().siblings('select');
                var $text = $button.prev(':input');
                var parent_fname, parent_value;
                var value = $text.val();
                var controlledBy = '';

                if($field.data('controlledBy')) {
                    jQuery.each($field.data('controlledBy'),function(field,value){
                        parent_fname = field;
                        parent_value = value;
                        controlledBy = '|'+field+'|'+value;
                    });                        
                }

                var optionValue =  encodeURIComponent(value).replace(/'/g, "&#039;")+"|click2add"+controlledBy;                     

                var $currOption = $field.children('option[value="' + optionValue+'"]');
                if(value != '' && $currOption.length == 0) {
                    $field
                        .append(jQuery("<option></option>")
                            .attr({
                                'value':optionValue,
                                'selected':'selected',
                                'data-ordering':99999, /* make sure it shows up last*/                            
                                'data-controlledBy':parent_fname,
                                'data-controlValue':parent_value
                            })
                            .text(value)
                        )
						.data('isActive',true)
                        .trigger('change');
                    $text.val('');
                } 
                else if ($currOption.length == 1) { 
                    $currOption.attr('selected','selected');
                }
                
                $button.siblings('.jr_validation').remove();                 
            }
        },
    inquiry:
        {
            submit: function(element,options)
                {
                    jQuery('#jr_inquiryForm .jr_validation').hide();
                    var $spinner = jQuery(element).siblings('.jr_loadingSmall');
                    $spinner.fadeIn();
                    jQuery(element).attr('disabled','disabled');
                    jQuery.ajax({
                        url: s2AjaxUri,
                        type: 'POST',
                        dataType: 'json',
                        data: jQuery('#jr_inquiryForm').serialize()
                        ,success: function(s2Out){
                            if(s2Out.error != undefined){ 
                                jQuery('#jr_inquiryResponse').html(s2Out.error);
                                jQuery('#jr_inquirySubmit').removeAttr('disabled');                          
                            }
                           if(s2Out.html != undefined){                                        
                                jQuery('#jr_inquiryForm').fadeOut('slow',function(){
                                   jQuery(this).html(options.submit_text).slideDown(); 
                                });
                            }                             
                            $spinner.hide();
                        }
                    });                    
                }
        },
    favorite:
        {
            add: function(element,options)
                {
                    jQuery(element).s2SubmitNoForm('listings','_favoritesAdd','data[listing_id]='+options.listing_id);                       
                },
            remove: function(element,options)
                {
                    jQuery(element).s2SubmitNoForm('listings','_favoritesDelete','data[listing_id]='+options.listing_id);                       
                } 
        },
    form: 
        {
            getValidationFields: function(form_id) {
                var validate = []; 
                jQuery(':input','#'+form_id).filter(function() {             
                   if(jQuery(this).parents('fieldset').data('isActive') != false && jQuery(this).data("isActive") == true) {         
                      validate.push(jQuery(this).data('fieldName'));    
                   }  
                });                
                return jQuery.unique(validate);
            }
        },
    module: 
        {
			pageNavInit: function(options)
                {
                    // options is object with module_id, page, page_count, columns, orientation, slideshow, slideshow_interval, arrows
					var o = options;
                    var $jr_modSlider = jQuery('div#jr_modSlider'+o.module_id),
					    $jr_modContainer = $jr_modSlider.find('.jr_modContainer'),
                        $jr_modPrev = $jr_modSlider.prev('a.jr_modPrev').addClass(o.orientation).bind('click',function(){return false;}),
                        $jr_modNext = $jr_modSlider.next('a.jr_modNext').addClass(o.orientation).bind('click',function(){return false;}),
                        outer_width = $jr_modSlider.parent().width(),
						hiddenTab = 0,
						jQueryUITab = 0,
                        page_count = $jr_modSlider.find('.jr_modContainer').length;
					
                    if(page_count < 2) {
                        $jr_modPrev.remove(); 
                        $jr_modNext.remove();
                    }
                    else if(o.nav_position == 'bottom') {
                        $jr_modNext.before($jr_modPrev.css('clear','left').detach()).css({'float': 'left', 'margin-left':'0px'});
                    }

					// fix related widget initialization when outputted inside jQuery UI tab.
                    if ($jr_modSlider.parents('.ui-tabs-panel').length) {
						var tabID = $jr_modSlider.parents('.ui-tabs-panel').attr('id');
						var tabIndex = jQuery('#jr_tabs a[href="#' + tabID + '"]').parent().index();
						jQuery(".jr_tabs").tabs( "select" , tabIndex );
						outer_width = $jr_modSlider.parent().width();
						jQueryUITab = 1;
                    }
                    
					// fix plugin initialization when outputted inside CB tab
					if ($jr_modSlider.parents('.tab-page').length) {
                        var firstCBTab = jQuery('#cb_tabmain').find('.tab-page').first();
                        outer_width = firstCBTab.width()-20;	
                    }

					// Get available width from hidden parent elements
					if(outer_width == 0) {
						hiddenParent = $jr_modSlider.parents().filter(':hidden').last();
						outer_width = hiddenParent.parent().width();
						hiddenTab = 1;
					}

                    // Auto width
                    if(page_count > 1 && o.nav_position === 'side'){   
                        $jr_modSlider.width(outer_width - $jr_modPrev.outerWidth(true) - $jr_modNext.outerWidth(true) - 1);
                    }
                    else {
                        $jr_modSlider.width(outer_width);
                    }
                    
                    var page_width = $jr_modSlider.width();
					$jr_modContainer.width(page_width);

					$jr_modContainer.each(function(){
						var listItems = jQuery(this).children('.listItem');
						if(o.columns > listItems.length) o.columns = listItems.length;
	                    listItems.each(function(index){
							var container_width = page_width - ((o.columns-1)*10);
	                        jQuery(this).width(container_width/o.columns);
							for (i=1; i<=(listItems.length/o.columns); i++) {
								if (index+1 == o.columns*i) {
									jQuery(this).addClass('lastItem');
								}
							}			
	                    });
					});
					
                    // Auto height
                    var maxHeight = jreviews.module.maxHeight($jr_modContainer, hiddenTab);                    
                    $jr_modContainer.height(maxHeight);
                    $jr_modSlider.height(maxHeight);
                    
					if (page_count > 1 && o.nav_position === 'side') {
						var arrows_margin =(($jr_modSlider.height()/2)-11);
						$jr_modPrev.css('marginTop',arrows_margin);
						$jr_modNext.css('marginTop',arrows_margin);
					}

                    if(page_count > 1) 
                    {
                        var modSlider = $jr_modSlider.scrollable({
                                clickable: false,
                                circular: (o.orientation === 'horizontal') || (o.orientation === 'vertical' && o.slideshow === "1"),
                                interval: 0,
                                easing: 'swing',
                                speed: 1000,
                                items: '.jr_modItems',
                                prev: '.jr_modPrev',
                                next: '.jr_modNext',                
                                disabledClass: 'jr_modDisabled',    
                                keyboard: false,
                                vertical: o.orientation === 'vertical'
                            });
                        if(o.slideshow === "1") {
                            modSlider.autoscroll({autoplay: true, interval: o.slideshow_interval*1000});							
                        }

                        if (o.orientation === 'horizontal') {
                            $jr_modSlider.css('paddingBottom','11px');
                            $jr_modSlider.append('<div class="navi" style="width:'+ 14 * page_count +'px;"></div>');
							var $nav = $jr_modSlider.find('div.navi');
							var navPosition = ($jr_modSlider.outerWidth()/2)-($nav.outerWidth()/2); 
							$jr_modSlider.find('div.navi').css('left',navPosition+'px');
                            modSlider.navigator();
                        }                            
                    }
					
                    if ($jr_modSlider.find('div.listItem').first().hasClass('slideshowLayout')) {
						$thumbs = $jr_modSlider.find('.contentThumbnail');
						$thumbs.each(function(){
							jQuery(this).hover(
								function(){
									jQuery(this).find('div.contentInfo').hide().slideDown(500);
								},
								function(){
									jQuery(this).find('div.contentInfo').slideUp(500);
                        }
							);
						});
                    }

					if (jQueryUITab) {
						jQuery("#jr_tabs").tabs( "select" , 0 );
					}
                },
			maxHeight: function(group, hiddenTab)
				{				
                    var tallest = 0;
					if (hiddenTab) {
						group.each(function() {
							var clonedGroup = jQuery(this).clone().appendTo('body');
	                        var thisHeight = clonedGroup.outerHeight();
	                        if(thisHeight > tallest) {
	                            tallest = thisHeight;
	                        }
							clonedGroup.remove();
	                    });
					} else {
						group.each(function() {
	                        var thisHeight = jQuery(this).outerHeight();
	                        if(thisHeight > tallest) {
	                            tallest = thisHeight;
	                        }
	                    });
					}                   
                    return tallest;
                }
        },
    lightbox: function()
        {
            if(jQuery('a.fancybox').size()) 
                {
                    jQuery("a.fancybox").fancybox({
                        'speedIn': 1000, 
                        'speedOut': 1000,
						'easingIn' : 'easeOutBack',
						'easingOut' : 'easeInBack',
						'transitionIn' : 'elastic',
						'transitionOut' : 'elastic',				
                        'overlayShow': true,
                        'opacity': true,
                        'padding': 4
                    }); 
                }                
        },        
    listing: 
        {
            claim: function (element,options)
                {
                    jQuery(element).s2Dialog('jr_claimListing',{
                            dialog:{width:'640px',height:'auto',title:options.title},
                            dialogData:{url:'claims/create/listing_id:'+options.listing_id}
                    });
                },
            remove: function (element,options)
                {
                    var data = {'url':'listings/_delete/id:'+options.listing_id}
                    data[options.token] = 1;
                    jQuery(element).s2Confirm({'dialog':{'title':options.title},'submitData':data},options.text);   
                },  
            manageAction: function(element,options,action)
                {
                    var data = {'data[Listing][id]':options.listing_id};
                    data[options.token] = 1;
                    jreviews.dispatch({'method':'get','type':'json','controller':'listings','action':action,'data':data,'onComplete':function(res){
                        if(!res.error)
                        {
                            var $element = jQuery(element);
                            var state = $element.is('.jr_published') ? 'unpublished' : 'published';
                            $element.removeClass().addClass('jr_'+state).html(options[state]);
                        } else 
                            s2Alert(res.msg);
                    }});
                },
            feature: function (element,options)
                {
                    jreviews.listing.manageAction(element,options,'_feature');
                },   
            frontpage: function (element,options)
                {
                    jreviews.listing.manageAction(element,options,'_frontpage');
                },                                                  
            publish: function (element,options)
                {
                    jreviews.listing.manageAction(element,options,'_publish');
                },              
            submit: function (element)
                {
                    jQuery('#controller, #action').remove();
                    var form = jQuery('#jr_listingForm');
                    
                    /* copy text of selected section/cat to hidden fields for use in Geomaps */
                    var count;
                    var selected = [];
                    var category;
                    var parent_category;
                    
                    jQuery("select[id^=cat_id]").each(function()
                    {  
                        var value = jQuery(this).val();
                        if(value > 0) selected.push(jQuery(this));
                    });
                    count = selected.length;
                    if(count == 1) 
                    {
                        form.find('#category').val(selected[0].find('option:selected').text().replace(/(- )+/,''));
                    }
                    else if(count > 1)
                    {
                        form.find('#category').val(selected[count-1].find('option:selected').text().replace(/(- )+/,''));
                        form.find('#parent_category').val(selected[count-2].find('option:selected').text().replace(/(- )+/,''));
                    }                    
                    form.find('#section').val(form.find('#section_id option:selected').text());
                    /* end copy text of selected section/cat to hidden fields for use in Geomaps */
                    
                    try {
                        jQuery('.wysiwyg_editor').RemoveTinyMCE();
                       
                    } 
                    catch(err) {
                        // console.log('editor could not be removed');
                    }
                    
                    jQuery(element).siblings('.jr_loadingSmall').fadeIn();
                    jQuery('#jr_listingForm .button').attr('disabled','disabled');
                    
                    /* Add form validation fields */
                    var valid_fields = jreviews.form.getValidationFields(form.attr('id')).join(',');
                    form.append('<input type="hidden" id="valid_fields" name="data[valid_fields]" value="'+valid_fields+'" />');
                    form.submit();
                    jQuery('#valid_fields','#jr_listingForm').remove();
                },
            submitSection: function (element)
                {
                    var $controller, $action;
                    var $parentForm = jQuery('#jr_listingForm');
                    $parentForm.find('#cat_id1').val(0);
                    $controller = jQuery('#controller').detach();
                    $action = jQuery('#action').detach();
                    $parentForm.append('<input type="hidden" id="controller" name="data[controller]" value="listings" />');
                    $parentForm.append('<input type="hidden" id="action" name="data[action]" value="_loadCategories" />');
                    jQuery(element).s2SubmitForm();
                    jQuery('#controller, #action','#jr_listingForm').remove();
                    $parentForm.append($controller, $action);
                },
            submitCategory: function (element)
                {              
                    
                    try {
                        jQuery('.wysiwyg_editor').RemoveTinyMCE(); /* required so the editor can be added again on new section/category changes*/
                    }
                    catch(err) {
//                        console.log('editor could not be removed');
                    }
                    
                    var $parentForm = jQuery('#jr_listingForm');                   
                    $parentForm.append('<input type="hidden" id="action" name="data[action]" value="_loadForm" />');
                    $parentForm.append('<input type="hidden" id="controller" name="data[controller]" value="listings" />');
                    $parentForm.append('<input type="hidden" id="hidden_cat_id" name="data[catid]" value="'+element.value+'" />'); /*J16*/
                    $parentForm.append('<input type="hidden" id="cat_level" name="data[level]" value="'+element.id+'" />');  /*J16*/
                    var callbacks = {
                        onAfterResponse: function(){
                            try {
                                jQuery('.wysiwyg_editor').tinyMCE();
                            }
                            catch(err) {
//                                console.log('editor could not be added');
                            }
                            // Facebook integration           
                            if(jreviews.facebook.enable == true) {    
                                jreviews.facebook.checkPermissions({
                                    'onPermission':function(){jreviews.facebook.setCheckbox('jr_submitListing',true);},
                                    'onNoSession':function(){jreviews.facebook.setCheckbox('jr_submitListing',false);}
                                });
                            };   
                            // Load custom field data
							jreviews.controlFieldListing = new jreviewsControlField('jr_listingForm','hidden_cat_id');
							jreviews.controlFieldListing.loadData({'entry_id':jQuery('#listing_id','#jr_listingForm').val(),'value':false,'page_setup':true,'referrer':'listing'});
							if(jQuery('#reviewForm').length) {
								var $controlFieldReview = new jreviewsControlField('reviewForm','hidden_cat_id');
								$controlFieldReview.loadData({'fieldLocation':'Review','entry_id':0,'value':false,'page_setup':true,'referrer':'review'});
							}  
                        }
                    };
                    jQuery(element).s2SubmitForm(callbacks);
                    jQuery('#controller, #action, #hidden_cat_id, #cat_level').remove();
                },
            setMainImage: function(element,options)
                {
                    jQuery(element).s2SubmitNoForm('listings','_imageSetMain','data[listing_id]='+options.listing_id+'&data[image_path]='+options.image_path+'&'+options.token+'=1');                    
                },
            deleteImage: function(element,options)
                {
                    var data =  {
                        'url':'listings/_imageDelete/',
                        'data[listing_id]':options.listing_id,
                        'data[delete_key]':options.delete_key,
                        'data[image_path]':options.image_path
                    };
                    data[options.token] = 1;
                    jQuery(element).s2Confirm({'dialog':{'title':options.title},'submitData': data},options.text);}        
        },
     review:
        {             
            starRating: function(suffix,inc)
                {
                    jQuery("div[id^='jr_stars"+suffix+"']").each(function(i) {
                        if( this.id != '' ) {
                            jQuery(this).parent().next().append('<span id="jr-rating-wrapper-' + this.id + '"></span>');
                            var splitStars = 1/inc; // 2 for half star ratings
                            jQuery("#"+this.id).stars({
                                split: splitStars,
                                captionEl: jQuery("#jr-rating-wrapper-" + this.id )
                            });
                        }
                    });                        
                },
            showForm: function(element)
                {
                    jQuery(element).hide('slow',function(){
                        jQuery('#jr_review0Form').slideDown(1000,function(){
                            jQuery('#jr_review0Form').scrollTo({duration:1000,offset:-50});
                        });
                    });                
                },
            hideForm: function()
                {
                    jQuery('#review_button').show();
                    jQuery('#review_button').scrollTo({duration:500,offset:-50}, function(){jQuery('#jr_review0Form').fadeOut('slow');});                
                },
            edit: function(element,options)
                {
					// Detach new review form and reattach on save
					var reviewForm = jQuery('#jr_review0Form');
					var newReviewForm = jQuery('<div id="newReviewForm" class="jr_hidden"></div>');
					reviewForm.before(newReviewForm);
					var detachedForm = reviewForm.detach();
                    
					jQuery(element).s2Dialog('jr_review'+options.review_id,
                        {
                            beforeSubmit: function(review_id) { 
								var $form = jQuery('#'+review_id+'Form');
								var valid_fields = jreviews.form.getValidationFields(review_id+'Form').join(',');
								$form.append('<input type="hidden" id="valid_fields" name="data[valid_fields]" value="'+valid_fields+'" />');
							},
							dialog:{width:800,height:600,title:options.title},
                            dialogData:{url:'reviews/_edit/review_id:'+ options.review_id},
							afterSubmit: function() { newReviewForm.after(detachedForm); newReviewForm.remove(); },
							onCancel: function () { newReviewForm.after(detachedForm); newReviewForm.remove();}
                        });
                },
            submit: function(element)
                {
                    var $button = jQuery(element);
                    var $form = $button.parents('form');
                    var form_id = $form.attr('id');
                    var valid_fields = jreviews.form.getValidationFields(form_id).join(',');
					$form.append('<input type="hidden" id="valid_fields" name="data[valid_fields]" value="'+valid_fields+'" />');
                    $button.s2SubmitForm();                    
                    jQuery('#valid_fields','#'+form_id).remove();
                },
            reply: function(element,options)
                {
                    jQuery(element).s2Dialog('jr_ownerReply',
                        {
                            dialog:{width:'640px',height:'auto',title:options.title},
                            dialogData:{url:'owner_replies/create/review_id:'+options.review_id}
                        });                    
                },        
            voteNo: function(element,options)
                {
                    jQuery(element).s2SubmitNoForm('votes','_save','data[Vote][review_id]='+options.review_id+'&data[Vote][vote_no]=1');                    
                }, 
            voteYes: function(element,options)
                {
                    jQuery(element).s2SubmitNoForm('votes','_save','data[Vote][review_id]='+options.review_id+'&data[Vote][vote_yes]=1');                    
                },
            rebuildRanksTable: function() 
                {
                    jreviews.dispatch({'controller':'reviews','action':'_rebuildRanksTable'});
                }
        },
     report:
        {
            showForm: function(element,options)
                {
                    jQuery(element).s2Dialog('jr_report',
                        {
                            dialog:{width:'640px',height:'auto',title:options.title},
                            dialogData:{url:'reports/create/listing_id:'+options.listing_id+'/review_id:'+options.review_id+'/post_id:'+options.post_id+'/extension:'+options.extension}
                        });                    
                }  
        },           
     search:
         {
            showRange: function(element,field) 
            {
                if(jQuery(element).val()=='between'){
                    jQuery('#'+field+'Div').fadeIn();
                } else {
                    jQuery('#'+field+'Div').fadeOut().find(':input').val('');                
                }    
            }
         },
     tooltip: function() 
         {
            if('undefined' != typeof jQuery.tools && 'undefined' != typeof jQuery.tools.tooltip)
            {     
                jQuery('.jr_infoTip').not('.jrTipInit').tooltip({
                    position: 'center right',
                    tipClass: 'jr_tooltipBox',
                    delay: 0,
                    opacity: 0.95,
                    effect: 'slide',
                    offset: [0, 10],
                    relative: true
                }).addClass('jrTipInit');
            }
         },    
     user:
        {
            autocomplete: function(element,options)
                {
                    var defaults = {
                        'target_user_id' : 'jr_reviewUserid',
                        'target_name'   : 'jr_reviewName',
                        'target_username' : 'jr_reviewUsername',
                        'target_email' : 'jr_reviewEmail'
                    };

                    var settings = jQuery.extend(defaults, options);

                    element.autocomplete({
                            source: function( request, response ) {
                                var cache = element.data('cache') || {};
                                var term = request.term;
                                if ( term in cache ) {
                                    response( cache[ term ] );
                                    return;
                                }
                                jreviews.dispatch({'type':'json','controller':'users','action':'_getList','data': {"data[value]": term},'onComplete':function( data ) { 
                                    cache[ term ] = data;
                                    element.data('cache',cache)
                                    response(data);
                                }});
                            },
                            select: function( event, ui) {  
                                jQuery('#'+settings.target_user_id).val(ui.item.id); 
                                jQuery('#'+settings.target_email).val(ui.item.email); 
                                jQuery('#'+settings.target_name).val(ui.item.name); 
                                jQuery('#'+settings.target_username).val(ui.item.username); 
                            },
                            minLength: 2
                    });
                    jQuery('.ui-autocomplete').css('white-space','nowrap');   
                }
        },
    facebook:
    {
        enable: false,
        permissions: false, 
        uid: null,
        init: function(options) {
            if(undefined!=options) jreviews.facebook.options = options;    
            if('undefined'==typeof(FB)) {  // Load facebook js only if not already loaded
                jQuery.ajax({
                    type: "GET",
                    url: "http://connect.facebook.net/"+jrVars["locale"]+"/all.js",
                    success: function(){    
                        FB.init({appId: options.appid, status: false, cookie: true, xfbml: true, oauth : true});  
                        if(undefined!=options.success) options.success();
                    },
                    dataType: "script",
                    cache: true
                });            
            } else if(undefined!=options.success && FB.init != undefined) {
                FB.init({appId: options.appid, status: false, cookie: true, xfbml: true, oauth : true});  
                options.success();                
            }
        },
        login: function()
        {     
            if(null == jreviews.facebook.uid) {
                FB.login(function(response) {
                    if (response.authResponse) {
                          // user is logged in and granted some permissions.
                          jreviews.facebook.uid = response.authResponse.userID;
                    } else {
                        jQuery('#fb_publish').attr('checked',false);
                    } 
                }, {scope:'publish_stream'});              
            }
        },
        checkPermissions: function(options) {
            if(undefined==options) options = {};
            jQuery("body").data('fb.options',options);   
            
            if(typeof(FB) == 'undefined') {
                jreviews.facebook.permissions = false;
                return;
            }
            
            FB.getLoginStatus(function(response) 
            {                   
                if(response.status === 'connected') 
                {         
                      // logged in and connected user
	                  jreviews.facebook.uid = response.authResponse.userID;
                      FB.api({
                                method: 'fql.query',
                                query: 'SELECT publish_stream FROM permissions WHERE uid= ' + response.authResponse.userID
                            },
                            function(response) { 
                                if(!response[0].publish_stream)
                                {
                                    // re-request publish_stream permission
                                    FB.login(function(response) {
                                        if (response.authResponse) 
                                        {                 
                                            // user is logged in and granted some permissions.
                                            var options = jQuery("body").data('fb.options');
                                            if(undefined!=options.onPermission) options.onPermission();  
                                            jreviews.facebook.permissions = true;
                                        }
                                    },{scope:'publish_stream'});                
                                } else {                
                                    var options = jQuery("body").data('fb.options');
                                    if(undefined!=options.onPermission) options.onPermission();                                            
                                    jreviews.facebook.permissions = true;
                                }
                          }
                    );  
                } 
                else   // User not logged in or has not granted publish_stream permission
                {   
                    jreviews.facebook.permissions = false;
                    if(undefined!=options.onNoSession) options.onNoSession();  
                }
            });    
        },
        setCheckbox: function(id,hidden) {
            if(hidden == true && !jreviews.facebook.options.optout) {                                                                                       
                jQuery('#'+id).before('<input id="fb_publish" name="data[fb_publish]" value="1" type="hidden"/>');                                                                                      
            }
            else
            {
                var fbcheckbox = '<input id="fb_publish" name="data[fb_publish]" type="checkbox" onclick="if(this.checked) jreviews.facebook.login();" />'
                    +'&nbsp;<div class="fb_button fb_button_medium"><span class="fb_button_text"><label for="fb_publish">'
                    +jreviews.facebook.options.publish_text
                    +'</label></span></div><br /><br />';  
                jQuery('#'+id).before(fbcheckbox);            
                if(hidden && jreviews.facebook.options.optout) jQuery('#fb_publish').attr("checked","checked");
            }    
        }
    },
    common:
    {
        initForm: function(form_id)
        {
            var parentForm = jQuery('#'+form_id);
            var captchaDiv = parentForm.find('div.jr_captcha');
            parentForm.one('mouseover',function() {
                jQuery(parentForm).s2SubmitNoForm('common','_initForm','data[form_id]='+form_id+'&data[captcha]='+captchaDiv.length);
                parentForm.find('button').removeAttr('disabled');
            });
        },
        inArray: function (needle, haystack) {
            var length = haystack.length;
            for(var i = 0; i < length; i++) {
                if(haystack[i] == needle) return true;
            }
            return false;
        }        
    }                     
}