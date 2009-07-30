<?php
/*
 * $Id: blogrss10.inc.php,v 1.1 2005/12/05 03:12:10 youka Exp $
 */


class Plugin_blogrss10 extends Plugin 
{
	public function do_inline($page, $param1, $param2)
	{
		$arg = array_map('trim', explode(',', $param1));
		if($arg[0] == ''){
			throw new PluginException('引数がありません。', $this);
		}
		
		$blogname = '&amp;blogname=' . urlencode($arg[0]);
		$num = isset($arg[1]) && $arg[1] > 0 ? '&amp;recent=' . (int)$arg[1] : '';
		return '<a href="' . SCRIPTURL . '?plugin=blogrss10' . $blogname . $num . '"><img src="' . dirname(SCRIPTURL) . '/' . PLUGIN_DIR . 'blogrss10/rss10.png"></a>';
	}
	
	
	public function do_url()
	{
		if(!isset(Vars::$get['blogname']) || trim(Vars::$get['blogname']) == ''){
			throw new PluginException('パラメータが足りません。', $this);
		}
		$blogname = trim(Vars::$get['blogname']);
		$num = isset(Vars::$get['recent']) && Vars::$get['recent'] > 0 ? (int)Vars::$get['recent'] : 15;
		
		$db = DataBase::getinstance();
		$_exp = $db->escape('^' . mb_ereg_quote($blogname) . '/\d{4}-\d{2}-\d{2}/');
		
		$query  = "SELECT pagename,timestamp FROM page";
		$query .= " WHERE php('mb_ereg', '$_exp', pagename)";
		$query .= " ORDER BY timestamp DESC, pagename ASC LIMIT $num";
		$result = $db->query($query);
		
		$list = array();
		$prefixsize = mb_strlen($blogname . '/9999-99-99/');
		while($row = $db->fetch($result)){
			$item['timestamp'] = $row['timestamp'];
			$item['pagename'] = mb_substr($row['pagename'], $prefixsize);
			$item['url'] = getURL(Page::getinstance($row['pagename']));
			$list[] = $item;
		}
		
		$smarty = $this->getSmarty();
		$smarty->assign('rssurl', SCRIPTURL . '?' . htmlspecialchars($_SERVER['QUERY_STRING']));
		$smarty->assign('sitename', SITENAME);
		$smarty->assign('blogurl', getURL(Page::getinstance($blogname)));
		$smarty->assign('blogname', $blogname);
		
		$smarty->assign('list', $list);
		header('Content-Type: application/xml; charset=UTF-8');
		header('Last-Modified: ' . gmdate( 'D, d M Y H:i:s', $item['timestamp'][0] ) . ' GMT' );
		$smarty->display('blogrss10.tpl.htm');
		exit();
	}
}

?>