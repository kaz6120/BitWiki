<?php
/* 
 * $Id: autols.inc.php,v 1.2 2005/06/14 22:58:45 youka Exp $
 */



class Plugin_autols extends Plugin
{
	const template = ':config/plugin/autols';
	
	
	function init()
	{
		Command::getCommand('edit')->attach($this);
	}
	
	
	function update($obj, $arg)
	{
		if(is_array($arg) && $arg[0] == 'write'){
			$page = $arg[1];
			if($page->isexist(0) && !$page->isexist(1)){	//新規の場合
				if(mb_ereg('^(.*)/.+?$', $page->getpagename(), $m)){
					$parent = Page::getinstance($m[1]);
					$template = Page::getinstance(self::template);
					if(!$parent->isexist() && $template->isexist()){
						$parent->write($template->getsource());
					}
				}
			}
		}
	}
}

?>