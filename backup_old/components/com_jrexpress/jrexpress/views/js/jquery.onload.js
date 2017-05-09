jQuery(document).ready(function() 
{	
	/* initializes tooltip for jtooltip class */
	if(jQuery.tooltip) {
		jQuery('#form_container img').tooltip({
			track: true,
			delay: 0,
			showURL: false,
			opacity: 0.95,
			fixPNG: true
		});
		jQuery('#jr_reviewform img').tooltip({
			track: true,
			delay: 0,
			showURL: false,
			opacity: 0.95,
			fixPNG: true
		});		
	}

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
});