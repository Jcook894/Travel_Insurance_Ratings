<?php
/**
 * jReviews - Reviews Extension
 * Copyright (C) 2006-2009 Alejandro Schmeichler
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice
 **/

defined( 'MVC_FRAMEWORK') or die( 'Direct Access to this location is not allowed.' );

class S2Model extends S2Object{
	
	var $useTable;
	var $primaryKey;
	var $className;
	var $_db;
	var $_user;
	
	var $fields = array();	
	var $conditions = array();
	var $joins = array();
	var $group = array();
	var $order = array();
	var $limit;
	var $offset;
	var $having = array();
	
	var $runAfterFind = true;

	var $validateErrors = array();
				
	function __construct() {
		# Adds CMS DB and Mainframe methods
		cmsFramework::init($this);
	}
	
	function emptyModel() {
		$model = array();
		foreach($this->fields AS $field) {
			$key = str_replace('`','',end(explode('.',substr($field,strpos($field,' AS ')))));
			
			$model[$this->name][$key] = null;
		}
		return $model;
	}
	
	function findAll($queryData, $afterFind = true) {
		
		return $this->find('all',$queryData, $afterFind);
	}
	
	function findAllCache($queryData, $afterFind = true) {
		
		return $this->find('all',$queryData, $afterFind, true);
	}

	function findOne($queryData, $afterFind = true) {
		
		return $this->find('one',$queryData, $afterFind);
	}
	
	function findOneCache($queryData, $afterFind = true) {
		
		return $this->find('one',$queryData, $afterFind, true);
	}

	function findRow($queryData = array(), $afterFind = true) {
		
		$rows = $this->find('all',$queryData, $afterFind);

		if(!$rows || empty($rows)) {
			
			return false;
		}

		return current($rows);
				
	}

	function findRowCache($queryData = array(), $afterFind = true) {
		
		$rows = $this->find('all',$queryData, $afterFind, true);

		if(!$rows || empty($rows)) {
			
			return false;
		}

		return current($rows);
				
	}	
	
	function findCount($queryData = array(), $countField = '*', $cache = false) 
	{
		$queryData = $this->__mergeArrays($queryData);
					
		if($cache === true && !Configure::read('Cache.disable') && Configure::read('Cache.check') === true) {
			
			$cache_key = md5(cmsFramework::getConfig('secret').serialize($queryData).$countField);
	
			$count = S2Cache::read($cache_key);
			
			if(false !== $count) {
				return $count;
			}		
		}
		
		$query = 'SELECT COUNT(' . $countField . ')'
		. "\n FROM " . $this->useTable
		. ( !empty($queryData['joins']) ? "\n". implode("\n", $queryData['joins']) : '')
		. ( !empty($queryData['conditions']) ? "\n WHERE 1 = 1 AND ( \n   ". implode("\n   AND ", $queryData['conditions']) . "\n )" : '') 
//        . ( !empty($queryData['groupCount']) ? "\n GROUP BY ". implode(',', $queryData['groupCount']) : '')
		. ( !empty($queryData['having']) ? "\n HAVING ". implode(' AND ', $queryData['having']) : '')
		;

		$this->_db->setQuery($query);

		$count = $this->_db->loadResult();

		// Log message
		$message[] = '*********' . get_class($this) . ' | findCount | Count: '. $count;
		$message[] = $this->_db->getQuery();
		$message[] = $this->_db->getErrorMsg();					
		appLogMessage($message, 'database');			
				
		if($cache === true && !Configure::read('Cache.disable') && Configure::read('Cache.check') === true) {
			S2Cache::write($cache_key,$count);
		}
				
		return $count;	
	}	
	
	function findCountCache($queryData = array(), $countField = '*') {
		return $this->findCount($queryData, $countField, true);
	}
	
