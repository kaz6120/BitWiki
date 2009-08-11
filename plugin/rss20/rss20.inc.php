<?php
/*
 * $Id: rss20.inc.php,v 0.1 2007/4/14 03:43:00 $
 * 
 * このプラグインは以下のrss10プラグインを一部変更し、RSS2.0対応にしたものです。
 * v 0.1:はっきりいってファイル名(とそれを使う部分)とテンプレート(.tpl.php)をいじっただけ。
 * v 0.2:description要素が空のままだったのを本文をHTML化したものを入れるようにした。
 *       pubDate要素の書式がRFC822準拠していなかったのを準拠するように修正。
 * 
 * > Id: rss10.inc.php,v 1.5 2005/12/05 03:35:06 youka Exp $
 * >
 * > このプラグインはhaltさんのrss10コマンドを元に作られています。
 */


class Plugin_rss20 extends Plugin 
{
	/*
	 *
	 */
	public function do_inline($page, $param1, $param2)
	{
		$arg = array_map('trim', explode(',', $param1, 2));
		$num = $arg[0] > 0 ? '&amp;recent=' . (int)$arg[0] : '';
		$include = isset($arg[1]) && $arg[1] != '' ? '&amp;include=' . urlencode($arg[1]) : '';
		$include = isset($param2) && trim($param2) != '' ? '&amp;exp=' . urlencode(trim($param2)) : $include;
		return '<a href="' . SCRIPTURL . '?plugin=rss20' . $num . $include . '"><img src="' . dirname(SCRIPTURL) . '/' . PLUGIN_DIR . 'rss20/rss20.png"></a>';
	}
	
	
	public function do_url()
	{
		//numはフィードに含める項目数。
		//GET引数でrecent(>0)が与えられればnumとして採用。そうでなければnum=15とする。
		$num = isset(Vars::$get['recent']) && Vars::$get['recent'] > 0 ? (int)Vars::$get['recent'] : 15;
		
		$db = DataBase::getinstance();
		$query  = "SELECT pagename,timestamp FROM page";
		//GET引数に正規表現exp(またはワイルドカードパターンinclude)が設定され、
		//かつ空でないなら、exp(またはinclude)と一致するページだけを検索。
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
			$_p = Page::getinstance($row['pagename']);
			$item['url'] = getURL($_p);
			$item['description'] = htmlspecialchars(convert_Page($_p));
			$list[] = $item;
		}
		$smarty = $this->getSmarty();
		$smarty->assign('list', $list);
		$smarty->assign('rssurl', SCRIPTURL . '?' . htmlspecialchars($_SERVER['QUERY_STRING']));
		$smarty->assign('sitename', SITENAME);
		$smarty->assign('baseurl', SCRIPTURL);
		header('Content-Type: application/xml; charset=UTF-8');
		header('Last-Modified: ' . gmdate( 'D, d M Y H:i:s', $item['timestamp'][0] ) . ' GMT' );
		$smarty->display('rss20.tpl.htm');
		exit();
	}
}

			