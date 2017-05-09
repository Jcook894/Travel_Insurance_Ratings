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
 * @modified	by ClickFWD LLC
 */

defined( 'MVC_FRAMEWORK') or die( 'Direct Access to this location is not allowed.' );

class HtmlHelper extends MyHelper
{
	var $viewSuffix = '';

	var $abs_url = false;

	var $tags = array(
		'metalink' => '<link href="%s" title="%s"%s />',
		'link' => '<a href="%s" %s>%s</a>',
		'mailto' => '<a href="mailto:%s" %s>%s</a>',
		'form' => '<form %s>',
		'formend' => '</form>',
		'input' => '<input name="%s" %s />',
		'email' => '<input type="email" name="%s" %s/>',
		'url' => '<input type="url" name="%s" %s/>',
		'number' => '<input type="number" name="%s" %s/>',
		'text' => '<input type="text" name="%s" %s/>',
		'textarea' => '<textarea name="%s" %s>%s</textarea>',
		'hidden' => '<input type="hidden" name="%s" %s/>',
		'checkbox' => '<input type="checkbox" name="%s" id="%s" %s/>%s',
		'checkboxmultiple' => '<input type="checkbox" name="%s[]" id="%s" %s />%s',
		'radio' => '<input type="radio" name="%s" id="%s" %s />%s',
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
		'button' => '<button %s>%s</button>',
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

	function css($file, $options = array())
    {
    	$default = array('minified'=>false, 'version'=>0);

    	$options = array_merge($default,$options);

    	extract($options);

		// Register in header to prevent duplicates
        $registry = ClassRegistry::getObject('css');

		if (is_array($file))
		{
			foreach ($file as $i)
			{
				if(!isset($registry[$i]))
				{
					$this->css($i, $options);
				}
			}

			return;
		}

        $relative = true;

        ClassRegistry::setObject($file,1,'css');

        if(false !== strpos($file,MVC_ADMIN))
        {
			// Automatic routing to admin path

            $file = str_replace(MVC_ADMIN .'/', '', $file);

            $cssUrl = $this->locateStyleSheet($file,array('admin'=>true,'relative'=>true,'minified'=>$minified));
        }
        else {

            $cssUrl = $this->locateStyleSheet($file,array('admin'=>false,'relative'=>true,'minified'=>$minified));
        }

        if($cssUrl != '' && $cssUrl != '/' && $cssUrl != '?v='.$version) {

            cmsFramework::addStyleSheet($file, $cssUrl, $version);
        }
	}

	function js($file, $options = array())
    {
    	$default = array('minified'=>false,'absUrls'=>array(), 'version'=>0);

    	$options = array_insert($default,$options);

    	extract($options);

		if(is_array($file)) {

			foreach($file as $i) {

				if(!isset($registry[$i])) {

					$this->js($i, $options);
				}
			}

			return;
		}

        $relative = in_array($file,$absUrls) ? false : true;

        if(false!==strpos($file,MVC_ADMIN))
        {
			// Automatic routing to admin path

            $file = str_replace(MVC_ADMIN .'/', '', $file);

            $jsUrl = $this->locateScript($file,array('admin'=>true,'relative'=>$relative,'minified'=>$minified));
        }
        else {

            $jsUrl = $this->locateScript($file,array('admin'=>false,'relative'=>$relative,'minified'=>$minified));
        }

        if($jsUrl != '' && $jsUrl != '/' && $jsUrl != '?v='.$version)
        {
            cmsFramework::addScript($jsUrl, $file, $version);
        }
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

	function link($title, $url = null, $attributes = array())
    {
    	$abs_url = false;

		if(isset($attributes['sef']) && !$attributes['sef'])
        {
            if(isset($attributes['return_url'])){
                return $url;
            }

            unset($attributes['sef']);

			if($this->abs_url || Sanitize::getBool($attributes,'abs_url'))
			{
				unset($attributes['abs_url']);

				$abs_url = true;
			}

			$attributes = $this->_parseAttributes($attributes);

			if($abs_url)
			{
				$url = cmsFramework::makeAbsUrl($url);
			}

			return sprintf($this->tags['link'],$url,$attributes,$title);
		}

		return $this->sefLink($title, $url, $attributes);
	}

	function sefLink($title, $url = null, $attributes = array())
    {
		$url = str_replace('{_PARAM_CHAR}',_PARAM_CHAR,$url);

		if(Sanitize::getBool($attributes,'abs_url')) {

			$this->abs_url = true;

			unset($attributes['abs_url']);
		}

		$sef_url = cmsFramework::route($url);

		if($this->abs_url) {

			$sef_url = cmsFramework::makeAbsUrl($sef_url);
		}

        if(isset($attributes['return_url'])){

            return $sef_url;
        }

		$attributes = $this->_parseAttributes($attributes);

        return sprintf($this->tags['link'],$sef_url,$attributes,$title);
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