function _InitDynamicFields(dynamicPrefix, numberOfDynamicFields)
{
   prefix = dynamicPrefix;
   maxDynamicFields = numberOfDynamicFields;
}

function AppendDynamicFields()
{
   var appended ="";
   for(var i=0; i< numberOfDynamicFields; i++)
   {
     var dynamicfield = document.getElementById("dynamic"+(i+1));
     if(dynamicfield.value!="" && dynamicfield.value!=null)
     {
        if(appended != "")
        {
		  appended+="||";
		}
		//remove any ||
		var parsedDF = dynamicfield.value.replace(/\|\|/g,"")
		//remove new lines as they will mess with js strings
		parsedDF = parsedDF.replace(/\n/g,"<br/>")
     	appended += parsedDF;
     }
   }
   var form = document.adminForm;
   form.dynamicFields.value = appended;
}

function displayDynamicFields(dynamicFields)	
{
	var fields=dynamicFields.split("||");
	for(var i=0; i< fields.length; i++)
	{
		addDynamicField(fields[i]);
	}
}