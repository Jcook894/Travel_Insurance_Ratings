<?php
/*
    JReviews Express - user reviews for Joomla
    Copyright (C) 2009  Alejandro Schmeichler

    JReviews Express is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    JReviews Express is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/

defined( 'MVC_FRAMEWORK') or die( 'Direct Access to this location is not allowed.' );

class MyController extends S2Controller {
	
	var $stats = array();
	
	function beforeFilter() {
	
		# Set pagination vars
		$this->limit = Sanitize::getInt($this->data,'limit',Sanitize::getInt($this->passedArgs,'limit',cmsFramework::getConfig('list_limit')));			
		
		$this->page = Sanitize::getInt($this->data,'page',Sanitize::getInt($this->passedArgs,'page',1));
	
		$this->offset = (int)($this->page-1) * $this->limit;
	
        $this->name = str_replace(MVC_ADMIN . _DS, '', $this->name);
    
		// Get moderation stats
		$sql = "SELECT count(*) FROM #__content AS content"
		."\n INNER JOIN #__jreviews_categories AS jr_category ON content.catid = jr_category.id"
		."\n WHERE content.state = 0";
		$this->_db->setQuery($sql);
		$this->stats['entries_unpublished'] = $this->_db->loadResult();	
		
		$sql = "SELECT count(*) FROM #__jreviews_comments AS review"
		."\n WHERE review.published = 0"
        ."\n AND review.`mode`='com_content'"
		;
		$this->_db->setQuery($sql);
		$this->stats['reviews_user_unpublished'] = $this->_db->loadResult();			
		
		$sql = "SELECT count(*) FROM #__jreviews_report AS Report"
		. "\n INNER JOIN #__jreviews_comments AS Review WHERE Report.reviewid = Review.id"
        ."\n AND review.`mode`='com_content'"
		;
		$this->_db->setQuery($sql);
		$this->stats['review_reports'] = $this->_db->loadResult();
		
		$this->set('stats',$this->stats);
		
		return true;
	}
	
    function __parseMysqlDump($url,$prefix)
    {        
        $file_content = file($url);
        $sql = array();

        foreach($file_content as $sql_line){
             if(trim($sql_line) != '' && strpos($sql_line, '--') === false){
                 $sql[] =  str_replace('#__',$prefix,$sql_line);
             }
        }
        
        $sql = implode('',$sql);
        
        $sql = explode(';',$sql);
        
        $result = true;
        
        foreach($sql AS $sql_line) 
        {    
            if(trim($sql_line) != '' && trim($sql_line) != ';') {
                $sql_line .= ';';
                $this->_db->setQuery($sql_line);            
                $out = $this->_db->query();
                if(false!==strpos($this->_db->getErrorMsg(),"Can't DROP") || false!==strpos($this->_db->getErrorMsg(),"Duplicate key name")) {
//                    echo '<br />';
//                    echo $this->_db->getErrorMsg();
//                    echo '<p>'.$sql_line.'</p>';
//                    echo (int)$result . '<br />';                    
                } else {
//                    echo '<br />xxxxxxxxxxxxxxxxxx';
//                    echo $this->_db->getErrorMsg();
//                    echo '<p>'.$sql_line.'</p>';
//                    echo (int)$result . '<br />';                    
                    $result = $out && $result;
                }
            }
        }
        
        return $result;
        
    }        
}