/**
 *  $Id: SRCore.js 118 2009-09-04 16:13:58Z rowan $
 *
 * 	Copyright (C) 2005-2009  Rowan Youngson
 * 
 *	This file is part of Simple Review.
 *
 *	Simple Review is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation, either version 3 of the License, or
 *  (at your option) any later version.

 *  Simple Review is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.

 *  You should have received a copy of the GNU General Public License
 *  along with Simple Review.  If not, see <http://www.gnu.org/licenses/>.
*/

SRCore = function(){};

/**
 * Loads a Css File into the documents head tag.
 * @param {Object} csslink
 */ 
SRCore.LoadCss = function(csslink)
{
	//if (!document.getElementById('loadedsrcss'))
	{
		var link = document.createElement('link');
		link.setAttribute('id', 'loadedsrcss');
		link.setAttribute('href', csslink);
		link.setAttribute('rel', 'stylesheet');
		link.setAttribute('type', 'text/css');
		var head = document.getElementsByTagName('head').item(0);
		head.appendChild(link);
	}
}

SRCore.IsIE = function()
{
	var version=0
	if (navigator.appVersion.indexOf("MSIE")!=-1)
	{
	    temp=navigator.appVersion.split("MSIE")
	    version=parseFloat(temp[1])
	}
	return (version > 0);          
}


/**
 * Loads a Css File into the documents head tag.
 * @param {Object} csslink
 */ 
function loadSRCSS(csslink)
{
	//if (!document.getElementById('loadedsrcss'))
	{
		var link = document.createElement('link');
		link.setAttribute('id', 'loadedsrcss');
		link.setAttribute('href', csslink);
		link.setAttribute('rel', 'stylesheet');
		link.setAttribute('type', 'text/css');
		var head = document.getElementsByTagName('head').item(0);
		head.appendChild(link);
	}
}

/**
 * A basic implementation of the C# String.Format method.
 * @param {String} format
 * @param {Array} args1,..,argsN
 * @return {String} the formatted string.
 */
String.SRFormat = function(format, args)
{
	var pattern = /\{\d+\}/g;
	
	if(arguments.length < 2)
	{
		var err = new Error();
		err.name = 'Invalid Argument Exception';
		err.message = 'The function must receive at least two arguments, (1) The string to format (2) A list of values to insert into the formatted string.';
		throw(err);
	}
	
	var a = arguments;
	var replacer = function(capture)
	{ 
		var matched = capture.match(/\d+/);
		return a[Number(matched[0]) + 1].toString();
	};
	
	return format.replace(pattern, replacer);
}