	function find($type, $queryData, $afterFind, $cache = false) {

		$queryData = $this->__mergeArrays($queryData);
		
		if($cache === true && !Configure::read('Cache.disable') && Configure::read('Cache.check') === true)		
		{
			$cache_key = md5(cmsFramework::getConfig('secret').$type.serialize($queryData).$afterFind);
			
			$rows = S2Cache::read($cache_key);
			
			if(false !== $rows) {
				return $rows;
			}
		}
		
		$query = "SELECT " . implode (",\n",$queryData['fields'])
		. "\n FROM " . $this->useTable
		. ( !empty($queryData['joins']) ? "\n". implode("\n", $queryData['joins']) : '') 
		. ( !empty($queryData['conditions']) ? "\n WHERE 1 = 1 AND ( \n   ". implode("\n   AND ", $queryData['conditions']) . "\n )" : '') 
		. ( !empty($queryData['group']) ? "\n GROUP BY ". implode(',', $queryData['group']) : '') 
		. ( !empty($queryData['having']) ? "\n HAVING ". implode(' AND ', $queryData['having']) : '') 
		. ( !empty($queryData['order']) ? "\n ORDER BY ". implode(',', $queryData['order']) : '') 
		. ( !empty($queryData['limit']) ? "\n LIMIT ". (Sanitize::getInt($queryData,'offset',null) ? $queryData['offset'] . ", " : ''). $queryData['limit'] : '')
		;


		$this->_db->setQuery($query);

		$message = array();
		$message[] = '*********' . get_class($this) . ' | find';
		$message[] = $this->_db->getQuery();		
		appLogMessage($message, 'database' );

		switch($type) {
			case 'all':
				$rows = $this->_db->loadObjectList();
				$rows = $this->__reformatArray($rows);				
				break;
			case 'one':
				$rows = $this->_db->loadResult();
				break;
		}

		$message = array();
		
		if($this->_db->getErrorMsg()) {
			$message[] = '*********' . get_class($this) . ' | find ERROR';
			$message[] = $this->_db->getErrorMsg();					
			appLogMessage($message, 'database' );
		}
		
		if($type != 'one' && method_exists($this,'afterFindHook')) {
			$rows = $this->afterFindHook($rows);
		}

		if($type != 'one' && method_exists($this,'afterFind') && $afterFind) {
			$rows = $this->afterFind($rows);
		}
		
		if($type != 'one' && method_exists($this,'afterAfterFindHook')) {
			$rows = $this->afterAfterFindHook($rows);
		}		
		
		if($cache === true && !Configure::read('Cache.disable') && Configure::read('Cache.check') === true) {
			S2Cache::write($cache_key,$rows);
		}

		return $rows;
	}
	
	/**
	 * Removes a field or fields if an array is passed from the query fields
	 */	
	function modelUnbind($fields) 
	{		
		if(is_array($fields)) {
			foreach($fields AS $field) {
				$this->modelUnbind($field);
			}
		
		} else {
			$key = array_search($fields,$this->fields);
			if(false != $key || 0 === $key) {
				unset($this->fields[$key]);
			}				
		}	
	}
	
	function init() {
		
		$model = array();
		
		foreach($this->fields AS $field) {
			$keys = explode('.', end(split(' AS ',str_replace('`','',$field))));
			$model[$keys[0]][$keys[1]] = null;		
		}
		
		return $model;
		
	}
	
	
	function store(&$data, $updateNulls = false) {
				
		if(method_exists($this,'beforeSave')) {
			$this->beforeSave();
		}		
							
		$table = substr($this->useTable,0,strpos($this->useTable,' AS'));
		
		$primaryKeyString = isset($this->realKey) ? $this->realKey : $this->primaryKey;
		
		$keyName = end(explode('.',str_replace('`','',$primaryKeyString)));
		
		if( isset($data[$this->name][$keyName]) &&  $data[$this->name][$keyName] > 0) {

			$ret = $this->update( $table, $this->name, $data, $keyName, $updateNulls );
		
		} else {

			$ret = $this->insert( $table, $this->name, $data, $keyName );
		}

		if( !$ret ) {
			$this->_error = strtolower(get_class( $this ))."::store failed <br />" . $this->_db->getErrorMsg();
		} 
		
		$this->data = &$data;

		if(method_exists($this,'afterSave')) {
			$this->afterSave($ret);
		}
		
		if(method_exists($this,'afterSaveHook')) {
			$this->afterSaveHook($ret);
		}
							
		clearCache('', 'views');
		clearCache('', '__data');
		
		return $ret;

	}

