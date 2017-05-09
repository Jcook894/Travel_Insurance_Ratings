(function($) {

	jreviews.imagemigrator = {

		init: function() {

			jrPage.on('click','.jr-image-migrator .jr-imagemigrator-popup',function(e) {

				e.preventDefault();

				jreviews.imagemigrator.loadPopup();
			});

			jrPage.on('click','.jr-image-migrator .jr-imagemigrator-reset',function(e) {

				e.preventDefault();

				jreviews.imagemigrator.resetErrors();
			});

		},

		loadPopup: function() {

			var loadingDialog = jreviews.dispatch({method:'get',type:'html',controller:'admin/admin_imagemigrator',action:'popUp','data':{task:'all'}});

			loadingDialog.done(function(html) {

				// Call dialog
				var buttons = {};

				var close = function(){$('body').data('imagemigrator.abort',1);};

				buttons[jreviews.__t('START')] = function() {

					$('#jr-images-abortUpdate').hide();

					$('body').data('imagemigrator.abort',0);

					$(this).attr('disabled','disabled');

					jreviews.imagemigrator.start();

				};

				buttons[jreviews.__t('STOP')] = function() {

					$('.jr-spinner').hide();

					$('#jr-images-statusUpdate').html('Aborting...please wait.');

					$('body').data('imagemigrator.abort',1);

					$(this).dialog('close');
				};

				$.jrDialog(html, {buttons:buttons,position:'top',width:'640px','close':close});

				var buttonPane = $('.ui-dialog-buttonpane');

				buttonPane.find('button:contains('+jreviews.__t('START')+')')
					.prepend('<span class="jrIconYes"></span>')
					.before('<span class="jr-spinner jrLoadingMedium jrHidden"></span>');

				buttonPane.find('button:contains('+jreviews.__t('STOP')+')').prepend('<span class="jrIconCancel"></span>');

			});

		},

		start: function() {

			var data = {
				task:'start',
				debug:$('input[name=debug_info]:checked').val(),
				increment:$('#limit').val(),
				delay:$('#delay').val()
			};

			var loadingAction = jreviews.dispatch({method:'get',type:'json',controller:'admin/admin_imagemigrator',action:'convert',data:data});

			loadingAction.done(function(res) {

				$('.jr-spinner').show();

				var remaining = $('#jr-images-remaining'),
					success = $('#jr-images-success'),
					errors = $('#jr-images-error'),
					success_count = Number(success.html()) + res.success,
					error_count = Number(errors.html()) + res.errors,
					remaining_count = res.remaining;

				remaining.html(remaining_count);

				success.html(success_count);

				errors.html(error_count);

				if(res.debug !== '') {

					$('#jr-imagemigrator-debug').show().prepend(res.debug);

				}

				if(remaining_count>0 && $('body').data('imagemigrator.abort')===0)
				{

					setTimeout(jreviews.imagemigrator.start, 0);
				}
				else {

					$('#jr-images-statusUpdate').hide();

					$('.jr-spinner').hide();

				}

			});

		},

		resetErrors: function() {

			var submittingAction = jreviews.dispatch({method:'get',type:'json',controller:'admin/admin_imagemigrator',action:'reset'});

			submittingAction.done(function() {

				$.jrAlert("You can run the migrator again to re-try the images with errors.");

			});

		}
	};

	jreviews.addOnload('imagemigrator-init',	jreviews.imagemigrator.init);

})(jQuery);