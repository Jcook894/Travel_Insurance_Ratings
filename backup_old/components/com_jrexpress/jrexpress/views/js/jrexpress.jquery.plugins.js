/* scrollTo plugin */
jQuery.fn.extend({
  scrollTo : function(speed, top_offset, easing) {
    return this.each(function() {
      var targetOffset = jQuery(this).offset().top - top_offset;
      jQuery('html,body').animate({scrollTop: targetOffset}, speed, easing);
    });
  }
});

/* tinyMCE plugin */
jQuery.fn.extend({
	tinyMCE : function(options) 
	{
	    return this.each(function()
	    {
            tinyMCE.execCommand('mceAddControl', false, this.id);
		});
	}
});

jQuery.fn.extend({
	RemoveTinyMCE : function(options) 
	{
	    return this.each(function()
	    {
            tinyMCE.execCommand('mceRemoveControl', true, this.id);
		});
	}
});  

jQuery.fn.extend({
    RemoveJCE : function(options) 
    {
        return this.each(function()
        {
            tinyMCE.execCommand('mceRemoveControl', true, this.id);
        });
    }
});  

/**
 * jQuery Plugin Toggle Fade v1.0
 * Requires jQuery 1.2.3 (Not tested with earlier versions).
 * Copyright (c) 2008 Gregorio Magini [gmagini at gmail dot com] 
 * 
 *	@param: Object Array. Arguments need to be in object notation.
 *	Returns: jQuery.
 *	Options:	
 *		speedIn: Sets the speed of the fadeIn effect. Default: "normal".
 *    speedOut: Sets the speed of the fadeOut effect. Default: same as speedIn.
 *
 *	Examples: 
 *    
 *    speedIn and speedOut both "normal":
 *		$("#toggle-link").toggleFade();
 *
 *    speedIn and speedOut both "fast":
 *		$("#toggle-link").toggleFade({ speedIn : "fast");
 *
 *    different settings for speedIn and speedOut:
 *		$("#toggle-link").toggleFade({ speedIn : 800, speedOut : 150 });
 *
 */
(function($) {
  $.fn.toggleFade = function(settings)
  {
  	settings = jQuery.extend(
  		{
        speedIn: "normal",
        speedOut: settings.speedIn
  		}, settings
  	);
  	return this.each(function()
  	{
  	  var isHidden = jQuery(this).is(":hidden");
      jQuery(this)[ isHidden ? "fadeIn" : "fadeOut" ]( isHidden ? settings.speedIn : settings.speedOut);
    });
  };
})(jQuery);