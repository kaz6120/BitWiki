<?php
/* 
 * $Id: urlcall.inc.php,v 1.1 2005/07/12 00:03:10 youka Exp $
 */

class Plugin_urlcall extends Plugin
{
	function do_inline($page, $param1, $param2)
	{
		$url = SCRIPTURL . '?' . htmlspecialchars($param1);
		if(trim($param2) == ''){
			$alias = htmlspecialchars($url);
		}
		else{
			$alias = mb_eregi_replace('(?:<a\s.*?>|</a>)', '', convert_inline($param2, $page->getpagename()));
		}
		return "<a href=\"{$url}\">{$alias}</a>";
	}
}

?>