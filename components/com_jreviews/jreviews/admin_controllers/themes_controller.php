<?php
/**
 * JReviews - Reviews Extension
 * Copyright (C) 2010-2015 ClickFWD LLC
 * This is not free software, do not distribute it.
 * For licencing information visit https://www.jreviews.com
 * or contact sales@jreviews.com
**/

// no direct access
defined( 'MVC_FRAMEWORK') or die( 'Direct Access to this location is not allowed.' );

class ThemesController extends MyController
{
	var $uses = array('directory','category','jreviews_category');

	var $helpers = array('html','form','admin/paginator');

    var $components = array('config','access');

	var $autoRender = false;

	var $autoLayout = false;

	function beforeFilter()
	{
		$this->Access->init($this->Config);

		# Call beforeFilter of MyAdminController parent class
		parent::beforeFilter();
	}

	function index()
    {
        return $this->categories();
	}

	function saveCategory()
    {
		$response = array();

        $tmpl = $this->data['tmpl'];

		$catids = array();

		foreach ($tmpl as $catid=>$value)
		{

			$category = $this->JreviewsCategory->findRow(array('conditions'=>array('JreviewsCategory.id = ' . $catid,'JreviewsCategory.option = "com_content"')));

			$tmpl = Sanitize::getString($value, 'name');

			$suffix = Sanitize::getString($value, 'suffix');

			if ($category['JreviewsCategory']['tmpl'] != $tmpl || $category['JreviewsCategory']['tmpl_suffix'] != $suffix )
			{
				$catids[] = $catid;

				$query = "
					UPDATE
						#__jreviews_categories
					SET
						tmpl = ". $this->Quote($tmpl) .", tmpl_suffix = " . $this->Quote($suffix) . "
					WHERE
						id = " . (int) $catid . " AND `option` = 'com_content'";

				if (!$this->JreviewsCategory->query($query))
                {
					return false;
				}

			}
		}

		// Clear cache
		clearCache('', 'views');

		clearCache('', '__data');

		$page = $this->categories();

		return true;
	}

    function categories()
    {
        $this->action = 'categories';

        $filters = Sanitize::getVar($this->params,'filter');

        $cat_id = Sanitize::getInt($filters, 'cat_id');

        $lists = array();

        $total = 0;

        $categories = $this->Category->getCategoryList(array('disabled'=>false));

        $rows = $this->Category->getReviewCategories($this->offset, $this->limit, $total, compact('cat_id'));

        $this->set(array(
            'rows'=>$rows,
            'categories'=>$categories,
            'cat_id'=>$cat_id,
            'pagination'=>array('total'=>$total)
        ));

        return $this->render();
    }

}
