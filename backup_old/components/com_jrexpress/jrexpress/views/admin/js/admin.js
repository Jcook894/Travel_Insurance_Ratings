(function($) {
        $.dequeue = function( a , b ){
                return $(a).dequeue(b);
        };

 })( jQuery ); 

jQuery(document).ready(function() {
		
    jQuery('#toolbar-box').remove();
    jQuery('#submenu-box').remove();
        
	/* initializes tabs */
	jQuery("#jtabs > ul").tabs();	
	
	/* initializes tooltip for jtooltip class */
	jQuery(".jtooltip").cluetip({
		cluetipClass: 'jtip',
		splitTitle: '|',
		arrows: true
	});
	
	/* initialize datepicker global defaults */
	if(jQuery.datepicker)
	{
		jQuery.datepicker.setDefaults({
			showOn: 'both', 
			buttonImageOnly: true, 
		    buttonImage: datePickerImage, 
		    buttonText: 'Calendar',
		    dateFormat: 'yy-mm-dd'
			});	
	
		/* attach datepicker to all date input fields */
		jQuery('.datepicker').datepicker();	
	}	
	
	jQuery('#groups').click(function() {
		jQuery('#page').fadeOut('fast',function(){xajax.request({xjxfun:'xajaxDispatch'},{URI:'index2.php?option=com_jrexpress&no_html=1',parameters:['admin/groups','index','jrexpress']});});
	});
	
	jQuery('#fields').click(function() {
		jQuery('#page').fadeOut('fast',function(){xajax.request({xjxfun:'xajaxDispatch'},{URI:'index2.php?option=com_jrexpress&no_html=1',parameters:['admin/fields','index','jrexpress']});});
	});
	
	jQuery('#criterias').click(function() {
		jQuery('#page').fadeOut('fast',function(){xajax.request({xjxfun:'xajaxDispatch'},{URI:'index2.php?option=com_jrexpress&no_html=1',parameters:['admin/criterias','index','jrexpress']});});
	});
	
	jQuery('#directories').click(function() {
		jQuery('#page').fadeOut('fast',function(){xajax.request({xjxfun:'xajaxDispatch'},{URI:'index2.php?option=com_jrexpress&no_html=1',parameters:['admin/directories','index','jrexpress']});});
	});
	
	jQuery('#categories').click(function() {
		jQuery('#page').fadeOut('fast',function(){xajax.request({xjxfun:'xajaxDispatch'},{URI:'index2.php?option=com_jrexpress&no_html=1',parameters:['admin/categories','index','jrexpress']});});		
	});
	
	jQuery('#themes').click(function() {
		jQuery('#page').fadeOut('fast',function(){xajax.request({xjxfun:'xajaxDispatch'},{URI:'index2.php?option=com_jrexpress&no_html=1',parameters:['admin/themes','index','jrexpress']});});		
	});
	
	jQuery('#seo').click(function() {
		jQuery('#page').fadeOut('fast',function(){xajax.request({xjxfun:'xajaxDispatch'},{URI:'index2.php?option=com_jrexpress&no_html=1',parameters:['admin/seo','index','jrexpress']});});			
	});
	
	jQuery('#clear_cache').click(function() {
		xajax.request({xjxfun:'xajaxDispatch'},{URI:'index2.php?option=com_jrexpress&no_html=1',parameters:['admin/common','_clearCache','jrexpress']});
	});	
    
    jQuery('#clear_registry').click(function() {
        xajax.request({xjxfun:'xajaxDispatch'},{URI:'index2.php?option=com_jrexpress&no_html=1',parameters:['admin/common','_clearFileRegistry','jrexpress']});
    });        
});

/* jQuery table effects */
function flashRow(row_id) {
	    jQuery('#'+row_id).css({backgroundColor: '#ff0'});
	    setTimeout(function() {jQuery('#'+row_id).animate({backgroundColor: '#fff'}, 500);});
}
function removeRow(row_id) {
	jQuery('#'+row_id).css({backgroundColor: '#ff0'}).fadeOut('slow');
}

/* xajax loading effects */
if(typeof xajax != "undefined") 
{
	xajax.callback.global.onRequest = function() {
		if (xajax.$('spinner') != null) {
			xajax.$('spinner').style.display = 'inline';
		}
	};
	xajax.callback.global.onComplete = function() {
		if (xajax.$('spinner') != null) {
			xajax.$('spinner').style.display = 'none';
		}
	}
}

/* Configuration functions */
function clearSelect(name) {
	var element = document.getElementById(name);
	count = element.length;
	for (i=0; i < count; i++) {
		element.options[i].selected = '';
	}
}

/* Paginate functions */
function setPage(page) {
	document.getElementById('page_number').value = page;
}

function setLimit(limit) {
	document.getElementById('limit').value = limit;
}

/* Listings functions */
function submitListingAdmin() {
	if(window.tinyMCE !== undefined){
		jQuery('.wysiwyg_editor').RemoveTinyMCE();
	}
	document.newItemForm.submit();
}

function deleteListing(listing_id) {
	if(confirm('This action will delete the content along with its custom fields and reviews. Are you sure you want to continue?'))
		xajax.request({xjxfun:'xajaxDispatch'},{URI:'index2.php?option=com_jrexpress&no_html=1',parameters:['admin/admin_listings','_delete','jrexpress',{data:{Listing:{id:listing_id}}}]});
}

