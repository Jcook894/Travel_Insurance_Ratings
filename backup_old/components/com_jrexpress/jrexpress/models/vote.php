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

class VoteModel extends MyModel  {
		
	var $name = 'Vote';
	
	var $useTable = '#__jreviews_votes AS `Vote`';
	
	// Process votes queue from #__jreviews_votes_tmp into #__jreviews_votes
	function summarizeVotes($period) {
				
		//read records in queue that will get updated based on age
		$now = date( "Y-m-d H:i:s", time() );
		
		$this->_db->setQuery("select id,created from `#__jreviews_votes_tmp`\n"
							."order by reviewid");
							
		$votes_tmp_age = $this->_db->loadObjectList();

		$queue = array();
		
		$expiration_time = 60*$period; // in minutes

		if (!count($votes_tmp_age)>0) return false;

		foreach ($votes_tmp_age as $record) {
			$life = strtotime($now) - strtotime($record->created);
			if ($life > $expiration_time*60)
			$queue[] = $record->id;
		}
		
		$queue = implode(',',$queue);

		if ($queue=='') return false;

		//get list of queued votes in tmp table that are old enough
		$this->_db->setQuery("select reviewid, sum(yes) as yes,sum(no) as no\n"
							."from #__jreviews_votes_tmp where id in ($queue)\n"
							."group by reviewid order by reviewid");
							
		$votes_tmp = $this->_db->loadObjectList();
		
		if(is_null($votes_tmp)) return false;

		//get list of reviewids already voted on
		$this->_db->setQuery("select reviewid from #__jreviews_votes order by reviewid");
		
		$reviewids = $this->_db->loadResultArray();
		
		if (!count($reviewids)>0) {
			$reviewids = array();
		}

		$updates = array();

		//build sql update array
		foreach ($votes_tmp as $vote_tmp) {

			if(in_array($vote_tmp->reviewid,$reviewids)) {

				$updates[] = "update #__jreviews_votes set yes=yes+". $vote_tmp->yes.","
				. "\n no=no+".$vote_tmp->no
				. "\n where reviewid = '$vote_tmp->reviewid'";

		   } else {

		   	$updates[] = "insert into #__jreviews_votes set"
				. "\n reviewid='$vote_tmp->reviewid',"
				. "\n yes='$vote_tmp->yes',"
				. "\n no='$vote_tmp->no'";

		   }
		}

		//process sql update array
		foreach ($updates as $update) {
			$this->_db->setQuery($update);
			$this->_db->query();
		}

		$this->_db->setQuery("delete from #__jreviews_votes_tmp where id in ($queue)");
		$this->_db->query();

	}

}