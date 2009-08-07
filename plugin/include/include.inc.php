<?php
/*
 * $Id: include.inc.php,v 1.1 2005/06/14 09:22:47 youka Exp $
 */

class Plugin_include extends Plugin
{
	function do_block($page, $param1, $param2)
	{
		$p = Page::getinstance($param1);
		if(!$p->isexist() || $p->isnull()){
			return '<p class="warning">ページがありません。</p>';
		}
		
		$smarty = $this->getSmarty();
		$smarty->assign('title', $p->getpagename());
		$smarty->assign('body', convert_Page($p));
		return $smarty->fetch('include.tpl.htm');
	}
}


?>