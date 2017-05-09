if(typeof xajax != "undefined") {
	fadeInModule = xajax.callback.create();
	fadeInModule.onComplete = function(oRequest) {
		jQuery('#jr_modContainer'+oRequest.module_id).fadeTo(100,1,function() {
				// Fix IE Cleartype bug
				if (jQuery.browser.msie)this.style.removeAttribute('filter');
			}	
		);
	}
}