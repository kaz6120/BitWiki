<?php
/* 
 * $Id: anchor.inc.php,v 1.1.1.1 2005/06/12 15:38:45 youka Exp $
 */

class Plugin_anchor extends Plugin
{
	function do_inline($page, $param1, $param2)
	{
		$id = htmlspecialchars(trim($param1));
		$html = convert_inline($param2, $page->getpagename());
		$str = mb_eregi_replace('(?:<a\s.*?>|</a>)', '', $html);
		return "<a href=\"#$id\">" . $str . '</a>';
	}
}

?>