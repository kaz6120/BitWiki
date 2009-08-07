<?php
/* 
 * $Id: ls.inc.php,v 1.1.1.1 2005/06/12 15:38:46 youka Exp $
 */

class Plugin_ls extends Plugin
{
	function do_block($page, $param1, $param2)
	{
		$prefix = resolvepath(trim($param1));
		if($prefix == ''){
			$prefix = $page->getpagename();
		}
		$prefix .= '/';
		
		$db = DataBase::getinstance();
		$query  = "SELECT pagename FROM page";
		$query .= " WHERE pagename like '${prefix}%'";
		$query .= " ORDER BY pagename ASC";
		$result = $db->query($query);
		
		$list = array();
		while($row = $db->fetch($result)){
			$list[] = $row['pagename'];
		}
		if($list == array()){
			return '';
		}
		natsort($list);
		
		$len = strlen($prefix);
		foreach($list as $pagename){
			$link[] = '<li>' . makelink(Page::getinstance($pagename), substr($pagename, $len)) . '</li>';
		}
		return "<ul>\n" . join("\n", $link) . "\n</ul>\n";
	}
}

?>