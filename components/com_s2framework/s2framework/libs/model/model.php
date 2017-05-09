<?php
/**
 * S2Framework
 * Copyright (C) 2010-2015 ClickFWD LLC
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 * @license       http://www.opensource.org/licenses/mit-license.php The MIT License
**/


defined( 'MVC_FRAMEWORK') or die( 'Direct Access to this location is not allowed.' );

class S2ModelCore extends S2Object
{
    var $useTable;

    var $primaryKey;

    var $className;

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

    function __construct()
    {
        parent::__construct();
    }

    /**
     * Platform specific methods
     */

    function getVersion() {}

    function makeSafe($text) {}

    function getErrorMsg() {}

    function getQuery() {}

    function query($query, $type = 'query', $param = '') {}

    function insertid() {}

    /**
     * S2Framework methods
     */

    function emptyModel($data = array())
    {
        $model = array();

        foreach($this->fields AS $field) {

            $alias_defined = strpos($field,' AS ');

            $field = $alias_defined ? explode('.',substr($field,$alias_defined)) : explode('.',$field);

            $model_name = str_replace(' AS `','',$field[0]);

            $key = str_replace('`','',end($field));

            if($model_name == $this->name)
            {
                $model[$this->name][$key] = isset($data[$this->name]) && isset($data[$this->name][$key]) ? $data[$this->name][$key] : null;
            }
        }

        return $model;
    }

    function getTableList($pattern = '')
    {
        if($pattern != '')
        {
            return $this->query('SHOW TABLES LIKE "%'.$pattern.'%"','loadColumn');
        }

        return $this->query('SHOW TABLES','loadColumn');
    }

    function getTableColumns($table = null)
    {
        if(!$table)
        {
            list($table, $alias) = explode(' AS ', $this->useTable);
        }

        $query = 'DESCRIBE ' . $table;

        $columns = $this->query($query, 'loadAssocList', 'Field');

        return $columns;
    }

    function findAll($queryData = array(), $callbacks=array('plgAfterFind','afterFind','plgAfterAfterFind')) {

        if(empty($queryData))
        {
            $queryData = array('fields'=>array('*'));
        }

        return $this->find('all',$queryData, $callbacks);
    }

    function findAllCache($queryData = array(), $callbacks=array('plgAfterFind','afterFind','plgAfterAfterFind')) {

        if(empty($queryData))
        {
            $queryData = array('fields'=>'*');
        }

        return $this->find('all',$queryData, $callbacks, true);
    }

    function findAllSimple($queryData = array()) {

        if(empty($queryData))
        {
            $queryData = array('fields'=>array('*'));
        }

        return $this->find('all-simple',$queryData, array() /* no callbacks */);
    }

    function findOne($queryData = array(), $callbacks=array('plgAfterFind','afterFind','plgAfterAfterFind')) {

        return $this->find('one',$queryData, $callbacks);
    }

