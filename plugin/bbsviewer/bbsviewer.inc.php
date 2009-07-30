<?php
/*
 * $Id: bbsviewer.inc.php,v 1.1.1.1 2005/06/12 15:38:46 youka Exp $
 */

class Plugin_bbsviewer extends Plugin
{
	function do_block($page, $param1, $param2)
	{
		if(!mb_ereg('^\s*(.+?)\s*,\s*(\d+)\s*$', $param1, $m) || $m[2] <= 0){
			throw new PluginException('引数が正しくありません。', $this);
		}
		$home = $m[1];
		$num = $m[2];
		
		$p = isset(Vars::$get['p']) ? max(0, Vars::$get['p']) : 0;
		$point = $num * $p;
		$db = DataBase::getinstance();
		$pattern = $db->escape('^' . mb_ereg_quote($home) . '/(\d+)/.+$');
		$query  = "SELECT pagename,source FROM page";
		$query .= " WHERE php('mb_ereg', '{$pattern}', pagename)";
		$query .= " ORDER BY timestamp DESC";
		$query .= " LIMIT $num OFFSET $point";
		$result = $db->query($query);
		$ret = array();
		while($row = $db->fetch($result)){
			$ret[] = $this->includepage($row['pagename'], $row['source'], $home);
		}
		
		$smarty = $this->getSmarty();
		$smarty->assign('pagename', $page->getpagename());
		if($p > 0){
			$smarty->assign('next', $p - 1);
		}
		$smarty->assign('prev', $p + 1);
		$smarty->assign('body', join("\n", $ret));
		return $smarty->fetch('bbsviewer.tpl.htm');
	}
	
	
	protected function includepage($pagename, $source, $home)
	{
		mb_ereg('^' . mb_ereg_quote($home) . '/\d+/(.+)$', $pagename, $m);
		$smarty = $this->getsmarty();
		$smarty->assign('title', $pagename);
		$smarty->assign('alias', $m[1]);
		$smarty->assign('body', convert_block($source, $pagename));
		return $smarty->fetch('include.tpl.htm');
	}
}


?>