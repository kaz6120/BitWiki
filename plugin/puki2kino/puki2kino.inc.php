<?php
/*
 * $Id: puki2kino.inc.php,v 1.2 2005/07/14 08:31:34 youka Exp $
 */


class Plugin_puki2kino extends Plugin
{
	function do_url()
	{
		$smarty = $this->getSmarty();
		
		$ret['title'] = 'PukiWikiからの移行';
		
		if(isset(Vars::$post['password'])){
			if(md5(Vars::$post['password']) == ADMINPASS){
				$smarty->assign('unconverted', $this->puki2kino());
				$ret['body'] = $smarty->fetch('finish.tpl.htm');
			}
			else{
				$smarty->assign('error', true);
				$ret['body'] = $smarty->fetch('password.tpl.htm');
			}
		}
		else{
			$smarty->assign('error', false);
			$ret['body'] = $smarty->fetch('password.tpl.htm');
		}
		
		return $ret;
	}
	
	
	protected function puki2kino()
	{
		set_time_limit(0);
		$unconverted = array();
		$db = DataBase::getinstance();
		$db->begin();
		
		$dir = opendir(DATA_DIR . 'wiki');
		while(($filename = readdir($dir)) !== false){
			$path = DATA_DIR . 'wiki/' . $filename;
			if(!is_file($path) || !preg_match('/^(.+)\.txt$/', $filename, $m)){
				continue;
			}
			$eucname = substr(pack('H*', '20202020' . $m[1]), 4);
			$pagename = mb_convert_encoding($eucname, 'UTF-8', 'EUC-JP');
			
			$_pagename = $db->escape($pagename);
			$_source = $db->escape(mb_convert_encoding(file_get_contents($path), 'UTF-8', 'EUC-JP'));
			$query  = "INSERT OR IGNORE INTO purepage";
			$query .= " VALUES('$_pagename', null, '$_source'," . time() . "," . time() . ")";
			$db->query($query);
			if($db->changes() == 0){
				$unconverted[] = $pagename;
			}
		}
		AutoLink::getinstance()->refresh();
		
		
		$dir = opendir(DATA_DIR . 'attach');
		while(($filename = readdir($dir)) !== false){
			$path = DATA_DIR . 'attach/' . $filename;
			if(!is_file($path) || !preg_match('/^([0-9A-F]+)_([0-9A-F]+)$/', $filename, $m)){
				continue;
			}
			$eucname = substr(pack('H*', '20202020' . $m[1]), 4);
			$pagename = mb_convert_encoding($eucname, 'UTF-8', 'EUC-JP');
			$eucfilename = substr(pack('H*', '20202020' . $m[2]), 4);
			$filename = mb_convert_encoding($eucfilename, 'UTF-8', 'EUC-JP');
			
			$_pagename = $db->escape($pagename);
			$_filename = $db->escape($filename);
			$bin = file_get_contents($path);
			$_data = $db->escape($bin);
			$_size = strlen($bin);
			$query  = "INSERT OR IGNORE INTO attach";
			$query .= " (pagename, filename, binary, size, timestamp, count)";
			$query .= " VALUES('$_pagename', '$_filename', '$_data', $_size, " . time() . ", 0)";
			$db->query($query);
			if($db->changes() == 0){
				$unconverted[] = "{$pagename} の添付ファイル {$filename}";
			}
		}
		
		$db->commit();
		return $unconverted;
	}
}

?>