<?php
/**
 * Html Helper class file.
 *
 * Simplifies the construction of HTML elements.
 *
 * CakePHP(tm) :  Rapid Development Framework <http://www.cakephp.org/>
 * Copyright 2005-2007, Cake Software Foundation, Inc.
 *								1785 E. Sahara Avenue, Suite 490-204
 *								Las Vegas, Nevada 89104
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 *  
 * @modified	by Alejandro Schmeichler
 * @lastmodified 2008-03-06
 */

defined( 'MVC_FRAMEWORK') or die( 'Direct Access to this location is not allowed.' );

class HtmlHelper extends MyHelper
{
	var $viewSuffix = '';
	
	var $tags = array(
		'metalink' => '<link href="%s" title="%s"%s />',
		'link' => '<a href="%s" %s>%s</a>',
		'mailto' => '<a href="mailto:%s" %s>%s</a>',
		'form' => '<form %s>',
		'formend' => '</form>',
		'input' => '<input name="%s" %s />',
		'text' => '<input type="text" name="%s" %s/>',
		'textarea' => '<textarea name="%s" %s>%s</textarea>',
		'hidden' => '<input type="hidden" name="%s" %s/>',
		'textarea' => '<textarea name="%s" %s>%s</textarea>',
		'checkbox' => '<input type="checkbox" name="%s[]" id="%s" %s/>&nbsp;%s',
		'checkboxmultiple' => '<input type="checkbox" name="%s[]" id="%s" %s />&nbsp;%s',
		'radio' => '<input type="radio" name="%s" id="%s" %s />&nbsp;%s',
		'selectstart' => '<select name="%s"%s>',
		'selectmultiplestart' => '<select name="%s[]"%s>',
		'selectempty' => '<option value=""%s>&nbsp;</option>',
		'selectoption' => '<option value="%s"%s>%s</option>',
		'selectend' => '</select>',
		'optiongroup' => '<optgroup label="%s"%s>',
		'optiongroupend' => '</optgroup>',
		'password' => '<input type="password" name="%s" %s />',
		'file' => '<input type="file" name="%s" %s/>',
		'file_no_model' => '<input type="file" name="%s" %s />',
		'submit' => '<input type="submit" %s/>',
		'submitimage' => '<input type="image" src="%s" %s />',
		'button' => '<input type="button" %s />',
		'imagebutton' => '<input type="image" %s />',		
		'image' => '<img src="%s" %s />',
		'tableheader' => '<th%s>%s</th>',
		'tableheaderrow' => '<tr%s>%s</tr>',
		'tablecell' => '<td%s>%s</td>',
		'tablerow' => '<tr%s>%s</tr>',
		'block' => '<div%s>%s</div>',
		'blockstart' => '<div%s>',
		'blockend' => '</div>',
		'para' => '<p%s>%s</p>',
		'parastart' => '<p%s>',
		'label' => '<label for="%s"%s>%s</label>',
        'label_no_for'=>'<label %s>%s</label>',
		'fieldset' => '<fieldset %s><legend>%s</legend>%s</fieldset>',
		'fieldsetstart' => '<fieldset><legend>%s</legend>',
		'fieldsetend' => '</fieldset>',
		'legend' => '<legend>%s</legend>',
		'css' => '<link rel="%s" type="text/css" href="%s" %s/>',
		'style' => '<style type="text/css" %s>%s</style>',
		'charset' => '<meta http-equiv="Content-Type" content="text/html; charset=%s" />',
		'javascriptlink' => '<script type="text/javascript" src="%s"></script>',
		'javascriptcode' => '<script type="text/javascript">%s</script>',		
		'ul' => '<ul%s>%s</ul>',
		'ol' => '<ol%s>%s</ol>',
		'li' => '<li%s>%s</li>'
	);
			
