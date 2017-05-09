/**
 *  $Id: Category_Module.Admin.js 122 2009-09-13 12:39:25Z rowan $
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

var SRTitleType = 
{
	Text: 'Text', 
	Link: 'Link', 
	Rating: 'Rating',
	List: 'List', 
	Option: 'Option', 
	Selection: 'Selection'
};


function validateSRForm(pressbutton)
{
    var form = document.adminForm;
    if (pressbutton == "list")
    {
      submitform( pressbutton );
      return;
    }
    
    if (form.name.value == '') {
		alert( "Category Name needs to be specified." );
	}
    else
    {  
        submitform( pressbutton );
    }
}

/**
* Manager for adding titles to a component.
* @param {String} instanceVariableName: the name of the variable which references the instance of this class
* @param {String} idPrefix: the prefix for the html elements id e.g. "title"
* @param {String} idPostfix: the postfix for the html elements id e.g. ""
* @param {Integer} maxTitles: the number of titles allowed to be created e.g. 20
* @param {Object} language: contains the translated strings
*/
function CategoryTitleManager(instanceVariableName, idPrefix, idPostfix, maxTitles, language){
    this._instanceVariableName = instanceVariableName;
    this._prefix = idPrefix;
    this._postfix = idPostfix;
    this._maxTitles = maxTitles;
    this._numberOfTitles = 0;
	this._language = language;
	
	this._titles = [];
}

CategoryTitleManager.prototype = {
	
	AddTitle: function(titleValue, titleName, titleDBID, titleType, mandatory){
		this._numberOfTitles++;
		
		var configuration = {
			name: titleName,
			value: titleValue,
			hiddenID: this._prefix + "ID_0_" +  String(this._numberOfTitles),
			id: titleDBID,
			mandatory: mandatory,
			titleID: this._prefix + String(this._numberOfTitles),
			titleNumber: this._numberOfTitles,
			type: SRTitleType[titleType]
		};
		
		var clonedTitle = jQuery("#titleTemplateToClone div:first").clone();

		jQuery("label.titleLabel", clonedTitle).attr("for", configuration.titleID)
											   .text(configuration.name);
		
		var divTitleText = jQuery("div.titleText", clonedTitle);
		
		var titleTextArea = jQuery("textarea.titleTextArea", divTitleText);
		titleTextArea.attr("id", configuration.titleID)
					 .attr("name", configuration.titleID)
					 .text(configuration.value);
									 
		jQuery("img.expand", divTitleText).click(function(){
			
	        if (titleTextArea.attr("rows") > 1)
	        {
	            titleTextArea.attr("rows", "1");
	            jQuery(this)
					.attr("src", "images/expandall.png")
	             	.attr("alt", "Expand")
	            	.attr("title", "Expand");
	        }
	        else
	        {
	            titleTextArea.attr("rows", "5");
	            jQuery(this)
					.attr("src", "images/collapseall.png")
	            	.attr("alt", "Contract")
	            	.attr("title", "Contract");
	        }
			
		});
		
		var titleOption = jQuery("div.titleOption", clonedTitle);
		
		var titleTypeSelect = jQuery("select.titleType", titleOption).attr("id", configuration.titleID + "Type")
											   				   		 .attr("name", configuration.titleID + "Type");
											   
		
		var optionSelector = String.SRFormat("option[value={0}]", configuration.type);			 
		jQuery(optionSelector, titleTypeSelect).attr("selected",true);
		
		var titleMandatory = jQuery("input[type=checkbox].titleMandatory", titleOption).attr("id", configuration.titleID + "Mandatory")
																  					   .attr("name", configuration.titleID + "Mandatory");
		if(configuration.mandatory)
		{
			titleMandatory.attr("checked", "checked");	
		}																			   
																					   
	
		jQuery("input[type=hidden].titleID", clonedTitle).attr("id", configuration.hiddenID)
														 .attr("name", configuration.hiddenID)
														 .val(configuration.id);
														 
		clonedTitle.appendTo("#titlesdiv");
		 
	},
	
    GetNextNumberedTitleName : function(namePrefix)
    {    
     	return namePrefix + " " + (this._numberOfTitles + 1);
    } 	
}