<?php

class Plugin_bbs extends Plugin
{
	protected static $sqlite_pattern;
	
	
	function do_block($page, $param1, $param2)
	{
		if(trim($param1) == ''){
			throw new PluginException('引数がありません。', $this);
		}
		
		$smarty = $this->getSmarty();
		$smarty->assign('pagename', $page->getpagename());
		$smarty->assign('bbsname', trim($param1));
		$smarty->assign('name', isset(Vars::$cookie['name']) ? Vars::$cookie['name'] : '');
		return $smarty->fetch('form.tpl.htm');
	}
	
	
	function do_url()
	{
		if(!isset(Vars::$post['bbsname']) || Vars::$post['bbsname'] == ''){
			throw new PluginException('パラメータが足りません。', $this);
		}
		if(!isset(Vars::$post['text']) || Vars::$post['text'] == ''){
			redirect(Page::getinstance(isset(Vars::$post['pagename']) ? Vars::$post['pagename'] : ''));
		}
		
		$db = DataBase::getinstance();
		self::$sqlite_pattern = '^' . mb_ereg_quote(Vars::$post['bbsname']) . '/(\d+)/.+$';
		$db->create_aggregate('maxbbsnum', array('Plugin_bbs', 'sqlite_maxbbsnum'), array('Plugin_bbs', 'sqlite_maxbbsnum_finalize'), 1);
		$row = $db->fetch($db->query("SELECT maxbbsnum(pagename) FROM page"));
		$num = $row[0] + 1;
		
		$subject = isset(Vars::$post['subject']) && trim(Vars::$post['subject']) != '' ? trim(Vars::$post['subject']) : '（無題）';
		$page = Page::getinstance(Vars::$post['bbsname'] . "/{$num}/{$subject}");
		$smarty = $this->getSmarty();
		$smarty->assign('timestamp', time());
		$name = isset(Vars::$post['name']) ? trim(Vars::$post['name']) : '';
		$smarty->assign('name', $name);
		$smarty->assign('text', Vars::$post['text']);
		$page->write($smarty->fetch('bbs.tpl'));
		setcookie('name', $name, time()+60*60*24*30);
		redirect($page);
	}
	
	
	function sqlite_maxbbsnum(&$context, $string)
	{
		if(mb_ereg(self::$sqlite_pattern, $string, $m)){
			if($m[1] > $context){
				$context = $m[1];
			}
		}
	}
	
	
	function sqlite_maxbbsnum_finalize(&$context)
	{
		return (int)$context;
	}
}


?>
