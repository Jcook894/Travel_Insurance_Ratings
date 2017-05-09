function validateSRForm(pressbutton)
{
    var form = document.adminForm;
    if (pressbutton == "list")
    {
      submitform( pressbutton );
      return;
    }
    if (form.name.value == '') {
		alert( "Name needs to be specified." );
	}
	else if (IsContentEmpty(form.imageURL.value)) {
		alert( "Image URL cannot be blank." );
	}
    else
    {
        submitform( pressbutton );
    }
}
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