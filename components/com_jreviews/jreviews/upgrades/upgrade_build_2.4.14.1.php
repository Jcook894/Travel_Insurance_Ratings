<?php
defined( 'MVC_FRAMEWORK') or die;

$Model = new S2Model;

// Find all related listing fields to convert the datatype to varchar 255

$query = "SELECT name FROM #__jreviews_fields WHERE type = 'relatedlisting'";

$relatedFields = $Model->query($query,'loadColumn');

$columns = $Model->getTableColumns('#__jreviews_content');

foreach($relatedFields AS $fname)
{
	if(strstr($columns[$fname]['Type'],'int'))
	{
		$query = "
			ALTER TABLE
				#__jreviews_content
			CHANGE COLUMN
				`" . $fname . "` `" . $fname . "` VARCHAR(255) NOT NULL DEFAULT '';
		";

		$Model->query($query);

		$query = "
			UPDATE
				#__jreviews_content
			SET
				" . $fname . " = CONCAT('*'," . $fname . ",'*')
			WHERE
				" . $fname . " != ''
		";

		$Model->query($query);
	}
}

