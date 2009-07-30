<?php
/* 
 * $Id: rename.inc.php,v 1.1.1.1 2005/06/12 15:37:46 youka Exp $
 */


class Command_rename extends Command
{
	function do_url()
	{
		if(!isset(Vars::$get['page']) || Vars::$get['page'] == ''){
			throw new CommandException('パラメータが足りません。', $this);
		}
		$page = Page::getinstance(Vars::$get['page']);
		
		if(isset(Vars::$post['newname']) && resolvepath(Vars::$post['newname']) != ''){
			$ret = $this->rename($page, Vars::$post['newname']);
		}
		else{
			$ret = $this->showform($page);
		}
		$ret['pagename'] = $page->getpagename();
		return $ret;
	}
	
	
	protected function showform($page, $newname = null)
	{
		$smarty = $this->getSmarty();
		$smarty->assign('pagename', $page->getpagename());
		if($newname != null){
			$smarty->assign('newname', resolvepath($newname));
		}
		$ret['body'] = $smarty->fetch('rename.tpl.htm');
		$ret['title'] = 'ページ名の変更';
		return $ret;
	}
	
	
	protected function rename($page, $newname)
	{
		if(!isset(Vars::$post['password']) || md5(Vars::$post['password']) != ADMINPASS){
			return $this->showform($page, Vars::$post['newname']);
		}
		$newpage = Page::getinstance(Vars::$post['newname']);
		
		$smarty = $this->getSmarty();
		$smarty->assign('pagename', $page->getpagename());
		$smarty->assign('newname', $newpage->getpagename());
		$ret['title'] = 'ページ名の変更';
		
		if($this->_rename($page, $newpage)){
			$ret['body'] = $smarty->fetch('success.tpl.htm');
		}
		else{
			$ret['body'] = $smarty->fetch('failed.tpl.htm');
		}
		return $ret;
	}


	/**
	 * ページ名を変更する（ソースコードを移動する）。
	 * 
	 * @param	Page	$page	変更前ページ
	 * @param	Page	$newpage	変更後ページ
	 * @return	bool	成功すればtrue。
	 */
	protected function _rename($page, $newpage)
	{
		if($newpage->isexist()){
			return false;
		}
		$db = DataBase::getinstance();
		$db->begin();
		
		$mail = Mail::getinstance();
		$old = $mail->setsending(false);
		$newpage->write($page->getsource());
		$page->write('');
		$mail->setsending($old);
		
		try{
			Attach::getinstance($page)->move($newpage);
		}
		catch(DBException $e){
			$db->rollback();
			return false;
		}
		
		$this->notify(array($page, $newpage));
		$this->mail($page, $newpage);
		$db->commit();
		return true;
	}

	
	protected function mail($page, $newpage)
	{
		$head = 'ページ名が変更されました。';
		$body[] = '旧ページ名：' . $page->getpagename();
		$body[] = '新ページ名：' . $newpage->getpagename();
		
		$subject = '[' . SITENAME . '] ページ名が変更されました';
		$text[] = $head;
		$text[] = '----------------------------------------------------------------------';
		$text[] = join("\n", $body);
		sendmail($subject, join("\n", $text));
	}
}

?>