    function findOneCache($queryData = array(), $callbacks=array('plgAfterFind','afterFind','plgAfterAfterFind')) {

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

        if(!$rows || empty($rows) || !is_array($rows)) {

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
        $queries = array();

        $conditionsArray = array();

        $union = Sanitize::getBool($queryData,'union');

        unset($queryData['union']);

        if(!$union)
        {
            $queryData = array($queryData);
        }

        foreach($queryData AS $key=>$query)
        {
            $queryData[$key] = $this->__mergeArrays($query, $union, true);

            // Check if session cache has been disabled for this particular query
            $session_cache = Sanitize::getBool($query,'session_cache',true);

            unset($queryData[$key]['session_cache']);

            $conditionsArray = array_merge($conditionsArray,$queryData[$key]['conditions']);
        }

        // Session cache takes precedence
        if($session_cache && Configure::read('Cache.session'))
        {
            $count = $this->cacheSessionGetCount($conditionsArray);

            if(is_numeric($count)) return $count;
        }

        if($cache === true && !Configure::read('Cache.disable') && Configure::read('Cache.query') === true)
        {
            $cache_key = s2CacheKey('query_'.$countField, $queryData);

            $count = S2Cache::read($cache_key,'default');

            if(false !== $count) {
                return $count;
            }
        }

        if($union && $countField == '*') $countField = $this->name.'.'.$this->realKey;

        foreach($queryData AS $key=>$query)
        {
            if(!Sanitize::getBool($queryData,'useGroup')) {

                unset($queryData['group']);
            }

            if($union && count($queryData) > 1) {

                $queries[] =
                    'SELECT ' . $countField
                    . "\n FROM " . $this->useTable
                    . ( !empty($query['joins']) ? "\n". implode("\n", $query['joins']) : '')
                    . ( !empty($query['conditions']) ? "\n WHERE 1 = 1 AND ( \n   ". implode("\n   AND ", $query['conditions']) . "\n )" : '')
                    . ( !empty($query['group']) ? "\n GROUP BY ". implode(',', $query['group']) : '')
            //        . ( !empty($query['groupCount']) ? "\n GROUP BY ". implode(',', $query['groupCount']) : '')
                    . ( !empty($query['having']) ? "\n HAVING ". implode(' AND ', $query['having']) : '')
                ;

            }
            else {

                $queries[] =
                    'SELECT COUNT(' . $countField . ')'
                    . "\n FROM " . $this->useTable
                    . ( !empty($query['joins']) ? "\n". implode("\n", $query['joins']) : '')
                    . ( !empty($query['conditions']) ? "\n WHERE 1 = 1 AND ( \n   ". implode("\n   AND ", $query['conditions']) . "\n )" : '')
                    . ( !empty($query['group']) ? "\n GROUP BY ". implode(',', $query['group']) : '')
            //        . ( !empty($query['groupCount']) ? "\n GROUP BY ". implode(',', $query['groupCount']) : '')
                    . ( !empty($query['having']) ? "\n HAVING ". implode(' AND ', $query['having']) : '')
                ;

            }

        }

        if(count($queries) > 1 && $union)
        {
            $sql = 'SELECT COUNT(*) FROM ((' . implode(" )\nUNION\n (", $queries) . ')) AS t';
        }
        else {

            $sql = array_shift($queries);
        }

        $count = $this->query($sql, 'loadResult');

        if(Configure::read('Cache.session')) {

            $this->cacheSessionSetCount($count,$conditionsArray);

            return $count;
        }

        if($cache === true && !Configure::read('Cache.disable') && Configure::read('Cache.query') === true) {

            S2Cache::write($cache_key,$count,'default');
        }

        return $count;
    }

    function findCountCache($queryData = array(), $countField = '*') {
        return $this->findCount($queryData, $countField, true);
    }

    function find($type, $queryData, $callbacks=array('plgAfterFind','afterFind','plgAfterAfterFind'), $cache = false)
    {
        $queries = array();

        $rows = array();

        $useTable = $this->useTable;

        $order = $limit = '';

        $union = Sanitize::getBool($queryData,'union');

        $unionLimit = Sanitize::getBool($queryData,'union_limit',true);

        unset($queryData['union'], $queryData['union_limit']);

        if(!$union)
        {
            $queryData = array($queryData);
        }

        foreach($queryData AS $key=>$query)
        {
            $queryData[$key] = $this->__mergeArrays($query, $union);
        }

        if($cache === true && !Configure::read('Cache.disable') && Configure::read('Cache.query') === true)
        {
            $cache_key = s2CacheKey('query_'.$type, serialize($queryData).serialize($callbacks));

            $rows = S2Cache::read($cache_key,'default');

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

        foreach($queryData AS $key=>$query)
        {
            // Add query KEY HINTS
            if(isset($query['useKey'])) {

                $table_alias = key($query['useKey']);

                $key_hint = $query['useKey'][$table_alias];

                if($table_alias == $this->name) {

                    $useTable .= ' USE KEY ('.$key_hint.')';
                }
                elseif (isset($query['joins'][$table_alias])) {

                    $split_ON = explode('ON',$query['joins'][$table_alias]);

                    $split_ON[0] .= ' USE KEY ('.$key_hint.') ';

                    $query['joins'][$table_alias] = implode('ON',$split_ON);
                }
            }

            $query = S2Model::array_remove_empty($query);

            $order = !empty($query['order']) ? "\n ORDER BY ". implode(',', $query['order']) : '';

            if(isset($query['limit']) && $query['limit'] === 0) {

                $limit = "\n LIMIT 0";
            }
            else {

                $limit = !empty($query['limit']) ? "\n LIMIT ". (Sanitize::getInt($query,'offset',null) ? $query['offset'] . ", " : ''). $query['limit'] : '';
            }

            $queries[] =
                "SELECT " .
                    implode (",\n",$query['fields'])
                    . "\n FROM " . $useTable
            //        . ( !empty($query['useKey']) ? " USE KEY (".$query['useKey'].")" : '')
                    . ( !empty($query['joins']) ? "\n". implode("\n", $query['joins']) : '')
                    . ( !empty($query['conditions']) ? "\n WHERE 1 = 1 AND ( \n   ". implode("\n   AND ", $query['conditions']) . "\n )" : '')
                    . ( !empty($query['group']) ? "\n GROUP BY ". implode(',', $query['group']) : '')
                    . ( !empty($query['having']) ? "\n HAVING ". implode(' AND ', $query['having']) : '')
                    . $order
                    . ($union && $unionLimit ? '' : $limit)
                ;

        }

        if (!empty($queries))
        {
            if ($union) {

                $sql = '(' . implode(" )\nUNION\n (", $queries) . ')' . $order . ($unionLimit  ? $limit : '');
            }
            else {

                $sql = array_shift($queries);

                $union and $sql = $sql . $limit;
            }

            switch($type) {

                case 'all':

                    $rows = $this->query($sql,'loadAssocList');

                    $rows = $this->__reformatArray($rows);

                    break;

                case 'all-simple':

                    $rows = $this->query($sql,'loadAssocList');

                    break;

                case 'one':

                    $rows = $this->query($sql,'loadResult');

                    break;
            }

            if($type != 'one' && in_array('plgAfterFind',$callbacks) && method_exists($this,'plgAfterFind')) {
                $rows = $this->plgAfterFind($rows);
            }

            if($type != 'one' && in_array('afterFind',$callbacks) && method_exists($this,'afterFind')) {
                $rows = $this->afterFind($rows);
            }


            if($cache === true && !Configure::read('Cache.disable') && Configure::read('Cache.query') === true) {
                S2Cache::write($cache_key,$rows,'default');
            }

            if($type != 'one' && in_array('plgAfterAfterFind',$callbacks) && method_exists($this,'plgAfterAfterFind')) {
                $rows = $this->plgAfterAfterFind($rows);
            }
        }

        return $rows;
    }

    static function array_remove_empty($haystack)
    {
        foreach ($haystack as $key => $value) {
            if (is_array($value)) {
                $haystack[$key] = S2Model::array_remove_empty($haystack[$key]);
            }

            if ($haystack[$key] !== 0 && empty($haystack[$key])) {
                unset($haystack[$key]);
            }
        }

        return $haystack;
    }

    /**
     * Removes a field or fields if an array is passed from the query fields
     */
    function modelUnbind($fields)
    {
       $fields = is_array($fields) ? $fields : array($fields);
       $this->fields = array_diff($this->fields,$fields);
    }

    function views($id,$views_col = 'views')
    {
        // Uncomment line below to test views increment on page reload
        // cmsFramework::clearSessionNamespace('jreviews');

        $session_var = cmsFramework::getSessionVar($this->name.'View'.$id,'jreviews');

        // Session check to prevent views increment when the same user is reloading the page

        if(!$session_var)
        {
            cmsFramework::setSessionVar($this->name.'View'.$id,true,'jreviews');

            $query = "
                UPDATE
                    {$this->useTable}
                SET
                    {$views_col} = {$views_col} + 1
                WHERE
                    {$this->realKey} = " . (int) $id . "
            ";

            $this->query($query);

        }
    }

    function init() {

        $model = array();

        foreach($this->fields AS $field)
        {
            $clean_name = str_replace('`','',$field);
            $field = explode(' AS ',$clean_name);
            $keys = explode('.', end($field));
            $model[$keys[0]][$keys[1]] = null;
        }

        return $model;

    }

    function store(&$data, $updateNulls = false, $callbacks=array('plgBeforeBeforeSave','beforeSave','afterSave','plgBeforeSave','plgAfterSave'))
    {
        $continue = true;

        $forceInsert = Sanitize::getBool($data,'insert',false);

        if(method_exists($this,'plgBeforeBeforeSave') && in_array('plgBeforeBeforeSave',$callbacks))
        {
            $data = $this->plgBeforeBeforeSave($data);
        }

        if(method_exists($this,'beforeSave') && in_array('beforeSave',$callbacks))
        {
            $continue = $this->beforeSave($data);

            $continue = $continue !== false || $continue === NULL;
        }

        if(method_exists($this,'plgBeforeSave') && in_array('plgBeforeSave',$callbacks))
        {
            $data = $this->plgBeforeSave($data);
        }

        if($continue === true)
        {
            $table = substr($this->useTable,0,strpos($this->useTable,' AS'));

            $primaryKeyString = isset($this->realKey) ? $this->realKey : $this->primaryKey;

            $clean_primary_key = str_replace('`','',$primaryKeyString);

            $key_parts = explode('.',$clean_primary_key);

            $keyName = end($key_parts);

            // If the unique model key column is present and is numeric then cast it to an integer

            if(isset($data[$this->name][$keyName]) && (is_numeric($data[$this->name][$keyName]) || $data[$this->name][$keyName] == ''))
            {
                $data[$this->name][$keyName] = (int) $data[$this->name][$keyName];
            }

            if(isset($data[$this->name][$keyName]) &&  $data[$this->name][$keyName] != '' && (string) $data[$this->name][$keyName] !== '0' && !$forceInsert) {

                $ret = $this->update( $table, $this->name, $data, $keyName, $updateNulls );

            }
            else {

                $ret = $this->insert( $table, $this->name, $data, $keyName );
            }

            if( !$ret ) {
                $this->_error = strtolower(get_class( $this ))."::store failed <br />" . $this->getErrorMsg();
            }

            $this->data = &$data;
        }
        else {

            $ret = true;
        }

        if(method_exists($this,'afterSave') && in_array('afterSave',$callbacks))
        {
            $this->afterSave($ret);
        }

        if(method_exists($this,'plgAfterSave') && in_array('plgAfterSave',$callbacks))
        {
            $this->plgAfterSave($ret);
        }

        // clearCache('', 'views');
        // clearCache('', '__data');

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

        $query = sprintf( $fmtsql, $keyName, is_array($values) ? implode( ",", $values ) : $values, $condition);

        $delete = $this->query($query);

        if($delete)
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

        // clearCache('', 'views');
        // clearCache('', '__data');
        // Clear session cache
        if(Configure::read('Cache.session')) {
            cmsFramework::clearSessionVar($this->name, 'findCount');
        }

        return $delete;
    }

    function insert( $table, $alias, &$data, $keyName = null)
    {
        $fmtsql = '
            INSERT INTO ' . $table . ' ( %s ) VALUES ( %s )
            ON DUPLICATE KEY UPDATE %s
        ';

        $alias = inflector::camelize($alias);

        $fields = array();

        foreach ($data[$alias] as $k => $v)
        {
            if (is_array($v) OR is_object($v) OR $v === NULL OR $k[0] == '_') continue;

            $fields[] = "`$k`";

            $values[] = $this->Quote($v);

            $update[] = "`$k` = " . $this->Quote($v);
        }

        if (!isset($fields)) die ('class database method insertObject - no fields');

        $query = sprintf( $fmtsql, implode( ",", $fields ), implode( ",", $values ) , implode($update, ","));

        $insert = $this->query($query);

        if ($insert === false)
        {
            return false;
        }

        $id = $this->insertid();

        if ($keyName && $id)
    {
            $data[$alias][$keyName] = $id;
        }

        $data['insertid'] = $id;

        // Clear session cache
        if(Configure::read('Cache.session'))
    {
            cmsFramework::clearSessionVar($this->name, 'findCount');
        }

        return true;
    }

    function replace( $table, $alias, &$data, $keyName = null)
    {
        // Changed from REPLACE to INSERT with ON DUPLICATE UPDATE
        $fmtsql = "INSERT INTO $table ( %s ) VALUES ( %s ) ON DUPLICATE KEY UPDATE %s";

        $alias = inflector::camelize($alias);

        $fields = $duplicates = array();

        foreach ($data[$alias] as $k => $v)
        {
            if (is_array($v) OR is_object($v) OR $v === NULL OR $k[0] == '_') continue;

            $fields[] = "`$k`";

            $values[] = $this->Quote($v);

            $duplicates[] = $k ."  = " . $this->Quote($v);
        }

        if (!isset($fields)) die ('class database method insertObject - no fields');

        $query = sprintf( $fmtsql, implode( ", ", $fields ), implode( ", ", $values ), implode( ", ", $duplicates ) );

        $replace = $this->query($query);

        if ($replace === false)
        {
            return false;
        }

        $id = $this->insertid();

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

        $query = sprintf( $fmtsql, implode( ",", $tmp ) , $where );

        $update = $this->query($query);

        if ($update === false)
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

        $query = "SELECT $k, ordering FROM $table WHERE ordering $relation $o1";

        $query .= ($where ? "\n AND $where" : '').' ORDER BY ordering '.$ordering.' LIMIT 1';

        if ($row = $this->query($query, 'loadObjectList'))
        {
            $row = current($row);

            $o2 = $row->ordering;

            $k2 = $row->$k;

            $query = "UPDATE $table SET ordering = (ordering=$o1)*$o2 + (ordering=$o2)*$o1 WHERE $k = $k1 OR $k = $k2";

            $this->query($query);
        }

        clearCache('', 'views');

        clearCache('', '__data');
    }

    function reorder($order_data, $order_col = 'ordering', $extras = array()) {

        $table = substr($this->useTable,0,strpos($this->useTable,' AS'));

        $primaryKeyString = isset($this->realKey) ? $this->realKey : $this->primaryKey;

        $keyParts = explode('.',str_replace('`','',$primaryKeyString));

        $key = end($keyParts);

        $query = "
            UPDATE
                 {$table}
            SET
                {$order_col} = CASE {$key}
        ";

        foreach ($order_data AS $row) {
            $ids[] = (int) $row['id'];
            $query .= sprintf("WHEN %d THEN %d ", (int) $row['id'], $row['order']);
        }

        $query .= "END WHERE {$key} IN (".cleanIntegerCommaList($ids).")";

        if($result = $this->query($query))
        {
            $result = $this->afterReorder($ids, $extras);
        }

        clearCache('', 'views');

        clearCache('', '__data');

        return $result;
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
                    $regex = '^((http|https)+://.*[.][^.].*|[^\:]*[.][^.]*)';
                    break;
                case 'email':
                    $regex = '.+@.*';
                    break;
                default:
                    $regex = '';
                    break;
            }
        }

        $value = trim(strip_tags($value));

        if ($required && trim(strip_tags($value)) == '' )
        {
            $this->validateSetError($name, $label);
        }
        elseif($value != '' && $regex != '') {

            if(!preg_match('~'.$regex.'~i',$value,$matches))
            {
                $this->validateSetError($name, $label);
            }
        }
    }

