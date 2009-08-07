<?php
/* 
 * $Id: referrer.inc.php,v 1.2 2005/08/02 14:46:57 youka Exp $
 */



class Plugin_referrer extends Plugin
{
	function init()
	{
		$db = DataBase::getinstance();
		$db->begin();
		if(!$db->istable('plugin_referrer')){
			$db->exec(file_get_contents(PLUGIN_DIR . 'referrer/referrer.sql'));
		}
		$db->commit();
		
		Command::getCommand('show')->attach($this);
	}
	
	
	function update($show, $arg)
	{
		if($arg == 'doing'){
			$this->record();
		}
		else if($arg == 'done'){
			$page = $this->getcurrentPage();

			$smarty = $this->getSmarty();
			$smarty->assign('pagename', $page->getpagename());
			$smarty->assign('referrer', $this->getlist($page));
			$this->setbody($smarty->fetch('list.tpl.htm'));
		}
	}
	
	
	protected function record()
	{
		if(isset($_SERVER['HTTP_REFERER']) && $_SERVER['HTTP_REFERER'] != ''){
			if(!mb_ereg('^' . mb_ereg_quote(SCRIPTDIR), $_SERVER['HTTP_REFERER']) && !mb_ereg('^(?:ftp|https?)://[^.]+?/', $_SERVER['HTTP_REFERER'])){
				$db = DataBase::getinstance();
				$_url = $db->escape($_SERVER['HTTP_REFERER']);
				$_pagename = $db->escape($this->getcurrentPage()->getpagename());
				$db->begin();
				$db->query("UPDATE plugin_referrer SET count = count + 1 WHERE pagename = '$_pagename' AND url = '$_url'");
				if($db->changes() == 0){
					$db->query("INSERT INTO plugin_referrer VALUES('$_pagename', '$_url', 1)");
				}
				$db->commit();
			}
		}
	}
	
	
	function do_url()
	{
		if(!isset(Vars::$get['page'])){
			throw new PluginException('パラメータが足りません。', $this);
		}
		$page = Page::getinstance(Vars::$get['page']);
		
		if(isset(Vars::$post['url']) && count(Vars::$post['url']) > 0 && isset(Vars::$post['password'])){
			if(md5(Vars::$post['password']) == ADMINPASS){
				return $this->delete($page, Vars::$post['url']);
			}
			else{
				return $this->show($page, Vars::$post['url']);
			}
		}
		return $this->show($page);
	}
	
	
	protected function delete($page, $url)
	{
		$db = DataBase::getinstance();
		$_pagename = $db->escape($page->getpagename());
		foreach($url as $u){
			$_url[] = $db->escape($u);
		}
		$_urls = '"' . join('", "',  $_url) . '"';
		$query  = 'DELETE FROM plugin_referrer';
		$query .= " WHERE pagename = \"$_pagename\" AND url IN ($_urls)";
		$db->query($query);
		
		$ret['title'] = $page->getpagename() . ' のReferrer';
		$smarty = $this->getSmarty();
		$smarty->assign('pagename', $page->getpagename());
		$smarty->assign('url', $url);
		$ret['body'] = $smarty->fetch('deleted.tpl.htm');
		return $ret;
	}
		
	
	protected function show($page, $checkedurl = array())
	{
		$ret['title'] = $page->getpagename() . ' のReferrer';
		$smarty = $this->getSmarty();
		$smarty->assign('pagename', $page->getpagename());
		$smarty->assign('referrer', $this->getlist($page));
		$smarty->assign('checkedurl', $checkedurl);
		$ret['body'] = $smarty->fetch('show.tpl.htm');
		return $ret;
	}
	
	
	/**
	 * 受信済みReferrerを取得する。
	 */
	protected function getlist($page)
	{
		$db = DataBase::getinstance();
		$_pagename = $db->escape($page->getpagename());
		$query  = "SELECT url, count FROM plugin_referrer";
		$query .= " WHERE pagename = '$_pagename'";
		$query .= " ORDER BY count DESC";
		return $db->fetchall($db->query($query));
	}
}

?>