	function storeModel($table, $alias, &$data, $keyName = null) {
		
		$this->data = &$data;
		
		if(method_exists($this,'beforeSave')) {
			$this->beforeSave();
		}		
		
		if(isset($data[$alias][$keyName]) && $data[$alias][$keyName] > 0) {
			
			$ret = $this->update( $table, $alias, $data, $keyName);
			
		} else {
			
			$ret = $this->insert( $table, $alias, $data, $keyName);
			
		}
		
		$this->data = &$data;
		
		if(method_exists($this,'afterSave')) {
			$this->afterSave($ret);
		}
		
/*		if(method_exists($this,'afterSaveHook')) {
			$this->afterSaveHook($ret);
		}	*/	

		clearCache('', 'views');
		clearCache('', '__data');
				
		return $ret;		
		
	}
	
	function delete($keyName, $values, $condition = '') {
		
		$table = substr($this->useTable,0,strpos($this->useTable,' AS'));
		
		$fmtsql = "DELETE FROM $table WHERE %s IN ( %s ) %s";
		
		$condition = $condition != '' ? "AND $condition" : '';
		
		$this->_db->setQuery( sprintf( $fmtsql, $keyName, is_array($values) ? implode( ",", $values ) : $values, $condition) );

		$delete = $this->_db->query();
		
		$message[] = '*********' . get_class($this) . ' | Delete';
		$message[] = $this->_db->getQuery();
		$message[] = $this->_db->getErrorMsg();							
		appLogMessage($message, 'database');	
		
		clearCache('', 'views');
		clearCache('', '__data');			
		
		return $delete;
		
	}
	
	function insert( $table, $alias, &$data, $keyName = null) 
	{		
		$fmtsql = "INSERT INTO $table ( %s ) VALUES ( %s ) ";

		$alias = inflector::camelize($alias);
		
		$fields = array();

		foreach ($data[$alias] as $k => $v) {
			if (is_array($v) OR is_object($v) OR $v === NULL OR $k[0] == '_') continue;
			$fields[] = "`$k`";
			$values[] = $this->Quote($v);
		}
		
		if (!isset($fields)) die ('class database method insertObject - no fields');
		
		$this->_db->setQuery( sprintf( $fmtsql, implode( ",", $fields ), implode( ",", $values ) ) );

		
		$insert = $this->_db->query();
		
		$message[] = '*********' . get_class($this) . ' | Insert';
		$message[] = $this->_db->getQuery();
		$message[] = $this->_db->getErrorMsg();							
		appLogMessage($message, 'database');
		
		if (!$insert) 
		{			
			return false;
		}
		
		$id = $this->_db->insertid();
		
		if ($keyName && $id) {
			$data[$alias][$keyName] = $id;			
		}
		
		$data['insertid'] = $id;
		
		return true;
	}
	
	function replace( $table, $alias, &$data, $keyName = null) 
	{		
		$fmtsql = "REPLACE INTO $table ( %s ) VALUES ( %s ) ";

		$alias = inflector::camelize($alias);
		
		$fields = array();

		foreach ($data[$alias] as $k => $v) {
			if (is_array($v) OR is_object($v) OR $v === NULL OR $k[0] == '_') continue;
			$fields[] = "`$k`";
			$values[] = $this->Quote($v);
		}
		
		if (!isset($fields)) die ('class database method insertObject - no fields');
		
		$this->_db->setQuery( sprintf( $fmtsql, implode( ",", $fields ), implode( ",", $values ) ) );

		$replace = $this->_db->query();
		
		$message[] = '*********' . get_class($this) . ' | Replace';
		$message[] = $this->_db->getQuery();
		$message[] = $this->_db->getErrorMsg();							
		appLogMessage($message, 'database');
		
		if (!$replace) 
		{			
			return false;
		}
		
		$id = $this->_db->insertid();
		
		if ($keyName && $id) $data[$alias][$keyName] = $id;
		
		return true;
	}	
	
