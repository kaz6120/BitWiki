<?php
/* 
 * $Id: comment.inc.php,v 1.2 2005/06/29 10:41:15 youka Exp $
 */



class Plugin_comment extends Plugin
{
	function do_block($page, $param1, $param2)
	{
		static $count = array();
		
		
		if(mb_ereg('^(.*?),(.*)$', $param1, $m)){
			$type = strtolower(trim($m[1])) == 'below' ? 'below' : 'above';
			$input = strtolower(trim($m[2])) == 'textarea' ? 'textarea' : 'line';
		}
		else{
			$type = 'above';
			$input = 'line';
		}
		
		$smarty = $this->getsmarty();
		if(!isset($count[$page->getpagename()])){
			$count[$page->getpagename()] = 0;
		}
		$smarty->assign('num', $count[$page->getpagename()]++);
		$smarty->assign('type', $type);
		$smarty->assign('pagename', $page->getpagename());
		$smarty->assign('name', isset(Vars::$cookie['name']) ? Vars::$cookie['name'] : '');
		if($input == 'textarea'){
			return $smarty->fetch('textarea.tpl.htm');
		}
		else{
			return $smarty->fetch('line.tpl.htm');
		}
	}
	
	
	function do_url()
	{
		if(!keys_exists(Vars::$post, 'num', 'type', 'pagename')){
			throw new PluginException('パラメータが足りません。', $this);
		}
		if(trim(Vars::$post['text']) == ''){
			redirect(Page::getinstance(Vars::$post['pagename']));
		}
		
		$page = Page::getinstance(Vars::$post['pagename']);
		$source = explode("\n", Page::getinstance(Vars::$post['pagename'])->getsource());
		$count = 0;
		for($i = 0; $i < count($source); $i++){
			if(mb_ereg('^#comment(?:\s|\(|{|$)', $source[$i])){
				if($count == Vars::$post['num']){
					$name = trim(Vars::$post['name']);
					$text = mb_ereg_replace('{', '&173;', Vars::$post['text']);
					$text = mb_ereg_replace('}', '&175;', $text);
					$time = date('Y-m-d H:i:s', time());
					$str = ":[[{$name}>UserPage/{$name}]] &size(80%){{$time}}:";
					$str .= mb_ereg("\n", $str) ? "\n#block{{$text}}" : " $text";
					$n = $i + (Vars::$post['type'] == 'below' ? 1 : 0);
					array_splice($source, $n, 0, $str);
					$page->write(join("\n", $source));
					setcookie('name', $name, time()+60*60*24*30);
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

?>