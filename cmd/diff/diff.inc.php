<?php
/* 
 * $Id: diff.inc.php,v 1.2 2005/06/19 10:13:42 youka Exp $
 */



class Command_diff extends Command
{
	public function do_url()
	{
		if(!isset(Vars::$get['page'])){
			throw new CommandException('パラメータが足りません。', $this);
		}
		
		$page = Page::getinstance(Vars::$get['page']);
		
		$ret['title'] = $page->getpagename() . ' の変更点';
		$smarty = $this->getSmarty();
		$smarty->assign('diff', diff($page->getsource(1), $page->getsource(0)));
		$ret['body'] = $smarty->fetch('diff.tpl.htm');
		$ret['pagename'] = $page->getpagename();
		return $ret;
	}
}


?>