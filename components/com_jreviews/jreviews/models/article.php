<?php
/**
 * JReviews - Reviews Extension
 * Copyright (C) 2010-2015 ClickFWD LLC
 * This is not free software, do not distribute it.
 * For licencing information visit https://www.jreviews.com
 * or contact sales@jreviews.com
**/

defined( 'MVC_FRAMEWORK') or die( 'Direct Access to this location is not allowed.' );

switch(_CMS_NAME)
{
    /**********************************
     *          Joomla                *
     **********************************/

	case 'joomla':

		class ArticleModel extends MyModel  {

			const _ARTICLE_TABLE = '#__content';

			const _ARTICLE_ID = 'id';

			const _ARTICLE_TITLE = 'title';

			const _ARTICLE_SUMMARY = 'introtext';

			const _ARTICLE_DESCRIPTION = 'introtext';

			var $name = 'Article';

			var $useTable = '#__content AS Article';

			var $primaryKey = 'Article.article_id';

			var $realKey = 'id';

			var $fields = array(
				'Article.id AS `Article.article_id`',
				'Article.title AS `Article.title`',
				'Article.introtext AS `Article.summary`',
				'Article.fulltext AS `Article.description`',
				'Article.catid AS `Article.cat_id`',
		        'Article.alias AS `Article.slug`',
		        'Category.alias AS `Category.slug`'
			);

			var $joins = array(
		        "LEFT JOIN #__categories AS Category ON Article.catid = Category.id"
			);

			function __construct()
		    {
				parent::__construct();
			}

			function articleUrl($listing)
		    {
				return $this->Routes->content('',$listing,array(),'',false);
			}
		}

		break;

    /**********************************
     *          Wordpress             *
     **********************************/

	case 'wordpress':

		class ArticleModel extends MyModel  {

			const _ARTICLE_TABLE = '#__posts';

			const _ARTICLE_ID = 'ID';

			const _ARTICLE_TITLE = 'post_title';

			const _ARTICLE_SUMMARY = 'post_excerpt';

			const _ARTICLE_DESCRIPTION = 'post_content';

			var $name = 'Article';

			var $useTable = '#__posts AS Article';

			var $primaryKey = 'Article.listing_id';

			var $realKey = 'ID';

			var $fields = array(
				'Article.ID AS `Article.article_id`',
				'Article.post_title AS `Article.title`',
				'Article.post_excerpt AS `Article.summary`',
				'Article.post_content AS `Article.description`'
			);

			function __construct()
		    {
				parent::__construct();
			}

			function articleUrl($listing)
		    {
				return $this->Routes->content('',$listing,array(),'',false);
			}
		}

		break;
}