	function update( $table, $alias, &$data, $keyName, $updateNulls=true ) 
	{
		$fmtsql = "UPDATE $table SET %s WHERE %s";

		$tmp = array();
		
		foreach ($data[$alias] as $k => $v) 
		{
			if (is_array($v) OR is_object($v) OR $k[0] == '_' OR ($v === null AND !$updateNulls)) continue;

			 // use primary key to locate update record
			 if( $k == $keyName ) 
			 {
				$where = "$keyName= " . $this->Quote( $v );
				continue;
			}

			if ($v === null)
			{
				if ($updateNulls) {
					$val = 'NULL';
				} else {
					continue;
				}
			} else {
				$val = $this->Quote($v);
			}
			
			$tmp[] = "`$k`= $val";

		}

		if (!isset($tmp)) return true;
		
		if (!isset($where)) die ('Model class update method - no key value');
		
		$this->_db->setQuery( sprintf( $fmtsql, implode( ",", $tmp ) , $where ) );

		$update = $this->_db->query();
		
		$message[] = '*********' . get_class($this) . ' | Update';
		$message[] = $this->_db->getQuery();
		$message[] = $this->_db->getErrorMsg();							
		appLogMessage($message, 'database');
		
		if (!$update) 
		{			
			return false;
		}
		
		return true;
	}
	
	function move( $direction, $where='' ) {
		
		$table = substr($this->useTable,0,strpos($this->useTable,' AS'));
				
		$compops = array (-1 => '<', 0 => '=', 1 => '>');
		$relation = $compops[($direction>0)-($direction<0)];
		$ordering = ($relation == '<' ? 'DESC' : 'ASC');
		$k = $this->realKey;
		$o1 = $this->Result[$this->name]['ordering'];
		$k1 = $this->Result[$this->name][$k];

		$sql = "SELECT $k, ordering FROM $table WHERE ordering $relation $o1";
		
		$sql .= ($where ? "\n AND $where" : '').' ORDER BY ordering '.$ordering.' LIMIT 1';
		
		$this->_db->setQuery( $sql );
		
		if ($row = $this->_db->loadObjectList()) {
			$row = current($row);
			$o2 = $row->ordering;
			$k2 = $row->$k;
			$sql = "UPDATE $table SET ordering = (ordering=$o1)*$o2 + (ordering=$o2)*$o1 WHERE $k = $k1 OR $k = $k2";
			$this->_db->setQuery($sql);
			$this->_db->query();
		}
		
		clearCache('', 'views');
		clearCache('', '__data');		
	}

	
	function reorder( $where='', $cfid=null, $order=null ) {
		
		$table = substr($this->useTable,0,strpos($this->useTable,' AS'));
				
		$k = $this->realKey;
		if ($table == "#__content_frontpage") $order2 = ", content_id DESC";
		else $order2 = "";
		
		if (!is_null($cfid) AND !is_null($order)) {
			foreach ($cfid as $i=>$id) {
				$o = intval($order[$i]);
				$set[] = "(id=$id)*$o";
			}
			$sql = "UPDATE $table SET ordering = ".implode(' + ', $set).' WHERE id IN ('.implode(',', $cfid).')';
			$this->_db->setQuery($sql);
			$this->_db->query();
		}
		
		$sql = "SELECT $k, ordering FROM {$table} "
		. ($where ? "\n WHERE $where" : '') . "\n ORDER BY ordering$order2";
		$this->_db->setQuery($sql);
		
		if (!$rows = $this->_db->loadObjectList()) {
			$this->_error = $this->_db->getErrorMsg();
			return false;
		}
		$i = 1;
		
		foreach ($rows as $row) {
			$sql = "UPDATE $table SET ordering=$i WHERE $k = ".$row->$k;
			$this->_db->setQuery($sql);
			$this->_db->query();
			$i++;
		}
		

		clearCache('', 'views');
		clearCache('', '__data');
				
		return true;
	}	
	
