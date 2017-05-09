/**
 * Ratings Migrator Addon for JReviews
 * Copyright (C) 2010-2015 ClickFWD LLC
 * This is not free software, do not distribute it.
 * For licencing information visit https://www.jreviews.com
 * or contact sales@jreviews.com
**/

if (!window.jreviews) {
    jreviews = {};
}

if (!window.jreviews.ratingsmigrator) {
    jreviews.ratingsmigrator = {};
}

(function($, jreviews, window, undefined) {

    var page;

    jreviews.ratingsmigrator.init = function() {

        page = jrPage;

        page.on('click','.jr-addon-ratingsmigrator .jr-start',function(e) {

            e.preventDefault();

            $(this).attr('disabled',true);

            page.find('[class*="jr-ratingsmigrator"]').hide();

            jreviews.ratingsmigrator.processStep(1);

        });
    };

    jreviews.ratingsmigrator.processStep = function(step, params) {

        var data = params || {};

        page.find('.jr-ratingsmigrator-step' + step).show();

        var doStep = jreviews.dispatch({method:'get',type:'json',controller:'admin/admin_ratingsmigrator',action: 'step' + step,data:data});

        doStep.done(function(res) {

            if(res.success) {

                // We want to run a specific step instead of continuing with the loop

                if(res.step !== undefined) {

                    jreviews.ratingsmigrator.processStep(res.step,res);

                    if(res.progress !== undefined)
                    {
                        page.find('.jr-ratingsmigrator-progress').show();

                        page.find('.jr-progress-bar').css('width',res.progress + '%');

                        page.find('.jr-progress-number').html(res.progress + '%');
                    }
                }
                else if(res.complete === undefined) {

                    page.find('.jr-ratingsmigrator-progress').hide();

                    jreviews.ratingsmigrator.processStep(step + 1);
                }
                else {

                    page.find('.jr-ratingsmigrator-progress').hide();

                    page.find('.jr-ratingsmigrator-complete').show();
                }
            }
            else {

                page.find('.jr-ratingsmigrator-error').html(res.msg).show();
            }
        });
    };

    jreviews.addOnload('ratingsmigrator-init', jreviews.ratingsmigrator.init);

})(jQuery, jreviews , window);
