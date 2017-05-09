<?php
/**
 * JReviews - Reviews Extension
 * Copyright (C) 2010-2015 ClickFWD LLC
 * This is not free software, do not distribute it.
 * For licencing information visit https://www.jreviews.com
 * or contact sales@jreviews.com
**/

defined( 'MVC_FRAMEWORK') or die( 'Direct Access to this location is not allowed.' );

class CategoryModel extends MyModel
{
    const _CATEGORY_ID = 'id';

    var $name = 'Category';

    var $useTable = '#__categories AS Category';

    var $primaryKey = 'Category.cat_id';

    var $realKey = 'id';

    var $fields = array(
        'cat_id'=>'Category.id AS `Category.cat_id`',
                    'Category.title AS `Category.title`',
                    'Category.alias AS `Category.slug`',
                    'Category.level AS `Category.level`',
                    'Category.params AS `Category.params`',
                    'Category.parent_id AS `Category.parent_id`',
                    'JreviewsCategory.criteriaid AS `Category.criteria_id`',
                    'JreviewsCategory.tmpl AS `Category.tmpl`',
                    'JreviewsCategory.tmpl_suffix AS `Category.tmpl_suffix`',
                    'Directory.id AS `Directory.dir_id`',
                    'Directory.desc AS `Directory.title`',
                    'Directory.title AS `Directory.slug`',
                    'ListingType.id AS `ListingType.id`',
                    'ListingType.config AS `ListingType.config`'
    );

    var $joins = array(
        'INNER JOIN #__jreviews_categories AS JreviewsCategory ON Category.id = JreviewsCategory.id AND JreviewsCategory.option = "com_content"',
        'LEFT JOIN #__jreviews_directories AS Directory ON JreviewsCategory.dirid = Directory.id',
        'LEFT JOIN #__jreviews_criteria AS ListingType ON JreviewsCategory.criteriaid = ListingType.id'
    );

    function __construct()
    {
        parent::__construct();
    }

    /**
     * Checks if core category is setup for jReviews
     */
    function isJreviewsCategory($cat_id)
    {
        $query = "
            SELECT
                count(*)
            FROM
                #__jreviews_categories AS JreviewCategory
            WHERE
                JreviewCategory.id = " . (int) $cat_id . "
                AND
                JreviewCategory.option = 'com_content'
                AND
                JreviewCategory.criteriaid > 0
        ";


		return $this->query($query, 'loadResult');
    }

    function afterFind($results)
    {
        $id = 0;

        $Menu = ClassRegistry::getClass('MenuModel');

        $results = $Menu->addMenuCategory($results);

        $listingTypes = array();

        foreach($results AS $key=>$result)
        {
            if(isset($result['Criteria']))
            {
                $id = Sanitize::getInt($result['Criteria'],'criteria_id');
            }
            elseif(isset($result['ListingType'])) {

                $id = Sanitize::getInt($result['ListingType'],'id');
            }

            if($id > 0 && !isset($listingTypes[$id]) && isset($result['ListingType']['config']))
            {
                $listingTypes[$id] = json_decode($result['ListingType']['config'],true);
            }

            $results[$key]['ListingType']['config'] = Sanitize::getVar($listingTypes,$id,array());
        }

        return $results;
    }

    function afterSave($ret)
    {
        clearCache('','__data');

        clearCache('','views');
    }

    /**
    * Recursive method to generate array for jsTree implementation
    *
    */
    static function makeParentChildRelations(&$inArray, &$outArray, $currentParentId = 1)
    {
        if(!is_array($inArray)) {
            return;
        }

        if(!is_array($outArray)) {
            return;
        }

        foreach($inArray as $key => $item)
        {
            $item = (array) $item;

            $item['attr'] = array('id'=>$item['value']);

            $item['data'] = $item['text'];

            if($item['parent_id'] == $currentParentId)
            {
                $item['children'] = array();

                CategoryModel::makeParentChildRelations($inArray, $item['children'], $item['value']);

                if(empty($item['children'])) unset($item['children']);

                $outArray[] = $item;
            }
        }
    }