	function ccss($files) {

		if($this->xajaxRequest) {
			return;
		}
	
		// Register in header to prevent duplicates
		$headCheck = RegisterClass::getInstance('HeadTracking');
				
		if (is_array($files)) {
			
			$out = '';
			$css = array();

			foreach ($files as $i) {
				// Check if already in header
				if(!$headCheck->check($i)) {
					$css[] = $i.".css";
				}
			}
			if(empty($css)) {
				return;
			}
		}
									
		if(is_array($files)) {
			foreach($files AS $file) {
				$headCheck->register($file);				
			}
		} else {
			$headCheck->register($files);
		}

		// Create combine script url
		$css_files = str_replace('/','!',implode(',',$css));

		$url = WWW_ROOT . "components/com_s2framework/vendors/combine/combine.php?app={$this->app}&type=css&theme={$this->viewTheme}&suffix={$this->viewSuffix}&files={$css_files}";

		$rel = 'stylesheet';
		
		$out = sprintf($this->tags['css'], $rel, $url, '');
			
		cmsFramework::addScript($out, false);
	}		
	
	function css($files, $inline = false) {
		
/**
 * BYPASSES THE CSS METHOD IN FAVOR OF CCSS (cached)
 */
		if(Configure::read('Cache.assets') && !defined('MVC_FRAMEWORK_ADMIN') && !$inline) {
			$this->ccss($files);
			return;
		}
		
		// Register in header to prevent duplicates
		$headCheck = RegisterClass::getInstance('HeadTracking');
				
		if (is_array($files)) {
			
			$out = '';
			
			foreach ($files as $i) {
				// Check if already in header
				if(!$headCheck->check($i)) {
					$out .= "\n\t" . $this->css($i, $inline);
				}
			}
			
			if ($out != '' && $inline)  {
				return $out . "\n";
			}
			
			return;
		}

		if(false!==strpos($files,MVC_ADMIN)) { // Automatic routing to admin path

			$fileArray = array('name'=>str_replace(MVC_ADMIN._DS,'',$files),'suffix'=>$this->viewSuffix,'ext'=>'css');
			
			$paths = array(
				S2Paths::get($this->app, 'S2_ADMIN_VIEWS_OVERRIDES') . 'themes' . DS . $this->viewTheme . DS . 'theme_css',
				S2Paths::get($this->app, 'S2_ADMIN_VIEWS_OVERRIDES') . 'themes' . DS . 'default' . DS . 'theme_css',
				S2Paths::get($this->app, 'S2_ADMIN_VIEWS') . 'themes' . DS . $this->viewTheme . DS . 'theme_css',
				S2Paths::get($this->app, 'S2_ADMIN_VIEWS') . 'themes' . DS . 'default' . DS . 'theme_css'				
			);
						
			$cssPath = fileExistsInPath($fileArray,$paths);
			$cssUrl = pathToUrl($cssPath);

		} else {

			$fileArray = array('name'=>$files,'suffix'=>$this->viewSuffix,'ext'=>'css');
			
			$paths = array(
				S2Paths::get($this->app, 'S2_THEMES_OVERRIDES') . $this->viewTheme . DS . 'theme_css',
				S2Paths::get($this->app, 'S2_THEMES') . $this->viewTheme . DS . 'theme_css',
				S2Paths::get($this->app, 'S2_THEMES_OVERRIDES') . 'default' . DS . 'theme_css',				
				S2Paths::get($this->app, 'S2_THEMES') . 'default' . DS . 'theme_css'
			);
											
			$cssPath = fileExistsInPath($fileArray,$paths);
			$cssUrl = pathToUrl($cssPath);

		}

		$headCheck->register($files);
		
		$rel = 'stylesheet';
		
		$out = sprintf($this->tags['css'], $rel, $cssUrl, '');
			
		cmsFramework::addScript($out,$inline);
		
	}
	

	function cjs($files, $duress = false) {
		
		if($this->xajaxRequest) {
			return;
		}
				
		// Register in header to prevent duplicates
		$headCheck = RegisterClass::getInstance('HeadTracking');
						
		if (is_array($files)) {
			
			$out = '';
			$js = array();
			
			foreach ($files as $i) {
				// Check if already in header
				if($duress || !$headCheck->check($i)) {
					$js[] = $i . '.js';
				}
			}
			
			if(empty($js)) {
				return;
			}
		}
		
		if(is_array($files)) {
			foreach($files AS $file) {
				$headCheck->register($file);				
			}
		} else {
			$headCheck->register($files);
		}
				
		// Create combine script url
		$js_files = str_replace('/','!',implode(',',$js));
		$url = WWW_ROOT . "components/com_s2framework/vendors/combine/combine.php?app={$this->app}&type=javascript&files={$js_files}";

		$out = sprintf($this->tags['javascriptlink'], $url);

		cmsFramework::addScript($out,false, $duress);		
	}
		
