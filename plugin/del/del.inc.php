<?php
/* 
 * $Id: del.inc.php,v 1.1.1.1 2005/06/12 15:38:46 youka Exp $
 */

class Plugin_del extends Plugin
{
	function do_inline($page, $param1, $param2)
	{
		return "<del>" . convert_inline($param2, $page->getpagename()) . '</del>';
	}
}

?>