    function validateClearErrors()
    {
        $this->validateErrors = array();
    }

    function validateSetError($name, $label, $priority = 10)
    {
        $this->validateErrors[] = array("input"=>$name, "message"=>$label, 'priority'=>$priority);
    }

    /* original function */

    function validateGetError()
    {
        $this->validateReorderMessages();

        $errors = $this->validateErrors;

        $msg = '';

        foreach($errors as $error)
        {
            $msg .= $error['message'] != 'ok' ? "<span class=\"error\">".addslashes($error['message'])."</span><br />" : '';
        }

        return $msg;
    }

    function validateGetErrorArray()
    {
        $this->validateReorderMessages();

        $errors = $this->validateErrors;

        $msg = array();

        foreach($errors as $error)
        {
            if($error['message'] != 'ok')
            {
                $msg[] =  $error['message'];
            }
        }

        return $msg;
    }

    function validateGetErrorAlert()
    {
        $this->validateReorderMessages();

        $errors = $this->validateErrors;

        $msg = array();

        foreach ($errors as $error)
        {
            $msg[] = $error['message'];
        }

        return count($msg) > 0 ? implode("\r\n",$msg) : '';
    }

    function validateReorderMessages()
    {
        $priority = array();

        $keys = array();

        foreach($this->validateErrors AS $key=>$error)
        {
            $keys[$key] = $key;

            $priority[$key] = $error['priority'];
        }

        array_multisort($priority, SORT_DESC, $keys, SORT_ASC, $this->validateErrors);
    }

