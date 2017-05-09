<?php
/**
 * JReviews - Reviews Extension
 * Copyright (C) 2010-2011 ClickFWD LLC
 * This is not free software, do not distribute it.
 * For licencing information visit http://www.reviewsforjoomla.com
 * or contact sales@reviewsforjoomla.com
**/

// no direct access
defined( 'MVC_FRAMEWORK') or die( 'Direct Access to this location is not allowed.' );

class FieldOptionsController extends MyController 
{
    var $uses = array('field_option','field','acl');
    var $components = array('config');
    var $autoRender = false;
    var $autoLayout = false;
    var $helpers = array('html','form','paginator');
        
    function index() 
    {
        $fieldid = Sanitize::getInt($this->data,'field_id');
        if(!$fieldid && isset($this->data['FieldOption'])) $fieldid = Sanitize::getInt($this->data['FieldOption'],'fieldid');
        if(!$fieldid) return;
        
        $limit = $this->limit;
        $limitstart = $this->offset;    
        $total = 0;

        $this->_db->setQuery("
            SELECT 
                fieldid,type,name,title,groupid,location 
            FROM 
                #__jreviews_fields 
            WHERE 
                fieldid = " . $fieldid
            );
        $field = current($this->_db->loadAssocList());
        $rows = $this->FieldOption->getList($fieldid, $limitstart, $limit, $total);

        $this->set(array(
            'table'=>$this->listViewTable($rows, $total, $field),
            'field'=>$field
        ));

        return $this->render(); 
    }
        
    function listViewTable($rows, $total, $field) 
    {              
        $this->set(
            array(
                'rows'=>$rows,
                'pagination'=>array(
                    'total'=>$total
                ),
                'field'=>$field
            )
        );
        
        return $this->render('fieldoptions','table');
    }    
    

    function _save($params) 
    {             
        $this->action = 'index';       
        $isNew = Sanitize::getInt($this->data['FieldOption'],'optionid') ? false : true;
            
        $text = Sanitize::getString($this->data['FieldOption'],'text');
        $value = Sanitize::stripAll($this->data['FieldOption'],'value');    
        $field_id = Sanitize::getInt($this->data['FieldOption'],'fieldid');
        $location = Sanitize::getString($this->data,'location','content');        
        $limit = $this->limit;
        $limitstart = $this->offset;        
        $total = 0;    
        
        // Begin validation
        $validation_ids = array();
        $text == '' and $validation_ids[] = "option_text";
        $value == '' and $validation_ids[] = "option_value";
        if(!empty($validation_ids))
        {
            return json_encode(compact('validation_ids'));        
        }
        
        // Begin save
        $result = $this->FieldOption->save($this->data);
        
        if($result != 'success') 
        {
            $msg = "An option with this value already exists for this field.";
            return $this->ajaxError($msg);                
        }
        
        // Begin update display
        $option_id = $this->data['FieldOption']['optionid'];
        $field_id = $this->data['FieldOption']['fieldid'];
        $rows = $this->FieldOption->getList($field_id, $limitstart, $limit, $total);
        
        $this->_db->setQuery("
            SELECT 
                fieldid,type,name,title,groupid,location 
            FROM 
                #__jreviews_fields 
            WHERE 
                fieldid = " . $field_id
            );
        $field = current($this->_db->loadAssocList());
                
        // Reloads the whole list to display the new/updated record
        $page = Sanitize::stripWhitespace($this->listViewTable($rows, $total, $field));
        $action = 'success';
        return json_encode(compact('action','page','option_id'));        
    }
    
