<?php
/*
 * $Id: rss10.inc.php,v 1.5 2005/12/05 03:35:06 youka Exp $
 *
 * このプラグインはhaltさんのrss10コマンドを元に作られています。
 */


class Plugin_rss10 extends Plugin 
{
	public function do_inline($page, $param1, $param2)
	{
		$arg = array_map('trim', explode(',', $param1, 2));
		$num = $arg[0] > 0 ? '&amp;recent=' . (int)$arg[0] : '';
		$include = isset($arg[1]) && $arg[1] != '' ? '&amp;include=' . urlencode($arg[1]) : '';
		$include = isset($param2) && trim($param2) != '' ? '&amp;exp=' . urlencode(trim($param2)) : $include;
		return '<a href="' . SCRIPTURL . '?plugin=rss10' . $num . $include . '"><img src="' . dirname(SCRIPTURL) . '/' . PLUGIN_DIR . 'rss10/rss10.png"></a>';
	}
	
	
	public function do_url()
	{
		$num = isset(Vars::$get['recent']) && Vars::$get['recent'] > 0 ? (int)Vars::$get['recent'] : 15;
		
		$db = DataBase::getinstance();
		$query  = "SELECT pagename,timestamp FROM page";
		if(isset(Vars::$get['exp']) && trim(Vars::$get['exp']) != ''){
			$_inc = $db->escape(Vars::$get['exp']);
			$query .= " WHERE php('mb_ereg', '$_inc', pagename)";
		}
		else if(isset(Vars::$get['include']) && trim(Vars::$get['include']) != ''){
			$_inc = $db->escape(glob2ereg(Vars::$get['include']));
			$query .= " WHERE php('mb_ereg', '$_inc', pagename)";
		}
		$query .= " ORDER BY timestamp DESC, pagename ASC LIMIT $num";
		$result = $db->query($query);
		
		$list = array();
		while($row = $db->fetch($result)){
			$item['timestamp'] = $row['timestamp'];
			$item['pagename'] = $row['pagename'];
			$item['url'] = getURL(Page::getinstance($row['pagename']));
			$list[] = $item;
		}
		
		$smarty = $this->getSmarty();
		$smarty->assign('rssurl', SCRIPTURL . '?' . htmlspecialchars($_SERVER['QUERY_STRING']));
		$smarty->assign('sitename', SITENAME);
		$smarty->assign('baseurl', SCRIPTURL);
		$smarty->assign('list', $list);
		header('Content-Type: application/xml; charset=UTF-8');
		header('Last-Modified: ' . gmdate( 'D, d M Y H:i:s', $item['timestamp'][0] ) . ' GMT' );
		$smarty->display('rss10.tpl.htm');
		exit();
	}
}

?>