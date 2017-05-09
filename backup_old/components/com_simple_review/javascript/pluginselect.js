//Simple Review
function SRListSelectAll(selectListID)
{
	var plugList = document.getElementById(selectListID);
	var plugLen = plugList.length;

	for (var i=plugLen-1; i > -1; i--) 
	{
		plugList.options[i].selected = true;
	}
}


/*
* Below is Modified code From Joomla
* @version $Id: joomla.javascript.js 3562 2006-05-20 12:27:49Z stingrey $
* @package Joomla
* @copyright Copyright (C) 2005 Open Source Matters. All rights reserved.
* @license http://www.gnu.org/copyleft/gpl.html GNU/GPL
* Joomla! is Free Software
*/

/**
* Adds a select item(s) from one list to another
*/

function SRaddSelectedToList(srcListID, tgtListID ) {

	var srcList = document.getElementById(srcListID);
	var tgtList = document.getElementById(tgtListID);
		
	var srcLen = srcList.length;
	var tgtLen = tgtList.length;
	var tgt = "x";

	//build array of target items
	for (var i=tgtLen-1; i > -1; i--) 
	{
		tgt += "," + tgtList.options[i].value + ","
		
		tgtList.options[i].selected = true;
	}

	//Pull selected resources and add them to list
	for (var i=0; i < srcLen; i++) 
	{
		if (srcList.options[i].selected && tgt.indexOf( "," + srcList.options[i].value + "," ) == -1) 
		{
			opt = new Option( srcList.options[i].text, srcList.options[i].value );
			opt.selected = true;
			tgtList.options[tgtList.length] = opt;
		}
	}
}

function SRdelSelectedFromList(srcListID) 
{
	var srcList = document.getElementById(srcListID);

	var srcLen = srcList.length;

	for (var i=srcLen-1; i > -1; i--) 
	{
		if (srcList.options[i].selected) 
		{
			srcList.options[i] = null;
		}
	}
}