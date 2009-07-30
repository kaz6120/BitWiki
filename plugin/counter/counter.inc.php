<?php
/* 
 * $Id: counter.inc.php,v 1.1.1.1 2005/06/12 15:38:46 youka Exp $
 */

class Plugin_counter extends Plugin
{
	protected static $count;
	
	
	function do_inline($page, $param1, $param2)
	{
		switch(trim($param1)){
			case 'today':
				return self::$count['today'];
			case 'yesterday':
				return self::$count['yesterday'];
			default:
				return self::$count['total'];
		}
	}
	
	
	function doing()
	{
		$db = DataBase::getinstance();
		$db->begin();
		
		if(!$db->istable('plugin_counter')){
			$db->exec(file_get_contents(PLUGIN_DIR . 'counter/counter.sql'));
		}
		
		$_pagename = $db->escape($this->getcurrentPage()->getpagename());
		$query  = "SELECT total,today,yesterday,date FROM plugin_counter";
		$query .= " WHERE pagename = '$_pagename'";
		$count = $db->fetch($db->query($query));
		
		$time = time();
		$date = date('Y-m-d', $time);
		if($count == null || $date != $count['date']){
			$yesterday = date('Y-m-d', $time - 24*60*60);
			$count['total'] = isset($count['total']) ? $count['total'] + 1 : 1;
			$count['yesterday'] = isset($count['date']) && $count['date'] == $yesterday ? $count['today'] : 0;
			$count['today'] = 1;
			$query  = "INSERT OR REPLACE INTO plugin_counter";
			$query .= " (pagename, total, today, yesterday, date)";
			$query .= " VALUES('$_pagename', {$count['total']}, {$count['today']}, {$count['yesterday']}, '$date')";
		}
		else{
			$count['total']++;
			$count['today']++;
			$query  = "UPDATE plugin_counter";
			$query .= " SET total = total + 1, today = today + 1";
			$query .= " WHERE pagename = '$_pagename'";
		}
		$db->query($query);
		
		self::$count = $count;
			
		$db->commit();
	}
}

?>