    function Quote( $values )
    {
        !is_array($values) and $values = array($values);

        foreach($values AS $key=>$text)
        {
            if(is_string($text))
            {
                $values[$key] = '\'' . $this->makeSafe($text) . '\'';
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

    function QuoteLike( $text )
    {
        $text = $this->makeSafe($text);

        return '\'%' . $text . '%\'';
    }

    function __mergeArrays($queryData, $union = false, $count = false)
    {
        $newQueryData = $queryData;

        $valid_keys = array('useTable','useKey','fields','field_count','conditions','joins','order','group','having','limit','offset');

        // elements that need to be sent as arrays
        $array_elements = array('fields','joins','conditions','group','having','order');

        foreach($valid_keys AS $key)
        {
            if(isset($queryData[$key]) && is_array($queryData[$key])) {

                if($union && $key == 'joins') {

                    $newQueryData[$key] = array_merge($newQueryData[$key],$this->$key);
                }
                else {

                    $newQueryData[$key] = array_merge($this->$key,$newQueryData[$key]);
                }

                $newQueryData[$key] = array_unique($newQueryData[$key]);

            } elseif (isset($queryData[$key]) && !is_array($queryData[$key]) && in_array($key,$array_elements)) {

                $newQueryData[$key] = array($newQueryData[$key]);

            } elseif (isset($this->$key)) {

                $newQueryData[$key] = $this->$key;
            }
        }

        $conditions = implode(' ', Sanitize::getVar($newQueryData,'conditions',array()));

        $fields = $count ? array() : implode(' ', Sanitize::getVar($newQueryData,'fields',array()));

        $order = implode(' ', Sanitize::getVar($newQueryData,'order',array()));

        $joins = array();

        // Extract the Model for each join and set it as the element key

        $modelArray = array();

        foreach(Sanitize::getVar($newQueryData,'joins',array()) AS $join)
        {
            $join = Sanitize::stripWhiteSpace($join);

            if(preg_match('/(?P<type>.*) JOIN (.*) AS (?P<model>[A-Za-z]+) ON /i', $join, $matches))
            {
                $joins[$matches['model']] = $join;

                // Always leve INNER and RIGHT joins in query

                if(in_array($matches['type'],array('INNER','RIGHT')))
                {
                    $modelArray[$matches['model']] = $matches['model'];
                }
            }

            if(preg_match('/(?P<type>.*) JOIN (.*) AS `(?P<model>[A-Za-z]+)` ON /i', $join, $matches))
            {
                $joins[$matches['model']] = $join;

                // Always leave INNER and RIGHT joins in query

                if(in_array($matches['type'],array('INNER','RIGHT')))
                {
                    $modelArray[$matches['model']] = $matches['model'];
                }
            }
        }

        // Fields

        if(!empty($fields) && preg_match_all('/(?P<model>[A-Za-z]+)\.[a-z_\*]+ AS /', $fields, $matches))
        {
            $modelArray = array_merge($modelArray,$matches['model']);
        }

        if(!empty($fields) && preg_match_all('/`(?P<model>[A-Za-z]+)`\.[a-z_\*]+ AS /', $fields, $matches))
        {
            $modelArray = array_merge($modelArray,$matches['model']);
        }

        // Conditions
        if(!empty($conditions) && preg_match_all('/(?P<model>[A-Za-z]+)\.[a-z_]+/', $conditions, $matches))
        {
            $modelArray = array_merge($modelArray,$matches['model']);
        }

        if(!empty($conditions) && preg_match_all('/`(?P<model>[A-Za-z]+)`\.[a-z_]+/', $conditions, $matches))
        {
            $modelArray = array_merge($modelArray,$matches['model']);
        }

        // Order

        if(!empty($order) && preg_match_all('/(?P<model>[A-Za-z]+)\.[a-z_]+/', $order, $matches))
        {
            $modelArray = array_merge($modelArray,$matches['model']);
        }

        if(!empty($order) && preg_match_all('/`(?P<model>[A-Za-z]+)`\.[a-z_]+/', $order, $matches))
        {
            $modelArray = array_merge($modelArray,$matches['model']);
        }

        // Joins

        if(!$count && !empty($joins) && preg_match_all('/ AS (?P<model>[A-Za-z]+) ON /', implode(' ', $joins), $matches))
        {
            $modelArray = array_merge($modelArray,$matches['model']);
        }

        if(!$count && !empty($joins) && preg_match_all('/ AS `(?P<model>[A-Za-z]+)` ON /', implode(' ', $joins), $matches))
        {
            $modelArray = array_merge($modelArray,$matches['model']);
        }

        $modelArray = array_unique($modelArray);

        $newQueryData['joins'] = array_intersect_key($joins, array_flip($modelArray));

        // Now add back any joins that were removed, but for which there are dependent joins
        // We use a recursive method because once a new joins is added back we need to check for further dependencies

        if($count && !empty($joins))
        {
            self::recursiveJoinCheck($newQueryData['joins'],$joins);
        }

        // Reorder Joins based on the original order so dependencies are not broken

        $desiredJoinsIndexOrder = array_keys($joins);

        if(!empty($newQueryData['joins']) && !empty($joins))
        {
            uksort($newQueryData['joins'], function($a, $b)  use ($desiredJoinsIndexOrder) {

                return array_search($a,$desiredJoinsIndexOrder) > array_search($b,$desiredJoinsIndexOrder) ? 1 : -1;
            });
        }

        return $newQueryData;
    }

    static function recursiveJoinCheck(&$modifiedJoins, $originalJoins)
    {
        $modifiedJoinsCopy = $modifiedJoins;

        preg_match_all('/ AS (?P<model1>[A-Za-z]+) ON (?P<model2>[A-Za-z]+)\.[A-Za-z_]+ = (?P<model3>[A-Za-z]+)\.[A-Za-z_]+/', implode(' ', $modifiedJoins), $matches);

        $joinModels = array();

        if(isset($matches['model1']))
        {
            $joinModels = array_merge($joinModels, $matches['model1']);
        }

        if(isset($matches['model2']))
        {
            $joinModels = array_merge($joinModels, $matches['model2']);
        }

        if(isset($matches['model3']))
        {
            $joinModels = array_merge($joinModels, $matches['model3']);
        }

        $joinModels = array_unique($joinModels);

        foreach($joinModels AS $key=>$model)
        {
            if(isset($originalJoins[$model]))
            {
                $modifiedJoins[$model] = $originalJoins[$model];
            }
        }

        if($modifiedJoins !== $modifiedJoinsCopy)
        {
            self::recursiveJoinCheck($modifiedJoins, $originalJoins);
        }
    }

    /**
     * [__reformatArray description]
     * @param  array $rows      Raw database query results
     * @param  function $fn     optional PHP function to run for each row
     * @param  array $params    options that can be used inside the php function
     * @param  array  $output   A new modified array passed by reference that can be build inside the foreach loop in addition to the reformatted $rows array
     */
    function __reformatArray($rows, $fn = null, $params = null, & $output = array()) {

        $results = array();

        if($rows && !empty($rows))
        {
            if($this->primaryKey)
            {
                foreach($rows AS $key=>$row)
                {
                    $row = (array) $row;

                    $primaryKey = Sanitize::getString($row, $this->primaryKey);

                    if(!$primaryKey)
                    {
                        $primaryKey = $key;
                    }

                    foreach($row AS $key2=>$row2)
                    {
                        $col_var = explode('.',$key2);

                        if(count($col_var) == 2)
                        {
                            $modelName = $col_var[0];

                            $modelKey =$col_var[1];
                        }
                        else {
                            $modelName = $this->name;

                            $modelKey =$col_var[0];
                        }

                        $results[$primaryKey][$modelName][$modelKey] = $row2;
                    }

                    if(is_callable($fn))
                    {
                        $results[$primaryKey] = $fn($primaryKey, $results[$primaryKey], $output, $params);
                    }
                }
            }
            elseif(is_callable($fn)) {

                foreach($rows AS $key=>$row)
                {
                    $results[$key] = $row;

                    $results[$key] = $fn($key, $results[$key], $output, $params);
                }
            }
            else {
                $results = $rows;
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

    function cacheSessionSetCount($count,$conditionsArray) {

        !isset($this->conditions) and $this->conditions = array();

        $conditions = array_filter(array_merge($this->conditions,$conditionsArray));

        $findCount = cmsFramework::getSessionVar($this->name,'findCount');

        $findCount[md5($this->name.implode('',$conditions))] = $count;

        cmsFramework::setSessionVar($this->name,$findCount,'findCount');
    }

    function cacheSessionGetCount($conditionsArray) {

        !isset($this->conditions) and $this->conditions = array();

        $conditions = array_filter(array_merge($this->conditions,$conditionsArray));

        $findCount = cmsFramework::getSessionVar($this->name,'findCount');

        if(isset($findCount[md5($this->name.implode('',$conditions))])) {

            return $findCount[md5($this->name.implode('',$conditions))];
        }

        return false;

    }

    /**
     * Model callbacks
     */

    function afterFind($results)
    {
        return $results;
    }

    function beforeSave(&$data)
    {
        return true;
    }

    function afterSave($status) {}

    function afterReorder($ids, $extras = array())
    {
        return true;
    }

    function beforeDelete($keyName, $values, $condition)
    {
        return true;
    }

    function afterDelete($keyName, $values, $condition)
    {
        return true;
    }
}

require( S2_LIBS . 'cms_compat' . DS . _CMS_NAME . DS . 'model.php');
