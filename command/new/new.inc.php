<?php
/* 
 * $Id: new.inc.php,v 1.1.1.1 2005/06/12 15:37:46 youka Exp $
 */



class Command_new extends Command
{
	function do_url()
	{
		if(isset(Vars::$get['page'])){
			$dir = getdirname(Vars::$get['page']);
			$default = $dir == '' ? '' : $dir . '/';
		}
		else{
			$default = '';
		}
		
		$ret['title'] = 'ページ新規作成';
		$smarty = $this->getSmarty();
		$smarty->assign('default', $default);
		$ret['body'] = $smarty->fetch('new.tpl.htm');
		return $ret;
	}
}

