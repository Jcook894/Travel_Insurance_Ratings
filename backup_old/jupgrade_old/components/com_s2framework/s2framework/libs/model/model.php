<?php
/**
 * S2Framework
 * Copyright (C) 2010-2012 ClickFWD LLC
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 * @license       http://www.opensource.org/licenses/mit-license.php The MIT License
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
    
    /**
    * Array of callbacks that should be run even if cache is enebaled before they peform other actions unrelated to the query results
    * plgAfterFind','afterFind','plgAfterAfterFind
    * @var mixed
    */
    var $cacheCallbacks = array();       

    var $validateErrors = array();
                
    function __construct() {
        # Adds CMS DB and Mainframe methods
        $this->_db = & cmsFramework::getDB();
        parent::__construct();
    }
    
    function emptyModel() 
    {
        $model = array();
        foreach($this->fields AS $field) {
            $key = str_replace('`','',end(explode('.',substr($field,strpos($field,' AS ')))));
            
            $model[$this->name][$key] = null;
        }
        return $model;
    }
    
    function findAll($queryData, $callbacks=array('plgAfterFind','afterFind','plgAfterAfterFind')) {
        
        return $this->find('all',$queryData, $callbacks);
    }
    
    function findAllCache($queryData, $callbacks=array('plgAfterFind','afterFind','plgAfterAfterFind')) {
        
        return $this->find('all',$queryData, $callbacks, true);
    }

    function findOne($queryData, $callbacks=array('plgAfterFind','afterFind','plgAfterAfterFind')) {
        
        return $this->find('one',$queryData, $callbacks);
    }
    
    function findOneCache($queryData, $callbacks=array('plgAfterFind','afterFind','plgAfterAfterFind')) {
        
        return $this->find('one',$queryData, $callbacks, true);
    }

    function findRow($queryData = array(), $callbacks=array('plgAfterFind','afterFind','plgAfterAfterFind')) 
    {
        $cache = false;
        if(isset($queryData['cache']) && $queryData['cache'] == true) {
            $cache = true;
            unset($queryData['cache']);
        }
        
        $rows = $this->find('all',$queryData, $callbacks, $cache);

        if(!$rows || empty($rows)) {
            
            return false;
        }
        return array_shift($rows);
                
    }

    function findRowCache($queryData = array(), $callbacks=array('plgAfterFind','afterFind','plgAfterAfterFind')) {
        
        $rows = $this->find('all',$queryData, $callbacks, true);

        if(!$rows || empty($rows)) {
            
            return false;
        }

        return current($rows);
                
    }    
    
    function findCount($queryData = array(), $countField = '*', $cache = false) 
    {
        $queryData = $this->__mergeArrays($queryData);
		
		// Check if session cache has been disabled for this particular query
		$session_cache = Sanitize::getBool($queryData,'session_cache',true);
		
		unset($queryData['session_cache']);
		
        // Session cache takes precedence
        if($session_cache && Configure::read('Cache.session')) {
            $count = $this->cacheSessionGetCount($queryData);
            if(is_numeric($count)) return $count;
        }

        if($cache === true && !Configure::read('Cache.disable') && Configure::read('Cache.query') === true) {
            
            $cache_key = md5(cmsFramework::getConfig('secret').serialize($queryData).$countField);
    
            $count = S2Cache::read($cache_key);
            
            if(false !== $count) {
                return $count;
            }        
        }
        
        if(!Sanitize::getBool($queryData,'useGroup')) {
            unset($queryData['group']);
        } 

        $query = 'SELECT COUNT(' . $countField . ')'
        . "\n FROM " . $this->useTable
        . ( !empty($queryData['joins']) ? "\n". implode("\n", $queryData['joins']) : '')
        . ( !empty($queryData['conditions']) ? "\n WHERE 1 = 1 AND ( \n   ". implode("\n   AND ", $queryData['conditions']) . "\n )" : '') 
        . ( !empty($queryData['group']) ? "\n GROUP BY ". implode(',', $queryData['group']) : '') 
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
                
        if(Configure::read('Cache.session')) {
            $this->cacheSessionSetCount($count,$queryData);
            return $count;
        }
        
        if($cache === true && !Configure::read('Cache.disable') && Configure::read('Cache.query') === true) {
            S2Cache::write($cache_key,$count);
        }
        
        return $count;    
    }    
    
    function findCountCache($queryData = array(), $countField = '*') {
        return $this->findCount($queryData, $countField, true);
    }
    
    function find($type, $queryData, $callbacks=array('plgAfterFind','afterFind','plgAfterAfterFind'), $cache = false) 
    {
        $useTable = $this->useTable;
        $queryData = $this->__mergeArrays($queryData);
       
        if($cache === true && !Configure::read('Cache.disable') && Configure::read('Cache.query') === true)        
        {                  
            $cache_key = md5(cmsFramework::getConfig('secret').$type.serialize($queryData).serialize($callbacks));
                 
            $rows = S2Cache::read($cache_key);
               
/*            if($type != 'one' && in_array('plgAfterFind',$this->cacheCallbacks) && method_exists($this,'plgAfterFind')) {
                $rows = $this->plgAfterFind($rows);
            }
            if($type != 'one' && in_array('afterFind',$this->cacheCallbacks) && method_exists($this,'afterFind')) {
                $rows = $this->afterFind($rows);
            }
*/            
            if($type != 'one' && in_array('plgAfterAfterFind',$this->cacheCallbacks) && method_exists($this,'plgAfterAfterFind')) {
                $rows = $this->plgAfterAfterFind($rows);
            }   
             
            if(false !== $rows) {
                return $rows;
            }  
        }
        
        // Add query KEY HINTS
        if(isset($queryData['useKey'])) {
            $table_alias = key($queryData['useKey']);
            $key_hint = $queryData['useKey'][$table_alias];
            if($table_alias == $this->name) {
                $useTable .= ' USE KEY ('.$key_hint.')';
            }
            elseif (isset($queryData['joins'][$table_alias])) {
                $split_ON = explode('ON',$queryData['joins'][$table_alias]);
                $split_ON[0] .= ' USE KEY ('.$key_hint.') ';
                $queryData['joins'][$table_alias] = implode('ON',$split_ON);
            }
        }

        $query = "SELECT " . implode (",\n",$queryData['fields'])
        . "\n FROM " . $useTable
//        . ( !empty($queryData['useKey']) ? " USE KEY (".$queryData['useKey'].")" : '')
        . ( !empty($queryData['joins']) ? "\n". implode("\n", $queryData['joins']) : '') 
        . ( !empty($queryData['conditions']) ? "\n WHERE 1 = 1 AND ( \n   ". implode("\n   AND ", $queryData['conditions']) . "\n )" : '') 
        . ( !empty($queryData['group']) ? "\n GROUP BY ". implode(',', $queryData['group']) : '') 
        . ( !empty($queryData['having']) ? "\n HAVING ". implode(' AND ', $queryData['having']) : '') 
        . ( !empty($queryData['order']) ? "\n ORDER BY ". implode(',', $queryData['order']) : '') 
        . ( !empty($queryData['limit']) ? "\n LIMIT ". (Sanitize::getInt($queryData,'offset',null) ? $queryData['offset'] . ", " : ''). $queryData['limit'] : '')
        ;

        $this->_db->setQuery($query);
        
//        prx($this->_db->getQuery());
       
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

        if($type != 'one' && in_array('plgAfterFind',$callbacks) && method_exists($this,'plgAfterFind')) {
            $rows = $this->plgAfterFind($rows);
        }

        if($type != 'one' && in_array('afterFind',$callbacks) && method_exists($this,'afterFind')) {
            $rows = $this->afterFind($rows);
        }
                  
        if($cache === true && !Configure::read('Cache.disable') && Configure::read('Cache.query') === true) {
            S2Cache::write($cache_key,$rows);
        }          

        if($type != 'one' && in_array('plgAfterAfterFind',$callbacks) && method_exists($this,'plgAfterAfterFind')) {
            $rows = $this->plgAfterAfterFind($rows);
        }        

        return $rows;
    }
    
    /**
     * Removes a field or fields if an array is passed from the query fields
     */    
    function modelUnbind($fields) 
    {        
       $fields = is_array($fields) ? $fields : array($fields);
       $this->fields = array_diff($this->fields,$fields);                   
    }
    
    function init() {
        
        $model = array();
        
        foreach($this->fields AS $field) 
        {
            $keys = explode('.', end(explode(' AS ',str_replace('`','',$field))));
            $model[$keys[0]][$keys[1]] = null;        
        }
        
        return $model;
        
    }
    
    
    function store(&$data, $updateNulls = false, $callbacks=array('beforeSave','afterSave','plgBeforeSave','plgAfterSave')) 
    {                                                         
        Sanitize::getBool($data,'insert') and $insert = true or $insert = false;    
        
        if(method_exists($this,'beforeSave') && in_array('beforeSave',$callbacks)) {
            $this->beforeSave($data);
        }        
        if(method_exists($this,'plgBeforeSave') && in_array('plgBeforeSave',$callbacks)) {
            $data = $this->plgBeforeSave($data);
        }            
                            
        $table = substr($this->useTable,0,strpos($this->useTable,' AS'));
        
        $primaryKeyString = isset($this->realKey) ? $this->realKey : $this->primaryKey;
        
        $keyName = end(explode('.',str_replace('`','',$primaryKeyString)));
        
        if( isset($data[$this->name][$keyName]) &&  $data[$this->name][$keyName] > 0 && !$insert) {

            $ret = $this->update( $table, $this->name, $data, $keyName, $updateNulls );
        
        } else {

            $ret = $this->insert( $table, $this->name, $data, $keyName );
        }

        if( !$ret ) {
            $this->_error = strtolower(get_class( $this ))."::store failed <br />" . $this->_db->getErrorMsg();
        } 
        
        $this->data = &$data;

        if(method_exists($this,'afterSave') && in_array('afterSave',$callbacks)) {
            $this->afterSave($ret);
        }
        
        if(method_exists($this,'plgAfterSave') && in_array('plgAfterSave',$callbacks)) {
            $this->plgAfterSave($ret);
        }
                            
        clearCache('', 'views');
        clearCache('', '__data');
        
        return $ret;

    }
    
    function delete($keyName, $values, $condition = '', $callbacks=array('beforeDelete','plgBeforeDelete','afterDelete','plgAfterDelete')) 
    {
        if(in_array('beforeDelete',$callbacks))
            {
            $this->beforeDelete($keyName, $values, $condition);            
            }
        
        if(in_array('plgBeforeDelete',$callbacks) && method_exists($this,'plgBeforeDelete'))
            {
            $this->plgBeforeDelete($keyName, $values, $condition);            
            }

        $table = substr($this->useTable,0,strpos($this->useTable,' AS'));
        
        $fmtsql = "DELETE FROM $table WHERE %s IN ( %s ) %s";
        
        $condition = $condition != '' ? "AND $condition" : '';
                                              
        $this->_db->setQuery( sprintf( $fmtsql, $keyName, is_array($values) ? implode( ",", $values ) : $values, $condition) );

        if($delete = $this->_db->query()) 
        {
            if(in_array('afterDelete',$callbacks))
                {
                    $this->afterDelete($keyName, $values, $condition);
                }

            if(in_array('plgAfterDelete',$callbacks) && method_exists($this,'plgAfterDelete'))
                {
                    $this->plgAfterDelete($keyName, $values, $condition);
                }
        }
        
                
        $message[] = '*********' . get_class($this) . ' | Delete';
        $message[] = $this->_db->getQuery();
        $message[] = $this->_db->getErrorMsg();                            
        appLogMessage($message, 'database');    
        
        clearCache('', 'views');
        clearCache('', '__data');  
        // Clear session cache
        if(Configure::read('Cache.session')) {
            cmsFramework::clearSessionVar($this->name, 'findCount');
        }
        
        
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
        
        // Clear session cache
        if(Configure::read('Cache.session')) {
            cmsFramework::clearSessionVar($this->name, 'findCount');
        }
        
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
    
    function validateInput($value, $name, $type, $label, $required, $regex = '') 
    {
        $regex = (string)trim($regex);
        
        if($regex=='')
        {
            switch($type)
            {
                case 'integer':
                    $regex = '^[0-9]+$';
                    break;
                case 'decimal':
                    $regex = '^(\.[0-9]+|[0-9]+(\.[0-9]*)?)$';
                    break;
                case 'website':
                    $regex = '^(ftp|http|https)+(:\/\/)+[a-z0-9_-]+\.+[a-z0-9_-]';
                    break;
                case 'email':
                    $regex = '.+@.*';
                    break;
                default:
                    $regex = '';
                    break;
            }                    
        }

        if ( $required && trim(strip_tags($value)) == "" ) 
        {                                            
            $this->validateSetError($name, $label);

        } elseif (trim(strip_tags($value)) && $regex != "") 
        {
            if(!preg_match('/'.$regex.'/i',$value)) 
            {                                                
                $this->validateSetError($name, $label);
            }

        }
    }

    function validateSetError($name, $label) 
    {
        $this->validateErrors[] = array("input"=>$name, "message"=>$label);
    }

    /* original function */
    function validateGetError() {

        $errors = $this->validateErrors;
        $msg = '';

        foreach($errors as $error) {
            $msg .= $error['message'] != 'ok' ? "<span class=\"error\">".addslashes($error['message'])."</span><br />" : '';
        }

        return $msg;
    }
    
    function validateGetErrorArray() 
    {
        $errors = $this->validateErrors;
        $msg = array();
        foreach($errors as $error) 
        {
            if($error['message'] != 'ok'){
                $msg[] =  $error['message'];
            }
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
    
    function makeSafe($text)
    {
        $dbResource = cmsFramework::getConnection($this->_db);
        
        if(is_object($dbResource) && get_class($dbResource) == 'mysqli')
        {
            $quoted = mysqli_real_escape_string( $dbResource, $text );
        } else {
            $quoted = mysql_real_escape_string( $text, $dbResource );
        }
        return $quoted;
    }
    
    function Quote( $values ) 
    {  
        !is_array($values) and $values = array($values);
        
        $dbResource = cmsFramework::getConnection();
        
        $mysqli = is_object($dbResource) && get_class($dbResource) == 'mysqli';
        
        foreach($values AS $key=>$text)
        {
            if(is_string($text)) {
                $values[$key] = '\'' . ($mysqli ? mysqli_real_escape_string( $dbResource, $text ) : mysql_real_escape_string( $text, $dbResource ) ) . '\'';   
            } 
            elseif(is_bool($text)) {
                $values[$key] = (int) $text;
            }
            else {
                $values[$key] = $text;
            }
        }
        
        return implode(',',$values);
    }  
    
    function quoteLike( $text ) 
    {
        $dbResource = cmsFramework::getConnection();

        if(is_object($dbResource) && get_class($dbResource) == 'mysqli')
        {
            $quoted = mysqli_real_escape_string( $dbResource, $text );
        } else {
            $quoted = mysql_real_escape_string( $text, $dbResource );
        }
        return '\'%' . $quoted . '%\'';
    }
        
    function __mergeArrays($queryData)
    {
        $newQueryData = $queryData;
        
        $valid_keys = array('useTable','useKey','fields','field_count','conditions','joins','order','group','having','limit','offset');

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
                    
                    foreach((array) $row AS $key2=>$row2) 
                    {
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
    
	function getErrorMsg()
	{
		return $this->_db->getErrorMsg();
	}
	
	function getQuery() 
	{
		return $this->_db->getQuery();
	}
	
	function query($query, $type = 'query', $param = '')
	{
		$message = array();

		$this->_db->setQuery($query);
		
        $message[] = '*********' . get_class($this) . ' | handwritten query';
        $message[] = $this->_db->getQuery();        

		if($param != '') {
			$result = $this->_db->{$type}($param); 
		}
		else {
			$result = $this->_db->{$type}(); 
		}

        if($this->_db->getErrorMsg()) {
            $message[] = '*********' . get_class($this) . ' | handwritten ERROR';
            $message[] = $this->_db->getErrorMsg();                    
        }

        appLogMessage($message, 'database' );
		
		return $result;
	}
	
    function changeKeys($rows, $modelName, $modelKey) {
        
        $results = array();
        
        foreach ($rows AS $row) {
            
            $results[$row[$modelName][$modelKey]] = $row;
            
        }
        
        return $results;
         
    }
    
    function cacheSessionSetCount($count,$queryData) {
        
        $conditions = array_filter(array_merge($this->conditions,$queryData['conditions']));
        $findCount = cmsFramework::getSessionVar($this->name,'findCount');
        $findCount[md5($this->name.implode('',$conditions))] = $count;
        cmsFramework::setSessionVar($this->name,$findCount,'findCount');
            
    }

    function cacheSessionGetCount($queryData) {

        $conditions = array_filter(array_merge($this->conditions,$queryData['conditions']));
        $findCount = cmsFramework::getSessionVar($this->name,'findCount');
        if(isset($findCount[md5($this->name.implode('',$conditions))])) {
            return $findCount[md5($this->name.implode('',$conditions))];
        }
        return false;
    
        
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

    function afterSave($created) {}    
    
    function beforeDelete() {}
    
    function afterDelete() {}
    
}
