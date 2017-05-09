<?php
/**
 * RapidLauncher Addon for JReviews
 * Copyright (C) 2010-2016 ClickFWD LLC
 * This is not free software, do not distribute it.
 * For licencing information visit https://www.jreviews.com
 * or contact sales@jreviews.com
**/

defined( 'MVC_FRAMEWORK') or die;

S2App::import('Vendor',
	[
		'fileuploader/fileuploader',
		'thephpleague' . DS . 'csv' . DS . 'autoload'
	],
	'jreviews'
);

use League\Csv\Reader;
use League\Csv\Writer;

class RapidlauncherCsvHelperComponent
{
    protected $path;

    function setPath($path)
    {
        $this->path = $path;

        return $this;
    }

	function read($filename)
	{
		if(file_exists($this->path . DS . $filename))
		{
			return Reader::createFromPath($this->path . DS . $filename);
		}

		return false;
	}

	function write($rows)
	{
		if(empty($rows)) return;

		// Get header
		$first = reset($rows);

        $writer = Writer::createFromFileObject(new SplTempFileObject());

		$writer->insertOne(array_keys($first));

		$writer->insertAll($rows);

		return $writer;
	}
}