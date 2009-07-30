<?php
/* 
 * $Id: show.inc.php,v 1.1.1.1 2005/06/12 15:37:46 youka Exp $
 */



class Command_show extends Command 
{
	function do_url()
	{
		$page = $this->getcurrentPage();
		if($page->isexist()){
			$html = convert_Page($page);
			if(keys_exists(Vars::$get, 'word', 'type')){
				$list = mb_split('[\s　]', Vars::$get['word']);
				$smarty = $this->getSmarty();
				$smarty->assign('word', $list);
				$smarty->assign('type', Vars::$get['type']);
				$smarty->assign('body', Search::getinstance()->mark($html, $list, Vars::$get['type']));
				$html = $smarty->fetch('highlight.tpl.htm');
			}
			$ret['body'] = $html;
			$ret['title'] = $page->getpagename();
			$ret['pagename'] = $page->getpagename();
			$ret['lastmodified'] = $page->gettimestamp();
		}
		else{
			$smarty = $this->getSmarty();
			$smarty->assign('pagename', $page->getpagename());
			$ret['body'] = $smarty->fetch('notexist.tpl.htm');
			$ret['title'] = $page->getpagename() . ' は存在しません';
			$ret['pagename'] = $page->getpagename();
		}
		return $ret;
	}
}


?>