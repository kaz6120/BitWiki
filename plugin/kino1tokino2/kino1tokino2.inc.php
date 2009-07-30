<?php
/*
 * $Id: kino1tokino2.inc.php,v 1.1 2005/07/14 08:30:29 youka Exp $
 */


class Plugin_kino1tokino2 extends Plugin
{
	function do_url()
	{
		$smarty = $this->getSmarty();
		
		$ret['title'] = 'KinoWiki1からの移行';
		
		if(isset(Vars::$post['password'])){
			if(md5(Vars::$post['password']) == ADMINPASS){
				$smarty->assign('unconverted', $this->kino1tokino2());
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
	
	
	protected function kino1tokino2()
	{
		set_time_limit(0);
		$unconverted = array();
		$db = DataBase::getinstance();
		$db->begin();
		
		$_path = $db->escape(realpath(DATA_DIR . WIKIID . '.db.kino1'));
		$db->exec("ATTACH DATABASE '{$_path}' as kino1");
		
		
		$query  = 'SELECT pagename FROM kino1.page';
		$query .= ' WHERE pagename IN (SELECT pagename FROM purepage)';
		$unconverted = $db->fetchsinglearray($db->query($query));
		
		$query  = 'INSERT INTO purepage';
		$query .= ' SELECT pagename, NULL, source, timestamp, timestamp FROM kino1.page';
		$query .= '  WHERE pagename NOT IN (SELECT pagename FROM purepage)';
		$db->exec($query);
		
		
		AutoLink::getinstance()->refresh();
		
		
		$db->exec('DELETE FROM pagebackup');
		
		
		$query  = 'SELECT main.attach.pagename, main.attach.filename FROM attach';
		$query .= ' INNER JOIN kino1.attach ON main.attach.pagename = kino1.attach.pagename AND main.attach.filename = kino1.attach.filename';
		$result = $db->query($query);
		while($row = $db->fetch($result)){
			$unconverted[] = "{$row['main.attach.pagename']} の添付ファイル {$row['main.attach.filename']}";
		}
		
		$query  = 'INSERT OR IGNORE INTO attach';
		$query .= ' SELECT * FROM kino1.attach';
		$db->exec($query);
		
		$db->commit();
		$db->exec("DETACH DATABASE kino1");
		return $unconverted;
	}
}

?>