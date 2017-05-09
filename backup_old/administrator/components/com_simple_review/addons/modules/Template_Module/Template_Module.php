<?php
/**
 *  $Id: Template_Module.php 96 2009-06-13 09:31:58Z rowan $
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

// ensure this file is being included by a parent file
defined('_VALID_MOS')||defined( '_JEXEC' ) or die( 'Direct Access to this location is not allowed.' );
require_once(dirname(__FILE__ )."/db.php");

class Template_Module extends Module_Base
{	
	var $_TemplateModule = null;
	function Template_Module(&$addonManager, &$moduleName, $initialise)
	{ 
		$this->friendlyName = "Template List.";
		$this->addonPath = dirname(__FILE__);
		
	  	$this->hasCSS=false;
	  	$this->hasLanguage=true;
	  	$this->hasConfig=false;	
			    				
	  	parent::Module_Base($addonManager, $moduleName, $initialise);
		$this->Initialised = $initialise;	 
					
		$this->friendlyName = $this->GetString($this, 'Templates');
		$this->defaultTaskName = $this->GetString($this, 'AdminDescription');  		 	    	   		
	}	
	
 	function TemplateList( $selected=NULL )
    {
		$templateTable = SRDB_Template::TableName();
        if($selected == '')
        {
            $selected = NULL;
        }

        $query = "SELECT * FROM $templateTable order by templateID";
        $rows = SRBridgeDatabase::Query($query);
        $templates = array();
        $templates[] = SRHtmlControls::Option( -1, "Don't Use Template");
        foreach($rows as $row)
        {
            $templates[] = SRHtmlControls::Option( $row->templateID, Simple_Review_Common::RemoveSlashes($row->name));
        }
        return SRHtmlControls::SelectList( $templates, 'templateID', 'size="1" class="inputbox" ', 'value', 'text', $selected );
    } 	
}

?>