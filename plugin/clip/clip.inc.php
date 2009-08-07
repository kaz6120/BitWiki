<?php
/*
 * $id: $
 *
 * このプラグインはgorouさんのclipプラグインを元に作られています。
 */

class Plugin_clip extends Plugin
{
	//作成するページのプレフィックス
	//memoと指定すると memo/YYYY-MM-DD という名前のページが作成される
	private $pagename_prefix = 'clip';
	
	function do_url()
	{
		if(isset(Vars::$post['param']) && Vars::$post['param'] == 'write'){
			return $this->write();
		}
		else{
			return $this->showform();
		}
	}
	
	
	private function showform()
	{
		if(!keys_exists(Vars::$get, 'title', 'url')){
			return $this->makeerrormessage_url('引数が足りません。');
		}
		
		$smarty = $this->getSmarty();
		$smarty->assign('title', Vars::$get['title']);
		$smarty->assign('url', Vars::$get['url']);
		$ret['title'] = 'clipプラグイン';
		$ret['body'] = $smarty->fetch('form.tpl.htm');
		return $ret;
	}
	
	
	private function write()
	{
		if(!keys_exists(Vars::$post, 'title', 'url', 'comment')){
			return $this->makeerrormessage_url('引数が足りません。');
		}
		
		$title = trim(Vars::$post['title']);
		$url = trim(Vars::$post['url']);
		$comment = trim(Vars::$post['comment']);
		
		if($title == ''){
			$title = 'no title';
		}
				
		$page = Page::getinstance($this->pagename_prefix . '/' . date('Y-m-d'));
		$source[] = ':' . mb_ereg_replace(':', '&#x3a;', $title) . ':' . $url;
		$source[] = mb_ereg_replace("[\r\n]+", "\n", $comment);
		$page->write($page->getsource() . "\n" . linetrim(join("\n", $source)));
		redirect($page);
	}
}
?>