/**
 * JReviews Express - user reviews for Joomla
 * Copyright (C) 2009 Alejandro Schmeichler
 * This javascript file is proprietary. Do not distribute it.
 * For licencing information visit http://www.reviewsforjoomla.com
 * or contact sales@reviewsforjoomla.com
**/

/* Review scripts */
function submitReview() {
	jReviewsSpinner();
	xajax.$('submitButton').disabled=true;
	xajax.$('cancel').disabled=true;
	xajax.request({xjxfun:'xajaxDispatch'},{URI:xajaxUri,parameters:['reviews','_save','jrexpress',xajax.getFormValues('reviewForm')]});	
}
function submitReviewEdit() {
	jReviewsSpinner();
	xajax.$('submitButtonEdit').disabled=true;
	xajax.$('cancelEdit').disabled=true;
	parent.xajax.request({xjxfun:'xajaxDispatch'},{URI:xajaxUri,parameters:['reviews','_save','jrexpress',xajax.getFormValues('reviewFormEdit')]});
}

function cancelReviewEdit() {
	tb_remove();
	return false;
}

function jReviewsSpinner(element) {
	xajax.callback.global.onRequest = function() {
		if(xajax.$('spinner')) {
			xajax.$('spinner').style.display = 'inline';
		}
	};
	xajax.callback.global.onComplete = function() {
		if(xajax.$('spinner')) {
			xajax.$('spinner').style.display = 'none';
		}
	}
}

function clearSelect(name) {
	var element = xajax.$(name);
	count = element.length;
	for (i=0; i < count; i++) {
		element.options[i].selected = '';
	}
}