/* Review functions */
function deleteReview(reviewid,uri) {
	if(confirm('Are you sure you want to delete this review?'))
		xajax.request({xjxfun:'xajaxDispatch'},{URI:uri,parameters:['admin/reviews','_delete','jrexpress',{row_id:reviewid}]});
}

/* Review Reports functions */
function deleteReport(reportid) {
	if(confirm('Are you sure you want to delete this report?'))
		xajax.request({xjxfun:'xajaxDispatch'},{URI:'index2.php?option=com_jrexpress&no_html=1',parameters:['admin/admin_review_reports','_delete','jrexpress',{data:{ReviewReport:{id:reportid}}}]});
}

/* Groups functions */
function deleteFieldGroup(groupid,field_count) {
	if(field_count>0){
		alert("To delete this group you first need to delete all the fields associated with it in the Fields Manager.");
	} else {
		if(confirm('Are you sure you want to delete this group?'))
		xajax.request({xjxfun:'xajaxDispatch'},{URI:'index2.php?option=com_jrexpress&no_html=1',parameters:['admin/groups','_delete','jrexpress',{data:{group_id:groupid}}]});
	}
}

/* Field functions */
function deleteField(fieldid) {
	xajax.$('fieldid').value = fieldid;
	if(confirm('This action will also delete all the information already stored for this field. Do you want to continue?.'))
		xajax.request({xjxfun:'xajaxDispatch'},{URI:'index2.php?option=com_jrexpress&no_html=1',parameters:['admin/fields','_delete','jrexpress',xajax.getFormValues('adminForm')]});
}

/* Criteria functions */
function deleteCriteria(criteriaid,uri) {
	if(confirm('If you delete this criteria set all reviews for items that have this criteria assigned will also be deleted. Do you want to continue?'))
		xajax.request({xjxfun:'xajaxDispatch'},{URI:uri,parameters:['admin/criterias','delete','jrexpress',{row_id:criteriaid}]});
}

/* Directory functions */
function deleteDirectory(dirid) {
	if(confirm('Are you sure you want to delete this directory?'))
		xajax.request({xjxfun:'xajaxDispatch'},{URI:'index2.php?option=com_jrexpress&no_html=1',parameters:['admin/directories','delete','jrexpress',{row_id:dirid}]});
}

/* Category functions */
function removeCategories() {
	if(xajax.$('cat_id').value > 0) {
		xajax.$('boxchecked').value = 0;
	}
	if(confirm('Are you sure you want to remove the selected categories from working with JReviews Express. The categories will NOT be deleted, but the review system will no longer work for content in the selected categories.'))
		xajax.request({xjxfun:'xajaxDispatch'},{URI:'index2.php?option=com_jrexpress&no_html=1',parameters:['admin/categories','delete','jrexpress',xajax.getFormValues('adminForm')]});
}

function deleteFieldOption(optionid) {
	if(confirm('Are you sure you want to delete this option?'))
		xajax.request({xjxfun:'xajaxDispatch'},{URI:'index2.php?option=com_jrexpress&no_html=1',parameters:['admin/fieldoptions','delete','jrexpress',{data:{FieldOption:{optionid:optionid}}}]});
}

function toggleImage(element,img1,img2) {
	element.src = element.src.search(img1) > 0 ? img2 : img1;
}


function fieldValidate(str) {
  if (str.value != '')
	str.value= str.value.replace(/[^a-zA-Z]+/g,'');
	str.value = str.value.toLowerCase();
}
function createOptionsInput(type,location,demo) {
	var multipleOption1 = 'multiple';
	var multipleOption2 = 'checkboxes';
	var multipleOption3 = 'website';
	var multipleOption4 = 'email';

	if (type == 'code') {

		document.getElementById('type_desc').innerHTML = 'Add code for paypal, amazon, ect. <span style="color: red;font-weight:bold;">Careful with the access for this field.</span>';
		if (demo) {
			document.getElementById('type_desc').innerHTML = 'This field type is disabled in the demo';
			document.getElementById('type').value = '';
		}
	} else if ( type.search(multipleOption3)>=0 || type.search(multipleOption4)>=0) {

		document.getElementById('jr_click2search').innerHTML = ('<b>This field type cannot be enabled for click2search.</b>').fontcolor("Red");
		document.getElementById('jr_click2search_tr').style.backgroundColor = "#FFF82A";

	} else if ( location=='content' && (type.search(multipleOption1)>=0 || type.search(multipleOption2)>=0) ) {

		document.getElementById('jr_sortlist').innerHTML = ('<b>This field type is not sortable.</b>').fontcolor("Red");
		document.getElementById('jr_sortlist_tr').style.backgroundColor = "#FFF82A";

	} else if ( location=='content') {
		document.getElementById('type_desc').innerHTML = '';

		// Return sort feature to default status
		document.getElementById('jr_sortlist').innerHTML = 'Shows the field in the dropdown list';
		document.getElementById('jr_sortlist_tr').style.backgroundColor = "#FFFFFF";

		// Return click2search feature to default status
		document.getElementById('jr_click2search').innerHTML = 'Makes field text clickable to find other items with the same value, except website field.';
		document.getElementById('jr_click2search_tr').style.backgroundColor = "#FFFFFF";

	} else {
		// Return website feature to default status
		document.getElementById('website_title').style.display = "none";
	}
}