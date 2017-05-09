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

class CategoriesController extends MyController {

    var $uses = array('category','criteria','directory','jreviews_category');

    var $helpers = array('html','form','admin/paginator');

    var $components = array('config', 'access');

    var $autoRender = false;

    var $autoLayout = false;


    function beforeFilter()
    {
        $this->Access->init($this->Config);

        parent::beforeFilter();
    }

    function index()
    {
        $this->action = 'index';

        $filters = Sanitize::getVar($this->params,'filter');

        $cat_id = Sanitize::getInt($filters, 'cat_id');

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

    function create()
    {
        $this->name = 'categories';

        $this->autoRender = true;

        $limit =  Sanitize::getInt($this->params,'limit',cmsFramework::getConfig('list_limit') );

        $limitstart =  Sanitize::getInt($this->params,'limitstart');

        $this->set(array(
            'limit'=>$limit,
            'limitstart'=>$limitstart,
            'criterias'=>$this->Criteria->getSelectList(),
            'directories'=>$this->Directory->getSelectList(),
            'review_categories'=>$this->Category->getReviewCategoryIds(),
            'categories'=>$this->Category->getNonReviewCategories()
        ));
    }

    function edit()
    {
        $this->name = 'categories';

        $this->autoRender = true;

        $cat_id =  Sanitize::getInt( $this->params,'id');

        $category = $this->Category->findRow(
            array(
                'conditions'=>array('Category.' . CategoryModel::_CATEGORY_ID . ' = ' . $cat_id)
            ),array()
        );

        $criteria = $this->Criteria->getSelectList(array('id' => $category['Category']['criteria_id']));

        $criteria = !$category['Category']['criteria_id'] ? array() : (array) end($criteria);

        $this->set(
            array(
                'criteria'=>$criteria,
                'directories'=>$this->Directory->getSelectList(),
                'category'=>$category
            )
        );
    }

    function updateCategories($data) {

        // Update database
        if(isset($this->data['Category']['id']))
        {
            if(is_array($this->data['Category']['id'][0])){

                $this->data['Category']['id'] = $this->data['Category']['id'][0];
            }

            foreach ($this->data['Category']['id'] as $id)
            {
                $query = "
                    INSERT INTO #__jreviews_categories
                        (id, criteriaid,dirid,`option`)
                        VALUES ("
                            .(int)$id.","
                            .(int)$this->data['Category']['criteriaid'].","
                            .(int)$this->data['Category']['dirid']."
                            ,'com_content'
                        )
                    ON DUPLICATE KEY UPDATE
                        dirid = ".(int)$this->data['Category']['dirid']."
                ";

                if($this->Category->query($query) === false){

                    return false;
                }
            }
        }

        clearCache('', 'core');

        return true;
    }

    function update()
    {
        $id = Sanitize::getInt($this->params,'id');

        $row = $this->JreviewsCategory->findRow(array(
            'fields'=>array('Directory.desc AS `Directory.title`'),
            'joins'=>array('LEFT JOIN #__jreviews_directories AS Directory ON Directory.id = JreviewsCategory.dirid'),
            'conditions'=>array('JreviewsCategory.id = ' . $id
        )));

        return cmsFramework::jsonResponse($row);
    }

    function seo()
    {
        $filters = Sanitize::getVar($this->params,'filter');

        $cat_id = Sanitize::getInt($filters, 'cat_id');

        $total = 0;

        $rows = $this->Category->getReviewCategories($this->offset, $this->limit, $total, compact('cat_id'));

        $this->set(array(
            'rows'=>$rows,
            'categories'=>$this->Category->getCategoryList(array('disabled'=>false)),
            'cat_id'=>$cat_id,
            'pagination'=>array('total'=>$total)
        ));

        return $this->render();
    }

    function _save()
    {
        $response = array('success'=>false,'str'=>array());

        $task = Sanitize::getString($this->data,'task');

    	$this->action = 'index';

        $cat_ids = array();

        // Begin form validation
        if (!isset($this->data['Category']['criteriaid']) || Sanitize::getString($this->data['Category'],'criteriaid') == '') {

            $response['str'][] = 'CATEGORY_VALIDATE_LISTING_TYPE';
        }

        if (!isset($this->data['Category']['dirid']) || !(int)$this->data['Category']['dirid']) {

            $response['str'][] = 'CATEGORY_VALIDATE_DIRECTORY';
        }

        if (!isset($this->data['Category']['id']))   {

            $response['str'][] = 'CATEGORY_VALIDATE_CATEGORY';
        }

        if (count($response['str']) > 0) {

            return cmsFramework::jsonResponse($response);
        }

        // Update database
        if(!$this->updateCategories($this->data)) {

            $response['str'] = s2Messages::submitErrorDb();

            return cmsFramework::jsonResponse($response);
        }

        $response['success'] = true;

        if($task == 'edit') {

            return cmsFramework::jsonResponse($response);
        }

        $response['isNew'] = true;

        $response['html'] = $this->index();

        $response['id'] = $this->data['Category']['id'];

        return cmsFramework::jsonResponse($response);
    }

    function _saveSeo()
    {
        $category = Sanitize::getVar($this->data,'seo');

        if($category) {

            $category = current($category);

            $this->JreviewsCategory->store($category);

            $this->Category->store($category);
        }
    }

    function _delete()
    {
        $response = array('success'=>false,'str'=>array());

        $ids = Sanitize::getVar($this->params,'cid');

        if(empty($ids)) {

            return cmsFramework::jsonResponse($response);
        }

        $reviewCount = $this->Category->getReviewCount($ids);

        if($reviewCount > 0){

            $response['str'][] = 'CATEGORY_REMOVE_NOT_EMPTY';

            return cmsFramework::jsonResponse($response);
        }

        # Delete listing and all associated records
        $deleted = $this->JreviewsCategory->delete('id',$ids,"`option`='com_content'");

        if($deleted)
        {
            $response['success'] = true;

            clearCache('', 'core');
        }

        return cmsFramework::jsonResponse($response);

    }


}
