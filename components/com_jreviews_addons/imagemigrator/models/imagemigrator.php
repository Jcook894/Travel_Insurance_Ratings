<?php
class ImagemigratorModel extends MyModel
{
	var $name = 'ImageMigrator';

	var $title = 'Image Migrator';

	var $process_limit = 10; // 10 listings at a time

    function free()
    {
        return true;
    }

	function createTable()
	{
		$query = "
			CREATE TABLE IF NOT EXISTS `#__jreviews_image_migrator` (
			  `id` int(11) NOT NULL auto_increment,
			  `title` varchar(255) NOT NULL,
			  `error` tinyint NOT NULL,
			  `files` text NOT NULL,
			  PRIMARY KEY  (`id`),
			  KEY `error` (`error`)
			);
		";

		$this->query($query);
	}

	function complete($listing_id, $title, $error, $files)
	{
		$files = implode("\n", $files);

		$query = "
			INSERT INTO
				#__jreviews_image_migrator
			(`id`, `title`, `error`, `files`) VALUES ($listing_id, ".$this->Quote($title).", ". (int) $error . ", ".$this->Quote($files).")
		";

		$this->query($query);
	}

	function getCatIds()
	{
		if(!$cat_ids = cmsFramework::getSessionVar('cat_ids','imagemigrator_addon'))
		{
			// Create table to track processed listing ids. #__jreviews_image_migrator
			$query = "
				SELECT
					id
				FROM
					#__jreviews_categories
				WHERE
					`option` = 'com_content' AND criteriaid > 0
				ORDER BY
					id
			";

			$cat_ids = $this->query($query,'loadColumn');

			cmsFramework::setSessionVar('cat_ids',$cat_ids,'imagemigrator_addon');

		}

		return $cat_ids;
	}

	function getListingCount()
	{
		$count = 0;

		$cat_ids = $this->getCatIds();

		if(!empty($cat_ids))
		{
			$query = "
				SELECT
					count(*)
				FROM
					#__content
				WHERE
					catid IN ( " . $this->Quote($cat_ids) . ")
					AND
					id NOT IN (SELECT id FROM #__jreviews_image_migrator)
					AND
					images <> ''
			";

			$count = $this->query($query, 'loadResult');
		}

		return $count;
	}

	function getErrors()
	{
		$query = "
			SELECT
				*
			FROM
				#__jreviews_image_migrator
			WHERE
				error = 1
			ORDER BY
				id
		";

		$errors = $this->query($query, 'loadAssocList');

		return $errors;
	}

	function getListingImages($limit = 10)
	{
		$limit == '' and $limit = $this->process_limit;

		$cat_ids = $this->getCatIds();

		$query = "
			SELECT
				id, title, images, created_by AS user_id
			FROM
				#__content
			WHERE
				catid IN (" . $this->Quote($cat_ids) . ")
				AND
				id NOT IN (SELECT id FROM #__jreviews_image_migrator)
				AND
				images <> ''
			LIMIT
				{$limit}
		";

		return $this->query($query, 'loadAssocList');
	}

	function mediaExists($listing_id, $filepath)
	{
		$pathinfo = pathinfo($filepath);

		$filename = $pathinfo['filename'];

		$file_extension = $pathinfo['extension'];

		$query = "
			SELECT
				count(*)
			FROM
				#__jreviews_media
			WHERE
				listing_id = " . (int) $listing_id . "
				AND
				filename = " . $this->Quote($filename) . "
				AND
				file_extension = " . $this->Quote($file_extension) . "
		";

		return $this->query($query, 'loadResult');
	}
}