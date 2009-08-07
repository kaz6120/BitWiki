<?php
/* 
 * $Id: recentdeleted.inc.php,v 1.2 2005/06/27 18:08:07 youka Exp $
 */

class Plugin_recentdeleted extends Plugin implements MyObserver 
{
	/** 削除記録を保持するページ名 */
	const LOGPAGE = 'RecentDeleted';
	
	
	function init()
	{
		Page::attach($this);
	}
	
	
	function update($page, $arg)
	{
		if(!$page->isexist() && $page->isexist(1)){
			$mailflag = Mail::getinstance()->setsending(false);
			
			$logpage = Page::getinstance(self::LOGPAGE);
			$log = '-' . date('Y-m-d (D) H:i:s') . ' [[' . $page->getpagename() . ']]';
			$logpage->write($log . "\n" . $logpage->getsource());
			
			Mail::getinstance()->setsending($mailflag);
		}
	}
}

?>