	function js($files, $inline = false, $duress = false, $nocache = false) {
		
/**
 * BYPASSES THE JS METHOD IN FAVOR OF CJS (cached)
 */

		if(Configure::read('Cache.assets') && !defined('MVC_FRAMEWORK_ADMIN') && !$inline && $nocache === false) {
			$this->cjs($files, $duress);
			return;
		}		
		
		// Register in header to prevent duplicates
		$headCheck = RegisterClass::getInstance('HeadTracking');			

		if (is_array($files)) {
			$out = '';
			
			foreach ($files as $i) {
				// Check if already in header
				if($duress || !$headCheck->check($i)) {
					$out .= "\n\t" . $this->js($i, $inline, $duress, $nocache);
				}
			}
			
			if ($out != '' && $inline)  {
				echo $out . "\n";
			}
			
			return;
		}
		
		$headCheck->register($files);		

		if(false!==strpos($files,MVC_ADMIN)) { // Automatic routing to admin path

			$fileArray = array('name'=>str_replace(MVC_ADMIN._DS,'',$files),'suffix'=>'','ext'=>'js');

			$jsPaths = array(
				S2Paths::get($this->app, 'S2_ADMIN_VIEWS_OVERRIDES') . 'js',
				S2Paths::get($this->app, 'S2_ADMIN_VIEWS') . 'js'
			);
						
			$jsPath = fileExistsInPath($fileArray,$jsPaths);
			$jsUrl = pathToUrl($jsPath);

		} else {

			$fileArray = array('name'=>$files,'suffix'=>'','ext'=>'js');
			
			$jsPaths = array(
				S2Paths::get($this->app, 'S2_VIEWS_OVERRIDES') . 'js',
				S2Paths::get($this->app, 'S2_VIEWS') . 'js'
			);
											
			$jsPath = fileExistsInPath($fileArray,$jsPaths);
			$jsUrl = pathToUrl($jsPath);

		}
		
		$out = sprintf($this->tags['javascriptlink'], $jsUrl);

		cmsFramework::addScript($out,$inline, $duress);		
	}		
		
	function getCrumbs($crumbs, $separator = '&raquo;', $startText = false) 
	{	
		if (count($crumbs)) {
			
			$out = array();
			
			if ($startText) {
				$out[] = $this->sefLink($startText, '/');
			}

			foreach ($crumbs as $crumb) {
				if (!empty($crumb['link'])) {
					$out[] = $this->sefLink($crumb['text'], $crumb['link']);
				} else {
					$out[] = $crumb['text'];
				}
			}
			
			return implode($separator, $out);
			
		} else {
			return null;
		}
	}
	
	function link($title, $url = null, $attributes = array()) {
		if(isset($attributes['sef']) && !$attributes['sef']) {
			unset($attributes['sef']);
			$attributes = $this->_parseAttributes($attributes);
			return sprintf($this->tags['link'],$url,$attributes,$title);			
		}
		return $this->sefLink($title, $url, $attributes);	
	}
	
	function sefLink($title, $url = null, $attributes = array()) {
		$url = str_replace('{_PARAM_CHAR}',_PARAM_CHAR,$url);
		$attributes = $this->_parseAttributes($attributes);
		return sprintf($this->tags['link'],cmsFramework::route($url),$attributes,$title);
	}
	
	function image($src,$attributes = array()) {
		$attributes = $this->_parseAttributes($attributes);
		return sprintf($this->tags['image'],$src,$attributes);
	}
	
	function div($class = null, $text = null, $attributes = array()) {

		if ($class != null && !empty($class)) {
			$attributes['class'] = $class;
		}
		if ($text === null) {
			$tag = 'blockstart';
		} else {
			$tag = 'block';
		}
		return $this->output(sprintf($this->tags[$tag], $this->_parseAttributes($attributes), $text));
	}	
}