    /**
    * Returns array of cat id/title value pairs given a listing type used for creating a tree list
    * Used in search and listing controllers
    *
    */
    function getCategoryList($options = array())
    {
        $Access = Configure::read('JreviewsSystem.Access');

        $options = array_merge(array(
                'indent'=>true,
                'disabled'=>true,
                'pad_char' => '─'
            ),
            $options
        );

        $fields = array(
                'Category.id AS value',
                'Category.level AS level',
                'Category.parent_id AS parent_id',
                'JreviewsCategory.criteriaid'
        );

		// Add listing type config to query
		$listing_type_join = '';

        $dir_id = cleanIntegerCommaList(Sanitize::getVar($options, 'dir_id'));

        $type_id = cleanIntegerCommaList(Sanitize::getVar($options, 'type_id'));

        $cat_id = cleanIntegerCommaList(Sanitize::getVar($options, 'cat_id'));

        $parent_id = cleanIntegerCommaList(Sanitize::getVar($options, 'parent_id'));

        $alias = $parent_alias = null;

        if($cat_id)
        {
            $query = "
                SELECT
                    path
                FROM
                    #__categories
                WHERE
                    id IN (" . $cat_id . ")"
            ;

            $alias = $this->query($query, 'loadColumn');
        }

        if($parent_id)
        {
            $query = "
                SELECT
                    ParentCategory.path
                FROM
                    #__categories AS Category
                LEFT JOIN
                    #__categories AS ParentCategory ON ParentCategory.id = Category.parent_id
                WHERE
                    Category.id IN (". $parent_id . ")"
            ;

            $parent_alias = $this->query($query, 'loadColumn');
        }

        // Return current category when requesting children

        $conditions = Sanitize::getVar($options,'conditions');

        if(is_array($type_id))
        {
            $type_id = implode(',',$type_id);
        }

        $level = Sanitize::getInt($options, 'level');

		if(isset($options['listing_type']))
        {
			$fields[] = 'ListingType.config';

        	$listing_type_join = "
				LEFT JOIN
					#__jreviews_criteria AS ListingType ON JreviewsCategory.criteriaid = ListingType.id
			";
		}

		unset($options['listing_type']);

        Sanitize::getBool($options,'disabled') and $fields[] = 'IF(JreviewsCategory.criteriaid = 0,1,0) AS disabled';

        $fields[] = Sanitize::getBool($options,'indent')
            ?
            "CONCAT(REPEAT('─', IF(Category.level>0,Category.level - 1,1)), ' ',Category.title) AS text"
            :
            "Category.title AS text"
        ;

        # Category conditions

        $cat_condition = array();

        if($alias)
        {
            foreach ($alias AS $path)
            {
                $cat_condition[] = '(Category.path = ' . $this->Quote($path) . ' OR Category.path LIKE ' . $this->Quote($path . '/%').')';
            }
        }
        elseif($cat_id) {

            $cat_condition[] = 'Category.id IN (' . $cat_id . ')';
        }
        elseif($type_id != '' || $dir_id) {

            $cat_condition[]  = 'Category.id IN (
                SELECT
                    id
                FROM
                    #__jreviews_categories
                WHERE
                    `option` = "com_content"
                ' . ($type_id != '' ? " AND criteriaid IN (" . $type_id . ")" : '' ) . '
                ' . ($dir_id ? " AND dirid IN (" . $dir_id . ")" : '' ) . '
            )';
        }

        if($parent_alias)
        {
            foreach ($parent_alias AS $path)
            {
                // Apr 1, 2017 Prevent issue with empty paths when menu structure in Joomla needs rebuilding

                if ($path)
                {
                    $cat_condition[] = '(Category.path IN (' . $this->Quote($path) . ') OR Category.path LIKE ' . $this->Quote($path . '/%').')';
                }
            }
        }
        elseif($parent_id) {

            $cat_condition[] = 'Category.id IN (' . $parent_id . ')';
        }

