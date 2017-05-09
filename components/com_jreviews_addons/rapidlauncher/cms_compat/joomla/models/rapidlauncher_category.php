<?php
/**
 * RapidLauncher Addon for JReviews
 * Copyright (C) 2010-2016 ClickFWD LLC
 * This is not free software, do not distribute it.
 * For licencing information visit https://www.jreviews.com
 * or contact sales@jreviews.com
**/

defined( 'MVC_FRAMEWORK') or die( 'Direct Access to this location is not allowed.' );

$basePath = JPATH_ADMINISTRATOR.'/components/com_categories';

require_once $basePath.'/models/category.php';

class RapidlauncherCategoryModel extends MyModel  {

    var $name = 'Category';

    public function getCategoryId($title)
    {
        $query = '
            SELECT id FROM #__categories WHERE title = ' . $this->Quote($title) . ' AND extension = "com_content"
        ';

        $id = $this->query($query, 'loadResult');

        return (int) $id;
    }

    public function getRootCategoryId()
    {
        $query = '
            SELECT id FROM #__categories WHERE parent_id = 0 AND extension = "system"
        ';

        $id = $this->query($query, 'loadResult');

        return (int) $id;

    }

    public function isDuplicate($title, $catId)
    {
        if(!$catId)
        {
            $catId = $this->getRootCategoryId();
        }

        $query = '
            SELECT
                id, alias
            FROM
                #__categories
            WHERE title = ' . $this->Quote($title)
            . ' AND parent_id = ' . $catId
            . ' AND extension = "com_content"
            AND published >= 0
        ';

        $category = $this->query($query, 'loadAssoc');

        return $category ?: false;
    }

    public function create($data)
    {
        // Before we try to add the category, lets check if there is a duplicate based on title and parent_id

        if($category = $this->isDuplicate($data['title'], $data['parent_id']))
        {
           return $category;
        }

        $data['rules'] = array(
            'core.edit.state' => array(),
            'core.edit.delete' => array(),
            'core.edit.edit' => array(),
            'core.edit.state' => array(),
            'core.edit.own' => array(1=>true)
        );

        $config  = array('table_path' => JPATH_ADMINISTRATOR.'/components/com_categories/tables');

        $category_model = new CategoriesModelCategory($config);

        if($category_model->save($data))
        {
            $category = $category_model->getItem();

            return array('id' => $category->id, 'alias' => $category->alias);
        }

        return false;
    }

    public function read($dirId)
    {
        $query = '
            SELECT
                Category.title AS `Title`, IF(ParentCategory.title = "ROOT","",ParentCategory.title) AS `Parent`, Directory.desc AS `Directory`, ListingType.title AS `ListingType`
            FROM
                #__categories AS Category
            LEFT JOIN
                #__categories AS ParentCategory ON Category.parent_id = ParentCategory.id
            LEFT JOIN
                #__jreviews_categories AS JreviewsCategory ON Category.id = JreviewsCategory.id
            LEFT JOIN
                #__jreviews_directories AS Directory ON JreviewsCategory.dirid = Directory.id
            LEFT JOIN
                #__jreviews_criteria AS ListingType ON JreviewsCategory.criteriaid = ListingType.id
            WHERE
                JreviewsCategory.dirid IN ('.cleanIntegerCommaList($dirId).')
            ORDER BY
                Category.lft
        ';

        return $this->query($query, 'loadAssocList');
    }
}