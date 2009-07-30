<?php
/*
 * $Id: blogviewer.inc.php,v 1.2 2005/06/14 10:52:57 youka Exp $
 */

//require_once(PLUGIN_DIR . '/blognavi/plugin.inc.php');


class Plugin_blogviewer extends Plugin
{
	function do_block($page, $param1, $param2)
	{
		if(!mb_ereg('^\s*(.+?)\s*,\s*(\d+)\s*$', $param1, $m) || $m[2] <= 0){
			throw new PluginException('引数が正しくありません。', $this);
		}
		$home = $m[1];
		$num = $m[2];
		
		$p = isset(Vars::$get['p']) ? max(0, Vars::$get['p']) : 0;
		
		$datelist = Plugin_blognavi_DateList::getinstance($home);
		$collist = Plugin_blognavi_ColumnList::getinstance($datelist);
		$pagename = $collist->getlast();
		for($i = 0; $i < $num*$p; $i++){
			$pagename = $collist->getprev($pagename);
		}
		$ret = array();
		for($i = 0; $i < $num; $i++){
			if($pagename == null){
				break;
			}
			$ret[] = $this->includepage($pagename);
			$pagename = $collist->getprev($pagename);
		}
		
		$smarty = $this->getSmarty();
		$smarty->assign('pagename', $page->getpagename());
		if($p > 0){
			$smarty->assign('next', $p - 1);
		}
		$smarty->assign('prev', $p + 1);
		$smarty->assign('body', join("\n", $ret));
		return $smarty->fetch('blogviewer.tpl.htm');
	}
	
	
	protected function includepage($pagename)
	{
		$source = mb_ereg_replace('#blognavi', '', Page::getinstance($pagename)->getsource());
		$smarty = $this->getSmarty();
		$smarty->assign('title', $pagename);
		$smarty->assign('body', convert_block($source, $pagename));
		return $smarty->fetch('include.tpl.htm');
	}
}


?>