		$query = "
			SELECT
                " . implode(',',$fields) . "
			FROM
				#__categories AS Category
            RIGHT JOIN
                #__jreviews_categories AS JreviewsCategory ON JreviewsCategory.id = Category.id AND JreviewsCategory.`option` = 'com_content'
			" . $listing_type_join . "
			WHERE
				Category.published = 1
				AND Category.access IN ( {$Access->getAccessLevels()}  )
                "
                . ($level ? " AND Category.level = $level " : 'AND Category.level > 0' )
                . " AND Category.extension = 'com_content' "
                . (!empty($cat_condition) ? " AND (" . implode(" OR ", $cat_condition) . ')' : '')
                . (!empty($conditions) ? " AND (" . implode(" AND " , $conditions) . ")" : '') . "
			ORDER BY
				Category.lft
		";

		$rows = $this->query($query, 'loadObjectList', 'value');

        if(isset($options['jstree']) && $options['jstree'])
        {
            $nodes = array();

            $first = current($rows);

            CategoryModel::makeParentChildRelations($rows, $nodes);

            return cmsFramework::jsonResponse($nodes);
        }

        $this->removeParentsWithoutListingType($rows, $level, $cat_id);

        $rows = $this->plgAfterCategoryList($rows);

        return $rows;
    }

    function removeParentsWithoutListingType(& $rows, $level, $cat_id)
    {
        $last = end($rows);

        $catIds = explode(',', $cat_id);

        if(!$level && count($rows) > 1 && $last->criteriaid == 0)
        {
            if(empty($cat_id) || ($cat_id && !in_array($last->value, $catIds)))
            {
                array_pop($rows);

                $this->removeParentsWithoutListingType($rows, $level, $cat_id);
            }
        }
    }

    /**
    * Category Manager, Theme Manager
    *
    * @param mixed $cat_id
    * @param mixed $offset
    * @param mixed $limit
    * @param mixed $total
    */
    function getReviewCategories($offset, $limit, &$total, $options = array())
    {
        $cat_id = Sanitize::getInt($options, 'cat_id');

        $alias = null;

        if($cat_id)
        {
            $query = "
                SELECT
                    path
                FROM
                    #__categories
                WHERE
                    id = " . $cat_id
            ;

            $alias = $this->query($query, 'loadResult');
        }

        $where = $alias ? "AND (Category.path = " . $this->Quote($alias) . " OR Category.path LIKE '{$alias}/%')" : '';

        // get the total number of records
        $query = "
            SELECT
                COUNT(*)
            FROM
                `#__jreviews_categories` AS jrcat
            LEFT JOIN
                #__categories AS Category ON Category.id = jrcat.id
            WHERE
                Category.extension = 'com_content'
                AND jrcat.option = 'com_content'"
            . $where
        ;

		$total = $this->query($query, 'loadResult');

        $query = "
            SELECT
                Category.id AS value, Category.title AS text, Category.level AS level,
                Category.metakey, Category.metadata,Category.language,
                Directory.desc AS dir_title, ListingType.title AS listing_type_title,
                JreviewCategory.*,Category.metadesc
            FROM
                #__categories AS Category
                    INNER JOIN #__jreviews_categories AS JreviewCategory ON JreviewCategory.id = Category.id AND JreviewCategory.`option` = 'com_content'
                    LEFT JOIN #__jreviews_criteria AS ListingType ON JreviewCategory.criteriaid = ListingType.id
                    LEFT JOIN #__jreviews_directories AS Directory ON JreviewCategory.dirid = Directory.id
                ,#__categories AS parent
            WHERE
                Category.extension = 'com_content'
                AND Category.lft BETWEEN parent.lft AND parent.rgt
                AND parent.id =  1
                " . $where . "
            ORDER
                BY Category.lft
            LIMIT {$offset}, {$limit}
        ";

		$rows = $this->query($query, 'loadObjectList');

        if(!$rows)
        {
            $rows = array();
        }

        return $rows;
    }

    /**
    * Used in category manager for new category setup
    *
    */
    function getReviewCategoryIds($params = array())
    {
        $dir_id = Sanitize::getVar($params, 'dir', array());

        $query = "
            SELECT
                id AS cat_id
            FROM
                #__jreviews_categories
            WHERE
                `option` = 'com_content'
                " . ($dir_id ? " AND dirid IN (" . $this->Quote($dir_id)     . ")" : '')
        ;

		$rows = $this->query($query, 'loadColumn');

        if(!$rows) {
            $rows = array();
        }
        return $rows;
    }

    /**
    * Used in category manager to get a list of categories not setup for JReviews
    *
    */
    function getNonReviewCategories()
    {
        $query = "
            SELECT
                node.id AS value, node.title AS text, node.level AS level
            FROM
                #__categories AS node,
                #__categories AS parent
            WHERE
                node.extension = 'com_content'
                AND node.lft BETWEEN parent.lft AND parent.rgt
                AND parent.id = 1
            ORDER
                BY node.lft
        ";

		$rows = $this->query($query, 'loadObjectList');

        return $rows;
    }

    /**
    * Directories Controller, Categories Controller
    * Generate the category tree array
    */
    function findTree($options = array())
    {
        $defaults = array(
            'pad_char' => '─'
        );

        $options = array_merge($defaults, $options);

        $Config = Configure::read('JreviewsSystem.Config');

        $Access = Configure::read('JreviewsSystem.Access');

        // This will force the generation of a different set of cache files for publisher and non-publisher user groups

        $options['is_publisher'] = $Access->isPublisher();

        $fields = array();

        $joins = array();

        $conditions = array();

        $group = array();

        $order = array();

        $having = array();

        /**
         * THE REPLACEMENT CODE STARTS HERE
         */

        $path = $forbidden_regex = '';

        $dir_id = Sanitize::getString($options,'dir_id');

        $cat_id = Sanitize::getInt($options,'cat_id');

        $level = Sanitize::getInt($options,'level');

        $limit = Sanitize::getVar($options,'limit',null);

        $offset = Sanitize::getVar($options,'offset',null);

        if($limit === 0) {

            $pagenav = " LIMIT 0";
        }
        else {

            $pagenav = $limit > 0 ? " LIMIT ". ($offset ? (int) $offset . ", " : '') . (int) $limit : '';
        }

        # Check for cached version only if it is not a paginated result

        if($pagenav == '')
        {
            $cache_file = S2CacheKey('jreviews_category_tree',$options);

            if($cache = S2Cache::read($cache_file,'_s2framework_core_'))
            {
                return $cache;
            }
        }

        if($cat_id)
        {
            $query = "SELECT level, path FROM #__categories WHERE id = " . $cat_id;

            $category = $this->query($query, 'loadRow');

            $level = $level ? $category[0] + 1 : null;

            $path = $category[1];
        }
        // Enforce category access level

        $query = '
            SELECT
                Category.path
            FROM
                #__categories AS Category
            WHERE
                Category.extension = "com_content"
                AND Category.access NOT IN ( '. $Access->getAccessLevels() . ')
        ';

        $forbiddenCats = $this->query($query, 'loadColumn');

        if($forbiddenCats)
        {
            $forbidden_regex = '^' . implode('|^', $forbiddenCats);
        }

        # LISTING COUNT LOGIC - it is not possible to hide empty categories if the listng count is disabled

        $show_listing_count = Sanitize::getBool($Config,'dir_cat_num_entries');

        $dir_category_hide_empty = Sanitize::getBool($Config,'dir_category_hide_empty');

        $JOIN_TYPE_HIDE_CATEGORIES = Sanitize::getBool($Config,'dir_category_hide_empty') ? ' INNER JOIN ' : ' LEFT JOIN ';

        $listing_count_field = ($show_listing_count || $dir_category_hide_empty)
                ?
                'IF(CategoryListingCount.listing_count > 0,CategoryListingCount.listing_count,0) AS `Category.listing_count`,'
                :
                '0 AS `Category.listing_count`,';

        $listing_count_subquery = ($show_listing_count || $dir_category_hide_empty)
                ?
                $JOIN_TYPE_HIDE_CATEGORIES . '
                (
                    SELECT
                        ParentCategory.id, COUNT(Listing.id) as listing_count
                    FROM
                        #__categories AS Category,
                        #__categories AS ParentCategory,
                        #__content AS Listing
                    WHERE
                         Category.lft BETWEEN ParentCategory.lft AND ParentCategory.rgt
                        AND Category.id IN (SELECT id FROM #__jreviews_categories WHERE `option` = "com_content")
                        AND Category.id = Listing.catid
                        AND Category.extension = "com_content"
                        AND Category.level > 0
                        ' . ( $path ? ' AND (Category.path = "' . $path . '" OR Category.path LIKE "' . $path . '/%")' : '' ) . '
                        ' .
                        ($Access->isPublisher()
                            ?
                            'AND Listing.state >= 0'
                            :
                            '
                            AND Listing.state = 1
                            AND ( Listing.publish_up = "' . NULL_DATE . '" OR Listing.publish_up <= "' . _END_OF_TODAY . '" )
                            AND ( Listing.publish_down = "' . NULL_DATE . '" OR Listing.publish_down >= "' . _TODAY . '" )
                            '
                        ) . '
                    GROUP BY
                        ParentCategory.id
                ) AS CategoryListingCount ON CategoryListingCount.id = Category.id'
                :
                '';

        $query  = '
            SELECT
                Category.id AS `Category.cat_id`,
                Category.title AS `Category.title`,
                Category.path AS `Category.path`,
                ' . $listing_count_field . '
                Category.alias AS `Category.slug`,
                Category.level AS `Category.level`,
                Category.params AS `Category.params`,
                Category.parent_id AS `Category.parent_id`,
                Category.metadesc AS `Category.metadesc`,
                Category.metakey AS `Category.metakey`,
                JreviewsCategory.criteriaid AS `Category.criteria_id`,
                JreviewsCategory.tmpl AS `Category.tmpl`,
                JreviewsCategory.tmpl_suffix AS `Category.tmpl_suffix`,
                Directory.id AS `Directory.dir_id`,
                Directory.desc AS `Directory.title`,
                Directory.title AS `Directory.slug`,
                ListingType.id AS `ListingType.id`,
                ListingType.config AS `ListingType.config`
            FROM
                #__categories AS Category
            ' . $listing_count_subquery . '

            INNER JOIN #__jreviews_categories AS JreviewsCategory ON Category.id = JreviewsCategory.id AND JreviewsCategory.option = "com_content"

            LEFT JOIN #__jreviews_directories AS Directory ON JreviewsCategory.dirid = Directory.id

            LEFT JOIN #__jreviews_criteria AS ListingType ON JreviewsCategory.criteriaid = ListingType.id

            WHERE 1 = 1 AND (
               Category.published = 1
               AND Category.extension = "com_content"
               ' . ( $path ? ' AND (Category.path = "' . $path . '" OR Category.path LIKE "' . $path . '/%")' : '' ) . '
               ' . ( $level ? ' AND Category.level <= ' . $level : '' ) . '
               ' . ( $dir_id ? ' AND Directory.id IN ( ' . $dir_id . ')' : '' ) . '
               ' . ( $forbidden_regex ? ' AND Category.path NOT REGEXP "' . $forbidden_regex  . '"' : '') . '
             )

            ORDER BY
                Category.lft
                # Removed directory from order by because it makes the query SLOW
                # Instead we run a much faster query below to re-order the output array
             '
             . $pagenav;

        $rows = $this->query($query,'loadAssocList','Category.cat_id');

        S2App::import('Model','menu','jreviews');

        $options['MenuModel'] = ClassRegistry::getClass('MenuModel');

        $menu_function = function($key, $row, & $output = array(), $options = array())
        {
            $Menu = $options['MenuModel'];

            if(isset($options['pad'])) {

                $row['Category']['level'] > 1 and $row['Category']['title'] = '└'.str_repeat(Sanitize::getVar($options,'pad_char','&nbsp;'),$row['Category']['level']-1) . $row['Category']['title'];
            }

            if(isset($options['menu_id']))
            {
                $row['Category']['menu_id'] = $Menu->getCategory(array('cat_id'=>$row['Category']['cat_id'],'dir_id'=>$row['Directory']['dir_id']));

                $row['Directory']['menu_id'] = $Menu->getDir($row['Directory']['dir_id']);

                $output[$row['Directory']['dir_id']][$row['Category']['cat_id']] = $row;
            }

            return $row;
        };

        $output = array();

        $rows = $this->__reformatArray($rows, $menu_function, $options, $output);

        $listingTypes = array();

        foreach($rows AS $cat)
        {
            $cat_id = $cat['Category']['cat_id'];

            // $cat['Category']['url'] = $MenuModel->getCategory($cat);

            if(!isset($listingTypes[$cat['ListingType']['id']]))
            {
                $listingType = json_decode($cat['ListingType']['config'],true);

                // Not necessary for the category tree and it takes up too much space/memory

                unset($listingType['relatedlistings'],$listingType['relatedreviews'],$listingType['userfavorites']);

                $listingTypes[$cat['ListingType']['id']] = $listingType;
            }

            $rows[$cat_id]['ListingType']['config'] = Sanitize::getVar($listingTypes,$cat['ListingType']['id'],array());
        }

        // The directory ids are the first level in the array so we need to re-order them alphabetically

        if(!empty($output)) {

            $reordered_output = array();

            $query = "
                SELECT id FROM #__jreviews_directories ORDER BY `desc`
            ";

            $dir_ids = $this->query($query,'loadColumn');

            foreach($dir_ids AS $dir_id)
            {
                if(isset($output[$dir_id]))
                {
                    $reordered_output[$dir_id] = $output[$dir_id];
                }
            }

            $rows = $reordered_output;
        }

        if($pagenav == '')
        {
            S2Cache::write($cache_file, $rows, '_s2framework_core_');
        }

        return $rows;
    }

    function findChildren($cat_id, $level = null)
    {
        return $this->findTree(array('cat_id'=>$cat_id, 'level'=>$level));
    }

    function findParents($cat_id)
    {
        $cache_file = S2CacheKey('jreviews_parents', $cat_id);

        if($cache = S2Cache::read($cache_file,'_s2framework_core_'))
        {
            return $cache;
        }

        $query = "
            SELECT
                path
            FROM
                #__categories
            WHERE
                id IN (" . $this->Quote($cat_id) . ")"
        ;

        $paths = $this->query($query, 'loadColumn');

        if(!$paths)
        {
            return false;
        }

        $ancestors = array();

        foreach ($paths AS $path)
        {
            $ancestor = array($this->Quote($path));

            $parts = explode('/', $path);

            while($parts)
            {
                array_pop($parts);

                if(empty($parts)) break;

                array_unshift($ancestor, $this->Quote(implode('/', $parts)));
            }

            $ancestors = array_merge($ancestors,$ancestor);
        }

        $query = "
            SELECT
                ParentCategory.id AS `Category.cat_id`,
                ParentCategory.lft AS `Category.lft`,
                ParentCategory.title AS `Category.title`,
                IF(JreviewsCategory.page_title <> '',JreviewsCategory.page_title,ParentCategory.title) AS `Category.title_seo`,
                JreviewsCategory.title_override AS `Category.title_override`,
                ParentCategory.alias AS `Category.slug`,
                ParentCategory.level AS `Category.level`,
                ParentCategory.published AS `Category.published`,
                ParentCategory.access AS `Category.access`,
                ParentCategory.params AS `Category.params`,
                ParentCategory.parent_id AS `Category.parent_id`,
                ParentCategory.metadesc AS `Category.metadesc`,
                ParentCategory.metakey AS `Category.metakey`,
                IF(ParentCategory.metadesc <> '' AND JreviewsCategory.desc_override =1,ParentCategory.metadesc,ParentCategory.description) AS `Category.description`,
                JreviewsCategory.criteriaid AS `Category.criteria_id`,
                JreviewsCategory.tmpl AS `Category.tmpl`,
                JreviewsCategory.tmpl_suffix AS `Category.tmpl_suffix`,
                JreviewsCategory.dirid AS `Directory.dir_id`,
                Directory.title AS `Directory.slug`,
                ListingType.config AS `ListingType.config`
            FROM
                #__categories AS ParentCategory
            INNER JOIN
                #__jreviews_categories AS JreviewsCategory ON JreviewsCategory.id = ParentCategory.id AND JreviewsCategory.`option` = 'com_content'
            LEFT JOIN
                #__jreviews_directories AS Directory ON JreviewsCategory.dirid = Directory.id
            LEFT JOIN
                #__jreviews_criteria AS ListingType ON ListingType.id = JreviewsCategory.criteriaid
            WHERE
                ParentCategory.path IN ( " . implode(',', $ancestors) . ")
            ORDER BY
                ParentCategory.lft
        ";

        $rows = $this->query($query, 'loadObjectList');

        $rows = $this->__reformatArray($rows);

        S2Cache::write($cache_file, $rows, '_s2framework_core_');

        return $rows;
    }

    function getParentList($catId, $options = array())
    {
        $defaults = array(
            'pad_char' => '─'
        );

        $params = array_merge($defaults, $options);

        $categories = $this->findParents($catId);

        $list = array();

        foreach ($categories AS $cat)
        {
            if ($cat['Category']['level'] > 1)
            {
                $cat['Category']['title'] = '└'.str_repeat(Sanitize::getVar($params,'pad_char','&nbsp;'),$cat['Category']['level']-1) . $cat['Category']['title'];
            }

            $list[$cat['Category']['cat_id']] = array(
                'value' => $cat['Category']['cat_id'],
                'level' => $cat['Category']['level'],
                'parent_id' => $cat['Category']['parent_id'],
                'criteriaid' => $cat['Category']['criteria_id'],
                'text' => $cat['Category']['title']
            );

            $list[$cat['Category']['cat_id']] = (object) $list[$cat['Category']['cat_id']];
        }

        return $list;
    }

    function getChildrenList($catId, $options = array())
    {
        $defaults = array(
            'pad_char' => '─'
        );

        $params = array_merge($defaults, $options);

        $categories = $this->findChildren($catId);

        $list = array();

        foreach ($categories AS $cat)
        {
            if ($cat['Category']['level'] > 1)
            {
                $cat['Category']['title'] = '└'.str_repeat(Sanitize::getVar($params,'pad_char','&nbsp;'),$cat['Category']['level']-1) . $cat['Category']['title'];
            }

            $list[$cat['Category']['cat_id']] = array(
                'value' => $cat['Category']['cat_id'],
                'level' => $cat['Category']['level'],
                'parent_id' => $cat['Category']['parent_id'],
                'criteriaid' => $cat['Category']['criteria_id'],
                'text' => $cat['Category']['title']
            );

            $list[$cat['Category']['cat_id']] = (object) $list[$cat['Category']['cat_id']];
        }

        return $list;
    }

    function isLeaf($cat_id)
    {
        $query = "
            SELECT
                count(*)
            FROM
                #__categories AS Category
            WHERE
                Category.parent_id = " . (int) $cat_id . "
                AND
                Category.extension = 'com_content'
        ";

		return !$this->query($query, 'loadResult');
    }

    function getReviewCount($ids)
    {
       $query = "
            SELECT
                COUNT(*)
            FROM
                #__jreviews_comments AS Review
            INNER JOIN
                #__content AS Content ON Content.id = Review.pid
            WHERE
                Review.mode = 'com_content'
                AND Content.catid IN ( ".cleanIntegerCommaList($ids)." )
        ";

        $reviewCount = $this->query($query,'loadResult');

        return $reviewCount;
    }
}
