<?php
/**
 * JReviews - Reviews Extension
 * Copyright (C) 2010-2016 ClickFWD LLC
 * This is not free software, do not distribute it.
 * For licencing information visit https://www.jreviews.com
 * or contact sales@jreviews.com
**/

defined('_JEXEC') or defined('ABSPATH') or die;

class JReviewsBaseShortCode
{
	public function processAttr(& $attr)
	{
		if (empty($attr)) return;

        // We can't allow anyone to just start writing custom queries
        // Need to prevent query injection vulnerability

        unset($attr['custom_where'], $attr['custom_order']);

        if (isset($attr['total']))
        {
            $attr['module_total'] = $attr['total'];
        }

        if (isset($attr['limit']))
        {
            $attr['module_limit'] = $attr['limit'];
        }
	}

	public function render($attr)
	{
		$this->processAttr($attr);

		$type = Sanitize::getString($attr, 'type');

		if ($this->shortcodeExists($type))
		{
			$className = ucfirst($type).'ShortCode';

			if (class_exists($className))
			{
				$shortcode = new $className;

				return $shortcode->render($attr);
			}
		}

		return null;
	}

	protected function shortcodeExists($type)
	{
		$exists = false;

		$filePath = '/types/'.$type.'.php';

		if (file_exists($this->overridePath.'/includes/shortcodes'.$filePath))
		{
	    	require_once($this->overridePath.'/includes/shortcodes'.$filePath);

			$exists = true;
		}
		elseif (file_exists(dirname(__FILE__).$filePath)) {

	    	require_once(dirname(__FILE__).$filePath);

			$exists = true;
		}

		return $exists;
	}
}