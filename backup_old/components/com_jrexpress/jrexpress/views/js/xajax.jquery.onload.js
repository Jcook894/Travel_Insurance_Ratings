/* Attach tooltip to jtooltip class */
if(jQuery.tooltip) {
	jQuery('#form_container img').tooltip({
		track: true,
		delay: 0,
		showURL: false,
		opacity: 0.95,
		fixPNG: true,
		top: -15,
		left: 5		
	});		
}

/* Attach datepicker to date fields */
if (jQuery.datepicker) {
	jQuery('.datepicker').datepicker();
}

/* initialize rating stars */
jQuery("div[id^='jr_stars-new']").each(function(i) {
	if( this.id != '' ) {
		jQuery("#"+this.id).stars({
			inputType: "select",
			cancelShow: false 
		});
	}
});	
