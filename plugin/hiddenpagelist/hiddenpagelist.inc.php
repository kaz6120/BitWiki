<?php
/* 
 * $Id: hiddenpagelist.inc.php,v 1.1 2005/07/18 09:24:01 youka Exp $
 */

class Plugin_hiddenpagelist extends Plugin
{
	function do_block($page, $param1, $param2)
	{
		$db = DataBase::getinstance();
		$query  = "SELECT pagename FROM allpage";
		$query .= " WHERE pagename LIKE ':%' OR pagename LIKE '%/:%";
		$query .= " ORDER BY pagename ASC";
		$result = $db->query($query);
		
		$list = $db->fetchsinglearray($result);
		if($list == array()){
			return '';
		}
		natsort($list);
		
		foreach($list as $pagename){
			$link[] = '<li>' . makelink($pagename) . '</li>';
		}
		return "<ul>\n" . join("\n", $link) . "\n</ul>\n";
	}
}

?>