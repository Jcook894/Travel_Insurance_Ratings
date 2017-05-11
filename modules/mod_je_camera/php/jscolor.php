<?php

defined('JPATH_BASE') or die;

jimport('joomla.form.formfield');

class JFormFieldJSColor extends JFormField
{
	protected $type = 'JSColor';
	protected function getInput()
	{
		global $JElementJSColorJSWritten;
		if (!$JElementJSColorJSWritten) 
		{				
                        $jsFile = dirname(__FILE__) . DIRECTORY_SEPARATOR . ".." . DIRECTORY_SEPARATOR . "js" . DIRECTORY_SEPARATOR . "jscolor" . DIRECTORY_SEPARATOR . "jscolor.js";
                        $jsUrl = str_replace(JPATH_ROOT, JURI::root(true), $jsFile);
                        $jsUrl = str_replace(DIRECTORY_SEPARATOR, "/", $jsUrl);
						
			$document	= JFactory::getDocument();
			$document->addScript( $jsUrl );
			$JElementJSColorJSWritten = TRUE;
		}

		// Initialize JavaScript field attributes.
		$onchange	= $this->element['onchange'] ? ' onchange="'.(string) $this->element['onchange'].'"' : '';

		$class		= 'class="color {hash:true}"';

		return '<input type="text" name="'.$this->name.'" id="'.$this->id.'"' .
				' value="'.htmlspecialchars($this->value, ENT_COMPAT, 'UTF-8').'"' .
				$class.$onchange.'/>';
	}
}
