<?php
/*
 * $Id: fuzzylink.inc.php,v 1.2 2005/07/12 00:02:19 youka Exp $
 */

 
class Command_fuzzylink extends Command 
{
	function init()
	{
		Command::getCommand('show')->attach($this);
	}
	
	
	function do_url()
	{
		if(isset(Vars::$get['key']) && Vars::$get['key'] != ''){
			return $this->show(Vars::$get['key']);
		}
		else if(isset(Vars::$get['param']) && Vars::$get['param'] == 'restruct'){
			return $this->restruct();
		}
		else{
			throw new CommandException('パラメータが足りません。', $this);
		}
	}

	
	protected function show($key)
	{
		$list = FuzzyLink::getinstance()->getpagelist($key);
		if($list == array()){
			throw new CommandException("「{$key}」で引くことの出来るページがありません。", $this);
		}
		else if(count($list) == 1){
			redirect($list[0]);
		}

		$ret['title'] = 'あいまいリンク';
		$smarty = $this->getSmarty();
		$smarty->assign('key', $key);
		foreach($list as $page){
			$smarty->append('pagelist', $page->getpagename());
		}
		$ret['body'] = $smarty->fetch('list.tpl.htm');
		return $ret;
	}
	
	
	protected function restruct()
	{
		$smarty = $this->getSmarty();
		
		$ret['title'] = 'あいまいリンクの再生成';
		
		if(isset(Vars::$post['password'])){
			if(md5(Vars::$post['password']) == ADMINPASS){
				FuzzyLink::getinstance()->restruct();
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
	
	
	function update($show, $arg)
	{
		$list = FuzzyLink::getinstance()->getpagelist($this->getcurrentPage()->getpagename());
		$smarty = $this->getSmarty();
		foreach($list as $page){
			if(!$page->equals($this->getcurrentPage())){	//現在のページは除外する。
				$smarty->append('pagelist', $page->getpagename());
			}
		}
		$this->setbody($smarty->fetch('maybe.tpl.htm'));
	}
}

?>