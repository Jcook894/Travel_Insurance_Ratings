<?php
/**
 * RapidLauncher Addon for JReviews
 * Copyright (C) 2010-2016 ClickFWD LLC
 * This is not free software, do not distribute it.
 * For licencing information visit https://www.jreviews.com
 * or contact sales@jreviews.com
**/

defined( 'MVC_FRAMEWORK') or die( 'Direct Access to this location is not allowed.' );

class RapidlauncherMenuModel extends MyModel  {

    var $name = 'Menu';

    protected $published = 1;

    protected $access = 1;

    protected $componentId;

    public function getMenuIdByTitle($title)
    {
        $query = '
            SELECT id FROM #__menu WHERE title = %s LIMIT 1
        ';

        $query = sprintf($query, $this->Quote($title));

        $id = $this->query($query, 'loadResult');

        return (int) $id;
    }

    public function getMenuIdByCatId($categoryId)
    {
        $query = '
            SELECT id FROM #__menu WHERE link = "index.php?option=com_content&view=category&layout=blog&id=%d" LIMIT 1
        ';

        $query = sprintf($query, $categoryId);

        $id = $this->query($query, 'loadResult');

        return (int) $id;
    }

    public function createMenuContainer($title, $alias, $description)
    {
        $data = array(
            'title' => $title,
            'menutype' => $alias,
            'description' => $description
        );

        foreach($data AS $key => $value)
        {
            $values[] = $this->Quote($value) . ' AS ' . $key;
        }

        $values = implode(',', $values);

        $query = '
            INSERT INTO
                #__menu_types (%s)
            SELECT * FROM (SELECT %s) AS tmp
            WHERE NOT EXISTS
                ( SELECT menutype
                    FROM #__menu_types
                  WHERE menutype = %s
                )
            LIMIT 1
        ';

        $query = sprintf($query, implode(',', array_keys($data)), $values, $this->Quote($alias));

        $this->query($query);

        return true;
    }

    protected function componentId($extension)
    {
        $query = '
            SELECT
                extension_id
            FROM
                #__extensions
            WHERE type = "component" AND  element = %s
            LIMIT 1
        ';

        $query = sprintf($query, $this->Quote($extension));

        return $this->query($query, 'loadResult');
    }

    public function createCategoryMenu($data)
    {
        $link = "index.php?option=com_content&view=category&layout=blog&id=%d";

        $extension = 'com_content';

        // Check if a menu already exists for this category and abort

        if($id = $this->getMenuIdByCatId($data['id']))
        {
            return false;
        }

        $link = sprintf($link, $data['id']);

        return $this->createMenu($data, $params = [], $link, $extension);
    }

    public function createJReviewsMenu($data)
    {
        $title = Sanitize::getString($data, 'title');

        $link = "index.php?option=com_jreviews&view=%s";

        if($this->getMenuIdByTitle($title) > 0 )
        {
            return false;
        }

        $type = Sanitize::getString($data, 'type');

        $view = $this->typeToView($type);

        $link = sprintf($link, $view);

        $data['menutype'] = 'jreviews-menus';

        $params = [
            'action' => $this->typeToAction($type),
            'dirid' => Sanitize::getInt($data, 'dirid'),
            'menu-anchor_title' => '',
            'menu-anchor_css' => '',
            'menu_image' => '',
            'menu_text' => 1,
            'page_title' => '',
            'show_page_heading' => 0,
            'page_heading' => '',
            'pageclass_sfx' => '',
            'menu-meta_description' => '',
            'menu-meta_keywords' => '',
            'robots' => '',
            'secure' => 0
        ];

        return $this->createMenu($data, $params, $link, 'com_jreviews');
    }

    protected function isDuplicate($data)
    {
        $query = '
            SELECT
                count(*)
            FROM
                #__menu
            WHERE
                client_id = %d
                AND parent_id = %d
                AND alias = %s
                AND language = %s
        ';

        $parentId = Sanitize::getInt($data, 'parent_id') == 0 ? 1 : Sanitize::getInt($data, 'parent_id');

        $query = sprintf($query, Sanitize::getInt($data, 'client_id'), $parentId, $this->Quote(Sanitize::getString($data, 'alias')), $this->Quote(Sanitize::getString($data, 'language')));

        $count = $this->query($query, 'loadResult');

        return $count > 0 ? $count : false;
    }

    public function createMenu($data, $params, $link, $extension)
    {
        $componentId = $this->componentId($extension);

        $parent_id = Sanitize::getInt($data, 'parent_id');

        $bindData = array(
            'id' => 0,
            'title' => $data['title'],
            'alias' => Sanitize::getString($data, 'alias'),
            'note' => 'JReviews Rapidlauncher',
            'link' => $link,
            'menutype' => $data['menutype'], // menu container alias
            'type' => 'component',
            'published' => $this->published,
            'parent_id' => Sanitize::getInt($data, 'parent_id'),
            'component_id' => $componentId,
            'browserNav' => 0,
            'access' => $this->access,
            'template_style_id' => 0,
            'home' => 0,
            'language' => '*',
            'params' => $params,
            'client_id' => 0
        );

        // Check if duplicate exists before continuing
        if($this->isDuplicate($bindData))
        {
            return false;
        }

        $row = JTable::getInstance('menu');

        $row->setLocation($parent_id, 'last-child');

        if ($row->bind($bindData))
        {
            if ($row->check())
            {
                if ($row->store())
                {
                    if ($row->rebuildPath($row->id))
                    {
                        return true;
                    }
                }
            }
        }

        return false;
    }

    protected function typeToView($type)
    {
        switch($type)
        {
            case 'directories/index':
                $view = 'directory';
                break;
            case 'search/index':
                $view = 'advancedsearch';
                break;
            case 'reviews/rankings':
                $view = 'reviewers';
                break;
            case 'categories/mylistings': // MyListings
                $view = 'categories';
                break;
            case 'categories/favorites': // MyFavorites
                $view = 'categories';
                break;
            case 'reviews/myreviews': // MyReviews
                $view = 'reviews';
                break;
            case 'media/myMedia': // MyMedia
                $view = 'media';
                break;
            case 'categories/compareCatchAll': // Listing Comparison
                $view = 'catchall';
                break;
        }

        return $view;
    }

    protected function typeToAction($type)
    {
        switch($type)
        {
            case 'directories/index':
                $action = 0;
                break;
            case 'search/index':
                $action = 11;
                break;
            case 'reviews/rankings':
                $action = 18;
                break;
            case 'categories/mylistings': // MyListings
                $action = 12;
                break;
            case 'categories/favorites': // MyFavorites
                $action = 13;
                break;
            case 'reviews/myreviews': // MyReviews
                $action = 10;
                break;
            case 'media/myMedia': // MyMedia
                $action = 23;
                break;
            case 'categories/compareCatchAll': // Listing Comparison
                $action = 103;
                break;
        }

        return $action;
    }

}