	function validateInput($value, $name, $type, $label, $required, $regex = '') {

		if ( $required && trim(strip_tags($value)) == "" ) 
		{
			$this->validateSetError($name, $label);

		} elseif (trim(strip_tags($value)) && $regex != "") 
		{
			if(!eregi($regex,$value)) 
			{
				$this->validateSetError($name, $label);
			}

		}
	}

	function validateSetError($name, $label) 
	{
		$this->validateErrors[] = array("input"=>$name, "message"=>$label);
	}

	function validateGetError() {

		$errors = $this->validateErrors;
		$msg = '';

		foreach($errors as $error) {
			$msg .= $error['message'] != 'ok' ? "<span class=\"error\">".addslashes($error['message'])."</span><br />" : '';
		}

		return $msg;

	}
	
	function validateGetErrorAlert() {

		$errors = $this->validateErrors;
		$msg = array();

		foreach ($errors as $error) {
			$msg[] = $error['message'];
		}

		return count($msg) > 0 ? implode("\r\n",$msg) : '';

	}	
	
	function Quote( $text ) {
	    if (phpversion() < '4.3.0') {
	        return '\'' . mysql_escape_string( $text ) . '\'';
	    } else {
	    	$quoted = @mysql_real_escape_string( $text, $this->_db->_resource );
	        if($quoted) {
		        return '\'' . $quoted . '\'';
	        } else {
				$quoted = @mysql_escape_string( $text );        	
		        return '\'' . $quoted . '\'';
	        }
	    }
	}	
	
	function quoteLike( $text ) {
	    if (phpversion() < '4.3.0') {
	        return '\'%' . mysql_escape_string( $text ) . '%\'';
	    } else {
	    	$quoted = @mysql_real_escape_string( $text, $this->_db->_resource );
	        if($quoted) {
		        return '\'%' . $quoted . '%\'';
	        } else {
				$quoted = @mysql_escape_string( $text );        	
		        return '\'%' . $quoted . '%\'';
	        }
	    }
	}
		
	function __mergeArrays($queryData)
	{
		$newQueryData = $queryData;
		
		$valid_keys = array('useTable','fields','field_count','conditions','joins','order','group','having','limit','offset');

		// elements that need to be sent as arrays 
		$array_elements = array('fields','joins','conditions','group','having','order');

		foreach($valid_keys AS $key)
		{
			if (isset($queryData[$key]) && is_array($queryData[$key])) {
				
				$newQueryData[$key] = array_merge($this->$key,$newQueryData[$key]);
				
			} elseif (isset($queryData[$key]) && !is_array($queryData[$key]) && in_array($key,$array_elements)) {
			
				$newQueryData[$key] = array($newQueryData[$key]);
				 
			} elseif (isset($this->$key)) {

				$newQueryData[$key] = $this->$key;
			}
		}

		return $newQueryData;	
	}	
	
	function __reformatArray($rows) {

		$results = array();
		
		if($rows && !empty($rows)) 
		{
			if($this->primaryKey) 
			{				
				foreach($rows AS $key=>$row) {

					if(isset($row->{$this->primaryKey})) {
						$primaryKey = $row->{$this->primaryKey};						
					} else {
						$primaryKey = $key;
					}
					
					foreach((array) $row AS $key2=>$row2) {

						$col_var = explode('.',$key2);

						if(count($col_var) == 2) {
							$modelName = $col_var[0];
							$modelKey =$col_var[1];
						} else {
							$modelName = $this->name;
							$modelKey =$col_var[0];
						}
						$results[$primaryKey][$modelName][$modelKey] = $row2;
                    }
				}
			} else {
				foreach($rows AS $key=>$row) {
					$results[$key] = (array) $row;
				}
			}
		}

		return $results;
		
	}
	
	function changeKeys($rows, $modelName, $modelKey) {
		
		$results = array();
		
		foreach ($rows AS $row) {
			
			$results[$row[$modelName][$modelKey]] = $row;
			
		}
		
		return $results;
 		
	}
	
	/**
	 * Model callbacks
	 */
	
	function afterFind($results, $primary = false) {
		return $results;
	}

	function beforeSave() {
		return true;
	}

	function afterSave($created) {
	}	
	
}