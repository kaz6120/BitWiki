<?php
/* 
 * $Id: trackback.inc.php,v 1.2 2005/06/27 22:54:44 youka Exp $
 */



class Plugin_trackback extends Plugin
{
	function init()
	{
		$db = DataBase::getinstance();
		$db->begin();
		if(!$db->istable('plugin_trackback')){
			$db->exec(file_get_contents(PLUGIN_DIR . 'trackback/trackback.sql'));
		}
		$db->commit();
		
		Command::getCommand('show')->attach($this);
	}
	
	
	function update($show, $arg)
	{
		if($arg == 'done'){
			$page = $this->getcurrentPage();
			Renderer::getinstance()->setoption('plugin_trackback_pingurlrdf' , $this->getpingurlrdf($page));
			
			$list = $this->getlist($page);
			if(count($list) > 0){
				$smarty = $this->getSmarty();
				$smarty->assign('pagename', $page->getpagename());
				$smarty->assign('trackback', $list);
				$this->setbody($smarty->fetch('list.tpl.htm'));
			}
		}
	}
	
	
	function do_inline($page, $param1, $param2)
	{
		$num = $this->countreceived($page);
		$path = SCRIPTURL;
		$pagename = rawurlencode($page->getpagename());
		return "<a href=\"{$path}?plugin=trackback&amp;param=show&amp;page={$pagename}\">TrackBack({$num})</a>";
	}
	
	
	function do_url()
	{
		if(isset(Vars::$get['param']) && Vars::$get['param'] == 'ping'){
			return $this->receive();
		}
		else{
			return $this->show();
		}
	}
	
	
	protected function show()
	{
		if(!isset(Vars::$get['page'])){
			throw new PluginException('パラメータが足りません。', $this);
		}
		$page = Page::getinstance(Vars::$get['page']);
		
		$ret['title'] = $page->getpagename() . ' へのTrackBack';
		$smarty = $this->getSmarty();
		$smarty->assign('pagename', $page->getpagename());
		$smarty->assign('pingurl', $this->getpingurl($page));
		$smarty->assign('trackback', $this->getlist($page));
		$ret['body'] = $smarty->fetch('show.tpl.htm');
		return $ret;
	}
	
	
	protected function receive()
	{
		if(!isset(Vars::$get['page']) || !Page::getinstance(Vars::$get['page'])->isexist()){
			$smarty = $this->getSmarty();
			$smarty->assign('errormes', 'unreceivable page');
			$smarty->display('receive_fail.tpl.htm');
			exit;
		}
		
		if(isset(Vars::$post['url'])){
			$data =& Vars::$post;
		}
		else if(isset(Vars::$get['url'])){
			$data =& Vars::$get;
		}
		else{
			$smarty = $this->getSmarty();
			$smarty->assign('errormes', 'no url');
			$smarty->display('receive_fail.tpl.htm');
			exit;
		}

		if(!mb_ereg('^' . EXP_URL . '$', $data['url'])){
			$smarty = $this->getSmarty();
			$smarty->assign('errormes', 'invalid url');
			$smarty->display('receive_fail.tpl.htm');
			exit;
		}
		
		$page = Page::getinstance(Vars::$get['page']);
		$title = isset($data['title']) ? $data['title'] : '';
		$excerpt = isset($data['excerpt']) ? $data['excerpt'] : '';
		$blog_name = isset($data['blog_name']) ? $data['blog_name'] : '';
		$url = $data['url'];
	
//		$encode = mb_detect_encoding($excerpt . $blog_name . $title);
//		$title = mb_convert_encoding($title, 'UTF-8', $encode);
//		$excerpt = mb_convert_encoding($excerpt, 'UTF-8', $encode);
//		$blog_name = mb_convert_encoding($blog_name, 'UTF-8', $encode);
		
		$title = mb_strlen($title) >= 64 ? mb_substr($title, 0, 60) . '...' : $title;
		$excerpt = mb_strlen($excerpt) >= 256 ? mb_substr($excerpt, 0, 252) . '...' : $excerpt;
		
		
		$db = DataBase::getinstance();
		
		$_pagename = $db->escape($page->getpagename());
		$_title = $db->escape($title);
		$_excerpt = $db->escape($excerpt);
		$_url = $db->escape($url);
		$_blog_name = $db->escape($blog_name);
		$_timestamp = time();
		
		$query  = "INSERT INTO plugin_trackback";
		$query .= " (num, pagename, title, excerpt, url, blog_name, timestamp)";
		$query .= " VALUES(null, '$_pagename', '$_title', '$_excerpt', '$_url', '$_blog_name', $_timestamp)";
		$db->query($query);
		
		$smarty = $this->getSmarty();
		$smarty->display('receive_success.tpl.htm');
		exit;
	}
	
	
	/**
	 * 受信済みトラックバックを取得する。
	 */
	protected function getlist($page)
	{
		$db = DataBase::getinstance();
		$_pagename = $db->escape($page->getpagename());
		$query  = "SELECT num, title, excerpt, url, blog_name, timestamp FROM plugin_trackback";
		$query .= " WHERE pagename = '$_pagename'";
		$query .= " ORDER BY timestamp DESC";
		return $db->fetchall($db->query($query));
	}

	
	/**
	 * 受信済みTrackBackの数を取得する。
	 */
	protected function countreceived($page)
	{
		$db = DataBase::getinstance();
		$_pagename = $db->escape($page->getpagename());
		$query  = "SELECT count(*) FROM plugin_trackback";
		$query .= " WHERE pagename = '$_pagename'";
		$row = $db->fetch($db->query($query));
		return $row[0];
	}
	
	
	/**
	 * TrackBack Ping URLを取得する。
	 */
	protected function getpingurl($page)
	{
		return SCRIPTURL . '?plugin=trackback&amp;param=ping&amp;page=' . rawurlencode($page->getpagename());
	}
	
	
	/**
	 * TrackBack Ping URL自動検知用RDFを取得する。
	 */
	function getpingurlrdf($page)
	{
		$smarty = $this->getSmarty();
		$smarty->assign('pagename', $page->getpagename());
		return $smarty->fetch('rdf.tpl');
	}
}

?>