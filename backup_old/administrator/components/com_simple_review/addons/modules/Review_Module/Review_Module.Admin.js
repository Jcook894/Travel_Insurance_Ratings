function IsContentEmpty(content)
{
    //remove html tags
    var strippedContent = content.replace(/(<([^>]+)>)/ig,'');
    strippedContent = strippedContent.replace(/^(&nbsp;)*/g,'');
    var leftTrimmed = strippedContent.replace(/^\s*/g,'');
    if(leftTrimmed == '')
        return true;
    return false;
}

function changeReviewTemplate()
{
    var form = document.adminForm;

    if(!confirm('Changing the template will cause your review to be saved. If the new category has less titles you will loose the reviews extra ones. Are you sure you wish to proceed?'))
    {
        return;   
    }

    if(confirm('Do you want to use the newly selected categories template? \nWarning: Changing the template will erase your reviews current content. \nClick Cancel to change category and keep your current reviews content.'))
    {
       form.task.value = 'changeTemplate';
       
    }
    else
    {
       form.task.value = 'keepTemplate'; 
    }
    
    if(form.title1.value == "")
    {
      form.title1.value = "TEMP TITLE";
    }
    
    form.submit();
}

function srExpand(imgElement, textAreaID)
{	
	var titleTextArea = jQuery("#"+textAreaID);
	
    if (titleTextArea.attr("rows") > 1)
    {
        titleTextArea.attr("rows", "1");
        jQuery(imgElement)
			.attr("src", "images/expandall.png")
         	.attr("alt", "Expand")
        	.attr("title", "Expand");
    }
    else
    {
        titleTextArea.attr("rows", "5");
        jQuery(imgElement)
			.attr("src", "images/collapseall.png")
        	.attr("alt", "Contract")
        	.attr("title", "Contract");
    }	
}