    function edit() 
    {
        $this->name = 'fieldoptions';
        $this->autoRender = false;
        $this->autoLayout = false;
        $optionid =  Sanitize::getInt( $this->passedArgs, 'optionid', '' );
        $location =  Sanitize::getString( $this->passedArgs, 'location', 'content' );       
        $field_id =  Sanitize::getInt( $this->passedArgs, 'field_id', '' );
        
        $this->_db->setQuery("
            SELECT 
                fieldid,type,name,title,groupid,location 
            FROM 
                #__jreviews_fields 
            WHERE 
                fieldid = " . $field_id
            );
        $field = current($this->_db->loadAssocList());
                
        $row = new stdClass();
        if ($optionid) {
            $row = $this->FieldOption->findRow(array('conditions'=>array('optionid = ' . $optionid)));
        }
        
        $this->set(array('field'=>$field,'row'=>$row,'location'=>$location));
        
        return $this->render();
    
    }    
    
    function delete($params) 
    {
        $option_id = Sanitize::getInt($this->data,'option_id');
        if(!$option_id) return 0;
            
        $del_id = 'optionid';

        $delete = $this->FieldOption->delete($del_id,$option_id);

        if (!$delete) 
        {
            return 0;
        }
        
        return 1;
    }    
    
    function _saveOrder() 
    {
        $cid = $this->data['cid'];
        $total = count( $cid );
        $order = $this->data['order'];
        $location = Sanitize::getString($this->data['Field'],'location','content');
        $conditions = array();
        $limit = $this->limit;
        $limitstart = $this->offset;
    
        // update ordering values
        for( $i=0; $i < $total; $i++ ) 
        {
            $option = $this->FieldOption->findRow(array('conditions'=>array('optionid = ' . $cid[$i] )));
            if ($option['FieldOption']['ordering'] != $order[$i]) {
                $option['FieldOption']['ordering'] = $order[$i];
                if (!$error = $this->FieldOption->store($option)) 
                {
                    //
                }
                // remember to updateOrder this group
                $condition = 'fieldid = ' . $option['FieldOption']['fieldid'];
                $found = false;
                foreach ( $conditions as $cond )
                    if ($cond[1]==$condition) {
                        $found = true;
                        break;
                    } // if
                if (!$found) $conditions[] = array($option['FieldOption']['fieldid'], $condition);
            } // if
        } // for
    
        // execute updateOrder for each group
        foreach ( $conditions as $cond ) 
        {
//            $this->FieldOption->findRow(array('conditions'=>array('optionid = ' . $cond[0] )));
            $this->FieldOption->reorder($cond[1]);            
        } // foreach
        
        $optionsRows = $this->FieldOption->getList($this->data['FieldOption']['fieldid'], $limitstart, $limit, $total);
    
        $field_option_table = $this->listViewTable($optionsRows, $total, $location);
    
        return $field_option_table;
    }
    
    function _changeOrder()
     {         
        $this->action = 'index'; // Required for paginator helper
        $uid = Sanitize::getInt($this->data,'option_id');
        $inc = Sanitize::getVar($this->data,'direction');
        $location = Sanitize::getString($this->data['Field'],'location','content');
        $limit = $this->limit;
        $limitstart = $this->offset;
        $total = 0;
    
        // Move row
        $option = $this->FieldOption->findRow(array('conditions'=>array('optionid = ' . $uid )));
        $this->FieldOption->Result = $option;
        $this->FieldOption->move( $inc,  "fieldid = ". $option['FieldOption']['fieldid'] );
        
        $optionRows = $this->FieldOption->getList($option['FieldOption']['fieldid'], $limitstart, $limit, $total);
        
        $field_option_table = $this->listViewTable($optionRows, $total, $location);
    
        return $field_option_table;
    }
    
    /**
    * Checks if there are any field option=>field option relationships
    * 
    */
    function _controlledByCheck()
    {
        $response = array('result'=>0);
        if($field_id = Sanitize::getInt($this->data,'fieldid')) {
            $query = "
                SELECT 
                    count(*) 
                FROM 
                    #__jreviews_fieldoptions
                WHERE
                    fieldid = " . $field_id . "
                    AND
                    control_field <> ''
            ";
            $this->_db->setQuery($query);
            $count =  $this->_db->loadResult();
            $response['result'] = $count;
        }
        
        return json_encode($response);
    }       
}
