<?php

class Plugin_refcount extends Plugin
{
	function do_block($page, $param1, $param2)
	{
		$smarty = $this->getSmarty();
		
		$db = DataBase::getinstance();
		$query  = "SELECT pagename, count(linker) AS pagecount, sum(times) AS total";
		$query .= " FROM page LEFT JOIN linklist ON linked = pagename";
		$query .= " GROUP BY pagename";
		$query .= " ORDER BY pagecount DESC, total DESC, pagename ASC";
		$result = $db->query($query);
		while($row = $db->fetch($result)){
			$smarty->append('list', $row);
		}
		
		return $smarty->fetch('list.tpl.htm');
	}
}

?>