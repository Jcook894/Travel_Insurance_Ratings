<?php
/**
 * JReviews - Reviews Extension
 * Copyright (C) 2010-2015 ClickFWD LLC
 * This is not free software, do not distribute it.
 * For licencing information visit https://www.jreviews.com
 * or contact sales@jreviews.com
**/

defined( 'MVC_FRAMEWORK') or die( 'Direct Access to this location is not allowed.' );

class BaseRepository extends S2Component {

    protected $queryData = array('fields' => array(), 'conditions' => array(), 'joins' => array(), 'order' => array(), 'group' => array(), 'having' => array());

    protected $queryOptions;

    protected $searchOptions;

    protected $countColumn = '*';

    protected $stopAfterFindModels;

    protected $callbacks = null;

    protected $error;

    static $urlSeparator = "_";

    protected function getModel()
    {
        return $this->{$this->modelName};
    }

    function get($results, $columns = '')
    {
        if($columns == '') return $results;

        $modelName = $this->getModel()->name;

        $output = array();

        $columns = explode(',', str_replace(' ', '', $columns));

        foreach($results AS $result)
        {
            $row = array_intersect_key($result[$modelName], array_flip($columns));

            $output[] = count($row) > 1 ? $row : array_shift($row);
        }

        return $output;
    }

    function data(& $queryData)
    {
        $this->queryData = & $queryData;

        return $this;
    }

    function clearQueryData()
    {
        $this->queryData = array('fields' => array(), 'conditions' => array(), 'joins' => array(), 'order' => array(), 'group' => array(), 'having' => array());

        return $this;
    }

    function setQueryData($queryData)
    {
        $this->queryData = $queryData;
    }

    function addQueryData($queryData)
    {
        $this->queryData = array_merge_recursive($this->queryData, $queryData);

        return $this;
    }

    function getQueryData()
    {
        return $this->queryData;
    }

    function getConditions()
    {
        return $this->queryData['conditions'];
    }

    function queryOptions($queryOptions)
    {
        $this->queryOptions = $queryOptions;

        return $this;
    }

    function searchOptions($searchOptions)
    {
        $this->searchOptions = $searchOptions;

        return $this;
    }

    function fieldReset($fields = array())
    {
        $this->getModel()->fields = array();

        if(!empty($fields))
        {
            $this->fields($fields);
        }

        return $this;
    }

    function fields($fields)
    {
    	if($fields)
    	{
    		if(is_array($fields))
    		{
    			$this->queryData['fields'] = array_merge($this->queryData['fields'], $fields);
    		}
    		else {

        		$this->queryData['fields'][] = $fields;
    		}
    	}

        return $this;
    }

    function joins($joins)
    {
    	if($joins)
    	{
    		if(is_array($joins))
    		{
    			$this->queryData['joins'] = array_merge($this->queryData['joins'], $joins);
    		}
    		else {

        		$this->queryData['joins'][] = $joins;
    		}
    	}

        return $this;
    }

    function where($conditions)
    {
    	if($conditions)
    	{
    		if(is_array($conditions))
    		{
    			$this->queryData['conditions'] = array_merge($this->queryData['conditions'], $conditions);

    		}
    		else {

        		$this->queryData['conditions'][] = '(' . $conditions . ')';
    		}
    	}

        return $this;
    }

    function whereIn($field, $list)
    {
        $list = is_array($list) ? implode(',', $list) : $list;

        $this->where($field . ' IN (' . $list . ')' );

        return $this;
    }

    function order($order)
    {
    	if($order)
    	{
    		if(is_array($order))
    		{
    			$this->queryData['order'] = array_merge($this->queryData['order'], $order);
    		}
    		else {

        		$this->queryData['order'][] = $order;
    		}
    	}

        return $this;
    }

    function without($models)
    {
        $this->stopAfterFindModels = explode(',', str_replace(' ', '', $models));

        return $this;
    }

    function callbacks($callbacks = '')
    {
        $this->callbacks = explode(',', str_replace(' ', '', $callbacks));

        return $this;
    }

   function offset($offset)
    {
        $this->queryData['offset'] = $offset;

        return $this;
    }

    function limit($limit)
    {
        $this->queryData['limit'] = $limit;

        return $this;
    }

    function sessionCache($state)
    {
        $this->queryData['session_cache'] = $state;

        return $this;
    }

    function countColumn($column)
    {
        $this->countColumn = $column;

        return $this;
    }

    function count()
    {
        $queryData = $this->getQueryData();

        $count = $this->getModel()->findCount($queryData, $this->countColumn);

        return $count;
    }

    function setError($error)
    {
    	$this->error = $error;
    }

    function getError()
    {
    	return $this->error;
    }
}