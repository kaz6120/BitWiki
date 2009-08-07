<?php
/* 
 * $Id: a.inc.php,v 1.1 2005/07/03 00:58:17 youka Exp $
 */

class Plugin_a extends Plugin
{
	function do_inline($page, $param1, $param2)
	{
		$url = htmlspecialchars(trim($param1));
		$html = convert_inline($param2, $page->getpagename());
		$str = mb_eregi_replace('(?:<a\s.*?>|</a>)', '', $html);
		return "<a href=\"$url\">$str</a>";
	}
	
	
	function do_inlinetag($page, $param1, $param2)
	{
		static $list = array('href', 'title', 'target');
		
		$arg = tagparam2array($param1);
		$array = array();
		foreach($list as $key){
			if(isset($arg[$key])){
				$array[] = htmlspecialchars($key) . '="' . htmlspecialchars($arg[$key]) . '"';
			}
		}
		$html = convert_inline($param2, $page->getpagename());
		$str = mb_eregi_replace('(?:<a\s.*?>|</a>)', '', $html);
		$attr = join(' ', $array);
		return "<a $attr>$str</a>";
	}
}

?>