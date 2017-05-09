/**
 * JReviews - Reviews Extension
 * Copyright (C) 2010-2017 ClickFWD LLC
 * This is not free software, do not distribute it.
 * For licencing information visit https://www.jreviews.com
 * or contact sales@jreviews.com
**/

jreviews = jreviews || {};

(function($, jreviews, undefined) {

	var jrPage = $('div.jr-page');

	var jrMain = $('div.jr-main');

	$(document).ajaxStop(function() {

		setTimeout(
			function()
			{
				jreviews.addOnloadAjax('datepicker',        jreviews.datepicker);
				jreviews.addOnloadAjax('popup',             jreviews.popup);
				jreviews.addOnloadAjax('tabs',              jreviews.tabs);
				jreviews.addOnloadAjax('listing-manager',   jreviews.listing.manager);
				jreviews.addOnloadAjax('pagenav-ajax', 		jreviews.pageNav.disableSelects);

				$.each(jreviews._onloadFnAjax, function(i,fn) {
					if(fn !== undefined) fn();
				});
			},
		10);
	});

	jreviews = $.extend(jreviews, {
		lang: jreviews.lang || {},
		_onloadFn: [],
		_onloadFnAjax: [],
		_onloadFnKeys: [],
		_onloadFnAjaxKeys: []
	});

	jreviews.addOnload = function(fn_name, fn) {

		if($.inArray(fn_name,jreviews._onloadFnKeys) == -1) {
			jreviews._onloadFn.push(fn);
			jreviews._onloadFnKeys.push(fn_name);
		}
	};

	jreviews.addOnloadAjax = function(fn_name, fn) {

		if($.inArray(fn_name,jreviews._onloadFnAjaxKeys) == -1) {
			jreviews._onloadFnAjax.push(fn);
			jreviews._onloadFnAjaxKeys.push(fn_name);
		}
	};

	jreviews.onload = function() {

		jrPage = jrPage.length > 0 ? jrPage : $('div.jr-page');

		jrMain = jrMain.length > 0 ? jrMain : $('div.jr-main');

		jreviews.addOnload('datepicker',        jreviews.datepicker);
		jreviews.addOnload('popup',             jreviews.popup);
		jreviews.addOnload('showmore',          jreviews.showmore);
		jreviews.addOnload('calendar',          jreviews.calendar.init);
		jreviews.addOnload('lightbox',          jreviews.lightbox);
		jreviews.addOnload('favorite',          jreviews.listing.favorite);
		jreviews.addOnload('tabs',              jreviews.tabs);
		jreviews.addOnload('login',             jreviews.login.init);
		jreviews.addOnload('social-sharing',    jreviews.socialSharing);
		jreviews.addOnload('pagenav',           jreviews.pageNav.init);

		jreviews.addOnload('listing-create',    jreviews.listing.formSetup);
		jreviews.addOnload('listing-manager',   jreviews.listing.manager);
		jreviews.addOnload('listing-widgets',   jreviews.listing.widgets);
		jreviews.addOnload('listing-claim',     jreviews.claim.init);
		jreviews.addOnload('listing-inquiry',   jreviews.inquiry.init);
		jreviews.addOnload('listing-masonry',   jreviews.masonry);

		jreviews.addOnload('review-init',       jreviews.review.init);
		jreviews.addOnload('review-edit',       jreviews.review.edit);
		jreviews.addOnload('review-vote',       jreviews.review.vote);
		jreviews.addOnload('review-reply',      jreviews.review.reply);

		jreviews.addOnload('report',            jreviews.report.init);

		jreviews.addOnload('comment-manager',   jreviews.discussion.manager);
		jreviews.addOnload('comment-new',       jreviews.discussion.init);
		jreviews.addOnload('comment-reply',     jreviews.discussion.reply);
		jreviews.addOnload('comment-earlier',   jreviews.discussion.earlierComment);

		jreviews.addOnload('media-init',        jreviews.media.init);
		jreviews.addOnload('media-thumbs',      jreviews.media.generateThumbs);
		jreviews.addOnload('media-manager',     jreviews.media.manager);
		jreviews.addOnload('media-download',    jreviews.media.download);

		jreviews.addOnload('search-simple',     jreviews.search.simple);
		jreviews.addOnload('search-adv-page',   jreviews.search.advancedPage);
		jreviews.addOnload('search-adv-module', jreviews.search.advancedModule);

		jreviews.addOnload('fields-module', 	jreviews.fieldsModule);

		jreviews.addOnload('directory-tree',    jreviews.directoryTree);

		jreviews.addOnload('facebook',          jreviews.facebook.load);

		jreviews.addOnload('comparison',        jreviews.compare);

		jreviews.addOnload('module-slider',     jreviews.module.init);

		jreviews.addOnload('trix-editor', 		jreviews.trix);

		jreviews.addOnload('jreviews-formbuilder', jreviews.formbuilder);

		$.each(jreviews._onloadFn, function(i,fn) {
			if(fn !== undefined) fn();
		});
	};

	/**
	 * Call after all scripts finish loading
	 */
	if(typeof head == 'function') {

		head.ready(function() {

			jreviews.onload();
		});
	}
	else {

		$(function() {

			jreviews.onload();
		});
	}

	jreviews.getScript = function(script,callback) {

		return $.ajax({type: "GET",url: script, success: function() {if(undefined!==callback) callback();},dataType: "script", cache: true});
	};

	jreviews.__t = function(string, options) {

		var defaults = {
			'add_ul':true
		};

		options = $.extend(defaults,options);

		if(typeof string == 'string') {

			return jreviews.lang[string] || string;
		}
		else {

			var out = [];

			$.each(string, function(i,s) {

				var t, l, stmp;

				if(typeof s == 'string') {

					t = jreviews.lang[s] || s;

				}
				else {

					stmp = s.shift();

					l = jreviews.lang[stmp] || stmp;

					s.unshift(l);

					t = window.sprintf.apply(null,s);
				}

				out.push('<li>' + t + '</li>');
			});

			if(options.add_ul) {

				return '<ul>' + out.join('') + '</ul>';
			}
			else {

				return out.join('');
			}
		}

	};

	jreviews.dispatch = function(options) {

		options = options || {};

		var method =  (options.form_id !== undefined || options.form !== undefined) ? 'POST' : 'GET';

		if(undefined !== options.method) method = options.method;

		var data = '';

		if(options.controller !== undefined) {
			if(method.toLowerCase() == 'post') {
				data = $.param({'data[controller]':options.controller,'data[action]':options.action});
			}
			else {
				data = $.param({'url':options.controller + '/' + options.action});
			}
		}

		var type = options.type || "json";

		if(undefined !== options.form_id || undefined !== options.form)
		{
			var form = options.form || $('#'+options.form_id);

			if(undefined !== options.controller) {

				form.find('input[name=data\\[controller\\]], input[name=data\\[action\\]]').remove();
			}

			data = form.serialize()+'&'+data;
		}

		if(options.data) {
			data = data + '&' + $.param(options.data);
		}

		var ajaxOptions = {type: method, url: s2AjaxUri, data: data, dataType: options.type};

		if (method.toLowerCase() == 'get' && options.cache !== undefined) {
			ajaxOptions.cache = options.cache;
		} else if (method.toLowerCase() != 'get') {
			ajaxOptions.headers = { "Cache-Control": "no-cache" };
		}

		return $.ajax(ajaxOptions);
	};

	jreviews.compare = function() {

		if('undefined' !== typeof(jreviewsCompare)) {
			jreviewsCompare.init();
		}
	};

	jreviews.datepicker = function() {

		if($().datepicker) {

			try {
				jreviews.datepickerClear();
			} catch (err) {}

			$.datepicker.setDefaults({
				showOn: 'both',
				buttonImage: jreviews.calendar_img,
				showButtonPanel: true,
				buttonImageOnly: true,
				buttonText: 'Calendar',
				dateFormat: 'yy-mm-dd',
				changeMonth: true,
				changeYear: true
			});

			$('.jr-date:not(".jr_ready")').addClass('jr-ready').each(function() {
				$(this).datepicker({
					'yearRange':$(this).data('yearrange'),
					'minDate':$(this).data('mindate'),
					'maxDate':$(this).data('maxdate')
				});
			});
		}
	};

	jreviews.datepickerClear = function() {

		var old_fn = $.datepicker._updateDatepicker;

		$.datepicker._updateDatepicker = function(inst) {

			old_fn.call(this, inst);

			var buttonPane = $(this).datepicker("widget").find(".ui-datepicker-buttonpane");

			if(buttonPane.find('.clearDate').length === 0) {
				$("<button class='clearDate' type='button' class='ui-datepicker-clean ui-state-default ui-priority-primary ui-corner-all'>"+jreviews.__t('CLEAR_DATE')+"</button>").appendTo(buttonPane).click(function(ev) {
					$.datepicker._clearDate(inst.input);
				});
			}
		};
	};

	jreviews.fieldsModule = function() {
		$('.jr-fields-module').each(function() {
			var $list = $(this),
				show_limit = $list.data('showLimit') || 5;

			var $items = $list.find('li');

			var $showAll = $('<a href="javascript:;" class="jrFieldsShowAll">'+jreviews.__t('FILTERS_SHOW_ALL')+'</a>').hide();
			var $showLess = $('<a href="javascript:;" class="jrFieldsShowLess">'+jreviews.__t('FILTERS_SHOW_LESS')+'</a>').hide();

			if ($list.data('showAll') == 1 && $items.length > show_limit) {
				$list.after($showAll, $showLess);

				$showAll.on('click', function(e) {
					e.preventDefault();
					$(this).hide();
					$showLess.show();
					$items.removeClass('jrOptionHidden');
				}).show();

				$showLess.on('click', function(e) {
					e.preventDefault();
					$(this).hide();
					$list.find('li:gt('+(show_limit - 1)+')').addClass('jrOptionHidden');
					$showAll.show();
				})
			}
		});
	};

	jreviews.directoryTree = function() {

		if($().treeview) {

			$('.jr-directory-tree')
				.treeview({animated: 'fast',unique: true,collapsed: false})
				.each(function() {

					var el = $(this),
						cat_id = el.data('cat-id'),
						dir_id = el.data('dir-id'),
						show_dir = el.data('show-dir'),
						current = null;

					if(cat_id) {

						current = $('.jr-tree-cat-' + cat_id);

					}
					else if(dir_id && show_dir) {

						current = $('.jr-tree-dir-' + dir_id);

					}

					if(current !== null) {

						current
							.removeClass("closed")
							.swapClass("expandable","collapsable")
							.swapClass("expandable-hitarea","collapsable-hitarea")
							.swapClass("lastExpandable-hitarea","lastCollapsable-hitarea")
							.swapClass("lastExpandable","lastCollapsable")
							;

						current.parents("ul, li").show()
							.removeClass("closed")
							.swapClass("expandable","collapsable")
							.swapClass("expandable-hitarea","collapsable-hitarea")
							.swapClass("lastExpandable-hitarea","lastCollapsable-hitarea")
							.swapClass("lastExpandable","lastCollapsable");

						current.children("ul, li").show().removeClass("closed");

						current.children("div")
							.swapClass("expandable","collapsable")
							.swapClass("expandable-hitarea","collapsable-hitarea");

						current.parents("ul, li").children("div")
							.swapClass("expandable-hitarea","collapsable-hitarea")
							.swapClass("lastExpandable-hitarea","lastCollapsable-hitarea");

						// current.closest("li").swapClass("lastExpandable","lastCollapsable");
					}

				});

		}

	};

	jreviews.tabs = function(tabs) {

		if(typeof $().tabs != "undefined") {

			tabs = tabs || $('div.jr-page .jr-tabs:not(".jr-ready")');

			var base_url = $('base').attr('href');

			if(base_url !== undefined && base_url.length)
			{
				tabs.find('ul:eq(0) li a').each(function() {
					$(this).attr('href',$(location).attr('href').split('#')[0] + $(this).attr('href'));
				});
			}

			tabs.addClass('jr-ready').tabs();
		}
	};

	jreviews.pageNav = {

		init: function() {

			$(window).on('popstate', function(e) {
				var state = e.originalEvent.state;
				var form = $('form');
				if (state !== null && state.url != '')  {
					if (state.form) {
						form = $('form[data-module-id="'+state.form.id+'"]');
						$.each(state.form.data, function(i, row) {
							var $input = form.find('[name="'+row.name+'"]');
							var $events = $._data($input[0], 'events');
							if ($events !== undefined && $._data($input[0], 'events').updateFilterNoChange !== undefined) {
								$input.val(row.value).trigger('updateFilterNoChange');
							}
						});
					}
					jreviews.pageNav.loadUrl(state.url, {scroll: form.data('update-scroll') || 0});
				}
			});

			jreviews.pageNav.disableSelects();

			jrMain.on('click','div.jr-pagenav[data-ajax="1"] a, div.jr-list-layout[data-ajax="1"] a',function(e) {

				e.preventDefault();

				$(this).trigger('jrPageNav');
			});

			jrMain.on('change','div.jr-pagenav[data-ajax="1"] select.jr-pagenav-limit, div.jr-pagenav[data-ajax="1"] select.jr-list-sort',function(e) {

				e.preventDefault();

				$(this).trigger('jrPageNav');
			});

			jrMain.on('jrPageNav','div.jr-pagenav[data-ajax="1"] a, div.jr-pagenav[data-ajax="1"] select, div.jr-list-layout[data-ajax="1"] a',function(e) {

				e.preventDefault();

				var refUrl = null,
					url = null,
					el = $(this),
					form = el.closest('form'),
					pageNav = jrPage.find('div.jr-pagenav');

				// List ajax pagination

				if (form.length == 0) {
					if (el.is('a')) {
						url = el.attr('href');
					} else if (el.is('select') && (el.hasClass('jr-pagenav-limit') || el.hasClass('jr-list-sort'))) {
						url = el.val();
					}

					if (url) {
						var page = el.closest('.jr-page');
						var scroll = el.parents('.jrPaginationBottom').length ? true : false;
						jreviews.pageNav.loadUrl(url, {push: true, scroll: scroll});
					}

				// This is the old ajax pagination used with paidlistings

				} else {

					var	limit = Number($('div.jr-pagenav select.jr-pagenav-limit').first().find(':selected').text()),
						page_number = 1,
						max_page = Number(pageNav.find('a.jr-pagenav-page').last().html()),
						data = [];

					var controller = form.find('input[name="data[controller]"]').val(),
						action = form.find('input[name="data[action]"]').val() || 'index';

					if(limit === 0) {

						limit = form.find('input[name="data[limit]"]').val() || 10;
					}

					if(el.hasClass('jr-pagenav-page')) {

						page_number = Number(el.html());
					}
					else if(el.hasClass('jr-pagenav-next')) {

						page_number = Number(el.siblings('span.jr-pagenav-current').html()) + 1;

						page_number = page_number > max_page ? page_number : page_number;
					}
					else if(el.hasClass('jr-pagenav-prev')) {

						page_number = Number(el.siblings('span.jr-pagenav-current').html()) - 1;

						page_number = page_number < 0 ? 1 : page_number;
					}

					data.push({name:jreviews.qvars.pg,value:page_number});

					data.push({name:'limit',value:limit});

					var loadingPage = jreviews.dispatch({method:'get',type:'html',controller:controller,action:action,data:data});

					loadingPage.done(function(html) {

						el.closest('.jr-page-inner, .jr-page').first().html(html);
					});

				}
			});

		},

		disableSelects: function() {
			jrMain.find('div.jr-pagenav[data-ajax="1"] select.jr-pagenav-limit, div.jr-pagenav[data-ajax="1"] select.jr-list-sort').removeAttr('onchange');
		},

		loadUrl: function(url, options) {

			var options = $.extend({push: false, scroll: false}, options);

			options['refUrl'] = url;

		    url += url.indexOf('?') === -1 ? '?' : '&';
		    url = url + 'format=raw';

			var loadingPage = $.ajax({type: 'get', url: url, dataType: 'html'});

			jrMain.addClass('jrIsLoading');

			$(document).trigger('jreviews-onBeforeAjaxUpdate');

			loadingPage.done(function(html) {
				jreviews.pageNav.updatePage(html, options);
			});
		},

		updatePage: function(html, options) {

			var options = $.extend({push: false, scroll: false, form: {}}, options);

			$html = $('<div>'+html+'</div>');

			var titleMeta = $html.find('meta[name="title"]').detach(),
				title = titleMeta.attr('content'),
				urlMeta = $html.find('meta[name="url"]').detach();
				refUrl = urlMeta.attr('content');

			if (title !== undefined) {
				$('title').html(title);
			} else {
				title = $('title').html();
			}

			if (options.refUrl == undefined && refUrl !== undefined) {
				options.push = true;
				options.refUrl = urlMeta.attr('content');
			}

			jrMain.html($html.html());

			if (options.scroll) {
				var main = jrMain.find('.jr-pagenav');
				if (main.length) {
					main.jrScrollTo({duration:350, offset: -150});
				}
			}

			if (options.push && options.refUrl) {
				window.history.pushState({url: options.refUrl, form: options.form}, null, options.refUrl);
			}

			$(document).trigger('jreviews-onAfterAjaxUpdate', [options.refUrl]);

			if (jrMain.find('.jr-map-canvas').length === 0) {
				jreviews.masonry();
				jrMain.removeClass('jrIsLoading');
			}

			// Google analytics support for tracking pageviews on pages loaded via ajax
			if (typeof ga == 'function') {
				ga('set', { page: options.refUrl.replace(/^.*\/\/[^\/]+/, '') , title: title });
				ga('send', 'pageview');
			}
		}
	};

	jreviews.socialSharing = function() {

		if(jrPage.find('.jr-tweet').length) {

			window.twttr = (function(d, s, id) {
			  var js, fjs = d.getElementsByTagName(s)[0],
			    t = window.twttr || {};
			  if (d.getElementById(id)) return t;
			  js = d.createElement(s);
			  js.id = id;
			  js.src = "//platform.twitter.com/widgets.js";
			  fjs.parentNode.insertBefore(js, fjs);
			  t._e = [];
			  t.ready = function(f) {
			    t._e.push(f);
			  };
			  return t;
			}(document, "script", "twitter-wjs"));
		}

		var GPlusOne = jrPage.find('.jr-gplusone');

		if(GPlusOne.length) {

			GPlusOne.remove();

			(function(d, s, id) {
			  var js, fjs = d.getElementsByTagName(s)[0];
			  if (d.getElementById(id)) return;
			  js = d.createElement(s); js.id = id;
			  js.src = "//apis.google.com/js/plusone.js";
			  fjs.parentNode.insertBefore(js, fjs);
			}(document, 'script', 'google-jssdk'));
		}

		var LinkedIn = jrPage.find('.jr-linkedin');

		if(LinkedIn.length) {

			LinkedIn.remove();

			(function(d, s, id) {
			  var js, fjs = d.getElementsByTagName(s)[0];
			  if (d.getElementById(id)) return;
			  js = d.createElement(s); js.id = id;
			  js.src = "//platform.linkedin.com/in.js";
			  fjs.parentNode.insertBefore(js, fjs);
			}(document, 'script', 'linkedin-jssdk'));
		}

		if(jrPage.find('.jr-pinterest').length) {

			(function(d, s, id) {
			  var js, fjs = d.getElementsByTagName(s)[0];
			  if (d.getElementById(id)) return;
			  js = d.createElement(s); js.id = id;
			  js.src = "//assets.pinterest.com/js/pinit.js";
			  fjs.parentNode.insertBefore(js, fjs);
			}(document, 'script', 'pinterest-jssdk'));
		}
	};

	jreviews.login = {

		init: function() {

			$('body').on('click','.jr-show-login',function() {
				$('#jr-login-form').toggle();
			});
		}
	};

	jreviews.discussion = {

		init: function() {

			var formDiv = $('div.jr-form-comment-outer'),
				form = formDiv.find('form[id^="jr-form-comment"]'),
				form_id = form.attr('id'),
				discussion_id = form.data('discussion-id'),
				buttons = form.find('.jr-buttons'),
				validation = form.find('.jr-validation'),
				commentAddButton = $('button.jr-comment-add');

			// Add comment button
			commentAddButton.on('click',function(e) {

				e.preventDefault();

				var el = $(this);

				formDiv.slideDown();//.delay().jrScrollTo({duration:500,offset:-50});

				el.fadeOut();

			});

			// Add comment button - first load only
			commentAddButton.one('click',function(e) {

				jreviews.common.initForm(form_id,true);

				jreviews.common.validateUsername(form);

				jreviews.common.validateEmail(form);
			}).trigger('click');

			// Cancel comment
			form.on('click','button.jr-comment-cancel',function(e) {

				e.preventDefault();

				formDiv.slideUp();

				commentAddButton.fadeIn();//.delay().jrScrollTo({duration:500,offset:-50});
			});

			// Submit comment
			form.on('click','button.jr-comment-submit',function(e) {

				e.preventDefault();

				var commentsDiv = $('.jr-review-comments');

				// Actions run on buttton press
				buttons.find('button').prop('disabled',true);

				buttons.append('<span class="jrLoadingSmall">');

				validation.hide();

				var submittingForm = jreviews.dispatch({form:form});

				submittingForm.done(function(res) {

					if(res.success) {

						formDiv.slideUp().remove();

						var success = '<div class="jr-message jrSuccess">' +
							(res.moderation ? jreviews.__t('DISCUSSION_SUBMIT_MODERATED') : jreviews.__t('DISCUSSION_SUBMIT_NEW')) +
							'</div>';

						commentsDiv.prepend('<div class="jr-new-comment jrHidden">' + success + res.html + '</div>')

							.jrScrollTo({duration:500,offset:-50}).delay()

							.find('.jr-new-comment').slideDown(1500)

							.find('div.jr-message').delay(4000).slideUp(500);

						jreviews.discussion.manager();

						jreviews.discussion.earlierComment();

					}
					else {

						if(res.str.length) {

							validation.html(jreviews.__t(res.str)).show();

							jreviews.captcha.reset(form.find('.g-recaptcha').data('id'));
						}
					}

				});

				submittingForm.always(function() {

					buttons.find(".jrLoadingSmall").remove();

					buttons.find('button').prop('disabled',false);

				});

			});

		},

		reply: function() {

			$('button.jr-comment-reply:not(".jr-ready")').on('click',function(e) {

				e.preventDefault();

				var el = $(this),
					text = el.find('span').eq(1),
					discussion_id = el.data('discussion-id'),
					review_id = el.data('review-id');

				var loadingForm = jreviews.dispatch({method:'get',type:'html',controller:'discussions',action:'reply',data:{discussion_id:discussion_id,review_id:review_id}});

				loadingForm.done(function(html) {

					// Call dialog
					var buttons = {},
						open = function() {

							var dialog = $(this),
								form = dialog.find('form').last();

							jreviews.common.initForm(form.attr('id'),true);

							jreviews.common.validateUsername(form);

							jreviews.common.validateEmail(form);

							dialog.on('click','.jr-show-login',function() {

								dialog.find('.jr-login-form').toggle();
							});
						};

					buttons[jreviews.__t('SUBMIT')] = function() { jreviews.discussion.submitReply(el, $(this)); };

					buttons[jreviews.__t('CANCEL')] = function() { $(this).dialog('close'); };

					var dialog = $.jrDialog(html, {buttons:buttons,title:text.html(),open:open,width:'640px'});

					dialog.find('.jr-form-comment-outer').show();

				});

			}).addClass('jr-ready');

		},

		manager: function () {

			var manager = $('ul.jr-comment-manager:not(".jr-ready")').addClass('jr-ready');

			manager.on('click','a.jr-comment-edit',function() {

				var el = $(this),
					text = el.find('span').eq(1),
					discussion_id = el.data('discussion-id');

				var loadingForm = jreviews.dispatch({method:'get',type:'html',controller:'discussions',action:'edit',data:{discussion_id:discussion_id}});

				loadingForm.done(function(html) {

					// Call dialog
					var buttons = {};

					buttons[jreviews.__t('SUBMIT')] = function() { jreviews.discussion.submitEdit(el, $(this)); };

					buttons[jreviews.__t('CANCEL')] = function() { $(this).dialog('close'); };

					var dialog = $.jrDialog(html, {buttons:buttons,title:text.html(),width:'640px',minHeight:'300'});

					dialog.find('.jr-form-comment-outer').show();

				});

			});

			manager.on('click','a.jr-comment-delete',function() {

				var el = $(this),
					discussion_id = el.data('discussion-id'),
					token = el.data('token'),
					text = el.find('span').eq(1);

				var deleteSubmit = new $.Deferred();

				deleteSubmit.done(function(dialog) {

					var data = {id:discussion_id,token:token};

					var deletingListing = jreviews.dispatch({method:'get',type:'json',controller:'discussions',action:'_delete','data':data});

					deletingListing.done(function(res) {

						if(res.success) {

							dialog.dialog('close');

							el.closest('.jr-layout-outer').html('<div class="jrSuccess">'+jreviews.__t('DISCUSSION_DELETED')+'</div>').jrScrollTo();
						}
						else {

							dialog.dialog('option','buttons',[]);

							if(res.str.length) {

								dialog.html(jreviews.__t(res.str));
							}
						}
					});

				});

				// Call dialog
				var buttons = {};

				buttons[text.html()] = function() { deleteSubmit.resolve($(this)); };

				buttons[jreviews.__t('CANCEL')] = function() { $(this).dialog('close'); };

				$.jrDialog(jreviews.__t('DISCUSSION_DELETE_CONFIRM'), {buttons:buttons,title:text.html(),width:'640px'});
			});
		},

		submitReply: function(el, dialog) {

			var form = dialog.find('form').last(),
				form_id = form.attr('id'),
				validation = form.find('.jr-validation');

			validation.hide();

			var submittingForm = jreviews.dispatch({form:form});

			submittingForm.done(function(res) {

				if(res.success) {

					dialog.dialog('close');

					var success = '<div class="jr-message jrSuccess">' +
						(res.moderation ? jreviews.__t('DISCUSSION_SUBMIT_MODERATED') : jreviews.__t('DISCUSSION_SUBMIT_NEW')) +
						'</div>';

					var newComment = $('<div class="jr-new-comment jrHidden">' + success + res.html + '</div>');

					el.closest('.jr-layout-outer').after(newComment);

					newComment.jrScrollTo({duration:500,offset:-50}).delay()

						.slideDown(1500)

						.find('div.jr-message').delay(4000).slideUp(500);

				}
				else if(res.str.length) {

					validation.html(jreviews.__t(res.str)).show();
				}

				jreviews.discussion.manager();
			});

		},

		submitEdit: function(el, dialog) {

			var form = dialog.find('form'),
				form_id = form.attr('id'),
				validation = form.find('.jr-validation');

			validation.hide();

			var submittingForm = jreviews.dispatch({form:form});

			submittingForm.done(function(res) {

				if(res.success) {

					dialog.dialog('close');

					var success = $('<div class="jr-message jrSuccess" style="margin-top:10px;">' +
						(res.moderation ? jreviews.__t('DISCUSSION_SUBMIT_MODERATED') : jreviews.__t('DISCUSSION_SUBMIT_EDIT')) +
						'</div>');

					var newComment = $('<div class="jr-new-comment jrClear jrHidden">' + res.html + '</div>');

					var innerLayout = el.closest('.jr-layout-inner');

					innerLayout.html(success).prepend(newComment);

					newComment.jrScrollTo({duration:500,offset:-50}).delay().slideDown(1500);

				}
				else if (res.str.length) {

					validation.html(jreviews.__t(res.str)).show();
				}

				jreviews.discussion.manager();

				jreviews.discussion.earlierComment();

			});

		},

		earlierComment: function() {

			$('.jr-earlier-comment').each(function() {

				var el = $(this),
					post_id = el.data('post-id'),
					contentBox = el.next('.jr-earlier-comment-content');

				var loadComment = $.Deferred();

				loadComment.done(function() {

					var loadingComment = jreviews.dispatch({method:'get',type:'html',controller:'discussions',action:'getPost',data:{id:post_id}});

					loadingComment.done(function(html) {

						contentBox.html(html);

					});

				});

				el.jrPopup({
					className: 'jrPopup',
					delay: 500,
					onBeforeShow: function() {

						if (contentBox.html() === "") {

							contentBox.html('<span class="jrLoadingMedium"></span>');

							loadComment.resolve();
						}
					}
				});
			});
		}

	};

	jreviews.module = {

		init: function(){

			$('div.jr-module-slider, div.jr-plugin-slider').not(".jr-ready").addClass('jr-ready').each(function(){

				var el = $(this),
					elHeight = el.height(),
					sliderContainer = el.find('.jrModuleItems'),
					o = el.data('options');

				if(o.page_count < 2) return;

				if (elHeight > 0) {
					el.css({'max-height': elHeight+'px', overflow: 'hidden'});
				}

				// display all pages
				el.removeClass('jrSliderSinglePage');

				// add a class to display vertical arrows
				if(o.orientation == 'vertical') {
					el.addClass('jrSliderVertical');
				}

				// add a class to display arrows on the sides
				if(o.nav_position == 'side') {
					el.addClass('jrSliderSideArrows');
				}

				var config = {
						mode: o.orientation,
						speed: 600,
						easing: 'swing',
						oneToOneTouch: true,
						auto: parseInt(o.slideshow,10),
						pause: o.slideshow_interval*1000,
						autoHover: true,
						pager: true,
						onSliderLoad: function() {
							el.css({'max-height': '100%', overflow: 'visible'});
						}
				};

				var slider = {};

				// If the slider is inside a jQuery tab we reload the slider when the tab is shown
				var tab = el.closest('.jr-tabs');

				// If the slider is inside a Bootstrap tab we reload the slider when the tab is shown
				var btab = el.closest('.tab-pane');

				// If the slider is inside a JomSocial tab we reload the slider when the tab is shown
				var jstab = el.closest('[data-ui-object="joms-tabs"]');

				var firstTabID, anchor, currTabIndex;

				if(tab.length) {

					firstTabID = tab.find('li a').eq(0).attr('href').split('#')[1];

					currTabIndex = tab.find('.ui-tabs-panel').index(sliderContainer.closest('.ui-tabs-panel'));

					// First tab always gets initialized on page load
					if(firstTabID && sliderContainer.parents('#'+firstTabID).length) {
						sliderContainer.bxSlider(config);
					}
					else {

						tab.find('.ui-tabs-nav > li > a').eq(currTabIndex).one('click', function() {

							sliderContainer.bxSlider(config);
						});
					}
				}

				else if(btab.length) {

					setTimeout(function(){

						anchor = btab.parent().prev('ul').find('li a').eq(0).attr('href');

						firstTabID = anchor ? anchor.split('#')[1] : null;

						// First tab always gets initialized on page load
						if(firstTabID && sliderContainer.parents('#'+firstTabID).length) {
							sliderContainer.bxSlider(config);
						}
						else {

							$('a[data-toggle="tab"]').on('shown.bs.tab', function (e) {

								if(typeof slider.redrawSlider == 'function') {
									slider.redrawSlider();
								}
								else {
									slider = sliderContainer.bxSlider(config);
								}
							});

							var tab_id = btab.attr('id').match(/[\d]+$/);

							if(tab_id) {

								$('#cbtabnav' + tab_id + ' a').one('click',function() {
									sliderContainer.bxSlider(config);
								});
							}
						}

					},550);
				}

				else if(jstab.length) {

					firstTabID = jstab.find('.joms-tab__bar a').eq(0).attr('href').split('#')[1];

					currTabIndex = jstab.find('.joms-tab__content').index(sliderContainer.closest('.joms-tab__content'));

					// First tab always gets initialized on page load
					if(firstTabID && sliderContainer.parents('#'+firstTabID).length) {
						sliderContainer.bxSlider(config);
					}
					else {

						jstab.find('.joms-tab__bar a').eq(currTabIndex).one('click', function(){

							sliderContainer.bxSlider(config);
						});
					}
				}

				else {
					if(typeof sliderContainer.bxSlider == 'function') {
						sliderContainer.not('.jr-slider-ready').addClass('jr-slider-ready').bxSlider(config);
					}
				}

			});
		}
	};

	jreviews.lightbox = function() {

		if($('.jrPage a.fancyvideo').length > 0)
		{
			$('a.fancyvideo').magnificPopup({
				type: 'iframe',
				callbacks: {
					elementParse: function(item) {
						item.src = item.src + '&lightbox=1&tmpl=component';
					}
				}
			});
		}

		if($('.jrPage a.fancybox').length > 0)
		{
			// Listing images
			$('.jrPage').magnificPopup({
				delegate: 'a.fancybox[rel="gallery"]',
				type: 'image',
				mainClass: 'mfp-fade',
				zoom: {
					enabled: true,
					duration: 300 // The duration all needs to be changed in css
				},
				gallery: {
					enabled: true,
					preload: [0,2],
					navigateByImgClick: true,
					arrowMarkup: '<button title="%title%" type="button" class="mfp-arrow mfp-arrow-%dir%"></button>',
					tCounter: '<span class="mfp-counter">%curr% of %total%</span>'
				}
			});

			// Review images
			$('.jrReviewContainer .jrMediaContainer').each(function() {

				$(this).magnificPopup({
					delegate: 'a.fancybox',
					type: 'image',
					mainClass: 'mfp-fade',
					zoom: {
						enabled: true,
						duration: 150 // The duration all needs to be changed in css
					},
					gallery: {
						enabled: true,
						preload: [0,2],
						navigateByImgClick: true,
						arrowMarkup: '<button title="%title%" type="button" class="mfp-arrow mfp-arrow-%dir%"></button>',
						tCounter: '<span class="mfp-counter">%curr% of %total%</span>'
					}
				});

			});
		}
	};

	jreviews.media = {

		init: function() {

			if('undefined' === typeof(jreviewsMedia)) return false;

			jreviews.media.formSetup();

			// Media Manager drag and drop reordering
			var mediaManager = $('div.jr-listing-media'),
				tabs = mediaManager.find('.jr-tabs-media'),
				types = ['photo','photo_owner','photo_user','video','video_owner','video_user','audio','attachment'];

			mediaManager.find('div.jr-media-sortable').sortable({
				axis: 'y',
				containment: 'parent',
				handle: '.jr-sort-handle',
				cursor: 'move',
				stop: jreviewsMedia.reorder
			});

			$(types).each(function(i,type) {

				if(!mediaManager.find('div#'+type).length)  {

					mediaManager.find('a[href="#'+type+'"]').closest('li').remove();
				}
				else {

					mediaManager.find('a[href="#'+type+'"]').closest('li').show();
				}
			});

			jreviews.tabs(tabs);

			// Implement use for function for photos
			jreviewsMedia.photoFunction(jrPage);

			// Video Gallery
			jreviewsMedia.videoGallery.init();

			// Photo Gallery
			jreviewsMedia.photoGallery.init();

			// Audio Player
			jreviewsMedia.audioPlayer();
		},

		generateThumbs: function() {

			// Find images requiring new thumbnails
			$('img[data-thumbnail="1"]').not('.jr-ready').each(function() {

				var el = $(this);

				el.addClass('jr-ready');

				var data = {
					'data[media_id]': $(this).data('media-id'),
					'data[size]': $(this).data('size'),
					'data[mode]': $(this).data('mode')
				};

				var generatingThumbnail = jreviews.dispatch({method:'post',type:'json',controller:'media_upload',action:'generateThumb',data:data});

				generatingThumbnail.done(function(res) {

					if(res.success && res.url !== '') {

						var thumbDiv = el.closest('div');

						if(thumbDiv.css('max-width') != 'none') {

							el.css({width:thumbDiv.css('max-width'),height:'auto'});
						}
						else {

							el.css({width:res.width+'px',height:res.height+'px'});
						}

						el.attr('src',res.url);
					}
				});
			});
		},
		formSetup: function () {
			// Media Upload Form
			var uploadPage = $('div.jr-media-upload'),
				uploaderDiv = uploadPage.find('#jr-media-uploader'),
				isAndroidOS = uploaderDiv.data('android');

			if(uploadPage.length) {

				var userForm = uploadPage.find('form#jr-user-info');

				// PaidListings - add the plan id as a hidden input
				try {

					if(jreviews.paid.plan.plan_selected) {

						var planId = $('<input type="hidden" name="data[plan_id]">').val(jreviews.paid.plan.plan_selected);

						uploadPage.find('form').append(planId);
					}
				}
				catch(e) {}

				jreviews.common.validateUsername(userForm);

				jreviews.common.validateEmail(userForm);

				jreviews.common.initForm('jr-user-info',true);

				var mediaUploadForm  = uploadPage.find('form#jr-form-media-upload'),
					fileValidation = mediaUploadForm.data('file-validation');

				jreviewsMedia.initUploader('jr-media-uploader',{fileValidation: fileValidation, multiple: isAndroidOS ? false : true});

				// For iOS
				var uploadButton = uploadPage.find('.jr-upload-button'),
					uploadInput = uploadButton.find('input'),
					mobileOptions = uploadPage.find('.jr-upload-mobile-options');

				if(mobileOptions.length) {

					uploadButton.hide();

					mobileOptions
						.on('change',function() {

							var sel = $(this).val();

							uploadInput.removeAttr('multiple');

							if(sel == 'multiple') {
								uploadInput.attr('multiple','multiple');
							}

							if(sel !== '') uploadInput.trigger('click');
						})
						.buttongroup();
				}
			}
		},

		manager: function () {

			var page = jrMain.length ? jrMain : jrPage;

			// Edit
			page.on('click','.jr-media-edit',function() {

				var el = $(this),
					media_id = el.data('media-id'),
					text = el.find('span').eq(1);

				var loadingForm = jreviews.dispatch({method:'get',type:'html',controller:'media',action:'_edit',data:{media_id:media_id}});

				loadingForm.done(function(res) {

					// Call dialog
					var buttons = {};

					buttons[jreviews.__t('SUBMIT')] = function() { jreviews.media.editSubmit(el, $(this)); };

					buttons[jreviews.__t('CANCEL')] = function() {

						$(this).find('#jr-video-player').trigger('jrVideoDestroy');

						$(this).find('#jr-audio-player').remove();

						$(this).dialog('close');
					};

					var close = function() {

						$(this).find('#jr-video-player').trigger('jrVideoDestroy');

						$(this).find('#jr-audio-player').remove();
					};

					var open = function() {

						var dialog = $(this),
							videoGallery = dialog.find('div.jr-video-gallery'),
							audio = dialog.find('#jr-audio-player'),
							thumbnailDiv = dialog.find('.jr-thumbnails');

						if(thumbnailDiv.length) {

							thumbnailDiv.find('.jr-thumb-del')
								.on('click',function() {

									var el = $(this);

									if(el.data('delete') == 1) {

										el.data('delete',0); // Prevent multiple clicks

										var data = {id:el.data('id'), size:el.data('size')};

										var token  = el.data('token');

										if(token) data[token] = 1;

										var deletingThumb = jreviews.dispatch({method:'get',type:'json',controller:'media',action:'_deleteThumb',data:data});

										deletingThumb.done(function(res) {

											if(res.success) {
												el.fadeOut(500);
											}
											else if(res.str.length) {

												$.jrAlert(jreviews.__t(res.str));
											}
										});
									}

								})
								.on('mouseenter',function() {

									var thumb = $(this);

									var delButton = $('<span class="jrIconDelete jrIconOnly">');

									$(this)
										.data('delete',1)
										.data('contents',$(this).html())
										.html(delButton);
								})
								.on('mouseleave',function() {

									$(this)
										.data('delete',0)
										.html($(this).data('contents'));
								});

						}

						if (videoGallery.length) {

							jreviewsMedia.videoGallery.init();
						}

						if(audio.length) {

							jreviewsMedia.audioPlayer();
						}

						jreviews.common.userAutocomplete(dialog.find('form'));
					};

					var dialog = $.jrDialog(res, {buttons: buttons, title: text.html(), open: open, close: close, width:'800px'});

				});

			});

			// Delete
			page.on('click','.jr-media-delete',function() {

				var el = $(this),
					media_id = el.data('media-id'),
					token = el.data('token'),
					lang = el.data('lang'),
					text = el.find('span').eq(1),
					data = {};

				var deleteSubmit = new $.Deferred();

				deleteSubmit.done(function(dialog) {

					data[token] = 1;

					data['data[Media][media_id]'] = media_id;

					var deletingMedia = jreviews.dispatch({method:'get',type:'json',controller:'media',action:'_delete','data':data});

					deletingMedia.done(function(res) {

						if(res.success) {

							dialog.dialog('close');

							el.closest('.jr-layout-outer')

								.css('background-image','none').animate({'backgroundColor':'#f7f1ac'},600)

								.promise().done(function() {

									$(this).slideUp(600,function() { $(this).remove();});

								});
						}
						else {

							dialog.dialog('option','buttons',[]);

							dialog.html(jreviews.__t(res.str));
						}
					});

				});

				// Call dialog
				var buttons = {};

				buttons[jreviews.__t('DELETE')] = function() { deleteSubmit.resolve($(this)); };

				buttons[jreviews.__t('CANCEL')] = function() { $(this).dialog('close'); };

				$.jrDialog(jreviews.__t('MEDIA_DELETE_CONFIRM'), {buttons:buttons,title:text.html(),width:'640px'});

			});

			// Publish media
			page.on('click','.jr-media-publish',function() {

				var el = $(this),
					icon = el.find('span').eq(0),
					media_id = el.data('media-id'),
					token = el.data('token'),
					states = el.data('states'),
					lang = {on: jreviews.__t('PUBLISHED'), off: jreviews.__t('UNPUBLISHED')};

				var data = {
					media_id:media_id
				};

				data[token] = 1;

				var togglingState = jreviews.dispatch({'method':'get','type':'json','controller':'media','action':'_publish','data':data});

				togglingState.done(function(res) {

					if(res.success) {

						icon.attr("class",res.state == 1 ? states.on : states.off);

						if(icon.next().is('span')) {

							icon.next().html(res.state == 1 ? lang.on : lang.off);
						}
						else {

							icon.attr('title',(res.state == 1 ? lang.on : lang.off));
						}
					}
					else {

						$.jrAlert(jreviews.__t(res.str));
					}
				});

			});

			// Set main media
			page.on('click','button.jr-media-main',function(e) {

				e.preventDefault();

				var el = $(this),
					icon = el.find('span').eq(0),
					listing_id = el.data('listing-id'),
					media_id = el.data('media-id'),
					token = el.data('token'),
					states = el.data('states');

				var data = {
					listing_id:listing_id,
					media_id:media_id,
					state:el.data('mainMedia')
				};

				data[token] = 1;

				el.prop('disabled',true);

				var settingMainMedia = jreviews.dispatch({method:'get',type:'json',controller:'media',action:'setMainMedia',data:data});

				settingMainMedia.done(function(res) {

					if(res.success === true) {

						var process = function() {

							page.find('button.jr-media-main').not(el).each(function() {

								$(this).data('mainMedia',0);

								$(this).find('span').eq(0).attr('class',states.off);
							});
						};

						$.when( process() ).done(function() {

							el.data('mainMedia', data.state === 1 ? 0 : 1);

							icon.attr('class',data.state === 1 ? states.off : states.on);

							el.prop('disabled',false);
						});
					}

				});

			});

		},

		editSubmit: function(el, dialog) {

			var form = dialog.find('form');

			var submittingForm = jreviews.dispatch({form:form});

			submittingForm.done(function(res) {

				if(res.success) {

					dialog.dialog('close');

					el.closest('.jr-layout-inner').find('.jr-media-title a').html(res.title);
				}
				else {

					dialog.dialog('option','buttons',[]);

					dialog.html(jreviews.__t(res.str));
				}
			});
		},

		download: function() {

			$('.jr-page').on('click','.jr-media-download',function() {

				var el = $(this),
					media_id = el.data('media-id'),
					tokens = el.data('token-s'),
					tokeni = el.data('token-i');

				if(jreviews.iOS) {

					window.open(s2AjaxUri+'&url=media/download&'+jreviews.qvars.mc+'='+media_id+'&'+tokens+'=1&'+tokeni+'=1');
				}
				else {

					$('#jr-download-'+media_id).remove();

					var iframe = $('<iframe id="jr-download-'+media_id+'" style="display:none;"></iframe>');

					iframe.attr('src',s2AjaxUri+'&url=media/download&'+jreviews.qvars.mc+'='+media_id+'&'+tokens+'=1&'+tokeni+'=1').appendTo('body');
				}
			});
		}

	};

	jreviews.inquiry = {

		init: function() {

			var embedded = $('button.jr-send-inquiry-embedded,a.jr-send-inquiry-embedded');

			if(embedded.length) {

				jreviews.common.initForm('jr-form-inquiry');

				embedded.on('click',function(e) {

					e.preventDefault();

					jreviews.inquiry.submit();

				});
			}

			$('body').on('click','button.jr-send-inquiry,a.jr-send-inquiry',function(e) {

				e.preventDefault();

				var el = $(this),
					listing_id = el.data('listing-id'),
					text = el.find('span').eq(1);

				var loadingForm = jreviews.dispatch({method:'get',type:'html',controller:'inquiry',action:'create',data:{id:listing_id}});

				loadingForm.done(function(res) {

					// Call dialog
					var buttons = {};

					buttons[jreviews.__t('SUBMIT')] = function() { jreviews.inquiry.submit($(this)); };

					buttons[jreviews.__t('CANCEL')] = function() { $(this).dialog('close'); };

					$.jrDialog(res, {buttons:buttons,title:text.html(),width:'640px'});

					jreviews.common.initForm('jr-form-inquiry',true);
				});

			});
		},

		submit: function(dialog) {

			var div = $('#jr-form-inquiry-outer'),
				validation = div.find('.jr-validation'),
				form;

			form = dialog ? dialog.find('form') : div.find('form');

			div.find('.jr-validation').hide();

			validation.hide().html('');

			var submittingForm = jreviews.dispatch({form:form});

			submittingForm.done(function(res) {

				if(res.success) {

					if(dialog) dialog.dialog('option','buttons',[]);

					div.html(jreviews.__t('INQUIRY_SUBMIT'));
				}
				else {

					if(res.str.length) {

						validation.html(jreviews.__t(res.str)).show();
					}

					jreviews.captcha.reset(form.find('.g-recaptcha').data('id'));

					div.find('#jr-form-inquiry').find('.jr-validation-input').hide();

					$.each(res.inputs,function(key,val) {
						div.find('#jr-inquiry-'+val).siblings('label').find('.jr-validation-input').show();
					});

					if(dialog) dialog.trigger('failedValidation');
				}

			});

		}
	};

	jreviews.masonry = function() {

		var outer_width, maxNumItems, itemMargins, itemBorders, itemWidth;

		var resizeItems = function() {

			outer_width = masonryPage.parent().width();

			if (thumb_width > 150) {
				maxNumItems = Math.floor(outer_width/thumb_width);
			} else {
				maxNumItems = Math.floor(outer_width/150);
			}

			itemMargins = (maxNumItems-1)*12;
			itemBorders = maxNumItems*2;
			itemWidth = (outer_width-itemMargins-itemBorders)/maxNumItems;

			masonryPage.find('.jrListItem').css({ 'width': itemWidth });

		};

		var masonryPage = jrMain.find('.jr-masonry-results');

		var thumb_width = masonryPage.find('.jrListItem:first').data('thumbwidth');

		var isRTL = masonryPage.find('.jrListItem:first').data('rtl');

		if(masonryPage.length && $().jrMasonry) {

			resizeItems();

			masonryPage.jrMasonry({
				itemSelector: '.jrListItem',
				columnWidth : function( containerWidth ) {
					return containerWidth / maxNumItems;
				},
				gutterWidth: 0,
				isFitWidth: true,
				isResizable: true,
				isRTL: isRTL,
				isAnimated: true,
				animationOptions: {
				  duration: 300
				}
			});

			$(window).on('resize', function() {

				resizeItems();

				masonryPage.jrMasonry('reload');
			});
		}


	};

	jreviews.claim = {

		init: function() {

			$('button.jr-listing-claim,a.jr-listing-claim').on('click',function(e) {

				e.preventDefault();

				var el = $(this),
					listing_id = el.data('listing-id'),
					state = el.data('state'),
					text = el.find('span').eq(1);

				if(state == 'no-access') {

					$.jrAlert(jreviews.__t('CLAIM_REGISTER'));
				}
				else {

					var loadingForm = jreviews.dispatch({method:'get',type:'html',controller:'claims',action:'create',data:{listing_id:listing_id}});

					loadingForm.done(function(res) {

						// Call dialog
						var buttons = {};

						buttons[jreviews.__t('SUBMIT')] = function() { jreviews.claim.submit($(this)); };

						buttons[jreviews.__t('CANCEL')] = function() { $(this).dialog('close'); };

						$.jrDialog(res, {buttons:buttons,title:text.html(),width:'640px'});
					});
				}
			});

		},

		submit: function(dialog) {

			var form = dialog.find('form'),
				validation = form.find('.jr-validation');

			validation.html('').hide();

			var submittingForm = jreviews.dispatch({form:form});

			submittingForm.done(function(res)
			{
				if(res.success) {

					dialog.dialog('option','buttons',[]);

					form.html(jreviews.__t('CLAIM_SUBMIT'));
				}
				else if(res.str.length) {

					validation.html(jreviews.__t(res.str)).show();
				}
				else {

					dialog.trigger('failedValidation');

					validation.html(jreviews.__t('CLAIM_VALIDATE_MESSAGE')).show();
				}
			});
		}
	};

	jreviews.listing = {

		favorite: function() {

			$('body').on('click', '.jr-listing-favorite', function(e)
			{
				e.preventDefault();

				var el = $(this),
					icon = el.find('span').eq(0),
					text = el.find('span').eq(1),
					states = el.data('states'),
					listing_id = el.data('listing-id');

				el.attr('disabled','disabled');

				switch(el.data('state')) {

					case 'favored':

						var removingFavorite = jreviews.dispatch({
							method: 'get',
							type: 'json',
							controller:'listings',
							action:'_favoritesDelete',
							data: {'data[listing_id]':listing_id}
						});

						removingFavorite.done(function(res) {

							if(res.success) {

								el.data('state', 'not_favored');

									icon.attr('class',states.not_favored);

									text.html(jreviews.__t('FAVORITE_ADD'));

									$('.jr-favorite-'+listing_id)
										.html(res.count).effect('highlight', {}, 1000);
							}
							else  {

								$.jrAlert(jreviews.__t(res.str));
							}
						});

						removingFavorite.always(function() {
							el.removeAttr('disabled');
						});

						break;

					case 'not_favored':

						var addingFavorite = jreviews.dispatch({
							method: 'get',
							type: 'json',
							controller:'listings',
							action:'_favoritesAdd',
							data: {'data[listing_id]':listing_id}
						});

						addingFavorite.done(function(res) {

							if(res.success) {

								el.data('state', 'favored');

									icon.attr('class',states.favored);

									text.html(jreviews.__t('FAVORITE_REMOVE'));

									$('.jr-favorite-'+listing_id)
										.html(res.count).effect('highlight', {}, 1000);
							}
							else {

								$.jrAlert(jreviews.__t(res.str));
							}
						});

						addingFavorite.always(function() {
							el.removeAttr('disabled');
						});

						break;

					case 'no_access':

						$.jrAlert(jreviews.__t('FAVORITE_REGISTER'));

						break;
				}
			});
		},

		manager: function () {

			var manager = $('ul.jr-listing-manager:not(".jr-ready")').addClass('jr-ready');

			manager.on('click','a.jr-listing-publish,a.jr-listing-feature',function() {

				var el = $(this),
					icon = el.find('span').eq(0),
					text = el.find('span').eq(1),
					states = el.data('states'),
					id = el.data('listing-id'),
					token = el.data('token'),
					data = {id:id},
					action,
					lang;

				data[token] = 1;

				switch(el.attr('class')) {
					case 'jr-listing-publish':
						action = '_publish';
						lang = {on: jreviews.__t('PUBLISHED'), off: jreviews.__t('UNPUBLISHED')};
					break;

					case 'jr-listing-feature':
						action = '_feature';
						lang = {on: jreviews.__t('FEATURED'), off: jreviews.__t('NOT_FEATURED')};
					break;
				}

				var togglingState = jreviews.dispatch({'method':'get','type':'json','controller':'listings','action':action,'data':data});

				togglingState.done(function(res) {

					if(res.success) {

						icon.attr("class",res.state == 1 ? states.on : states.off);

						text.html(res.state == 1 ? lang.on : lang.off);
					}
					else {
						$.jrAlert(jreviews.__t(res.str));
					}
				});
			});


			manager.on('click','a.jr-listing-delete',function() {

				var el = $(this),
					text = el.find('span').eq(1),
					id = el.data('listing-id'),
					token = el.data('token'),
					data = {id:id};

				data[token] = 1;

				var deleteSubmit = new $.Deferred();

				deleteSubmit.done(function(dialog) {

					var deletingListing = jreviews.dispatch({method:'get',type:'json',controller:'listings',action:'_delete',data:data});

					deletingListing.done(function(res) {

						if(res.success) {

							dialog.dialog('close');

							try {
								el.closest('.jr-layout-outer').html('<div class="jrSuccess">'+jreviews.__t('LISTING_DELETED')+'</div>').jrScrollTo();
							} catch(e) {
								// Not able to display the deleted message
							}
						}
						else {

							dialog.dialog('option','buttons',[]);

							dialog.html(jreviews.__t(res.str));
						}
					});

				});

				// Call dialog
				var buttons = {};

				buttons[text.html()] = function() { deleteSubmit.resolve($(this)); };

				buttons[jreviews.__t('CANCEL')] = function() { $(this).dialog('close'); };

				$.jrDialog(jreviews.__t('LISTING_DELETE_CONFIRM'), {buttons:buttons,title:text.html(),width:'640px'});
			});

		},

		formSetup: function() {

			var form = $('#jr-form-listing'),
				formChooser = form.find('.jr-form-categories-outer'),
				formFields = form.find('.jr-form-listing-fields'),
				catSelect = formChooser.find('.jr-cat-select'),
				listing_id = form.data('listing-id'),
				default_cat_id = parseInt(form.data('cat-id'),10);

			// Listing edit
			if(listing_id) {

				var spinner = $('<span class="jrLoadingSmall">');

				var loadingFields = form.jreviewsFields({
					'entry_id': $('#listing_id','#jr-form-listing').val() || '',
					'value':false,
					'page_setup':true,
					'referrer':'listing'
				});

				formChooser.append(spinner);

				if(loadingFields) {

					loadingFields.done(function() {

						formFields.show().find('.jr-wysiwyg-editor').tinyMCE();

						jrPage.trigger('onListingFormLoaded');

						formFields.jrApplyFieldOptionLayout();

						spinner.remove();

					});
				}
				else {

					formFields.show().find('.jr-wysiwyg-editor').tinyMCE();

					spinner.remove();
				}
			}

			jreviews.listing.submitCategory(form);

			if(default_cat_id) {

				var lastSelect = catSelect.last().removeAttr('disabled').trigger('change');

				if(!listing_id) {

					catSelect.attr('disabled','disabled');
				}

			}

			form.on('change','input#jr-review-optional', function() {

				jrPage.find('.jr-form-review').slideToggle();
			});

			form.on('click','button.jr-cancel-listing', function(e) {

				e.preventDefault();

				history.go(-1);
			});

			form.on('click','button.jr-submit-listing',function(e) {

				e.preventDefault();

				var el = $(this),
					formOuter = form.closest('.jr-form-listing-outer'),
					buttons = form.find('.jr-buttons'),
					editorAreas = form.find('.jr-wysiwyg-editor'),
					validation = form.find('.jr-validation'),
					count = 0,
					selected = [],
					category,
					parent_category;

				$("select[id^=cat_id]").each(function()
				{
					var value = $(this).val();
					if(value > 0) selected.push($(this));
				});

				count = selected.length;

				if(count == 1) {

					form.find('#category').val(selected[0].find('option:selected').text().replace(/(- )+/,''));
				}
				else if(count > 1) {

					form.find('#category').val(selected[count-1].find('option:selected').text().replace(/(- )+/,''));

					form.find('#parent_category').val(selected[count-2].find('option:selected').text().replace(/(- )+/,''));
				}

				/* end copy text of selected cat to hidden fields for use in Geomaps */

				try {

					editorAreas.SaveTinyMCE();
				}
				catch(err) {
					// console.log('editor could not be removed');
				}

				// Set valid custom fields
				form.jrSetValidFields().join(',');

				// Actions run on buttton press
				buttons.find('button').attr('disabled','disabled');

				buttons.append('<span class="jrLoadingSmall">');

				validation.hide();

				if(default_cat_id || listing_id) {

					formChooser.find('select').removeAttr('disabled');
				}

				var submittingForm = jreviews.dispatch({form:form});

				submittingForm.done(function(res)
				{
					// Success
					if(res.success === true) {

						if(res.facebook && res.moderation === false) {

							var data = {id:res.listing_id};

							data[res.token] = 1;

							jreviews.facebook.addTokenAndPost('_postListing','html',data);
						}

						if(res.plgBeforeRenderListingSave) {

							el.trigger('plgBeforeRenderListingSave', res);

							return false;
						}

						formOuter.jrScrollTo({duration:400,offset:-100}, function() {

							formOuter.html(function() {

								var success = '',
									mediaForm = '',
									text = '';

								// Adds the listing post submit theme to the page with messages for moderated and unmoderated listings

								success = res.html;

								// Media form
								if(res.mediaForm !== undefined) {

									mediaForm = res.mediaForm;
								}

								return success + mediaForm;

							});

							jreviews.tabs(formOuter.find('.jr-tabs'));

							jreviews.media.formSetup();

						});

					}
					// Validation
					else {

						if(res.str) {

							if(res.str.length) validation.html(jreviews.__t(res.str)).show();

							jreviews.captcha.reset(form.find('.g-recaptcha').data('id'));
						}
					}

				});

				submittingForm.always(function() {

					if(default_cat_id || listing_id) {

						formChooser.find('select').attr('disabled','disabled');

					}

					form.find('#valid_fields').remove();

					buttons.find(".jrLoadingSmall").remove();

					form.find('button').removeAttr('disabled');
				});
			});
		},

		submitCategory: function (form) {

			var formLoadingDiv = $('<div class="jrRoundedPanel" style="text-align:center;"><span class="jrLoadingMedium" style="display:inline;padding:20px;"></span>'+jreviews.__t('LOADING')+'</div>');

			form.on('change','.jr-cat-select', function(e) {

				var el = $(this);
					formChooser = form.find('.jr-form-categories-outer'),
					formFields = form.find('.jr-form-listing-fields'),
					formCategories = form.find('.jr-form-categories'),
					listing_id = parseInt(form.data('listing-id'),10) || '',
					selected_cat_id = parseInt(form.find('.jr-cat-select').last().val(),10),
					editing = listing_id > 0 ? true : false;

				var level = formCategories.find('select').index(el) + 1;

				// Editing so no need to update the form
				if(editing) {

					return false;
				}

				/* required so the editor can be added again on new category changes*/
				form.find('.jr-wysiwyg-editor').RemoveTinyMCE();

				// Find disabled select lists to disable them again below
				var disabledSelects = [];
				$(".jr-form-categories").find('select[disabled="disabled"]').each(function() {
					disabledSelects.push($(this));
				});

				var data = formChooser.find('select,input').removeAttr('disabled').serializeArray();

				$(disabledSelects).each(function() {
					$(this).attr('disabled','disabled');
				});

				data.push({name:'data[level]',value:level});

				data.push({name:'data[catid]',value:el.val()});

				var submittingCategory = jreviews.dispatch({method:'get',type:'json',controller:'listings',action:'_loadForm',data:data});

				formLoadingDiv.insertAfter(formChooser);

				// Review Fields
				var ReviewFields = new $.Deferred();

				ReviewFields.done(function(reviewForm, options) {
					try {
						reviewForm.jreviewsFields(options);
					}
					catch(e) {}
				});

				ReviewFields.always(function() {
					jrPage.trigger('onListingFormLoaded');
				});

				// Listing Fields
				var ListingFields = new $.Deferred();

				ListingFields.done(function(options) {
					var loadingListingFields = formFields.jreviewsFields(options);
				});

				ListingFields.always(function() {

					jreviews.common.initForm(form.attr('id'),true);

					jreviews.common.validateUsername(form);

					jreviews.common.validateEmail(form);

					var reviewForm = form.find('fieldset.jr-form-review');

					if(reviewForm.length) {

						ReviewFields.resolve(reviewForm, {'fieldLocation':'Review','entry_id':0,'value':false,'page_setup':true,'referrer':'review'}, selected_cat_id, 'listingFormReview');
					} else {
						jrPage.trigger('onListingFormLoaded');
					}

					formLoadingDiv.remove();

					formFields.show();

					formFields.jrApplyFieldOptionLayout();
				});

				submittingCategory.done(function(res) {

					if(res.level !== undefined) {

						var catLists = formCategories.children("select");

						catLists.each(function(index) {if(index > res.level) { $(this).remove(); }});
					}

					if(res.select !== undefined) {

						formCategories.append(res.select);
					}

					switch(res.action) {

						case 'show_form':

							formFields.html(res.html);

							try {
								formFields.find('.jr-wysiwyg-editor').tinyMCE();
							}
							catch(e) {}

							ListingFields.resolve({'entry_id':listing_id,'value':false,'page_setup':true,'referrer':'listing',res:res});

							jreviews.facebook.init(form);

							jreviews.review.starRating(form);

							setTimeout(function() {

								jreviews.captcha.init(form);

							}, 200);

							var titleInput = form.find('.jr-listing-title');

							if(titleInput.length) {
								jreviews.search.liveSearch(titleInput, {'id': 'submitListing'});
							}

							break;

						case 'hide_form':

							formLoadingDiv.remove();

							formFields.hide();

							break;

						case 'no_access':

							formLoadingDiv.remove();

							formFields.html(jreviews.__t('LISTING_SUBMIT_DISALLOWED')).show();

						break;
					}

				});
			});
		},

		widgets: function() {

			var detailPage = jrPage.filter('.jr-listing-detail'),
				jrTabArray = [];

			if(!detailPage.length) return;

			detailPage.find('.jr-tabs').find('li>a').each(function(i,t) {

				var href = $(t).attr('href');

				var tabId = href.substring(href.indexOf("#")+1);

				jrTabArray['#'+tabId] = $(t).parent('li');
			});

			if(typeof jrRelatedWidgets !== 'undefined' && jrRelatedWidgets.length) {

				$(jrRelatedWidgets).each(function(k,params) {

					var controller;

					if(params.favorites) {
						controller = 'module_favorite_users';
					}
					else if(params.type == 'relatedreviews') {
						controller = 'module_reviews';
					}
					else {
						controller = 'module_listings';
					}

					var loadingWidget = jreviews.dispatch({method:'get',type:'html',controller:controller,action:'index',data:params});

					loadingWidget.done(function(html){

						var target_element = params.target_class !== '' ? params.target_class : params.target_id,
							targetElement = params.target_class !== '' ? $('.'+params.target_class) : $('#'+params.target_id);

						if(html.trim() !== '') {

							var widget = $('<div id="'+target_element+'Widget'+params.key+'"></div>').addClass('jrWidget');

							if(params.title) {
								widget.append('<h3 class="jrHeading">'+params.title+'</h3>');
							}

							widget.append(html);

							targetElement.append(widget);

							var array = [0,1,2,3,4,5,6,7,8,9];

							for(var i=0; i < array.length; i++) {
								array[i] = $('#'+target_element+'Widget'+ array[i]);
							}

							for(var i=0; i<array.length; i++) {
								if(array[i].length > 0) {
									targetElement.append(array[i]);
								}
							}

							if(jrTabArray['#'+target_element] !== undefined && targetElement.html() !== '') {
								jrTabArray['#'+target_element].show();
							}
						}
						else {

							if(jrTabArray['#'+target_element] !== undefined && targetElement.html() === '') {
								jrTabArray['#'+target_element].hide();
							}
						}

						jreviews.module.init();

						jreviews.media.generateThumbs();
					});

				});
			}
		}

	};

	jreviews.review = {

		setup: function(form_id, review_id) {

			var form = $('form#'+form_id),
				formDiv = form.closest('div.jr-form-review-outer');

			if (form.data('initialized') == undefined) {
				form.data('initialized', 1);

				jreviews.review.starRating(formDiv);

				jreviews.common.validateUsername(form);

				jreviews.common.validateEmail(form);

				jreviews.common.initForm(form_id,true);

				jreviews.facebook.init(form);

				var loadingReviewFields = form.jreviewsFields({
					'fieldLocation':'Review',
					'entry_id':review_id,
					'value':false,
					'page_setup':true,
					'referrer':'review'
				});

				if(loadingReviewFields) {

					loadingReviewFields.done(function() {

						if(review_id === 0) {
							formDiv.slideDown().delay().jrScrollTo({duration:500,offset:-50});
						}

						jrPage.trigger('onReviewFormLoaded');

						form.jrApplyFieldOptionLayout();
					});
				}
			}
		},

		// new review
		init: function() {

			var url = document.URL;

			// Add review button
			$('body').on('click', '.jr-page .jr-review-add', function(e) {
				e.preventDefault();

				var el = $(this),
					userReviewsContainer = $('#userReviews'),
					editorReviewsContainer = $('#editorReviews'),
					page,
					loginBox
					;

				if (userReviewsContainer.length) {
					page = userReviewsContainer.parents('.jr-page');
					loginBox = userReviewsContainer.find('.jr-login-box');
				} else if (editorReviewsContainer.length) {
					page = editorReviewsContainer.parents('.jr-page');
					loginBox = editorReviewsContainer.find('.jr-login-box');
				} else {
					return false;
				}

				var tabs = page.find('.jr-tabs'),
					formDiv = page.find('div.jr-form-review-outer'),
					form = formDiv.find('form[id^="jr-form-review"]'),
					form_id = form.attr('id'),
					review_id = form.data('review-id');

				if(tabs.length) {
					tabs.tabs('select', '#reviewsTab');
				}

				if (form.length && !loginBox.length) {

					jreviews.review.setup(form_id,review_id);

					// jreviews.review.starRating(formDiv);

					page.find('button.jr-review-add:not(".jr-listing-info")').fadeOut();

					formDiv.slideDown('fast').jrScrollTo({duration:500,offset:-50});

				} else {
					userReviewsContainer.jrScrollTo();
				}

				if (loginBox.length && !el.hasClass('jr-listing-info')) {
					loginBox.slideToggle(100);
				}
			});

			// Cancel review
			$('body').on('click','form[id^="jr-form-review"] .jr-review-cancel',function(e) {
				e.preventDefault();

				$(this).closest('.jr-form-review-outer').slideUp();

				$(this).closest('.jr-page').find('button.jr-review-add:not(".jr-listing-info")').fadeIn().delay().jrScrollTo({duration:500,offset:-50});
			});

			// Submit review
			$('body').on('click','form[id^="jr-form-review"] .jr-review-submit',function(e) {
				e.preventDefault();

				var el = $(this),
					reviewsDiv = $('.jr-user-reviews'),
					formDiv = el.closest('div.jr-form-review-outer'),
					form = formDiv.find('form[id^="jr-form-review"]'),
					buttons = form.find('.jr-buttons'),
					validation = form.find('div.jr-validation');

				// Set valid custom fields
				form.jrSetValidFields().join(',');

				// Actions run on buttton press
				buttons.find('button').attr('disabled','disabled');

				buttons.append('<span class="jrLoadingSmall">');

				validation.hide();

				var submittingReview = jreviews.dispatch({form:form});

				submittingReview.done(function(res) {

					if(res.success) {

						formDiv.slideUp().remove();

						if(res.facebook && res.moderation === false) {

							var data = {id:res.review_id};

							data[res.token] = 1;

							jreviews.facebook.addTokenAndPost('_postReview','html',data);
						}

						var success = '';

						// Unmoderated
						if(res.moderation === false) {

							success = '<div class="jr-message jrSuccess">' +
								(res.review_type == 'user' ? jreviews.__t('REVIEW_SUBMIT_NEW') : jreviews.__t('REVIEW_SUBMIT_NEW_REFRESH')) +
								'</div>';
						}

						reviewsDiv.prepend('<div class="jr-new-review jrHidden">' + success + res.html + '</div>')

							.jrScrollTo({duration:500,offset:-50}).delay()

							.find('.jr-new-review').slideDown(1500)

							.find('div.jr-message').delay(4000).slideUp(500);

						jreviews.review.edit();

						jreviews.review.vote();

						jreviews.popup();

					}
					else if(res.str.length) {
						validation.html(jreviews.__t(res.str)).show();
						jreviews.captcha.reset(form.find('.g-recaptcha').data('id'));
					}
				});

				submittingReview.always(function() {

					form.find('#jr-valid_fields').remove();

					buttons.find(".jrLoadingSmall").remove();

					buttons.find('button').removeAttr('disabled');

				});

			});

			// Delete Owner Reply
			$('body').on('click', '.jr-page .jr-owner-reply-del', function(e) {

				e.preventDefault();

				var el = $(this),
					review_id = el.data('review-id'),
					token = el.data('token'),
					text = el.find('span').eq(1);

				var deleteSubmit = new $.Deferred();

				deleteSubmit.done(function(dialog) {

					var data = {id:review_id};
					data[token] = 1;

					var deletingReply = jreviews.dispatch({method:'get',type:'json',controller:'owner_replies',action:'_delete','data':data});

					deletingReply.done(function(res) {

						if(res.success) {

							dialog.dialog('close');

							el.closest('.jr-owner-reply-outer').replaceWith('<div class="jrSuccess">'+jreviews.__t('OWNER_REPLY_DELETED')+'</div>');
						}
						else {

							dialog.dialog('option','buttons',[]);

							if(res.str.length) {

								dialog.html(jreviews.__t(res.str));
							}
						}
					});
				});

				var buttons = {};

				buttons[jreviews.__t('DELETE')] = function() { deleteSubmit.resolve($(this)); };

				buttons[jreviews.__t('CANCEL')] = function() { $(this).dialog('close'); };

				var dialog = $.jrDialog(jreviews.__t('OWNER_REPLY_DELETE_CONFIRM'), {buttons:buttons,title:text.html(),width:'640px'});
			});

			// Write review anchor triggers review form
			if (url.match("#reviewForm$") || url.match("#userReviews$")) {

				var tabs = jrPage.find('.jr-tabs');

				if(tabs.length) {
					$('.jr-tabs').tabs('select', '#reviewsTab' ).jrScrollTo({duration:500,offset:-100});
				}

				if(url.match("#reviewForm$")) {
					jrPage.find('.jr-review-add').first().trigger('click');
				}
			}
		},

		edit: function() {

			$('body').on('click', '.jr-page .jr-review-edit:not(".jr-ready")', function(e) {

				e.preventDefault();

				var el = $(this),
					text = el.find('span').eq(1),
					review_id = el.data('review-id'),
					referrer = el.data('referrer'),
					form_id = 'jr-form-review-' + review_id;

				// Detach new review form and reattach on save
				var formNew = $('#jr-form-review-0');

				var formNewPlaceholder = $('<div id="jr-form-review-placeholder" class="jrHidden"></div>');

				formNew.before(formNewPlaceholder);

				var formNewDettached = formNew.detach();

				var loadingForm = jreviews.dispatch({method:'get',type:'html',controller:'reviews',action:'_edit',data:{review_id:review_id}});

				loadingForm.done(function(res) {

					// Call dialog
					var buttons = {};

					buttons[jreviews.__t('SUBMIT')] = function() {

						jreviews.review.editSubmit(el, $(this), formNewPlaceholder, formNewDettached);
					};

					buttons[jreviews.__t('CANCEL')] = function() {

						formNewPlaceholder.after(formNewDettached);

						formNewPlaceholder.remove();

						$(this).dialog('close');
					};

					var dialog = $.jrDialog(res, {buttons:buttons,title:text.html(),width:'800px',height:600});

					dialog.find('form').append('<input name="data[referrer]" type="hidden" value='+referrer+'>');

					jreviews.review.setup(form_id,review_id);

					jreviews.common.userAutocomplete(dialog);

				});

			}).addClass('jr-ready');
		},

		editSubmit: function(el, dialog, formNewPlaceholder, formNewDettached) {

			var layoutInner = el.closest('.jr-layout-inner'),
				form = dialog.find('form'),
				form_id = form.attr('id'),
				buttonPane = $('.ui-dialog-buttonpane'),
				validation = form.find('.jr-validation').clone();

				buttonPane.find('.jr-validation').fadeOut().remove();

				// Set valid custom fields
				form.jrSetValidFields().join(',');

				var submittingForm = jreviews.dispatch({form:form});

				submittingForm.done(function(res) {

					if (res.success) {

						formNewPlaceholder.after(formNewDettached);

						formNewPlaceholder.remove();

						dialog.dialog('close');

						var success = '';

						layoutInner

							.hide(0).html(function() {

								if(res.moderation === false) {

									success = '<div class="jr-message jrSuccess">' +
									(res.review_type == 'user' ? jreviews.__t('REVIEW_SUBMIT_EDIT') : jreviews.__t('REVIEW_SUBMIT_EDIT_REFRESH')) +
									'</div>';
								}

								return success + res.html;

							})

							.jrScrollTo({duration:500,offset:-50}).delay().slideDown(1500)

							.find('div.jr-message').delay(4000).slideUp(500);
					}
					else {

						if(res.str.length) {

							validation.html(jreviews.__t(res.str));

							buttonPane.prepend(validation);

							validation.fadeIn();
						}

					}

					jreviews.review.edit();

					jreviews.review.vote();

					jreviews.popup();
				});

				submittingForm.always(function() {

					form.find('#jr-valid_fields').remove();

				});

		},

		starRating: function(form) {

			form.find('.jr-rating-stars').each(function() {

				var el = $(this),
					selector = el.data('selector'),
					inc = el.data('inc'),
					$input = el.find('select'),
					$caption = el.closest('.jrRow').find('.jrRatingCaption');

				var styleClass = $input.data('style') || 1;
				var colorClass = $input.data('color') || 'orange';

				if (selector == 'stars') {
					var splitStars = 1/inc; // 2 for half star ratings
					el.stars({
						starColor: colorClass,
						starStyle: styleClass,
						split: splitStars,
						captionEl: el.parent().next()
					});
				}
				else if (selector == 'slider') {
					var $slider, $sliderHandle, $cancel, optionalRating;

					optionalRating = $input.find('[value="na"]').length;

					if (optionalRating) {
						$cancel = $('<div class="ui-stars-cancel"><a>na</a></div>');
					} else {
						$cancel = $('<div class="ui-stars-cancel-placeholder"></div>');
					}

					// Prevent Mootools conflict
					$.ui.slider.prototype.widgetEventPrefix = 'slider';
					$slider = $('<div class="jrSliderInput">')
								.slider({
								      min: 1,
								      max: $input.children('option').last().val(),
								      step: inc,
								      value: $input.val() !== 'na' ? $input.val() : 0,
								      start: function(event, ui) {
										$sliderHandle.addClass(colorClass).toggleClass('ui-slider-on',true);
								      },
								      slide: function(event, ui ) {
										$input.val(ui.value);
										setRatingValue();
								      }
								    });

					$sliderHandle = $slider.find('.ui-slider-handle')
										.on('click mousedown',function() {
											$slider.slider('enable');
											$caption.html($input.find('option:selected').text());
											$cancel.removeClass('ui-stars-star-on');
										});

					$cancel.on('click', function(e) {
						e.preventDefault();
						setNoRatingValue();
					});

					if ($input.val() !== 'na') {
						setRatingValue();
					} else {
						setNoRatingValue();
					}

					el.hide().after($cancel,$slider);

					function setRatingValue() {
						$caption.html($input.find('option:selected').text());
						if ($input.val() > 0) {
							$slider.find('.ui-slider-handle').toggleClass('ui-slider-on',true);
						}
						$cancel.removeClass('ui-stars-star-on');
					}

					function setNoRatingValue() {
						$cancel.addClass('ui-stars-star-on')
						$input.val('na');
						$sliderHandle.css('left','0%').removeClass('ui-slider-on');
						$caption.html($input.find('[value="na"]').text());
					}
				}
			});

		},

		reply: function() {

			$('body').on('click', '.jr-page .jr-owner-reply:not(".jr-ready")', function(e) {

				var el = $(this),
					text = el.find('span').eq(1),
					review_id = el.data('review-id');

				var loadingForm = jreviews.dispatch({method:'get',type:'html',controller:'owner_replies',action:'create',data:{review_id:review_id}});

				loadingForm.done(function(res) {

					// Call dialog
					var buttons = {};

					buttons[jreviews.__t('SUBMIT')] = function() { jreviews.review.replySubmit(el, $(this));};

					buttons[jreviews.__t('CANCEL')] = function() { $(this).dialog('close'); };

					$.jrDialog(res, {buttons:buttons,title:text.html(),width:'640'});

				});
			});
		},

		replySubmit: function(el, dialog) {

			var form = dialog.find('form'),
				validation = form.find('.jr-validation');

			var submittingForm = jreviews.dispatch({form:form});

			validation.hide();

			submittingForm.done(function(res) {

				if(res.success) {

					dialog.html(jreviews.__t(res.str));

					dialog.dialog('option','buttons',[]);

					el.attr('disabled','disabled');
				}
				else {

					dialog.trigger('failedValidation');

					validation.html(jreviews.__t(res.str)).show();
				}
			});

		},

		vote: function() {

			$('body').on('click','div.jr-review-vote:not(".jr-ready") button',function(e) {

				e.preventDefault();

				var el = $(this),
					vote = el.data('vote'),
					div = el.closest('.jr-review-vote'),
					state = div.data('state'),
					review_id = div.data('review-id'),
					icon = el.find('span').eq(0),
					text = el.find('span').eq(1);

				div.unbind('click').find('button').attr('disabled','distabled');

				if(state != 'access') {

					$.jrAlert(jreviews.__t(state == 'register' ? 'REVIEW_VOTE_REGISTER' : 'ACCESS_DENIED'));
				}
				else {

					var data = {'data[Vote][review_id]':review_id,'data[type]':vote};

					var submittingAction = jreviews.dispatch({method:'get',type:'json',controller:'votes',action:'_save',data:data});

					submittingAction.done(function(res) {

						if(res.success) {

							text.html(parseInt(text.html(),10) + 1);

							if(res.facebook && vote == 'yes') {

								var data = {id:review_id};

								data[res.token] = 1;

								// Define the FB posting request

								var postToFB = $.Deferred();

								postToFB.done(function() {

									var callback = function(res) {

										try {
											if(typeof res  == 'object') {
												FB.ui(res);
											}

										}
										catch(err) {
											console.log(err);
										}
									};

									jreviews.facebook.addTokenAndPost('_postVote','json',data, callback);
								});

								// Check if facebook integration is enabled for votes. The FB login status and publish permission

								FB.getLoginStatus(function(response) {

									if(response.status === 'connected') {

										FB.api(
											"/me/permissions",
											function (response) {

												var publish_actions = false;

												if (response && !response.error) {

													$.each(response.data,function(i,data) {
															if(data.permission == 'publish_actions' && data.status == 'granted') {
															publish_actions = true;
														}
													});

													if(!publish_actions) {

														// re-request publish_actions permission
														FB.login(function(response) {

															if (response.authResponse) {

																// Run FB vote post request
																postToFB.resolve();
															}

														},{scope:'publish_actions'});

													}
													else {

														// Run FB vote post request
														postToFB.resolve();
													}
												}
											}
										);
									}
									else {

										// re-request publish_actions permission
										FB.login(function(response) {

											if (response.authResponse) {

												// Run FB vote post request
												postToFB.resolve();
											}

										},{scope:'publish_actions'});

									}

								});

							}
						}
						else if(res.str.length) {

							$.jrAlert(jreviews.__t(res.str));
						}
					});

				}

			}).addClass('jr-ready');
		}
	};

	jreviews.report = {

		init: function() {

			$('body')
				.off('click','.jr-page button.jr-report')
				.on('click','.jr-page button.jr-report',function(e) {

					e.preventDefault();

					var el = $(this);

					var data = {
						listing_id: el.data('listing-id'),
						review_id: el.data('review-id'),
						post_id: el.data('post-id'),
						m: el.data('media-id'),
						extension: el.data('extension')
					};

					var loadingForm = jreviews.dispatch({method:'get',type:'html',controller:'reports',action:'create',data:data});

					loadingForm.done(function(html) {

						// Call dialog
						var buttons = {};

						buttons[jreviews.__t('SUBMIT')] = function() { jreviews.report.submit($(this)); };

						buttons[jreviews.__t('CANCEL')] = function() { $(this).dialog('close'); };

						$.jrDialog(html, {buttons:buttons,title:jreviews.__t('REPORT_INAPPROPRIATE'),width:'640'});

					});

			});

		},

		submit: function(dialog) {

			var form = dialog.find('form'),
				validation = form.find('.jr-validation');

			validation.html('').hide();

			var submittingForm = jreviews.dispatch({form:form});

			submittingForm.done(function(res)
			{
				if(res.success) {

					dialog.dialog('option','buttons',[]);

					form.html(jreviews.__t('REPORT_SUBMIT'));
				}
				else {

					dialog.trigger('failedValidation');

					validation.html(jreviews.__t(res.str)).show();
				}
			});
		}

	};

	jreviews.facebook = {

		buttons_class: 'jr-buttons',

		enable: false,

		permissions: false,

		uid: null,

		load: function() {

			var appid = jreviews.fb.appid || null;

			var FBbuttons = jrPage.find('.jr-fb-like').length || jrPage.find('.jr-fb-share').length;

			if(jreviews.fb.xfbml || jreviews.fb.post || FBbuttons) {

				if(!$('#fb-root').length) {

					$('body').append('<div class="jrHidden" id="fb-root"></div>');

					window.fbAsyncInit = function() {

						FB.init({
							appId: appid,
							status: true,
							cookie: true,
							xfbml: FBbuttons ? true : false,
							// oauth : true,
							version: 'v2.3'
						});
					};
				}
				else if(FBbuttons && typeof FB == 'object') {

					FB.XFBML.parse(jrPage.get(0));
				}
				else if(FBbuttons && typeof window.fbAsyncInit !== 'function') {

					window.fbAsyncInit = function() {

						FB.XFBML.parse(jrPage.get(0));
					};
				}

				(function(d, s, id) {
					var js, fjs = d.getElementsByTagName(s)[0];
					if (d.getElementById(id)) return;
					js = d.createElement(s); js.id = id; js.async = true;
					js.src = "//connect.facebook.net/"+jreviews.locale+"/sdk.js";
					fjs.parentNode.insertBefore(js, fjs);
				}(document, 'script', 'facebook-jssdk'));
			}
		},

		init: function(form) {

			if(jreviews.fb.post) {

				jreviews.facebook.form = form;

				jreviews.facebook.checkPermissions();
			}
		},

		login: function() {

			if(jreviews.facebook.uid === null || !jreviews.facebook.permissions) {

				FB.login(function(response) {

					if (response.authResponse) {

						// user is logged in and granted some permissions.
						jreviews.facebook.uid = response.authResponse.userID;

						if(!jreviews.facebook.permissions) {

							FB.api("/me/permissions",function (response) {

								if (response && !response.error) {

									$.each(response.data,function(i,data) {
										if(data.permission == 'publish_actions' && data.status == 'granted') {
											jreviews.facebook.permissions = true;
										}
									});
								}

								if(!jreviews.facebook.permissions) {
									$('#jr-fb-publish').prop('checked',false);
								}
							});

						}
					}
					else {
						$('#jr-fb-publish').prop('checked',false);
					}

				}, {scope:'publish_actions'});
			}
		},

		checkPermissions: function(options) {

			var params = {};

			params = $.extend(params,options);

			jreviews.facebook.FBInit = setInterval(function(){

				if(typeof FB == 'object')
				{
					FB.getLoginStatus(function(response) {

						if(response.status === 'connected') {

							// logged in and connected user
							jreviews.facebook.uid = response.authResponse.userID;

							FB.api(
								"/me/permissions",
								function (response) {

									var publish_actions = false;

									if (response && !response.error) {

										$.each(response.data,function(i,data) {
												if(data.permission == 'publish_actions' && data.status == 'granted') {
												publish_actions = true;
											}
										});

										if(!publish_actions) {
											jreviews.facebook.addButton(false);
										}
										else {
											jreviews.facebook.addButton(true);
										}
									}
								}
							);
						}
						else {

							// User not logged in or has not granted publish_actions permission
							jreviews.facebook.addButton(false);
						}
					});
				}

			},450);
		},

		addButton: function(permission) {

			clearInterval(jreviews.facebook.FBInit);

			jreviews.facebook.permissions = permission;

			if(jreviews.facebook.form === undefined) return false;

			var buttons = jreviews.facebook.form.find('.' + jreviews.facebook.buttons_class);

			if(permission === true && !jreviews.fb.optout) {

				buttons.prepend('<input id="jr-fb-publish" name="data[fb_publish]" value="1" type="hidden" />');
			}
			else {

				var FBPublish = $(
						'<div class="jrFacebookPublish">' +
							'<input id="jr-fb-publish" name="data[fb_publish]" value="1" type="checkbox">' +
							'<div class="jrButton jrBlue"><span class="fb_button_text"><label for="jr-fb-publish">'+jreviews.__t('FACEBOOK_PUBLISH')+'</label></span></div>' +
						'</div>'
					)
					.on('click','input',function() {

						if(this.checked)
						{
							jreviews.facebook.login();
						}
					});

				if(buttons.find('#jr-fb-publish').length === 0) {

					buttons.prepend(FBPublish);
				}

				if(permission && jreviews.fb.optout) {

					FBPublish.find('input').attr("checked","checked");
				}
			}
		},

		addTokenAndPost: function(action, type, data, callback) {

			FB.getLoginStatus(function(response) {

				if(response.status == 'connected') {

					// Need to send a current FB accessToken to the server
					data['accessToken'] = response.authResponse.accessToken;

					var posting = jreviews.dispatch({method:'get',type:type,controller:'facebook',action:action,data:data});

					posting.done(function(res) {

						if(typeof callback == 'function') {

							callback(res);
						}
					});
				}
			});
		}
	};

	jreviews.captcha = {

		init: function(form) {

			if(form.find('.jr-captcha').length && typeof grecaptcha === 'object') {

				var captcha = form.find('.g-recaptcha');

				var opt_widget_id = grecaptcha.render(captcha[0], {
					'sitekey': captcha.data('sitekey'),
					'theme': captcha.data('theme'),
					'expired-callback': jreviews.captcha.reset
				});

				captcha.data('id',opt_widget_id);
			}
		},

		reset: function(id) {

			if(id !== undefined && typeof grecaptcha === 'object') {

				grecaptcha.reset(id);
			}
		}
	};

	jreviews.common =
	{
		initForm: function(form_id,trigger) {

			var form = typeof form_id == 'string' ? $('#'+form_id) : form_id,
				captchaDiv = form.find('div.jr-captcha');

			form.on('click','.jr-user-forget', function(e) {

				var el = $(this);

				e.preventDefault();

				var clearingUserInfo = jreviews.dispatch({cache: false, method:'get',controller:'users',action:'_clearUserInfo'});

				clearingUserInfo.done(function() {

					form.find('.jr-user-name, .jr-user-username, .jr-user-email').val('').prop('disabled', false);

					el.closest('span').remove();
				});

			});

			form.one('mouseover',function() {

				var initiliazingForm = jreviews.dispatch({cache: false, method:'get',type:'json',controller:'common',action:'_initForm',data:{'data[form_id]':form_id,'data[captcha]':captchaDiv.length}});

				initiliazingForm.done(function(res) {

					form.find('span.jr_token :input').attr('name',res.token);

					if(res.captcha) {

						captchaDiv.html(res.captcha);

						// Added delay to ensure the captcha loads on the review comments form which is opened by default

						setTimeout(function() { jreviews.captcha.init(form); }, 750);
					}

					form.find('input.jr-user-name').val(res.name);

					var usernameInput = form.find('input.jr-user-username');

					usernameInput
						.val(res.username)
						.on('focus mouseover',function() {
							$(this).closest('div').find('.jr-more-info').trigger('mouseover');
						})
						.on('blur mouseout',function() {
							$(this).closest('div').find('.jr-more-info').trigger('mouseout');
						});

					if(usernameInput.data != undefined && usernameInput.data('replace-placeholder') == 1)
					{
						usernameInput.attr('placeholder',jreviews.__t('USERNAME_CREATE_ACCOUNT'));
					}

					form.find('input.jr-user-email').val(res.email);

					form.find('input.jr-user-name, input.jr-user-username, input.jr-user-email')
						.each(function() {

							var value = $(this).val();

							if(value !== '') {

								$(this).prop('disabled',true);
							}
							else {
								$(this).prop('disabled',false);
							}
						});

				});
			});

			if(trigger === true) form.trigger('mouseover');

		},

		validateUsername: function(form) {

			jreviews.common.validateUserData(form, 'username');
		},

		validateEmail: function(form) {

			jreviews.common.validateUserData(form, 'email');
		},

		validateUserData: function(form, dataType) {

			var sourceInput,
				usernameInput,
				validationHtml,
				action;

			switch(dataType) {

				case 'username':

					emailInput = 'input.jr-email-check';

					sourceInput = 'input.jr-user-username';

					validationHtml = $('<input class="jr-valid-username" type="hidden" name="data[valid_username]" value="0" />');

					action = '_validateUsername';

					break;

				case 'email':

					usernameInput = 'input.jr-user-username';

					sourceInput = 'input.jr-email-check';

					validationHtml = $('<input class="jr-valid-email" type="hidden" name="data[valid_email]" value="0" />');

					action = '_validateEmail';

					break;
			}

			// Store the starting value to skip the check if it's already filled in

			var input = form.find(sourceInput);

			var passEl = $('<span class="jrSuccess" style="padding:3px 8px;"></span>'),
				failEl = $('<span class="jrError" style="padding:3px 8px;"></span>');

			if(input.val() !== '') {
				input.data('default', input.val());
			}

			form.off('focusout', sourceInput)
				.on('focusout', sourceInput, function() {

					var el = $(this),
						data = {value: el.val()},
						spinner = $('<span class="jrLoadingSmall"></span>');

					validationHtml.remove();
					passEl.remove();
					failEl.remove();

					el.next('span').remove();

					// If the username input is present on the form, then only run the email validation if the username is filled in

					if(dataType == 'email' && form.find(usernameInput).length && form.find(usernameInput).val() == '') return;

					if(dataType == 'username' && form.find(emailInput).length && form.find(emailInput).val() !== '') {
						form.find(emailInput).trigger('focusout');
					}

					if(el.val() === '') return;

					if(el.data('default') !== '' && el.val() !== el.data('default')) {

						el.after(spinner);

						jreviews.dispatch({method:'get',type:'json',controller:'users',action:action,data:data}).done(function(res) {

							spinner.remove();

							if(res.success) {

								el.after(passEl.html(res.text));
								form.append(validationHtml.val(1));
							}
							else {

								el.after(failEl.html(res.text));
								form.append(validationHtml.val(0));
							}
						});
					}
			});
		},

		userAutocomplete: function(form) {

			var el = form.find('input.jr-user-autocomplete');

			var settings = {
				'target_userid' : 'jr-user-id-ac',
				'target_name'   : 'jr-user-name-ac',
				'target_username' : 'jr-user-username-ac',
				'target_email' : 'jr-user-email-ac'
			};

			if(el.val() === '') {

				var userid = form.find('.'+settings.target_userid).val();

				if(Number(userid) > 0) {

					var loadingAction = jreviews.dispatch({method:'get',type:'text',controller:'users',action:'_getUsername',data:{id:userid}});

					loadingAction.done(function(username) {

						el.val(username);
					});
				}
			}

			el.autocomplete({

				source: function( request, response ) {
					var cache = el.data('cache') || {};
					var term = request.term;
					if ( term in cache ) {
						response( cache[ term ] );
						return;
					}

					var searching = jreviews.dispatch({method:'get',type:'json',controller:'users',action:'_getList',data: {'q': term}});

					searching.done(function(res) {

						cache[ term ] = res;
						el.data('cache',cache);
						response(res);

					});
				},

				select: function( event, ui) {

					form.find('.'+settings.target_userid).val(ui.item.id);
					form.find('.'+settings.target_email).val(ui.item.email);
					form.find('.'+settings.target_name).val(ui.item.name);
					form.find('.'+settings.target_username).val(ui.item.username);
				},
				minLength: 2

			});

			$('.ui-autocomplete').addClass('jrAutoComplete');
		},

		listingAutocomplete: function(form, options) {

			var el = options.acfield || form.find('input.jr-listing-autocomplete');

			var settings = {
				'target_listingid'  : 'jr-listing-id-ac',
				'target_title'      : 'jr-listing-title-ac',
				'target_alias'      : 'jr-listing-alias-ac',
				'target_url'        : 'jr-listing-url-ac',
				'target_cat'		: 'jr-listing-cat-ac',
				'target_type'		: 'jr-listing-type-ac',
        		'target_dir'       : 'jr-listing-dir-ac'
			};

			var id = form.find('.'+settings.target_listingid).val(),
				dir = form.find('.'+settings.target_dir).val(),
				cat = form.find('.'+settings.target_cat).val(),
				type = form.find('.'+settings.target_type).val()
				;

			if(el.val() === '') {

				if(Number(id) > 0) {

					var loadingAction = jreviews.dispatch({frontend:true,method:'get',type:'json',controller:'listings',action:'_getList',data:{id:id}});

					loadingAction.done(function(res) {

						if(options.onSelect) {

							options.onSelect(el, res[0]);
						}
						else {

							el.val(res[0].value);
						}
					});
				}
			}

			el
				.on('blur',function() {

					if(el.val() === '') {
						form.find('.' + settings.target_listingid +
							', .' + settings.target_title +
							', .' + settings.target_alias +
							', .' + settings.target_url).val('');
					}
				})
				.autocomplete({

					source: function( request, response ) {
						var cache = el.data('cache') || {};
						var term = request.term;
						if ( term in cache ) {
							response( cache[ term ] );
							return;
						}

						var searching = jreviews.dispatch({frontend:true,method:'get',type:'json',controller:'listings',action:'_getList',data: {search: term, dir: dir, cat: cat, type: type}});

						searching.done(function(res) {

							cache[ term ] = res;
							el.data('cache',cache);
							response(res);

						});
					},

					select: function(event, ui) {

						form.find('.'+settings.target_listingid).val(ui.item.id);
						form.find('.'+settings.target_title).val(ui.item.value);
						form.find('.'+settings.target_alias).val(ui.item.alias);
						form.find('.'+settings.target_url).val(ui.item.url);

						if(options.onSelect) {

							options.onSelect(el, ui);
						}
					},
					minLength: 2

				});

			$('.ui-autocomplete').addClass('jrAutoComplete');
		}
	};

	jreviews.search = {

		submit_delay: 150,
		submit_delay_enter: 600, // When the enter key is pressed. Need more time to process jrBeforeSearch event
		liveSearch_timer: null,
		liveSearch_timeout: 200,
		pageUpdateRequest: null,

		simple: function() {

			$('form.jr-simple-search').on('click','button.jr-search',function() {

				$(this).closest('form').attr('action',s2AjaxUri).submit();
			});
		},

		advancedPage: function() {

			var form = $('#jr-form-adv-search'),
				formChooser = form.find('.jr-listing-type-outer');

			var formLoadingDiv = $('<div class="jrRoundedPanel" style="text-align:center;"><span class="jrLoadingMedium" style="display:inline;padding:20px;"></span>'+jreviews.__t('LOADING')+'</div>');

			form
				.attr('action',s2AjaxUri)
				.on('click','button.jr-search',function(e) {

					e.preventDefault();

					$(this).trigger('jrBeforeSearch');

					setTimeout(function() { form.submit(); }, jreviews.search.submit_delay);
				});

			jreviews.search.searchRange(form);

			form.on('change','select.jr-listing-type,input.jr-listing-type',function(e) {

				e.preventDefault();

				var el = $(this),
					form = el.closest('form'),
					criteria_id = el.val(),
					searchFields = form.find('div.jr-search-fields');

				if(parseInt(criteria_id,10) === 0) {

					searchFields.fadeOut();

					return false;
				}

				formLoadingDiv.insertAfter(formChooser);

				submittingAction = jreviews.dispatch({method:'get',type:'html',controller:'search',action:'_loadForm',data:{criteria_id:criteria_id}});

				submittingAction.done(function(html) {

					searchFields.html(html);

					var loadingSearchFields = searchFields.jreviewsFields({
						'page_setup':true,
						'referrer':'adv_search'
					});

					if(loadingSearchFields) {

						loadingSearchFields.done(function() {

							formLoadingDiv.remove();

							form.trigger('jrSearchFormLoaded');

							searchFields.show();

							searchFields.jrApplyFieldOptionLayout();
						});
					}
					else {
						formLoadingDiv.remove();
					}

				});

			});

			var criteria_id = form.find('input.jr-listing-type').val();

			if(criteria_id > 0) {

				form.find('input.jr-listing-type').trigger('change');
			}
		},

		requestResults: function(form, resultsDiv, showLoading) {

			clearTimeout(jreviews.search.liveSearch_timer);

			if($.jrGetSelected() !== '') { return false; }

			jreviews.search.liveSearch_timer = setTimeout(function() {

				if(showLoading) {
					resultsDiv.html('<div class="jrLoadingMedium"></div>').show();
				}

				// cancel existing requests
				if(jreviews.search.loadingResults !== undefined) jreviews.search.loadingResults.abort();

				if(form.is('input') && form.val() == '') {
					resultsDiv.hide();
				}
				else {
					// exclude controller/action inputs from data. Otherwise it gets duplicated in the request
					var data = form.is('input') ?
								[{"name": "data[keywords]", "value": form.val()}, {"name": "data[referrer]", "value": "submitListing"}]
								:
								form.find(':not([name="data[controller]"],[name="data[action]"])').serializeArray();

					jreviews.search.loadingResults = jreviews.dispatch({method:'get',controller:'categories',action:'liveSearch', data: data});

					jreviews.search.loadingResults.done(function(html) {
						if(html != '') {
							resultsDiv.html(html).show();
						}
						else {
							resultsDiv.hide();
						}
					});
				}

			},jreviews.search.liveSearch_timeout);
		},

		advancedModule: function() {

			if (typeof $().jrFiltersPanel == 'function' && $('.jr-list-show-filters').length > 0) {
				$('.jr-filters-module').jrFiltersPanel();
			}

			$('form.jr-form-adv-search-module').each(function() {

				var form = $(this),
					module_id = form.data('module-id'),
					formData,
					loadingResults;

					var advFilteringModule = form.data('filters') == 1;

					jreviews.search.searchRange(form);

					form
						.attr('action',s2AjaxUri)
						.on('click','button.jr-search',function(e) {
							e.preventDefault();
							$(this).trigger('jrBeforeSearch');
							setTimeout(function() { form.submit(); }, jreviews.search.submit_delay);
						})
						.find('input[type=text]').on('keydown',function (e) {
							if (e.keyCode == 13) {
								e.preventDefault();
								if (form.data('live-search') !== 1) {
									$(this).trigger('jrBeforeSearch');
									// Add geomaps proximity search check to use a longer delay if the input is the proximity field
									var delay = $(this).data('jrGeolocation') === true ? jreviews.search.submit_delay_enter : jreviews.search.submit_delay;
									setTimeout(function() { form.submit(); }, delay);
								}
							}
						});

					try {
						var loadingSearchFields = form.jreviewsFields({
							'page_setup':true,
							'recallValues':false,
							'context': {
								dir: form.find('[name="data[dir]"]').val() || '',
								cat: form.find('[name="data[categories]"]').val() || '',
								listing_type: form.find('[name="data[Search][criteria_id"]').val() || ''
							},
							'referrer': advFilteringModule ? 'filtering' : 'adv_search_module',
							/* Disable automcomplete for the filtering module */
							'autocomplete': advFilteringModule == 1 ? 0 : 1
						});

						loadingSearchFields.done(function() {
							if (!advFilteringModule) {
								form.jrApplyFieldOptionLayout();
							}
						});

					} catch(e) {
						console.log('JReviews fields.js not loaded');
					}

					if (advFilteringModule) {

						// Transform standard fields. Custom fields are transformed after the field data request finishes
						form.find('[data-display-as]').not('[name^="data\[Field]"\]').jrFilterTransformer();

						form.find('.jr-filters-reset').on('click' , function(e) {
							e.preventDefault();
							form.find('[data-display-as]:not([name="data\[categories\]"],[name="data\[Search\]\[criteria_id\]"])').jrFilterTransformer('reset');
							form.trigger('onAdvancedFiltering');
						});

						// Set the initial state so it can be retrieved when going back in history to this page
						var firstPushState = true;
						var pushStateData = [];

						form.on('jrListingFieldsLoaded', function() {
							form.find(':input:not([type="hidden"])').each(function() {
								if ($(this).attr('name') !== undefined) {
									pushStateData.push({name: $(this).attr('name'), value: form.find('[name="'+$(this).attr('name')+'"]').val()});
								}
							});
						});

						form.on('onAdvancedFiltering', function(e, $input) {

							var reloadPage = $input !== undefined && $input.data('reload') == 1 ? true : false;
							var liveSearch = form.data('live-search') == 1 && !reloadPage && $('.jr-page.jrListings').length;

							// Necessary to set the base state before the first search request is performed
							if (firstPushState) {
								firstPushState = false;
								window.history.replaceState({url: document.location.href, form: {id: form.data('moduleId'), data: pushStateData}}, null, document.location.href);
							}

							if (liveSearch) {
								form.find('[name="data\[order\]"]').val(jreviews.search.getSortValue());
								jrMain.addClass('jrIsLoading');
								$(this).trigger('jrBeforeSearch');
								setTimeout(function() {
									// Needs to be inside the timeout function. Otherwise aborting previous requests doesn't always work
									if (jreviews.search.pageUpdateRequest) {
										jreviews.search.pageUpdateRequest.abort();
									}

									formData = form.find(':not([name="data[controller]"],[name="data[action]"])').serializeArray();

									jreviews.search.pageUpdateRequest = jreviews.dispatch({method:'get',controller:'categories',action:'liveFilter', data: formData});

									jreviews.search.pageUpdateRequest.done(function(html) {
										var pushStateData = [];
										form.find(':input:not([type="hidden"])').each(function() {
											if ($(this).attr('name') !== undefined) {
												pushStateData.push({name: $(this).attr('name'), value: form.find('[name="'+$(this).attr('name')+'"]').val()});
											}
										});
										jreviews.pageNav.updatePage(html, {push: false, scroll: form.data('update-scroll') || 0, form: {id: form.data('moduleId'), data: pushStateData}});
									});

								}, jreviews.search.submit_delay);
							}
							else {
								$(this).trigger('jrBeforeSearch');
								setTimeout(function() { form.submit(); }, jreviews.search.submit_delay);
							}
						});

						form.find('select,input[type="text"],input[type="number"]').on('change',function(e){
							var $input = $(e.target);
							if ($input.data('isControlled') === true && $input.data('isActive') === false) {
								// Do nothing - prevents dependent field update requests from being triggered, when the dependent field is cleared indirectly
								// by deselecting a controlling field option
							} else {
								form.trigger('onAdvancedFiltering', [$input]);
							}
						});

					} else {
						jreviews.search.liveSearch(form, {'id': module_id});
					}

			});
		},

		getSortValue: function() {
			// Update order input based on current URL order
			var url = window.location.search.substring(1);
			var params = url.split('&');
			var sortValue;
			for (var i = 0; i < params.length; i++) {
				var paramName = params[i].split('=');
				if (paramName[0] == 'order') {
				    return paramName[1];
				}
			}
		},

		liveSearch: function(form, options) {

			var module_id = options.id || '';
				inputs = form.is('input') ? form : form.find('input[type=text]'),
				SelectsRadiosCheckboxes = form.find('select,input[type=radio],input[type=checkbox]'),
				beforeDiv = null;

			var settings = {
				'enabled': 1,
				'auto_hide': 1,
				'popup': 1,
				'display_event': 'keyup',
				'show_loading': true
			};

			$.extend(settings, form.data('searchSettings') || {});

			var $liveSearchContainer = $('.jrAdvSearchSuggest.jr-live-search' + module_id).length ? $('.jrAdvSearchSuggest.jr-live-search' + module_id) : $('<div class="jrAdvSearchSuggest jr-live-search' + module_id + '"></div>');

			form.on('onLiveSearch',function(){

				var suggest_results = form.data('live-search') !== undefined ? form.data('live-search') : settings.enabled,
					auto_hide = form.data('live-search-hide') !== undefined ? form.data('live-search-hide') : settings.auto_hide;

				if(!suggest_results) return false;

				var $advSearchForm = $(this),
					$advSearchContainer = $advSearchForm.parent();

				if(settings.popup) {
					$liveSearchContainer.appendTo('body');
				}
				else {
					$liveSearchContainer.insertAfter(form);
				}

				// Set live search container position
				function resizeLiveSearch(){

					var liveSearchPosition = $advSearchContainer.offset(),
					liveSearchWidth = $advSearchContainer.outerWidth();

					liveSearchPosition.top += $advSearchContainer.outerHeight() + 1;
					$liveSearchContainer.css(liveSearchPosition);
					$liveSearchContainer.css('width', liveSearchWidth);

				}

				if(settings.popup) {
					resizeLiveSearch();
					window.onresize = resizeLiveSearch;
				}

				jreviews.search.requestResults($advSearchForm, $liveSearchContainer, settings.show_loading);

				// Hide results when visitors click outside the adv. search module
				if (auto_hide) {
					$(document).on('mousedown',function (e)
					{
						if ((!$advSearchContainer.is(e.target) && $advSearchContainer.has(e.target).length === 0)
							&&
						   (!$liveSearchContainer.is(e.target) && $liveSearchContainer.has(e.target).length === 0)) {

							$liveSearchContainer.hide();
						}
					});
				}

				// Hide results when visitors click the close button
				$liveSearchContainer.on('click','.jr-close-live-results',function (e)
				{
					$liveSearchContainer.hide();
				});
			});

			if(form.is('input')) {
				form.on('focusin', function() {
					$liveSearchContainer.hide();
				});
			}

			inputs.on(settings.display_event, function(){
				form.trigger('onLiveSearch');
			});

			// The datepicker needs it's own trigger

			form.on('change', '.jr-date', function(){
				form.trigger('onLiveSearch');
			});

			SelectsRadiosCheckboxes.on('change', function(e){
				form.trigger('onLiveSearch');
			});
		},

		searchRange: function(form) {

			form.on('change','select.jr-search-range',function() {

				var el = $(this),
					highRangeOuter = el.parent().find('span').last();

				if(el.val() == 'between') {

					highRangeOuter.show();

				} else {

					highRangeOuter.hide().find('input').val('');
				}
			});
		}
	};

	jreviews.popup = function() {

		$('.jr-more-info').not('jr-ready').jrPopup({
			className: 'jrPopup',
			delay: 150
		}).addClass('jr-ready');

	};

	jreviews.showmore = function() {

		$('.jr-show-more').not('jr-ready').jrShowMore().addClass('jr-ready');

	};

	jreviews.trix = function() {
		/**
		 * Disable file uploads through drag and drop
		 */
		document.addEventListener('trix-file-accept', function(e) {
			e.preventDefault();
		});
	}

	jreviews.formbuilder = function() {

		jrPage.on('onListingFormLoaded onReviewFormLoaded', function() {
			try {
				console.log('Bootstrapping FormBuilder Field');
	            jrPage.find('[jr-formbuilder]').each(function() {
	                new jreviewsFormBuilder($(this)).initialize();
	            });
			}
			catch(e) {
				console.log('FormBuilder bootstrapping failed.');
			}
		});
	}

	jreviews.calendar = {

		init: function() {

			$('.jr-module-calendar').not(".jr-ready").addClass('jr-ready').each(function(){

				var el = $(this),
					calendarContainer = el.find('.jr-module-calendar-container'),
					o = el.data('options');

				var data = {
					start_field: o.start_field,
					end_field: o.end_field,
					start_date: o.start_date,
					end_date: o.end_date,
					hide_past: o.hide_past,
				}

				// Set Locale
				moment.locale(jreviews.locale);

	            var getEvents = jreviews.dispatch({method:'get',type:'json',controller:'module_calendar',action:'_getEvents',data:data});

	            getEvents.done(function(res) {

	                if(res.success && res.results.length) {

						var events = [];

						$.each(res.results, function(i, value){

							if(value.Field[o.end_field] !== "0000-00-00 00:00:00") {
								events.push(
									{start: value.Field[data.start_field], end: value.Field[data.end_field], id: value.Listing.id}
								);
							} else {
								events.push(
									{date: value.Field[data.start_field], id: value.Listing.id}
								);
							}

						});

						// Precompile hogan template
						var precompiledTemplate = Hogan.compile( $('#jr-module-calendar-template-'+o.module_id).html() );

						calendarContainer.clndr({
							events: events,
							multiDayEvents: {
								startDate: 'start',
								endDate: 'end',
								singleDay: 'date'
							},
							ready: function() {
								if (parseInt(o.autoload)) {
									el.find('.today.event').trigger('click');
								}
							},
							clickEvents: {
						      click: function(target) {
						      	if (target.events.length) {
						      		jreviews.calendar.listings(el, o, target.events);
						      	}
						      }
						    },
						    render: function(data){
								return precompiledTemplate.render(data);
							},
						    constraints: {
								startDate: data.start_date,
								endDate: data.end_date
							},
							adjacentDaysChangeMonth: false,
				    		forceSixRows: false
						});

	                }
	                else {

	                    return false;

	                }

	            });

			});
		},

		listings: function(el, o, listings) {

			listing_ids = [];

			$.each(listings, function(i, value){
				listing_ids.push(value.id);
			});

			listing_ids = listing_ids.join(',');

			params = {
				module_id: Math.floor(Math.random() * 10000),
				referrer: 'calendar',
				ajax_init: true,
				module: {
					listing_order: o.listing_order,
					summary: o.summary,
					summary_words: o.summary_words,
					show_category: o.show_category,
					fields: o.fields,
					editor_rating: o.editor_rating,
					user_rating: o.user_rating,
					columns: o.columns,
					show_numbers: o.show_numbers,
					tmpl_suffix: o.listings_tmpl_suffix,
					tn_show: o.tn_show,
					tn_position: o.tn_position,
					tn_mode: o.tn_mode,
					tn_size: o.tn_size,
					listing: listing_ids,
					module_total: 50,
					module_limit: 50
				}
			}

			var getListings = jreviews.dispatch({method:'get',type:'html',controller:'module_listings',action:'index',data:params});

	        getListings.done(function(res) {

	        	el.find('.jr-module-calendar-listings').html("").append(res);

	        });

		}
	}

	})(jQuery, jreviews);

	/*********************** BEGIN PLUGINS ***************************/

	/* get selected text */

	(function($) {
		$.jrGetSelected = function()
		{
			var t = '';
			if(window.getSelection){
				t = window.getSelection().toString();
			}
			else if(document.getSelection){
				t = document.getSelection().toString();
			}
			else if(document.selection && document.selection.type != "Control"){
				t = document.selection.createRange().text;
			}
			return t;
		};
	})(jQuery);

	/* custom dialog functions */

	(function($) {

		$.jrDialog = function(html, options) {

			$('div.jr-dialog').dialog('destroy').remove();

			var params = {},
				dialogDiv = $('<div class="jr-dialog jrDialogContent jrHidden"></div>');

			params = {
				title:          '',
				width:          '640px',
				height:         'auto',
				dialogClass:    'jrDialog',
				resizable:      false,
				modal:          true,
				autoOpen:       true,
				position:       'center',
				lightbox: 		false
			};

			params = $.extend(params,options);

			if(jreviews.mobi == 1) {

				params.maxWidth = params.width;

				params.width = '95%';

				params.position = 'center top';
			}

			if($.trim(html) === '') {

				html = jreviews.__t('PROCESS_REQUEST_ERROR');

				params.buttons = {};
			}

			dialogDiv.html(html);

			dialogDiv.dialog(params);

			if(params.lightbox == true) {
				dialogDiv.closest('.ui-dialog').find('.ui-dialog-titlebar').remove();

				$('body').on('click', function(e) {
					if($(e.target).closest('.ui-dialog').length == 0 )  {
						$('div.jr-dialog').dialog('destroy').remove();
					}
				});
			}

			var buttonPane = $('.ui-dialog-buttonpane');

			// Disable jQuery UI button in dialogs

			if(typeof jQuery().button == 'function') {

				buttonPane.find('button').button('destroy');
			}

			if(jreviews.mobi == 1) {

				buttonPane.find('.ui-dialog-buttonset').css('float','left');
			}

			var delButton = buttonPane.find('button:contains('+jreviews.__t('DELETE')+')'),
				submitButton = buttonPane.find('button:contains('+jreviews.__t('SUBMIT')+')'),
				cancelButton = buttonPane.find('button:contains('+jreviews.__t('CANCEL')+')');

			delButton.addClass('jr-submit jrButton').prepend('<span class="jrIconDelete"></span>');

			submitButton.addClass('jr-submit jrButton')
						.prepend('<span class="jrIconSave"></span>')
						.on('click',function() {
							$(this).prop('disabled',true)
									.find('span').removeClass('jrIconSave').addClass('jrButtonSpinner');
						});

			cancelButton.addClass('jr-cancel jrButton')
						.prepend('<span class="jrIconCancel"></span>');

			dialogDiv.on('failedValidation',function() {

				submitButton.prop('disabled',false)
						.find('span').removeClass('jrButtonSpinner').addClass('jrIconSave');
			});

			return dialogDiv;
		};

		$.jrAlert = function(text, params) {

			$('div.jr-alert').dialog('destroy').remove();

			var dialogDiv = $('<div class="jr-alert">' + text + '</div>');

			var defaults = {
				title:          '',
				width:          '400px',
				height:         'auto',
				dialogClass:    'jrDialog',
				resizable:      false,
				modal:          true,
				autoOpen:       true,
				position:       'center',
				buttons: {}
			};

			params = $.extend(defaults, params);

			params.buttons[jreviews.__t('CLOSE')] = function() {

				$(this).dialog('close');
			};

			if(jreviews.mobi == 1) {

				params.maxWidth = params.width;

				params.width = '95%';

				params.position = 'center top';
			}

			dialogDiv.dialog(params);
		};

	})(jQuery);

	/* getCSS plugin */

	(function($) {

		$.getCSS = function( url, media ) {
			$(document.createElement('link') ).attr({
				href: url,
				media: media || 'screen',
				type: 'text/css',
				rel: 'stylesheet'
			}).appendTo('head');
		};

	})(jQuery);


	/* scrollTo plugin */

	(function($) {

		$.fn.jrScrollTo = function(options,onAfter) {

			var settings = $.extend({}, $.fn.jrScrollTo.defaults, options);

			var targetOffset = $(this).offset().top + settings.offset;

			$('html,body').animate({scrollTop: targetOffset}, settings.duration, settings.easing, onAfter);

			return $(this);
		};

		$.fn.jrScrollTo.defaults = {
			offset: -30,
			duration: 1000,
			easing: 'swing'
		};

	})(jQuery);


	/* tinyMCE plugin */
	(function($) {

		$.fn.tinyMCE = function() {

			var el = this,
				timeout = 1000,
				mceID;

			try {

				setTimeout(function() {

					if (typeof tinyMCE == 'object') {

						// WordPress

						if(typeof tinyMCEPreInit == 'object' && !$.isEmptyObject(tinyMCEPreInit))
						{
							var init = 'mceInit' in tinyMCEPreInit ? tinyMCEPreInit.mceInit['jr-editor'] : false;

							var qt = 'qtInit' in tinyMCEPreInit ? tinyMCEPreInit.qtInit['jr-editor'] : false;

							$('.jr-wysiwyg-editor').each(function() {

								var id = $(this).attr('id');

								if(qt) {

									var qt_settings = $.extend( {}, qt, { id : id } );

									tinyMCEPreInit.qtInit[id] = qt_settings;

									// turn on the quicktags editor for each
									quicktags( qt_settings );

									// Required to overcome empty toolbar for multiple editors
									QTags._buttonsInit();
								}

								if(init) {

									var tmc_settings = $.extend( {}, init, { selector : "#" + id } );

									tinyMCEPreInit.mceInit[id] = tmc_settings;

									// turn on tinyMCE for each
									tinyMCE.init( tmc_settings );
								}

							});
						}

						// Joomla

						else {

							if(typeof WFEditor == 'undefined' && typeof tinyMCE.settings == 'object') {

								tinyMCE.settings.height = null;
							}

							return el.each(function() {

								tinyMCE.execCommand('mceAddEditor', false, this.id);
							});

						}
					}

				}, timeout);

			} catch (err) {

				console(err);
			}
		};

		$.fn.RemoveTinyMCE = function() {

			var el = this;

			try {

				if (typeof tinyMCE == 'object') {

					return el.each(function() {

						tinyMCE.execCommand('mceRemoveEditor', true, this.id);
					});
				}

			} catch (err) {

				//
			}
		};

		$.fn.SaveTinyMCE = function() {

			var el = this;

			try {

				if (typeof tinyMCE == 'object') {

					return el.each(function() {

						tinyMCE.get(this.id).save();
					});
				}

			} catch (err) {

				//
			}
		};

	})(jQuery);

	/* jrShowMore plugin */
	(function($) {

		var ShowMore = function(elem, options) {
			this.elem = elem;
			this.$elem = $(elem);

			this.options = $.extend({}, $.fn.jrShowMore.options, options);
			this.init();
		};

		ShowMore.prototype = {

			init: function() {

				var self = this;
				var characters = self.$elem.data('characters');
				var separator = self.$elem.data('separator');

				if (characters !== undefined && parseInt(characters,10) > 0) {
					self.options.characters = characters;
				}

				if (separator !== undefined) {
					self.options.separator = separator;
				}

				self.elemTextOrig = self.$elem.html().trim();
				self.elemText = self.$elem.text().trim();
				self.elemLength = self.elemText.length;

				// output the show more link only if the number of default characters is shorten than original text
				if (self.elemLength > self.options.characters + self.options.separator.length) {

					self.elemTextMain = self.elemText.substring(0, self.options.characters);

					self.elemTextMainHTML = '<span class="jrShowMain">' + self.elemTextMain + '</span>';
					self.elemTextSeparatorHTML = '<span class="jrShowMoreSeparator">' + self.options.separator + '</span>';
					self.elemTextMoreHTML = '<span class="jrShowMore" style="display: none;">' + self.elemTextOrig + '</span>';
					self.elemTextMoreLink = '<a class="jrShowLink" href="#">' + self.options.showMoreText + '</a>';

					self.$elem.html(self.elemTextMainHTML + self.elemTextSeparatorHTML + self.elemTextMoreHTML + ' ' + self.elemTextMoreLink);

					self.$elem.on('click','a', function(e){

						e.preventDefault();

						var $link = $(this);

						if ($link.hasClass('jrShowLink')) {

							$link.html(self.options.hideMoreText).removeClass('jrShowLink').addClass('jrHideLink');

						} else {

							$link.html(self.options.showMoreText).removeClass('jrHideLink').addClass('jrShowLink');

						}

						self.$elem.find('.jrShowMain, .jrShowMoreSeparator, .jrShowMore').toggle();
					});
				}

			}

		};

		$.fn.jrShowMore = function(options) {

			return this.each(function() {
				new ShowMore(this, options);
			});

		};

		$.fn.jrShowMore.options = {
			showMoreText: jreviews.__t('SHOW_MORE'),
			hideMoreText: jreviews.__t('HIDE_MORE'),
			separator: '...',
			characters : 250
		};

	})(jQuery);

	/* jrPopup plugin */
	(function($) {

		var Popup = function(elem, options) {
			this.elem = elem;
			this.$elem = $(elem);

			this.options = $.extend({}, $.fn.jrPopup.options, options);
			this.init();
		};

		Popup.prototype = {

			init: function() {

				var self = this;

				self.popupDiv = self.$elem.next('.'+self.options.className);

				if (self.popupDiv.length && ($.trim(self.popupDiv.html()) !== '' || self.popupDiv.hasClass('jr-ajax'))) {
					self.$popupDiv = $(self.popupDiv);
					self.$elem.hover(
						function() {
							self.show();
						},
						function() {
							self.hide();
						}
					);
					self.$elem.click(function() {
							self.hide();
						}
					);
					self.$popupDiv.hover(
						function() {
							self.show();
						},
						function() {
							self.hide();
						}
					);
				}

			},

			show: function() {

				var self = this;

				if (self.options.onBeforeShow) {
					self.options.onBeforeShow();
				}

				var pos = self.getPosition();

				self.$popupDiv.stop(true, true);

				self.clearTimeouts();

				self.showTimeout = setTimeout(function() {
					self.$popupDiv.appendTo('body').css({'top' : pos.top, 'left': pos.left});
					self.$popupDiv.fadeIn(100);
				}, 300);

			},

			hide: function() {

				var self = this;

				self.clearTimeouts();

				self.showTimeout = setTimeout(function() {
					$(self.popupDiv).fadeOut(100, function() {
						self.$elem.after($(this));
					});
				}, self.options.delay);

			},

			clearTimeouts: function() {

				if (this.showTimeout) {
					clearTimeout(this.showTimeout);
					this.showTimeout = 0;
				}

				if (this.showTimeout) {
					clearTimeout(this.showTimeout);
					this.showTimeout = 0;
				}

			},

			getPosition: function() {

				var elemTop = this.$elem.offset().top,
					elemLeft = this.$elem.offset().left,
					elemWidth = this.$elem.outerWidth(),
					elemHeight = this.$elem.outerHeight();

				return {
					'left'  : elemLeft + elemWidth/2 - this.$popupDiv.outerWidth()/2 + 'px',
					'top'   : elemTop - this.$popupDiv.outerHeight() - 20 + 'px'
				};

			}

		};

		$.fn.jrPopup = function(options) {

			return this.each(function() {
				new Popup(this, options);
			});

		};

		$.fn.jrPopup.options = {
			className: 'jrPopup',
			delay : 300
		};

	})(jQuery);

	/**
	 * Copyright (c) 2010 Jakob Westhoff
	 *
	 * Permission is hereby granted, free of charge, to any person obtaining a copy
	 * of this software and associated documentation files (the "Software"), to deal
	 * in the Software without restriction, including without limitation the rights
	 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
	 * copies of the Software, and to permit persons to whom the Software is
	 * furnished to do so, subject to the following conditions:
	 *
	 * The above copyright notice and this permission notice shall be included in
	 * all copies or substantial portions of the Software.
	 *
	 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
	 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
	 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
	 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
	 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
	 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
	 * THE SOFTWARE.
	 */
	(function( window ) {
		var sprintf = function( format ) {
			// Check for format definition
			if ( typeof format != 'string' ) {
				throw "sprintf: The first arguments need to be a valid format string.";
			}

			/**
			 * Define the regex to match a formating string
			 * The regex consists of the following parts:
			 * percent sign to indicate the start
			 * (optional) sign specifier
			 * (optional) padding specifier
			 * (optional) alignment specifier
			 * (optional) width specifier
			 * (optional) precision specifier
			 * type specifier:
			 *  % - literal percent sign
			 *  b - binary number
			 *  c - ASCII character represented by the given value
			 *  d - signed decimal number
			 *  f - floating point value
			 *  o - octal number
			 *  s - string
			 *  x - hexadecimal number (lowercase characters)
			 *  X - hexadecimal number (uppercase characters)
			 */
			var r = new RegExp( /%(\+)?([0 ]|'(.))?(-)?([0-9]+)?(\.([0-9]+))?([%bcdfosxX])/g );

			/**
			 * Each format string is splitted into the following parts:
			 * 0: Full format string
			 * 1: sign specifier (+)
			 * 2: padding specifier (0/<space>/'<any char>)
			 * 3: if the padding character starts with a ' this will be the real
			 *    padding character
			 * 4: alignment specifier
			 * 5: width specifier
			 * 6: precision specifier including the dot
			 * 7: precision specifier without the dot
			 * 8: type specifier
			 */
			var parts      = [];
			var paramIndex = 1;
			while ( part = r.exec( format ) ) {
				// Check if an input value has been provided, for the current
				// format string
				if ( paramIndex >= arguments.length ) {
					throw "sprintf: At least one argument was missing.";
				}

				parts[parts.length] = {
					/* beginning of the part in the string */
					begin: part.index,
					/* end of the part in the string */
					end: part.index + part[0].length,
					/* force sign */
					sign: ( part[1] == '+' ),
					/* is the given data negative */
					negative: ( parseInt( arguments[paramIndex] ) < 0 ) ? true : false,
					/* padding character (default: <space>) */
					padding: ( part[2] == undefined )
							 ? ( ' ' ) /* default */
							 : ( ( part[2].substring( 0, 1 ) == "'" )
								 ? ( part[3] ) /* use special char */
								 : ( part[2] ) /* use normal <space> or zero */
							   ),
					/* should the output be aligned left?*/
					alignLeft: ( part[4] == '-' ),
					/* width specifier (number or false) */
					width: ( part[5] != undefined ) ? part[5] : false,
					/* precision specifier (number or false) */
					precision: ( part[7] != undefined ) ? part[7] : false,
					/* type specifier */
					type: part[8],
					/* the given data associated with this part converted to a string */
					data: ( part[8] != '%' ) ? String ( arguments[paramIndex++] ) : false
				};
			}

			var newString = "";
			var start = 0;
			// Generate our new formated string
			for( var i=0; i<parts.length; ++i ) {
				// Add first unformated string part
				newString += format.substring( start, parts[i].begin );

				// Mark the new string start
				start = parts[i].end;

				// Create the appropriate preformat substitution
				// This substitution is only the correct type conversion. All the
				// different options and flags haven't been applied to it at this
				// point
				var preSubstitution = "";
				switch ( parts[i].type ) {
					case '%':
						preSubstitution = "%";
					break;
					case 'b':
						preSubstitution = Math.abs( parseInt( parts[i].data ) ).toString( 2 );
					break;
					case 'c':
						preSubstitution = String.fromCharCode( Math.abs( parseInt( parts[i].data ) ) );
					break;
					case 'd':
						preSubstitution = String( Math.abs( parseInt( parts[i].data ) ) );
					break;
					case 'f':
						preSubstitution = ( parts[i].precision == false )
										  ? ( String( ( Math.abs( parseFloat( parts[i].data ) ) ) ) )
										  : ( Math.abs( parseFloat( parts[i].data ) ).toFixed( parts[i].precision ) );
					break;
					case 'o':
						preSubstitution = Math.abs( parseInt( parts[i].data ) ).toString( 8 );
					break;
					case 's':
						preSubstitution = parts[i].data.substring( 0, parts[i].precision ? parts[i].precision : parts[i].data.length ); /* Cut if precision is defined */
					break;
					case 'x':
						preSubstitution = Math.abs( parseInt( parts[i].data ) ).toString( 16 ).toLowerCase();
					break;
					case 'X':
						preSubstitution = Math.abs( parseInt( parts[i].data ) ).toString( 16 ).toUpperCase();
					break;
					default:
						throw 'sprintf: Unknown type "' + parts[i].type + '" detected. This should never happen. Maybe the regex is wrong.';
				}

				// The % character is a special type and does not need further processing
				if ( parts[i].type ==  "%" ) {
					newString += preSubstitution;
					continue;
				}

				// Modify the preSubstitution by taking sign, padding and width
				// into account

				// Pad the string based on the given width
				if ( parts[i].width != false ) {
					// Padding needed?
					if ( parts[i].width > preSubstitution.length )
					{
						var origLength = preSubstitution.length;
						for( var j = 0; j < parts[i].width - origLength; ++j )
						{
							preSubstitution = ( parts[i].alignLeft == true )
											  ? ( preSubstitution + parts[i].padding )
											  : ( parts[i].padding + preSubstitution );
						}
					}
				}

				// Add a sign symbol if neccessary or enforced, but only if we are
				// not handling a string
				if ( parts[i].type == 'b'
				  || parts[i].type == 'd'
				  || parts[i].type == 'o'
				  || parts[i].type == 'f'
				  || parts[i].type == 'x'
				  || parts[i].type == 'X') {
					if ( parts[i].negative == true ) {
						preSubstitution = "-" + preSubstitution;
					}
					else if ( parts[i].sign == true ) {
						preSubstitution = "+" + preSubstitution;
					}
				}

				// Add the substitution to the new string
				newString += preSubstitution;
			}

			// Add the last part of the given format string, which may still be there
			newString += format.substring( start, format.length );

			return newString;
		};

		// Register the new sprintf function as a global function, as well as a
		// method to the String object.
		window.sprintf = sprintf;
		String.prototype.printf = function() {
			var newArguments = Array.prototype.slice.call( arguments );
			newArguments.unshift( String( this ) );
			return sprintf.apply( undefined, newArguments );
		};
	})( window );

	(function($){

	$.widget( "jreviews.buttongroup", {

		buttons: {},

		classes: {
			buttonGroup: 'jrButtonGroup',
			button:'jrButton',
			selected:'jrBlue'
		},

		_create: function() {

			var self = this,
				el = self.element,
				classes = self.classes;

			el.hide();

			var container = $('<div class="'+classes.buttonGroup+'">');

			el.find('option').each(function(i) {

				var option = $(this);

				var button = $('<div class="'+classes.button+'">').html(option.text()).data('index',i),
					icon = option.data('icon');

				if(icon) button.prepend('<span class="'+icon+'">');

				button.on('click',function() {

					el.find('option:eq('+$(this).data('index')+')').prop('selected',true);

					option.trigger('change');
				});

				self.buttons[i] = button;

				if(option.val() !== '') container.append(button);
			});

			el.on('change',function() {

				container.find('.'+classes.button).removeClass(classes.selected);

				self.buttons[el.find('option:selected').index()].addClass(classes.selected);
			});

			container.insertBefore(el);
		},

		_init: function() {

			var self = this,
				el = self.element;

			el.trigger('change');
		},

		destroy: function() {

			$.Widget.prototype.destroy.call( this );
		}
	});

}(jQuery));
