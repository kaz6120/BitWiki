<?php
/* 
 * $Id: backup.inc.php,v 1.2 2005/06/19 10:12:24 youka Exp $
 */



class Command_backup extends Command
{
	function do_url()
	{
		if(!isset(Vars::$get['param'])){
			throw new CommandException('パラメータが足りません。', $this);
		}
		
		switch(Vars::$get['param']){
			case 'list':
				return $this->showlist();
			case 'show':
				return $this->show();
			case 'diff':
				return $this->diff();
			case 'diffnow':
				return $this->diffnow();
			case 'source':
				return $this->source();
			case 'restore':
				return $this->restore();
			case 'delete':
				return $this->delete();
			default:
				throw new CommandException('パラメータがちがいます。', $this);
		}
	}
	
	
	protected function showlist()
	{
		if(isset(Vars::$get['page'])){
			$page = Page::getinstance(Vars::$get['page']);
			$smarty = $this->getSmarty();
			$smarty->assign('pagename', $page->getpagename());
			$smarty->assign('list', $page->getbackup());
			$ret['title'] = $page->getpagename() . ' のバックアップ一覧';
			$ret['body'] = $smarty->fetch('list.tpl.htm');
			$ret['pagename'] = $page->getpagename();
		}
		else{
			$db = DataBase::getinstance();
			$query  = "SELECT DISTINCT pagename FROM pagebackup ORDER BY pagename ASC";
			$result = $db->query($query);
			$list = array();
			while($row = $db->fetch($result)){
				$list[] = $row['pagename'];
			}
			$smarty = $this->getSmarty();
			$smarty->assign('list', $list);
			$ret['title'] = 'バックアップ一覧';
			$ret['body'] = $smarty->fetch('alllist.tpl.htm');
		}
		return $ret;
	}
	
	
	protected function show()
	{
		if(!isset(Vars::$get['page'])){
			throw new CommadnException('パラメータが足りません。', $this);
		}
		
		$num = isset(Vars::$get['num']) ? Vars::$get['num'] : 1;
		$page = Page::getinstance(Vars::$get['page']);
		$source = $page->getsource($num);
		$timestamp = $page->gettimestamp($num);
		
		$ret['title'] = $page->getpagename() . ' のバックアップ';
		$smarty = $this->getSmarty();
		$smarty->assign('pagename', $page->getpagename());
		$smarty->assign('timestamp', $timestamp);
		$smarty->assign('backupnumber', $num);
		$smarty->assign('body', convert_block($source, $page->getpagename()));
		$ret['body'] = $smarty->fetch('show.tpl.htm');
		$ret['pagename'] = $page->getpagename();
		return $ret;
	}
	
	
	protected function diff()
	{
		if(!isset(Vars::$get['page'])){
			throw new CommadnException('パラメータが足りません。', $this);
		}
		
		$num = isset(Vars::$get['num']) ? Vars::$get['num'] : 1;
		$page = Page::getinstance(Vars::$get['page']);
		$timestamp = $page->gettimestamp($num);
		$diff = diff($page->getsource($num+1), $page->getsource($num));
		
		$ret['title'] = $page->getpagename() . ' のバックアップ差分';
		$smarty = $this->getSmarty();
		$smarty->assign('pagename',  $page->getpagename());
		$smarty->assign('timestamp', $timestamp);
		$smarty->assign('backupnumber', $num);
		$smarty->assign('diff', $diff);
		$ret['body'] = $smarty->fetch('diff.tpl.htm');
		$ret['pagename'] = $page->getpagename();
		return $ret;
	}
	
	
	protected function diffnow()
	{
		if(!isset(Vars::$get['page'])){
			throw new CommadnException('パラメータが足りません。', $this);
		}
		
		$num = isset(Vars::$get['num']) ? Vars::$get['num'] : 1;
		$page = Page::getinstance(Vars::$get['page']);
		$timestamp = $page->gettimestamp($num);
		$diff = diff($page->getsource($num), $page->getsource(0));
		$renderer = new DiffRenderer($diff);
		
		$ret['title'] = $page->getpagename() . ' の現在との差分';
		$smarty = $this->getSmarty();
		$smarty->assign('pagename',  $page->getpagename());
		$smarty->assign('timestamp', $timestamp);
		$smarty->assign('backupnumber', $num);
		$smarty->assign('diff', $diff);
		$ret['body'] = $smarty->fetch('diffnow.tpl.htm');
		$ret['pagename'] = $page->getpagename();
		return $ret;
	}
	
	
	protected function source()
	{
		if(!isset(Vars::$get['page'])){
			throw new CommadnException('パラメータが足りません。', $this);
		}
		
		$num = isset(Vars::$get['num']) ? Vars::$get['num'] : 1;
		$page = Page::getinstance(Vars::$get['page']);
		$source = $page->getsource($num);
		$timestamp = $page->gettimestamp($num);
		
		$ret['title'] = $page->getpagename() . ' のバックアップ';
		$smarty = $this->getSmarty();
		$smarty->assign('pagename', $page->getpagename());
		$smarty->assign('timestamp', $timestamp);
		$smarty->assign('backupnumber', $num);
		$smarty->assign('source', $source);
		$ret['body'] = $smarty->fetch('source.tpl.htm');
		$ret['pagename'] = $page->getpagename();
		return $ret;
	}
	
	
	protected function restore()
	{
		if(!isset(Vars::$get['page'])){
			throw new CommadnException('パラメータが足りません。', $this);
		}
		
		$num = isset(Vars::$get['num']) ? Vars::$get['num'] : 1;
		$page = Page::getinstance(Vars::$get['page']);
		
		$ret['title'] = $page->getpagename() . ' の復元';
		$smarty = $this->getSmarty();
		$smarty->assign('pagename', $page->getpagename());
		$smarty->assign('source', $page->getsource($num));
		$smarty->assign('seed', md5($page->getsource()));
		$ret['body'] = $smarty->fetch('restore.tpl.htm');
		$ret['pagename'] = $page->getpagename();
		return $ret;
	}
	
	
	protected function delete()
	{
		if(!isset(Vars::$get['page'])){
			throw new CommadnException('パラメータが足りません。', $this);
		}

		$page = Page::getinstance(Vars::$get['page']);
		
		$ret['title'] = "バックアップの削除";
		$smarty = $this->getSmarty();
		$smarty->assign('pagename', $page->getpagename());
		if(isset(Vars::$post['password'])){
			if(md5(Vars::$post['password']) == ADMINPASS){
				$page->deletebackup();
				$ret['body'] = $smarty->fetch('delete_end.tpl.htm');
			}
			else{
				$smarty->assign('iserror', true);
				$ret['body'] = $smarty->fetch('delete.tpl.htm');
			}
		}
		else{
			$ret['body'] = $smarty->fetch('delete.tpl.htm');
		}
		$ret['pagename'] = $page->getpagename();
		return $ret;
	}
}


?>
