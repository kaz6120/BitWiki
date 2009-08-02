<?php

class Plugin_bbsres extends Plugin
{
	function do_block($page, $param1, $param2)
	{
		static $count = array();
		
		
		$smarty = $this->getSmarty();
		if(!isset($count[$page->getpagename()])){
			$count[$page->getpagename()] = 0;
		}
		$smarty->assign('num', $count[$page->getpagename()]++);
		$smarty->assign('pagename', $page->getpagename());
		$smarty->assign('name', isset(Vars::$cookie['name']) ? Vars::$cookie['name'] : '');
		return $smarty->fetch('form.tpl.htm');
	}
	
	
	function do_url()
	{
		if(!keys_exists(Vars::$post, 'num', 'pagename')){
			throw new PluginException('パラメータが足りません。', $this);
		}
		if(trim(Vars::$post['text']) == ''){
			redirect(Page::getinstance(Vars::$post['pagename']));
		}
		
		$page = Page::getinstance(Vars::$post['pagename']);
		$source = explode("\n", Page::getinstance(Vars::$post['pagename'])->getsource());
		$count = 0;
		for($i = 0; $i < count($source); $i++){
			if(mb_ereg('^#bbsres(?:\s|\(|{|$)', $source[$i])){
				if($count == Vars::$post['num']){
					$name = isset(Vars::$post['name']) ? trim(Vars::$post['name']) : '';
					$smarty = $this->getSmarty();
					$smarty->assign('name', $name);
					$smarty->assign('text', Vars::$post['text']);
					$smarty->assign('timestamp', time());
					array_splice($source, $i, 0, $smarty->fetch('bbsres.tpl'));
					$page->write(join("\n", $source));
					setcookie('name', trim($name), time()+60*60*24*30);
					redirect($page);
				}
				$count++;
			}
		}
		
		$ret['title'] = 'error';
		$smarty = $this->getSmarty();
		$smarty->assign('text', Vars::$post['text']);
		$ret['body'] = $smarty->fetch('error.tpl.htm');
		return $ret;
	}
}

