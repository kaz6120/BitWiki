<?php
/* 
 * $Id: backlink.inc.php,v 1.2 2005/07/18 02:27:16 youka Exp $
 */



class Command_backlink extends Command
{
	function init()
	{
		Command::getCommand('show')->attach($this);
	}
	
	
	function update($show, $arg)
	{
		if($arg == 'done'){
			$page = $this->getcurrentPage();
			$list = BackLink::getinstance()->getlist($page);
			if($list != array()){
				$smarty = $this->getSmarty();
				$smarty->assign('backlink', $list);
				$smarty->assign('pagename', $this->getcurrentPage()->getpagename());
				$this->setbody($smarty->fetch('backlink.tpl.htm'));
			}
		}
	}
	

	function do_url()
	{
		if(isset(Vars::$get['param']) && Vars::$get['param'] == 'restruct'){
			return $this->restruct();
		}
		else if(isset(Vars::$get['page'])){
			return $this->showall(Page::getinstance(Vars::$get['page']));
		}
		throw new CommandException('パラメータがちがいます', $this);
	}	
	
	
	protected function showall($page)
	{
		$ret['title'] = $page->getpagename() . ' の逆リンク';
		
		$smarty = $this->getSmarty();
		$smarty->assign('backlink', BackLink::getinstance()->getlist($page));
		$smarty->assign('pagename', $page->getpagename());
		$ret['body'] = $smarty->fetch('all.tpl.htm');
		return $ret;
	}
			
	
	protected function restruct()
	{
		$smarty = $this->getSmarty();
		
		$ret['title'] = '逆リンクの再生成';
		
		if(isset(Vars::$post['password'])){
			if(md5(Vars::$post['password']) == ADMINPASS){
				BackLink::getinstance()->refreshall();
				$ret['body'] = $smarty->fetch('restruct.tpl.htm');
			}
			else{
				$smarty->assign('error', true);
				$ret['body'] = $smarty->fetch('password.tpl.htm');
			}
		}
		else{
			$ret['body'] = $smarty->fetch('password.tpl.htm');
		}
		
		return $ret;
	}
}


?>
