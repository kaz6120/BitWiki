<?php
/* 
 * $Id: edit.inc.php,v 1.2 2005/06/14 22:57:50 youka Exp $
 */



class Command_edit extends Command 
{
	function do_url()
	{
		if($_SERVER['REQUEST_METHOD'] == 'POST'){
			if(!keys_exists(Vars::$post, 'pagename', 'source', 'seed')){
				throw new CommandException('パラメータが足りません。', $this);
			}
			if(Vars::$post['pagename'] == ''){
				throw new CommandException('ページ名に空文字列は使えません。', $this);
			}
			
			if(isset(Vars::$post['post'])){
				return $this->write();
			}
			else if(isset(Vars::$post['preview'])){
				return $this->preview();
			}
			else if(isset(Vars::$post['cancel'])){
				return $this->cancel();
			}
			else{
				throw new CommandException('パラメータが正しくありません。', 'edit');
			}
		}
		else{
			return $this->edit();
		}
	}
	
	
	/**
	 * 書き込みフォームを用意する。
	 * 
	 * @param	string	$pagename	ページ名（エンコード無し）
	 * @param	string	$source	ページ内容。
	 * @param	bool	$notimestamp	タイムスタンプ更新なしのオプション
	 * @param	string	$seed	同時更新回避の識別子
	 */
	protected function getpostform($pagename, $source = null, $notimestamp = false, $seed = null)
	{
		if($pagename == ''){
			throw new CommandException('ページ名に空文字列は使えません。', $this);
		}
		
		if($source == null){
			$page = Page::getinstance($pagename);
			$source = $page->getsource();
			$seed = md5($source);
			if($source == ''){
				$source = Page::getinstance(getdirname($pagename) . '/:template')->getsource();
			}
		}
		else if($seed == null){
			$seed = md5($source);
		}
		
		$smarty = $this->getSmarty();
		$smarty->assign('pagename', $pagename);
		$smarty->assign('source', $source);
		$smarty->assign('seed', $seed);
		$smarty->assign('notimestamp', $notimestamp);
		return $smarty->fetch('postform.tpl.htm');
	}
	
	
	/**
	 * ポストされたデータを元に書き込む。
	 */
	protected function write()
	{
		$source = mb_ereg_replace('\r?\n', "\n", Vars::$post['source']);
		$seed = Vars::$post['seed'];
		$notimestamp = (isset(Vars::$post['notimestamp']) && Vars::$post['notimestamp'] == 'on') ? true : false;
		$page = Page::getinstance(Vars::$post['pagename']);
		
		if($seed != md5($page->getsource())){
			$ret['title'] = '更新が衝突しました';
			$smarty = $this->getSmarty();
			$smarty->assign('pagename', $page->getpagename());
			$smarty->assign('diff', diff($page->getsource(), $source));
			$smarty->assign('form', $this->getpostform($page->getpagename(), $source, $notimestamp, md5($page->getsource())));
			$ret['body'] = $smarty->fetch('conflict.tpl.htm');
			$ret['pagename'] = $page->getpagename();
			return $ret;
		}
		else{
			if($source != $page->getsource()){ //内容に変更がある場合のみ更新
				$page->write($source, $notimestamp);
				$this->notify(array('write', $page));
			}
			redirect($page);
		}
	}
	
	
	/**
	 * プレビューを表示する。
	 */
	protected function preview()
	{
		$source = mb_ereg_replace('\r?\n', "\n", Vars::$post['source']);
		$seed = Vars::$post['seed'];
		$notimestamp = (isset(Vars::$post['notimestamp']) && Vars::$post['notimestamp'] == 'on') ? true : false;
		$page = Page::getinstance(Vars::$post['pagename']);
		
		$ret['title'] = $page->getpagename() . ' のプレビュー';
		$smarty = $this->getSmarty();
		$smarty->assign('preview', convert_block($source, $page->getpagename()));
		$smarty->assign('form', $this->getpostform($page->getpagename(), $source, $notimestamp, $seed));
		$ret['body'] = $smarty->fetch('preview.tpl.htm');
		$ret['pagename'] = $page->getpagename();
		return $ret;
	}
	
	
	/**
	 * 編集を取り止める。
	 */
	protected function cancel()
	{
		redirect(Page::getinstance(Vars::$post['pagename']));
	}		
	
	
	/**
	 * 編集画面を表示する。
	 */
	protected function edit()
	{
		if(isset(Vars::$get['page'])){
			$page = Page::getinstance(Vars::$get['page']);
			if(!$page->isnull()){
				$ret['title'] = $page->getpagename() . ' の編集';
				$smarty = $this->getSmarty();
				$smarty->assign('form', $this->getpostform($page->getpagename()));
				$ret['body'] = $smarty->fetch('edit.tpl.htm');
				$ret['pagename'] = $page->getpagename();
				return $ret;
			}
		}
		
		throw new CommandException('引数が正しくありません。', $this);
	}
}


?>