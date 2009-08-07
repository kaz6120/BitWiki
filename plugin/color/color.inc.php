<?php
/* 
 * $Id: color.inc.php,v 1.2 2005/06/14 10:37:46 youka Exp $
 */

class Plugin_color extends Plugin
{
	function do_inline($page, $param1, $param2)
	{
		$color = htmlspecialchars(trim($param1));
		return "<span style=\"color: $color\">" . convert_inline($param2, $page->getpagename()) . '</span>';
	}
	
	
	function do_block($page, $param1, $param2)
	{
		$color = htmlspecialchars(trim($param1));
		return "<div style=\"color: $color\">" . convert_block($param2, $page->getpagename()) . '</div>';
	}
}

?>