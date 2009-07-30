<?php
/*
 * $Id: size.inc.php,v 1.3 2005/06/14 10:40:10 youka Exp $
 */


class Plugin_size extends Plugin
{
	function do_inline($page, $param1, $param2)
	{
		$size = htmlspecialchars(trim($param1));
		return "<span style=\"font-size: $size\">" . convert_inline($param2, $page->getpagename()) . '</span>';
	}
	
	
	function do_block($page, $param1, $param2)
	{
		$size = htmlspecialchars(trim($param1));
		return "<div style=\"font-size: $size\">" . convert_block($param2, $page->getpagename()) . '</div>';
	}
}

?>